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

.run-meta-block { background:#F8FAFC; border:1px solid #E2E8F0; border-left:4px solid #1565C0; border-radius:10px; padding:12px 16px; margin-bottom:16px; }
.run-meta-header { display:flex; justify-content:space-between; align-items:baseline; gap:12px; flex-wrap:wrap; }
.run-meta-name { font-size:14px; color:#0f2342; }
.run-meta-code { color:#64748B; margin-left:8px; font-family:monospace; font-size:12px; letter-spacing:.5px; }
.run-meta-right { color:#64748B; font-size:12px; }
.run-meta-desc { margin-top:6px; color:#475569; font-size:13px; line-height:1.4; }

.run-rules-block { background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; padding:10px 16px; margin-bottom:16px; font-size:12.5px; color:#92400E; }
.run-rule-item { display:inline-block; margin-left:10px; white-space:nowrap; }

.mg-legend { display:flex; flex-wrap:wrap; gap:6px; background:#fff; border:1px solid var(--mg-border); border-radius:10px; padding:12px 16px; margin-bottom:18px; }
.mg-legend-chip { border-radius:14px; padding:4px 12px; font-size:11px; font-weight:700; color:#fff; cursor:pointer; }
.mg-legend-chip.dimmed { opacity:.25; }

.mg-filter { display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap; align-items:center; }
.mg-filter select { border:1.5px solid var(--mg-border); border-radius:10px; padding:8px 12px; font-size:13px; min-width:200px; }
.mg-filter label { font-size:12px; font-weight:700; color:var(--mg-navy); margin-bottom:0; }

.mg-card { background:#fff; border:1px solid var(--mg-border); border-radius:var(--mg-radius); box-shadow:var(--mg-shadow); overflow:hidden; }
.mg-scroll { overflow:auto; max-height:calc(100vh - 260px); }

table.mg-grid-v { width:100%; border-collapse:collapse; font-size:12px; table-layout:fixed; }

table.mg-grid-v thead th {
    position:sticky; top:0; z-index:3;
    color:#fff; padding:10px 6px;
    font-size:11px; text-transform:uppercase; letter-spacing:.4px;
    text-align:center; border:1px solid #0f2342;
    font-weight:700; line-height:1.35;
}
table.mg-grid-v thead th.day-th { background:#0f2342; width:110px; }
table.mg-grid-v thead th.period-th { background:#1565C0; }
table.mg-grid-v thead th.period-th .period-time {
    display:block; font-weight:400; font-size:10.5px;
    opacity:.85; margin-top:2px; text-transform:none; letter-spacing:0;
}

table.mg-grid-v td {
    border:1px solid var(--mg-border);
    padding:6px; vertical-align:top;
    font-size:11.5px;
}
table.mg-grid-v td.day-cell {
    color:#fff; font-weight:700; font-size:12.5px;
    text-transform:uppercase; letter-spacing:.5px;
    text-align:center; vertical-align:middle;
    position:sticky; left:0; z-index:2;
}
table.mg-grid-v td.break-cell {
    background:#FFFBEB; color:#D97706; font-weight:700;
    text-align:center; vertical-align:middle; font-size:11.5px;
}
table.mg-grid-v td.free-cell {
    color:#CBD5E1; text-align:center; vertical-align:middle;
    font-style:italic; font-size:11px;
}
table.mg-grid-v td.na-cell {
    background:#F8FAFC; color:#E2E8F0;
    text-align:center; vertical-align:middle;
}
table.mg-grid-v td.entries-cell { padding:4px 6px; }

.mg-chip {
    border-radius:6px;
    padding:5px 8px;
    margin-bottom:4px;
    line-height:1.25;
    border-left:3px solid transparent;
    transition: transform .15s ease, box-shadow .15s ease;
}
.mg-chip:last-child { margin-bottom:0; }
.mg-chip:hover { transform:translateX(2px); box-shadow: 0 1px 3px rgba(0,0,0,.08); }

.mg-chip .mg-chip-class {
    font-weight:700;
    font-size:10.5px;
    letter-spacing:.2px;
    margin-bottom:2px;
}
.mg-chip .mg-chip-subj {
    font-weight:700;
    color:#0f2342;
    font-size:11.5px;
}
.mg-chip .mg-chip-meta {
    color:#64748B;
    font-size:10.5px;
    margin-top:1px;
}

.mg-chip.dimmed { opacity:.15; }
.mg-chip.mg-conflict-chip { box-shadow: inset 0 0 0 1px #fecaca; }
.mg-chip.mg-staff-highlight { box-shadow: 0 0 0 2px #dc2626 inset; }

@media print {
    .no-print { display:none !important; }
    .mg-scroll { max-height:none; overflow:visible; }
    table.mg-grid-v thead th { position:static; }
    table.mg-grid-v td.day-cell { position:static; }
    .mg-card { box-shadow:none; }
    .mg-hero { display:none; }
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

<div class="mg-hero no-print">
    <div>
        <h1><i class="ri-layout-row-line me-2"></i>Master Timetable — Days as Rows</h1>
        <p>{{ $schoolInfo->school_name ?? 'School' }} · {{ $sessionName }} · {{ $termName }} · Generated {{ $generatedAt }}</p>
        <p style="margin-top:6px;"><i class="ri-stack-line me-1"></i>Every class stacked inside each (day, period) cell</p>
    </div>
    <button class="mg-print-btn" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print / Save PDF</button>
</div>

@if(!empty($runMeta))
<div class="run-meta-block no-print">
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

@if(!empty($runRules))
<div class="run-rules-block no-print">
    <strong>Generation rules used:</strong>
    @foreach($runRules as $key => $value)
        <span class="run-rule-item">
            {{ $key }} = {{ is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'true' : 'false') : $value) }}
        </span>
    @endforeach
</div>
@endif

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

<div class="mg-card">
    <div class="mg-scroll">
        <table class="mg-grid-v">
            <thead>
                <tr>
                    <th class="day-th">Day</th>
                    @foreach ($rows as $row)
                        <th class="period-th">
                            {{ $row['label'] }}
                            <span class="period-time">{{ $row['time'] }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($days as $day)
                    <tr>
                        <td class="day-cell" style="background:{{ $dayColors[$day] ?? '#0f2342' }};">
                            {{ $day }}
                        </td>
                        @foreach ($rows as $row)
                            @php $cell = $row['days'][$day] ?? ['entries'=>[],'is_break'=>false,'applicable'=>false]; @endphp

                            @if(!$cell['applicable'])
                                <td class="na-cell">—</td>
                            @elseif($cell['is_break'])
                                <td class="break-cell">☕ Break</td>
                            @elseif(empty($cell['entries']))
                                <td class="free-cell">Free</td>
                            @else
                                <td class="entries-cell">
                                    @foreach($cell['entries'] as $e)
                                        <div class="mg-chip {{ !empty($e['is_conflict']) ? 'mg-conflict-chip' : '' }}"
                                             data-cls="{{ $e['class'] }}"
                                             data-teacher-id="{{ $e['teacher_id'] ?? '' }}"
                                             style="background:{{ $e['color'] }}15;border-left:3px solid {{ $e['color'] }};">
                                            <div class="mg-chip-class" style="color:{{ $e['color'] }};">
                                                {{ $e['class'] }}
                                                @if(!empty($e['is_conflict']))
                                                    <i class="ri-alert-line text-danger" style="font-size:10px;" title="Teacher double-booked"></i>
                                                @endif
                                            </div>
                                            <div class="mg-chip-subj">{{ $e['subject'] }}</div>
                                            @if($e['teacher'])
                                                <div class="mg-chip-meta">
                                                    {{ $e['teacher'] }}@if($e['room']) · {{ $e['room'] }}@endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

</div></div></div>

<script>
function mgFilterClass(cls) {
    document.getElementById('mgClassFilter').value = cls;
    document.querySelectorAll('.mg-chip').forEach(chip => {
        chip.classList.toggle('dimmed', !!cls && chip.dataset.cls !== cls);
    });
    document.querySelectorAll('.mg-legend-chip').forEach(chip => {
        chip.classList.toggle('dimmed', !!cls && chip.dataset.cls !== cls);
    });
}
</script>
@endsection