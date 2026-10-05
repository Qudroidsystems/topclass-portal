<?php
// app/Http/Controllers/BroadsheetController.php  (TopClass)

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\BroadsheetRankingSetting;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\PromotionSetting;
use App\Models\Studentclass;
use App\Services\BroadsheetRankingService;
use App\Services\PromotionEvaluator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class BroadsheetController extends Controller
{
    private PromotionEvaluator $promotionEvaluator;
    private BroadsheetRankingService $rankingService;

    public function __construct(PromotionEvaluator $promotionEvaluator, BroadsheetRankingService $rankingService)
    {
        $this->middleware('permission:View student-report');
        $this->promotionEvaluator = $promotionEvaluator;
        $this->rankingService     = $rankingService;
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): View
    {
        $pagetitle      = 'Class Broadsheet Generator';
        $schoolclasses  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm'])
            ->orderBy('schoolclass.schoolclass')
            ->get();
        $schoolsessions = Schoolsession::orderByDesc('id')->get();
        $schoolterms    = Schoolterm::all();

        return view('broadsheet.index', compact(
            'pagetitle', 'schoolclasses', 'schoolsessions', 'schoolterms'
        ));
    }

    // =========================================================================
    // GET COLUMN OPTIONS (AJAX)
    // =========================================================================

    public function getColumnOptions(Request $request): JsonResponse
    {
        try {
            $schoolclassid = $request->input('schoolclassid');
            $sessionid     = $request->input('sessionid');
            $termid        = $request->input('termid');

            if (!$schoolclassid || !$sessionid || !$termid) {
                return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
            }

            $actualSubjectCount = DB::table('subjectclass as sc')
                ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
                ->where('sc.schoolclassid', $schoolclassid)
                ->where('st.termid', $termid)
                ->where('st.sessionid', $sessionid)
                ->distinct()
                ->count('sc.subjectid');

            $term              = Schoolterm::find($termid);
            $isPromotionalTerm = $term && $term->is_promotional;

            $columns = [
                'student_info' => [
                    'sn'           => ['label' => 'SN',           'default' => true],
                    'admission_no' => ['label' => 'Admission No', 'default' => true],
                    'name'         => ['label' => 'Student Name', 'default' => true],
                    'gender'       => ['label' => 'Gender',       'default' => false],
                ],
                'scores' => [
                    'ca1'             => ['label' => 'CA1',               'default' => true],
                    'ca2'             => ['label' => 'CA2',               'default' => true],
                    'ca3'             => ['label' => 'CA3',               'default' => true],
                    'exam'            => ['label' => 'Exam',              'default' => true],
                    'total'           => ['label' => 'Total',             'default' => true],
                    'bf'              => ['label' => 'BF',                'default' => true],
                    'cum'             => ['label' => 'Cum (raw sum)',     'default' => true],
                    'grade'           => ['label' => 'Grade',             'default' => true],
                    'pos_class_cum'   => ['label' => 'Class Pos (Cum)',   'default' => true],
                    'pos_class_total' => ['label' => 'Class Pos (Total)', 'default' => false],
                    'pos_arm_total'   => ['label' => 'Arm Pos (Total)',   'default' => true],
                    'pos_arm_cum'     => ['label' => 'Arm Pos (Cum)',     'default' => true],
                    'class_average'   => ['label' => 'Class Avg',         'default' => true],
                    'remark'          => ['label' => 'Remark',            'default' => false],
                ],
                'summary' => [
                    'position_cum'  => ['label' => 'Overall Pos (Cum)',  'default' => true],
                    'position_term' => ['label' => 'Overall Pos (Term)', 'default' => true],
                ],
                'promotion' => [
                    'promotion_status' => [
                        'label'   => 'Promotion Status',
                        'default' => $isPromotionalTerm,
                        'note'    => $isPromotionalTerm ? null : 'Non-promotional term',
                    ],
                    'promotion_label' => [
                        'label'   => 'Promotion Label (verbose)',
                        'default' => false,
                    ],
                    'promotion_rule_applied' => [
                        'label'   => 'Rule Applied',
                        'default' => true,
                    ],
                ],
            ];

            return response()->json([
                'success'             => true,
                'columns'             => $columns,
                'subject_count'       => $actualSubjectCount,
                'is_promotional_term' => $isPromotionalTerm,
                'grade_basis_options' => ['total' => 'Term Total', 'cum' => 'Cumulative'],
                'default_grade_basis' => 'cum',
            ]);
        } catch (\Throwable $e) {
            Log::error('getColumnOptions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load column options: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // GET STUDENT PREVIEW (AJAX)
    // =========================================================================

    public function getStudentPreview(Request $request): JsonResponse
    {
        try {
            $schoolclassid = $request->input('schoolclassid');
            $classgroup    = $request->input('classgroup');
            $sessionid     = $request->input('sessionid');

            if ($classgroup && $sessionid) {
                $matchingClasses = Schoolclass::where('schoolclass', $classgroup)->get();
                $classIds        = $matchingClasses->pluck('id')->toArray();

                $count = Studentclass::whereIn('schoolclassid', $classIds)
                    ->where('sessionid', $sessionid)
                    ->count();

                return response()->json([
                    'success'    => true,
                    'count'      => $count,
                    'arms_count' => $matchingClasses->count(),
                ]);
            }

            if (!$schoolclassid || !$sessionid) {
                return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
            }

            $students = Studentclass::where('schoolclassid', $schoolclassid)
                ->where('sessionid', $sessionid)
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->select([
                    'studentRegistration.id as id',
                    'studentRegistration.admissionNo as admissionno',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.gender',
                    'studentpicture.picture',
                ])
                ->orderBy('studentRegistration.lastname')
                ->orderBy('studentRegistration.firstname')
                ->get();

            $subjectCount = DB::table('subjectclass as sc')
                ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
                ->where('sc.schoolclassid', $schoolclassid)
                ->distinct()
                ->count('sc.subjectid');

            return response()->json([
                'success'       => true,
                'count'         => $students->count(),
                'students'      => $students,
                'subject_count' => $subjectCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('getStudentPreview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // POSITION RECALCULATION — isolated, safe, never blocks callers
    // =========================================================================

    protected function recalculatePositionsForClass(int $schoolclassid, int $termid, int $sessionid): void
    {
        try {
            $subjectClassIds = DB::table('subjectclass')
                ->where('schoolclassid', $schoolclassid)
                ->pluck('id');

            foreach ($subjectClassIds as $scId) {
                $this->recalculatePositionsForSubjectClass((int) $scId, $termid, $sessionid);
            }
        } catch (\Throwable $e) {
            Log::warning('recalculatePositionsForClass skipped: ' . $e->getMessage(), [
                'schoolclass_id' => $schoolclassid,
                'term_id'        => $termid,
                'session_id'     => $sessionid,
            ]);
        }
    }

    /**
     * Recompute subject positions for one subjectclass.
     * Scope = all arms of the same class name. Competition rank (1,2,2,4).
     * Unscored students get null position.
     */
    protected function recalculatePositionsForSubjectClass(int $subjectclassid, int $termid, int $sessionid): void
    {
        try {
            $subjectClass = DB::table('subjectclass')
                ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->where('subjectclass.id', $subjectclassid)
                ->first(['subjectclass.schoolclassid', 'subjectteacher.subjectid']);

            if (!$subjectClass) {
                return;
            }

            $subjectId     = $subjectClass->subjectid;
            $schoolclassId = $subjectClass->schoolclassid;

            $baseClass = DB::table('schoolclass')
                ->where('id', $schoolclassId)
                ->first(['schoolclass', 'classcategoryid']);
            if (!$baseClass) {
                return;
            }

            $allArmIds = DB::table('schoolclass')
                ->where('schoolclass', $baseClass->schoolclass)
                ->pluck('id');
            if ($allArmIds->isEmpty()) {
                return;
            }

            $allSubjectClassIds = DB::table('subjectclass')
                ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->whereIn('subjectclass.schoolclassid', $allArmIds)
                ->where('subjectteacher.subjectid', $subjectId)
                ->pluck('subjectclass.id');
            if ($allSubjectClassIds->isEmpty()) {
                return;
            }

            $allStudents = DB::table('broadsheets')
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->whereIn('broadsheets.subjectclass_id', $allSubjectClassIds)
                ->where('broadsheets.term_id', $termid)
                ->where('broadsheet_records.session_id', $sessionid)
                ->get([
                    'broadsheets.id',
                    'broadsheets.cum',
                    'broadsheets.total',
                    'broadsheets.ca1',
                    'broadsheets.ca2',
                    'broadsheets.ca3',
                    'broadsheets.exam',
                    'broadsheets.bf',
                    'broadsheet_records.schoolclass_id',
                    'broadsheet_records.student_id',
                ]);

            if ($allStudents->isEmpty()) {
                return;
            }

            // Healing: recompute total & cum live from raw components so
            // positions rank against the same numbers shown on screen.
            $studentIdsForHealing = $allStudents->pluck('student_id')->unique()
                ->map(fn ($v) => (int) $v)->values()->toArray();
            $prevTotals = $this->fetchPreviousTermCums(
                $studentIdsForHealing, $sessionid, $termid, $allArmIds->toArray()
            );

            foreach ($allStudents as $row) {
                $healedTotal = $this->computeTotal($row->ca1, $row->ca2, $row->ca3, $row->exam);
                $bf          = $this->resolveBf($prevTotals[(int) $row->student_id][(int) $subjectId] ?? null, $row->bf);

                $row->total = $healedTotal;
                $row->cum   = $this->computeCum($bf, $healedTotal, $termid);
            }

            $this->applySubjectRank($allStudents, rankBy: 'cum',   eligibleKey: 'cum', column: 'subject_position_class');
            $this->applySubjectRank($allStudents, rankBy: 'total', eligibleKey: 'cum', column: 'subject_position_class_total');

            foreach ($allStudents->groupBy('schoolclass_id') as $armStudents) {
                $this->applySubjectRank($armStudents, rankBy: 'total', eligibleKey: 'cum', column: 'arm_position');
                $this->applySubjectRank($armStudents, rankBy: 'cum',   eligibleKey: 'cum', column: 'arm_position_cum');
            }
        } catch (\Throwable $e) {
            Log::warning('recalculatePositionsForSubjectClass skipped: ' . $e->getMessage(), [
                'subjectclass_id' => $subjectclassid,
                'term_id'         => $termid,
                'session_id'      => $sessionid,
            ]);
        }
    }

    protected function applySubjectRank($rows, string $rankBy, string $eligibleKey, string $column): void
    {
        try {
            $collection = $rows instanceof \Illuminate\Support\Collection ? $rows : collect($rows);

            $scored      = [];
            $unscoredIds = [];

            foreach ($collection as $row) {
                $totalRaw = $row->total ?? null;
                $cumRaw   = $row->cum   ?? null;

                $hasTotal = $totalRaw !== null && $totalRaw !== '' && is_numeric($totalRaw) && (float) $totalRaw != 0.0;
                $hasCum   = $cumRaw   !== null && $cumRaw   !== '' && is_numeric($cumRaw)   && (float) $cumRaw   != 0.0;

                if (!$hasTotal && !$hasCum) {
                    $unscoredIds[] = $row->id;
                    continue;
                }

                $raw = $row->{$rankBy} ?? null;
                if ($rankBy === 'cum') {
                    if ($raw === null || $raw === '' || !is_numeric($raw) || (float) $raw == 0.0) {
                        $raw = $hasTotal ? (float) $totalRaw : null;
                    }
                } elseif ($rankBy === 'total') {
                    if ($raw === null || $raw === '' || !is_numeric($raw)) {
                        $raw = $hasCum ? (float) $cumRaw : null;
                    }
                }

                if ($raw === null || $raw === '' || !is_numeric($raw)) {
                    $unscoredIds[] = $row->id;
                    continue;
                }

                $scored[] = (object) ['id' => $row->id, 'val' => (float) $raw];
            }

            if (!empty($unscoredIds)) {
                DB::table('broadsheets')->whereIn('id', $unscoredIds)->update([$column => null]);
            }

            if (empty($scored)) {
                return;
            }

            usort($scored, fn ($a, $b) => $b->val <=> $a->val);

            $lastVal = null;
            $rank    = 0;
            $lastPos = 0;

            foreach ($scored as $item) {
                $rank++;
                if ($lastVal !== null && $item->val == $lastVal) {
                    $pos = $lastPos;
                } else {
                    $pos     = $rank;
                    $lastPos = $pos;
                    $lastVal = $item->val;
                }

                DB::table('broadsheets')->where('id', $item->id)->update([$column => $pos]);
            }
        } catch (\Throwable $e) {
            Log::warning('applySubjectRank skipped: ' . $e->getMessage(), ['column' => $column]);
        }
    }

    /** @deprecated Use applySubjectRank */
    protected function applyDenseRank($rows, string $sortKey, string $column): void
    {
        $this->applySubjectRank($rows, rankBy: $sortKey, eligibleKey: 'cum', column: $column);
    }

    // =========================================================================
    // SCORE FORMULAS — single source of truth for TopClass (fixed CA structure)
    // =========================================================================

    /** Total = ((CA1 + CA2 + CA3) / 3 + Exam) / 2 */
    private function computeTotal($ca1, $ca2, $ca3, $exam): float
    {
        $caAvg = ((float) ($ca1 ?? 0) + (float) ($ca2 ?? 0) + (float) ($ca3 ?? 0)) / 3;
        return round(($caAvg + (float) ($exam ?? 0)) / 2, 1);
    }

    /** Term 1: Cum = Total. Terms 2–3: Cum = (BF + Total) / 2 */
    private function computeCum(float $bf, float $total, int $termid): float
    {
        return $termid == 1 ? $total : round(($bf + $total) / 2, 2);
    }

    /** Previous term value wins; stored bf is the fallback; otherwise 0. */
    private function resolveBf($prevValue, $storedBf): float
    {
        if ($prevValue !== null && (float) $prevValue > 0) return (float) $prevValue;
        if (!empty($storedBf) && (float) $storedBf > 0)    return (float) $storedBf;
        return 0.0;
    }

    // =========================================================================
    // HELPER: previous term's total for BF
    // Scoped by student + session only (same fix as CSS Kabba): a student who
    // changed arm between terms would otherwise lose their BF. $studentIds
    // already limits the cohort. $classIds is kept for signature compatibility.
    // =========================================================================

    private function fetchPreviousTermCums(
        array $studentIds,
        int   $sessionid,
        int   $currentTermId,
        array $classIds = []
    ): array {
        if (empty($studentIds)) return [];

        try {
            $prevTerm = Schoolterm::where('id', '<', $currentTermId)->orderByDesc('id')->first();
            if (!$prevTerm) return [];

            $rows = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
                ->where('broadsheets.term_id', $prevTerm->id)
                ->where('broadsheet_records.session_id', $sessionid)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->select([
                    'broadsheet_records.student_id',
                    'broadsheet_records.subject_id',
                    'broadsheets.total',
                ])
                ->get();

            $map = [];
            foreach ($rows as $r) {
                $map[(int) $r->student_id][(int) $r->subject_id] = (float) $r->total;
            }
            return $map;
        } catch (\Throwable $e) {
            Log::warning('fetchPreviousTermCums failed: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // SHARED: pivot broadsheet rows into [student][subject] score arrays
    // =========================================================================

    private function pivotScores($broadsheets, array &$subjectsMap, array $prevCumMap, int $termid): array
    {
        $studentSubjectMap = [];

        foreach ($broadsheets as $row) {
            $sid = (int) $row->student_id;
            $sub = (int) $row->subject_id;

            if (!isset($subjectsMap[$sub])) {
                $subjectsMap[$sub] = [
                    'subject_id'   => $sub,
                    'subject_name' => $row->subject_name,
                    'subject_code' => $row->subject_code ?? '',
                ];
            }

            $ca1   = (float) ($row->ca1 ?? 0);
            $ca2   = (float) ($row->ca2 ?? 0);
            $ca3   = (float) ($row->ca3 ?? 0);
            $exam  = (float) ($row->exam ?? 0);
            $total = $this->computeTotal($ca1, $ca2, $ca3, $exam);
            $bf    = $this->resolveBf($prevCumMap[$sid][$sub] ?? null, $row->bf);
            $cum   = $this->computeCum($bf, $total, $termid);

            $studentSubjectMap[$sid][$sub] = [
                'ca1'             => $ca1,
                'ca2'             => $ca2,
                'ca3'             => $ca3,
                'exam'            => $exam,
                'total'           => $total,
                'bf'              => $bf,
                'cum'             => $cum,
                'grade'           => $row->grade ?? '-',
                'remark'          => $row->remark ?? '-',
                'pos_class_cum'   => $row->pos_class_cum   ?? null,
                'pos_class_total' => $row->pos_class_total ?? null,
                'pos_arm_total'   => $row->pos_arm_total   ?? null,
                'pos_arm_cum'     => $row->pos_arm_cum     ?? null,
                'class_average'   => (float) ($row->class_average ?? 0),
            ];
        }

        return $studentSubjectMap;
    }

    private function broadsheetSelectColumns(): array
    {
        return [
            'broadsheets.id as broadsheet_id',
            'broadsheet_records.student_id',
            'broadsheet_records.subject_id',
            'broadsheet_records.schoolclass_id',
            'subject.subject as subject_name',
            'subject.subject_code',
            'broadsheets.ca1',
            'broadsheets.ca2',
            'broadsheets.ca3',
            'broadsheets.exam',
            'broadsheets.total',
            'broadsheets.bf',
            'broadsheets.cum',
            'broadsheets.grade',
            'broadsheets.remark',
            'broadsheets.subject_position_class as pos_class_cum',
            'broadsheets.subject_position_class_total as pos_class_total',
            'broadsheets.arm_position as pos_arm_total',
            'broadsheets.arm_position_cum as pos_arm_cum',
            'broadsheets.avg as class_average',
        ];
    }

    // =========================================================================
    // BUILD BROADSHEET DATA (single class)
    // $lite = true skips position recalculation and promotion evaluation —
    // used by the Best Students report, which only needs scores.
    // =========================================================================

    private function buildBroadsheetData(
        int    $schoolclassid,
        int    $sessionid,
        int    $termid,
        array  $selectedColumns = [],
        string $gradeBasis = 'cum',
        bool   $lite = false
    ): array {
        if (!$lite) {
            $this->recalculatePositionsForClass($schoolclassid, $termid, $sessionid);
        }

        $schoolInfo  = SchoolInformation::getActiveSchool() ?? new \stdClass();
        $schoolclass = Schoolclass::with('classcategory')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.id', $schoolclassid)
            ->first();

        $schoolsession = Schoolsession::find($sessionid);
        $schoolterm    = Schoolterm::find($termid);

        // Subjects scoped through subjectteacher term/session (subjectclass has none)
        $subjectsMap    = [];
        $subjectClasses = DB::table('subjectclass as sc')
            ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
            ->join('subject', 'subject.id', '=', 'sc.subjectid')
            ->where('sc.schoolclassid', $schoolclassid)
            ->where('st.termid', $termid)
            ->where('st.sessionid', $sessionid)
            ->select(['sc.subjectid', 'subject.subject as subject_name', 'subject.subject_code',
                      'sc.subjectteacherid', 'st.staffid'])
            ->distinct()
            ->get();

        foreach ($subjectClasses as $sc) {
            $subjectsMap[(int) $sc->subjectid] = [
                'subject_id'       => (int) $sc->subjectid,
                'subject_name'     => $sc->subject_name,
                'subject_code'     => $sc->subject_code ?? '',
                'subjectteacherid' => $sc->subjectteacherid,
                'staffid'          => $sc->staffid,
            ];
        }

        $studentIds = Studentclass::where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->pluck('studentId')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        if (empty($studentIds)) {
            return $this->emptyBroadsheetResult(
                $schoolInfo, $schoolclass, $schoolsession, $schoolterm,
                $subjectsMap, $selectedColumns,
                ['grade_basis' => $gradeBasis]
            );
        }

        $prevCumMap = $this->fetchPreviousTermCums($studentIds, $sessionid, $termid);

        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.schoolclass_id', $schoolclassid)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->select($this->broadsheetSelectColumns())
            ->get();

        $studentSubjectMap = $this->pivotScores($broadsheets, $subjectsMap, $prevCumMap, $termid);

        $armLabels = [$schoolclassid => (string) ($schoolclass->arm_name ?? '')];

        return $this->assembleStudentRows(
            $studentIds, $sessionid, $schoolclassid, null,
            $studentSubjectMap, $subjectsMap,
            $schoolInfo, $schoolclass, $schoolsession, $schoolterm,
            $selectedColumns, $armLabels, null, false, $gradeBasis, $lite
        );
    }

    // =========================================================================
    // ASSEMBLE STUDENT ROWS
    // =========================================================================

    private function assembleStudentRows(
        array  $studentIds,
        int    $sessionid,
        ?int   $schoolclassid,
        ?array $classIds,
        array  $studentSubjectMap,
        array  $subjectsMap,
        $schoolInfo,
        $schoolclass,
        $schoolsession,
        $schoolterm,
        array  $selectedColumns,
        array  $armLabels       = [],
        ?array $studentClassMap = null,
        bool   $isCombined      = false,
        string $gradeBasis      = 'cum',
        bool   $lite            = false
    ): array {
        $query = Studentclass::where('sessionid', $sessionid);
        if ($schoolclassid) {
            $query->where('schoolclassid', $schoolclassid);
        } else {
            $query->whereIn('schoolclassid', $classIds ?? []);
        }

        $studentInfoRows = $query
            ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.gender',
                'studentRegistration.dateofbirth',
                'studentpicture.picture',
                'studentclass.schoolclassid',
            ])
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get();

        $termid          = $schoolterm ? $schoolterm->id : null;
        $shouldEvalPromo = !$lite && $termid && $sessionid;
        $className       = (string) ($schoolclass->schoolclass ?? '');

        $studentRows = [];
        foreach ($studentInfoRows as $stu) {
            $sid       = (int) $stu->id;
            $subScores = $studentSubjectMap[$sid] ?? [];

            $termTotals = [];
            $cumValues  = [];
            foreach ($subScores as $subData) {
                $t = $subData['total'] ?? null;
                $c = $subData['cum']   ?? null;

                if ($t !== null && $t !== '' && is_numeric($t)) {
                    $termTotals[] = (float) $t;
                }
                if ($c === null || $c === '' || !is_numeric($c)) {
                    $c = ($t !== null && $t !== '' && is_numeric($t)) ? (float) $t : null;
                }
                if ($c !== null && is_numeric($c)) {
                    $cumValues[] = (float) $c;
                }
            }

            $totalTerm   = array_sum($termTotals);
            $totalCum    = array_sum($cumValues);
            $numSubjects = max(count($cumValues), count($termTotals));
            $nCum        = count($cumValues);
            $nTerm       = count($termTotals);
            $classAvg    = $nCum > 0 ? round($totalCum / $nCum, 1) : ($nTerm > 0 ? round($totalTerm / $nTerm, 1) : 0);
            $termAve     = $nTerm > 0 ? round($totalTerm / $nTerm, 1) : 0;
            $cumAve      = $nCum  > 0 ? round($totalCum  / $nCum,  1) : 0;

            // Arm label from the preloaded map — no per-student queries
            $rowClassId = (int) $stu->schoolclassid;
            $armLabel   = (string) ($armLabels[$rowClassId] ?? '');

            // ── Promotion evaluation ──
            $promoResult = null;
            if ($shouldEvalPromo) {
                $evalClassId  = $rowClassId ?: (int) $schoolclassid;
                $averageBasis = in_array($gradeBasis, ['total', 'cum'], true) ? $gradeBasis : 'total';

                try {
                    $scoresForPromo = $this->getStudentScoresForPromotion($sid, $evalClassId, (int) $sessionid, (int) $termid);
                    $overallAverage = $this->promotionEvaluator->computeOverallAverage($scoresForPromo, $averageBasis);

                    if ($this->classHasNoApplicableSetting($evalClassId, (int) $sessionid, (int) $termid)) {
                        $promoResult = $this->promotionEvaluator->awaitingResult(
                            $overallAverage, 'No matching promotion setting', $evalClassId
                        );
                    } else {
                        $promoResult = $this->promotionEvaluator->evaluate(
                            studentId:      $sid,
                            schoolclassid:  $evalClassId,
                            termid:         (int) $termid,
                            sessionid:      (int) $sessionid,
                            scores:         $scoresForPromo,
                            overallAverage: $overallAverage
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning('Promotion eval failed for student ' . $sid . ': ' . $e->getMessage());
                    $promoResult = $this->promotionEvaluator->awaitingResult(null, 'Evaluation error', $evalClassId ?? null);
                }
            }

            $ruleApplied = $this->extractRuleApplied($promoResult);

            $studentRows[$sid] = [
                'id'                     => $sid,
                'admissionno'            => $stu->admissionno,
                'firstname'              => $stu->firstname,
                'lastname'               => $stu->lastname,
                'gender'                 => $stu->gender,
                'dateofbirth'            => $stu->dateofbirth,
                'picture'                => $stu->picture,
                'arm'                    => $armLabel,
                'class_name'             => $className,
                'class_label'            => trim($className . ' ' . $armLabel),
                'schoolclassid'          => $rowClassId,
                'subjects'               => $subScores,
                'total_cum'              => round($totalCum, 1),
                'total_term'             => round($totalTerm, 1),
                'cum_ave'                => $cumAve,
                'term_ave'               => $termAve,
                'num_subjects'           => $numSubjects,
                'class_average'          => $classAvg,
                'position_cum'           => 0,
                'position_term'          => 0,
                'promotion_status'       => $promoResult['status']       ?? 'awaiting',
                'promotion_label'        => $promoResult['status_label'] ?? $promoResult['label'] ?? 'Awaiting Decision',
                'promotion_rule_applied' => $ruleApplied,
                'promotion_data'         => $promoResult,
            ];
        }

        // Rank by AVERAGE (not sum) so different subject counts stay fair.
        $posMapCum  = $this->buildPositionMap($studentRows, 'cum_ave');
        $posMapTerm = $this->buildPositionMap($studentRows, 'term_ave');

        foreach ($studentRows as $sid => &$row) {
            $row['position_cum']  = $posMapCum[(int) $sid]  ?? 0;
            $row['position_term'] = $posMapTerm[(int) $sid] ?? 0;
        }
        unset($row);

        $subjectStats = $this->buildSubjectStats($subjectsMap, $studentRows);
        uasort($subjectsMap, fn ($a, $b) => strcmp($a['subject_name'], $b['subject_name']));

        $result = [
            'schoolInfo'      => $schoolInfo,
            'schoolclass'     => $schoolclass,
            'schoolsession'   => $schoolsession,
            'schoolterm'      => $schoolterm,
            'subjects'        => $subjectsMap,
            'studentRows'     => array_values($studentRows),
            'subjectStats'    => $subjectStats,
            'selectedColumns' => $selectedColumns,
            'totalStudents'   => count($studentRows),
            'generatedAt'     => now()->format('d M Y, H:i'),
            'grade_basis'     => $gradeBasis,
        ];

        if ($isCombined) {
            $result['arm_labels']  = $armLabels;
            $result['is_combined'] = true;
        }

        return $result;
    }

    private function extractRuleApplied(?array $promoResult): ?string
    {
        if (!$promoResult) return null;

        $ruleApplied = null;
        if (isset($promoResult['applied_rule'])) {
            $appliedRule = $promoResult['applied_rule'];
            if (is_array($appliedRule)) {
                $ruleApplied = $appliedRule['name']
                            ?? $appliedRule['rule']
                            ?? $appliedRule['label']
                            ?? $appliedRule['rule_name']
                            ?? $appliedRule['description']
                            ?? null;
            } elseif (is_string($appliedRule)) {
                $ruleApplied = $appliedRule;
            }
        }

        if (!$ruleApplied && isset($promoResult['status'])) {
            $ruleApplied = [
                'promoted'      => 'Standard Promotion',
                'trial'         => 'Trial Promotion',
                'see_principal' => 'Principal Review Required',
                'repeated'      => 'Repeat Year',
                'awaiting'      => 'Awaiting Decision',
            ][$promoResult['status']] ?? 'Unknown Rule';
        }

        return $ruleApplied;
    }

    // =========================================================================
    // HELPER: position map
    // =========================================================================

    private function buildPositionMap(array $studentRows, string $key): array
    {
        $eligible = [];
        foreach ($studentRows as $sid => $row) {
            $n = (int) ($row['num_subjects'] ?? 0);
            if ($n <= 0 && (float) ($row[$key] ?? 0) == 0.0) {
                continue;
            }
            $eligible[$sid] = $row;
        }

        uasort($eligible, fn ($a, $b) => ($b[$key] ?? 0) <=> ($a[$key] ?? 0));

        $positionMap = [];
        $prevVal     = null;
        $prevPos     = 0;
        $counter     = 0;

        foreach ($eligible as $sid => $row) {
            $counter++;
            $val = round((float) ($row[$key] ?? 0), 4);

            if ($prevVal !== null && $val === $prevVal) {
                $positionMap[(int) $sid] = $prevPos;
            } else {
                $positionMap[(int) $sid] = $counter;
                $prevPos = $counter;
            }
            $prevVal = $val;
        }

        return $positionMap;
    }

    // =========================================================================
    // HELPER: subject stats
    // =========================================================================

    private function buildSubjectStats(array $subjectsMap, array $studentRows): array
    {
        $subjectStats = [];
        foreach ($subjectsMap as $sub => $subInfo) {
            $totals = [];
            foreach ($studentRows as $row) {
                $val = $row['subjects'][$sub]['total'] ?? 0;
                if ($val > 0) $totals[] = $val;
            }
            $count = count($totals);
            $subjectStats[$sub] = [
                'avg'     => $count > 0 ? round(array_sum($totals) / $count, 1) : 0,
                'highest' => $count > 0 ? max($totals) : 0,
                'lowest'  => $count > 0 ? min($totals) : 0,
                'passed'  => count(array_filter($totals, fn ($v) => $v >= 40)),
                'failed'  => count(array_filter($totals, fn ($v) => $v < 40)),
            ];
        }
        return $subjectStats;
    }

    // =========================================================================
    // HELPER: empty result
    // =========================================================================

    private function emptyBroadsheetResult(
        $schoolInfo, $schoolclass, $schoolsession, $schoolterm,
        $subjectsMap, array $selectedColumns, array $extra = []
    ): array {
        return array_merge([
            'schoolInfo'      => $schoolInfo,
            'schoolclass'     => $schoolclass,
            'schoolsession'   => $schoolsession,
            'schoolterm'      => $schoolterm,
            'subjects'        => $subjectsMap,
            'studentRows'     => [],
            'subjectStats'    => [],
            'selectedColumns' => $selectedColumns,
            'totalStudents'   => 0,
            'generatedAt'     => now()->format('d M Y, H:i'),
        ], $extra);
    }

    // =========================================================================
    // WEB VIEW
    // =========================================================================

    public function webView(Request $request): View|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'schoolclassid'   => 'required|integer|exists:schoolclass,id',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'grade_basis'     => 'nullable|in:total,cum',
                'rank_by'         => 'nullable|string',
            ]);

            $data = $this->buildBroadsheetData(
                (int) $validated['schoolclassid'],
                (int) $validated['sessionid'],
                (int) $validated['termid'],
                $request->input('selectedColumns', []),
                $request->input('grade_basis', 'cum')
            );

            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);
            $data['pagetitle']          = 'Class Broadsheet – Web View';

            $data = $this->attachRanking($data, $request);

            return view('broadsheet.web', $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Invalid input.');
        } catch (\Throwable $e) {
            Log::error('Broadsheet web view error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate broadsheet: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // STUDENT LIST — single class OR combined arms (classgroup)
    // =========================================================================

    public function studentList(Request $request): View|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'schoolclassid'        => 'nullable|required_without:classgroup|integer|exists:schoolclass,id',
                'classgroup'           => 'nullable|required_without:schoolclassid|string',
                'sessionid'            => 'required|integer|exists:schoolsession,id',
                'termid'               => 'required|integer',
                'list_fields'          => 'nullable|array',
                'recommendation_order' => 'nullable|array',
                'show_photos'          => 'nullable',
                'show_sn'              => 'nullable',
                'grade_basis'          => 'nullable|in:total,cum',
            ]);

            $gradeBasis = $request->input('grade_basis', 'cum');

            $data = !empty($validated['schoolclassid'])
                ? $this->buildBroadsheetData(
                    (int) $validated['schoolclassid'],
                    (int) $validated['sessionid'],
                    (int) $validated['termid'],
                    [],
                    $gradeBasis
                )
                : $this->buildAllClassesBroadsheetData(
                    (string) $validated['classgroup'],
                    (int) $validated['sessionid'],
                    (int) $validated['termid'],
                    [],
                    $gradeBasis
                );

            $listFields = $request->input('list_fields', []);
            if (empty($listFields)) {
                $listFields = ['admissionno', 'firstname', 'lastname', 'arm', 'total_cum', 'cum_ave', 'position_cum'];
            }

            $recommendationOrder = $request->input('recommendation_order', [
                'promoted', 'trial', 'see_principal', 'repeated', 'awaiting',
            ]);
            $showPhotos = filter_var($request->input('show_photos', false), FILTER_VALIDATE_BOOLEAN);
            $showSn     = filter_var($request->input('show_sn', true), FILTER_VALIDATE_BOOLEAN);

            $grouped = [];
            foreach ($recommendationOrder as $status) {
                $grouped[$status] = [];
            }
            $grouped['__other'] = [];

            foreach ($data['studentRows'] as $stu) {
                $status = $stu['promotion_status'] ?? 'awaiting';
                if (array_key_exists($status, $grouped)) {
                    $grouped[$status][] = $stu;
                } else {
                    $grouped['__other'][] = $stu;
                }
            }

            $grouped = array_filter($grouped, fn ($g) => count($g) > 0);

            $data['grouped_students']     = $grouped;
            $data['list_fields']          = $listFields;
            $data['recommendation_order'] = $recommendationOrder;
            $data['show_photos']          = $showPhotos;
            $data['show_sn']              = $showSn;
            $data['school_logo_base64']   = $this->getLogoBase64($data['schoolInfo']);
            $data['pagetitle']            = 'Student Promotion List';

            return view('broadsheet.student_list', $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Invalid input.');
        } catch (\Throwable $e) {
            Log::error('Student list error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate student list: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // EXPORT PDF
    // =========================================================================

    public function exportPdf(Request $request): \Illuminate\Http\Response|RedirectResponse
    {
        try {
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            $validated = $request->validate([
                'schoolclassid'   => 'required|integer|exists:schoolclass,id',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'paper_size'      => 'nullable|in:A0,A1,A2,A3,A4',
                'orientation'     => 'nullable|in:portrait,landscape',
                'grade_basis'     => 'nullable|in:total,cum',
            ]);

            $selectedColumns = $request->input('selectedColumns', []);
            $orientation     = $request->input('orientation', 'landscape');
            $paperSize       = $request->input('paper_size', 'A3');
            $gradeBasis      = $request->input('grade_basis', 'cum');

            $data = $this->buildBroadsheetData(
                (int) $validated['schoolclassid'],
                (int) $validated['sessionid'],
                (int) $validated['termid'],
                $selectedColumns,
                $gradeBasis
            );
            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);

            [$widthPt, $heightPt] = $this->computePdfDimensions(
                $paperSize,
                count($data['subjects'] ?? []),
                $this->countActivePerSubjectCols($selectedColumns)
            );
            $data['pdf_width_pt']   = $widthPt;
            $data['pdf_height_pt']  = $heightPt;
            $data['pdf_paper_size'] = $paperSize;

            $pdf = Pdf::loadView('broadsheet.pdf', $data)
                ->setPaper([0, 0, $widthPt, $heightPt], $orientation)
                ->setOptions([
                    'isHtml5ParserEnabled'    => true,
                    'isRemoteEnabled'         => true,
                    'isFontSubsettingEnabled' => true,
                    'defaultFont'             => 'DejaVu Sans',
                    'dpi'                     => 96,
                    'enable_css_float'        => false,
                    'enable_javascript'       => false,
                ]);

            return $pdf->stream($this->buildFilename(
                ($data['schoolclass']->schoolclass ?? 'Class') . ' ' . ($data['schoolclass']->arm_name ?? ''),
                $data['schoolsession']->session ?? '',
                $data['schoolterm']->term ?? 'Term'
            ));
        } catch (\Throwable $e) {
            Log::error('Broadsheet PDF export error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // EXPORT EXCEL
    // =========================================================================

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'schoolclassid'   => 'required|integer|exists:schoolclass,id',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'grade_basis'     => 'nullable|in:total,cum',
            ]);

            $data = $this->buildBroadsheetData(
                (int) $validated['schoolclassid'],
                (int) $validated['sessionid'],
                (int) $validated['termid'],
                $request->input('selectedColumns', []),
                $request->input('grade_basis', 'cum')
            );

            return Excel::download(
                new \App\Exports\BroadsheetExport($data),
                $this->buildFilename(
                    ($data['schoolclass']->schoolclass ?? 'Class') . ' ' . ($data['schoolclass']->arm_name ?? ''),
                    $data['schoolsession']->session ?? '',
                    $data['schoolterm']->term ?? 'Term',
                    'xlsx'
                )
            );
        } catch (\Throwable $e) {
            Log::error('Broadsheet Excel export error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate Excel: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // ALL CLASSES
    // =========================================================================

    public function allClassesWebView(Request $request): View|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'classgroup'      => 'required|string',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'grade_basis'     => 'nullable|in:total,cum',
                'rank_by'         => 'nullable|string',
            ]);

            $data = $this->buildAllClassesBroadsheetData(
                $validated['classgroup'],
                (int) $validated['sessionid'],
                (int) $validated['termid'],
                $request->input('selectedColumns', []),
                $request->input('grade_basis', 'cum')
            );

            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);
            $data['pagetitle']          = 'All Classes Broadsheet – ' . $validated['classgroup'];
            $data['is_combined']        = true;

            $data = $this->attachRanking($data, $request);

            return view('broadsheet.web', $data);
        } catch (\Throwable $e) {
            Log::error('All-classes broadsheet error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate broadsheet: ' . $e->getMessage());
        }
    }

    public function allClassesExportPdf(Request $request): \Illuminate\Http\Response|RedirectResponse
    {
        try {
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            $validated = $request->validate([
                'classgroup'      => 'required|string',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'paper_size'      => 'nullable|in:A0,A1,A2,A3,A4',
                'orientation'     => 'nullable|in:portrait,landscape',
                'grade_basis'     => 'nullable|in:total,cum',
            ]);

            $selectedColumns = $request->input('selectedColumns', []);
            $orientation     = $request->input('orientation', 'landscape');
            $paperSize       = $request->input('paper_size', 'A2');

            $data = $this->buildAllClassesBroadsheetData(
                $validated['classgroup'],
                (int) $validated['sessionid'],
                (int) $validated['termid'],
                $selectedColumns,
                $request->input('grade_basis', 'cum')
            );
            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);
            $data['is_combined']        = true;

            [$widthPt, $heightPt] = $this->computePdfDimensions(
                $paperSize,
                count($data['subjects'] ?? []),
                $this->countActivePerSubjectCols($selectedColumns)
            );
            $data['pdf_width_pt']   = $widthPt;
            $data['pdf_height_pt']  = $heightPt;
            $data['pdf_paper_size'] = $paperSize;

            $pdf = Pdf::loadView('broadsheet.pdf', $data)
                ->setPaper([0, 0, $widthPt, $heightPt], $orientation)
                ->setOptions([
                    'isHtml5ParserEnabled'    => true,
                    'isRemoteEnabled'         => true,
                    'isFontSubsettingEnabled' => true,
                    'defaultFont'             => 'DejaVu Sans',
                    'dpi'                     => 96,
                ]);

            return $pdf->stream($this->buildFilename(
                $validated['classgroup'],
                $data['schoolsession']->session ?? '',
                $data['schoolterm']->term ?? ''
            ));
        } catch (\Throwable $e) {
            Log::error('All-classes PDF error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    private function buildAllClassesBroadsheetData(
        string $classgroup,
        int    $sessionid,
        int    $termid,
        array  $selectedColumns = [],
        string $gradeBasis = 'cum'
    ): array {
        $schoolInfo    = SchoolInformation::getActiveSchool() ?? new \stdClass();
        $schoolsession = Schoolsession::find($sessionid);
        $schoolterm    = Schoolterm::find($termid);

        $matchingClasses = Schoolclass::with('classcategory')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.schoolclass', $classgroup)
            ->orderBy('schoolarm.arm')
            ->get();

        $firstClass = $matchingClasses->first();

        $combinedClass = (object) [
            'schoolclass'   => $classgroup,
            'arm_name'      => $matchingClasses->isEmpty()
                ? '(All Arms)'
                : '(' . $matchingClasses->pluck('arm_name')->filter()->implode(', ') . ')',
            'id'            => null,
            'classcategory' => $firstClass ? $firstClass->classcategory : null,
        ];

        if ($matchingClasses->isEmpty()) {
            return $this->emptyBroadsheetResult(
                $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
                [], $selectedColumns,
                ['classgroup' => $classgroup, 'arm_labels' => [], 'is_combined' => true, 'grade_basis' => $gradeBasis]
            );
        }

        $classIds = $matchingClasses->pluck('id')->map(fn ($v) => (int) $v)->toArray();

        foreach ($classIds as $clsId) {
            $this->recalculatePositionsForClass($clsId, $termid, $sessionid);
        }

        $subjectsMap    = [];
        $subjectClasses = DB::table('subjectclass as sc')
            ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
            ->join('subject', 'subject.id', '=', 'sc.subjectid')
            ->whereIn('sc.schoolclassid', $classIds)
            ->where('st.termid', $termid)
            ->where('st.sessionid', $sessionid)
            ->select(['sc.subjectid', 'subject.subject as subject_name', 'subject.subject_code'])
            ->distinct()
            ->get();

        foreach ($subjectClasses as $sc) {
            $subjectsMap[(int) $sc->subjectid] = [
                'subject_id'   => (int) $sc->subjectid,
                'subject_name' => $sc->subject_name,
                'subject_code' => $sc->subject_code ?? '',
            ];
        }

        $studentClassRecords = Studentclass::whereIn('schoolclassid', $classIds)
            ->where('sessionid', $sessionid)
            ->get(['studentId', 'schoolclassid']);

        $studentClassMap = [];
        foreach ($studentClassRecords as $r) {
            $studentClassMap[(int) $r->studentId] = (int) $r->schoolclassid;
        }
        $allStudentIds = array_keys($studentClassMap);

        $armLabels = $matchingClasses->pluck('arm_name', 'id')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (string) $v])
            ->toArray();

        if (empty($allStudentIds)) {
            return $this->emptyBroadsheetResult(
                $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
                $subjectsMap, $selectedColumns,
                ['classgroup' => $classgroup, 'arm_labels' => $armLabels, 'is_combined' => true, 'grade_basis' => $gradeBasis]
            );
        }

        $prevCumMap = $this->fetchPreviousTermCums($allStudentIds, $sessionid, $termid);

        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $allStudentIds)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->whereIn('broadsheet_records.schoolclass_id', $classIds)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->select($this->broadsheetSelectColumns())
            ->get();

        $studentSubjectMap = $this->pivotScores($broadsheets, $subjectsMap, $prevCumMap, $termid);

        return $this->assembleStudentRows(
            $allStudentIds, $sessionid, null, $classIds,
            $studentSubjectMap, $subjectsMap,
            $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
            $selectedColumns, $armLabels, $studentClassMap, true, $gradeBasis
        );
    }

    // =========================================================================
    // RANKING — UNOFFICIAL best-student panel on the web view
    // Operates only on assembled $studentRows; never touches official positions.
    // Best-effort — failures are logged, not fatal.
    // =========================================================================

    private function attachRanking(array $data, Request $request): array
    {
        try {
            $rows = $data['studentRows'] ?? [];
            if (empty($rows)) return $data;

            $classes = !empty($data['is_combined'])
                ? Schoolclass::with('classcategory')->where('schoolclass', (string) $request->input('classgroup'))->get()
                : Schoolclass::with('classcategory')->where('id', (int) $request->input('schoolclassid'))->get();

            $classIds = $classes->pluck('id')->map(fn ($v) => (int) $v)->all();
            $isSenior = (bool) optional(optional($classes->first())->classcategory)->is_senior;
            $settings = BroadsheetRankingSetting::forSection($isSenior ? 'senior' : 'junior');

            $compulsory = $settings->require_all_compulsory
                ? $this->compulsorySubjectIds($classIds, (int) $request->input('sessionid'))
                : [];

            $ranking = $this->rankingService->rank(
                $rows,
                $settings,
                $request->input('rank_by'),
                $isSenior,
                $compulsory,
                $data['subjects'] ?? [],
                $data['grade_basis'] ?? 'cum'
            );

            $data['ranking']  = $ranking;
            $data['rank_map'] = $ranking['rank_map'];
        } catch (\Throwable $e) {
            Log::warning('Broadsheet ranking attach failed: ' . $e->getMessage());
        }

        return $data;
    }

    private function compulsorySubjectIds(array $classIds, int $sessionid): array
    {
        if (empty($classIds) || !Schema::hasTable('compulsory_subject_classes')) {
            return [];
        }

        return DB::table('compulsory_subject_classes')
            ->whereIn('schoolclassid', $classIds)
            ->when($sessionid, fn ($q) => $q->where('sessionid', $sessionid))
            ->pluck('subjectId')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
    }

    // =========================================================================
    // BEST STUDENTS REPORT — any mix of whole classes (all arms) and single arms
    // =========================================================================

    public function bestStudents(Request $request): View
    {
        return view('broadsheet.best-students', $this->bestStudentsFormData() + [
            'pagetitle' => 'Best Students Report',
            'report'    => null,
            'input'     => [],
        ]);
    }

    public function bestStudentsReport(Request $request): View|RedirectResponse
    {
        $measureKeys = implode(',', array_keys(BroadsheetRankingSetting::MEASURES));

        $v = $request->validate([
            'class_groups'   => 'nullable|array',
            'class_groups.*' => 'string',
            'class_ids'      => 'nullable|array',
            'class_ids.*'    => 'integer',
            'sessionid'      => 'required|integer|exists:schoolsession,id',
            'termid'         => 'required|integer|exists:schoolterm,id',
            'measure'        => "required|in:{$measureKeys}",
            'top_n'          => 'required|integer|min:1|max:20',
            'subject_top_n'  => 'required|integer|min:1|max:10',
            'min_subjects'   => 'nullable|integer|min:0|max:40',
            'grade_basis'    => 'required|in:cum,total',
        ]);

        $groups = array_values(array_filter((array) ($v['class_groups'] ?? [])));
        $ids    = array_map('intval', (array) ($v['class_ids'] ?? []));

        if (empty($groups) && empty($ids)) {
            return back()->withErrors(['class_ids' => 'Select at least one class or arm.'])->withInput();
        }

        try {
            @ini_set('max_execution_time', 300);

            // ── Resolve selection into concrete class (arm) ids ──
            $classes = Schoolclass::with('classcategory')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
                ->where(function ($q) use ($groups, $ids) {
                    if ($groups) $q->orWhereIn('schoolclass.schoolclass', $groups);
                    if ($ids)    $q->orWhereIn('schoolclass.id', $ids);
                })
                ->orderBy('schoolclass.schoolclass')
                ->orderBy('schoolarm.arm')
                ->get();

            $sessionid = (int) $v['sessionid'];
            $termid    = (int) $v['termid'];
            $basis     = $v['grade_basis'];

            // ── Build lite rows per arm and merge ──
            $allRows     = [];
            $subjectsMap = [];
            $rowsByGroup = [];
            foreach ($classes as $cls) {
                $data = $this->buildBroadsheetData((int) $cls->id, $sessionid, $termid, [], $basis, true);
                foreach ($data['subjects'] as $subId => $info) {
                    $subjectsMap[$subId] = $subjectsMap[$subId] ?? $info;
                }
                foreach ($data['studentRows'] as $row) {
                    $allRows[(int) $row['id']]                  = $row;
                    $rowsByGroup[$row['class_name']][$row['id']] = $row;
                }
            }
            uasort($subjectsMap, fn ($a, $b) => strcmp($a['subject_name'], $b['subject_name']));

            // ── Ranking settings from the form (core subjects from saved settings) ──
            $core = array_values(array_unique(array_merge(
                (array) BroadsheetRankingSetting::forSection('junior')->core_subject_ids,
                (array) BroadsheetRankingSetting::forSection('senior')->core_subject_ids
            )));

            $setting = new BroadsheetRankingSetting([
                'section'          => 'report',
                'primary_measure'  => $v['measure'],
                'tiebreakers'      => array_values(array_diff(['distinctions', 'lowest_score', 'cum_ave'], [$v['measure']])),
                'min_subjects'     => (int) ($v['min_subjects'] ?? 0),
                'exclude_failed'   => $request->boolean('exclude_failed'),
                'scope'            => 'both',
                'top_n'            => (int) $v['top_n'],
                'subject_top_n'    => (int) $v['subject_top_n'],
                'core_subject_ids' => $core,
            ]);

            // Overall across the whole selection + per arm + per subject
            $overall = $this->rankingService->rank(array_values($allRows), $setting, null, false, [], $subjectsMap, $basis);

            // Per class (all selected arms of each class together)
            $byClass = [];
            ksort($rowsByGroup, SORT_NATURAL);
            foreach ($rowsByGroup as $className => $rows) {
                $r = $this->rankingService->rank(array_values($rows), $setting, null, false, [], $subjectsMap, $basis);
                $byClass[$className] = [
                    'top'        => $r['overall'],
                    'arms'       => count(array_unique(array_column($rows, 'schoolclassid'))),
                    'students'   => count($rows),
                    'by_subject' => $r['by_subject'],
                ];
            }

            $report = [
                'overall'        => $overall['overall'],
                'by_arm'         => $overall['by_arm'],
                'by_subject'     => $overall['by_subject'],
                'by_class'       => $byClass,
                'measure_label'  => $overall['measure_label'],
                'tiebreakers'    => $overall['tiebreakers'],
                'basis'          => $basis,
                'eligible_count' => $overall['eligible_count'],
                'excluded_count' => count($overall['excluded']),
                'student_count'  => count($allRows),
                'selection'      => $classes->map(fn ($c) => trim($c->schoolclass . ' ' . ($c->arm_name ?? '')))->all(),
                'session'        => optional(Schoolsession::find($sessionid))->session,
                'term'           => optional(Schoolterm::find($termid))->term,
                'generatedAt'    => now()->format('d M Y, H:i'),
            ];

            return view('broadsheet.best-students', $this->bestStudentsFormData() + [
                'pagetitle'          => 'Best Students Report',
                'report'             => $report,
                'input'              => $request->all(),
                'schoolInfo'         => SchoolInformation::getActiveSchool() ?? new \stdClass(),
                'school_logo_base64' => $this->getLogoBase64(SchoolInformation::getActiveSchool()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Best students report error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage())->withInput();
        }
    }

    private function bestStudentsFormData(): array
    {
        $classes = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm'])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        return [
            'classesByGroup' => $classes->groupBy('schoolclass'),
            'schoolsessions' => Schoolsession::orderByDesc('id')->get(),
            'schoolterms'    => Schoolterm::all(),
            'measures'       => BroadsheetRankingSetting::MEASURES,
        ];
    }

    // =========================================================================
    // AJAX: class groups
    // =========================================================================

    public function getClassGroups(): JsonResponse
    {
        try {
            $groups = Schoolclass::select('schoolclass')->distinct()->orderBy('schoolclass')->pluck('schoolclass');
            return response()->json(['success' => true, 'groups' => $groups]);
        } catch (\Throwable $e) {
            Log::error('getClassGroups error: ' . $e->getMessage());
            return response()->json(['success' => false, 'groups' => []], 500);
        }
    }

    // =========================================================================
    // PDF HELPERS
    // =========================================================================

    private function countActivePerSubjectCols(array $selectedColumns): float
    {
        $cols      = 0.0;
        $showAll   = empty($selectedColumns);
        $scoreCols = ['ca1', 'ca2', 'ca3', 'exam', 'total', 'bf', 'cum', 'grade',
                      'pos_class_cum', 'pos_class_total', 'pos_arm_total', 'pos_arm_cum',
                      'class_average', 'remark'];

        foreach ($scoreCols as $col) {
            if ($showAll || in_array($col, $selectedColumns)) $cols++;
        }
        return $cols;
    }

    private function computePdfDimensions(string $paperSize, int $subjectCount, float $perSubjCols): array
    {
        $heights = ['A0' => 2384, 'A1' => 1684, 'A2' => 1190, 'A3' => 842,  'A4' => 595];
        $widths  = ['A0' => 3370, 'A1' => 2384, 'A2' => 1684, 'A3' => 1190, 'A4' => 842];
        $needed  = 200 + ($subjectCount * max(1, ceil($perSubjCols)) * 22) + 57;

        return [
            max($widths[$paperSize] ?? 1190, $needed + 100),
            $heights[$paperSize] ?? 842,
        ];
    }

    private function buildFilename(string $class, string $session, string $term, string $ext = 'pdf'): string
    {
        $c = fn (string $s) => preg_replace('/[^A-Za-z0-9_\-]/', '_', trim($s));
        return 'Broadsheet_' . $c($class) . '_' . $c($session) . '_' . $c($term) . '.' . $ext;
    }

    // =========================================================================
    // PROMOTION HELPERS — same behaviour as PromotionController
    // =========================================================================

    private function getStudentScoresForPromotion(
        int $studentId,
        int $schoolclassId,
        int $sessionId,
        int $termId
    ): \Illuminate\Support\Collection {
        try {
            return Broadsheets::where('broadsheet_records.student_id', $studentId)
                ->where('broadsheets.term_id', $termId)
                ->where('broadsheet_records.session_id', $sessionId)
                ->where('broadsheet_records.schoolclass_id', $schoolclassId)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->select([
                    'subject.id           as subject_id',
                    'subject.subject      as subject_name',
                    'subject.subject_code as subject_code',
                    'broadsheets.total    as total',
                    'broadsheets.cum      as cum',
                    'broadsheets.grade    as grade',
                ])
                ->get();
        } catch (\Throwable $e) {
            Log::error('getStudentScoresForPromotion failed', ['student_id' => $studentId, 'error' => $e->getMessage()]);
            return collect();
        }
    }

    private function classHasNoApplicableSetting(int $schoolclassId, int $sessionId, int $termId): bool
    {
        $activeSettings = PromotionSetting::where('schoolclass_id', $schoolclassId)
            ->where('is_active', true)
            ->get(['id', 'session_id', 'term_id']);

        if ($activeSettings->isEmpty()) {
            return true;
        }

        foreach ($activeSettings as $setting) {
            $sid = $setting->session_id;
            $tid = $setting->term_id;

            if ($sid == $sessionId && $tid == $termId)                return false;
            if ($sid == $sessionId && $tid === null)                  return false;
            if ($sid === null      && $tid == $termId)                return false;
            if ($sid === null      && $tid === null)                  return false;
            if ($sid !== null && $sid != $sessionId && $tid === null) return false;
        }

        return true;
    }

    private function getLogoBase64($schoolInfo): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">'
            . '<rect width="80" height="80" rx="40" fill="#1e3a5f"/>'
            . '<text x="40" y="45" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">SCH</text>'
            . '</svg>'
        );

        if (!$schoolInfo || empty($schoolInfo->school_logo)) return $placeholder;

        foreach ([
            storage_path('app/public/' . $schoolInfo->school_logo),
            public_path('storage/' . $schoolInfo->school_logo),
            public_path($schoolInfo->school_logo),
        ] as $path) {
            if (file_exists($path) && filesize($path) > 100) {
                return 'data:' . (mime_content_type($path) ?: 'image/jpeg')
                    . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        return $placeholder;
    }
}
