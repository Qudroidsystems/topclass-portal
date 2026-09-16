<?php
// app/Http/Controllers/MyPrincipalsCommentController.php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\Principalscomment;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Studentclass;
use App\Models\Studentpersonalityprofile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MyPrincipalsCommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-principals-comment',   ['only' => ['index', 'classBroadsheet']]);
        $this->middleware('permission:Update my-principals-comment', ['only' => ['updateComments']]);
    }

    // =========================================================================
    // INDEX — list principal comment assignments
    // =========================================================================

    public function index()
    {
        $pagetitle = "My Principal's Comment Assignments";

        $assignments = Principalscomment::where('staffId', Auth::id())
            ->join('schoolclass', 'principalscomments.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolsession', 'principalscomments.sessionid', '=', 'schoolsession.id')
            ->leftJoin('schoolterm', 'principalscomments.termid', '=', 'schoolterm.id')
            ->select([
                'principalscomments.id',
                'schoolclass.id as schoolclassid',
                'schoolclass.schoolclass as sclass',
                'schoolarm.arm as schoolarm',
                'schoolsession.id as session_id',
                'schoolsession.session as session_name',
                'schoolterm.id as term_id',
                'schoolterm.term as term_name',
                'principalscomments.updated_at',
            ])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        return view('myprincipalscomment.index', compact('assignments', 'pagetitle'));
    }

    // =========================================================================
    // CLASS BROADSHEET — main entry view for comments
    // =========================================================================

    public function classBroadsheet(Request $request, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle = "Principal's Comment & Class Broadsheet";

        // Scoring mode: 'cumulative' (default), 'term', or 'mock'
        $scoringMode = $request->get('scoring_mode', 'cumulative');
        if (!in_array($scoringMode, ['cumulative', 'term', 'mock'])) {
            $scoringMode = 'cumulative';
        }

        // ── 1. Students enrolled in this class/session ──────────────────
        $students = Studentclass::where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get([
                'studentRegistration.id          as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname   as fname',
                'studentRegistration.lastname    as lastname',
                'studentRegistration.othername   as othername',
                'studentRegistration.gender      as gender',
                'studentpicture.picture          as picture',
            ]);

        // ── 2. School class meta ────────────────────────────────────────
        $schoolclass = Schoolclass::with('classcategory')->findOrFail($schoolclassid);

        $armName = '';
        if ($schoolclass->arm) {
            $armObj  = \App\Models\Schoolarm::find($schoolclass->arm);
            $armName = $armObj->arm ?? '';
        }
        $schoolclass->arm_name = $armName;

        $schoolterm    = Schoolterm::find($termid);
        $schooltermName = $schoolterm?->term ?? 'N/A';
        $schoolsession = Schoolsession::find($sessionid)?->session ?? 'N/A';

        $isSenior = $schoolclass && $schoolclass->classcategory
            ? (bool) $schoolclass->classcategory->is_senior
            : false;

        $studentIds = $students->pluck('id')->map(fn ($v) => (int) $v)->toArray();

        // ── 3. Terminal broadsheet rows (Project 1) ─────────────────────
        $termScoreMap = [];
        $cumScoreMap  = [];
        $bfMap        = [];
        $broadsheetRows = collect();

        if ($scoringMode !== 'mock') {
            $prevCumMap = $this->fetchPreviousTermCums($studentIds, $sessionid, $termid, [$schoolclassid]);

            $broadsheetRows = Broadsheets::where('broadsheet_records.schoolclass_id', $schoolclassid)
                ->where('broadsheets.term_id', $termid)
                ->where('broadsheet_records.session_id', $sessionid)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'broadsheet_records.student_id',
                    'broadsheet_records.subject_id',
                    'subject.subject as subject_name',
                    'broadsheets.ca1',
                    'broadsheets.ca2',
                    'broadsheets.ca3',
                    'broadsheets.exam',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.grade',
                    'broadsheets.remark',
                ])
                ->get();

            foreach ($broadsheetRows as $row) {
                $sid      = (int) $row->student_id;
                $subj     = $row->subject_name;
                $subId    = (int) $row->subject_id;

                $ca1  = (float) ($row->ca1  ?? 0);
                $ca2  = (float) ($row->ca2  ?? 0);
                $ca3  = (float) ($row->ca3  ?? 0);
                $exam = (float) ($row->exam ?? 0);

                $caAvg = ($ca1 + $ca2 + $ca3) / 3;
                $total = round(($caAvg + $exam) / 2, 1);

                $prevCum = $prevCumMap[$sid][$subId] ?? null;
                if ($prevCum !== null && $prevCum > 0) {
                    $bf = $prevCum;
                } elseif (!empty($row->bf) && (float) $row->bf > 0) {
                    $bf = (float) $row->bf;
                } else {
                    $bf = 0.0;
                }

                $cum = $termid == 1 ? $total : round(($bf + $total) / 2, 2);

                $termScoreMap[$sid][$subj] = $total;
                $cumScoreMap[$sid][$subj]  = $cum;
                $bfMap[$sid][$subj]        = $bf;
            }
        }

        // ── 4. Mock broadsheet rows (optional) ──────────────────────────
        $mockScoreMap = [];
        $hasMockData  = false;

        if ($scoringMode === 'mock') {
            $mockRows = BroadsheetsMock::where('broadsheet_records_mock.schoolclass_id', $schoolclassid)
                ->where('broadsheetmock.term_id', $termid)
                ->where('broadsheet_records_mock.session_id', $sessionid)
                ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'broadsheet_records_mock.student_id',
                    'subject.subject as subject_name',
                    'broadsheetmock.exam',
                    'broadsheetmock.total',
                ])
                ->get();

            foreach ($mockRows as $row) {
                $sid  = (int) $row->student_id;
                $subj = $row->subject_name;
                $mockScoreMap[$sid][$subj] = (float) ($row->total ?? 0);
            }

            $hasMockData = $mockRows->isNotEmpty();
        }

        // ── 5. Distinct subject list per mode ───────────────────────────
        if ($scoringMode === 'mock') {
            $subjects = collect($mockScoreMap)
                ->flatMap(fn ($row) => array_keys($row))
                ->unique()->sort()->values()->toArray();
        } else {
            $subjects = $broadsheetRows
                ->pluck('subject_name')
                ->unique()->sort()->values()->toArray();
        }

        // ── 6. Per-student grades / weak subjects ───────────────────────
        $studentGrades        = [];
        $studentGradeAnalysis = [];

        foreach ($students as $student) {
            $sid = $student->id;

            $studentGradeAnalysis[$sid] = [
                'grades'        => [],
                'counts'        => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0],
                'weak_subjects' => [],
            ];

            foreach ($subjects as $subject) {
                if ($scoringMode === 'mock') {
                    $activeScore = $mockScoreMap[$sid][$subject] ?? 0;
                    [$activeGrade, $activeGradeLetter] = $this->gradeFromScore((float) $activeScore, $isSenior);

                    $entry = [
                        'subject'              => $subject,
                        'mock_score'           => $activeScore,
                        'mock_grade'           => $activeGrade,
                        'mock_grade_letter'    => $activeGradeLetter,
                        'cum_score'            => 0,
                        'cum_grade'            => '-',
                        'cum_grade_letter'     => '',
                        'term_score'           => 0,
                        'term_grade'           => '-',
                        'term_grade_letter'    => '',
                        'bf_score'             => 0,
                        'score'                => $activeScore,
                        'grade'                => $activeGrade,
                        'grade_letter'         => $activeGradeLetter,
                    ];
                } else {
                    $cumTotal  = $cumScoreMap[$sid][$subject]  ?? 0;
                    $termTotal = $termScoreMap[$sid][$subject] ?? 0;
                    $bf        = $bfMap[$sid][$subject]        ?? 0;

                    [$cumGrade, $cumGradeLetter]   = $this->gradeFromScore((float) $cumTotal, $isSenior);
                    [$termGrade, $termGradeLetter] = $this->gradeFromScore((float) $termTotal, $isSenior);

                    // Active grade depends on mode
                    $activeScore = $scoringMode === 'term' ? $termTotal : $cumTotal;
                    [$activeGrade, $activeGradeLetter] = $this->gradeFromScore((float) $activeScore, $isSenior);

                    $entry = [
                        'subject'            => $subject,
                        'cum_score'          => $cumTotal,
                        'cum_grade'          => $cumGrade,
                        'cum_grade_letter'   => $cumGradeLetter,
                        'term_score'         => $termTotal,
                        'term_grade'         => $termGrade,
                        'term_grade_letter'  => $termGradeLetter,
                        'bf_score'           => $bf,
                        'mock_score'         => 0,
                        'mock_grade'         => '-',
                        'mock_grade_letter'  => '',
                        'score'              => $activeScore,
                        'grade'              => $activeGrade,
                        'grade_letter'       => $activeGradeLetter,
                    ];
                }

                $studentGrades[$sid][]                  = $entry;
                $studentGradeAnalysis[$sid]['grades'][] = $entry;
                $studentGradeAnalysis[$sid]['counts'][$entry['grade_letter']]++;

                if (in_array($entry['grade_letter'], ['C', 'D', 'E', 'F'])) {
                    $studentGradeAnalysis[$sid]['weak_subjects'][] = [
                        'subject'        => $subject,
                        'grade'          => $entry['grade'],
                        'grade_letter'   => $entry['grade_letter'],
                        'cum_score'      => $entry['cum_score'],
                        'term_score'     => $entry['term_score'],
                        'mock_score'     => $entry['mock_score'],
                    ];
                }
            }
        }

        // ── 7. Existing principal comments ──────────────────────────────
        $profiles = Studentpersonalityprofile::where('schoolclassid', $schoolclassid)
            ->where('termid',    $termid)
            ->where('sessionid', $sessionid)
            ->pluck('principalscomment', 'studentid')
            ->toArray();

        // ── 8. Standard personalised comments (second person) ───────────
        $baseTemplates = [
            "Excellent result {NAME}, keep it up!",
            "A very good result {NAME}, keep it up!",
            "Good result {NAME}, keep it up!",
            "Average result {NAME}, there's still room for improvement next term.",
            "{NAME}, you can do better next term.",
            "{NAME}, you need to sit up and be serious.",
            "{NAME}, wake up and be serious.",
        ];

        $standardPersonalizedComments = [];

        foreach ($students as $student) {
            $sid       = $student->id;
            $firstName = $student->fname;

            $weakSubjects = $studentGradeAnalysis[$sid]['weak_subjects'] ?? [];
            $advice       = '';

            if (!empty($weakSubjects)) {
                usort($weakSubjects, fn ($a, $b) =>
                    ['F' => 0, 'E' => 1, 'D' => 2, 'C' => 3][$a['grade_letter']]
                    <=>
                    ['F' => 0, 'E' => 1, 'D' => 2, 'C' => 3][$b['grade_letter']]
                );

                $subjectList = array_map(
                    fn ($ws) => strtoupper($ws['subject']) . ' (' . $ws['grade'] . ')',
                    $weakSubjects
                );
                $advice = "\n\nYou should work harder in "
                        . $this->formatList($subjectList)
                        . " to improve your performance.";
            }

            $options = [];
            foreach ($baseTemplates as $template) {
                $options[] = str_replace('{NAME}', $firstName, $template) . $advice;
            }

            $standardPersonalizedComments[$sid] = $options;
        }

        // ── 9. Intelligent (3rd-person) comments ────────────────────────
        $intelligentComments = [];

        foreach ($students as $student) {
            $sid       = $student->id;
            $firstName = $student->fname;
            $analysis  = $studentGradeAnalysis[$sid];

            $gradeParts = [];
            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $g) {
                $count = $analysis['counts'][$g] ?? 0;
                if ($count > 0) {
                    $gradeParts[] = "$count {$g}" . ($count > 1 ? "'s" : '');
                }
            }
            $gradeSummary = !empty($gradeParts) ? $this->formatList($gradeParts) : 'no grades recorded';

            $totalGrades    = array_sum($analysis['counts']);
            $goodGrades     = ($analysis['counts']['A'] ?? 0) + ($analysis['counts']['B'] ?? 0);
            $percentageGood = $totalGrades > 0 ? ($goodGrades / $totalGrades) * 100 : 0;

            $baseComment = match (true) {
                $percentageGood >= 80 => "Excellent result {NAME}, keep it up!",
                $percentageGood >= 70 => "A very good result {NAME}, keep it up!",
                $percentageGood >= 60 => "Good result {NAME}, keep it up!",
                $percentageGood >= 50 => "Average result {NAME}, there's still room for improvement next term.",
                $percentageGood >= 40 => "{NAME}, you can do better next term.",
                $percentageGood >= 30 => "{NAME}, you need to sit up and be serious.",
                default               => "{NAME}, wake up and be serious.",
            };

            $termInfo = '';
            if ($scoringMode === 'cumulative') {
                $termInfo = ' (based on Cumulative)';
            } elseif ($scoringMode === 'mock') {
                $termInfo = ' (Mock examination result)';
            }

            $comment    = "$firstName has $gradeSummary$termInfo. "
                        . str_replace('{NAME}', $firstName, $baseComment);
            $pronoun    = strtoupper($student->gender) === 'MALE' ? 'He'  : 'She';
            $possessive = strtoupper($student->gender) === 'MALE' ? 'his' : 'her';

            $weakSubjects = $analysis['weak_subjects'] ?? [];
            if (!empty($weakSubjects)) {
                usort($weakSubjects, fn ($a, $b) =>
                    ['F' => 0, 'E' => 1, 'D' => 2, 'C' => 3][$a['grade_letter']]
                    <=>
                    ['F' => 0, 'E' => 1, 'D' => 2, 'C' => 3][$b['grade_letter']]
                );
                $subjectList = array_map(fn ($ws) => $ws['subject'] . ' (' . $ws['grade'] . ')', $weakSubjects);
                $comment    .= "\n\n$pronoun should work harder in "
                             . $this->formatList($subjectList)
                             . " to improve $possessive performance.";
            }

            $intelligentComments[$sid] = $comment;
        }

        // ── 10. Analytics (per student, mode-aware) ────────────────────
        $studentTotals       = [];
        $studentTermTotals   = [];
        $studentMockTotals   = [];

        foreach ($students as $student) {
            $sid       = $student->id;
            $cumSum    = 0;
            $termSum   = 0;
            $mockSum   = 0;
            $count     = 0;
            $mockCount = 0;

            foreach ($subjects as $subject) {
                $cum  = $cumScoreMap[$sid][$subject]  ?? null;
                $term = $termScoreMap[$sid][$subject] ?? null;
                $mock = $mockScoreMap[$sid][$subject] ?? null;

                if (!is_null($cum) && $cum > 0) { $cumSum += $cum; $count++; }
                if (!is_null($term))            { $termSum += $term; }
                if (!is_null($mock) && $mock > 0){ $mockSum += $mock; $mockCount++; }
            }

            $studentTotals[$sid] = [
                'total'    => $cumSum,
                'average'  => $count > 0 ? round($cumSum / $count, 1) : 0,
                'subjects' => $count,
            ];
            $studentTermTotals[$sid] = [
                'total'   => $termSum,
                'average' => $count > 0 ? round($termSum / $count, 1) : 0,
            ];
            $studentMockTotals[$sid] = [
                'total'    => $mockSum,
                'average'  => $mockCount > 0 ? round($mockSum / $mockCount, 1) : 0,
                'subjects' => $mockCount,
            ];
        }

        // Class averages
        $classCumSubjects = array_sum(array_column($studentTotals, 'subjects'));
        $classCumSum      = array_sum(array_column($studentTotals, 'total'));
        $classCumAverage  = $classCumSubjects > 0 ? round($classCumSum / $classCumSubjects, 1) : 0;

        $classTermSum     = array_sum(array_column($studentTermTotals, 'total'));
        $classTermAverage = $classCumSubjects > 0 ? round($classTermSum / $classCumSubjects, 1) : 0;

        $classMockSubjects = array_sum(array_column($studentMockTotals, 'subjects'));
        $classMockSum      = array_sum(array_column($studentMockTotals, 'total'));
        $classMockAverage  = $classMockSubjects > 0 ? round($classMockSum / $classMockSubjects, 1) : 0;

        $activeClassAverage = match ($scoringMode) {
            'term' => $classTermAverage,
            'mock' => $classMockAverage,
            default => $classCumAverage,
        };

        $classAnalytics = [
            'average'        => $activeClassAverage,
            'cum_average'    => $classCumAverage,
            'term_average'   => $classTermAverage,
            'mock_average'   => $classMockAverage,
            'total_students' => $students->count(),
        ];

        // Positions per mode
        $sortedStudents = $students->sortByDesc(function ($s) use ($scoringMode, $studentTermTotals, $studentTotals, $studentMockTotals) {
            return match ($scoringMode) {
                'term' => $studentTermTotals[$s->id]['average'] ?? 0,
                'mock' => $studentMockTotals[$s->id]['average'] ?? 0,
                default => $studentTotals[$s->id]['average'] ?? 0,
            };
        })->values();

        $positions = [];
        $rank      = 1;
        $prevAvg   = null;

        foreach ($sortedStudents as $index => $student) {
            $avg = match ($scoringMode) {
                'term' => $studentTermTotals[$student->id]['average'] ?? 0,
                'mock' => $studentMockTotals[$student->id]['average'] ?? 0,
                default => $studentTotals[$student->id]['average'] ?? 0,
            };
            if ($index > 0 && $avg < $prevAvg) {
                $rank = $index + 1;
            }
            $positions[$student->id] = $rank;
            $prevAvg = $avg;
        }

        $studentAnalytics = [];
        foreach ($students as $student) {
            $sid      = $student->id;
            $position = $positions[$sid] ?? null;

            $studentAnalytics[$sid] = [
                'total_score'   => $studentTotals[$sid]['total'],
                'average'       => $studentTotals[$sid]['average'],
                'term_total'    => $studentTermTotals[$sid]['total'],
                'term_average'  => $studentTermTotals[$sid]['average'],
                'mock_total'    => $studentMockTotals[$sid]['total'],
                'mock_average'  => $studentMockTotals[$sid]['average'],
                'subjects'      => $studentTotals[$sid]['subjects'],
                'position'      => $position,
                'position_text' => $position ? $this->getPositionSuffix($position) : '-',
                'grade_counts'  => $studentGradeAnalysis[$sid]['counts'] ?? [],
            ];
        }

        return view('myprincipalscomment.classbroadsheet', compact(
            'students',
            'subjects',
            'termScoreMap',
            'cumScoreMap',
            'bfMap',
            'mockScoreMap',
            'profiles',
            'schoolclass',
            'schoolterm',
            'schooltermName',
            'schoolsession',
            'schoolclassid',
            'sessionid',
            'termid',
            'pagetitle',
            'studentGrades',
            'studentGradeAnalysis',
            'intelligentComments',
            'standardPersonalizedComments',
            'studentAnalytics',
            'classAnalytics',
            'isSenior',
            'scoringMode',
            'hasMockData'
        ));
    }

    // =========================================================================
    // UPDATE COMMENTS
    // =========================================================================

    public function updateComments(Request $request, $schoolclassid, $sessionid, $termid)
    {
        $request->validate(['teacher_comments.*' => 'nullable|string|max:5000']);

        $comments     = $request->input('teacher_comments', []);
        $updatedCount = 0;
        $createdCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($comments as $studentId => $comment) {
                if (is_null($comment) || trim($comment) === '') {
                    $skippedCount++;
                    continue;
                }

                $comment = trim(strip_tags($comment));
                $comment = html_entity_decode($comment, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $existing = Studentpersonalityprofile::where('studentid',    $studentId)
                    ->where('schoolclassid', $schoolclassid)
                    ->where('sessionid',     $sessionid)
                    ->where('termid',        $termid)
                    ->first();

                if ($existing) {
                    if ($existing->principalscomment !== $comment) {
                        $existing->update([
                            'staffid'           => Auth::id(),
                            'principalscomment' => $comment,
                        ]);
                        $updatedCount++;
                    }
                } else {
                    Studentpersonalityprofile::create([
                        'studentid'         => $studentId,
                        'schoolclassid'     => $schoolclassid,
                        'sessionid'         => $sessionid,
                        'termid'            => $termid,
                        'staffid'           => Auth::id(),
                        'principalscomment' => $comment,
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            $totalProcessed = $updatedCount + $createdCount;
            $message = $totalProcessed > 0
                ? "Successfully saved: {$updatedCount} updated, {$createdCount} created. Skipped: {$skippedCount} empty comments."
                : "No changes detected. {$skippedCount} empty comments skipped.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'updated' => $updatedCount,
                'created' => $createdCount,
                'skipped' => $skippedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving principals comments', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function fetchPreviousTermCums(array $studentIds, int $sessionid, int $currentTermId, array $classIds): array
    {
        if (empty($studentIds) || $currentTermId == 1) return [];

        $prevTerm = Schoolterm::where('id', '<', $currentTermId)->orderByDesc('id')->first();
        if (!$prevTerm) return [];

        $rows = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $prevTerm->id)
            ->where('broadsheet_records.session_id', $sessionid)
            ->whereIn('broadsheet_records.schoolclass_id', $classIds)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->select(['broadsheet_records.student_id', 'broadsheet_records.subject_id', 'broadsheets.cum'])
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->student_id][(int) $r->subject_id] = (float) $r->cum;
        }
        return $map;
    }

    protected function gradeFromScore(float $score, bool $isSenior): array
    {
        if ($score <= 0) return ['-', ''];

        if ($isSenior) {
            if ($score >= 75) return ['A1', 'A'];
            if ($score >= 70) return ['B2', 'B'];
            if ($score >= 65) return ['B3', 'B'];
            if ($score >= 60) return ['C4', 'C'];
            if ($score >= 55) return ['C5', 'C'];
            if ($score >= 50) return ['C6', 'C'];
            if ($score >= 45) return ['D7', 'D'];
            if ($score >= 40) return ['E8', 'E'];
            return ['F9', 'F'];
        }

        if ($score >= 70) return ['A', 'A'];
        if ($score >= 60) return ['B', 'B'];
        if ($score >= 50) return ['C', 'C'];
        if ($score >= 40) return ['D', 'D'];
        return ['F', 'F'];
    }

    protected function formatList(array $items): string
    {
        $count = count($items);
        if ($count === 0) return '';
        if ($count === 1) return $items[0];
        if ($count === 2) return implode(' and ', $items);
        return implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items);
    }

    protected function getPositionSuffix(int $num): string
    {
        if ($num % 100 >= 11 && $num % 100 <= 13) return $num . 'th';
        return match ($num % 10) {
            1 => $num . 'st',
            2 => $num . 'nd',
            3 => $num . 'rd',
            default => $num . 'th',
        };
    }
}