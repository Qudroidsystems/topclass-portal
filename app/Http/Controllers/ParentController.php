<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ParentRegistration;


class ParentController extends Controller
{


    function __construct()
    {
         $this->middleware('permission:parent-list|parent-create|parent-edit|parent-delete', ['only' => ['index','store']]);
         $this->middleware('permission:parent-create', ['only' => ['create','store']]);
         $this->middleware('permission:parent-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:parent-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        //$parent = parentRegistration::all();
        $parent = ParentRegistration::leftJoin('studentRegistration', 'studentRegistration.id','=','parentRegistration.studentId')
        ->get(['studentRegistration.admissionNo as admissionNo','parentRegistration.id as id','parentRegistration.father as father',
             'parentRegistration.mother as mother','parentRegistration.father_phone as father_phone',
             'parentRegistration.studentId as studentID','parentRegistration.mother_phone as mother_phone']);

             foreach ($parent as $p){
//echo $p->studentID;
             }

     return view('parent.index')->with('parent',$parent);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $id = $request->post('sid');

        //echo $id;
       $parent =  ParentRegistration::find($id);
       $input = $request->all();

         $parent->update($input);
        return redirect()->back()->with('success', 'Data updated successfully');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
       // echo $id;

        $student = Student::find($id);
        $parent = ParentRegistration::find($id);

      //echo $parent->mother_title;
       return View('parent.edit')->with('student',$student)
                             ->with('parent',$parent);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        //echo $id;

       // $parent =  parentRegistration::find($id);
        //$input = $request->all();
        echo $parent;
       // $parent->update($input);
        //return redirect()->back()->with('success', 'Data updated successfully');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
