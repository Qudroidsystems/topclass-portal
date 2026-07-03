<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\Broadsheet;
use App\Models\BroadsheetRecord;
use App\Models\BroadsheetRecordMock;
use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\ParentRegistration;
use App\Models\PromotionStatus;
use App\Models\ReportHistory;
use App\Models\Schoolclass;
use App\Models\Schoolhouse;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\StudentBatchModel;
use App\Models\StudentBillInvoice;
use App\Models\StudentBillPayment;
use App\Models\StudentBillPaymentBook;
use App\Models\StudentBillPaymentRecord;
use App\Models\Studentclass;
use App\Models\StudentCurrentTerm;
use App\Models\Studenthouse;
use App\Models\Studenthouses;
use App\Models\Studentpersonalityprofile;
use App\Models\Studentpersonalityprofiles;
use App\Models\Studentpicture;
use App\Models\Subjectclass;
use App\Models\SubjectRegistrationStatus;
use App\Traits\ImageManager as TraitsImageManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    use TraitsImageManager;

    public function __construct()
    {
        $this->middleware("permission:View student|Show Student|Create student|Update student|Delete student", ["only" => ["index", "store"]]);
        $this->middleware("permission:Create student", ["only" => ["create", "store"]]);
        $this->middleware("permission:Update student", ["only" => ["edit", "update"]]);
        $this->middleware("permission:Delete student", ["only" => ["destroy", "deletestudent"]]);
        $this->middleware("permission:Create student-bulk-upload", ["only" => ["bulkupload"]]);
        $this->middleware("permission:Create student-bulk-uploadsave", ["only" => ["bulkuploadsave"]]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Student Management";

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass, ' - ', schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')
            ->get();
        $schoolterms = Schoolterm::select('id', 'term as name')->get();
        $schoolsessions = Schoolsession::select('id', 'session as name')->get();
        $currentSession = Schoolsession::where('status', 'Current')->first();
        $schoolhouses = Schoolhouse::all();

        $status_counts = Student::groupBy('statusId')
            ->selectRaw("CASE WHEN statusId = 1 THEN 'Old Student' ELSE 'New Student' END as student_status, COUNT(*) as student_count")
            ->pluck('student_count', 'student_status')
            ->toArray();
        $status_counts = [
            'Old Student' => $status_counts['Old Student'] ?? 0,
            'New Student' => $status_counts['New Student'] ?? 0
        ];

        $student_status_counts = Student::groupBy('student_status')
            ->selectRaw('student_status, COUNT(*) as status_count')
            ->pluck('status_count', 'student_status')
            ->toArray();
        $student_status_counts = [
            'Active'   => $student_status_counts['Active'] ?? 0,
            'Inactive' => $student_status_counts['Inactive'] ?? 0
        ];

        $gender_counts = Student::groupBy('gender')
            ->selectRaw('gender, COUNT(*) as gender_count')
            ->pluck('gender_count', 'gender')
            ->toArray();
        $gender_counts = [
            'Male'   => $gender_counts['Male'] ?? 0,
            'Female' => $gender_counts['Female'] ?? 0
        ];

        $religion_counts = Student::groupBy('religion')
            ->selectRaw('religion, COUNT(*) as religion_count')
            ->pluck('religion_count', 'religion')
            ->toArray();
        $religion_counts = [
            'Christianity' => $religion_counts['Christianity'] ?? 0,
            'Islam'        => $religion_counts['Islam'] ?? 0,
            'Others'       => $religion_counts['Others'] ?? 0
        ];

        $total_population = Student::count();

        $staff_count = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereNotIn('name', ['Student']);
        })->count();

        $currentTerm = Schoolterm::where('status', 'Current')->first();

        return view('student.index', compact(
            'schoolclasses',
            'schoolterms',
            'schoolsessions',
            'schoolhouses',
            'currentSession',
            'status_counts',
            'student_status_counts',
            'gender_counts',
            'religion_counts',
            'pagetitle',
            'total_population',
            'staff_count',
            'currentTerm',
        ));
    }

    /**
     * Get students optimized with server-side pagination and filtering.
     * Fixed: robust N/A date handling, no ONLY_FULL_GROUP_BY issues.
     */
    public function getStudentsOptimized(Request $request)
    {
        try {
            $perPage   = $request->get('per_page', 12);
            $search    = $request->get('search', '');
            $classId   = $request->get('class_id', 'all');
            $status    = $request->get('status', 'all');
            $gender    = $request->get('gender', 'all');
            $sessionId = $request->get('session_id', 'all');

            Log::info('getStudentsOptimized called', compact('perPage','search','classId','status','gender','sessionId'));

            // --- Build ID query first to avoid ONLY_FULL_GROUP_BY ---
            $idQuery = Student::query()
                ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
                ->select('studentRegistration.id');

            if (!empty($search)) {
                $idQuery->where(function ($q) use ($search) {
                    $q->where('studentRegistration.firstname', 'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.lastname',  'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.admissionNo','LIKE',"%{$search}%")
                      ->orWhere('studentRegistration.othername', 'LIKE', "%{$search}%");
                });
            }

            if ($classId !== 'all' && !empty($classId)) {
                $idQuery->where('studentclass.schoolclassid', $classId);
            }

            if ($status !== 'all' && !empty($status)) {
                if (in_array($status, ['1', '2'])) {
                    $idQuery->where('studentRegistration.statusId', $status);
                } elseif (in_array($status, ['Active', 'Inactive'])) {
                    $idQuery->where('studentRegistration.student_status', $status);
                }
            }

            if ($gender !== 'all' && !empty($gender)) {
                $idQuery->where('studentRegistration.gender', $gender);
            }

            if ($sessionId !== 'all' && !empty($sessionId)) {
                $idQuery->where('studentclass.sessionid', $sessionId);
            }

            $idQuery->groupBy('studentRegistration.id');

            $paginatedIds = $idQuery->paginate($perPage, ['studentRegistration.id'], 'page', $request->get('page', 1));
            $studentIds   = $paginatedIds->pluck('id')->toArray();

            if (empty($studentIds)) {
                return response()->json([
                    'success' => true,
                    'data'    => new \Illuminate\Pagination\LengthAwarePaginator(
                        [], 0, $perPage,
                        $request->get('page', 1),
                        ['path' => $request->url(), 'query' => $request->query()]
                    )
                ]);
            }

            $students = Student::query()
                ->leftJoin('studentpicture',      'studentpicture.studentid',      '=', 'studentRegistration.id')
                ->leftJoin('studentclass',        'studentclass.studentId',        '=', 'studentRegistration.id')
                ->leftJoin('schoolclass',         'schoolclass.id',                '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm',           'schoolarm.id',                  '=', 'schoolclass.arm')
                ->leftJoin('parentRegistration',  'parentRegistration.studentId',  '=', 'studentRegistration.id')
                ->leftJoin('studenthouses',       'studenthouses.studentid',       '=', 'studentRegistration.id')
                ->leftJoin('schoolhouses',        'schoolhouses.id',               '=', 'studenthouses.schoolhouse')
                ->whereIn('studentRegistration.id', $studentIds)
                ->select([
                    'studentRegistration.*',
                    'studentpicture.picture',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'studentclass.schoolclassid',
                    'studentclass.termid',
                    'studentclass.sessionid',
                    'parentRegistration.father',
                    'parentRegistration.mother',
                    'parentRegistration.father_phone',
                    'parentRegistration.mother_phone',
                    'parentRegistration.father_occupation',
                    'parentRegistration.father_city',
                    'parentRegistration.office_address',
                    'parentRegistration.parent_email',
                    'parentRegistration.parent_address',
                    'parentRegistration.father_title',
                    'parentRegistration.mother_title',
                    'schoolhouses.house as school_house',
                ])
                ->orderBy('studentRegistration.created_at', 'desc')
                ->get();

            $groupedStudents = $students->groupBy('id')->map(fn($g) => $g->first())->values();

            $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
                $groupedStudents,
                $paginatedIds->total(),
                $paginatedIds->perPage(),
                $paginatedIds->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $processedStudents = $paginatedData->getCollection()->map(function ($student) {
                try {
                    $age = null;
                    if ($student->dateofbirth && $student->dateofbirth !== 'N/A'
                        && !str_contains($student->dateofbirth, 'N/A')
                        && !str_contains($student->dateofbirth, '0000-00-00')) {
                        try { $age = Carbon::parse($student->dateofbirth)->age; } catch (\Exception $e) {}
                    }

                    $safeDate = function ($value) {
                        if (!$value || $value === 'N/A' || str_contains($value, 'N/A') || str_contains($value, '0000-00-00')) {
                            return null;
                        }
                        try { return Carbon::parse($value)->format('Y-m-d'); } catch (\Exception $e) { return null; }
                    };

                    $safeDatetime = function ($value) {
                        if (!$value || $value === 'N/A' || str_contains($value, 'N/A')) return null;
                        try { return Carbon::parse($value)->format('Y-m-d H:i:s'); } catch (\Exception $e) { return null; }
                    };

                    return [
                        'id'                => $student->id,
                        'admissionNo'       => $student->admissionNo,
                        'admission_date'    => $safeDate($student->admission_date),
                        'admissionYear'     => $student->admissionYear,
                        'firstname'         => $student->firstname ?? '',
                        'lastname'          => $student->lastname  ?? '',
                        'othername'         => $student->othername ?? '',
                        'fullname'          => trim(($student->lastname ?? '').' '.($student->firstname ?? '').' '.($student->othername ?? '')),
                        'gender'            => $student->gender,
                        'statusId'          => $student->statusId,
                        'student_status'    => $student->student_status,
                        'created_at'        => $safeDatetime($student->created_at),
                        'updated_at'        => $safeDatetime($student->updated_at),
                        'picture'           => $student->picture,
                        'schoolclass'       => $student->schoolclass,
                        'arm'               => $student->arm,
                        'schoolclassid'     => $student->schoolclassid,
                        'termid'            => $student->termid,
                        'sessionid'         => $student->sessionid,
                        'age'               => $age,
                        'dateofbirth'       => $safeDate($student->dateofbirth),
                        'title'             => $student->title,
                        'placeofbirth'      => $student->placeofbirth,
                        'phone_number'      => $student->phone_number,
                        'email'             => $student->email,
                        'permanent_address' => $student->home_address2,
                        'future_ambition'   => $student->future_ambition,
                        'nationality'       => $student->nationality,
                        'state'             => $student->state,
                        'local'             => $student->local,
                        'city'              => $student->city,
                        'religion'          => $student->religion,
                        'blood_group'       => $student->blood_group,
                        'mother_tongue'     => $student->mother_tongue,
                        'nin_number'        => $student->nin_number,
                        'student_category'  => $student->student_category,
                        'last_school'       => $student->last_school,
                        'last_class'        => $student->last_class,
                        'reason_for_leaving'=> $student->reason_for_leaving,
                        'father_name'       => $student->father,
                        'father_title'      => $student->father_title,
                        'father_phone'      => $student->father_phone,
                        'father_occupation' => $student->father_occupation,
                        'father_city'       => $student->father_city,
                        'mother_name'       => $student->mother,
                        'mother_title'      => $student->mother_title,
                        'mother_phone'      => $student->mother_phone,
                        'parent_email'      => $student->parent_email,
                        'parent_address'    => $student->parent_address,
                        'office_address'    => $student->office_address,
                        'school_house'      => $student->school_house,
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing student ID '.($student->id ?? 'unknown').': '.$e->getMessage());
                    return [
                        'id'        => $student->id,
                        'admissionNo'=> $student->admissionNo,
                        'firstname' => $student->firstname ?? '',
                        'lastname'  => $student->lastname  ?? '',
                        'fullname'  => trim(($student->lastname ?? '').' '.($student->firstname ?? '')),
                        'error'     => 'Failed to process student data',
                    ];
                }
            });

            $paginatedData->setCollection($processedStudents);

            return response()->json(['success' => true, 'data' => $paginatedData]);

        } catch (\Exception $e) {
            Log::error('Error in getStudentsOptimized: '.$e->getMessage()."\n".$e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Failed to fetch students: '.$e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        Log::debug('Creating new student', $request->all());

        try {
            $statesLgas = json_decode(file_get_contents(public_path('states_lgas.json')), true);
            $states     = array_column($statesLgas, 'state');
            $lgas       = collect($statesLgas)->pluck('lgas', 'state')->toArray();

            $validator = Validator::make($request->all(), [
                'avatar'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'admissionMode'      => 'required|in:auto,manual',
                'title'              => 'nullable|in:Master,Miss',
                'admissionNo'        => ['required','string','max:255','unique:studentRegistration,admissionNo'],
                'admissionYear'      => 'required|integer|min:1900|max:'.date('Y'),
                'admissionDate'      => 'required|date|before_or_equal:today',
                'firstname'          => 'required|string|max:255',
                'lastname'           => 'required|string|max:255',
                'othername'          => 'nullable|string|max:255',
                'gender'             => 'required|in:Male,Female',
                'dateofbirth'        => 'required|date|before:today',
                'placeofbirth'       => 'required|string|max:255',
                'nationality'        => 'required|string|max:255',
                'age'                => 'required|integer|min:1|max:100',
                'blood_group'        => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'mother_tongue'      => 'nullable|string|max:255',
                'religion'           => 'required|in:Christianity,Islam,Others',
                'sport_house'        => 'nullable|string|max:255',
                'phone_number'       => 'nullable|string|max:20',
                'email'              => 'nullable|email|max:255',
                'nin_number'         => 'nullable|string|max:20',
                'city'               => 'nullable|string|max:255',
                'state'              => ['required','string','max:255', function ($a,$v,$fail) use ($states) {
                    if (!in_array($v,$states)) $fail('The selected state is invalid.');
                }],
                'local'              => ['required','string','max:255', function ($a,$v,$fail) use ($request,$lgas) {
                    $s = $request->input('state');
                    if (!isset($lgas[$s]) || !in_array($v,$lgas[$s])) $fail('The selected LGA is invalid for the chosen state.');
                }],
                'future_ambition'    => 'required|string|max:500',
                'permanent_address'  => 'required|string|max:255',
                'student_category'   => 'required|in:Day,Boarding',
                'schoolclassid'      => 'required|exists:schoolclass,id',
                'schoolhouseid'      => 'required|exists:schoolhouses,id',
                'termid'             => 'required|exists:schoolterm,id',
                'sessionid'          => 'required|exists:schoolsession,id',
                'statusId'           => 'required|in:1,2',
                'student_status'     => 'required|in:Active,Inactive',
                'father_title'       => 'nullable|in:Mr,Dr,Prof',
                'mother_title'       => 'nullable|in:Mrs,Dr,Prof',
                'father_name'        => 'nullable|string|max:255',
                'mother_name'        => 'nullable|string|max:255',
                'father_occupation'  => 'nullable|string|max:255',
                'father_city'        => 'nullable|string|max:255',
                'office_address'     => 'nullable|string|max:255',
                'father_phone'       => 'nullable|string|max:20',
                'mother_phone'       => 'nullable|string|max:20',
                'parent_email'       => 'nullable|email|max:255',
                'parent_address'     => 'nullable|string|max:255',
                'last_school'        => 'nullable|string|max:255',
                'last_class'         => 'nullable|string|max:255',
                'reason_for_leaving' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()], 422);
                }
                return redirect()->route('student.index')->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $student = new Student();

            if ($request->admissionMode === 'auto') {
                $admissionResponse = $this->getLastAdmissionNumber(new Request(['year' => $request->admissionYear]));
                $admissionData     = json_decode($admissionResponse->getContent(), true);
                if (!$admissionData['success']) throw new \Exception('Failed to generate admission number: '.$admissionData['message']);
                $student->admissionNo = $admissionData['admissionNo'];
            } else {
                $student->admissionNo = $request->admissionNo;
            }

            $student->admission_date     = $request->admissionDate;
            $student->title              = $request->title;
            $student->admissionYear      = $request->admissionYear;
            $student->firstname          = $request->firstname;
            $student->lastname           = $request->lastname;
            $student->othername          = $request->othername;
            $student->gender             = $request->gender;
            $student->dateofbirth        = $request->dateofbirth;
            $student->age                = $request->age;
            $student->blood_group        = $request->blood_group;
            $student->mother_tongue      = $request->mother_tongue;
            $student->religion           = $request->religion;
            $student->sport_house        = $request->sport_house;
            $student->phone_number       = $request->phone_number;
            $student->email              = $request->email;
            $student->nin_number         = $request->nin_number;
            $student->city               = $request->city;
            $student->state              = $request->state;
            $student->local              = $request->local;
            $student->nationality        = $request->nationality;
            $student->placeofbirth       = $request->placeofbirth;
            $student->future_ambition    = $request->future_ambition;
            $student->home_address2      = $request->permanent_address;
            $student->student_category   = $request->student_category;
            $student->statusId           = $request->statusId;
            $student->student_status     = $request->student_status;
            $student->last_school        = $request->last_school;
            $student->last_class         = $request->last_class;
            $student->reason_for_leaving = $request->reason_for_leaving;
            $student->registeredBy       = auth()->user()->id;
            $student->save();

            $studentId = $student->id;

            Studentclass::create([
                'studentId'     => $studentId,
                'schoolclassid' => $request->schoolclassid,
                'termid'        => $request->termid,
                'sessionid'     => $request->sessionid,
            ]);

            PromotionStatus::create([
                'studentId'       => $studentId,
                'schoolclassid'   => $request->schoolclassid,
                'termid'          => $request->termid,
                'sessionid'       => $request->sessionid,
                'promotionStatus' => 'PROMOTED',
                'classstatus'     => 'CURRENT',
            ]);

            $parent               = new ParentRegistration();
            $parent->studentId    = $studentId;
            $parent->father_title = $request->father_title;
            $parent->mother_title = $request->mother_title;
            $parent->father       = $request->father_name;
            $parent->mother       = $request->mother_name;
            $parent->father_phone = $request->father_phone;
            $parent->mother_phone = $request->mother_phone;
            $parent->father_occupation = $request->father_occupation;
            $parent->father_city  = $request->father_city;
            $parent->office_address = $request->office_address;
            $parent->parent_email = $request->parent_email;
            $parent->parent_address = $request->parent_address;
            $parent->save();

            $picture            = new Studentpicture();
            $picture->studentid = $studentId;
            if ($request->hasFile('avatar')) {
                $path = $this->storeImage($request->file('avatar'), 'images/student_avatars');
                $picture->picture = basename($path);
            } else {
                $picture->picture = 'unnamed.jpg';
            }
            $picture->save();

            $studenthouses            = new Studenthouse();
            $studenthouses->studentid = $studentId;
            $studenthouses->schoolhouse = $request->studenthouseid;
            $studenthouses->termid    = $request->termid;
            $studenthouses->sessionid = $request->sessionid;
            $studenthouses->save();

            $studentpersonalityprofiles              = new Studentpersonalityprofile();
            $studentpersonalityprofiles->studentid   = $studentId;
            $studentpersonalityprofiles->schoolclassid = $request->schoolclassid;
            $studentpersonalityprofiles->termid      = $request->termid;
            $studentpersonalityprofiles->sessionid   = $request->sessionid;
            $studentpersonalityprofiles->save();

            StudentCurrentTerm::create([
                'studentId'     => $studentId,
                'schoolclassId' => $request->schoolclassid,
                'termId'        => $request->termid,
                'sessionId'     => $request->sessionid,
                'is_current'    => true,
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Student created successfully',
                    'student' => ['id' => $student->id, 'admissionNo' => $student->admissionNo],
                ], 201);
            }

            return redirect()->route('student.index')->with('success', 'Student created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating student: {$e->getMessage()}\n{$e->getTraceAsString()}");
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success'=>false,'message'=>'Failed to create student: '.$e->getMessage()], 500);
            }
            return redirect()->route('student.index')->with('error', 'Failed to create student: '.$e->getMessage());
        }
    }

    protected function storeImage($file, $directory)
    {
        try {
            return $file->store($directory, 'public');
        } catch (\Exception $e) {
            Log::error("Error storing image: {$e->getMessage()}");
            throw $e;
        }
    }

    public function data(Request $request): JsonResponse
    {
        try {
            $students = Student::leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('studentclass',  'studentclass.studentId',  '=', 'studentRegistration.id')
                ->leftJoin('schoolclass',   'schoolclass.id',           '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm',     'schoolarm.id',             '=', 'schoolclass.arm')
                ->select([
                    'studentRegistration.id',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentRegistration.statusId',
                    'studentRegistration.student_status',
                    'studentRegistration.created_at',
                    'studentpicture.picture',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'studentclass.schoolclassid',
                ])
                ->latest()
                ->get();

            return response()->json(['success' => true, 'students' => $students], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching students: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to fetch students: '.$e->getMessage()], 500);
        }
    }

    protected function generateAdmissionNumber()
    {
        $lastAdmission = Student::max('admissionNo');
        $year   = date('Y');
        $number = $lastAdmission ? (int) substr($lastAdmission, -4) + 1 : 1;
        return sprintf('TCC/2025/%04d', $number);
    }

    public function show($id)
    {
        try {
            $student = Student::where('studentRegistration.id', $id)
                ->leftJoin('studentclass',      'studentclass.studentId',    '=','studentRegistration.id')
                ->leftJoin('parentRegistration','parentRegistration.studentId','=','studentRegistration.id')
                ->leftJoin('studentpicture',    'studentpicture.studentid',  '=','studentRegistration.id')
                ->leftJoin('schoolclass',       'schoolclass.id',            '=','studentclass.schoolclassid')
                ->leftJoin('schoolarm',         'schoolarm.id',              '=','schoolclass.arm')
                ->leftJoin('schoolterm',        'schoolterm.id',             '=','studentclass.termid')
                ->leftJoin('schoolsession',     'schoolsession.id',          '=','studentclass.sessionid')
                ->leftJoin('studenthouses',     'studenthouses.studentId',   '=','studentRegistration.id')
                ->leftJoin('schoolhouses',      'schoolhouses.id',           '=','studenthouses.schoolhouse')
                ->where('schoolsession.status', 'Current')
                ->select([
                    'studentRegistration.id as id',
                    'studentRegistration.admissionNo as student_id',
                    'studentRegistration.firstname as first_name',
                    'studentRegistration.lastname as last_name',
                    'studentRegistration.othername as middle_name',
                    'studentRegistration.gender as gender',
                    'studentRegistration.dateofbirth as date_of_birth',
                    'studentRegistration.blood_group as blood_group',
                    'studentRegistration.admission_date as admission_date',
                    'studentRegistration.student_category as student_category',
                    'studentRegistration.mother_tongue as mother_tongue',
                    'studentRegistration.sport_house as sport_house',
                    'studentRegistration.phone_number as phone_number',
                    'studentRegistration.email as email',
                    'studentRegistration.nin_number as nin_number',
                    'studentRegistration.city as city',
                    'studentRegistration.statusId as statusId',
                    'studentRegistration.student_status as student_status',
                    'studentpicture.picture as picture',
                    'schoolclass.schoolclass as schoolclass',
                    'schoolarm.arm as arm',
                    'schoolterm.term as term',
                    'schoolsession.session as session',
                    'schoolhouses.house as schoolhouse',
                ])
                ->firstOrFail();

            $billPayments     = StudentBillPayment::where('student_id', $id)->with(['schoolBill','studentBillPaymentRecords'])->get();
            $billPaymentBooks = StudentBillPaymentBook::where('student_id', $id)->get();

            return view('student.show', compact('student','billPayments','billPaymentBooks'));
        } catch (\Exception $e) {
            return redirect()->route('student.index')->with('error', 'Student not found.');
        }
    }

    public function create()
    {
        $pagetitle    = "Create Student";
        $schoolclasses = Schoolclass::leftJoin('schoolarm','schoolarm.id','=','schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass,' - ',schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')->get();
        $schoolterms   = Schoolterm::select('id','term as name')->get();
        $schoolsessions= Schoolsession::select('id','session as name')->get();
        $currentSession= Schoolsession::where('status','Current')->first();

        return view('student.create', compact('schoolclasses','schoolterms','schoolsessions','currentSession','pagetitle'));
    }

    /**
     * Return student data for the edit modal — always fetches fresh from DB.
     */
    public function edit($student)
    {
        try {
            $studentData = Student::where('studentRegistration.id', $student)
                ->leftJoin('studentpicture',    'studentRegistration.id', '=','studentpicture.studentid')
                ->leftJoin('studentclass',      'studentRegistration.id', '=','studentclass.studentId')
                ->leftJoin('parentRegistration','studentRegistration.id', '=','parentRegistration.studentId')
                ->leftJoin('schoolclass',       'schoolclass.id',         '=','studentclass.schoolclassid')
                ->leftJoin('schoolarm',         'schoolarm.id',           '=','schoolclass.arm')
                ->leftJoin('schoolterm',        'schoolterm.id',          '=','studentclass.termid')
                ->leftJoin('schoolsession',     'schoolsession.id',       '=','studentclass.sessionid')
                ->leftJoin('studenthouses',     'studenthouses.studentId','=','studentRegistration.id')
                ->leftJoin('schoolhouses',      'schoolhouses.id',        '=','studenthouses.schoolhouse')
                ->select([
                    'studentRegistration.id',
                    'studentRegistration.admissionNo',
                    'studentRegistration.admissionYear',
                    'studentRegistration.admission_date as admissionDate',
                    'studentRegistration.title',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentRegistration.dateofbirth',
                    'studentRegistration.age',
                    'studentRegistration.blood_group',
                    'studentRegistration.mother_tongue',
                    'studentRegistration.religion',
                    'studentRegistration.sport_house',
                    'studentRegistration.phone_number',
                    'studentRegistration.email',
                    'studentRegistration.nin_number',
                    'studentRegistration.city',
                    'studentRegistration.state',
                    'studentRegistration.local',
                    'studentRegistration.nationality',
                    'studentRegistration.placeofbirth',
                    'studentRegistration.future_ambition',
                    'studentRegistration.home_address2 as permanent_address',
                    'studentRegistration.student_category',
                    'studentRegistration.statusId',
                    'studentRegistration.student_status',
                    'studentRegistration.last_school',
                    'studentRegistration.last_class',
                    'studentRegistration.reason_for_leaving',
                    'studentclass.schoolclassid',
                    'studentclass.termid',
                    'studentclass.sessionid',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'schoolterm.term as term_name',
                    'schoolsession.session as session_name',
                    'parentRegistration.father_title',
                    'parentRegistration.mother_title',
                    'parentRegistration.father as father_name',
                    'parentRegistration.mother as mother_name',
                    'parentRegistration.father_occupation',
                    'parentRegistration.father_city',
                    'parentRegistration.office_address',
                    'parentRegistration.father_phone',
                    'parentRegistration.mother_phone',
                    'parentRegistration.parent_email',
                    'parentRegistration.parent_address',
                    'studentpicture.picture',
                    'studenthouses.schoolhouse as schoolhouseid',
                    'schoolhouses.house as school_house',
                ])
                ->first();

            if (!$studentData) {
                return response()->json(['success'=>false,'message'=>'Student not found'], 404);
            }

            return response()->json(['success'=>true,'student'=>$studentData], 200);

        } catch (\Exception $e) {
            Log::error("Error fetching student ID {$student}: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Server error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Update student.
     *
     * KEY FIXES vs original:
     * 1. Studentclass  — find-then-update (not updateOrCreate) so changing class
     *    updates the existing row instead of inserting a duplicate.
     * 2. PromotionStatus — scoped to all four fields; correct and unchanged.
     * 3. StudentCurrentTerm — correct; unchanged.
     */

// ============================================================================
// FIXED update() METHOD — drop this into StudentController.php
// ============================================================================


public function update(Request $request, $id): JsonResponse
{
    Log::debug('Updating student', ['id' => $id, 'data' => $request->except(['avatar', '_token'])]);

    try {
        // Load states/LGAs safely
        $states = [];
        $lgas   = [];

        $jsonPath = public_path('states_lgas.json');
        if (file_exists($jsonPath)) {
            try {
                $statesLgas = json_decode(file_get_contents($jsonPath), true);
                if (is_array($statesLgas)) {
                    $states = array_column($statesLgas, 'state');
                    $lgas   = collect($statesLgas)->pluck('lgas', 'state')->toArray();
                }
            } catch (\Exception $e) {
                Log::warning('Could not load states_lgas.json: ' . $e->getMessage());
            }
        }

        $hasStateData = !empty($states);

        // Validation rules
        $rules = [
            'avatar'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'admissionMode'      => 'required|in:auto,manual',
            'admissionNo'        => 'required|string|max:255|unique:studentRegistration,admissionNo,' . $id,
            'admissionYear'      => 'required|integer|min:1900|max:' . date('Y'),
            'admissionDate'      => 'required|date|before_or_equal:today',
            'title'              => 'nullable|in:Master,Miss',
            'firstname'          => 'required|string|max:255',
            'lastname'           => 'required|string|max:255',
            'othername'          => 'nullable|string|max:255',
            'gender'             => 'required|in:Male,Female',
            'dateofbirth'        => 'required|date|before:today',
            'placeofbirth'       => 'required|string|max:255',
            'nationality'        => 'required|string|max:255',
            'age'                => 'required|integer|min:1|max:100',
            'blood_group'        => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'mother_tongue'      => 'nullable|string|max:255',
            'religion'           => 'required|in:Christianity,Islam,Others',
            'sport_house'        => 'nullable|string|max:255',
            'phone_number'       => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:255',
            'nin_number'         => 'nullable|string|max:20',
            'city'               => 'nullable|string|max:255',
            'state'              => 'required|string|max:255',
            'local'              => 'required|string|max:255',
            'future_ambition'    => 'required|string|max:500',
            'permanent_address'  => 'required|string|max:255',
            'student_category'   => 'required|in:Day,Boarding',
            'schoolclassid'      => 'required|exists:schoolclass,id',
            'schoolhouseid'      => 'nullable|exists:schoolhouses,id',
            'termid'             => 'required|exists:schoolterm,id',
            'sessionid'          => 'required|exists:schoolsession,id',
            'statusId'           => 'required|in:1,2',
            'student_status'     => 'required|in:Active,Inactive',
            'father_title'       => 'nullable|in:Mr,Dr,Prof',
            'mother_title'       => 'nullable|in:Mrs,Dr,Prof',
            'father_name'        => 'nullable|string|max:255',
            'mother_name'        => 'nullable|string|max:255',
            'father_occupation'  => 'nullable|string|max:255',
            'father_city'        => 'nullable|string|max:255',
            'office_address'     => 'nullable|string|max:255',
            'father_phone'       => 'nullable|string|max:20',
            'mother_phone'       => 'nullable|string|max:20',
            'parent_email'       => 'nullable|email|max:255',
            'parent_address'     => 'nullable|string|max:255',
            'last_school'        => 'nullable|string|max:255',
            'last_class'         => 'nullable|string|max:255',
            'reason_for_leaving' => 'nullable|string|max:500',
        ];

        if ($hasStateData) {
            $rules['state'] = [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($states) {
                    if (!in_array($value, $states)) {
                        $fail('The selected state is invalid.');
                    }
                },
            ];

            $rules['local'] = [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($request, $lgas) {
                    $selectedState = $request->input('state');
                    if (!isset($lgas[$selectedState]) || !in_array($value, $lgas[$selectedState])) {
                        $fail('The selected LGA is invalid for the chosen state.');
                    }
                },
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            Log::warning('Validation failed for student update', [
                'id'     => $id,
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        // 1. Core student record
        $student = Student::findOrFail($id);

        // Handle title field - if empty, set default based on gender
        $title = $request->title;
        if (empty($title)) {
            $title = $request->gender === 'Male' ? 'Master' : 'Miss';
            Log::info('Title was empty, set default: ' . $title . ' for student ID: ' . $id);
        }

        // Handle admission number
        if ($request->admissionMode === 'auto') {
            $admissionResponse = $this->getLastAdmissionNumber(new Request(['year' => $request->admissionYear]));
            $admissionData = json_decode($admissionResponse->getContent(), true);
            if ($admissionData['success']) {
                $student->admissionNo = $admissionData['admissionNo'];
            }
        } else {
            $student->admissionNo = $request->admissionNo;
        }

        $student->admission_date     = $request->admissionDate;
        $student->title              = $title;
        $student->admissionYear      = $request->admissionYear;
        $student->firstname          = $request->firstname;
        $student->lastname           = $request->lastname;
        $student->othername          = $request->othername;
        $student->gender             = $request->gender;
        $student->dateofbirth        = $request->dateofbirth;
        $student->age                = $request->age;
        $student->blood_group        = $request->blood_group;
        $student->mother_tongue      = $request->mother_tongue;
        $student->religion           = $request->religion;
        $student->sport_house        = $request->sport_house;
        $student->phone_number       = $request->phone_number;
        $student->email              = $request->email;
        $student->nin_number         = $request->nin_number;
        $student->city               = $request->city;
        $student->state              = $request->state;
        $student->local              = $request->local;
        $student->nationality        = $request->nationality;
        $student->placeofbirth       = $request->placeofbirth;
        $student->future_ambition    = $request->future_ambition;
        $student->home_address2      = $request->permanent_address;
        $student->student_category   = $request->student_category;
        $student->statusId           = $request->statusId;
        $student->student_status     = $request->student_status;
        $student->last_school        = $request->last_school;
        $student->last_class         = $request->last_class;
        $student->reason_for_leaving = $request->reason_for_leaving;
        $student->registeredBy       = auth()->user()->id;
        $student->save();

        // 2. Studentclass - Update or create
        $existingClass = Studentclass::where('studentId', $id)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->first();

        if ($existingClass) {
            $existingClass->update(['schoolclassid' => $request->schoolclassid]);
        } else {
            Studentclass::create([
                'studentId'     => $id,
                'schoolclassid' => $request->schoolclassid,
                'termid'        => $request->termid,
                'sessionid'     => $request->sessionid,
            ]);
        }

        // 3. PromotionStatus - Update or create
        PromotionStatus::updateOrCreate(
            [
                'studentId'     => $id,
                'schoolclassid' => $request->schoolclassid,
                'termid'        => $request->termid,
                'sessionid'     => $request->sessionid,
            ],
            [
                'promotionStatus' => 'PROMOTED',
                'classstatus'     => 'CURRENT',
            ]
        );

        // 4. Parent Registration - Update or create
        $parent = ParentRegistration::firstOrNew(['studentId' => $id]);
        $parent->father_title      = $request->father_title;
        $parent->mother_title      = $request->mother_title;
        $parent->father            = $request->father_name;
        $parent->mother            = $request->mother_name;
        $parent->father_phone      = $request->father_phone;
        $parent->mother_phone      = $request->mother_phone;
        $parent->father_occupation = $request->father_occupation;
        $parent->father_city       = $request->father_city;
        $parent->office_address    = $request->office_address;
        $parent->parent_email      = $request->parent_email;
        $parent->parent_address    = $request->parent_address;
        $parent->save();

        // 5. Student Picture - Update or create
        $picture = Studentpicture::firstOrNew(['studentid' => $id]);
        if ($request->hasFile('avatar')) {
            // Delete old image if exists
            if ($picture->picture && $picture->picture !== 'unnamed.jpg') {
                try {
                    Storage::delete('public/images/student_avatars/' . $picture->picture);
                } catch (\Exception $e) {
                    Log::warning('Could not delete old avatar: ' . $e->getMessage());
                }
            }
            $path = $this->storeImage($request->file('avatar'), 'images/student_avatars');
            $picture->picture = basename($path);
        }
        if (!$picture->picture) {
            $picture->picture = 'unnamed.jpg';
        }
        $picture->save();

        // 6. Student House - Update or create
        if ($request->filled('schoolhouseid')) {
            Studenthouse::updateOrCreate(
                [
                    'studentid' => $id,
                    'termid'    => $request->termid,
                    'sessionid' => $request->sessionid,
                ],
                ['schoolhouse' => $request->schoolhouseid]
            );
        }

        // 7. Personality Profile - Create if not exists
        Studentpersonalityprofile::firstOrCreate([
            'studentid'     => $id,
            'schoolclassid' => $request->schoolclassid,
            'termid'        => $request->termid,
            'sessionid'     => $request->sessionid,
        ]);

        // 8. StudentCurrentTerm - PROPERLY HANDLE UNIQUE CONSTRAINT
        $this->updateStudentCurrentTerm($id, $request->schoolclassid, $request->termid, $request->sessionid);

        DB::commit();

        // Clear cache for this student
        Cache::forget('student_' . $id);
        Cache::forget('student_terms_' . $id);

        return response()->json([
            'success'  => true,
            'message'  => 'Student updated successfully',
            'redirect' => route('student.index'),
            'student'  => [
                'id'                 => $student->id,
                'admissionNo'        => $student->admissionNo,
                'admissionYear'      => $student->admissionYear,
                'title'              => $student->title,
                'firstname'          => $student->firstname,
                'lastname'           => $student->lastname,
                'othername'          => $student->othername,
                'gender'             => $student->gender,
                'dateofbirth'        => $student->dateofbirth,
                'placeofbirth'       => $student->placeofbirth,
                'nationality'        => $student->nationality,
                'religion'           => $student->religion,
                'last_school'        => $student->last_school,
                'last_class'         => $student->last_class,
                'schoolclassid'      => $request->schoolclassid,
                'termid'             => $request->termid,
                'sessionid'          => $request->sessionid,
                'phone_number'       => $student->phone_number,
                'nin_number'         => $student->nin_number,
                'blood_group'        => $student->blood_group,
                'mother_tongue'      => $student->mother_tongue,
                'father_name'        => $parent->father ?? '',
                'father_phone'       => $parent->father_phone ?? '',
                'father_occupation'  => $parent->father_occupation ?? '',
                'mother_name'        => $parent->mother ?? '',
                'mother_phone'       => $parent->mother_phone ?? '',
                'parent_address'     => $parent->parent_address ?? '',
                'student_category'   => $student->student_category,
                'reason_for_leaving' => $student->reason_for_leaving,
                'picture'            => $picture->picture ?? 'unnamed.jpg',
                'state'              => $student->state,
                'local'              => $student->local,
                'statusId'           => $student->statusId,
                'student_status'     => $student->student_status,
                'future_ambition'    => $student->future_ambition,
                'permanent_address'  => $student->home_address2,
            ],
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
        Log::error("Student ID {$id} not found during update");
        return response()->json(['success' => false, 'message' => 'Student not found'], 404);

    } catch (\Illuminate\Database\QueryException $e) {
        DB::rollBack();
        Log::error("Database error updating student ID {$id}: {$e->getMessage()}");

        // Check if it's a duplicate entry error
        if ($e->errorInfo[1] == 1062) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate entry error. The student may already have a current term record. Please try again.',
            ], 409);
        }

        return response()->json([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
        ], 500);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error updating student ID {$id}: {$e->getMessage()}\n{$e->getTraceAsString()}");
        return response()->json([
            'success' => false,
            'message' => 'Failed to update student: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Helper method to update student current term while respecting unique constraint
 */
private function updateStudentCurrentTerm($studentId, $schoolclassId, $termId, $sessionId)
{
    try {
        // First, check if there's already a record for this specific term/session
        $existingTerm = StudentCurrentTerm::where('studentId', $studentId)
            ->where('termId', $termId)
            ->where('sessionId', $sessionId)
            ->first();

        if ($existingTerm) {
            // Before updating, set all other records to is_current = false
            StudentCurrentTerm::where('studentId', $studentId)
                ->where('id', '!=', $existingTerm->id)
                ->update(['is_current' => false]);

            // Now update the existing record
            $existingTerm->update([
                'schoolclassId' => $schoolclassId,
                'is_current'    => true
            ]);

            Log::info('Updated existing current term record', [
                'student_id' => $studentId,
                'record_id' => $existingTerm->id
            ]);
        } else {
            // Check if there's any record marked as current for this student
            $currentRecord = StudentCurrentTerm::where('studentId', $studentId)
                ->where('is_current', true)
                ->first();

            if ($currentRecord) {
                // Set the existing current record to false
                $currentRecord->update(['is_current' => false]);
                Log::info('Unset previous current term record', [
                    'student_id' => $studentId,
                    'old_record_id' => $currentRecord->id
                ]);
            }

            // Create the new record
            $newRecord = StudentCurrentTerm::create([
                'studentId'     => $studentId,
                'schoolclassId' => $schoolclassId,
                'termId'        => $termId,
                'sessionId'     => $sessionId,
                'is_current'    => true,
            ]);

            Log::info('Created new current term record', [
                'student_id' => $studentId,
                'record_id' => $newRecord->id
            ]);
        }

    } catch (\Illuminate\Database\QueryException $e) {
        // If we still get a duplicate error, do a more aggressive cleanup
        if ($e->errorInfo[1] == 1062) {
            Log::warning('Duplicate entry detected, performing aggressive cleanup for student: ' . $studentId);

            // Delete ALL current term records for this student
            StudentCurrentTerm::where('studentId', $studentId)->delete();

            // Create fresh record
            StudentCurrentTerm::create([
                'studentId'     => $studentId,
                'schoolclassId' => $schoolclassId,
                'termId'        => $termId,
                'sessionId'     => $sessionId,
                'is_current'    => true,
            ]);

            Log::info('Aggressive cleanup completed for student: ' . $studentId);
        } else {
            throw $e;
        }
    }
}


    protected function deleteImage($filename)
    {
        try {
            if ($filename && $filename !== 'unnamed.jpg'
                && Storage::exists('public/images/student_avatars/'.$filename)) {
                Storage::delete('public/images/student_avatars/'.$filename);
            }
        } catch (\Exception $e) {
            Log::error("Error deleting image: {$e->getMessage()}");
            throw $e;
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $student = Student::findOrFail($id);

            $picture = Studentpicture::where('studentid', $id)->first();
            if ($picture && $picture->picture) $this->deleteImage($picture->picture);

            $billPayments = StudentBillPayment::where('student_id', $id)->get();
            foreach ($billPayments as $bp) {
                StudentBillPaymentRecord::where('student_bill_payment_id', $bp->id)->delete();
                $bp->delete();
            }
            StudentBillPaymentBook::where('student_id', $id)->delete();
            StudentBillInvoice::where('student_id', $id)->delete();
            Studentclass::where('studentId', $id)->delete();
            PromotionStatus::where('studentId', $id)->delete();
            ParentRegistration::where('studentId', $id)->delete();
            Studentpicture::where('studentid', $id)->delete();

            $broadsheetRecords = BroadsheetRecord::where('student_id', $id)->get();
            foreach ($broadsheetRecords as $record) {
                Broadsheets::where('broadsheet_record_id', $record->id)->delete();
                $record->delete();
            }

            SubjectRegistrationStatus::where('studentId', $id)->delete();
            Studenthouse::where('studentid', $id)->delete();
            Studentpersonalityprofile::where('studentid', $id)->delete();
            StudentCurrentTerm::where('studentId', $id)->delete();

            $student->delete();

            DB::commit();

            return response()->json(['success'=>true,'message'=>'Student deleted successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting student: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to delete student: '.$e->getMessage()], 500);
        }
    }

    public function destroyMultiple(Request $request): JsonResponse
    {
        try {
            $ids = $request->validate(['ids'=>'required|array|exists:studentRegistration,id'])['ids'];
            DB::beginTransaction();

            foreach ($ids as $id) {
                $picture = Studentpicture::where('studentid', $id)->first();
                if ($picture && $picture->picture) $this->deleteImage($picture->picture);

                $billPayments = StudentBillPayment::where('student_id', $id)->get();
                foreach ($billPayments as $bp) {
                    StudentBillPaymentRecord::where('student_bill_payment_id', $bp->id)->delete();
                    $bp->delete();
                }
                StudentBillPaymentBook::where('student_id', $id)->delete();
                StudentBillInvoice::where('student_id', $id)->delete();
                Studentclass::where('studentId', $id)->delete();
                PromotionStatus::where('studentId', $id)->delete();
                ParentRegistration::where('studentId', $id)->delete();
                Studentpicture::where('studentid', $id)->delete();
                Broadsheet::where('studentId', $id)->delete();
                SubjectRegistrationStatus::where('studentId', $id)->delete();
                Studenthouse::where('studentid', $id)->delete();
                Studentpersonalityprofile::where('studentid', $id)->delete();
                StudentCurrentTerm::where('studentId', $id)->delete();
            }

            Student::whereIn('id', $ids)->delete();

            DB::commit();

            return response()->json(['success'=>true,'message'=>'Students deleted successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Bulk delete error: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to delete students: '.$e->getMessage()], 500);
        }
    }

    public function deletestudent(Request $request)
    {
        return $this->destroy($request->input('id'));
    }

    public function deletestudentbatch(Request $request): JsonResponse
    {
        $batchId = $request->input('studentbatchid');
        try {
            if (!Schema::hasTable('student_batch_upload')) throw new \Exception('student_batch_upload table does not exist');
            if (!Schema::hasColumn('studentRegistration','batchid')) throw new \Exception('batchid column missing');

            $batch = StudentBatchModel::findOrFail($batchId);
            DB::beginTransaction();

            $studentIds = Student::where('batchid', $batch->id)->pluck('id');
            foreach ($studentIds as $studentId) {
                $picture = Studentpicture::where('studentid', $studentId)->first();
                if ($picture && $picture->picture) $this->deleteImage($picture->picture);

                $billPayments = StudentBillPayment::where('student_id', $studentId)->get();
                foreach ($billPayments as $bp) {
                    StudentBillPaymentRecord::where('student_bill_payment_id', $bp->id)->delete();
                    $bp->delete();
                }
                StudentBillPaymentBook::where('student_id', $studentId)->delete();
                StudentBillInvoice::where('student_id', $studentId)->delete();

                $bsRecords = BroadsheetRecord::where('student_id', $studentId)->get();
                foreach ($bsRecords as $r) { Broadsheets::where('broadsheet_record_id', $r->id)->delete(); $r->delete(); }

                $bsMockRecords = BroadsheetRecordMock::where('student_id', $studentId)->get();
                foreach ($bsMockRecords as $r) { BroadsheetsMock::where('broadsheet_records_mock_id', $r->id)->delete(); $r->delete(); }

                Studentclass::where('studentId', $studentId)->delete();
                PromotionStatus::where('studentId', $studentId)->delete();
                ParentRegistration::where('studentId', $studentId)->delete();
                Studentpicture::where('studentid', $studentId)->delete();
                SubjectRegistrationStatus::where('studentId', $studentId)->delete();
                Studenthouse::where('studentid', $studentId)->delete();
                Studentpersonalityprofile::where('studentid', $studentId)->delete();
                StudentCurrentTerm::where('studentId', $studentId)->delete();
            }

            Student::where('batchid', $batch->id)->delete();
            $batch->delete();

            DB::commit();

            return response()->json(['success'=>true,'message'=>'Batch Upload has been removed'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Batch not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting batch ID {$batchId}: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to delete batch: '.$e->getMessage()], 500);
        }
    }

    public function bulkupload()
    {
        $pagetitle     = "Bulk Upload Students";
        $schoolclasses = Schoolclass::leftJoin('schoolarm','schoolarm.id','=','schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass,' - ',schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')->get();
        $schoolterms   = Schoolterm::select('id','term as name')->get();
        $schoolsessions= Schoolsession::select('id','session as name')->get();

        return view('student.bulkupload', compact('schoolclasses','schoolterms','schoolsessions','pagetitle'));
    }

    public function batchindex()
    {
        $pagetitle = "Student Batch Management";
        $batch = StudentBatchModel::leftJoin('schoolclass',   'schoolclass.id',   '=','student_batch_upload.schoolclassid')
            ->leftJoin('schoolsession','schoolsession.id','=','student_batch_upload.session')
            ->leftJoin('schoolterm',   'schoolterm.id',   '=','student_batch_upload.termid')
            ->leftJoin('schoolarm',    'schoolarm.id',    '=','schoolclass.arm')
            ->orderBy('upload_date','desc')
            ->get([
                'student_batch_upload.id as id',
                'student_batch_upload.title as title',
                'schoolclass.schoolclass as schoolclass',
                'schoolterm.term as term',
                'schoolsession.session as session',
                'schoolarm.arm as arm',
                'student_batch_upload.status as status',
                'student_batch_upload.updated_at as upload_date',
            ]);

        $schoolclasses = Schoolclass::leftJoin('schoolarm','schoolarm.id','=','schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass,' - ',schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')->get();
        $schoolterms   = Schoolterm::select('id','term as name')->get();
        $schoolsessions= Schoolsession::select('id','session as name')->get();

        return view('student.batchindex', compact('batch','schoolclasses','schoolterms','schoolsessions','pagetitle'));
    }

    public function bulkuploadsave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filesheet'    => 'required|mimes:xlsx,csv,xls',
            'title'        => 'required',
            'termid'       => 'required|exists:schoolterm,id',
            'sessionid'    => 'required|exists:schoolsession,id',
            'schoolclassid'=> 'required|exists:schoolclass,id',
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();

        if (StudentBatchModel::where('title', $request->title)->exists()) {
            return redirect()->back()->with('success', 'Title already used. Please choose another.');
        }

        try {
            DB::beginTransaction();

            $batch = StudentBatchModel::create([
                'title'        => $request->title,
                'schoolclassid'=> $request->schoolclassid,
                'termid'       => $request->termid,
                'session'      => $request->sessionid,
                'status'       => '',
            ]);

            session(['sclassid'=>$request->schoolclassid,'tid'=>$request->termid,'sid'=>$request->sessionid,'batchid'=>$batch->id]);

            (new StudentsImport())->import($request->file('filesheet'), null, \Maatwebsite\Excel\Excel::XLSX);
            $batch->update(['status'=>'Success']);

            DB::commit();

            return redirect()->back()->with('success', 'Student Batch File Imported Successfully');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();
            $batch->update(['status'=>'Failed']);
            $errors = collect($e->failures())->map(fn($f) => "Row {$f->row()}: ".implode(', ',$f->errors()))->implode('; ');
            return redirect()->back()->with('status', $errors);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error importing batch: {$e->getMessage()}");
            return redirect()->back()->with('status', 'Failed to import batch: '.$e->getMessage());
        }
    }

    public function getLastAdmissionNumber(Request $request)
    {
        try {
            $year = $request->query('year', date('Y'));
            if (!preg_match('/^\d{4}$/', $year)) {
                return response()->json(['success'=>false,'message'=>'Invalid year format'], 400);
            }

            $lastStudent = Student::where('admissionNo', 'LIKE', "TCC/{$year}/%")->orderBy('id','desc')->first();
            $lastNumber  = 870;

            if ($lastStudent && $lastStudent->admissionNo) {
                $parts = explode('/', $lastStudent->admissionNo);
                if (count($parts) === 3 && is_numeric($parts[2])) {
                    $lastNumber = max(870, (int)$parts[2]);
                }
            }

            return response()->json(['success'=>true,'admissionNo'=>sprintf('TCC/%s/%04d',$year,$lastNumber+1)], 200);

        } catch (\Exception $e) {
            Log::error("Error generating admission number: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to generate admission number'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Report generation and all remaining methods below are UNCHANGED from
    // the original — copy them in verbatim from your existing controller.
    // Only store(), update(), edit(), and getStudentsOptimized() changed above.
    // -------------------------------------------------------------------------

    public function generateReport(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        Log::info('=== GENERATE REPORT STARTED ===');

        $reportId      = null;
        $currentTerms  = null;
        $reportStudents= null;

        try {
            $request->validate([
                'class_id'               => 'nullable|exists:schoolclass,id',
                'term_id'                => 'nullable|exists:schoolterm,id',
                'session_id'             => 'nullable|exists:schoolsession,id',
                'status'                 => 'nullable|in:1,2,Active,Inactive',
                'columns'                => 'required|string',
                'columns_order'          => 'nullable|string',
                'format'                 => 'required|in:pdf,excel',
                'orientation'            => 'nullable|in:portrait,landscape',
                'include_header'         => 'nullable|boolean',
                'include_logo'           => 'nullable|boolean',
                'exclude_photos'         => 'nullable|boolean',
                'template'               => 'nullable|in:default,detailed,simple',
                'confidential'           => 'nullable|boolean',
                'preview'                => 'nullable|boolean',
                'optimize_large_reports' => 'nullable|boolean',
            ]);

            $user = auth()->user();
            if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized.'], 401);
            if (!$user->hasAnyRole(['Staff','Admin','Super Admin'])) {
                return response()->json(['success'=>false,'message'=>'Access denied.'], 403);
            }

            $columns     = array_filter(explode(',', $request->columns));
            $columnOrder = [];
            if ($request->filled('columns_order')) {
                $columnOrder = array_filter(explode(',', $request->columns_order));
                $columns     = array_values(array_intersect($columnOrder, $columns));
            }

            $template = $request->input('template', 'default');
            if ($template === 'detailed') {
                $columns = array_unique(array_merge($columns, ['photo','admissionNo','firstname','lastname','othername','gender','dateofbirth','age','class','status']));
            } elseif ($template === 'simple') {
                $columns = array_values(array_intersect($columns, ['photo','admissionNo','firstname','lastname','class','status']));
            }

            if ($request->boolean('exclude_photos')) {
                $columns = array_filter($columns, fn($c) => $c !== 'photo');
            }

            if (empty($columns)) return response()->json(['success'=>false,'message'=>'No columns selected'], 422);

            $termName      = 'All Terms';
            $sessionName   = 'All Sessions';
            $selectedTerm  = null;
            $selectedSession = null;

            if ($request->filled('term_id')) {
                $selectedTerm = Schoolterm::find($request->term_id);
                $termName     = $selectedTerm ? $selectedTerm->term : 'Unknown Term';
            }
            if ($request->filled('session_id')) {
                $selectedSession = Schoolsession::find($request->session_id);
                $sessionName     = $selectedSession ? $selectedSession->session : 'Unknown Session';
            }

            $query = StudentCurrentTerm::query()
                ->with(['student.picture','student.parent','schoolClass.armRelation','term','session'])
                ->select('student_current_term.*');

            if ($request->filled('class_id'))   $query->where('schoolclassId', $request->class_id);
            if ($request->filled('term_id'))     $query->where('termId',        $request->term_id);
            if ($request->filled('session_id'))  $query->where('sessionId',     $request->session_id);

            if ($request->filled('status')) {
                $query->whereHas('student', function ($q) use ($request) {
                    if (in_array($request->status, ['1','2'])) {
                        $q->where('statusId', $request->status);
                    } else {
                        $q->where('student_status', $request->status);
                    }
                });
            }

            $currentTerms = $query->get();

            if ($currentTerms->isEmpty()) {
                return response()->json(['success'=>false,'message'=>'No students found in the selected term and session.'], 404);
            }

            $isLargeReport  = $currentTerms->count() > 100;
            $optimizeLarge  = $request->boolean('optimize_large_reports', true);

            if ($isLargeReport && $optimizeLarge && !$request->boolean('exclude_photos')) {
                $columns = array_filter($columns, fn($c) => $c !== 'photo');
            }

            $reportId = uniqid('report_');
            Cache::put($reportId, ['status'=>'processing','progress'=>0,'total'=>$currentTerms->count(),'message'=>'Processing...'], now()->addMinutes(10));

            $reportStudents = $currentTerms->map(function ($currentTerm) use ($isLargeReport) {
                $student = $currentTerm->student;
                $picture = $student->picture;
                $parent  = $student->parent;

                $photoBase64 = null;
                $hasPhoto    = false;

                if ($picture && $picture->picture && $picture->picture !== 'unnamed.jpg') {
                    $hasPhoto = true;
                    if (!$isLargeReport) $photoBase64 = $this->getOptimizedImageForPDF($picture->picture);
                }

                $currentClass   = $currentTerm->schoolClass->schoolclass ?? null;
                $currentArm     = $currentTerm->schoolClass->armRelation->arm ?? null;
                $currentTermName= $currentTerm->term->term ?? null;
                $currentSession = $currentTerm->session->session ?? null;

                return (object) [
                    'id'                  => $student->id,
                    'admissionNo'         => $student->admissionNo,
                    'admissionYear'       => $student->admissionYear,
                    'admission_date'      => $student->admission_date,
                    'title'               => $student->title,
                    'firstname'           => $student->firstname,
                    'lastname'            => $student->lastname,
                    'othername'           => $student->othername,
                    'gender'              => $student->gender,
                    'dateofbirth'         => $student->dateofbirth,
                    'age'                 => $student->age,
                    'blood_group'         => $student->blood_group,
                    'mother_tongue'       => $student->mother_tongue,
                    'religion'            => $student->religion,
                    'phone_number'        => $student->phone_number,
                    'email'               => $student->email,
                    'nin_number'          => $student->nin_number,
                    'city'                => $student->city,
                    'state'               => $student->state,
                    'local'               => $student->local,
                    'nationality'         => $student->nationality,
                    'placeofbirth'        => $student->placeofbirth,
                    'future_ambition'     => $student->future_ambition,
                    'permanent_address'   => $student->home_address2,
                    'student_category'    => $student->student_category,
                    'statusId'            => $student->statusId,
                    'student_status'      => $student->student_status,
                    'last_school'         => $student->last_school,
                    'last_class'          => $student->last_class,
                    'reason_for_leaving'  => $student->reason_for_leaving,
                    'created_at'          => $student->created_at,
                    'current_term_id'     => $currentTerm->termId,
                    'current_session_id'  => $currentTerm->sessionId,
                    'current_class_id'    => $currentTerm->schoolclassId,
                    'is_current'          => $currentTerm->is_current,
                    'current_class_name'  => $currentClass,
                    'current_arm'         => $currentArm,
                    'current_term_name'   => $currentTermName,
                    'current_session_name'=> $currentSession,
                    'schoolclass'         => $currentClass,
                    'arm_name'            => $currentArm,
                    'termid'              => $currentTerm->termId,
                    'sessionid'           => $currentTerm->sessionId,
                    'picture'             => $picture ? $picture->picture : null,
                    'picture_base64'      => $photoBase64,
                    'has_photo'           => $hasPhoto,
                    'photo_initials'      => substr($student->firstname??'',0,1).substr($student->lastname??'',0,1),
                    'father_name'         => $parent ? $parent->father : null,
                    'mother_name'         => $parent ? $parent->mother : null,
                    'father_phone'        => $parent ? $parent->father_phone : null,
                    'mother_phone'        => $parent ? $parent->mother_phone : null,
                    'parent_email'        => $parent ? $parent->parent_email : null,
                    'parent_address'      => $parent ? $parent->parent_address : null,
                    'father_occupation'   => $parent ? $parent->father_occupation : null,
                    'father_city'         => $parent ? $parent->father_city : null,
                ];
            });

            Cache::put($reportId, ['status'=>'complete','progress'=>$currentTerms->count(),'total'=>$currentTerms->count(),'message'=>'Complete'], now()->addMinutes(10));

            $className = 'All Classes';
            if ($request->filled('class_id')) {
                $class     = Schoolclass::with('armRelation')->find($request->class_id);
                $className = $class ? $class->schoolclass.($class->armRelation ? ' - '.$class->armRelation->arm : '') : 'All Classes';
            }

            $format      = $request->input('format');
            $orientation = $request->query('orientation','portrait');
            $schoolInfo  = SchoolInformation::where('is_active', true)->first();

            $data = [
                'students'        => $reportStudents,
                'columns'         => $columns,
                'title'           => $request->boolean('confidential') ? 'CONFIDENTIAL - Student Master List Report' : 'Student Master List Report',
                'className'       => $className,
                'termName'        => $termName,
                'sessionName'     => $sessionName,
                'generated'       => now()->format('d M Y h:i A'),
                'generated_by'    => $user->name,
                'total'           => $reportStudents->count(),
                'males'           => $reportStudents->where('gender','Male')->count(),
                'females'         => $reportStudents->where('gender','Female')->count(),
                'orientation'     => $orientation,
                'include_header'  => $request->boolean('include_header', true),
                'include_logo'    => $request->boolean('include_logo', true),
                'school_info'     => $schoolInfo,
                'school_logo_base64' => null,
                'selected_term'   => $selectedTerm,
                'selected_session'=> $selectedSession,
                'template'        => $template,
                'confidential'    => $request->boolean('confidential'),
                'report_id'       => $reportId,
                'is_large_report' => $isLargeReport,
                'warning'         => $isLargeReport ? 'Large report detected. Photos may be excluded for performance.' : null,
            ];

            if ($data['include_logo'] && $schoolInfo && $format === 'pdf') {
                $data['school_logo_base64'] = $this->getSchoolLogoBase64($schoolInfo);
            }

            $filename = 'student-report-'.now()->format('Y-m-d-His').($request->boolean('confidential') ? '-CONFIDENTIAL' : '');

            if ($request->boolean('preview')) {
                $data['students'] = $reportStudents->take(5);
                $data['is_preview'] = true;
                $data['warning']  = 'PREVIEW - Showing first 5 records only';
                return Pdf::loadView('student.reports.student_report_pdf', $data)
                    ->setPaper('A4', $orientation)
                    ->setOptions(['isRemoteEnabled'=>true,'isHtml5ParserEnabled'=>true,'defaultFont'=>'DejaVu Sans'])
                    ->stream('preview-report.pdf');
            }

            if ($format === 'excel') {
                if (!class_exists('App\Exports\StudentReportExport')) throw new \Exception('StudentReportExport class not found.');
                return Excel::download(new \App\Exports\StudentReportExport($data), $filename.'.xlsx');
            }

            $view = 'student.reports.student_report_pdf';
            if ($template === 'detailed' && view()->exists('student.reports.detailed_report_pdf')) $view = 'student.reports.detailed_report_pdf';
            if ($template === 'simple'   && view()->exists('student.reports.simple_report_pdf'))   $view = 'student.reports.simple_report_pdf';

            return Pdf::loadView($view, $data)
                ->setPaper('A4', $orientation)
                ->setOptions(['isRemoteEnabled'=>true,'isHtml5ParserEnabled'=>true,'defaultFont'=>'DejaVu Sans'])
                ->download($filename.'.pdf');

        } catch (\Exception $e) {
            Log::error('Error generating report: '.$e->getMessage()."\n".$e->getTraceAsString());

            if (isset($reportId)) {
                try {
                    Cache::put($reportId, ['status'=>'failed','progress'=>0,'total'=>0,'message'=>$e->getMessage()], now()->addMinutes(10));
                } catch (\Exception $ce) {}
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success'=>false,'message'=>'Server error: '.$e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to generate report: '.$e->getMessage());
        }
    }

    public function getReportProgress(Request $request)
    {
        $request->validate(['report_id'=>'required|string']);
        return response()->json(['success'=>true,'progress'=>Cache::get($request->report_id, ['status'=>'unknown','progress'=>0,'total'=>0,'message'=>'Not found'])]);
    }

    private function getOptimizedImageForPDF($imagePath, $maxWidth = 100)
    {
        if (!$imagePath) return null;
        $cacheKey = 'optimized_image_'.md5($imagePath.'_'.$maxWidth);
        if (Cache::has($cacheKey)) return Cache::get($cacheKey);

        $possiblePaths = [
            storage_path('app/public/images/student_avatars/'.$imagePath),
            public_path('storage/images/student_avatars/'.$imagePath),
            storage_path('app/public/student_avatars/'.$imagePath),
            public_path('storage/student_avatars/'.$imagePath),
        ];
        $foundPath = null;
        foreach ($possiblePaths as $p) { if (file_exists($p)) { $foundPath = $p; break; } }
        if (!$foundPath) return null;

        try {
            $imageData = base64_encode(file_get_contents($foundPath));
            $mimeType  = mime_content_type($foundPath);
            $result    = 'data:'.$mimeType.';base64,'.$imageData;
            Cache::put($cacheKey, $result, now()->addHours(24));
            return $result;
        } catch (\Exception $e) {
            Log::warning('Failed to encode image at '.$foundPath.': '.$e->getMessage());
            return null;
        }
    }

    private function getSchoolLogoBase64($schoolInfo)
    {
        if (!$schoolInfo || !$schoolInfo->school_logo) return null;
        $cacheKey = 'school_logo_'.md5($schoolInfo->school_logo);
        if (Cache::has($cacheKey)) return Cache::get($cacheKey);

        $possiblePaths = [
            storage_path('app/public/'.$schoolInfo->school_logo),
            public_path('storage/'.$schoolInfo->school_logo),
        ];
        foreach ($possiblePaths as $p) {
            if (file_exists($p)) {
                try {
                    $result = 'data:'.mime_content_type($p).';base64,'.base64_encode(file_get_contents($p));
                    Cache::put($cacheKey, $result, now()->addHours(24));
                    return $result;
                } catch (\Exception $e) {}
            }
        }
        return null;
    }

    public function getCurrentTerm($studentId)
    {
        try {
            $currentTerm = StudentCurrentTerm::getCurrentForStudent($studentId);
            if (!$currentTerm) return response()->json(['success'=>false,'message'=>'No current term found'], 404);
            return response()->json(['success'=>true,'data'=>$currentTerm]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getActiveTerm($studentId)
    {
        try {
            $activeTerm    = Schoolterm::where('status', true)->first();
            $activeSession = Schoolsession::where('status', 'Current')->first();

            if (!$activeTerm || !$activeSession) return response()->json(['success'=>false,'message'=>'No active term/session found'], 404);

            $activeTermRecord = StudentCurrentTerm::with(['schoolClass.armRelation','term','session'])
                ->where('studentId', $studentId)
                ->where('termId',    $activeTerm->id)
                ->where('sessionId', $activeSession->id)
                ->first();

            if (!$activeTermRecord) return response()->json(['success'=>false,'message'=>'Student not registered in current active term'], 404);

            return response()->json(['success'=>true,'data'=>[
                'id'            => $activeTermRecord->id,
                'studentId'     => $activeTermRecord->studentId,
                'schoolclassId' => $activeTermRecord->schoolclassId,
                'termId'        => $activeTermRecord->termId,
                'sessionId'     => $activeTermRecord->sessionId,
                'is_current'    => $activeTermRecord->is_current,
                'schoolClass'   => $activeTermRecord->schoolClass ? ['id'=>$activeTermRecord->schoolClass->id,'schoolclass'=>$activeTermRecord->schoolClass->schoolclass,'armRelation'=>$activeTermRecord->schoolClass->armRelation ? ['id'=>$activeTermRecord->schoolClass->armRelation->id,'arm'=>$activeTermRecord->schoolClass->armRelation->arm] : null] : null,
                'term'          => $activeTermRecord->term    ? ['id'=>$activeTermRecord->term->id,   'term'=>$activeTermRecord->term->term,       'status'=>$activeTermRecord->term->status]    : null,
                'session'       => $activeTermRecord->session ? ['id'=>$activeTermRecord->session->id,'session'=>$activeTermRecord->session->session,'status'=>$activeTermRecord->session->status] : null,
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getCurrentInfo($id)
    {
        try {
            $student = Student::with(['currentTerm.schoolClass.armRelation','currentTerm.term','currentTerm.session'])->findOrFail($id);
            if (!$student->currentTerm) return response()->json(['success'=>false,'message'=>'No current term assigned'], 404);

            $ct = $student->currentTerm;
            return response()->json(['success'=>true,'data'=>[
                'student_id'       => $student->id,
                'admission_no'     => $student->admissionNo,
                'name'             => $student->firstname.' '.$student->lastname,
                'current_class_id' => $ct->schoolclassId,
                'current_class'    => $ct->schoolClass ? $ct->schoolClass->schoolclass : 'N/A',
                'current_class_arm'=> $ct->schoolClass && $ct->schoolClass->armRelation ? $ct->schoolClass->armRelation->arm : 'N/A',
                'current_term_id'  => $ct->termId,
                'current_term'     => $ct->term    ? $ct->term->term       : 'N/A',
                'current_session_id'=> $ct->sessionId,
                'current_session'  => $ct->session ? $ct->session->session : 'N/A',
                'is_current'       => $ct->is_current,
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getAllRegisteredTerms($id)
    {
        try {
            $terms = StudentCurrentTerm::where('studentId', $id)
                ->with(['schoolClass.armRelation','term','session'])
                ->orderBy('sessionId','desc')->orderBy('termId','desc')
                ->get()->map(fn($t) => [
                    'id'           => $t->id,
                    'term_id'      => $t->termId,
                    'term_name'    => $t->term    ? $t->term->term       : 'N/A',
                    'session_id'   => $t->sessionId,
                    'session_name' => $t->session ? $t->session->session : 'N/A',
                    'class_id'     => $t->schoolclassId,
                    'class_name'   => $t->schoolClass ? $t->schoolClass->schoolclass : 'N/A',
                    'arm_name'     => $t->schoolClass && $t->schoolClass->armRelation ? $t->schoolClass->armRelation->arm : 'N/A',
                    'is_current'   => $t->is_current,
                    'created_at'   => $t->created_at,
                    'updated_at'   => $t->updated_at,
                ]);

            return response()->json(['success'=>true,'data'=>$terms]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getStudentsByCurrentFilters(Request $request)
    {
        $request->validate(['classId'=>'nullable|exists:schoolclass,id','termId'=>'nullable|exists:schoolterm,id','sessionId'=>'nullable|exists:schoolsession,id']);
        try {
            $query = StudentCurrentTerm::with(['student','schoolClass','term','session'])->where('is_current', true);
            if ($request->filled('classId'))   $query->where('schoolclassId', $request->classId);
            if ($request->filled('termId'))    $query->where('termId',        $request->termId);
            if ($request->filled('sessionId')) $query->where('sessionId',     $request->sessionId);
            return response()->json(['success'=>true,'data'=>$query->get()]);
        } catch (\Exception $e) {
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
}
