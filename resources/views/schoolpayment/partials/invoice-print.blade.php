{{-- resources/views/schoolpayment/partials/invoice-print.blade.php
     Single-page (A4) printable invoice. Shared by the PDF download
     (studentinvoicepdf.blade.php) and the "Print Invoice" button on the
     web view (studentinvoice.blade.php), so both always print on ONE page.
     Uses tables only (no flexbox) so DomPDF renders it the same as a browser.
     Sizes scale automatically so the invoice fills one A4 page (see the size calculation below). --}}
@php
    $student     = $studentdata->first() ?? null;
    $itemCount   = $studentpaymentbill->count();
    $lastPayment = $studentpaymentbill->first();

    /*
     | Auto-fit to ONE A4 page.
     | Every size below is multiplied by $s. We estimate the page height the
     | content needs at a given scale and pick the largest scale that fits:
     | short invoices grow to use the page, long ones shrink to stay on one page.
     | Any space still left is shared out as taller rows and a gap above the
     | totals/signature block, so the invoice always reaches the bottom of the page.
     | All numbers are CSS px at 96dpi (A4 minus 10mm margins = 1047px tall).
     */
    $pageH   = 1000;                         // usable height we aim for (a little safety)
    $hasDesc = $studentpaymentbill->contains(fn ($b) => !empty($b->description));
    $textLen = $studentpaymentbill->map(fn ($b) => mb_strlen(trim(($b->title ?? '') . ' · ' . ($b->description ?? '')))
                                                   + (!empty($b->total_savings) && $b->total_savings > 0 ? 24 : 0))->values()->all();
    $titleLen = $studentpaymentbill->map(fn ($b) => mb_strlen($b->title ?? ''))->values()->all();
    $methodLen = $studentpaymentbill->map(fn ($b) => mb_strlen($b->paymentMethod ?? 'N/A'))->values()->all();
    // Longest money figure in the table: large amounts need narrower text to stay on one line.
    $money = fn ($v) => mb_strlen('N' . number_format((float) $v, 2));
    $amtLen = 10;
    foreach ($studentpaymentbill as $b) {
        $amtLen = max($amtLen, $money($b->amount ?? 0), $money($b->previousPaid ?? 0), $money($b->amountPaid ?? 0), $money($b->balance ?? 0));
    }
    $maxScale = min(1.35, 12.5 / $amtLen);
    // Student / invoice detail boxes: 4 per row, long values wrap.
    $gridRows = [
        [mb_strlen($studentFullName ?? ''), mb_strlen($student->admissionNo ?? ''), mb_strlen(trim(($student->schoolclass ?? '') . ' ' . ($student->arm ?? ''))), mb_strlen(($schoolterm ?? '') . ' · ' . ($schoolsession ?? ''))],
        [12, 12, 14, $money($totalBillAmount ?? 0)],
    ];
    // Fixed parts (header, details grid, table head, totals, signature, footer) at scale 1,
    // plus the logo / stamp images when present. Measured from DomPDF output.
    $base = 289 + (!empty($schoolInfo->logo_base64) ? 50 : 0) + (!empty($schoolInfo->stamp_base64) ? 12 : 0);
    $estimate = function ($s, $hideDesc = false) use ($textLen, $titleLen, $methodLen, $gridRows, $base) {
        $cpl  = 48 / $s;                     // characters per line in the "Bill Description" column
        $rows = 0;
        foreach ($textLen as $i => $len) {
            $len   = $hideDesc ? $titleLen[$i] : $len;
            $lines = max(ceil($len / $cpl), ceil($methodLen[$i] * $s / 13.5), 1);
            $rows += 26.8 + 14.3 * ($lines - 1);                    // one-line row + each wrapped line
        }
        $grid = 0;
        foreach ($gridRows as $vals) {
            $grid += 14.3 * (max(array_map(fn ($l) => max(1, ceil($l * $s / 28)), $vals)) - 1);
        }
        return $s * ($base + $grid + max($rows, 40));
    };
    $hideDesc = false;
    $s = 1.0;
    $pageH = $pageH * ($fitAdjust ?? 1);   // the controller lowers this if DomPDF still needed 2 pages
    for ($try = $maxScale; $try >= 0.62; $try -= 0.01) {
        $s = $try;
        if ($estimate($try) <= $pageH) break;
    }
    if ($estimate($s) > $pageH && $hasDesc) {          // still too long: drop descriptions
        $hideDesc = true;
        for ($try = 1.0; $try >= 0.55; $try -= 0.01) { $s = $try; if ($estimate($try, true) <= $pageH) break; }
    }
    $spare   = max(0, $pageH - $estimate($s, $hideDesc));
    $rowPad  = $itemCount ? min(18, $spare * 0.6 / $itemCount) : 0;   // extra px per row (split top/bottom)
    $gap     = max(0, $spare - $rowPad * $itemCount);                   // the rest goes above the totals block
    $z = fn ($px) => round($px * $s, 2) . 'px';
@endphp
<style>
    @page { size: A4 portrait; margin: 10mm 12mm; }
    html, body { margin:0; padding:0; background:#fff; }
    .inv { font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; font-size:{{ $z(10) }}; color:#1f2937; line-height:1.3;
           -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv * { box-sizing: border-box; }
    .inv table { width:100%; border-collapse:collapse; }
    .inv td, .inv th { vertical-align: top; }
    .inv .nw { white-space: nowrap; }
    .inv .r { text-align:right; }
    .inv .c { text-align:center; }
    .inv .muted { color:#6b7280; }
    .inv .ok { color:#16a34a; }
    .inv .bad { color:#dc2626; }
    .inv .warn { color:#d97706; }

    .inv .hdr td { padding:0; vertical-align: middle; }
    .inv .logo-img { max-height:{{ $z(46) }}; max-width:{{ $z(170) }}; }
    .inv .school-name { font-size:{{ $z(14) }}; font-weight:bold; color:#1e3a5f; }
    .inv .school-meta { font-size:{{ $z(8) }}; color:#6b7280; margin-top:{{ $z(2) }}; }
    .inv .doc-title { font-size:{{ $z(18) }}; font-weight:bold; color:#1e3a5f; letter-spacing:{{ $z(1) }}; }
    .inv .doc-no { font-size:{{ $z(9) }}; color:#0d6efd; font-weight:bold; }
    .inv .rule { border-bottom:2px solid #1e3a5f; margin:{{ $z(6) }} 0 {{ $z(8) }}; }

    .inv .info td { border:1px solid #e5e7eb; padding:{{ $z(4) }} {{ $z(6) }}; width:25%; }
    .inv .lbl { font-size:{{ $z(7) }}; color:#6b7280; text-transform:uppercase; letter-spacing:{{ $z(0.3) }}; }
    .inv .val { font-size:{{ $z(10) }}; font-weight:bold; color:#1e3a5f; }

    .inv .items { margin-top:{{ $z(8) }}; }
    .inv .items th { background:#1e3a5f; color:#fff; font-size:{{ $z(7.5) }}; text-transform:uppercase; padding:{{ $z(4) }} {{ $z(5) }}; text-align:left; }
    .inv .items th.r { text-align:right; }
    .inv .items td { padding:{{ round(5*$s + $rowPad/2, 2) }}px {{ $z(5) }}; border-bottom:1px solid #e5e7eb; font-size:{{ $z(9.5) }}; }
    .inv .items tr { page-break-inside: avoid; }
    .inv .desc { font-size:{{ $z(8) }}; color:#6b7280; }

    .inv .bottom { margin-top:{{ $z(8) }}; page-break-inside: avoid; }
    .inv .bottom > tbody > tr > td { padding:0; }
    .inv .pinfo td { padding:{{ $z(2) }} 0; font-size:{{ $z(9) }}; }
    .inv .pinfo .k { color:#6b7280; width:38%; }
    .inv .pinfo .v { font-weight:bold; color:#1e3a5f; }
    .inv .tot td { padding:{{ $z(2) }} {{ $z(6) }}; font-size:{{ $z(9.5) }}; }
    .inv .tot .v { text-align:right; font-weight:bold; color:#1e3a5f; white-space:nowrap; }
    .inv .tot .grand td { border-top:2px solid #1e3a5f; font-size:{{ $z(12) }}; padding-top:{{ $z(4) }}; font-weight:bold; color:#1e3a5f; }

    .inv .sign { margin-top:{{ $z(6) }}; }
    .inv .stamp { max-height:{{ $z(55) }}; max-width:{{ $z(100) }}; }
    .inv .sign-line { border-top:1px solid #1e3a5f; width:{{ $z(170) }}; margin-top:{{ $z(22) }}; }
    .inv .footer { margin-top:{{ $z(8) }}; border-top:1px solid #e5e7eb; padding-top:{{ $z(4) }}; font-size:{{ $z(7.5) }}; color:#9ca3af; text-align:center; }

    .inv .bottom { margin-top: {{ round(8*$s + $gap, 2) }}px; }
</style>

<div class="inv">
    {{-- Header --}}
    <table class="hdr">
        <tr>
            <td style="width:60%;">
                @if($schoolInfo && $schoolInfo->logo_base64)
                    <img src="{{ $schoolInfo->logo_base64 }}" class="logo-img" alt="School Logo">
                @endif
                <div class="school-name">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</div>
                <div class="school-meta">
                    {{ $schoolInfo->school_address ?? '' }}
                    @if(!empty($schoolInfo->school_email)) &nbsp;|&nbsp; {{ $schoolInfo->school_email }} @endif
                    @if($schoolInfo && $schoolInfo->formatted_phones) &nbsp;|&nbsp; {{ $schoolInfo->formatted_phones }} @endif
                </div>
            </td>
            <td style="width:40%;" class="r">
                <div class="doc-title">INVOICE</div>
                <div class="doc-no">#{{ $invoiceNumber }}</div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    {{-- Student + invoice details --}}
    <table class="info">
        <tr>
            <td><div class="lbl">Student Name</div><div class="val">{{ $studentFullName ?? trim(($student->lastname ?? '').' '.($student->firstname ?? '')) }}</div></td>
            <td><div class="lbl">Admission No</div><div class="val">{{ $student->admissionNo ?? 'N/A' }}</div></td>
            <td><div class="lbl">Class &amp; Arm</div><div class="val">{{ $student->schoolclass ?? '' }} {{ $student->arm ?? '' }}</div></td>
            <td><div class="lbl">Term / Session</div><div class="val">{{ $schoolterm ?? 'N/A' }} &middot; {{ $schoolsession ?? 'N/A' }}</div></td>
        </tr>
        <tr>
            <td><div class="lbl">Invoice Date</div><div class="val">{{ \Carbon\Carbon::now()->format('d M, Y') }}</div></td>
            <td><div class="lbl">Due Date</div><div class="val">{{ \Carbon\Carbon::now()->addDays(7)->format('d M, Y') }}</div></td>
            <td><div class="lbl">Payment Status</div>
                <div class="val {{ $totalOutstanding == 0 ? 'ok' : 'warn' }}">{{ $totalOutstanding == 0 ? 'FULLY PAID' : 'PARTIALLY PAID' }}</div></td>
            <td><div class="lbl">Total Bill Amount</div><div class="val nw">&#8358;{{ number_format($totalBillAmount, 2) }}</div></td>
        </tr>
    </table>

    {{-- Bill items --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:30%;">Bill Description</th>
                <th class="r" style="width:11%;">Amount</th>
                <th class="r" style="width:11%;">Prev. Paid</th>
                <th class="r" style="width:11%;">Today</th>
                <th class="r" style="width:11%;">Total Paid</th>
                <th style="width:11%;">Method</th>
                <th class="r" style="width:11%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($studentpaymentbill as $i => $sp)
            <tr>
                <td class="c">{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $sp->title }}</strong>
                    @if($sp->description && !$hideDesc)<span class="desc"> &middot; {{ $sp->description }}</span>@endif
                    @if(isset($sp->total_savings) && $sp->total_savings > 0)
                        <span class="desc ok"> &middot; Savings &#8358;{{ number_format($sp->total_savings, 2) }}</span>
                    @endif
                </td>
                <td class="r nw">&#8358;{{ number_format($sp->amount, 2) }}</td>
                <td class="r nw">&#8358;{{ number_format($sp->previousPaid, 2) }}</td>
                <td class="r nw ok">&#8358;{{ number_format($sp->todayPaid, 2) }}</td>
                <td class="r nw">&#8358;{{ number_format($sp->amountPaid, 2) }}</td>
                <td>{{ $sp->paymentMethod ?? 'N/A' }}</td>
                <td class="r nw {{ $sp->balance > 0 ? 'bad' : 'ok' }}">&#8358;{{ number_format($sp->balance, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="c muted" style="padding:{{ $z(12) }};">No payment records found for this student.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Payment info + signature (left)  |  Totals (right) --}}
    <table class="bottom">
        <tr>
            <td style="width:50%; padding-right:{{ $z(14) }};">
                @if($lastPayment)
                <div class="lbl" style="margin-bottom:{{ $z(2) }};">Latest Payment Information</div>
                <table class="pinfo">
                    <tr><td class="k">Payment Method</td><td class="v">{{ $lastPayment->paymentMethod ?? 'N/A' }}</td></tr>
                    <tr><td class="k">Received By</td><td class="v">{{ $lastPayment->receivedBy ?? $lastPayment->recievedBy ?? 'School Administration' }}</td></tr>
                    <tr><td class="k">Payment Date</td><td class="v">{{ $lastPayment->paymentDate ? \Carbon\Carbon::parse($lastPayment->paymentDate)->format('d M, Y') : date('d M, Y') }}</td></tr>
                </table>
                @endif
                <table class="sign">
                    <tr>
                        <td style="vertical-align:bottom;">
                            <div class="sign-line"></div>
                            <div style="font-weight:bold; font-size:{{ $z(9) }}; margin-top:{{ $z(2) }};">Authorized Signatory</div>
                            <div class="muted" style="font-size:{{ $z(7.5) }};">{{ $schoolInfo->school_name ?? 'School Name' }}</div>
                        </td>
                        <td class="r" style="vertical-align:bottom;">
                            @if($schoolInfo && $schoolInfo->stamp_base64)
                                <img src="{{ $schoolInfo->stamp_base64 }}" class="stamp" alt="School Stamp">
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width:50%;">
                <table class="tot">
                    <tr><td>Subtotal (Bill Amount)</td><td class="v">&#8358;{{ number_format($totalBillAmount, 2) }}</td></tr>
                    @if(isset($totalSavings) && $totalSavings > 0)
                    <tr class="ok"><td>Total Savings Applied</td><td class="v ok">-&#8358;{{ number_format($totalSavings, 2) }}</td></tr>
                    @endif
                    <tr><td>Total Previous Payments</td><td class="v">&#8358;{{ number_format($totalPreviousPaid, 2) }}</td></tr>
                    <tr><td>Today's Payment</td><td class="v ok">&#8358;{{ number_format($totalTodayPaid, 2) }}</td></tr>
                    <tr><td>Total Amount Paid</td><td class="v">&#8358;{{ number_format($totalPaid, 2) }}</td></tr>
                    <tr class="grand"><td>Outstanding Balance</td><td class="v {{ $totalOutstanding > 0 ? 'bad' : '' }}">&#8358;{{ number_format($totalOutstanding, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Computer-generated invoice &middot; Invoice #{{ $invoiceNumber }} &middot; Generated {{ \Carbon\Carbon::now()->format('d M, Y \a\t H:i') }}
    </div>
</div>
