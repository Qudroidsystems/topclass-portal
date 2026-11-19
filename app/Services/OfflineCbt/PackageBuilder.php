<?php

namespace App\Services\OfflineCbt;

use App\Models\Exam;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the "exam package" the offline CBT app downloads before an exam:
 * school details, the exams with their questions frozen as they are now,
 * and every student allowed to take each exam (with login credentials).
 *
 * Eligibility follows the online CBT list (CBTController::index): the student
 * is in the exam's class for the exam's term & session, and is registered
 * for the exam's subject.
 */
class PackageBuilder
{
    public function build(array $examIds, int $deviceId, bool $includePasswords = true): array
    {
        $exams = Exam::with(['questions' => fn ($q) => $q->orderBy('order')->orderBy('id'),
                             'questions.options' => fn ($q) => $q->orderBy('id'),
                             'subject:id,subject',
                             'schoolclass:id,schoolclass,arm'])
            ->whereIn('id', $examIds)
            ->get();

        $examOut  = [];
        $students = [];   // users.id => student row

        foreach ($exams as $exam) {
            $questions = $exam->questions->map(function ($q) {
                return [
                    'id'      => $q->id,
                    'text'    => $q->question_text,
                    'type'    => $q->type,               // mcq | true_false | short_answer
                    'marks'   => (float) ($q->marks ?? 1),
                    'order'   => (int) ($q->order ?? 0),
                    'image'   => $this->imageData($q->image),
                    'options' => $q->options->map(fn ($o) => [
                        'id'         => $o->id,
                        'label'      => $o->label,
                        'text'       => $o->option_text,
                        'is_correct' => (bool) $o->is_correct,
                    ])->values()->all(),
                ];
            })->values()->all();

            $armName = $exam->schoolclass && $exam->schoolclass->arm
                ? DB::table('schoolarm')->where('id', $exam->schoolclass->arm)->value('arm') : null;

            $examOut[] = [
                'id'            => $exam->id,
                'title'         => $exam->title,
                'description'   => $exam->description,
                'duration'      => (int) $exam->duration,          // minutes
                'start_time'    => optional($exam->start_time)->toIso8601String(),
                'end_time'      => optional($exam->end_time)->toIso8601String(),
                'term_id'       => (int) $exam->termid,
                'session_id'    => (int) $exam->session,
                'is_published'  => (bool) $exam->is_published,
                'subject'       => ['id' => (int) $exam->subject_id, 'name' => $exam->subject->subject ?? ''],
                'class'         => ['id' => (int) $exam->schoolclass_id,
                                    'name' => trim(($exam->schoolclass->schoolclass ?? '') . ' ' . ($armName ?? ''))],
                'total_marks'   => (float) $exam->questions->sum('marks'),
                'question_count'=> count($questions),
                'questions'     => $questions,
            ];

            foreach ($this->eligibleStudents($exam) as $s) {
                $uid = (int) $s->user_id;
                if (!isset($students[$uid])) {
                    $students[$uid] = [
                        'user_id'        => $uid,
                        'student_id'     => (int) $s->student_id,
                        'admission_no'   => (string) $s->admissionNo,
                        'firstname'      => (string) $s->firstname,
                        'lastname'       => (string) $s->lastname,
                        'othername'      => (string) ($s->othername ?? ''),
                        'class_name'     => $examOut[count($examOut) - 1]['class']['name'],
                        'exam_ids'       => [],
                        'done_online'    => [],      // exams already attempted online
                        'password_hash'  => $includePasswords ? $s->password : null,
                        'pin'            => null,
                        'photo'          => $this->photo($s->picture ?? null),
                    ];
                }
                $students[$uid]['exam_ids'][] = $exam->id;
            }
        }

        if ($students) {
            $uids = array_keys($students);
            $done = DB::table('exam_attempts')->whereIn('student_id', $uids)->whereIn('exam_id', $examIds)
                ->get(['student_id', 'exam_id']);
            foreach ($done as $d) $students[(int) $d->student_id]['done_online'][] = (int) $d->exam_id;

            $pins = DB::table('offline_cbt_pins')->whereIn('user_id', $uids)->pluck('pin', 'user_id');
            foreach ($pins as $uid => $pin) $students[(int) $uid]['pin'] = $pin;
        }

        $school  = SchoolInformation::first();
        $session = Schoolsession::where('status', 'Current')->first();
        $term    = Schoolterm::current();

        $package = [
            'format'       => 1,
            'package_uuid' => (string) Str::uuid(),
            'generated_at' => now()->toIso8601String(),
            'school'       => [
                'name'    => $school->school_name ?? '',
                'address' => $school->school_address ?? '',
                'logo'    => $this->imageData($school->school_logo ?? null),
            ],
            'session'      => $session ? ['id' => $session->id, 'name' => $session->session] : null,
            'term'         => $term ? ['id' => $term->id, 'name' => $term->term] : null,
            'exams'        => $examOut,
            'students'     => array_values($students),
        ];

        // Checksum of what was frozen (questions, options, marks). The app sends
        // it back with each attempt so we know which version was used.
        $package['checksum'] = hash('sha256', json_encode(array_map(fn ($e) => [$e['id'], $e['questions'], $e['duration']], $examOut)));

        DB::table('offline_cbt_packages')->insert([
            'package_uuid'  => $package['package_uuid'],
            'device_id'     => $deviceId,
            'exam_ids'      => json_encode(array_values($examIds)),
            'checksum'      => $package['checksum'],
            'student_count' => count($students),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $package;
    }

    /** Students (with a portal login) allowed to take this exam. */
    public function eligibleStudents(Exam $exam)
    {
        return DB::table('studentclass')
            ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->join('users', 'users.student_id', '=', 'studentRegistration.id')
            ->where('studentclass.schoolclassid', $exam->schoolclass_id)
            ->where('studentclass.termid', $exam->termid)
            ->where('studentclass.sessionid', $exam->session)
            ->whereExists(function ($q) use ($exam) {
                $q->select(DB::raw(1))
                  ->from('student_subject_register_record as r')
                  ->join('subjectclass', 'subjectclass.id', '=', 'r.subjectclassid')
                  ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                  ->whereColumn('r.studentId', 'studentRegistration.id')
                  ->where('subjectteacher.subjectid', $exam->subject_id);
            })
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select('users.id as user_id', 'users.password', 'studentRegistration.id as student_id', 'studentpicture.picture',
                     'studentRegistration.admissionNo', 'studentRegistration.firstname',
                     'studentRegistration.lastname', 'studentRegistration.othername')
            ->distinct()
            ->orderBy('studentRegistration.lastname')
            ->get();
    }

    /** Small JPEG thumbnail of the student's photo (shown at login and to the invigilator). */
    private function photo(?string $picture): ?string
    {
        if (!$picture) return null;
        $disk = Storage::disk('public');
        $name = basename($picture);
        foreach ([$picture, 'images/student_avatars/' . $name, 'student_avatars/' . $name] as $path) {
            try {
                if (!$disk->exists($path)) continue;
                $full = $disk->path($path);
                if (function_exists('imagecreatefromstring')) {
                    $img = @imagecreatefromstring(file_get_contents($full));
                    if ($img) {
                        $w = imagesx($img); $h = imagesy($img);
                        $size = 160;
                        $scale = min(1, $size / max($w, $h));
                        $thumb = imagecreatetruecolor(max(1, (int) ($w * $scale)), max(1, (int) ($h * $scale)));
                        imagecopyresampled($thumb, $img, 0, 0, 0, 0, imagesx($thumb), imagesy($thumb), $w, $h);
                        ob_start();
                        imagejpeg($thumb, null, 75);
                        $jpg = ob_get_clean();
                        imagedestroy($img); imagedestroy($thumb);
                        return 'data:image/jpeg;base64,' . base64_encode($jpg);
                    }
                }
                if (filesize($full) <= 150 * 1024) {   // no GD: send small originals only
                    return 'data:' . (mime_content_type($full) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($full));
                }
            } catch (\Throwable $e) {
            }
        }
        return null;
    }

    private function imageData(?string $path): ?string
    {
        if (!$path) return null;
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path)) return null;
            $full = $disk->path($path);
            $mime = mime_content_type($full) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($full));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
