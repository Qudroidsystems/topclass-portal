<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pagetitle }}</title>
    <style>
        :root {
            --primary: #2563eb;
            --success: #059669;
            --danger: #dc2626;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --bg-light: #f3f4f6;
            --border: #e5e7eb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: var(--text-primary);
            background-color: #f9fafb;
            margin: 20px;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            padding: 1rem;
            margin: 0 auto;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .header img {
            max-width: 100px;
            height: auto;
            margin-bottom: 0.5rem;
        }
        .header h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        .header p {
            color: var(--text-secondary);
            margin: 0.15rem 0;
            font-size: 11px;
        }
        .meta-info {
            background: var(--bg-light);
            border-radius: 4px;
            padding: 0.5rem;
            margin: 0.5rem 0;
            font-size: 11px;
        }
        .student-info {
            margin: 0.5rem 0;
            background: #fff;
            border-radius: 4px;
            padding: 0.5rem;
            border: 1px solid var(--border);
        }
        .student-info table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .student-info td {
            padding: 0.25rem 0.5rem;
            vertical-align: top;
            font-size: 11px;
        }
        .student-info td:first-child {
            font-weight: 600;
            color: var(--text-primary);
            width: 150px;
        }
        .summary-card {
            margin: 0.5rem 0;
            padding: 0.5rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 4px;
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: stretch;
            gap: 1.2rem;
        }
        .summary-mini {
            min-width: 90px;
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--bg-light);
            border-radius: 4px;
            border: 1px solid var(--border);
            padding: 0.3rem 0.5rem;
            box-sizing: border-box;
        }
        .summary-mini .row {
            display: flex;
            align-items: baseline;
            gap: 0.15rem;
        }
        .summary-mini .icon {
            font-size: 13px;
            font-weight: bold;
        }
        .summary-mini .amount {
            font-size: 15px;
            font-weight: 700;
        }
        .summary-mini .label {
            font-size: 9px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-top: 0.15rem;
            letter-spacing: 0.03em;
            text-align: center;
        }
        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0.5rem 0;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid var(--border);
        }
        .payment-table-container {
            margin: 0.5rem 0;
            overflow-x: auto;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 11px;
            border: 1px solid var(--border);
        }
        .payment-table th,
        .payment-table td {
            border: 1px solid var(--border);
            padding: 0.5rem;
            text-align: left;
        }
        .payment-table th {
            background: var(--bg-light);
            font-weight: 600;
            color: var(--text-primary);
        }
        .payment-table td {
            color: var(--text-secondary);
        }
        .payment-table tr:nth-child(even) {
            background-color: #f7fafc;
        }
        .footer {
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--text-secondary);
            font-size: 10px;
        }
        .timestamp {
            margin-top: 0.5rem;
            font-style: italic;
            color: var(--text-secondary);
        }
        .generated-by {
            margin-top: 0.25rem;
            font-size: 10px;
        }
        @media print {
            body { background: none; margin: 0; }
            .container { max-width: none; margin: 0; padding: 0.5rem; }
            .payment-table,
            .payment-table th,
            .payment-table td {
                border: 1px solid #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .payment-table th {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .payment-table tr:nth-child(even) {
                background-color: #f7fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .summary-card, .student-info { border: 1px solid #000 !important; }
            .summary-mini { 
                border: 1px solid #000 !important; 
                background: #eee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- School Information -->
        <div class="header">
            @if ($schoolInfo->logo_url)
                <img src="{{ $schoolInfo->logo_url }}" alt="{{ $schoolInfo->school_name }} Logo">
            @endif
            <h1>{{ $schoolInfo->school_name }}</h1>
            <p>{{ $schoolInfo->school_address }}</p>
            <p>Email: {{ $schoolInfo->school_email }} | Phone: {{ $schoolInfo->school_phone }}</p>
            <h2>Payment Statement</h2>
            <div class="meta-info">
                <p>Statement Number: {{ $statementNumber }}</p>
                <p>Term: {{ $schoolterm }} | Session: {{ $schoolsession }}</p>
            </div>
        </div>

        <!-- Student Information -->
        @if ($studentdata->isNotEmpty())
            @foreach ($studentdata as $student)
                <div class="student-info">
                    <table>
                        <tr>
                            <td>Student Name:</td>
                            <td>{{ $student->firstname }} {{ $student->lastname }}</td>
                        </tr>
                        <tr>
                            <td>Admission Number:</td>
                            <td>{{ $student->admissionNo }}</td>
                        </tr>
                        <tr>
                            <td>Class:</td>
                            <td>{{ $student->schoolclass }} {{ $student->arm }}</td>
                        </tr>
                        <tr>
                            <td>Address:</td>
                            <td>{{ $student->homeadd ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Phone:</td>
                            <td>{{ $student->phone ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            @endforeach
        @else
            <p>No student information available.</p>
        @endif

        <!-- Summary -->
        <div class="summary-card">
            <div class="summary-mini">
                <div class="row">
                    <span class="icon text-success">N</span>
                    <div class="amount text-success">{{ number_format($totalSchoolBill, 2) }}</div>
                </div>
                <div class="label">Total Bill</div>
            </div>
            <div class="summary-mini">
                <div class="row">
                    <span class="icon text-success">N</span>
                    <div class="amount text-success">{{ number_format($totalPaid, 2) }}</div>
                </div>
                <div class="label">Total Paid</div>
            </div>
            <div class="summary-mini">
                <div class="row">
                    <span class="icon text-danger">N</span>
                    <div class="amount text-danger">{{ number_format($totalOutstanding, 2) }}</div>
                </div>
                <div class="label">Outstanding</div>
            </div>
        </div>

        <!-- Payment Records -->
        <h3 class="section-title">Payment Records</h3>
        @if ($studentpaymentbill->isNotEmpty())
            <div class="payment-table-container">
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Bill Title</th>
                            <th>Description</th>
                            <th>Bill Amount</th>
                            <th>Amount Paid</th>
                            <th>Balance</th>
                            <th>Payment Method</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($studentpaymentbill as $payment)
                            <tr>
                                <td>{{ $payment->title ?? 'N/A' }}</td>
                                <td>{{ $payment->description ?? 'N/A' }}</td>
                                <td>N {{ number_format($payment->amount, 2) }}</td>
                                <td>N {{ number_format($payment->amount_paid, 2) }}</td>
                                <td>N {{ number_format($payment->balance, 2) }}</td>
                                <td>{{ $payment->payment_method ?? 'N/A' }}</td>
                                <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $payment->payment_status }}</td>
                                <td>{{ $payment->received_by }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p>No payment records found for this term and session.</p>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="timestamp">
                Generated on: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}
            </div>
            <div class="generated-by">
                Generated by: {{ Auth::user()->name ?? 'System' }}
            </div>
            <p>This is an official payment statement from {{ $schoolInfo->school_name }}.</p>
            <p>For any queries, please contact the school administration.</p>
        </div>
    </div>
</body>
</html>
