@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ═══════════════════════════════════════════════════════════
     STYLES  — same design token system as users/schoolpayment
═══════════════════════════════════════════════════════════ --}}
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap');

:root {
    --r-primary:  #1e3a5f;
    --r-accent:   #2563eb;
    --r-indigo:   #4f46e5;
    --r-success:  #16a34a;
    --r-warning:  #d97706;
    --r-danger:   #dc2626;
    --r-muted:    #6b7280;
    --r-border:   #e2e8f0;
    --r-bg:       #f8fafc;
    --r-surface:  #ffffff;
    --r-radius:   12px;
    --r-shadow:   0 2px 8px rgba(0,0,0,.07);
    --r-shadow-lg:0 8px 32px rgba(0,0,0,.12);
}

*, *::before, *::after { box-sizing: border-box; }
.main-content, .page-content { font-family: 'Plus Jakarta Sans', sans-serif; }

/* ── Keyframes ── */
@keyframes fadeInDown { from{opacity:0;transform:translateY(-14px);}to{opacity:1;transform:translateY(0);} }
@keyframes fadeInUp   { from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);} }
@keyframes scaleIn    { from{opacity:0;transform:scale(.92);}to{opacity:1;transform:scale(1);} }
@keyframes pulse      { 0%,100%{transform:scale(1);}50%{transform:scale(1.05);} }
@keyframes rowIn      { from{opacity:0;transform:translateX(-8px);}to{opacity:1;transform:translateX(0);} }
@keyframes badgePop   { 0%{transform:scale(.5);}70%{transform:scale(1.15);}100%{transform:scale(1);} }
@keyframes spin       { to{transform:rotate(360deg);} }

/* ── Selection banner ── */
#selectionAlert {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1060;
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    color: #fff; border: none; border-radius: 0;
    padding: 10px 20px;
    font-size: 13px; font-weight: 600;
    display: none;
    animation: fadeInDown .3s ease;
    box-shadow: 0 4px 20px rgba(37,99,235,.3);
}
#selectionAlert .btn-close { filter: invert(1); opacity: .8; }

/* ── Hero ── */
.r-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 55%, #4f46e5 100%);
    border-radius: var(--r-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative; overflow: hidden;
    animation: fadeInDown .5s ease both;
}
.r-hero::before {
    content:''; position:absolute; top:-70px; right:-70px;
    width:240px; height:240px;
    background:rgba(255,255,255,.06); border-radius:50%;
    animation: pulse 6s ease-in-out infinite;
}
.r-hero::after {
    content:''; position:absolute; bottom:-50px; right:140px;
    width:150px; height:150px;
    background:rgba(255,255,255,.04); border-radius:50%;
}
.r-hero h1 { font-size:22px; font-weight:800; color:#fff; margin:0 0 6px; position:relative; letter-spacing:-.3px; }
.r-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Filter card ── */
.r-filter-card {
    background: var(--r-surface);
    border: 1px solid var(--r-border);
    border-top: 3px solid var(--r-accent);
    border-radius: var(--r-radius);
    padding: 18px 22px;
    margin-bottom: 20px;
    animation: fadeInUp .5s .1s ease both;
}
.r-label {
    font-size: 11px; font-weight: 700; color: var(--r-muted);
    text-transform: uppercase; letter-spacing: .5px;
    display: block; margin-bottom: 5px;
}
.r-input {
    width: 100%;
    border: 1.5px solid var(--r-border);
    border-radius: 9px;
    padding: 9px 14px;
    font-size: 13px;
    font-family: inherit;
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.r-input:focus { border-color: var(--r-accent); outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.r-input-icon-wrap { position: relative; }
.r-input-icon { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
.r-input-icon-wrap .r-input { padding-left: 34px; }

/* ── Buttons ── */
.r-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 9px;
    font-size: 13px; font-weight: 600; font-family: inherit;
    border: none; cursor: pointer;
    transition: transform .15s, box-shadow .15s, opacity .15s;
    text-decoration: none; white-space: nowrap;
}
.r-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,.15); opacity: .92; }
.r-btn:disabled { opacity: .5; pointer-events: none; }
.r-btn.primary { background:linear-gradient(135deg,var(--r-accent),var(--r-indigo)); color:#fff; }
.r-btn.success { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; }
.r-btn.secondary { background:#fff; color:var(--r-primary); border:1.5px solid var(--r-border); }

/* ── Table card ── */
.r-table-card {
    background: var(--r-surface);
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius);
    overflow: hidden;
    box-shadow: var(--r-shadow);
    animation: fadeInUp .5s .15s ease both;
}
.r-table-card .card-header {
    background: var(--r-surface);
    border-bottom: 1px solid var(--r-border);
    padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between;
}
.r-table thead th {
    background: var(--r-primary);
    color: #fff;
    padding: 11px 14px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
    border: none;
}
.r-table thead th:first-child { border-radius: 0; }
.r-table tbody td {
    padding: 11px 14px;
    vertical-align: middle;
    border-bottom: 1px solid var(--r-border);
    font-size: 13px;
    transition: background .12s;
}
.r-table tbody tr { animation: rowIn .3s ease both; }
.r-table tbody tr:hover td { background: #f0f9ff; }
.r-table tbody tr:last-child td { border-bottom: none; }

/* Stagger */
.r-table tbody tr:nth-child(1)  { animation-delay: .03s; }
.r-table tbody tr:nth-child(2)  { animation-delay: .06s; }
.r-table tbody tr:nth-child(3)  { animation-delay: .09s; }
.r-table tbody tr:nth-child(4)  { animation-delay: .12s; }
.r-table tbody tr:nth-child(5)  { animation-delay: .15s; }
.r-table tbody tr:nth-child(6)  { animation-delay: .18s; }
.r-table tbody tr:nth-child(7)  { animation-delay: .21s; }
.r-table tbody tr:nth-child(8)  { animation-delay: .24s; }
.r-table tbody tr:nth-child(9)  { animation-delay: .27s; }
.r-table tbody tr:nth-child(10) { animation-delay: .30s; }

/* ── Avatar ── */
.r-avatar-wrap {
    position: relative; display: inline-block; cursor: pointer;
}
.r-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    object-fit: cover;
    border: 2.5px solid var(--r-border);
    background: #f0f0f0;
    transition: transform .2s, box-shadow .2s;
    display: block;
}
.r-avatar:hover { transform: scale(1.12); box-shadow: 0 4px 14px rgba(0,0,0,.2); }
.r-avatar-placeholder {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 700; color: #fff;
    border: 2px solid var(--r-border);
    cursor: pointer;
    transition: transform .2s;
    flex-shrink: 0;
}
.r-avatar-placeholder:hover { transform: scale(1.1); }
.r-avatar-zoom-btn {
    position: absolute; bottom: -2px; right: -2px;
    width: 16px; height: 16px;
    background: var(--r-accent); color: #fff;
    border-radius: 50%; font-size: 8px;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity .2s;
}
.r-avatar-wrap:hover .r-avatar-zoom-btn { opacity: 1; }

/* ── Gender badge ── */
.r-gender-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.r-gender-badge.male   { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.r-gender-badge.female { background:#fce7f3; color:#9d174d; border:1px solid #fbcfe8; }

/* ── Class badge ── */
.r-class-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f0fdf4; color: #16a34a;
    border: 1px solid #bbf7d0;
    padding: 3px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
    white-space: nowrap;
}

/* ── Action buttons ── */
.r-action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 7px;
    font-size: 12px; font-weight: 600; font-family: inherit;
    border: none; cursor: pointer;
    text-decoration: none;
    transition: transform .15s, box-shadow .15s;
    background: none;
}
.r-action-btn:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0,0,0,.15); }
.r-action-btn.view  { background:#eff6ff; color:#2563eb; }
.r-action-btn.print { background:#f0fdf4; color:#16a34a; }

/* ── Empty state ── */
.r-empty {
    text-align: center; padding: 52px 24px; color: var(--r-muted);
}
.r-empty .r-empty-icon {
    font-size: 3rem; display: block; margin-bottom: 14px; opacity: .25;
}
.r-empty h6 { font-size: 15px; font-weight: 700; color: var(--r-primary); margin-bottom: 6px; }
.r-empty p  { font-size: 13px; }

/* ── Loading skeleton ── */
.r-skeleton {
    background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 6px; height: 14px;
    display: inline-block; width: 100%;
}
@keyframes shimmer { from{background-position:-200% 0;}to{background-position:200% 0;} }
.r-loading-spinner {
    width: 20px; height: 20px;
    border: 2.5px solid var(--r-border);
    border-top-color: var(--r-accent);
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: inline-block;
}

/* ── Modal ── */
.r-modal .modal-content {
    border: none; border-radius: 18px; overflow: hidden;
    box-shadow: var(--r-shadow-lg);
    animation: scaleIn .25s ease;
}
.r-modal-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    padding: 22px 28px; position: relative; overflow: hidden;
}
.r-modal-hero::before {
    content:''; position:absolute; top:-40px; right:-40px;
    width:140px; height:140px;
    background:rgba(255,255,255,.07); border-radius:50%;
}
.r-modal-hero h5 { color:#fff; font-weight:700; font-size:16px; margin:0; position:relative; }
.r-modal-hero p  { color:rgba(255,255,255,.72); font-size:12px; margin:4px 0 0; position:relative; }
.r-modal-hero .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); opacity:.8; }

/* Column selection cards */
.col-section-card {
    background: var(--r-surface);
    border: 1px solid var(--r-border);
    border-radius: 10px; overflow: hidden; margin-bottom: 14px;
}
.col-section-header {
    background: var(--r-bg); border-bottom: 1px solid var(--r-border);
    padding: 10px 16px; font-size: 12px; font-weight: 700;
    color: var(--r-primary); text-transform: uppercase; letter-spacing: .5px;
}
.col-section-body { padding: 14px 16px; }
.col-check-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px; border-radius: 8px; margin-bottom: 6px;
    border: 1.5px solid var(--r-border); background: #fff;
    cursor: pointer; transition: all .15s;
}
.col-check-item:hover { border-color: var(--r-accent); background: #eff6ff; }
.col-check-item input[type="checkbox"] { accent-color: var(--r-accent); width:15px; height:15px; }
.col-check-item label { margin:0; cursor:pointer; font-size:13px; font-weight:500; }
.col-check-item.checked { border-color: var(--r-accent); background: #eff6ff; }

/* ── Pagination ── */
#pagination-container .pagination {
    margin: 0;
}
#pagination-container .page-link {
    border: 1.5px solid var(--r-border);
    border-radius: 7px !important;
    color: var(--r-primary); font-size: 12px; font-weight: 600;
    padding: 5px 11px; margin: 0 2px;
    transition: all .15s;
}
#pagination-container .page-link:hover,
#pagination-container .page-item.active .page-link {
    background: var(--r-accent); border-color: var(--r-accent); color: #fff;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Fixed selection banner --}}
    <div id="selectionAlert" role="alert">
        <div class="d-flex align-items-center justify-content-between">
            <span id="selectionAlertText"></span>
            <button type="button" class="btn-close ms-3" onclick="document.getElementById('selectionAlert').style.display='none'"></button>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="row mb-1" style="margin-top:56px; animation:fadeInDown .4s ease both;">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 small">
                    <li class="breadcrumb-item"><a href="#" style="color:var(--r-accent)">Reports</a></li>
                    <li class="breadcrumb-item active text-muted">Student Mock Reports</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Hero --}}
    <div class="r-hero">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="ri-file-chart-line me-2"></i>Student Mock Report Management</h1>
                <p>View and print mock exam results by class, session and term.</p>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('status') || session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-3" style="animation:fadeInDown .4s ease both;">
        <i class="bi bi-check-circle me-2"></i>{{ session('status') ?? session('success') }}
    </div>
    @endif

    {{-- ── FILTER CARD ── --}}
    <div class="r-filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label class="r-label">Class</label>
                <select class="r-input" id="idclass" name="schoolclassid">
                    <option value="ALL">— Select Class —</option>
                    @foreach ($schoolclasses as $class)
                        <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="r-label">Session</label>
                <select class="r-input" id="idsession" name="sessionid">
                    <option value="ALL">— Select Session —</option>
                    @foreach ($schoolsessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2" id="termSelectContainer" style="display:none;">
                <label class="r-label">Term</label>
                <select class="r-input" id="idterm" name="termid">
                    <option value="ALL">— Select Term —</option>
                    <option value="1">First Term</option>
                    <option value="2">Second Term</option>
                    <option value="3">Third Term</option>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="r-label">Search</label>
                <div class="r-input-icon-wrap">
                    <i class="bi bi-search r-input-icon"></i>
                    <input type="text" class="r-input" id="searchInput" placeholder="Name, admission no…">
                </div>
            </div>
            <div class="col-sm-12 col-lg-2 d-flex gap-2">
                <button type="button" class="r-btn secondary w-100" id="searchBtn"
                        style="display:none;" onclick="filterData()">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </div>

        {{-- Print button row --}}
        <div class="row mt-3" id="printBtnRow" style="display:none!important;">
            <div class="col-12">
                <button type="button" class="r-btn success" id="printAllBtn" onclick="printAllResults()">
                    <i class="bi bi-printer"></i>
                    Print Selected Results
                    <span id="printCountBadge" class="badge ms-1"
                          style="background:rgba(255,255,255,.3);color:#fff;font-size:11px;">0</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── TABLE CARD ── --}}
    <div class="r-table-card" id="studentList">
        <div class="card-header">
            <div class="fw-bold" style="color:var(--r-primary);font-size:14px;">
                <i class="ri-group-line me-2" style="color:var(--r-accent)"></i>
                Students
                <span id="studentcount" class="badge ms-2"
                      style="background:var(--r-accent);font-size:11px;font-weight:600;">
                    {{ $allstudents ? $allstudents->total() : 0 }}
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small" id="selectedInfo" style="display:none;">
                    <i class="bi bi-check2-square me-1 text-success"></i>
                    <span id="selectedCountText">0</span> selected
                </span>
                <button type="button" class="r-btn secondary"
                        style="padding:5px 12px;font-size:12px;"
                        id="selectAllTopBtn" onclick="selectAllVisible()" style="display:none;">
                    <i class="bi bi-check-all"></i> Select All
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table r-table mb-0" id="studentListTable">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="checkAll"
                                   style="accent-color:#fff;cursor:pointer;width:15px;height:15px;">
                        </th>
                        <th>Adm. No</th>
                        <th>Photo</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Other Name</th>
                        <th>Gender</th>
                        <th>Class</th>
                        <th>Arm</th>
                        <th>Session</th>
                        <th width="130">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    @include('studentmockreports.partials.student_rows')
                </tbody>
            </table>
        </div>

        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top"
             style="background:var(--r-bg);">
            <div class="text-muted small">
                Total: <span class="fw-semibold text-dark" id="totalCountText">
                    {{ $allstudents ? $allstudents->total() : 0 }}
                </span> students
            </div>
            <div id="pagination-container">
                {{ $allstudents ? $allstudents->links('pagination::bootstrap-5') : '' }}
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         IMAGE ZOOM MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="imageViewModal" class="modal fade" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content" style="background:transparent;border:none;box-shadow:none;">
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        style="position:absolute;top:14px;right:14px;z-index:10;
                               background:rgba(0,0,0,.6);border-radius:50%;padding:10px;opacity:1;filter:brightness(0) invert(1);">
                </button>
                <div class="modal-body text-center p-4">
                    <img id="enlargedImage" src="" alt="Student Photo"
                         style="max-width:90%;max-height:70vh;border-radius:16px;
                                box-shadow:0 20px 60px rgba(0,0,0,.4);
                                border:4px solid rgba(255,255,255,.9);
                                animation:scaleIn .3s ease;"
                         onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                    <div id="zoomedStudentName"
                         style="color:#fff;margin-top:16px;font-size:15px;font-weight:700;
                                background:rgba(0,0,0,.5);padding:7px 20px;
                                border-radius:30px;display:inline-block;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         COLUMN SELECTION MODAL
    ══════════════════════════════════════════════════════ --}}
    <div class="modal fade r-modal" id="columnSelectionModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="r-modal-hero">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <h5><i class="bi bi-layout-text-window-reverse me-2"></i>Configure Mock PDF Report</h5>
                    <p>Choose which columns to include in the printed report</p>
                </div>

                <div class="modal-body p-4" style="background:var(--r-bg);max-height:60vh;overflow-y:auto;">
                    <div id="columnSelectionLoader" class="r-empty">
                        <div class="r-loading-spinner mx-auto mb-3" style="width:32px;height:32px;"></div>
                        <p class="text-muted">Loading column options…</p>
                    </div>

                    <div id="columnSelectionForm" style="display:none;">
                        <div class="col-section-card">
                            <div class="col-section-header"><i class="bi bi-person-lines-fill me-2"></i>Student Information</div>
                            <div class="col-section-body"><div class="row g-2" id="studentInfoColumns"></div></div>
                        </div>
                        <div class="col-section-card">
                            <div class="col-section-header"><i class="bi bi-bar-chart-fill me-2"></i>Scores &amp; Metrics</div>
                            <div class="col-section-body"><div class="row g-2" id="scoreColumns"></div></div>
                        </div>
                        <div class="col-section-card">
                            <div class="col-section-header"><i class="bi bi-three-dots me-2"></i>Other</div>
                            <div class="col-section-body"><div class="row g-2" id="otherColumns"></div></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="background:var(--r-surface);border-top:1px solid var(--r-border);">
                    <div class="me-auto small text-muted">
                        <span id="colSelectedCount">0</span> columns selected
                    </div>
                    <button type="button" class="r-btn secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="r-btn primary" id="saveColumnSelection" disabled>
                        <i class="bi bi-printer"></i> Generate PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Expose Laravel route base URLs to JavaScript --}}
<script>
    window._mockRoutes = {
        index:     '{{ route("studentmockreports.index") }}',
        viewBase:  '{{ url("studentmockreports") }}',
        pdfBase:   '{{ url("studentmockreports/export-pdf") }}',
        exportClass: '{{ route("studentmockreports.exportClassMockResultsPdf") }}',
        columnOptions: '{{ route("studentmockreports.column-options") }}',
    };
</script>

<script>
(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // ── Server-side flash messages → SweetAlert ─────────────
    @if (session('error'))
    Swal.fire({
        icon: 'warning',
        title: 'Unable to Open Result',
        text: @json(session('error')),
        confirmButtonColor: '#2563eb'
    });
    @endif

    // ════════════════════════════════════════════════════════
    // PER-ROW ACTION GUARDS
    // ════════════════════════════════════════════════════════

    /**
     * Called by each row's View button.
     * Shows a SweetAlert warning if no term is selected, otherwise navigates.
     */
    window.handleViewResult = function (btn) {
        const trm = document.getElementById('idterm');

        if (!trm || trm.value === 'ALL') {
            Swal.fire({
                icon: 'warning',
                title: 'Select a Term First',
                text: 'Please choose a term from the filter above before viewing a student\'s mock result.',
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'OK, got it'
            });
            // Highlight the term select to draw attention
            if (trm) {
                trm.closest('#termSelectContainer') &&
                    (trm.closest('#termSelectContainer').style.display = '');
                trm.style.borderColor = 'var(--r-warning)';
                trm.style.boxShadow   = '0 0 0 3px rgba(217,119,6,.15)';
                setTimeout(() => {
                    trm.style.borderColor = '';
                    trm.style.boxShadow   = '';
                }, 2500);
            }
            return;
        }

        const stid      = btn.dataset.stid;
        const classid   = btn.dataset.classid;
        const sessionid = btn.dataset.sessionid;
        const termid    = trm.value;

        window.location.href =
            `${window._mockRoutes.viewBase}/${stid}/${classid}/${sessionid}/${termid}`;
    };

    /**
     * Called by each row's PDF button.
     * Shows a SweetAlert warning if no term is selected, otherwise opens the PDF in a new tab.
     */
    window.handlePdfResult = function (btn) {
        const trm = document.getElementById('idterm');

        if (!trm || trm.value === 'ALL') {
            Swal.fire({
                icon: 'warning',
                title: 'Select a Term First',
                text: 'Please choose a term from the filter above before exporting a PDF.',
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'OK, got it'
            });
            // Highlight the term select
            if (trm) {
                trm.closest('#termSelectContainer') &&
                    (trm.closest('#termSelectContainer').style.display = '');
                trm.style.borderColor = 'var(--r-warning)';
                trm.style.boxShadow   = '0 0 0 3px rgba(217,119,6,.15)';
                setTimeout(() => {
                    trm.style.borderColor = '';
                    trm.style.boxShadow   = '';
                }, 2500);
            }
            return;
        }

        const stid      = btn.dataset.stid;
        const classid   = btn.dataset.classid;
        const sessionid = btn.dataset.sessionid;
        const termid    = trm.value;

        window.open(
            `${window._mockRoutes.pdfBase}/${stid}/${classid}/${sessionid}/${termid}`,
            '_blank'
        );
    };

    // ════════════════════════════════════════════════════════
    // SELECTION STATE
    // ════════════════════════════════════════════════════════

    function updateSelectionUI() {
        const cls     = document.getElementById('idclass');
        const ses     = document.getElementById('idsession');
        const trm     = document.getElementById('idterm');
        const checked = document.querySelectorAll('tbody input[name="chk_child"]:checked');

        // Banner
        const banner = document.getElementById('selectionAlert');
        if (cls.value !== 'ALL' && ses.value !== 'ALL') {
            const parts = [];
            parts.push(cls.options[cls.selectedIndex].text);
            parts.push(ses.options[ses.selectedIndex].text);
            if (trm.value !== 'ALL') parts.push(trm.options[trm.selectedIndex].text);
            parts.push(checked.length + ' student(s) selected');
            document.getElementById('selectionAlertText').textContent = parts.join(' · ');
            banner.style.display = 'block';
        } else {
            banner.style.display = 'none';
        }

        // Selected count
        const selectedInfo = document.getElementById('selectedInfo');
        const selectedCountText = document.getElementById('selectedCountText');
        if (checked.length > 0) {
            selectedInfo.style.display = '';
            selectedCountText.textContent = checked.length;
        } else {
            selectedInfo.style.display = 'none';
        }

        // Print button row — only show when a term is chosen AND students are selected
        const printRow   = document.getElementById('printBtnRow');
        const printBadge = document.getElementById('printCountBadge');
        if (trm.value !== 'ALL' && checked.length > 0) {
            printRow.style.cssText = ''; // clear the !important hide
            printBadge.textContent = checked.length;
        } else {
            printRow.style.cssText = 'display:none!important;';
        }
    }

    // ── Checkbox setup ──────────────────────────────────────
    function setupCheckboxListeners() {
        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('tbody input[name="chk_child"]').forEach(cb => {
                    cb.checked = this.checked;
                    cb.closest('tr').classList.toggle('table-active', this.checked);
                });
                updateSelectionUI();
            });
        }
        document.querySelectorAll('tbody input[name="chk_child"]').forEach(cb => {
            cb.addEventListener('change', function () {
                this.closest('tr').classList.toggle('table-active', this.checked);
                const all     = document.querySelectorAll('tbody input[name="chk_child"]');
                const checked = document.querySelectorAll('tbody input[name="chk_child"]:checked');
                if (checkAll) checkAll.checked = all.length > 0 && all.length === checked.length;
                updateSelectionUI();
            });
        });
    }

    window.selectAllVisible = function () {
        document.querySelectorAll('tbody input[name="chk_child"]').forEach(cb => {
            cb.checked = true;
            cb.closest('tr').classList.add('table-active');
        });
        document.getElementById('checkAll').checked = true;
        updateSelectionUI();
    };

    // ════════════════════════════════════════════════════════
    // FILTER / SEARCH
    // ════════════════════════════════════════════════════════

    window.filterData = function () {
        const cls    = document.getElementById('idclass').value;
        const ses    = document.getElementById('idsession').value;
        const trm    = document.getElementById('idterm').value;
        const search = document.getElementById('searchInput').value.trim();

        if (cls === 'ALL' || ses === 'ALL') {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Selection',
                text: 'Please select a class and session.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        const tbody = document.getElementById('studentTableBody');
        tbody.innerHTML = `<tr><td colspan="11">
            <div class="r-empty">
                <div class="r-loading-spinner mx-auto mb-3"></div>
                <p class="text-muted">Loading students…</p>
            </div>
        </td></tr>`;

        axios.get(window._mockRoutes.index, {
            params: { search, schoolclassid: cls, sessionid: ses, termid: trm },
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(res => {
            tbody.innerHTML = res.data.tableBody ||
                `<tr><td colspan="11"><div class="r-empty">
                    <i class="ri-inbox-line r-empty-icon"></i>
                    <h6>No students found</h6>
                    <p>Try adjusting your filters</p>
                </div></td></tr>`;
            document.getElementById('pagination-container').innerHTML = res.data.pagination || '';
            const count = res.data.studentCount || 0;
            document.getElementById('studentcount').textContent = count;
            document.getElementById('totalCountText').textContent = count;
            setupPaginationLinks();
            setupCheckboxListeners();
            setupImageZoom();
            updateTermVisibility();
            updateSelectionUI();
        }).catch(err => {
            tbody.innerHTML = `<tr><td colspan="11"><div class="r-empty">
                <i class="ri-error-warning-line r-empty-icon" style="color:var(--r-danger)"></i>
                <h6 style="color:var(--r-danger)">Failed to load data</h6>
                <p>${err.response?.data?.message || 'Please try again'}</p>
            </div></td></tr>`;
        });
    };

    // ── Term visibility ─────────────────────────────────────
    function updateTermVisibility() {
        const count   = parseInt(document.getElementById('studentcount').textContent) || 0;
        const termCon = document.getElementById('termSelectContainer');
        termCon.style.display = count > 0 ? '' : 'none';
        updateSelectionUI();
    }

    function updateSearchBtnVisibility() {
        const cls = document.getElementById('idclass').value;
        const ses = document.getElementById('idsession').value;
        document.getElementById('searchBtn').style.display =
            (cls !== 'ALL' && ses !== 'ALL') ? '' : 'none';
    }

    // ════════════════════════════════════════════════════════
    // BULK PRINT (class PDF)
    // ════════════════════════════════════════════════════════

    window.printAllResults = function () {
        const cls     = document.getElementById('idclass').value;
        const ses     = document.getElementById('idsession').value;
        const trm     = document.getElementById('idterm').value;
        const checked = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const ids     = Array.from(checked).map(cb => cb.value);

        if (cls === 'ALL' || ses === 'ALL' || trm === 'ALL') {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Selection',
                text: 'Please select class, session, and term.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }
        if (!ids.length) {
            Swal.fire({
                icon: 'warning',
                title: 'No Students Selected',
                text: 'Select at least one student.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        window.currentPrintParams = { classId: cls, sessionId: ses, termId: trm, studentIds: ids };
        loadColumnOptions();
        new bootstrap.Modal(document.getElementById('columnSelectionModal')).show();
    };

    function loadColumnOptions() {
        const p       = window.currentPrintParams;
        const loader  = document.getElementById('columnSelectionLoader');
        const form    = document.getElementById('columnSelectionForm');
        const saveBtn = document.getElementById('saveColumnSelection');
        loader.style.display = ''; form.style.display = 'none'; saveBtn.disabled = true;

        fetch(window._mockRoutes.columnOptions, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                schoolclassid: p.classId,
                sessionid:     p.sessionId,
                termid:        p.termId
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                populateColumnOptions(data.columns);
                loader.style.display = 'none';
                form.style.display   = '';
                saveBtn.disabled     = false;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#2563eb'
                });
            }
        })
        .catch(() => Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Failed to load options.',
            confirmButtonColor: '#2563eb'
        }));
    }

    function populateColumnOptions(columns) {
        const sections = {
            'studentInfoColumns': columns.student_info,
            'scoreColumns':       columns.scores,
            'otherColumns':       columns.other,
        };
        Object.entries(sections).forEach(([id, cols]) => {
            const el = document.getElementById(id);
            el.innerHTML = '';
            if (!cols) return;
            Object.entries(cols).forEach(([key, cfg]) => {
                const d = document.createElement('div');
                d.className = 'col-md-4 col-sm-6';
                d.innerHTML = `
                    <label class="col-check-item ${cfg.default ? 'checked' : ''}" id="wrap_${key}">
                        <input type="checkbox" class="column-checkbox" data-column="${key}" ${cfg.default ? 'checked' : ''}>
                        <span style="font-size:13px;font-weight:500;">${cfg.label}</span>
                    </label>`;
                el.appendChild(d);
            });
        });

        function updateColCount() {
            const n = document.querySelectorAll('.column-checkbox:checked').length;
            document.getElementById('colSelectedCount').textContent = n;
            document.querySelectorAll('.col-check-item').forEach(item => {
                item.classList.toggle('checked', item.querySelector('input').checked);
            });
        }
        document.querySelectorAll('.column-checkbox').forEach(cb =>
            cb.addEventListener('change', updateColCount)
        );
        updateColCount();
    }

    document.getElementById('saveColumnSelection').addEventListener('click', function () {
        const selected = Array.from(
            document.querySelectorAll('.column-checkbox:checked')
        ).map(cb => cb.dataset.column);

        if (!selected.length) {
            Swal.fire({
                icon: 'warning',
                title: 'No Columns Selected',
                text: 'Select at least one column.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        const p = window.currentPrintParams;
        bootstrap.Modal.getInstance(document.getElementById('columnSelectionModal'))?.hide();

        Swal.fire({
            title: 'Generating PDF',
            html: `<p class="text-muted">Processing <strong>${p.studentIds.length}</strong> student(s)…</p>`,
            icon: 'info',
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window._mockRoutes.exportClass;
        form.target = '_blank';

        const add = (name, val) => {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = name; i.value = val;
            form.appendChild(i);
        };
        add('_token', CSRF);
        add('schoolclassid', p.classId);
        add('sessionid',     p.sessionId);
        add('termid',        p.termId);
        p.studentIds.forEach((id, i) => add(`studentIds[${i}]`, id));
        selected.forEach((col, i)    => add(`selectedColumns[${i}]`, col));

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        setTimeout(() => Swal.close(), 2000);
    });

    // ════════════════════════════════════════════════════════
    // IMAGE ZOOM
    // ════════════════════════════════════════════════════════

    function setupImageZoom() {
        document.querySelectorAll('[data-image-zoom]').forEach(el => {
            el.addEventListener('click', function () {
                const src  = this.dataset.imageSrc;
                const name = this.dataset.imageName || '';
                document.getElementById('enlargedImage').src = src;
                document.getElementById('zoomedStudentName').textContent = name;
                new bootstrap.Modal(document.getElementById('imageViewModal')).show();
            });
        });
    }

    // ════════════════════════════════════════════════════════
    // PAGINATION
    // ════════════════════════════════════════════════════════

    function setupPaginationLinks() {
        document.querySelectorAll('#pagination-container a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                if (this.href && !this.classList.contains('disabled')) {
                    loadPage(this.href);
                }
            });
        });
    }

    function loadPage(url) {
        const tbody = document.getElementById('studentTableBody');
        tbody.innerHTML = `<tr><td colspan="11">
            <div class="r-empty">
                <div class="r-loading-spinner mx-auto mb-2"></div>
                <p class="text-muted">Loading…</p>
            </div>
        </td></tr>`;

        axios.get(url, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            tbody.innerHTML = res.data.tableBody ||
                `<tr><td colspan="11">
                    <div class="r-empty">
                        <i class="ri-inbox-line r-empty-icon"></i>
                        <p>No students</p>
                    </div>
                </td></tr>`;
            document.getElementById('pagination-container').innerHTML = res.data.pagination || '';
            document.getElementById('studentcount').textContent   = res.data.studentCount || 0;
            document.getElementById('totalCountText').textContent = res.data.studentCount || 0;
            setupPaginationLinks();
            setupCheckboxListeners();
            setupImageZoom();
            updateTermVisibility();
            updateSelectionUI();
        }).catch(() => {
            tbody.innerHTML = `<tr><td colspan="11">
                <div class="r-empty text-danger"><p>Error loading page</p></div>
            </td></tr>`;
        });
    }

    // ════════════════════════════════════════════════════════
    // DOM READY
    // ════════════════════════════════════════════════════════

    document.addEventListener('DOMContentLoaded', function () {
        setupCheckboxListeners();
        setupPaginationLinks();
        setupImageZoom();

        const cls = document.getElementById('idclass');
        const ses = document.getElementById('idsession');
        const trm = document.getElementById('idterm');

        const resetTable = () => {
            document.getElementById('studentTableBody').innerHTML =
                `<tr><td colspan="11">
                    <div class="r-empty">
                        <i class="ri-filter-line r-empty-icon"></i>
                        <h6>Select Class &amp; Session</h6>
                        <p>Use the filters above to load students</p>
                    </div>
                </td></tr>`;
            document.getElementById('pagination-container').innerHTML = '';
            document.getElementById('studentcount').textContent       = '0';
            document.getElementById('totalCountText').textContent     = '0';
            document.getElementById('termSelectContainer').style.display = 'none';
            document.getElementById('printBtnRow').style.cssText         = 'display:none!important;';
            document.getElementById('selectionAlert').style.display      = 'none';
        };

        cls.addEventListener('change', () => {
            updateSearchBtnVisibility();
            trm.value = 'ALL';
            resetTable();
        });
        ses.addEventListener('change', () => {
            updateSearchBtnVisibility();
            trm.value = 'ALL';
            resetTable();
        });
        trm.addEventListener('change', () => {
            updateSelectionUI();
            if (trm.value !== 'ALL') filterData();
        });

        // Keyboard search trigger
        document.getElementById('searchInput').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') filterData();
        });
    });

})();
</script>

@endsection
