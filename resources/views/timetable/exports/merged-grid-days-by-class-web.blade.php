<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $pagetitle ?? 'Merged Timetable' }} — Days as Rows (Grouped by Class)</title>
<style>
    :root { --border:#E2E8F0; --ink:#0F172A; --muted:#64748B; }
    * { box-sizing:border-box; }
    body {
        font-family:-apple-system,"Segoe UI",Roboto,sans-serif;
        margin:0; padding:24px; background:#F8FAFC; color:var(--ink);
    }

    .toolbar {
        display:flex; justify-content:space-between; align-items:flex-start;
        margin-bottom:16px; flex-wrap:wrap; gap:12px;
    }
    .toolbar .title-block h1 { font-size:18px; margin:0 0 2px; }
    .toolbar .title-block .school-name {
        font-size:15px; font-weight:600; color:#0F172A; margin:0 0 2px;
    }
    .toolbar .title-block .school-meta {
        font-size:12px; color:var(--muted); line-height:1.4;
    }
    .toolbar .title-block .school-motto { font-style:italic; }
    .toolbar .title-block .doc-sub {
        font-size:12.5px; color:var(--muted); margin-top:4px;
    }

    .btn {
        border:1px solid var(--border); background:#fff; border-radius:8px;
        padding:8px 14px; font-size:13px; cursor:pointer;
    }
    .btn:hover { background:#F1F5F9; }

    .grid-wrap {
        overflow-x:auto; background:#fff;
        border:1px solid var(--border); border-radius:12px;
    }
    table { border-collapse:collapse; width:100%; min-width:900px; }
    th, td { border:1px solid var(--border); padding:6px 8px; vertical-align:top; font-size:12px; }
    thead th {
        background:#1E293B; color:#fff; position:sticky; top:0;
        text-align:center; font-size:11.5px; text-transform:uppercase;
    }
    th.day-col   { width:70px; }
    th.class-col { width:80px; }

    td.day-cell {
        font-weight:700; text-align:center; vertical-align:middle;
        color:#fff; font-size:13px;
        border-right:2px solid #0F172A;
        background:#334155;
    }
    td.class-cell {
        font-weight:700; text-align:center; vertical-align:middle;
        color:#fff; font-size:12px;
        border-right:2px solid #0F172A;
        white-space:nowrap;
    }

    .cell-lesson { padding-left:6px; border-left-width:3px; border-left-style:solid; }
    .cell-lesson .subj { font-weight:700; display:block; }
    .cell-lesson .meta { display:block; color:var(--muted); font-size:11px; margin-top:1px; }

    .cell-break { text-align:center; color:#D97706; font-weight:600; }
    .cell-na    { background:#F8FAFC; }
    .cell-free  { text-align:center; color:#CBD5E1; font-style:italic; }

    .legend { display:flex; flex-wrap:wrap; gap:14px; margin-top:16px; font-size:12px; }
    .legend .item { display:flex; align-items:center; gap:6px; }
    .legend .swatch { width:11px; height:11px; border-radius:3px; display:inline-block; }

    .footer-note { margin-top:10px; font-size:11px; color:#94A3B8; }

    @media print {
        .toolbar .btn { display:none; }
        body { background:#fff; padding:0; }
        .grid-wrap { border:none; border-radius:0; }
        thead th { position:static; }
    }
</style>
</head>
<body>

    <div class="toolbar">
        <div class="title-block">
            @if(!empty($schoolInfo))
                <div class="school-name">{{ $schoolInfo->school_name ?? config('app.name') }}</div>
                @if($schoolInfo->school_address)
                    <div class="school-meta">{{ $schoolInfo->school_address }}</div>
                @endif
                @if($schoolInfo->school_motto)
                    <div class="school-meta school-motto">“{{ $schoolInfo->school_motto }}”</div>
                @endif
                @if(($schoolInfo->formatted_phones ?? '-') !== '-' || $schoolInfo->school_email)
                    <div class="school-meta">
                        @if(($schoolInfo->formatted_phones ?? '-') !== '-')
                            Tel: {{ $schoolInfo->formatted_phones }}
                        @endif
                        @if($schoolInfo->school_email)
                            @if(($schoolInfo->formatted_phones ?? '-') !== '-') &middot; @endif
                            {{ $schoolInfo->school_email }}
                        @endif
                    </div>
                @endif
            @endif
            <h1 style="margin-top:8px;">Merged Timetable — Days as Rows (Grouped by Class)</h1>
            <div class="doc-sub">{{ $sessionName }} &middot; {{ $termName }}</div>
        </div>
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    @if(empty($daySections))
        <div class="grid-wrap" style="padding:40px; text-align:center; color:#94A3B8;">
            No timetables found for this session / term.
        </div>
    @else
        <div class="grid-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="day-col">Day</th>
                        <th class="class-col">Class</th>
                        @foreach($timeSlots as $slot)
                            <th>
                                {{ $slot['label'] }}<br>
                                <span style="font-weight:400;opacity:.75">{{ $slot['start'] }}–{{ $slot['end'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($daySections as $section)
                        @foreach($section['class_rows'] as $rowIdx => $classRow)
                            <tr>
                                @if($rowIdx === 0)
                                    <td class="day-cell"
                                        rowspan="{{ $section['rowspan'] }}"
                                        style="background: {{ $section['day_color'] }};">
                                        {{ $section['day'] }}
                                    </td>
                                @endif

                                <td class="class-cell" style="background: {{ $classRow['class_color'] }};">
                                    {{ $classRow['class'] }}
                                </td>

                                @foreach($classRow['cells'] as $cell)
                                    @if($cell['state'] === 'na')
                                        <td class="cell-na"></td>
                                    @elseif($cell['state'] === 'break')
                                        <td class="cell-break">☕ Break</td>
                                    @elseif($cell['state'] === 'free')
                                        <td class="cell-free">Free</td>
                                    @else
                                        <td>
                                            <div class="cell-lesson"
                                                 style="border-left-color: {{ $classRow['class_color'] }};">
                                                <span class="subj">{{ $cell['subject'] ?? '—' }}</span>
                                                <span class="meta">
                                                    {{ trim(($cell['teacher'] ?? '') . ($cell['room'] ? ' · ' . $cell['room'] : '')) }}
                                                </span>
                                            </div>
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($classList))
        <div class="legend">
            @foreach($classList as $cls)
                <span class="item">
                    <span class="swatch" style="background: {{ $classColors[$cls] ?? '#999' }}"></span>{{ $cls }}
                </span>
            @endforeach
        </div>
    @endif

    <div class="footer-note">Generated {{ $generatedAt }}</div>
</body>
</html>