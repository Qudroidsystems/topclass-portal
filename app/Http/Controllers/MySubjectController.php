<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubjectTeacher;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\Subject;
use App\Models\Schoolclass;
use App\Models\Schoolarm;
use Illuminate\Support\Facades\Auth;

class MySubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-subject|Create my-subject|Update my-subject|Delete my-subject', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create my-subject', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update my-subject', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete my-subject', ['only' => ['destroy']]);
    }

    public function index()
    {
        // Page title
        $pagetitle = "My subjects";

        $user = Auth::user();
        $current = "Current";

        // Query for current subjects
        $query = SubjectTeacher::where('subjectteacher.staffid', $user->id)
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->where('schoolsession.status', '=', $current);

        $mysubjects = $query->select([
            'subjectteacher.id as id',
            'users.id as userid',
            'users.name as staffname',
            'subject.subject as subject',
            'subject.subject_code as subjectcode',
            'schoolclass.schoolclass as schoolclass',
            'schoolarm.arm as arm',
            'subjectteacher.termid as termid',
            'subjectteacher.sessionid as sessionid',
            'schoolterm.term as term',
            'schoolsession.session as session'
        ])->orderBy('subject.subject')->paginate(5);

        // Query for subject history
        $mysubjectshistory = SubjectTeacher::query()
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('subjectteacher.staffid', $user->id)
            ->where('schoolsession.status', '!=', $current)
            ->select([
                'subjectteacher.id as id',
                'users.id as userid',
                'users.name as staffname',
                'subject.subject as subject',
                'subject.subject_code as subjectcode',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm',
                'subjectteacher.termid as termid',
                'subjectteacher.sessionid as sessionid',
                'schoolterm.term as term',
                'schoolsession.session as session'
            ])->orderBy('schoolsession.session')->get();

        $terms = Schoolterm::all();
        $schoolsessions = Schoolsession::where('status', 1)->get();
        $subjects = Subject::all();
        $schoolclasses = Schoolclass::join('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
            ->select('schoolclass.id as id', 'schoolclass.schoolclass', 'schoolarm.arm')
            ->get();

        // Count unique subjects per session
        $unique_subject_count = SubjectTeacher::query()
            ->join('schoolsession', 'subjectteacher.sessionid', '=', 'schoolsession.id')
            ->where('subjectteacher.staffid', $user->id)
            ->where('schoolsession.status', $current)
            ->distinct('subjectteacher.subjectid', 'subjectteacher.sessionid')
            ->count();

        // Count subjects per term for chart
        $term_counts = SubjectTeacher::query()
            ->join('schoolterm', 'subjectteacher.termid', '=', 'schoolterm.id')
            ->join('schoolsession', 'subjectteacher.sessionid', '=', 'schoolsession.id')
            ->where('subjectteacher.staffid', $user->id)
            ->where('schoolsession.status', $current)
            ->selectRaw('schoolterm.term, COUNT(DISTINCT subjectteacher.subjectid) as count')
            ->groupBy('schoolterm.term')
            ->pluck('count', 'term')
            ->toArray();

        return view('mysubject.index', compact('mysubjects', 'mysubjectshistory', 'terms', 'schoolsessions', 'subjects', 'schoolclasses', 'term_counts', 'unique_subject_count', 'pagetitle'));
    }

    // Other methods unchanged
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}