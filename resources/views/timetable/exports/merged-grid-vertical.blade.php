<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 12px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; color:#1E293B; }

    /* ── School header ── */
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

    /* ── Run metadata ── */
    .run-meta-block { border: 1px solid #CBD5E1; border-left: 3px solid #1565C0; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; background: #F8FAFC; font-size: {{ 10 * ($bodyScale ?? 1.0) }}px; }
    .run-meta-header { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; flex-wrap: wrap; }
    .run-meta-name { font-size: {{ 12 * ($bodyScale ?? 1.0) }}px; color: #0f2342; }
    .run-meta-code { color: #64748B; margin-left: 8px; font-family: monospace; letter-spacing: 0.5px; }
    .run-meta-right { color: #64748B; font-size: {{ 9 * ($bodyScale ?? 1.0) }}px; }
    .run-meta-desc { margin-top: 4px; color: #475569; line-height: 1.35; }

    /* ── Generation rules appendix ── */
    .run-rules-block { border: 1px solid #FDE68A; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; background: #FFFBEB; font-size: {{ 9 * ($bodyScale ?? 1.0) }}px; color: #92400E; }
    .run-rule-item { display: inline-block; margin-left: 8px; margin-right: 2px; white-space: nowrap; }

    /* ── Class legend ── */
    .legend { display:table; width:100%; margin-bottom:8px; border:1px solid #E2E8F0; border-radius:6px; padding:5px 8px; background:#F8FAFC; }
    .legend-chip { display:inline-block; padding:2px 8px; border-radius:10px; color:#fff; font-size:7.5px; font-weight:700; margin:1px 3px; }

    /* ── Days-as-rows grid ── */
    table.mgrid-v { width:100%; border-collapse:collapse; table-layout:fixed; }
    table.mgrid-v th {
        color:#fff; font-size: {{ 8.5 * ($bodyScale ?? 1.0) }}px;
        text-transform:uppercase; letter-spacing:.3px;
        padding:6px 3px; text-align:center;
        border:1px solid #0f2342; font-weight:700;
    }
    table.mgrid-v th.day-th { background:#0f2342; width:70px; }
    table.mgrid-v th.period-th { background:#1565C0; }
    table.mgrid-v th .period-time {
        display:block; font-weight:400; font-size: {{ 7 * ($bodyScale ?? 1.0) }}px;
        opacity:.85; margin-top:2px; text-transform:none; letter-spacing:0;
    }

    table.mgrid-v td {
        border:1px solid #CBD5E1; padding:3px; vertical-align:top;
        font-size: {{ 7 * ($bodyScale ?? 1.0) }}px;
    }
    table.mgrid-v td.day-cell {
        color:#fff; font-weight:700; font-size: {{ 9 * ($bodyScale ?? 1.0) }}px;
        text-transform:uppercase; letter-spacing:.4px;
        text-align:center; vertical-align:middle;
    }
    table.mgrid-v td.break-cell {
        background:#FFFBEB; color:#D97706; font-weight:700;
        text-align:center; vertical-align:middle; font-size: {{ 7 * ($bodyScale ?? 1.0) }}px;
    }
    table.mgrid-v td.free-cell {
        color:#CBD5E1; text-align:center; vertical-align:middle;
        font-style:italic; font-size: {{ 7 * ($bodyScale ?? 1.0) }}px;
    }
    table.mgrid-v td.na-cell { background:#F8FAFC; color:#E2E8F0; text-align:center; vertical-align:middle; }
    table.mgrid-v td.entries-cell { padding:3px; }

    /* ── Per-class chip ── */
    .chip {
        border-radius: 4px;
        padding: 3px 5px;
        margin-bottom: 3px;
        line-height: 1.25;
        border-left: 3px solid;
    }
    .chip:last-child { margin-bottom: 0; }
    .chip .chip-class {
        font-weight: 700;
        font-size: {{ 6.5 * ($bodyScale ?? 1.0) }}px;
        letter-spacing: .2px;
        margin-bottom: 1px;
    }
    .chip .chip-subj {
        font-weight: 700;
        color: #0f2342;
        font-size: {{ 7.5 * ($bodyScale ?? 1.0) }}px;
    }
    .chip .chip-meta {
        color: #64748B;
        font-size: {{ 6.5 * ($bodyScale ?? 1.0) }}px;
        margin-top: 1px;
    }
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
        Master Timetable (All Classes) — {{ $sessionName }} · {{ $termName }}
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

<table class="mgrid-v">
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
                                <div class="chip"
                                     style="background:{{ $e['color'] }}15;border-left:3px solid {{ $e['color'] }};">
                                    <div class="chip-class" style="color:{{ $e['color'] }};">
                                        {{ $e['class'] }}
                                        @if(!empty($e['is_conflict'])) ⚠ @endif
                                    </div>
                                    <div class="chip-subj">{{ $e['subject'] }}</div>
                                    @if($e['teacher'])
                                        <div class="chip-meta">
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

</body>
</html>