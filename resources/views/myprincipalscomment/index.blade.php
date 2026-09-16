{{-- resources/views/myprincipalscomment/index.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
:root {
    --pc-navy:    #0f2342;
    --pc-teal:    #0d9488;
    --pc-sky:     #0ea5e9;
    --pc-amber:   #f59e0b;
    --pc-purple:  #7c3aed;
    --pc-muted:   #64748b;
    --pc-border:  #e2e8f0;
    --pc-surface: #f8fafc;
    --pc-white:   #ffffff;
    --pc-radius:  14px;
    --pc-shadow:  0 2px 12px rgba(15,35,66,.08);
    --pc-shadow-lg:0 8px 28px rgba(15,35,66,.14);
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; }

@keyframes fadeInDown  { from { opacity:0; transform:translateY(-22px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInUp    { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }
@keyframes scaleIn     { from { opacity:0; transform:scale(.9); } to { opacity:1; transform:scale(1); } }
@keyframes rowSlide    { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:translateX(0); } }
@keyframes floatUp     { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-8px); } }

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

/* ══ STATS ══ */
.pc-stat {
    background: var(--pc-white);
    border: 1px solid var(--pc-border);
    border-radius: var(--pc-radius);
    padding: 20px 22px;
    position: relative; overflow: hidden;
    transition: all .35s cubic-bezier(.22,1,.36,1);
    animation: scaleIn .5s cubic-bezier(.22,1,.36,1) both;
}
.pc-stat:hover { transform: translateY(-4px); box-shadow: var(--pc-shadow-lg); }
.pc-stat .stat-accent {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: var(--pc-radius) var(--pc-radius) 0 0;
}
.pc-stat .stat-value { font-size: 28px; font-weight: 700; color: var(--pc-navy); line-height: 1; margin-top: 8px; }
.pc-stat .stat-label { font-size: 12px; color: var(--pc-muted); margin-top: 6px; font-weight: 500; }
.pc-stat .stat-ico {
    font-size: 34px; opacity: .1;
    position: absolute; right: 18px; top: 50%;
    transform: translateY(-50%);
    transition: all .3s ease;
}
.pc-stat:hover .stat-ico { opacity: .18; transform: translateY(-50%) scale(1.1); }

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

/* ══ TABLE ══ */
.pc-table-wrap { overflow-x: auto; }
.pc-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.pc-table thead th {
    background: var(--pc-navy); color: #fff;
    padding: 12px 14px; font-weight: 600; font-size: 11.5px;
    text-transform: uppercase; letter-spacing: .4px;
    text-align: left; white-space: nowrap;
    border: none; position: sticky; top: 0; z-index: 2;
}
.pc-table thead th.col-center { text-align: center; }
.pc-table tbody tr {
    transition: all .22s ease;
    animation: rowSlide .4s ease both;
    border-bottom: 1px solid var(--pc-border);
}
.pc-table tbody tr:nth-child(1) { animation-delay: .05s; }
.pc-table tbody tr:nth-child(2) { animation-delay: .08s; }
.pc-table tbody tr:nth-child(3) { animation-delay: .11s; }
.pc-table tbody tr:nth-child(4) { animation-delay: .14s; }
.pc-table tbody tr:nth-child(5) { animation-delay: .17s; }
.pc-table tbody tr:nth-child(n+6) { animation-delay: .20s; }
.pc-table tbody tr:hover { background: #f0f6ff !important; box-shadow: inset 3px 0 0 var(--pc-purple); }
.pc-table tbody td {
    padding: 12px 14px;
    vertical-align: middle;
    color: #374151;
    border: none;
}

/* ══ Badges ══ */
.pc-class-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 14px;
    background: #ede9fe; color: #5b21b6;
    font-size: 12px; font-weight: 700;
}
.pc-session-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 14px;
    background: #dbeafe; color: #1e40af;
    font-size: 11.5px; font-weight: 600;
}
.pc-term-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 14px;
    background: #dcfce7; color: #15803d;
    font-size: 11.5px; font-weight: 600;
}
.pc-updated-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 14px;
    background: #fef3c7; color: #92400e;
    font-size: 11.5px; font-weight: 600;
}
.pc-never { color: var(--pc-muted); font-size: 11.5px; }

/* ══ Action button ══ */
.pc-action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--pc-purple), #a855f7);
    color: #fff; font-weight: 700; font-size: 12px;
    text-decoration: none;
    transition: all .25s cubic-bezier(.22,1,.36,1);
    border: none;
    cursor: pointer;
}
.pc-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(124,58,237,.4);
    color: #fff;
}

/* ══ Empty ══ */
.pc-empty {
    padding: 80px 24px; text-align: center;
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
}
.pc-empty-icon {
    width: 100px; height: 100px; border-radius: 50%;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 46px; color: var(--pc-purple);
    margin-bottom: 20px;
    animation: floatUp 3s ease-in-out infinite;
}
.pc-empty h5 { font-size: 17px; font-weight: 700; color: var(--pc-navy); margin-bottom: 8px; }
.pc-empty p { color: var(--pc-muted); font-size: 13px; margin-bottom: 0; }

/* ══ Mobile ══ */
@media (max-width: 768px) {
    .pc-hero { padding: 22px; }
    .pc-hero h1 { font-size: 20px; }
    .pc-stat { padding: 16px 18px; }
    .pc-stat .stat-value { font-size: 22px; }
    .pc-table thead th { padding: 10px 8px; font-size: 10px; }
    .pc-table tbody td { padding: 10px 8px; font-size: 11.5px; }
    .pc-table { min-width: 700px; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- HERO --}}
    <div class="pc-hero">
        <h1><i class="ri-chat-quote-line me-2"></i>My Principal's Comment Assignments</h1>
        <p>Manage and enter Principal's comments for assigned classes across sessions and terms.</p>
    </div>

    {{-- STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="pc-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--pc-navy),var(--pc-purple));"></div>
                <div class="stat-ico"><i class="ri-file-list-3-line"></i></div>
                <div class="stat-value">{{ $assignments->count() }}</div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pc-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--pc-sky),#38bdf8);"></div>
                <div class="stat-ico"><i class="ri-calendar-line"></i></div>
                <div class="stat-value text-info">{{ $assignments->pluck('session_name')->filter()->unique()->count() }}</div>
                <div class="stat-label">Unique Sessions</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pc-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--pc-teal),#14b8a6);"></div>
                <div class="stat-ico"><i class="ri-bookmark-line"></i></div>
                <div class="stat-value" style="color:var(--pc-teal);">{{ $assignments->pluck('term_name')->filter()->unique()->count() }}</div>
                <div class="stat-label">Unique Terms</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pc-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--pc-amber),#fcd34d);"></div>
                <div class="stat-ico"><i class="ri-time-line"></i></div>
                <div class="stat-value" style="color:var(--pc-amber);">{{ $assignments->whereNotNull('updated_at')->count() }}</div>
                <div class="stat-label">Recently Updated</div>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="pc-card">
        <div class="pc-card-header">
            <h5>
                <i class="ri-list-check" style="color:var(--pc-purple);"></i>
                My Class Assignments
                <span class="badge" style="background:var(--pc-purple);color:#fff;border-radius:20px;font-size:11px;padding:3px 10px;">
                    {{ $assignments->count() }}
                </span>
            </h5>
        </div>

        @if($assignments->count() > 0)
        <div class="pc-table-wrap">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Class</th>
                        <th>Session</th>
                        <th>Term</th>
                        <th>Last Updated</th>
                        <th class="col-center" style="width:130px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $index => $assignment)
                        <tr>
                            <td style="font-weight:600;color:var(--pc-muted);">{{ $index + 1 }}</td>
                            <td>
                                <span class="pc-class-badge">
                                    <i class="ri-building-line"></i>
                                    {{ $assignment->sclass }} {{ $assignment->schoolarm ?? '' }}
                                </span>
                            </td>
                            <td>
                                <span class="pc-session-badge">
                                    <i class="ri-calendar-line"></i>
                                    {{ $assignment->session_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="pc-term-badge">
                                    <i class="ri-bookmark-line"></i>
                                    {{ $assignment->term_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                @if($assignment->updated_at)
                                    <span class="pc-updated-badge">
                                        <i class="ri-time-line"></i>
                                        {{ \Carbon\Carbon::parse($assignment->updated_at)->format('d M Y, h:i A') }}
                                    </span>
                                @else
                                    <span class="pc-never">Never updated</span>
                                @endif
                            </td>
                            <td class="col-center">
                                <a href="{{ route('myprincipalscomment.classbroadsheet', [
                                    $assignment->schoolclassid,
                                    $assignment->session_id,
                                    $assignment->term_id,
                                ]) }}" class="pc-action-btn">
                                    <i class="ri-edit-line"></i> Open
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="pc-empty">
            <div class="pc-empty-icon">
                <i class="ri-chat-quote-line"></i>
            </div>
            <h5>No Classes Assigned</h5>
            <p>You have not been assigned any class for entering Principal's comments yet.</p>
        </div>
        @endif
    </div>

</div>
</div>
</div>
@endsection