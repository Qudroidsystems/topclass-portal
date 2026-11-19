{{-- resources/views/offlinecbt/index.blade.php — CBT → Offline CBT --}}
@extends('layouts.master')

@section('content')
<style>
.oc-hero { background:linear-gradient(135deg,#1e3a5f 0%,#0f766e 60%,#0891b2 100%); border-radius:12px; padding:24px 28px; margin-bottom:20px; color:#fff; }
.oc-hero h1 { font-size:21px; font-weight:700; margin:0 0 4px; color:#fff; }
.oc-hero p { margin:0; font-size:13px; opacity:.8; }
.oc-stat { border:1px solid var(--tb-border-color,#e2e8f0); border-radius:12px; padding:14px 18px; background:var(--tb-secondary-bg,#fff); }
.oc-stat .v { font-size:24px; font-weight:700; }
.oc-stat .l { font-size:12px; opacity:.65; }
.oc-step { display:flex; gap:12px; margin-bottom:10px; font-size:13px; }
.oc-step .n { flex:0 0 26px; height:26px; border-radius:50%; background:#0f766e; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; }
.oc-key { font-family:monospace; font-size:15px; background:#0f172a; color:#a7f3d0; padding:10px 14px; border-radius:8px; word-break:break-all; }
.badge-st { font-size:11px; }
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

    <div class="oc-hero">
        <h1><i class="ri-wifi-off-line me-2"></i>Offline CBT</h1>
        <p>Run CBT exams on your school Wi-Fi without internet, then upload the results here as if they were taken online.</p>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if($newKey)
    <div class="alert alert-warning">
        <div class="fw-semibold mb-2"><i class="ri-key-2-line me-1"></i>Device key for “{{ $newKey['name'] }}” — copy it now, it will not be shown again:</div>
        <div class="d-flex gap-2 align-items-center">
            <div class="oc-key flex-grow-1" id="newKey">{{ $newKey['key'] }}</div>
            <button type="button" class="btn btn-dark" onclick="navigator.clipboard.writeText(document.getElementById('newKey').innerText);this.innerText='Copied'">Copy</button>
        </div>
        <div class="small mt-2">Enter this key and the portal address <strong>{{ url('/') }}</strong> in the Offline CBT app on the admin computer.</div>
    </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="oc-stat"><div class="v">{{ (int) ($counts['synced'] ?? 0) + (int) ($counts['conflict_accepted'] ?? 0) }}</div><div class="l">Attempts uploaded</div></div></div>
        <div class="col-md-3"><div class="oc-stat"><div class="v text-warning">{{ (int) ($counts['conflict'] ?? 0) }}</div><div class="l">Conflicts to review</div></div></div>
        <div class="col-md-3"><div class="oc-stat"><div class="v text-danger">{{ (int) ($counts['rejected'] ?? 0) }}</div><div class="l">Rejected</div></div></div>
        <div class="col-md-3"><div class="oc-stat"><div class="v">{{ $devices->whereNull('revoked_at')->count() }}</div><div class="l">Authorised computers</div></div></div>
    </div>

    <div class="row g-3">
        {{-- How it works --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h6 class="fw-semibold mb-3">How it works</h6>
                <div class="oc-step"><span class="n">1</span><div>Create a <strong>device key</strong> below and enter it in the Offline CBT app on the admin computer.</div></div>
                <div class="oc-step"><span class="n">2</span><div>While online, the app <strong>downloads the exams</strong> (questions are frozen) and the students allowed to take them.</div></div>
                <div class="oc-step"><span class="n">3</span><div>Student computers join the admin computer over <strong>Wi-Fi</strong>. Students log in with their portal password or exam PIN.</div></div>
                <div class="oc-step"><span class="n">4</span><div>Answers save continuously on the student computer and the admin computer — power cuts lose nothing.</div></div>
                <div class="oc-step"><span class="n">5</span><div>When internet is back, click <strong>Sync</strong> in the app. Results appear here and in the exam results, marked by the portal. Syncing twice never duplicates.</div></div>
            </div></div>
        </div>

        {{-- Devices --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h6 class="fw-semibold mb-1">Authorised computers</h6>
                <div class="small text-muted mb-3">Latest app version: {{ $latestVersion }}
                    @if($downloadUrl) · <a href="{{ $downloadUrl }}" target="_blank" rel="noopener">download installer</a>@endif
                </div>
                <form method="POST" action="{{ route('offlinecbt.devices.store') }}" class="d-flex gap-2 mb-3">
                    @csrf
                    <input name="name" class="form-control" placeholder="e.g. ICT Lab admin PC" required maxlength="100">
                    <button class="btn btn-primary text-nowrap"><i class="ri-add-line"></i> New key</button>
                </form>
                <div class="list-group list-group-flush">
                @forelse($devices as $d)
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold {{ $d->revoked_at ? 'text-decoration-line-through text-muted' : '' }}">{{ $d->name }}</div>
                            <div class="small text-muted">{{ $d->token_hint }}… ·
                                {{ $d->last_seen_at ? 'last used ' . \Carbon\Carbon::parse($d->last_seen_at)->diffForHumans() : 'never used' }}
                                @if($d->app_version) · app {{ $d->app_version }}
                                    @if($latestVersion && version_compare($d->app_version, $latestVersion, '<'))
                                        <span class="badge bg-warning-subtle text-warning">update to {{ $latestVersion }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if(!$d->revoked_at)
                        <form method="POST" action="{{ route('offlinecbt.devices.revoke', $d->id) }}" onsubmit="return confirm('Revoke this key? That computer will no longer be able to sync.')">
                            @csrf <button class="btn btn-sm btn-outline-danger">Revoke</button>
                        </form>
                        @else <span class="badge bg-secondary-subtle text-secondary">Revoked</span> @endif
                    </div>
                @empty
                    <div class="text-muted small">No computers yet.</div>
                @endforelse
                </div>
            </div></div>
        </div>

        {{-- PINs --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h6 class="fw-semibold mb-1">Student exam PINs</h6>
                <p class="small text-muted">Students can log in offline with their admission number and either their portal password or a PIN. {{ $pinCount }} student(s) have a PIN.</p>
                <form method="POST" action="{{ route('offlinecbt.pins.generate') }}" id="pinForm">
                    @csrf
                    <div class="mb-2"><select name="class_id" class="form-select" required>
                        <option value="">Class…</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->label }}</option>@endforeach
                    </select></div>
                    <div class="row g-2 mb-2">
                        <div class="col"><select name="term_id" class="form-select" required>
                            @foreach($terms as $t)<option value="{{ $t->id }}" @selected($t->status)>{{ $t->term }}</option>@endforeach
                        </select></div>
                        <div class="col"><select name="session_id" class="form-select" required>
                            @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->status === 'Current')>{{ $s->session }}</option>@endforeach
                        </select></div>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="regenerate" value="1" id="regen">
                        <label class="form-check-label small" for="regen">Replace existing PINs</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1"><i class="ri-key-line me-1"></i>Generate PINs</button>
                        <button type="button" class="btn btn-light" id="printPins"><i class="ri-printer-line me-1"></i>Print slips</button>
                    </div>
                </form>
            </div></div>
        </div>
    </div>

    {{-- Sync log --}}
    <div class="card border-0 shadow-sm mt-3"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h6 class="fw-semibold mb-0">Uploaded attempts</h6>
            <form class="d-flex flex-wrap gap-2" method="GET">
                <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:200px" placeholder="Student, admission no., exam…">
                <select name="exam" class="form-select form-select-sm" style="width:200px">
                    <option value="">All exams</option>
                    @foreach($examsWithSyncs as $e)<option value="{{ $e->id }}" @selected(request('exam') == $e->id)>{{ $e->title }}</option>@endforeach
                </select>
                <select name="status" class="form-select form-select-sm" style="width:170px">
                    <option value="">All statuses</option>
                    @foreach(['synced' => 'Uploaded', 'conflict' => 'Conflict (review)', 'conflict_accepted' => 'Conflict – accepted', 'conflict_discarded' => 'Conflict – discarded', 'rejected' => 'Rejected'] as $k => $v)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
                @if(request()->hasAny(['q','exam','status']))<a href="{{ route('offlinecbt.index') }}" class="btn btn-sm btn-light">Clear</a>@endif
            </form>
        </div>
        <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Student</th><th>Exam</th><th>Score</th><th>Taken</th><th>Computer</th><th title="Times the student left the exam window">Left window</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($syncs as $s)
                @php
                    $badge = ['synced' => 'success', 'conflict' => 'warning', 'conflict_accepted' => 'info', 'conflict_discarded' => 'secondary', 'rejected' => 'danger'][$s->status] ?? 'secondary';
                    $label = ['synced' => 'Uploaded', 'conflict' => 'Conflict', 'conflict_accepted' => 'Accepted', 'conflict_discarded' => 'Discarded', 'rejected' => 'Rejected'][$s->status] ?? $s->status;
                @endphp
                <tr>
                    <td><div class="fw-semibold">{{ $s->student_name ?: 'User #'.$s->user_id }}</div><div class="small text-muted">{{ $s->admissionNo }}</div></td>
                    <td>{{ $s->exam_title ?? 'Exam #'.$s->exam_id }}</td>
                    <td class="text-nowrap">{{ $s->score !== null ? rtrim(rtrim(number_format($s->score, 2), '0'), '.') . ' / ' . rtrim(rtrim(number_format($s->total_marks, 2), '0'), '.') : '—' }}</td>
                    <td class="small text-nowrap">{{ $s->submitted_at ? \Carbon\Carbon::parse($s->submitted_at)->format('d M Y H:i') : '—' }}
                        @if($s->outside_window)<br><span class="text-warning" title="Taken outside the exam's online start/end time">outside exam window</span>@endif</td>
                    <td class="small">{{ $s->device_name }}@if($s->workstation)<br><span class="text-muted">{{ $s->workstation }}</span>@endif</td>
                    <td class="text-center">@php $fl = (int) ($s->focus_lost ?? 0); @endphp
                        <span class="badge {{ $fl === 0 ? 'bg-success-subtle text-success' : ($fl < 3 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}">{{ $fl }}</span></td>
                    <td><span class="badge badge-st bg-{{ $badge }}-subtle text-{{ $badge }}">{{ $label }}</span>
                        @if($s->message)<div class="small text-muted" style="max-width:260px">{{ $s->message }}</div>@endif</td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-light view-answers" data-url="{{ route('offlinecbt.syncs.show', $s->id) }}"><i class="ri-eye-line"></i></button>
                        @if($s->status === 'conflict')
                        <form method="POST" action="{{ route('offlinecbt.syncs.resolve', $s->id) }}" class="d-inline" onsubmit="return confirm(this.decision.value==='accept' ? 'Replace the student\'s existing attempt and result with this offline attempt?' : 'Discard this offline attempt and keep the existing one?')">
                            @csrf <input type="hidden" name="decision" value="">
                            <button class="btn btn-sm btn-success" onclick="this.form.decision.value='accept'">Use offline</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="this.form.decision.value='discard'">Keep existing</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Nothing uploaded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $syncs->links() }}
    </div></div>

</div></div></div>

<div class="modal fade" id="answersModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Offline answers</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="answersBody">Loading…</div>
</div></div></div>

<script>
document.getElementById('printPins').addEventListener('click', function () {
    var f = document.getElementById('pinForm');
    if (!f.class_id.value) { f.class_id.focus(); return; }
    var q = new URLSearchParams({ class_id: f.class_id.value, term_id: f.term_id.value, session_id: f.session_id.value });
    window.open('{{ route('offlinecbt.pins.print') }}?' + q.toString(), '_blank');
});
document.querySelectorAll('.view-answers').forEach(function (b) {
    b.addEventListener('click', function () {
        var body = document.getElementById('answersBody');
        body.textContent = 'Loading…';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('answersModal')).show();
        fetch(b.dataset.url, { headers: { 'Accept': 'application/json' } }).then(r => r.json()).then(function (d) {
            var html = '<ol class="ps-3">';
            d.answers.forEach(function (a) {
                var q = document.createElement('div'); q.textContent = a.question;
                var ans = document.createElement('div'); ans.textContent = a.answer === null || a.answer === '' ? '— no answer —' : a.answer;
                html += '<li class="mb-2"><div>' + q.innerHTML + '</div><div class="fw-semibold text-primary">' + ans.innerHTML + '</div></li>';
            });
            body.innerHTML = html + '</ol>';
        }).catch(function () { body.textContent = 'Could not load answers.'; });
    });
});
</script>
@endsection
