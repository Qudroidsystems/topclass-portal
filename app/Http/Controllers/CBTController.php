<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use App\Models\Exam;
use App\Models\Answer;
use App\Models\Result;
use App\Models\ExamAttempt;
use App\Models\Schoolterm;
use App\Models\Schoolsession;

class CBTController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View cbt-exam', ['only' => ['index']]);
        $this->middleware('permission:Take cbt-exam', ['only' => ['takeCBT']]);
        $this->middleware('permission:Submit cbt-exam', ['only' => ['submit']]);
    }

    public function index(Request $request)
    {
        $pagetitle = 'CBT Management';

        // $authUserId  = users.id  (FK used in exam_attempts, answers, results)
        // $studentRegId = studentRegistration.id (used for class/subject lookups)
        $authUserId   = auth()->user()->id;
        $studentRegId = auth()->user()->student_id;

        $student = DB::table('studentRegistration')
            ->where('id', $studentRegId)
            ->select('id', 'firstname', 'lastname', 'admissionNo')
            ->first();

        $studentClassData = DB::table('studentclass')
            ->where('studentId', $studentRegId)
            ->join('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->join('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
            ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->select(
                'schoolclass.id as class_id',
                'schoolclass.schoolclass as class_name',
                'schoolterm.id as term_id',
                'schoolterm.term as term_name',
                'schoolsession.id as session_id',
                'schoolsession.session as session_name'
            )
            ->first();

        $class      = $studentClassData ? (object) ['id' => $studentClassData->class_id,   'schoolclass' => $studentClassData->class_name]   : null;
        $termObj    = $studentClassData ? (object) ['id' => $studentClassData->term_id,    'term'       => $studentClassData->term_name]     : null;
        $sessionObj = $studentClassData ? (object) ['id' => $studentClassData->session_id, 'session'   => $studentClassData->session_name] : null;

        $terms    = Schoolterm::orderBy('id', 'desc')->get(['id', 'term']);
        $sessions = Schoolsession::orderBy('id', 'desc')->get(['id', 'session', 'status']);

        $selectedTermId    = $request->query('term');
        $selectedSessionId = $request->query('session');
        $search            = trim($request->query('search', ''));

        // Selected names for display
        $selectedTermName    = $selectedTermId ? Schoolterm::find($selectedTermId)?->term ?? 'Unknown Term' : null;
        $selectedSessionName = null;
        if ($selectedSessionId) {
            $sessionRecord = Schoolsession::select('session', 'status')->find($selectedSessionId);
            if ($sessionRecord) {
                $selectedSessionName = $sessionRecord->session;
                if ($sessionRecord->status) {
                    $selectedSessionName .= " ({$sessionRecord->status})";
                }
            }
        }

        $exams    = new LengthAwarePaginator(collect([]), 0, 15, 1, [
            'path'  => Paginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);
        $attempts = [];
        $totalreg = 0;
        $reg      = 0;

        if ($selectedTermId && $selectedSessionId && $studentClassData) {

            $totalreg = DB::table('subjectclass')
                ->where('schoolclassid', $studentClassData->class_id)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->where('subjectteacher.sessionid', $selectedSessionId)
                ->where('subjectteacher.termid', $selectedTermId)
                ->distinct('subjectteacher.subjectid')
                ->count('subjectteacher.subjectid');

            $reg = DB::table('student_subject_register_record')
                ->where('student_subject_register_record.studentId', $studentRegId)
                ->leftJoin('subjectclass', 'subjectclass.id', '=', 'student_subject_register_record.subjectclassid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'student_subject_register_record.session')
                ->count();

            // Get the student's registered subject IDs
            $registeredSubjectIds = DB::table('student_subject_register_record')
                ->where('student_subject_register_record.studentId', $studentRegId)
                ->leftJoin('subjectclass', 'subjectclass.id', '=', 'student_subject_register_record.subjectclassid')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'student_subject_register_record.session')
                ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->pluck('subject.id')
                ->unique()
                ->toArray();

            Log::info('Registered Subject IDs for student', [
                'auth_user_id'       => $authUserId,
                'student_reg_id'     => $studentRegId,
                'registered_subjects' => $registeredSubjectIds,
                'class_id'           => $studentClassData->class_id,
                'term_id'            => $selectedTermId,
                'session_id'         => $selectedSessionId
            ]);

            $examsQuery = Exam::where('schoolclass_id', $studentClassData->class_id)
                ->where('termid', $selectedTermId)
                ->where('session', $selectedSessionId);

            if (!empty($registeredSubjectIds)) {
                $examsQuery->whereIn('subject_id', $registeredSubjectIds);
            } else {
                $examsQuery->whereRaw('1=0');
            }

            if ($search !== '') {
                $examsQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $exams = $examsQuery
                ->select('id', 'title', 'subject_id', 'description', 'duration', 'start_time', 'end_time')
                ->with(['subject:id,subject'])
                ->paginate(15)
                ->appends($request->query());

            Log::info('Exams found for student', [
                'auth_user_id' => $authUserId,
                'exams_count'  => $exams->count(),
                'exams'        => $exams->pluck('id')->toArray()
            ]);

            $examIds = $exams->pluck('id')->toArray();

            // Use $authUserId (users.id) for exam_attempts lookup
            $attempts = ExamAttempt::where('student_id', $authUserId)
                ->whereIn('exam_id', $examIds)
                ->where('status', 'completed')
                ->pluck('exam_id')
                ->toArray();
        }

        if ($request->ajax()) {
            return response()->view('cbt.partials.exams-table', compact(
                'exams', 'attempts', 'student', 'class', 'termObj', 'sessionObj', 'totalreg', 'reg'
            ));
        }

        return view('cbt.index', compact(
            'pagetitle',
            'student',
            'class',
            'termObj',
            'sessionObj',
            'terms',
            'sessions',
            'selectedTermId',
            'selectedSessionId',
            'selectedTermName',
            'selectedSessionName',
            'exams',
            'attempts',
            'totalreg',
            'reg'
        ));
    }

    public function takeCBT($examid)
    {
        $pagetitle = 'CBT Exams';

        try {
            // $authUserId  = users.id  → used as FK in exam_attempts.student_id
            // $studentRegId = studentRegistration.id → used for class/subject data
            $authUserId   = auth()->user()->id;
            $studentRegId = auth()->user()->student_id;

            $exam = Exam::where('id', $examid)
                ->with(['questions.options' => function ($query) {
                    $query->select('id', 'question_id', 'option_text', 'is_correct');
                }])
                ->firstOrFail();

            $now       = Carbon::now();
            $startTime = Carbon::parse($exam->start_time);
            $endTime   = Carbon::parse($exam->end_time);

            if (!$now->between($startTime, $endTime)) {
                return redirect()->route('cbt.index')->with('error', 'This exam is not currently available.');
            }

            // Check for existing attempt using users.id
            $existingAttempt = ExamAttempt::where('student_id', $authUserId)
                ->where('exam_id', $exam->id)
                ->first();

            if ($existingAttempt) {
                return redirect()->route('cbt.index')->with('error', 'You have already taken this exam.');
            }

            // Create attempt using users.id as student_id (FK → users.id)
            $attempt = ExamAttempt::create([
                'student_id' => $authUserId,
                'exam_id'    => $exam->id,
                'start_time' => $now,
                'status'     => 'in_progress'
            ]);

            // Build questions array
            $questions = $exam->questions->map(function ($question) {
                $correctOption = $question->options->where('is_correct', true)->first();

                return [
                    'id'             => $question->id,
                    'text'           => $question->question_text,
                    'type'           => $question->type,
                    'options'        => $question->options->map(function ($option) {
                        return [
                            'id'         => $option->id,
                            'text'       => $option->option_text,
                            'is_correct' => $option->is_correct
                        ];
                    })->toArray(),
                    'correct_answer' => $correctOption ? $correctOption->option_text : '',
                    'image_url'      => $question->image ? asset('storage/' . $question->image) : null,
                    'marks'          => (float) ($question->marks ?? 1.0),
                ];
            })->toArray();

            $totalExamMarks = $exam->questions->sum('marks');

            // Student display info still comes from studentRegistration
            $studentReg = DB::table('studentRegistration')
                ->where('id', $studentRegId)
                ->select('id', 'firstname', 'lastname', 'admissionNo')
                ->first();

            $studentClassData = DB::table('studentclass')
                ->where('studentId', $studentRegId)
                ->join('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->join('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
                ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->select(
                    'schoolclass.id as class_id',
                    'schoolclass.schoolclass as class_name',
                    'schoolterm.id as term_id',
                    'schoolterm.term as term_name',
                    'schoolsession.id as session_id',
                    'schoolsession.session as session_name'
                )
                ->first();

            if (!$studentClassData) {
                return redirect()->route('cbt.index')->with('error', 'No registration found for this student.');
            }

            $class   = (object) ['id' => $studentClassData->class_id,   'schoolclass' => $studentClassData->class_name];
            $term    = (object) ['id' => $studentClassData->term_id,    'term'       => $studentClassData->term_name];
            $session = (object) ['id' => $studentClassData->session_id, 'session'   => $studentClassData->session_name];

            return view('cbt.take', compact(
                'pagetitle',
                'exam',
                'questions',
                'studentReg',
                'class',
                'term',
                'session',
                'attempt',
                'totalExamMarks'
            ));

        } catch (\Exception $e) {
            return redirect()->route('cbt.index')
                ->with('error', 'An error occurred while loading the exam: ' . $e->getMessage());
        }
    }

    public function submit(Request $request)
    {
        try {
            Log::info('Submit request received', $request->all());

            $data = $request->validate([
                'attempt_id'            => 'required|exists:exam_attempts,id',
                'exam_id'               => 'required|exists:exams,id',
                'answers'               => 'required|array|min:1',
                'answers.*.question_id' => 'required|integer|exists:questions,id',
                'answers.*.answer'      => 'nullable|string|max:1000',
                'answers.*.notes'       => 'nullable|string|max:1000',
            ]);

            // Use users.id for all FK references in exam_attempts / answers / results
            $authUserId = auth()->user()->id;
            if (!$authUserId) {
                throw new \Exception('No authenticated user found');
            }

            $attempt = ExamAttempt::findOrFail($data['attempt_id']);

            if ($attempt->student_id != $authUserId || $attempt->exam_id != $data['exam_id']) {
                return response()->json(['success' => false, 'message' => 'Invalid attempt or exam'], 403);
            }

            if ($attempt->status === 'completed') {
                return response()->json(['success' => true, 'message' => 'Exam already submitted']);
            }

            $exam = Exam::with(['questions.options' => function ($query) {
                $query->select('id', 'question_id', 'option_text', 'is_correct');
            }])->findOrFail($data['exam_id']);

            $now       = Carbon::now();
            $startTime = Carbon::parse($exam->start_time);
            $endTime   = Carbon::parse($exam->end_time);

            if (!$now->between($startTime, $endTime)) {
                return response()->json(['success' => false, 'message' => 'Exam submission time has expired.'], 403);
            }

            $attempt->update([
                'end_time' => $now,
                'status'   => 'completed'
            ]);

            // Marking is shared with offline-CBT sync (App\Services\OfflineCbt\CbtMarker)
            $marked     = \App\Services\OfflineCbt\CbtMarker::mark($exam, $data['answers']);
            $totalMarks = $marked['total_marks'];
            $score      = $marked['score'];
            $attempted  = $marked['attempted'];

            foreach ($marked['rows'] as $row) {
                // Store answer — user_id = users.id
                Answer::create([
                    'user_id'      => $authUserId,
                    'exam_id'      => $data['exam_id'],
                    'question_id'  => $row['question_id'],
                    'option_id'    => $row['option_id'],
                    'short_answer' => $row['short_answer'],
                ]);
            }

            // Store result — user_id = users.id
            Result::updateOrCreate(
                [
                    'user_id' => $authUserId,
                    'exam_id' => $data['exam_id'],
                ],
                [
                    'score'           => $score,
                    'total_marks'     => $totalMarks,
                    'percentage'      => $totalMarks > 0 ? ($score / $totalMarks) * 100 : 0,
                    'attempted'       => $attempted,
                    'total_questions' => $exam->questions->count(),
                ]
            );

            Log::info('Result saved', [
                'auth_user_id' => $authUserId,
                'score'        => $score,
                'total_marks'  => $totalMarks,
                'percentage'   => $totalMarks > 0 ? ($score / $totalMarks) * 100 : 0,
                'attempted'    => $attempted
            ]);

            return response()->json([
                'success'         => true,
                'message'         => 'Exam submitted successfully',
                'score'           => $score,
                'total_marks'     => $totalMarks,
                'percentage'      => $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0,
                'attempted'       => $attempted,
                'total_questions' => $exam->questions->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // CRUD stubs
    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}
