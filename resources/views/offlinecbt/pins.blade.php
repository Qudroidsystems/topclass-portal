{{-- Printable exam PIN slips for one class --}}
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Offline CBT PINs — {{ $className }}</title>
<style>
@page { size:A4; margin:10mm; }
body { font-family:Arial, Helvetica, sans-serif; margin:0; color:#111; }
.bar { padding:10px; text-align:right; }
.grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:0; }
.slip { border:1px dashed #999; padding:10px 12px; height:30mm; box-sizing:border-box; break-inside:avoid; }
.slip .s { font-size:9px; color:#555; text-transform:uppercase; letter-spacing:.3px; }
.slip .n { font-size:13px; font-weight:bold; margin:3px 0; }
.slip .a { font-size:11px; }
.slip .p { font-family:monospace; font-size:20px; font-weight:bold; letter-spacing:3px; margin-top:4px; }
.slip .p.none { font-size:11px; color:#b45309; letter-spacing:0; font-family:Arial; }
@media print { .bar { display:none; } }
</style></head>
<body>
<div class="bar"><button onclick="window.print()">Print</button></div>
<div class="grid">
@forelse($students as $s)
    <div class="slip">
        <div class="s">{{ $school }} · Offline CBT · {{ $className }}</div>
        <div class="n">{{ trim($s->lastname . ' ' . $s->firstname . ' ' . $s->othername) }}</div>
        <div class="a">Admission no: <strong>{{ $s->admissionNo }}</strong></div>
        @if($s->pin)<div class="p">PIN {{ $s->pin }}</div>@else<div class="p none">No PIN yet — generate PINs first</div>@endif
    </div>
@empty
    <p style="padding:20px">No students with a portal login found in this class for the selected term and session.</p>
@endforelse
</div>
</body></html>
