<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;

use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\SubjectTeacher;
use App\Models\ClassTeacher;
use App\Models\Staffclasssetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Add this line

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
            $user = auth()->user();
            $current = "Current";
            
            $terms = Schoolterm::all();
            $session = SchoolSession::all();
            $exams = Exam::all();
      
    
            $mysubjects = SubjectTeacher::where('staffid',$user->id)
            ->leftJoin('users', 'users.id','=','subjectteacher.staffid')
            ->leftJoin('subjectclass', 'subjectclass.subjectteacherid','=','subjectteacher.id')
            ->leftJoin('schoolclass', 'schoolclass.id','=','subjectclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id','=','schoolclass.arm')
            ->leftJoin('subject', 'subject.id','=','subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id','=','subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id','=','subjectteacher.sessionid')
            ->where('schoolsession.status','=',$current)
            ->get(['subjectteacher.id as id','users.id as userid','users.name as staffname',
                 'subject.subject as subject','subject.subject_code as subjectcode','schoolclass.schoolclass as schoolclass','schoolarm.arm as arm',
                 'subjectteacher.termid as termid','subjectteacher.sessionid as sessionid',
               'schoolterm.term as term','schoolsession.session as session'])->sortBy('subject');
          
           //dd($mysubjects);
        
            $myclass = ClassTeacher::where('staffid',$user->id)
                ->leftJoin('users', 'users.id','=','classteacher.staffid')
                ->leftJoin('schoolclass', 'schoolclass.id','=','classteacher.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id','=','schoolclass.arm')
                ->leftJoin('schoolterm', 'schoolterm.id','=','classteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id','=','classteacher.sessionid')
                ->where('schoolsession.status','=',$current)
                ->get(['classteacher.id as id','users.id as userid','users.name as staffname',
                    'schoolclass.schoolclass as schoolclass','classteacher.termid as termid','classteacher.sessionid as sessionid','schoolarm.arm as schoolarm',
                    'schoolclass.description as classcategory','schoolterm.term as term',
                    'schoolsession.session as session','classteacher.updated_at as updated_at','schoolclass.id as schoolclassID'])->sortBy('schoolclass');
    
        return view('exam.index', compact('exams','terms','session','mysubjects','myclass'));
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
    $validated = $request->validate([
        'staffId'=> 'required|string|max:255',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'duration' => 'required|integer|min:1',
        'start_time' => 'required|date',
        'end_time' => 'required|date|after:start_time',
        'termid' => 'required|string|max:255',
        'session' => 'required|string|max:255',
        'subject_id' => 'required',
        'schoolclass_id' => 'required|', 
        'is_published' => 'boolean|nullable',
    ]);
    
    // Handle the checkbox value (will be null if not checked)
    $validated['is_published'] = $request->has('is_published') ? true : false;
    
    Exam::create($validated);
    return redirect()->route('exams.index')->with('success', 'Exam created successfully');
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
