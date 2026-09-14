{{-- resources/views/schoolbilltermsession/index.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style>
:root {
    --ts-primary: #1e3a5f;
    --ts-accent:  #2563eb;
    --ts-success: #16a34a;
    --ts-warning: #d97706;
    --ts-danger:  #dc2626;
    --ts-muted:   #6b7280;
    --ts-border:  #e2e8f0;
    --ts-bg:      #f8fafc;
    --ts-radius:  12px;
    --ts-shadow:  0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ──────────────────────────────────────────────── */
.ts-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #0f766e 60%, #0891b2 100%);
    border-radius: var(--ts-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.ts-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.ts-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.ts-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.ts-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--ts-border);
    border-radius:var(--ts-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--ts-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--ts-primary); }
.stat-card .stat-label { font-size:12px; color:var(--ts-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ─────────────────────────────────────────────── */
.ts-table th {
    background:var(--ts-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.ts-table td {
    padding:12px 16px; vertical-align:middle;
    border-bottom:1px solid var(--ts-border); font-size:13px;
}
.ts-table tr:hover td { background:#f0fdfa; }

/* ── Badges ────────────────────────────────────────────── */
.ts-badge {
    display:inline-flex; align-items:center;
    padding:3px 9px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.ts-badge-term    { background:#dbeafe; color:#2563eb; }
.ts-badge-session { background:#ccfbf1; color:#0f766e; }

/* ── DataTables overrides ──────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--ts-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--ts-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--ts-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--ts-muted); }
.dataTables_wrapper .paginate_button {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--ts-accent) !important;
    border-color:var(--ts-accent) !important; color:#fff !important;
}

/* ── Modal ─────────────────────────────────────────────── */
#tsModal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, #1e3a5f 0%, #0f766e 100%);
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
    border:1.5px solid var(--ts-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--ts-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Checkbox group ────────────────────────────────────── */
.check-group {
    border:1.5px solid var(--ts-border); border-radius:10px;
    padding:14px 16px; background:var(--ts-bg);
    max-height:160px; overflow-y:auto;
}
.check-group .form-check { margin-bottom:6px; }
.check-group .form-check:last-child { margin-bottom:0; }
.check-group .form-check-input:checked {
    background-color:var(--ts-accent);
    border-color:var(--ts-accent);
}
.select-all-bar {
    background:#eff6ff; border:1.5px solid #bfdbfe;
    border-radius:8px; padding:7px 12px; margin-bottom:8px;
    display:flex; align-items:center; gap:8px; font-size:12px;
    font-weight:600; color:var(--ts-accent);
}

/* ── Bulk bar ──────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* Edit mode note ──────────────────────────────────────── */
.edit-info-note {
    background:#eff6ff; border:1px solid #bfdbfe;
    border-radius:8px; padding:10px 14px;
    font-size:12px; color:#2563eb; margin-bottom:16px;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="ts-hero">
        <h1><i class="ri-calendar-check-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Assign school bills to classes, terms, and sessions in one step.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-links-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-file-list-3-line"></i></div>
                <div class="stat-value text-primary" id="statBills">—</div>
                <div class="stat-label">Unique Bills Assigned</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-2-line"></i></div>
                <div class="stat-value text-success" id="statSessions">—</div>
                <div class="stat-label">Active Sessions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-money-dollar-circle-line"></i></div>
                <div class="stat-value text-warning" id="statAmount">—</div>
                <div class="stat-label">Total Assigned Value</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--ts-primary)">
                    <i class="ri-list-check me-2"></i>All Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create school-bill-for-term-session')
                    <button class="btn btn-primary" id="createAssignmentBtn">
                        <i class="ri-add-line me-1"></i>Create Assignment
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
                <table class="table ts-table w-100 mb-0" id="assignmentsTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>School Bill</th>
                            <th>Class</th>
                            <th>Term | Session</th>
                            <th>Created By</th>
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

{{-- CREATE / EDIT MODAL --}}
<div class="modal fade" id="tsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5 id="modalTitle"><i class="ri-links-line me-2"></i>Create Assignment</h5>
            </div>
            <form id="tsForm">
                @csrf
                <input type="hidden" id="assignmentId">
                <div class="modal-body p-4">

                    {{-- Edit mode note (hidden for create) --}}
                    <div class="edit-info-note d-none" id="editNote">
                        <i class="ri-information-line me-1"></i>
                        You are editing a <strong>single</strong> assignment record. To reassign multiple classes or terms, delete and recreate.
                    </div>

                    <div class="mb-4">
                        <label class="form-label">School Bill <span class="text-danger">*</span></label>
                        <select id="bill_id" class="form-select" required>
                            <option value="">— Select School Bill —</option>
                            @foreach($schoolbills as $bill)
                                <option value="{{ $bill->id }}">{{ $bill->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Classes (checkboxes for create, single select for edit) --}}
                    <div class="mb-4" id="classCheckboxGroup">
                        <label class="form-label">
                            Classes <span class="text-danger">*</span>
                            <span class="text-muted fw-normal small ms-1">(select one or more)</span>
                        </label>
                        <div class="select-all-bar">
                            <input type="checkbox" class="form-check-input" id="selectAllClasses">
                            <label for="selectAllClasses" class="mb-0" style="cursor:pointer">Select All Classes</label>
                        </div>
                        <div class="check-group" id="classCheckboxes">
                            @foreach($schoolclasses as $class)
                                <div class="form-check">
                                    <input class="form-check-input class-cb" type="checkbox"
                                           value="{{ $class->id }}" id="cls_{{ $class->id }}">
                                    <label class="form-check-label" for="cls_{{ $class->id }}">
                                        {{ $class->schoolclass }} {{ $class->arm }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Single class select (edit mode only) --}}
                    <div class="mb-4 d-none" id="classSingleGroup">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select id="class_id_single" class="form-select">
                            <option value="">— Select Class —</option>
                            @foreach($schoolclasses as $class)
                                <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Terms (checkboxes for create, single select for edit) --}}
                    <div class="mb-4" id="termCheckboxGroup">
                        <label class="form-label">
                            Terms <span class="text-danger">*</span>
                            <span class="text-muted fw-normal small ms-1">(select one or more)</span>
                        </label>
                        <div class="check-group" id="termCheckboxes">
                            @foreach($terms as $term)
                                <div class="form-check">
                                    <input class="form-check-input term-cb" type="checkbox"
                                           value="{{ $term->id }}" id="trm_{{ $term->id }}">
                                    <label class="form-check-label" for="trm_{{ $term->id }}">
                                        {{ $term->term }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Single term select (edit mode only) --}}
                    <div class="mb-4 d-none" id="termSingleGroup">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select id="termid_id_single" class="form-select">
                            <option value="">— Select Term —</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Session --}}
                    <div class="mb-3">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3" id="sessionRadios">
                            @foreach($schoolsessions as $session)
                                <div class="form-check form-check-outline form-check-primary">
                                    <input class="form-check-input session-rb" type="radio"
                                           name="session_id" value="{{ $session->id }}"
                                           id="ses_{{ $session->id }}">
                                    <label class="form-check-label" for="ses_{{ $session->id }}">
                                        {{ $session->session }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="alert alert-danger d-none" id="formErrors"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="ri-save-line me-1"></i>Save Assignment
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
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemTitle"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="ri-delete-bin-line me-1"></i>Delete
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

    // ── Route helpers ──────────────────────────────────────────────────
    const ROUTES = {
        index:       '{{ route("schoolbilltermsession.index") }}',
        data:        '{{ route("schoolbilltermsession.data") }}',
        stats:       '{{ route("schoolbilltermsession.stats") }}',
        store:       '{{ route("schoolbilltermsession.store") }}',
        update:      function(id) { return '{{ url("schoolbilltermsession") }}/' + id; },
        destroy:     function(id) { return '{{ url("schoolbilltermsession") }}/' + id; },
        bulkDestroy: '{{ route("schoolbilltermsession.bulk-destroy") }}',
    };

    const CSRF = $('meta[name="csrf-token"]').attr('content');
    let table, deleteId = null;

    // ── DataTable ──────────────────────────────────────────────────────
    table = $('#assignmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  ROUTES.data,
            type: 'GET',
            error: function (xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                Swal.fire('Error', 'Failed to load assignments. Please refresh the page.', 'error');
            }
        },
        columns: [
            {
                data: 'id', orderable: false, searchable: false,
                render: function(data) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' + data + '">';
                }
            },
            { data: 'DT_RowIndex',           orderable: false, searchable: false },
            { data: 'formatted_bill',         orderable: false },
            { data: 'formatted_class',        orderable: false },
            { data: 'formatted_term_session', orderable: false },
            { data: 'createdBy',              orderable: false },
            { data: 'formatted_date',         orderable: false },
            { data: 'action',                 orderable: false, searchable: false },
        ],
        language: {
            processing:        '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading...',
            search:            '',
            searchPlaceholder: 'Search assignments...',
            lengthMenu:        'Show _MENU_ entries',
            info:              'Showing _START_&ndash;_END_ of _TOTAL_ assignments',
            infoEmpty:         'No assignments found',
            zeroRecords:       'No matching assignments',
            emptyTable:        'No assignments created yet',
        },
        order:      [[1, 'desc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function () {
            bindCheckboxes();
            const info = this.api().page.info();
            $('#totalBadge').text(info.recordsTotal);
        },
    });

    // ── Load stats ─────────────────────────────────────────────────────
    function loadStats() {
        $.get(ROUTES.stats, function (data) {
            if (data.stats) {
                $('#statTotal'   ).text(data.stats.total);
                $('#statBills'   ).text(data.stats.unique_bills);
                $('#statSessions').text(data.stats.unique_sessions);
                $('#statAmount'  ).text(
                    '&#8358;' + Number(data.stats.total_amount)
                        .toLocaleString('en-NG', { minimumFractionDigits: 0 })
                );
            }
        });
    }
    loadStats();

    // ── Checkboxes ─────────────────────────────────────────────────────
    function bindCheckboxes() {
        $('.row-checkbox').off('change').on('change', updateBulkBar);
    }

    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkBar();
    });

    function updateBulkBar() {
        const count = $('.row-checkbox:checked').length;
        $('#bulkBar').toggleClass('show', count > 0);
        $('#bulkCount').text(count);
        $('#bulkDeleteBtn').toggleClass('d-none', count === 0);
        if (count === 0) $('#selectAll').prop('checked', false);
    }

    // ── "Select All Classes" toggle ───────────────────────────────────
    $('#selectAllClasses').on('change', function () {
        $('.class-cb').prop('checked', this.checked);
    });

    // ── Helpers: switch between create mode (checkboxes) / edit mode (selects)
    function setCreateMode() {
        $('#editNote'          ).addClass('d-none');
        $('#classCheckboxGroup').removeClass('d-none');
        $('#classSingleGroup'  ).addClass('d-none');
        $('#termCheckboxGroup' ).removeClass('d-none');
        $('#termSingleGroup'   ).addClass('d-none');
    }

    function setEditMode() {
        $('#editNote'          ).removeClass('d-none');
        $('#classCheckboxGroup').addClass('d-none');
        $('#classSingleGroup'  ).removeClass('d-none');
        $('#termCheckboxGroup' ).addClass('d-none');
        $('#termSingleGroup'   ).removeClass('d-none');
    }

    function resetForm() {
        $('#assignmentId').val('');
        $('#bill_id').val('');
        $('.class-cb').prop('checked', false);
        $('#selectAllClasses').prop('checked', false);
        $('.term-cb').prop('checked', false);
        $('.session-rb').prop('checked', false);
        $('#class_id_single').val('');
        $('#termid_id_single').val('');
        $('#formErrors').addClass('d-none').html('');
    }

    // ── Create ─────────────────────────────────────────────────────────
    $('#createAssignmentBtn').on('click', function () {
        resetForm();
        setCreateMode();
        $('#modalTitle').html('<i class="ri-links-line me-2"></i>Create Assignment');
        $('#saveBtn').html('<i class="ri-save-line me-1"></i>Save Assignment');
        $('#tsModal').modal('show');
    });

    // ── Edit ───────────────────────────────────────────────────────────
    $(document).on('click', '.edit-assignment', function () {
        resetForm();
        setEditMode();

        const id         = $(this).data('id');
        const bill_id    = $(this).data('bill_id');
        const class_id   = $(this).data('class_id');
        const termid_id  = $(this).data('termid_id');
        const session_id = $(this).data('session_id');

        $('#assignmentId').val(id);
        $('#bill_id').val(bill_id);
        $('#class_id_single').val(class_id);
        $('#termid_id_single').val(termid_id);
        $('#ses_' + session_id).prop('checked', true);

        $('#modalTitle').html('<i class="ri-edit-line me-2"></i>Edit Assignment');
        $('#saveBtn').html('<i class="ri-save-line me-1"></i>Update Assignment');
        $('#tsModal').modal('show');
    });

    // ── Save (create + update) ─────────────────────────────────────────
    $('#tsForm').on('submit', function (e) {
        e.preventDefault();

        const id     = $('#assignmentId').val();
        const isEdit = !!id;
        const url    = isEdit ? ROUTES.update(id) : ROUTES.store;

        // Build payload
        const payload = {
            bill_id:    $('#bill_id').val(),
            session_id: $('input[name="session_id"]:checked').val() || '',
            _token:     CSRF,
        };

        if (isEdit) {
            payload._method   = 'PUT';
            payload.class_id  = $('#class_id_single').val();
            payload.termid_id = $('#termid_id_single').val();
        } else {
            payload['class_id[]']  = $('.class-cb:checked').map(function(i, el) { return el.value; }).get();
            payload['termid_id[]'] = $('.term-cb:checked').map(function(i, el) { return el.value; }).get();
        }

        $('#saveBtn').prop('disabled', true)
                     .html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        $('#formErrors').addClass('d-none').html('');

        $.ajax({
            url:         url,
            type:        'POST',
            data:        payload,
            traditional: true,
            success: function(res) {
                if (res.success) {
                    $('#tsModal').modal('hide');
                    table.ajax.reload();
                    loadStats();
                    Swal.fire({
                        icon: 'success', title: 'Saved!', text: res.message,
                        timer: 2500, showConfirmButton: false,
                    });
                } else {
                    showErrors(res.message, res.errors);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const json = xhr.responseJSON;
                    showErrors(json ? json.message : null, json ? json.errors : null);
                } else {
                    Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
                }
            },
            complete: function() {
                $('#saveBtn').prop('disabled', false)
                             .html('<i class="ri-save-line me-1"></i>Save Assignment');
            },
        });
    });

    function showErrors(message, errors) {
        let html = '<ul class="mb-0 ps-3">';
        if (errors) {
            $.each(errors, function (k, v) {
                html += '<li>' + (Array.isArray(v) ? v[0] : v) + '</li>';
            });
        } else {
            html += '<li>' + (message || 'Something went wrong.') + '</li>';
        }
        html += '</ul>';
        $('#formErrors').removeClass('d-none').html(html);
    }

    // ── Single delete ──────────────────────────────────────────────────
    $(document).on('click', '.delete-assignment', function () {
        deleteId = $(this).data('id');
        $('#deleteItemTitle').text('"' + $(this).data('title') + '"');
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function () {
        if (!deleteId) return;
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url:  ROUTES.destroy(deleteId),
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            success: function(res) {
                if (res.success) {
                    $('#deleteModal').modal('hide');
                    table.ajax.reload();
                    loadStats();
                    Swal.fire({
                        icon: 'success', title: 'Deleted!', text: res.message,
                        timer: 2000, showConfirmButton: false,
                    });
                } else {
                    Swal.fire('Error!', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to delete assignment.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false)
                   .html('<i class="ri-delete-bin-line me-1"></i>Delete');
                deleteId = null;
            },
        });
    });

    // ── Bulk delete ────────────────────────────────────────────────────
    function doBulkDelete() {
        const ids = $('.row-checkbox:checked').map(function(i, el) { return el.value; }).get();
        if (!ids.length) return;

        Swal.fire({
            title: 'Delete ' + ids.length + ' assignment(s)?',
            text:  'This cannot be undone.',
            icon:  'warning',
            showCancelButton:   true,
            confirmButtonColor: '#dc2626',
            confirmButtonText:  'Yes, delete',
        }).then(function(result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url:         ROUTES.bulkDestroy,
                type:        'POST',
                data:        { ids: ids, _token: CSRF },
                traditional: true,
                success: function(res) {
                    if (res.success) {
                        table.ajax.reload();
                        loadStats();
                        $('#selectAll').prop('checked', false);
                        updateBulkBar();
                        Swal.fire({
                            icon: 'success', title: 'Deleted!', text: res.message,
                            timer: 2000, showConfirmButton: false,
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete assignments.', 'error');
                },
            });
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);
});
</script>
@endsection
