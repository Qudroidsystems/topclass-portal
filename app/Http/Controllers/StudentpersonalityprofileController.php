<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Schoolterm;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\BroadsheetsMock;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Studentpersonalityprofile;

class StudentpersonalityprofileController extends Controller
{
    /**
     * Display the student personality profile with terminal and mock reports.
     *
     * @param int $id Student ID
     * @param int $schoolclassid School Class ID
     * @param int $sessionid School Session ID
     * @param int $termid School Term ID
     * @return \Illuminate\Http\Response
     */
    public function studentpersonalityprofile($id, $schoolclassid, $sessionid, $termid) 
    {
        $pagetitle = "Student Personality Profile";

        // Fetch student details
        $students = Student::where('studentRegistration.id', $id)
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->get([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as fname',
                'studentRegistration.home_address2 as homeaddress',
                'studentRegistration.lastname as lastname',
                'studentRegistration.othername as othername',
                'studentRegistration.dateofbirth as dateofbirth',
                'studentRegistration.gender as gender',
                'studentRegistration.updated_at as updated_at',
                'studentpicture.picture as picture'
            ]);

        // Create personality profile if it doesn't exist, then fetch
        $studentpp = Studentpersonalityprofile::firstOrCreate(
            [
                'studentid' => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                // 'staffid' => Auth::user()->id,
            ],
            [
                
                // Add any default values for other fields if needed
            ]
        );
        $studentpp = Studentpersonalityprofile::where('studentid', $id)
            ->where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->where('termid', $termid)
           // ->where('staffid', Auth::user()->id)
            ->get();

        // Fetch terminal report scores
        $scores = Broadsheets::where('broadsheet_records.student_id', $id)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.schoolclass_id', $schoolclassid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->orderBy('subject.subject')
            ->get([
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
                'broadsheets.subject_position_class as position',
                'broadsheets.avg as class_average',
            ]);

        // Fetch mock report scores
        $mockScores = BroadsheetsMock::where('broadsheet_records_mock.student_id', $id)
            ->where('broadsheetmock.term_id', $termid)
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.schoolclass_id', $schoolclassid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->get([
                'subject.subject as subject_name',
                'subject.subject_code',
                'broadsheetmock.exam',
                'broadsheetmock.total',
                'broadsheetmock.grade',
                'broadsheetmock.subject_position_class as position',
                'broadsheetmock.avg as class_average',
            ]);

        $schoolclass = Schoolclass::where('id', $schoolclassid)->first(['schoolclass', 'arm']);
        $schoolterm = Schoolterm::where('id', $termid)->value('term') ?? 'N/A';
        $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';

        return view('studentpersonalityprofile.edit')
            ->with('students', $students)
            ->with('studentpp', $studentpp)
            ->with('scores', $scores)
            ->with('mockScores', $mockScores)
            ->with('staffid', Auth::user()->id)
            ->with('studentid', $id)
            ->with('schoolclassid', $schoolclassid)
            ->with('sessionid', $sessionid)
            ->with('termid', $termid)
            ->with('pagetitle', $pagetitle)
            ->with('schoolclass', $schoolclass)
            ->with('schoolterm', $schoolterm)
            ->with('schoolsession', $schoolsession);
    }

    /**
     * Save or update the student personality profile.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $request->validate([
            'studentid' => 'required|exists:studentRegistration,id',
            'schoolclassid' => 'required|exists:schoolclass,id',
            'termid' => 'required|exists:schoolterm,id',
            'sessionid' => 'required|exists:schoolsession,id',
            'staffid' => 'nullable|exists:users,id',
            'punctuality' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'neatness' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'leadership' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'attitude' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'reading' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'honesty' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'cooperation' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'selfcontrol' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'politeness' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'physicalhealth' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'stability' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'gamesandsports' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'attendance' => 'nullable|integer|min:0|max:100',
            'attentiveness_in_class' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'class_participation' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'relationship_with_others' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'doing_assignment' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'writing_skill' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'reading_skill' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'spoken_english_communication' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'hand_writing' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'club' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'music' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'classteachercomment' => 'nullable|string|max:1000',
            'principalscomment' => 'nullable|string|max:1000',
            'remark_on_other_activities' => 'nullable|string|max:1000',
        ]);

        $studentpp = Studentpersonalityprofile::where('studentid', $request->studentid)
            ->where('schoolclassid', $request->schoolclassid)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->firstOrNew();

        try {
            $input = $request->only([
                'studentid',
                'staffid',
                'schoolclassid',
                'termid',
                'sessionid',
                'punctuality',
                'neatness',
                'leadership',
                'attitude',
                'reading',
                'honesty',
                'cooperation',
                'selfcontrol',
                'politeness',
                'physicalhealth',
                'stability',
                'gamesandsports',
                'attendance',
                'attentiveness_in_class',
                'class_participation',
                'relationship_with_others',
                'doing_assignment',
                'writing_skill',
                'reading_skill',
                'spoken_english_communication',
                'hand_writing',
                'club',
                'music',
                'classteachercomment',
                'principalscomment',
                'remark_on_other_activities',
            ]);
            $studentpp->fill($input)->save();
            return redirect()->back()->with('success', 'Student Personality Profile Updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }
}