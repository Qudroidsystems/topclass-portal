<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records students leaving the school (Left / Transferred / Graduated /
 * Expelled) and brings them back.
 *
 * Leaving is effective from a session + term: the first term the student is
 * no longer in school. Their class enrolments from that point on (including a
 * promotion into a new class they never attended) are removed, so they drop
 * out of every class list, promotion list and bill that is built from
 * studentclass. Earlier terms are untouched, so past results and reports still
 * show them. The removed rows are kept in student_status_history and put back
 * by reactivate().
 *
 * Sessions are ordered by id (each new session gets a higher id).
 */
class StudentExitService
{
    /**
     * @return array{done:int, skipped:array<int,string>}
     */
    public function recordExit(
        array $studentIds,
        string $status,
        int $sessionId,
        int $termId,
        ?string $exitDate,
        ?string $reason,
        ?string $destination,
        ?int $userId
    ): array {
        if (!Student::isExitStatus($status)) {
            throw new InvalidArgumentException("{$status} is not a leaving status.");
        }

        $done    = 0;
        $skipped = [];

        foreach (array_unique(array_map('intval', $studentIds)) as $studentId) {
            $student = Student::find($studentId);
            if (!$student) {
                $skipped[$studentId] = 'Student not found';
                continue;
            }

            DB::transaction(function () use (
                $student, $status, $sessionId, $termId, $exitDate, $reason, $destination, $userId
            ) {
                $from = $student->student_status;

                // Last class actually attended: latest enrolment before the exit point.
                $lastClassId = DB::table('studentclass')
                    ->where('studentId', $student->id)
                    ->where(fn ($q) => $this->beforePoint($q, 'sessionid', 'termid', $sessionId, $termId))
                    ->orderByDesc(DB::raw('CAST(sessionid AS UNSIGNED)'))
                    ->orderByDesc(DB::raw('CAST(termid AS UNSIGNED)'))
                    ->value('schoolclassid');

                $archived = [
                    'studentclass'         => $this->takeFrom('studentclass', 'studentId', 'sessionid', 'termid', $student->id, $sessionId, $termId),
                    'promotionStatus'      => $this->takeFrom('promotionStatus', 'studentId', 'sessionid', 'termid', $student->id, $sessionId, $termId),
                    'student_current_term' => $this->takeFrom('student_current_term', 'studentId', 'sessionId', 'termId', $student->id, $sessionId, $termId),
                ];

                $student->forceFill([
                    'student_status'   => $status,
                    'exit_date'        => $exitDate,
                    'exit_reason'      => $reason,
                    'exit_destination' => $destination,
                    'exit_session_id'  => $sessionId,
                    'exit_term_id'     => $termId,
                    'exit_class_id'    => $lastClassId,
                    'exit_recorded_by' => $userId,
                ])->save();

                DB::table('student_status_history')->insert([
                    'student_id'           => $student->id,
                    'from_status'          => $from,
                    'to_status'            => $status,
                    'effective_session_id' => $sessionId,
                    'effective_term_id'    => $termId,
                    'last_class_id'        => $lastClassId,
                    'exit_date'            => $exitDate,
                    'reason'               => $reason,
                    'destination'          => $destination,
                    'archived_enrolments'  => json_encode($archived),
                    'changed_by'           => $userId,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            });

            $done++;
        }

        return ['done' => $done, 'skipped' => $skipped];
    }

    /**
     * Makes a leaver Active again and restores the class enrolments that were
     * removed when they left (rows that have been re-created since are kept).
     */
    public function reactivate(int $studentId, ?string $note, ?int $userId): void
    {
        $student = Student::findOrFail($studentId);
        if (!Student::isExitStatus($student->student_status)) {
            throw new InvalidArgumentException('This student has not left the school.');
        }

        DB::transaction(function () use ($student, $note, $userId) {
            $lastExit = DB::table('student_status_history')
                ->where('student_id', $student->id)
                ->where('to_status', $student->student_status)
                ->orderByDesc('id')
                ->first();

            $archived = $lastExit && $lastExit->archived_enrolments
                ? json_decode($lastExit->archived_enrolments, true)
                : [];

            $this->restore('studentclass', $archived['studentclass'] ?? [], ['studentId', 'sessionid', 'termid']);
            $this->restore('promotionStatus', $archived['promotionStatus'] ?? [], ['studentId', 'schoolclassid', 'sessionid', 'termid']);

            // student_current_term allows one current row per student, so only
            // the row that was current comes back, and only if none exists now.
            $wasCurrent = collect($archived['student_current_term'] ?? [])->first(fn ($r) => (bool) ($r['is_current'] ?? false));
            $hasCurrent = DB::table('student_current_term')
                ->where('studentId', $student->id)->where('is_current', true)->exists();
            if ($wasCurrent && !$hasCurrent) {
                $this->restore('student_current_term', [$wasCurrent], ['studentId', 'sessionId', 'termId']);
            }

            $from = $student->student_status;
            $student->forceFill([
                'student_status'   => 'Active',
                'exit_date'        => null,
                'exit_reason'      => null,
                'exit_destination' => null,
                'exit_session_id'  => null,
                'exit_term_id'     => null,
                'exit_class_id'    => null,
                'exit_recorded_by' => null,
            ])->save();

            DB::table('student_status_history')->insert([
                'student_id' => $student->id,
                'from_status' => $from,
                'to_status'  => 'Active',
                'reason'     => $note,
                'changed_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Hides leavers from a query for a given session/term. $query must join
     * studentRegistration. A leaver still shows for terms before they left.
     */
    public static function excludeLeavers(QueryBuilder|EloquentBuilder $query, int $sessionId, int $termId): QueryBuilder|EloquentBuilder
    {
        return $query->where(function ($q) use ($sessionId, $termId) {
            $q->whereNull('studentRegistration.student_status')
              ->orWhereNotIn('studentRegistration.student_status', Student::EXIT_STATUSES)
              ->orWhereNull('studentRegistration.exit_session_id')
              ->orWhere('studentRegistration.exit_session_id', '>', $sessionId)
              ->orWhere(function ($q2) use ($sessionId, $termId) {
                  $q2->where('studentRegistration.exit_session_id', $sessionId)
                     ->where('studentRegistration.exit_term_id', '>', $termId);
              });
        });
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function beforePoint($q, string $sessionCol, string $termCol, int $sessionId, int $termId): void
    {
        $q->whereRaw("CAST({$sessionCol} AS UNSIGNED) < ?", [$sessionId])
          ->orWhere(function ($q2) use ($sessionCol, $termCol, $sessionId, $termId) {
              $q2->whereRaw("CAST({$sessionCol} AS UNSIGNED) = ?", [$sessionId])
                 ->whereRaw("CAST({$termCol} AS UNSIGNED) < ?", [$termId]);
          });
    }

    /** Deletes the student's rows at/after the exit point and returns them. */
    private function takeFrom(string $table, string $studentCol, string $sessionCol, string $termCol, int $studentId, int $sessionId, int $termId): array
    {
        $query = DB::table($table)
            ->where($studentCol, $studentId)
            ->where(function ($q) use ($sessionCol, $termCol, $sessionId, $termId) {
                $q->whereRaw("CAST({$sessionCol} AS UNSIGNED) > ?", [$sessionId])
                  ->orWhere(function ($q2) use ($sessionCol, $termCol, $sessionId, $termId) {
                      $q2->whereRaw("CAST({$sessionCol} AS UNSIGNED) = ?", [$sessionId])
                         ->whereRaw("CAST({$termCol} AS UNSIGNED) >= ?", [$termId]);
                  });
            });

        $rows = (clone $query)->get()->map(fn ($r) => (array) $r)->all();
        if ($rows) {
            $query->delete();
        }

        return $rows;
    }

    private function restore(string $table, array $rows, array $matchCols): void
    {
        foreach ($rows as $row) {
            $exists = DB::table($table);
            foreach ($matchCols as $col) {
                $exists->where($col, $row[$col] ?? null);
            }
            if ($exists->exists()) {
                continue;
            }
            unset($row['id']);
            DB::table($table)->insert($row);
        }
    }
}
