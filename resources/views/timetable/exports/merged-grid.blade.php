<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 14px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: {{ 9 * ($bodyScale ?? 1.0) }}px; color:#1E293B; }

    /* ── School header ── */
    .school-header { border:2px solid #0f2342; border-radius:6px; overflow:hidden; margin-bottom:8px; }
    .school-header table { width:100%; border-collapse:collapse; }
    .school-header .logo-cell { width:64px; text-align:center; vertical-align:middle; padding:5px; background:#0f2342; }
    .school-header .logo-cell img { width:52px; height:52px; border-radius:50%; object-fit:contain; border:2px solid rgba(255,255,255,.35); }
    .school-header-top { background:#0f2342; color:#fff; }
    .school-header-top .name { font-size: {{ 15 * ($bodyScale ?? 1.0) }}px; font-weight:700; text-transform:uppercase; text-align:center; letter-spacing:1px; }
    .school-header-top .addr { font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; opacity:.85; text-align:center; margin-top:2px; }
    .school-header-top .motto { font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; font-style:italic; opacity:.7; text-align:center; margin-top:2px; }
    .school-header-top .contact { font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; opacity:.85; text-align:center; margin-top:3px; }
    .school-header-top .contact span { display:inline-block; margin:0 6px; }
    .school-header-bottom { background:#1565C0; color:#fff; text-align:center; padding:5px; font-size: {{ 11 * ($bodyScale ?? 1.0) }}px; font-weight:700; letter-spacing:1px; }

    /* ── Run metadata block ── */
    .run-meta-block {
        border: 1px solid #CBD5E1;
        border-left: 3px solid #1565C0;
        border-radius: 4px;
        padding: 6px 10px;
        margin-bottom: 10px;
        background: #F8FAFC;
        font-size: {{ 10 * ($bodyScale ?? 1.0) }}px;
    }
    .run-meta-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        flex-wrap: wrap;
    }
    .run-meta-name {
        font-size: {{ 12 * ($bodyScale ?? 1.0) }}px;
        color: #0f2342;
    }
    .run-meta-code {
        color: #64748B;
        margin-left: 8px;
        font-family: monospace;
        letter-spacing: 0.5px;
    }
    .run-meta-right {
        color: #64748B;
        font-size: {{ 9 * ($bodyScale ?? 1.0) }}px;
    }
    .run-meta-desc {
        margin-top: 4px;
        color: #475569;
        line-height: 1.35;
    }

    /* ── Generation rules appendix ── */
    .run-rules-block {
        border: 1px solid #FDE68A;
        border-radius: 4px;
        padding: 6px 10px;
        margin-bottom: 10px;
        background: #FFFBEB;
        font-size: {{ 9 * ($bodyScale ?? 1.0) }}px;
        color: #92400E;
    }
    .run-rule-item {
        display: inline-block;
        margin-left: 8px;
        margin-right: 2px;
        white-space: nowrap;
    }

    /* ── Class legend ── */
    .legend { display:table; width:100%; margin-bottom:8px; border:1px solid #E2E8F0; border-radius:6px; padding:5px 8px; background:#F8FAFC; }
    .legend-chip { display:inline-block; padding:2px 8px; border-radius:10px; color:#fff; font-size:7.5px; font-weight:700; margin:1px 3px; }

    /* ── Merged grid table ── */
    table.mgrid { width:100%; border-collapse:collapse; table-layout:fixed; }
    table.mgrid th { color:#fff; font-size:9px; text-transform:uppercase; padding:6px 4px; text-align:center; }
    table.mgrid th.period-th { background:#0f2342; width:80px; }
    table.mgrid td { border:1px solid #CBD5E1; padding:3px; vertical-align:top; }
    table.mgrid td.period-col { background:#F8FAFC; text-align:left; padding:5px; }
    .p-name { font-size:8.5px; font-weight:700; }
    .p-time { font-size:7px; color:#94A3B8; }
    .break-cell { background:#FFFBEB; color:#D97706; font-weight:700; font-size:8px; text-align:center; vertical-align:middle; }
    .free-cell { color:#CBD5E1; font-size:8px; text-align:center; vertical-align:middle; }
    .na-cell { color:#E2E8F0; text-align:center; }

    .chip { border-radius:4px; padding:2px 4px; margin-bottom:2px; font-size:7.5px; line-height:1.25; }
    .chip .cls { font-weight:700; color:#fff; }
    .chip .subj { font-weight:700; }
    .chip .tch { opacity:.85; }
</style>
</head>
<body>

@php
    $isVertical = ($orientation ?? 'horizontal') === 'vertical';

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
                @if($logo)
                    <img src="{{ $logo }}" alt="Logo">
                @endif
            </td>
            <td>
                <div class="name">{{ $schoolName }}</div>
                @if($address)
                    <div class="addr">{{ $address }}</div>
                @endif
                @if($motto)
                    <div class="motto">"{{ $motto }}"</div>
                @endif
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

<div class="legend">
    <strong style="font-size:8px;">CLASSES:</strong>
    @foreach($classColors as $cls => $color)
        <span class="legend-chip" style="background:{{ $color }}">{{ $cls }}</span>
    @endforeach
</div>

<table class="mgrid">
    <thead>
        <tr>
            @if ($isVertical)
                <th class="period-th">Day</th>
                @foreach ($rows as $row)
                    <th style="background:#1565C0">
                        {{ $row['label'] }}<br>
                        <span style="font-weight:normal">{{ $row['time'] }}</span>
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
                        <div class="p-name">{{ $day }}</div>
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
                            <td>
                                @foreach($cell['entries'] as $e)
                                    <div class="chip" style="background:{{ $e['color'] }}18;border-left:3px solid {{ $e['color'] }};">
                                        <span class="cls" style="color:{{ $e['color'] }};">{{ $e['class'] }}</span><br>
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
                    <div class="p-name">{{ $row['label'] }}</div>
                    <div class="p-time">{{ $row['time'] }}</div>
                </td>
                @foreach($days as $day)
                    @php $cell = $row['days'][$day] ?? ['entries'=>[],'is_break'=>false,'applicable'=>false]; @endphp
                    @if(!$cell['applicable'])
                        <td class="na-cell">—</td>
                    @elseif($cell['is_break'])
                        <td class="break-cell">☕ Break</td>
                    @elseif(empty($cell['entries']))
                        <td class="free-cell">Free</td>
                    @else
                        <td>
                            @foreach($cell['entries'] as $e)
                                <div class="chip" style="background:{{ $e['color'] }}18;border-left:3px solid {{ $e['color'] }};">
                                    <span class="cls" style="color:{{ $e['color'] }};">{{ $e['class'] }}</span><br>
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

</body>
</html>