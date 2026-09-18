{{-- resources/views/holidays/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --hol-navy:#1e1b4b; --hol-amber:#d97706; --hol-gold:#f59e0b;
    --hol-border:#e2e8f0; --hol-muted:#64748b; --hol-radius:14px;
    --hol-shadow:0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}

.hol-hero {
    background: linear-gradient(135deg, var(--hol-navy) 0%, #4c1d95 55%, var(--hol-amber) 100%);
    border-radius: var(--hol-radius); padding: 24px 28px; margin-bottom: 20px; color: #fff;
    display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;
}
.hol-hero h1 { font-size: 20px; font-weight: 700; margin: 0 0 4px; display:flex; align-items:center; gap:10px; }
.hol-hero p  { font-size: 13px; opacity: .78; margin: 0; max-width: 520px; }
.hol-hero .pills { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.hol-pill { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.24); border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; }
.hol-hero .hol-add-btn {
    background:#fff; color:var(--hol-navy); border:none; border-radius:10px; padding:10px 18px;
    font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px; white-space:nowrap;
    box-shadow: 0 2px 8px rgba(0,0,0,.15); transition: all .15s;
}
.hol-hero .hol-add-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.2); color:var(--hol-navy); }

.hol-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
.hol-stat { background: #fff; border: 1px solid var(--hol-border); border-radius: var(--hol-radius); box-shadow: var(--hol-shadow); padding: 14px 16px; display:flex; align-items:center; gap:12px; }
.hol-stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.hol-stat-icon.total    { background:#EEF2FF; color:#4338ca; }
.hol-stat-icon.upcoming { background:#FEF3C7; color:#b45309; }
.hol-stat-icon.full     { background:#FEE2E2; color:#b91c1c; }
.hol-stat-icon.half     { background:#FEF9C3; color:#a16207; }
.hol-stat .v { font-size: 22px; font-weight: 700; color: var(--hol-navy); line-height:1.1; }
.hol-stat .l { font-size: 11px; color: var(--hol-muted); text-transform: uppercase; margin-top: 2px; letter-spacing:.02em; }

.hol-toolbar {
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px;
    background: #fff; border: 1px solid var(--hol-border); border-radius: var(--hol-radius);
    padding: 12px 16px;
}
.hol-toolbar .search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 320px; }
.hol-toolbar .search-wrap input { width: 100%; padding: 8px 12px 8px 34px; border: 1.5px solid var(--hol-border); border-radius: 8px; font-size: 13px; }
.hol-toolbar .search-wrap i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--hol-muted); }
.hol-toolbar select { border: 1.5px solid var(--hol-border); border-radius: 8px; padding: 7px 10px; font-size: 12.5px; min-width: 140px; }
.hol-toolbar .hol-clear { font-size:12.5px; color:var(--hol-muted); text-decoration:none; margin-left:auto; }
.hol-toolbar .hol-clear:hover { color:#dc2626; }

.hol-section-title { font-size: 14px; font-weight: 700; color: var(--hol-navy); margin: 0 0 12px; display:flex; align-items:center; gap:8px; }

.hol-upcoming-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px; margin-bottom: 24px; }
.hol-up-card {
    background:#fff; border:1px solid var(--hol-border); border-radius: var(--hol-radius); box-shadow: var(--hol-shadow);
    padding:14px; text-align:center; position:relative; overflow:hidden;
}
.hol-up-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; }
.hol-up-card.full::before { background: linear-gradient(90deg, #ef4444, #f97316); }
.hol-up-card.half::before { background: linear-gradient(90deg, #f59e0b, #eab308); }
.hol-up-date { font-size:11px; font-weight:700; color:var(--hol-muted); text-transform:uppercase; letter-spacing:.03em; margin-top:6px; }
.hol-up-title { font-size:14px; font-weight:700; color:var(--hol-navy); margin:6px 0 8px; word-break: break-word; }
.hol-up-badge { font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; display:inline-block; }
.hol-up-badge.full { background:#FEE2E2; color:#b91c1c; }
.hol-up-badge.half { background:#FEF9C3; color:#a16207; }
.hol-up-scope { font-size:11px; color:var(--hol-muted); margin-top:6px; }

.hol-table-card { background:#fff; border:1px solid var(--hol-border); border-radius: var(--hol-radius); box-shadow: var(--hol-shadow); overflow:hidden; }
.hol-table { width:100%; margin:0; }
.hol-table thead th { background:#F8FAFC; border-bottom:1px solid var(--hol-border); font-size:11px; text-transform:uppercase; letter-spacing:.03em; color:var(--hol-muted); font-weight:700; padding:12px 16px; }
.hol-table td { padding:12px 16px; vertical-align:middle; font-size:13px; border-bottom:1px solid #F1F5F9; }
.hol-table tbody tr:last-child td { border-bottom:none; }
.hol-table tbody tr:hover { background:#F8FAFC; }
.hol-date-chip { display:inline-flex; flex-direction:column; align-items:center; justify-content:center; width:46px; height:46px; border-radius:10px; background:#EEF2FF; color:var(--hol-navy); line-height:1.1; }
.hol-date-chip .d { font-size:15px; font-weight:800; }
.hol-date-chip .m { font-size:9.5px; text-transform:uppercase; font-weight:700; }
.hol-badge { font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; display:inline-block; }
.hol-badge.full { background:#FEE2E2; color:#b91c1c; }
.hol-badge.half { background:#FEF9C3; color:#a16207; }
.hol-scope-chip { font-size:10.5px; padding:2px 8px; border-radius:6px; background:#F1F5F9; color:#334155; font-weight:600; display:inline-block; margin:1px 2px 1px 0; }
.hol-row-actions { display:flex; gap:6px; }
.hol-row-actions button { width:30px; height:30px; border-radius:8px; border:1px solid var(--hol-border); background:#fff; color:var(--hol-muted); display:flex; align-items:center; justify-content:center; font-size:14px; cursor:pointer; transition: all .12s; }
.hol-row-actions button:hover { background:var(--hol-navy); color:#fff; border-color:var(--hol-navy); }
.hol-row-actions button.danger:hover { background:#dc2626; border-color:#dc2626; }
.hol-empty { text-align:center; padding:48px 16px; color:var(--hol-muted); }
.hol-empty i { font-size:40px; display:block; margin-bottom:10px; opacity:.4; }

#addHolidayModal .modal-content, #editHolidayModal .modal-content { border:none; border-radius: var(--hol-radius); overflow:hidden; }
#addHolidayModal .modal-header, #editHolidayModal .modal-header { background: linear-gradient(135deg, var(--hol-navy), #4c1d95); color:#fff; border:none; }
#addHolidayModal .modal-header .btn-close, #editHolidayModal .modal-header .btn-close { filter: invert(1) brightness(2); }
#addHolidayModal .form-label, #editHolidayModal .form-label { font-size:12.5px; font-weight:600; color:#334155; }

@media (max-width: 768px) {
    .hol-stats { grid-template-columns: repeat(2,1fr); }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="hol-hero">
        <div>
            <h1><i class="ri-calendar-event-line"></i> {{ $pagetitle }}</h1>
            <p>Plan public holidays and school breaks, and control whether they automatically cancel classes on the timetable.</p>
            <div class="pills">
                <span class="hol-pill"><i class="ri-calendar-check-line me-1"></i>{{ $stats['total'] }} total</span>
                <span class="hol-pill"><i class="ri-time-line me-1"></i>{{ $stats['upcoming'] }} upcoming</span>
            </div>
        </div>
        @can('Create holidays')
        <button type="button" class="hol-add-btn" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
            <i class="ri-add-line"></i> Add Holiday
        </button>
        @endcan
    </div>

    <div class="hol-stats">
        <div class="hol-stat">
            <div class="hol-stat-icon total"><i class="ri-calendar-2-line"></i></div>
            <div><div class="v">{{ $stats['total'] }}</div><div class="l">Total Holidays</div></div>
        </div>
        <div class="hol-stat">
            <div class="hol-stat-icon upcoming"><i class="ri-hourglass-line"></i></div>
            <div><div class="v">{{ $stats['upcoming'] }}</div><div class="l">Upcoming</div></div>
        </div>
        <div class="hol-stat">
            <div class="hol-stat-icon full"><i class="ri-close-circle-line"></i></div>
            <div><div class="v">{{ $stats['full_day'] }}</div><div class="l">Full-Day</div></div>
        </div>
        <div class="hol-stat">
            <div class="hol-stat-icon half"><i class="ri-time-line"></i></div>
            <div><div class="v">{{ $stats['half_day'] }}</div><div class="l">Half-Day</div></div>
        </div>
    </div>

    @if($upcomingHolidays->count() > 0)
    <div class="hol-section-title"><i class="ri-calendar-event-line"></i> Upcoming Holidays</div>
    <div class="hol-upcoming-grid">
        @foreach($upcomingHolidays as $holiday)
        <div class="hol-up-card {{ $holiday->is_full_day ? 'full' : 'half' }}">
            <div class="hol-up-date">{{ \Carbon\Carbon::parse($holiday->date)->format('D, M d') }}</div>
            <div class="hol-up-title">{{ $holiday->title }}</div>
            <span class="hol-up-badge {{ $holiday->is_full_day ? 'full' : 'half' }}">
                {{ $holiday->is_full_day ? 'Full Day' : 'Half Day' }}
            </span>
            @if($holiday->session)
            <div class="hol-up-scope">{{ $holiday->session->session }}{{ $holiday->term ? ' · '.$holiday->term->term : '' }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <form method="GET" action="{{ route('holidays.index') }}" class="hol-toolbar" id="holidayFilterForm">
        <div class="search-wrap">
            <i class="ri-search-line"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search holidays…">
        </div>
        <select name="type" onchange="document.getElementById('holidayFilterForm').submit()">
            <option value="">All Types</option>
            <option value="full" {{ request('type') === 'full' ? 'selected' : '' }}>Full Day</option>
            <option value="half" {{ request('type') === 'half' ? 'selected' : '' }}>Half Day</option>
        </select>
        <select name="session_id" onchange="document.getElementById('holidayFilterForm').submit()">
            <option value="">All Sessions</option>
            @foreach($sessions as $session)
            <option value="{{ $session->id }}" {{ (string) request('session_id') === (string) $session->id ? 'selected' : '' }}>{{ $session->session }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="ri-search-line"></i> Filter</button>
        @if(request()->anyFilled(['search', 'type', 'session_id']))
        <a href="{{ route('holidays.index') }}" class="hol-clear"><i class="ri-close-line"></i> Clear filters</a>
        @endif
    </form>

    <div class="hol-table-card">
        <div class="table-responsive">
            <table class="table hol-table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Cut-off</th>
                        <th>Scope</th>
                        <th>Created By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $holiday)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="hol-date-chip">
                                    <span class="d">{{ \Carbon\Carbon::parse($holiday->date)->format('d') }}</span>
                                    <span class="m">{{ \Carbon\Carbon::parse($holiday->date)->format('M') }}</span>
                                </span>
                                <span class="text-muted" style="font-size:11.5px;">{{ \Carbon\Carbon::parse($holiday->date)->format('D, Y') }}</span>
                            </div>
                        </td>
                        <td><strong>{{ $holiday->title }}</strong></td>
                        <td>
                            <span class="hol-badge {{ $holiday->is_full_day ? 'full' : 'half' }}">
                                {{ $holiday->is_full_day ? 'Full Day' : 'Half Day' }}
                            </span>
                        </td>
                        <td>{{ $holiday->cutoff_time ? \Carbon\Carbon::parse($holiday->cutoff_time)->format('H:i') : '—' }}</td>
                        <td>
                            <span class="hol-scope-chip">{{ $holiday->session?->session ?? 'All Sessions' }}</span>
                            <span class="hol-scope-chip">{{ $holiday->term?->term ?? 'All Terms' }}</span>
                        </td>
                        <td class="text-muted">{{ $holiday->creator?->name ?? 'System' }}</td>
                        <td>
                            <div class="hol-row-actions justify-content-end">
                                @can('Edit holidays')
                                <button type="button" class="edit-holiday"
                                        data-id="{{ $holiday->id }}"
                                        data-date="{{ \Carbon\Carbon::parse($holiday->date)->format('Y-m-d') }}"
                                        data-title="{{ $holiday->title }}"
                                        data-is_full_day="{{ $holiday->is_full_day ? '1' : '0' }}"
                                        data-cutoff_time="{{ $holiday->cutoff_time }}"
                                        data-session_id="{{ $holiday->session_id }}"
                                        data-term_id="{{ $holiday->term_id }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editHolidayModal" title="Edit">
                                    <i class="ri-edit-line"></i>
                                </button>
                                @endcan
                                @can('Delete holidays')
                                <button type="button" class="danger delete-holiday"
                                        data-id="{{ $holiday->id }}"
                                        data-title="{{ $holiday->title }}" title="Delete">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="hol-empty">
                                <i class="ri-calendar-close-line"></i>
                                No holidays found{{ request()->anyFilled(['search','type','session_id']) ? ' for these filters.' : '.' }}
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($holidays->hasPages())
        <div class="d-flex justify-content-center py-3">
            {{ $holidays->links() }}
        </div>
        @endif
    </div>
</div>
</div>
</div>

<!-- Add Holiday Modal -->
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-add-line me-2"></i>Add New Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addHolidayForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Christmas Day" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_full_day" name="is_full_day" value="1" checked>
                            <label class="form-check-label" for="is_full_day">Full Day Holiday</label>
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="cutoff_time_group">
                        <label for="cutoff_time" class="form-label">Cut-off Time (for half-day)</label>
                        <input type="time" class="form-control" id="cutoff_time" name="cutoff_time">
                        <small class="text-muted">Classes after this time will be canceled</small>
                    </div>
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session (Optional)</label>
                        <select class="form-select" id="session_id" name="session_id">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="term_id" class="form-label">Term (Optional)</label>
                        <select class="form-select" id="term_id" name="term_id">
                            <option value="">All Terms</option>
                            @foreach(\App\Models\Schoolterm::orderBy('id', 'desc')->get() as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="affects_timetable" name="affects_timetable" value="1" checked>
                            <label class="form-check-label" for="affects_timetable">Affects Timetable</label>
                        </div>
                        <small class="text-muted">Automatically cancels/adjusts classes on this date across matching timetables.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveHolidayBtn">Save Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Holiday Modal -->
<div class="modal fade" id="editHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-edit-line me-2"></i>Edit Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editHolidayForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_holiday_id" name="holiday_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_is_full_day" name="is_full_day" value="1">
                            <label class="form-check-label" for="edit_is_full_day">Full Day Holiday</label>
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="edit_cutoff_time_group">
                        <label for="edit_cutoff_time" class="form-label">Cut-off Time (for half-day)</label>
                        <input type="time" class="form-control" id="edit_cutoff_time" name="cutoff_time">
                        <small class="text-muted">Classes after this time will be canceled</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_session_id" class="form-label">Session (Optional)</label>
                        <select class="form-select" id="edit_session_id" name="session_id">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_term_id" class="form-label">Term (Optional)</label>
                        <select class="form-select" id="edit_term_id" name="term_id">
                            <option value="">All Terms</option>
                            @foreach(\App\Models\Schoolterm::orderBy('id', 'desc')->get() as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_affects_timetable" name="affects_timetable" value="1" checked>
                            <label class="form-check-label" for="edit_affects_timetable">Affects Timetable</label>
                        </div>
                        <small class="text-muted">Automatically cancels/adjusts classes on this date across matching timetables.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updateHolidayBtn">Update Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('theme/layouts/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Toggle cut-off time visibility based on full day checkbox
    $('#is_full_day, #edit_is_full_day').on('change', function() {
        const isFullDay = $(this).is(':checked');
        const groupId = $(this).attr('id') === 'is_full_day' ? 'cutoff_time_group' : 'edit_cutoff_time_group';
        $('#' + groupId).toggleClass('d-none', isFullDay);
    });

    // Add Holiday
    $('#addHolidayForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        // Convert checkbox values to boolean
        data.is_full_day = data.is_full_day ? true : false;
        data.affects_timetable = data.affects_timetable ? true : false;

        $('#saveHolidayBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        $.ajax({
            url: '{{ route("holidays.store") }}',
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                });
            },
            complete: function() {
                $('#saveHolidayBtn').prop('disabled', false).html('Save Holiday');
            }
        });
    });

    // Edit Holiday - Load data into modal
    $('.edit-holiday').on('click', function() {
        const id = $(this).data('id');
        const date = $(this).data('date');
        const title = $(this).data('title');
        const isFullDay = $(this).data('is_full_day') == 1;
        const cutoffTime = $(this).data('cutoff_time');
        const sessionId = $(this).data('session_id');
        const termId = $(this).data('term_id');

        $('#edit_holiday_id').val(id);
        $('#edit_date').val(date);
        $('#edit_title').val(title);
        $('#edit_is_full_day').prop('checked', isFullDay);
        $('#edit_cutoff_time').val(cutoffTime || '');
        $('#edit_session_id').val(sessionId || '');
        $('#edit_term_id').val(termId || '');
        
        // Show/hide cutoff time
        $('#edit_cutoff_time_group').toggleClass('d-none', isFullDay);
    });

    // Update Holiday
    $('#editHolidayForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#edit_holiday_id').val();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        data.is_full_day = data.is_full_day ? true : false;
        data.affects_timetable = data.affects_timetable ? true : false;

        $('#updateHolidayBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');

        $.ajax({
            url: `/holidays/${id}`,
            method: 'POST',
            data: {
                ...data,
                _method: 'PUT'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                });
            },
            complete: function() {
                $('#updateHolidayBtn').prop('disabled', false).html('Update Holiday');
            }
        });
    });

    // Delete Holiday
    $('.delete-holiday').on('click', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');

        Swal.fire({
            title: 'Delete Holiday',
            text: `Are you sure you want to delete "${title}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/holidays/${id}`,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: response.message,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'An error occurred'
                        });
                    }
                });
            }
        });
    });

    // Auto-dismiss modal on close
    $('#addHolidayModal, #editHolidayModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
        $(this).find('#cutoff_time_group, #edit_cutoff_time_group').addClass('d-none');
    });
});
</script>

@endsection
