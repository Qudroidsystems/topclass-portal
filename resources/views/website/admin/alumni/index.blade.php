@extends('layouts.master')

@php
    $T = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl(\App\Services\Website\Yearbooks::thumb($p));
    $tab = fn ($s, $label, $n) => '<a href="' . route('alumni.admin.index', array_filter(['status' => $s, 'q' => request('q'), 'year' => request('year')])) . '" class="btn btn-sm ' . ($status === $s ? 'btn-primary' : 'btn-light') . '">' . e($label) . ' <span class="badge bg-white text-dark ms-1">' . $n . '</span></a>';
@endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Alumni network" icon="ri-graduation-cap-line" subtitle="Old students who joined from the website or were added from a yearbook. Approve new members and profile changes before they show.">
        <x-slot:actions>
            <a class="cb-hero-btn" href="{{ route('alumni.admin.create') }}"><i class="ri-user-add-line me-1"></i>Add alumnus</a>
            <a class="cb-hero-btn" href="{{ route('alumni.admin.export') }}"><i class="ri-file-excel-2-line me-1"></i>Export CSV</a>
        </x-slot:actions>
    </x-cb.hero>
    @include('website.admin.yearbooks._nav')

    <x-cb.card title="Members" icon="ri-team-line" :count="$alumni->total()">
        <x-slot:tools>
            <form class="d-flex gap-2" method="GET">
                <input type="hidden" name="status" value="{{ $status }}">
                <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()"><option value="">Any year</option>@foreach($years as $y)<option @selected(request('year') == $y)>{{ $y }}</option>@endforeach</select>
                <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search…" style="width:180px">
            </form>
        </x-slot:tools>
        <div class="d-flex flex-wrap gap-2 mb-3">
            {!! $tab('all', 'All', $counts['all']) !!}
            {!! $tab('pending', 'Waiting for approval', $counts['pending']) !!}
            {!! $tab('approved', 'Listed', $counts['approved']) !!}
            {!! $tab('mentors', 'Mentors', $counts['mentors']) !!}
            {!! $tab('rejected', 'Rejected', \App\Models\Website\Alumnus::where('status', 'rejected')->count()) !!}
        </div>

        @if($alumni->isEmpty())
            <p class="text-muted mb-0">No alumni here yet. Share the “Join” page — <a href="{{ route('site.alumni.join') }}" target="_blank">{{ route('site.alumni.join') }}</a> — or add a graduating set from its yearbook (select graduates › “Add to alumni network”).</p>
        @else
            <form method="POST" action="{{ route('alumni.admin.bulk') }}" id="alBulk" class="d-flex flex-wrap gap-2 align-items-center mb-2 p-2 bg-light rounded">@csrf
                <label class="mb-0 small"><input type="checkbox" id="alAll"> Select all</label>
                <select name="action" class="form-select form-select-sm" style="width:auto" required>
                    <option value="">With selected…</option><option value="approve">Approve (list)</option><option value="reject">Reject</option>
                    <option value="feature">Spotlight</option><option value="unfeature">Remove spotlight</option><option value="delete">Delete</option>
                </select>
                <button class="btn btn-sm btn-primary" onclick="return this.form.action.value!=='delete'||confirm('Delete the selected alumni?')">Apply</button>
            </form>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th style="width:30px"></th><th>Name</th><th>Class of</th><th>Now</th><th>Contact</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach($alumni as $a)
                            <tr class="{{ $a->status === 'pending' || $a->pending_at ? 'table-warning' : '' }}">
                                <td><input type="checkbox" class="form-check-input al-sel" name="ids[]" value="{{ $a->id }}" form="alBulk"></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($a->displayPhoto())<img src="{{ $T($a->displayPhoto()) }}" style="width:38px;height:38px;border-radius:50%;object-fit:cover">@else<span class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle fw-bold text-muted" style="width:38px;height:38px">{{ $a->initials() }}</span>@endif
                                        <div><a href="{{ route('alumni.admin.edit', $a->id) }}" class="fw-semibold text-reset">{{ $a->name }}</a>
                                            @if($a->featured)<i class="ri-star-fill text-warning" title="Spotlight"></i>@endif
                                            @if($a->mentor)<span class="badge bg-success-subtle text-success">Mentor</span>@endif
                                            <div class="small text-muted">{{ $a->entry ? 'From yearbook' : 'Joined online' }} · {{ $a->created_at?->format('d M Y') }}</div></div>
                                    </div>
                                </td>
                                <td>{{ $a->graduation_year }}<div class="small text-muted">{{ $a->class_set }}</div></td>
                                <td class="small">{{ $a->headline() }}<div class="text-muted">{{ $a->location }}</div></td>
                                <td class="small">{{ $a->email }}<div>{{ $a->phone }}</div></td>
                                <td>
                                    <span class="badge {{ ['approved' => 'bg-success', 'pending' => 'bg-warning text-dark', 'rejected' => 'bg-secondary'][$a->status] ?? 'bg-light' }}">{{ ['approved' => 'Listed', 'pending' => 'Waiting', 'rejected' => 'Rejected'][$a->status] ?? $a->status }}</span>
                                    @if($a->pending_at)<span class="badge bg-warning text-dark">Profile changes</span>@endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if($a->status !== 'approved')
                                        <form method="POST" action="{{ route('alumni.admin.status', $a->id) }}" class="d-inline">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-success" title="Approve"><i class="ri-check-line"></i></button></form>
                                    @endif
                                    <a href="{{ route('alumni.admin.edit', $a->id) }}" class="btn btn-sm btn-light"><i class="ri-edit-line"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $alumni->links() }}</div>
        @endif
    </x-cb.card>
</div></div></div>
@endsection

@push('scripts')
<script>
var alAll = document.getElementById('alAll');
if (alAll) alAll.addEventListener('change', function () { document.querySelectorAll('.al-sel').forEach(function (c) { c.checked = alAll.checked; }); });
</script>
@endpush
