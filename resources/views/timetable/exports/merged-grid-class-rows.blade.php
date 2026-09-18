<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 12px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; color:#1E293B; }

    .school-header { border:2px solid #0f2342; border-radius:6px; overflow:hidden; margin-bottom:8px; }
    .school-header table { width:100%; border-collapse:collapse; }
    .school-header .logo-cell { width:64px; text-align:center; vertical-align:middle; padding:5px; background:#0f2342; }
    .school-header .logo-cell img { width:52px; height:52px; border-radius:50%; object-fit:contain; border:2px solid rgba(255,255,255,.35); }
    .school-header-top { background:#0f2342; color:#fff; }
    .school-header-top .name { font-size: {{ 14 * ($bodyScale ?? 1.0) }}px; font-weight:700; text-transform:uppercase; text-align:center; letter-spacing:1px; }
    .school-header-top .addr { font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; opacity:.85; text-align:center; margin-top:2px; }
    .school-header-top .motto { font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; font-style:italic; opacity:.7; text-align:center; margin-top:2px; }
    .school-header-top .contact { font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; opacity:.85; text-align:center; margin-top:3px; }
    .school-header-top .contact span { display:inline-block; margin:0 6px; }
    .school-header-bottom { background:#1565C0; color:#fff; text-align:center; padding:5px; font-size: {{ 11 * ($bodyScale ?? 1.0) }}px; font-weight:700; letter-spacing:1px; }

    .run-meta-block { border: 1px solid #CBD5E1; border-left: 3px solid #1565C0; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; background: #F8FAFC; font-size: {{ 10 * ($bodyScale ?? 1.0) }}px; }
    .run-meta-header { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; flex-wrap: wrap; }
    .run-meta-name { font-size: {{ 12 * ($bodyScale ?? 1.0) }}px; color: #0f2342; }
    .run-meta-code { color: #64748B; margin-left: 8px; font-family: monospace; letter-spacing: 0.5px; }
    .run-meta-right { color: #64748B; font-size: {{ 9 * ($bodyScale ?? 1.0) }}px; }
    .run-meta-desc { margin-top: 4px; color: #475569; line-height: 1.35; }

    .run-rules-block { border: 1px solid #FDE68A; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; background: #FFFBEB; font-size: {{ 9 * ($bodyScale ?? 1.0) }}px; color: #92400E; }
    .run-rule-item { display: inline-block; margin-left: 8px; margin-right: 2px; white-space: nowrap; }

    .legend { display:table; width:100%; margin-bottom:8px; border:1px solid #E2E8F0; border-radius:6px; padding:5px 8px; background:#F8FAFC; }
    .legend-chip { display:inline-block; padding:2px 8px; border-radius:10px; color:#fff; font-size:7.5px; font-weight:700; margin:1px 3px; }

    table.byclass-rows { width:100%; border-collapse:collapse; table-layout:fixed; }
    table.byclass-rows th {
        color:#fff; font-size: {{ 7.5 * ($bodyScale ?? 1.0) }}px;
        padding:5px 2px; text-align:center; border:1px solid #0f2342;
        font-weight:700; word-wrap:break-word;
    }
    table.byclass-rows th.class-th  { background:#0f2342; }
    table.byclass-rows th.period-th { background:#334155; }
    table.byclass-rows th.time-th   { background:#475569; font-weight:400; font-size: {{ 6.5 * ($bodyScale ?? 1.0) }}px; }
    table.byclass-rows th.day-th    { background:#1565C0; }
    table.byclass-rows th.day-th.tuesday   { background:#6A1B9A; }
    table.byclass-rows th.day-th.wednesday { background:#1B5E20; }
    table.byclass-rows th.day-th.thursday  { background:#E65100; }
    table.byclass-rows th.day-th.friday    { background:#880E4F; }

    table.byclass-rows td {
        border:1px solid #CBD5E1; padding:2px; vertical-align:middle;
        text-align:center; font-size: {{ 7 * ($bodyScale ?? 1.0) }}px;
        word-wrap:break-word;
    }
    table.byclass-rows td.class-cell {
        font-weight:700; color:#fff;
        font-size: {{ 9 * ($bodyScale ?? 1.0) }}px;
        text-align:center; vertical-align:middle;
        letter-spacing:.3px;
    }
    table.byclass-rows td.period-col { background:#F1F5F9; font-weight:700; font-size: {{ 7.5 * ($bodyScale ?? 1.0) }}px; }
    table.byclass-rows td.time-col   { background:#F8FAFC; font-size: {{ 6.5 * ($bodyScale ?? 1.0) }}px; color:#64748B; }

    table.byclass-rows td.cell-na    { background:#F8FAFC; color:#E2E8F0; }
    table.byclass-rows td.cell-free  { color:#CBD5E1; font-style:italic; }
    table.byclass-rows td.cell-break { background:#FFFBEB; color:#D97706; font-weight:700; font-size: {{ 6.5 * ($bodyScale ?? 1.0) }}px; }
    table.byclass-rows td.cell-lesson { padding:2px; }
    table.byclass-rows td.cell-lesson .s { font-weight:700; color:#0f2342; font-size: {{ 7.5 * ($bodyScale ?? 1.0) }}px; line-height:1.15; }
    table.byclass-rows td.cell-lesson .t { color:#475569; font-size: {{ 6.5 * ($bodyScale ?? 1.0) }}px; line-height:1.15; }
    table.byclass-rows td.cell-lesson .r { color:#94A3B8; font-size: {{ 6 * ($bodyScale ?? 1.0) }}px; line-height:1.15; }

    table.byclass-rows tr.class-sep td { border-top:2px solid #0f2342; }
</style>
</head>
<body>

@php
    $schoolInfo = $schoolInfo ?? (object) [];
    $logo       = $schoolInfo->logo_base64 ?? null;
    $schoolName = $schoolInfo->school_name  ?? 'School';
    $address    = $schoolInfo->school_address ?? null;
    $motto      = $schoolInfo->school_motto   ?? null;
    $phones     = $schoolInfo->formatted_phones ?? null;
    $email      = $schoolInfo->school_email   ?? null;
    $website    = $schoolInfo->school_website ?? null;

    $classRowCounts = collect($rows)->groupBy('class')->map->count();
@endphp

<div class="school-header">
    <table>
        <tr class="school-header-top">
            <td class="logo-cell">
                @if($logo)<img src="{{ $logo }}" alt="Logo">@endif
            </td>
            <td>
                <div class="name">{{ $schoolName }}</div>
                @if($address)<div class="addr">{{ $address }}</div>@endif
                @if($motto)<div class="motto">"{{ $motto }}"</div>@endif
                @if($phones || $email || $website)
                    <div class="contact">
                        @if($phones && $phones !== '-')<span>📞 {{ $phones }}</span>@endif
                        @if($email)<span>✉ {{ $email }}</span>@endif
                        @if($website)<span>🌐 {{ $website }}</span>@endif
                    </div>
                @endif
            </td>
            <td style="width:64px;"></td>
        </tr>
    </table>
    <div class="school-header-bottom">
        Master Timetable — Classes as Rows — {{ $sessionName }} · {{ $termName }}
        @if(!empty($paperSize)) · {{ strtoupper($paperSize) }} @endif
    </div>
</div>

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

@if(!empty($runRules))
<div class="run-rules-block">
    <strong>Generation rules used:</strong>
    @foreach($runRules as $key => $value)
        <span class="run-rule-item">
            {{ $key }} = {{ is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'true' : 'false') : $value) }}
        </span>
    @endforeach
</div>
@endif

<div class="legend">
    <strong style="font-size:8px;">CLASSES:</strong>
    @foreach($classColors as $cls => $color)
        <span class="legend-chip" style="background:{{ $color }}">{{ $cls }}</span>
    @endforeach
</div>

<table class="byclass-rows">
    <thead>
        <tr>
            <th class="class-th" style="width:70px">Class</th>
            <th class="period-th" style="width:70px">Period</th>
            <th class="time-th" style="width:60px">Time</th>
            @foreach($days as $day)
                <th class="day-th {{ strtolower($day) }}">{{ $day }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php $lastClass = null; @endphp
        @foreach($rows as $row)
            @php
                $isNewClass = $row['class'] !== $lastClass;
                $lastClass  = $row['class'];
            @endphp
            <tr class="{{ $isNewClass && !$loop->first ? 'class-sep' : '' }}">
                @if($isNewClass)
                    <td class="class-cell"
                        rowspan="{{ $classRowCounts[$row['class']] ?? 1 }}"
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

</body>
</html>