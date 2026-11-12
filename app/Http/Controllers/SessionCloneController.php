<?php

namespace App\Http\Controllers;

use App\Models\Schoolsession;
use App\Models\Schoolterm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Clone academic setup from one session/term into another.
 *
 * Supported modes:
 *   - Term → Term     (e.g. 1st Term 2025/2026 → 2nd Term 2025/2026)
 *   - Session → Session, one term  (e.g. 3rd Term 2024/2025 → 1st Term 2025/2026)
 *   - Session → Session, all terms (each term copied onto the same term in the new session)
 *
 * Cloning is strictly ADDITIVE: nothing in the target is updated or deleted.
 * Rows that already exist in the target are skipped, so the operation can be re-run safely.
 *
 * Every run is saved as a snapshot (session_clone_batches + session_clone_batch_items)
 * holding a copy of each inserted row, so a run can be reviewed, downloaded as JSON
 * and reverted. Revert keeps any record that has since been put to use.
 */
class SessionCloneController extends Controller
{
    /**
     * Module registry. `requires` lists modules that must also be selected.
     */
    private const MODULES = [
        'subject_teachers' => [
            'label'       => 'Subject Teachers',
            'description' => 'Teacher ↔ subject assignments',
            'icon'        => 'ri-user-star-line',
            'table'       => 'subjectteacher',
            'requires'    => [],
        ],
        'subject_classes' => [
            'label'       => 'Subject Classes',
            'description' => 'Which subject (and teacher) is offered in which class',
            'icon'        => 'ri-book-open-line',
            'table'       => 'subjectclass',
            'requires'    => ['subject_teachers'],
        ],
        'class_teachers' => [
            'label'       => 'Class Teachers',
            'description' => 'Form/class teacher for each class',
            'icon'        => 'ri-team-line',
            'table'       => 'classteacher',
            'requires'    => [],
        ],
        'compulsory_subjects' => [
            'label'       => 'Compulsory Subjects',
            'description' => 'Compulsory subjects per class with minimum grades',
            'icon'        => 'ri-shield-check-line',
            'table'       => 'compulsory_subject_classes',
            'requires'    => [],
        ],
        'school_bills' => [
            'label'       => 'School Bills per Class',
            'description' => 'Bills attached to each class for the term',
            'icon'        => 'ri-bill-line',
            'table'       => 'school_bill_class_term_session',
            'requires'    => [],
        ],
        'promotion_settings' => [
            'label'       => 'Promotion Rules',
            'description' => 'Promotion rule sets per class',
            'icon'        => 'ri-medal-line',
            'table'       => 'promotion_settings',
            'requires'    => [],
        ],
    ];

    /** @var array<string, array<int,string>> */
    private array $columnCache = [];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:View session clone')->only(['index', 'preview', 'history', 'showBatch', 'downloadBatch']);
        $this->middleware('permission:Run session clone')->only(['clone']);
        $this->middleware('permission:Revert session clone')->only(['revertBatch']);
    }

    /* ------------------------------------------------------------------ */
    /*  Pages                                                             */
    /* ------------------------------------------------------------------ */

    public function index()
    {
        $pagetitle = 'Clone Session / Term Setup';

        $sessions = Schoolsession::orderByDesc('id')->get(['id', 'session', 'status']);
        $terms    = Schoolterm::orderBy('id')->get(['id', 'term', 'status']);

        $modules = collect(self::MODULES)
            ->filter(fn ($m) => Schema::hasTable($m['table']))
            ->map(fn ($m, $key) => array_merge($m, ['key' => $key]))
            ->values();

        $currentSession = $sessions->firstWhere('status', 'Current');
        $currentTerm    = $terms->first(fn ($t) => (bool) $t->status);

        return view('sessionclone.index', compact(
            'pagetitle', 'sessions', 'terms', 'modules', 'currentSession', 'currentTerm'
        ));
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX: preview                                                     */
    /* ------------------------------------------------------------------ */

    public function preview(Request $request): JsonResponse
    {
        [$ok, $payload] = $this->validatePayload($request);
        if (!$ok) {
            return $payload;
        }

        try {
            $result = $this->run($payload, dryRun: true);

            return response()->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            Log::error('Session clone preview failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not build the preview: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX: execute                                                     */
    /* ------------------------------------------------------------------ */

    public function clone(Request $request): JsonResponse
    {
        [$ok, $payload] = $this->validatePayload($request);
        if (!$ok) {
            return $payload;
        }

        if (!$this->snapshotTablesReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Snapshot tables are missing. Run "php artisan migrate" first so every clone can be reverted.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Open a snapshot batch for this run
            $batchId = DB::table('session_clone_batches')->insertGetId([
                'user_id'           => auth()->id(),
                'mode'              => $payload['mode'],
                'source_session_id' => $payload['source_session'],
                'source_term'       => $payload['source_term_raw'],
                'target_session_id' => $payload['target_session'],
                'target_term'       => $payload['target_term_raw'],
                'modules'           => json_encode($payload['modules']),
                'status'            => 'completed',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $payload['batch_id'] = $batchId;

            // 2. Run the clone; every insert is recorded against the batch
            $result  = $this->run($payload, dryRun: false);
            $created = $result['totals']['create'];

            if ($created === 0) {
                // Nothing was written — don't keep an empty snapshot
                DB::table('session_clone_batches')->where('id', $batchId)->delete();
                $batchId = null;
            } else {
                DB::table('session_clone_batches')->where('id', $batchId)->update([
                    'totals'        => json_encode($result['totals']),
                    'module_totals' => json_encode(collect($result['modules'])->map(fn ($m) => [
                        'key' => $m['key'], 'label' => $m['label'], 'create' => $m['create'], 'skip' => $m['skip'],
                    ])->values()),
                    'updated_at'    => now(),
                ]);
            }

            DB::commit();

            Log::info('Session/term setup cloned', [
                'by'       => auth()->id(),
                'batch_id' => $batchId,
                'payload'  => $payload,
                'totals'   => $result['totals'],
            ]);

            return response()->json([
                'success'  => true,
                'batch_id' => $batchId,
                'message'  => $created > 0
                    ? "{$created} record(s) cloned successfully. A snapshot (#{$batchId}) was saved — you can undo this from Clone History."
                    : 'Nothing new to clone — the target already has everything.',
            ] + $result);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Session clone failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Cloning failed and no changes were saved: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Snapshot history & revert                                         */
    /* ------------------------------------------------------------------ */

    /**
     * List of clone runs (most recent first).
     */
    public function history(): JsonResponse
    {
        if (!$this->snapshotTablesReady()) {
            return response()->json(['success' => true, 'ready' => false, 'data' => []]);
        }

        $sessions = Schoolsession::pluck('session', 'id');
        $terms    = Schoolterm::pluck('term', 'id');

        $rows = DB::table('session_clone_batches as b')
            ->leftJoin('users as u', 'u.id', '=', 'b.user_id')
            ->leftJoin('users as r', 'r.id', '=', 'b.reverted_by')
            ->select('b.*', 'u.name as user_name', 'r.name as reverted_by_name')
            ->orderByDesc('b.id')
            ->limit(100)
            ->get();

        $remaining = DB::table('session_clone_batch_items')
            ->whereIn('batch_id', $rows->pluck('id'))
            ->whereNull('reverted_at')
            ->selectRaw('batch_id, COUNT(*) as c')
            ->groupBy('batch_id')
            ->pluck('c', 'batch_id');

        $data = $rows->map(function ($b) use ($sessions, $terms, $remaining) {
            $totals = json_decode($b->totals ?? '{}', true) ?: [];

            return [
                'id'           => $b->id,
                'created_at'   => \Carbon\Carbon::parse($b->created_at)->format('d M Y, g:i A'),
                'user'         => $b->user_name ?? 'Unknown',
                'route'        => $this->routeText($b, $sessions, $terms),
                'modules'      => collect(json_decode($b->module_totals ?? '[]', true))
                                    ->filter(fn ($m) => ($m['create'] ?? 0) > 0)
                                    ->map(fn ($m) => $m['label'] . ' (' . $m['create'] . ')')
                                    ->values(),
                'created'      => (int) ($totals['create'] ?? 0),
                'remaining'    => (int) ($remaining[$b->id] ?? 0),
                'status'       => $b->status,
                'reverted_at'  => $b->reverted_at ? \Carbon\Carbon::parse($b->reverted_at)->format('d M Y, g:i A') : null,
                'reverted_by'  => $b->reverted_by_name,
            ];
        });

        return response()->json(['success' => true, 'ready' => true, 'data' => $data]);
    }

    /**
     * Items recorded in one snapshot.
     */
    public function showBatch(int $batch): JsonResponse
    {
        $b = DB::table('session_clone_batches')->find($batch);
        if (!$b) {
            return response()->json(['success' => false, 'message' => 'Snapshot not found.'], 404);
        }

        $items = DB::table('session_clone_batch_items')
            ->where('batch_id', $batch)
            ->orderBy('id')
            ->get(['id', 'module', 'table_name', 'record_id', 'label', 'reverted_at', 'kept_reason']);

        return response()->json([
            'success' => true,
            'batch'   => ['id' => $b->id, 'status' => $b->status],
            'items'   => $items->map(fn ($i) => [
                'module'      => self::MODULES[$i->module]['label'] ?? $i->module,
                'label'       => $i->label,
                'record_id'   => $i->record_id,
                'reverted'    => (bool) $i->reverted_at,
                'kept_reason' => $i->kept_reason,
            ]),
        ]);
    }

    /**
     * Download the full snapshot (batch + every inserted row) as JSON.
     */
    public function downloadBatch(int $batch)
    {
        $b = DB::table('session_clone_batches')->find($batch);
        abort_unless($b, 404);

        $items = DB::table('session_clone_batch_items')->where('batch_id', $batch)->orderBy('id')->get();

        $payload = [
            'batch' => array_merge((array) $b, [
                'modules'        => json_decode($b->modules, true),
                'totals'         => json_decode($b->totals ?? 'null', true),
                'module_totals'  => json_decode($b->module_totals ?? 'null', true),
                'revert_summary' => json_decode($b->revert_summary ?? 'null', true),
            ]),
            'items' => $items->map(fn ($i) => array_merge((array) $i, [
                'snapshot' => json_decode($i->snapshot, true),
            ])),
            'exported_at' => now()->toDateTimeString(),
        ];

        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            "clone-snapshot-{$batch}.json",
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Revert (undo) a clone run.
     *
     * Deletes the records the run created, newest-dependency first. A record is
     * KEPT if it has been used since the clone (e.g. a subject class that now has
     * registered students or scores, or a class bill with invoices/payments).
     * Pass dry_run=1 to see what would happen without changing anything.
     */
    public function revertBatch(Request $request, int $batch): JsonResponse
    {
        if (!$this->snapshotTablesReady()) {
            return response()->json(['success' => false, 'message' => 'Snapshot tables are missing.'], 422);
        }

        $b = DB::table('session_clone_batches')->find($batch);
        if (!$b) {
            return response()->json(['success' => false, 'message' => 'Snapshot not found.'], 404);
        }
        if ($b->status === 'reverted') {
            return response()->json(['success' => false, 'message' => 'This clone has already been fully reverted.'], 422);
        }

        $dry = $request->boolean('dry_run');

        DB::beginTransaction();
        try {
            $summary = $this->performRevert($b, $dry);

            if ($dry) {
                DB::rollBack();
            } else {
                $stillThere = DB::table('session_clone_batch_items')
                    ->where('batch_id', $batch)->whereNull('reverted_at')->count();

                DB::table('session_clone_batches')->where('id', $batch)->update([
                    'status'         => $stillThere === 0 ? 'reverted' : 'partially_reverted',
                    'reverted_at'    => now(),
                    'reverted_by'    => auth()->id(),
                    'revert_summary' => json_encode($summary),
                    'updated_at'     => now(),
                ]);
                DB::commit();

                Log::info('Session clone reverted', ['batch' => $batch, 'by' => auth()->id(), 'summary' => $summary]);
            }

            $msg = $dry
                ? "{$summary['remove']} record(s) will be removed" . ($summary['keep'] ? ", {$summary['keep']} will be kept because they are now in use." : '.')
                : "{$summary['remove']} record(s) removed" . ($summary['keep'] ? ", {$summary['keep']} kept because they are in use." : '.');

            return response()->json(['success' => true, 'dry_run' => $dry, 'message' => $msg] + $summary);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Session clone revert failed', ['batch' => $batch, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Revert failed and nothing was changed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Core revert routine. Runs inside a transaction; in dry-run the caller rolls back.
     */
    private function performRevert(object $batch, bool $dry): array
    {
        $items = DB::table('session_clone_batch_items')
            ->where('batch_id', $batch->id)
            ->whereNull('reverted_at')
            ->get();

        // Dependants first: subject classes before the subject teachers they point to
        $order = ['subject_classes', 'class_teachers', 'compulsory_subjects', 'school_bills', 'promotion_settings', 'subject_teachers'];
        $items = $items->sortBy(fn ($i) => array_search($i->module, $order, true) === false ? 99 : array_search($i->module, $order, true));

        $refColumns = $this->referenceColumns();
        $out = ['remove' => 0, 'keep' => 0, 'missing' => 0, 'modules' => [], 'kept' => []];
        // ids removed (or, in dry-run, that would be removed) during this pass, per table
        $removed = [];

        foreach ($items as $item) {
            $label = self::MODULES[$item->module]['label'] ?? $item->module;
            $out['modules'][$item->module] ??= ['label' => $label, 'remove' => 0, 'keep' => 0];

            $exists = DB::table($item->table_name)->where('id', $item->record_id)->exists();
            if (!$exists) {
                // Already deleted manually — just mark it done
                $out['missing']++;
                if (!$dry) {
                    DB::table('session_clone_batch_items')->where('id', $item->id)
                        ->update(['reverted_at' => now(), 'kept_reason' => null, 'updated_at' => now()]);
                }
                continue;
            }

            $reason = $this->inUseReason($item, $refColumns, $removed);

            if ($reason !== null) {
                $out['keep']++;
                $out['modules'][$item->module]['keep']++;
                if (count($out['kept']) < 100) {
                    $out['kept'][] = ['label' => $item->label, 'module' => $label, 'reason' => $reason];
                }
                if (!$dry) {
                    DB::table('session_clone_batch_items')->where('id', $item->id)
                        ->update(['kept_reason' => $reason, 'updated_at' => now()]);
                }
                continue;
            }

            // Dry-run never touches the real rows (some legacy tables may not be
            // transactional); later dependency checks use $removed instead.
            $removed[$item->table_name][] = $item->record_id;

            if (!$dry) {
                DB::table($item->table_name)->where('id', $item->record_id)->delete();
                DB::table('session_clone_batch_items')->where('id', $item->id)
                    ->update(['reverted_at' => now(), 'kept_reason' => null, 'updated_at' => now()]);
            }

            $out['remove']++;
            $out['modules'][$item->module]['remove']++;
        }

        $out['modules'] = array_values($out['modules']);

        return $out;
    }

    /**
     * Returns a reason string if the cloned record is now referenced elsewhere, otherwise null.
     */
    private function inUseReason(object $item, array $refColumns, array $removed = []): ?string
    {
        $id = $item->record_id;

        switch ($item->table_name) {
            case 'subjectclass':
                foreach ($refColumns['subjectclass'] as [$table, $col]) {
                    if (DB::table($table)->where($col, $id)->exists()) {
                        return "In use — has records in {$table}";
                    }
                }
                return null;

            case 'subjectteacher':
                foreach ($refColumns['subjectteacher'] as [$table, $col]) {
                    $q = DB::table($table)->where($col, $id);
                    if (!empty($removed[$table])) {
                        $q->whereNotIn('id', $removed[$table]); // ignore rows this revert removes
                    }
                    if ($q->exists()) {
                        return $table === 'subjectclass'
                            ? 'Still linked to a subject class that is in use'
                            : "In use — has records in {$table}";
                    }
                }
                return null;

            case 'school_bill_class_term_session':
                $snap = json_decode($item->snapshot, true) ?: [];
                foreach (['student_bill_payment', 'student_bill_invoice'] as $t) {
                    if (Schema::hasTable($t) && DB::table($t)
                            ->where('school_bill_id', $snap['bill_id'] ?? 0)
                            ->where('class_id', $snap['class_id'] ?? 0)
                            ->where('termid_id', $snap['termid_id'] ?? 0)
                            ->where('session_id', $snap['session_id'] ?? 0)
                            ->exists()) {
                        return $t === 'student_bill_payment'
                            ? 'In use — payments have been recorded against this bill'
                            : 'In use — invoices have been generated for this bill';
                    }
                }
                return null;

            case 'promotion_settings':
                foreach ($refColumns['promotion_settings'] as [$table, $col]) {
                    if (DB::table($table)->where($col, $id)->exists()) {
                        return "In use — referenced by {$table}";
                    }
                }
                return null;

            default:
                return null; // class teachers, compulsory subjects: safe to remove
        }
    }

    /**
     * Discover every column in the database that points at a subject class,
     * subject teacher or promotion setting (broadsheets, registrations, locks,
     * lesson notes, exam papers, …) so new modules are covered automatically.
     */
    private function referenceColumns(): array
    {
        $map = [
            'subjectclass'       => ['subjectclass_id', 'subjectclassid'],
            'subjectteacher'     => ['subjectteacherid', 'subjectteacher_id'],
            'promotion_settings' => ['promotion_setting_id', 'promotion_settings_id'],
        ];

        $all = collect(DB::select(
            'SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()'
        ));

        $out = [];
        foreach ($map as $target => $cols) {
            $out[$target] = $all
                ->filter(fn ($r) => in_array(strtolower($r->c), $cols, true)
                    && $r->t !== $target
                    && !str_starts_with($r->t, 'session_clone_'))
                ->map(fn ($r) => [$r->t, $r->c])
                ->values()
                ->all();
        }

        return $out;
    }

    private function snapshotTablesReady(): bool
    {
        return Schema::hasTable('session_clone_batches') && Schema::hasTable('session_clone_batch_items');
    }

    private function routeText(object $b, $sessions, $terms): string
    {
        $ss = $sessions[$b->source_session_id] ?? ('Session ' . $b->source_session_id);
        $ts = $sessions[$b->target_session_id] ?? ('Session ' . $b->target_session_id);

        if ($b->source_term === 'all') {
            return "{$ss} → {$ts} (all terms)";
        }

        $st = $terms[$b->source_term] ?? ('Term ' . $b->source_term);
        $tt = $terms[$b->target_term] ?? ('Term ' . $b->target_term);

        return "{$st} {$ss} → {$tt} {$ts}";
    }

    /* ------------------------------------------------------------------ */
    /*  Validation                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{0:bool,1:mixed}
     */
    private function validatePayload(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'source_session' => 'required|exists:schoolsession,id',
            'source_term'    => 'required',
            'target_session' => 'required|exists:schoolsession,id',
            'target_term'    => 'nullable',
            'modules'        => 'required|array|min:1',
            'modules.*'      => 'in:' . implode(',', array_keys(self::MODULES)),
        ], [
            'modules.required' => 'Select at least one item to clone.',
            'modules.min'      => 'Select at least one item to clone.',
        ]);

        if ($validator->fails()) {
            return [false, response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422)];
        }

        $allTerms     = $request->input('source_term') === 'all';
        $validTermIds = Schoolterm::pluck('id')->map(fn ($id) => (string) $id)->all();

        if (!$allTerms && !in_array((string) $request->input('source_term'), $validTermIds, true)) {
            return [false, response()->json(['success' => false, 'message' => 'Select a valid source term.'], 422)];
        }

        if (!$allTerms && !in_array((string) $request->input('target_term'), $validTermIds, true)) {
            return [false, response()->json(['success' => false, 'message' => 'Select a valid target term.'], 422)];
        }

        // Build list of [sourceTerm => targetTerm] pairs
        $pairs = $allTerms
            ? array_combine($validTermIds, $validTermIds)
            : [(string) $request->input('source_term') => (string) $request->input('target_term')];

        $sourceSession = (string) $request->input('source_session');
        $targetSession = (string) $request->input('target_session');

        foreach ($pairs as $s => $t) {
            if ($sourceSession === $targetSession && (string) $s === (string) $t) {
                return [false, response()->json([
                    'success' => false,
                    'message' => 'Source and target are the same session and term. Choose a different target.',
                ], 422)];
            }
        }

        // Auto-include dependencies (e.g. subject classes need subject teachers)
        $modules = array_values(array_unique($request->input('modules')));
        foreach ($modules as $m) {
            foreach (self::MODULES[$m]['requires'] as $req) {
                if (!in_array($req, $modules, true)) {
                    $modules[] = $req;
                }
            }
        }
        // Keep registry order so dependencies run first
        $modules = array_values(array_filter(array_keys(self::MODULES), fn ($k) => in_array($k, $modules, true)));
        $modules = array_values(array_filter($modules, fn ($k) => Schema::hasTable(self::MODULES[$k]['table'])));

        $mode = $allTerms ? 'session_all' : ($sourceSession === $targetSession ? 'term' : 'session');

        return [true, [
            'mode'            => $mode,
            'source_session'  => $sourceSession,
            'target_session'  => $targetSession,
            'source_term_raw' => $allTerms ? 'all' : (string) $request->input('source_term'),
            'target_term_raw' => $allTerms ? null : (string) $request->input('target_term'),
            'pairs'           => $pairs,
            'modules'         => $modules,
        ]];
    }

    /* ------------------------------------------------------------------ */
    /*  Engine                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Runs every selected module for every term pair.
     * In dry-run mode nothing is written; counts and sample rows are returned.
     */
    private function run(array $p, bool $dryRun): array
    {
        $summary = [];
        foreach ($p['modules'] as $m) {
            $summary[$m] = [
                'key'     => $m,
                'label'   => self::MODULES[$m]['label'],
                'icon'    => self::MODULES[$m]['icon'],
                'source'  => 0,
                'create'  => 0,
                'skip'    => 0,
                'samples' => [],
                'notes'   => [],
            ];
        }

        foreach ($p['pairs'] as $srcTerm => $tgtTerm) {
            $ctx = [
                'src_session' => $p['source_session'],
                'src_term'    => (string) $srcTerm,
                'tgt_session' => $p['target_session'],
                'tgt_term'    => (string) $tgtTerm,
                'dry'         => $dryRun,
                'batch_id'    => $p['batch_id'] ?? null,
                // old subjectteacher id => new (or already-existing) subjectteacher id
                'st_map'      => [],
                // dry-run placeholders for subjectteacher rows that would be created
                'st_virtual'  => [],
            ];

            foreach ($p['modules'] as $m) {
                $method = 'clone' . str_replace('_', '', ucwords($m, '_'));
                $res    = $this->{$method}($ctx);

                $summary[$m]['source'] += $res['source'];
                $summary[$m]['create'] += $res['create'];
                $summary[$m]['skip']   += $res['skip'];
                $summary[$m]['notes']   = array_values(array_unique(array_merge($summary[$m]['notes'], $res['notes'] ?? [])));

                if (count($summary[$m]['samples']) < 60) {
                    $summary[$m]['samples'] = array_slice(
                        array_merge($summary[$m]['samples'], $res['samples']),
                        0,
                        60
                    );
                }
            }
        }

        $totals = ['source' => 0, 'create' => 0, 'skip' => 0];
        foreach ($summary as $s) {
            $totals['source'] += $s['source'];
            $totals['create'] += $s['create'];
            $totals['skip']   += $s['skip'];
        }

        return [
            'modules' => array_values($summary),
            'totals'  => $totals,
            'dry_run' => $dryRun,
        ];
    }

    /* ---------------------------- Modules ----------------------------- */

    private function cloneSubjectTeachers(array &$ctx): array
    {
        $rows = DB::table('subjectteacher as st')
            ->leftJoin('users as u', 'u.id', '=', 'st.staffid')
            ->leftJoin('subject as s', 's.id', '=', 'st.subjectid')
            ->where('st.sessionid', $ctx['src_session'])
            ->where('st.termid', $ctx['src_term'])
            ->select('st.*', 'u.name as _staff', 's.subject as _subject')
            ->get();

        $existing = DB::table('subjectteacher')
            ->where('sessionid', $ctx['tgt_session'])
            ->where('termid', $ctx['tgt_term'])
            ->get(['id', 'staffid', 'subjectid'])
            ->keyBy(fn ($r) => $r->staffid . '|' . $r->subjectid);

        $out = ['source' => $rows->count(), 'create' => 0, 'skip' => 0, 'samples' => []];

        foreach ($rows as $row) {
            $key   = $row->staffid . '|' . $row->subjectid;
            $label = trim(($row->_staff ?? 'Unknown staff') . ' — ' . ($row->_subject ?? 'Unknown subject'));

            if ($existing->has($key)) {
                $ctx['st_map'][$row->id] = $existing->get($key)->id;
                $out['skip']++;
                $out['samples'][] = ['text' => $label, 'status' => 'exists', 'term' => $ctx['tgt_term']];
                continue;
            }

            if ($ctx['dry']) {
                $ctx['st_map'][$row->id]     = 'virtual:' . $row->id;
                $ctx['st_virtual'][$row->id] = true;
            } else {
                $data = $this->copyRow('subjectteacher', $row, [
                    'sessionid' => $ctx['tgt_session'],
                    'termid'    => $ctx['tgt_term'],
                ]);
                $newId = DB::table('subjectteacher')->insertGetId($data);
                $this->track($ctx, 'subject_teachers', 'subjectteacher', $newId, $data, $label);
                $ctx['st_map'][$row->id] = $newId;
                $existing->put($key, (object) ['id' => $newId, 'staffid' => $row->staffid, 'subjectid' => $row->subjectid]);
            }

            $out['create']++;
            $out['samples'][] = ['text' => $label, 'status' => 'new', 'term' => $ctx['tgt_term']];
        }

        return $out;
    }

    private function cloneSubjectClasses(array &$ctx): array
    {
        $sourceStIds = DB::table('subjectteacher')
            ->where('sessionid', $ctx['src_session'])
            ->where('termid', $ctx['src_term'])
            ->pluck('id');

        $rows = DB::table('subjectclass as sc')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'sc.schoolclassid')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->leftJoin('subject as s', 's.id', '=', 'sc.subjectid')
            ->whereIn('sc.subjectteacherid', $sourceStIds)
            ->select('sc.*', 'c.schoolclass as _class', 'a.arm as _arm', 's.subject as _subject')
            ->get();

        $out = ['source' => $rows->count(), 'create' => 0, 'skip' => 0, 'samples' => [], 'notes' => []];

        // Existing pairs in target, keyed by class|subjectteacher
        $realTargetStIds = array_filter($ctx['st_map'], fn ($v) => is_int($v) || ctype_digit((string) $v));
        $existing = DB::table('subjectclass')
            ->whereIn('subjectteacherid', array_values($realTargetStIds))
            ->get(['schoolclassid', 'subjectteacherid'])
            ->mapWithKeys(fn ($r) => [$r->schoolclassid . '|' . $r->subjectteacherid => true]);

        $hasTerm      = $this->hasColumn('subjectclass', 'termid');
        $hasSessionId = $this->hasColumn('subjectclass', 'sessionid');
        $hasSession   = $this->hasColumn('subjectclass', 'session');

        foreach ($rows as $row) {
            $label = trim(($row->_class ?? 'Class') . ' ' . ($row->_arm ?? '')) . ' — ' . ($row->_subject ?? 'Subject');

            $newStId = $ctx['st_map'][$row->subjectteacherid] ?? null;
            if ($newStId === null) {
                $out['skip']++;
                $out['notes'][] = 'Some subject classes were skipped because their subject teacher could not be mapped.';
                continue;
            }

            $key = $row->schoolclassid . '|' . $newStId;
            if (isset($existing[$key])) {
                $out['skip']++;
                $out['samples'][] = ['text' => $label, 'status' => 'exists', 'term' => $ctx['tgt_term']];
                continue;
            }

            if (!$ctx['dry']) {
                $overrides = ['subjectteacherid' => $newStId];
                if ($hasTerm) {
                    $overrides['termid'] = $ctx['tgt_term'];
                }
                if ($hasSessionId) {
                    $overrides['sessionid'] = $ctx['tgt_session'];
                }
                if ($hasSession) {
                    $overrides['session'] = $ctx['tgt_session'];
                }
                // Fresh assignment: teacher editing re-enabled, lock metadata cleared
                foreach (['teacher_editing_enabled' => 1, 'teacher_editing_disabled_at' => null, 'teacher_editing_disabled_by' => null] as $col => $val) {
                    if ($this->hasColumn('subjectclass', $col)) {
                        $overrides[$col] = $val;
                    }
                }

                $data  = $this->copyRow('subjectclass', $row, $overrides);
                $newId = DB::table('subjectclass')->insertGetId($data);
                $this->track($ctx, 'subject_classes', 'subjectclass', $newId, $data, $label);
            }

            $existing[$key] = true;
            $out['create']++;
            $out['samples'][] = ['text' => $label, 'status' => 'new', 'term' => $ctx['tgt_term']];
        }

        return $out;
    }

    private function cloneClassTeachers(array &$ctx): array
    {
        $rows = DB::table('classteacher as ct')
            ->leftJoin('users as u', 'u.id', '=', 'ct.staffid')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'ct.schoolclassid')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->where('ct.sessionid', $ctx['src_session'])
            ->where('ct.termid', $ctx['src_term'])
            ->select('ct.*', 'u.name as _staff', 'c.schoolclass as _class', 'a.arm as _arm')
            ->get();

        // A class may only have one class teacher per term/session — skip if already set
        $taken = DB::table('classteacher')
            ->where('sessionid', $ctx['tgt_session'])
            ->where('termid', $ctx['tgt_term'])
            ->pluck('schoolclassid')
            ->map(fn ($v) => (string) $v)
            ->flip();

        $out = ['source' => $rows->count(), 'create' => 0, 'skip' => 0, 'samples' => []];

        foreach ($rows as $row) {
            $label = trim(($row->_class ?? 'Class') . ' ' . ($row->_arm ?? '')) . ' — ' . ($row->_staff ?? 'Unknown staff');

            if (isset($taken[(string) $row->schoolclassid])) {
                $out['skip']++;
                $out['samples'][] = ['text' => $label, 'status' => 'exists', 'term' => $ctx['tgt_term']];
                continue;
            }

            if (!$ctx['dry']) {
                $data  = $this->copyRow('classteacher', $row, [
                    'sessionid' => $ctx['tgt_session'],
                    'termid'    => $ctx['tgt_term'],
                ]);
                $newId = DB::table('classteacher')->insertGetId($data);
                $this->track($ctx, 'class_teachers', 'classteacher', $newId, $data, $label);
            }

            $taken[(string) $row->schoolclassid] = true;
            $out['create']++;
            $out['samples'][] = ['text' => $label, 'status' => 'new', 'term' => $ctx['tgt_term']];
        }

        return $out;
    }

    private function cloneCompulsorySubjects(array &$ctx): array
    {
        $rows = DB::table('compulsory_subject_classes as cs')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'cs.schoolclassid')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->leftJoin('subject as s', 's.id', '=', 'cs.subjectId')
            ->where('cs.sessionid', $ctx['src_session'])
            ->where('cs.termid', $ctx['src_term'])
            ->select('cs.*', 'c.schoolclass as _class', 'a.arm as _arm', 's.subject as _subject')
            ->get();

        $existing = DB::table('compulsory_subject_classes')
            ->where('sessionid', $ctx['tgt_session'])
            ->where('termid', $ctx['tgt_term'])
            ->get(['schoolclassid', 'subjectId'])
            ->mapWithKeys(fn ($r) => [$r->schoolclassid . '|' . $r->subjectId => true]);

        return $this->simpleClone(
            $ctx, 'compulsory_subjects', $rows, 'compulsory_subject_classes',
            fn ($r) => $r->schoolclassid . '|' . $r->subjectId,
            $existing,
            ['sessionid' => $ctx['tgt_session'], 'termid' => $ctx['tgt_term']],
            fn ($r) => trim(($r->_class ?? 'Class') . ' ' . ($r->_arm ?? '')) . ' — ' . ($r->_subject ?? 'Subject')
                . ($r->min_grade ? " (min {$r->min_grade})" : '')
        );
    }

    private function cloneSchoolBills(array &$ctx): array
    {
        $q = DB::table('school_bill_class_term_session as b')
            ->leftJoin('school_bill as sb', 'sb.id', '=', 'b.bill_id')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'b.class_id')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->where('b.session_id', $ctx['src_session'])
            ->where('b.termid_id', $ctx['src_term']);

        // Never copy soft-deleted class bills, or bills that have themselves been deleted
        if ($this->hasColumn('school_bill_class_term_session', 'deleted_at')) {
            $q->whereNull('b.deleted_at');
        }
        if (Schema::hasColumn('school_bill', 'deleted_at')) {
            $q->whereNull('sb.deleted_at');
        }

        $rows = $q->select('b.*', 'sb.title as _bill', 'sb.bill_amount as _amount', 'c.schoolclass as _class', 'a.arm as _arm')
            ->get();

        // Includes soft-deleted rows on purpose: the unique index (bill, class, term, session) covers them too
        $existing = DB::table('school_bill_class_term_session')
            ->where('session_id', $ctx['tgt_session'])
            ->where('termid_id', $ctx['tgt_term'])
            ->get(['bill_id', 'class_id'])
            ->mapWithKeys(fn ($r) => [$r->bill_id . '|' . $r->class_id => true]);

        $overrides = ['session_id' => $ctx['tgt_session'], 'termid_id' => $ctx['tgt_term']];
        if ($this->hasColumn('school_bill_class_term_session', 'created_by')) {
            $overrides['created_by'] = auth()->id();
        }

        return $this->simpleClone(
            $ctx, 'school_bills', $rows, 'school_bill_class_term_session',
            fn ($r) => $r->bill_id . '|' . $r->class_id,
            $existing,
            $overrides,
            fn ($r) => trim(($r->_class ?? 'Class') . ' ' . ($r->_arm ?? '')) . ' — ' . ($r->_bill ?? 'Bill')
                . ($r->_amount !== null ? ' (₦' . number_format((float) $r->_amount, 2) . ')' : '')
        );
    }

    private function clonePromotionSettings(array &$ctx): array
    {
        $rows = DB::table('promotion_settings as p')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'p.schoolclass_id')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->where('p.session_id', $ctx['src_session'])
            ->where('p.term_id', $ctx['src_term'])
            ->select('p.*', 'c.schoolclass as _class', 'a.arm as _arm')
            ->get();

        $existing = DB::table('promotion_settings')
            ->where('session_id', $ctx['tgt_session'])
            ->where('term_id', $ctx['tgt_term'])
            ->get(['schoolclass_id', 'rule_set_name'])
            ->mapWithKeys(fn ($r) => [$r->schoolclass_id . '|' . strtolower((string) $r->rule_set_name) => true]);

        return $this->simpleClone(
            $ctx, 'promotion_settings', $rows, 'promotion_settings',
            fn ($r) => $r->schoolclass_id . '|' . strtolower((string) $r->rule_set_name),
            $existing,
            ['session_id' => $ctx['tgt_session'], 'term_id' => $ctx['tgt_term']],
            fn ($r) => trim(($r->_class ?? 'All classes') . ' ' . ($r->_arm ?? '')) . ' — ' . ($r->rule_set_name ?: 'Default rules')
        );
    }

    /* ---------------------------- Helpers ----------------------------- */

    /**
     * Generic "copy rows that don't exist yet" routine.
     */
    private function simpleClone(
        array $ctx,
        string $module,
        $rows,
        string $table,
        callable $keyFn,
        $existing,
        array $overrides,
        callable $labelFn
    ): array {
        $out  = ['source' => $rows->count(), 'create' => 0, 'skip' => 0, 'samples' => []];
        $seen = $existing->all();

        foreach ($rows as $row) {
            $key   = $keyFn($row);
            $label = $labelFn($row);

            if (isset($seen[$key])) {
                $out['skip']++;
                $out['samples'][] = ['text' => $label, 'status' => 'exists', 'term' => $ctx['tgt_term']];
                continue;
            }

            if (!$ctx['dry']) {
                $data  = $this->copyRow($table, $row, $overrides);
                $newId = DB::table($table)->insertGetId($data);
                $this->track($ctx, $module, $table, $newId, $data, $label);
            }

            $seen[$key] = true;
            $out['create']++;
            $out['samples'][] = ['text' => $label, 'status' => 'new', 'term' => $ctx['tgt_term']];
        }

        return $out;
    }

    /**
     * Copy a DB row to an insertable array: only real table columns,
     * drops id/timestamps, applies overrides, stamps fresh timestamps.
     */
    private function copyRow(string $table, object $row, array $overrides): array
    {
        $cols = $this->columns($table);
        $data = [];

        foreach ((array) $row as $col => $val) {
            if (str_starts_with($col, '_') || in_array($col, ['id', 'created_at', 'updated_at'], true)) {
                continue;
            }
            if (in_array($col, $cols, true)) {
                $data[$col] = $val;
            }
        }

        foreach ($overrides as $col => $val) {
            if (in_array($col, $cols, true)) {
                $data[$col] = $val;
            }
        }

        $now = now();
        if (in_array('created_at', $cols, true)) {
            $data['created_at'] = $now;
        }
        if (in_array('updated_at', $cols, true)) {
            $data['updated_at'] = $now;
        }

        return $data;
    }

    /**
     * Record an inserted row against the current snapshot batch.
     */
    private function track(array $ctx, string $module, string $table, int $id, array $data, string $label): void
    {
        if (empty($ctx['batch_id'])) {
            return;
        }

        DB::table('session_clone_batch_items')->insert([
            'batch_id'   => $ctx['batch_id'],
            'module'     => $module,
            'table_name' => $table,
            'record_id'  => $id,
            'label'      => mb_substr($label, 0, 250),
            'snapshot'   => json_encode(array_merge(['id' => $id], $data), JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function columns(string $table): array
    {
        return $this->columnCache[$table] ??= Schema::getColumnListing($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }
}
