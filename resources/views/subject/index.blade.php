{{-- resources/views/subject/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sub-primary:  #1e3a5f;
    --sub-accent:   #2563eb;
    --sub-success:  #16a34a;
    --sub-warning:  #d97706;
    --sub-danger:   #dc2626;
    --sub-muted:    #6b7280;
    --sub-border:   #e2e8f0;
    --sub-bg:       #f8fafc;
    --sub-radius:   12px;
    --sub-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.sub-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #7c3aed 100%);
    border-radius: var(--sub-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.sub-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.sub-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.sub-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sub-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--sub-border);
    border-radius:var(--sub-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sub-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sub-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sub-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.sub-table th {
    background:var(--sub-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.sub-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--sub-border); font-size:13px;
}
.sub-table tr:hover td { background:#eff6ff; }

/* ── Badges ──────────────────────────────────────────────── */
.sub-badge {
    display:inline-flex; align-items:center;
    padding:3px 10px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.sub-badge-remark {
    background:#f0fdf4; color:#16a34a;
    border:1px solid #bbf7d0;
}

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--sub-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--sub-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--sub-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--sub-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--sub-accent) !important;
    border-color:var(--sub-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.sub-modal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, var(--sub-primary) 0%, #7c3aed 100%);
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
    border:1.5px solid var(--sub-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--sub-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#sub-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#sub-page-loader.active { opacity:1; visibility:visible; }

.sub-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22);
    min-width:220px;
}
.sub-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0;
    border-top-color:var(--sub-accent);
    border-radius:50%;
    animation:sub-spin .75s linear infinite;
}
@keyframes sub-spin { to { transform:rotate(360deg); } }

.sub-loader-label {
    font-size:14px; font-weight:600;
    color:var(--sub-primary); margin-bottom:12px;
}
.sub-progress-wrap {
    width:160px; height:5px;
    background:#e2e8f0; border-radius:99px; overflow:hidden;
    margin:0 auto;
}
.sub-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--sub-accent), #7c3aed);
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
    border-top-color:var(--sub-accent);
    border-radius:50%;
    animation:sub-spin .7s linear infinite;
}
.modal-body-loader .mbl-text {
    font-size:13px; font-weight:600; color:var(--sub-primary);
}

/* ── Toast notifications ──────────────────────────────────── */
#sub-toast-stack {
    position:fixed; bottom:24px; right:24px;
    z-index:10000; display:flex;
    flex-direction:column-reverse; gap:10px;
    pointer-events:none;
}
.sub-toast {
    pointer-events:all;
    background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--sub-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.sub-toast.show { transform:translateX(0); }
.sub-toast.sub-toast-success { border-left-color:var(--sub-success); }
.sub-toast.sub-toast-error   { border-left-color:var(--sub-danger);  }
.sub-toast.sub-toast-warning { border-left-color:var(--sub-warning); }
.sub-toast .sub-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.sub-toast-success .sub-toast-icon { color:var(--sub-success); }
.sub-toast-error   .sub-toast-icon { color:var(--sub-danger);  }
.sub-toast-warning .sub-toast-icon { color:var(--sub-warning); }
.sub-toast .sub-toast-body { flex:1; }
.sub-toast .sub-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.sub-toast .sub-toast-msg   { font-size:12px; color:var(--sub-muted); line-height:1.4; }
.sub-toast .sub-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--sub-muted); font-size:16px; line-height:1;
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
    animation:sub-spin .65s linear infinite;
}
.btn-loading.btn-outline-secondary::after,
.btn-loading.btn-outline-danger::after { border-top-color:currentColor; }
.btn-loading.btn-light::after { border-top-color:#374151; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="sub-page-loader">
    <div class="sub-loader-card">
        <div class="sub-loader-spinner"></div>
        <div class="sub-loader-label" id="sub-loader-label">Processing…</div>
        <div class="sub-progress-wrap">
            <div class="sub-progress-bar" id="sub-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast notification stack ═══ --}}
<div id="sub-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="sub-hero">
        <h1><i class="ri-book-2-line me-2"></i>Subject Management</h1>
        <p>Create and manage all subjects offered across your school.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-book-2-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Subjects</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-barcode-line"></i></div>
                <div class="stat-value text-primary" id="statShowing">—</div>
                <div class="stat-label">Showing Now</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-star-line"></i></div>
                <div class="stat-value text-success" id="statWithTeachers">—</div>
                <div class="stat-label">With Teachers</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-line"></i></div>
                <div class="stat-value text-warning" id="statRecent">—</div>
                <div class="stat-label">Recent Updates (30d)</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sub-primary)">
                    <i class="ri-list-check me-2"></i>All Subjects
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create subjects')
                    <button class="btn btn-primary" id="createSubjectBtn">
                        <i class="ri-add-line me-1"></i>Create Subject
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> subject(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table sub-table w-100 mb-0" id="subjectsTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Subject Code</th>
                            <th>Remark</th>
                            <th>Usage</th>
                            <th>Last Updated</th>
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
<div class="modal fade sub-modal" id="addSubjectModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-circle-line me-2"></i>Add Subject</h5>
            </div>
            <form id="add-subject-form" autocomplete="off">
                @csrf
                <div class="modal-body-loader" id="add-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="add-modal-loader-text">Saving…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">
                    <div class="mb-3">
                        <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="add-subject" class="form-control"
                               placeholder="e.g. Mathematics" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" id="add-subject-code" class="form-control"
                               placeholder="e.g. MTH101" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark <span class="text-danger">*</span></label>
                        <input type="text" name="remark" id="add-remark" class="form-control"
                               placeholder="e.g. Core Subject" required>
                    </div>
                    <div class="alert alert-danger d-none" id="add-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Subject</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade sub-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Subject</h5>
            </div>
            <form id="edit-subject-form" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-id">
                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">
                    <div class="mb-3">
                        <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="edit-subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" id="edit-subject-code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark <span class="text-danger">*</span></label>
                        <input type="text" name="remark" id="edit-remark" class="form-control" required>
                    </div>
                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Subject</span>
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
                <p>Delete subject <strong id="delete-subject-name"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
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
            $('#sub-loader-label').text(label);
            $('#sub-progress-bar').css('width', '0%');
            $('#sub-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#sub-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#sub-progress-bar').css('width', '100%');
            setTimeout(() => $('#sub-page-loader').removeClass('active'), 350);
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
        const id  = 'sub-toast-' + Date.now();
        const $el = $(`
            <div class="sub-toast sub-toast-${type}" id="${id}">
                <span class="sub-toast-icon"><i class="${icons[type] || icons.info}"></i></span>
                <div class="sub-toast-body">
                    <div class="sub-toast-title">${title}</div>
                    ${msg ? `<div class="sub-toast-msg">${msg}</div>` : ''}
                </div>
                <button class="sub-toast-close" onclick="$('#${id}').remove()">×</button>
            </div>
        `);
        $('#sub-toast-stack').append($el);
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

    var table = $('#subjectsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("subject.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load subjects. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'subject_info', orderable: false },
            { data: 'code_info', orderable: false },
            { data: 'remark_info', orderable: false },
            { data: 'usage_count', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing:      '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search:          '',
            searchPlaceholder: 'Search subjects…',
            lengthMenu:      'Show _MENU_ entries',
            info:            'Showing _START_–_END_ of _TOTAL_ subjects',
            infoEmpty:       'No subjects found',
            zeroRecords:     'No matching subjects',
            emptyTable:      'No subjects created yet',
        },
        order: [[2, 'asc']],
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
        $.get('{{ route("subject.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statShowing').text(data.stats.showing);
                $('#statWithTeachers').text(data.stats.with_teachers);
                $('#statRecent').text(data.stats.recently_updated);
            }
        }).fail(function() {
            $('#statTotal, #statShowing, #statWithTeachers, #statRecent').text('—');
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
    // CREATE MODAL — guard button
    // =========================================================================

    function updateCreateBtn() {
        var ok = $('#add-subject').val().trim() !== '' &&
                 $('#add-subject-code').val().trim() !== '' &&
                 $('#add-remark').val().trim() !== '';
        $('#add-btn').prop('disabled', !ok);
    }

    $('#add-subject, #add-subject-code, #add-remark').on('input', updateCreateBtn);

    // ── Open CREATE ───────────────────────────────────────────
    $('#createSubjectBtn').on('click', function() {
        $('#add-subject').val('');
        $('#add-subject-code').val('');
        $('#add-remark').val('');
        $('#add-btn').prop('disabled', true);
        $('#add-error-msg').addClass('d-none').html('');
        hideModalLoader('add');
        new bootstrap.Modal(document.getElementById('addSubjectModal')).show();
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-subject-btn', function() {
        var id      = $(this).data('id');
        var subject = $(this).data('subject');
        var code    = $(this).data('code');
        var remark  = $(this).data('remark');

        $('#edit-id').val(id);
        $('#edit-subject').val(subject);
        $('#edit-subject-code').val(code);
        $('#edit-remark').val(remark);

        $('#edit-error-msg').addClass('d-none').html('');
        hideModalLoader('edit');
        btnReset('#update-btn');

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // =========================================================================
    // SUBMIT: CREATE
    // =========================================================================

    $('#add-subject-form').on('submit', function(e) {
        e.preventDefault();

        var subject = $('#add-subject').val().trim();
        var code    = $('#add-subject-code').val().trim();
        var remark  = $('#add-remark').val().trim();

        if (!subject || !code || !remark) {
            showError('#add-error-msg', 'All fields are required.');
            return;
        }

        btnLoad('#add-btn', 'Adding…');
        showModalLoader('add', 'Saving subject…');
        $('#add-error-msg').addClass('d-none').html('');

        $.ajax({
            url:     '{{ route("subject.store") }}',
            type:    'POST',
            data:    { subject, subject_code: code, remark, _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                if (res.success) {
                    $('#addSubjectModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('add');
                    btnReset('#add-btn');
                    updateCreateBtn();
                    showError('#add-error-msg', res.message || 'Could not add subject.');
                }
            },

            error: function(xhr) {
                hideModalLoader('add');
                btnReset('#add-btn');
                updateCreateBtn();
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

    $('#edit-subject-form').on('submit', function(e) {
        e.preventDefault();

        var id      = $('#edit-id').val();
        var subject = $('#edit-subject').val().trim();
        var code    = $('#edit-subject-code').val().trim();
        var remark  = $('#edit-remark').val().trim();

        if (!subject || !code || !remark) {
            showError('#edit-error-msg', 'All fields are required.');
            return;
        }

        btnLoad('#update-btn', 'Updating…');
        showModalLoader('edit', 'Saving changes…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url:     `{{ url('subject') }}/${id}`,
            type:    'POST',
            data:    { subject, subject_code: code, remark, _token: CSRF, _method: 'PUT' },
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
    });

    // =========================================================================
    // DELETE: SINGLE
    // =========================================================================

    $(document).on('click', '.delete-subject-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-subject-name').text($(this).data('subject') || 'this subject');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: `{{ url('subject') }}/${deleteId}`,
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
// DELETE: BULK - FIXED
// =========================================================================

function doBulkDelete() {
    var ids = [];
    $('.row-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    
    if (ids.length === 0) {
        toast('warning', 'No Selection', 'Please select at least one subject to delete.');
        return;
    }

    Swal.fire({
        title: 'Delete ' + ids.length + ' subject(s)?',
        html: 'This will permanently remove the selected subjects.<br><strong>This action cannot be undone!</strong>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, delete them!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        showLoaderOnConfirm: true,
        preConfirm: function() {
            return new Promise(function(resolve, reject) {
                PageLoader.show('Deleting subjects…');
                
                // Send as JSON with proper array format
                $.ajax({
                    url: '{{ route("subject.bulk-destroy") }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        ids: ids,
                        _token: CSRF
                    }),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        PageLoader.hide();
                        if (res.success) {
                            resolve(res);
                        } else {
                            reject(res.message || 'Failed to delete subjects');
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
            toast('success', 'Deleted!', result.value.message || 'Subjects deleted successfully.');
            table.ajax.reload();
            loadStats();
            $('#selectAll').prop('checked', false);
            updateBulkBar();
        }
    }).catch(function(error) {
        toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete subjects.');
    });
}

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection