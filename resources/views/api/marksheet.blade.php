{{-- Marks sheet PDF for the mobile app (TopClass: CA1, CA2, CA3, Exam). --}}
<!doctype html>
<html><head><meta charset="utf-8"><style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #0f172a; }
    h1 { font-size: 16px; margin: 0; } .muted { color: #64748b; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #1e3a5f; color: #fff; padding: 5px; font-size: 10px; }
    td { border: 1px solid #e2e8f0; padding: 4px 5px; } tr:nth-child(even) td { background: #f8fafc; }
    .c { text-align: center; } .b { font-weight: bold; }
    .sign { margin-top: 28px; } .sign span { display: inline-block; width: 45%; border-top: 1px solid #94a3b8; padding-top: 4px; margin-right: 8%; }
</style></head><body>
    <h1>{{ $school->school_name ?? config('app.name') }}</h1>
    <div class="muted">{{ $school->school_address ?? '' }}</div>
    <p class="b">Marks sheet — {{ $sheet['subject'] }} · {{ $sheet['class_name'] }} · {{ $sheet['term'] }} · {{ $sheet['session'] }}</p>
    <div class="muted">Total = ((CA1 + CA2 + CA3) ÷ 3 + Exam) ÷ 2 · Teacher: {{ $teacher }} · Printed {{ now()->format('d M Y, H:i') }}</div>
    <table>
        <thead><tr><th>#</th><th>Adm. No</th><th style="text-align:left">Student</th>
            @foreach($sheet['assessments'] as $a)<th>{{ $a['name'] }}</th>@endforeach
            <th>Total</th><th>BF</th><th>Cum</th><th>Grade</th><th>Position</th><th>Remark</th></tr></thead>
        <tbody>
        @foreach($sheet['students'] as $i => $s)
            <tr><td class="c">{{ $i + 1 }}</td><td>{{ $s['admission_no'] }}</td><td>{{ trim($s['last_name'].' '.$s['first_name'].' '.$s['other_name']) }}</td>
                @foreach($sheet['assessments'] as $a)<td class="c">{{ isset($s['scores']->{$a['id']}) && $s['scores']->{$a['id']} !== null ? rtrim(rtrim(number_format($s['scores']->{$a['id']}, 1), '0'), '.') : '–' }}</td>@endforeach
                <td class="c b">{{ $s['total'] ?? '–' }}</td><td class="c">{{ $s['bf'] ?? '–' }}</td><td class="c b">{{ $s['cum'] ?? '–' }}</td>
                <td class="c b">{{ $s['grade'] ?? '–' }}</td><td class="c">{{ $s['position'] ?? '–' }}</td><td>{{ $s['remark'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="sign"><span>Subject teacher</span><span>Head of department / Principal</span></div>
</body></html>
