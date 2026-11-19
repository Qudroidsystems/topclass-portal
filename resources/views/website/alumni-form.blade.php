@extends('website.layout', ['pageTitle' => $mode === 'join' ? 'Join the alumni network' : 'Update your alumni profile'])

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $v = fn ($f) => old($f, $a->{$f});
    $action = $mode === 'join' ? route('site.alumni.join.save') : route('site.alumni.edit.save', $token);
    $photo = $a->pending_photo ?: $a->photo;
@endphp

@push('head')
    @if($mode === 'edit')<meta name="robots" content="noindex, nofollow">@endif
@endpush

@section('content')
    @include('website.partials.page-hero', [
        'title' => $mode === 'join' ? 'Join the alumni network' : 'Update your profile',
        'lead' => $mode === 'join' ? 'Reconnect with your classmates, share what you are doing now, and inspire the students coming after you.' : 'Keep your alumni profile up to date.',
        'crumbs' => [['Alumni', route('site.alumni')], [$mode === 'join' ? 'Join' : 'Update', null]],
    ])

    <section class="section">
        <div class="container" style="max-width:860px">
            @if(session('alumni_link'))
                <div class="yb-note" style="margin-bottom:26px;border:1px solid var(--line)">
                    <b><i class="ri-bookmark-line"></i> Save this private link</b> — use it any time to update your profile:
                    <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                        <input readonly value="{{ session('alumni_link') }}" id="alLink" style="flex:1;min-width:240px;border:1.5px solid var(--line);border-radius:10px;padding:10px 12px;font:inherit">
                        <button type="button" class="btn btn-primary btn-sm" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('alLink').value);this.textContent='Copied'">Copy</button>
                    </div>
                </div>
            @endif
            @if($mode === 'edit' && $a->pending_at)
                <div class="yb-note" style="margin-bottom:20px"><i class="ri-time-line"></i> Your last changes ({{ $a->pending_at->diffForHumans() }}) are waiting for the school’s approval.</div>
            @elseif($mode === 'edit' && $a->status === 'pending')
                <div class="yb-note" style="margin-bottom:20px"><i class="ri-time-line"></i> Your profile is waiting for the school to confirm it.</div>
            @endif

            <form class="form panel" method="POST" action="{{ $action }}" enctype="multipart/form-data">@csrf
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
                <h3 style="margin:0">About you</h3>
                <div class="row2">
                    <div><label>Full name *</label><input name="name" required maxlength="150" value="{{ $v('name') }}"></div>
                    <div><label>Name while in school (if different)</label><input name="maiden_name" maxlength="100" value="{{ $v('maiden_name') }}"></div>
                </div>
                <div class="row2">
                    <div><label>Year you graduated *</label><input type="number" name="graduation_year" required min="1950" max="{{ date('Y') + 1 }}" value="{{ $v('graduation_year') }}" placeholder="e.g. 2015"></div>
                    <div><label>Last class / set</label><input name="class_set" maxlength="100" value="{{ $v('class_set') }}" placeholder="e.g. SS 3 Science A"></div>
                </div>
                <div>
                    <label>Your photo</label>
                    <div style="display:flex;gap:14px;align-items:center">
                        @if($photo)<img src="{{ $F(\App\Services\Website\Yearbooks::thumb($photo)) }}" alt="" style="width:70px;height:70px;border-radius:99px;object-fit:cover">@endif
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                    </div>
                    @error('photo')<div class="err">{{ $message }}</div>@enderror
                </div>

                <h3 style="margin:14px 0 0">What you do now</h3>
                <div class="row2">
                    <div><label>Occupation / role</label><input name="occupation" maxlength="150" value="{{ $v('occupation') }}" placeholder="e.g. Software engineer"></div>
                    <div><label>Company / organisation</label><input name="organisation" maxlength="150" value="{{ $v('organisation') }}"></div>
                </div>
                <div class="row2">
                    <div><label>Field</label><select name="industry"><option value="">Choose…</option>@foreach(\App\Models\Website\Alumnus::INDUSTRIES as $ind)<option @selected($v('industry') === $ind)>{{ $ind }}</option>@endforeach</select></div>
                    <div><label>City / country</label><input name="location" maxlength="150" value="{{ $v('location') }}" placeholder="e.g. Lagos, Nigeria"></div>
                </div>
                <div><label>Higher institution(s) attended</label><input name="higher_institution" maxlength="200" value="{{ $v('higher_institution') }}" placeholder="e.g. University of Ibadan — B.Sc. Microbiology"></div>
                <div><label>Short bio</label><textarea name="bio" rows="4" maxlength="2000" placeholder="Your journey since leaving school">{{ $v('bio') }}</textarea></div>
                <div><label>A message to current students</label><textarea name="message" rows="2" maxlength="1000">{{ $v('message') }}</textarea></div>

                <h3 style="margin:14px 0 0">Contact</h3>
                <div class="row2">
                    <div><label>Email</label><input type="email" name="email" maxlength="150" value="{{ $v('email') }}"></div>
                    <div><label>Phone / WhatsApp</label><input name="phone" maxlength="40" value="{{ $v('phone') }}"></div>
                </div>
                <div><label>LinkedIn profile (link)</label><input type="url" name="linkedin" maxlength="200" value="{{ $v('linkedin') }}" placeholder="https://www.linkedin.com/in/…"></div>
                @foreach(['email', 'linkedin', 'graduation_year', 'name'] as $f)@error($f)<div class="err">{{ $message }}</div>@enderror @endforeach
                <label class="yb-check"><input type="checkbox" name="show_contact" value="1" @checked(old('show_contact', $a->show_contact))> <span>Show my email, phone and LinkedIn on my alumni profile so classmates can reach me. (Otherwise only the school sees them.)</span></label>
                <label class="yb-check"><input type="checkbox" name="mentor" value="1" @checked(old('mentor', $a->mentor))> <span>I’m happy to mentor current students or give career talks.</span></label>
                @if($mode === 'join')
                    <label class="yb-check"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))> <span>I attended this school and agree that my profile may be listed in the alumni directory on the school website.</span></label>
                    @error('consent')<div class="err">{{ $message }}</div>@enderror
                @endif
                <button class="btn btn-primary" style="justify-self:start">{{ $mode === 'join' ? 'Join the network' : 'Save my profile' }}</button>
            </form>
        </div>
    </section>
@endsection
