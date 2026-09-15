<?php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\SchoolInformation;
use App\Models\Studentclass;
use App\Models\PromotionStatus;
use App\Models\Assessment;
use App\Models\BroadsheetAssessmentScore;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class TranscriptController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View student-transcript');
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): View
    {
        $pagetitle     = 'Student Transcript Generator';
        $sessions      = Schoolsession::orderByDesc('id')->get();
        $terms         = Schoolterm::all();
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm'])
            ->orderBy('schoolclass.schoolclass')
            ->get();

        return view('transcript.index', compact('pagetitle', 'sessions', 'terms', 'schoolclasses'));
    }

    // =========================================================================
    // SEARCH STUDENTS (AJAX)
    // =========================================================================

    public function searchStudents(Request $request): JsonResponse
    {
        $q         = $request->input('q', '');
        $sessionId = $request->input('session_id');
        $classId   = $request->input('class_id');

        $query = DB::table('studentRegistration as sr')
            ->leftJoin('studentpicture as sp',   'sp.studentid',   '=', 'sr.id')
            ->leftJoin('studentclass as sc',      'sc.studentId',   '=', 'sr.id')
            ->leftJoin('schoolclass as cls',      'cls.id',         '=', 'sc.schoolclassid')
            ->leftJoin('schoolarm as arm',        'arm.id',         '=', 'cls.arm')
            ->leftJoin('schoolsession as sess',   'sess.id',        '=', 'sc.sessionid')
            ->select([
                'sr.id',
                'sr.admissionNo as admissionno',
                'sr.firstname',
                'sr.lastname',
                'sr.othername',
                'sr.gender',
                'sp.picture',
                'cls.schoolclass',
                'arm.arm',
                'sess.session',
            ])
            ->where(function ($inner) use ($q) {
                $inner->where('sr.lastname',     'like', "%{$q}%")
                      ->orWhere('sr.firstname',  'like', "%{$q}%")
                      ->orWhere('sr.admissionNo','like', "%{$q}%");
            });

        if ($sessionId) $query->where('sc.sessionid', $sessionId);
        if ($classId)   $query->where('sc.schoolclassid', $classId);

        $students = $query->distinct()->orderBy('sr.lastname')->limit(30)->get();

        return response()->json(['success' => true, 'students' => $students]);
    }

    // =========================================================================
    // PREVIEW
    // =========================================================================

    public function preview(Request $request): View|JsonResponse|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|exists:studentRegistration,id',
                'type'       => 'nullable|in:full,term,session',
                'session_id' => 'nullable|exists:schoolsession,id',
                'term_id'    => 'nullable|exists:schoolterm,id',
            ]);

            $data = $this->buildTranscriptData(
                (int) $validated['student_id'],
                $validated['type']       ?? 'full',
                $validated['session_id'] ?? null,
                $validated['term_id']    ?? null,
            );

            $data['pagetitle']          = 'Student Transcript – ' . $data['student']->lastname . ' ' . $data['student']->firstname;
            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);

            return view('transcript.preview', $data);

        } catch (\Exception $e) {
            Log::error('Transcript preview error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load transcript: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // EXPORT PDF
    // =========================================================================

    public function exportPdf(Request $request): mixed
    {
        try {
            ini_set('max_execution_time', 300);
            ini_set('memory_limit', '512M');

            $validated = $request->validate([
                'student_id' => 'required|exists:studentRegistration,id',
                'type'       => 'nullable|in:full,term,session',
                'session_id' => 'nullable|exists:schoolsession,id',
                'term_id'    => 'nullable|exists:schoolterm,id',
                'copy_type'  => 'nullable|in:original,duplicate',
            ]);

            $data = $this->buildTranscriptData(
                (int) $validated['student_id'],
                $validated['type']       ?? 'full',
                $validated['session_id'] ?? null,
                $validated['term_id']    ?? null,
            );

            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);
            $data['copy_type']          = $validated['copy_type'] ?? 'original';
            $data['generated_at']       = now()->format('d M Y, H:i');
            $data['generated_by']       = auth()->user()->name ?? 'System';

            $pdf = Pdf::loadView('transcript.pdf', $data)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled'    => true,
                    'isRemoteEnabled'         => true,
                    'isFontSubsettingEnabled' => true,
                    'defaultFont'             => 'DejaVu Sans',
                    'dpi'                     => 96,
                    'enable_css_float'        => false,
                    'enable_javascript'       => false,
                ]);

            $student  = $data['student'];
            $filename = 'Transcript_'
                . preg_replace('/[^A-Za-z0-9_\-]/', '_', trim($student->lastname . '_' . $student->firstname))
                . '_' . now()->format('Ymd') . '.pdf';

            return $pdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('Transcript PDF error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to generate transcript: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // BUILD TRANSCRIPT DATA
    // =========================================================================

    private function buildTranscriptData(
        int $studentId,
        string $type = 'full',
        ?int $sessionId = null,
        ?int $termId = null
    ): array {
        $schoolInfo = SchoolInformation::getActiveSchool() ?? new \stdClass();

        // ── Student info ──────────────────────────────────────────────────────
        $student = DB::table('studentRegistration as sr')
            ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 'sr.id')
            ->where('sr.id', $studentId)
            ->select([
                'sr.id',
                'sr.admissionNo as admissionno',
                'sr.firstname',
                'sr.lastname',
                'sr.othername',
                'sr.gender',
                'sr.dateofbirth',
                'sr.phone_number as phone',
                'sr.home_address2 as address',
                'sp.picture',
            ])
            ->first();

        if (!$student) {
            throw new \Exception('Student not found.');
        }

        // ── Enrolments ────────────────────────────────────────────────────────
        $enrolmentQuery = Studentclass::where('studentId', $studentId)
            ->join('schoolclass',   'schoolclass.id',   '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id',     '=', 'schoolclass.arm')
            ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->join('schoolterm',    'schoolterm.id',    '=', 'studentclass.termid')
            ->select([
                'studentclass.schoolclassid',
                'studentclass.termid',
                'studentclass.sessionid',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolsession.session',
                'schoolterm.term',
                'schoolsession.id as session_id_val',
                'schoolterm.id as term_id_val',
            ]);

        if ($type === 'session' && $sessionId) {
            $enrolmentQuery->where('studentclass.sessionid', $sessionId);
        } elseif ($type === 'term' && $sessionId && $termId) {
            $enrolmentQuery->where('studentclass.sessionid', $sessionId)
                           ->where('studentclass.termid', $termId);
        }

        $enrolments = $enrolmentQuery
            ->orderBy('schoolsession.id')
            ->orderBy('schoolterm.id')
            ->get();

        // ── Assessments – get from the first class the student was enrolled in ─
        $firstClassId = $enrolments->first()?->schoolclassid;
        $assessments  = collect();

        if ($firstClassId) {
            $schoolclass = Schoolclass::with('classcategories')->find($firstClassId);
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                    ->orderBy('id')
                    ->get();
            }
        }

        // ── Broadsheet records ────────────────────────────────────────────────
        $bsQuery = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id',       '=', 'broadsheets.broadsheet_record_id')
            ->join('subject',            'subject.id',                  '=', 'broadsheet_records.subject_id')
            ->join('schoolterm',         'schoolterm.id',               '=', 'broadsheets.term_id')
            ->join('schoolsession',      'schoolsession.id',            '=', 'broadsheet_records.session_id')
            ->join('schoolclass',        'schoolclass.id',              '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm',      'schoolarm.id',                '=', 'schoolclass.arm')
            ->where('broadsheet_records.student_id', $studentId)
            ->select([
                'broadsheets.id as broadsheet_id',
                'broadsheet_records.session_id',
                'broadsheets.term_id',
                'broadsheet_records.schoolclass_id',
                'subject.subject as subject_name',
                'subject.subject_code',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'broadsheets.remark',
                'broadsheets.subject_position_class as position',
                'broadsheets.avg as class_average',
                'broadsheets.vettedstatus',
                'schoolterm.term',
                'schoolsession.session',
                'schoolclass.schoolclass',
                'schoolarm.arm',
            ]);

        if ($type === 'session' && $sessionId) {
            $bsQuery->where('broadsheet_records.session_id', $sessionId);
        } elseif ($type === 'term' && $sessionId && $termId) {
            $bsQuery->where('broadsheet_records.session_id', $sessionId)
                    ->where('broadsheets.term_id', $termId);
        }

        $allRecords = $bsQuery
            ->orderBy('schoolsession.id')
            ->orderBy('broadsheets.term_id')
            ->orderBy('subject.subject')
            ->get();

        // ── Fetch assessment scores for all broadsheet records ────────────────
        $broadsheetIds       = $allRecords->pluck('broadsheet_id')->unique()->filter()->toArray();
        $assessmentScoresAll = collect();

        if (!empty($broadsheetIds)) {
            $assessmentScoresAll = BroadsheetAssessmentScore::whereIn('broadsheet_id', $broadsheetIds)
                ->get()
                ->groupBy('broadsheet_id');
        }

        // ── Promotion history ─────────────────────────────────────────────────
        $promotions = DB::table('promotionStatus')
            ->join('schoolterm',    'schoolterm.id',    '=', 'promotionStatus.termid')
            ->join('schoolsession', 'schoolsession.id', '=', 'promotionStatus.sessionid')
            ->join('schoolclass',   'schoolclass.id',   '=', 'promotionStatus.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id',     '=', 'schoolclass.arm')
            ->where('promotionStatus.studentId', $studentId)
            ->select([
                'promotionStatus.promotionStatus',
                'promotionStatus.classstatus',
                'promotionStatus.position',
                'schoolterm.term',
                'schoolsession.session',
            ])
            ->orderBy('schoolsession.id')
            ->orderBy('schoolterm.id')
            ->get()
            ->keyBy(fn ($r) => $r->session . '_' . $r->term);

        // ── Principal's comments ──────────────────────────────────────────────
        $comments = DB::table('studentpersonalityprofiles')
            ->join('schoolterm',    'schoolterm.id',    '=', 'studentpersonalityprofiles.termid')
            ->join('schoolsession', 'schoolsession.id', '=', 'studentpersonalityprofiles.sessionid')
            ->where('studentpersonalityprofiles.studentid', $studentId)
            ->select([
                'studentpersonalityprofiles.principalscomment',
                'schoolterm.term',
                'schoolsession.session',
            ])
            ->get()
            ->keyBy(fn ($r) => $r->session . '_' . $r->term);

        // ── Structure: session → term → subjects ──────────────────────────────
        $transcriptData = [];

        foreach ($enrolments as $enrol) {
            $sessionKey = $enrol->session;
            $termKey    = $enrol->term;
            $mapKey     = $sessionKey . '_' . $termKey;

            if (!isset($transcriptData[$sessionKey])) {
                $transcriptData[$sessionKey] = [
                    'session'    => $sessionKey,
                    'session_id' => $enrol->session_id_val,
                    'terms'      => [],
                ];
            }

            $termRecords = $allRecords->filter(
                fn ($r) => $r->session === $sessionKey && $r->term === $termKey
            );

            $subjects     = [];
            $totalScore   = 0;
            $totalCum     = 0;
            $subjectCount = 0;

            foreach ($termRecords as $rec) {
                // Build assessment scores for this subject row
                $assessmentScoreRow = $assessmentScoresAll->get($rec->broadsheet_id, collect());
                $assessmentData     = [];
                foreach ($assessments as $a) {
                    $score = $assessmentScoreRow->firstWhere('assessment_id', $a->id);
                    $assessmentData[$a->id] = $score ? (float) $score->score : null;
                }

                $subjects[] = [
                    'subject'       => $rec->subject_name,
                    'subject_code'  => $rec->subject_code ?? '',
                    'assessments'   => $assessmentData,
                    'total'         => (float) ($rec->total ?? 0),
                    'bf'            => (float) ($rec->bf ?? 0),
                    'cum'           => (float) ($rec->cum ?? 0),
                    'grade'         => $rec->grade ?? '-',
                    'remark'        => $rec->remark ?? '-',
                    'position'      => $rec->position ?? '-',
                    'class_average' => (float) ($rec->class_average ?? 0),
                ];

                $totalScore   += (float) ($rec->total ?? 0);
                $totalCum     += (float) ($rec->cum ?? 0);
                $subjectCount++;
            }

            $termAvg   = $subjectCount > 0 ? round($totalScore / $subjectCount, 1) : 0;
            $cumAvg    = $subjectCount > 0 ? round($totalCum   / $subjectCount, 1) : 0;
            $promotion = $promotions[$mapKey] ?? null;
            $comment   = $comments[$mapKey]   ?? null;

            $transcriptData[$sessionKey]['terms'][$termKey] = [
                'term'           => $termKey,
                'term_id'        => $enrol->term_id_val,
                'class'          => $enrol->schoolclass . ' ' . ($enrol->arm ?? ''),
                'subjects'       => $subjects,
                'subject_count'  => $subjectCount,
                'total_score'    => round($totalScore, 1),
                'average'        => $termAvg,
                'cum_average'    => $cumAvg,
                'promotion'      => $promotion?->promotionStatus ?? null,
                'class_position' => $promotion?->position        ?? null,
                'comment'        => $comment?->principalscomment ?? null,
            ];
        }

        // ── Overall GPA ───────────────────────────────────────────────────────
        $allTotals  = $allRecords->pluck('total')->filter(fn ($v) => $v > 0);
        $overallGpa = $allTotals->count() > 0
            ? round($allTotals->map(fn ($s) => $this->getGradePoint((float) $s))->avg(), 2)
            : 0.0;

        // ── Grade distribution ────────────────────────────────────────────────
        $gradeDistribution = $allRecords
            ->groupBy('grade')
            ->map(fn ($g) => $g->count())
            ->sortKeys();

        return [
            'schoolInfo'        => $schoolInfo,
            'student'           => $student,
            'transcriptData'    => $transcriptData,
            'allRecords'        => $allRecords,
            'assessments'       => $assessments,
            'overallGpa'        => $overallGpa,
            'overallGpaGrade'   => $this->getGpaGrade($overallGpa),
            'gradeDistribution' => $gradeDistribution,
            'type'              => $type,
            'sessionId'         => $sessionId,
            'termId'            => $termId,
            'totalSubjects'     => $allRecords->pluck('subject_name')->unique()->count(),
            'totalSessions'     => collect($transcriptData)->count(),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function getGradePoint(float $score): float
    {
        if ($score >= 75) return 5.0;
        if ($score >= 70) return 4.5;
        if ($score >= 65) return 4.0;
        if ($score >= 60) return 3.5;
        if ($score >= 55) return 3.0;
        if ($score >= 50) return 2.5;
        if ($score >= 45) return 2.0;
        if ($score >= 40) return 1.0;
        return 0.0;
    }

    private function getGpaGrade(float $gpa): string
    {
        if ($gpa >= 4.5) return 'A1';
        if ($gpa >= 4.0) return 'B2';
        if ($gpa >= 3.5) return 'B3';
        if ($gpa >= 3.0) return 'C4';
        if ($gpa >= 2.5) return 'C5';
        if ($gpa >= 2.0) return 'C6';
        if ($gpa >= 1.5) return 'D7';
        if ($gpa >= 1.0) return 'E8';
        return 'F9';
    }

    private function getLogoBase64($schoolInfo): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                <rect width="80" height="80" rx="40" fill="#1e3a5f"/>
                <text x="40" y="45" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">SCH</text>
            </svg>'
        );

        if (!$schoolInfo || empty($schoolInfo->school_logo)) return $placeholder;

        $paths = [
            storage_path('app/public/' . $schoolInfo->school_logo),
            public_path('storage/' . $schoolInfo->school_logo),
            public_path($schoolInfo->school_logo),
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && filesize($path) > 100) {
                $mime = mime_content_type($path) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        return $placeholder;
    }
}
