<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Examination Attendance Sheet</title>
<style>
    @page { margin: 24px 28px 52px 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #111; }
    table { border-collapse: collapse; width: 100%; }
    .hdr td { vertical-align: middle; }
    .school-name { font-size: 16px; font-weight: bold; text-transform: uppercase; text-align: center; }
    .school-sub  { font-size: 9px; text-align: center; color: #333; }
    .title { margin: 8px 0 6px; text-align: center; font-size: 12px; font-weight: bold;
             border-top: 2px solid #111; border-bottom: 2px solid #111; padding: 4px 0; letter-spacing: .5px; }
    .info td { padding: 3px 4px; font-size: 9.5px; }
    .info .lbl { font-weight: bold; width: 17%; }
    .info .val { border-bottom: 1px dotted #555; width: 33%; }
    .att { margin-top: 8px; }
    .att th, .att td { border: 1px solid #222; }
    .att th { background: #e5e7eb; font-size: 8.5px; padding: 4px 2px; text-align: center; }
    .att td { height: 24px; padding: 0 3px; font-size: 9px; }
    .att thead { display: table-header-group; }
    .att tr { page-break-inside: avoid; }
    .c { text-align: center; }
    .box { display: inline-block; width: 11px; height: 11px; border: 1px solid #111; }
    .summary { margin-top: 12px; page-break-inside: avoid; }
    .summary td { padding: 4px; font-size: 9.5px; }
    .sign { margin-top: 18px; page-break-inside: avoid; }
    .sign td { width: 33%; padding: 0 8px; vertical-align: bottom; font-size: 9px; text-align: center; }
    .sign .line { border-top: 1px solid #111; padding-top: 2px; margin-top: 28px; }
    .footer { position: fixed; bottom: -34px; left: 0; right: 0; font-size: 8px; color: #666; text-align: center; }
    .pagenum:before { content: counter(page); }
</style>
</head>
<body>

<div class="footer">
    Generated {{ now()->format('d M Y h:i A') }}{{ $generatedBy ? ' by ' . $generatedBy : '' }} &nbsp;|&nbsp; Page <span class="pagenum"></span>
</div>

{{-- School header --}}
<table class="hdr">
    <tr>
        <td style="width:70px;">
            @if($school && $school->logo_base64)
                <img src="{{ $school->logo_base64 }}" style="width:62px;height:62px;">
            @endif
        </td>
        <td>
            <div class="school-name">{{ $school->school_name ?? 'School Name' }}</div>
            @if($school?->school_address)<div class="school-sub">{{ $school->school_address }}</div>@endif
            <div class="school-sub">
                @if($school?->school_phones) Tel: {{ $school->formatted_phones }} @endif
                @if($school?->school_email) &nbsp;|&nbsp; {{ $school->school_email }} @endif
            </div>
            @if($school?->school_motto)<div class="school-sub"><em>{{ $school->school_motto }}</em></div>@endif
        </td>
        <td style="width:70px;"></td>
    </tr>
</table>

<div class="title">{{ strtoupper($examTitle) }} — ATTENDANCE SHEET</div>

<table class="info">
    <tr>
        <td class="lbl">Class:</td><td class="val">{{ $className }}</td>
        <td class="lbl">Term / Session:</td><td class="val">{{ $termName }} — {{ $sessionName }}</td>
    </tr>
    <tr>
        <td class="lbl">Subject:</td><td class="val">{{ $subjectName }}{{ $subjectCode ? ' (' . $subjectCode . ')' : '' }}</td>
        <td class="lbl">Subject Teacher:</td><td class="val">{{ $teacherName }}</td>
    </tr>
    <tr>
        <td class="lbl">Date:</td><td class="val">{{ $examDate ?? '' }}&nbsp;</td>
        <td class="lbl">Time / Duration:</td><td class="val">{{ trim(($startTime ?? '') . ($duration ? ' (' . $duration . ')' : '')) }}&nbsp;</td>
    </tr>
    <tr>
        <td class="lbl">Venue / Hall:</td><td class="val">{{ $venue ?? '' }}&nbsp;</td>
        <td class="lbl">Total on list:</td><td class="val">{{ $students->count() }}</td>
    </tr>
</table>

<table class="att">
    <thead>
        <tr>
            <th style="width:4%;">S/N</th>
            <th style="width:13%;">Admission No</th>
            <th>Student Name</th>
            <th style="width:6%;">Present</th>
            <th style="width:6%;">Absent</th>
            <th style="width:8%;">Time In</th>
            <th style="width:13%;">Sign In</th>
            <th style="width:8%;">Time Out</th>
            <th style="width:13%;">Sign Out</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $i => $s)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td class="c">{{ $s->admissionNo }}</td>
                <td>{{ strtoupper($s->lastname) }}, {{ $s->firstname }} {{ $s->othername }}</td>
                <td class="c"><span class="box"></span></td>
                <td class="c"><span class="box"></span></td>
                <td></td><td></td><td></td><td></td>
            </tr>
        @endforeach

        @for($k = 1; $k <= $extraRows; $k++)
            <tr>
                <td class="c">{{ $students->count() + $k }}</td>
                <td></td><td></td>
                <td class="c"><span class="box"></span></td>
                <td class="c"><span class="box"></span></td>
                <td></td><td></td><td></td><td></td>
            </tr>
        @endfor
    </tbody>
</table>

<table class="summary">
    <tr>
        <td><strong>Total Present:</strong> ______</td>
        <td><strong>Total Absent:</strong> ______</td>
        <td><strong>Malpractice cases:</strong> ______</td>
        <td><strong>Scripts collected:</strong> ______</td>
    </tr>
    <tr>
        <td colspan="4"><strong>Remarks:</strong> ______________________________________________________________________________</td>
    </tr>
</table>

<table class="sign">
    <tr>
        <td><div class="line">Examiner / Invigilator (Name &amp; Sign)</div></td>
        <td><div class="line">Subject Teacher: {{ $teacherName }}<br>(Sign &amp; Date)</div></td>
        <td><div class="line">Exam Officer / Head of Department<br>(Sign &amp; Date)</div></td>
    </tr>
</table>

</body>
</html>