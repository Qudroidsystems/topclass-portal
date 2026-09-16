{{-- resources/views/mysubjectvettings/index.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════════════════════
   DESIGN SYSTEM
   ═══════════════════════════════════════════════════════════════════ */
:root {
    --sv-primary:  #1e3a5f;
    --sv-accent:   #2563eb;
    --sv-success:  #16a34a;
    --sv-warning:  #d97706;
    --sv-danger:   #dc2626;
    --sv-info:     #0891b2;
    --sv-muted:    #6b7280;
    --sv-border:   #e2e8f0;
    --sv-surface:  #f8fafc;
    --sv-white:    #ffffff;
    --sv-radius:   14px;
    --sv-shadow:   0 2px 12px rgba(30,58,95,.08);
    --sv-shadow-lg:0 8px 28px rgba(30,58,95,.14);
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; }

.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ═══════════════════════════════════════════════════════════════════
   HERO
   ═══════════════════════════════════════════════════════════════════ */
.sv-hero {
    background: linear-gradient(135deg, var(--sv-primary) 0%, #2563eb 55%, #4f46e5 100%);
    border-radius: var(--sv-radius);
    padding: 32px 36px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: fadeInDown .55s cubic-bezier(.22,1,.36,1) both;
}
.sv-hero::before {
    content:''; position:absolute; top:-90px; right:-70px;
    width:280px; height:280px;
    background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);
    border-radius:50%;
    animation: floatUp 6s ease-in-out infinite;
}
.sv-hero::after {
    content:''; position:absolute; bottom:-70px; left:-50px;
    width:200px; height:200px;
    background:radial-gradient(circle,rgba(255,255,255,.05) 0%,transparent 70%);
    border-radius:50%;
    animation: floatUp 8s ease-in-out infinite reverse;
}
@keyframes fadeInDown { from { opacity:0; transform:translateY(-22px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInUp   { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }
@keyframes scaleIn    { from { opacity:0; transform:scale(.9); } to { opacity:1; transform:scale(1); } }
@keyframes floatUp    { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-8px); } }
@keyframes rowSlide   { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:translateX(0); } }
@keyframes countUp    { from { opacity:0; transform:scale(.6); } to { opacity:1; transform:scale(1); } }
@keyframes popIn      { 0% { opacity:0; transform:scale(.7) translateY(12px); } 60% { transform:scale(1.04) translateY(-3px); } 100% { opacity:1; transform:scale(1) translateY(0); } }

.sv-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 26px; font-weight: 700; color: #fff;
    margin: 0 0 8px; position: relative; z-index: 1;
}
.sv-hero p { font-size: 13px; color: rgba(255,255,255,.78); margin: 0; position: relative; z-index: 1; }
.sv-hero .meta-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; position: relative; z-index: 1; }
.sv-meta-pill {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 12px; font-weight: 600; color: #fff;
    display: inline-flex; align-items: center; gap: 5px;
    transition: all .3s ease;
    animation: fadeInUp .5s ease both;
}
.sv-meta-pill:hover { background: rgba(255,255,255,.22); transform: translateY(-2px); }

/* ═══════════════════════════════════════════════════════════════════
   STAT CARDS
   ═══════════════════════════════════════════════════════════════════ */
.sv-stat {
    background: var(--sv-white);
    border: 1px solid var(--sv-border);
    border-radius: var(--sv-radius);
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: all .35s cubic-bezier(.22,1,.36,1);
    animation: scaleIn .5s cubic-bezier(.22,1,.36,1) both;
    cursor: default;
}
.sv-stat:hover { transform: translateY(-4px); box-shadow: var(--sv-shadow-lg); }
.sv-stat .stat-accent {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: var(--sv-radius) var(--sv-radius) 0 0;
}
.sv-stat .stat-value {
    font-size: 28px; font-weight: 700; color: var(--sv-primary);
    line-height: 1; margin-top: 8px;
    animation: countUp .6s ease both; animation-delay: .4s;
}
.sv-stat .stat-label {
    font-size: 12px; color: var(--sv-muted);
    margin-top: 6px; font-weight: 500;
}
.sv-stat .stat-ico {
    font-size: 34px; opacity: .1;
    position: absolute; right: 18px; top: 50%;
    transform: translateY(-50%);
    transition: all .3s ease;
}
.sv-stat:hover .stat-ico { opacity: .18; transform: translateY(-50%) scale(1.1) rotate(-5deg); }

/* ═══════════════════════════════════════════════════════════════════
   FILTER BAR
   ═══════════════════════════════════════════════════════════════════ */
.sv-filter-bar {
    background: var(--sv-white);
    border: 1px solid var(--sv-border);
    border-radius: var(--sv-radius);
    padding: 18px 22px;
    margin-bottom: 22px;
    box-shadow: var(--sv-shadow);
    animation: fadeInUp .5s ease .1s both;
}
.sv-filter-label {
    font-size: 10.5px; font-weight: 700;
    color: #475569; text-transform: uppercase;
    letter-spacing: .4px; margin-bottom: 6px; display: block;
}
.sv-filter-input {
    width: 100%;
    height: 42px;
    border: 1.5px solid var(--sv-border);
    border-radius: 10px;
    padding: 8px 14px 8px 40px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    background: var(--sv-surface);
    transition: all .22s ease;
    color: var(--sv-primary);
}
.sv-filter-input:focus {
    outline: none;
    border-color: var(--sv-accent);
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    background: #fff;
}
.sv-filter-wrap { position: relative; }
.sv-filter-wrap i {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: var(--sv-muted); pointer-events: none;
    font-size: 15px;
}
.sv-filter-select {
    width: 100%;
    height: 42px;
    border: 1.5px solid var(--sv-border);
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    background: var(--sv-surface);
    color: var(--sv-primary);
    cursor: pointer;
    transition: all .22s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 13px;
    padding-right: 38px;
}
.sv-filter-select:focus {
    outline: none;
    border-color: var(--sv-accent);
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    background-color: #fff;
}
.sv-btn-filter {
    height: 42px;
    padding: 0 22px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, var(--sv-accent), #4f46e5);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    transition: all .25s cubic-bezier(.22,1,.36,1);
    width: 100%;
}
.sv-btn-filter:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(37,99,235,.4); }
.sv-btn-reset {
    height: 42px;
    padding: 0 18px;
    border-radius: 10px;
    background: #fff;
    border: 1.5px solid var(--sv-border);
    color: var(--sv-muted);
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    transition: all .22s ease;
    text-decoration: none;
    width: 100%;
}
.sv-btn-reset:hover { background: var(--sv-surface); color: var(--sv-primary); border-color: #94a3b8; }

/* ═══════════════════════════════════════════════════════════════════
   TABLE CARD
   ═══════════════════════════════════════════════════════════════════ */
.sv-card {
    background: var(--sv-white);
    border: 1px solid var(--sv-border);
    border-radius: var(--sv-radius);
    box-shadow: var(--sv-shadow);
    overflow: hidden;
    animation: fadeInUp .5s ease .2s both;
}
.sv-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--sv-border);
    background: linear-gradient(to right, #f8fafc, #f0fdf9);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
}
.sv-card-header h5 {
    margin: 0; font-size: 15px; font-weight: 700; color: var(--sv-primary);
    display: flex; align-items: center; gap: 8px;
}

.sv-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
    transition: all .2s ease;
}
.sv-badge-pending   { background: #fef3c7; color: #92400e; }
.sv-badge-completed { background: #dcfce7; color: #15803d; }
.sv-badge-rejected  { background: #fee2e2; color: #991b1b; }

/* ═══════════════════════════════════════════════════════════════════
   TABLE
   ═══════════════════════════════════════════════════════════════════ */
.sv-table { width: 100%; border-collapse: collapse; }
.sv-table thead th {
    background: var(--sv-primary);
    color: #fff;
    padding: 12px 14px;
    font-weight: 600;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: .4px;
    text-align: left;
    white-space: nowrap;
    border: none;
    position: sticky; top: 0; z-index: 2;
}
.sv-table thead th.sortable { cursor: pointer; user-select: none; }
.sv-table thead th.sortable:hover { background: #163152; }
.sv-table tbody tr {
    transition: all .22s ease;
    animation: rowSlide .4s ease both;
    border-bottom: 1px solid var(--sv-border);
}
.sv-table tbody tr:nth-child(1) { animation-delay: .05s; }
.sv-table tbody tr:nth-child(2) { animation-delay: .08s; }
.sv-table tbody tr:nth-child(3) { animation-delay: .11s; }
.sv-table tbody tr:nth-child(4) { animation-delay: .14s; }
.sv-table tbody tr:nth-child(5) { animation-delay: .17s; }
.sv-table tbody tr:nth-child(n+6) { animation-delay: .20s; }
.sv-table tbody tr:hover { background: #f0f6ff !important; box-shadow: inset 3px 0 0 var(--sv-accent); }
.sv-table tbody td {
    padding: 12px 14px;
    vertical-align: middle;
    font-size: 12.5px;
    color: #374151;
    border: none;
}

.sv-subject-name { font-weight: 700; color: var(--sv-primary); }
.sv-subject-code {
    display: inline-block;
    font-family: 'Courier New', monospace;
    font-size: 10.5px;
    color: var(--sv-muted);
    background: #f1f5f9;
    padding: 1px 7px;
    border-radius: 10px;
    margin-left: 6px;
}

.sv-teacher-cell { display: flex; align-items: center; gap: 8px; }
.sv-teacher-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: linear-gradient(135deg, var(--sv-accent), #4f46e5);
    color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 11px;
    flex-shrink: 0;
}

.sv-class-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 14px;
    background: #dbeafe; color: #1e40af;
    font-size: 11.5px; font-weight: 700;
}

.sv-arm-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: 12px;
    background: #ede9fe; color: #5b21b6;
    font-size: 11px; font-weight: 700;
}

.sv-meta-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 14px;
    font-size: 11.5px;
    font-weight: 600;
}
.sv-meta-term    { background: #e0f2fe; color: #0369a1; }
.sv-meta-session { background: #dcfce7; color: #15803d; }

/* ── Actions ── */
.sv-action {
    width: 34px; height: 34px;
    border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1.5px solid var(--sv-border);
    background: #fff;
    color: var(--sv-muted);
    font-size: 15px;
    cursor: pointer;
    text-decoration: none;
    transition: all .22s cubic-bezier(.22,1,.36,1);
}
.sv-action:hover { transform: scale(1.1); }
.sv-action-view:hover    { background: #f0fdf4; border-color: #86efac; color: var(--sv-success); }
.sv-action-edit:hover    { background: #eff6ff; border-color: #93c5fd; color: var(--sv-accent); }

/* ═══════════════════════════════════════════════════════════════════
   EMPTY STATE
   ═══════════════════════════════════════════════════════════════════ */
.sv-empty {
    padding: 70px 24px; text-align: center;
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
}
.sv-empty-icon {
    width: 100px; height: 100px; border-radius: 50%;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 44px; color: var(--sv-accent);
    margin-bottom: 20px;
    animation: floatUp 3s ease-in-out infinite;
}
.sv-empty h5 {
    font-size: 17px; font-weight: 700;
    color: var(--sv-primary); margin-bottom: 8px;
}
.sv-empty p { color: var(--sv-muted); font-size: 13px; margin-bottom: 0; }

/* ═══════════════════════════════════════════════════════════════════
   MODAL
   ═══════════════════════════════════════════════════════════════════ */
.sv-modal .modal-content {
    border-radius: 18px; border: none;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,.25);
    animation: popIn .28s cubic-bezier(.22,1,.36,1);
}
.sv-modal .modal-header {
    background: linear-gradient(135deg, var(--sv-primary), var(--sv-accent));
    color: #fff; padding: 20px 24px;
    border-radius: 18px 18px 0 0; border: none;
}
.sv-modal .modal-title { font-weight: 700; font-size: 15px; }
.sv-modal .modal-body { padding: 24px; }
.sv-modal .form-label {
    font-size: 11.5px; font-weight: 700;
    color: #475569; text-transform: uppercase; letter-spacing: .4px;
}
.sv-modal .form-select, .sv-modal .form-control {
    border: 1.5px solid var(--sv-border); border-radius: 10px;
    padding: 9px 14px; font-size: 13px;
    background: var(--sv-surface);
    transition: all .22s ease;
    font-family: 'DM Sans', sans-serif;
}
.sv-modal .form-select:focus, .sv-modal .form-control:focus {
    border-color: var(--sv-accent);
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    background: #fff;
}
.sv-modal .modal-footer {
    border-top: 1px solid var(--sv-border);
    padding: 16px 24px; background: #f8fafc;
    border-radius: 0 0 18px 18px;
}
.sv-btn-primary {
    background: linear-gradient(135deg, var(--sv-accent), #4f46e5);
    border: none; color: #fff;
    padding: 9px 22px; border-radius: 10px;
    font-weight: 700; font-size: 13px;
    transition: all .25s cubic-bezier(.22,1,.36,1);
}
.sv-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(37,99,235,.4); color:#fff; }
.sv-btn-secondary {
    background: #fff; border: 1.5px solid var(--sv-border);
    color: var(--sv-muted); padding: 9px 22px; border-radius: 10px;
    font-weight: 600; font-size: 13px;
    transition: all .22s ease;
}
.sv-btn-secondary:hover { background: #f1f5f9; color: var(--sv-primary); }

/* ═══════════════════════════════════════════════════════════════════
   ALERTS
   ═══════════════════════════════════════════════════════════════════ */
.sv-alert {
    padding: 14px 18px; border-radius: 10px;
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 13px; margin-bottom: 20px;
    animation: fadeInUp .35s ease;
}
.sv-alert-danger  { background: #fef2f2; border-left: 4px solid var(--sv-danger); color: #991b1b; }
.sv-alert-success { background: #f0fdf4; border-left: 4px solid var(--sv-success); color: #15803d; }

/* ═══════════════════════════════════════════════════════════════════
   MOBILE
   ═══════════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
    .sv-hero { padding: 22px 22px; }
    .sv-hero h1 { font-size: 20px; }
    .sv-stat { padding: 16px 18px; }
    .sv-stat .stat-value { font-size: 22px; }
    .sv-table thead th { padding: 10px 8px; font-size: 10px; }
    .sv-table tbody td { padding: 10px 8px; font-size: 11.5px; }
    .sv-table { min-width: 720px; }
    .sv-card-body-wrap { overflow-x: auto; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- ══ ALERTS ══ --}}
    @if ($errors->any())
        <div class="sv-alert sv-alert-danger">
            <i class="ri-error-warning-fill fs-5 flex-shrink-0"></i>
            <div>
                <strong>There were some problems with your input:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif
    @if (session('success'))
        <div class="sv-alert sv-alert-success">
            <i class="ri-checkbox-circle-fill fs-5 flex-shrink-0"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('danger'))
        <div class="sv-alert sv-alert-danger">
            <i class="ri-error-warning-fill fs-5 flex-shrink-0"></i>
            <div>{{ session('danger') }}</div>
        </div>
    @endif

    {{-- ══ HERO ══ --}}
    <div class="sv-hero">
        <h1><i class="ri-shield-check-line me-2"></i>My Subject Vetting Assignments</h1>
        <p>Review, vet, and manage scoresheets assigned to you across classes, terms, and sessions.</p>
        <div class="meta-pills">
            <span class="sv-meta-pill"><i class="ri-list-check-2"></i>{{ $subjectvettings->count() }} Assignments</span>
            <span class="sv-meta-pill"><i class="ri-time-line"></i>{{ $statusCounts['pending'] ?? 0 }} Pending</span>
            <span class="sv-meta-pill"><i class="ri-check-double-line"></i>{{ $statusCounts['completed'] ?? 0 }} Completed</span>
        </div>
    </div>

    {{-- ══ STAT CARDS ══ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="sv-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--sv-primary),var(--sv-accent));"></div>
                <div class="stat-ico"><i class="ri-list-check-2"></i></div>
                <div class="stat-value" id="statTotal">{{ $subjectvettings->count() }}</div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="sv-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--sv-warning),#fcd34d);"></div>
                <div class="stat-ico"><i class="ri-time-line"></i></div>
                <div class="stat-value" style="color:var(--sv-warning);" id="statPending">{{ $statusCounts['pending'] ?? 0 }}</div>
                <div class="stat-label">Pending Vetting</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="sv-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--sv-success),#4ade80);"></div>
                <div class="stat-ico"><i class="ri-check-double-line"></i></div>
                <div class="stat-value" style="color:var(--sv-success);" id="statCompleted">{{ $statusCounts['completed'] ?? 0 }}</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="sv-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--sv-danger),#f87171);"></div>
                <div class="stat-ico"><i class="ri-close-circle-line"></i></div>
                <div class="stat-value" style="color:var(--sv-danger);" id="statRejected">{{ $statusCounts['rejected'] ?? 0 }}</div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
    </div>

    {{-- ══ FILTER BAR ══ --}}
    <div class="sv-filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="sv-filter-label"><i class="ri-search-line me-1"></i>Search</label>
                <div class="sv-filter-wrap">
                    <i class="ri-search-line"></i>
                    <input type="text" class="sv-filter-input" id="searchInput" placeholder="Search subject, teacher, class…">
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="sv-filter-label"><i class="ri-bookmark-line me-1"></i>Term</label>
                <select class="sv-filter-select" id="idTerm">
                    <option value="all">All Terms</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->term }}">{{ $term->term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="sv-filter-label"><i class="ri-calendar-line me-1"></i>Session</label>
                <select class="sv-filter-select" id="idSession">
                    <option value="all">All Sessions</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->session }}">{{ $session->session }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="sv-filter-label" style="visibility:hidden;">Action</label>
                <a href="{{ route('mysubjectvettings.index') }}" class="sv-btn-reset" title="Reset filters">
                    <i class="ri-refresh-line"></i>Reset
                </a>
            </div>
        </div>
    </div>

    {{-- ══ TABLE CARD ══ --}}
    <div class="sv-card">
        <div class="sv-card-header">
            <h5>
                <i class="ri-file-list-3-line" style="color:var(--sv-accent);"></i>
                Vetting Assignments
                <span class="sv-badge sv-badge-completed" id="visibleCount">{{ $subjectvettings->count() }}</span>
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="sv-badge sv-badge-pending"><i class="ri-time-line me-1"></i>{{ $statusCounts['pending'] ?? 0 }} pending</span>
                <span class="sv-badge sv-badge-completed"><i class="ri-check-line me-1"></i>{{ $statusCounts['completed'] ?? 0 }} done</span>
                @if(($statusCounts['rejected'] ?? 0) > 0)
                    <span class="sv-badge sv-badge-rejected"><i class="ri-close-line me-1"></i>{{ $statusCounts['rejected'] ?? 0 }} rejected</span>
                @endif
            </div>
        </div>

        @if($subjectvettings->count() > 0)
        <div class="sv-card-body-wrap">
            <table class="sv-table" id="vettingTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="subject">Subject</th>
                        <th class="sortable" data-sort="teacher">Teacher</th>
                        <th class="sortable" data-sort="class">Class</th>
                        <th>Arm</th>
                        <th class="sortable" data-sort="term">Term</th>
                        <th class="sortable" data-sort="session">Session</th>
                        <th class="sortable" data-sort="status">Status</th>
                        <th style="width:110px; text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="vettingTableBody">
                    @foreach ($subjectvettings as $sv)
                        @php
                            $teacherName = $sv->teachername ?? 'N/A';
                            $teacherInitials = strtoupper(implode('', array_map(fn($p) => substr($p,0,1), array_filter(explode(' ', $teacherName)))));
                            $teacherInitials = substr($teacherInitials, 0, 2) ?: '??';

                            $statusClass = match(strtolower($sv->status)) {
                                'completed' => 'sv-badge-completed',
                                'rejected'  => 'sv-badge-rejected',
                                default     => 'sv-badge-pending',
                            };
                            $statusIcon = match(strtolower($sv->status)) {
                                'completed' => 'ri-checkbox-circle-line',
                                'rejected'  => 'ri-close-circle-line',
                                default     => 'ri-time-line',
                            };
                        @endphp
                        <tr data-id="{{ $sv->svid }}"
                            data-subject="{{ strtolower($sv->subjectname . ' ' . $sv->subjectcode) }}"
                            data-teacher="{{ strtolower($teacherName) }}"
                            data-class="{{ strtolower($sv->sclass) }}"
                            data-term="{{ strtolower($sv->termname) }}"
                            data-session="{{ strtolower($sv->sessionname) }}"
                            data-status="{{ strtolower($sv->status) }}">

                            <td>
                                <span class="sv-subject-name">{{ $sv->subjectname }}</span>
                                <span class="sv-subject-code">{{ $sv->subjectcode }}</span>
                            </td>

                            <td>
                                <div class="sv-teacher-cell">
                                    <div class="sv-teacher-avatar">{{ $teacherInitials }}</div>
                                    <span>{{ $teacherName }}</span>
                                </div>
                            </td>

                            <td>
                                <span class="sv-class-badge">
                                    <i class="ri-building-line"></i>{{ $sv->sclass }}
                                </span>
                            </td>

                            <td>
                                @if($sv->schoolarm)
                                    <span class="sv-arm-badge">
                                        <i class="ri-links-line"></i>{{ $sv->schoolarm }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                <span class="sv-meta-badge sv-meta-term">
                                    <i class="ri-bookmark-line"></i>{{ $sv->termname }}
                                </span>
                            </td>

                            <td>
                                <span class="sv-meta-badge sv-meta-session">
                                    <i class="ri-calendar-line"></i>{{ $sv->sessionname }}
                                </span>
                            </td>

                            <td>
                                <span class="sv-badge {{ $statusClass }}">
                                    <i class="{{ $statusIcon }}"></i>{{ ucfirst($sv->status) }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                <div class="d-inline-flex gap-2">
                                    @can('View my-subject-vettings')
                                        <a href="{{ route('mysubjectvettings.classbroadsheet', [$sv->schoolclassid, $sv->subjectclassid, $sv->staffid, $sv->termid, $sv->sessionid]) }}"
                                           class="sv-action sv-action-view"
                                           title="Open Broadsheet for {{ $sv->sclass }} {{ $sv->schoolarm }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    @endcan
                                    @can('Update my-subject-vettings')
                                        <button type="button"
                                                class="sv-action sv-action-edit edit-item-btn"
                                                data-id="{{ $sv->svid }}"
                                                data-status="{{ $sv->status }}"
                                                data-subject="{{ $sv->subjectname }} ({{ $sv->subjectcode }})"
                                                data-teacher="{{ $teacherName }}"
                                                title="Update vetting status">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid var(--sv-border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <span style="font-size:12px; color:var(--sv-muted);">
                Showing <strong id="showingCount">{{ $subjectvettings->count() }}</strong> of
                <strong>{{ $subjectvettings->count() }}</strong> assignments
            </span>
            <div id="noResultsNote" style="display:none; font-size:12px; color:var(--sv-danger);">
                <i class="ri-information-line me-1"></i>No matching assignments
            </div>
        </div>
        @else
        <div class="sv-empty">
            <div class="sv-empty-icon">
                <i class="ri-inbox-archive-line"></i>
            </div>
            <h5>No Vetting Assignments</h5>
            <p>You have not been assigned any subject vetting tasks yet. Please check back later.</p>
        </div>
        @endif
    </div>

</div>
</div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div id="editModal" class="modal fade sv-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ri-edit-line me-2"></i>Update Vetting Status
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="edit-subjectvetting-form" autocomplete="off">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="edit-id-field" name="id">
                    <input type="hidden" id="edit-method" name="_method" value="PUT">

                    <div style="background:#f8fafc; border-radius:10px; padding:12px 16px; margin-bottom:16px;">
                        <div style="font-size:11px; text-transform:uppercase; color:var(--sv-muted); font-weight:700; letter-spacing:.4px;">Assignment</div>
                        <div id="modalSubjectName" style="font-size:14px; font-weight:700; color:var(--sv-primary); margin-top:4px;">—</div>
                        <div id="modalTeacherName" style="font-size:12px; color:var(--sv-muted); margin-top:2px;">—</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-status" class="form-label">Status</label>
                        <select name="status" id="edit-status" class="form-select" required>
                            <option value="pending">⏳ Pending</option>
                            <option value="completed">✅ Completed</option>
                            <option value="rejected">❌ Rejected</option>
                        </select>
                    </div>

                    <div class="alert alert-danger d-none" id="edit-alert-error-msg" style="font-size:12px; border-radius:8px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sv-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="sv-btn-primary" id="update-btn">
                        <i class="ri-save-line me-1"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ═══ Search & Filter ═══
    const searchInput  = document.getElementById('searchInput');
    const termFilter   = document.getElementById('idTerm');
    const sessionFilter= document.getElementById('idSession');
    const tableBody    = document.getElementById('vettingTableBody');
    const showingCount = document.getElementById('showingCount');
    const visibleCount = document.getElementById('visibleCount');
    const noResults    = document.getElementById('noResultsNote');

    function applyFilters() {
        if (!tableBody) return;
        const q        = (searchInput?.value || '').toLowerCase().trim();
        const termVal  = (termFilter?.value  || 'all').toLowerCase();
        const sessVal  = (sessionFilter?.value || 'all').toLowerCase();
        let visible = 0;

        tableBody.querySelectorAll('tr').forEach(row => {
            const subject = row.dataset.subject || '';
            const teacher = row.dataset.teacher || '';
            const cls     = row.dataset.class   || '';
            const term    = row.dataset.term    || '';
            const session = row.dataset.session || '';

            const matchesSearch  = !q || subject.includes(q) || teacher.includes(q) || cls.includes(q);
            const matchesTerm    = termVal === 'all' || term === termVal;
            const matchesSession = sessVal === 'all' || session === sessVal;

            const show = matchesSearch && matchesTerm && matchesSession;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (showingCount) showingCount.textContent = visible;
        if (visibleCount) visibleCount.textContent = visible;
        if (noResults)    noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    searchInput?.addEventListener('input', applyFilters);
    termFilter?.addEventListener('change', applyFilters);
    sessionFilter?.addEventListener('change', applyFilters);

    // ═══ Column Sorting ═══
    document.querySelectorAll('.sv-table th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const key = th.dataset.sort;
            const rows = Array.from(tableBody?.querySelectorAll('tr') || []);
            if (!rows.length) return;

            const currentDir = th.dataset.dir === 'asc' ? 'desc' : 'asc';
            th.dataset.dir = currentDir;

            // Clear other sort directions
            document.querySelectorAll('.sv-table th.sortable').forEach(other => {
                if (other !== th) delete other.dataset.dir;
            });

            // Remove existing sort indicators
            document.querySelectorAll('.sv-table th.sortable i.sort-indicator').forEach(i => i.remove());

            // Add sort indicator
            const icon = document.createElement('i');
            icon.className = `sort-indicator ms-1 ri-arrow-${currentDir === 'asc' ? 'up' : 'down'}-s-line`;
            th.appendChild(icon);

            const keyMap = { subject: 'subject', teacher: 'teacher', class: 'class', term: 'term', session: 'session', status: 'status' };
            const dataKey = keyMap[key] || key;

            rows.sort((a, b) => {
                const aVal = (a.dataset[dataKey] || '').toLowerCase();
                const bVal = (b.dataset[dataKey] || '').toLowerCase();
                if (aVal < bVal) return currentDir === 'asc' ? -1 : 1;
                if (aVal > bVal) return currentDir === 'asc' ? 1 : -1;
                return 0;
            });

            rows.forEach(r => tableBody.appendChild(r));
        });
    });

    // ═══ Edit Modal ═══
    const editModalEl = document.getElementById('editModal');
    const editModal   = editModalEl && typeof bootstrap !== 'undefined'
        ? new bootstrap.Modal(editModalEl) : null;

    let editingId = null;

    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            editingId = this.dataset.id;
            document.getElementById('edit-id-field').value = editingId;
            document.getElementById('edit-status').value = this.dataset.status || 'pending';
            document.getElementById('modalSubjectName').textContent = this.dataset.subject || '—';
            document.getElementById('modalTeacherName').textContent = this.dataset.teacher || '—';
            document.getElementById('edit-alert-error-msg').classList.add('d-none');
            if (editModal) editModal.show();
        });
    });

    // ═══ Submit Edit Form ═══
    const editForm = document.getElementById('edit-subjectvetting-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('update-btn');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line spin me-1"></i>Updating…';

            const formData = new FormData(editForm);
            const id = document.getElementById('edit-id-field').value;

            // Assume the update route uses PUT/PATCH — adjust to your routes
            fetch(`/mysubjectvettings/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        const newStatus = document.getElementById('edit-status').value;
                        const statusCell = row.querySelector('td:nth-child(7) .sv-badge');
                        const icon = newStatus === 'completed' ? 'ri-checkbox-circle-line'
                                   : newStatus === 'rejected'  ? 'ri-close-circle-line'
                                   : 'ri-time-line';
                        const cls = newStatus === 'completed' ? 'sv-badge-completed'
                                  : newStatus === 'rejected'  ? 'sv-badge-rejected'
                                  : 'sv-badge-pending';
                        statusCell.className = 'sv-badge ' + cls;
                        statusCell.innerHTML = `<i class="${icon}"></i>${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}`;
                        row.dataset.status = newStatus.toLowerCase();

                        // update edit button
                        const editBtn = row.querySelector('.edit-item-btn');
                        if (editBtn) editBtn.dataset.status = newStatus;
                    }
                    if (editModal) editModal.hide();
                    showToast('Vetting status updated successfully.', 'success');
                } else {
                    const errEl = document.getElementById('edit-alert-error-msg');
                    errEl.textContent = data.message || 'Update failed.';
                    errEl.classList.remove('d-none');
                }
            })
            .catch(err => {
                const errEl = document.getElementById('edit-alert-error-msg');
                errEl.textContent = 'Network error: ' + err.message;
                errEl.classList.remove('d-none');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            });
        });
    }

    // ═══ Toast ═══
    function showToast(message, type = 'info') {
        const colors = {
            success: { bg: '#ecfdf5', border: '#86efac', text: '#15803d', icon: 'ri-checkbox-circle-fill' },
            danger:  { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', icon: 'ri-error-warning-fill' },
            info:    { bg: '#eff6ff', border: '#93c5fd', text: '#1d4ed8', icon: 'ri-information-fill' },
        };
        const c = colors[type] || colors.info;
        const id = 'sv-toast-' + Date.now();
        document.body.insertAdjacentHTML('beforeend', `
            <div id="${id}" style="
                position: fixed; bottom: 24px; right: 24px; z-index: 99999;
                min-width: 300px; padding: 14px 18px;
                background: ${c.bg}; border: 1.5px solid ${c.border};
                color: ${c.text}; border-radius: 12px;
                box-shadow: 0 8px 28px rgba(0,0,0,.12);
                display: flex; align-items: center; gap: 10px;
                font-size: 13px; font-weight: 600;
                animation: rowSlide .3s ease;
                font-family: 'DM Sans', sans-serif;
            ">
                <i class="${c.icon}" style="font-size: 18px; flex-shrink: 0;"></i>
                <div style="flex: 1;">${message}</div>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: ${c.text}; cursor: pointer; font-size: 16px;">&times;</button>
            </div>
        `);
        setTimeout(() => document.getElementById(id)?.remove(), 4500);
    }
})();
</script>
@endsection