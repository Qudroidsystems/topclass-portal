@extends('layouts.master')

@section('content')
<!-- Main content container -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Debug Info -->
            <div class="alert alert-info" style="display: none;">
                Debug: {{ $student->count() }} students loaded.
            </div>

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
            {{-- @if ($student->isNotEmpty())
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="d-flex flex-wrap flex-stack mb-4">
                                        <div class="d-flex flex-column flex-grow-1 pe-8">
                                            <div class="d-flex flex-wrap">
                                                <!-- Class Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $student->first()->schoolclass ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Class</div>
                                                </div>
                                                <!-- Term Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-calendar fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $student->first()->term ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Term</div>
                                                </div>
                                                <!-- Session Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-calendar fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $student->first()->session ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Session</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif --}}

            <!-- Payments Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    {{ $pagetitle }}
                                    @if ($student->isNotEmpty())
                                        <span class="badge bg-info-subtle text-info ms-2" id="studentCount">{{ $student->count() }}</span>
                                    @endif
                                </h5>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by admission no or name..." style="min-width: 200px;" {{ $student->isEmpty() ? 'disabled' : '' }}>
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <a href="{{ url()->previous() }}" class="btn btn-primary">
                                    <i class="ri-arrow-left-line"></i> Back
                                </a>
                            </div>

                            <!-- No Data Alert -->
                            <div class="alert alert-info text-center" id="noDataAlert" style="display: {{ $student->isEmpty() ? 'block' : 'none' }};">
                                <i class="ri-information-line me-2"></i>
                                No students available. Please check your filters or add students.
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
                                            <th class="sort cursor-pointer" data-sort="admissionno">Admission No</th>
                                            <th class="sort cursor-pointer" data-sort="name">Name</th>
                                            <th class="sort cursor-pointer" data-sort="gender">Gender</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentsTableBody" class="list form-check-all">
                                        @php $i = 0; @endphp
                                        @forelse ($student as $std)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input student-checkbox" type="checkbox" name="chk_child" data-id="{{ $std->id }}">
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </td>
                                                <td class="sn">{{ ++$i }}</td>
                                                <td class="admissionno" data-admissionno="{{ $std->admissionNo ?? '-' }}">{{ $std->admissionNo ?? '-' }}</td>
                                                <td class="name" data-name="{{ ($std->firstname ?? '') . ' ' . ($std->lastname ?? '') }}">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2">
                                                            <img src="{{ $std->picture ? Storage::url('images/studentavatar/' . $std->picture) : Storage::url('images/studentavatar/unnamed.png') }}" alt="{{ ($std->firstname ?? '') . ' ' . ($std->lastname ?? '') }}" class="rounded-circle w-100">
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            {{ ($std->firstname ?? '') . ' ' . ($std->lastname ?? '') }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="gender" data-gender="{{ $std->gender ?? '-' }}">{{ $std->gender ?? '-' }}</td>
                                                <td>
                                                    <a href="{{ route('schoolpayment.termsession', $std->id) }}" class="btn btn-sm btn-info">
                                                        <i class="ri-eye-line me-1"></i> View Payments
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="noDataRow">
                                                <td colspan="6" class="text-center">No students available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Dependencies -->
<script>
window.students = @json($student);

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const tableBody = document.getElementById('paymentsTableBody');
    const noDataAlert = document.getElementById('noDataAlert');
    const studentCount = document.getElementById('studentCount');
    const checkAll = document.getElementById('checkAll');

    // Log initial students for debugging
    console.log('Initial students:', window.students);

    // Debounce function to limit search execution
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Search functionality
    const performSearch = debounce(function () {
        const searchTerm = searchInput.value.trim().toLowerCase();
        const rows = tableBody.querySelectorAll('tr:not(#noDataRow)');
        let visibleRows = 0;

        rows.forEach(row => {
            const admissionNo = (row.querySelector('.admissionno')?.dataset.admissionno || '').toLowerCase();
            const name = (row.querySelector('.name')?.dataset.name || '').toLowerCase();
            const isMatch = admissionNo.includes(searchTerm) || name.includes(searchTerm);
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleRows++;
        });

        // Toggle no data alert
        noDataAlert.style.display = visibleRows === 0 && rows.length > 0 ? 'block' : 'none';
        // Update student count
        if (studentCount) {
            studentCount.textContent = visibleRows;
        }
        // Show/hide noDataRow
        const noDataRow = document.getElementById('noDataRow');
        if (noDataRow) {
            noDataRow.style.display = rows.length === 0 ? '' : 'none';
        }
    }, 300);

    // Attach search event listener
    if (searchInput) {
        searchInput.addEventListener('input', performSearch);
    }

    // Clear search
    if (clearSearch) {
        clearSearch.addEventListener('click', function () {
            searchInput.value = '';
            performSearch();
        });
    }

    // Check all checkboxes
    if (checkAll && tableBody) {
        checkAll.addEventListener('change', function () {
            const checkboxes = tableBody.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});
</script>
@endsection
