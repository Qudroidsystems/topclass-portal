<?php

namespace App\Services\OfflineCbt;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Result;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Imports attempts uploaded by the offline CBT app.
 *
 *  - Idempotent: every attempt carries a UUID. Uploading it again returns the
 *    first outcome without touching anything (the admin can press Sync twice).
 *  - Never overwrites: if the student already has an attempt for that exam
 *    (taken online, or another offline copy), the upload is stored as a
 *    "conflict" for an admin to accept or discard from the Offline CBT page.
 *  - Marks with CbtMarker, the same code as the online submit, using the
 *    questions currently on the server.
 */
class AttemptImporter
{
    public const SYNCED    = 'synced';
    public const CONFLICT  = 'conflict';
    public const REJECTED  = 'rejected';
    public const ACCEPTED  = 'conflict_accepted';
    public const DISCARDED = 'conflict_discarded';

    /** @return array{attempt_uuid: string, status: string, message: ?string, score: ?float, total_marks: ?float} */
    public function import(array $a, int $deviceId): array
    {
        $uuid = (string) ($a['attempt_uuid'] ?? '');

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            return $this->out($uuid, self::REJECTED, 'Missing or invalid attempt_uuid.');
        }

        // Already received? Return the original outcome (idempotent).
        $prev = DB::table('offline_cbt_syncs')->where('attempt_uuid', $uuid)->first();
        if ($prev) {
            return $this->out($uuid, $prev->status, 'Already received ' . Carbon::parse($prev->created_at)->diffForHumans() . '.',
                $prev->score, $prev->total_marks, true);
        }

        $examId = (int) ($a['exam_id'] ?? 0);
        $userId = (int) ($a['user_id'] ?? 0);
        $answers = array_values(array_filter((array) ($a['answers'] ?? []), 'is_array'));

        $exam = Exam::with(['questions.options'])->find($examId);
        $userOk = $userId && DB::table('users')->where('id', $userId)->exists();

        $startedAt   = $this->time($a['started_at'] ?? null);
        $submittedAt = $this->time($a['submitted_at'] ?? null) ?? now();

        $base = [
            'attempt_uuid'     => $uuid,
            'device_id'        => $deviceId,
            'package_uuid'     => $a['package_uuid'] ?? null,
            'exam_id'          => $examId,
            'user_id'          => $userId,
            'started_at'       => $startedAt,
            'submitted_at'     => $submittedAt,
            'duration_seconds' => isset($a['duration_seconds']) ? (int) $a['duration_seconds'] : null,
            'workstation'      => isset($a['workstation']) ? mb_substr((string) $a['workstation'], 0, 100) : null,
            'focus_lost'       => max(0, (int) ($a['focus_lost'] ?? 0)),
            'answers'          => json_encode($answers),
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        if (!$exam || !$userOk) {
            $msg = !$exam ? "Exam #{$examId} no longer exists online." : "Student user #{$userId} not found online.";
            DB::table('offline_cbt_syncs')->insert($base + ['status' => self::REJECTED, 'message' => $msg]);
            return $this->out($uuid, self::REJECTED, $msg);
        }

        $marked = CbtMarker::mark($exam, $answers);
        $base += [
            'score'          => $marked['score'],
            'total_marks'    => $marked['total_marks'],
            'attempted'      => $marked['attempted'],
            'outside_window' => $this->outsideWindow($exam, $startedAt ?? $submittedAt),
        ];

        try {
            return DB::transaction(function () use ($exam, $userId, $marked, $base, $uuid, $startedAt, $submittedAt) {
                // Lock this student's attempts for the exam while we check & write.
                $existing = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $userId)->lockForUpdate()->first();

                if ($existing) {
                    $msg = 'Student already has an attempt for this exam (' . ($existing->status ?? 'unknown')
                         . ', ' . optional($existing->start_time)->format('d M Y H:i') . '). Kept for review.';
                    DB::table('offline_cbt_syncs')->insert($base + ['status' => self::CONFLICT, 'message' => $msg]);
                    return $this->out($uuid, self::CONFLICT, $msg, $marked['score'], $marked['total_marks']);
                }

                $attemptId = $this->write($exam, $userId, $marked, $startedAt, $submittedAt);
                DB::table('offline_cbt_syncs')->insert($base + [
                    'status'          => self::SYNCED,
                    'message'         => $base['outside_window'] ? 'Taken outside the online exam window.' : null,
                    'exam_attempt_id' => $attemptId,
                ]);
                return $this->out($uuid, self::SYNCED, null, $marked['score'], $marked['total_marks']);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Two uploads of the same UUID at the same moment: the unique key stops the second.
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $this->out($uuid, self::SYNCED, 'Already received.', null, null, true);
            }
            Log::error('Offline CBT import failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return $this->out($uuid, 'error', 'Server error while saving; it will be retried.');
        }
    }

    /**
     * Admin decision on a conflict. Accept = the offline attempt replaces the
     * student's existing attempt, answers and result. Discard = keep online.
     */
    public function resolve(int $syncId, bool $accept, int $adminId): string
    {
        return DB::transaction(function () use ($syncId, $accept, $adminId) {
            $row = DB::table('offline_cbt_syncs')->where('id', $syncId)->lockForUpdate()->first();
            if (!$row || $row->status !== self::CONFLICT) return 'This record is not an open conflict.';

            if (!$accept) {
                DB::table('offline_cbt_syncs')->where('id', $syncId)->update([
                    'status' => self::DISCARDED, 'resolved_by' => $adminId, 'resolved_at' => now(), 'updated_at' => now(),
                ]);
                return 'Offline attempt discarded; the existing attempt was kept.';
            }

            $exam = Exam::with(['questions.options'])->findOrFail($row->exam_id);
            Answer::where('exam_id', $row->exam_id)->where('user_id', $row->user_id)->delete();
            Result::where('exam_id', $row->exam_id)->where('user_id', $row->user_id)->delete();
            ExamAttempt::where('exam_id', $row->exam_id)->where('student_id', $row->user_id)->delete();

            $marked    = CbtMarker::mark($exam, json_decode($row->answers, true) ?: []);
            $attemptId = $this->write($exam, (int) $row->user_id, $marked,
                $row->started_at ? Carbon::parse($row->started_at) : null, Carbon::parse($row->submitted_at));

            DB::table('offline_cbt_syncs')->where('id', $syncId)->update([
                'status' => self::ACCEPTED, 'exam_attempt_id' => $attemptId,
                'score' => $marked['score'], 'total_marks' => $marked['total_marks'], 'attempted' => $marked['attempted'],
                'resolved_by' => $adminId, 'resolved_at' => now(), 'updated_at' => now(),
            ]);
            return 'Offline attempt accepted and now replaces the earlier attempt.';
        });
    }

    /** Write the attempt exactly as an online submit would. */
    private function write(Exam $exam, int $userId, array $marked, ?Carbon $startedAt, Carbon $submittedAt): int
    {
        $attempt = ExamAttempt::create([
            'student_id' => $userId,
            'exam_id'    => $exam->id,
            'start_time' => $startedAt ?? $submittedAt,
            'end_time'   => $submittedAt,
            'status'     => 'completed',
        ]);

        foreach ($marked['rows'] as $row) {
            Answer::create([
                'user_id'      => $userId,
                'exam_id'      => $exam->id,
                'question_id'  => $row['question_id'],
                'option_id'    => $row['option_id'],
                'short_answer' => $row['short_answer'],
            ]);
        }

        Result::updateOrCreate(
            ['user_id' => $userId, 'exam_id' => $exam->id],
            ['score' => $marked['score'], 'total_marks' => $marked['total_marks']]
        );

        return $attempt->id;
    }

    private function outsideWindow(Exam $exam, ?Carbon $at): bool
    {
        if (!$at || !$exam->start_time || !$exam->end_time) return false;
        return !$at->between(Carbon::parse($exam->start_time)->subHours(12), Carbon::parse($exam->end_time)->addHours(12));
    }

    private function time($v): ?Carbon
    {
        if (!$v) return null;
        try { return Carbon::parse($v)->setTimezone(config('app.timezone')); } catch (\Throwable $e) { return null; }
    }

    private function out(string $uuid, string $status, ?string $message, $score = null, $total = null, bool $duplicate = false): array
    {
        return [
            'attempt_uuid' => $uuid,
            'status'       => $status,
            'duplicate'    => $duplicate,
            'message'      => $message,
            'score'        => $score !== null ? (float) $score : null,
            'total_marks'  => $total !== null ? (float) $total : null,
        ];
    }
}
