@extends('layouts.master')

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $T = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl(\App\Services\Website\Yearbooks::thumb($p));
    $v = fn ($f) => old($f, $a->{$f});
    $isNew = !$a->exists;
    $pending = (array) $a->pending;
@endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$isNew ? 'Add alumnus' : $a->name" icon="ri-graduation-cap-line" :subtitle="$isNew ? 'Add an old student to the alumni network' : ('Class of ' . $a->graduation_year)" :back="route('alumni.admin.index')" back-label="Alumni">
        <x-slot:actions>
            @if(!$isNew && $a->status === 'approved')
                <a class="cb-hero-btn" target="_blank" href="{{ route('site.alumnus', $a->id) }}"><i class="ri-external-link-line me-1"></i>View profile</a>
            @endif
        </x-slot:actions>
    </x-cb.hero>
    @include('website.admin.yearbooks._nav')

    @if(!$isNew && $a->status === 'pending')
        <div class="cb-banner warning"><i class="ri-time-line"></i><div class="d-flex flex-wrap gap-2 align-items-center w-100">
            <span class="me-auto"><b>{{ $a->name }}</b> joined online {{ $a->created_at?->diffForHumans() }} and is waiting to be listed. Check they are a genuine old student.</span>
            <form method="POST" action="{{ route('alumni.admin.status', $a->id) }}">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-success">Approve &amp; list</button></form>
            <form method="POST" action="{{ route('alumni.admin.status', $a->id) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
        </div></div>
    @endif

    @if(!$isNew && $a->pending_at)
        <x-cb.card title="Profile changes waiting for approval" icon="ri-edit-box-line" class="border border-warning">
            <div class="row g-3">
                @if($a->pending_photo)<div class="col-md-2"><img src="{{ $T($a->pending_photo) }}" class="w-100 rounded-3"><div class="small text-muted text-center">New photo</div></div>@endif
                <div class="col">
                    <table class="table table-sm small">
                        @foreach($pending as $k => $val)
                            @php $old = $a->{$k}; $changed = (string) $old !== (string) (is_bool($val) ? (int) $val : $val); @endphp
                            @if($changed)<tr><th style="width:170px" class="text-capitalize">{{ str_replace('_', ' ', $k) }}</th><td>{{ is_bool($val) ? ($val ? 'Yes' : 'No') : $val }}<div class="text-muted">was: {{ is_bool($old) ? ($old ? 'Yes' : 'No') : ($old ?: '—') }}</div></td></tr>@endif
                        @endforeach
                    </table>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('alumni.admin.changes', $a->id) }}">@csrf<input type="hidden" name="do" value="apply"><button class="btn btn-success btn-sm">Apply changes</button></form>
                        <form method="POST" action="{{ route('alumni.admin.changes', $a->id) }}">@csrf<input type="hidden" name="do" value="discard"><button class="btn btn-outline-danger btn-sm">Discard</button></form>
                    </div>
                </div>
            </div>
        </x-cb.card>
    @endif

    <form method="POST" action="{{ $isNew ? route('alumni.admin.store') : route('alumni.admin.save', $a->id) }}" enctype="multipart/form-data">@csrf
        <div class="row g-3">
            <div class="col-xl-4">
                <x-cb.card title="Photo" icon="ri-image-line">
                    @if($a->displayPhoto())<img src="{{ $F($a->displayPhoto()) }}" class="w-100 rounded-3 mb-2" style="aspect-ratio:1;object-fit:cover;object-position:top">@endif
                    <input type="file" name="photo" accept="image/*" class="form-control">
                    @if($a->photo)<label class="small mt-1"><input type="checkbox" name="remove_photo" value="1"> Remove photo</label>@endif
                    @if($a->entry)<div class="small text-muted mt-2">Yearbook: <a href="{{ route('yearbook.admin.entry', [$a->entry->yearbook_id, $a->entry->id]) }}">{{ optional($a->entry->yearbook)->title }}</a></div>@endif
                </x-cb.card>
                <x-cb.card title="Listing" icon="ri-eye-line">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select mb-2">@foreach(['approved' => 'Listed (approved)', 'pending' => 'Waiting for approval', 'rejected' => 'Rejected'] as $k => $l)<option value="{{ $k }}" @selected($v('status') === $k)>{{ $l }}</option>@endforeach</select>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="featured" value="1" id="af" @checked(old('featured', $a->featured))><label class="form-check-label" for="af">Alumni spotlight (needs a bio)</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="mentor" value="1" id="am" @checked(old('mentor', $a->mentor))><label class="form-check-label" for="am">Mentor for students</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="show_contact" value="1" id="ac" @checked(old('show_contact', $a->show_contact))><label class="form-check-label" for="ac">Show contact details publicly</label></div>
                    <div class="form-text">Only switch this on if they agreed.</div>
                </x-cb.card>
                @unless($isNew)
                    <x-cb.card title="Their update link" icon="ri-links-line">
                        <p class="small text-muted mb-2">Send this private link so they can update their own profile.</p>
                        <div class="input-group input-group-sm"><input class="form-control" readonly value="{{ $a->editUrl() }}" id="aul"><button type="button" class="btn btn-outline-secondary" onclick="var i=document.getElementById('aul');i.select();(navigator.clipboard&&window.isSecureContext)?navigator.clipboard.writeText(i.value):document.execCommand('copy');this.innerHTML='Copied'">Copy</button></div>
                        @php $p = \App\Services\Website\Yearbooks::waPhone($a->phone); @endphp
                        @if($p)<a class="btn btn-sm btn-success mt-2" target="_blank" rel="noopener" href="https://wa.me/{{ $p }}?text={{ rawurlencode('Hello ' . $a->name . ', here is your private link to update your alumni profile: ' . $a->editUrl()) }}"><i class="ri-whatsapp-line me-1"></i>WhatsApp</a>@endif
                    </x-cb.card>
                @endunless
            </div>
            <div class="col-xl-8">
                <x-cb.card title="Profile" icon="ri-profile-line">
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Full name *</label><input name="name" class="form-control" required maxlength="150" value="{{ $v('name') }}"></div>
                        <div class="col-md-6"><label class="form-label">Name while in school</label><input name="maiden_name" class="form-control" maxlength="100" value="{{ $v('maiden_name') }}"></div>
                        <div class="col-md-4"><label class="form-label">Class of</label><input type="number" name="graduation_year" class="form-control" min="1950" max="2100" value="{{ $v('graduation_year') }}"></div>
                        <div class="col-md-8"><label class="form-label">Set / last class</label><input name="class_set" class="form-control" maxlength="100" value="{{ $v('class_set') }}"></div>
                        <div class="col-md-6"><label class="form-label">Occupation</label><input name="occupation" class="form-control" maxlength="150" value="{{ $v('occupation') }}"></div>
                        <div class="col-md-6"><label class="form-label">Organisation</label><input name="organisation" class="form-control" maxlength="150" value="{{ $v('organisation') }}"></div>
                        <div class="col-md-6"><label class="form-label">Field</label><select name="industry" class="form-select"><option value="">—</option>@foreach(\App\Models\Website\Alumnus::INDUSTRIES as $i)<option @selected($v('industry') === $i)>{{ $i }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Location</label><input name="location" class="form-control" maxlength="150" value="{{ $v('location') }}"></div>
                        <div class="col-12"><label class="form-label">Higher institution(s)</label><input name="higher_institution" class="form-control" maxlength="200" value="{{ $v('higher_institution') }}"></div>
                        <div class="col-12"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="4" maxlength="2000">{{ $v('bio') }}</textarea></div>
                        <div class="col-12"><label class="form-label">Message to current students</label><textarea name="message" class="form-control" rows="2" maxlength="1000">{{ $v('message') }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" maxlength="150" value="{{ $v('email') }}"></div>
                        <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" maxlength="40" value="{{ $v('phone') }}"></div>
                        <div class="col-md-4"><label class="form-label">LinkedIn</label><input type="url" name="linkedin" class="form-control" maxlength="200" value="{{ $v('linkedin') }}"></div>
                    </div>
                </x-cb.card>
                <button class="btn btn-primary"><i class="ri-save-3-line me-1"></i>Save</button>
            </div>
        </div>
    </form>
    @unless($isNew)
        <form method="POST" action="{{ route('alumni.admin.destroy', $a->id) }}" class="text-end" onsubmit="return confirm('Remove from the alumni network?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
    @endunless
</div></div></div>
@endsection
