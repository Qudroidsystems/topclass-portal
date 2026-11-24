<?php

namespace App\Http\Controllers;

use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\StudentCurrentTerm;
use App\Models\SubjectTeacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StudentClassOperationsController
 *
 * Owns "roster" operations: acting on the SET of students that match a
 * class + term + session, as opposed to StudentController which owns
 * "directory" operations (find/view/edit one student at a time).
 *
 * Endpoints:
 *   - bulkUpdateCurrentTerm()
 *   - updateCurrentTerm()
 *   - getStudentsByClassAndSession()
 *   - bulkUpdateStatus()
 *   - getStudentsInTerm()
 *   - removeFromTerm()
 *   - bulkRemoveFromTerm()
 *   - getExamSubjects()      (exam attendance sheet)
 *   - examAttendancePdf()    (exam attendance sheet)
 */
class StudentClassOperationsController extends Controller
{
    public function __construct()
    {
        // Mirrors the permission gates StudentController used for the
        // equivalent actions before the split.
        $this->middleware('permission:Update student', ['only' => [
            'bulkUpdateCurrentTerm', 'updateCurrentTerm', 'bulkUpdateStatus',
        ]]);
        $this->middleware('permission:Delete student', ['only' => [
            'removeFromTerm', 'bulkRemoveFromTerm',
        ]]);
        $this->middleware('permission:View student|Show Student|Create student|Update student|Delete student', ['only' => [
            'getExamSubjects', 'examAttendancePdf',
        ]]);
    }

    /**
     * Class & Term Operations page.
     */
    public function index()
    {
        $pagetitle = 'Class & Term Operations';

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass, ' - ', schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $schoolterms    = Schoolterm::select('id', 'term as name')->get();
        $schoolsessions = Schoolsession::select('id', 'session as name')->get();

        return view('student.class-operations', compact(
            'schoolclasses', 'schoolterms', 'schoolsessions', 'pagetitle'
        ));
    }

    /**
     * ── Roster Actions: Class Assignment ────────────────────────────
     */

    public function bulkUpdateCurrentTerm(Request $request)
    {
        $request->validate([
            'student_ids'   => 'required|array',
            'student_ids.*' => 'exists:studentRegistration,id',
            'schoolclassId' => 'required|exists:schoolclass,id',
            'termId'        => 'required|exists:schoolterm,id',
            'sessionId'     => 'required|exists:schoolsession,id',
            'is_current'    => 'sometimes|boolean',
        ]);
        try {
            DB::beginTransaction();
            $success = 0; $failed = 0; $results = [];
            foreach ($request->student_ids as $studentId) {
                try {
                    if (!Student::find($studentId)) { $results[$studentId]='Not found'; $failed++; continue; }
                    StudentCurrentTerm::registerTerm($studentId, $request->schoolclassId, $request->termId, $request->sessionId, $request->input('is_current', true));
                    $results[$studentId]='Success'; $success++;
                } catch (\Exception $e) {
                    Log::error("Error registering term for student {$studentId}: ".$e->getMessage());
                    $results[$studentId]='Failed: '.$e->getMessage(); $failed++;
                }
            }
            DB::commit();
            return response()->json(['success'=>true,'message'=>"Registered term for {$success} student(s). Failed: {$failed}.",'data'=>$results,'summary'=>['total'=>count($request->student_ids),'success'=>$success,'failed'=>$failed]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function updateCurrentTerm(Request $request, $studentId)
    {
        $request->validate(['schoolclassId'=>'required|exists:schoolclass,id','termId'=>'required|exists:schoolterm,id','sessionId'=>'required|exists:schoolsession,id','is_current'=>'sometimes|boolean']);
        try {
            if (!Student::find($studentId)) return response()->json(['success'=>false,'message'=>'Student not found'], 404);
            $currentTerm = StudentCurrentTerm::registerTerm($studentId, $request->schoolclassId, $request->termId, $request->sessionId, $request->input('is_current', true));
            return response()->json(['success'=>true,'message'=>'Term registered successfully','data'=>$currentTerm]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getStudentsByClassAndSession(Request $request)
    {
        try {
            $request->validate(['class_id'=>'required|exists:schoolclass,id','session_id'=>'required|exists:schoolsession,id']);

            $students = Student::query()
                ->leftJoin('studentclass',  'studentclass.studentId',  '=','studentRegistration.id')
                ->leftJoin('studentpicture','studentpicture.studentid','=','studentRegistration.id')
                ->leftJoin('schoolclass',   'schoolclass.id',          '=','studentclass.schoolclassid')
                ->leftJoin('schoolarm',     'schoolarm.id',            '=','schoolclass.arm')
                ->where('studentclass.schoolclassid', $request->class_id)
                ->where('studentclass.sessionid',     $request->session_id)
                ->select([
                    'studentRegistration.id',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentRegistration.statusId',
                    'studentRegistration.student_status',
                    'studentpicture.picture',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                ])->get();

            $processedStudents = $students->map(function ($student) {
                $s = new \stdClass();
                $s->id             = $student->id;
                $s->admissionNo    = $student->admissionNo;
                $s->firstname      = $student->firstname;
                $s->lastname       = $student->lastname;
                $s->othername      = $student->othername;
                $s->gender         = $student->gender;
                $s->statusId       = $student->statusId;
                $s->student_status = $student->student_status;
                $s->picture        = $student->picture;
                $s->schoolclass    = $student->schoolclass;
                $s->arm            = $student->arm;
                return $s;
            });

            return response()->json([
                'success'  => true,
                'students' => $processedStudents,
                'stats'    => [
                    'total'        => $processedStudents->count(),
                    'active'       => $processedStudents->where('student_status','Active')->count(),
                    'inactive'     => $processedStudents->where('student_status','Inactive')->count(),
                    'old_students' => $processedStudents->where('statusId',1)->count(),
                    'new_students' => $processedStudents->where('statusId',2)->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getStudentsByClassAndSession: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        try {
            $request->validate([
                'student_ids'   => 'required|array',
                'student_ids.*' => 'exists:studentRegistration,id',
                'update_type'   => 'required|in:activity_status,student_type',
                'value'         => 'required',
            ]);

            DB::beginTransaction();

            $updated = 0;
            if ($request->update_type === 'activity_status') {
                if (!in_array($request->value, ['Active','Inactive'])) throw new \Exception('Invalid activity status value.');
                $updated = Student::whereIn('id', $request->student_ids)->update(['student_status'=>$request->value]);
            } else {
                if (!in_array($request->value, ['old','new'])) throw new \Exception('Invalid student type value.');
                $updated = Student::whereIn('id', $request->student_ids)->update(['statusId'=>$request->value==='old'?1:2]);
            }

            DB::commit();
            return response()->json(['success'=>true,'message'=>"Successfully updated {$updated} student(s)",'updated_count'=>$updated]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * ── Term Registration Management ────────────────────────────────
     */

    public function getStudentsInTerm(Request $request)
    {
        try {
            $request->validate([
                'term_id'    => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id',
                'class_id'   => 'nullable|exists:schoolclass,id',
            ]);

            $query = StudentCurrentTerm::with(['student.picture','schoolClass.armRelation','term','session'])
                ->where('termId',    $request->term_id)
                ->where('sessionId', $request->session_id);

            if ($request->filled('class_id')) $query->where('schoolclassId', $request->class_id);

            $registrations = $query->get();

            $formattedStudents = $registrations->map(function ($reg) {
                $student = $reg->student;
                if (!$student) return null;
                return [
                    'registration_id' => $reg->id,
                    'student_id'      => $student->id,
                    'admissionNo'     => $student->admissionNo ?? 'N/A',
                    'firstname'       => $student->firstname ?? '',
                    'lastname'        => $student->lastname  ?? '',
                    'othername'       => $student->othername ?? '',
                    'fullname'        => trim(($student->lastname??'').' '.($student->firstname??'').' '.($student->othername??'')),
                    'gender'          => $student->gender ?? 'N/A',
                    'class'           => $reg->schoolClass ? $reg->schoolClass->schoolclass : 'N/A',
                    'arm'             => $reg->schoolClass && $reg->schoolClass->armRelation ? $reg->schoolClass->armRelation->arm : '',
                    'term'            => $reg->term    ? $reg->term->term       : 'N/A',
                    'session'         => $reg->session ? $reg->session->session : 'N/A',
                    'is_current'      => $reg->is_current,
                    'picture'         => $student->picture ? $student->picture->picture : null,
                    'registered_at'   => $reg->created_at ? $reg->created_at->format('d M Y') : 'N/A',
                ];
            })->filter()->values();

            return response()->json(['success'=>true,'students'=>$formattedStudents,'total'=>$formattedStudents->count()]);

        } catch (\Exception $e) {
            Log::error('Error fetching students in term: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function removeFromTerm(Request $request)
    {
        try {
            $request->validate(['registration_id'=>'required|exists:student_current_term,id']);
            DB::beginTransaction();
            $reg         = StudentCurrentTerm::findOrFail($request->registration_id);
            $studentName = $reg->student ? $reg->student->firstname.' '.$reg->student->lastname : 'Unknown';
            $reg->delete();
            DB::commit();
            return response()->json(['success'=>true,'message'=>'Student removed from term registration successfully','student_name'=>$studentName]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function bulkRemoveFromTerm(Request $request)
    {
        try {
            $request->validate(['registration_ids'=>'required|array','registration_ids.*'=>'exists:student_current_term,id']);
            DB::beginTransaction();
            $count = StudentCurrentTerm::whereIn('id', $request->registration_ids)->delete();
            DB::commit();
            return response()->json(['success'=>true,'message'=>"Successfully removed {$count} student(s) from term registration",'removed_count'=>$count]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * ── Exam Attendance Sheet ───────────────────────────────────────
     */

    /**
     * Subjects (with their teachers) assigned to a class for a term + session.
     */
    public function getExamSubjects(Request $request)
    {
        try {
            $request->validate([
                'class_id'   => 'required|exists:schoolclass,id',
                'term_id'    => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id',
            ]);

            $rows = SubjectTeacher::with(['subject', 'user'])
                ->where('termid', $request->term_id)
                ->where('sessionid', $request->session_id)
                ->whereHas('subjectclass', fn ($q) => $q->where('schoolclassid', $request->class_id))
                ->get()
                ->map(fn ($st) => [
                    'id'      => $st->id,
                    'subject' => $st->subject->subject ?? 'N/A',
                    'code'    => $st->subject->subject_code ?? '',
                    'teacher' => $st->user->name ?? 'N/A',
                ])
                ->sortBy('subject', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return response()->json(['success' => true, 'subjects' => $rows]);
        } catch (\Exception $e) {
            Log::error('getExamSubjects: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Printable examination attendance sheet.
     */
    public function examAttendancePdf(Request $request)
    {
        $request->validate([
            'class_id'          => 'required|exists:schoolclass,id',
            'term_id'           => 'required|exists:schoolterm,id',
            'session_id'        => 'required|exists:schoolsession,id',
            'student_ids'       => 'required|array|min:1',
            'student_ids.*'     => 'exists:studentRegistration,id',
            'subjectteacher_id' => 'nullable|exists:subjectteacher,id',
            'subject_name'      => 'nullable|string|max:255',
            'teacher_name'      => 'nullable|string|max:255',
            'exam_title'        => 'nullable|string|max:255',
            'exam_date'         => 'nullable|date',
            'start_time'        => 'nullable|string|max:50',
            'duration'          => 'nullable|string|max:50',
            'venue'             => 'nullable|string|max:255',
            'extra_rows'        => 'nullable|integer|min:0|max:20',
        ]);

        // Only students actually registered in this class/term/session
        $registeredIds = StudentCurrentTerm::where('schoolclassId', $request->class_id)
            ->where('termId', $request->term_id)
            ->where('sessionId', $request->session_id)
            ->whereIn('studentId', $request->student_ids)
            ->pluck('studentId');

        $students = Student::whereIn('id', $registeredIds)
            ->orderBy('lastname')->orderBy('firstname')
            ->get(['id', 'admissionNo', 'firstname', 'lastname', 'othername']);

        if ($students->isEmpty()) {
            return back()->with('error', 'No registered students found for the selection.');
        }

        $class   = Schoolclass::with('armRelation')->find($request->class_id);
        $term    = Schoolterm::find($request->term_id);
        $session = Schoolsession::find($request->session_id);

        $subjectName = $request->subject_name;
        $teacherName = $request->teacher_name;
        $subjectCode = null;

        if ($request->filled('subjectteacher_id')) {
            $st = SubjectTeacher::with(['subject', 'user'])->find($request->subjectteacher_id);
            $subjectName = $st->subject->subject ?? $subjectName;
            $subjectCode = $st->subject->subject_code ?? null;
            $teacherName = $st->user->name ?? $teacherName;
        }

        $data = [
            'school'      => SchoolInformation::getActiveSchool(),
            'students'    => $students,
            'className'   => $class ? trim($class->schoolclass . ' ' . ($class->armRelation->arm ?? '')) : 'N/A',
            'termName'    => $term->term ?? 'N/A',
            'sessionName' => $session->session ?? 'N/A',
            'subjectName' => $subjectName ?: '—',
            'subjectCode' => $subjectCode,
            'teacherName' => $teacherName ?: '—',
            'examTitle'   => $request->input('exam_title') ?: 'Examination',
            'examDate'    => $request->filled('exam_date') ? \Carbon\Carbon::parse($request->exam_date)->format('l, d F Y') : null,
            'startTime'   => $request->start_time,
            'duration'    => $request->duration,
            'venue'       => $request->venue,
            'extraRows'   => (int) $request->input('extra_rows', 0),
            'generatedBy' => auth()->user()->name ?? '',
        ];

        $filename = 'Exam-Attendance_' . preg_replace('/[^A-Za-z0-9\-]+/', '-', $data['className'] . '_' . $data['subjectName']) . '.pdf';

        return Pdf::loadView('student.reports.exam_attendance_pdf', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'defaultFont' => 'DejaVu Sans'])
            ->stream($filename);
    }
}