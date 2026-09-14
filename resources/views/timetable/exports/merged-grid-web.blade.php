@extends('layouts.master')

@section('content')
<style>
:root { --mg-navy:#0f2342; --mg-teal:#0d9488; --mg-border:#e2e8f0; --mg-radius:14px; --mg-shadow:0 4px 16px rgba(15,35,66,.10); }
.mg-hero {
    background: linear-gradient(135deg, var(--mg-navy) 0%, #1e4a7e 55%, #0d9488 100%);
    border-radius: var(--mg-radius); padding: 26px 30px; margin-bottom: 20px; color:#fff;
    display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:14px;
}
.mg-hero h1 { font-size:21px; font-weight:700; margin:0 0 6px; }
.mg-hero p  { font-size:13px; opacity:.75; margin:0; }
.mg-print-btn { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3); color:#fff; border-radius:10px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; }
.mg-print-btn:hover { background:rgba(255,255,255,.28); }

/* ── Run metadata block (web) ── */
.run-meta-block {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-left: 4px solid #1565C0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
}
.run-meta-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 12px;
    flex-wrap: wrap;
}
.run-meta-name {
    font-size: 14px;
    color: #0f2342;
}
.run-meta-code {
    color: #64748B;
    margin-left: 8px;
    font-family: monospace;
    font-size: 12px;
    letter-spacing: 0.5px;
}
.run-meta-right {
    color: #64748B;
    font-size: 12px;
}
.run-meta-desc {
    margin-top: 6px;
    color: #475569;
    font-size: 13px;
    line-height: 1.4;
}
.run-rules-block {
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: 10px;
    padding: 10px 16px;
    margin-bottom: 16px;
    font-size: 12.5px;
    color: #92400E;
}
.run-rule-item {
    display: inline-block;
    margin-left: 10px;
    margin-right: 2px;
    white-space: nowrap;
}

.mg-legend { display:flex; flex-wrap:wrap; gap:6px; background:#fff; border:1px solid var(--mg-border); border-radius:10px; padding:12px 16px; margin-bottom:18px; }
.mg-legend-chip { border-radius:14px; padding:4px 12px; font-size:11px; font-weight:700; color:#fff; cursor:pointer; }

.mg-filter { display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap; align-items:center; }
.mg-filter select { border:1.5px solid var(--mg-border); border-radius:10px; padding:8px 12px; font-size:13px; min-width:200px; }
.mg-filter label { font-size:12px; font-weight:700; color:var(--mg-navy); margin-bottom:0; }

.mg-staff-card { background:#fff; border:1px solid var(--mg-border); border-radius:var(--mg-radius); box-shadow:var(--mg-shadow); padding:18px 20px; margin-bottom:18px; }
.mg-staff-card h5 { font-size:14px; font-weight:700; color:var(--mg-navy); margin:0 0 12px; display:flex; align-items:center; gap:8px; }
.mg-staff-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:14px; }
.mg-staff-summary .mini { background:#F8FAFC; border:1px solid var(--mg-border); border-radius:10px; padding:10px 12px; }
.mg-staff-summary .mini .v { font-size:20px; font-weight:700; color:var(--mg-navy); }
.mg-staff-summary .mini .l { font-size:10.5px; color:#94a3b8; text-transform:uppercase; margin-top:2px; }

#mgStaffDetail { display:none; }
#mgStaffDetail.active { display:block; }
.mg-freq-list { display:flex; flex-direction:column; gap:6px; }
.mg-freq-row { display:flex; align-items:center; gap:10px; font-size:12.5px; }
.mg-freq-row .name { flex:1; color:#334155; }
.mg-freq-bar-wrap { flex:2; background:#eef2f7; border-radius:6px; height:8px; overflow:hidden; }
.mg-freq-bar { height:100%; background:linear-gradient(90deg,#0d9488,#0ea5e9); border-radius:6px; }
.mg-freq-count { font-weight:700; color:var(--mg-navy); min-width:22px; text-align:right; }
.mg-staff-cols { display:grid; grid-template-columns:1fr 1fr; gap:22px; }
@media (max-width: 768px) { .mg-staff-cols { grid-template-columns:1fr; } }
.mg-daily-load { display:flex; gap:6px; margin-top:4px; }
.mg-daily-chip { flex:1; text-align:center; border-radius:8px; padding:6px 4px; background:#F8FAFC; border:1px solid var(--mg-border); }
.mg-daily-chip .d { font-size:9.5px; color:#94a3b8; text-transform:uppercase; }
.mg-daily-chip .n { font-size:15px; font-weight:700; color:var(--mg-navy); }
.mg-daily-chip.busiest { background:#FFFBEB; border-color:#fbbf24; }
.mg-conflict-banner { background:#FEF2F2; border:1px solid #fecaca; color:#b91c1c; border-radius:10px; padding:8px 14px; font-size:12.5px; font-weight:600; margin-top:10px; }

.mg-board { width:100%; border-collapse:collapse; font-size:12.5px; }
.mg-board th { text-align:left; padding:8px 10px; font-size:10.5px; text-transform:uppercase; color:#94a3b8; border-bottom:2px solid var(--mg-border); }
.mg-board td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.mg-board tr.mg-board-row { cursor:pointer; }
.mg-board tr.mg-board-row:hover { background:#F8FAFC; }
.mg-board tr.mg-board-selected { background:#EFF6FF; }
.mg-board .conflict-cell { color:#dc2626; font-weight:700; }

.mg-card { background:#fff; border:1px solid var(--mg-border); border-radius:var(--mg-radius); box-shadow:var(--mg-shadow); overflow:hidden; }
table.mg-grid { width:100%; border-collapse:collapse; font-size:12px; }
table.mg-grid th { background:#0f2342; color:#fff; padding:10px 6px; text-align:center; font-size:11px; text-transform:uppercase; }
table.mg-grid th.period-th { width:110px; }
table.mg-grid td { border:1px solid var(--mg-border); padding:6px; vertical-align:top; }
table.mg-grid td.period-col { background:#F8FAFC; text-align:left; font-weight:700; white-space:nowrap; }
table.mg-grid.is-vertical td.period-col { white-space: nowrap; min-width: 90px; color: #fff; }
.mg-ptime { font-weight:400; font-size:10.5px; color:#94a3b8; }
.mg-break { background:#FFFBEB; color:#d97706; font-weight:700; font-size:11px; text-align:center; }
.mg-free  { color:#cbd5e1; font-size:11px; text-align:center; }
.mg-na    { color:#e2e8f0; text-align:center; }

.mg-chip { border-radius:6px; padding:4px 8px; margin-bottom:4px; font-size:11px; line-height:1.3; transition:transform .15s ease, box-shadow .15s ease; border-left:3px solid transparent; }
.mg-chip:hover { transform:translateX(2px); }
.mg-chip .cls { font-weight:700; }
.mg-chip .subj { font-weight:700; color:#0f2342; }
.mg-chip .tch { color:#64748b; font-size:10px; }
.mg-chip.dimmed { opacity:.15; }
.mg-chip.mg-conflict-chip { box-shadow: inset 0 0 0 1px #fecaca; }
.mg-chip.mg-staff-highlight { box-shadow: 0 0 0 2px #dc2626 inset; }

@media print {
    .no-print { display:none !important; }
    .mg-card { box-shadow:none; }
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

<div class="mg-hero">
    <div>
        <h1><i class="ri-layout-grid-line me-2"></i>Master Timetable — All Classes Merged</h1>
        <p>{{ $schoolInfo->school_name ?? 'School' }} · {{ $sessionName }} · {{ $termName }} · Generated {{ $generatedAt }}</p>
        <p style="margin-top:6px;"><i class="ri-layout-column-line me-1"></i>{{ ucfirst($orientation ?? 'horizontal') }} layout</p>
    </div>
    <button class="mg-print-btn no-print" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print / Save PDF</button>
</div>

{{-- Run metadata block — only present when exporting a saved generation run. --}}
@if(!empty($runMeta))
<div class="run-meta-block">
    <div class="run-meta-header">
        <div>
            <strong class="run-meta-name">{{ $runMeta['name'] }}</strong>
            <span class="run-meta-code">Run {{ $runMeta['run_code'] }}</span>
        </div>
        <div class="run-meta-right">
            {{ $runMeta['creator'] }} · {{ $runMeta['created_at'] }}
            @if($runMeta['seed']) · seed {{ $runMeta['seed'] }} @endif
        </div>
    </div>
    @if(!empty($runMeta['description']))
        <div class="run-meta-desc">{{ $runMeta['description'] }}</div>
    @endif
</div>
@endif

{{-- Advanced-rules appendix — only when the caller requested it. --}}
@if(!empty($runRules))
<div class="run-rules-block">
    <strong>Generation rules used:</strong>
    @foreach($runRules as $key => $value)
        <span class="run-rule-item">
            {{ $key }} =
            {{ is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'true' : 'false') : $value) }}
        </span>
    @endforeach
</div>
@endif

<div class="mg-staff-card no-print">
    <h5><i class="ri-bar-chart-grouped-line"></i>Staff Analysis</h5>

    <div class="mg-filter">
        <label for="mgStaffFilter">Select staff:</label>
        <select id="mgStaffFilter" onchange="mgStaffSelect(this.value)">
            <option value="">— Show all classes (no staff selected) —</option>
            @foreach(($staffAnalytics['staff'] ?? []) as $s)
                <option value="{{ $s['id'] }}">{{ $s['name'] }} ({{ $s['total_periods'] }} periods{{ $s['conflict_count'] > 0 ? ', ' . $s['conflict_count'] . ' conflict(s)' : '' }})</option>
            @endforeach
        </select>
    </div>

    @if(!empty($staffAnalytics['summary']))
    <div class="mg-staff-summary">
        <div class="mini"><div class="v">{{ $staffAnalytics['summary']['total_staff'] ?? 0 }}</div><div class="l">Staff Scheduled</div></div>
        <div class="mini"><div class="v">{{ $staffAnalytics['summary']['avg_periods_per_staff'] ?? 0 }}</div><div class="l">Avg Periods / Staff</div></div>
        <div class="mini"><div class="v">{{ $staffAnalytics['summary']['busiest_staff']['name'] ?? '—' }}</div><div class="l">Busiest ({{ $staffAnalytics['summary']['busiest_staff']['periods'] ?? 0 }} periods)</div></div>
        <div class="mini"><div class="v" style="color:{{ ($staffAnalytics['summary']['most_conflicted_staff']['conflicts'] ?? 0) > 0 ? '#dc2626' : '#16a34a' }}">
            {{ $staffAnalytics['summary']['most_conflicted_staff']['name'] ?? 'None' }}
        </div><div class="l">Most Conflicted{{ isset($staffAnalytics['summary']['most_conflicted_staff']) ? ' (' . $staffAnalytics['summary']['most_conflicted_staff']['conflicts'] . ')' : '' }}</div></div>
    </div>
    @endif

    <div id="mgStaffDetail">
        <div class="mg-staff-cols">
            <div>
                <h6 style="font-size:12.5px;font-weight:700;color:#334155;">Classes &amp; Arms (frequency)</h6>
                <div class="mg-freq-list" id="mgStaffClasses"></div>
            </div>
            <div>
                <h6 style="font-size:12.5px;font-weight:700;color:#334155;">Subjects Taught (frequency)</h6>
                <div class="mg-freq-list" id="mgStaffSubjects"></div>
            </div>
        </div>
        <h6 style="font-size:12.5px;font-weight:700;color:#334155;margin-top:16px;">Daily Load</h6>
        <div class="mg-daily-load" id="mgStaffDailyLoad"></div>
        <div id="mgStaffConflictBanner"></div>
    </div>

    <h6 style="font-size:12.5px;font-weight:700;color:#334155;margin-top:18px;">All Staff — Workload Leaderboard</h6>
    <div style="overflow-x:auto;">
        <table class="mg-board">
            <thead>
                <tr>
                    <th>Staff</th><th>Total Periods</th><th>Classes</th>
                    <th>Subjects</th><th>Busiest Day</th><th>Conflicts</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($staffAnalytics['staff'] ?? []) as $s)
                    <tr class="mg-board-row" data-staff-id="{{ $s['id'] }}" onclick="mgStaffSelect('{{ $s['id'] }}')">
                        <td>{{ $s['name'] }}</td>
                        <td>{{ $s['total_periods'] }}</td>
                        <td>{{ $s['class_count'] }}</td>
                        <td>{{ $s['subject_count'] }}</td>
                        <td>{{ $s['busiest_day'] ?? '—' }}</td>
                        <td class="{{ $s['conflict_count'] > 0 ? 'conflict-cell' : '' }}">{{ $s['conflict_count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center py-3">No staff scheduled in this scope yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mg-legend no-print">
    <strong style="font-size:12px;color:#0f2342;margin-right:6px;">Classes:</strong>
    @foreach($classColors as $cls => $color)
        <span class="mg-legend-chip" data-cls="{{ $cls }}" style="background:{{ $color }};" onclick="mgFilterClass('{{ $cls }}')">{{ $cls }}</span>
    @endforeach
</div>

<div class="mg-filter no-print">
    <select id="mgClassFilter" onchange="mgFilterClass(this.value)">
        <option value="">Show all classes</option>
        @foreach($classList as $cls)
            <option value="{{ $cls }}">{{ $cls }}</option>
        @endforeach
    </select>
</div>

@php $isVertical = ($orientation ?? 'horizontal') === 'vertical'; @endphp

<div class="mg-card">
    <div style="overflow-x:auto;">
        <table class="mg-grid {{ $isVertical ? 'is-vertical' : '' }}">
            <thead>
                <tr>
                    @if ($isVertical)
                        <th class="period-th">Day</th>
                        @foreach ($rows as $row)
                            <th style="background:#1565C0">
                                {{ $row['label'] }}<br>
                                <span style="font-weight:normal;font-size:10px;">{{ $row['time'] }}</span>
                            </th>
                        @endforeach
                    @else
                        <th class="period-th">Period</th>
                        @foreach ($days as $day)
                            <th style="background:{{ $dayColors[$day] ?? '#1565C0' }}">{{ $day }}</th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @if ($isVertical)
                    @foreach ($days as $day)
                        <tr>
                            <td class="period-col" style="background:{{ $dayColors[$day] ?? '#0f2342' }};color:#fff;">
                                {{ $day }}
                            </td>
                            @foreach ($rows as $row)
                                @php $cell = $row['days'][$day] ?? ['entries'=>[],'is_break'=>false,'applicable'=>false]; @endphp
                                @if(!$cell['applicable'])
                                    <td class="mg-na">—</td>
                                @elseif($cell['is_break'])
                                    <td class="mg-break">☕ Break</td>
                                @elseif(empty($cell['entries']))
                                    <td class="mg-free">Free</td>
                                @else
                                    <td>
                                        @foreach($cell['entries'] as $e)
                                            <div class="mg-chip {{ !empty($e['is_conflict']) ? 'mg-conflict-chip' : '' }}"
                                                 data-cls="{{ $e['class'] }}"
                                                 data-teacher-id="{{ $e['teacher_id'] ?? '' }}"
                                                 style="background:{{ $e['color'] }}18;border-left:3px solid {{ $e['color'] }};">
                                                <span class="cls" style="color:{{ $e['color'] }};">{{ $e['class'] }}</span>
                                                @if(!empty($e['is_conflict']))<i class="ri-alert-line text-danger" style="font-size:10px;" title="Teacher double-booked"></i>@endif
                                                <br>
                                                <span class="subj">{{ $e['subject'] }}</span>
                                                @if($e['teacher'])<span class="tch"> · {{ $e['teacher'] }}</span>@endif
                                                @if($e['room'])<span class="tch"> · {{ $e['room'] }}</span>@endif
                                            </div>
                                        @endforeach
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @else
                    @foreach($rows as $row)
                    <tr>
                        <td class="period-col">
                            {{ $row['label'] }}<br><span class="mg-ptime">{{ $row['time'] }}</span>
                        </td>
                        @foreach($days as $day)
                            @php $cell = $row['days'][$day] ?? ['entries'=>[],'is_break'=>false,'applicable'=>false]; @endphp
                            @if(!$cell['applicable'])
                                <td class="mg-na">—</td>
                            @elseif($cell['is_break'])
                                <td class="mg-break">☕ Break</td>
                            @elseif(empty($cell['entries']))
                                <td class="mg-free">Free</td>
                            @else
                                <td>
                                    @foreach($cell['entries'] as $e)
                                        <div class="mg-chip {{ !empty($e['is_conflict']) ? 'mg-conflict-chip' : '' }}"
                                             data-cls="{{ $e['class'] }}"
                                             data-teacher-id="{{ $e['teacher_id'] ?? '' }}"
                                             style="background:{{ $e['color'] }}18;border-left:3px solid {{ $e['color'] }};">
                                            <span class="cls" style="color:{{ $e['color'] }};">{{ $e['class'] }}</span>
                                            @if(!empty($e['is_conflict']))<i class="ri-alert-line text-danger" style="font-size:10px;" title="Teacher double-booked"></i>@endif
                                            <br>
                                            <span class="subj">{{ $e['subject'] }}</span>
                                            @if($e['teacher'])<span class="tch"> · {{ $e['teacher'] }}</span>@endif
                                            @if($e['room'])<span class="tch"> · {{ $e['room'] }}</span>@endif
                                        </div>
                                    @endforeach
                                </td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

</div></div></div>

<script>
window.STAFF_ANALYTICS = @json(collect($staffAnalytics['staff'] ?? [])->keyBy('id'));

function mgFilterClass(cls) {
    document.getElementById('mgClassFilter').value = cls;
    document.querySelectorAll('.mg-chip').forEach(chip => {
        chip.classList.toggle('dimmed', !!cls && chip.dataset.cls !== cls);
    });
}

function mgStaffSelect(staffId) {
    document.getElementById('mgStaffFilter').value = staffId || '';

    document.querySelectorAll('.mg-chip').forEach(chip => {
        const matches = !!staffId && String(chip.dataset.teacherId) === String(staffId);
        chip.classList.toggle('mg-staff-highlight', matches);
        chip.classList.toggle('dimmed', !!staffId && !matches);
    });

    document.querySelectorAll('.mg-board-row').forEach(row => {
        row.classList.toggle('mg-board-selected', String(row.dataset.staffId) === String(staffId));
    });

    const detail = document.getElementById('mgStaffDetail');
    if (!staffId || !window.STAFF_ANALYTICS[staffId]) {
        detail.classList.remove('active');
        return;
    }

    const s = window.STAFF_ANALYTICS[staffId];
    detail.classList.add('active');

    const renderFreq = (obj, max) => Object.entries(obj || {}).map(([name, count]) => `
        <div class="mg-freq-row">
            <span class="name">${name}</span>
            <div class="mg-freq-bar-wrap"><div class="mg-freq-bar" style="width:${max ? Math.round((count / max) * 100) : 0}%"></div></div>
            <span class="mg-freq-count">${count}</span>
        </div>
    `).join('') || '<div class="text-muted small">None</div>';

    const classMax = Math.max(0, ...Object.values(s.classes || {}));
    const subjectMax = Math.max(0, ...Object.values(s.subjects || {}));
    document.getElementById('mgStaffClasses').innerHTML = renderFreq(s.classes, classMax);
    document.getElementById('mgStaffSubjects').innerHTML = renderFreq(s.subjects, subjectMax);

    const days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    document.getElementById('mgStaffDailyLoad').innerHTML = days.map(d => `
        <div class="mg-daily-chip ${d === s.busiest_day ? 'busiest' : ''}">
            <div class="d">${d.slice(0,3)}</div>
            <div class="n">${(s.daily_load && s.daily_load[d]) || 0}</div>
        </div>
    `).join('');

    document.getElementById('mgStaffConflictBanner').innerHTML = s.conflict_count > 0
        ? `<div class="mg-conflict-banner"><i class="ri-alert-line me-1"></i>${s.name} has ${s.conflict_count} period(s) with a genuine double-booking across classes — check the highlighted chips above.</div>`
        : '';
}
</script>
@endsection