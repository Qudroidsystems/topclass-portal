{{-- resources/views/studentreports/index.blade.php --}}
@extends('layouts.master')

@section('content')

@php
    /**
     * Inline SVG avatar placeholder.
     * Rendered as a data URI so the browser NEVER makes an HTTP request,
     * which sidesteps any 403/404 from the web server for /storage/ files.
     */
    $defaultAvatarSvg = 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">'
        . '<rect width="80" height="80" fill="#e2e8f0"/>'
        . '<circle cx="40" cy="30" r="14" fill="#94a3b8"/>'
        . '<path d="M14 78 Q40 52 66 78 Z" fill="#94a3b8"/>'
        . '</svg>'
    );
@endphp

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══════════════════════════════════════════════════════════
     STYLES — same design token system as Student Mock Report Management
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
@keyframes shimmer    { from{background-position:-200% 0;}to{background-position:200% 0;} }

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

/* ── Stat cards ── */
.r-stat-card {
    background: var(--r-surface);
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 16px;
    padding: 16px 18px;
    display: flex; align-items: center; gap: 14px;
    box-shadow: 0 1px 2px rgba(0,0,0,.03), 0 6px 16px rgba(0,0,0,.03);
    transition: transform .18s ease, box-shadow .18s ease;
    animation: fadeInUp .5s .05s ease both;
}
.r-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,.04), 0 14px 28px rgba(0,0,0,.06);
}
.r-stat-icon-chip {
    width: 46px; height: 46px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.r-stat-body { flex: 1; min-width: 0; }
.r-stat-value { font-size: 24px; font-weight: 800; color: #1d1d1f; line-height: 1.1; }
.r-stat-label {
    font-size: 11px; font-weight: 600; color: #86868b;
    text-transform: uppercase; letter-spacing: .4px; margin-top: 3px;
}

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
.r-btn.primary   { background:linear-gradient(135deg,var(--r-accent),var(--r-indigo)); color:#fff; }
.r-btn.success   { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; }
.r-btn.secondary { background:#fff; color:var(--r-primary); border:1.5px solid var(--r-border); }

/* ── Table card ── */
.r-table-card {
    background: var(--r-surface);
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 8px 24px rgba(0,0,0,.04);
    animation: fadeInUp .5s .15s ease both;
}
.r-table-card .card-header {
    background: #fbfbfd;
    border-bottom: 1px solid rgba(0,0,0,.06);
    padding: 16px 22px;
    display: flex; align-items: center; justify-content: space-between;
}
.r-table {
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Plus Jakarta Sans", sans-serif;
}
.r-table thead th {
    background: #fbfbfd;
    color: #6e6e73;
    padding: 13px 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
    border: none;
    border-bottom: 1px solid rgba(0,0,0,.06);
}
.r-table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    border-bottom: 1px solid rgba(0,0,0,.05);
    font-size: 13.5px;
    color: #1d1d1f;
    transition: background .15s ease;
}
.r-table tbody tr { animation: rowIn .3s ease both; }
.r-table tbody tr:hover td { background: #f5f5f7; }
.r-table tbody tr.table-active td { background: rgba(0,113,227,.07) !important; }
.r-table tbody tr:last-child td { border-bottom: none; }

/* ── Apple-style cell text ── */
.r-adm-chip {
    font-family: "SF Mono", "JetBrains Mono", ui-monospace, monospace;
    font-size: 12px; font-weight: 600; color: #1d1d1f;
    background: #f5f5f7; padding: 4px 9px; border-radius: 6px;
    letter-spacing: .2px;
}
.r-cell-primary   { font-weight: 600; color: #1d1d1f; font-size: 13.5px; }
.r-cell-secondary { color: #3a3a3c; font-size: 13.5px; }
.r-cell-muted     { color: #86868b; font-size: 12px; }
.r-cell-faint     { color: #86868b; font-size: 12px; }
.r-row-check, #checkAll { accent-color: #0071e3; width: 16px; height: 16px; cursor: pointer; }

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
.r-avatar-wrap { position: relative; display: inline-block; cursor: pointer; }
.r-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    object-fit: cover;
    border: none;
    box-shadow: 0 1px 4px rgba(0,0,0,.18);
    background: #f0f0f0;
    transition: transform .2s, box-shadow .2s;
    display: block;
}
.r-avatar:hover { transform: scale(1.12); box-shadow: 0 4px 14px rgba(0,0,0,.22); }
.r-avatar-placeholder {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 700; color: #fff;
    border: none;
    box-shadow: 0 1px 4px rgba(0,0,0,.18);
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
.r-gender-badge.male   { background:#e8f0fe; color:#0071e3; border:none; font-weight:500; }
.r-gender-badge.female { background:#fdebf1; color:#d0287a; border:none; font-weight:500; }

/* ── Class / Arm badge ── */
.r-class-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #e9f8ee; color: #1a7f37;
    border: none;
    padding: 3px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 500;
    white-space: nowrap;
}
.r-arm-badge {
    background: #f1edfc; color: #5e42d6;
    border: none;
    padding: 3px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 500;
}

/* ── Empty state ── */
.r-empty { text-align: center; padding: 52px 24px; color: var(--r-muted); }
.r-empty .r-empty-icon { font-size: 3rem; display: block; margin-bottom: 14px; opacity: .25; }
.r-empty h6 { font-size: 15px; font-weight: 700; color: var(--r-primary); margin-bottom: 6px; }
.r-empty p  { font-size: 13px; }

/* ── Loading spinner ── */
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

/* ── Column selection cards ── */
.col-section-card {
    background: var(--r-surface);
    border: 1px solid var(--r-border);
    border-radius: 10px; overflow: hidden; margin-bottom: 14px;
}
.col-section-header {
    background: var(--r-bg); border-bottom: 1px solid var(--r-border);
    padding: 10px 16px; font-size: 12px; font-weight: 700;
    color: var(--r-primary); text-transform: uppercase; letter-spacing: .5px;
    display: flex; align-items: center; justify-content: space-between;
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

/* ── Grade basis toggle ── */
.grade-basis-option {
    border: 1.5px solid var(--r-border);
    border-radius: 10px;
    padding: 12px 14px;
    cursor: pointer;
    transition: border-color .15s, background .15s;
    height: 100%;
}
.grade-basis-option:hover { border-color: var(--r-accent); }
.grade-basis-option.active { border-color: var(--r-accent); background: #eff6ff; }
.grade-basis-option .form-check-input { margin-top: 3px; accent-color: var(--r-accent); }
.grade-basis-option .gb-title { font-weight: 700; font-size: 13px; color: var(--r-primary); }
.grade-basis-option .gb-desc  { font-size: 11.5px; color: var(--r-muted); margin-top: 2px; }

/* ── Form controls inside modals (kept Bootstrap-driven) ── */
.form-control, .form-select {
    border: 1.5px solid var(--r-border); border-radius: 8px;
    font-size: 13px; padding: 9px 14px; transition: border .15s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--r-accent);
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    outline: none;
}

/* ── Pagination ── */
#pagination-container .pagination { margin: 0; }
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
            <span id="selectionAlertText">No selections made.</span>
            <button type="button" class="btn-close ms-3" onclick="document.getElementById('selectionAlert').style.display='none'"></button>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="row mb-1" style="margin-top:56px; animation:fadeInDown .4s ease both;">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 small">
                    <li class="breadcrumb-item"><a href="#" style="color:var(--r-accent)">Reports</a></li>
                    <li class="breadcrumb-item active text-muted">{{ $pagetitle }}</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Hero --}}
    <div class="r-hero">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="ri-bar-chart-2-line me-2"></i>{{ $pagetitle }}</h1>
                <p>Filter, view and export student academic reports by class, session and term.</p>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="animation:fadeInDown .4s ease both;">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('status') || session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-3" style="animation:fadeInDown .4s ease both;">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') ?? session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="animation:fadeInDown .4s ease both;">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="r-stat-card">
                <div class="r-stat-icon-chip" style="background:#e8f0fe;color:#0071e3;"><i class="ri-group-line"></i></div>
                <div class="r-stat-body">
                    <div class="r-stat-value" id="statTotal">{{ $allstudents ? $allstudents->total() : 0 }}</div>
                    <div class="r-stat-label">Total Students</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="r-stat-card">
                <div class="r-stat-icon-chip" style="background:#eef2ff;color:#4f46e5;"><i class="ri-men-line"></i></div>
                <div class="r-stat-body">
                    <div class="r-stat-value" id="statMale">{{ $maleCount ?? 0 }}</div>
                    <div class="r-stat-label">Male Students</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="r-stat-card">
                <div class="r-stat-icon-chip" style="background:#fdebf1;color:#d0287a;"><i class="ri-women-line"></i></div>
                <div class="r-stat-body">
                    <div class="r-stat-value" id="statFemale">{{ $femaleCount ?? 0 }}</div>
                    <div class="r-stat-label">Female Students</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="r-stat-card">
                <div class="r-stat-icon-chip" style="background:#fff4e5;color:#d97706;"><i class="ri-file-chart-line"></i></div>
                <div class="r-stat-body">
                    <div class="r-stat-value" id="statSelected">0</div>
                    <div class="r-stat-label">Selected for Export</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FILTER CARD ── --}}
    <div class="r-filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-xxl-3 col-sm-6">
                <label class="r-label">Class</label>
                <select class="r-input" id="idclass" name="schoolclassid">
                    <option value="ALL">— Select Class —</option>
                    @foreach ($schoolclasses as $class)
                        <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xxl-3 col-sm-6">
                <label class="r-label">Session</label>
                <select class="r-input" id="idsession" name="sessionid">
                    <option value="ALL">— Select Session —</option>
                    @foreach ($schoolsessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xxl-3 col-sm-6" id="termSelectContainer" style="display:none;">
                <label class="r-label">Term</label>
                <select class="r-input" id="idterm" name="termid">
                    <option value="ALL">— Select Term —</option>
                    <option value="1">First Term</option>
                    <option value="2">Second Term</option>
                    <option value="3">Third Term</option>
                </select>
            </div>
            <div class="col-xxl-3 col-sm-6">
                <label class="r-label">Search</label>
                <div class="r-input-icon-wrap">
                    <i class="bi bi-search r-input-icon"></i>
                    <input type="text" class="r-input" id="searchInput" name="search" placeholder="Search students...">
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6 d-flex gap-2">
                <button type="button" class="r-btn secondary w-50" id="searchBtn" style="display:none;" onclick="filterData()">
                    <i class="bi bi-search"></i> Search
                </button>
                <button type="button" class="r-btn success w-50" id="printAllBtn" style="display:none;" onclick="printAllResults()">
                    <i class="bi bi-printer"></i> Print Selected
                </button>
            </div>
        </div>
    </div>

    {{-- ── STUDENT TABLE CARD ── --}}
    <div class="r-table-card">
        <div class="card-header">
            <div class="fw-bold" style="color:var(--r-primary);font-size:14px;">
                <i class="ri-group-line me-2" style="color:var(--r-accent)"></i>
                Students
                <span id="studentcount" class="badge ms-2"
                      style="background:var(--r-accent);font-size:11px;font-weight:600;">
                    {{ $allstudents ? $allstudents->total() : 0 }}
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table r-table w-100 mb-0" id="studentListTable">
                <thead>
                    <tr>
                        <th width="40">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                        </th>
                        <th>Admission No</th>
                        <th>Picture</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Other Name</th>
                        <th>Gender</th>
                        <th>Class</th>
                        <th>Arm</th>
                        <th>Session</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    @include('studentreports.partials.student_rows', ['defaultAvatarSvg' => $defaultAvatarSvg])
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end p-3" id="pagination-container">
            {{ $allstudents ? $allstudents->links('pagination::bootstrap-5') : '' }}
        </div>
    </div>

</div>
</div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     Image View Modal
     ═══════════════════════════════════════════════════════════════════ --}}
<div id="imageViewModal" class="modal fade r-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="r-modal-hero">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-image-line me-2"></i>Student Image</h5>
            </div>
            <div class="modal-body text-center p-4">
                <img id="enlargedImage" src="" alt="Student Image"
                     class="img-fluid rounded" style="max-height:420px;"
                     onerror="this.onerror=null; this.src='{{ $defaultAvatarSvg }}';">
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     Column Selection Modal
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade r-modal" id="columnSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="r-modal-hero">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-layout-column-line me-2"></i>Select Columns for PDF Report</h5>
                <p>Class, Session, and Term must be selected first.</p>
            </div>
            <div class="modal-body p-4">
                <div id="columnSelectionLoader" class="text-center py-5">
                    <div class="r-loading-spinner mb-3"></div>
                    <p class="text-muted" style="font-size:13px;">Loading column options...</p>
                </div>

                <div id="columnSelectionForm" style="display:none;">
                    <div class="row g-3">
                        {{-- Grade Basis Toggle --}}
                        <div class="col-12">
                            <div class="col-section-card">
                                <div class="col-section-header">
                                    <span><i class="ri-medal-line me-1"></i>Grade Basis</span>
                                </div>
                                <div class="col-section-body">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="grade-basis-option active d-flex gap-2 mb-0" id="gbOptionTotal">
                                                <input class="form-check-input" type="radio" name="gradeBasis"
                                                       id="gradeBasisTotal" value="total" checked>
                                                <span>
                                                    <span class="gb-title d-block">Term Total (current)</span>
                                                    <span class="gb-desc d-block">Grades each subject off the raw score entered for this term.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="grade-basis-option d-flex gap-2 mb-0" id="gbOptionCumAve">
                                                <input class="form-check-input" type="radio" name="gradeBasis"
                                                       id="gradeBasisCumAve" value="cum_ave">
                                                <span>
                                                    <span class="gb-title d-block">Cumulative Average</span>
                                                    <span class="gb-desc d-block">Grades each subject off Cum Ave (cumulative sum ÷ term number) instead.</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="col-section-card">
                                <div class="col-section-header">
                                    <span><i class="ri-user-line me-1"></i>Student Information</span>
                                </div>
                                <div class="col-section-body">
                                    <div class="row" id="studentInfoColumns"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="col-section-card">
                                <div class="col-section-header">
                                    <span><i class="ri-pencil-ruler-line me-1"></i>Assessments</span>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="selectAllAssessments">
                                        <label class="form-check-label" for="selectAllAssessments"
                                               style="font-size:12px; text-transform:none; letter-spacing:0; font-weight:500; color:var(--r-muted);">Select All</label>
                                    </div>
                                </div>
                                <div class="col-section-body">
                                    <div class="row" id="assessmentColumns"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="col-section-card">
                                <div class="col-section-header">
                                    <span><i class="ri-bar-chart-line me-1"></i>Scores &amp; Metrics</span>
                                </div>
                                <div class="col-section-body">
                                    <div class="row" id="scoreColumns"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="col-section-card">
                                <div class="col-section-header">
                                    <span><i class="ri-more-line me-1"></i>Other Information</span>
                                </div>
                                <div class="col-section-body">
                                    <div class="row" id="otherColumns"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="r-btn secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="r-btn primary" id="saveColumnSelection" disabled>
                    <i class="ri-file-pdf-line"></i> Apply &amp; Generate PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    console.log("[studentreports] Script loaded at", new Date().toISOString());

    // ══════════════════════════════════════════════════════════════════
    // CSRF helper
    // ══════════════════════════════════════════════════════════════════
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;

        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        if (match) return decodeURIComponent(match[1]);

        return '';
    }

    // ══════════════════════════════════════════════════════════════════
    // Visibility helpers
    // ══════════════════════════════════════════════════════════════════
    function updateSelectionAlert() {
        const classSelect   = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect    = document.getElementById("idterm");
        const checked       = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const alert         = document.getElementById("selectionAlert");
        const alertText     = document.getElementById("selectionAlertText");

        let parts = [];
        if (classSelect.value !== 'ALL')
            parts.push(`Class: ${classSelect.options[classSelect.selectedIndex].text}`);
        if (sessionSelect.value !== 'ALL')
            parts.push(`Session: ${sessionSelect.options[sessionSelect.selectedIndex].text}`);
        if (termSelect && termSelect.value !== 'ALL')
            parts.push(`Term: ${termSelect.options[termSelect.selectedIndex].text}`);
        parts.push(`Students Selected: ${checked.length}`);

        document.getElementById('statSelected').textContent = checked.length;

        if (classSelect.value !== 'ALL' && sessionSelect.value !== 'ALL') {
            alert.style.display = 'block';
            alertText.innerText = parts.join(' | ');
        } else {
            alert.style.display = 'none';
            alertText.innerText = 'No selections made.';
        }
    }

    function updateSearchButtonVisibility() {
        const classValue   = document.getElementById("idclass").value;
        const sessionValue = document.getElementById("idsession").value;
        document.getElementById("searchBtn").style.display =
            (classValue !== 'ALL' && sessionValue !== 'ALL') ? 'block' : 'none';
        updateSelectionAlert();
    }

    function updateTermSelectVisibility() {
        const studentCount = parseInt(document.getElementById("studentcount").innerText) || 0;
        document.getElementById("termSelectContainer").style.display =
            studentCount > 0 ? 'block' : 'none';
        updateSelectionAlert();
    }

    function updatePrintButtonVisibility() {
        const termSelect = document.getElementById("idterm");
        const termValue  = termSelect ? termSelect.value : 'ALL';
        const checked    = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const show       = termValue !== 'ALL' && checked.length > 0;
        document.getElementById("printAllBtn").style.display = show ? 'block' : 'none';
        updateSelectionAlert();
    }

    // ══════════════════════════════════════════════════════════════════
    // filterData — with guard
    // ══════════════════════════════════════════════════════════════════
    function filterData() {
        if (typeof axios === 'undefined') {
            Swal.fire({ icon: "error", title: "Configuration Error", text: "Axios library is missing." });
            return;
        }

        const classSelect   = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect    = document.getElementById("idterm");

        const classValue   = classSelect ? classSelect.value : 'ALL';
        const sessionValue = sessionSelect ? sessionSelect.value : 'ALL';
        const termValue    = termSelect ? termSelect.value : 'ALL';
        const searchValue  = (document.getElementById("searchInput").value || '').trim();

        if (!classValue || classValue === 'ALL' || !sessionValue || sessionValue === 'ALL') {
            console.warn("[filterData] ABORTED — class or session not selected", {
                classValue, sessionValue, termValue, searchValue
            });
            return;
        }

        console.log("[filterData] Firing AJAX with params", {
            search: searchValue,
            schoolclassid: classValue,
            sessionid: sessionValue,
            termid: termValue,
        });

        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML = `<tr><td colspan="10">
            <div class="r-empty">
                <div class="r-loading-spinner mb-3"></div>
                <p class="text-muted">Loading students…</p>
            </div>
        </td></tr>`;

        axios.get('{{ route("studentreports.index") }}', {
            params: {
                search:        searchValue,
                schoolclassid: classValue,
                sessionid:     sessionValue,
                termid:        termValue,
            },
            headers: {
                'X-CSRF-TOKEN':     getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            withCredentials: true,
        }).then(function (response) {
            document.getElementById('studentTableBody').innerHTML =
                response.data.tableBody ||
                `<tr><td colspan="10"><div class="r-empty">
                    <i class="ri-inbox-line r-empty-icon"></i>
                    <h6>No students found</h6>
                    <p>Try adjusting your filters</p>
                </div></td></tr>`;
            document.getElementById('pagination-container').innerHTML =
                response.data.pagination || '';

            const count = response.data.studentCount || '0';
            document.getElementById('studentcount').innerText = count;
            document.getElementById('statTotal').innerText    = count;
            document.getElementById('statMale').innerText     = response.data.maleCount   ?? 0;
            document.getElementById('statFemale').innerText   = response.data.femaleCount ?? 0;

            setupPaginationLinks();
            setupCheckboxListeners();
            updateTermSelectVisibility();
            updatePrintButtonVisibility();

            if (!response.data.tableBody ||
                response.data.tableBody.includes('No students found') ||
                response.data.tableBody.includes('Select class and session')) {
                Swal.fire({ icon: "info", title: "No Results",
                            text: "No students found for the selected filters." });
            }
        }).catch(function (error) {
            console.error("[filterData] AJAX error:", error);
            tableBody.innerHTML = `<tr><td colspan="10"><div class="r-empty">
                <i class="ri-error-warning-line r-empty-icon" style="color:var(--r-danger)"></i>
                <h6 style="color:var(--r-danger)">Error loading data</h6>
                <p>Please try again</p>
            </div></td></tr>`;
            Swal.fire({ icon: "error", title: "Error",
                        text: error.response?.data?.message || "Failed to fetch student data." });
        });
    }

    function resetTable() {
        document.getElementById('studentTableBody').innerHTML = `<tr><td colspan="10">
            <div class="r-empty">
                <i class="ri-filter-line r-empty-icon"></i>
                <h6>Select Class &amp; Session</h6>
                <p>Use the filters above to load students.</p>
            </div>
        </td></tr>`;
        document.getElementById('pagination-container').innerHTML = '';
        document.getElementById('studentcount').innerText = '0';
        document.getElementById('statTotal').innerText    = '0';
        document.getElementById('statMale').innerText     = '0';
        document.getElementById('statFemale').innerText   = '0';
        document.getElementById('printAllBtn').style.display = 'none';
        document.getElementById('termSelectContainer').style.display = 'none';
        updateSelectionAlert();
    }

    // ══════════════════════════════════════════════════════════════════
    // Print / PDF
    // ══════════════════════════════════════════════════════════════════
    function printAllResults() {
        const classValue   = document.getElementById("idclass").value;
        const sessionValue = document.getElementById("idsession").value;
        const termValue    = document.getElementById("idterm").value;
        const checked      = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const selectedIds  = Array.from(checked).map(cb => cb.value);

        if (classValue === 'ALL' || sessionValue === 'ALL' || termValue === 'ALL') {
            Swal.fire({ icon: "warning", title: "Missing Selection",
                        text: "Please select a valid class, session, and term." });
            return;
        }
        if (selectedIds.length === 0) {
            Swal.fire({ icon: "warning", title: "No Students Selected",
                        text: "Please select at least one student to generate the PDF." });
            return;
        }

        const columnModal = new bootstrap.Modal(document.getElementById('columnSelectionModal'));
        columnModal.show();
        loadColumnOptions(classValue, sessionValue, termValue, selectedIds);
    }

    function loadColumnOptions(classId, sessionId, termId, studentIds) {
        const loader  = document.getElementById('columnSelectionLoader');
        const form    = document.getElementById('columnSelectionForm');
        const saveBtn = document.getElementById('saveColumnSelection');

        loader.style.display  = 'block';
        form.style.display    = 'none';
        saveBtn.disabled      = true;

        window.currentPrintParams = { classId, sessionId, termId, studentIds };

        console.log('[loadColumnOptions] Fetching columns', { classId, sessionId, termId });

        fetch('{{ route("studentreports.column-options") }}', {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
            },
            body: JSON.stringify({
                schoolclassid: classId,
                sessionid:     sessionId,
                termid:        termId,
            }),
            credentials: 'same-origin',
        })
        .then(async r => {
            console.log('[loadColumnOptions] Response status:', r.status);
            if (!r.ok) {
                const text = await r.text();
                console.error('[loadColumnOptions] Non-OK response:', text);
                throw new Error('HTTP ' + r.status + ': ' + text.substring(0, 200));
            }
            return r.json();
        })
        .then(data => {
            if (data.success) {
                populateColumnOptions(data.columns);
                loader.style.display = 'none';
                form.style.display   = 'block';
                saveBtn.disabled     = false;
            } else {
                Swal.fire({ icon: "error", title: "Error",
                            text: data.message || "Failed to load column options." });
                bootstrap.Modal.getInstance(
                    document.getElementById('columnSelectionModal')).hide();
            }
        })
        .catch(error => {
            console.error('[loadColumnOptions] Error:', error);
            Swal.fire({ icon: "error", title: "Network Error",
                        text: error.message || "Failed to load column options. Please try again." });
            bootstrap.Modal.getInstance(
                document.getElementById('columnSelectionModal')).hide();
        });
    }

    function populateColumnOptions(columns) {
        ['studentInfoColumns','assessmentColumns','scoreColumns',
         'otherColumns'].forEach(id => {
            document.getElementById(id).innerHTML = '';
        });

        function renderCheckboxes(containerId, data, extraClass) {
            if (!data) return;
            const container = document.getElementById(containerId);
            Object.entries(data).forEach(([key, config]) => {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-md-4 col-sm-6 mb-2';
                const subText = config.has_sub_assessments
                    ? '<small class="text-muted d-block">Has sub-assessments</small>' : '';
                colDiv.innerHTML = `
                    <label class="col-check-item ${config.default ? 'checked' : ''}" for="col_${key}">
                        <input class="column-checkbox ${extraClass || ''}"
                               type="checkbox" id="col_${key}" data-column="${key}"
                               ${config.default ? 'checked' : ''}>
                        <span style="font-size:13px;font-weight:500;">
                            ${config.label}${subText}
                        </span>
                    </label>`;
                container.appendChild(colDiv);
            });
        }

        renderCheckboxes('studentInfoColumns', columns.student_info);
        renderCheckboxes('assessmentColumns',  columns.assessments,  'assessment-checkbox');
        renderCheckboxes('scoreColumns',       columns.scores);
        renderCheckboxes('otherColumns',       columns.other);

        document.querySelectorAll('.column-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                this.closest('.col-check-item')?.classList.toggle('checked', this.checked);
            });
        });

        document.getElementById('selectAllAssessments').addEventListener('change', function () {
            document.querySelectorAll('.assessment-checkbox').forEach(cb => {
                cb.checked = this.checked;
                cb.closest('.col-check-item')?.classList.toggle('checked', this.checked);
            });
        });
    }

    // ── Grade basis toggle visuals ──────────────────────────────────────
    function refreshGradeBasisVisuals() {
        const totalChecked = document.getElementById('gradeBasisTotal').checked;
        document.getElementById('gbOptionTotal').classList.toggle('active', totalChecked);
        document.getElementById('gbOptionCumAve').classList.toggle('active', !totalChecked);
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('gradeBasisTotal')?.addEventListener('change', refreshGradeBasisVisuals);
        document.getElementById('gradeBasisCumAve')?.addEventListener('change', refreshGradeBasisVisuals);
    });

    document.getElementById('saveColumnSelection').addEventListener('click', function () {
        const selectedColumns = [];
        document.querySelectorAll('.column-checkbox:checked')
                .forEach(cb => selectedColumns.push(cb.dataset.column));

        if (selectedColumns.length === 0) {
            Swal.fire({ icon: "warning", title: "No Columns Selected",
                        text: "Please select at least one column to include in the PDF." });
            return;
        }

        const params = window.currentPrintParams;
        const gradeBasisEl = document.querySelector('input[name="gradeBasis"]:checked');
        const gradeBasis = gradeBasisEl ? gradeBasisEl.value : 'total';
        bootstrap.Modal.getInstance(document.getElementById('columnSelectionModal')).hide();

        Swal.fire({
            title: 'Generating PDF',
            html: `
                <p><strong>Class:</strong> ${document.getElementById('idclass').options[document.getElementById('idclass').selectedIndex].text}</p>
                <p><strong>Session:</strong> ${document.getElementById('idsession').options[document.getElementById('idsession').selectedIndex].text}</p>
                <p><strong>Term:</strong> ${document.getElementById('idterm').options[document.getElementById('idterm').selectedIndex].text}</p>
                <p><strong>Grade Basis:</strong> ${gradeBasis === 'cum_ave' ? 'Cumulative Average' : 'Term Total'}</p>
                <p><strong>Students Selected:</strong> ${params.studentIds.length}</p>
                <p><strong>Columns Selected:</strong> ${selectedColumns.length}</p>
                <p>Generating PDF… Please wait.</p>`,
            icon: 'info',
            showCancelButton: false,
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("studentreports.exportClassResultsPdf") }}';
        form.target = '_blank';

        const addInput = (name, value) => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = name;
            input.value = value;
            form.appendChild(input);
        };

        addInput('_token',          getCsrfToken());
        addInput('schoolclassid',   params.classId);
        addInput('sessionid',       params.sessionId);
        addInput('termid',          params.termId);
        addInput('response_method', 'inline');
        addInput('grade_basis',     gradeBasis);
        params.studentIds.forEach((id,  i) => addInput(`studentIds[${i}]`,      id));
        selectedColumns.forEach((col,   i) => addInput(`selectedColumns[${i}]`, col));

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(() => Swal.close(), 2000);
    });

    // ══════════════════════════════════════════════════════════════════
    // Pagination
    // ══════════════════════════════════════════════════════════════════
    function setupPaginationLinks() {
        document.querySelectorAll('#pagination-container a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                if (this.href && !this.classList.contains('disabled')) loadPage(this.href);
            });
        });
    }

    function loadPage(url) {
        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML = `<tr><td colspan="10">
            <div class="r-empty">
                <div class="r-loading-spinner mb-2"></div>
                <p class="text-muted">Loading…</p>
            </div>
        </td></tr>`;

        axios.get(url, {
            headers: {
                'X-CSRF-TOKEN':     getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            withCredentials: true,
        }).then(function (response) {
            document.getElementById('studentTableBody').innerHTML =
                response.data.tableBody ||
                `<tr><td colspan="10"><div class="r-empty">
                    <i class="ri-inbox-line r-empty-icon"></i>
                    <h6>No students found</h6>
                </div></td></tr>`;
            document.getElementById('pagination-container').innerHTML =
                response.data.pagination || '';

            const count = response.data.studentCount || '0';
            document.getElementById('studentcount').innerText = count;
            document.getElementById('statTotal').innerText    = count;
            document.getElementById('statMale').innerText     = response.data.maleCount   ?? 0;
            document.getElementById('statFemale').innerText   = response.data.femaleCount ?? 0;

            setupPaginationLinks();
            setupCheckboxListeners();
            updateTermSelectVisibility();
            updatePrintButtonVisibility();
        }).catch(function (error) {
            console.error("[loadPage] Error:", error);
            tableBody.innerHTML = `<tr><td colspan="10"><div class="r-empty">
                <i class="ri-error-warning-line r-empty-icon" style="color:var(--r-danger)"></i>
                <h6 style="color:var(--r-danger)">Error loading data</h6>
                <p>Please try again</p>
            </div></td></tr>`;
            Swal.fire({ icon: "error", title: "Error",
                        text: error.response?.data?.message || "Failed to fetch student data." });
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // Checkboxes
    // ══════════════════════════════════════════════════════════════════
    function setupCheckboxListeners() {
        const checkAll   = document.getElementById("checkAll");
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');

        if (checkAll) {
            const freshCheckAll = checkAll.cloneNode(true);
            checkAll.parentNode.replaceChild(freshCheckAll, checkAll);

            freshCheckAll.addEventListener("change", function () {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                    cb.closest("tr").classList.toggle("table-active", this.checked);
                });
                updatePrintButtonVisibility();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener("change", function () {
                this.closest("tr").classList.toggle("table-active", this.checked);
                const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
                const allCount     = document.querySelectorAll('tbody input[name="chk_child"]').length;
                const ca           = document.getElementById("checkAll");
                if (ca) ca.checked = checkedCount === allCount && allCount > 0;
                updatePrintButtonVisibility();
            });
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // Boot
    // ══════════════════════════════════════════════════════════════════
    document.addEventListener("DOMContentLoaded", function () {
        setupCheckboxListeners();

        const classSelect   = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect    = document.getElementById("idterm");

        classSelect.addEventListener("change", function () {
            console.log("[class change] class =", this.value);
            if (termSelect) termSelect.value = 'ALL';
            updateSearchButtonVisibility();
            resetTable();
        });

        sessionSelect.addEventListener("change", function () {
            console.log("[session change] session =", this.value);
            if (termSelect) termSelect.value = 'ALL';
            updateSearchButtonVisibility();
            resetTable();
        });

        if (termSelect) {
            termSelect.addEventListener("change", function () {
                console.log("[term change] term =", this.value);
                if (this.value !== 'ALL') {
                    filterData();
                } else {
                    document.getElementById("printAllBtn").style.display = 'none';
                    updateSelectionAlert();
                }
            });
        }

        const imageModal = document.getElementById('imageViewModal');
        if (imageModal) {
            imageModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                const src = btn.getAttribute('data-image');
                document.getElementById('enlargedImage').src = src || '{{ $defaultAvatarSvg }}';
            });
        }
    });
</script>