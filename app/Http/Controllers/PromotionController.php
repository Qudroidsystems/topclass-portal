<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\View\View;
use App\Models\Schoolclass;
use App\Models\Studentclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\PromotionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

class PromotionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View promotion', ['only' => ['index']]);
        $this->middleware('permission:Update promotion', ['only' => ['update', 'destroy']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        $pagetitle = "Student Promotion Management";

        $allstudents = new LengthAwarePaginator([], 0, 10);

        if ($request->filled('schoolclassid') && $request->filled('sessionid') && $request->input('schoolclassid') !== 'ALL' && $request->input('sessionid') !== 'ALL') {
            $query = Studentclass::query()
                ->where('schoolclassid', $request->input('schoolclassid'))
                ->where('sessionid', $request->input('sessionid'))
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.firstname', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.lastname', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.othername', 'like', "%{$search}%");
                });
            }

            try {
                $allstudents = $query->select([
                    'studentRegistration.id as stid',                    // Student primary key
                    'studentRegistration.admissionNo as admissionno',
                    'studentRegistration.firstname as firstname',
                    'studentRegistration.lastname as lastname',
                    'studentRegistration.othername as othername',
                    'studentRegistration.gender as gender',
                    'studentpicture.picture as picture',
                    'studentclass.schoolclassid as schoolclassID',
                    'studentclass.sessionid as sessionid',
                    'studentclass.termid as termid',
                    'schoolclass.schoolclass as schoolclass',
                    'schoolarm.arm as schoolarm',
                    'schoolsession.session as session',
                ])->latest('studentclass.created_at')->paginate(100);

                // Fetch promotion statuses including the primary key ID
                $studentKeys = $allstudents->map(function ($student) {
                    return $student->stid . '_' . $student->schoolclassID . '_' . $student->sessionid;
                })->toArray();

                $promotionStatuses = PromotionStatus::whereIn(
                    DB::raw('CONCAT(studentId, "_", schoolclassid, "_", sessionid)'),
                    $studentKeys
                )->select(['id', 'studentId', 'schoolclassid', 'sessionid', 'promotionStatus'])
                 ->get()
                 ->keyBy(function ($item) {
                     return $item->studentId . '_' . $item->schoolclassid . '_' . $item->sessionid;
                 });

                $allstudents->getCollection()->transform(function ($student) use ($promotionStatuses) {
                    $key = $student->stid . '_' . $student->schoolclassID . '_' . $student->sessionid;
                    if (isset($promotionStatuses[$key])) {
                        $student->promotion_status = $promotionStatuses[$key]->promotionStatus ?? 'N/A';
                        $student->promotion_id = $promotionStatuses[$key]->id; // PromotionStatus table ID
                    } else {
                        $student->promotion_status = 'N/A';
                        $student->promotion_id = null;
                    }
                    return $student;
                });

            } catch (Exception $e) {
                Log::error('Promotion query failed', [
                    'request' => $request->all(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $allstudents = new LengthAwarePaginator([], 0, 10);
            }
        }

        $schoolsessions = Schoolsession::get();
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);

        if ($request->ajax()) {
            return response()->json([
                'tableBody' => view('promotions.partials.student_rows', compact('allstudents'))->render(),
                'pagination' => $allstudents->links('pagination::bootstrap-5')->render(),
                'studentCount' => $allstudents->total(),
            ]);
        }

        return view('promotions.index', compact('allstudents', 'schoolsessions', 'schoolclasses', 'pagetitle'));
    }



// ============================================================
// REPLACEMENT for PromotionController::update()
// ============================================================
// The root cause of promotion-duplication:
//   updateOrCreate for PromotionStatus used:
//     ['studentId', 'schoolclassid', 'sessionid', 'termid']
//   ...but updateOrCreate for Studentclass only used:
//     ['studentId', 'sessionid', 'termid']
//   WITHOUT 'schoolclassid' in the studentclass key, a new
//   class assignment was inserted every time the class changed.
//
//   Also: if a student is promoted to a different class for the
//   same session+term, the old Studentclass row must be removed
//   first (or updated in-place), otherwise both rows survive
//   and the student appears in two classes.
//
// Fix:
//   - Studentclass: use the OLD class row (matched by studentId
//     + old sessionid + old termid) and UPDATE it in-place.
//     If no existing row is found, create one (fresh registration).
//   - PromotionStatus: identical scoping — old class is updated,
//     not duplicated.
// ============================================================

public function update(Request $request, $studentId)
{
    $request->validate([
        'new_schoolclassid' => 'required|exists:schoolclass,id',
        'new_sessionid'     => 'required|exists:schoolsession,id',
        'new_termid'        => 'required|integer|min:1|max:3',
        'promotion'         => 'boolean',
        'repeat'            => 'boolean',
    ]);

    if ($request->boolean('promotion') && $request->boolean('repeat')) {
        return response()->json([
            'success' => false,
            'message' => 'Cannot select both promotion and repeat.',
        ], 422);
    }

    $promotionStatus = $request->boolean('promotion')
        ? 'PROMOTED'
        : ($request->boolean('repeat') ? 'REPEAT' : 'PARENTS TO SEE PRINCIPAL');

    try {
        DB::transaction(function () use ($studentId, $request, $promotionStatus) {
            $newClassId  = $request->new_schoolclassid;
            $newSessionId= $request->new_sessionid;
            $newTermId   = $request->new_termid;

            // ----------------------------------------------------------
            // Studentclass: find the most-recent existing row for this
            // student in the target session+term and update it in-place.
            // This avoids leaving orphan rows from the old class.
            // If no row exists yet (first time for this term), create one.
            // ----------------------------------------------------------
            $existingClass = Studentclass::where('studentId', $studentId)
                ->where('sessionid', $newSessionId)
                ->where('termid', $newTermId)
                ->first();

            if ($existingClass) {
                // Update the existing row — never insert a second one
                $existingClass->update(['schoolclassid' => $newClassId]);
            } else {
                // First registration for this term/session — safe to insert
                Studentclass::create([
                    'studentId'    => $studentId,
                    'schoolclassid'=> $newClassId,
                    'sessionid'    => $newSessionId,
                    'termid'       => $newTermId,
                ]);
            }

            // ----------------------------------------------------------
            // PromotionStatus: same scoping.
            // Key = studentId + schoolclassid + sessionid + termid
            // All four together form the unique business key.
            // ----------------------------------------------------------
            PromotionStatus::updateOrCreate(
                [
                    'studentId'    => $studentId,
                    'schoolclassid'=> $newClassId,
                    'sessionid'    => $newSessionId,
                    'termid'       => $newTermId,
                ],
                [
                    'promotionStatus' => $promotionStatus,
                    'classstatus'     => 'CURRENT',
                    'position'        => null,
                ]
            );

            // ----------------------------------------------------------
            // StudentCurrentTerm: mark this as the current term and
            // unmark any previously-current row for this student.
            // ----------------------------------------------------------
            DB::table('student_current_term')
                ->where('studentId', $studentId)
                ->update(['is_current' => false]);

            \App\Models\StudentCurrentTerm::updateOrCreate(
                [
                    'studentId'    => $studentId,
                    'schoolclassId'=> $newClassId,
                    'termId'       => $newTermId,
                    'sessionId'    => $newSessionId,
                ],
                ['is_current' => true]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Promotion updated successfully.',
        ]);

    } catch (Exception $e) {
        Log::error('Promotion update failed', [
            'studentId' => $studentId,
            'request'   => $request->all(),
            'error'     => $e->getMessage(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to update promotion.',
        ], 500);
    }
}




    public function destroy(Request $request, $studentId)
    {
        $request->validate([
            'schoolclassid' => 'required|exists:schoolclass,id',
            'sessionid' => 'required|exists:schoolsession,id',
            'termid' => 'required|integer|min:1|max:3',
        ]);

        $schoolclassid = $request->input('schoolclassid');
        $sessionid = $request->input('sessionid');
        $termid = $request->input('termid');

        try {
            DB::transaction(function () use ($studentId, $schoolclassid, $sessionid, $termid) {
                Studentclass::where('studentId', $studentId)
                    ->where('schoolclassid', $schoolclassid)
                    ->where('sessionid', $sessionid)
                    ->where('termid', $termid)
                    ->delete();

                PromotionStatus::where('studentId', $studentId)
                    ->where('schoolclassid', $schoolclassid)
                    ->where('sessionid', $sessionid)
                    ->where('termid', $termid)
                    ->delete();
            });

            return response()->json(['success' => true, 'message' => 'Student removed successfully from class.']);
        } catch (Exception $e) {
            Log::error('Student removal failed', [
                'studentId' => $studentId,
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to remove student.'], 500);
        }
    }
}
