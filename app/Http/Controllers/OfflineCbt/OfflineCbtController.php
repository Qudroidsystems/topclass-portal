<?php

namespace App\Http\Controllers\OfflineCbt;

use App\Http\Controllers\Controller;
use App\Services\OfflineCbt\AttemptImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Portal page: CBT → Offline CBT.
 * Device keys for the desktop app, student exam PINs, and the sync log
 * (including conflicts to accept or discard).
 */
class OfflineCbtController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage offline-cbt');
    }

    public function index(Request $request)
    {
        $pagetitle = 'Offline CBT';

        $devices = DB::table('offline_cbt_devices')->leftJoin('users', 'users.id', '=', 'offline_cbt_devices.created_by')
            ->orderByRaw('revoked_at IS NOT NULL')->orderByDesc('offline_cbt_devices.id')
            ->get(['offline_cbt_devices.*', 'users.name as created_by_name']);

        $syncs = DB::table('offline_cbt_syncs as s')
            ->leftJoin('exams', 'exams.id', '=', 's.exam_id')
            ->leftJoin('users', 'users.id', '=', 's.user_id')
            ->leftJoin('studentRegistration as sr', 'sr.id', '=', 'users.student_id')
            ->leftJoin('offline_cbt_devices as d', 'd.id', '=', 's.device_id')
            ->when($request->filled('status'), fn ($q) => $q->where('s.status', $request->status))
            ->when($request->filled('exam'), fn ($q) => $q->where('s.exam_id', (int) $request->exam))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%' . trim($request->q) . '%';
                $q->where(fn ($w) => $w->where('sr.firstname', 'like', $like)->orWhere('sr.lastname', 'like', $like)
                    ->orWhere('sr.admissionNo', 'like', $like)->orWhere('exams.title', 'like', $like));
            })
            ->orderByRaw("s.status = 'conflict' DESC")->orderByDesc('s.id')
            ->select('s.*', 'exams.title as exam_title', 'd.name as device_name',
                DB::raw("TRIM(CONCAT(COALESCE(sr.lastname,''),' ',COALESCE(sr.firstname,''))) as student_name"), 'sr.admissionNo')
            ->paginate(25)->withQueryString();

        $counts = DB::table('offline_cbt_syncs')->select('status', DB::raw('COUNT(*) as n'))->groupBy('status')->pluck('n', 'status');
        $examsWithSyncs = DB::table('offline_cbt_syncs')->join('exams', 'exams.id', '=', 'offline_cbt_syncs.exam_id')
            ->distinct()->orderBy('exams.title')->get(['exams.id', 'exams.title']);

        $classes = DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->orderBy('schoolclass.schoolclass')
            ->get(['schoolclass.id', DB::raw("TRIM(CONCAT(schoolclass.schoolclass,' ',COALESCE(schoolarm.arm,''))) as label")]);
        $sessions = DB::table('schoolsession')->orderByDesc('id')->get(['id', 'session', 'status']);
        $terms    = DB::table('schoolterm')->orderBy('id')->get(['id', 'term', 'status']);
        $pinCount = DB::table('offline_cbt_pins')->count();

        $newKey = session('offline_cbt_new_key');   // shown once after creating a device

        $latestVersion = (string) config('offlinecbt.latest_version');
        $downloadUrl   = (string) config('offlinecbt.download_url');

        return view('offlinecbt.index', compact('pagetitle', 'devices', 'syncs', 'counts', 'examsWithSyncs',
            'classes', 'sessions', 'terms', 'pinCount', 'newKey', 'latestVersion', 'downloadUrl'));
    }

    public function storeDevice(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        $key  = 'ocbt_' . Str::random(40);
        DB::table('offline_cbt_devices')->insert([
            'name'       => $data['name'],
            'token_hash' => hash('sha256', $key),
            'token_hint' => substr($key, 0, 10),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('offlinecbt.index')
            ->with('offline_cbt_new_key', ['name' => $data['name'], 'key' => $key])
            ->with('success', 'Device key created. Copy it now — it will not be shown again.');
    }

    public function revokeDevice($id)
    {
        DB::table('offline_cbt_devices')->where('id', $id)->update(['revoked_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Device key revoked. That computer can no longer download exams or upload results.');
    }

    /** Students in a class for a term/session (with a portal login). */
    private function classStudents(int $classId, int $termId, int $sessionId)
    {
        return DB::table('studentclass')
            ->join('studentRegistration as sr', 'sr.id', '=', 'studentclass.studentId')
            ->join('users', 'users.student_id', '=', 'sr.id')
            ->leftJoin('offline_cbt_pins as p', 'p.user_id', '=', 'users.id')
            ->where('studentclass.schoolclassid', $classId)
            ->where('studentclass.termid', $termId)
            ->where('studentclass.sessionid', $sessionId)
            ->orderBy('sr.lastname')->orderBy('sr.firstname')
            ->distinct()
            ->get(['users.id as user_id', 'sr.admissionNo', 'sr.firstname', 'sr.lastname', 'sr.othername', 'p.pin']);
    }

    /** Generate PINs for a class (only students without one, unless "regenerate"). */
    public function generatePins(Request $request)
    {
        $data = $request->validate([
            'class_id'   => 'required|integer',
            'term_id'    => 'required|integer',
            'session_id' => 'required|integer',
            'regenerate' => 'nullable|boolean',
        ]);
        $students = $this->classStudents($data['class_id'], $data['term_id'], $data['session_id']);
        $made = 0;
        foreach ($students as $s) {
            if ($s->pin && empty($data['regenerate'])) continue;
            DB::table('offline_cbt_pins')->updateOrInsert(
                ['user_id' => $s->user_id],
                ['pin' => $this->newPin(), 'generated_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]
            );
            $made++;
        }
        return redirect()->route('offlinecbt.pins.print', $data)
            ->with('success', $made . ' PIN(s) generated. Download the exam package again so the desktop app gets the new PINs.');
    }

    /** Printable PIN slips for a class. */
    public function printPins(Request $request)
    {
        $classId = (int) $request->class_id; $termId = (int) $request->term_id; $sessionId = (int) $request->session_id;
        $students  = $this->classStudents($classId, $termId, $sessionId);
        $className = DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('schoolclass.id', $classId)
            ->value(DB::raw("TRIM(CONCAT(schoolclass.schoolclass,' ',COALESCE(schoolarm.arm,'')))"));
        $school = DB::table('school_information')->value('school_name');
        return view('offlinecbt.pins', compact('students', 'className', 'school'));
    }

    public function resolve(Request $request, $id, AttemptImporter $importer)
    {
        $accept = $request->input('decision') === 'accept';
        $msg = $importer->resolve((int) $id, $accept, (int) auth()->id());
        return back()->with('success', $msg);
    }

    public function showSync($id)
    {
        $row = DB::table('offline_cbt_syncs')->where('id', $id)->first();
        abort_unless($row, 404);
        $questions = DB::table('questions')->where('exam_id', $row->exam_id)->orderBy('order')->orderBy('id')
            ->get(['id', 'question_text', 'type']);
        $answers = collect(json_decode($row->answers, true) ?: [])->keyBy('question_id');
        return response()->json([
            'success' => true,
            'answers' => $questions->map(fn ($q) => [
                'question' => strip_tags((string) $q->question_text),
                'answer'   => $answers[$q->id]['answer'] ?? null,
            ]),
        ]);
    }

    private function newPin(): string
    {
        // 6 characters, no look-alikes (0/O, 1/I/L)
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $pin = '';
        for ($i = 0; $i < 6; $i++) $pin .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        return $pin;
    }
}
