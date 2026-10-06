{{-- resources/views/finance/payroll/summary.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.payroll-summary-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
}
.summary-stat {
    background: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s;
}
.summary-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="payroll-summary-hero">
        <h1 class="text-white"><i class="ri-file-chart-line me-2"></i>{{ $pagetitle }}</h1>
        <p class="text-white-50 mb-0">Annual payroll summary with statutory deductions breakdown.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <select id="yearSelect" class="form-select form-select-lg">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-9 text-end">
            <button class="btn btn-success" id="exportExcelBtn"><i class="ri-file-excel-line me-1"></i>Export Excel</button>
            <button class="btn btn-danger" id="exportPdfBtn"><i class="ri-file-pdf-line me-1"></i>Export PDF</button>
        </div>
    </div>

    <div class="row g-3 mb-4" id="summaryStats"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="ri-table-line me-2"></i>Monthly Payroll Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover w-100" id="payrollSummaryTable">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Period</th>
                            <th>Gross Pay (₦)</th>
                            <th>PAYE (₦)</th>
                            <th>Pension (₦)</th>
                            <th>NHF (₦)</th>
                            <th>Net Pay (₦)</th>
                            <th>Employer Cost (₦)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3"><h5 class="mb-0"><i class="ri-tax-line me-2"></i>Statutory Remittance Summary</h5></div>
                <div class="card-body"><canvas id="statutoryChart" height="250"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3"><h5 class="mb-0"><i class="ri-trending-up-line me-2"></i>Monthly Payroll Trend</h5></div>
                <div class="card-body"><canvas id="trendChart" height="250"></canvas></div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
let summaryTable, statutoryChart, trendChart;

$(document).ready(function() {
    summaryTable = $('#payrollSummaryTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 12,
        ordering: false,
        ajax: {
            url: '{{ route("payroll.summary") }}',
            type: 'GET',
            data: function(d) {
                d.year = $('#yearSelect').val();
            }
        },
        columns: [
            { data: 'month', name: 'month' },
            { data: 'period_name', name: 'period_name' },
            { data: 'gross_pay', name: 'gross_pay' },
            { data: 'paye', name: 'paye' },
            { data: 'pension', name: 'pension' },
            { data: 'nhf', name: 'nhf' },
            { data: 'net_pay', name: 'net_pay' },
            { data: 'employer_cost', name: 'employer_cost' }
        ]
    });

    $('#yearSelect').on('change', function() {
        summaryTable.ajax.reload();
        loadYearStats();
    });

    $('#exportExcelBtn').on('click', () => Swal.fire('Export Started', 'Excel file will download shortly', 'success'));
    $('#exportPdfBtn').on('click', () => Swal.fire('Export Started', 'PDF file will download shortly', 'success'));

    loadYearStats();
});

function loadYearStats() {
    const year = $('#yearSelect').val();
    $.ajax({
        url: '{{ route("payroll.summary") }}',
        data: { year: year, stats: true },
        success: function(response) {
            if (response.success) {
                updateStatsAndCharts(response);
            }
        }
    });
}

function updateStatsAndCharts(response) {
    const s = response.stats;
    $('#summaryStats').html(`
        <div class="col-md-3"><div class="summary-stat"><div class="fs-2 fw-bold text-primary">₦${(s.total_gross || 0).toLocaleString()}</div><div>Total Gross Pay</div></div></div>
        <div class="col-md-3"><div class="summary-stat"><div class="fs-2 fw-bold text-danger">₦${(s.total_tax || 0).toLocaleString()}</div><div>Total PAYE</div></div></div>
        <div class="col-md-3"><div class="summary-stat"><div class="fs-2 fw-bold text-success">₦${(s.total_pension || 0).toLocaleString()}</div><div>Total Pension</div></div></div>
        <div class="col-md-3"><div class="summary-stat"><div class="fs-2 fw-bold text-warning">₦${(s.total_net || 0).toLocaleString()}</div><div>Total Net Pay</div></div></div>
    `);

    // Statutory Chart
    if (response.statutory_data) {
        const ctx1 = document.getElementById('statutoryChart').getContext('2d');
        if (statutoryChart) statutoryChart.destroy();
        statutoryChart = new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: ['PAYE Tax', 'Employee Pension', 'NHF'],
                datasets: [{ data: response.statutory_data, backgroundColor: ['#dc2626', '#2563eb', '#d97706'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Trend Chart
    if (response.trend_data) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const ctx2 = document.getElementById('trendChart').getContext('2d');
        if (trendChart) trendChart.destroy();
        trendChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: months.slice(0, response.trend_data.length),
                datasets: [{ label: 'Net Pay (₦)', data: response.trend_data, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.1)', fill: true, tension: 0.4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }
}
</script>
@endsection
