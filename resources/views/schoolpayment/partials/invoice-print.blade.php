{{-- resources/views/schoolpayment/partials/invoice-print.blade.php
     Single-page (A4) printable invoice. Shared by the PDF download
     (studentinvoicepdf.blade.php) and the "Print Invoice" button on the
     web view (studentinvoice.blade.php), so both always print on ONE page.
     Uses tables only (no flexbox) so DomPDF renders it the same as a browser. --}}
@php
    $student   = $studentdata->first() ?? null;
    $itemCount = $studentpaymentbill->count();
    // Shrink rows progressively for long bill lists so it still fits one page
    $density   = $itemCount > 26 ? 'inv-xs' : ($itemCount > 16 ? 'inv-sm' : '');
    $lastPayment = $studentpaymentbill->first();
@endphp
<style>
    @page { size: A4 portrait; margin: 10mm 12mm; }
    html, body { margin:0; padding:0; background:#fff; }
    .inv { font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; font-size:10px; color:#1f2937; line-height:1.3;
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
    .inv .logo-img { max-height:46px; max-width:170px; }
    .inv .school-name { font-size:14px; font-weight:bold; color:#1e3a5f; }
    .inv .school-meta { font-size:8px; color:#6b7280; margin-top:2px; }
    .inv .doc-title { font-size:18px; font-weight:bold; color:#1e3a5f; letter-spacing:1px; }
    .inv .doc-no { font-size:9px; color:#0d6efd; font-weight:bold; }
    .inv .rule { border-bottom:2px solid #1e3a5f; margin:6px 0 8px; }

    .inv .info td { border:1px solid #e5e7eb; padding:4px 6px; width:25%; }
    .inv .lbl { font-size:7px; color:#6b7280; text-transform:uppercase; letter-spacing:0.3px; }
    .inv .val { font-size:10px; font-weight:bold; color:#1e3a5f; }

    .inv .items { margin-top:8px; }
    .inv .items th { background:#1e3a5f; color:#fff; font-size:7.5px; text-transform:uppercase; padding:4px 5px; text-align:left; }
    .inv .items th.r { text-align:right; }
    .inv .items td { padding:5px 5px; border-bottom:1px solid #e5e7eb; font-size:9.5px; }
    .inv .items tr { page-break-inside: avoid; }
    .inv .desc { font-size:8px; color:#6b7280; }

    .inv .bottom { margin-top:8px; page-break-inside: avoid; }
    .inv .bottom > tbody > tr > td { padding:0; }
    .inv .pinfo td { padding:2px 0; font-size:9px; }
    .inv .pinfo .k { color:#6b7280; width:38%; }
    .inv .pinfo .v { font-weight:bold; color:#1e3a5f; }
    .inv .tot td { padding:2px 6px; font-size:9.5px; }
    .inv .tot .v { text-align:right; font-weight:bold; color:#1e3a5f; white-space:nowrap; }
    .inv .tot .grand td { border-top:2px solid #1e3a5f; font-size:12px; padding-top:4px; font-weight:bold; color:#1e3a5f; }

    .inv .sign { margin-top:6px; }
    .inv .stamp { max-height:55px; max-width:100px; }
    .inv .sign-line { border-top:1px solid #1e3a5f; width:170px; margin-top:22px; }
    .inv .footer { margin-top:8px; border-top:1px solid #e5e7eb; padding-top:4px; font-size:7.5px; color:#9ca3af; text-align:center; }

    /* Denser modes for long bill lists */
    .inv.inv-sm .items td { padding:2px 4px; font-size:8px; }
    .inv.inv-sm .desc { font-size:7px; }
    .inv.inv-xs .items td { padding:1px 4px; font-size:7.5px; }
    .inv.inv-xs .items th { padding:3px 4px; }
    .inv.inv-xs .desc { display:none; }
</style>

<div class="inv {{ $density }}">
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
                    @if($sp->description)<span class="desc"> &middot; {{ $sp->description }}</span>@endif
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
            <tr><td colspan="8" class="c muted" style="padding:12px;">No payment records found for this student.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Payment info + signature (left)  |  Totals (right) --}}
    <table class="bottom">
        <tr>
            <td style="width:50%; padding-right:14px;">
                @if($lastPayment)
                <div class="lbl" style="margin-bottom:2px;">Latest Payment Information</div>
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
                            <div style="font-weight:bold; font-size:9px; margin-top:2px;">Authorized Signatory</div>
                            <div class="muted" style="font-size:7.5px;">{{ $schoolInfo->school_name ?? 'School Name' }}</div>
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
