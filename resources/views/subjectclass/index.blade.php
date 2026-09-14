{{-- resources/views/subjectclass/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sc-primary:  #1e3a5f;
    --sc-accent:   #2563eb;
    --sc-success:  #16a34a;
    --sc-warning:  #d97706;
    --sc-danger:   #dc2626;
    --sc-muted:    #6b7280;
    --sc-border:   #e2e8f0;
    --sc-bg:       #f8fafc;
    --sc-radius:   12px;
    --sc-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.sc-hero {
    background: linear-gradient(135deg, var(--sc-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sc-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.sc-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.sc-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.sc-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sc-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--sc-border);
    border-radius:var(--sc-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sc-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sc-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sc-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.sc-table th {
    background:var(--sc-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.sc-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--sc-border); font-size:13px;
}
.sc-table tr:hover td { background:#eff6ff; }

/* ── Badges ──────────────────────────────────────────────── */
.sc-badge {
    display:inline-flex; align-items:center;
    padding:3px 9px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.sc-badge-class    { background:#dbeafe; color:#2563eb; }
.sc-badge-session  { background:#ccfbf1; color:#0f766e; }
.sc-badge-term-first  { background:#dcfce7; color:#16a34a; }
.sc-badge-term-second { background:#dbeafe; color:#2563eb; }
.sc-badge-term-third  { background:#fee2e2; color:#dc2626; }
.sc-badge-term-other  { background:#f3f4f6; color:#6b7280; }

/* ── Avatar ──────────────────────────────────────────────── */
.teacher-avatar {
    width:36px; height:36px; border-radius:50%;
    object-fit:cover; border:2px solid var(--sc-border);
    cursor:pointer; transition:border-color .15s;
}
.teacher-avatar:hover { border-color:var(--sc-accent); }

.avatar-initials {
    width:36px; height:36px; border-radius:50%;
    background:linear-gradient(135deg, #1e3a5f 0%, #0891b2 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:700; font-size:13px; letter-spacing:.5px;
    border:2px solid var(--sc-border);
    cursor:pointer; flex-shrink:0; user-select:none;
    transition:border-color .15s, transform .15s;
}
.avatar-initials:hover { border-color:var(--sc-accent); transform:scale(1.08); }

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--sc-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--sc-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--sc-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--sc-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--sc-accent) !important;
    border-color:var(--sc-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.sc-modal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, var(--sc-primary) 0%, #2563eb 100%);
    padding:22px 28px; position:relative; overflow:hidden;
}
.modal-hero-bar::before {
    content:''; position:absolute; top:-30px; right:-30px;
    width:120px; height:120px; background:rgba(255,255,255,.07); border-radius:50%;
}
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select {
    border:1.5px solid var(--sc-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--sc-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Checkbox group scroll ───────────────────────────────── */
.checkbox-scroll {
    max-height:220px; overflow-y:auto;
    border:1.5px solid var(--sc-border); border-radius:8px;
    padding:10px 14px; background:#fafbfc;
}
.checkbox-scroll .form-check { padding:5px 0; border-bottom:1px solid #f0f0f0; }
.checkbox-scroll .form-check:last-child { border-bottom:none; }
.checkbox-scroll .form-check-label { font-size:13px; cursor:pointer; }
.checkbox-scroll .form-check-input:checked { background-color:var(--sc-accent); border-color:var(--sc-accent); }

/* ── Context info box ────────────────────────────────────── */
.sc-context-box {
    background: var(--sc-bg);
    border: 1.5px solid var(--sc-border);
    border-radius: 10px;
    padding: 14px 18px;
}
.sc-context-box .context-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--sc-muted);
    margin-bottom: 4px;
}
.sc-context-box .context-value {
    font-size: 13px;
    font-weight: 600;
    color: var(--sc-primary);
}
.sc-context-box .context-sub {
    font-size: 11px;
    color: var(--sc-muted);
}
.sc-lock-note {
    font-size: 11px;
    color: var(--sc-muted);
    text-align: center;
    margin-top: 8px;
}

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#sc-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#sc-page-loader.active { opacity:1; visibility:visible; }

.sc-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22);
    min-width:220px;
}
.sc-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0;
    border-top-color:var(--sc-accent);
    border-radius:50%;
    animation:sc-spin .75s linear infinite;
}
@keyframes sc-spin { to { transform:rotate(360deg); } }

.sc-loader-label {
    font-size:14px; font-weight:600;
    color:var(--sc-primary); margin-bottom:12px;
}
.sc-progress-wrap {
    width:160px; height:5px;
    background:#e2e8f0; border-radius:99px; overflow:hidden;
    margin:0 auto;
}
.sc-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--sc-accent), #4f46e5);
    border-radius:99px;
    transition:width .35s ease;
}

/* ── Modal body loading overlay ──────────────────────────── */
.modal-body-loader {
    position:absolute; inset:0; z-index:10;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(2px);
    display:flex; align-items:center; justify-content:center;
    border-radius:0 0 16px 16px;
    opacity:0; visibility:hidden;
    transition:opacity .18s, visibility .18s;
}
.modal-body-loader.active { opacity:1; visibility:visible; }
.modal-body-loader .inner {
    display:flex; flex-direction:column;
    align-items:center; gap:10px;
}
.modal-body-loader .mbl-spinner {
    width:36px; height:36px;
    border:3px solid #e2e8f0;
    border-top-color:var(--sc-accent);
    border-radius:50%;
    animation:sc-spin .7s linear infinite;
}
.modal-body-loader .mbl-text {
    font-size:13px; font-weight:600; color:var(--sc-primary);
}

/* ── Toast notifications ──────────────────────────────────── */
#sc-toast-stack {
    position:fixed; bottom:24px; right:24px;
    z-index:10000; display:flex;
    flex-direction:column-reverse; gap:10px;
    pointer-events:none;
}
.sc-toast {
    pointer-events:all;
    background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--sc-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.sc-toast.show { transform:translateX(0); }
.sc-toast.sc-toast-success { border-left-color:var(--sc-success); }
.sc-toast.sc-toast-error   { border-left-color:var(--sc-danger);  }
.sc-toast.sc-toast-warning { border-left-color:var(--sc-warning); }
.sc-toast .sc-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.sc-toast-success .sc-toast-icon { color:var(--sc-success); }
.sc-toast-error   .sc-toast-icon { color:var(--sc-danger);  }
.sc-toast-warning .sc-toast-icon { color:var(--sc-warning); }
.sc-toast .sc-toast-body { flex:1; }
.sc-toast .sc-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.sc-toast .sc-toast-msg   { font-size:12px; color:var(--sc-muted); line-height:1.4; }
.sc-toast .sc-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--sc-muted); font-size:16px; line-height:1;
    padding:0; flex-shrink:0;
}

/* ── Button loading state ────────────────────────────────── */
.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:'';
    position:absolute; inset:0;
    margin:auto; width:16px; height:16px;
    border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff;
    border-radius:50%;
    animation:sc-spin .65s linear infinite;
}
.btn-loading.btn-outline-secondary::after,
.btn-loading.btn-outline-danger::after { border-top-color:currentColor; }
.btn-loading.btn-light::after { border-top-color:#374151; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="sc-page-loader">
    <div class="sc-loader-card">
        <div class="sc-loader-spinner"></div>
        <div class="sc-loader-label" id="sc-loader-label">Processing…</div>
        <div class="sc-progress-wrap">
            <div class="sc-progress-bar" id="sc-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast notification stack ═══ --}}
<div id="sc-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="sc-hero">
        <h1><i class="ri-book-open-line me-2"></i>Subject Class Management</h1>
        <p>Assign subject teachers to classes across terms and sessions.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-book-2-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-star-line"></i></div>
                <div class="stat-value text-primary" id="statTeachers">—</div>
                <div class="stat-label">Unique Teachers</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-building-line"></i></div>
                <div class="stat-value text-success" id="statClasses">—</div>
                <div class="stat-label">Classes Covered</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-flask-line"></i></div>
                <div class="stat-value text-warning" id="statSubjects">—</div>
                <div class="stat-label">Unique Subjects</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sc-primary)">
                    <i class="ri-list-check me-2"></i>Subject Class Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create subject-class')
                    <button class="btn btn-primary" id="createSubjectClassBtn">
                        <i class="ri-add-line me-1"></i>Create Subject Class
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> assignment(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table sc-table w-100 mb-0" id="subjectClassTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Teacher</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Session</th>
                            <th>Registrations</th>
                            <th>Updated</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- ═══════════════════════ ADD MODAL ═══════════════════════ --}}
<div class="modal fade sc-modal" id="addSubjectClassModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-circle-line me-2"></i>Add Subject Class</h5>
            </div>
            <form id="add-subjectclass-form" autocomplete="off">
                @csrf
                <div class="modal-body-loader" id="add-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="add-modal-loader-text">Saving…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">
                    <div class="mb-3">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="schoolclassid" id="add-schoolclassid" class="form-select" required>
                            <option value="">— Select Class —</option>
                            @foreach ($schoolclasses as $class)
                                <option value="{{ $class->id }}">{{ $class->schoolclass }} ({{ $class->arm }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject Teachers <span class="text-danger">*</span></label>
                        <div class="mb-2">
                            <input type="text" id="add-teacher-search" class="form-control form-control-sm"
                                   placeholder="🔍  Filter teachers…">
                        </div>
                        <div class="checkbox-scroll" id="add-teacher-list">
                            @foreach ($subjectteacher->sortBy(['teachername', 'subject']) as $teacher)
                                @php
                                    $tColor = match(true) {
                                        str_contains($teacher->termname ?? '', 'First')  => '#16a34a',
                                        str_contains($teacher->termname ?? '', 'Second') => '#2563eb',
                                        str_contains($teacher->termname ?? '', 'Third')  => '#dc2626',
                                        default => '#6b7280'
                                    };
                                @endphp
                                <div class="form-check teacher-item">
                                    <input class="form-check-input add-teacher-checkbox"
                                           type="checkbox"
                                           name="subjectteacherid[]"
                                           id="add-t-{{ $teacher->id }}"
                                           value="{{ $teacher->id }}"
                                           data-label="{{ $teacher->teachername }} {{ $teacher->subject }}">
                                    <label class="form-check-label" for="add-t-{{ $teacher->id }}">
                                        <strong>{{ $teacher->teachername }}</strong>
                                        — {{ $teacher->subject }}
                                        <small class="text-muted">({{ $teacher->subjectcode }})</small>
                                        <span class="ms-1" style="color:{{ $tColor }};font-size:11px;font-weight:600;">
                                            {{ $teacher->termname }}
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <span id="add-selected-count">0</span> teacher(s) selected
                        </small>
                    </div>

                    <div class="alert alert-danger d-none" id="add-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Subject Class</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade sc-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-user-follow-line me-2"></i>Change Subject Teacher</h5>
            </div>
            <form id="edit-subjectclass-form" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-id">
                <input type="hidden" id="edit-old-staffid">

                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>

                <div class="modal-body p-4" style="position:relative">

                    {{-- Locked context — subject / term / session / class --}}
                    <div class="sc-context-box mb-4">
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="context-label">Subject</div>
                                <div class="context-value" id="ctx-subject">—</div>
                                <div class="context-sub" id="ctx-subject-code"></div>
                            </div>
                            <div class="col-6">
                                <div class="context-label">Class</div>
                                <div class="context-value" id="ctx-class">—</div>
                            </div>
                            <div class="col-6">
                                <div class="context-label">Term</div>
                                <div class="context-value" id="ctx-term">—</div>
                            </div>
                            <div class="col-6">
                                <div class="context-label">Session</div>
                                <div class="context-value" id="ctx-session">—</div>
                            </div>
                        </div>
                        <p class="sc-lock-note mt-3 mb-0">
                            <i class="ri-lock-line me-1"></i>
                            Subject, class, term and session are fixed — only the teacher will change.
                        </p>
                    </div>

                    {{-- Current teacher display --}}
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Current Teacher</label>
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:#f0fdf4;border:1.5px solid #bbf7d0;">
                            <i class="ri-user-3-line text-success fs-5"></i>
                            <span class="fw-semibold" id="ctx-current-teacher" style="color:#15803d;">—</span>
                        </div>
                    </div>

                    {{-- New teacher picker --}}
                    <div class="mb-3">
                        <label class="form-label">New Teacher <span class="text-danger">*</span></label>
                        <select name="new_staffid" id="edit-new-staffid" class="form-select" required>
                            <option value="">— Select new teacher —</option>
                            @foreach ($allStaff as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">
                            Select the person who will now teach this subject for this class, term and session.
                        </small>
                    </div>

                    <div class="alert alert-warning d-none mb-2" id="edit-staff-change-warning">
                        <i class="ri-alert-line me-1"></i>
                        <strong>Heads up:</strong> Saving will update the staff assignment across all related broadsheet and registration records for this subject class.
                    </div>

                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Teacher</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ DELETE MODAL ════════════════════ --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete the subject class assignment for <strong id="delete-subject-name"></strong> taught by <strong id="delete-teacher-name"></strong>?</p>
                <p class="text-muted small mb-0">
                    This will be blocked if students are already registered or scores exist.
                </p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                    <i class="ri-delete-bin-line me-1"></i><span class="btn-text">Delete</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    const CSRF = $('meta[name="csrf-token"]').attr('content');
    let deleteId = null;

    // =========================================================================
    // LOADING HELPERS
    // =========================================================================

    const PageLoader = {
        _prog: 0, _timer: null,
        show(label = 'Processing…') {
            $('#sc-loader-label').text(label);
            $('#sc-progress-bar').css('width', '0%');
            $('#sc-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#sc-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#sc-progress-bar').css('width', '100%');
            setTimeout(() => $('#sc-page-loader').removeClass('active'), 350);
        },
    };

    function showModalLoader(id, text = 'Processing…') {
        $(`#${id}-modal-loader-text`).text(text);
        $(`#${id}-modal-loader`).addClass('active');
    }
    function hideModalLoader(id) {
        $(`#${id}-modal-loader`).removeClass('active');
    }

    function btnLoad(selector, loadingText = '') {
        const $btn = $(selector);
        $btn.data('original-html', $btn.html())
            .prop('disabled', true)
            .addClass('btn-loading');
        if (loadingText) {
            $btn.html(`<span class="btn-text">${loadingText}</span>`);
        }
        return $btn;
    }
    function btnReset(selector) {
        const $btn = $(selector);
        const orig = $btn.data('original-html');
        if (orig) $btn.html(orig);
        $btn.prop('disabled', false).removeClass('btn-loading');
    }

    function toast(type, title, msg, duration = 4000) {
        const icons = {
            success: 'ri-checkbox-circle-fill',
            error:   'ri-close-circle-fill',
            warning: 'ri-alert-fill',
            info:    'ri-information-fill',
        };
        const id  = 'sc-toast-' + Date.now();
        const $el = $(`
            <div class="sc-toast sc-toast-${type}" id="${id}">
                <span class="sc-toast-icon"><i class="${icons[type] || icons.info}"></i></span>
                <div class="sc-toast-body">
                    <div class="sc-toast-title">${title}</div>
                    ${msg ? `<div class="sc-toast-msg">${msg}</div>` : ''}
                </div>
                <button class="sc-toast-close" onclick="$('#${id}').remove()">×</button>
            </div>
        `);
        $('#sc-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        if (duration > 0) {
            setTimeout(() => {
                $el.removeClass('show');
                setTimeout(() => $el.remove(), 350);
            }, duration);
        }
    }

    function showError(selector, msg) {
        $(selector).removeClass('d-none').html(
            `<i class="ri-error-warning-line me-1"></i>${msg}`
        );
    }

    // =========================================================================
    // DATATABLE (server-side)
    // =========================================================================

    var table = $('#subjectClassTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("subjectclass.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load assignments. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'teacher_info', orderable: false },
            { data: 'subject_info', orderable: false },
            { data: 'class_info', orderable: false },
            { data: 'term_info', orderable: false },
            { data: 'session_info', orderable: false },
            { data: 'registration_count', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing:      '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search:          '',
            searchPlaceholder: 'Search assignments…',
            lengthMenu:      'Show _MENU_ entries',
            info:            'Showing _START_–_END_ of _TOTAL_ assignments',
            infoEmpty:       'No assignments found',
            zeroRecords:     'No matching assignments',
            emptyTable:      'No subject class assignments yet',
        },
        order: [[1, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            bindCheckboxes();
            $('#totalBadge').text(this.api().page.info().recordsTotal);
        },
    });

    // =========================================================================
    // STATS
    // =========================================================================

    function loadStats() {
        $.get('{{ route("subjectclass.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statTeachers').text(data.stats.unique_teachers);
                $('#statClasses').text(data.stats.unique_classes);
                $('#statSubjects').text(data.stats.unique_subjects);
            }
        }).fail(function() {
            $('#statTotal, #statTeachers, #statClasses, #statSubjects').text('—');
        });
    }
    loadStats();

    // =========================================================================
    // CHECKBOXES & BULK BAR
    // =========================================================================

    function bindCheckboxes() {
        $('.row-checkbox').off('change').on('change', updateBulkBar);
    }
    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkBar();
    });
    function updateBulkBar() {
        var count = $('.row-checkbox:checked').length;
        $('#bulkBar').toggleClass('show', count > 0);
        $('#bulkCount').text(count);
        $('#bulkDeleteBtn').toggleClass('d-none', count === 0);
        if (count === 0) $('#selectAll').prop('checked', false);
    }

    // =========================================================================
    // TEACHER SEARCH FILTER (inside add modal)
    // =========================================================================

    $('#add-teacher-search').on('input', function () {
        const q = $(this).val().toLowerCase();
        $('#add-teacher-list .teacher-item').each(function () {
            $(this).toggle($(this).find('label').text().toLowerCase().includes(q));
        });
    });

    // =========================================================================
    // ADD MODAL — selected count & button guard
    // =========================================================================

    $('#add-teacher-list').on('change', '.add-teacher-checkbox', function () {
        $('#add-selected-count').text($('.add-teacher-checkbox:checked').length);
        updateAddBtn();
    });

    $('#add-schoolclassid').on('change', updateAddBtn);

    function updateAddBtn() {
        const ok = $('#add-schoolclassid').val() !== '' &&
                   $('.add-teacher-checkbox:checked').length > 0;
        $('#add-btn').prop('disabled', !ok);
    }

    // ── Open ADD ──────────────────────────────────────────────
    $('#createSubjectClassBtn').on('click', function() {
        $('#add-schoolclassid').val('');
        $('.add-teacher-checkbox').prop('checked', false);
        $('#add-selected-count').text(0);
        $('#add-btn').prop('disabled', true);
        $('#add-error-msg').addClass('d-none').html('');
        $('#add-teacher-search').val('');
        $('#add-teacher-list .teacher-item').show();
        hideModalLoader('add');
        new bootstrap.Modal(document.getElementById('addSubjectClassModal')).show();
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-sc-btn', function () {
        const $btn = $(this);

        const id          = $btn.data('id');
        const staffId     = $btn.data('staffid');
        const teacherName = $btn.data('teachername');
        const subjectName = $btn.data('subjectname');
        const subjectCode = $btn.data('subjectcode');
        const termName    = $btn.data('termname');
        const sessionName = $btn.data('sessionname');
        const className   = $btn.data('classname');

        $('#edit-id').val(id);
        $('#edit-old-staffid').val(staffId);

        $('#ctx-subject').text(subjectName || '—');
        $('#ctx-subject-code').text(subjectCode || '');
        $('#ctx-class').text(className || '—');
        $('#ctx-term').text(termName || '—');
        $('#ctx-session').text(sessionName || '—');
        $('#ctx-current-teacher').text(teacherName || '—');

        $('#edit-new-staffid').val('');
        $('#edit-staff-change-warning').addClass('d-none');
        $('#edit-error-msg').addClass('d-none').html('');
        hideModalLoader('edit');
        btnReset('#update-btn');

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // Show warning when a different teacher is selected
    $('#edit-new-staffid').on('change', function () {
        const oldStaffId = $('#edit-old-staffid').val();
        const newVal     = $(this).val();
        const isDifferent = newVal && newVal !== String(oldStaffId);
        $('#edit-staff-change-warning').toggleClass('d-none', !isDifferent);
    });

    // =========================================================================
    // DELETE MODAL
    // =========================================================================

    $(document).on('click', '.delete-sc-btn', function () {
        deleteId = $(this).data('id');
        $('#delete-subject-name').text($(this).data('subject'));
        $('#delete-teacher-name').text($(this).data('teacher'));
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    // =========================================================================
    // SUBMIT: ADD
    // =========================================================================

    $('#add-subjectclass-form').on('submit', function(e) {
        e.preventDefault();

        const schoolclassid     = $('#add-schoolclassid').val();
        const subjectteacherids = $('.add-teacher-checkbox:checked').map((i, el) => el.value).get();

        if (!schoolclassid) {
            showError('#add-error-msg', 'Please select a class.');
            return;
        }
        if (subjectteacherids.length === 0) {
            showError('#add-error-msg', 'Please select at least one subject teacher.');
            return;
        }

        btnLoad('#add-btn', 'Adding…');
        showModalLoader('add', `Adding ${subjectteacherids.length} assignment(s)…`);
        $('#add-error-msg').addClass('d-none').html('');

        $.ajax({
            url:         '{{ route("subjectclass.store") }}',
            type:        'POST',
            data:        { schoolclassid, subjectteacherid: subjectteacherids, _token: CSRF },
            traditional: true,
            headers:     { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                if (res.success) {
                    $('#addSubjectClassModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('add');
                    btnReset('#add-btn');
                    updateAddBtn();
                    showError('#add-error-msg', res.message || 'Could not add subject class.');
                }
            },

            error: function(xhr) {
                hideModalLoader('add');
                btnReset('#add-btn');
                updateAddBtn();
                var json = xhr.responseJSON;
                var msg = (json && json.message) ||
                          (json && json.errors && Object.values(json.errors).flat().join(', ')) ||
                          'An error occurred.';
                showError('#add-error-msg', msg);
                toast('error', 'Failed', msg);
            },
        });
    });

    // =========================================================================
    // SUBMIT: EDIT
    // =========================================================================

    $('#edit-subjectclass-form').on('submit', function(e) {
        e.preventDefault();

        const id         = $('#edit-id').val();
        const newStaffId = $('#edit-new-staffid').val();
        const oldStaffId = $('#edit-old-staffid').val();

        if (!newStaffId) {
            showError('#edit-error-msg', 'Please select a new teacher.');
            return;
        }

        const teacherChanged = String(newStaffId) !== String(oldStaffId);

        const doUpdate = function() {
            btnLoad('#update-btn', 'Updating…');
            showModalLoader('edit', 'Updating teacher & cascading records…');
            $('#edit-error-msg').addClass('d-none').html('');

            $.ajax({
                url:     `{{ url('subjectclass') }}/${id}`,
                type:    'POST',
                data:    { new_staffid: newStaffId, _token: CSRF, _method: 'PUT' },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },

                success: function(res) {
                    if (res.success) {
                        $('#editModal').modal('hide');
                        toast('success', 'Updated!', res.message);
                        table.ajax.reload();
                        loadStats();
                    } else {
                        hideModalLoader('edit');
                        btnReset('#update-btn');
                        showError('#edit-error-msg', res.message || 'Could not update.');
                    }
                },

                error: function(xhr) {
                    hideModalLoader('edit');
                    btnReset('#update-btn');
                    var json = xhr.responseJSON;
                    var msg = (json && json.message) ||
                              (json && json.errors && Object.values(json.errors).flat().join(', ')) ||
                              'An error occurred.';
                    showError('#edit-error-msg', msg);
                    toast('error', 'Failed', msg);
                },
            });
        };

        if (teacherChanged) {
            Swal.fire({
                title:             'Change Teacher?',
                html:              'This will update the staff assignment on all related <strong>broadsheet</strong> and <strong>registration records</strong> for this subject class. Continue?',
                icon:              'warning',
                showCancelButton:   true,
                confirmButtonColor: '#2563eb',
                confirmButtonText:  'Yes, update',
                cancelButtonText:   'Cancel',
            }).then(function(result) { if (result.isConfirmed) doUpdate(); });
        } else {
            toast('info', 'No Change', 'The selected teacher is already assigned to this subject class.');
            $('#editModal').modal('hide');
        }
    });

    // =========================================================================
    // DELETE: SINGLE
    // =========================================================================

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: `{{ url('subjectclass') }}/${deleteId}`,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                $('#deleteModal').modal('hide');
                if (res.success) {
                    toast('success', 'Deleted!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    toast('error', 'Cannot Delete', res.message);
                    Swal.fire({ icon:'error', title:'Cannot Delete',
                        text: res.message, confirmButtonColor:'#2563eb' });
                }
            },

            error: function(xhr) {
                $('#deleteModal').modal('hide');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete.';
                toast('error', 'Error', msg);
                Swal.fire('Error!', msg, 'error');
            },

            complete: function() {
                btnReset($btn);
                deleteId = null;
            },
        });
    });

    // =========================================================================
    // DELETE: BULK
    // =========================================================================

    function doBulkDelete() {
        var ids = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        
        if (ids.length === 0) {
            toast('warning', 'No Selection', 'Please select at least one assignment to delete.');
            return;
        }

        Swal.fire({
            title: 'Delete ' + ids.length + ' assignment(s)?',
            html: 'Assignments with student records or scores cannot be deleted.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting assignments…');
                    
                    $.ajax({
                        url: '{{ route("subjectclass.bulk-destroy") }}',
                        type: 'POST',
                        data: {
                            ids: ids,
                            _token: CSRF
                        },
                        traditional: true,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function(res) {
                            PageLoader.hide();
                            if (res.success) {
                                resolve(res);
                            } else {
                                reject(res.message || 'Failed to delete assignments');
                            }
                        },
                        error: function(xhr) {
                            PageLoader.hide();
                            var errorMsg = 'An error occurred while deleting.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            reject(errorMsg);
                        }
                    });
                });
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                toast('success', 'Deleted!', result.value.message || 'Assignments deleted successfully.');
                table.ajax.reload();
                loadStats();
                $('#selectAll').prop('checked', false);
                updateBulkBar();
            }
        }).catch(function(error) {
            toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete assignments.');
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection