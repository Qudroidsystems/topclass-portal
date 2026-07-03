<?php

namespace App\Http\Controllers;

use App\Models\BroadsheetsMock;
use App\Models\MockSubjectVetting;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subjectclass;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MockSubjectVettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View mock-subject-vettings|Create mock-subject-vettings|Update mock-subject-vettings|Delete mock-subject-vettings', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create mock-subject-vettings', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update mock-subject-vettings', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete mock-subject-vettings', ['only' => ['destroy']]);
    }

    /**
     * Search subject classes via AJAX with term colors
     */
    public function searchSubjectClasses(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $excludeIds = $request->get('exclude_ids', []);

            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $subjectClasses = Subjectclass::select(
                    'subjectclass.id',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'schoolclass.schoolclass as sclass',
                    'schoolarm.arm as schoolarm',
                    'users.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname'
                )
                ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->where(function($q) use ($query) {
                    $q->where('subject.subject', 'LIKE', "%{$query}%")
                      ->orWhere('subject.subject_code', 'LIKE', "%{$query}%")
                      ->orWhere('schoolclass.schoolclass', 'LIKE', "%{$query}%")
                      ->orWhere('schoolarm.arm', 'LIKE', "%{$query}%")
                      ->orWhere('users.name', 'LIKE', "%{$query}%")
                      ->orWhere('schoolsession.session', 'LIKE', "%{$query}%")
                      ->orWhere('schoolterm.term', 'LIKE', "%{$query}%");
                })
                ->when(!empty($excludeIds), function($q) use ($excludeIds) {
                    if (is_array($excludeIds)) {
                        $q->whereNotIn('subjectclass.id', $excludeIds);
                    } elseif (str_contains($excludeIds, ',')) {
                        $ids = explode(',', $excludeIds);
                        $q->whereNotIn('subjectclass.id', $ids);
                    } else {
                        $q->where('subjectclass.id', '!=', $excludeIds);
                    }
                })
                ->orderBy('schoolterm.id')
                ->orderBy('subject.subject')
                ->limit(30)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $subjectClasses
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching subject classes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get selected subject classes details
     */
    public function getSelectedSubjectClasses(Request $request)
    {
        try {
            $ids = $request->get('ids', []);
            if (empty($ids)) {
                return response()->json([]);
            }

            $idsArray = is_array($ids) ? $ids : explode(',', $ids);

            $subjectClasses = Subjectclass::select(
                    'subjectclass.id',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'schoolclass.schoolclass as sclass',
                    'schoolarm.arm as schoolarm',
                    'users.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname'
                )
                ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->whereIn('subjectclass.id', $idsArray)
                ->get();

            return response()->json($subjectClasses);

        } catch (\Exception $e) {
            Log::error('Error getting selected subject classes: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'userid' => 'required|exists:users,id',
                'termid.*' => 'required|exists:schoolterm,id',
                'sessionid' => 'required|exists:schoolsession,id',
                'subjectclassid' => 'required|array',
                'subjectclassid.*' => 'required|exists:subjectclass,id',
            ], [
                'userid.required' => 'Please select a staff member!',
                'termid.*.required' => 'Please select at least one term!',
                'sessionid.required' => 'Please select a session!',
                'subjectclassid.required' => 'Please select at least one subject-class!',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('userid');
            $termIds = $request->input('termid', []);
            $sessionId = $request->input('sessionid');
            $subjectClassIds = array_unique($request->input('subjectclassid', []));

            if (empty($termIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one term.'
                ], 422);
            }

            if (empty($subjectClassIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one subject-class.'
                ], 422);
            }

            // Check if the vetting staff is the same as the subject teacher
            $subjectClasses = Subjectclass::whereIn('subjectclass.id', $subjectClassIds)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->pluck('subjectteacher.staffid')
                ->toArray();

            if (in_array($userId, $subjectClasses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected staff member cannot vet their own subject-class assignment.'
                ], 422);
            }

            // Check for existing assignments
            $existingAssignments = MockSubjectVetting::whereIn('subjectclassId', $subjectClassIds)
                ->whereIn('termid', $termIds)
                ->where('sessionid', $sessionId)
                ->pluck('subjectclassId')
                ->toArray();

            if (!empty($existingAssignments)) {
                $assignedSubjectClasses = Subjectclass::whereIn('subjectclass.id', array_unique($existingAssignments))
                    ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                    ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                    ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                    ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                    ->get(['subject.subject as subjectname', 'schoolclass.schoolclass as sclass', 'schoolarm.arm as schoolarm'])
                    ->map(function ($sc) {
                        return "{$sc->subjectname} - {$sc->sclass} ({$sc->schoolarm})";
                    })->toArray();

                return response()->json([
                    'success' => false,
                    'message' => 'The following subject-classes are already assigned: ' . implode(', ', $assignedSubjectClasses)
                ], 422);
            }

            $createdRecords = [];
            foreach ($termIds as $termId) {
                foreach ($subjectClassIds as $subjectClassId) {
                    $mockSubjectVetting = MockSubjectVetting::create([
                        'userid' => $userId,
                        'subjectclassId' => $subjectClassId,  // Note: capital I
                        'termid' => $termId,
                        'sessionid' => $sessionId,
                        'status' => 'pending',
                    ]);
                    $createdRecords[] = $mockSubjectVetting;
                }
            }

            if (empty($createdRecords)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No new mock subject vetting assignments were created.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => count($createdRecords) . ' Mock Subject Vetting assignment(s) added successfully.',
                'data' => $createdRecords
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error storing mock subject vetting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding mock subject vetting assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $pagetitle = "Mock Subject Vetting Management";

            $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->get(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
                ->sortBy('schoolclass');

            $subjectclasses = collect();

            $staff = User::get(['id', 'name', 'avatar'])->sortBy('name');
            $terms = Schoolterm::get(['id', 'term'])->sortBy('term');
            $sessions = Schoolsession::get(['id', 'session'])->sortBy('session');

            $mocksubjectvettings = MockSubjectVetting::leftJoin('subjectclass', 'mock_subject_vettings.subjectclassId', '=', 'subjectclass.id')
                ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users as vetting_user', 'mock_subject_vettings.userid', '=', 'vetting_user.id')
                ->leftJoin('users as teacher_user', 'subjectteacher.staffid', '=', 'teacher_user.id')
                ->leftJoin('schoolterm', 'mock_subject_vettings.termid', '=', 'schoolterm.id')
                ->leftJoin('schoolsession', 'mock_subject_vettings.sessionid', '=', 'schoolsession.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select([
                    'mock_subject_vettings.id as svid',
                    'mock_subject_vettings.userid as vetting_userid',
                    'vetting_user.name as vetting_username',
                    'vetting_user.avatar as vetting_picture',
                    'subjectclass.id as subjectclassid',
                    'schoolclass.id as schoolclassid',
                    'schoolclass.schoolclass as sclass',
                    'schoolarm.arm as schoolarm',
                    'subjectteacher.staffid as subtid',
                    'subject.id as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'teacher_user.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'mock_subject_vettings.status',
                    'mock_subject_vettings.updated_at'
                ])
                ->orderBy('vetting_username')
                ->get();

            $statusCounts = MockSubjectVetting::groupBy('status')
                ->selectRaw('status, COUNT(*) as count')
                ->pluck('count', 'status')
                ->toArray();

            $statusCounts = array_merge([
                'pending' => 0,
                'completed' => 0,
                'rejected' => 0
            ], $statusCounts);

            return view('mocksubjectvetting.index')
                ->with('mocksubjectvettings', $mocksubjectvettings)
                ->with('schoolclasses', $schoolclasses)
                ->with('subjectclasses', $subjectclasses)
                ->with('staff', $staff)
                ->with('terms', $terms)
                ->with('sessions', $sessions)
                ->with('pagetitle', $pagetitle)
                ->with('statusCounts', $statusCounts);

        } catch (\Exception $e) {
            Log::error('Error loading mock subject vetting index: ' . $e->getMessage());
            return view('mocksubjectvetting.index')
                ->with('mocksubjectvettings', collect([]))
                ->with('schoolclasses', collect([]))
                ->with('subjectclasses', collect([]))
                ->with('staff', collect([]))
                ->with('terms', collect([]))
                ->with('sessions', collect([]))
                ->with('pagetitle', 'Mock Subject Vetting Management')
                ->with('statusCounts', ['pending' => 0, 'completed' => 0, 'rejected' => 0])
                ->with('danger', 'Failed to load data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'userid' => 'required|exists:users,id',
                'subjectclassid' => 'required|exists:subjectclass,id',
                'termid' => 'required|exists:schoolterm,id',
                'sessionid' => 'required|exists:schoolsession,id',
                'status' => 'required|in:pending,completed,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $mockSubjectVetting = MockSubjectVetting::find($id);
            if (!$mockSubjectVetting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mock Subject Vetting assignment not found.'
                ], 404);
            }

            $mockSubjectVetting->update([
                'userid' => $request->input('userid'),
                'subjectclassId' => $request->input('subjectclassid'),  // Note: capital I
                'termid' => $request->input('termid'),
                'sessionid' => $request->input('sessionid'),
                'status' => $request->input('status'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mock Subject Vetting assignment updated successfully.',
                'data' => $mockSubjectVetting
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating mock subject vetting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $mockSubjectVetting = MockSubjectVetting::find($id);
            if (!$mockSubjectVetting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mock Subject Vetting assignment not found.'
                ], 404);
            }

            DB::transaction(function () use ($mockSubjectVetting) {
                BroadsheetsMock::where('vettedby', $mockSubjectVetting->userid)
                    ->where('subjectclass_id', $mockSubjectVetting->subjectclassId)
                    ->where('term_id', $mockSubjectVetting->termid)
                    ->update([
                        'vettedby' => null,
                        'vettedstatus' => null
                    ]);

                $mockSubjectVetting->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Mock Subject Vetting assignment deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting mock subject vetting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting: ' . $e->getMessage()
            ], 500);
        }
    }


    public function bulkDelete(Request $request)
{
    try {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No records selected for deletion.'
            ], 422);
        }

        DB::transaction(function () use ($ids) {
            $vettings = MockSubjectVetting::whereIn('id', $ids)->get();

            foreach ($vettings as $vetting) {
                // Update related broadsheets if they exist
                BroadsheetsMock::where('vettedby', $vetting->userid)
                    ->where('subjectclass_id', $vetting->subjectclassId)
                    ->where('term_id', $vetting->termid)
                    ->update([
                        'vettedby' => null,
                        'vettedstatus' => null
                    ]);
            }

            MockSubjectVetting::whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => count($ids) . ' record(s) deleted successfully.'
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error in bulk delete: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error deleting records: ' . $e->getMessage()
        ], 500);
    }
}
}
