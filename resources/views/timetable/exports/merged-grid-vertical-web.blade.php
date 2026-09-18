<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $pagetitle ?? 'Merged Timetable' }} — Days as Rows</title>
<style>
    :root { --border:#E2E8F0; }
    * { box-sizing:border-box; }
    body { font-family: -apple-system, "Segoe UI", Roboto, sans-serif; margin:0; padding:24px; background:#F8FAFC; color:#1E293B; }
    .toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px; }
    .toolbar h1 { font-size:18px; margin:0; }
    .toolbar .sub { font-size:13px; color:#64748B; }
    .btn { border:1px solid var(--border); background:#fff; border-radius:8px; padding:8px 14px; font-size:13px; cursor:pointer; }
    .btn:hover { background:#F1F5F9; }
    .grid-wrap { overflow-x:auto; background:#fff; border:1px solid var(--border); border-radius:12px; }
    table { border-collapse:collapse; width:100%; min-width:900px; }
    th, td { border:1px solid var(--border); padding:8px; vertical-align:top; font-size:12px; }
    thead th { background:#1E293B; color:#fff; position:sticky; top:0; text-align:left; }
    th.day-col, td.day-col { width:90px; text-align:center; font-weight:700; color:#fff; }
    .entry { border-left:3px solid #999; padding:4px 6px; margin-bottom:4px; border-radius:4px; background:#F8FAFC; }
    .entry.conflict { background:#FEF2F2; border-left-color:#EF4444 !important; }
    .entry .cls { font-weight:700; display:block; }
    .entry .subj { display:block; }
    .entry .meta { color:#64748B; font-size:11px; }
    .cell-break { text-align:center; color:#D97706; font-weight:600; }
    .cell-na { background:#F8FAFC; }
    .legend { display:flex; flex-wrap:wrap; gap:12px; margin-top:16px; font-size:12px; }
    .legend .item { display:flex; align-items:center; gap:5px; }
    .swatch { width:10px; height:10px; border-radius:3px; display:inline-block; }
    .footer-note { margin-top:10px; font-size:11px; color:#94A3B8; }
    @media print {
        .toolbar .btn { display:none; }
        body { background:#fff; padding:0; }
    }
</style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>{{ $pagetitle ?? 'Merged Timetable' }} — Days as Rows</h1>
            <div class="sub">{{ $sessionName }} &middot; {{ $termName }}</div>
        </div>
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <div class="grid-wrap">
    <table>
        <thead>
            <tr>
                <th class="day-col">Day</th>
                @foreach($rows as $row)
                    <th>{{ $row['label'] }}<br><span style="font-weight:400;opacity:.8">{{ $row['time'] }}</span></th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($days as $day)
                <tr>
                    <td class="day-col" style="background: {{ $dayColors[$day] ?? '#334155' }};">{{ $day }}</td>
                    @foreach($rows as $row)
                        @php $cell = $row['days'][$day] ?? null; @endphp
                        @if(!$cell || !($cell['applicable'] ?? false))
                            <td class="cell-na"></td>
                        @elseif(!empty($cell['is_break']))
                            <td class="cell-break">☕ Break</td>
                        @elseif(empty($cell['entries']))
                            <td></td>
                        @else
                            <td>
                                @foreach($cell['entries'] as $e)
                                    <div class="entry {{ !empty($e['is_conflict']) ? 'conflict' : '' }}" style="border-left-color: {{ $e['color'] ?? '#999' }}">
                                        <span class="cls">{{ $e['class'] }}</span>
                                        <span class="subj">{{ $e['subject'] }}</span>
                                        <span class="meta">{{ trim(($e['teacher'] ?? '') . ($e['room'] ? ' · ' . $e['room'] : '')) }}</span>
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

    <div class="legend">
        @foreach($classList as $cls)
            <span class="item"><span class="swatch" style="background: {{ $classColors[$cls] ?? '#999' }}"></span>{{ $cls }}</span>
        @endforeach
    </div>

    <div class="footer-note">Generated {{ $generatedAt }}</div>
</body>
</html>