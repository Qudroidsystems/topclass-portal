@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Former Students</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('student.index') }}">Students</a></li>
                                <li class="breadcrumb-item active">Former Students</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light border small">
                Students who have left the school. They no longer appear in class lists, promotion or score entry from the
                term they left, but their earlier results are kept. To record a leaver, tick them on
                <a href="{{ route('promotions.index') }}">Promotion Management</a> and click <strong>Mark as Left</strong>.
                <strong>Reactivate</strong> brings a student back and restores the class places removed when they left.
            </div>

            {{-- Totals per status --}}
            <div class="row g-3 mb-3">
                @foreach($exitStatuses as $st)
                    <div class="col-6 col-md-3">
                        <a href="{{ route('students.former', ['status' => $st]) }}" class="card mb-0 text-decoration-none">
                            <div class="card-body py-3">
                                <div class="text-muted small">{{ $st }}</div>
                                <div class="fs-4 fw-bold">{{ $counts[$st] ?? 0 }}</div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('students.former') }}" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All statuses</option>
                                @foreach($exitStatuses as $st)
                                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="session_id" class="form-select">
                                <option value="">Any session</option>
                                @foreach($schoolsessions as $s)
                                    <option value="{{ $s->id }}" @selected(request('session_id') == $s->id)>Left in {{ $s->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name or admission no">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button class="btn btn-primary"><i class="ri-search-line me-1"></i>Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Admission No</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Last class</th>
                                    <th>Left from</th>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th>Recorded by</th>
                                    <th style="width:120px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $st)
                                    <tr id="former-row-{{ $st->id }}">
                                        <td class="fw-medium">{{ $st->admissionno }}</td>
                                        <td>{{ $st->lastname }}, {{ $st->firstname }} {{ $st->othername }}</td>
                                        <td><span class="badge bg-danger-subtle text-danger">{{ $st->student_status }}</span></td>
                                        <td>{{ $st->schoolclass ? trim($st->schoolclass . ' ' . $st->arm) : '—' }}</td>
                                        <td>{{ $st->session ? $st->session . ', ' . $st->term : '—' }}</td>
                                        <td>{{ $st->exit_date ? \Illuminate\Support\Carbon::parse($st->exit_date)->format('d M Y') : '—' }}</td>
                                        <td class="small">
                                            {{ $st->exit_reason ?: '—' }}
                                            @if($st->exit_destination)
                                                <div class="text-muted">To: {{ $st->exit_destination }}</div>
                                            @endif
                                        </td>
                                        <td class="small">{{ $st->recorded_by ?? '—' }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-subtle-secondary" title="Status history"
                                                        onclick="showStatusHistory({{ $st->id }}, @js($st->lastname . ', ' . $st->firstname))">
                                                    <i class="ri-history-line"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-subtle-success" title="Reactivate"
                                                        onclick="reactivateStudent({{ $st->id }}, @js($st->lastname . ', ' . $st->firstname))">
                                                    <i class="ri-user-follow-line me-1"></i>Reactivate
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-5">No former students found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $students->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Status history: <span id="statusHistoryName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statusHistoryBody"></div>
        </div>
    </div>
</div>

<script>
const FORMER_CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

async function reactivateStudent(id, name) {
    const note = prompt(`Reactivate ${name}? They will be Active again and put back in the classes they were removed from.\n\nOptional note:`, '');
    if (note === null) return;
    const res = await fetch(`{{ url('students') }}/${id}/reactivate`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': FORMER_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ note }),
    });
    const data = await res.json().catch(() => ({}));
    alert(data.message || (res.ok ? 'Done' : 'Could not reactivate the student.'));
    if (res.ok && data.success) document.getElementById(`former-row-${id}`)?.remove();
}

async function showStatusHistory(id, name) {
    document.getElementById('statusHistoryName').innerText = name;
    const body = document.getElementById('statusHistoryBody');
    body.innerHTML = '<p class="text-muted">Loading…</p>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('statusHistoryModal')).show();
    const res  = await fetch(`{{ url('students') }}/${id}/status-history`, { headers: { 'Accept': 'application/json' } });
    const data = await res.json().catch(() => ({}));
    const rows = data.history || [];
    body.innerHTML = rows.length ? `<table class="table table-sm mb-0">
        <thead><tr><th>When</th><th>Change</th><th>From term</th><th>Last class</th><th>Note</th><th>By</th></tr></thead>
        <tbody>${rows.map(r => `<tr>
            <td>${esc((r.created_at || '').slice(0, 10))}</td>
            <td>${esc(r.from_status || '—')} → <strong>${esc(r.to_status)}</strong></td>
            <td>${r.session ? esc(r.session + ', ' + r.term) : '—'}</td>
            <td>${esc(r.schoolclass || '—')}</td>
            <td>${esc(r.reason || '')}${r.destination ? '<br><small class="text-muted">To: ' + esc(r.destination) + '</small>' : ''}</td>
            <td>${esc(r.changed_by || '—')}</td>
        </tr>`).join('')}</tbody></table>` : '<p class="text-muted mb-0">No history recorded.</p>';
}
</script>
@endsection
