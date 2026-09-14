{{-- resources/views/subject/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sub-primary: #1e3a5f; --sub-accent: #2563eb; --sub-success: #16a34a;
    --sub-warning: #d97706; --sub-danger: #dc2626; --sub-muted: #6b7280;
    --sub-border: #e2e8f0; --sub-radius: 12px; --sub-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.sub-hero { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#7c3aed 100%); border-radius:var(--sub-radius); padding:28px 32px; margin-bottom:24px; position:relative; overflow:hidden; }
.sub-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%; }
.sub-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sub-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.stat-card { background:#fff; border:1px solid var(--sub-border); border-radius:var(--sub-radius); padding:18px 20px; }
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sub-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sub-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sub-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.sub-table th { background:var(--sub-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.sub-table td { padding:11px 16px; vertical-align:middle; border-bottom:1px solid var(--sub-border); font-size:13px; }
.sub-table tr:hover td { background:#eff6ff; }

.sub-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.sub-badge-remark { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

.dataTables_wrapper .dataTables_filter input { border:1.5px solid var(--sub-border); border-radius:8px; padding:7px 14px; margin-left:8px; font-size:13px; }
.dataTables_wrapper .dataTables_filter input:focus { border-color:var(--sub-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.dataTables_wrapper .dataTables_length select { border:1.5px solid var(--sub-border); border-radius:8px; padding:6px 10px; margin:0 6px; font-size:13px; }
.dataTables_wrapper .paginate_button.current, .dataTables_wrapper .paginate_button.current:hover { background:var(--sub-accent) !important; border-color:var(--sub-accent) !important; color:#fff !important; }

.sub-modal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar { background:linear-gradient(135deg,#1e3a5f 0%,#7c3aed 100%); padding:22px 28px; position:relative; overflow:hidden; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border:1.5px solid var(--sub-border); border-radius:8px; font-size:13px; padding:9px 14px; }
.form-control:focus, .form-select:focus { border-color:var(--sub-accent); box-shadow:0 0 0 3px rgba(37,99,235,.1); }

.bulk-bar { background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:10px 16px; display:none; align-items:center; gap:12px; margin-bottom:12px; }
.bulk-bar.show { display:flex; }

#sub-page-loader { position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; opacity:0; visibility:hidden; transition:opacity .22s, visibility .22s; }
#sub-page-loader.active { opacity:1; visibility:visible; }
.sub-loader-card { background:#fff; border-radius:16px; padding:32px 40px; text-align:center; box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px; }
.sub-loader-spinner { width:52px; height:52px; margin:0 auto 16px; border:4px solid #e2e8f0; border-top-color:var(--sub-accent); border-radius:50%; animation:sub-spin .75s linear infinite; }
@keyframes sub-spin { to { transform:rotate(360deg); } }
.sub-loader-label { font-size:14px; font-weight:600; color:var(--sub-primary); margin-bottom:12px; }

#sub-toast-stack { position:fixed; bottom:24px; right:24px; z-index:10000; display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none; }
.sub-toast { pointer-events:all; background:#fff; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.14); padding:14px 18px; min-width:280px; max-width:360px; display:flex; align-items:flex-start; gap:12px; border-left:4px solid var(--sub-accent); transform:translateX(120%); transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.sub-toast.show { transform:translateX(0); }
.sub-toast-success { border-left-color:var(--sub-success); }
.sub-toast-error   { border-left-color:var(--sub-danger);  }
.sub-toast-warning { border-left-color:var(--sub-warning); }
.sub-toast .sub-toast-icon { font-size:20px; flex-shrink:0; }
.sub-toast .sub-toast-body { flex:1; }
.sub-toast .sub-toast-title { font-size:13px; font-weight:700; color:#111827; }
.sub-toast .sub-toast-msg   { font-size:12px; color:var(--sub-muted); }
.sub-toast .sub-toast-close { background:none; border:none; cursor:pointer; color:var(--sub-muted); font-size:16px; }

.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after { content:''; position:absolute; inset:0; margin:auto; width:16px; height:16px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:sub-spin .65s linear infinite; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<div id="sub-page-loader"><div class="sub-loader-card"><div class="sub-loader-spinner"></div><div class="sub-loader-label" id="sub-loader-label">Processing…</div></div></div>
<div id="sub-toast-stack"></div>

<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="sub-hero">
        <h1><i class="ri-book-2-line me-2"></i>Subject Management</h1>
        <p>Create and manage all subjects offered across your school.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-book-2-line"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Subjects</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-barcode-line"></i></div><div class="stat-value text-primary" id="statShowing">—</div><div class="stat-label">Showing Now</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-user-star-line"></i></div><div class="stat-value text-success" id="statWithTeachers">—</div><div class="stat-label">With Teachers</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-calendar-line"></i></div><div class="stat-value text-warning" id="statRecent">—</div><div class="stat-label">Recent Updates (30d)</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sub-primary)">
                    <i class="ri-list-check me-2"></i>All Subjects
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
                    @can('Create subjects')
                    <button class="btn btn-primary" id="createSubjectBtn"><i class="ri-add-line me-1"></i>Create Subject</button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> subject(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table sub-table w-100 mb-0" id="subjectsTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
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
</div></div></div>

{{-- ADD MODAL --}}
<div class="modal fade sub-modal" id="addSubjectModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-circle-line me-2"></i>Add Subject</h5>
            </div>
            <form id="add-subject-form" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="add-subject" class="form-control" placeholder="e.g. Mathematics" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" id="add-subject-code" class="form-control" placeholder="e.g. MTH101" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark <span class="text-danger">*</span></label>
                        <input type="text" name="remark" id="add-remark" class="form-control" placeholder="e.g. Core Subject" required>
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

{{-- EDIT MODAL --}}
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
                <div class="modal-body p-4">
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

{{-- DELETE MODAL --}}
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

    const PageLoader = {
        show(lbl) { $('#sub-loader-label').text(lbl || 'Processing…'); $('#sub-page-loader').addClass('active'); },
        hide() { setTimeout(() => $('#sub-page-loader').removeClass('active'), 300); }
    };

    function toast(type, title, msg) {
        var icons = { success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
        var id = 'sub-toast-' + Date.now();
        var $el = $('<div class="sub-toast sub-toast-' + type + '" id="' + id + '">'
            + '<span class="sub-toast-icon"><i class="' + icons[type] + '"></i></span>'
            + '<div class="sub-toast-body"><div class="sub-toast-title">' + title + '</div>'
            + (msg ? '<div class="sub-toast-msg">' + msg + '</div>' : '') + '</div>'
            + '<button class="sub-toast-close" onclick="$(\'#' + id + '\').remove()">×</button></div>');
        $('#sub-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        setTimeout(() => { $el.removeClass('show'); setTimeout(() => $el.remove(), 300); }, 4000);
    }

    function btnLoad($b, lbl) { $b.data('orig', $b.html()).prop('disabled', true).addClass('btn-loading'); if (lbl) $b.html('<span class="btn-text">' + lbl + '</span>'); }
    function btnReset($b) { var o = $b.data('orig'); if (o) $b.html(o); $b.prop('disabled', false).removeClass('btn-loading'); }
    function showErr(sel, m) { $(sel).removeClass('d-none').html('<i class="ri-error-warning-line me-1"></i>' + m); }

    var table = $('#subjectsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("subject.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load subjects. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'subject_info', orderable: false, searchable: false },
            { data: 'code_info', orderable: false, searchable: false },
            { data: 'remark_info', orderable: false, searchable: false },
            { data: 'usage_count', orderable: false, searchable: false },
            { data: 'formatted_date', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search: '', searchPlaceholder: 'Search subjects…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ subjects',
            infoEmpty: 'No subjects found', zeroRecords: 'No matching subjects',
            emptyTable: 'No subjects created yet'
        },
        order: [[2, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            bindCB();
            $('#totalBadge').text(this.api().page.info().recordsTotal);
        }
    });

    function loadStats() {
        $.get('{{ route("subject.stats") }}', function(d) {
            if (d.stats) {
                $('#statTotal').text(d.stats.total);
                $('#statShowing').text(d.stats.showing);
                $('#statWithTeachers').text(d.stats.with_teachers);
                $('#statRecent').text(d.stats.recently_updated);
            }
        }).fail(function() {
            $('#statTotal, #statShowing, #statWithTeachers, #statRecent').text('—');
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

    function updateCreateBtn() {
        var ok = $('#add-subject').val().trim() !== ''
              && $('#add-subject-code').val().trim() !== ''
              && $('#add-remark').val().trim() !== '';
        $('#add-btn').prop('disabled', !ok);
    }
    $('#add-subject, #add-subject-code, #add-remark').on('input', updateCreateBtn);

    $('#createSubjectBtn').on('click', function() {
        $('#add-subject').val('');
        $('#add-subject-code').val('');
        $('#add-remark').val('');
        $('#add-btn').prop('disabled', true);
        $('#add-error-msg').addClass('d-none').html('');
        new bootstrap.Modal(document.getElementById('addSubjectModal')).show();
    });

    $(document).on('click', '.edit-subject-btn', function() {
        var $b = $(this);
        $('#edit-id').val($b.data('id'));
        $('#edit-subject').val($b.data('subject'));
        $('#edit-subject-code').val($b.data('code'));
        $('#edit-remark').val($b.data('remark'));
        $('#edit-error-msg').addClass('d-none').html('');
        btnReset($('#update-btn'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $('#add-subject-form').on('submit', function(e) {
        e.preventDefault();
        btnLoad($('#add-btn'), 'Adding…');
        $('#add-error-msg').addClass('d-none').html('');
        $.ajax({
            url: '{{ route("subject.store") }}',
            type: 'POST',
            data: {
                subject: $('#add-subject').val().trim(),
                subject_code: $('#add-subject-code').val().trim(),
                remark: $('#add-remark').val().trim(),
                _token: CSRF
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#addSubjectModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($('#add-btn')); updateCreateBtn();
                    showErr('#add-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#add-btn')); updateCreateBtn();
                var j = xhr.responseJSON;
                var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr('#add-error-msg', m);
            }
        });
    });

    $('#edit-subject-form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#edit-id').val();
        btnLoad($('#update-btn'), 'Updating…');
        $('#edit-error-msg').addClass('d-none').html('');
        $.ajax({
            url: '{{ url("subject") }}/' + id,
            type: 'POST',
            data: {
                _method: 'PUT',
                subject: $('#edit-subject').val().trim(),
                subject_code: $('#edit-subject-code').val().trim(),
                remark: $('#edit-remark').val().trim(),
                _token: CSRF
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($('#update-btn'));
                    showErr('#edit-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#update-btn'));
                var j = xhr.responseJSON;
                var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr('#edit-error-msg', m);
            }
        });
    });

    $(document).on('click', '.delete-subject-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-subject-name').text($(this).data('subject') || 'this subject');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $b = $(this); btnLoad($b, 'Deleting…');
        $.ajax({
            url: '{{ url("subject") }}/' + deleteId,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                $('#deleteModal').modal('hide');
                if (res.success) { toast('success', 'Deleted!', res.message); table.ajax.reload(); loadStats(); }
                else { toast('error', 'Cannot Delete', res.message); }
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                toast('error', 'Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete.');
            },
            complete: function() { btnReset($b); deleteId = null; }
        });
    });

    function doBulk() {
        var ids = $('.row-checkbox:checked').map(function() { return this.value; }).get();
        if (!ids.length) { toast('warning', 'No Selection', 'Select at least one subject.'); return; }
        Swal.fire({
            title: 'Delete ' + ids.length + ' subject(s)?',
            html: 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel', reverseButtons: true, showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting subjects…');
                    $.ajax({
                        url: '{{ route("subject.bulk-destroy") }}',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ ids: ids, _token: CSRF }),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function(res) { PageLoader.hide(); if (res.success) resolve(res); else reject(res.message); },
                        error: function(xhr) { PageLoader.hide(); reject((xhr.responseJSON && xhr.responseJSON.message) || 'Error.'); }
                    });
                });
            }
        }).then(function(r) {
            if (r.isConfirmed && r.value) {
                toast('success', 'Deleted!', r.value.message);
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
