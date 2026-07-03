@extends('layouts.master')

@section('content')
<!-- Main content container -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Debug: Display payment records count -->
            <div>Debug: {{ $studentpaymentbill->count() }} payment records found</div>

            <!-- Display validation errors -->
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

            <!-- Display success/status messages -->
            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?: session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Student Information Cards -->
            @if ($studentdata)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="d-flex flex-wrap flex-stack mb-4">
                                        <!-- Student Avatar -->
                                        <div class="me-6 mb-3">
                                            <img src="{{ $studentdata->avatar ? Storage::url('images/studentavatar/' . $studentdata->avatar) : asset('images/default-avatar.png') }}" alt="{{ $studentdata->firstname }} {{ $studentdata->lastname }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #e5e7eb;">
                                        </div>
                                        <!-- Student Information -->
                                        <div class="d-flex flex-column flex-grow-1 pe-8">
                                            <div class="d-flex flex-wrap">
                                                <!-- Student Name Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person fs-3 text-primary me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $studentdata->firstname }} {{ $studentdata->lastname }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Student Name</div>
                                                </div>
                                                <!-- Admission No Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-card-text fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $studentdata->admissionNo }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Admission No</div>
                                                </div>

                                                <!-- Student Status Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-card-text fs-3 {{ $studentdata->student_status !== 'Active' ? 'text-danger' : 'text-success' }} me-2"></i>
                                                        <div class="fs-2 fw-bold {{ $studentdata->student_status !== 'Active' ? 'text-danger' : 'text-success' }}">
                                                            {{ $studentdata->statusId == 1 ? 'Returning Student' : ($studentdata->statusId == 2 ? 'New Student' : $studentdata->statusId) }} | {{ $studentdata->student_status }}
                                                        </div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Student Status | Active Mode</div>
                                                </div>


                                                <!-- Class Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $studentdata->schoolclass }} {{ $studentdata->arm }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Class</div>
                                                </div>
                                                <!-- Term | Session Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-calendar fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $schoolterm }} | {{ $schoolsession }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Term | Session</div>
                                                </div>
                                                <!-- Total School Bill Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-currency-dollar fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">₦ {{ number_format($student_bill_info->sum('amount')) }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Total Bill</div>
                                                </div>
                                                <!-- Total Amount Paid Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-wallet fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">₦ {{ number_format($studentpaymentbillbook->where('student_id', $studentId)->where('term_id', $termid)->where('session_id', $sessionid)->sum('amount_paid')) }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Total Paid</div>
                                                </div>
                                                <!-- Total Outstanding Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-exclamation-circle fs-3 text-danger me-2"></i>
                                                        @php
                                                            $totalBill = $student_bill_info->sum('amount');
                                                            $totalPaid = $studentpaymentbillbook->where('student_id', $studentId)->where('term_id', $termid)->where('session_id', $sessionid)->sum('amount_paid');
                                                            $outstanding = max(0, $totalBill - $totalPaid);
                                                        @endphp
                                                        <div class="fs-2 fw-bold text-danger">₦ {{ number_format($outstanding) }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Outstanding</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Payments Section -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">Student Payment Details</h5>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by bill title or description..." style="min-width: 200px;" {{ $studentpaymentbill->isEmpty() ? 'disabled' : '' }}>
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <a href="{{ route('schoolpayment.index') }}" class="btn btn-primary">
                                    <i class="ri-arrow-left-line"></i> Back to Students
                                </a>
                                <div>
                                    @if ($paymentRecordsCount > 0)
                                        <a href="{{ route('schoolpayment.invoice', ['studentId' => $studentId, 'schoolclassid' => $schoolclassId, 'termid' => $termid, 'sessionid' => $sessionid]) }}" class="btn btn-primary me-2">
                                            <i class="ri-download-line me-1"></i> Generate Invoice
                                        </a>
                                    @else
                                        <button class="btn btn-primary me-2" disabled title="No payment records available">
                                            <i class="ri-download-line me-1"></i> Generate Invoice
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Tab Navigation -->
                            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mb-5" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="payment-records-tab" data-bs-toggle="tab" href="#payment-records" role="tab" aria-controls="payment-records" aria-selected="true">
                                        Payment Records
                                        @if ($studentpaymentbill->isNotEmpty())
                                            <span class="badge bg-info-subtle text-info ms-2" id="paymentCount">{{ $studentpaymentbill->count() }}</span>
                                        @endif
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="school-bills-tab" data-bs-toggle="tab" href="#school-bills" role="tab" aria-controls="school-bills" aria-selected="false">
                                        School Bills
                                        @if ($student_bill_info->isNotEmpty())
                                            <span class="badge bg-info-subtle text-info ms-2">{{ $student_bill_info->count() }}</span>
                                        @endif
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="payment-history-tab" data-bs-toggle="tab" href="#payment-history" role="tab" aria-controls="payment-history" aria-selected="false">
                                        Payment History
                                        @if ($paymentHistory->isNotEmpty())
                                            <span class="badge bg-info-subtle text-info ms-2">{{ $paymentHistory->count() }}</span>
                                        @endif
                                    </a>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content" id="paymentTabContent">
                                <!-- Payment Records Tab -->
                                <div class="tab-pane fade show active" id="payment-records" role="tabpanel" aria-labelledby="payment-records-tab">
                                    <!-- No Data Alert -->
                                    <div class="alert alert-info text-center" id="noDataAlert" style="display: {{ $studentpaymentbill->isEmpty() ? 'block' : 'none' }};">
                                        <i class="ri-information-line me-2"></i>
                                        No new payment records available for the selected student. Please add payments.
                                    </div>

                                    <!-- Payments Table -->
                                    <div class="table-responsive">
                                        <table class="table table-centered align-middle table-nowrap mb-0" id="paymentsTable">
                                            <thead class="table-active">
                                                <tr>
                                                    <th style="width: 50px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="checkAll">
                                                            <label class="form-check-label" for="checkAll"></label>
                                                        </div>
                                                    </th>
                                                    <th style="width: 50px;" class="sort cursor-pointer" data-sort="sn">SN</th>
                                                    <th class="sort cursor-pointer" data-sort="title">School Bill</th>
                                                    <th class="sort cursor-pointer" data-sort="description">Description</th>
                                                    <th>Bill Amount</th>
                                                    <th>Amount Paid</th>
                                                    <th>Outstanding</th>
                                                    <th>Received By</th>
                                                    <th>Date - Time</th>
                                                    <th>Payment Method</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="paymentsTableBody" class="list form-check-all">
                                                @php $i = 0; @endphp
                                                @forelse ($studentpaymentbill as $sp)
                                                    <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input class="form-check-input payment-checkbox" type="checkbox" name="chk_child" data-id="{{ $sp->recordId }}">
                                                                <label class="form-check-label"></label>
                                                            </div>
                                                        </td>
                                                        <td class="sn">{{ ++$i }}</td>
                                                        <td class="title" data-title="{{ $sp->title }}">{{ $sp->title }}</td>
                                                        <td class="description" data-description="{{ $sp->description }}">{{ $sp->description }}</td>
                                                        <td>₦ {{ number_format($sp->billAmount) }}</td>
                                                        <td>₦ {{ number_format($sp->totalAmountPaid ?? 0) }}</td>
                                                        <td>₦ {{ number_format($sp->balance) }}</td>
                                                        <td>{{ $sp->receivedBy ?? 'Unknown' }}</td>
                                                        <td>{{ $sp->receivedDate ? \Carbon\Carbon::parse($sp->receivedDate)->format('d M Y, H:i') : 'N/A' }}</td>
                                                        <td>{{ $sp->paymentMethod ?? 'N/A' }}</td>
                                                        <td>
                                                            <span class="badge {{ $sp->paymentStatus === 'Completed' ? 'bg-success' : 'bg-danger' }}">{{ $sp->paymentStatus }}</span>
                                                        </td>
                                                        <td>
                                                            <a href="javascript:void(0)" class="btn btn-sm btn-danger delete-payment" data-url="{{ route('schoolpayment.deletestudentpayment', ['recordId' => $sp->recordId]) }}">Delete</a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr id="noDataRow">
                                                        <td colspan="12" class="text-center">No new payment records available.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Payment History Tab -->
                                <div class="tab-pane fade" id="payment-history" role="tabpanel" aria-labelledby="payment-history-tab">
                                    <!-- Search Bar -->
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="historySearchInput" placeholder="Search by bill title or description..." style="min-width: 200px;" {{ $paymentHistory->isEmpty() ? 'disabled' : '' }}>
                                        <button class="btn btn-outline-secondary" type="button" id="historyClearSearch">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    </div>
                                    <!-- No Data Alert -->
                                    <div class="alert alert-info text-center" id="historyNoDataAlert" style="display: {{ $paymentHistory->isEmpty() ? 'block' : 'none' }};">
                                        <i class="ri-information-line me-2"></i>
                                        No payment history available for the selected student.
                                    </div>
                                    <!-- Payment History Table -->
                                    <div class="table-responsive">
                                        <table class="table table-centered align-middle table-nowrap mb-0" id="paymentHistoryTable">
                                            <thead class="table-active">
                                                <tr>
                                                    <th style="width: 50px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="historyCheckAll">
                                                            <label class="form-check-label" for="historyCheckAll"></label>
                                                        </div>
                                                    </th>
                                                    <th style="width: 50px;" class="sort cursor-pointer" data-sort="sn">SN</th>
                                                    <th class="sort cursor-pointer" data-sort="title">School Bill</th>
                                                    <th class="sort cursor-pointer" data-sort="description">Description</th>
                                                    <th>Bill Amount</th>
                                                    <th>Amount Paid</th>
                                                    <th>Outstanding</th>
                                                    <th>Received By</th>
                                                    <th>Date - Time</th>
                                                    <th>Payment Method</th>
                                                    <th>Status</th>
                                                    <th>Invoice</th>
                                                </tr>
                                            </thead>
                                            <tbody id="paymentHistoryTableBody" class="list form-check-all">
                                                @php $i = 0; @endphp
                                                @forelse ($paymentHistory as $ph)
                                                    <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input class="form-check-input history-checkbox" type="checkbox" name="history_chk_child" data-id="{{ $ph->recordId }}">
                                                                <label class="form-check-label"></label>
                                                            </div>
                                                        </td>
                                                        <td class="sn">{{ ++$i }}</td>
                                                        <td class="title" data-title="{{ $ph->title }}">{{ $ph->title }}</td>
                                                        <td class="description" data-description="{{ $ph->description }}">{{ $ph->description }}</td>
                                                        <td>₦ {{ number_format($ph->billAmount) }}</td>
                                                        <td>₦ {{ number_format($ph->totalAmountPaid ?? 0) }}</td>
                                                        <td>₦ {{ number_format($ph->balance) }}</td>
                                                        <td>{{ $ph->receivedBy ?? 'Unknown' }}</td>
                                                        <td>{{ $ph->receivedDate ? \Carbon\Carbon::parse($ph->receivedDate)->format('d M Y, H:i') : 'N/A' }}</td>
                                                        <td>{{ $ph->paymentMethod ?? 'N/A' }}</td>
                                                        <td>
                                                            <span class="badge {{ $ph->paymentStatus === 'Completed' ? 'bg-success' : 'bg-danger' }}">{{ $ph->paymentStatus }}</span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('schoolpayment.invoice', ['studentId' => $studentId, 'schoolclassid' => $ph->classId, 'termid' => $ph->termId, 'sessionid' => $ph->sessionId]) }}" class="btn btn-sm btn-info">
                                                                <i class="ri-file-download-line me-1"></i> View Invoice
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr id="historyNoDataRow">
                                                        <td colspan="12" class="text-center">No payment history available.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                     <!-- Download Payment Statement Button -->
                                    <div class="mb-3">
                                        <a href="{{ route('schoolpayment.statement', ['studentId' => $studentId, 'schoolclassid' => $schoolclassId, 'termid' => $termid, 'sessionid' => $sessionid]) }}" class="btn btn-primary">
                                            Download Payment Statement
                                        </a>
                                    </div>

                                </div>

                                <!-- School Bills Tab -->
                                <div class="tab-pane fade" id="school-bills" role="tabpanel" aria-labelledby="school-bills-tab">
                                    @if ($student_bill_info->isNotEmpty())
                                        <div class="row g-3">
                                            @foreach ($student_bill_info as $sc)
                                                @php
                                                    $paymentFound = false;
                                                    $amountPaid = 0;
                                                    $balance = $sc->amount;
                                                    foreach ($studentpaymentbillbook as $paymentBook) {
                                                        if ((int)$paymentBook->school_bill_id === (int)$sc->schoolbillid) {
                                                            $paymentFound = true;
                                                            $amountPaid = $paymentBook->amount_paid;
                                                            $balance = $paymentBook->amount_owed;
                                                            break;
                                                        }
                                                    }
                                                    $totalLastPayment = \App\Models\StudentBillPayment::where('student_id', $studentId)
                                                        ->where('student_bill_payment.class_id', $schoolclassId)
                                                        ->where('student_bill_payment.termid_id', $termid)
                                                        ->where('student_bill_payment.session_id', $sessionid)
                                                        ->where('school_bill_id', $sc->schoolbillid)
                                                        ->leftJoin('student_bill_payment_record', 'student_bill_payment_record.student_bill_payment_id', '=', 'student_bill_payment.id')
                                                        ->sum(DB::raw('CAST(student_bill_payment_record.amount_paid AS DECIMAL(10, 2))'));
                                                    if ($totalLastPayment > 0) {
                                                        $amountPaid = $totalLastPayment;
                                                        $balance = $sc->amount - $amountPaid;
                                                    }
                                                    $progressPercentage = $sc->amount > 0 ? ($amountPaid / $sc->amount) * 100 : 0;
                                                    $isPaidInFull = (float)$sc->amount === (float)$amountPaid;
                                                    $paymentExists = $studentpaymentbill->where('school_bill_id', $sc->schoolbillid)->isNotEmpty();
                                                    $paymentRecord = $studentpaymentbill->where('school_bill_id', $sc->schoolbillid)->first();
                                                    $invoicePending = $paymentExists && $paymentRecord && $paymentRecord->delete_status == '1';
                                                @endphp
                                                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                                    <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden" style="border-radius: 12px; transition: all 0.3s ease;">
                                                        <!-- Status indicator stripe -->
                                                        <div class="position-absolute top-0 start-0 w-100" style="height: 3px; background: {{ $isPaidInFull ? 'linear-gradient(90deg, #10b981, #059669)' : 'linear-gradient(90deg, #f59e0b, #d97706)' }};"></div>
                                                        <div class="card-body p-4">
                                                            <!-- Header Section -->
                                                            <div class="d-flex align-items-start justify-content-between mb-3">
                                                                <div class="flex-grow-1">
                                                                    <h6 class="card-title mb-1 fw-bold text-gray-900" style="font-size: 1rem; line-height: 1.3;">
                                                                        {{ $sc->title }}
                                                                    </h6>
                                                                    <span class="badge {{ $isPaidInFull ? 'bg-success' : 'bg-warning' }} bg-opacity-10 {{ $isPaidInFull ? 'text-success' : 'text-warning' }} px-2 py-1 rounded-pill fw-medium" style="font-size: 0.65rem;">
                                                                        <i class="fas {{ $isPaidInFull ? 'fa-check-circle' : 'fa-clock' }} me-1"></i>
                                                                        {{ $isPaidInFull ? 'Paid' : $sc->description }}
                                                                    </span>
                                                                </div>
                                                                <!-- Payment status icon -->
                                                                <div class="ms-2">
                                                                    <div class="d-flex align-items-center justify-content-center rounded-circle {{ $isPaidInFull ? 'bg-success' : 'bg-warning' }} bg-opacity-10" style="width: 32px; height: 32px;">
                                                                        <i class="fas {{ $isPaidInFull ? 'fa-check' : 'fa-credit-card' }} {{ $isPaidInFull ? 'text-success' : 'text-warning' }}" style="font-size: 0.9rem;"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Amount Information -->
                                                            <div class="mb-3">
                                                                <div class="text-center mb-3">
                                                                    <div class="fs-5 fw-bold text-primary">₦{{ number_format($sc->amount) }}</div>
                                                                    <div class="fs-7 text-muted">Total Amount</div>
                                                                </div>
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <div class="text-center p-2 bg-success bg-opacity-10 rounded-2">
                                                                            <div class="fs-7 fw-bold text-success mb-0">₦{{ number_format($amountPaid) }}</div>
                                                                            <div class="fs-8 text-muted">Paid</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="text-center p-2 {{ $balance > 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10' }} rounded-2">
                                                                            <div class="fs-7 fw-bold {{ $balance > 0 ? 'text-danger' : 'text-success' }} mb-0">
                                                                                ₦{{ number_format($balance) }}
                                                                            </div>
                                                                            <div class="fs-8 text-muted">Balance</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Progress Bar -->
                                                            <div class="mb-3">
                                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                                    <span class="fs-8 text-muted">Progress</span>
                                                                    <span class="fs-8 fw-bold {{ $isPaidInFull ? 'text-success' : 'text-primary' }}">
                                                                        {{ number_format($progressPercentage, 0) }}%
                                                                    </span>
                                                                </div>
                                                                <div class="progress rounded-pill" style="height: 6px;">
                                                                    <div class="progress-bar {{ $isPaidInFull ? 'bg-success' : 'bg-primary' }} rounded-pill" role="progressbar" style="width: {{ $progressPercentage }}%; transition: width 0.6s ease;" aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Action Button -->
                                                            <div class="d-grid">
                                                                @if ($isPaidInFull)
                                                                    <button class="btn btn-success btn-sm rounded-pill py-2 fw-medium" disabled>
                                                                        <i class="fas fa-check-circle me-1"></i>
                                                                        Complete
                                                                    </button>
                                                                @else
                                                                    <button class="btn btn-primary btn-sm rounded-pill py-2 fw-medium make-payment"
                                                                            @if ($invoicePending) disabled title="Cannot make payment until invoice is generated or previous payment is deleted" @endif
                                                                            data-student_id="{{ $studentId }}"
                                                                            data-amount="{{ number_format($sc->amount) }}"
                                                                            data-amount_actual="{{ $sc->amount }}"
                                                                            data-amount_paid="{{ number_format($amountPaid) }}"
                                                                            data-balance="{{ number_format($balance) }}"
                                                                            data-school_bill_id="{{ $sc->schoolbillid }}"
                                                                            data-class_id="{{ $schoolclassId }}"
                                                                            data-term_id="{{ $termid }}"
                                                                            data-session_id="{{ $sessionid }}"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#paymentModal"
                                                                            style="background: #3b82f6; border: none; color: white; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3); transition: all 0.3s ease;">
                                                                        <i class="fas fa-credit-card me-1"></i>
                                                                        Make Payment
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            <!-- Debug: Log button data -->
                                                            @if (!$isPaidInFull)
                                                                <script>
                                                                    try {
                                                                        console.log('Make Payment Button Data for Bill {{ \Illuminate\Support\Js::from($sc->schoolbillid) }}:', {
                                                                            student_id: {{ \Illuminate\Support\Js::from($studentId) }},
                                                                            amount: {{ \Illuminate\Support\Js::from(number_format($sc->amount)) }},
                                                                            amount_actual: {{ \Illuminate\Support\Js::from($sc->amount) }},
                                                                            amount_paid: {{ \Illuminate\Support\Js::from(number_format($amountPaid)) }},
                                                                            balance: {{ \Illuminate\Support\Js::from(number_format($balance)) }},
                                                                            school_bill_id: {{ \Illuminate\Support\Js::from($sc->schoolbillid) }},
                                                                            class_id: {{ \Illuminate\Support\Js::from($schoolclassId) }},
                                                                            term_id: {{ \Illuminate\Support\Js::from($termid) }},
                                                                            session_id: {{ \Illuminate\Support\Js::from($sessionid) }},
                                                                            invoicePending: {{ $invoicePending ? 'true' : 'false' }}
                                                                        });
                                                                    } catch (e) {
                                                                        console.error('Error logging button data for bill {{ \Illuminate\Support\Js::from($sc->schoolbillid) }}:', e);
                                                                    }
                                                                </script>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <div class="card border-0 shadow-sm mx-auto" style="max-width: 400px; border-radius: 16px;">
                                                <div class="card-body p-5">
                                                    <div class="mb-4">
                                                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 mx-auto mb-3" style="width: 80px; height: 80px;">
                                                            <i class="fas fa-info-circle text-info" style="font-size: 2rem;"></i>
                                                        </div>
                                                    </div>
                                                    <h5 class="card-title mb-3 text-gray-900">No Bills Available</h5>
                                                    <p class="text-muted mb-0">No school bills are currently available for the selected student.</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <!-- Custom CSS for enhanced interactions -->
                                    <style>
                                        .card:hover {
                                            transform: translateY(-4px);
                                            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
                                        }
                                        .make-payment:hover {
                                            transform: translateY(-1px);
                                            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4) !important;
                                            background: #1d4ed8 !important;
                                        }
                                        .progress-bar {
                                            background: linear-gradient(90deg, #3b82f6, #1d4ed8) !important;
                                        }
                                        .bg-success.progress-bar {
                                            background: linear-gradient(90deg, #10b981, #059669) !important;
                                        }
                                        .card-title {
                                            color: inherit;
                                            line-height: 1.4;
                                        }
                                        .badge {
                                            font-size: 0.75rem;
                                            font-weight: 600;
                                            letter-spacing: 0.02em;
                                        }
                                        @media (max-width: 767px) {
                                            .col-6 {
                                                margin-bottom: 0.5rem;
                                            }
                                            .card-body {
                                                padding: 1rem !important;
                                            }
                                            .fs-5 {
                                                font-size: 1.1rem !important;
                                            }
                                        }
                                        .fs-8 {
                                            font-size: 0.65rem;
                                        }
                                    </style>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Modal -->
                <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title fw-bold">Make Payment</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form id="paymentForm" action="{{ route('schoolpayment.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" id="student_id" name="student_id">
                                    <input type="hidden" id="class_id" name="class_id">
                                    <input type="hidden" id="term_id" name="term_id">
                                    <input type="hidden" id="session_id" name="session_id">
                                    <input type="hidden" id="school_bill_id" name="school_bill_id">
                                    <input type="hidden" id="actual_amount" name="actual_amount">
                                    <input type="hidden" id="balance2" name="balance2">
                                    <input type="hidden" id="last_amount_paid" name="last_amount_paid">
                                    <!-- Bill Information (Read-only) -->
                                    <div class="form-group mb-3">
                                        <label class="required fw-semibold fs-6 mb-2">Bill Amount</label>
                                        <input type="text" id="amount_d" name="amount_d" class="form-control form-control-sm @error('amount_d') is-invalid @enderror" readonly required>
                                        @error('amount_d')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold fs-6 mb-2">Amount Paid</label>
                                        <input type="text" id="amount_paid_d" class="form-control form-control-sm" readonly>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold fs-6 mb-2">Outstanding Balance</label>
                                        <input type="text" id="balance_d" class="form-control form-control-sm bg-warning bg-opacity-10" readonly>
                                        <div class="form-text text-muted">
                                            <small><i class="fas fa-info-circle me-1"></i>Payment amount cannot exceed this balance</small>
                                        </div>
                                    </div>
                                    <!-- Payment Input -->
                                    <div class="form-group mb-3">
                                        <label class="required fw-semibold fs-6 mb-2">Enter Payment Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="text" id="payment_amount" name="payment_amount" class="form-control form-control-sm @error('payment_amount') is-invalid @enderror" placeholder="0.00" required>
                                        </div>
                                        <input type="hidden" id="payment_amount2" name="payment_amount2">
                                        @error('payment_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <!-- Payment Method -->
                                    <div class="form-group mb-3">
                                        <label class="required fw-semibold fs-6 mb-2">Payment Method</label>
                                        <select id="payment_method2" name="payment_method2" class="form-select form-select-sm @error('payment_method2') is-invalid @enderror" required>
                                            <option value="" disabled selected>Select Payment Method</option>
                                            <option value="Bank Deposit" {{ old('payment_method2') == 'Bank Deposit' ? 'selected' : '' }}>Bank Deposit / Bank Teller</option>
                                            <option value="School POS" {{ old('payment_method2') == 'School POS' ? 'selected' : '' }}>School POS/Cash</option>
                                            <option value="Bank Transfer" {{ old('payment_method2') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="Cheque" {{ old('payment_method2') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                                        </select>
                                        @error('payment_method2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <!-- Form Action Buttons -->
                                    <div class="text-center pt-3">
                                        <button type="button" class="btn btn-outline-secondary me-3" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="submitPayment">
                                            <i class="fas fa-credit-card me-1"></i> Make Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Modal -->
                <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="headerModalTitle">Notification</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="messageModalBody">
                                <!-- Message will be injected here -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Modal -->
                <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Deletion</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to delete this payment record?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" id="cancelDeleteButton" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CSS for Payment Modal -->
                <style>
                    #paymentModal .modal-dialog {
                        max-width: 500px;
                        width: 90%;
                    }
                    #paymentModal .modal-content {
                        border-radius: 12px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                        max-height: 90vh;
                    }
                    #paymentModal .modal-header {
                        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
                        color: white;
                        border-bottom: none;
                        border-radius: 12px 12px 0 0;
                    }
                    #paymentModal .modal-body {
                        padding: 1.5rem;
                        overflow-y: auto;
                        max-height: calc(90vh - 120px);
                    }
                    #paymentModal .form-group {
                        margin-bottom: 1rem;
                    }
                    #paymentModal .form-control-sm {
                        font-size: 0.875rem;
                        padding: 0.5rem 0.75rem;
                    }
                    #paymentModal .btn-primary {
                        background-color: #3b82f6;
                        border: none;
                        border-radius: 8px;
                        padding: 0.5rem 1.5rem;
                        transition: background-color 0.3s ease;
                    }
                    #paymentModal .btn-primary:hover {
                        background-color: #1d4ed8;
                        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.75);
                    }
                    @media (max-width: 576px) {
                        #paymentModal .modal-dialog {
                            margin: 0.5rem;
                        }
                        #paymentModal .modal-body {
                            padding: 1rem;
                        }
                    }
                </style>

                <!-- CSS for Confirmation Modal -->
                <style>
                    #confirmModal .modal-content {
                        border-radius: 12px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                    }
                    #confirmModal .modal-header {
                        background: linear-gradient(90deg, #ef4444, #b91c1c);
                        color: white;
                        border-bottom: none;
                    }
                    #confirmModal .modal-body {
                        font-size: 1rem;
                        color: inherit;
                        text-align: center;
                        padding: 1.5rem;
                    }
                    #confirmModal .modal-footer {
                        border-top: none;
                        justify-content: center;
                    }
                    #confirmModal .btn-danger {
                        background: #ef4444;
                        border: none;
                        border-radius: 8px;
                        padding: 0.5rem 1.5rem;
                        transition: all 0.3s ease;
                    }
                    #confirmModal .btn-danger:hover {
                        background: #b91c1c;
                        box-shadow: 0 4px 8px rgba(239, 64, 64, 0.3);
                    }
                </style>

                <!-- CSS for Message Modal -->
                <style>
                    #messageModal .modal-content {
                        border-radius: 12px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                    }
                    #messageModal .modal-header {
                        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
                        color: white;
                        border-bottom: 1px solid #1f40af;
                        border-radius: 12px 12px 0 0;
                    }
                    #messageModal .modal-body {
                        font-size: 1rem;
                        color: inherit;
                        text-align: center;
                        padding: 1.5rem;
                    }
                    #messageModal .modal-footer {
                        border-top: none;
                        justify-content: center;
                    }
                    #messageModal .btn-primary {
                        background-color: #3b82f6;
                        border: none;
                        border-radius: 8px;
                        padding: 0.5rem 1.5rem;
                        transition: background-color 0.3s ease;
                    }
                    #messageModal .btn-primary:hover {
                        background-color: #1d4ed8;
                        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.75);
                    }
                </style>

                <!-- JavaScript -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('DOM fully loaded, initializing scripts');

                        // Search Functionality for Payment Records
                        const searchInput = document.getElementById('searchInput');
                        const clearSearch = document.getElementById('clearSearch');
                        const tableBody = document.getElementById('paymentsTableBody');
                        const noDataAlert = document.getElementById('noDataAlert');

                        if (searchInput && tableBody && noDataAlert) {
                            searchInput.addEventListener('input', function() {
                                const query = this.value.trim().toLowerCase();
                                const rows = tableBody.querySelectorAll('tr:not(#noDataRow)');
                                let hasVisibleRows = false;

                                rows.forEach(row => {
                                    const title = (row.querySelector('.title')?.getAttribute('data-title') || '').toLowerCase();
                                    const description = (row.querySelector('.description')?.getAttribute('data-description') || '').toLowerCase();
                                    const isMatch = title.includes(query) || description.includes(query);
                                    row.style.display = isMatch ? '' : 'none';
                                    if (isMatch) hasVisibleRows = true;
                                });

                                noDataAlert.style.display = hasVisibleRows ? 'none' : 'block';
                            });

                            clearSearch.addEventListener('click', function() {
                                searchInput.value = '';
                                const rows = tableBody.querySelectorAll('tr:not(#noDataRow)');
                                rows.forEach(row => row.style.display = '');
                                noDataAlert.style.display = rows.length > 0 ? 'none' : 'block';
                            });
                        }

                        // Search Functionality for Payment History
                        const historySearchInput = document.getElementById('historySearchInput');
                        const historyClearSearch = document.getElementById('historyClearSearch');
                        const historyTableBody = document.getElementById('paymentHistoryTableBody');
                        const historyNoDataAlert = document.getElementById('historyNoDataAlert');

                        if (historySearchInput && historyTableBody && historyNoDataAlert) {
                            historySearchInput.addEventListener('input', function() {
                                const query = this.value.trim().toLowerCase();
                                const rows = historyTableBody.querySelectorAll('tr:not(#historyNoDataRow)');
                                let hasVisibleRows = false;

                                rows.forEach(row => {
                                    const title = (row.querySelector('.title')?.getAttribute('data-title') || '').toLowerCase();
                                    const description = (row.querySelector('.description')?.getAttribute('data-description') || '').toLowerCase();
                                    const isMatch = title.includes(query) || description.includes(query);
                                    row.style.display = isMatch ? '' : 'none';
                                    if (isMatch) hasVisibleRows = true;
                                });

                                historyNoDataAlert.style.display = hasVisibleRows ? 'none' : 'block';
                            });

                            historyClearSearch.addEventListener('click', function() {
                                historySearchInput.value = '';
                                const rows = historyTableBody.querySelectorAll('tr:not(#historyNoDataRow)');
                                rows.forEach(row => row.style.display = '');
                                historyNoDataAlert.style.display = rows.length > 0 ? 'none' : 'block';
                            });
                        }

                        // Checkbox Select All for Payment Records
                        const checkAll = document.getElementById('checkAll');
                        const checkboxes = document.querySelectorAll('.payment-checkbox');

                        if (checkAll) {
                            checkAll.addEventListener('change', function() {
                                checkboxes.forEach(checkbox => {
                                    checkbox.checked = this.checked;
                                });
                            });

                            checkboxes.forEach(checkbox => {
                                checkbox.addEventListener('change', function() {
                                    checkAll.checked = Array.from(checkboxes).every(c => c.checked);
                                });
                            });
                        }

                        // Checkbox Select All for Payment History
                        const historyCheckAll = document.getElementById('historyCheckAll');
                        const historyCheckboxes = document.querySelectorAll('.history-checkbox');

                        if (historyCheckAll) {
                            historyCheckAll.addEventListener('change', function() {
                                historyCheckboxes.forEach(checkbox => {
                                    checkbox.checked = this.checked;
                                });
                            });

                            historyCheckboxes.forEach(checkbox => {
                                checkbox.addEventListener('change', function() {
                                    historyCheckAll.checked = Array.from(historyCheckboxes).every(c => c.checked);
                                });
                            });
                        }

                        // Populate Payment Modal
                        const paymentButtons = document.querySelectorAll('.make-payment');
                        console.log('Found', paymentButtons.length, 'make-payment buttons');

                        paymentButtons.forEach((button, index) => {
                            button.addEventListener('click', function(e) {
                                console.log(`Make Payment button ${index} clicked`);
                                try {
                                    const data = {
                                        student_id: button.getAttribute('data-student_id') || '',
                                        amount: button.getAttribute('data-amount') || '',
                                        amount_actual: button.getAttribute('data-amount_actual') || '',
                                        amount_paid: button.getAttribute('data-amount_paid') || '',
                                        balance: button.getAttribute('data-balance') || '',
                                        school_bill_id: button.getAttribute('data-school_bill_id') || '',
                                        class_id: button.getAttribute('data-class_id') || '',
                                        term_id: button.getAttribute('data-term_id') || '',
                                        session_id: button.getAttribute('data-session_id') || ''
                                    };
                                    console.log(`Button ${index} attributes:`, data);

                                    // Validate data
                                    if (!data.student_id || !data.class_id || !data.term_id || !data.session_id || !data.school_bill_id) {
                                        throw new Error('Missing required data attributes');
                                    }

                                    // Populate modal fields
                                    document.querySelector('#student_id').value = data.student_id;
                                    document.querySelector('#class_id').value = data.class_id;
                                    document.querySelector('#term_id').value = data.term_id;
                                    document.querySelector('#session_id').value = data.session_id;
                                    document.querySelector('#school_bill_id').value = data.school_bill_id;
                                    document.querySelector('#amount_d').value = data.amount ? '₦' + data.amount : '';
                                    document.querySelector('#amount_paid_d').value = data.amount_paid ? '₦' + data.amount_paid : '₦0';
                                    document.querySelector('#balance_d').value = data.balance ? '₦' + data.balance : '₦0';
                                    document.querySelector('#actual_amount').value = data.amount_actual ? parseFloat(data.amount_actual).toFixed(2) : '0.00';
                                    document.querySelector('#balance2').value = data.balance ? parseFloat(data.balance.replace(/[^0-9.]/g, '')).toFixed(2) : '0.00';
                                    document.querySelector('#last_amount_paid').value = data.amount_paid ? parseFloat(data.amount_paid.replace(/[^0-9.]/g, '')).toFixed(2) : '0.00';
                                    document.querySelector('#payment_amount').value = '';
                                    document.querySelector('#payment_amount2').value = '';
                                    document.querySelector('#payment_method2').value = '';

                                    console.log('Modal fields populated:', {
                                        student_id: document.querySelector('#student_id').value,
                                        amount_d: document.querySelector('#amount_d').value,
                                        amount_paid_d: document.querySelector('#amount_paid_d').value,
                                        balance_d: document.querySelector('#balance_d').value
                                    });

                                    // Open modal
                                    const modal = document.querySelector('#paymentModal');
                                    if (typeof bootstrap === 'undefined') {
                                        throw new Error('Bootstrap not loaded');
                                    }
                                    const paymentModal = new bootstrap.Modal(modal);
                                    paymentModal.show();
                                    console.log('Payment modal opened');
                                } catch (error) {
                                    console.error('Error populating modal:', error);
                                    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                                    document.getElementById('messageModalBody').textContent = 'Error opening payment modal: ' + error.message;
                                    document.getElementById('headerModalTitle').textContent = 'Error';
                                    messageModal.show();
                                }
                            });
                        });

                        // Payment Amount Validation
                        const paymentForm = document.querySelector('#paymentForm');
                        const paymentModalElement = document.querySelector('#paymentModal');
                        const paymentAmountInput = document.querySelector('#payment_amount');
                        const paymentAmountHidden = document.querySelector('#payment_amount2');
                        const billAmountInput = document.querySelector('#amount_d');

                        if (paymentAmountInput && paymentAmountHidden) {
                            paymentAmountInput.addEventListener('input', function() {
                                let value = this.value.replace(/[^0-9.]/g, '');
                                const balance = parseFloat(document.querySelector('#balance2')?.value || 0);
                                const amount = parseFloat(value);

                                if (isNaN(amount) || amount <= 0) {
                                    this.classList.add('is-invalid');
                                    this.parentElement.querySelector('.invalid-feedback').textContent = 'Enter a valid amount greater than 0.';
                                } else if (amount > balance) {
                                    this.classList.add('is-invalid');
                                    this.parentElement.querySelector('.invalid-feedback').textContent = 'Amount cannot exceed outstanding balance.';
                                } else {
                                    this.classList.remove('is-invalid');
                                    this.parentElement.querySelector('.invalid-feedback').textContent = '';
                                }

                                paymentAmountHidden.value = isNaN(amount) ? '' : amount.toFixed(2);
                            });
                        }

                        // Form Submission
                        if (paymentForm) {
                            paymentForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                console.log('Payment form submitted');

                                // Validate Bill Amount
                                if (billAmountInput) {
                                    const billAmountValue = billAmountInput.value.trim();
                                    if (!billAmountValue || billAmountValue === '₦0' || isNaN(parseFloat(billAmountValue.replace(/[^0-9.]/g, '')))) {
                                        billAmountInput.classList.add('is-invalid');
                                        billAmountInput.parentElement.insertAdjacentHTML('afterend', '<div class="invalid-feedback">Bill Amount is required and must be greater than 0.</div>');
                                        return;
                                    } else {
                                        billAmountInput.classList.remove('is-invalid');
                                        const existingFeedback = billAmountInput.parentElement.querySelector('.invalid-feedback');
                                        if (existingFeedback) existingFeedback.remove();
                                    }
                                }

                                // Validate Payment Amount
                                let value = paymentAmountInput.value.replace(/[^0-9.]/g, '');
                                const balance = parseFloat(document.querySelector('#balance2')?.value || 0);
                                const amount = parseFloat(value);

                                if (isNaN(amount) || amount <= 0) {
                                    paymentAmountInput.classList.add('is-invalid');
                                    paymentAmountInput.parentElement.querySelector('.invalid-feedback').textContent = 'Enter a valid amount greater than 0.';
                                    return;
                                } else if (amount > balance) {
                                    paymentAmountInput.classList.add('is-invalid');
                                    paymentAmountInput.parentElement.querySelector('.invalid-feedback').textContent = 'Amount cannot exceed outstanding balance.';
                                    return;
                                }

                                // Validate Payment Method
                                const paymentMethodSelect = document.querySelector('#payment_method2');
                                if (!paymentMethodSelect?.value) {
                                    paymentMethodSelect.classList.add('is-invalid');
                                    paymentMethodSelect.parentElement.insertAdjacentHTML('afterend', '<div class="invalid-feedback">Select a payment method.</div>');
                                    return;
                                } else {
                                    paymentMethodSelect.classList.remove('is-invalid');
                                    const existingFeedback = paymentMethodSelect.parentElement.querySelector('.invalid-feedback');
                                    if (existingFeedback) existingFeedback.remove();
                                }

                                paymentAmountHidden.value = value;

                                const formData = new FormData(this);
                                console.log('Form Data:', Object.fromEntries(formData));

                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                    }
                                })
                                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                                .then(({ status, body }) => {
                                    console.log('Response:', { status, body });
                                    const paymentModalInstance = bootstrap.Modal.getInstance(paymentModalElement);
                                    paymentModalInstance.hide();
                                    document.body.classList.remove('modal-open');
                                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                                    document.body.style.overflow = '';

                                    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                                    if (status === 200 && body.success) {
                                        document.getElementById('messageModalBody').textContent = body.message || 'Payment processed successfully.';
                                        document.getElementById('headerModalTitle').textContent = 'Success';
                                        messageModal.show();
                                        if (body.redirect_url) {
                                            setTimeout(() => window.location.href = body.redirect_url, 1000);
                                        }
                                    } else {
                                        let errorMessage = body.message || 'Error processing payment.';
                                        if (status === 422 && body.errors) {
                                            errorMessage = Object.values(body.errors).flat().join('\n');
                                        }
                                        document.getElementById('messageModalBody').textContent = errorMessage;
                                        document.getElementById('headerModalTitle').textContent = 'Error';
                                        messageModal.show();
                                    }
                                })
                                .catch(error => {
                                    console.error('Fetch Error:', error);
                                    const paymentModalInstance = bootstrap.Modal.getInstance(paymentModalElement);
                                    paymentModalInstance.hide();
                                    document.body.classList.remove('modal-open');
                                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                                    document.body.style.overflow = '';
                                    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                                    document.getElementById('messageModalBody').textContent = 'Unexpected error: ' + error.message;
                                    document.getElementById('headerModalTitle').textContent = 'Error';
                                    messageModal.show();
                                });
                            });
                        }

                        // Delete Payment
                        const deleteButtons = document.querySelectorAll('.delete-payment');
                        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
                        const confirmModal = document.getElementById('confirmModal');
                        let deleteUrl = '';

                        deleteButtons.forEach(button => {
                            button.addEventListener('click', function() {
                                deleteUrl = this.getAttribute('data-url');
                                console.log('Delete button clicked, URL:', deleteUrl);
                                if (deleteUrl) {
                                    new bootstrap.Modal(confirmModal).show();
                                }
                            });
                        });

                        if (confirmDeleteBtn) {
                            confirmDeleteBtn.addEventListener('click', function() {
                                console.log('Confirm delete clicked, URL:', deleteUrl);
                                const confirmModalInstance = bootstrap.Modal.getInstance(confirmModal);
                                confirmModalInstance.hide();
                                document.body.classList.remove('modal-open');
                                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                                document.body.style.overflow = '';

                                fetch(deleteUrl, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({})
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! Status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    console.log('Delete response:', data);
                                    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                                    document.getElementById('messageModalBody').textContent = data.message || (data.success ? 'Payment deleted successfully.' : 'Failed to delete payment.');
                                    document.getElementById('headerModalTitle').textContent = data.success ? 'Success' : 'Error';
                                    messageModal.show();
                                    if (data.success) {
                                        setTimeout(() => window.location.reload(), 1000);
                                    }
                                })
                                .catch(error => {
                                    console.error('Delete Error:', error);
                                    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                                    document.getElementById('messageModalBody').textContent = 'Error deleting payment: ' + error.message;
                                    document.getElementById('headerModalTitle').textContent = 'Error';
                                    messageModal.show();
                                });
                            });
                        }
                    });
                </script>
            </div>
        </div>
    </div>
</div>
@endsection