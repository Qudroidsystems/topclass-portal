{{-- resources/views/myprincipalscomment/classbroadsheet.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
:root {
    --pc-navy:   #0f2342;
    --pc-teal:   #0d9488;
    --pc-sky:    #0ea5e9;
    --pc-amber:  #f59e0b;
    --pc-purple: #7c3aed;
    --pc-green:  #22c55e;
    --pc-rose:   #f43f5e;
    --pc-muted:  #64748b;
    --pc-border: #e2e8f0;
    --pc-surface:#f8fafc;
    --pc-white:  #ffffff;
    --pc-radius: 14px;
    --pc-shadow: 0 2px 12px rgba(15,35,66,.08);
    --pc-shadow-lg:0 8px 28px rgba(15,35,66,.14);
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; }

@keyframes fadeInDown  { from { opacity:0; transform:translateY(-22px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInUp    { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }
@keyframes scaleIn     { from { opacity:0; transform:scale(.9); } to { opacity:1; transform:scale(1); } }
@keyframes floatUp     { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-8px); } }
@keyframes rowSlide    { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:translateX(0); } }
@keyframes popIn       { 0% { opacity:0; transform:scale(.7) translateY(12px); } 60% { transform:scale(1.04) translateY(-3px); } 100% { opacity:1; transform:scale(1) translateY(0); } }
@keyframes spin        { to { transform:rotate(360deg); } }

.spin { animation: spin 1s linear infinite; }

/* ══ HERO ══ */
.pc-hero {
    background: linear-gradient(135deg, var(--pc-navy) 0%, #1e4a7e 55%, var(--pc-purple) 100%);
    border-radius: var(--pc-radius);
    padding: 32px 36px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: fadeInDown .55s cubic-bezier(.22,1,.36,1) both;
}
.pc-hero::before {
    content:''; position:absolute; top:-90px; right:-70px;
    width:280px; height:280px;
    background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);
    border-radius:50%; animation: floatUp 6s ease-in-out infinite;
}
.pc-hero::after {
    content:''; position:absolute; bottom:-70px; left:-50px;
    width:200px; height:200px;
    background:radial-gradient(circle,rgba(255,255,255,.05) 0%,transparent 70%);
    border-radius:50%; animation: floatUp 8s ease-in-out infinite reverse;
}
.pc-hero h1 {
    font-family:'Playfair Display', serif;
    font-size: 26px; font-weight: 700; color: #fff;
    margin: 0 0 8px; position: relative;
}
.pc-hero p { font-size: 13px; color: rgba(255,255,255,.78); margin: 0; position: relative; }
.pc-hero .meta-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; position: relative; }
.pc-meta-pill {
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px; padding: 4px 14px;
    font-size: 12px; font-weight: 600; color: #fff;
    display: inline-flex; align-items: center; gap: 5px;
    transition: all .3s ease;
}
.pc-meta-pill:hover { background: rgba(255,255,255,.22); transform: translateY(-2px); }
.pc-btn-back {
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    border-radius: 10px; padding: 8px 18px;
    color: #fff; font-size: 12px; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    transition: all .3s ease;
}
.pc-btn-back:hover { background: rgba(255,255,255,.22); color: #fff; transform: translateX(-4px); }

/* ══ MODE TOGGLE BAR ══ */
.pc-mode-bar {
    background: #fff;
    border: 1px solid var(--pc-border);
    border-radius: var(--pc-radius);
    padding: 14px 20px;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    box-shadow: var(--pc-shadow);
    animation: fadeInUp .5s ease .1s both;
}
.pc-mode-bar .mode-label {
    font-size: 13px; font-weight: 600;
    color: var(--pc-navy); white-space: nowrap;
}
.pc-mode-toggle {
    display: flex;
    background: var(--pc-surface);
    border: 1.5px solid var(--pc-border);
    border-radius: 10px; overflow: hidden;
    flex-shrink: 0;
}
.pc-mode-toggle .mode-btn {
    padding: 7px 18px; font-size: 12.5px; font-weight: 700;
    border: none; background: transparent; color: var(--pc-muted);
    cursor: pointer; transition: all .2s ease;
    display: flex; align-items: center; gap: 6px; text-decoration: none;
}
.pc-mode-toggle .mode-btn:hover { background: #e9ecef; color: var(--pc-navy); }
.pc-mode-toggle .mode-btn.active-cumulative { background: linear-gradient(135deg, #7c3aed, #a855f7); color: #fff; }
.pc-mode-toggle .mode-btn.active-term       { background: linear-gradient(135deg, #0891b2, #06b6d4); color: #fff; }
.pc-mode-toggle .mode-btn.active-mock       { background: linear-gradient(135deg, #7c3aed, #a855f7); color: #fff; }

.pc-mode-hint {
    font-size: 12px; color: var(--pc-muted);
    background: var(--pc-surface);
    border: 1px dashed var(--pc-border);
    border-radius: 6px; padding: 5px 10px;
}
.pc-mode-hint strong { color: var(--pc-navy); }

/* ══ STATS ══ */
.pc-stat {
    background: var(--pc-white);
    border: 1px solid var(--pc-border);
    border-radius: var(--pc-radius);
    padding: 20px 22px;
    position: relative; overflow: hidden;
    transition: all .35s cubic-bezier(.22,1,.36,1);
}
.pc-stat:hover { transform: translateY(-4px); box-shadow: var(--pc-shadow-lg); }
.pc-stat .stat-value { font-size: 26px; font-weight: 700; color: var(--pc-navy); line-height: 1; margin-top: 8px; }
.pc-stat .stat-label { font-size: 12px; color: var(--pc-muted); margin-top: 6px; font-weight: 500; }
.pc-stat .stat-ico {
    font-size: 32px; opacity: .1;
    position: absolute; right: 18px; top: 50%;
    transform: translateY(-50%);
}

/* ══ CARD ══ */
.pc-card {
    background: var(--pc-white);
    border: 1px solid var(--pc-border);
    border-radius: var(--pc-radius);
    box-shadow: var(--pc-shadow);
    overflow: hidden;
    animation: fadeInUp .5s ease .15s both;
}
.pc-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--pc-border);
    background: linear-gradient(to right, #f8fafc, #f5f3ff);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.pc-card-header h5 {
    margin: 0; font-size: 15px; font-weight: 700;
    color: var(--pc-navy); display: flex; align-items: center; gap: 8px;
}

/* ══ SEARCH ══ */
.pc-search-wrap { position: relative; max-width: 280px; width: 100%; }
.pc-search-wrap i {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: var(--pc-muted); pointer-events: none;
}
.pc-search-input {
    width: 100%; height: 40px;
    border: 1.5px solid var(--pc-border);
    border-radius: 10px;
    padding: 8px 14px 8px 40px;
    font-size: 13px; font-family: inherit;
    background: var(--pc-surface);
    transition: all .22s ease;
}
.pc-search-input:focus {
    outline: none; border-color: var(--pc-purple);
    box-shadow: 0 0 0 3px rgba(124,58,237,.12);
    background: #fff;
}

/* ══ TABLE ══ */
.pc-table-wrap { overflow-x: auto; }
.pc-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.pc-table thead th {
    background: var(--pc-navy); color: #fff;
    padding: 12px 14px; font-weight: 600; font-size: 11px;
    text-transform: uppercase; letter-spacing: .4px;
    text-align: center; white-space: nowrap;
    border: none; position: sticky; top: 0; z-index: 2;
}
.pc-table thead th.col-left { text-align: left; }
.pc-table tbody tr {
    transition: all .2s ease;
    animation: rowSlide .35s ease both;
    border-bottom: 1px solid var(--pc-border);
}
.pc-table tbody tr:hover { background: #f8f5ff !important; box-shadow: inset 3px 0 0 var(--pc-purple); }
.pc-table tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    color: #374151;
    border: none;
}

/* Student cell */
.pc-student-cell { display: flex; align-items: center; gap: 10px; }
.pc-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
    border: 2px solid var(--pc-border);
    transition: all .25s cubic-bezier(.22,1,.36,1);
    cursor: pointer;
}
.pc-avatar:hover { transform: scale(1.12) rotate(-3deg); border-color: var(--pc-purple); box-shadow: 0 4px 14px rgba(124,58,237,.25); }
.pc-avatar-initials {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--pc-purple), #a855f7);
    color: #fff; display: inline-flex;
    align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px;
    flex-shrink: 0; cursor: pointer;
    border: 2px solid var(--pc-border);
}
.pc-student-name { font-weight: 700; font-size: 12.5px; color: var(--pc-navy); }
.pc-student-adm { font-size: 10.5px; color: var(--pc-muted); margin-top: 1px; }
.pc-comment-saved { font-size: 10px; color: var(--pc-green); font-weight: 600; margin-top: 2px; }

/* Score cells */
.pc-score-badge {
    display: inline-block; padding: 3px 9px;
    border-radius: 8px; font-weight: 700; font-size: 11.5px;
    min-width: 42px;
}
.pc-score-green  { background: #dcfce7; color: #15803d; }
.pc-score-blue   { background: #dbeafe; color: #1e40af; }
.pc-score-amber  { background: #fef3c7; color: #92400e; }
.pc-score-red    { background: #fee2e2; color: #991b1b; }
.pc-score-muted  { background: #f1f5f9; color: #64748b; }
.pc-score-purple { background: #ede9fe; color: #5b21b6; }

.pc-grade-badge {
    display: inline-block; padding: 3px 9px;
    border-radius: 8px; font-weight: 700; font-size: 11.5px;
    min-width: 34px; text-align: center;
}
.pc-grade-a { background: #dcfce7; color: #15803d; }
.pc-grade-b { background: #dbeafe; color: #1d4ed8; }
.pc-grade-c { background: #fef9c3; color: #a16207; }
.pc-grade-d { background: #ffedd5; color: #c2410c; }
.pc-grade-e { background: #ffe4e6; color: #be123c; }
.pc-grade-f { background: #fee2e2; color: #b91c1c; }

/* Comment column */
.pc-comment-cell { min-width: 280px; max-width: 340px; }

.pc-ai-preview {
    background: #f0fdf4; border: 1px solid #86efac; border-left: 3px solid var(--pc-green);
    border-radius: 8px; padding: 8px 10px; margin-bottom: 8px;
    font-size: 11px; line-height: 1.5;
}
.pc-ai-preview .ai-label {
    font-size: 9.5px; font-weight: 700; text-transform: uppercase;
    color: var(--pc-green); letter-spacing: .3px;
    display: flex; align-items: center; gap: 4px; margin-bottom: 4px;
}
.pc-saved-preview {
    background: #dbeafe; border-left: 3px solid #2563eb;
    border-radius: 8px; padding: 6px 10px; margin-bottom: 8px;
    font-size: 11px; line-height: 1.4; max-height: 60px;
    overflow: hidden;
}
.pc-saved-preview .saved-label {
    font-size: 9.5px; font-weight: 700; text-transform: uppercase;
    color: #1e40af; letter-spacing: .3px;
    display: flex; align-items: center; gap: 4px; margin-bottom: 3px;
}

.pc-comment-select {
    width: 100%; font-size: 12px;
    border: 1.5px solid var(--pc-border);
    border-radius: 8px; padding: 8px 10px;
    background: var(--pc-surface); font-family: inherit;
    transition: all .22s ease; cursor: pointer;
}
.pc-comment-select:focus {
    outline: none; border-color: var(--pc-purple);
    box-shadow: 0 0 0 3px rgba(124,58,237,.12);
    background: #fff;
}

/* Toggle chips */
.pc-toggle-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 10.5px; font-weight: 700;
    margin: 1px;
}
.pc-toggle-cumulative { background: #f5f3ff; color: #5b21b6; }
.pc-toggle-term       { background: #e0f2fe; color: #0369a1; }
.pc-toggle-mock       { background: #ede9fe; color: #5b21b6; }

/* Sticky save bar */
.pc-save-bar {
    position: sticky; bottom: 0; z-index: 50;
    background: rgba(15,35,66,.97);
    backdrop-filter: blur(10px);
    border-top: 2px solid var(--pc-purple);
    padding: 14px 24px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.pc-save-bar-info { color: rgba(255,255,255,.75); font-size: 12.5px; font-weight: 500; }
.pc-btn-primary {
    background: var(--pc-purple); color: #fff;
    border: none; padding: 10px 24px;
    border-radius: 10px; font-weight: 700; font-size: 13px;
    cursor: pointer; transition: all .25s cubic-bezier(.22,1,.36,1);
    display: inline-flex; align-items: center; gap: 6px;
    font-family: inherit;
}
.pc-btn-primary:hover { background: #6d28d9; transform: translateY(-2px); box-shadow: 0 8px 22px rgba(124,58,237,.4); }

/* Toast */
.pc-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 99999;
    min-width: 300px; padding: 14px 18px;
    border-radius: 12px; display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 600;
    box-shadow: 0 8px 28px rgba(0,0,0,.14);
    animation: popIn .3s cubic-bezier(.22,1,.36,1);
}
.pc-toast-success { background: #ecfdf5; border: 1.5px solid #86efac; color: #15803d; }
.pc-toast-error   { background: #fef2f2; border: 1.5px solid #fca5a5; color: #991b1b; }

/* Empty */
.pc-empty {
    padding: 80px 24px; text-align: center;
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
}
.pc-empty-icon {
    width: 100px; height: 100px; border-radius: 50%;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 46px; color: var(--pc-purple);
    margin-bottom: 20px; animation: floatUp 3s ease-in-out infinite;
}
.pc-empty h5 { font-size: 17px; font-weight: 700; color: var(--pc-navy); margin-bottom: 8px; }
.pc-empty p { color: var(--pc-muted); font-size: 13px; }

/* Mobile */
@media (max-width: 768px) {
    .pc-hero { padding: 22px; }
    .pc-hero h1 { font-size: 20px; }
    .pc-stat { padding: 16px 18px; }
    .pc-stat .stat-value { font-size: 22px; }
    .pc-table thead th { padding: 10px 8px; font-size: 10px; }
    .pc-table tbody td { padding: 10px 8px; font-size: 11.5px; }
    .pc-table { min-width: 1100px; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

{{-- ══ HERO ══ --}}
<div class="pc-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1><i class="ri-chat-quote-line me-2"></i>Principal's Comment &amp; Class Broadsheet</h1>
            <p>Review student performance and enter Principal's comments for the selected class.</p>
            <div class="meta-pills">
                <span class="pc-meta-pill">
                    <i class="ri-building-line"></i>{{ $schoolclass->schoolclass }} {{ $schoolclass->arm_name ?? '' }}
                </span>
                <span class="pc-meta-pill">
                    <i class="ri-calendar-line"></i>{{ $schooltermName }} | {{ $schoolsession }}
                </span>
                <span class="pc-meta-pill" style="background:rgba(124,58,237,.2);border-color:rgba(124,58,237,.4);">
                    <i class="ri-group-line"></i>{{ $students->count() }} Students
                </span>
            </div>
        </div>
        <a href="{{ route('myprincipalscomment.index') }}" class="pc-btn-back">
            <i class="ri-arrow-left-line"></i> Back to Assignments
        </a>
    </div>
</div>

{{-- ══ MODE TOGGLE ══ --}}
<div class="pc-mode-bar">
    <span class="mode-label">
        <i class="ri-bar-chart-grouped-line me-1"></i>
        Grading Mode:
    </span>
    <div class="pc-mode-toggle">
        <a href="{{ request()->fullUrlWithQuery(['scoring_mode' => 'cumulative']) }}"
           class="mode-btn {{ $scoringMode === 'cumulative' ? 'active-cumulative' : '' }}">
            <i class="ri-bar-chart-line"></i> Cumulative
        </a>
        <a href="{{ request()->fullUrlWithQuery(['scoring_mode' => 'term']) }}"
           class="mode-btn {{ $scoringMode === 'term' ? 'active-term' : '' }}">
            <i class="ri-calendar-check-line"></i> Term
        </a>
        @if($hasMockData)
            <a href="{{ request()->fullUrlWithQuery(['scoring_mode' => 'mock']) }}"
               class="mode-btn {{ $scoringMode === 'mock' ? 'active-mock' : '' }}">
                <i class="ri-file-list-3-line"></i> Mock
            </a>
        @else
            <span class="mode-btn text-muted" style="cursor:not-allowed;opacity:.5;" title="No mock data available">
                <i class="ri-file-list-3-line"></i> Mock
            </span>
        @endif
    </div>
    <span class="pc-mode-hint">
        @if($scoringMode === 'cumulative')
            <i class="ri-information-line text-primary"></i>
            Grades based on <strong>Cumulative (BF + Term avg)</strong>
        @elseif($scoringMode === 'term')
            <i class="ri-information-line text-info"></i>
            Grades based on <strong>Term scores only</strong>
        @else
            <i class="ri-information-line" style="color:var(--pc-purple)"></i>
            Grades based on <strong>Mock exam scores</strong>
        @endif
    </span>
</div>

{{-- ══ STATS ══ --}}
@if($students->isNotEmpty())
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="pc-stat">
            <div class="stat-ico"><i class="ri-group-line"></i></div>
            <div class="stat-value">{{ $students->count() }}</div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pc-stat">
            <div class="stat-ico"><i class="ri-book-open-line"></i></div>
            <div class="stat-value text-primary">{{ count($subjects) }}</div>
            <div class="stat-label">Subjects</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pc-stat">
            <div class="stat-ico"><i class="ri-bar-chart-line"></i></div>
            <div class="stat-value
                @if($scoringMode === 'term') text-info
                @elseif($scoringMode === 'mock') text-purple
                @else text-success
                @endif"
                style="{{ $scoringMode === 'mock' ? 'color:var(--pc-purple)' : '' }}">
                {{ $classAnalytics['average'] }}
            </div>
            <div class="stat-label">Class Average</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pc-stat">
            <div class="stat-ico"><i class="ri-award-line"></i></div>
            <div class="stat-value text-warning" style="font-size:15px;">
                @php
                    $topStudent = $students->sortByDesc(function ($s) use ($studentAnalytics, $scoringMode) {
                        return match($scoringMode) {
                            'term' => $studentAnalytics[$s->id]['term_average'] ?? 0,
                            'mock' => $studentAnalytics[$s->id]['mock_average'] ?? 0,
                            default => $studentAnalytics[$s->id]['average'] ?? 0,
                        };
                    })->first();
                @endphp
                @if($topStudent)
                    {{ $topStudent->fname }} {{ substr($topStudent->lastname, 0, 1) }}.
                @else —
                @endif
            </div>
            <div class="stat-label">Top Performer</div>
        </div>
    </div>
</div>
@endif

{{-- ══ TABLE ══ --}}
@if($students->isNotEmpty())
<div class="pc-card">
    <div class="pc-card-header">
        <h5>
            <i class="ri-table-alt-line" style="color:var(--pc-purple);"></i>
            Student Performance &amp; Comments
            <span class="badge" style="background:var(--pc-purple);color:#fff;border-radius:20px;font-size:11px;padding:3px 10px;">
                {{ $students->count() }}
            </span>
        </h5>
        <div class="pc-search-wrap">
            <i class="ri-search-line"></i>
            <input type="text" class="pc-search-input" id="searchInput" placeholder="Search students…">
        </div>
    </div>

    <form id="commentsForm" action="{{ route('myprincipalscomment.updateComments', [$schoolclassid, $sessionid, $termid]) }}" method="POST">
        @csrf

        <div class="pc-table-wrap">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th style="width:44px;">#</th>
                        <th class="col-left" style="min-width:200px;">Student</th>
                        <th class="col-left" style="min-width:340px;">Subject Scores</th>
                        <th class="col-left" style="min-width:320px;">Principal's Comment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $index => $student)
                        @php
                            $sid        = $student->id;
                            $hasPic     = !empty($student->picture) && $student->picture !== 'unnamed.jpg';
                            $imgUrl     = $hasPic ? asset('storage/student_avatars/' . basename($student->picture)) : null;
                            $initials   = strtoupper(substr($student->fname ?? '', 0, 1) . substr($student->lastname ?? '', 0, 1)) ?: 'ST';
                            $fullName   = trim(($student->lastname ?? '') . ' ' . ($student->fname ?? '') . (($student->othername ?? '') ? ' (' . $student->othername . ')' : ''));
                            $currentComment = $profiles[$sid] ?? '';
                            $plainComment   = strip_tags($currentComment);
                            $intelligent    = $intelligentComments[$sid] ?? '';
                            $stdOptions     = $standardPersonalizedComments[$sid] ?? [];
                        @endphp
                        <tr data-student-id="{{ $sid }}"
                            data-search="{{ strtolower($fullName . ' ' . ($student->admissionNo ?? '')) }}">

                            <td style="text-align:center;font-weight:600;color:var(--pc-muted);">{{ $index + 1 }}</td>

                            {{-- Student --}}
                            <td class="col-left">
                                <div class="pc-student-cell">
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" class="pc-avatar" alt="{{ $student->fname }}"
                                             onerror="this.outerHTML='<div class=\'pc-avatar-initials\'>{{ $initials }}</div>'">
                                    @else
                                        <div class="pc-avatar-initials">{{ $initials }}</div>
                                    @endif
                                    <div>
                                        <div class="pc-student-name">{{ strtoupper($student->lastname ?? '') }}, {{ $student->fname ?? '' }}</div>
                                        <div class="pc-student-adm">{{ $student->admissionNo ?? '—' }}</div>
                                        @if($currentComment)
                                            <div class="pc-comment-saved"><i class="ri-check-double-line"></i> Comment Saved</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Scores grid --}}
                            <td class="col-left">
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:6px;">
                                    @foreach($subjects as $subject)
                                        @php
                                            $tScore = $termScoreMap[$sid][$subject] ?? 0;
                                            $cScore = $cumScoreMap[$sid][$subject]  ?? 0;
                                            $bf     = $bfMap[$sid][$subject]        ?? 0;
                                            $mScore = $mockScoreMap[$sid][$subject] ?? 0;

                                            if ($scoringMode === 'mock') {
                                                $display = $mScore;
                                                $activeScore = $mScore;
                                                $activeLabel = 'MK';
                                                $activeColor = 'pc-score-purple';
                                                [$activeGrade] = $this->gradeFromScore ?? [null];
                                                if ($isSenior) {
                                                    $aGrade = $activeScore >= 75 ? 'A1' : ($activeScore >= 70 ? 'B2' : ($activeScore >= 65 ? 'B3' : ($activeScore >= 60 ? 'C4' : ($activeScore >= 55 ? 'C5' : ($activeScore >= 50 ? 'C6' : ($activeScore >= 45 ? 'D7' : ($activeScore >= 40 ? 'E8' : ($activeScore > 0 ? 'F9' : '-'))))))));
                                                } else {
                                                    $aGrade = $activeScore >= 70 ? 'A' : ($activeScore >= 60 ? 'B' : ($activeScore >= 50 ? 'C' : ($activeScore >= 40 ? 'D' : ($activeScore > 0 ? 'F' : '-'))));
                                                }
                                                $gradeClass = match(substr($aGrade, 0, 1)) {
                                                    'A' => 'pc-grade-a', 'B' => 'pc-grade-b', 'C' => 'pc-grade-c',
                                                    'D' => 'pc-grade-d', 'E' => 'pc-grade-e', 'F' => 'pc-grade-f',
                                                    default => 'pc-score-muted',
                                                };
                                            } else {
                                                $display = $scoringMode === 'term' ? $tScore : $cScore;
                                                $activeScore = $display;
                                                $activeLabel = $scoringMode === 'term' ? 'T' : 'C';
                                                $activeColor = $scoringMode === 'term' ? 'pc-score-blue' : 'pc-score-purple';
                                                if ($isSenior) {
                                                    $aGrade = $activeScore >= 75 ? 'A1' : ($activeScore >= 70 ? 'B2' : ($activeScore >= 65 ? 'B3' : ($activeScore >= 60 ? 'C4' : ($activeScore >= 55 ? 'C5' : ($activeScore >= 50 ? 'C6' : ($activeScore >= 45 ? 'D7' : ($activeScore >= 40 ? 'E8' : ($activeScore > 0 ? 'F9' : '-'))))))));
                                                } else {
                                                    $aGrade = $activeScore >= 70 ? 'A' : ($activeScore >= 60 ? 'B' : ($activeScore >= 50 ? 'C' : ($activeScore >= 40 ? 'D' : ($activeScore > 0 ? 'F' : '-'))));
                                                }
                                                $gradeClass = match(substr($aGrade, 0, 1)) {
                                                    'A' => 'pc-grade-a', 'B' => 'pc-grade-b', 'C' => 'pc-grade-c',
                                                    'D' => 'pc-grade-d', 'E' => 'pc-grade-e', 'F' => 'pc-grade-f',
                                                    default => 'pc-score-muted',
                                                };
                                            }
                                        @endphp
                                        <div style="background:var(--pc-surface);border:1px solid var(--pc-border);border-radius:8px;padding:6px 8px;text-align:center;">
                                            <div style="font-size:9px;font-weight:700;color:var(--pc-muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:3px;">
                                                {{ Str::limit($subject, 10) }}
                                            </div>
                                            <div>
                                                <span class="pc-score-badge {{ $activeColor }}">{{ number_format($activeScore, 1) }}</span>
                                            </div>
                                            <div style="margin-top:3px;">
                                                <span class="pc-grade-badge {{ $gradeClass }}">{{ $aGrade }}</span>
                                            </div>
                                            @if($bf > 0)
                                                <div style="font-size:8.5px;color:var(--pc-muted);margin-top:3px;">BF: {{ number_format($bf, 1) }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>

                            {{-- Comment --}}
                            <td class="col-left pc-comment-cell">
                                @if($intelligent)
                                    <div class="pc-ai-preview">
                                        <div class="ai-label">
                                            <i class="ri-lightbulb-line"></i> AI SUGGESTED
                                        </div>
                                        {{ Str::limit($intelligent, 110) }}
                                    </div>
                                @endif

                                @if($currentComment)
                                    <div class="pc-saved-preview">
                                        <div class="saved-label">
                                            <i class="ri-chat-check-line"></i> SAVED
                                        </div>
                                        {{ Str::limit($plainComment, 90) }}
                                    </div>
                                @endif

                                <select class="pc-comment-select auto-save-comment"
                                        name="teacher_comments[{{ $sid }}]"
                                        data-student-id="{{ $sid }}"
                                        data-original-value="{{ $plainComment }}">
                                    <option value="">— Select a comment —</option>
                                    @foreach($stdOptions as $opt)
                                        @php $plain = strip_tags($opt); @endphp
                                        <option value="{{ $plain }}" {{ $plainComment === $plain ? 'selected' : '' }}>
                                            {{ Str::limit($plain, 80) }}
                                        </option>
                                    @endforeach
                                    @php
                                        $intPlain  = strip_tags($intelligent);
                                        $stdPlains = array_map('strip_tags', $stdOptions);
                                        $showAI    = $intPlain && !in_array($intPlain, $stdPlains);
                                    @endphp
                                    @if($showAI)
                                        <option value="{{ $intPlain }}"
                                                style="background:#e8f5e8;font-weight:600;"
                                            {{ $plainComment === $intPlain ? 'selected' : '' }}>
                                            💡 Use AI Generated Comment
                                        </option>
                                    @endif
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- SAVE BAR --}}
        <div class="pc-save-bar">
            <div class="pc-save-bar-info">
                <i class="ri-information-line me-1"></i>
                Editing <strong>{{ $students->count() }}</strong> students —
                Mode: <strong>{{ ucfirst($scoringMode) }}</strong>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span id="savingIndicator" style="display:none;color:rgba(255,255,255,.75);font-size:13px;">
                    <i class="ri-loader-4-line spin me-1"></i>Saving…
                </span>
                <button type="submit" class="pc-btn-primary" id="saveAllBtn">
                    <i class="ri-save-line"></i> Save All Comments
                </button>
            </div>
        </div>
    </form>
</div>
@else
<div class="pc-card">
    <div class="pc-empty">
        <div class="pc-empty-icon"><i class="ri-user-unfollow-line"></i></div>
        <h5>No Students Enrolled</h5>
        <p>No students are enrolled in this class for the selected session and term.</p>
    </div>
</div>
@endif

</div>
</div>
</div>

<script>
(function () {
    'use strict';

    const CSRF       = '{{ csrf_token() }}';
    const SAVE_URL   = '{{ route("myprincipalscomment.updateComments", [$schoolclassid, $sessionid, $termid]) }}';

    // Toast
    function toast(msg, type) {
        const c = type === 'success'
            ? { bg: '#ecfdf5', border: '#86efac', text: '#15803d', icon: 'ri-checkbox-circle-fill' }
            : { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', icon: 'ri-error-warning-fill' };
        const el = document.createElement('div');
        el.className = 'pc-toast pc-toast-' + type;
        el.innerHTML = `<i class="${c.icon}" style="font-size:18px;flex-shrink:0;"></i> <div style="flex:1;">${msg}</div>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4200);
    }

    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('tr[data-student-id]').forEach(row => {
                const hay = row.dataset.search || '';
                row.style.display = (!q || hay.includes(q)) ? '' : 'none';
            });
        });
    }

    // Auto-save on change
    document.querySelectorAll('.auto-save-comment').forEach(select => {
        select.addEventListener('change', function () {
            const studentId = this.dataset.studentId;
            const comment   = this.value.trim();
            const original  = this.dataset.originalValue || '';
            if (comment === original) return;

            this.style.borderColor = '#f59e0b';
            this.style.backgroundColor = '#fffbeb';
            this.disabled = true;

            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append(`teacher_comments[${studentId}]`, comment);

            fetch(SAVE_URL, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.dataset.originalValue = comment;
                    this.style.borderColor = '#16a34a';
                    this.style.backgroundColor = '#d1fae5';
                    toast('Comment saved', 'success');
                } else {
                    throw new Error(data.message || 'Save failed');
                }
            })
            .catch(err => {
                this.value = original;
                this.style.borderColor = '#dc2626';
                this.style.backgroundColor = '#fee2e2';
                toast('Failed: ' + err.message, 'error');
            })
            .finally(() => {
                this.disabled = false;
                setTimeout(() => {
                    this.style.borderColor = '';
                    this.style.backgroundColor = '';
                }, 1800);
            });
        });
    });

    // Bulk save
    const commentsForm = document.getElementById('commentsForm');
    if (commentsForm) {
        commentsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('saveAllBtn');
            const ind = document.getElementById('savingIndicator');
            const orig = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line spin me-1"></i>Saving All…';
            ind.style.display = 'inline-block';

            const fd = new FormData();
            fd.append('_token', CSRF);
            document.querySelectorAll('.auto-save-comment').forEach(s => {
                const val = s.value.trim();
                if (val) fd.append(`teacher_comments[${s.dataset.studentId}]`, val);
            });

            fetch(SAVE_URL, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Save failed');
                toast('All comments saved successfully', 'success');
            })
            .catch(err => toast('Error: ' + err.message, 'error'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = orig;
                ind.style.display = 'none';
            });
        });
    }
})();
</script>
@endsection