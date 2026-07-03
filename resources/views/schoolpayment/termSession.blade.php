@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Main content container -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
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

            <!-- Term and Session Selection -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">Select Term and Session for Student Payments</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <a href="{{ route('schoolpayment.index') }}" class="btn btn-primary">
                                    <i class="ri-arrow-left-line"></i> Back to Students
                                </a>
                            </div>

                            <!-- Form -->
                            <form id="termSessionForm" action="{{ route('schoolpayment.termsessionpayments') }}" method="GET">
                                @csrf
                                <input type="hidden" name="studentId" value="{{ $id }}">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="required fw-semibold fs-6 mb-2">Select Term</label>
                                        <select name="termid" id="termid" class="form-select form-select-solid form-select-lg fw-semibold fs-6 text-gray-700" required>
                                            <option value="">Select Term</option>
                                            @foreach ($schoolterms as $term)
                                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="required fw-semibold fs-6 mb-2">Select Session</label>
                                        <select name="sessionid" id="sessionid" class="form-select form-select-solid form-select-lg fw-semibold fs-6 text-gray-700" required>
                                            <option value="">Select Session</option>
                                            @foreach ($schoolsessions as $session)
                                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="text-center pt-10">
                                    <button type="reset" class="btn btn-outline-secondary me-3">Discard</button>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('termSessionForm');
    form.addEventListener('submit', function (e) {
        const termid = document.getElementById('termid').value;
        const sessionid = document.getElementById('sessionid').value;
        if (!termid || !sessionid) {
            e.preventDefault();
            alert('Please select both term and session.');
        }
    });
});
</script>
@endsection
