<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Alumnus;
use App\Models\Website\WebsitePost;
use App\Models\Website\Yearbook;
use App\Models\Website\YearbookEntry;
use App\Services\Website\WebsiteContent as W;
use App\Services\Website\Yearbooks as YB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Public yearbook and alumni pages on the school website (also usable inside
 * the portal, where portal-only yearbooks can be seen by signed-in users).
 */
class YearbookController extends Controller
{
    // ── yearbooks ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = auth()->user();
        $books = Yearbook::where('published', true)->withCount(['entries as graduates_count' => fn ($q) => $q->where('published', true)])
            ->orderByDesc('graduation_year')->orderBy('sort')->get();

        $results = collect();
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) >= 2) {
            $visible = $books->filter(fn ($b) => $b->visibleTo($user))->pluck('id');
            $results = YearbookEntry::with('yearbook')->whereIn('yearbook_id', $visible)->where('published', true)
                ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('nickname', 'like', "%{$q}%"))
                ->orderBy('name')->limit(60)->get();
        }

        // A student / parent signed in to the portal: their own yearbook profiles.
        $mine = collect();
        if ($user && ($ids = YB::myStudentIds($user))) {
            $mine = YearbookEntry::with('yearbook')->whereIn('student_id', $ids)->get()->filter(fn ($e) => $e->yearbook && $e->yearbook->published);
        }

        return view('website.yearbooks', [
            'books' => $books, 'results' => $results, 'q' => $q, 'mine' => $mine,
            'alumniCount' => Schema::hasTable('alumni') ? Alumnus::approved()->count() : 0,
        ]);
    }

    public function show(string $slug)
    {
        $book = $this->book($slug);
        if (!$book instanceof Yearbook) return $book;
        $entries = $book->publishedEntries()->get();
        return view('website.yearbook', [
            'book' => $book,
            'entries' => $entries,
            'classes' => $entries->pluck('class_name')->filter()->unique()->sort()->values(),
            'honours' => $entries->filter(fn ($e) => $e->position || $e->awards)->sortByDesc('featured')->values(),
            'album' => $book->album_id ? $book->album()->where('published', true)->with('photos')->first() : null,
            'others' => Yearbook::visible(auth()->user())->where('id', '!=', $book->id)->orderByDesc('graduation_year')->limit(6)->get(['title', 'slug', 'graduation_year']),
            'canManage' => auth()->check() && auth()->user()->can('Manage yearbook'),
        ]);
    }

    public function entry(string $slug, int $id)
    {
        $book = $this->book($slug);
        if (!$book instanceof Yearbook) return $book;
        $all = $book->publishedEntries()->get(['id', 'name']);
        $entry = YearbookEntry::where('yearbook_id', $book->id)->where('published', true)->findOrFail($id);
        $i = $all->search(fn ($e) => $e->id === $entry->id);
        $alumnus = Schema::hasTable('alumni') ? Alumnus::approved()->where('yearbook_entry_id', $entry->id)->first() : null;
        return view('website.yearbook-entry', [
            'book' => $book, 'e' => $entry, 'alumnus' => $alumnus,
            'prev' => $i > 0 ? $all[$i - 1] : null, 'next' => $i !== false && $i < $all->count() - 1 ? $all[$i + 1] : null,
            'signedIn' => auth()->check(),
        ]);
    }

    /** Print-ready yearbook (A4) — "Save as PDF" from the browser's print dialog. */
    public function print(string $slug)
    {
        $book = $this->book($slug);
        if (!$book instanceof Yearbook) return $book;
        return view('website.yearbook-print', [
            'book' => $book, 'entries' => $book->publishedEntries()->get(), 'school' => W::school(), 'site' => W::settings(),
        ]);
    }

    /** The yearbook, if this visitor may see it — or the redirect / 404 that applies. */
    protected function book(string $slug)
    {
        $book = Yearbook::where('slug', $slug)->firstOrFail();
        $user = auth()->user();
        $admin = $user && $user->can('Manage yearbook');
        if (!$book->published && !$admin) abort(404);
        if ($book->isPortalOnly() && !$user) {
            // Portal-only: sign in on the portal (the website's domain has no portal session).
            if (W::isWebsiteHost()) return redirect()->away(W::portalUrl(request()->path()));
            return redirect()->guest(route('login'))->with('status', 'Please sign in to view this yearbook.');
        }
        return $book;
    }

    // ── private upload link (student / parent) ──────────────────────────

    public function uploadForm(string $token)
    {
        $e = YearbookEntry::with('yearbook')->where('upload_token', $token)->firstOrFail();
        return view('website.yearbook-upload', ['e' => $e, 'book' => $e->yearbook, 'open' => $e->yearbook->acceptingSubmissions()]);
    }

    public function uploadSave(Request $request, string $token)
    {
        $e = YearbookEntry::with('yearbook')->where('upload_token', $token)->firstOrFail();
        if (!$e->yearbook->acceptingSubmissions()) return back()->withErrors(['photo' => 'Submissions for this yearbook are closed. Please contact the school.']);

        $data = $request->validate([
            'photo'             => ($e->photo || $e->pending_photo ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png,webp|max:10240',
            'nickname'          => 'nullable|string|max:80',
            'ambition'          => 'nullable|string|max:150',
            'hobbies'           => 'nullable|string|max:200',
            'favourite_subject' => 'nullable|string|max:100',
            'quote'             => 'nullable|string|max:300',
            'best_memory'       => 'nullable|string|max:1500',
            'testimonial'       => 'nullable|string|max:2000',
            'next_step'         => 'nullable|string|max:150',
            'submitter'         => 'required|string|max:120',
            'relationship'      => 'required|in:Student,Parent,Guardian',
            'consent'           => 'accepted',
            'website'           => 'nullable|max:0',
        ], ['photo.required' => 'Please choose the graduation photo.', 'consent.accepted' => 'Please confirm that the photo and details may be shown in the yearbook.']);

        $pending = [];
        foreach (YearbookEntry::SUBMITTABLE as $f) {
            if (isset($data[$f]) && trim($data[$f]) !== '') $pending[$f] = trim($data[$f]);
        }
        if ($request->hasFile('photo')) {
            YB::deletePortrait($e->pending_photo);
            $e->pending_photo = YB::storePortrait($request->file('photo'), 'yearbooks/' . $e->yearbook_id . '/submitted');
        }
        $e->pending = $pending;
        $e->pending_at = now();
        $e->pending_by = $data['submitter'] . ' (' . $data['relationship'] . ')';
        $e->save();

        YB::notifyManagers('Yearbook photo received', $e->name . ' — sent by ' . $e->pending_by, route('yearbook.admin.show', ['id' => $e->yearbook_id, 'status' => 'pending']));
        return redirect()->route('site.yearbook.upload', $token)->with('site_success', 'Thank you! The photo and details were received. They will appear in the yearbook once the school approves them.');
    }

    // ── alumni ──────────────────────────────────────────────────────────

    public function alumni(Request $request)
    {
        $q = Alumnus::approved()->with('entry');
        if ($s = trim((string) $request->get('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('maiden_name', 'like', "%{$s}%")->orWhere('occupation', 'like', "%{$s}%")
                ->orWhere('organisation', 'like', "%{$s}%")->orWhere('location', 'like', "%{$s}%"));
        }
        if ($request->filled('year')) $q->where('graduation_year', (int) $request->get('year'));
        if ($request->filled('industry')) $q->where('industry', $request->get('industry'));
        if ($request->boolean('mentors')) $q->where('mentor', true);

        $base = Alumnus::approved();
        return view('website.alumni', [
            'alumni' => $q->orderByDesc('featured')->orderByDesc('graduation_year')->orderBy('name')->paginate(24)->withQueryString(),
            'featured' => (clone $base)->where('featured', true)->whereNotNull('bio')->with('entry')->inRandomOrder()->limit(3)->get(),
            'years' => (clone $base)->whereNotNull('graduation_year')->distinct()->orderByDesc('graduation_year')->pluck('graduation_year'),
            'industries' => (clone $base)->whereNotNull('industry')->distinct()->orderBy('industry')->pluck('industry'),
            'stats' => [
                'members' => (clone $base)->count(),
                'sets' => (clone $base)->whereNotNull('graduation_year')->distinct()->count('graduation_year'),
                'mentors' => (clone $base)->where('mentor', true)->count(),
            ],
            'news' => WebsitePost::live()->where('category', 'like', '%alumni%')->orderByDesc('published_at')->limit(3)->get(),
            'bookYears' => Yearbook::visible(auth()->user())->pluck('slug', 'graduation_year'),
        ]);
    }

    public function alumnus(int $id)
    {
        $a = Alumnus::approved()->with('entry.yearbook')->findOrFail($id);
        $book = $a->entry && $a->entry->yearbook && $a->entry->yearbook->visibleTo(auth()->user()) ? $a->entry->yearbook : null;
        $book = $book ?: Yearbook::visible(auth()->user())->where('graduation_year', $a->graduation_year)->first();
        $mates = $a->graduation_year ? Alumnus::approved()->where('graduation_year', $a->graduation_year)->where('id', '!=', $a->id)->inRandomOrder()->limit(8)->get() : collect();
        return view('website.alumnus', compact('a', 'book', 'mates'));
    }

    public function join()
    {
        return view('website.alumni-form', ['a' => new Alumnus(['show_contact' => false]), 'mode' => 'join']);
    }

    public function joinSave(Request $request)
    {
        $data = $this->validateAlumnus($request, true);
        $a = new Alumnus();
        $this->fillAlumnus($a, $data);
        $a->status = 'pending';
        $a->ip = $request->ip();
        if ($request->hasFile('photo')) $a->photo = YB::storePortrait($request->file('photo'), 'alumni');
        $a->save();
        $link = $a->editUrl();
        YB::notifyManagers('New alumni registration', $a->name . ' (Class of ' . $a->graduation_year . ')', route('alumni.admin.index', ['status' => 'pending']));
        return redirect()->route('site.alumni.join')->with('alumni_link', $link)
            ->with('site_success', 'Welcome to the alumni network! Your profile will be listed once the school confirms it.');
    }

    public function edit(string $token)
    {
        $a = Alumnus::where('edit_token', $token)->firstOrFail();
        // Show what they sent last if it is still waiting for approval.
        if ($a->pending) $a->fill(array_intersect_key($a->pending, array_flip(Alumnus::EDITABLE)));
        return view('website.alumni-form', ['a' => $a, 'mode' => 'edit', 'token' => $token]);
    }

    public function editSave(Request $request, string $token)
    {
        $a = Alumnus::where('edit_token', $token)->firstOrFail();
        $data = $this->validateAlumnus($request, false);
        if ($a->status === 'approved') {
            // Changes to a listed profile wait for the school's approval.
            $pending = [];
            foreach (Alumnus::EDITABLE as $f) $pending[$f] = $data[$f] ?? (in_array($f, ['show_contact', 'mentor'], true) ? false : null);
            $pending['show_contact'] = $request->boolean('show_contact');
            $pending['mentor'] = $request->boolean('mentor');
            if ($request->hasFile('photo')) {
                YB::deletePortrait($a->pending_photo);
                $a->pending_photo = YB::storePortrait($request->file('photo'), 'alumni');
            }
            $a->pending = $pending;
            $a->pending_at = now();
            $a->save();
            YB::notifyManagers('Alumni profile update', $a->name . ' updated their profile', route('alumni.admin.edit', $a->id));
            return back()->with('site_success', 'Thank you! Your changes will show once the school approves them.');
        }
        $this->fillAlumnus($a, $data);
        if ($request->hasFile('photo')) {
            YB::deletePortrait($a->photo);
            $a->photo = YB::storePortrait($request->file('photo'), 'alumni');
        }
        $a->save();
        return back()->with('site_success', 'Your profile has been updated.');
    }

    protected function validateAlumnus(Request $request, bool $new): array
    {
        return $request->validate([
            'name'               => 'required|string|max:150',
            'maiden_name'        => 'nullable|string|max:100',
            'graduation_year'    => 'required|integer|min:1950|max:' . (date('Y') + 1),
            'class_set'          => 'nullable|string|max:100',
            'photo'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'occupation'         => 'nullable|string|max:150',
            'organisation'       => 'nullable|string|max:150',
            'industry'           => 'nullable|in:' . implode(',', Alumnus::INDUSTRIES),
            'location'           => 'nullable|string|max:150',
            'higher_institution' => 'nullable|string|max:200',
            'bio'                => 'nullable|string|max:2000',
            'message'            => 'nullable|string|max:1000',
            'email'              => 'nullable|email|max:150|required_without:phone',
            'phone'              => 'nullable|string|max:40',
            'linkedin'           => 'nullable|url|max:200',
            'consent'            => $new ? 'accepted' : 'nullable',
            'website'            => 'nullable|max:0',
        ], ['email.required_without' => 'Please give an email address or a phone number so the school can reach you.',
            'consent.accepted' => 'Please confirm that you attended the school and agree to be listed.']);
    }

    protected function fillAlumnus(Alumnus $a, array $data): void
    {
        foreach (Alumnus::EDITABLE as $f) {
            if (in_array($f, ['show_contact', 'mentor'], true)) continue;
            $v = $data[$f] ?? null;
            $a->{$f} = is_string($v) && trim($v) === '' ? null : $v;
        }
        $a->show_contact = request()->boolean('show_contact');
        $a->mentor = request()->boolean('mentor');
    }
}
