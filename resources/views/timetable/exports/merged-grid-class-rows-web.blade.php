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
.mg-legend-chip { border-radius:14px; padding:4px 12px; font-size:11px; font-weight:700; color:#fff; }

.mg-card { background:#fff; border:1px solid var(--mg-border); border-radius:var(--mg-radius); box-shadow:var(--mg-shadow); overflow:hidden; }
.mg-scroll { overflow:auto; max-height:calc(100vh - 260px); }

table.mg-byclass-rows { width:100%; border-collapse:collapse; font-size:12px; table-layout:fixed; }

table.mg-byclass-rows thead th {
    position:sticky; top:0; z-index:3;
    background:#1E293B; color:#fff; padding:10px 6px;
    font-size:11px; text-transform:uppercase; letter-spacing:.3px;
    text-align:center; border-right:1px solid #0f2342;
    font-weight:700; white-space:nowrap;
}
table.mg-byclass-rows thead th.class-th  { background:#0f2342; width:120px; }
table.mg-byclass-rows thead th.period-th { background:#1E293B; width:90px; }
table.mg-byclass-rows thead th.time-th   { background:#334155; width:100px; font-weight:500; font-size:10.5px; }
table.mg-byclass-rows thead th.monday    { background:#1565C0; }
table.mg-byclass-rows thead th.tuesday   { background:#6A1B9A; }
table.mg-byclass-rows thead th.wednesday { background:#1B5E20; }
table.mg-byclass-rows thead th.thursday  { background:#E65100; }
table.mg-byclass-rows thead th.friday    { background:#880E4F; }

table.mg-byclass-rows td {
    border:1px solid var(--mg-border);
    padding:5px 7px; text-align:center; vertical-align:middle;
    font-size:11.5px; line-height:1.25;
    word-wrap:break-word;
}
table.mg-byclass-rows td.class-cell {
    color:#fff; font-weight:700;
    font-size:12px; text-align:center; vertical-align:middle;
    letter-spacing:.3px;
    position:sticky; left:0; z-index:2;
}
table.mg-byclass-rows td.period-col { background:#F1F5F9; font-weight:700; font-size:11.5px; white-space:nowrap; }
table.mg-byclass-rows td.time-col   { background:#F8FAFC; font-size:10.5px; color:#64748B; white-space:nowrap; }

table.mg-byclass-rows tr.class-sep td { border-top:2px solid #0f2342; }

td.cell-na    { background:#F8FAFC; color:#CBD5E1; }
td.cell-free  { color:#CBD5E1; font-style:italic; font-size:10.5px; }
td.cell-break { background:#FFFBEB; color:#D97706; font-weight:700; font-size:10.5px; }
td.cell-lesson { padding:3px 5px; }
td.cell-lesson .s { font-weight:700; color:#0f2342; font-size:11.5px; }
td.cell-lesson .t { color:#475569; font-size:10.5px; }
td.cell-lesson .r { color:#94A3B8; font-size:10px; }

@media print {
    .no-print { display:none !important; }
    .mg-scroll { max-height:none; overflow:visible; }
    table.mg-byclass-rows thead th { position:static; }
    table.mg-byclass-rows td.class-cell { position:static; }
    .mg-card { box-shadow:none; }
    .mg-hero { display:none; }
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

<div class="mg-hero no-print">
    <div>
        <h1><i class="ri-table-line me-2"></i>Master Timetable — Classes as Rows</h1>
        <p>{{ $schoolInfo->school_name ?? 'School' }} · {{ $sessionName }} · {{ $termName }} · Generated {{ $generatedAt }}</p>
        <p style="margin-top:6px;"><i class="ri-layout-row-line me-1"></i>{{ count($classList) }} classes · each class spans its week horizontally</p>
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
        <span class="mg-legend-chip" style="background:{{ $color }}">{{ $cls }}</span>
    @endforeach
</div>

<div class="mg-card">
    <div class="mg-scroll">
        <table class="mg-byclass-rows">
            <thead>
                <tr>
                    <th class="class-th">Class</th>
                    <th class="period-th">Period</th>
                    <th class="time-th">Time</th>
                    @foreach($days as $day)
                        <th class="{{ strtolower($day) }}">{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    $lastClass = null;
                    $classRowCounts = collect($rows)->groupBy('class')->map->count();
                @endphp
                @foreach($rows as $row)
                    @php
                        $isNewClass = $row['class'] !== $lastClass;
                        $lastClass  = $row['class'];
                        $rowspan    = $isNewClass ? ($classRowCounts[$row['class']] ?? 1) : 0;
                    @endphp
                    <tr class="{{ $isNewClass && !$loop->first ? 'class-sep' : '' }}">
                        @if($isNewClass)
                            <td class="class-cell"
                                rowspan="{{ $rowspan }}"
                                style="background:{{ $row['class_color'] }}">
                                {{ $row['class'] }}
                            </td>
                        @endif
                        <td class="period-col">{{ $row['label'] }}</td>
                        <td class="time-col">{{ $row['time'] }}</td>

                        @foreach($days as $day)
                            @php $cell = $row['cells'][$day] ?? ['state' => 'na']; @endphp
                            @if($cell['state'] === 'na')
                                <td class="cell-na">—</td>
                            @elseif($cell['state'] === 'break')
                                <td class="cell-break">Break</td>
                            @elseif($cell['state'] === 'free')
                                <td class="cell-free">Free</td>
                            @else
                                <td class="cell-lesson"
                                    style="background:{{ $row['class_color'] }}15;border-left:3px solid {{ $row['class_color'] }}">
                                    <div class="s">{{ $cell['subject'] ?? '—' }}</div>
                                    @if(!empty($cell['teacher']))<div class="t">{{ $cell['teacher'] }}</div>@endif
                                    @if(!empty($cell['room']))<div class="r">{{ $cell['room'] }}</div>@endif
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
@endsection