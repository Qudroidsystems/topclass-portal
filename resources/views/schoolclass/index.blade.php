{{-- resources/views/compulsorysubjectclass/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --cs-primary: #0f766e;
    --cs-accent:  #0d9488;
    --cs-border:  #e2e8f0;
    --cs-muted:   #6b7280;
    --cs-radius:  12px;
}

.cs-hero {
    background: linear-gradient(135deg,#0f766e 0%,#0d9488 60%,#14b8a6 100%);
    border-radius: var(--cs-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.cs-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.cs-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.cs-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.stat-card { background:#fff; border:1px solid var(--cs-border); border-radius:var(--cs-radius); padding:18px 20px; }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--cs-primary); }
.stat-card .stat-label { font-size:12px; color:var(--cs-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.cs-table th { background:var(--cs-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.cs-table td { padding:11px 16px; vertical-align:middle; border-bottom:1px solid var(--cs-border); font-size:13px; }
.cs-table tr:hover td { background:#f0fdfa; }

.cs-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.cs-badge-term       { background:#dbeafe; color:#2563eb; }
.cs-badge-all-terms  { background:#f3f4f6; color:#6b7280; }
.cs-badge-session    { background:#fef3c7; color:#92400e; }
.cs-badge-grade      { background:#ede9fe; color:#6d28d9; }
.cs-badge-pass-avg   { background:#d1fae5; color:#065f46; }

.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--cs-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--cs-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--cs-accent) !important;
    border-color:var(--cs-accent) !important;
    color:#fff !important;
}

.cs-modal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar { background:linear-gradient(135deg,#0f766e 0%,#0d9488 100%); padding:22px 28px; position:relative; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select {
    border:1.5px solid var(--cs-border); border-radius:8px;
    font-size:13px; padding:9px 14px;
}
.form-control:focus, .form-select:focus {
    border-color:var(--cs-accent);
    box-shadow:0 0 0 3px rgba(13,148,136,.1);
}

.checkbox-scroll {
    max-height:240px; overflow-y:auto;
    border:1.5px solid var(--cs-border); border-radius:8px;
    padding:10px 14px; background:#fafbfc;
}
.checkbox-scroll .form-check { padding:5px 0; border-bottom:1px solid #f0f0f0; }
.checkbox-scroll .form-check:last-child { border-bottom:none; }

.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

#cs-toast-stack {
    position:fixed; bottom:24px; right:24px; z-index:10000;
    display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none;
}
.cs-toast {
    pointer-events:all; background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--cs-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.cs-toast.show { transform:translateX(0); }
.cs-toast-success { border-left-color:#16a34a; }
.cs-toast-error   { border-left-color:#dc2626; }
.cs-toast-warning { border-left-color:#d97706; }
.cs-toast .cs-toast-icon { font-size:20px; flex-shrink:0; }
.cs-toast .cs-toast-body { flex:1; }
.cs-toast .cs-toast-title { font-size:13px; font-weight:700; color:#111827; }
.cs-toast .cs-toast-msg   { font-size:12px; color:var(--cs-muted); }
.cs-toast .cs-toast-close { background:none; border:none; cursor:pointer; color:var(--cs-muted); font-size:16px; }

.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:''; position:absolute; inset:0; margin:auto;
    width:16px; height:16px;
    border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%;
    animation:cs-spin .65s linear infinite;
}
@keyframes cs-spin { to { transform:rotate(360deg); } }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<div id="cs-toast-stack"></div>

<div class="main-content"><div class="page-content"><div class="container-fluid">

    <div class="cs-hero">
        <h1><i class="ri-star-line me-2"></i>Compulsory Subject Class</h1>
        <p>Assign compulsory subjects per class, with optional term/session rules and a minimum grade.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-list-check"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Rules</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-group-line"></i></div><div class="stat-value text-primary" id="statClasses">—</div><div class="stat-label">Total Classes</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-calendar-line"></i></div><div class="stat-value text-warning" id="statSessions">—</div><div class="stat-label">Sessions</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-shield-check-line"></i></div><div class="stat-value text-success" id="statWithRules">—</div><div class="stat-label">Classes with Rules</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--cs-primary)">
                    <i class="ri-list-check me-2"></i>Compulsory Subjects
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
                    @can('Create compulsory-subject')
                    <button class="btn btn-primary" id="createBtn"><i class="ri-add-line me-1"></i>Add Compulsory Subject</button>
                    @endcan
                    @can('Update compulsory-subject')
                    <button class="btn btn-outline-primary" id="passAvgBtn"><i class="ri-settings-line me-1"></i>Promotion Pass Average</button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> record(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table cs-table w-100 mb-0" id="csTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Session</th>
                            <th>Min Grade</th>
                            <th>Pass Avg</th>
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

{{-- CREATE MODAL --}}
<div class="modal fade cs-modal" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Add Compulsory Subject(s)</h5>
            </div>
            <form id="createForm" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">School Class <span class="text-danger">*</span></label>
                        <select class="form-select" id="create-classid" required>
                            <option value="">-- Select Class --</option>
                            @foreach ($schoolclasses as $sc)
                                <option value="{{ $sc->id }}">{{ $sc->schoolclass }}{{ $sc->arm ? ' ('.$sc->arm.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Term (optional — leave blank for all terms)</label>
                            <select class="form-select" id="create-termid">
                                <option value="">All Terms</option>
                                @foreach ($terms as $t)
                                    <option value="{{ $t->id }}">{{ $t->term }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session (optional)</label>
                            <select class="form-select" id="create-sessionid">
                                <option value="">Any Session</option>
                                @foreach ($sessions as $s)
                                    <option value="{{ $s->id }}">{{ $s->session }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subjects <span class="text-danger">*</span></label>
                        <div id="create-subjects-wrap" class="checkbox-scroll">
                            <div class="text-muted small p-2">Select a class first to load its subjects.</div>
                        </div>
                    </div>

                    <div class="alert alert-danger d-none" id="create-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="create-save-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Subjects</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade cs-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Compulsory Subject</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">School Class <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit-classid" required>
                            @foreach ($schoolclasses as $sc)
                                <option value="{{ $sc->id }}">{{ $sc->schoolclass }}{{ $sc->arm ? ' ('.$sc->arm.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit-subjectid" required>
                            <option value="">-- Select Subject --</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Term</label>
                            <select class="form-select" id="edit-termid">
                                <option value="">All Terms</option>
                                @foreach ($terms as $t)
                                    <option value="{{ $t->id }}">{{ $t->term }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session</label>
                            <select class="form-select" id="edit-sessionid">
                                <option value="">Any Session</option>
                                @foreach ($sessions as $s)
                                    <option value="{{ $s->id }}">{{ $s->session }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Min Grade (optional)</label>
                        <input type="text" id="edit-min-grade" class="form-control" maxlength="10" placeholder="e.g., C6">
                    </div>
                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- PASS AVERAGE MODAL --}}
<div class="modal fade cs-modal" id="passAvgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-settings-line me-2"></i>Promotion Pass Average</h5>
            </div>
            <form id="passAvgForm" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">School Class <span class="text-danger">*</span></label>
                        <select class="form-select" id="pavg-classid" required>
                            <option value="">-- Select Class --</option>
                            @foreach ($schoolclasses as $sc)
                                <option value="{{ $sc->id }}">{{ $sc->schoolclass }}{{ $sc->arm ? ' ('.$sc->arm.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pass Average (%) <small class="text-muted">leave blank to disable</small></label>
                        <input type="number" id="pavg-value" class="form-control" min="0" max="100" step="0.1" placeholder="e.g., 40">
                    </div>
                    <div class="alert alert-danger d-none" id="pavg-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="pavg-save-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Save</span>
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

    function toast(type, title, msg) {
        var icons = { success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
        var id = 'cs-toast-' + Date.now();
        var $el = $('<div class="cs-toast cs-toast-' + type + '" id="' + id + '">'
            + '<span class="cs-toast-icon"><i class="' + icons[type] + '"></i></span>'
            + '<div class="cs-toast-body"><div class="cs-toast-title">' + title + '</div>'
            + (msg ? '<div class="cs-toast-msg">' + msg + '</div>' : '') + '</div>'
            + '<button class="cs-toast-close" onclick="$(\'#' + id + '\').remove()">×</button></div>');
        $('#cs-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        setTimeout(() => { $el.removeClass('show'); setTimeout(() => $el.remove(), 300); }, 4000);
    }

    function btnLoad($b, lbl) { $b.data('orig', $b.html()).prop('disabled', true).addClass('btn-loading'); if (lbl) $b.html('<span class="btn-text">' + lbl + '</span>'); }
    function btnReset($b) { var o = $b.data('orig'); if (o) $b.html(o); $b.prop('disabled', false).removeClass('btn-loading'); }
    function showErr(sel, m) { $(sel).removeClass('d-none').html('<i class="ri-error-warning-line me-1"></i>' + m); }

    var table = $('#csTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: '{{ route("compulsorysubjectclass.data") }}', type: 'GET' },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'subject_info', orderable: false },
            { data: 'class_info', orderable: false },
            { data: 'term_info', orderable: false },
            { data: 'session_info', orderable: false },
            { data: 'min_grade_info', orderable: false },
            { data: 'pass_avg_info', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search: '', searchPlaceholder: 'Search compulsory subjects…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ records',
            infoEmpty: 'No records found', zeroRecords: 'No matching records',
            emptyTable: 'No compulsory subjects yet'
        },
        pageLength: 15, responsive: true,
        drawCallback: function() { bindCB(); $('#totalBadge').text(this.api().page.info().recordsTotal); }
    });

    function loadStats() {
        $.get('{{ route("compulsorysubjectclass.stats") }}', function(d) {
            if (d.stats) {
                $('#statTotal').text(d.stats.total);
                $('#statClasses').text(d.stats.total_classes);
                $('#statSessions').text(d.stats.total_sessions);
                $('#statWithRules').text(d.stats.classes_with_rules);
            }
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
    }

    function loadSubjectsForCreate() {
        var classId   = $('#create-classid').val();
        var termId    = $('#create-termid').val();
        var sessionId = $('#create-sessionid').val();

        if (!classId) {
            $('#create-subjects-wrap').html('<div class="text-muted small p-2">Select a class first.</div>');
            $('#create-save-btn').prop('disabled', true);
            return;
        }

        $('#create-subjects-wrap').html('<div class="text-muted small p-2"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>');

        $.get('{{ route("compulsorysubjectclass.subjectsByClass") }}', {
            classid: classId, termid: termId, sessionid: sessionId
        }, function(res) {
            if (!res.success) {
                $('#create-subjects-wrap').html('<div class="text-danger small p-2">' + (res.message || 'Failed to load subjects.') + '</div>');
                $('#create-save-btn').prop('disabled', true);
                return;
            }
            if (!res.subjects || res.subjects.length === 0) {
                $('#create-subjects-wrap').html('<div class="text-muted small p-2">No subjects found for this class/term/session.</div>');
                $('#create-save-btn').prop('disabled', true);
                return;
            }
            var html = '';
            res.subjects.forEach(function(s) {
                var disabled = s.assigned ? 'disabled' : '';
                var checked  = s.assigned ? 'checked' : '';
                var badge    = s.assigned ? ' <small class="text-success">(already assigned)</small>' : '';
                html += '<div class="form-check">'
                     + '<input class="form-check-input cs-subject-cb" type="checkbox" '
                     + 'value="' + s.id + '" id="cs-subj-' + s.id + '" ' + disabled + checked + '>'
                     + '<label class="form-check-label" for="cs-subj-' + s.id + '">'
                     + s.subject + ' <small class="text-muted">(' + (s.subject_code || '') + ')</small>'
                     + badge + '</label></div>';
            });
            $('#create-subjects-wrap').html(html);
            $('#create-save-btn').prop('disabled', false);
        }).fail(function() {
            $('#create-subjects-wrap').html('<div class="text-danger small p-2">Failed to load subjects.</div>');
            $('#create-save-btn').prop('disabled', true);
        });
    }

    $('#create-classid, #create-termid, #create-sessionid').on('change', loadSubjectsForCreate);

    $('#createBtn').on('click', function() {
        $('#create-classid').val('');
        $('#create-termid').val('');
        $('#create-sessionid').val('');
        $('#create-subjects-wrap').html('<div class="text-muted small p-2">Select a class first.</div>');
        $('#create-save-btn').prop('disabled', true);
        $('#create-error-msg').addClass('d-none').html('');
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });

    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        var subjectIds = $('.cs-subject-cb:not(:disabled):checked').map(function() { return this.value; }).get();
        if (!subjectIds.length) { showErr('#create-error-msg', 'Select at least one subject.'); return; }

        btnLoad($('#create-save-btn'), 'Saving…');
        $('#create-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("compulsorysubjectclass.store") }}',
            type: 'POST',
            data: {
                schoolclassid: $('#create-classid').val(),
                termid: $('#create-termid').val(),
                sessionid: $('#create-sessionid').val(),
                'subjectId[]': subjectIds,
                _token: CSRF
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#createModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($('#create-save-btn'));
                    showErr('#create-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#create-save-btn'));
                var j = xhr.responseJSON;
                var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr('#create-error-msg', m);
            }
        });
    });

    $(document).on('click', '.edit-cs-btn', function() {
        var $b = $(this);
        var id = $b.data('id');
        var classId = $b.data('class-id');
        var subjectId = $b.data('subject-id');
        var termId = $b.data('term-id');
        var sessionId = $b.data('session-id');
        var minGrade = $b.data('min-grade');

        $('#edit-id').val(id);
        $('#edit-classid').val(classId);
        $('#edit-termid').val(termId || '');
        $('#edit-sessionid').val(sessionId || '');
        $('#edit-min-grade').val(minGrade || '');

        $('#edit-subjectid').html('<option>Loading…</option>');
        $.get('{{ route("compulsorysubjectclass.subjectsByClass") }}', {
            classid: classId, termid: termId, sessionid: sessionId
        }, function(res) {
            var html = '<option value="">-- Select Subject --</option>';
            if (res.success && res.subjects) {
                res.subjects.forEach(function(s) {
                    html += '<option value="' + s.id + '"' + (s.id == subjectId ? ' selected' : '') + '>'
                         + s.subject + ' (' + (s.subject_code || '') + ')</option>';
                });
            }
            $('#edit-subjectid').html(html);
        });

        $('#edit-error-msg').addClass('d-none').html('');
        btnReset($('#edit-update-btn'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#edit-id').val();
        btnLoad($('#edit-update-btn'), 'Updating…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ url("compulsorysubjectclass") }}/' + id,
            type: 'POST',
            data: {
                _method: 'PUT',
                schoolclassid: $('#edit-classid').val(),
                subjectId: $('#edit-subjectid').val(),
                termid: $('#edit-termid').val(),
                sessionid: $('#edit-sessionid').val(),
                min_grade: $('#edit-min-grade').val(),
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

    $('#passAvgBtn').on('click', function() {
        $('#pavg-classid').val('');
        $('#pavg-value').val('');
        $('#pavg-error-msg').addClass('d-none').html('');
        btnReset($('#pavg-save-btn'));
        new bootstrap.Modal(document.getElementById('passAvgModal')).show();
    });

    $('#pavg-classid').on('change', function() {
        var classId = $(this).val();
        if (!classId) { $('#pavg-value').val(''); return; }
        $.get('{{ route("compulsorysubjectclass.subjectsByClass") }}', { classid: classId }, function(res) {
            if (res.success && res.pass_average !== null && res.pass_average !== undefined) {
                $('#pavg-value').val(res.pass_average);
            } else {
                $('#pavg-value').val('');
            }
        });
    });

    $('#passAvgForm').on('submit', function(e) {
        e.preventDefault();
        btnLoad($('#pavg-save-btn'), 'Saving…');
        $('#pavg-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("compulsorysubjectclass.updatePassAverage") }}',
            type: 'POST',
            data: {
                schoolclassid: $('#pavg-classid').val(),
                promotion_pass_average: $('#pavg-value').val(),
                _token: CSRF
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                btnReset($('#pavg-save-btn'));
                if (res.success) {
                    $('#passAvgModal').modal('hide');
                    toast('success', 'Saved!', res.message);
                    table.ajax.reload();
                } else {
                    showErr('#pavg-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#pavg-save-btn'));
                var j = xhr.responseJSON;
                showErr('#pavg-error-msg', (j && j.message) || 'An error occurred.');
            }
        });
    });

    $(document).on('click', '.delete-cs-btn', function() {
        deleteId = $(this).data('id');
        var name = $(this).data('name') || 'this record';
        var cls = $(this).data('class') || '';
        $('#delete-item-title').text(name + (cls ? ' — ' + cls : ''));
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $b = $(this); btnLoad($b, 'Deleting…');
        $.ajax({
            url: '{{ url("compulsorysubjectclass") }}/' + deleteId,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                $('#deleteModal').modal('hide');
                toast(res.success ? 'success' : 'error', res.success ? 'Deleted!' : 'Cannot Delete', res.message);
                if (res.success) { table.ajax.reload(); loadStats(); }
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                toast('error', 'Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed.');
            },
            complete: function() { btnReset($b); deleteId = null; }
        });
    });

    function doBulk() {
        var ids = $('.row-checkbox:checked').map(function() { return this.value; }).get();
        if (!ids.length) { toast('warning', 'No Selection', 'Select at least one record.'); return; }
        Swal.fire({
            title: 'Delete ' + ids.length + ' record(s)?',
            html: 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel', reverseButtons: true, showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    $.ajax({
                        url: '{{ route("compulsorysubjectclass.bulkDestroy") }}',
                        type: 'POST',
                        data: { ids: ids, _token: CSRF },
                        traditional: true,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) { if (res.success) resolve(res); else reject(res.message); },
                        error: function(xhr) { reject((xhr.responseJSON && xhr.responseJSON.message) || 'Error.'); }
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
