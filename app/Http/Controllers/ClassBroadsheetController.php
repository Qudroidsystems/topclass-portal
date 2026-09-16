<?php
// app/Http/Controllers/ClassBroadsheetController.php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Schoolsessession;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\Studentclass;
use App\Models\Studentpersonalityprofile;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClassBroadsheetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View student-report');
    }

    // =========================================================================
    // GRADE HELPERS
    // =========================================================================

    /**
     * Calculate grade from a score, using the class category scale.
     */
    public function gradeFromScorePublic(float $score, bool $isSenior): array
    {
        return $this->gradeFromScore($score, $isSenior);
    }

    private function gradeFromScore(float $score, bool $isSenior): array
    {
        if ($score <= 0) return ['-', '-'];

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

    // =========================================================================
    // BF SOURCE — previous term's raw cum
    // =========================================================================

    /**
     * Fetch previous term's cumulative (raw sum = BF for the next term) for
     * every student × subject in the given class.
     *
     * Project-1 rule: BF for term N = raw "cum" from term N-1.
     */
    private function fetchPreviousTermCums(
        array $studentIds,
        int   $sessionid,
        int   $currentTermId,
        array $classIds
    ): array {
        if (empty($studentIds)) return [];

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
    }

    // =========================================================================
    // MAIN VIEW — class broadsheet with comments
    // =========================================================================

    public function classBroadsheet($schoolclassid, $sessionid, $termid, Request $request)
    {
        $pagetitle = "Class Broadsheet";

        // Grade basis: 'cum' (default) or 'total'
        $gradeBasis = $request->get('grade_basis', 'cum');
        if (!in_array($gradeBasis, ['cum', 'total'])) {
            $gradeBasis = 'cum';
        }

        // ── Student roster ──────────────────────────────────────────────
        $students = Studentclass::where('studentclass.schoolclassid', $schoolclassid)
            ->where('studentclass.sessionid', $sessionid)
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'studentRegistration.id          as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname   as fname',
                'studentRegistration.lastname    as lastname',
                'studentRegistration.othername   as othername',
                'studentRegistration.gender      as gender',
                'studentpicture.picture          as picture',
            ])
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get();

        // ── Subjects for this class ─────────────────────────────────────
        $subjects = DB::table('subjectclass as sc')
            ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
            ->join('subject', 'subject.id', '=', 'sc.subjectid')
            ->where('sc.schoolclassid', $schoolclassid)
            ->select(['subject.id', 'subject.subject', 'subject.subject_code'])
            ->distinct()
            ->orderBy('subject.subject')
            ->get();

        // ── Class + category info ───────────────────────────────────────
        $schoolclassModel = Schoolclass::with('classcategory')->find($schoolclassid);
        $isSenior = $schoolclassModel && $schoolclassModel->classcategory
            ? (bool) $schoolclassModel->classcategory->is_senior
            : false;

        $studentIds = $students->pluck('id')->map(fn ($v) => (int) $v)->toArray();

        // ── Previous term raw cum → BF map ──────────────────────────────
        $prevCumMap = $this->fetchPreviousTermCums(
            $studentIds, $sessionid, $termid, [$schoolclassid]
        );

        // ── Fetch this term's broadsheets ───────────────────────────────
        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.schoolclass_id', $schoolclassid)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->select([
                'broadsheets.id as broadsheet_id',
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
                'broadsheets.subject_position_class as pos_class_cum',
                'broadsheets.subject_position_class_total as pos_class_total',
                'broadsheets.arm_position as pos_arm_total',
                'broadsheets.arm_position_cum as pos_arm_cum',
                'broadsheets.avg as class_average',
            ])
            ->get();

        // ── Build per-student, per-subject score maps ───────────────────
        $termScoreMap        = [];
        $cumScoreMap         = [];
        $bfMap               = [];
        $studentSubjectData  = [];

        foreach ($broadsheets as $row) {
            $sid  = (int) $row->student_id;
            $sub  = (int) $row->subject_id;
            $name = $row->subject_name;

            $ca1  = (float) ($row->ca1  ?? 0);
            $ca2  = (float) ($row->ca2  ?? 0);
            $ca3  = (float) ($row->ca3  ?? 0);
            $exam = (float) ($row->exam ?? 0);

            // Recompute total per project-1 formula (never trust stale DB)
            $caAvg = ($ca1 + $ca2 + $ca3) / 3;
            $total = round(($caAvg + $exam) / 2, 1);

            // BF — prefer previous term's raw cum, fall back to persisted BF
            $prevCum = $prevCumMap[$sid][$sub] ?? null;
            if ($prevCum !== null && $prevCum > 0) {
                $bf = $prevCum;
            } elseif (!empty($row->bf) && (float) $row->bf > 0) {
                $bf = (float) $row->bf;
            } else {
                $bf = 0.0;
            }

            // Project-1 cum rule
            $cum = $termid == 1 ? $total : round(($bf + $total) / 2, 2);

            $termScoreMap[$sid][$name] = $total;
            $cumScoreMap[$sid][$name]  = $cum;
            $bfMap[$sid][$name]        = $bf;

            // Display grade depends on grade basis
            $displayScore = $gradeBasis === 'total' ? $total : $cum;
            [$displayGrade, $displayGradeLetter] = $this->gradeFromScore((float) $displayScore, $isSenior);

            $studentSubjectData[$sid][$name] = [
                'ca1'             => $ca1,
                'ca2'             => $ca2,
                'ca3'             => $ca3,
                'exam'            => $exam,
                'total'           => $total,
                'bf'              => $bf,
                'cum'             => $cum,
                'grade'           => $displayGrade,
                'grade_letter'    => $displayGradeLetter,
                'pos_class_cum'   => $row->pos_class_cum   ?? null,
                'pos_class_total' => $row->pos_class_total ?? null,
                'pos_arm_total'   => $row->pos_arm_total   ?? null,
                'pos_arm_cum'     => $row->pos_arm_cum     ?? null,
                'class_average'   => (float) ($row->class_average ?? 0),
            ];
        }

        // ── Auto-create personality profiles ────────────────────────────
        foreach ($students as $student) {
            $profile = Studentpersonalityprofile::firstOrNew([
                'studentid'     => $student->id,
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
            ]);
            if (!$profile->exists) {
                $profile->staffid = Auth::id();
                $profile->save();
            }
        }

        // ── Per-student analytics ───────────────────────────────────────
        $studentAnalytics    = [];
        $topPerformerByCum   = null;
        $topPerformerByTerm  = null;
        $topPerformerPicture = null;
        $topCumPercentage    = -1;
        $topTermPercentage   = -1;

        foreach ($students as $student) {
            $sid = (int) $student->id;

            $termTotal    = 0;
            $cumTotal     = 0;
            $subjectCount = 0;
            $grades       = [];

            foreach ($subjects as $subject) {
                $subjName = $subject->subject;
                $data     = $studentSubjectData[$sid][$subjName] ?? null;

                if ($data) {
                    $termScore = (float) ($data['total'] ?? 0);
                    $cumScore  = (float) ($data['cum']   ?? 0);

                    if ($termScore > 0 || $cumScore > 0) $subjectCount++;
                    $termTotal += $termScore;
                    $cumTotal  += $cumScore;

                    [$termGrade] = $this->gradeFromScore($termScore, $isSenior);
                    [$cumGrade]  = $this->gradeFromScore($cumScore,  $isSenior);

                    $grades[] = [
                        'subject'         => $subjName,
                        'term_score'      => $termScore,
                        'cum_score'       => $cumScore,
                        'bf_score'        => $data['bf'] ?? 0,
                        'term_grade'      => $termGrade,
                        'cum_grade'       => $cumGrade,
                        'display_grade'   => $gradeBasis === 'total' ? $termGrade : $cumGrade,
                        'pos_class_cum'   => $data['pos_class_cum']   ?? null,
                        'pos_class_total' => $data['pos_class_total'] ?? null,
                        'pos_arm_total'   => $data['pos_arm_total']   ?? null,
                        'pos_arm_cum'     => $data['pos_arm_cum']     ?? null,
                    ];
                } else {
                    $grades[] = [
                        'subject'         => $subjName,
                        'term_score'      => 0,
                        'cum_score'       => 0,
                        'bf_score'        => 0,
                        'term_grade'      => '-',
                        'cum_grade'       => '-',
                        'display_grade'   => '-',
                        'pos_class_cum'   => null,
                        'pos_class_total' => null,
                        'pos_arm_total'   => null,
                        'pos_arm_cum'     => null,
                    ];
                }
            }

            $totalObtainable = $subjectCount * 100;
            $termPercentage  = $totalObtainable > 0 ? round(($termTotal / $totalObtainable) * 100, 1) : 0;
            $cumPercentage   = $totalObtainable > 0 ? round(($cumTotal  / $totalObtainable) * 100, 1) : 0;

            $studentAnalytics[$sid] = [
                'term_total'       => round($termTotal, 1),
                'cum_total'        => round($cumTotal, 1),
                'term_average'     => $subjectCount > 0 ? round($termTotal / $subjectCount, 1) : 0,
                'cum_average'      => $subjectCount > 0 ? round($cumTotal / $subjectCount, 1) : 0,
                'subject_count'    => $subjectCount,
                'total_obtainable' => $totalObtainable,
                'term_percentage'  => $termPercentage,
                'cum_percentage'   => $cumPercentage,
                'grades'           => $grades,
                'grade_basis'      => $gradeBasis,
            ];
        }

        // ── Positions ranked by the chosen grade basis ──────────────────
        $positionMap = [];
        $rankKey     = $gradeBasis === 'total' ? 'term_percentage' : 'cum_percentage';

        $ranked = collect($studentAnalytics)->sortByDesc($rankKey)->values();
        $prevPct = null;
        $prevPos = 0;
        $counter = 0;

        foreach ($ranked as $an) {
            $counter++;
            $sid = null;
            foreach ($studentAnalytics as $s => $a) {
                if ($a === $an) { $sid = $s; break; }
            }
            if ($sid === null) continue;

            $pct = (float) ($an[$rankKey] ?? 0);
            if ($prevPct !== null && $pct == $prevPct) {
                $positionMap[$sid] = $prevPos;
            } else {
                $positionMap[$sid] = $counter;
                $prevPos = $counter;
            }
            $prevPct = $pct;
        }

        // ── Top performers ──────────────────────────────────────────────
        foreach ($studentAnalytics as $sid => &$an) {
            $an['position'] = $positionMap[$sid] ?? 0;

            if ($an['cum_percentage'] > $topCumPercentage) {
                $topCumPercentage = $an['cum_percentage'];
                $student = $students->firstWhere('id', $sid);
                if ($student) {
                    $topPerformerByCum = trim(($student->lastname ?? '') . ' ' . ($student->fname ?? ''));
                    $topPerformerPicture = $student->picture
                        ? asset('storage/student_avatars/' . basename($student->picture))
                        : null;
                }
            }
            if ($an['term_percentage'] > $topTermPercentage) {
                $topTermPercentage = $an['term_percentage'];
                $student = $students->firstWhere('id', $sid);
                if ($student) {
                    $topPerformerByTerm = trim(($student->lastname ?? '') . ' ' . ($student->fname ?? ''));
                }
            }
        }
        unset($an);

        // ── Personality profiles for the view ───────────────────────────
        $personalityProfiles = Studentpersonalityprofile::where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->where('termid', $termid)
            ->get();

        // ── Class / term / session labels ───────────────────────────────
        $schoolclass = Schoolclass::where('schoolclass.id', $schoolclassid)
            ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
            ->first(['schoolclass.schoolclass', 'schoolclass.arm', 'schoolarm.arm']);

        $schoolterm    = Schoolterm::where('id', $termid)->value('term')       ?? 'N/A';
        $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';

        // ── Class averages ──────────────────────────────────────────────
        $avgTermPercentage = 0;
        $avgCumPercentage  = 0;

        if (count($studentAnalytics) > 0) {
            $avgTermPercentage = round(array_sum(array_column($studentAnalytics, 'term_percentage')) / count($studentAnalytics), 1);
            $avgCumPercentage  = round(array_sum(array_column($studentAnalytics, 'cum_percentage'))  / count($studentAnalytics), 1);
        }

        return view('classbroadsheet.classbroadsheet', compact(
            'students', 'subjects',
            'termScoreMap', 'cumScoreMap', 'bfMap',
            'personalityProfiles',
            'schoolclass', 'schoolterm', 'schoolsession',
            'schoolclassid', 'sessionid', 'termid',
            'isSenior', 'studentAnalytics', 'pagetitle', 'positionMap',
            'topPerformerByCum', 'topPerformerByTerm', 'topPerformerPicture',
            'avgTermPercentage', 'avgCumPercentage',
            'gradeBasis'
        ));
    }

    // =========================================================================
    // PAST COMMENTS — history feed for one student
    // =========================================================================

    public function getPastComments($studentId)
    {
        try {
            $student = Student::find($studentId);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found'], 404);
            }

            $profiles = Studentpersonalityprofile::where('studentid', $studentId)
                ->where(function ($q) {
                    $q->whereNotNull('classteachercomment')
                      ->orWhereNotNull('guidancescomment')
                      ->orWhereNotNull('remark_on_other_activities')
                      ->orWhereNotNull('principalscomment');
                })
                ->orderByDesc('sessionid')
                ->orderByDesc('termid')
                ->get();

            $staffUserIds = $profiles->pluck('staffid')->filter()->unique()->values()->toArray();

            $staffMembers = Staff::with(['user.staffPicture'])
                ->whereIn('userid', $staffUserIds)
                ->get()
                ->keyBy('userid');

            $commentCounts = [
                'classteacher' => 0,
                'guidance'     => 0,
                'activities'   => 0,
                'principal'    => 0,
                'total'        => 0,
            ];

            $result = collect();

            foreach ($profiles as $profile) {
                $hasComment  = false;
                $commentText = '';
                $commentType = '';
                $staffUserId = $profile->staffid;

                if ($profile->classteachercomment && trim($profile->classteachercomment) !== '') {
                    $commentText = $profile->classteachercomment;
                    $commentType = 'Teacher';
                    $commentCounts['classteacher']++;
                    $hasComment = true;
                } elseif ($profile->guidancescomment && trim($profile->guidancescomment) !== '') {
                    $commentText = $profile->guidancescomment;
                    $commentType = 'Guidance';
                    $commentCounts['guidance']++;
                    $hasComment = true;
                } elseif ($profile->remark_on_other_activities && trim($profile->remark_on_other_activities) !== '') {
                    $commentText = $profile->remark_on_other_activities;
                    $commentType = 'Activities';
                    $commentCounts['activities']++;
                    $hasComment = true;
                } elseif ($profile->principalscomment && trim($profile->principalscomment) !== '') {
                    $commentText = $profile->principalscomment;
                    $commentType = 'Principal';
                    $commentCounts['principal']++;
                    $hasComment = true;
                }

                if (!$hasComment) continue;

                $staffName    = null;
                $staffPicture = null;

                if ($staffUserId) {
                    $staff = $staffMembers->get($staffUserId);

                    if (!$staff) {
                        $staff = Staff::with(['user.staffPicture'])->where('userid', $staffUserId)->first();
                        if ($staff) $staffMembers->put($staff->userid, $staff);
                    }

                    if ($staff && $staff->user) {
                        $staffName = $staff->user->name;
                        if ($staff->user->staffPicture && $staff->user->staffPicture->picture) {
                            $staffPicture = asset('storage/staff_avatars/' . $staff->user->staffPicture->picture);
                        }
                    } elseif ($staff && $staff->employmentid) {
                        $staffName = $staff->employmentid;
                    } else {
                        $user = User::with('staffPicture')->find($staffUserId);
                        if ($user) {
                            $staffName = $user->name;
                            if ($user->staffPicture && $user->staffPicture->picture) {
                                $staffPicture = asset('storage/staff_avatars/' . $user->staffPicture->picture);
                            }
                        } else {
                            $staffName = 'Unknown Staff';
                        }
                    }
                } else {
                    $staffName = 'System';
                }

                if (!$staffName || $staffName === 'Staff Member') {
                    $staffName = 'System User';
                }

                $term    = Schoolterm::find($profile->termid);
                $session = Schoolsession::find($profile->sessionid);
                $class   = Schoolclass::where('schoolclass.id', $profile->schoolclassid)
                    ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
                    ->first(['schoolclass.schoolclass', 'schoolarm.arm']);

                $result->push([
                    'id'            => $profile->id,
                    'term'          => $term    ? $term->term       : 'Unknown Term',
                    'session'       => $session ? $session->session : 'Unknown Session',
                    'class'         => $class   ? trim($class->schoolclass . ' ' . $class->arm) : 'Unknown Class',
                    'comment_text'  => $commentText,
                    'comment_type'  => $commentType,
                    'date'          => optional($profile->updated_at ?? $profile->created_at)->format('d M Y') ?? '',
                    'staff_name'    => $staffName,
                    'staff_picture' => $staffPicture,
                    'staff_id'      => $staffUserId,
                ]);
            }

            $commentCounts['total'] = $result->count();
            $studentName = trim($student->lastname . ' ' . $student->firstname . ' ' . ($student->othername ?? ''));

            return response()->json([
                'success'  => true,
                'data'     => $result->values(),
                'student'  => [
                    'id'           => $student->id,
                    'name'         => $studentName,
                    'admission_no' => $student->admissionNo,
                ],
                'counts'   => $commentCounts,
            ]);

        } catch (\Exception $e) {
            Log::error('getPastComments error', [
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'studentId' => $studentId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE COMMENTS — bulk save
    // =========================================================================

    public function updateComments(Request $request, $schoolclassid, $sessionid, $termid)
    {
        $request->validate([
            'teacher_comments.*'            => 'nullable|string|max:5000',
            'guidance_comments.*'           => 'nullable|string|max:5000',
            'remarks_on_other_activities.*' => 'nullable|string|max:5000',
            'principals_comments.*'         => 'nullable|string|max:5000',
            'no_of_times_school_absent.*'   => 'nullable|integer|min:0',
            'signature'                     => 'nullable|mimes:jpg,jpeg,png,pdf|max:5048',
        ]);

        $signaturePath = null;
        if ($request->hasFile('signature') && $request->file('signature')->isValid()) {
            $file      = $request->file('signature');
            $filename  = 'signature_' . Auth::id() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $stored    = $file->storeAs('public/signatures', $filename);
            $signaturePath = str_replace('public/', '', $stored);
        }

        $teacherComments   = $request->input('teacher_comments', []);
        $guidanceComments  = $request->input('guidance_comments', []);
        $remarks           = $request->input('remarks_on_other_activities', []);
        $principalComments = $request->input('principals_comments', []);
        $absences          = $request->input('no_of_times_school_absent', []);

        $allStudentIds = array_unique(array_merge(
            array_keys($teacherComments),
            array_keys($guidanceComments),
            array_keys($remarks),
            array_keys($principalComments),
            array_keys($absences)
        ));

        if (empty($allStudentIds) && !$signaturePath) {
            return response()->json(['success' => false, 'message' => 'No data provided.'], 400);
        }

        DB::beginTransaction();
        try {
            $updatedCount = 0;
            $createdCount = 0;
            $skippedCount = 0;

            foreach ($allStudentIds as $studentId) {
                $teacherComment   = trim($teacherComments[$studentId]   ?? '');
                $guidanceComment  = trim($guidanceComments[$studentId]  ?? '');
                $remark           = trim($remarks[$studentId]           ?? '');
                $principalComment = trim($principalComments[$studentId] ?? '');
                $absence = (isset($absences[$studentId]) && $absences[$studentId] !== '')
                    ? (int) $absences[$studentId]
                    : null;

                if ($teacherComment === '' && $guidanceComment === '' && $remark === '' && $principalComment === ''
                    && $absence === null && !$signaturePath) {
                    $skippedCount++;
                    continue;
                }

                $existing = Studentpersonalityprofile::where('studentid',     $studentId)
                    ->where('schoolclassid', $schoolclassid)
                    ->where('sessionid',     $sessionid)
                    ->where('termid',        $termid)
                    ->first();

                $payload = [
                    'staffid'                     => Auth::id(),
                    'classteachercomment'         => $teacherComment   ?: null,
                    'guidancescomment'            => $guidanceComment  ?: null,
                    'remark_on_other_activities'  => $remark           ?: null,
                    'principalscomment'           => $principalComment ?: null,
                    'no_of_times_school_absent'   => $absence,
                ];
                if ($signaturePath) $payload['signature'] = $signaturePath;

                if ($existing) {
                    $existing->update($payload);
                    $updatedCount++;
                } else {
                    Studentpersonalityprofile::create(array_merge($payload, [
                        'studentid'     => $studentId,
                        'schoolclassid' => $schoolclassid,
                        'sessionid'     => $sessionid,
                        'termid'        => $termid,
                    ]));
                    $createdCount++;
                }
            }

            DB::commit();

            $total = $updatedCount + $createdCount;
            if ($total === 0) {
                return response()->json(['success' => false, 'message' => 'No comments entered.'], 400);
            }

            $parts = [];
            if ($updatedCount) $parts[] = "{$updatedCount} updated";
            if ($createdCount) $parts[] = "{$createdCount} new";
            if ($skippedCount) $parts[] = "{$skippedCount} skipped";

            return response()->json([
                'success' => true,
                'message' => 'Saved successfully: ' . implode(', ', $parts) . '.',
                'updated' => $updatedCount,
                'created' => $createdCount,
                'skipped' => $skippedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ClassBroadsheet updateComments error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}