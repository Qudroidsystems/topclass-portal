{{-- resources/views/principalscomment/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --pc-primary:#1e3a5f; --pc-accent:#2563eb; --pc-success:#16a34a;
    --pc-warning:#d97706; --pc-danger:#dc2626; --pc-muted:#6b7280;
    --pc-border:#e2e8f0; --pc-radius:12px; --pc-shadow:0 2px 8px rgba(0,0,0,.08);
}
.pc-hero {
    background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#4f46e5 100%);
    border-radius:var(--pc-radius); padding:28px 32px; margin-bottom:24px;
    position:relative; overflow:hidden;
}
.pc-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%; }
.pc-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.pc-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.stat-card { background:#fff; border:1px solid var(--pc-border); border-radius:var(--pc-radius); padding:18px 20px; transition:transform .15s, box-shadow .15s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--pc-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--pc-primary); }
.stat-card .stat-label { font-size:12px; color:var(--pc-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.pc-table th { background:var(--pc-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.pc-table td { padding:11px 16px; vertical-align:middle; border-bottom:1px solid var(--pc-border); font-size:13px; }
.pc-table tr:hover td { background:#f0f9ff; }

.pc-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.pc-badge-class   { background:#dbeafe; color:#2563eb; }
.pc-badge-session { background:#ccfbf1; color:#0f766e; }
.pc-badge-term    { background:#ede9fe; color:#6d28d9; }

.staff-image { width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid var(--pc-border); flex-shrink:0; }
.avatar-initials {
    width:36px; height:36px; border-radius:50%;
    background:linear-gradient(135deg,#1e3a5f 0%,#0891b2 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:700; font-size:13px; letter-spacing:.5px;
    border:2px solid var(--pc-border); flex-shrink:0; user-select:none;
}

.dataTables_wrapper .dataTables_filter input { border:1.5px solid var(--pc-border); border-radius:8px; padding:7px 14px; margin-left:8px; font-size:13px; }
.dataTables_wrapper .dataTables_filter input:focus { border-color:var(--pc-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.dataTables_wrapper .dataTables_length select { border:1.5px solid var(--pc-border); border-radius:8px; padding:6px 10px; margin:0 6px; font-size:13px; }
.dataTables_wrapper .paginate_button.current, .dataTables_wrapper .paginate_button.current:hover { background:var(--pc-accent) !important; border-color:var(--pc-accent) !important; color:#fff !important; }

.pc-modal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); padding:22px 28px; position:relative; overflow:hidden; }
.modal-hero-bar::before { content:''; position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,.07); border-radius:50%; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border:1.5px solid var(--pc-border); border-radius:8px; font-size:13px; padding:9px 14px; }
.form-control:focus, .form-select:focus { border-color:var(--pc-accent); box-shadow:0 0 0 3px rgba(37,99,235,.1); }

.checkbox-scroll { max-height:220px; overflow-y:auto; border:1.5px solid var(--pc-border); border-radius:8px; padding:10px 14px; background:#fafbfc; }
.checkbox-scroll .form-check { padding:5px 0; border-bottom:1px solid #f0f0f0; }
.checkbox-scroll .form-check:last-child { border-bottom:none; }
.checkbox-scroll .form-check-label { font-size:13px; cursor:pointer; }

.bulk-bar { background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:10px 16px; display:none; align-items:center; gap:12px; margin-bottom:12px; }
.bulk-bar.show { display:flex; }

#pc-page-loader { position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; opacity:0; visibility:hidden; transition:opacity .22s, visibility .22s; }
#pc-page-loader.active { opacity:1; visibility:visible; }
.pc-loader-card { background:#fff; border-radius:16px; padding:32px 40px; text-align:center; box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px; }
.pc-loader-spinner { width:52px; height:52px; margin:0 auto 16px; border:4px solid #e2e8f0; border-top-color:var(--pc-accent); border-radius:50%; animation:pc-spin .75s linear infinite; }
@keyframes pc-spin { to { transform:rotate(360deg); } }
.pc-loader-label { font-size:14px; font-weight:600; color:var(--pc-primary); margin-bottom:12px; }

#pc-toast-stack { position:fixed; bottom:24px; right:24px; z-index:10000; display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none; }
.pc-toast { pointer-events:all; background:#fff; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.14); padding:14px 18px; min-width:280px; max-width:360px; display:flex; align-items:flex-start; gap:12px; border-left:4px solid var(--pc-accent); transform:translateX(120%); transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.pc-toast.show { transform:translateX(0); }
.pc-toast-success { border-left-color:var(--pc-success); }
.pc-toast-error   { border-left-color:var(--pc-danger);  }
.pc-toast-warning { border-left-color:var(--pc-warning); }
.pc-toast .pc-toast-icon { font-size:20px; flex-shrink:0; }
.pc-toast .pc-toast-body { flex:1; }
.pc-toast .pc-toast-title { font-size:13px; font-weight:700; color:#111827; }
.pc-toast .pc-toast-msg   { font-size:12px; color:var(--pc-muted); }
.pc-toast .pc-toast-close { background:none; border:none; cursor:pointer; color:var(--pc-muted); font-size:16px; }

.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after { content:''; position:absolute; inset:0; margin:auto; width:16px; height:16px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:pc-spin .65s linear infinite; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<div id="pc-page-loader"><div class="pc-loader-card"><div class="pc-loader-spinner"></div><div class="pc-loader-label" id="pc-loader-label">Processing…</div></div></div>
<div id="pc-toast-stack"></div>

<div class="main-content"><div class="page-content"><div class="container-fluid">

    {{-- Hero --}}
    <div class="pc-hero">
        <h1><i class="ri-chat-quote-line me-2"></i>Principals Comment Management</h1>
        <p>Assign staff members to classes for principal's report card comments.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-list-check"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Assignments</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-user-star-line"></i></div><div class="stat-value text-primary" id="statStaff">—</div><div class="stat-label">Unique Staff</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-building-line"></i></div><div class="stat-value text-success" id="statClasses">—</div><div class="stat-label">Classes Covered</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-calendar-line"></i></div><div class="stat-value text-warning" id="statSessions">—</div><div class="stat-label">Sessions</div></div></div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--pc-primary)">
                    <i class="ri-list-check me-2"></i>Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
                    @can('Create principals-comment')
                    <button class="btn btn-primary" id="createBtn"><i class="ri-add-line me-1"></i>Create Assignment</button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> assignment(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table pc-table w-100 mb-0" id="pcTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>#</th>
                            <th>Staff</th>
                            <th>Class</th>
                            <th>Arm</th>
                            <th>Session</th>
                            <th>Term</th>
                            <th>Updated</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div></div></div>

{{-- ADD MODAL --}}
<div class="modal fade pc-modal" id="addPrincipalsCommentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Create Assignment</h5>
            </div>
            <form id="add-principalscomment-form" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Staff Member <span class="text-danger">*</span></label>
                            <select name="staffId" class="form-select" required>
                                <option value="">— Select Staff —</option>
                                @foreach ($staff as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session <span class="text-danger">*</span></label>
                            <select name="sessionid" class="form-select" required>
                                <option value="">— Select Session —</option>
                                @foreach ($sessions as $session)
                                    <option value="{{ $session->id }}" {{ $session->status == 'Current' ? 'selected' : '' }}>
                                        {{ $session->session }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Term <span class="text-danger">*</span></label>
                            <select name="termid" class="form-select" required>
                                <option value="">— Select Term —</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}">{{ $term->term }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Classes <span class="text-danger">*</span></label>
                            <div class="checkbox-scroll">
                                @foreach ($schoolclasses as $class)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="schoolclassid[]" value="{{ $class->id }}"
                                               id="add-class-{{ $class->id }}">
                                        <label class="form-check-label" for="add-class-{{ $class->id }}">
                                            {{ $class->label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-danger mt-3 d-none" id="alert-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Assignment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade pc-modal" id="editPrincipalsCommentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Assignment</h5>
            </div>
            <form id="edit-principalscomment-form" autocomplete="off">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Staff Member <span class="text-danger">*</span></label>
                            <select name="staffId" id="edit-staffId" class="form-select" required>
                                <option value="">— Select Staff —</option>
                                @foreach ($staff as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select name="schoolclassid" id="edit-schoolclassid" class="form-select" required>
                                <option value="">— Select Class —</option>
                                @foreach ($schoolclasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-danger mt-3 d-none" id="edit-alert-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Assignment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this assignment?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
                <input type="hidden" id="delete-id">
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

    const PageLoader = {
        show(lbl) { $('#pc-loader-label').text(lbl || 'Processing…'); $('#pc-page-loader').addClass('active'); },
        hide() { setTimeout(() => $('#pc-page-loader').removeClass('active'), 300); }
    };

    function toast(type, title, msg) {
        var icons = { success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
        var id = 'pc-toast-' + Date.now();
        var $el = $('<div class="pc-toast pc-toast-' + type + '" id="' + id + '">'
            + '<span class="pc-toast-icon"><i class="' + icons[type] + '"></i></span>'
            + '<div class="pc-toast-body"><div class="pc-toast-title">' + title + '</div>'
            + (msg ? '<div class="pc-toast-msg">' + msg + '</div>' : '') + '</div>'
            + '<button class="pc-toast-close" onclick="$(\'#' + id + '\').remove()">×</button></div>');
        $('#pc-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        setTimeout(() => { $el.removeClass('show'); setTimeout(() => $el.remove(), 300); }, 4000);
    }

    function btnLoad($b, lbl) { $b.data('orig', $b.html()).prop('disabled', true).addClass('btn-loading'); if (lbl) $b.html('<span class="btn-text">' + lbl + '</span>'); }
    function btnReset($b) { var o = $b.data('orig'); if (o) $b.html(o); $b.prop('disabled', false).removeClass('btn-loading'); }

    var table = $('#pcTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("principalscomment.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load assignments. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'staff_info', orderable: false, searchable: false },
            { data: 'class_info', orderable: false, searchable: false },
            { data: 'arm_info', orderable: false, searchable: false },
            { data: 'session_info', orderable: false, searchable: false },
            { data: 'term_info', orderable: false, searchable: false },
            { data: 'formatted_date', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search: '', searchPlaceholder: 'Search assignments…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ assignments',
            infoEmpty: 'No assignments found', zeroRecords: 'No matching assignments',
            emptyTable: 'No principals comment assignments yet'
        },
        order: [[1, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            bindCB();
            $('#totalBadge').text(this.api().page.info().recordsTotal);
        }
    });

    function loadStats() {
        $.get('{{ route("principalscomment.stats") }}', function(d) {
            if (d.stats) {
                $('#statTotal').text(d.stats.total);
                $('#statStaff').text(d.stats.unique_staff);
                $('#statClasses').text(d.stats.unique_classes);
                $('#statSessions').text(d.stats.unique_sessions);
            }
        }).fail(function() {
            $('#statTotal, #statStaff, #statClasses, #statSessions').text('—');
        });
    }
    loadStats();

    function bindCB() { $('.row-checkbox').off('change').on('change', updBulk); }
    $('#selectAll').on('change', function() { $('.row-checkbox').prop('checked', this.checked); updBulk(); });
    function updBulk() {
        var c = $('.row-checkbox:checked').length;
        $('#bulkBar').toggleClass('show', c > 0);
        $('#bulkCount').text(c);
        $('#bulkDeleteBtn').toggleClass('d-none', c === 0);
        if (c === 0) $('#selectAll').prop('checked', false);
    }

    $('#createBtn').on('click', function() {
        document.getElementById('add-principalscomment-form').reset();
        $('#alert-error-msg').addClass('d-none').html('');
        new bootstrap.Modal(document.getElementById('addPrincipalsCommentModal')).show();
    });

    $(document).on('click', '.edit-btn', function() {
        $('#edit-id').val($(this).data('id'));
        $('#edit-staffId').val($(this).data('staffid'));
        $('#edit-schoolclassid').val($(this).data('schoolclassid'));
        $('#edit-alert-error-msg').addClass('d-none').html('');
        btnReset($('#edit-btn'));
        new bootstrap.Modal(document.getElementById('editPrincipalsCommentModal')).show();
    });

    $(document).on('click', '.delete-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-id').val(deleteId);
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteConfirmationModal')).show();
    });

    $('#add-principalscomment-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#add-btn');
        btnLoad($btn, 'Adding…');
        $('#alert-error-msg').addClass('d-none').html('');

        var fd = new FormData(this);

        $.ajax({
            url: '{{ route("principalscomment.store") }}',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            success: function(res) {
                if (res.success) {
                    $('#addPrincipalsCommentModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($btn);
                    var msg = res.message || (res.errors && Object.values(res.errors).flat()[0]) || 'Failed.';
                    $('#alert-error-msg').removeClass('d-none').html(msg);
                }
            },
            error: function(xhr) {
                btnReset($btn);
                var j = xhr.responseJSON;
                var msg = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                $('#alert-error-msg').removeClass('d-none').html(msg);
            }
        });
    });

    $('#edit-principalscomment-form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#edit-id').val();
        var $btn = $('#edit-btn');
        btnLoad($btn, 'Updating…');
        $('#edit-alert-error-msg').addClass('d-none').html('');

        var fd = new FormData(this);

        $.ajax({
            url: '{{ url("principalscomment") }}/' + id,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-HTTP-Method-Override': 'PUT'
            },
            success: function(res) {
                if (res.success) {
                    $('#editPrincipalsCommentModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($btn);
                    var msg = res.message || (res.errors && Object.values(res.errors).flat()[0]) || 'Failed.';
                    $('#edit-alert-error-msg').removeClass('d-none').html(msg);
                }
            },
            error: function(xhr) {
                btnReset($btn);
                var j = xhr.responseJSON;
                var msg = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                $('#edit-alert-error-msg').removeClass('d-none').html(msg);
            }
        });
    });

    $('#confirm-delete-btn').on('click', function() {
        var id = $('#delete-id').val();
        if (!id) return;
        var $b = $(this); btnLoad($b, 'Deleting…');

        $.ajax({
            url: '{{ url("principalscomment") }}/' + id,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                $('#deleteConfirmationModal').modal('hide');
                if (res.success) {
                    toast('success', 'Deleted!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    toast('error', 'Cannot Delete', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                $('#deleteConfirmationModal').modal('hide');
                toast('error', 'Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed.');
            },
            complete: function() { btnReset($b); deleteId = null; }
        });
    });

    function doBulk() {
        var ids = $('.row-checkbox:checked').map(function() { return this.value; }).get();
        if (!ids.length) { toast('warning', 'No Selection', 'Select at least one assignment.'); return; }

        Swal.fire({
            title: 'Delete ' + ids.length + ' assignment(s)?',
            html: 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel', reverseButtons: true, showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting assignments…');

                    // Loop deletions client-side since there's no bulk endpoint
                    var done = 0, failed = 0, firstErr = null;
                    var total = ids.length;

                    function next() {
                        if (done + failed >= total) {
                            PageLoader.hide();
                            if (done === 0 && firstErr) return reject(firstErr);
                            return resolve({ message: done + ' assignment(s) deleted.' + (failed ? ' ' + failed + ' failed.' : '') });
                        }
                        var id = ids[done + failed];
                        $.ajax({
                            url: '{{ url("principalscomment") }}/' + id,
                            type: 'POST',
                            data: { _method: 'DELETE', _token: CSRF },
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            success: function(res) { if (res.success) done++; else { failed++; firstErr = firstErr || res.message; } },
                            error: function(xhr) { failed++; firstErr = firstErr || (xhr.responseJSON && xhr.responseJSON.message) || 'Error.'; },
                            complete: next
                        });
                    }
                    next();
                });
            }
        }).then(function(r) {
            if (r.isConfirmed && r.value) {
                toast('success', 'Done!', r.value.message);
                table.ajax.reload(); loadStats();
                $('#selectAll').prop('checked', false); updBulk();
            }
        }).catch(function(err) { toast('error', 'Failed', typeof err === 'string' ? err : 'Could not delete.'); });
    }
    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulk);

    bindCB();
});
</script>
@endsection
