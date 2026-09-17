<?php
// app/Http/Controllers/BroadsheetController.php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\PromotionSetting;
use App\Models\Studentclass;
use App\Services\PromotionEvaluator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class BroadsheetController extends Controller
{
    private PromotionEvaluator $promotionEvaluator;

    public function __construct(PromotionEvaluator $promotionEvaluator)
    {
        $this->middleware('permission:View student-report');
        $this->promotionEvaluator = $promotionEvaluator;
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
                    'ca1'             => ['label' => 'CA1',                  'default' => true],
                    'ca2'             => ['label' => 'CA2',                  'default' => true],
                    'ca3'             => ['label' => 'CA3',                  'default' => true],
                    'exam'            => ['label' => 'Exam',                 'default' => true],
                    'total'           => ['label' => 'Total',                'default' => true],
                    'bf'              => ['label' => 'BF',                   'default' => true],
                    'cum'             => ['label' => 'Cum (raw sum)',        'default' => true],
                    'grade'           => ['label' => 'Grade',                'default' => true],
                    'pos_class_cum'   => ['label' => 'Class Pos (Cum)',      'default' => true],
                    'pos_class_total' => ['label' => 'Class Pos (Total)',    'default' => false],
                    'pos_arm_total'   => ['label' => 'Arm Pos (Total)',      'default' => true],
                    'pos_arm_cum'     => ['label' => 'Arm Pos (Cum)',        'default' => true],
                    'class_average'   => ['label' => 'Class Avg',            'default' => true],
                    'remark'          => ['label' => 'Remark',               'default' => false],
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

            // ── Class group path ────────────────────────────────────────
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

            // ── Single class path ───────────────────────────────────────
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

    /**
     * Recompute all 4 position dimensions for every subjectclass in a class.
     * Silently swallows all errors — callers proceed regardless.
     */
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
     * Recompute all 4 position dimensions for a single subjectclass.
     * Silently swallows all errors.
     */
    protected function recalculatePositionsForSubjectClass(int $subjectclassid, int $termid, int $sessionid): void
    {
        try {
            $subjectClass = DB::table('subjectclass')
                ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->where('subjectclass.id', $subjectclassid)
                ->first(['subjectclass.schoolclassid', 'subjectteacher.subjectid']);

            if (!$subjectClass) return;

            $subjectId     = $subjectClass->subjectid;
            $schoolclassId = $subjectClass->schoolclassid;

            $baseClass = DB::table('schoolclass')
                ->where('id', $schoolclassId)
                ->first(['schoolclass', 'classcategoryid']);
            if (!$baseClass) return;

            $allArmIds = DB::table('schoolclass')
                ->where('schoolclass', $baseClass->schoolclass)
                ->where('classcategoryid', $baseClass->classcategoryid)
                ->pluck('id');
            if ($allArmIds->isEmpty()) return;

            $allSubjectClassIds = DB::table('subjectclass')
                ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->whereIn('subjectclass.schoolclassid', $allArmIds)
                ->where('subjectteacher.subjectid', $subjectId)
                ->pluck('subjectclass.id');
            if ($allSubjectClassIds->isEmpty()) return;

            $allStudents = DB::table('broadsheets')
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->whereIn('broadsheets.subjectclass_id', $allSubjectClassIds)
                ->where('broadsheets.term_id', $termid)
                ->where('broadsheet_records.session_id', $sessionid)
                ->get([
                    'broadsheets.id',
                    'broadsheets.cum',
                    'broadsheets.total',
                    'broadsheet_records.schoolclass_id',
                ]);

            if ($allStudents->isEmpty()) return;

            // 1) Class-wide rank by cum
            $this->applyDenseRank($allStudents, 'cum',   'subject_position_class');

            // 2) Class-wide rank by total
            $this->applyDenseRank($allStudents, 'total', 'subject_position_class_total');

            // 3 & 4) Per-arm ranks
            foreach ($allStudents->groupBy('schoolclass_id') as $armStudents) {
                $this->applyDenseRank($armStudents, 'total', 'arm_position');
                $this->applyDenseRank($armStudents, 'cum',   'arm_position_cum');
            }
        } catch (\Throwable $e) {
            Log::warning('recalculatePositionsForSubjectClass skipped: ' . $e->getMessage(), [
                'subjectclass_id' => $subjectclassid,
                'term_id'         => $termid,
                'session_id'      => $sessionid,
            ]);
        }
    }

    /**
     * Dense-rank rows by numeric key. Ties share rank; next distinct value
     * gets rank = its 1-based index. Silently swallows write errors.
     */
    protected function applyDenseRank($rows, string $sortKey, string $column): void
    {
        try {
            $sorted  = $rows->sortByDesc(fn ($r) => (float) ($r->$sortKey ?? 0))->values();
            $lastVal = null;
            $rank    = 0;

            foreach ($sorted as $idx => $row) {
                $currentVal = (float) ($row->$sortKey ?? 0);

                if ($lastVal === null || $currentVal !== $lastVal) {
                    $rank    = $idx + 1;
                    $lastVal = $currentVal;
                }

                DB::table('broadsheets')
                    ->where('id', $row->id)
                    ->update([$column => $rank]);
            }
        } catch (\Throwable $e) {
            Log::warning('applyDenseRank skipped: ' . $e->getMessage(), ['column' => $column]);
        }
    }

    // =========================================================================
    // HELPER: fetch previous term's cum for BF computation
    // =========================================================================

    private function fetchPreviousTermCums(
        array $studentIds,
        int   $sessionid,
        int   $currentTermId,
        array $classIds
    ): array {
        if (empty($studentIds)) return [];

        try {
            $prevTerm = Schoolterm::where('id', '<', $currentTermId)
                ->orderByDesc('id')
                ->first();
            if (!$prevTerm) return [];

            $rows = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
                ->where('broadsheets.term_id', $prevTerm->id)
                ->where('broadsheet_records.session_id', $sessionid)
                ->whereIn('broadsheet_records.schoolclass_id', $classIds)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->select([
                    'broadsheet_records.student_id',
                    'broadsheet_records.subject_id',
                    'broadsheets.cum',
                ])
                ->get();

            $map = [];
            foreach ($rows as $r) {
                $map[(int) $r->student_id][(int) $r->subject_id] = (float) $r->cum;
            }
            return $map;
        } catch (\Throwable $e) {
            Log::warning('fetchPreviousTermCums failed: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // BUILD BROADSHEET DATA (single class)
    // =========================================================================

    private function buildBroadsheetData(
        int    $schoolclassid,
        int    $sessionid,
        int    $termid,
        array  $selectedColumns = [],
        string $gradeBasis = 'cum'
    ): array {
        // ── Recompute positions (fully isolated, cannot throw) ──────
        $this->recalculatePositionsForClass($schoolclassid, $termid, $sessionid);

        $schoolInfo  = SchoolInformation::getActiveSchool() ?? new \stdClass();
        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.id', $schoolclassid)
            ->first();

        $schoolsession = Schoolsession::find($sessionid);
        $schoolterm    = Schoolterm::find($termid);

        // ── Subject list ────────────────────────────────────────────
        $subjectsMap    = [];
        $subjectClasses = DB::table('subjectclass as sc')
            ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
            ->join('subject', 'subject.id', '=', 'sc.subjectid')
            ->where('sc.schoolclassid', $schoolclassid)
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

        // ── Student roster ──────────────────────────────────────────
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

        // ── Previous term cums (for BF) ─────────────────────────────
        $prevCumMap = $this->fetchPreviousTermCums(
            $studentIds, $sessionid, $termid, [$schoolclassid]
        );

        // ── Pull broadsheets ────────────────────────────────────────
        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.schoolclass_id', $schoolclassid)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'broadsheets.id as broadsheet_id',
                'broadsheet_records.student_id',
                'broadsheet_records.subject_id',
                'subject.subject as subject_name',
                'subject.subject_code',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.gender',
                'studentpicture.picture',
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
                'broadsheets.vettedstatus',
            ])
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->orderBy('subject.subject')
            ->get();

        // ── Pivot ───────────────────────────────────────────────────
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

            $ca1  = (float) ($row->ca1 ?? 0);
            $ca2  = (float) ($row->ca2 ?? 0);
            $ca3  = (float) ($row->ca3 ?? 0);
            $exam = (float) ($row->exam ?? 0);

            $caAvg = ($ca1 + $ca2 + $ca3) / 3;
            $total = round(($caAvg + $exam) / 2, 1);

            $prevCum = $prevCumMap[$sid][$sub] ?? null;
            if ($prevCum !== null && $prevCum > 0) {
                $bf = $prevCum;
            } elseif (!empty($row->bf) && (float) $row->bf > 0) {
                $bf = (float) $row->bf;
            } else {
                $bf = 0.0;
            }

            $cum = $termid == 1 ? $total : round(($bf + $total) / 2, 2);

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

        return $this->assembleStudentRows(
            $studentIds, $sessionid, $schoolclassid, null,
            $studentSubjectMap, $subjectsMap,
            $schoolInfo, $schoolclass, $schoolsession, $schoolterm,
            $selectedColumns, [], null, false, $gradeBasis
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
        string $gradeBasis      = 'cum'
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
        $shouldEvalPromo = $termid && $sessionid;

        $studentRows = [];
        foreach ($studentInfoRows as $stu) {
            $sid       = (int) $stu->id;
            $subScores = $studentSubjectMap[$sid] ?? [];

            $termTotals = [];
            $cumValues  = [];
            foreach ($subScores as $subData) {
                if (($subData['total'] ?? 0) > 0) $termTotals[] = $subData['total'];
                if (($subData['cum']   ?? 0) > 0) $cumValues[]  = $subData['cum'];
            }

            $totalTerm   = array_sum($termTotals);
            $totalCum    = array_sum($cumValues);
            $numSubjects = count($cumValues);
            $classAvg    = $numSubjects > 0 ? round($totalCum / $numSubjects, 1) : 0;

            // ── Arm label ──────────────────────────────────────────
            $armLabel = '';
            if ($isCombined && $studentClassMap && isset($studentClassMap[$sid])) {
                $armLabel = $armLabels[$studentClassMap[$sid]] ?? '';
            } elseif ($schoolclassid) {
                $studentClass = Studentclass::where('studentId', $sid)
                    ->where('sessionid', $sessionid)
                    ->first();
                if ($studentClass && $studentClass->schoolclassid) {
                    $class = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                        ->where('schoolclass.id', $studentClass->schoolclassid)
                        ->first(['schoolclass.*', 'schoolarm.arm as arm_name']);
                    if ($class && $class->arm_name) {
                        $armLabel = $class->arm_name;
                    }
                }
            }

            // ── Promotion evaluation (aligned with PromotionController) ──
            $promoResult = null;
            if ($shouldEvalPromo) {
                $evalClassId = ($isCombined && $studentClassMap && isset($studentClassMap[$sid]))
                    ? (int) $studentClassMap[$sid]
                    : (int) $schoolclassid;

                $averageBasis = in_array($gradeBasis, ['total', 'cum'], true)
                    ? $gradeBasis
                    : 'total';

                try {
                    $scoresForPromo = $this->getStudentScoresForPromotion(
                        $sid,
                        $evalClassId,
                        (int) $sessionid,
                        (int) $termid
                    );

                    $overallAverage = $this->promotionEvaluator->computeOverallAverage(
                        $scoresForPromo,
                        $averageBasis
                    );

                    $shouldSkip = $this->classHasNoApplicableSetting(
                        $evalClassId,
                        (int) $sessionid,
                        (int) $termid
                    );

                    if ($shouldSkip) {
                        $promoResult = $this->promotionEvaluator->awaitingResult(
                            $overallAverage,
                            'No matching promotion setting',
                            $evalClassId
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
                    $promoResult = $this->promotionEvaluator->awaitingResult(
                        null,
                        'Evaluation error',
                        $evalClassId ?? null
                    );
                }
            }

            // ── Rule applied ───────────────────────────────────────
            $ruleApplied = null;
            if ($promoResult && isset($promoResult['applied_rule'])) {
                $appliedRule = $promoResult['applied_rule'];
                if (is_array($appliedRule)) {
                    $ruleApplied = $appliedRule['name']
                                ?? $appliedRule['rule']
                                ?? $appliedRule['label']
                                ?? $appliedRule['rule_name']
                                ?? null;
                    if (!$ruleApplied && isset($appliedRule['description'])) {
                        $ruleApplied = $appliedRule['description'];
                    }
                } elseif (is_string($appliedRule)) {
                    $ruleApplied = $appliedRule;
                }
            }

            if (!$ruleApplied && $promoResult && isset($promoResult['status'])) {
                $statusRuleMap = [
                    'promoted'      => 'Standard Promotion',
                    'trial'         => 'Trial Promotion',
                    'see_principal' => 'Principal Review Required',
                    'repeated'      => 'Repeat Year',
                    'awaiting'      => 'Awaiting Decision',
                ];
                $ruleApplied = $statusRuleMap[$promoResult['status']] ?? 'Unknown Rule';
            }

            $studentRows[$sid] = [
                'id'                     => $sid,
                'admissionno'            => $stu->admissionno,
                'firstname'              => $stu->firstname,
                'lastname'               => $stu->lastname,
                'gender'                 => $stu->gender,
                'dateofbirth'            => $stu->dateofbirth,
                'picture'                => $stu->picture,
                'arm'                    => $armLabel,
                'schoolclassid'          => (int) $stu->schoolclassid,
                'subjects'               => $subScores,
                'total_cum'              => round($totalCum, 1),
                'total_term'             => round($totalTerm, 1),
                'cum_ave'                => $numSubjects > 0 ? round($totalCum / $numSubjects, 1) : 0,
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

        // ── Overall positions ─────────────────────────────────────
        $posMapCum  = $this->buildPositionMap($studentRows, 'total_cum');
        $posMapTerm = $this->buildPositionMap($studentRows, 'total_term');

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

    // =========================================================================
    // HELPER: position map
    // =========================================================================

    private function buildPositionMap(array $studentRows, string $key): array
    {
        $sorted = $studentRows;
        uasort($sorted, fn ($a, $b) => ($b[$key] ?? 0) <=> ($a[$key] ?? 0));

        $positionMap = [];
        $prevVal     = null;
        $prevPos     = 0;
        $counter     = 0;

        foreach ($sorted as $sid => $row) {
            $counter++;
            $val = (float) ($row[$key] ?? 0);

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
            ]);

            $gradeBasis = $request->input('grade_basis', 'cum');

            $data = $this->buildBroadsheetData(
                (int) $validated['schoolclassid'],
                (int) $validated['sessionid'],
                (int) $validated['termid'],
                $request->input('selectedColumns', []),
                $gradeBasis
            );

            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);
            $data['pagetitle']          = 'Class Broadsheet – Web View';

            return view('broadsheet.web', $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Invalid input.');
        } catch (\Throwable $e) {
            Log::error('Broadsheet web view error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate broadsheet: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // STUDENT LIST
    // =========================================================================

    public function studentList(Request $request): View|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'schoolclassid'        => 'required|integer|exists:schoolclass,id',
                'sessionid'            => 'required|integer|exists:schoolsession,id',
                'termid'               => 'required|integer',
                'list_fields'          => 'nullable|array',
                'recommendation_order' => 'nullable|array',
                'show_photos'          => 'nullable',
                'show_sn'              => 'nullable',
                'grade_basis'          => 'nullable|in:total,cum',
            ]);

            $gradeBasis = $request->input('grade_basis', 'cum');

            $data = $this->buildBroadsheetData(
                (int) $validated['schoolclassid'],
                (int) $validated['sessionid'],
                (int) $validated['termid'],
                [],
                $gradeBasis
            );

            $listFields = $request->input('list_fields', []);
            if (empty($listFields)) {
                $listFields = ['admissionno', 'firstname', 'lastname', 'arm',
                               'total_cum', 'cum_ave', 'position_cum'];
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
                    'isHtml5ParserEnabled'     => true,
                    'isRemoteEnabled'          => true,
                    'isFontSubsettingEnabled'  => true,
                    'defaultFont'              => 'DejaVu Sans',
                    'dpi'                      => 96,
                    'enable_css_float'         => false,
                    'enable_javascript'        => false,
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

            $selectedColumns = $request->input('selectedColumns', []);
            $gradeBasis      = $request->input('grade_basis', 'cum');

            $data = $this->buildBroadsheetData(
                $validated['schoolclassid'],
                $validated['sessionid'],
                $validated['termid'],
                $selectedColumns,
                $gradeBasis
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
            $gradeBasis      = $request->input('grade_basis', 'cum');

            $data = $this->buildAllClassesBroadsheetData(
                $validated['classgroup'],
                (int) $validated['sessionid'],
                (int) $validated['termid'],
                $selectedColumns,
                $gradeBasis
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

        $matchingClasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.schoolclass', $classgroup)
            ->orderBy('schoolarm.arm')
            ->get();

        $combinedClass = (object) [
            'schoolclass' => $classgroup,
            'arm_name'    => $matchingClasses->isEmpty()
                ? '(All Arms)'
                : '(' . $matchingClasses->pluck('arm_name')->filter()->implode(', ') . ')',
            'id'          => null,
        ];

        if ($matchingClasses->isEmpty()) {
            return $this->emptyBroadsheetResult(
                $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
                [], $selectedColumns,
                ['classgroup' => $classgroup, 'arm_labels' => [],
                 'is_combined' => true, 'grade_basis' => $gradeBasis]
            );
        }

        $classIds = $matchingClasses->pluck('id')->map(fn ($v) => (int) $v)->toArray();

        // ── Recompute positions for all arms (isolated) ─────────────
        foreach ($classIds as $clsId) {
            $this->recalculatePositionsForClass($clsId, $termid, $sessionid);
        }

        // ── Subjects across arms ────────────────────────────────────
        $subjectsMap    = [];
        $subjectClasses = DB::table('subjectclass as sc')
            ->join('subjectteacher', 'st.id', '=', 'sc.subjectteacherid')
            ->join('subject', 'subject.id', '=', 'sc.subjectid')
            ->whereIn('sc.schoolclassid', $classIds)
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

        // ── Student → class map ─────────────────────────────────────
        $studentClassRecords = Studentclass::whereIn('schoolclassid', $classIds)
            ->where('sessionid', $sessionid)
            ->get(['studentId', 'schoolclassid']);

        $studentClassMap = [];
        foreach ($studentClassRecords as $r) {
            $studentClassMap[(int) $r->studentId] = (int) $r->schoolclassid;
        }
        $allStudentIds = array_keys($studentClassMap);

        if (empty($allStudentIds)) {
            return $this->emptyBroadsheetResult(
                $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
                $subjectsMap, $selectedColumns,
                ['classgroup' => $classgroup,
                 'arm_labels' => $matchingClasses->pluck('arm_name', 'id')->toArray(),
                 'is_combined' => true, 'grade_basis' => $gradeBasis]
            );
        }

        $prevCumMap = $this->fetchPreviousTermCums($allStudentIds, $sessionid, $termid, $classIds);

        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $allStudentIds)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->whereIn('broadsheet_records.schoolclass_id', $classIds)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
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
            ])
            ->get();

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

            $ca1  = (float) ($row->ca1 ?? 0);
            $ca2  = (float) ($row->ca2 ?? 0);
            $ca3  = (float) ($row->ca3 ?? 0);
            $exam = (float) ($row->exam ?? 0);

            $caAvg = ($ca1 + $ca2 + $ca3) / 3;
            $total = round(($caAvg + $exam) / 2, 1);

            $prevCum = $prevCumMap[$sid][$sub] ?? null;
            if ($prevCum !== null && $prevCum > 0) {
                $bf = $prevCum;
            } elseif (!empty($row->bf) && (float) $row->bf > 0) {
                $bf = (float) $row->bf;
            } else {
                $bf = 0.0;
            }

            $cum = $termid == 1 ? $total : round(($bf + $total) / 2, 2);

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

        $armLabels = $matchingClasses->pluck('arm_name', 'id')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => $v])
            ->toArray();

        return $this->assembleStudentRows(
            $allStudentIds, $sessionid, null, $classIds,
            $studentSubjectMap, $subjectsMap,
            $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
            $selectedColumns, $armLabels, $studentClassMap, true, $gradeBasis
        );
    }

    // =========================================================================
    // AJAX: class groups
    // =========================================================================

    public function getClassGroups(): JsonResponse
    {
        try {
            $groups = Schoolclass::select('schoolclass')
                ->distinct()
                ->orderBy('schoolclass')
                ->pluck('schoolclass');

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
        $scoreCols = ['ca1','ca2','ca3','exam','total','bf','cum','grade',
                      'pos_class_cum','pos_class_total','pos_arm_total','pos_arm_cum',
                      'class_average','remark'];

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

    /**
     * Same query shape as PromotionController::getStudentScores().
     * Returns stored grade, total, cum from broadsheets — not pivot recalculation.
     */
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
            Log::error('getStudentScoresForPromotion failed', [
                'student_id' => $studentId,
                'error'      => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Same applicability check as PromotionController::classHasNoApplicableSetting().
     */
    private function classHasNoApplicableSetting(
        int $schoolclassId,
        int $sessionId,
        int $termId
    ): bool {
        $activeSettings = PromotionSetting::where('schoolclass_id', $schoolclassId)
            ->where('is_active', true)
            ->get(['id', 'session_id', 'term_id']);

        if ($activeSettings->isEmpty()) {
            return true;
        }

        foreach ($activeSettings as $setting) {
            $sid = $setting->session_id;
            $tid = $setting->term_id;

            if ($sid == $sessionId && $tid == $termId)               return false;
            if ($sid == $sessionId && $tid === null)                 return false;
            if ($sid === null      && $tid == $termId)               return false;
            if ($sid === null      && $tid === null)                 return false;
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