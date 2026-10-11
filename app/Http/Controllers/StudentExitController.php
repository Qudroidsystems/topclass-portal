<?php

namespace App\Http\Controllers;

use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Services\StudentExitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Former Students: record that students have left the school (Left,
 * Transferred, Graduated, Expelled), list them, and reactivate them.
 */
class StudentExitController extends Controller
{
    public function __construct(private StudentExitService $exits)
    {
        $this->middleware('permission:View student', ['only' => ['index', 'history']]);
        $this->middleware('permission:Update student|Update promotion', ['only' => ['store', 'reactivate']]);
    }

    public function index(Request $request): View
    {
        $pagetitle = 'Former Students';

        $query = DB::table('studentRegistration')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentRegistration.exit_class_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentRegistration.exit_session_id')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentRegistration.exit_term_id')
            ->leftJoin('users', 'users.id', '=', 'studentRegistration.exit_recorded_by')
            ->whereIn('studentRegistration.student_status', Student::EXIT_STATUSES);

        if (in_array($request->input('status'), Student::EXIT_STATUSES, true)) {
            $query->where('studentRegistration.student_status', $request->input('status'));
        }
        if ($request->filled('session_id')) {
            $query->where('studentRegistration.exit_session_id', $request->input('session_id'));
        }
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                  ->orWhere('studentRegistration.firstname', 'like', "%{$search}%")
                  ->orWhere('studentRegistration.lastname', 'like', "%{$search}%");
            });
        }

        $counts = DB::table('studentRegistration')
            ->whereIn('student_status', Student::EXIT_STATUSES)
            ->groupBy('student_status')
            ->selectRaw('student_status, COUNT(*) as cnt')
            ->pluck('cnt', 'student_status');

        $students = $query->orderByDesc('studentRegistration.exit_date')
            ->orderBy('studentRegistration.lastname')
            ->select([
                'studentRegistration.id',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.gender',
                'studentRegistration.student_status',
                'studentRegistration.exit_date',
                'studentRegistration.exit_reason',
                'studentRegistration.exit_destination',
                'studentpicture.picture',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolsession.session',
                'schoolterm.term',
                'users.name as recorded_by',
            ])
            ->paginate(50)
            ->appends($request->query());

        $schoolsessions = Schoolsession::orderByDesc('id')->get();
        $exitStatuses   = Student::EXIT_STATUSES;

        return view('student.former', compact('pagetitle', 'students', 'counts', 'schoolsessions', 'exitStatuses'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:studentRegistration,id',
            'status'        => 'required|in:' . implode(',', Student::EXIT_STATUSES),
            'session_id'    => 'required|exists:schoolsession,id',
            'term_id'       => 'required|exists:schoolterm,id',
            'exit_date'     => 'nullable|date',
            'reason'        => 'nullable|string|max:2000',
            'destination'   => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->exits->recordExit(
                $data['student_ids'],
                $data['status'],
                (int) $data['session_id'],
                (int) $data['term_id'],
                $data['exit_date'] ?? null,
                $data['reason'] ?? null,
                $data['destination'] ?? null,
                auth()->id()
            );
        } catch (Throwable $e) {
            Log::error('Recording student exit failed', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Could not record the change of status.'], 500);
        }

        $msg = "{$result['done']} student(s) marked as {$data['status']}.";
        if ($result['skipped']) {
            $msg .= ' ' . count($result['skipped']) . ' skipped.';
        }

        return response()->json(['success' => true, 'message' => $msg] + $result);
    }

    public function reactivate(Request $request, int $studentId): JsonResponse
    {
        $data = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            $this->exits->reactivate($studentId, $data['note'] ?? null, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('Reactivating student failed', ['studentId' => $studentId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not reactivate the student.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Student reactivated and their class enrolments restored.',
        ]);
    }

    public function history(int $studentId): JsonResponse
    {
        $rows = DB::table('student_status_history as h')
            ->leftJoin('users', 'users.id', '=', 'h.changed_by')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'h.effective_session_id')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'h.effective_term_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'h.last_class_id')
            ->where('h.student_id', $studentId)
            ->orderByDesc('h.id')
            ->get([
                'h.from_status', 'h.to_status', 'h.exit_date', 'h.reason', 'h.destination', 'h.created_at',
                'users.name as changed_by', 'schoolsession.session', 'schoolterm.term', 'schoolclass.schoolclass',
            ]);

        return response()->json(['success' => true, 'history' => $rows]);
    }

    /** Defaults for the "record a leaver" form: the school's current session and term. */
    public static function currentPoint(): array
    {
        return [
            'session_id' => Schoolsession::where('status', 'Current')->value('id'),
            'term_id'    => Schoolterm::where('status', true)->value('id'),
        ];
    }
}
