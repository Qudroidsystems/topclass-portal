@extends('layouts.master')

@section('content')
<style>
    :root {
        --tb-primary: #009ef7;
        --tb-secondary: #3b82f6;
        --tb-success: #50cd89;
        --tb-light: #f5f8fa;
        --tb-success-subtle: rgba(80, 205, 137, 0.1);
    }

    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        max-width: 100%;
    }

    .invoice-effect-top {
        z-index: 0;
    }

    .card-body {
        z-index: 1;
        position: relative;
        padding: 1.5rem;
    }

    .card-logo {
        height: 28px;
        margin-bottom: 1rem; /* Space below logo in vertical layout */
    }

    .fs-md {
        font-size: 1.125rem !important;
    }

    .fs-xxs {
        font-size: 0.625rem !important;
    }

    .table-borderless th, .table-borderless td {
        border: none;
        padding: 0.5rem 0.75rem;
        vertical-align: middle;
    }

    .table-nowrap th, .table-nowrap td {
        white-space: nowrap;
    }

    .table-light {
        background-color: var(--tb-light);
    }

    .border-top-dashed {
        border-top: 1px dashed #dee2e6 !important;
    }

    .alert-danger {
        background-color: rgba(241, 65, 108, 0.1);
        border-color: #f1416c;
        color: #f1416c;
        padding: 0.75rem;
    }

    .invoice-signature img {
        height: 30px;
    }

    .hstack {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0.5rem;
    }

    .d-print-none {
        display: flex !important;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table {
        width: 100%;
        table-layout: auto;
    }

    .student-avatar {
        width: 50px;
        height: 50px;
        object-fit: cover;
    }

    .address-wrap {
        overflow-wrap: break-word;
        word-break: break-word;
        hyphens: auto;
        max-width: 180px; /* Adjusted for vertical layout */
        display: inline-block; /* Ensures max-width is respected */
    }

    .school-details {
        margin-bottom: 1rem; /* Space below details in vertical layout */
    }

    .school-details h6 {
        margin-bottom: 0.5rem; /* Spacing between detail items */
    }

    @media print {
        html, body {
            background-color: #fff;
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
        }

        .main-content, .page-content, .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .card {
            box-shadow: none;
            max-width: 100%;
            width: 100%;
            border-radius: 0;
            margin: 0;
            padding: 0;
        }

        .card-body {
            padding: 0.5cm;
        }

        .d-print-none, .alert {
            display: none !important;
        }

        .invoice-effect-top {
            display: none;
        }

        .card::before {
            content: "{{ $schoolInfo->school_name ?? 'TOPCLASS COLLEGE' }} Invoice";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #212529;
        }

        .card::after {
            content: "© {{ date('Y') }} {{ $schoolInfo->school_name ?? 'TOPCLASS COLLEGE' }}";
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #7E8299;
        }

        .table-responsive {
            overflow: visible;
        }

        table {
            table-layout: fixed;
            font-size: 0.75rem;
        }

        .fs-md {
            font-size: 0.9rem !important;
        }

        .fs-xxs {
            font-size: 0.5rem !important;
        }

        .table-borderless th, .table-borderless td {
            padding: 0.3rem 0.5rem;
        }

        .row.g-3 {
            margin-bottom: 0.5rem;
        }

        .col-lg, .col-6 {
            padding: 0 0.25rem;
        }

        h6, p {
            margin-bottom: 0.2rem;
            font-size: 0.8rem;
        }

        .invoice-signature img {
            height: 20px;
        }

        .student-avatar {
            width: 30px;
            height: 30px;
        }

        .address-wrap {
            max-width: 120px; /* Adjusted for print */
        }

        .card-logo {
            height: 20px; /* Slightly smaller for print */
            margin-bottom: 0.5cm;
        }

        .school-details {
            margin-bottom: 0.5cm;
        }

        .school-details h6 {
            margin-bottom: 0.3rem;
        }

        @page {
            size: A4;
            margin: 0.5cm;
        }
    }

    @media (max-width: 767.98px) {
        .card-body {
            padding: 1rem;
        }

        .hstack {
            flex-direction: column;
            gap: 1rem;
        }

        .col-6 {
            width: 100%;
            margin-bottom: 1rem;
        }

        .table-nowrap th, .table-nowrap td {
            white-space: normal;
        }

        table {
            font-size: 0.875rem;
        }

        .address-wrap {
            max-width: 100%; /* Full width on small screens */
        }

        .card-logo {
            margin-bottom: 0.75rem;
        }

        .school-details {
            margin-bottom: 0.75rem;
        }
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Error!</strong> There were some problems with your input.<br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?: session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row justify-content-center">
                <div class="col-xxl-9 col-lg-10 col-md-12">
                    <div class="hstack gap-2 justify-content-end d-print-none mb-4">
                        <a href="{{ route('schoolpayment.termsessionpayments', ['studentId' => $studentId, 'termid' => $termId, 'sessionid' => $sessionId]) }}" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
                        <a href="javascript:window.print()" class="btn btn-success"><i class="ri-printer-line align-bottom me-1"></i> Print</a>
                        <button type="button" id="download-button" class="btn btn-primary"><i class="ri-download-2-line align-bottom me-1"></i> Download</button>
                    </div>
                    <div class="card overflow-hidden" id="invoice">
                        <div class="invoice-effect-top position-absolute start-0">
                            <svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 764 182" width="764" height="182">
                                <g>
                                    <g>
                                        <path style="fill: var(--tb-light);" d="m-6.6 177.4c17.5 0.1 35.1 0 52.8-0.4 286.8-6.6 537.6-77.8 700.3-184.6h-753.1z" />
                                    </g>
                                    <g>
                                        <path style="fill: var(--tb-secondary);" d="m-6.6 132.8c43.5 2.1 87.9 2.7 132.9 1.7 246.9-5.6 467.1-59.2 627.4-142.1h-760.3z" />
                                    </g>
                                    <g style="opacity: .5">
                                        <path style="fill: var(--tb-primary);" d="m-6.6 87.2c73.2 7.4 149.3 10.6 227.3 8.8 206.2-4.7 393.8-42.8 543.5-103.6h-770.8z" />
                                    </g>
                                </g>
                            </svg>
                        </div>
                        <div class="card-body z-1 position-relative">
                            <div class="school-header">
                                <img src="{{ $schoolInfo->logo_url }}" class="card-logo" alt="{{ $schoolInfo->school_name ?? 'TOPCLASS COLLEGE' }}" height="28">
                                <div class="school-details">
                                    <h6><span class="text-muted fw-normal">Invoice No:</span> <span id="legal-register-no">{{ $invoiceNumber }}</span></h6>
                                    <h6><span class="text-muted fw-normal">Email:</span> <span id="email">{{ $schoolInfo->school_email ?? 'info@topclassschool.edu' }}</span></h6>
                                    <h6><span class="text-muted fw-normal">Website:</span> <span id="website">{{ $schoolInfo->school_website ? '<a href="' . $schoolInfo->school_website . '" target="_blank">' . $schoolInfo->school_website . '</a>' : 'N/A' }}</span></h6>
                                    <h6><span class="text-muted fw-normal">Address:</span> <span id="address" class="address-wrap">{!! Str::replace(',', ',<br>', $schoolInfo->school_address ?? 'Your School Address Here') !!}</span></h6>
                                    <h6 class="mb-0"><span class="text-muted fw-normal">Contact No: </span><span id="contact-no">{{ $schoolInfo->school_phone ?? 'Your Phone Number' }}</span></h6>
                                </div>
                            </div>
                            <div class="mt-5 pt-4">
                                <div class="row g-3">
                                    <div class="col-lg col-6">
                                        <p class="text-muted mb-2 text-uppercase">Invoice No</p>
                                        <h5 class="fs-md mb-0">#<span id="invoice-no">{{ $invoiceNumber }}</span></h5>
                                    </div>
                                    <div class="col-lg col-6">
                                        <p class="text-muted mb-2 text-uppercase">Date</p>
                                        <h5 class="fs-md mb-0"><span id="invoice-date">{{ \Carbon\Carbon::now()->format('d F, Y') }}</span></h5>
                                    </div>
                                    <div class="col-lg col-6">
                                        <p class="text-muted mb-2 text-uppercase">Due Date</p>
                                        <h5 class="fs-md mb-0"><span id="invoice-due-date">{{ \Carbon\Carbon::now()->addDays(7)->format('d F, Y') }}</span></h5>
                                    </div>
                                    <div class="col-lg col-6">
                                        <p class="text-muted mb-2 text-uppercase">Payment Status</p>
                                        <span class="badge bg-success-subtle text-success fs-xs" id="payment-status">
                                            {{ $totalOutstanding == 0 ? 'Paid' : 'Pending' }}
                                        </span>
                                    </div>
                                    <div class="col-lg col-6">
                                        <p class="text-muted mb-2 text-uppercase">Total Amount</p>
                                        <h5 class="fs-md mb-0">₦<span id="total-amount">{{ number_format($totalBillAmount, 2, '.', ',') }}</span></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 pt-2">
                                <div class="row g-3">
                                    @if ($studentdata->isNotEmpty())
                                        @foreach ($studentdata as $s)
                                        <div class="col-6">
                                            <p class="text-muted text-uppercase">Student Details</p>
                                            @if ($s->avatar)
                                                <img src="{{ Storage::url('images/studentavatar/' . $s->avatar) }}" alt="{{ $s->firstname }} {{ $s->lastname }}" class="rounded-circle mb-2 student-avatar">
                                            @endif
                                            <h6 class="fs-md">{{ $s->firstname }} {{ $s->lastname }}</h6>
                                            <p class="text-muted mb-1">ID: {{ $s->admissionNo }}</p>
                                            <p class="text-muted mb-1">Class: {{ $s->schoolclass }} {{ $s->arm }}</p>
                                            <p class="text-muted mb-0">Term: {{ $schoolterm }} | Session: {{ $schoolsession }}</p>
                                        </div>
                                        <div class="col-6">
                                            <p class="text-muted text-uppercase">Billing Address</p>
                                            <h6 class="fs-md">{{ $s->firstname }} {{ $s->lastname }}</h6>
                                            <p class="text-muted mb-1 address-wrap">{!! Str::replace(',', ',<br>', $s->homeaddress ?? ($s->homeadd ?? 'N/A')) !!}</p>
                                            <p class="text-muted mb-0">Phone: {{ $s->phone ?? ($schoolInfo->school_phone ?? 'Your Phone Number') }}</p>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <p class="text-muted text-center">No student data available.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="table-responsive mt-4">
                                <table class="table table-borderless text-center table-nowrap align-middle mb-0">
                                    <thead>
                                        <tr class="table-light">
                                            <th scope="col" style="width: 50px;">#</th>
                                            <th scope="col">Bill Details</th>
                                            <th scope="col">Bill Amount</th>
                                            <th scope="col">Previous Paid</th>
                                            <th scope="col">Paid Today</th>
                                            <th scope="col">Total Paid</th>
                                            <th scope="col">Payment Method</th>
                                            <th scope="col" class="text-end">Outstanding</th>
                                        </tr>
                                    </thead>
                                    <tbody id="products-list">
                                        @if ($studentpaymentbill->isEmpty())
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No bills available.</td>
                                            </tr>
                                        @else
                                            @php $counter = 1; @endphp
                                            @foreach ($studentpaymentbill as $sp)
                                            <tr>
                                                <th scope="row">{{ $counter++ }}</th>
                                                <td class="text-start">
                                                    <span class="fw-medium">{{ $sp->title }}</span>
                                                    <p class="text-muted mb-0">{{ $sp->description }}</p>
                                                </td>
                                                <td>₦ {{ number_format($sp->amount, 2, '.', ',') }}</td>
                                                <td>₦ {{ number_format($sp->previousPaid, 2, '.', ',') }}</td>
                                                <td>₦ {{ number_format($sp->todayPaid, 2, '.', ',') }}</td>
                                                <td>₦ {{ number_format($sp->amountPaid, 2, '.', ',') }}</td>
                                                <td>
                                                    @if ($sp->paymentMethod == 'Bank Transfer')
                                                        <span class="badge bg-primary-subtle text-primary">{{ $sp->paymentMethod }}</span>
                                                    @elseif ($sp->paymentMethod == 'School POS' || $sp->paymentMethod == 'Cash')
                                                        <span class="badge bg-success-subtle text-success">{{ $sp->paymentMethod }}</span>
                                                    @elseif ($sp->paymentMethod == 'N/A')
                                                        <span class="badge bg-secondary-subtle text-secondary">{{ $sp->paymentMethod }}</span>
                                                    @else
                                                        <span class="badge bg-info-subtle text-info">{{ $sp->paymentMethod }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">₦ {{ number_format($sp->balance, 2, '.', ',') }}</td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="border-top border-top-dashed mt-2" id="products-list-total">
                                <table class="table table-borderless table-nowrap align-middle mb-0 ms-auto" style="width:300px">
                                    <tbody>
                                        <tr>
                                            <td>Total Bill Amount</td>
                                            <td class="text-end">₦ {{ number_format($totalBillAmount, 2, '.', ',') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Total Previous Paid</td>
                                            <td class="text-end">₦ {{ number_format($totalPreviousPaid, 2, '.', ',') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Total Paid Today</td>
                                            <td class="text-end">₦ {{ number_format($totalTodayPaid, 2, '.', ',') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Total Amount Paid</td>
                                            <td class="text-end">₦ {{ number_format($totalPaid, 2, '.', ',') }}</td>
                                        </tr>
                                        <tr class="border-top border-top-dashed fs-15">
                                            <th scope="row">Total Outstanding</th>
                                            <td class="text-end">₦ {{ number_format($totalOutstanding, 2, '.', ',') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            @if ($studentpaymentbill->isNotEmpty())
                                <div class="mt-3">
                                    <h6 class="text-muted text-uppercase fw-semibold mb-3">Latest Payment Details:</h6>
                                    @php $lastPayment = $studentpaymentbill->first(); @endphp
                                    <p class="text-muted mb-1">Payment Method: <span class="fw-medium" id="payment-method">{{ $lastPayment->paymentMethod }}</span></p>
                                    <p class="text-muted mb-1">Received By: <span class="fw-medium" id="card-holder-name">{{ $lastPayment->recievedBy ?? 'School Administration' }}</span></p>
                                    <p class="text-muted mb-0">Total Bill Amount: <span class="fw-medium">₦</span><span id="card-total-amount">{{ number_format($totalBillAmount, 2, '.', ',') }}</span></p>
                                </div>
                            @endif
                            <div>
                                <p class="mb-4 pb-2"><b>Thank you for your continued partnership with {{ $schoolInfo->school_name ?? 'TOPCLASS COLLEGE' }}!</b> We appreciate your commitment to your child's education.</p>
                                <div class="invoice-signature text-center">
                                    <img src="{{ asset('assets/images/invoice-signature.svg') }}" alt="Authorized Sign" id="sign-img" height="30">
                                    <h6 class="mb-0 mt-3">Authorized Sign</h6>
                                </div>
                            </div>
                        </div>
                        <div class="invoice-effect-top position-absolute end-0" style="transform: rotate(180deg); bottom: -40px;">
                            <svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 764 182" width="764" height="182">
                                <g>
                                    <g>
                                        <path style="fill: var(--tb-light);" d="m-6.6 177.4c17.5 0.1 35.1 0 52.8-0.4 286.8-6.6 537.6-77.8 700.3-184.6h-753.1z" />
                                    </g>
                                    <g>
                                        <path style="fill: var(--tb-secondary);" d="m-6.6 132.8c43.5 2.1 87.9 2.7 132.9 1.7 246.9-5.6 467.1-59.2 627.4-142.1h-760.3z" />
                                    </g>
                                    <g style="opacity: .5">
                                        <path style="fill: var(--tb-primary);" d="m-6.3 87.51c73.2 7.41 149.6 45.1 227.6 43.4 206.1 4.6 393.7-42.8 543.4-103.6h-770.45z" />
                                    </g>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const downloadButton = document.getElementById('download-button');
                    if (downloadButton) {
                        downloadButton.addEventListener('click', function() {
                            this.disabled = true;
                            this.innerHTML = '<i class="ri-download-line align-bottom me-1"></i> Downloading...';
                            window.location.assign('{{ url()->current() }}?download_pdf=1');
                            setTimeout(() => {
                                this.disabled = false;
                                this.innerHTML = '<i class="ri-download-2-line align-bottom me-1"></i> Download';
                            }, 2000);
                        });
                    }
                });
            </script> --}}
        </div>
    </div>
</div>
@endsection