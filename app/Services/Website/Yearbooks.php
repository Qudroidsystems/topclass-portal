<?php

namespace App\Services\Website;

use App\Models\Website\Alumnus;
use App\Models\Website\Yearbook;
use App\Models\Website\YearbookEntry;
use App\Services\Website\WebsiteContent as W;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Helpers shared by the yearbook / alumni pages and their admin. */
class Yearbooks
{
    /** Saves a portrait (graduation photo, alumni photo) with a thumbnail next to it. */
    public static function storePortrait(UploadedFile $file, string $dir): string
    {
        return W::storeImage($file, $dir, 1400, 520)['path'];
    }

    /** Thumbnail saved beside a photo by storePortrait (falls back to the photo). */
    public static function thumb(?string $path): ?string
    {
        if (!$path || Str::startsWith($path, ['http', '/'])) return $path;
        $thumb = dirname($path) . '/thumbs/' . basename($path);
        try {
            return Storage::disk('public')->exists($thumb) ? $thumb : $path;
        } catch (\Throwable $e) {
            return $path;
        }
    }

    public static function url(?string $path): ?string
    {
        return W::fileUrl($path);
    }

    public static function deletePortrait(?string $path): void
    {
        if (!$path || Str::startsWith($path, ['http', '/'])) return;
        W::deleteFile($path);
        W::deleteFile(dirname($path) . '/thumbs/' . basename($path));
    }

    /**
     * Copies the student's portal passport into the yearbook's folder, so the
     * yearbook keeps it even if the portal picture is changed later.
     */
    public static function copyPortalPhoto(int $studentId, int $yearbookId): ?string
    {
        if (!Schema::hasTable('studentpicture')) return null;
        $pic = DB::table('studentpicture')->where('studentid', $studentId)->whereNotNull('picture')->where('picture', '!=', '')->orderByDesc('id')->value('picture');
        if (!$pic) return null;
        $disk = Storage::disk('public');
        $name = basename(str_replace('\\', '/', $pic));
        foreach (['student_avatars/' . $name, 'images/student_avatars/' . $name, ltrim($pic, '/')] as $src) {
            try {
                if (!$disk->exists($src)) continue;
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg');
                $dest = "website/yearbooks/{$yearbookId}/portal/" . Str::random(12) . '.' . $ext;
                $disk->copy($src, $dest);
                return $dest;
            } catch (\Throwable $e) {
            }
        }
        return null;
    }

    /** Portal users who can manage yearbooks (for notifications). */
    public static function managerIds(): array
    {
        try {
            return DB::table('model_has_permissions as mp')->join('permissions as p', 'p.id', '=', 'mp.permission_id')
                ->where('p.name', 'Manage yearbook')->pluck('mp.model_id')
                ->merge(DB::table('model_has_roles as mr')->join('role_has_permissions as rp', 'rp.role_id', '=', 'mr.role_id')
                    ->join('permissions as p', 'p.id', '=', 'rp.permission_id')->where('p.name', 'Manage yearbook')->pluck('mr.model_id'))
                ->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function notifyManagers(string $title, string $body, string $url): void
    {
        try {
            if (!class_exists(\App\Services\Messaging\PortalNotifier::class)) return;
            $ids = self::managerIds();
            if ($ids) \App\Services\Messaging\PortalNotifier::toUsers($ids, $title, $body, $url, 'system', 'yb:' . now()->format('YmdHis') . ':' . substr(md5($body), 0, 6));
        } catch (\Throwable $e) {
        }
    }

    /** Students (ids) the signed-in user is: themselves, or their children for a parent account. */
    public static function myStudentIds($user): array
    {
        if (!$user) return [];
        $ids = $user->student_id ? [(int) $user->student_id] : [];
        try {
            if (Schema::hasTable('parent_student')) {
                $ids = array_merge($ids, DB::table('parent_student')->where('user_id', $user->id)->pluck('student_id')->map(fn ($i) => (int) $i)->all());
            }
        } catch (\Throwable $e) {
        }
        return array_values(array_unique($ids));
    }

    /** Parent phone numbers for students, as [student_id => '234…']. */
    public static function parentPhones(array $studentIds): array
    {
        if (!$studentIds || !Schema::hasTable('parentRegistration')) return [];
        $out = [];
        try {
            $cols = array_values(array_filter(['father_phone', 'mother_phone'], fn ($c) => Schema::hasColumn('parentRegistration', $c)));
            if (!$cols) return [];
            foreach (DB::table('parentRegistration')->whereIn('studentId', $studentIds)->get(array_merge(['studentId'], $cols)) as $r) {
                foreach ($cols as $c) {
                    $p = self::waPhone($r->{$c} ?? null);
                    if ($p) { $out[(int) $r->studentId] = $p; break; }
                }
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

    /** A phone number as WhatsApp wants it (Nigerian 0… numbers become 234…). */
    public static function waPhone(?string $phone): ?string
    {
        $p = preg_replace('/[^0-9]/', '', (string) preg_split('/[,;\/]/', (string) $phone)[0]);
        if (strlen($p) < 10) return null;
        if (str_starts_with($p, '0')) $p = '234' . substr($p, 1);
        return $p;
    }

    /** The message sent with an upload link. */
    public static function linkMessage(YearbookEntry $e, Yearbook $book): string
    {
        $school = W::school()->name ?? config('app.name');
        return "Hello! {$school} is preparing the {$book->title} (Class of {$book->graduation_year}). "
            . "Please use this private link to upload {$e->name}'s graduation photo (in gown, suit or graduation attire) and yearbook details: "
            . $e->uploadUrl();
    }

    /** Creates (or returns) the alumni record for a yearbook entry. */
    public static function alumnusFromEntry(YearbookEntry $e, Yearbook $book): Alumnus
    {
        return Alumnus::firstOrCreate(['yearbook_entry_id' => $e->id], [
            'student_id' => $e->student_id, 'name' => $e->name, 'graduation_year' => $book->graduation_year,
            'class_set' => $e->class_name ?: $book->class_label, 'status' => 'approved',
            'higher_institution' => $e->next_step,
        ]);
    }
}
