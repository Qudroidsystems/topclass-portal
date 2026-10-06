{{-- resources/views/finance/payroll/statutory.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.report-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.report-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: white;
    padding: 15px 20px;
}
.stat-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold">Statutory Remittance Report</h4>
            <p class="text-muted">PAYE, Pension, NHF and other statutory deductions summary</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <select id="yearSelect" class="form-select">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary" id="filterBtn">
                <i class="ri-search-line me-1"></i>Filter
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-danger fs-2 fw-bold">₦{{ number_format($totalPaye, 2) }}</div>
                <small>Total PAYE Tax</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-primary fs-2 fw-bold">₦{{ number_format($totalEmployeePension, 2) }}</div>
                <small>Employee Pension</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-info fs-2 fw-bold">₦{{ number_format($totalEmployerPension, 2) }}</div>
                <small>Employer Pension</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-warning fs-2 fw-bold">₦{{ number_format($totalNhf, 2) }}</div>
                <small>Total NHF</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Monthly Statutory Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="statutoryTable">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th>Period</th>
                            <th class="text-end">PAYE (₦)</th>
                            <th class="text-end">Employee Pension (₦)</th>
                            <th class="text-end">Employer Pension (₦)</th>
                            <th class="text-end">NHF (₦)</th>
                            <th class="text-end">Net Pay (₦)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                        <tr>
                            <td>{{ date('F', mktime(0, 0, 0, $row->month, 1)) }}</td>
                            <td>{{ $row->period_name }}</td>
                            <td class="text-end">₦{{ number_format($row->paye, 2) }}</td>
                            <td class="text-end">₦{{ number_format($row->employee_pension, 2) }}</td>
                            <td class="text-end">₦{{ number_format($row->employer_pension, 2) }}</td>
                            <td class="text-end">₦{{ number_format($row->nhf, 2) }}</td>
                            <td class="text-end">₦{{ number_format($row->total_net_pay, 2) }}</td>
                        </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No statutory data available</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-active">
                        <tr>
                            <th colspan="2" class="text-end">Totals:</th>
                            <th class="text-end">₦{{ number_format($totalPaye, 2) }}</th>
                            <th class="text-end">₦{{ number_format($totalEmployeePension, 2) }}</th>
                            <th class="text-end">₦{{ number_format($totalEmployerPension, 2) }}</th>
                            <th class="text-end">₦{{ number_format($totalNhf, 2) }}</th>
                            <th class="text-end">₦{{ number_format($data->sum('total_net_pay'), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<script>
$(document).ready(function() {
    $('#filterBtn').on('click', function() {
        var year = $('#yearSelect').val();
        window.location.href = '{{ route("payroll.statutory") }}?year=' + year;
    });
});
</script>
@endsection
