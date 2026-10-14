<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ClassResultsLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class broadsheets for staff — TopClass edition.
 *
 * TopClass has no report-card approval workflow, so the board lists each
 * class with its score-entry readiness, and the approval endpoints answer
 * "not available" (the app hides those buttons).
 *
 * Who sees what (same as the portal): class teachers see their own classes;
 * staff with "View student-report" or "View report-approvals" see every class.
 * Broadsheet figures come from ClassResultsLoader (same maths as the web
 * broadsheet: Total = ((CA1+CA2+CA3)/3 + Exam)/2, Cum from BF).
 */
class ResultsController extends ApiController
{
    /** GET results/board?term_id&session_id */
    public function board(Request $request)
    {
        [$termId, $sessionId] = $this->period($request);
        $user = $request->user();

        $only = $this->seesAll($user) ? null : $this->myClassIds($user, $sessionId);
        if ($only !== null && !$only) return $this->ok([], null, 200, $this->boardMeta($termId, $sessionId));

        $classes = DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->when($only !== null, fn ($q) => $q->whereIn('c.id', $only))
            ->orderBy('c.schoolclass')->orderBy('a.arm')
            ->get(['c.id', DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as name")]);

        $students = DB::table('studentclass')->where('termid', $termId)->where('sessionid', $sessionId)
            ->groupBy('schoolclassid')->pluck(DB::raw('COUNT(DISTINCT studentId)'), 'schoolclassid');

        $sheets = DB::table('broadsheet_records as br')->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->where('br.session_id', $sessionId)->where('b.term_id', $termId)
            ->groupBy('br.schoolclass_id')
            ->get(['br.schoolclass_id', DB::raw('COUNT(DISTINCT br.subject_id) as subjects'), DB::raw('COUNT(*) as entries'),
                   DB::raw('SUM(b.total > 0) as scored'), DB::raw('SUM(b.vettedstatus = 1) as vetted')])
            ->keyBy('schoolclass_id');

        $teachers = DB::table('classteacher as ct')->join('users as u', 'u.id', '=', 'ct.staffid')
            ->where('ct.sessionid', $sessionId)->orderByRaw('ct.termid = ? desc', [$termId])
            ->get(['ct.schoolclassid', 'u.name'])->unique('schoolclassid')->keyBy('schoolclassid');

        $comments = DB::table('studentpersonalityprofiles')->where('termid', $termId)->where('sessionid', $sessionId)
            ->groupBy('schoolclassid')
            ->get(['schoolclassid', DB::raw("SUM(COALESCE(classteachercomment,'') <> '') as t"), DB::raw("SUM(COALESCE(principalscomment,'') <> '') as p")])
            ->keyBy('schoolclassid');

        $rows = $classes->map(function ($c) use ($students, $sheets, $teachers, $comments) {
            $s = $sheets[$c->id] ?? null;
            $entries = (int) ($s->entries ?? 0);
            $scored = (int) ($s->scored ?? 0);
            $pct = $entries ? (int) round($scored / $entries * 100) : 0;
            $ready = $entries > 0 && $scored >= $entries;
            return [
                'class_id'           => (int) $c->id,
                'class_name'         => $c->name,
                'teacher'            => $teachers[$c->id]->name ?? null,
                'students'           => (int) ($students[$c->id] ?? 0),
                'subjects'           => (int) ($s->subjects ?? 0),
                'entries'            => $entries,
                'vetted'             => (int) ($s->vetted ?? 0),
                'vetted_pct'         => $pct,
                'teacher_comments'   => (int) ($comments[$c->id]->t ?? 0),
                'principal_comments' => (int) ($comments[$c->id]->p ?? 0),
                'ready'              => $ready,
                'status'             => $entries === 0 ? 'no_scores' : ($ready ? 'complete' : 'in_progress'),
                'status_label'       => $entries === 0 ? 'No scores yet' : ($ready ? 'All scores in' : $pct . '% scored'),
                'approval_id'        => null,
            ];
        })->filter(fn ($r) => $r['students'] > 0 || $r['entries'] > 0)->values();

        return $this->ok($rows, null, 200, $this->boardMeta($termId, $sessionId));
    }

    /** GET results/classes/{class}/broadsheet?term_id&session_id&basis=cum_ave|total */
    public function broadsheet(Request $request, int $class)
    {
        [$termId, $sessionId] = $this->period($request);
        if ($deny = $this->denyClass($request, $class, $sessionId)) return $deny;

        $basis = $request->get('basis') === 'total' ? 'total' : 'cum_ave';
        $data = app(ClassResultsLoader::class)->load($termId, $sessionId, [$class]);

        $subjects = collect($data['subjects'])->sortBy('subject_name', SORT_NATURAL)->values();
        $rows = collect($data['rows'])->filter(fn ($r) => $r['num_subjects'] > 0);

        $measure = $basis === 'total' ? 'term_ave' : 'cum_ave';
        $sorted = $rows->sortByDesc($measure)->values();
        $positions = [];
        $prev = null; $pos = 0;
        foreach ($sorted as $i => $r) {
            if ($prev === null || $r[$measure] < $prev) { $pos = $i + 1; $prev = $r[$measure]; }
            $positions[$r['id']] = $pos;
        }

        $classRow = collect($data['classes'])->firstWhere('id', $class);
        // Subject positions as stored by the portal (all arms): by cumulative or by term total.
        $subjectPositions = DB::table('broadsheets as b')->join('broadsheet_records as br', 'br.id', '=', 'b.broadsheet_record_id')
            ->where('br.schoolclass_id', $class)->where('br.session_id', $sessionId)->where('b.term_id', $termId)
            ->get(['br.student_id', 'br.subject_id', 'b.cum', 'b.subject_position_class', 'b.subject_position_class_total'])
            ->keyBy(fn ($p) => $p->student_id . '-' . $p->subject_id);
        $posKey = $basis === 'total' ? 'subject_position_class_total' : 'subject_position_class';
        $out = $sorted->map(fn ($r) => [
            'student_id'   => $r['id'],
            'admission_no' => $r['admissionno'],
            'name'         => trim(($r['lastname'] ?? '') . ' ' . ($r['firstname'] ?? '')),
            'gender'       => $r['gender'],
            'photo_url'    => $this->studentPhotoUrl($r['picture'] ?? null),
            'subjects'     => (object) collect($r['subjects'])->map(fn ($s, $sid) => [
                'total'    => $this->num($s['total']),
                'cum'      => $this->num($subjectPositions[$r['id'] . '-' . $sid]->cum ?? $s['cum']),
                'cum_ave'  => $this->num($s['cum']),
                'grade'    => $s['grade'],
                'position' => \App\Services\Results\ResultDisplay::ordinal($subjectPositions[$r['id'] . '-' . $sid]->{$posKey} ?? null),
            ])->all(),
            'num_subjects' => $r['num_subjects'],
            'total_term'   => $this->num($r['total_term'], 1),
            'term_average' => $this->num($r['term_ave'], 1),
            'total_cum'    => $this->num($r['total_cum'], 1),
            'cum_average'  => $this->num($r['cum_ave'], 1),
            'gpa'          => null,
            'position'     => $positions[$r['id']] ?? null,
        ]);

        return $this->ok([
            'class_id'      => $class,
            'class_name'    => $classRow ? trim(($classRow->schoolclass ?? '') . ' ' . ($classRow->arm_name ?? '')) : null,
            'term_id'       => $termId,
            'term'          => DB::table('schoolterm')->where('id', $termId)->value('term'),
            'session_id'    => $sessionId,
            'session'       => DB::table('schoolsession')->where('id', $sessionId)->value('session'),
            'basis'         => $basis,
            'class_average' => $sorted->count() ? round($sorted->avg($measure), 1) : null,
            'subjects'      => $subjects->map(fn ($s) => [
                'subject_id' => (int) $s['subject_id'], 'name' => $s['subject_name'], 'code' => ($s['subject_code'] ?? null) ?: null,
            ])->values(),
            'students'      => $out->values(),
            'class_size' => $sorted->count(),
            'gpa'        => \App\Services\Results\ResultDisplay::supportsGpa() && \App\Services\Results\ResultDisplay::show('summary', 'gpa'),
        ]);
    }

    /** Report-card approval is not part of TopClass. */
    public function approval(Request $request, int $class) { return $this->notAvailable(); }
    public function submit(Request $request, int $approval) { return $this->notAvailable(); }
    public function approve(Request $request, int $approval) { return $this->notAvailable(); }
    public function returnBack(Request $request, int $approval) { return $this->notAvailable(); }
    public function reopen(Request $request, int $approval) { return $this->notAvailable(); }
    public function approveAll(Request $request) { return $this->notAvailable(); }
    public function preview(Request $request, int $approval, int $student) { return $this->notAvailable(); }

    protected function notAvailable()
    {
        return $this->fail('Report-card approval is not used on this portal.', 404, 'not_available');
    }

    protected function boardMeta(int $termId, int $sessionId): array
    {
        return [
            'term_id' => $termId, 'session_id' => $sessionId,
            'can_approve' => false,
            'enforced' => false,
            'approvals' => false,
            'terms' => DB::table('schoolterm')->orderBy('id')->get(['id', 'term']),
            'sessions' => DB::table('schoolsession')->orderByDesc('id')->get(['id', 'session']),
        ];
    }

    protected function period(Request $request): array
    {
        return [
            (int) ($request->get('term_id') ?: $request->input('term_id') ?: $this->currentTermId()),
            (int) ($request->get('session_id') ?: $request->input('session_id') ?: $this->currentSessionId()),
        ];
    }

    protected function seesAll($user): bool
    {
        return $this->can($user, 'View report-approvals') || $this->can($user, 'View student-report');
    }

    protected function myClassIds($user, int $sessionId): array
    {
        return DB::table('classteacher')->where('staffid', $user->id)->where('sessionid', $sessionId)
            ->pluck('schoolclassid')->map(fn ($v) => (int) $v)->unique()->values()->all();
    }

    protected function denyClass(Request $request, int $classId, int $sessionId)
    {
        $user = $request->user();
        if ($this->seesAll($user) || in_array($classId, $this->myClassIds($user, $sessionId), true)) return null;
        return $this->fail('You can only open your own classes.', 403, 'forbidden');
    }

    protected function can($user, string $perm): bool
    {
        try { return $user->can($perm); } catch (\Throwable $e) { return false; }
    }
}
