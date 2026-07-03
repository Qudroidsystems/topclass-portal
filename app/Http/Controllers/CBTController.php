<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\StudentSubjectRecord;
use App\Models\Subjectclass;
use App\Models\Exam; 
use App\Models\ExamAttempt;      // Add this import
use App\Models\Result; // Explicitly import the Result model
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // Add this import

class CBTController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $studentId = 1; // Replace with auth()->user()->student_id ?? $request->user()->id;

        $studentClassData = DB::table('studentclass')
            ->where('studentId', $studentId)
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
           // dd($studentClassData);

            // if (!$studentClassData) {
            //     return view('cbt.index', ['error' => 'No active class found for this student.']);
            // }

        $student = DB::table('studentRegistration')
            ->where('id', $studentId)
            ->select('id', 'firstname', 'lastname', 'admissionNo')
            ->first();


        $current = 'Current';

        $totalreg = DB::table('subjectclass')
            ->where('schoolclassid', $studentClassData->class_id)
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->where('schoolsession.status', '=', $current)
            ->distinct('subjectteacher.subjectid')
            ->count('subjectteacher.subjectid');
            //dd($totalreg);

        $reg = DB::table('student_subject_register_record')
            ->where('student_subject_register_record.studentId', $studentId)
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'student_subject_register_record.subjectclassid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'student_subject_register_record.session')
            ->where('schoolsession.status', '=', $current)
            ->count();
            //dd($reg);

        $registeredSubjects = DB::table('student_subject_register_record')
            ->where('student_subject_register_record.studentId', $studentId)
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'student_subject_register_record.subjectclassid')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'student_subject_register_record.session')
            ->where('schoolsession.status', '=', $current)
           ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
           ->pluck('subjectteacher.id')
           ->toArray();
           //dd($registeredSubjects);

            $exams = DB::table('exams')
            ->whereIn('subject_id',  $registeredSubjects)
           //->where('schoolclass_id', $studentClassData->class_id)
          // ->where('termid', $studentClassData->term_id)
           ->where('session', $studentClassData->session_id)
            ->select('id', 'title', 'subject_id', 'description','duration','start_time','end_time')
            ->get();

           // dd($exams);

        $class = (object) ['id' => $studentClassData->class_id, 'schoolclass' => $studentClassData->class_name];
        $term = (object) ['id' => $studentClassData->term_id, 'term' => $studentClassData->term_name];
        $session = (object) ['id' => $studentClassData->session_id, 'session' => $studentClassData->session_name];

        return view('cbt.index', [
            'exams' => $exams,
            'student' => $student,
            'class' => $class,
            'term' => $term,
            'session' => $session,
            'totalreg' => $totalreg,
            'reg' => $reg,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function takeCBT($examid)
    {
        try {
            // Get the authenticated student
           // $student = auth()->user();
            $student = 1;
            
            // Verify student has permission to take this exam
            $exam = Exam::where('id', $examid)
                ->with(['questions.options' => function ($query) {
                    $query->select('id', 'question_id', 'option_text');
                }])
                ->firstOrFail();

            // Check if exam is currently available
            $now = Carbon::now();
            $startTime = Carbon::parse($exam->start_time);
            $endTime = Carbon::parse($exam->end_time);

            // if (!$now->between($startTime, $endTime)) {
            //     return redirect()->route('cbt.index')->with('error', 'This exam is not currently available.');
            // }

            // Check if student has already taken the exam
            $existingAttempt = ExamAttempt::where('student_id', $student)
                ->where('exam_id', $exam->id)
                ->first();

            if ($existingAttempt) {
                return redirect()->route('cbt.index')->with('error', 'You have already taken this exam.');
            }

            // Create new exam attempt
            $attempt = ExamAttempt::create([
                'student_id' => $student,
                'exam_id' => $exam->id,
                'start_time' => $now,
                'status' => 'in_progress'
            ]);

            // Prepare question data for frontend
            $questions = $exam->questions->map(function ($question) {
                return [
                    'id' => $question,
                    'text' => $question->question_text,
                    'options' => $question->options->pluck('option_text')->toArray(),
                    'image_url' => $question->image ? asset('storage/' . $question->image) : null // Adjust path as needed
                ];
            })->toArray();

            // Get student registration details
            $registration = Student::where('id', $student)
                ->with(['class', 'term', 'session'])
                ->first();

            if (!$registration) {
                return redirect()->route('cbt.index')->with('error', 'No registration found for this student.');
            }

            return view('cbt.take', [
                'exam' => $exam,
                'questions' => $questions,
                'student' => $student,
                'class' => $registration->class,
                'term' => $registration->term,
                'session' => $registration->session,
                'attempt' => $attempt
            ]);

        } catch (\Exception $e) {
            return redirect()->route('cbt.index')
                ->with('error', 'An error occurred while loading the exam: ' . $e->getMessage());
        }
    }

    public function submit(Request $request)
    {
        try {
            \Log::info('Submit request received', $request->all());
    
            $data = $request->validate([
                'attempt_id' => 'required|exists:exam_attempts,id',
                'exam_id' => 'required|exists:exams,id',
                'answers' => 'required|array',
                'answers.*.question_id' => '',
                'answers.*.answer' => 'nullable|string',
                'answers.*.notes' => 'nullable|string',
            ]);
    
            $student=1;
            if (!$student) {
                throw new \Exception('No authenticated student or student with ID 1 found');
            }
            \Log::info('Student ID', ['student_id' => $student]);
    
            $attempt = ExamAttempt::findOrFail($data['attempt_id']);
            \Log::info('Attempt found', ['attempt_id' => $attempt]);
    
            if ($attempt->student_id != $student || $attempt->exam_id != $data['exam_id']) {
                return response()->json(['success' => false, 'message' => 'Invalid attempt or exam'], 403);
            }
    
            if ($attempt->status === 'completed') {
                return response()->json(['success' => true, 'message' => 'Exam already submitted']);
            }
    
            $attempt->update([
                'end_time' => Carbon::now(),
                'status' => 'completed'
            ]);
            \Log::info('Attempt updated', ['attempt_id' => $attempt->id]);
    
            $exam = Exam::with(['questions.options'])->findOrFail($data['exam_id']);
            $totalMarks = $exam->questions->count();
            $score = 0;
    
            foreach ($data['answers'] as $submittedAnswer) {
                $question = $exam->questions->firstWhere('id', $submittedAnswer['question_id']);
                if ($question) {
                    $correctOption = $question->options->where('is_correct', true)->first();
                    if (!$correctOption) {
                        \Log::warning('No correct option found for question', ['question_id' => $submittedAnswer['question_id']]);
                        continue;
                    }
                    $correctAnswer = $correctOption->option_text;
                    \Log::info('Checking answer', [
                        'question_id' => $submittedAnswer['question_id'],
                        'submitted_answer' => $submittedAnswer['answer'],
                        'correct_answer' => $correctAnswer
                    ]);
                    if ($submittedAnswer['answer'] === $correctAnswer) {
                        $score++;
                    }
                }
            }
    
            Result::create([
                'user_id' => $student,
                'exam_id' => $data['exam_id'],
                'score' => $score,
                'total_marks' => $totalMarks,
            ]);
            \Log::info('Result saved', ['score' => $score, 'total_marks' => $totalMarks]);
    
            return response()->json(['success' => true, 'message' => 'Exam submitted successfully']);
    
        } catch (\Exception $e) {
            \Log::error('Submission failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
