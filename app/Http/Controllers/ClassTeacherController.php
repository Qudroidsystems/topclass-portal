<?php

namespace App\Http\Controllers;

use App\Models\ClassTeacher;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ClassTeacherController extends Controller
{
    public function __construct()
    {
        // Read endpoints
        $this->middleware('permission:View class-teacher', [
            'only' => ['index', 'data', 'stats', 'show', 'assignments'],
        ]);

        // Write endpoints
        $this->middleware('permission:Create class-teacher', ['only' => ['store']]);
        $this->middleware('permission:Update class-teacher', ['only' => ['update']]);
        $this->middleware('permission:Delete class-teacher', ['only' => ['destroy', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = 'Class Teacher Management';

        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select([
                'schoolclass.id as id',
                'schoolarm.arm as schoolarm',
                'schoolclass.schoolclass as schoolclass',
            ])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        $subjectteachers = User::whereHas('roles', function ($q) {
            $q->where('name', '!=', 'Student');
        })->orderBy('name')->get(['users.id as userid', 'users.name as name', 'users.avatar as avatar']);

        $schoolterms    = Schoolterm::orderBy('term')->get();
        $schoolsessions = Schoolsession::orderByDesc('session')->get();

        return view('classteacher.index')->with(compact(
            'schoolclass', 'subjectteachers', 'schoolterms', 'schoolsessions', 'pagetitle'
        ));
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

    public function data(Request $request)
    {
        $assignments = ClassTeacher::query()
            ->leftJoin('users',        'users.id',        '=', 'classteacher.staffid')
            ->leftJoin('schoolclass',  'schoolclass.id',  '=', 'classteacher.schoolclassid')
            ->leftJoin('schoolarm',    'schoolarm.id',    '=', 'schoolclass.arm')
            ->leftJoin('schoolterm',   'schoolterm.id',   '=', 'classteacher.termid')
            ->leftJoin('schoolsession','schoolsession.id','=', 'classteacher.sessionid')
            ->select([
                'classteacher.id              as id',
                'classteacher.staffid         as staffid',
                'classteacher.schoolclassid   as schoolclassid',
                'classteacher.termid          as termid',
                'classteacher.sessionid       as sessionid',
                'users.name                   as staffname',
                'users.avatar                 as avatar',
                'schoolclass.schoolclass      as schoolclass',
                'schoolarm.arm                as schoolarm',
                'schoolterm.term              as term',
                'schoolsession.session        as session',
                'classteacher.updated_at      as updated_at',
            ]);

        return DataTables::of($assignments)
            ->addIndexColumn()

            // ── Teacher cell ──────────────────────────────────────────────
            ->addColumn('teacher_info', function ($row) {
                $staffname = $this->cleanUtf8String($row->staffname ?? 'Unknown');

                $defaultUrl = asset('storage/staff_avatars/unnamed.jpg');
                $avatarUrl  = $defaultUrl;
                $hasImage   = false;

                $avatar = trim($row->avatar ?? '');
                if ($avatar !== '' && !in_array($avatar, ['unnamed.jpg', 'unnamed.png'], true)) {
                    $resolved = $this->resolveAvatar($avatar);
                    if ($resolved) {
                        $avatarUrl = $resolved;
                        $hasImage  = true;
                    }
                }

                $dataAttrs = sprintf(
                    'data-staffname="%s" data-image="%s" data-has-image="%s"',
                    e($staffname),
                    e($avatarUrl),
                    $hasImage ? 'true' : 'false'
                );

                if ($hasImage) {
                    $avatarHtml = sprintf(
                        '<img src="%s" alt="%s" class="teacher-avatar ct-avatar-trigger" %s onerror="this.onerror=null;this.src=\'%s\'">',
                        e($avatarUrl), e($staffname), $dataAttrs, e($defaultUrl)
                    );
                } else {
                    $words    = preg_split('/\s+/', trim($staffname));
                    $initials = implode('', array_map(
                        fn ($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'),
                        array_slice($words, 0, 2)
                    ));
                    $avatarHtml = sprintf(
                        '<div class="avatar-initials ct-avatar-trigger" %s>%s</div>',
                        $dataAttrs, e($initials)
                    );
                }

                return '<div class="d-flex align-items-center gap-2">'
                    . $avatarHtml
                    . '<span class="fw-semibold text-dark">' . e($staffname) . '</span>'
                    . '</div>';
            })

            ->addColumn('class_info', function ($row) {
                $text = trim($this->cleanUtf8String($row->schoolclass ?? '') . ' ' . $this->cleanUtf8String($row->schoolarm ?? ''));
                return '<span class="ct-badge ct-badge-class">' . e($text) . '</span>';
            })

            ->addColumn('term', function ($row) {
                return '<span class="ct-badge ct-badge-term">' . e($this->cleanUtf8String($row->term ?? '')) . '</span>';
            })

            ->addColumn('session', function ($row) {
                return '<span class="ct-badge ct-badge-session">' . e($this->cleanUtf8String($row->session ?? '')) . '</span>';
            })

            ->addColumn('formatted_date', function ($row) {
                if (!$row->updated_at) return '<span class="text-muted small">—</span>';
                return '<small class="text-muted">' . \Carbon\Carbon::parse($row->updated_at)->format('d M Y') . '</small>';
            })

            ->addColumn('action', function ($row) {
                $title = e(trim(
                    $this->cleanUtf8String($row->staffname ?? '') . ' — '
                    . $this->cleanUtf8String($row->schoolclass ?? '') . ' '
                    . $this->cleanUtf8String($row->schoolarm ?? '')
                ));

                $buttons = '<div class="d-flex gap-1">';

                if (auth()->user()->can('Update class-teacher')) {
                    $buttons .= sprintf(
                        '<button class="btn btn-sm btn-outline-secondary edit-assignment" title="Edit" '
                        . 'data-id="%s" data-staffid="%s" data-termid="%s" data-sessionid="%s">'
                        . '<i class="ph-pencil"></i></button>',
                        $row->id, $row->staffid, $row->termid, $row->sessionid
                    );
                }

                if (auth()->user()->can('Delete class-teacher')) {
                    $buttons .= sprintf(
                        '<button class="btn btn-sm btn-outline-danger delete-assignment" title="Delete" '
                        . 'data-id="%s" data-title="%s"><i class="ph-trash"></i></button>',
                        $row->id, $title
                    );
                }

                return $buttons . '</div>';
            })

            // Prevent Yajra from generating WHERE clauses against computed columns
            ->filterColumn('teacher_info',   fn ($q, $kw) => null)
            ->filterColumn('class_info',     fn ($q, $kw) => null)
            ->filterColumn('term',           fn ($q, $kw) => null)
            ->filterColumn('session',        fn ($q, $kw) => null)
            ->filterColumn('formatted_date', fn ($q, $kw) => null)
            ->orderColumn('formatted_date',  fn ($q, $dir) => $q->orderBy('classteacher.updated_at', $dir))

            ->rawColumns(['teacher_info', 'class_info', 'term', 'session', 'formatted_date', 'action'])
            ->make(true);
    }

    // =========================================================================
    // STATS
    // =========================================================================

    public function stats()
    {
        try {
            return response()->json([
                'stats' => [
                    'total'           => ClassTeacher::count(),
                    'unique_teachers' => ClassTeacher::query()->distinct()->count('staffid'),
                    'unique_classes'  => ClassTeacher::query()->distinct()->count('schoolclassid'),
                    'active_sessions' => ClassTeacher::query()->distinct()->count('sessionid'),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ClassTeacher stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'unique_teachers' => 0, 'unique_classes' => 0, 'active_sessions' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staffid'         => 'required|exists:users,id',
            'schoolclassid'   => 'required|array|min:1',
            'schoolclassid.*' => 'exists:schoolclass,id',
            'termid'          => 'required|exists:schoolterm,id',
            'sessionid'       => 'required|exists:schoolsession,id',
        ], [
            'staffid.required'       => 'Please select a teacher.',
            'staffid.exists'         => 'Selected teacher does not exist.',
            'schoolclassid.required' => 'Please select at least one class.',
            'schoolclassid.min'      => 'Please select at least one class.',
            'schoolclassid.*.exists' => 'One or more selected classes do not exist.',
            'termid.required'        => 'Please select a term.',
            'termid.exists'          => 'Selected term does not exist.',
            'sessionid.required'     => 'Please select a session.',
            'sessionid.exists'       => 'Selected session does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $staffId   = (int) $request->input('staffid');
        $termId    = (int) $request->input('termid');
        $sessionId = (int) $request->input('sessionid');
        $classIds  = (array) $request->input('schoolclassid', []);

        $createdRecords = [];
        $duplicateNames = [];
        $conflictNames  = [];
        $failedIds      = [];

        // Preload class names for messages
        $classNames = Schoolclass::whereIn('id', $classIds)->pluck('schoolclass', 'id')->toArray();

        DB::beginTransaction();
        try {
            foreach ($classIds as $classId) {
                $classId = (int) $classId;

                $dup = ClassTeacher::where('staffid', $staffId)
                    ->where('schoolclassid', $classId)
                    ->where('termid', $termId)
                    ->where('sessionid', $sessionId)
                    ->exists();

                if ($dup) {
                    $duplicateNames[] = $classNames[$classId] ?? $classId;
                    continue;
                }

                $conflict = ClassTeacher::where('schoolclassid', $classId)
                    ->where('termid', $termId)
                    ->where('sessionid', $sessionId)
                    ->where('staffid', '!=', $staffId)
                    ->exists();

                if ($conflict) {
                    $conflictNames[] = $classNames[$classId] ?? $classId;
                    continue;
                }

                try {
                    $createdRecords[] = ClassTeacher::create([
                        'staffid'       => $staffId,
                        'schoolclassid' => $classId,
                        'termid'        => $termId,
                        'sessionid'     => $sessionId,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('ClassTeacher store row failed', [
                        'classid' => $classId,
                        'error'   => $e->getMessage(),
                    ]);
                    $failedIds[] = $classNames[$classId] ?? $classId;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClassTeacher store transaction failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not save assignments: ' . $e->getMessage(),
            ], 500);
        }

        if (empty($createdRecords) && empty($failedIds)) {
            $msg = 'No assignments created.';
            if ($duplicateNames) {
                $msg = 'Already assigned: ' . implode(', ', $duplicateNames) . '.';
            } elseif ($conflictNames) {
                $msg = 'These classes already have another teacher: ' . implode(', ', $conflictNames) . '.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        $msg = count($createdRecords) . ' class teacher assignment(s) added successfully.';
        $skipped = array_merge($duplicateNames, $conflictNames, $failedIds);
        if (!empty($skipped)) {
            $msg .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }

        Log::info('ClassTeacher store', ['created' => count($createdRecords), 'skipped' => count($skipped)]);

        return response()->json([
            'success' => !empty($createdRecords),
            'message' => $msg,
            'data'    => $createdRecords,
        ], !empty($createdRecords) ? 201 : 422);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'staffid'         => 'required|exists:users,id',
            'schoolclassid'   => 'required|array|min:1',
            'schoolclassid.*' => 'exists:schoolclass,id',
            'termid'          => 'required|exists:schoolterm,id',
            'sessionid'       => 'required|exists:schoolsession,id',
        ], [
            'staffid.required'       => 'Please select a teacher.',
            'staffid.exists'         => 'Selected teacher does not exist.',
            'schoolclassid.required' => 'Please select at least one class.',
            'schoolclassid.min'      => 'Please select at least one class.',
            'schoolclassid.*.exists' => 'One or more selected classes do not exist.',
            'termid.required'        => 'Please select a term.',
            'termid.exists'          => 'Selected term does not exist.',
            'sessionid.required'     => 'Please select a session.',
            'sessionid.exists'       => 'Selected session does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $primary = ClassTeacher::find($id);
        if (!$primary) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }

        $newStaffId   = (int) $request->input('staffid');
        $newTermId    = (int) $request->input('termid');
        $newSessionId = (int) $request->input('sessionid');
        $newClassIds  = array_map('intval', (array) $request->input('schoolclassid', []));

        // All rows we will REPLACE:
        //   - the original (staff, term, session) group
        //   - the new (staff, term, session) group (if the teacher changed)
        $originalGroupIds = ClassTeacher::where('staffid',   $primary->staffid)
            ->where('termid',    $primary->termid)
            ->where('sessionid', $primary->sessionid)
            ->pluck('id')->toArray();

        $newGroupIds = ClassTeacher::where('staffid',   $newStaffId)
            ->where('termid',    $newTermId)
            ->where('sessionid', $newSessionId)
            ->pluck('id')->toArray();

        $idsToDelete = array_unique(array_merge($originalGroupIds, $newGroupIds));

        // Conflict check — ignore anything we're about to delete
        $classNames = Schoolclass::whereIn('id', $newClassIds)->pluck('schoolclass', 'id')->toArray();
        $conflictNames = [];

        foreach ($newClassIds as $classId) {
            $conflict = ClassTeacher::where('schoolclassid', $classId)
                ->where('termid',    $newTermId)
                ->where('sessionid', $newSessionId)
                ->where('staffid',   '!=', $newStaffId)
                ->whereNotIn('id',   $idsToDelete)
                ->exists();

            if ($conflict) {
                $conflictNames[] = $classNames[$classId] ?? $classId;
            }
        }

        if (!empty($conflictNames)) {
            return response()->json([
                'success' => false,
                'message' => 'These classes are already assigned to a different teacher for this term/session: '
                    . implode(', ', $conflictNames) . '.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            ClassTeacher::whereIn('id', $idsToDelete)->delete();

            $createdRecords = [];
            foreach ($newClassIds as $classId) {
                $createdRecords[] = ClassTeacher::create([
                    'staffid'       => $newStaffId,
                    'schoolclassid' => $classId,
                    'termid'        => $newTermId,
                    'sessionid'     => $newSessionId,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClassTeacher update failed: ' . $e->getMessage(), ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Could not update assignment: ' . $e->getMessage(),
            ], 500);
        }

        Log::info('ClassTeacher update', ['id' => $id, 'new_records' => count($createdRecords)]);

        return response()->json([
            'success' => true,
            'message' => count($createdRecords) . ' class teacher assignment(s) updated successfully.',
            'data'    => $createdRecords,
        ], 200);
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        $ct = ClassTeacher::find($id);
        if (!$ct) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }

        $ct->delete();
        Log::info('ClassTeacher deleted', ['id' => $id]);

        return response()->json(['success' => true, 'message' => 'Assignment deleted successfully.'], 200);
    }

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

    public function deleteMultiple(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No assignments selected.'], 400);
        }

        $deleted = ClassTeacher::whereIn('id', $ids)->delete();
        Log::info('ClassTeacher bulk delete', ['count' => $deleted]);

        return response()->json([
            'success' => true,
            'message' => $deleted . ' assignment(s) deleted successfully.',
        ], 200);
    }

    // =========================================================================
    // ASSIGNMENTS — pre-load for edit modal
    // =========================================================================

    public function assignments($staffId, $termId, $sessionId)
    {
        $classIds = ClassTeacher::where('staffid',   (int) $staffId)
            ->where('termid',    (int) $termId)
            ->where('sessionid', (int) $sessionId)
            ->pluck('schoolclassid')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        return response()->json(['success' => true, 'classIds' => $classIds]);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $ct = ClassTeacher::find($id);
        if (!$ct) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }
        return response()->json(['success' => true, 'data' => $ct]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Resolve an avatar filename to a public URL (cached for 10 min).
     * Returns null if no file exists on any known path.
     */
    private function resolveAvatar(string $filename): ?string
    {
        return cache()->remember('avatar_url:' . md5($filename), now()->addMinutes(10), function () use ($filename) {
            $storageCandidates = [
                'public/staff_avatars/'      . $filename => asset('storage/staff_avatars/'      . $filename),
                'public/images/staffavatar/' . $filename => asset('storage/images/staffavatar/' . $filename),
                'public/staffavatar/'        . $filename => asset('storage/staffavatar/'        . $filename),
            ];
            foreach ($storageCandidates as $storagePath => $assetUrl) {
                if (Storage::exists($storagePath)) return $assetUrl;
            }

            $diskCandidates = [
                public_path('storage/staff_avatars/'      . $filename) => asset('storage/staff_avatars/'      . $filename),
                public_path('storage/images/staffavatar/' . $filename) => asset('storage/images/staffavatar/' . $filename),
                public_path('storage/staffavatar/'        . $filename) => asset('storage/staffavatar/'        . $filename),
            ];
            foreach ($diskCandidates as $diskPath => $assetUrl) {
                if (file_exists($diskPath)) return $assetUrl;
            }

            return null;
        });
    }

    private function cleanUtf8String($string): string
    {
        if (empty($string)) return '';
        $string = mb_convert_encoding((string) $string, 'UTF-8', 'UTF-8');
        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    }
}
