<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Alumnus;
use App\Services\Website\Yearbooks as YB;
use Illuminate\Http\Request;

/** Portal › Yearbook & Alumni › Alumni: approve sign-ups and profile changes, edit, feature, export. */
class AlumniAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage yearbook');
    }

    public function index(Request $request)
    {
        $q = Alumnus::query()->with('entry');
        $status = $request->get('status', 'all');
        match ($status) {
            'pending'  => $q->where(fn ($w) => $w->where('status', 'pending')->orWhereNotNull('pending_at')),
            'approved' => $q->where('status', 'approved'),
            'rejected' => $q->where('status', 'rejected'),
            'mentors'  => $q->where('mentor', true)->where('status', 'approved'),
            default    => null,
        };
        if ($s = trim((string) $request->get('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('occupation', 'like', "%{$s}%")->orWhere('organisation', 'like', "%{$s}%"));
        }
        if ($request->filled('year')) $q->where('graduation_year', (int) $request->get('year'));

        return view('website.admin.alumni.index', [
            'pagetitle' => 'Alumni',
            'alumni' => $q->orderByRaw('pending_at is null')->orderByRaw("status = 'pending' desc")->orderByDesc('graduation_year')->orderBy('name')->paginate(30)->withQueryString(),
            'status' => $status,
            'years' => Alumnus::whereNotNull('graduation_year')->distinct()->orderByDesc('graduation_year')->pluck('graduation_year'),
            'counts' => [
                'all' => Alumnus::count(), 'approved' => Alumnus::where('status', 'approved')->count(),
                'pending' => Alumnus::where(fn ($w) => $w->where('status', 'pending')->orWhereNotNull('pending_at'))->count(),
                'mentors' => Alumnus::where('status', 'approved')->where('mentor', true)->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('website.admin.alumni.edit', ['pagetitle' => 'Add alumnus', 'a' => new Alumnus(['status' => 'approved'])]);
    }

    public function edit(int $id)
    {
        return view('website.admin.alumni.edit', ['pagetitle' => 'Alumnus', 'a' => Alumnus::with('entry.yearbook')->findOrFail($id)]);
    }

    public function save(Request $request, ?int $id = null)
    {
        $a = $id ? Alumnus::findOrFail($id) : new Alumnus();
        $data = $request->validate([
            'name' => 'required|string|max:150', 'maiden_name' => 'nullable|string|max:100',
            'graduation_year' => 'nullable|integer|min:1950|max:2100', 'class_set' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:150', 'organisation' => 'nullable|string|max:150',
            'industry' => 'nullable|in:' . implode(',', Alumnus::INDUSTRIES), 'location' => 'nullable|string|max:150',
            'higher_institution' => 'nullable|string|max:200', 'bio' => 'nullable|string|max:2000', 'message' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:150', 'phone' => 'nullable|string|max:40', 'linkedin' => 'nullable|url|max:200',
            'status' => 'required|in:pending,approved,rejected', 'photo' => 'nullable|image|max:8192',
        ]);
        foreach ($data as $k => $v) {
            if ($k === 'photo') continue;
            $a->{$k} = is_string($v) && trim($v) === '' ? null : $v;
        }
        foreach (['show_contact', 'mentor', 'featured'] as $f) $a->{$f} = $request->boolean($f);
        if ($request->hasFile('photo')) {
            YB::deletePortrait($a->photo);
            $a->photo = YB::storePortrait($request->file('photo'), 'alumni');
        } elseif ($request->boolean('remove_photo')) {
            YB::deletePortrait($a->photo);
            $a->photo = null;
        }
        $a->save();
        return redirect()->route('alumni.admin.index')->with('success', $a->name . ' saved.');
    }

    public function destroy(int $id)
    {
        $a = Alumnus::findOrFail($id);
        YB::deletePortrait($a->photo);
        YB::deletePortrait($a->pending_photo);
        $a->delete();
        return redirect()->route('alumni.admin.index')->with('success', 'Removed from the alumni network.');
    }

    public function status(Request $request, int $id)
    {
        $a = Alumnus::findOrFail($id);
        $request->validate(['status' => 'required|in:approved,rejected,pending']);
        $a->update(['status' => $request->get('status')]);
        return back()->with('success', $a->name . ' — ' . $request->get('status') . '.');
    }

    /** Profile changes an alumnus sent through their edit link. */
    public function changes(Request $request, int $id)
    {
        $a = Alumnus::findOrFail($id);
        if ($request->get('do') === 'apply') {
            foreach ((array) $a->pending as $f => $v) if (in_array($f, Alumnus::EDITABLE, true)) $a->{$f} = $v;
            if ($a->pending_photo) {
                YB::deletePortrait($a->photo);
                $a->photo = $a->pending_photo;
            }
            $msg = 'Changes applied to ' . $a->name . '.';
        } else {
            YB::deletePortrait($a->pending_photo);
            $msg = 'Changes discarded.';
        }
        $a->forceFill(['pending' => null, 'pending_photo' => null, 'pending_at' => null])->save();
        return back()->with('success', $msg);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer', 'action' => 'required|in:approve,reject,feature,unfeature,delete']);
        $n = 0;
        foreach (Alumnus::whereIn('id', $data['ids'])->get() as $a) {
            match ($data['action']) {
                'approve' => $a->update(['status' => 'approved']),
                'reject' => $a->update(['status' => 'rejected']),
                'feature' => $a->update(['featured' => true]),
                'unfeature' => $a->update(['featured' => false]),
                'delete' => (function () use ($a) { YB::deletePortrait($a->photo); YB::deletePortrait($a->pending_photo); $a->delete(); })(),
            };
            $n++;
        }
        return back()->with('success', "{$n} updated.");
    }

    public function export(Request $request)
    {
        $rows = Alumnus::where('status', 'approved')->orderByDesc('graduation_year')->orderBy('name')->get();
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Maiden name', 'Class of', 'Set / class', 'Occupation', 'Organisation', 'Industry', 'Location', 'Higher institution', 'Email', 'Phone', 'LinkedIn', 'Mentor', 'Joined']);
            foreach ($rows as $a) {
                fputcsv($out, [$a->name, $a->maiden_name, $a->graduation_year, $a->class_set, $a->occupation, $a->organisation, $a->industry, $a->location,
                    $a->higher_institution, $a->email, $a->phone, $a->linkedin, $a->mentor ? 'Yes' : 'No', optional($a->created_at)->format('Y-m-d')]);
            }
            fclose($out);
        }, 'alumni-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** The private link an alumnus uses to update their profile (copy & send). */
    public function link(int $id)
    {
        $a = Alumnus::findOrFail($id);
        return response()->json(['url' => $a->editUrl()]);
    }
}
