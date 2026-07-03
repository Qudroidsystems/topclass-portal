<?php

namespace App\Http\Controllers;

use App\Exports\MarksSheetExport;
use App\Exports\MockMarksSheetExport;
use App\Exports\MockRecordsheetExport;
use App\Exports\RecordsheetExport;
use App\Imports\ScoresheetImport;
use App\Models\BroadsheetRecordMock;
use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\PromotionStatus;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


class MyScoreSheetController extends Controller
{
    public function index(Request $request)
    {
        $pagetitle = 'My Scoresheets';
        $broadsheets = collect();

        Log::info('Index session:', $request->session()->all());

        if (!$request->ajax()) {
            $termId = $request->query('termid', 'ALL');
            $sessionId = $request->query('sessionid', 'ALL');

            if ($termId !== 'ALL' && $sessionId !== 'ALL') {
                $broadsheets = $this->getBroadsheets($request->user()->id, $termId, $sessionId);
                Log::info('Index broadsheets count:', ['count' => $broadsheets->count()]);
            }
        }

        if ($request->ajax()) {
            $termId = $request->input('termid', 'ALL');
            $sessionId = $request->input('sessionid', 'ALL');

            if ($termId === 'ALL' || $sessionId === 'ALL') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select both term and session.',
                ], 422);
            }

            $broadsheets = $this->getBroadsheets($request->user()->id, $termId, $sessionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'broadsheets' => $broadsheets,
                ],
            ]);
        }

        
        return view('subjectscoresheet.index', compact('pagetitle', 'broadsheets'));
    }


   
    public function subjectscoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        Log::info('Subjectscoresheet parameters:', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid'));

        session([
            'schoolclass_id' => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
        ]);

        // Initial broadsheets fetch to check data
        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

        if ($broadsheets->isNotEmpty()) {
            // Update metrics and positions
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

            // Refresh broadsheets to ensure updated positions
            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

            Log::info('Broadsheets after position update:', $broadsheets->map(function ($b) {
                return [
                    'id' => $b->id,
                    'student_id' => $b->student_id,
                    'admissionno' => $b->admissionno,
                    'cum' => $b->cum,
                    'subject_position_class' => $b->position,
                ];
            })->toArray());

            $pagetitle = sprintf(
                'Scoresheet for %s (%s) - %s %s - %s %s',
                $broadsheets->first()->subject,
                $broadsheets->first()->subject_code,
                $broadsheets->first()->schoolclass,
                $broadsheets->first()->arm,
                $broadsheets->first()->term,
                $broadsheets->first()->session
            );
        } else {
            $pagetitle = 'Subject Scoresheet';
            Log::warning('No broadsheets found for the given parameters', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid'));
        }

        $schoolclass = Schoolclass::with('classcategory')->find($schoolclassid);
        $is_senior = $schoolclass && $schoolclass->classcategory ? $schoolclass->classcategory->is_senior : false;

        return view('subjectscoresheet.index', compact('broadsheets', 'pagetitle', 'is_senior'));
    }


    protected function getBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = Broadsheets::query()
            ->where('broadsheets.staff_id', $staffId)
            ->where('broadsheets.term_id', $termId)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subjectclass', function ($join) use ($subjectClassId) {
                $join->on('subjectclass.id', '=', 'broadsheets.subjectclass_id')
                    ->on('broadsheet_records.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records.schoolclass_id', '=', 'subjectclass.schoolclassid');
                if ($subjectClassId) {
                    $join->where('subjectclass.id', $subjectClassId);
                }
            })
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheet_records.session_id', $sessionId);

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        // Log the raw SQL query for debugging
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        Log::debug('getBroadsheets: Raw SQL query', [
            'sql' => $sql,
            'bindings' => $bindings,
        ]);

        $results = $query->get([
            'broadsheets.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records.subject_id',
            'schoolclass.schoolclass',
            'schoolclass.id as schoolclass_id',
            'schoolarm.arm',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheets.staff_id',
            'broadsheets.term_id',
            'broadsheet_records.session_id as sessionid',
            'classcategories.ca1score as ca1score',
            'classcategories.ca2score as ca2score',
            'classcategories.ca3score as ca3score',
            'classcategories.examscore as examscore',
            'studentpicture.picture',
            'broadsheets.ca1',
            'broadsheets.ca2',
            'broadsheets.ca3',
            'broadsheets.exam',
            'broadsheets.total',
            'broadsheets.bf',
            'broadsheets.cum',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position',
            'broadsheets.remark',
            'broadsheets.vettedstatus', // Added vettedstatus
        ])->sortBy('lastname');

        Log::debug('getBroadsheets: Retrieved broadsheets', [
            'staff_id' => $staffId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'schoolclass_id' => $schoolClassId,
            'subjectclass_id' => $subjectClassId,
            'result_count' => $results->count(),
            'students' => $results->map(function ($item) {
                return [
                    'admissionno' => $item->admissionno,
                    'student_id' => $item->student_id,
                    'subject' => $item->subject,
                    'subject_id' => $item->subject_id,
                    'subjectclass_id' => $item->subjectclid,
                    'position' => $item->position,
                    'vettedstatus' => $item->vettedstatus, // Added to log
                ];
            })->toArray(),
            'subjects' => $results->pluck('subject')->unique()->values()->toArray(),
        ]);

        foreach ($results as $broadsheet) {
            $ca1 = $broadsheet->ca1 ?? 0;
            $ca2 = $broadsheet->ca2 ?? 0;
            $ca3 = $broadsheet->ca3 ?? 0;
            $exam = $broadsheet->exam ?? 0;
            $caAverage = ($ca1 + $ca2 + $ca3) / 3;
            $newTotal = round(($caAverage + $exam) / 2, 1);

            $newBf = $this->getPreviousTermCum(
                $broadsheet->student_id,
                $broadsheet->subject_id,
                $termId,
                $sessionId
            );

            $newCum = $termId == 1 ? $newTotal : round(($newBf + $newTotal) / 2, 2);

            $schoolclass = Schoolclass::with('classcategory')->find($broadsheet->schoolclass_id);
            $newGrade = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($newCum)
                : $this->getDefaultGrade($newCum);

            $newRemark = $this->getRemark($newGrade);

            $significantChange = abs($broadsheet->bf - $newBf) > 0.01 ||
                                abs($broadsheet->total - $newTotal) > 0.01 ||
                                abs($broadsheet->cum - $newCum) > 0.01 ||
                                $broadsheet->grade !== $newGrade ||
                                $broadsheet->remark !== $newRemark;

            if ($significantChange) {
                Log::info("getBroadsheets: Updating broadsheet {$broadsheet->id} due to significant changes", [
                    'schoolclass_id' => $broadsheet->schoolclass_id,
                    'subjectclass_id' => $subjectClassId,
                    'student_id' => $broadsheet->student_id,
                    'admissionno' => $broadsheet->admissionno,
                    'subject_id' => $broadsheet->subject_id,
                    'subject' => $broadsheet->subject,
                    'old_values' => [
                        'bf' => $broadsheet->bf,
                        'total' => $broadsheet->total,
                        'cum' => $broadsheet->cum,
                        'grade' => $broadsheet->grade,
                        'remark' => $broadsheet->remark,
                        'position' => $broadsheet->position,
                        'vettedstatus' => $broadsheet->vettedstatus,
                    ],
                    'new_values' => [
                        'bf' => $newBf,
                        'total' => $newTotal,
                        'cum' => $newCum,
                        'grade' => $newGrade,
                        'remark' => $newRemark,
                        'position' => $broadsheet->position,
                        'vettedstatus' => $broadsheet->vettedstatus,
                    ],
                ]);

                $broadsheet->bf = $newBf;
                $broadsheet->total = $newTotal;
                $broadsheet->cum = $newCum;
                $broadsheet->grade = $newGrade;
                $broadsheet->remark = $newRemark;
                $broadsheet->save();
            }
        }

        return $results;
    }

  

    public function results()
    {
        try {
            $subjectclass_id = session('subjectclass_id');
            $schoolclass_id = session('schoolclass_id');
            $term_id = session('term_id');
            $session_id = session('session_id');

            if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required session data',
                    'scores' => [],
                ], 400);
            }

            $broadsheets = Broadsheets::where([
                'subjectclass_id' => $subjectclass_id,
                'term_id' => $term_id,
            ])
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->where('broadsheet_records.session_id', $session_id)
                ->get([
                    'broadsheets.id',
                    'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname',
                    'broadsheets.ca1',
                    'broadsheets.ca2',
                    'broadsheets.ca3',
                    'broadsheets.exam',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.grade',
                    'broadsheets.subject_position_class as position',
                    'broadsheets.term_id',
                ]);

            return response()->json([
                'success' => true,
                'scores' => $broadsheets->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in results endpoint: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        // Fetch the subjectclass to get the subject_id
        $subjectClass = DB::table('subjectclass')
            ->where('id', $subjectclassid)
            ->first(['subjectteacherid']);

        if (!$subjectClass) {
            Log::warning('Subjectclass not found', ['subjectclass_id' => $subjectclassid]);
            return;
        }

        $subjectTeacher = DB::table('subjectteacher')
            ->where('id', $subjectClass->subjectteacherid)
            ->first(['subjectid']);

        if (!$subjectTeacher) {
            Log::warning('Subjectteacher not found', ['subjectteacherid' => $subjectClass->subjectteacherid]);
            return;
        }

        $subjectId = $subjectTeacher->subjectid;

        // Calculate class metrics (min, max, avg) for the subject across all students
        $metrics = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassid)
            ->where('broadsheets.staff_id', $staffid)
            ->where('broadsheets.term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->select([
                DB::raw('MIN(broadsheets.cum) as class_min'),
                DB::raw('MAX(broadsheets.cum) as class_max'),
                DB::raw('SUM(broadsheets.cum) as cum_sum'),
                DB::raw('COUNT(broadsheets.id) as student_count')
            ])
            ->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round($metrics->cum_sum / $metrics->student_count, 1) : 0;

        Log::info('Calculated class metrics', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subject_id' => $subjectId,
            'class_min' => $classMin,
            'class_max' => $classMax,
            'class_avg' => $classAvg,
            'student_count' => $metrics->student_count,
            'cum_sum' => $metrics->cum_sum,
        ]);

        // Update all relevant broadsheet records with the calculated metrics
        Broadsheets::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->update([
                'cmin' => $classMin,
                'cmax' => $classMax,
                'avg' => $classAvg,
            ]);

        Log::info('Updated class metrics for broadsheets', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subject_id' => $subjectId,
        ]);
    }

    protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        Log::info('updateSubjectPositions called', compact('subjectclass_id', 'staff_id', 'term_id', 'session_id'));
        $broadsheets = Broadsheets::where('subjectclass_id', $subjectclass_id)
            ->where('staff_id', $staff_id)
            ->where('term_id', $term_id)
            ->where('broadsheet_records.session_id', $session_id)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->orderByDesc('broadsheets.cum')
            ->orderBy('broadsheets.id')
            ->get();

        if ($broadsheets->isEmpty()) {
            Log::warning('No broadsheets found for position update', compact('subjectclass_id', 'staff_id', 'term_id', 'session_id'));
            return;
        }

        $rank = 0;
        $lastCum = null;
        $lastPosition = 0;

        foreach ($broadsheets as $broadsheet) {
            $rank++;
            if ($lastCum !== null && $broadsheet->cum == $lastCum) {
                // Tied rank
            } else {
                $lastPosition = $rank;
                $lastCum = $broadsheet->cum;
            }
            if ($broadsheet->subject_position_class != $lastPosition) {
                $broadsheet->subject_position_class = $lastPosition;
                $broadsheet->save();
                Log::info('Updated position', [
                    'broadsheet_id' => $broadsheet->id,
                    'student_id' => $broadsheet->student_id,
                    'admissionno' => $broadsheet->admissionno,
                    'cum' => $broadsheet->cum,
                    'subject_position_class' => $lastPosition,
                ]);
            }
        }

        Log::info('Subject positions updated', ['total_records' => $broadsheets->count()]);
    }

    protected function updateClassPositions($schoolclassid, $termid, $sessionid)
    {
        $rank = 0;
        $lastScore = null;
        $rows = 0;

        $pos = PromotionStatus::where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)
            ->orderBy('subjectstotalscores', 'DESC')
            ->get();

        foreach ($pos as $row) {
            $rows++;
            if ($lastScore !== $row->subjectstotalscores) {
                $lastScore = $row->subjectstotalscores;
                $rank = $rows;
            }
            $position = match ($rank) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
            $rankPos = $rank . $position;

            PromotionStatus::where('id', $row->id)
                ->update(['position' => $rankPos]);
        }
    }

    public function edit($id)
    {
        $broadsheet = Broadsheets::where('broadsheets.id', $id)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheets.subjectclass_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->first([
                'broadsheets.id as bid',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.title',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentpicture.picture',
                'broadsheets.ca1',
                'broadsheets.ca2',
                'broadsheets.ca3',
                'broadsheets.exam',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'schoolterm.term',
                'schoolsession.session',
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.id',
                'broadsheets.subject_position_class as position',
                'broadsheets.remark',
                'classcategories.ca1id as id1',
                'classcategories.ca2id as id2',
                'classcategories.ca3id as id3',
                'classcategories.examid as id4',
                'broadsheet_records.student_id',
                'broadsheets.staff_id',
                'broadsheets.term_id',
                'broadsheet_records.session_id as sessionid',
            ]);

        if (!$broadsheet) {
            return view('error', [
                'id' => $id,
                'title' => 'Not Found',
                'message' => 'Score not found.',
            ]);
        }

        $pagetitle = sprintf(
            'Edit Score for %s %s - %s (%s)',
            $broadsheet->fname,
            $broadsheet->lname,
            $broadsheet->subject,
            $id
        );

        return view('scoresheet.edit', compact('broadsheet', 'pagetitle'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ca1' => 'nullable|numeric|min:0|max:100',
            'ca2' => 'nullable|numeric|min:0|max:100',
            'ca3' => 'nullable|numeric|min:0|max:100',
            'exam' => 'nullable|numeric|min:0|max:100',
        ]);

        $broadsheet = Broadsheets::findOrFail($id);
        $termId = $broadsheet->term_id;
        $broadsheetRecord = DB::table('broadsheet_records')
            ->where('id', $broadsheet->broadsheet_record_id)
            ->first();

        if (!$broadsheetRecord) {
            return redirect()->back()->with('error', 'Broadsheet record not found.');
        }

        $ca1 = $request->ca1 ?? 0;
        $ca2 = $request->ca2 ?? 0;
        $ca3 = $request->ca3 ?? 0;
        $exam = $request->exam ?? 0;
        $caAverage = ($ca1 + $ca2 + $ca3) / 3;
        $total = round(($caAverage + $exam) / 2, 1);
        $bf = $this->getPreviousTermCum(
            $broadsheetRecord->student_id,
            $broadsheetRecord->subject_id,
            $termId,
            $broadsheetRecord->session_id
        );
        $cum = $termId == 1 ? $total : round(($bf + $total) / 2, 2);

        // Fetch the school class and its class category for grading
        $schoolclass = Schoolclass::with('classcategory')->find($broadsheetRecord->schoolclass_id);
        $grade = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->calculateGrade($cum)
            : $this->getDefaultGrade($cum); // Fallback grading if classcategory is not found
        $remark = $this->getRemark($grade);

        $broadsheet->update([
            'ca1' => $ca1,
            'ca2' => $ca2,
            'ca3' => $ca3,
            'exam' => $exam,
            'total' => $total,
            'bf' => $bf,
            'cum' => $cum,
            'grade' => $grade,
            'remark' => $remark,
        ]);

        $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);
        $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);
        $this->updateClassPositions($broadsheetRecord->schoolclass_id, $broadsheet->term_id, $broadsheetRecord->session_id);

        return redirect()->action(
            [self::class, 'subjectscoresheet'],
            [
                'schoolclassid' => $broadsheetRecord->schoolclass_id,
                'subjectclassid' => $broadsheet->subjectclass_id,
                'staffid' => $broadsheet->staff_id,
                'termid' => $termId,
                'sessionid' => $broadsheetRecord->session_id,
            ]
        )->with('success', 'Score updated successfully!');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $broadsheet = Broadsheets::findOrFail($id);
        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid = $broadsheet->staff_id;
        $termid = $broadsheet->term_id;

        $broadsheetRecord = DB::table('broadsheet_records')
            ->where('id', $broadsheet->broadsheet_record_id)
            ->first();

        $broadsheet->delete();

        if ($broadsheetRecord) {
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateClassPositions($broadsheetRecord->schoolclass_id, $termid, $broadsheetRecord->session_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Score deleted successfully!',
        ]);
    }

     protected function calculateJuniorGrade($score)
    {
        if ($score >= 70 && $score <= 100) {
            return 'A';
        } elseif ($score >= 60) {
            return 'B';
        } elseif ($score >= 50) {
            return 'C';
        } elseif ($score >= 40) {
            return 'D';
        }
        return 'F';
    }

        /**
     * Fallback grading logic when class category is not available
     */
    protected function getDefaultGrade($score)
    {
        if ($score >= 70 && $score <= 100) {
            return 'A';
        } elseif ($score >= 60) {
            return 'B';
        } elseif ($score >= 50) {
            return 'C';
        } elseif ($score >= 40) {
            return 'D';
        }
        return 'F';
    }

    protected function getRemark($grade)
    {
        $remarks = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Pass',
            'F' => 'Fail',
            'A1' => 'Excellent',
            'B2' => 'Very Good',
            'B3' => 'Good',
            'C4' => 'Credit',
            'C5' => 'Credit',
            'C6' => 'Credit',
            'D7' => 'Pass',
            'E8' => 'Pass',
            'F9' => 'Fail',
        ];
        return $remarks[$grade] ?? 'Unknown';
    }

    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) {
            Log::debug('getBroadsheets: Term 1, bf set to 0', [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
            ]);
            return 0;
        }

        $previousTerm = Broadsheets::where('broadsheet_records.student_id', $studentId)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->where('broadsheets.term_id', $termId - 1)
            ->where('broadsheet_records.session_id', $sessionId)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->value('broadsheets.cum');

        if (is_null($previousTerm)) {
            Log::warning('getBroadsheets: No previous term cum found', [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'term_id' => $termId - 1,
                'session_id' => $sessionId,
            ]);
            return 0;
        }

        $cum = round($previousTerm, 2);
        Log::debug('getBroadsheets: Fetched previous cum', [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'term_id' => $termId - 1,
            'cum' => $cum,
        ]);

        return $cum;
    }

   public function bulkUpdateScores(Request $request)
{
    $scores = $request->input('scores', []);
    $term_id = $request->input('term_id');
    $session_id = $request->input('session_id');
    $subjectclass_id = $request->input('subjectclass_id');
    $staff_id = $request->input('staff_id');
    $schoolclass_id = $request->input('schoolclass_id');

    // Validate input parameters
    if (!$term_id || !$session_id || !$subjectclass_id || !$staff_id || !$schoolclass_id) {
        Log::error('Missing required parameters for bulk update', [
            'term_id' => $term_id,
            'session_id' => $session_id,
            'subjectclass_id' => $subjectclass_id,
            'staff_id' => $staff_id,
            'schoolclass_id' => $schoolclass_id,
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Missing required parameters',
        ], 400);
    }

    Log::info('Starting bulk update scores', [
        'scores_count' => count($scores),
        'term_id' => $term_id,
        'session_id' => $session_id,
        'subjectclass_id' => $subjectclass_id,
        'staff_id' => $staff_id,
        'schoolclass_id' => $schoolclass_id,
    ]);

    // Fetch the school class and its class category once
    $schoolclass = Schoolclass::with('classcategory')->find($schoolclass_id);
    if (!$schoolclass) {
        Log::error('Schoolclass not found', ['schoolclass_id' => $schoolclass_id]);
        return response()->json([
            'success' => false,
            'message' => 'School class not found',
        ], 404);
    }

    DB::transaction(function () use ($scores, $term_id, $session_id, $subjectclass_id, $staff_id, $schoolclass_id, $schoolclass) {
        foreach ($scores as $score) {
            $broadsheet = Broadsheets::find($score['id']);
            if (!$broadsheet) {
                Log::warning('Broadsheet not found', ['id' => $score['id']]);
                continue;
            }

            $ca1 = floatval($score['ca1'] ?? 0);
            $ca2 = floatval($score['ca2'] ?? 0);
            $ca3 = floatval($score['ca3'] ?? 0);
            $exam = floatval($score['exam'] ?? 0);

            $ca_average = ($ca1 + $ca2 + $ca3) / 3;
            $total = round(($ca_average + $exam) / 2, 1);

            $bf = $this->getPreviousTermCum(
                $broadsheet->broadsheetRecord->student_id,
                $broadsheet->broadsheetRecord->subject_id,
                $term_id,
                $session_id
            );

            $cum = $term_id == 1 ? $total : round(($bf + $total) / 2, 2);

            // Use Classcategory's calculateGrade method
            $grade = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($cum)
                : $this->getDefaultGrade($cum);
            $remark = $this->getRemark($grade);

            $broadsheet->update([
                'ca1' => $ca1,
                'ca2' => $ca2,
                'ca3' => $ca3,
                'exam' => $exam,
                'total' => $total,
                'bf' => $bf,
                'cum' => $cum,
                'grade' => $grade,
                'remark' => $remark,
                'updated_at' => now(),
            ]);

            Log::info('Updated broadsheet', [
                'id' => $broadsheet->id,
                'admissionno' => $broadsheet->broadsheetRecord->student->admissionNO ?? 'N/A',
                'ca1' => $ca1,
                'ca2' => $ca2,
                'ca3' => $ca3,
                'exam' => $exam,
                'total' => $total,
                'bf' => $bf,
                'cum' => $cum,
                'grade' => $grade,
                'remark' => $remark,
            ]);
        }

        // Update metrics and positions
        $this->updateClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateClassPositions($schoolclass_id, $term_id, $session_id);
    });

    // Fetch updated broadsheets with all required fields
    $updatedBroadsheets = Broadsheets::query()
        ->where('broadsheets.subjectclass_id', $subjectclass_id)
        ->where('broadsheets.term_id', $term_id)
        ->where('broadsheets.staff_id', $staff_id)
        ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
        ->join('subjectclass', function ($join) use ($subjectclass_id) {
            $join->on('subjectclass.id', '=', 'broadsheets.subjectclass_id')
                 ->on('broadsheet_records.subject_id', '=', 'subjectclass.subjectid')
                 ->on('broadsheet_records.schoolclass_id', '=', 'subjectclass.schoolclassid');
        })
        ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
        ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
        ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
        ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
        ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
        ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
        ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
        ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
        ->where('broadsheet_records.session_id', $session_id)
        ->where('schoolclass.id', $schoolclass_id)
        ->select([
            'broadsheets.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records.subject_id',
            'schoolclass.schoolclass',
            'schoolclass.id as schoolclass_id',
            'schoolarm.arm',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheets.staff_id',
            'broadsheets.term_id',
            'broadsheet_records.session_id as sessionid',
            'classcategories.ca1score as ca1score',
            'classcategories.ca2score as ca2score',
            'classcategories.ca3score as ca3score',
            'classcategories.examscore as examscore',
            'studentpicture.picture',
            'broadsheets.ca1',
            'broadsheets.ca2',
            'broadsheets.ca3',
            'broadsheets.exam',
            'broadsheets.total',
            'broadsheets.bf',
            'broadsheets.cum',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position',
            'broadsheets.remark',
        ])
        ->orderBy('broadsheets.cum', 'DESC')
        ->get();

    Log::info('bulkUpdateScores: Returning updated broadsheets', [
        'count' => $updatedBroadsheets->count(),
        'broadsheets' => $updatedBroadsheets->map(function ($b) {
            return [
                'id' => $b->id,
                'admissionno' => $b->admissionno,
                'cum' => $b->cum,
                'grade' => $b->grade,
                'position' => $b->position,
            ];
        })->toArray(),
    ]);

    return response()->json([
        'success' => true,
        'data' => [
            'broadsheets' => $updatedBroadsheets->toArray(),
        ],
    ], 200);
}


public function import(Request $request)
{
    Log::info('Import: Request received', [
        'user_id' => $request->user()->id,
        'has_file' => $request->hasFile('file'),
        'input' => $request->all(),
    ]);

    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
        'schoolclass_id' => 'required|integer|exists:schoolclass,id',
        'subjectclass_id' => 'required|integer|exists:subjectclass,id',
        'staff_id' => 'required|integer|exists:users,id',
        'term_id' => 'required|integer|in:1,2,3',
        'session_id' => 'required|integer|exists:schoolsession,id',
    ]);

    try {
        $importData = [
            'schoolclass_id' => $request->schoolclass_id,
            'subjectclass_id' => $request->subjectclass_id,
            'staff_id' => $request->staff_id,
            'term_id' => $request->term_id,
            'session_id' => $request->session_id,
        ];

        Log::debug('Import: Starting import', $importData);

        // Initialize progress tracking
        $progressKey = 'import_progress_' . $request->user()->id;
        session([$progressKey => ['progress' => 0, 'total' => 0, 'status' => 'starting']]);

        $import = new ScoresheetImport($importData);

        // Validate Excel metadata
        $filePath = $request->file('file')->getPathname();
        $import->validateExcelMetadata($filePath);

        // Estimate total rows for progress tracking
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $totalRows = $spreadsheet->getActiveSheet()->getHighestRow() - ($import->startRow() - 1);
        session([$progressKey => ['progress' => 0, 'total' => $totalRows, 'status' => 'processing']]);

        // Proceed with import
        Excel::import($import, $request->file('file'));

        // After import, update class metrics and positions
        $this->updateClassMetrics($request->subjectclass_id, $request->staff_id, $request->term_id, $request->session_id);
        $this->updateSubjectPositions($request->subjectclass_id, $request->staff_id, $request->term_id, $request->session_id);
        $this->updateClassPositions($request->schoolclass_id, $request->term_id, $request->session_id);

        $updatedBroadsheets = $import->getUpdatedBroadsheets();
        $failures = $import->getFailures();

        // Update progress to complete
        session([$progressKey => ['progress' => $totalRows, 'total' => $totalRows, 'status' => 'completed']]);

        Log::info('Import: Success', [
            'updated_broadsheets_count' => count($updatedBroadsheets),
            'failures_count' => count($failures),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'broadsheets' => $updatedBroadsheets,
                'failures' => $failures,
            ],
            'message' => 'Scores imported successfully! Updated ' . count($updatedBroadsheets) . ' records.' . 
                        (count($failures) ? ' Skipped ' . count($failures) . ' rows due to validation errors.' : ''),
        ]);

    } catch (\Exception $e) {
        // Update progress to failed
        $progressKey = 'import_progress_' . $request->user()->id;
        session([$progressKey => ['progress' => 0, 'total' => 0, 'status' => 'failed', 'error' => $e->getMessage()]]);

        Log::error('Import: Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Customize error message for metadata validation failures
        $errorMessage = $e->getMessage();
        if (str_contains($errorMessage, 'Excel file metadata does not match')) {
            $errorMessage = 'The uploaded scoresheet does not match the selected class, subject, term, or session. Please check the scoresheet details and try again. Details: ' . $errorMessage;
        } else {
            $errorMessage = 'Failed to import scores: ' . $errorMessage;
        }

        return response()->json([
            'success' => false,
            'message' => $errorMessage,
        ], 422);
    }
}


public function importProgress(Request $request)
{
    $progressKey = 'import_progress_' . $request->user()->id;
    $progress = session($progressKey, ['progress' => 0, 'total' => 0, 'status' => 'idle']);

    return response()->json([
        'progress' => $progress['progress'],
        'total' => $progress['total'],
        'status' => $progress['status'],
        'error' => $progress['error'] ?? null,
    ]);
}
    public function export()
    {
        $schoolclassId = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId = session('term_id');
        $sessionId = session('session_id');
        $staffId = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return redirect()->back()->with('error', 'Missing required data for export.');
        }

        $broadsheet = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassId)
            ->where('broadsheets.staff_id', $staffId)
            ->where('broadsheets.term_id', $termId)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheets.subjectclass_id')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->where('broadsheet_records.session_id', $sessionId)
            ->first([
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolterm.term',
                'schoolsession.session',
                'users.name as staff_name',
            ]);

        if (!$broadsheet) {
            return redirect()->back()->with('error', 'No data found for export.');
        }

        $staffName = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->staff_name);
        $subject = str_replace([' ', '.', ',', "'", '"', '&'], '_', $broadsheet->subject);
        $subjectCode = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->subject_code);
        $schoolClass = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->schoolclass);
        $arm = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->arm);
        $term = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->term);
        $session = str_replace([' ', '.', ',', "'", '"', '/', '-'], '', $broadsheet->session);

        $filename = sprintf(
            'Scores_Sheet_%s_%s_%s_%s_%s_%s_%s.xlsx',
            $staffName,
            $subject,
            $subjectCode,
            $schoolClass,
            $arm,
            $term,
            $session
        );

        return Excel::download(
            new RecordsheetExport($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId),
            $filename
        );
    }

    public function downloadMarkSheet()
    {
        $schoolclassId = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId = session('term_id');
        $sessionId = session('session_id');
        $staffId = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return response()->json([
                'error' => 'Missing required data for download.',
                'session_data' => session()->all(),
            ], 400);
        }

        try {
            $export = new MarksSheetExport($subjectclassId, $staffId, $termId, $sessionId, $schoolclassId);
            return $export->download();
        } catch (\Exception $e) {
            Log::error('Marksheet download error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate marksheet: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function mockIndex(Request $request)
    {
        $pagetitle = 'My Mock Scoresheets';
        $broadsheets = collect();

        Log::info('Mock Index session:', $request->session()->all());

        if (!$request->ajax()) {
            $termId = $request->query('termid', 'ALL');
            $sessionId = $request->query('sessionid', 'ALL');

            if ($termId !== 'ALL' && $sessionId !== 'ALL') {
                $broadsheets = $this->getMockBroadsheets($request->user()->id, $termId, $sessionId);
                Log::info('Mock Index broadsheets count:', ['count' => $broadsheets->count()]);
            }
        }

        if ($request->ajax()) {
            $termId = $request->input('termid', 'ALL');
            $sessionId = $request->input('sessionid', 'ALL');

            if ($termId === 'ALL' || $sessionId === 'ALL') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select both term and session.',
                ], 422);
            }

            $broadsheets = $this->getMockBroadsheets($request->user()->id, $termId, $sessionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'broadsheets' => $broadsheets,
                ],
            ]);
        }

        return view('subjectscoresheet.mock_index', compact('pagetitle', 'broadsheets'));
    }

    public function mockSubjectscoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        Log::info('Mock Subjectscoresheet parameters:', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid'));

        session([
            'schoolclass_id' => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
        ]);

        $broadsheets = $this->getMockBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

        Log::info('Mock Subjectscoresheet broadsheets count:', ['count' => $broadsheets->count()]);

        $pagetitle = 'Mock Subject Scoresheet';

        if ($broadsheets->isNotEmpty()) {
            $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);

            $firstBroadsheet = $broadsheets->first();
            $pagetitle = sprintf(
                'Mock Scoresheet for %s (%s) - %s %s - %s %s',
                $firstBroadsheet->subject,
                $firstBroadsheet->subject_code,
                $firstBroadsheet->schoolclass,
                $firstBroadsheet->arm,
                $firstBroadsheet->term,
                $firstBroadsheet->session
            );
        }

        return view('subjectscoresheet.subjectscoresheet-mock', compact('broadsheets', 'pagetitle'));
    }

    public function mockEdit($id)
    {
        $broadsheet = BroadsheetsMock::where('broadsheetmock.id', $id)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheetmock.subjectclass_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records_mock.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheetmock.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
            ->first([
                'broadsheetmock.id as bid',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.title',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentpicture.picture',
                'broadsheetmock.exam',
                'broadsheetmock.total',
                'broadsheetmock.grade',
                'schoolterm.term',
                'schoolsession.session',
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.id',
                'broadsheetmock.subject_position_class as position',
                'broadsheetmock.remark',
                'broadsheet_records_mock.student_id',
                'broadsheetmock.staff_id',
                'broadsheetmock.term_id',
                'broadsheet_records_mock.session_id as sessionid',
            ]);

        if (!$broadsheet) {
            return view('error', [
                'id' => $id,
                'title' => 'Not Found',
                'message' => 'Mock score not found.',
            ]);
        }

        $pagetitle = sprintf(
            'Edit Mock Score for %s %s - %s (%s)',
            $broadsheet->fname,
            $broadsheet->lname,
            $broadsheet->subject,
            $id
        );

        return view('scoresheet.mock_edit', compact('broadsheet', 'pagetitle'));
    }

   public function mockUpdate(Request $request, $id)
    {
        $request->validate([
            'exam' => 'nullable|numeric|min:0|max:100',
        ]);

        $broadsheet = BroadsheetsMock::findOrFail($id);
        $termId = $broadsheet->term_id;
        $broadsheetRecord = BroadsheetRecordMock::where('id', $broadsheet->broadsheet_records_mock_id)->first();

        if (!$broadsheetRecord) {
            return redirect()->back()->with('error', 'Mock broadsheet record not found.');
        }

        $exam = $request->exam ?? 0;
        $total = $exam;

        // Fetch the school class and its class category for grading
        $schoolclass = Schoolclass::with('classcategory')->find($broadsheetRecord->schoolclass_id);
        $grade = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->calculateGrade($total)
            : $this->getDefaultGrade($total); // Fallback grading
        $remark = $this->getRemark($grade);

        $broadsheet->update([
            'exam' => $exam,
            'total' => $total,
            'grade' => $grade,
            'remark' => $remark,
        ]);

        $this->updateMockClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);
        $this->updateMockSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);

        return redirect()->action(
            [self::class, 'mockSubjectscoresheet'],
            [
                'schoolclassid' => $broadsheetRecord->schoolclass_id,
                'subjectclassid' => $broadsheet->subjectclass_id,
                'staffid' => $broadsheet->staff_id,
                'termid' => $termId,
                'sessionid' => $broadsheetRecord->session_id,
            ]
        )->with('success', 'Mock score updated successfully!');
    }

    public function mockDestroy(Request $request)
    {
        $id = $request->input('id');
        $broadsheet = BroadsheetsMock::findOrFail($id);
        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid = $broadsheet->staff_id;
        $termid = $broadsheet->term_id;

        $broadsheetRecord = BroadsheetRecordMock::where('id', $broadsheet->broadsheet_records_mock_id)->first();

        $broadsheet->delete();

        if ($broadsheetRecord) {
            $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mock score deleted successfully!',
        ]);
    }

   public function mockBulkUpdateScores(Request $request)
    {
        $scores = $request->input('scores', []);
        $term_id = $request->input('term_id');
        $session_id = $request->input('session_id');
        $subjectclass_id = $request->input('subjectclass_id');
        $staff_id = $request->input('staff_id');
        $schoolclass_id = $request->input('schoolclass_id');

        if (!$term_id || !$session_id || !$subjectclass_id || !$staff_id || !$schoolclass_id) {
            Log::error('Missing required parameters for mock bulk update', [
                'term_id' => $term_id,
                'session_id' => $session_id,
                'subjectclass_id' => $subjectclass_id,
                'staff_id' => $staff_id,
                'schoolclass_id' => $schoolclass_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Missing required parameters',
            ], 400);
        }

        Log::info('Starting mock bulk update scores', [
            'scores_count' => count($scores),
            'term_id' => $term_id,
            'session_id' => $session_id,
        ]);

        // Fetch the school class and its class category once for all scores
        $schoolclass = Schoolclass::with('classcategory')->find($schoolclass_id);

        DB::transaction(function () use ($scores, $term_id, $session_id, $subjectclass_id, $staff_id, $schoolclass_id, $schoolclass) {
            foreach ($scores as $score) {
                $broadsheet = BroadsheetsMock::find($score['id']);
                if (!$broadsheet) {
                    Log::warning('Mock broadsheet not found', ['id' => $score['id']]);
                    continue;
                }

                $exam = floatval($score['exam'] ?? 0);
                $total = $exam;

                Log::info('Mock score calculation', [
                    'id' => $score['id'],
                    'total' => $total,
                    'term_id' => $term_id,
                ]);

                // Use Classcategory's calculateGrade method
                $grade = $schoolclass && $schoolclass->classcategory
                    ? $schoolclass->classcategory->calculateGrade($total)
                    : $this->getDefaultGrade($total); // Fallback grading
                $remark = $this->getRemark($grade);

                $broadsheet->update([
                    'exam' => $exam,
                    'total' => $total,
                    'grade' => $grade,
                    'remark' => $remark,
                    'updated_at' => now(),
                ]);
            }

            $this->updateMockClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
            $this->updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
        });

        $updatedBroadsheets = BroadsheetsMock::where('broadsheetmock.subjectclass_id', $subjectclass_id)
            ->where('broadsheetmock.term_id', $term_id)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
            ->select([
                'broadsheetmock.*',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
            ])
            ->orderBy('broadsheetmock.total', 'DESC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'broadsheets' => $updatedBroadsheets,
            ],
        ]);
    }

    public function mockImport(Request $request)
    {
        Log::info('Mock import: Request received', [
            'user_id' => $request->user()->id,
            'has_file' => $request->hasFile('file'),
        ]);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'schoolclass_id' => 'required|integer',
            'subjectclass_id' => 'required|integer',
            'staff_id' => 'required|integer',
            'term_id' => 'required|integer',
            'session_id' => 'required|integer',
        ]);

        try {
            $importData = [
                'schoolclass_id' => $request->schoolclass_id,
                'subjectclass_id' => $request->subjectclass_id,
                'staff_id' => $request->staff_id,
                'term_id' => $request->term_id,
                'session_id' => $request->session_id,
            ];

            Log::debug('Mock import: Starting import', $importData);

            $import = new ScoresheetImport($importData, true);
            Excel::import($import, $request->file('file'));

            // After import, update mock class metrics and positions
            $this->updateMockClassMetrics($request->subjectclass_id, $request->staff_id, $request->term_id, $request->session_id);
            $this->updateMockSubjectPositions($request->subjectclass_id, $request->staff_id, $request->term_id, $request->session_id);

            $updatedBroadsheets = $import->getUpdatedBroadsheets();
            $failures = $import->getFailures();

            Log::info('Mock import: Success', [
                'updated_broadsheets_count' => count($updatedBroadsheets),
                'failures_count' => count($failures),
            ]);

            $message = "Mock scores imported successfully! Updated " . count($updatedBroadsheets) . " records.";
            if ($failures) {
                $message .= " Skipped " . count($failures) . " rows due to validation errors.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'broadsheets' => $updatedBroadsheets,
                'errors' => $failures,
            ]);
        } catch (\Exception $e) {
            Log::error('Mock import: Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to import mock scores: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function mockResults()
    {
        try {
            $subjectclass_id = session('subjectclass_id');
            $schoolclass_id = session('schoolclass_id');
            $term_id = session('term_id');
            $session_id = session('session_id');

            if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required session data',
                    'scores' => [],
                ], 400);
            }

            $broadsheets = BroadsheetsMock::where([
                'subjectclass_id' => $subjectclass_id,
                'term_id' => $term_id,
            ])
                ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
                ->where('broadsheet_records_mock.session_id', $session_id)
                ->get([
                    'broadsheetmock.id',
                    'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname',
                    'broadsheetmock.exam',
                    'broadsheetmock.total',
                    'broadsheetmock.grade',
                    'broadsheetmock.subject_position_class as position',
                    'broadsheetmock.term_id',
                ]);

            return response()->json([
                'success' => true,
                'scores' => $broadsheets->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in mock results endpoint: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function mockExport()
    {
        $schoolclassId = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId = session('term_id');
        $sessionId = session('session_id');
        $staffId = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return redirect()->back()->with('error', 'Missing required data for mock export.');
        }

        $broadsheet = BroadsheetsMock::where('broadsheetmock.subjectclass_id', $subjectclassId)
            ->where('broadsheetmock.staff_id', $staffId)
            ->where('broadsheetmock.term_id', $termId)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records_mock.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheetmock.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheetmock.subjectclass_id')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->where('broadsheet_records_mock.session_id', $sessionId)
            ->first([
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolterm.term',
                'schoolsession.session',
                'users.name as staff_name',
            ]);

        if (!$broadsheet) {
            return redirect()->back()->with('error', 'No data found for mock export.');
        }

        $staffName = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->staff_name);
        $subject = str_replace([' ', '.', ',', "'", '"', '&'], '_', $broadsheet->subject);
        $subjectCode = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->subject_code);
        $schoolClass = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->schoolclass);
        $arm = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->arm);
        $term = str_replace([' ', '.', ',', "'", '"'], '_', $broadsheet->term);
        $session = str_replace([' ', '.', ',', "'", '"', '/', '-'], '', $broadsheet->session);

        $filename = sprintf(
            'Mock_Scores_Sheet_%s_%s_%s_%s_%s_%s_%s.xlsx',
            $staffName,
            $subject,
            $subjectCode,
            $schoolClass,
            $arm,
            $term,
            $session
        );

        return Excel::download(
            new MockRecordsheetExport($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId, true),
            $filename
        );
    }

    public function mockDownloadMarkSheet()
    {
        $schoolclassId = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId = session('term_id');
        $sessionId = session('session_id');
        $staffId = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return redirect()->back()->with('error', 'Missing required data for mock download.');
        }

        try {
            $export = new MockMarksSheetExport($subjectclassId, $staffId, $termId, $sessionId, $schoolclassId, true);
            return $export->download();
        } catch (\Exception $e) {
            Log::error('Mock marksheet download error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate mock marksheet: ' . $e->getMessage());
        }
    }

    protected function getMockBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = BroadsheetsMock::query()
            ->where('broadsheetmock.staff_id', $staffId)
            ->where('broadsheetmock.term_id', $termId)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records_mock.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheetmock.subjectclass_id')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheetmock.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
            ->where('broadsheet_records_mock.session_id', $sessionId);

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }
        if ($subjectClassId) {
            $query->where('subjectclass.id', $subjectClassId);
        }

        $results = $query->get([
            'broadsheetmock.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records_mock.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records_mock.subject_id',
            'schoolclass.schoolclass',
            'schoolarm.arm',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheetmock.staff_id',
            'broadsheetmock.term_id',
            'broadsheet_records_mock.session_id as sessionid',
            'studentpicture.picture',
            'broadsheetmock.exam',
            'broadsheetmock.total',
            'broadsheetmock.grade',
            'broadsheetmock.subject_position_class as position',
            'broadsheetmock.remark',
        ])->sortBy('lname');

            foreach ($results as $broadsheet) {
            $exam = $broadsheet->exam ?? 0;
            $newTotal = $exam;

            // Fetch the school class and its class category for grading
            $schoolclass = Schoolclass::with('classcategory')->find($broadsheet->schoolclass_id);
            $newGrade = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($newTotal)
                : $this->getDefaultGrade($newTotal); // Updated line
            $newRemark = $this->getRemark($newGrade);

            $significantChange = abs($broadsheet->total - $newTotal) > 0.01 ||
                                $broadsheet->grade !== $newGrade ||
                                $broadsheet->remark !== $newRemark;

            if ($significantChange) {
                Log::info("Updating mock broadsheet {$broadsheet->id} due to significant changes", [
                    'old_values' => [
                        'total' => $broadsheet->total,
                        'grade' => $broadsheet->grade,
                        'remark' => $broadsheet->remark,
                    ],
                    'new_values' => [
                        'total' => $newTotal,
                        'grade' => $newGrade,
                        'remark' => $newRemark,
                    ],
                ]);

                $broadsheet->total = $newTotal;
                $broadsheet->grade = $newGrade;
                $broadsheet->remark = $newRemark;
                $broadsheet->save();
            }
        }

        return $results;
    }

   protected function updateMockClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        // Fetch the subjectclass to get the subject_id
        $subjectClass = DB::table('subjectclass')
            ->where('id', $subjectclassid)
            ->first(['subjectteacherid']);

        if (!$subjectClass) {
            Log::warning('Subjectclass not found for mock', ['subjectclass_id' => $subjectclassid]);
            return;
        }

        $subjectTeacher = DB::table('subjectteacher')
            ->where('id', $subjectClass->subjectteacherid)
            ->first(['subjectid']);

        if (!$subjectTeacher) {
            Log::warning('Subjectteacher not found for mock', ['subjectteacherid' => $subjectClass->subjectteacherid]);
            return;
        }

        $subjectId = $subjectTeacher->subjectid;

        // Calculate class metrics (min, max, avg) for the subject across all students linked to the subjectclass_id
        $metrics = BroadsheetsMock::where('broadsheetmock.subjectclass_id', $subjectclassid)
            ->where('broadsheetmock.staff_id', $staffid)
            ->where('broadsheetmock.term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.subject_id', $subjectId)
            ->select([
                DB::raw('MIN(broadsheetmock.total) as class_min'),
                DB::raw('MAX(broadsheetmock.total) as class_max'),
                DB::raw('AVG(broadsheetmock.total) as class_avg'),
                DB::raw('COUNT(broadsheetmock.id) as student_count'),
                DB::raw('SUM(broadsheetmock.total) as total_sum')
            ])
            ->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round($metrics->class_avg, 1) : 0;

        Log::info('Calculated mock class metrics', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subject_id' => $subjectId,
            'class_min' => $classMin,
            'class_max' => $classMax,
            'class_avg' => $classAvg,
            'student_count' => $metrics->student_count,
            'total_sum' => $metrics->total_sum,
        ]);

        // Update all relevant mock broadsheet records with the calculated metrics
        BroadsheetsMock::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.subject_id', $subjectId)
            ->update([
                'cmin' => $classMin,
                'cmax' => $classMax,
                'avg' => $classAvg,
            ]);

        Log::info('Updated mock class metrics for broadsheets', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subject_id' => $subjectId,
        ]);
    }

    protected function updateMockSubjectPositions($subjectclassid, $staffid, $termid, $sessionid)
    {
        $rank = 0;
        $lastScore = null;
        $rows = 0;

        $classPos = BroadsheetsMock::where('broadsheetmock.subjectclass_id', $subjectclassid)
            ->where('broadsheetmock.staff_id', $staffid)
            ->where('broadsheetmock.term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->orderBy('broadsheetmock.total', 'DESC')
            ->get(['broadsheetmock.id', 'broadsheetmock.total', 'broadsheetmock.broadsheet_records_mock_id']);

        foreach ($classPos as $row) {
            $rows++;
            if ($lastScore !== $row->total) {
                $lastScore = $row->total;
                $rank = $rows;
            }
            $position = match ($rank) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
            $rankPos = $rank . $position;

            $broadsheetRecord = BroadsheetRecordMock::where('id', $row->broadsheet_records_mock_id)->first();

            if ($broadsheetRecord) {
                BroadsheetsMock::where('id', $row->id)
                    ->update(['subject_position_class' => $rankPos]);
            }
        }

        Log::info('Updated subject positions for mock exams across entire class', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'total_records' => $rows,
        ]);
    }

    public function calculateGradePreview(Request $request)
    {
        $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'cum' => 'required|numeric|min:0|max:100',
        ]);

        $schoolclass = Schoolclass::with('classcategory')->findOrFail($request->schoolclass_id);
        $grade = $schoolclass->classcategory
            ? $schoolclass->classcategory->calculateGrade($request->cum)
            : $this->getDefaultGrade($request->cum);

        return response()->json(['grade' => $grade]);
    }
}