<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\WebsiteAlbum;
use App\Models\Website\Yearbook;
use App\Models\Website\YearbookEntry;
use App\Services\Website\WebsiteContent as W;
use App\Services\Website\Yearbooks as YB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Portal › Yearbook & Alumni: yearbooks, graduating students (import from the
 * portal, graduation photos, profiles, awards), private upload links for
 * students / parents, and approval of what they send.
 */
class YearbookAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage yearbook');
    }

    // ── yearbooks ───────────────────────────────────────────────────────

    public function index()
    {
        $books = Yearbook::withCount([
            'entries',
            'entries as photos_count' => fn ($q) => $q->whereNotNull('photo'),
            'entries as pending_count' => fn ($q) => $q->whereNotNull('pending_at'),
        ])->orderByDesc('graduation_year')->orderBy('sort')->get();

        return view('website.admin.yearbooks.index', [
            'pagetitle' => 'Yearbooks',
            'books' => $books,
            'sessions' => $this->sessions(),
            'alumniPending' => Schema::hasTable('alumni') ? DB::table('alumni')->where(fn ($q) => $q->where('status', 'pending')->orWhereNotNull('pending_at'))->count() : 0,
            'alumniCount' => Schema::hasTable('alumni') ? DB::table('alumni')->where('status', 'approved')->count() : 0,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:150', 'graduation_year' => 'required|integer|min:1950|max:2100',
            'session_id' => 'nullable|integer', 'class_label' => 'nullable|string|max:100',
        ]);
        $title = trim((string) ($data['title'] ?? '')) ?: ('Class of ' . $data['graduation_year']);
        $book = Yearbook::create([
            'title' => $title, 'slug' => W::uniqueSlug('yearbooks', $title), 'graduation_year' => $data['graduation_year'],
            'session_id' => $data['session_id'] ?? null, 'class_label' => $data['class_label'] ?? null,
            'visibility' => 'public', 'allow_submissions' => true, 'published' => false, 'created_by' => auth()->id(),
        ]);
        return redirect()->route('yearbook.admin.show', $book->id)->with('success', 'Yearbook created. Now add the graduating students.');
    }

    public function show(Request $request, int $id)
    {
        $book = Yearbook::findOrFail($id);
        $q = YearbookEntry::where('yearbook_id', $book->id);
        if ($s = trim((string) $request->get('q'))) $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('admission_no', 'like', "%{$s}%"));
        if ($request->filled('class')) $q->where('class_name', $request->get('class'));
        match ($request->get('status')) {
            'pending'  => $q->whereNotNull('pending_at'),
            'nophoto'  => $q->whereNull('photo'),
            'photo'    => $q->whereNotNull('photo'),
            'hidden'   => $q->where('published', false),
            'honours'  => $q->where(fn ($w) => $w->whereNotNull('position')->orWhereNotNull('awards')),
            default    => null,
        };
        $entries = $q->orderBy('sort')->orderBy('name')->get();
        $all = YearbookEntry::where('yearbook_id', $book->id);
        return view('website.admin.yearbooks.show', [
            'pagetitle' => $book->title,
            'book' => $book,
            'entries' => $entries,
            'phones' => YB::parentPhones($entries->pluck('student_id')->filter()->all()),
            'classes' => (clone $all)->whereNotNull('class_name')->distinct()->orderBy('class_name')->pluck('class_name'),
            'counts' => [
                'all' => (clone $all)->count(), 'photo' => (clone $all)->whereNotNull('photo')->count(),
                'pending' => (clone $all)->whereNotNull('pending_at')->count(), 'hidden' => (clone $all)->where('published', false)->count(),
            ],
            'sessions' => $this->sessions(),
            'schoolClasses' => $this->schoolClasses(),
        ]);
    }

    public function settings(int $id)
    {
        return view('website.admin.yearbooks.settings', [
            'pagetitle' => 'Yearbook settings',
            'book' => Yearbook::findOrFail($id),
            'sessions' => $this->sessions(),
            'albums' => Schema::hasTable('website_albums') ? WebsiteAlbum::orderByDesc('taken_on')->orderByDesc('id')->get(['id', 'title']) : collect(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $book = Yearbook::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:150', 'graduation_year' => 'required|integer|min:1950|max:2100',
            'session_id' => 'nullable|integer', 'class_label' => 'nullable|string|max:100', 'theme' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:3000', 'principal_message' => 'nullable|string|max:20000',
            'principal_name' => 'nullable|string|max:150', 'album_id' => 'nullable|integer',
            'visibility' => 'required|in:public,portal', 'submissions_close_on' => 'nullable|date', 'sort' => 'nullable|integer|min:0|max:65000',
            'cover' => 'nullable|image|max:12288', 'class_photo' => 'nullable|image|max:16384', 'principal_photo' => 'nullable|image|max:8192',
        ]);
        foreach (['title', 'graduation_year', 'session_id', 'class_label', 'theme', 'description', 'principal_name', 'album_id', 'visibility', 'submissions_close_on'] as $f) {
            $book->{$f} = ($data[$f] ?? null) === '' ? null : ($data[$f] ?? null);
        }
        $book->principal_message = W::clean($data['principal_message'] ?? null);
        $book->sort = (int) ($data['sort'] ?? 0);
        $book->published = $request->boolean('published');
        $book->allow_submissions = $request->boolean('allow_submissions');
        foreach (['cover' => 2200, 'class_photo' => 2400, 'principal_photo' => 900] as $f => $max) {
            if ($request->hasFile($f)) {
                W::deleteFile($book->{$f});
                $book->{$f} = W::storeImage($request->file($f), 'yearbooks/' . $book->id, $max)['path'];
            } elseif ($request->boolean('remove_' . $f)) {
                W::deleteFile($book->{$f});
                $book->{$f} = null;
            }
        }
        if ($book->isDirty('title')) $book->slug = W::uniqueSlug('yearbooks', $book->title, $book->id);
        $book->save();
        return redirect()->route('yearbook.admin.show', $book->id)->with('success', 'Yearbook settings saved.');
    }

    public function destroy(int $id)
    {
        $book = Yearbook::with('entries')->findOrFail($id);
        foreach ($book->entries as $e) $this->deleteEntryFiles($e);
        foreach (['cover', 'class_photo', 'principal_photo'] as $f) W::deleteFile($book->{$f});
        if (Schema::hasTable('alumni')) DB::table('alumni')->whereIn('yearbook_entry_id', $book->entries->pluck('id'))->update(['yearbook_entry_id' => null]);
        YearbookEntry::where('yearbook_id', $book->id)->delete();
        $book->delete();
        return redirect()->route('yearbook.admin.index')->with('success', 'Yearbook deleted.');
    }

    // ── import graduating students from the portal ──────────────────────

    /** Students of the chosen classes in the session (and students marked Graduated from them). */
    public function candidates(Request $request, int $id)
    {
        $book = Yearbook::findOrFail($id);
        $data = $request->validate(['session_id' => 'required|integer', 'class_ids' => 'required|array|min:1', 'class_ids.*' => 'integer']);
        $classIds = array_map('intval', $data['class_ids']);
        $sessionId = (int) $data['session_id'];

        $rows = collect();
        if (Schema::hasTable('studentclass')) {
            $rows = DB::table('studentclass as sc')->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
                ->whereIn('sc.schoolclassid', $classIds)->where('sc.sessionid', $sessionId)
                ->groupBy('s.id', 'sc.schoolclassid')
                ->get(['s.id', 'sc.schoolclassid as class_id']);
        }
        // Students already marked Graduated whose last class was one of these (their enrolments may be archived).
        if (Schema::hasColumn('studentRegistration', 'exit_class_id')) {
            $next = DB::table('schoolsession')->where('id', '>', $sessionId)->min('id');
            $grads = DB::table('studentRegistration')->where('student_status', 'Graduated')->whereIn('exit_class_id', $classIds)
                ->where(fn ($q) => $q->whereNull('exit_session_id')->orWhereIn('exit_session_id', array_filter([$sessionId, $next])))
                ->get(['id', 'exit_class_id as class_id']);
            $rows = $rows->concat($grads)->unique('id');
        }

        $ids = $rows->pluck('id')->all();
        $students = DB::table('studentRegistration')->whereIn('id', $ids)->get(['id', 'firstname', 'lastname', 'othername', 'admissionNo', 'student_status'])->keyBy('id');
        $classNames = $this->schoolClasses()->pluck('name', 'id');
        $taken = YearbookEntry::where('yearbook_id', $book->id)->whereIn('student_id', $ids)->pluck('student_id')->flip();

        $out = $rows->map(function ($r) use ($students, $classNames, $taken) {
            $s = $students[$r->id] ?? null;
            if (!$s) return null;
            return [
                'id' => (int) $s->id, 'name' => $this->studentName($s), 'admission_no' => $s->admissionNo,
                'class' => $classNames[$r->class_id] ?? '', 'class_id' => (int) $r->class_id, 'status' => $s->student_status,
                'in_book' => isset($taken[$s->id]),
            ];
        })->filter()->sortBy([['class', 'asc'], ['name', 'asc']])->values();

        return response()->json($out);
    }

    public function import(Request $request, int $id)
    {
        $book = Yearbook::findOrFail($id);
        $data = $request->validate(['students' => 'required|array|min:1', 'students.*' => 'string', 'copy_photos' => 'nullable']);
        $classNames = $this->schoolClasses()->pluck('name', 'id');
        $houses = Schema::hasTable('schoolhouses') ? DB::table('schoolhouses')->pluck('house', 'id') : collect();
        $hasAmbition = Schema::hasColumn('studentRegistration', 'future_ambition');
        $hasHouse = Schema::hasColumn('studentRegistration', 'sport_house');

        $added = 0;
        $sort = (int) YearbookEntry::where('yearbook_id', $book->id)->max('sort');
        foreach ($data['students'] as $pair) {
            [$sid, $cid] = array_map('intval', array_pad(explode(':', $pair), 2, 0));
            if (!$sid || YearbookEntry::where('yearbook_id', $book->id)->where('student_id', $sid)->exists()) continue;
            $s = DB::table('studentRegistration')->where('id', $sid)->first();
            if (!$s) continue;

            $house = $hasHouse ? trim((string) ($s->sport_house ?? '')) : '';
            if (is_numeric($house)) $house = (string) ($houses[(int) $house] ?? '');
            if ($house === '' && Schema::hasTable('studenthouses')) {
                $hid = DB::table('studenthouses')->where('studentid', $sid)->orderByDesc('id')->value('schoolhouse');
                $house = (string) ($houses[(int) $hid] ?? (is_numeric($hid) ? '' : $hid));
            }

            YearbookEntry::create([
                'yearbook_id' => $book->id, 'student_id' => $sid, 'name' => $this->studentName($s),
                'admission_no' => $s->admissionNo ?? null, 'gender' => $s->gender ?? null,
                'class_name' => $classNames[$cid] ?? null, 'house' => $house ?: null,
                'ambition' => $hasAmbition ? (trim((string) $s->future_ambition) ?: null) : null,
                'portal_photo' => $request->boolean('copy_photos', true) ? YB::copyPortalPhoto($sid, $book->id) : null,
                'published' => true, 'sort' => ++$sort, 'upload_token' => Str::random(40),
            ]);
            $added++;
        }
        return redirect()->route('yearbook.admin.show', $book->id)->with('success', "{$added} student(s) added. Upload their graduation photos, or send them their upload links.");
    }

    // ── entries ─────────────────────────────────────────────────────────

    public function createEntry(int $id)
    {
        $book = Yearbook::findOrFail($id);
        return view('website.admin.yearbooks.entry', ['pagetitle' => 'Add graduate', 'book' => $book, 'e' => new YearbookEntry(['published' => true, 'class_name' => $book->class_label])]);
    }

    public function editEntry(int $id, int $entry)
    {
        $book = Yearbook::findOrFail($id);
        $e = YearbookEntry::where('yearbook_id', $book->id)->findOrFail($entry);
        $ids = YearbookEntry::where('yearbook_id', $book->id)->orderBy('sort')->orderBy('name')->pluck('id');
        $i = $ids->search($e->id);
        return view('website.admin.yearbooks.entry', [
            'pagetitle' => $e->name, 'book' => $book, 'e' => $e,
            'prevId' => $i > 0 ? $ids[$i - 1] : null, 'nextId' => $i !== false && $i < $ids->count() - 1 ? $ids[$i + 1] : null,
            'phone' => $e->student_id ? (YB::parentPhones([$e->student_id])[$e->student_id] ?? null) : null,
        ]);
    }

    public function saveEntry(Request $request, int $id, ?int $entry = null)
    {
        $book = Yearbook::findOrFail($id);
        $e = $entry ? YearbookEntry::where('yearbook_id', $book->id)->findOrFail($entry) : new YearbookEntry(['yearbook_id' => $book->id]);
        $data = $request->validate([
            'name' => 'required|string|max:150', 'nickname' => 'nullable|string|max:80', 'admission_no' => 'nullable|string|max:60',
            'gender' => 'nullable|in:Male,Female', 'class_name' => 'nullable|string|max:60', 'house' => 'nullable|string|max:60',
            'position' => 'nullable|string|max:100', 'awards' => 'nullable|string|max:300', 'ambition' => 'nullable|string|max:150',
            'hobbies' => 'nullable|string|max:200', 'favourite_subject' => 'nullable|string|max:100', 'quote' => 'nullable|string|max:300',
            'best_memory' => 'nullable|string|max:1500', 'testimonial' => 'nullable|string|max:2000', 'school_remark' => 'nullable|string|max:2000',
            'next_step' => 'nullable|string|max:150', 'sort' => 'nullable|integer|min:0|max:65000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:12288',
        ]);
        foreach (['name', 'nickname', 'admission_no', 'gender', 'class_name', 'house', 'position', 'awards', 'ambition', 'hobbies',
                  'favourite_subject', 'quote', 'best_memory', 'testimonial', 'school_remark', 'next_step'] as $f) {
            $v = $data[$f] ?? null;
            $e->{$f} = is_string($v) && trim($v) === '' ? null : (is_string($v) ? trim($v) : $v);
        }
        $e->sort = (int) ($data['sort'] ?? ($e->sort ?: (int) YearbookEntry::where('yearbook_id', $book->id)->max('sort') + 1));
        $e->published = $request->boolean('published');
        $e->featured = $request->boolean('featured');
        if ($request->hasFile('photo')) {
            YB::deletePortrait($e->photo);
            $e->photo = YB::storePortrait($request->file('photo'), 'yearbooks/' . $book->id);
        } elseif ($request->boolean('remove_photo')) {
            YB::deletePortrait($e->photo);
            $e->photo = null;
        }
        if (!$e->upload_token) $e->upload_token = Str::random(40);
        $e->save();

        if ($request->get('then') === 'next' && ($next = YearbookEntry::where('yearbook_id', $book->id)
                ->where(fn ($q) => $q->where('sort', '>', $e->sort)->orWhere(fn ($x) => $x->where('sort', $e->sort)->where('name', '>', $e->name)))
                ->orderBy('sort')->orderBy('name')->first())) {
            return redirect()->route('yearbook.admin.entry', [$book->id, $next->id])->with('success', $e->name . ' saved.');
        }
        if ($request->get('then') === 'new') return redirect()->route('yearbook.admin.entry.create', $book->id)->with('success', $e->name . ' added.');
        return redirect()->route('yearbook.admin.show', $book->id)->with('success', $e->name . ' saved.');
    }

    public function deleteEntry(int $id, int $entry)
    {
        $e = YearbookEntry::where('yearbook_id', $id)->findOrFail($entry);
        $this->deleteEntryFiles($e);
        if (Schema::hasTable('alumni')) DB::table('alumni')->where('yearbook_entry_id', $e->id)->update(['yearbook_entry_id' => null]);
        $e->delete();
        return redirect()->route('yearbook.admin.show', $id)->with('success', 'Removed from the yearbook.');
    }

    /** Use what the student / parent sent through the link. */
    public function approve(int $id, int $entry)
    {
        $e = YearbookEntry::where('yearbook_id', $id)->findOrFail($entry);
        $this->applyPending($e);
        return back()->with('success', $e->name . ': photo and details approved — now on the yearbook.');
    }

    public function reject(int $id, int $entry)
    {
        $e = YearbookEntry::where('yearbook_id', $id)->findOrFail($entry);
        YB::deletePortrait($e->pending_photo);
        $e->forceFill(['pending' => null, 'pending_photo' => null, 'pending_at' => null, 'pending_by' => null])->save();
        return back()->with('success', $e->name . ': submission rejected. You can send the link again.');
    }

    public function newLink(int $id, int $entry)
    {
        $e = YearbookEntry::where('yearbook_id', $id)->findOrFail($entry);
        $e->forceFill(['upload_token' => Str::random(40), 'link_sent_at' => null])->save();
        return back()->with('success', 'A new link was made for ' . $e->name . '. The old link no longer works.');
    }

    // ── bulk actions ────────────────────────────────────────────────────

    public function bulk(Request $request, int $id)
    {
        $book = Yearbook::findOrFail($id);
        $data = $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer', 'action' => 'required|in:publish,hide,feature,unfeature,approve,notify,alumni,delete']);
        $entries = YearbookEntry::where('yearbook_id', $book->id)->whereIn('id', $data['ids'])->get();
        $n = 0;
        foreach ($entries as $e) {
            switch ($data['action']) {
                case 'publish': $e->update(['published' => true]); $n++; break;
                case 'hide': $e->update(['published' => false]); $n++; break;
                case 'feature': $e->update(['featured' => true]); $n++; break;
                case 'unfeature': $e->update(['featured' => false]); $n++; break;
                case 'approve': if ($e->hasPending()) { $this->applyPending($e); $n++; } break;
                case 'alumni': if (Schema::hasTable('alumni')) { YB::alumnusFromEntry($e, $book); $n++; } break;
                case 'delete':
                    $this->deleteEntryFiles($e);
                    if (Schema::hasTable('alumni')) DB::table('alumni')->where('yearbook_entry_id', $e->id)->update(['yearbook_entry_id' => null]);
                    $e->delete(); $n++; break;
                case 'notify': $n += $this->sendLink($e, $book) ? 1 : 0; break;
            }
        }
        $msg = match ($data['action']) {
            'notify' => "Upload links sent to {$n} student(s) and their parents through the portal.",
            'alumni' => "{$n} graduate(s) added to the alumni network.",
            'approve' => "{$n} submission(s) approved.",
            default => "{$n} updated.",
        };
        return back()->with('success', $msg);
    }

    /** Several graduation photos at once, matched to students by file name (admission number or name). */
    public function bulkPhotos(Request $request, int $id)
    {
        $book = Yearbook::findOrFail($id);
        $request->validate(['photos' => 'required|array|max:60', 'photos.*' => 'image|max:12288']);
        $norm = fn ($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
        $entries = YearbookEntry::where('yearbook_id', $book->id)->get();
        $byAdm = $entries->filter(fn ($e) => $e->admission_no)->keyBy(fn ($e) => $norm($e->admission_no));
        $byName = $entries->keyBy(fn ($e) => $norm($e->name));
        $matched = 0; $missed = [];
        foreach ($request->file('photos') as $file) {
            $stem = $norm(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $e = $byAdm[$stem] ?? $byName[$stem] ?? null;
            if (!$e) {
                // "SS3A-TCC-2020-014-John Doe" style names: any admission number inside the file name.
                $e = $byAdm->first(fn ($x, $k) => strlen($k) >= 4 && str_contains($stem, $k));
            }
            if (!$e) { $missed[] = $file->getClientOriginalName(); continue; }
            YB::deletePortrait($e->photo);
            $e->update(['photo' => YB::storePortrait($file, 'yearbooks/' . $book->id)]);
            $matched++;
        }
        $msg = "{$matched} photo(s) matched and saved.";
        if ($missed) $msg .= ' Not matched: ' . implode(', ', array_slice($missed, 0, 10)) . (count($missed) > 10 ? '…' : '') . ' — rename them to the admission number or upload them on the student.';
        if ($request->expectsJson()) return response()->json(['ok' => true, 'matched' => $matched, 'missed' => $missed]);
        return back()->with($missed ? 'error' : 'success', $msg);
    }

    /** Names, parent phones and upload links (CSV) — for WhatsApp / SMS broadcast. */
    public function exportLinks(int $id)
    {
        $book = Yearbook::findOrFail($id);
        $entries = YearbookEntry::where('yearbook_id', $book->id)->orderBy('class_name')->orderBy('name')->get();
        $phones = YB::parentPhones($entries->pluck('student_id')->filter()->all());
        $name = Str::slug($book->title) . '-upload-links.csv';
        return response()->streamDownload(function () use ($entries, $phones) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Admission no', 'Class', 'Parent phone', 'Graduation photo', 'Upload link']);
            foreach ($entries as $e) {
                fputcsv($out, [$e->name, $e->admission_no, $e->class_name, $phones[$e->student_id] ?? '', $e->photo ? 'Yes' : 'No', $e->uploadUrl()]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    protected function applyPending(YearbookEntry $e): void
    {
        foreach ((array) $e->pending as $f => $v) {
            if (in_array($f, YearbookEntry::SUBMITTABLE, true) && $v !== null && $v !== '') $e->{$f} = $v;
        }
        if ($e->pending_photo) {
            YB::deletePortrait($e->photo);
            $e->photo = $e->pending_photo;
        }
        $e->forceFill(['pending' => null, 'pending_photo' => null, 'pending_at' => null, 'pending_by' => null])->save();
    }

    protected function sendLink(YearbookEntry $e, Yearbook $book): bool
    {
        if (!$e->student_id || !class_exists(\App\Services\Messaging\PortalNotifier::class)) return false;
        try {
            $n = \App\Services\Messaging\PortalNotifier::toStudents([$e->student_id], 'Yearbook: upload your graduation photo', YB::linkMessage($e, $book), $e->uploadUrl(), 'system', 'yb-link:' . $e->id . ':' . now()->format('YmdHi'));
            if ($n) $e->forceFill(['link_sent_at' => now()])->save();
            return $n > 0;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    protected function deleteEntryFiles(YearbookEntry $e): void
    {
        YB::deletePortrait($e->photo);
        YB::deletePortrait($e->pending_photo);
        W::deleteFile($e->portal_photo);
    }

    protected function studentName($s): string
    {
        return trim(preg_replace('/\s+/', ' ', ($s->lastname ?? '') . ' ' . ($s->firstname ?? '') . ' ' . ($s->othername ?? '')));
    }

    protected function sessions()
    {
        return Schema::hasTable('schoolsession') ? DB::table('schoolsession')->orderByDesc('id')->get(['id', 'session', 'status']) : collect();
    }

    /** Classes as [id, name] with the arm, e.g. "SS 3 A". */
    protected function schoolClasses()
    {
        if (!Schema::hasTable('schoolclass')) return collect();
        $q = DB::table('schoolclass as c');
        if (Schema::hasTable('schoolarm')) {
            $q->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm');
            return $q->orderBy('c.schoolclass')->orderBy('a.arm')->get(['c.id', 'c.schoolclass', 'a.arm'])
                ->map(fn ($c) => (object) ['id' => $c->id, 'name' => trim($c->schoolclass . ' ' . ($c->arm ?? ''))]);
        }
        return $q->orderBy('c.schoolclass')->get(['c.id', 'c.schoolclass'])->map(fn ($c) => (object) ['id' => $c->id, 'name' => $c->schoolclass]);
    }
}
