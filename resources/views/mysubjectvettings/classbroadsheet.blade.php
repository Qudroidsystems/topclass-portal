{{-- resources/views/mysubjectvettings/classbroadsheet.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
:root {
    --v-navy:    #0f2342;
    --v-teal:    #0d9488;
    --v-sky:     #0ea5e9;
    --v-amber:   #f59e0b;
    --v-rose:    #f43f5e;
    --v-green:   #22c55e;
    --v-purple:  #7c3aed;
    --v-muted:   #64748b;
    --v-border:  #e2e8f0;
    --v-surface: #f8fafc;
    --v-white:   #ffffff;
    --v-radius:  14px;
    --v-shadow:  0 2px 12px rgba(15,35,66,.08);
    --v-shadow-lg:0 8px 28px rgba(15,35,66,.14);
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; }

.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeInDown { from { opacity:0; transform:translateY(-22px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInUp   { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }
@keyframes scaleIn    { from { opacity:0; transform:scale(.9); } to { opacity:1; transform:scale(1); } }
@keyframes rowSlide   { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:translateX(0); } }
@keyframes floatUp    { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-8px); } }
@keyframes popIn      { 0% { opacity:0; transform:scale(.7) translateY(12px); } 60% { transform:scale(1.04) translateY(-3px); } 100% { opacity:1; transform:scale(1) translateY(0); } }

/* ══ HERO ══ */
.v-hero {
    background: linear-gradient(135deg, var(--v-navy) 0%, #1e4a7e 55%, #0d9488 100%);
    border-radius: var(--v-radius);
    padding: 32px 36px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: fadeInDown .55s cubic-bezier(.22,1,.36,1) both;
}
.v-hero::before {
    content:''; position:absolute; top:-90px; right:-70px;
    width:280px; height:280px;
    background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);
    border-radius:50%; animation: floatUp 6s ease-in-out infinite;
}
.v-hero::after {
    content:''; position:absolute; bottom:-70px; left:-50px;
    width:200px; height:200px;
    background:radial-gradient(circle,rgba(255,255,255,.05) 0%,transparent 70%);
    border-radius:50%; animation: floatUp 8s ease-in-out infinite reverse;
}
.v-hero h1 {
    font-family:'Playfair Display', serif;
    font-size: 26px; font-weight: 700; color: #fff;
    margin: 0 0 8px; position: relative;
}
.v-hero p { font-size: 13px; color: rgba(255,255,255,.78); margin: 0; position: relative; }
.v-hero .meta-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; position: relative; }
.v-meta-pill {
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px; padding: 4px 14px;
    font-size: 12px; font-weight: 600; color: #fff;
    display: inline-flex; align-items: center; gap: 5px;
    transition: all .3s ease;
}
.v-meta-pill:hover { background: rgba(255,255,255,.22); transform: translateY(-2px); }
.v-btn-back {
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    border-radius: 10px; padding: 8px 18px;
    color: #fff; font-size: 12px; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    transition: all .3s ease;
}
.v-btn-back:hover { background: rgba(255,255,255,.22); color: #fff; transform: translateX(-4px); }

/* ══ STATS ══ */
.v-stat {
    background: var(--v-white);
    border: 1px solid var(--v-border);
    border-radius: var(--v-radius);
    padding: 20px 22px;
    position: relative; overflow: hidden;
    transition: all .35s cubic-bezier(.22,1,.36,1);
    animation: scaleIn .5s cubic-bezier(.22,1,.36,1) both;
}
.v-stat:hover { transform: translateY(-4px); box-shadow: var(--v-shadow-lg); }
.v-stat .stat-accent {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: var(--v-radius) var(--v-radius) 0 0;
}
.v-stat .stat-value { font-size: 28px; font-weight: 700; color: var(--v-navy); line-height: 1; margin-top: 8px; }
.v-stat .stat-label { font-size: 12px; color: var(--v-muted); margin-top: 6px; font-weight: 500; }
.v-stat .stat-ico {
    font-size: 34px; opacity: .1;
    position: absolute; right: 18px; top: 50%;
    transform: translateY(-50%);
    transition: all .3s ease;
}
.v-stat:hover .stat-ico { opacity: .18; transform: translateY(-50%) scale(1.1); }

/* ══ Progress ══ */
.v-progress-wrap {
    background: #e2e8f0; border-radius: 6px; height: 8px;
    overflow: hidden; margin-top: 12px;
}
.v-progress-bar {
    height: 100%; border-radius: 6px;
    background: linear-gradient(90deg, var(--v-teal), #14b8a6);
    transition: width .5s ease;
}

/* ══ Card ══ */
.v-card {
    background: var(--v-white);
    border: 1px solid var(--v-border);
    border-radius: var(--v-radius);
    box-shadow: var(--v-shadow);
    overflow: hidden;
    animation: fadeInUp .5s ease .15s both;
}
.v-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--v-border);
    background: linear-gradient(to right, #f8fafc, #f0fdf9);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.v-card-header h5 {
    margin: 0; font-size: 15px; font-weight: 700;
    color: var(--v-navy); display: flex; align-items: center; gap: 8px;
}

/* ══ Table ══ */
.v-table-wrap { overflow-x: auto; }
.v-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.v-table thead th {
    background: var(--v-navy); color: #fff;
    padding: 12px 14px; font-weight: 600; font-size: 11.5px;
    text-transform: uppercase; letter-spacing: .4px;
    text-align: center; white-space: nowrap;
    border: none; position: sticky; top: 0; z-index: 2;
}
.v-table thead th.col-left { text-align: left; }
.v-table tbody tr {
    transition: all .22s ease;
    animation: rowSlide .4s ease both;
    border-bottom: 1px solid var(--v-border);
}
.v-table tbody tr:nth-child(1) { animation-delay: .05s; }
.v-table tbody tr:nth-child(2) { animation-delay: .08s; }
.v-table tbody tr:nth-child(3) { animation-delay: .11s; }
.v-table tbody tr:nth-child(4) { animation-delay: .14s; }
.v-table tbody tr:nth-child(5) { animation-delay: .17s; }
.v-table tbody tr:nth-child(n+6) { animation-delay: .20s; }
.v-table tbody tr:hover { background: #f0f6ff !important; box-shadow: inset 3px 0 0 var(--v-teal); }
.v-table tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    color: #374151;
    border: none;
}

/* Row state colors */
.v-row-vetted     { background: #f0fdf4 !important; }
.v-row-not-vetted { background: #fef2f2 !important; }
.v-row-pending    { background: #fffbeb !important; }

/* ══ Student cell ══ */
.v-student-cell { display: flex; align-items: center; gap: 10px; }
.v-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
    border: 2px solid var(--v-border);
    transition: all .25s cubic-bezier(.22,1,.36,1);
    cursor: pointer;
}
.v-avatar:hover { transform: scale(1.12) rotate(-3deg); border-color: var(--v-teal); box-shadow: 0 4px 14px rgba(13,148,136,.25); }
.v-avatar-initials {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--v-teal), var(--v-sky));
    color: #fff; display: inline-flex;
    align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px;
    flex-shrink: 0; cursor: pointer;
    transition: all .25s cubic-bezier(.22,1,.36,1);
    border: 2px solid var(--v-border);
}
.v-avatar-initials:hover { transform: scale(1.12) rotate(-3deg); border-color: var(--v-teal); }

.v-student-name { font-weight: 700; font-size: 12.5px; color: var(--v-navy); }
.v-student-adm { font-size: 10.5px; color: var(--v-muted); margin-top: 1px; }

/* ══ Score badges ══ */
.v-score-badge {
    display: inline-block; padding: 3px 10px;
    border-radius: 8px; font-weight: 700; font-size: 12px;
    min-width: 42px;
}
.v-score-success { background: #dcfce7; color: #15803d; }
.v-score-info    { background: #dbeafe; color: #1e40af; }
.v-score-warning { background: #fef3c7; color: #92400e; }
.v-score-danger  { background: #fee2e2; color: #991b1b; }
.v-score-muted   { background: #f1f5f9; color: #64748b; }

.v-grade-badge {
    display: inline-block; padding: 3px 10px;
    border-radius: 8px; font-weight: 700; font-size: 12px;
    min-width: 38px; text-align: center;
}
.v-grade-a { background: #dcfce7; color: #15803d; }
.v-grade-b { background: #dbeafe; color: #1d4ed8; }
.v-grade-c { background: #fef9c3; color: #a16207; }
.v-grade-d { background: #ffedd5; color: #c2410c; }
.v-grade-e { background: #ffe4e6; color: #be123c; }
.v-grade-f { background: #fee2e2; color: #b91c1c; }

/* ══ Vetting toggle ══ */
.v-toggle-switch {
    position: relative; display: inline-block;
    width: 52px; height: 28px;
}
.v-toggle-switch input { opacity: 0; width: 0; height: 0; }
.v-slider {
    position: absolute; cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .3s; border-radius: 28px;
}
.v-slider:before {
    position: absolute; content: "";
    height: 22px; width: 22px;
    left: 3px; bottom: 3px;
    background-color: #fff;
    transition: .3s; border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.v-toggle-switch input:checked + .v-slider { background-color: var(--v-green); }
.v-toggle-switch input:checked + .v-slider:before { transform: translateX(24px); }

.v-status-label {
    display: inline-block;
    font-size: 10.5px; font-weight: 700;
    padding: 2px 8px; border-radius: 10px;
    margin-top: 3px;
}
.v-status-vetted    { background: #dcfce7; color: #15803d; }
.v-status-pending   { background: #fef3c7; color: #92400e; }
.v-status-untouched { background: #f1f5f9; color: #64748b; }

/* ══ Search ══ */
.v-search-wrap { position: relative; max-width: 280px; width: 100%; }
.v-search-wrap i {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: var(--v-muted); pointer-events: none;
}
.v-search-input {
    width: 100%; height: 40px;
    border: 1.5px solid var(--v-border);
    border-radius: 10px;
    padding: 8px 14px 8px 40px;
    font-size: 13px; font-family: inherit;
    background: var(--v-surface);
    transition: all .22s ease;
}
.v-search-input:focus {
    outline: none; border-color: var(--v-teal);
    box-shadow: 0 0 0 3px rgba(13,148,136,.12);
    background: #fff;
}

/* ══ Save bar ══ */
.v-save-bar {
    position: sticky; bottom: 0; z-index: 50;
    background: rgba(15,35,66,.97);
    backdrop-filter: blur(10px);
    border-top: 2px solid var(--v-teal);
    padding: 14px 24px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.v-save-bar-info { color: rgba(255,255,255,.75); font-size: 12.5px; font-weight: 500; }
.v-btn-primary {
    background: var(--v-teal); color: #fff;
    border: none; padding: 10px 24px;
    border-radius: 10px; font-weight: 700; font-size: 13px;
    cursor: pointer; transition: all .25s cubic-bezier(.22,1,.36,1);
    display: inline-flex; align-items: center; gap: 6px;
    font-family: inherit;
}
.v-btn-primary:hover { background: #0b7c72; transform: translateY(-2px); box-shadow: 0 8px 22px rgba(13,148,136,.4); }
.v-btn-secondary {
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    color: #fff; padding: 10px 20px;
    border-radius: 10px; font-weight: 600; font-size: 13px;
    cursor: pointer; transition: all .25s ease;
    display: inline-flex; align-items: center; gap: 6px;
    font-family: inherit;
    text-decoration: none;
}
.v-btn-secondary:hover { background: rgba(255,255,255,.22); color: #fff; transform: translateY(-2px); }

/* ══ Empty ══ */
.v-empty {
    padding: 80px 24px; text-align: center;
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
}
.v-empty-icon {
    width: 100px; height: 100px; border-radius: 50%;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 46px; color: var(--v-teal);
    margin-bottom: 20px;
    animation: floatUp 3s ease-in-out infinite;
}
.v-empty h5 { font-size: 17px; font-weight: 700; color: var(--v-navy); margin-bottom: 8px; }
.v-empty p { color: var(--v-muted); font-size: 13px; }

/* ══ Image modal ══ */
.v-image-modal .modal-content { background: transparent; border: none; box-shadow: none; }
.v-image-modal .modal-dialog { max-width: 92vw; }
.v-zoomed-image {
    max-width: 88vw; max-height: 72vh;
    border-radius: 16px; border: 4px solid #fff;
    box-shadow: 0 24px 60px rgba(0,0,0,.4);
    object-fit: contain;
    animation: scaleIn .3s ease;
}
.v-image-name {
    margin-top: 16px; font-size: 18px; font-weight: 700;
    color: #fff; text-align: center;
    text-shadow: 0 2px 8px rgba(0,0,0,.5);
}
.v-image-meta { margin-top: 6px; font-size: 13px; color: rgba(255,255,255,.75); text-align: center; }

/* ══ Toast ══ */
.v-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 99999;
    min-width: 300px; padding: 14px 18px;
    border-radius: 12px; display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 600;
    box-shadow: 0 8px 28px rgba(0,0,0,.14);
    animation: popIn .3s cubic-bezier(.22,1,.36,1);
}
.v-toast-success { background: #ecfdf5; border: 1.5px solid #86efac; color: #15803d; }
.v-toast-error   { background: #fef2f2; border: 1.5px solid #fca5a5; color: #991b1b; }
.v-toast-info    { background: #eff6ff; border: 1.5px solid #93c5fd; color: #1d4ed8; }

/* ══ Mobile ══ */
@media (max-width: 768px) {
    .v-hero { padding: 22px; }
    .v-hero h1 { font-size: 20px; }
    .v-stat { padding: 16px 18px; }
    .v-stat .stat-value { font-size: 22px; }
    .v-table thead th { padding: 9px 6px; font-size: 10px; }
    .v-table tbody td { padding: 8px 6px; font-size: 11.5px; }
    .v-table { min-width: 900px; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

{{-- ══ HERO ══ --}}
<div class="v-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1><i class="ri-shield-check-line me-2"></i>Terminal Broadsheet — Vetting</h1>
            <p>Review and vet student scores for this subject and class.</p>
            <div class="meta-pills">
                <span class="v-meta-pill"><i class="ri-book-line"></i>{{ $broadsheets->first()->subject ?? 'Subject' }}</span>
                <span class="v-meta-pill"><i class="ri-building-line"></i>{{ $schoolclass->schoolclass ?? '' }} {{ $schoolclass->arm->arm ?? '' }}</span>
                <span class="v-meta-pill"><i class="ri-calendar-line"></i>{{ $schoolterm }} | {{ $schoolsession }}</span>
            </div>
        </div>
        <a href="{{ route('mysubjectvettings.index') }}" class="v-btn-back">
            <i class="ri-arrow-left-line"></i> Back to Assignments
        </a>
    </div>
</div>

@if($broadsheets->isNotEmpty())
@php
    $total      = $broadsheets->count();
    $vetted     = $broadsheets->where('vettedstatus', '1')->count();
    $pending    = $broadsheets->where('vettedstatus', '0')->count();
    $untouched  = $total - $vetted - $pending;
    $avgCum     = $total > 0 ? round($broadsheets->avg('cum'), 1) : 0;
    $highest    = $total > 0 ? round($broadsheets->max('cum'), 1) : 0;
    $lowest     = $total > 0 ? round($broadsheets->min('cum'), 1) : 0;
    $vettedPct  = $total > 0 ? round(($vetted / $total) * 100) : 0;
@endphp

{{-- ══ STATS ══ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="v-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--v-navy),var(--v-teal));"></div>
            <div class="stat-ico"><i class="ri-group-line"></i></div>
            <div class="stat-value">{{ $total }}</div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="v-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--v-green),#4ade80);"></div>
            <div class="stat-ico"><i class="ri-check-double-line"></i></div>
            <div class="stat-value" style="color:var(--v-green);">{{ $vetted }}</div>
            <div class="stat-label">Vetted ({{ $vettedPct }}%)</div>
            <div class="v-progress-wrap"><div class="v-progress-bar" style="width:{{ $vettedPct }}%"></div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="v-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--v-amber),#fcd34d);"></div>
            <div class="stat-ico"><i class="ri-time-line"></i></div>
            <div class="stat-value" style="color:var(--v-amber);">{{ $pending }}</div>
            <div class="stat-label">Explicitly Not Vetted</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="v-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--v-sky),#38bdf8);"></div>
            <div class="stat-ico"><i class="ri-bar-chart-line"></i></div>
            <div class="stat-value" style="color:var(--v-sky);">{{ $avgCum }}</div>
            <div class="stat-label">Class Avg (Cum)</div>
            <div style="font-size:11px;color:var(--v-muted);margin-top:4px;">H: {{ $highest }} · L: {{ $lowest }}</div>
        </div>
    </div>
</div>

{{-- ══ TABLE ══ --}}
<div class="v-card">
    <div class="v-card-header">
        <h5>
            <i class="ri-table-alt-line" style="color:var(--v-teal);"></i>
            Student Scores
            <span class="badge" style="background:var(--v-teal);color:#fff;border-radius:20px;font-size:11px;padding:3px 10px;" id="scoreCount">{{ $total }}</span>
        </h5>
        <div class="v-search-wrap">
            <i class="ri-search-line"></i>
            <input type="text" class="v-search-input" id="searchInput" placeholder="Search student or admission…">
        </div>
    </div>

    <div class="v-table-wrap">
        <table class="v-table" id="vettingTable">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th class="col-left" style="min-width:220px;">Student</th>
                    <th>Adm. No</th>
                    <th style="width:70px;">CA1</th>
                    <th style="width:70px;">CA2</th>
                    <th style="width:70px;">CA3</th>
                    <th style="width:70px;">Exam</th>
                    <th style="width:80px;">Total</th>
                    <th style="width:70px;">BF</th>
                    <th style="width:80px;">Cum</th>
                    <th style="width:70px;">Grade</th>
                    <th style="width:70px;">Pos</th>
                    <th style="width:80px;">Class Avg</th>
                    <th style="width:130px; text-align:center;">Vetted</th>
                </tr>
            </thead>
            <tbody id="vettingTableBody">
                @forelse($broadsheets as $index => $b)
                    @php
                        $isVetted  = $b->vettedstatus === '1';
                        $isPending = $b->vettedstatus === '0';
                        $rowClass  = $isVetted ? 'v-row-vetted' : ($isPending ? 'v-row-not-vetted' : 'v-row-pending');

                        $hasPic   = !empty($b->picture) && $b->picture !== 'unnamed.jpg';
                        $imgUrl   = $hasPic ? asset('storage/student_avatars/' . basename($b->picture)) : null;
                        $initials = strtoupper(substr($b->fname ?? '', 0, 1) . substr($b->lname ?? '', 0, 1)) ?: 'ST';

                        $totalVal = (float) ($b->total ?? 0);
                        $cumVal   = (float) ($b->cum ?? 0);

                        $totalClass = $totalVal >= 70 ? 'v-score-success'
                                    : ($totalVal >= 50 ? 'v-score-info'
                                    : ($totalVal >= 40 ? 'v-score-warning' : 'v-score-danger'));
                        $cumClass   = $cumVal >= 70 ? 'v-score-success'
                                    : ($cumVal >= 50 ? 'v-score-info'
                                    : ($cumVal >= 40 ? 'v-score-warning' : 'v-score-danger'));

                        $grade      = $b->grade ?? '-';
                        $gradeClass = match(strtoupper(substr($grade, 0, 1))) {
                            'A' => 'v-grade-a',
                            'B' => 'v-grade-b',
                            'C' => 'v-grade-c',
                            'D' => 'v-grade-d',
                            'E' => 'v-grade-e',
                            default => 'v-grade-f',
                        };

                        $statusLabel = $isVetted ? 'Vetted' : ($isPending ? 'Not Vetted' : 'Pending');
                        $statusClass = $isVetted ? 'v-status-vetted' : ($isPending ? 'v-status-pending' : 'v-status-untouched');
                    @endphp
                    <tr class="{{ $rowClass }}"
                        data-id="{{ $b->id }}"
                        data-search="{{ strtolower(($b->fname ?? '') . ' ' . ($b->lname ?? '') . ' ' . ($b->admissionno ?? '')) }}">

                        <td style="text-align:center;font-weight:600;color:var(--v-muted);">{{ $index + 1 }}</td>

                        <td class="col-left">
                            <div class="v-student-cell">
                                @if($imgUrl)
                                    <img src="{{ $imgUrl }}"
                                         class="v-avatar student-image"
                                         data-image="{{ $imgUrl }}"
                                         data-name="{{ trim(($b->lname ?? '') . ' ' . ($b->fname ?? '')) }}"
                                         data-admission="{{ $b->admissionno ?? '—' }}"
                                         alt="{{ $b->fname }}"
                                         onerror="this.outerHTML='<div class=\'v-avatar-initials\'>{{ $initials }}</div>'">
                                @else
                                    <div class="v-avatar-initials">{{ $initials }}</div>
                                @endif
                                <div>
                                    <div class="v-student-name">
                                        {{ strtoupper($b->lname ?? '') }}, {{ $b->fname ?? '' }}
                                    </div>
                                    @if(!empty($b->mname))
                                        <div class="v-student-adm">{{ $b->mname }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td style="font-family:'Courier New',monospace;font-size:11.5px;color:var(--v-muted);">
                            {{ $b->admissionno ?? '—' }}
                        </td>

                        <td style="text-align:center;">
                            <span class="v-score-badge v-score-muted" style="font-weight:600;">
                                {{ number_format((float) ($b->ca1 ?? 0), 1) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span class="v-score-badge v-score-muted" style="font-weight:600;">
                                {{ number_format((float) ($b->ca2 ?? 0), 1) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span class="v-score-badge v-score-muted" style="font-weight:600;">
                                {{ number_format((float) ($b->ca3 ?? 0), 1) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span class="v-score-badge v-score-muted" style="font-weight:600;">
                                {{ number_format((float) ($b->exam ?? 0), 1) }}
                            </span>
                        </td>

                        <td style="text-align:center;">
                            <span class="v-score-badge {{ $totalClass }}">{{ number_format($totalVal, 1) }}</span>
                        </td>

                        <td style="text-align:center;">
                            <span class="v-score-badge v-score-muted">{{ number_format((float) ($b->bf ?? 0), 2) }}</span>
                        </td>

                        <td style="text-align:center;">
                            <span class="v-score-badge {{ $cumClass }}">{{ number_format($cumVal, 2) }}</span>
                        </td>

                        <td style="text-align:center;">
                            <span class="v-grade-badge {{ $gradeClass }}">{{ $grade }}</span>
                        </td>

                        <td style="text-align:center;">
                            <span class="v-score-badge v-score-info">
                                {{ $b->position ? \App\Helpers\OrdinalHelper::getOrdinalSuffix($b->position) : '—' }}
                            </span>
                        </td>

                        <td style="text-align:center;">
                            <span class="v-score-badge v-score-muted">{{ number_format((float) ($b->avg ?? 0), 1) }}</span>
                        </td>

                        <td style="text-align:center;">
                            <label class="v-toggle-switch" title="{{ $statusLabel }}">
                                <input type="checkbox"
                                       class="vetted-toggle"
                                       data-broadsheet-id="{{ $b->id }}"
                                       {{ $isVetted ? 'checked' : '' }}>
                                <span class="v-slider"></span>
                            </label>
                            <div>
                                <span class="v-status-label {{ $statusClass }}" id="status-label-{{ $b->id }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" style="padding:0;">
                            <div class="v-empty">
                                <div class="v-empty-icon"><i class="ri-inbox-archive-line"></i></div>
                                <h5>No Scores Available</h5>
                                <p>No student scores found for this subject, class, term and session.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="v-save-bar">
        <div class="v-save-bar-info">
            <i class="ri-information-line me-1"></i>
            <span id="vettedCount">{{ $vetted }}</span> of <span>{{ $total }}</span> students vetted
            @if($untouched > 0)
                <span style="opacity:.6;">· {{ $untouched }} not yet reviewed</span>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="v-btn-secondary" id="markAllVetted">
                <i class="ri-check-double-line"></i> Mark All Vetted
            </button>
            <button type="button" class="v-btn-secondary" id="clearAllVetted">
                <i class="ri-close-line"></i> Clear All
            </button>
        </div>
    </div>
</div>
@else
<div class="v-card">
    <div class="v-empty">
        <div class="v-empty-icon"><i class="ri-inbox-archive-line"></i></div>
        <h5>No Scores Available</h5>
        <p>No student scores found for this subject, class, term and session.</p>
        <a href="{{ route('mysubjectvettings.index') }}" class="v-btn-back" style="background:var(--v-teal);border-color:var(--v-teal);margin-top:20px;">
            <i class="ri-arrow-left-line"></i> Back to Assignments
        </a>
    </div>
</div>
@endif

</div>
</div>
</div>

{{-- ══ IMAGE ZOOM MODAL ══ --}}
<div class="modal fade v-image-modal" id="imageZoomModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    style="position:absolute;top:14px;right:14px;z-index:10;background:rgba(0,0,0,.5);border-radius:50%;padding:10px;opacity:1;filter:brightness(0) invert(1);"></button>
            <div class="modal-body text-center">
                <img id="zoomedImage" src="" alt="Student" class="v-zoomed-image">
                <div class="v-image-name" id="zoomedName"></div>
                <div class="v-image-meta" id="zoomedMeta"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const CSRF       = '{{ csrf_token() }}';
    const UPDATE_URL = '{{ route("broadsheets.update-vetted-status") }}';

    // ═══ Toast ═══
    function toast(msg, type) {
        const colors = {
            success: { bg: '#ecfdf5', border: '#86efac', text: '#15803d', icon: 'ri-checkbox-circle-fill' },
            error:   { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', icon: 'ri-error-warning-fill' },
            info:    { bg: '#eff6ff', border: '#93c5fd', text: '#1d4ed8', icon: 'ri-information-fill' },
        };
        const c  = colors[type] || colors.info;
        const el = document.createElement('div');
        el.className = 'v-toast v-toast-' + (type || 'info');
        el.innerHTML = `<i class="${c.icon}" style="font-size:18px;flex-shrink:0;"></i> <div style="flex:1;">${msg}</div>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4200);
    }

    // ═══ Search ═══
    const searchInput = document.getElementById('searchInput');
    const tableBody   = document.getElementById('vettingTableBody');
    const scoreCount  = document.getElementById('scoreCount');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            tableBody.querySelectorAll('tr[data-id]').forEach(row => {
                const hay  = row.dataset.search || '';
                const show = !q || hay.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (scoreCount) scoreCount.textContent = visible;
        });
    }

    // ═══ Vetted status toggle ═══
    document.querySelectorAll('.vetted-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const broadsheetId = this.dataset.broadsheetId;
            const vettedStatus = this.checked ? 1 : 0;
            const row          = this.closest('tr');
            const label        = document.getElementById('status-label-' + broadsheetId);

            // Optimistic UI update
            row.classList.remove('v-row-vetted', 'v-row-not-vetted', 'v-row-pending');
            row.classList.add(vettedStatus === 1 ? 'v-row-vetted' : 'v-row-not-vetted');

            if (label) {
                label.textContent = vettedStatus === 1 ? 'Vetted' : 'Not Vetted';
                label.className   = 'v-status-label ' + (vettedStatus === 1 ? 'v-status-vetted' : 'v-status-pending');
            }

            fetch(UPDATE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    broadsheet_id: broadsheetId,
                    vettedstatus:  vettedStatus,
                }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Update failed');
                refreshVettedCount();
                toast('Vetted status updated', 'success');
            })
            .catch(err => {
                // Revert on failure
                this.checked = !this.checked;
                row.classList.remove('v-row-vetted', 'v-row-not-vetted', 'v-row-pending');
                row.classList.add(this.checked ? 'v-row-vetted' : 'v-row-not-vetted');
                if (label) {
                    label.textContent = this.checked ? 'Vetted' : 'Not Vetted';
                    label.className   = 'v-status-label ' + (this.checked ? 'v-status-vetted' : 'v-status-pending');
                }
                toast('Error: ' + err.message, 'error');
            });
        });
    });

    function refreshVettedCount() {
        const checked = document.querySelectorAll('.vetted-toggle:checked').length;
        const counter = document.getElementById('vettedCount');
        if (counter) counter.textContent = checked;
    }

    // ═══ Bulk actions ═══
    document.getElementById('markAllVetted')?.addEventListener('click', function () {
        document.querySelectorAll('.vetted-toggle').forEach(t => {
            if (!t.checked) { t.checked = true; t.dispatchEvent(new Event('change')); }
        });
    });

    document.getElementById('clearAllVetted')?.addEventListener('click', function () {
        document.querySelectorAll('.vetted-toggle').forEach(t => {
            if (t.checked) { t.checked = false; t.dispatchEvent(new Event('change')); }
        });
    });

    // ═══ Image zoom ═══
    const imgModalEl  = document.getElementById('imageZoomModal');
    const imgModal    = imgModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(imgModalEl) : null;
    const zoomedImage = document.getElementById('zoomedImage');
    const zoomedName  = document.getElementById('zoomedName');
    const zoomedMeta  = document.getElementById('zoomedMeta');

    document.querySelectorAll('.student-image').forEach(img => {
        img.addEventListener('click', function () {
            const src  = this.dataset.image;
            const name = this.dataset.name || 'Student';
            const adm  = this.dataset.admission || '—';
            if (zoomedImage) zoomedImage.src = src;
            if (zoomedName)  zoomedName.textContent = name;
            if (zoomedMeta)  zoomedMeta.innerHTML = `<i class="ri-id-card-line me-1"></i>${adm}`;
            if (imgModal) imgModal.show();
        });
    });

    if (zoomedImage) zoomedImage.addEventListener('click', () => imgModal?.hide());
})();
</script>
@endsection