{{-- resources/views/schoolhouse/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sh-primary:#1e3a5f; --sh-accent:#2563eb; --sh-success:#16a34a;
    --sh-warning:#d97706; --sh-danger:#dc2626; --sh-muted:#6b7280;
    --sh-border:#e2e8f0; --sh-radius:12px; --sh-shadow:0 2px 8px rgba(0,0,0,.08);
}
.sh-hero { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#4f46e5 100%); border-radius:var(--sh-radius); padding:28px 32px; margin-bottom:24px; position:relative; overflow:hidden; }
.sh-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%; }
.sh-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sh-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.stat-card { background:#fff; border:1px solid var(--sh-border); border-radius:var(--sh-radius); padding:18px 20px; transition:transform .15s, box-shadow .15s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sh-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sh-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sh-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.sh-table th { background:var(--sh-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.sh-table td { padding:11px 16px; vertical-align:middle; border-bottom:1px solid var(--sh-border); font-size:13px; }
.sh-table tr:hover td { background:#f0f9ff; }

.sh-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.sh-badge-colour { padding:4px 12px; color:#fff; border-radius:20px; }
.sh-badge-term    { background:#dbeafe; color:#2563eb; }
.sh-badge-session { background:#ccfbf1; color:#0f766e; }

.dataTables_wrapper .dataTables_filter input { border:1.5px solid var(--sh-border); border-radius:8px; padding:7px 14px; margin-left:8px; font-size:13px; }
.dataTables_wrapper .dataTables_filter input:focus { border-color:var(--sh-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.dataTables_wrapper .dataTables_length select { border:1.5px solid var(--sh-border); border-radius:8px; padding:6px 10px; margin:0 6px; font-size:13px; }
.dataTables_wrapper .dataTables_info { font-size:13px; color:var(--sh-muted); }
.dataTables_wrapper .paginate_button { border-radius:6px !important; font-size:13px !important; padding:4px 10px !important; }
.dataTables_wrapper .paginate_button.current, .dataTables_wrapper .paginate_button.current:hover { background:var(--sh-accent) !important; border-color:var(--sh-accent) !important; color:#fff !important; }

.sh-modal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); padding:22px 28px; position:relative; overflow:hidden; }
.modal-hero-bar::before { content:''; position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,.07); border-radius:50%; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border:1.5px solid var(--sh-border); border-radius:8px; font-size:13px; padding:9px 14px; }
.form-control:focus, .form-select:focus { border-color:var(--sh-accent); box-shadow:0 0 0 3px rgba(37,99,235,.1); }

.bulk-bar { background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:10px 16px; display:none; align-items:center; gap:12px; margin-bottom:12px; }
.bulk-bar.show { display:flex; }

#sh-page-loader { position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; opacity:0; visibility:hidden; transition:opacity .22s, visibility .22s; }
#sh-page-loader.active { opacity:1; visibility:visible; }
.sh-loader-card { background:#fff; border-radius:16px; padding:32px 40px; text-align:center; box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px; }
.sh-loader-spinner { width:52px; height:52px; margin:0 auto 16px; border:4px solid #e2e8f0; border-top-color:var(--sh-accent); border-radius:50%; animation:sh-spin .75s linear infinite; }
@keyframes sh-spin { to { transform:rotate(360deg); } }
.sh-loader-label { font-size:14px; font-weight:600; color:var(--sh-primary); margin-bottom:12px; }

#sh-toast-stack { position:fixed; bottom:24px; right:24px; z-index:10000; display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none; }
.sh-toast { pointer-events:all; background:#fff; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.14); padding:14px 18px; min-width:280px; max-width:360px; display:flex; align-items:flex-start; gap:12px; border-left:4px solid var(--sh-accent); transform:translateX(120%); transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.sh-toast.show { transform:translateX(0); }
.sh-toast-success { border-left-color:var(--sh-success); }
.sh-toast-error   { border-left-color:var(--sh-danger);  }
.sh-toast-warning { border-left-color:var(--sh-warning); }
.sh-toast .sh-toast-icon { font-size:20px; flex-shrink:0; }
.sh-toast .sh-toast-body { flex:1; }
.sh-toast .sh-toast-title { font-size:13px; font-weight:700; color:#111827; }
.sh-toast .sh-toast-msg   { font-size:12px; color:var(--sh-muted); }
.sh-toast .sh-toast-close { background:none; border:none; cursor:pointer; color:var(--sh-muted); font-size:16px; }

.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after { content:''; position:absolute; inset:0; margin:auto; width:16px; height:16px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:sh-spin .65s linear infinite; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<div id="sh-page-loader"><div class="sh-loader-card"><div class="sh-loader-spinner"></div><div class="sh-loader-label" id="sh-loader-label">Processing…</div></div></div>
<div id="sh-toast-stack"></div>

<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="sh-hero">
        <h1><i class="ri-home-2-line me-2"></i>School House Management</h1>
        <p>Manage school houses, their colours, masters, terms, and sessions.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-home-2-line"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Houses</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-user-line"></i></div><div class="stat-value text-primary" id="statMasters">—</div><div class="stat-label">Unique Masters</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-bookmark-line"></i></div><div class="stat-value text-success" id="statTerms">—</div><div class="stat-label">Active Terms</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-calendar-event-line"></i></div><div class="stat-value text-warning" id="statSessions">—</div><div class="stat-label">Active Sessions</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sh-primary)">
                    <i class="ri-list-check me-2"></i>School Houses List
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
                    @can('Create schoolhouse')
                    <button class="btn btn-primary" id="createHouseBtn"><i class="ri-add-line me-1"></i>Create House</button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> house(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table sh-table w-100 mb-0" id="housesTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>#</th>
                            <th>House</th>
                            <th>Colour</th>
                            <th>House Master</th>
                            <th>Term</th>
                            <th>Session</th>
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

{{-- CREATE MODAL --}}
<div class="modal fade sh-modal" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Create New School House</h5>
            </div>
            <form id="createForm" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">House Name <span class="text-danger">*</span></label>
                        <input type="text" name="house" id="create-house" class="form-control" placeholder="Enter house name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">House Colour <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" name="housecolour" id="create-housecolour" class="form-control" placeholder="e.g., red, #FF0000" required>
                            <input type="color" id="create-colour-picker" class="form-control" style="width: 60px; padding: 2px; height: 38px;" value="#2563eb">
                        </div>
                        <small class="text-muted">Enter a valid CSS color (name, hex, or RGB)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">House Master <span class="text-danger">*</span></label>
                        <select name="housemasterid" id="create-housemasterid" class="form-select" required>
                            <option value="">— Select House Master —</option>
                            @foreach ($staff as $s)
                                <option value="{{ $s->userid }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="termid" id="create-termid" class="form-select" required>
                            <option value="">— Select Term —</option>
                            @foreach ($schoolterm as $term)
                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <select name="sessionid" id="create-sessionid" class="form-select" required>
                            <option value="">— Select Session —</option>
                            @foreach ($schoolsession as $session)
                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-danger d-none" id="create-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="create-save-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Create House</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade sh-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit School House</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-house-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">House Name <span class="text-danger">*</span></label>
                        <input type="text" name="house" id="edit-house" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">House Colour <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" name="housecolour" id="edit-housecolour" class="form-control" required>
                            <input type="color" id="edit-colour-picker" class="form-control" style="width: 60px; padding: 2px; height: 38px;" value="#2563eb">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">House Master <span class="text-danger">*</span></label>
                        <select name="housemasterid" id="edit-housemasterid" class="form-select" required>
                            <option value="">— Select House Master —</option>
                            @foreach ($staff as $s)
                                <option value="{{ $s->userid }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="termid" id="edit-termid" class="form-select" required>
                            <option value="">— Select Term —</option>
                            @foreach ($schoolterm as $term)
                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <select name="sessionid" id="edit-sessionid" class="form-select" required>
                            <option value="">— Select Session —</option>
                            @foreach ($schoolsession as $session)
                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update House</span>
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
                <p>Remove <strong id="delete-item-title"></strong>?</p>
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
        _p: 0, _t: null,
        show(lbl) { $('#sh-loader-label').text(lbl || 'Processing…'); $('#sh-page-loader').addClass('active'); },
        hide() { setTimeout(() => $('#sh-page-loader').removeClass('active'), 300); }
    };

    function toast(type, title, msg) {
        var icons = { success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
        var id = 'sh-toast-' + Date.now();
        var $el = $('<div class="sh-toast sh-toast-' + type + '" id="' + id + '">'
            + '<span class="sh-toast-icon"><i class="' + icons[type] + '"></i></span>'
            + '<div class="sh-toast-body"><div class="sh-toast-title">' + title + '</div>'
            + (msg ? '<div class="sh-toast-msg">' + msg + '</div>' : '') + '</div>'
            + '<button class="sh-toast-close" onclick="$(\'#' + id + '\').remove()">×</button></div>');
        $('#sh-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        setTimeout(() => { $el.removeClass('show'); setTimeout(() => $el.remove(), 300); }, 4000);
    }

    function btnLoad($b, lbl) { $b.data('orig', $b.html()).prop('disabled', true).addClass('btn-loading'); if (lbl) $b.html('<span class="btn-text">' + lbl + '</span>'); }
    function btnReset($b) { var o = $b.data('orig'); if (o) $b.html(o); $b.prop('disabled', false).removeClass('btn-loading'); }
    function showErr(sel, m) { $(sel).removeClass('d-none').html('<i class="ri-error-warning-line me-1"></i>' + m); }

    var table = $('#housesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("schoolhouse.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load houses. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'house_info', orderable: false },
            { data: 'colour_info', orderable: false },
            { data: 'master_info', orderable: false },
            { data: 'term_info', orderable: false },
            { data: 'session_info', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search: '', searchPlaceholder: 'Search houses…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ houses',
            infoEmpty: 'No houses found', zeroRecords: 'No matching houses',
            emptyTable: 'No school houses created yet'
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
        $.get('{{ route("schoolhouse.stats") }}', function(d) {
            if (d.stats) {
                $('#statTotal').text(d.stats.total);
                $('#statMasters').text(d.stats.unique_masters);
                $('#statTerms').text(d.stats.unique_terms);
                $('#statSessions').text(d.stats.unique_sessions);
            }
        }).fail(function() {
            $('#statTotal, #statMasters, #statTerms, #statSessions').text('—');
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

    // Colour picker sync
    $('#create-colour-picker').on('input', function() { $('#create-housecolour').val($(this).val()); updateCreateBtn(); });
    $('#create-housecolour').on('input', function() { $('#create-colour-picker').val($(this).val()); updateCreateBtn(); });
    $('#edit-colour-picker').on('input', function() { $('#edit-housecolour').val($(this).val()); });
    $('#edit-housecolour').on('input', function() { $('#edit-colour-picker').val($(this).val()); });

    function updateCreateBtn() {
        var ok = $('#create-house').val().trim() !== '' &&
                 $('#create-housecolour').val().trim() !== '' &&
                 $('#create-housemasterid').val() !== '' &&
                 $('#create-termid').val() !== '' &&
                 $('#create-sessionid').val() !== '';
        $('#create-save-btn').prop('disabled', !ok);
    }
    $('#create-house, #create-housecolour, #create-housemasterid, #create-termid, #create-sessionid').on('change input', updateCreateBtn);

    $('#createHouseBtn').on('click', function() {
        $('#create-house').val('');
        $('#create-housecolour').val('#2563eb');
        $('#create-colour-picker').val('#2563eb');
        $('#create-housemasterid').val('');
        $('#create-termid').val('');
        $('#create-sessionid').val('');
        $('#create-save-btn').prop('disabled', true);
        $('#create-error-msg').addClass('d-none').html('');
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });

    $(document).on('click', '.edit-house-btn', function() {
        var $b = $(this);
        $('#edit-house-id').val($b.data('id'));
        $('#edit-house').val($b.data('house'));
        $('#edit-housecolour').val($b.data('housecolour') || '#2563eb');
        $('#edit-colour-picker').val($b.data('housecolour') || '#2563eb');
        $('#edit-housemasterid').val($b.data('housemasterid'));
        $('#edit-termid').val($b.data('termid'));
        $('#edit-sessionid').val($b.data('sessionid'));
        $('#edit-error-msg').addClass('d-none').html('');
        btnReset($('#edit-update-btn'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        btnLoad($('#create-save-btn'), 'Saving…');
        $('#create-error-msg').addClass('d-none').html('');
        $.ajax({
            url: '{{ route("schoolhouse.store") }}',
            type: 'POST',
            data: {
                house: $('#create-house').val().trim(),
                housecolour: $('#create-housecolour').val().trim(),
                housemasterid: $('#create-housemasterid').val(),
                termid: $('#create-termid').val(),
                sessionid: $('#create-sessionid').val(),
                _token: CSRF
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#createModal').modal('hide');
                    toast('success', 'Created!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($('#create-save-btn')); updateCreateBtn();
                    showErr('#create-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#create-save-btn')); updateCreateBtn();
                var j = xhr.responseJSON;
                var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr('#create-error-msg', m);
            }
        });
    });

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        btnLoad($('#edit-update-btn'), 'Updating…');
        $('#edit-error-msg').addClass('d-none').html('');
        $.ajax({
            url: '{{ route("schoolhouse.updatehouse") }}',
            type: 'POST',
            data: {
                id: $('#edit-house-id').val(),
                house: $('#edit-house').val().trim(),
                housecolour: $('#edit-housecolour').val().trim(),
                housemasterid: $('#edit-housemasterid').val(),
                termid: $('#edit-termid').val(),
                sessionid: $('#edit-sessionid').val(),
                _token: CSRF
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($('#edit-update-btn'));
                    showErr('#edit-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#edit-update-btn'));
                var j = xhr.responseJSON;
                var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr('#edit-error-msg', m);
            }
        });
    });

    $(document).on('click', '.delete-house-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-item-title').text($(this).data('name') || 'this house');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $b = $(this); btnLoad($b, 'Deleting…');
        $.ajax({
            url: '{{ route("schoolhouse.deletehouse") }}',
            type: 'POST',
            data: { houseid: deleteId, _token: CSRF },
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
        if (!ids.length) { toast('warning', 'No Selection', 'Please select at least one house.'); return; }
        Swal.fire({
            title: 'Delete ' + ids.length + ' house(s)?',
            html: 'This will permanently remove the selected houses.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel', reverseButtons: true, showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting houses…');
                    $.ajax({
                        url: '{{ route("schoolhouse.bulk-destroy") }}',
                        type: 'POST',
                        data: { ids: ids, _token: CSRF },
                        traditional: true,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function(res) { PageLoader.hide(); if (res.success) resolve(res); else reject(res.message || 'Failed.'); },
                        error: function(xhr) { PageLoader.hide(); reject((xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred.'); }
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
