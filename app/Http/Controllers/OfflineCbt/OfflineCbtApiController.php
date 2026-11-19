<?php

namespace App\Http\Controllers\OfflineCbt;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\OfflineCbt\AttemptImporter;
use App\Services\OfflineCbt\PackageBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API used by the offline CBT desktop app (admin / server computer).
 * All routes: /api/offline-cbt/v1/...   Auth: device key (OfflineCbtDevice).
 */
class OfflineCbtApiController extends Controller
{
    /** GET /ping — check the key and get basic school info + server time. */
    public function ping(Request $request)
    {
        $device = $request->attributes->get('offline_cbt_device');
        $school = DB::table('school_information')->first();
        return response()->json([
            'success'     => true,
            'device'      => ['id' => $device->id, 'name' => $device->name],
            'school'      => $school->school_name ?? '',
            'server_time' => now()->toIso8601String(),
            'api_version' => 1,
            'latest_app_version' => config('offlinecbt.latest_version'),
            'download_url'       => config('offlinecbt.download_url'),
        ]);
    }

    /** GET /exams?session_id=&term_id=&search= — exams the admin can pick to download. */
    public function exams(Request $request)
    {
        $sessionId = $request->integer('session_id') ?: DB::table('schoolsession')->where('status', 'Current')->value('id');
        $termId    = $request->integer('term_id') ?: DB::table('schoolterm')->where('status', true)->value('id');

        $q = Exam::query()
            ->leftJoin('subject', 'subject.id', '=', 'exams.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'exams.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->when($sessionId, fn ($w) => $w->where('exams.session', $sessionId))
            ->when($termId, fn ($w) => $w->where('exams.termid', $termId))
            ->withCount('questions')
            ->select('exams.id', 'exams.title', 'exams.duration', 'exams.start_time', 'exams.end_time',
                     'exams.is_published', 'subject.subject as subject_name',
                     DB::raw("TRIM(CONCAT(COALESCE(schoolclass.schoolclass,''),' ',COALESCE(schoolarm.arm,''))) as class_name"))
            ->orderBy('exams.start_time');

        if ($s = trim((string) $request->query('search'))) {
            $q->where(fn ($w) => $w->where('exams.title', 'like', "%$s%")->orWhere('subject.subject', 'like', "%$s%")
                ->orWhere('schoolclass.schoolclass', 'like', "%$s%"));
        }

        return response()->json([
            'success'  => true,
            'session'  => DB::table('schoolsession')->where('id', $sessionId)->first(['id', 'session as name']),
            'term'     => DB::table('schoolterm')->where('id', $termId)->first(['id', 'term as name']),
            'sessions' => DB::table('schoolsession')->orderByDesc('id')->get(['id', 'session as name', 'status']),
            'terms'    => DB::table('schoolterm')->orderBy('id')->get(['id', 'term as name']),
            'exams'    => $q->get(),
        ]);
    }

    /** POST /package {exam_ids: [...]} — download the frozen exam package. */
    public function package(Request $request, PackageBuilder $builder)
    {
        $data = $request->validate([
            'exam_ids'          => 'required|array|min:1|max:200',
            'exam_ids.*'        => 'integer|exists:exams,id',
            'include_passwords' => 'nullable|boolean',
        ]);
        $device = $request->attributes->get('offline_cbt_device');

        // Schools can choose PIN-only login: then no password hashes leave the portal.
        $withPasswords = $request->has('include_passwords') ? $request->boolean('include_passwords') : true;
        $package = $builder->build(array_map('intval', $data['exam_ids']), (int) $device->id, $withPasswords);
        return response()->json(['success' => true, 'package' => $package]);
    }

    /**
     * POST /attempts — upload finished attempts (batches of up to 200).
     * Body: {attempts: [{attempt_uuid, package_uuid, exam_id, user_id, started_at,
     *                    submitted_at, duration_seconds, workstation, answers: [{question_id, answer}]}]}
     * Returns one result per attempt; safe to repeat.
     */
    public function upload(Request $request, AttemptImporter $importer)
    {
        $data = $request->validate([
            'attempts'   => 'required|array|min:1|max:200',
            'attempts.*' => 'array',
        ]);
        $device = $request->attributes->get('offline_cbt_device');

        $results = [];
        foreach ($data['attempts'] as $a) {
            $results[] = $importer->import($a, (int) $device->id);
        }

        $counts = array_count_values(array_column($results, 'status'));
        return response()->json(['success' => true, 'counts' => $counts, 'results' => $results]);
    }

    /** POST /attempts/status {attempt_uuids: [...]} — what the server has for these attempts. */
    public function status(Request $request)
    {
        $data = $request->validate(['attempt_uuids' => 'required|array|max:1000', 'attempt_uuids.*' => 'string']);
        $rows = DB::table('offline_cbt_syncs')->whereIn('attempt_uuid', $data['attempt_uuids'])
            ->get(['attempt_uuid', 'status', 'message', 'score', 'total_marks']);
        return response()->json(['success' => true, 'results' => $rows]);
    }
}
