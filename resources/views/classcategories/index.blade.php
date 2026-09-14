{{-- resources/views/classcategories/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --cc-primary:  #1e3a5f;
    --cc-accent:   #2563eb;
    --cc-success:  #16a34a;
    --cc-warning:  #d97706;
    --cc-danger:   #dc2626;
    --cc-muted:    #6b7280;
    --cc-border:   #e2e8f0;
    --cc-bg:       #f8fafc;
    --cc-radius:   12px;
    --cc-shadow:   0 2px 8px rgba(0,0,0,.08);
}

.cc-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--cc-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.cc-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.cc-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.cc-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.cc-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.nav-tabs-custom {
    display: flex; gap: 8px; margin-bottom: 24px;
    border-bottom: 1px solid var(--cc-border);
}
.nav-tabs-custom .nav-link {
    padding: 10px 20px; font-size: 13px; font-weight: 600;
    color: var(--cc-muted); background: transparent;
    border: none; border-radius: 8px 8px 0 0;
    cursor: pointer; transition: all .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
}
.nav-tabs-custom .nav-link:hover { color: var(--cc-accent); background: rgba(37,99,235,.05); }
.nav-tabs-custom .nav-link.active { color: var(--cc-accent); border-bottom: 2px solid var(--cc-accent); }

.stat-card {
    background:#fff; border:1px solid var(--cc-border);
    border-radius:var(--cc-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--cc-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--cc-primary); }
.stat-card .stat-label { font-size:12px; color:var(--cc-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.cc-table th {
    background:var(--cc-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.cc-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--cc-border); font-size:13px;
}
.cc-table tr:hover td { background:#f0f9ff; }

.cc-badge {
    display:inline-flex; align-items:center;
    padding:4px 12px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.cc-badge-senior { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.cc-badge-junior { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }

.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--cc-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--cc-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--cc-muted); }
.dataTables_wrapper .paginate_button  { border-radius:6px !important; font-size:13px !important; padding:4px 10px !important; }
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--cc-accent) !important; border-color:var(--cc-accent) !important; color:#fff !important;
}

.cc-modal .modal-content {
    border:none; border-radius:16px; overflow:hidden;
    box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    padding:22px 28px; position:relative; overflow:hidden;
}
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select {
    border:1.5px solid var(--cc-border); border-radius:8px;
    font-size:13px; padding:9px 14px;
}
.form-control:focus, .form-select:focus {
    border-color:var(--cc-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

#cc-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55); backdrop-filter:blur(3px);
    display:flex; align-items:center; justify-content:center;
    opacity:0; visibility:hidden; transition:opacity .22s, visibility .22s;
}
#cc-page-loader.active { opacity:1; visibility:visible; }
.cc-loader-card {
    background:#fff; border-radius:16px; padding:32px 40px;
    text-align:center; box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px;
}
.cc-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0; border-top-color:var(--cc-accent);
    border-radius:50%; animation:cc-spin .75s linear infinite;
}
@keyframes cc-spin { to { transform:rotate(360deg); } }
.cc-loader-label { font-size:14px; font-weight:600; color:var(--cc-primary); margin-bottom:12px; }
.cc-progress-wrap { width:160px; height:5px; background:#e2e8f0; border-radius:99px; overflow:hidden; margin:0 auto; }
.cc-progress-bar { height:100%; width:0%; background:linear-gradient(90deg, var(--cc-accent), #0d9488); border-radius:99px; transition:width .35s ease; }

#cc-toast-stack {
    position:fixed; bottom:24px; right:24px; z-index:10000;
    display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none;
}
.cc-toast {
    pointer-events:all; background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--cc-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.cc-toast.show { transform:translateX(0); }
.cc-toast.cc-toast-success { border-left-color:var(--cc-success); }
.cc-toast.cc-toast-error   { border-left-color:var(--cc-danger);  }
.cc-toast.cc-toast-warning { border-left-color:var(--cc-warning); }
.cc-toast .cc-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.cc-toast-success .cc-toast-icon { color:var(--cc-success); }
.cc-toast-error   .cc-toast-icon { color:var(--cc-danger);  }
.cc-toast-warning .cc-toast-icon { color:var(--cc-warning); }
.cc-toast .cc-toast-body { flex:1; }
.cc-toast .cc-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.cc-toast .cc-toast-msg   { font-size:12px; color:var(--cc-muted); line-height:1.4; }
.cc-toast .cc-toast-close { background:none; border:none; cursor:pointer; color:var(--cc-muted); font-size:16px; line-height:1; padding:0; flex-shrink:0; }

.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:''; position:absolute; inset:0; margin:auto;
    width:16px; height:16px; border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%; animation:cc-spin .65s linear infinite;
}
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<div id="cc-page-loader">
    <div class="cc-loader-card">
        <div class="cc-loader-spinner"></div>
        <div class="cc-loader-label" id="cc-loader-label">Processing…</div>
        <div class="cc-progress-wrap"><div class="cc-progress-bar" id="cc-progress-bar"></div></div>
    </div>
</div>

<div id="cc-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="cc-hero">
        <h1><i class="ri-bookmark-line me-2"></i>Class Category Management</h1>
        <p>Manage class categories and their CA/Exam score configuration for grading.</p>
    </div>

    <div class="nav-tabs-custom">
        <a href="{{ route('classcategories.index') }}" class="nav-link active">
            <i class="ri-bookmark-line"></i> Class Categories
        </a>
        <a href="{{ route('compulsorysubjectclass.index') }}" class="nav-link">
            <i class="ri-star-line"></i> Compulsory Subjects
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-bookmark-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Categories</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-school-line"></i></div>
                <div class="stat-value text-success" id="statSenior">—</div>
                <div class="stat-label">Senior Categories</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-graduation-cap-line"></i></div>
                <div class="stat-value text-warning" id="statJunior">—</div>
                <div class="stat-label">Junior Categories</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-file-list-line"></i></div>
                <div class="stat-value text-primary" id="statWithAssessment">—</div>
                <div class="stat-label">With CA Config</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--cc-primary)">
                    <i class="ri-list-check me-2"></i>Class Categories List
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create class-category')
                    <button class="btn btn-primary" id="createCategoryBtn">
                        <i class="ri-add-line me-1"></i>Create Category
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> category(ies) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table cc-table w-100 mb-0" id="categoriesTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>CA1 / CA2 / CA3 / Exam</th>
                            <th>Grade Type</th>
                            <th>Total Max</th>
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

{{-- ═══════════════════════ CREATE MODAL ════════════════════ --}}
<div class="modal fade cc-modal" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Create New Class Category</h5>
            </div>
            <form id="createForm" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" id="create-category" name="category" class="form-control"
                               placeholder="e.g., Science, Arts, Commercial" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Grade Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="create_is_senior"
                                       id="create-junior" value="0" checked>
                                <label class="form-check-label" for="create-junior">
                                    <span class="cc-badge cc-badge-junior">Junior (A, B, C, D, F)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="create_is_senior"
                                       id="create-senior" value="1">
                                <label class="form-check-label" for="create-senior">
                                    <span class="cc-badge cc-badge-senior">Senior (A1-F9)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">CA1 Max <span class="text-danger">*</span></label>
                            <input type="number" id="create-ca1score" name="ca1score" class="form-control"
                                   min="0" step="0.01" placeholder="e.g., 20" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CA2 Max <span class="text-danger">*</span></label>
                            <input type="number" id="create-ca2score" name="ca2score" class="form-control"
                                   min="0" step="0.01" placeholder="e.g., 20" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CA3 Max <span class="text-danger">*</span></label>
                            <input type="number" id="create-ca3score" name="ca3score" class="form-control"
                                   min="0" step="0.01" placeholder="e.g., 20" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Exam Max <span class="text-danger">*</span></label>
                            <input type="number" id="create-examscore" name="examscore" class="form-control"
                                   min="0" step="0.01" placeholder="e.g., 60" required>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Total Max Score (auto)</label>
                        <input type="text" id="create-total" class="form-control" readonly value="0">
                    </div>

                    <div class="alert alert-danger d-none mt-3" id="create-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="create-save-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Create Category</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade cc-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Class Category</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit-category" name="category" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Grade Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="edit_is_senior"
                                       id="edit-junior" value="0">
                                <label class="form-check-label" for="edit-junior">
                                    <span class="cc-badge cc-badge-junior">Junior (A, B, C, D, F)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="edit_is_senior"
                                       id="edit-senior" value="1">
                                <label class="form-check-label" for="edit-senior">
                                    <span class="cc-badge cc-badge-senior">Senior (A1-F9)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">CA1 Max <span class="text-danger">*</span></label>
                            <input type="number" id="edit-ca1score" name="ca1score" class="form-control"
                                   min="0" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CA2 Max <span class="text-danger">*</span></label>
                            <input type="number" id="edit-ca2score" name="ca2score" class="form-control"
                                   min="0" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CA3 Max <span class="text-danger">*</span></label>
                            <input type="number" id="edit-ca3score" name="ca3score" class="form-control"
                                   min="0" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Exam Max <span class="text-danger">*</span></label>
                            <input type="number" id="edit-examscore" name="examscore" class="form-control"
                                   min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Total Max Score (auto)</label>
                        <input type="text" id="edit-total" class="form-control" readonly value="0">
                    </div>

                    <div class="alert alert-danger d-none mt-3" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Category</span>
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

    // ── Loader ─────────────────────────────────────────────
    const PageLoader = {
        _prog: 0, _timer: null,
        show(label) {
            $('#cc-loader-label').text(label || 'Processing…');
            $('#cc-progress-bar').css('width', '0%');
            $('#cc-page-loader').addClass('active');
            this._prog = 0;
            this._timer = setInterval(() => {
                if (this._prog < 85) {
                    this._prog += Math.random() * 8;
                    $('#cc-progress-bar').css('width', Math.min(this._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#cc-progress-bar').css('width', '100%');
            setTimeout(() => $('#cc-page-loader').removeClass('active'), 350);
        }
    };

    function toast(type, title, msg, duration) {
        duration = duration || 4000;
        var icons = {
            success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill',
            warning: 'ri-alert-fill', info: 'ri-information-fill'
        };
        var id  = 'cc-toast-' + Date.now();
        var $el = $([
            '<div class="cc-toast cc-toast-' + type + '" id="' + id + '">',
            '  <span class="cc-toast-icon"><i class="' + (icons[type] || icons.info) + '"></i></span>',
            '  <div class="cc-toast-body">',
            '    <div class="cc-toast-title">' + title + '</div>',
            msg ? '    <div class="cc-toast-msg">' + msg + '</div>' : '',
            '  </div>',
            '  <button class="cc-toast-close" onclick="$(\'#' + id + '\').remove()">×</button>',
            '</div>'
        ].join(''));
        $('#cc-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        if (duration > 0) {
            setTimeout(() => { $el.removeClass('show'); setTimeout(() => $el.remove(), 350); }, duration);
        }
    }

    function btnLoad($btn, label) {
        if (!$btn.data('original-html')) $btn.data('original-html', $btn.html());
        $btn.prop('disabled', true).addClass('btn-loading');
        if (label) $btn.html('<span class="btn-text">' + label + '</span>');
    }
    function btnReset($btn) {
        var orig = $btn.data('original-html');
        if (orig) { $btn.html(orig); $btn.removeData('original-html'); }
        $btn.prop('disabled', false).removeClass('btn-loading');
    }
    function showError(sel, msg) {
        $(sel).removeClass('d-none').html('<i class="ri-error-warning-line me-1"></i>' + msg);
    }

    // ── DataTable ──────────────────────────────────────────
    var table = $('#categoriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("classcategories.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load categories. Please refresh.');
            }
        },
        columns: [
            { data: 'id', orderable: false, searchable: false,
              render: d => '<input type="checkbox" class="form-check-input row-checkbox" value="' + d + '">' },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'category_info', orderable: false },
            { data: 'scores_info', orderable: false },
            { data: 'grade_type', orderable: false },
            { data: 'total_max', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search: '', searchPlaceholder: 'Search categories…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ categories',
            infoEmpty: 'No categories found',
            zeroRecords: 'No matching categories',
            emptyTable: 'No class categories created yet',
        },
        order: [[1, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            bindCheckboxes();
            $('#totalBadge').text(this.api().page.info().recordsTotal);
        },
    });

    // ── Stats ──────────────────────────────────────────────
    function loadStats() {
        $.get('{{ route("classcategories.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statSenior').text(data.stats.senior);
                $('#statJunior').text(data.stats.junior);
                $('#statWithAssessment').text(data.stats.with_assessment);
            }
        }).fail(function() {
            $('#statTotal, #statSenior, #statJunior, #statWithAssessment').text('—');
        });
    }
    loadStats();

    // ── Checkboxes ─────────────────────────────────────────
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

    // ── Total max computation ──────────────────────────────
    function computeTotal(prefix) {
        var c1 = parseFloat($('#' + prefix + '-ca1score').val()) || 0;
        var c2 = parseFloat($('#' + prefix + '-ca2score').val()) || 0;
        var c3 = parseFloat($('#' + prefix + '-ca3score').val()) || 0;
        var ex = parseFloat($('#' + prefix + '-examscore').val()) || 0;
        $('#' + prefix + '-total').val(c1 + c2 + c3 + ex);
    }

    function createFormValid() {
        var category = $('#create-category').val().trim();
        var c1 = parseFloat($('#create-ca1score').val());
        var c2 = parseFloat($('#create-ca2score').val());
        var c3 = parseFloat($('#create-ca3score').val());
        var ex = parseFloat($('#create-examscore').val());
        var ok = category !== '' && !isNaN(c1) && !isNaN(c2) && !isNaN(c3) && !isNaN(ex);
        $('#create-save-btn').prop('disabled', !ok);
    }

    $('#create-ca1score, #create-ca2score, #create-ca3score, #create-examscore').on('input', function() {
        computeTotal('create');
        createFormValid();
    });
    $('#create-category').on('input', createFormValid);

    $('#edit-ca1score, #edit-ca2score, #edit-ca3score, #edit-examscore').on('input', function() {
        computeTotal('edit');
    });

    // ── Open CREATE ────────────────────────────────────────
    $('#createCategoryBtn').on('click', function() {
        $('#create-category').val('');
        $('#create-ca1score').val('');
        $('#create-ca2score').val('');
        $('#create-ca3score').val('');
        $('#create-examscore').val('');
        $('#create-total').val('0');
        $('#create-junior').prop('checked', true);
        $('#create-save-btn').prop('disabled', true);
        $('#create-error-msg').addClass('d-none').html('');
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });

    // ── Open EDIT ──────────────────────────────────────────
    $(document).on('click', '.edit-category-btn', function() {
        var $b = $(this);
        $('#edit-id').val($b.data('id'));
        $('#edit-category').val($b.data('category'));
        $('#edit-ca1score').val($b.data('ca1score'));
        $('#edit-ca2score').val($b.data('ca2score'));
        $('#edit-ca3score').val($b.data('ca3score'));
        $('#edit-examscore').val($b.data('examscore'));

        if ($b.data('is_senior') == 1) $('#edit-senior').prop('checked', true);
        else $('#edit-junior').prop('checked', true);

        computeTotal('edit');
        $('#edit-error-msg').addClass('d-none').html('');
        btnReset($('#edit-update-btn'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // ── Submit CREATE ──────────────────────────────────────
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        var payload = {
            category: $('#create-category').val().trim(),
            is_senior: parseInt($('input[name="create_is_senior"]:checked').val()),
            ca1score: parseFloat($('#create-ca1score').val()) || 0,
            ca2score: parseFloat($('#create-ca2score').val()) || 0,
            ca3score: parseFloat($('#create-ca3score').val()) || 0,
            examscore: parseFloat($('#create-examscore').val()) || 0,
        };

        if (!payload.category) { showError('#create-error-msg', 'Category name is required.'); return; }

        var $btn = $('#create-save-btn');
        btnLoad($btn, 'Saving…');
        $('#create-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("classcategories.store") }}',
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(res) {
                btnReset($btn);
                if (res.success) {
                    $('#createModal').modal('hide');
                    toast('success', 'Created!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    showError('#create-error-msg', res.message || 'Could not create category.');
                }
            },
            error: function(xhr) {
                btnReset($btn);
                var json = xhr.responseJSON;
                var msg = (json && json.message) ||
                          (json && json.errors && Object.values(json.errors).flat().join(', ')) ||
                          'An error occurred.';
                showError('#create-error-msg', msg);
                toast('error', 'Failed', msg);
            }
        });
    });

    // ── Submit EDIT ────────────────────────────────────────
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        var payload = {
            id: $('#edit-id').val(),
            category: $('#edit-category').val().trim(),
            is_senior: parseInt($('input[name="edit_is_senior"]:checked').val()),
            ca1score: parseFloat($('#edit-ca1score').val()) || 0,
            ca2score: parseFloat($('#edit-ca2score').val()) || 0,
            ca3score: parseFloat($('#edit-ca3score').val()) || 0,
            examscore: parseFloat($('#edit-examscore').val()) || 0,
        };

        if (!payload.category) { showError('#edit-error-msg', 'Category name is required.'); return; }

        var $btn = $('#edit-update-btn');
        btnLoad($btn, 'Updating…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("classcategories.updateclasscategory") }}',
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(res) {
                btnReset($btn);
                if (res.success) {
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    showError('#edit-error-msg', res.message || 'Could not update category.');
                }
            },
            error: function(xhr) {
                btnReset($btn);
                var json = xhr.responseJSON;
                var msg = (json && json.message) ||
                          (json && json.errors && Object.values(json.errors).flat().join(', ')) ||
                          'An error occurred.';
                showError('#edit-error-msg', msg);
                toast('error', 'Failed', msg);
            }
        });
    });

    // ── Delete single ──────────────────────────────────────
    $(document).on('click', '.delete-category-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-item-title').text($(this).data('name') || 'this category');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: '{{ url("classcategories") }}/' + deleteId,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(res) {
                $('#deleteModal').modal('hide');
                if (res.success) {
                    toast('success', 'Deleted!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    toast('error', 'Cannot Delete', res.message);
                }
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete.';
                toast('error', 'Error', msg);
            },
            complete: function() { btnReset($btn); deleteId = null; }
        });
    });

    // ── Bulk delete ────────────────────────────────────────
    function doBulkDelete() {
        var ids = [];
        $('.row-checkbox:checked').each(function() { ids.push($(this).val()); });
        if (ids.length === 0) { toast('warning', 'No Selection', 'Select at least one category.'); return; }

        Swal.fire({
            title: 'Delete ' + ids.length + ' category(ies)?',
            html: 'This will permanently remove the selected categories.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting categories…');
                    $.ajax({
                        url: '{{ route("classcategories.bulk-destroy") }}',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ ids: ids, _token: CSRF }),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function(res) {
                            PageLoader.hide();
                            if (res.success) resolve(res);
                            else reject(res.message || 'Failed to delete.');
                        },
                        error: function(xhr) {
                            PageLoader.hide();
                            reject((xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred.');
                        }
                    });
                });
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                toast('success', 'Deleted!', result.value.message || 'Categories deleted.');
                table.ajax.reload();
                loadStats();
                $('#selectAll').prop('checked', false);
                updateBulkBar();
            }
        }).catch(function(error) {
            toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete.');
        });
    }
    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection
