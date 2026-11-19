@extends('layouts.master')

@php $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p); @endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Yearbook & Alumni" icon="ri-book-open-line" subtitle="Yearbooks for each graduating set — graduation photos, profiles, testimonials and awards — and the alumni network." />
    @include('website.admin.yearbooks._nav')

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Yearbooks" :value="$books->count()" icon="ri-book-open-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Graduates" :value="$books->sum('entries_count')" icon="ri-team-line" accent="indigo" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Photos awaiting approval" :value="$books->sum('pending_count')" icon="ri-time-line" accent="amber" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Alumni members" :value="$alumniCount" icon="ri-graduation-cap-line" accent="rose" :hint="$alumniPending ? $alumniPending . ' waiting' : null" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-cb.card title="New yearbook" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('yearbook.admin.store') }}">@csrf
                    <label class="form-label">Graduation year *</label>
                    <input type="number" name="graduation_year" class="form-control mb-2" required min="1950" max="2100" value="{{ date('Y') }}">
                    <label class="form-label">Graduating class</label>
                    <input name="class_label" class="form-control mb-2" placeholder="e.g. SS 3, JSS 3 or Primary 6">
                    <label class="form-label">Session</label>
                    <select name="session_id" class="form-select mb-2"><option value="">—</option>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->session }}{{ $s->status === 'Current' ? ' (current)' : '' }}</option>@endforeach</select>
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control mb-1" placeholder="Leave empty for “Class of {{ date('Y') }}”">
                    <div class="form-text mb-3">You can add a set name, e.g. “Class of {{ date('Y') }} — The Trailblazers”.</div>
                    <button class="btn btn-primary w-100"><i class="ri-add-line me-1"></i>Create yearbook</button>
                </form>
            </x-cb.card>
            <x-cb.card title="How it works" icon="ri-lightbulb-line">
                <ol class="small text-muted mb-0 ps-3">
                    <li class="mb-1">Create a yearbook and <b>import the graduating students</b> from their class.</li>
                    <li class="mb-1"><b>Upload graduation photos</b> (gown, suit or attire) one by one or many at once, named by admission number.</li>
                    <li class="mb-1">Or <b>send each student / parent their private upload link</b> — through the portal, WhatsApp or a CSV for SMS.</li>
                    <li class="mb-1"><b>Approve</b> the photos and details they send.</li>
                    <li class="mb-1">Add prefect positions, prizes and the school’s testimonial, then <b>publish</b>.</li>
                    <li>Add the set to the <b>alumni network</b> when they leave.</li>
                </ol>
            </x-cb.card>
        </div>
        <div class="col-xl-8">
            <x-cb.card title="Yearbooks" icon="ri-book-2-line" :count="$books->count()">
                @if($books->isEmpty())<p class="text-muted mb-0">No yearbooks yet. Create the first one on the left.</p>@endif
                <div class="row g-3">
                    @foreach($books as $b)
                        @php $pct = $b->entries_count ? round($b->photos_count / $b->entries_count * 100) : 0; @endphp
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('yearbook.admin.show', $b->id) }}" class="d-block border rounded-3 overflow-hidden text-reset h-100">
                                <div style="aspect-ratio:4/3;background:linear-gradient(150deg,#0b3d2e,#062019);position:relative">
                                    @if($b->cover ?: $b->class_photo)<img src="{{ $F($b->cover ?: $b->class_photo) }}" style="width:100%;height:100%;object-fit:cover;opacity:.75">@endif
                                    <div style="position:absolute;left:14px;bottom:10px;color:#fff;line-height:1">
                                        <div style="font-size:.65rem;letter-spacing:.25em;text-transform:uppercase;opacity:.85">Class of</div>
                                        <div style="font-size:2.2rem;font-weight:800">{{ $b->graduation_year }}</div>
                                    </div>
                                    <div style="position:absolute;right:10px;top:10px" class="d-flex gap-1">
                                        @if($b->published)<span class="badge bg-success">Published</span>@else<span class="badge bg-secondary">Draft</span>@endif
                                        @if($b->isPortalOnly())<span class="badge bg-dark"><i class="ri-lock-line"></i> Portal</span>@endif
                                    </div>
                                </div>
                                <div class="p-2">
                                    <div class="fw-semibold">{{ $b->title }}</div>
                                    <div class="small text-muted">{{ $b->entries_count }} graduates · {{ $b->photos_count }} graduation photos
                                        @if($b->pending_count)<span class="badge bg-warning text-dark ms-1">{{ $b->pending_count }} to approve</span>@endif</div>
                                    <div class="progress mt-2" style="height:5px" title="{{ $pct }}% have a graduation photo"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
