<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Schoolterm;
use App\Models\Schoolclass;
use App\Models\ClassTeacher;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ClassTeacherController extends Controller
{
    // Helper: Get full class name like "SSS 3 A"
    private function getFullClassName($classId)
    {
        return Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('schoolclass.id', $classId)
            ->value(\DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))"))
            ?: "Class ID: $classId";
    }

    public function index(Request $request)
    {
        $pagetitle = "Class Teacher Management";

        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select([
                'schoolclass.id',
                'schoolclass.schoolclass',
                'schoolarm.arm as schoolarm',
                \DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as fullclass")
            ])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        $subjectteachers = User::whereHas('roles', function ($q) {
            $q->where('name', '!=', 'Student');
        })->select('users.id as userid', 'users.name')->get();

        $schoolterms = Schoolterm::all();
        $schoolsessions = Schoolsession::all();

        $classteachers = ClassTeacher::leftJoin('users', 'users.id', '=', 'classteacher.staffid')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'classteacher.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'classteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'classteacher.sessionid')
            ->select([
                'classteacher.id',
                'users.id as userid',
                'users.name as staffname',
                'users.avatar',
                'schoolclass.id as schoolclassid',
                'schoolclass.schoolclass',
                'schoolarm.arm as schoolarm',
                \DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as fullclass"),
                'schoolterm.id as termid',
                'schoolterm.term',
                'schoolsession.id as sessionid',
                'schoolsession.session',
                'classteacher.updated_at'
            ])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->orderBy('users.name')
            ->paginate(1000);

        if ($request->ajax()) {
            $html = view('classteacher.index', compact(
                'classteachers', 'schoolclass', 'subjectteachers',
                'schoolterms', 'schoolsessions', 'pagetitle'
            ))->render();

            return response()->json([
                'success' => true,
                'html'    => $html,
                'count'   => $classteachers->count(),
                'total'   => $classteachers->total(),
            ]);
        }

        return view('classteacher.index', compact(
            'classteachers', 'schoolclass', 'subjectteachers',
            'schoolterms', 'schoolsessions', 'pagetitle'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staffid'       => 'required|exists:users,id',
            'schoolclassid' => 'required|array',
            'schoolclassid.*' => 'exists:schoolclass,id',
            'termid'        => 'required|exists:schoolterm,id',
            'sessionid'     => 'required|exists:schoolsession,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $staffId    = $request->staffid;
        $termId     = $request->termid;
        $sessionId  = $request->sessionid;
        $classIds   = $request->schoolclassid;

        $duplicateClasses = [];
        $conflictClasses  = [];

        foreach ($classIds as $classId) {
            $fullName = $this->getFullClassName($classId);

            // 1. This teacher already has this exact class+arm in this term/session?
            $alreadyHas = ClassTeacher::where('staffid', $staffId)
                ->where('schoolclassid', $classId)
                ->where('termid', $termId)
                ->where('sessionid', $sessionId)
                ->exists();

            if ($alreadyHas) {
                $duplicateClasses[] = $fullName;
                continue;
            }

            // 2. Another teacher has this exact class+arm in this term/session?
            $takenByOther = ClassTeacher::where('schoolclassid', $classId)
                ->where('termid', $termId)
                ->where('sessionid', $sessionId)
                ->where('staffid', '!=', $staffId)
                ->exists();

            if ($takenByOther) {
                $conflictClasses[] = $fullName;
            }
        }

        if (!empty($duplicateClasses)) {
            return response()->json([
                'success' => false,
                'message' => 'This teacher is already assigned to: ' . implode(', ', $duplicateClasses)
            ], 422);
        }

        if (!empty($conflictClasses)) {
            return response()->json([
                'success' => false,
                'message' => 'The following class(es) are already assigned to another teacher for the selected term and session: ' . implode(', ', $conflictClasses)
            ], 422);
        }

        foreach ($classIds as $classId) {
            ClassTeacher::create([
                'staffid'       => $staffId,
                'schoolclassid' => $classId,
                'termid'        => $termId,
                'sessionid'     => $sessionId,
            ]);
        }

        Log::info("Class teacher(s) assigned", ['staff' => $staffId, 'classes' => $classIds]);

        return response()->json([
            'success' => true,
            'message' => 'Class teacher(s) assigned successfully.'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'staffid'       => 'required|exists:users,id',
            'schoolclassid' => 'required|array',
            'schoolclassid.*' => 'exists:schoolclass,id',
            'termid'        => 'required|exists:schoolterm,id',
            'sessionid'     => 'required|exists:schoolsession,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $oldRecord = ClassTeacher::findOrFail($id);

        $oldStaffId    = $oldRecord->staffid;
        $oldTermId     = $oldRecord->termid;
        $oldSessionId  = $oldRecord->sessionid;

        $newStaffId    = $request->staffid;
        $newTermId     = $request->termid;
        $newSessionId  = $request->sessionid;
        $newClassIds   = $request->schoolclassid;

        $duplicateClasses = [];
        $conflictClasses  = [];

        foreach ($newClassIds as $classId) {
            $fullName = $this->getFullClassName($classId);

            // 1. New teacher already has this class+arm in the new term/session (outside old group)?
            $newTeacherHas = ClassTeacher::where('staffid', $newStaffId)
                ->where('schoolclassid', $classId)
                ->where('termid', $newTermId)
                ->where('sessionid', $newSessionId)
                ->where('staffid', '!=', $oldStaffId) // ignore records we're about to delete
                ->exists();

            if ($newTeacherHas) {
                $duplicateClasses[] = $fullName;
                continue;
            }

            // 2. Any third teacher has this class+arm in the new term/session?
            $takenByOther = ClassTeacher::where('schoolclassid', $classId)
                ->where('termid', $newTermId)
                ->where('sessionid', $newSessionId)
                ->whereNotIn('staffid', [$oldStaffId, $newStaffId])
                ->exists();

            if ($takenByOther) {
                $conflictClasses[] = $fullName;
            }
        }

        if ($duplicateClasses || $conflictClasses) {
            $msg = '';
            if ($duplicateClasses) $msg .= 'This teacher already has: ' . implode(', ', $duplicateClasses) . '. ';
            if ($conflictClasses)  $msg .= 'These classes are taken: ' . implode(', ', $conflictClasses);
            return response()->json(['success' => false, 'message' => trim($msg)], 422);
        }

        // Delete old assignments for this teacher + old term/session
        ClassTeacher::where('staffid', $oldStaffId)
            ->where('termid', $oldTermId)
            ->where('sessionid', $oldSessionId)
            ->delete();

        // Create new ones
        foreach ($newClassIds as $classId) {
            ClassTeacher::create([
                'staffid'       => $newStaffId,
                'schoolclassid' => $classId,
                'termid'        => $newTermId,
                'sessionid'     => $newSessionId,
            ]);
        }

        Log::info("Class teacher updated", [
            'from' => $oldStaffId,
            'to'   => $newStaffId,
            'classes' => $newClassIds
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class teacher assignment updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $record = ClassTeacher::find($id);
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $record->delete();
        Log::info("Class teacher deleted", ['id' => $id]);

        return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
    }

    public function deleteMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No records selected.'], 400);
        }

        $deleted = ClassTeacher::whereIn('id', $ids)->delete();
        Log::info("Multiple class teachers deleted", ['count' => $deleted]);

        return response()->json([
            'success' => true,
            'message' => "$deleted record(s) deleted."
        ]);
    }

    public function assignments($staffId, $termId, $sessionId)
    {
        $classIds = ClassTeacher::where('staffid', $staffId)
            ->where('termid', $termId)
            ->where('sessionid', $sessionId)
            ->pluck('schoolclassid')
            ->toArray();

        return response()->json([
            'success'  => true,
            'classIds' => $classIds
        ]);
    }
}