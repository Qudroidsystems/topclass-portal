{{-- resources/views/promotions/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row" style="margin-top: 60px;">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item active">Student Promotions</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?? session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="studentList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idclass" name="schoolclassid">
                                            <option value="ALL">Select Class</option>
                                            @foreach ($schoolclasses as $class)
                                                <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm ?? '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idsession" name="sessionid">
                                            <option value="ALL">Select Session</option>
                                            @foreach ($schoolsessions as $session)
                                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" id="searchInput" name="search" placeholder="Search students...">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6 d-flex gap-2">
                                        <button type="button" class="btn btn-secondary w-100" id="searchBtn" style="display: none;" onclick="filterData()"><i class="bi bi-search align-baseline me-1"></i> Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Students <span class="badge bg-dark-subtle text-dark ms-1" id="studentcount">{{ $allstudents->total() }}</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="studentListTable">
                                        <thead class="table-active">
                                            <tr>
                                                @if(config('app.debug'))
                                                    <th>ID (Debug)</th>
                                                    <th>Promotion ID (Debug)</th>
                                                @endif
                                                <th>Admission No</th>
                                                <th>Picture</th>
                                                <th>Last Name</th>
                                                <th>First Name</th>
                                                <th>Other Name</th>
                                                <th>Gender</th>
                                                <th>Class</th>
                                                <th>Arm</th>
                                                <th>Session</th>
                                                <th>Promotion Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                            @include('promotions.partials.student_rows')
                                        </tbody>
                                    </table>
                                    <div class="d-flex justify-content-end mt-3" id="pagination-container">
                                        {{ $allstudents->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Promotion Modal -->
                <div id="promotionModal" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-primary text-white">
                                <div>
                                    <h5 class="modal-title mb-0"><i class="ri-user-star-line me-2"></i>Student Promotion</h5>
                                    <small class="opacity-75">Update student class and session information</small>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="promotionForm">
                                @csrf
                                <div class="modal-body p-4">
                                    <!-- Student Information Card -->
                                    <div class="card border shadow-sm mb-4">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-3">
                                                <i class="ri-user-line me-2"></i>Current Student Information
                                            </h6>
                                            <div class="row align-items-center">
                                                <div class="col-md-3 text-center">
                                                    <img id="modalStudentImage" 
                                                         src="" 
                                                         alt="Student Picture" 
                                                         class="img-fluid rounded-circle shadow" 
                                                         style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #f0f0f0;"
                                                         onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                </div>
                                                <div class="col-md-9">
                                                    <h5 class="mb-3 text-primary" id="modalStudentName"></h5>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                                                <i class="ri-book-2-line text-primary fs-5 me-2"></i>
                                                                <div>
                                                                    <small class="text-muted d-block">Current Class</small>
                                                                    <strong id="modalCurrentClass"></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                                                <i class="ri-team-line text-primary fs-5 me-2"></i>
                                                                <div>
                                                                    <small class="text-muted d-block">Current Arm</small>
                                                                    <strong id="modalCurrentArm"></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                                                <i class="ri-calendar-line text-primary fs-5 me-2"></i>
                                                                <div>
                                                                    <small class="text-muted d-block">Current Session</small>
                                                                    <strong id="modalCurrentSession"></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                                                <i class="ri-calendar-check-line text-primary fs-5 me-2"></i>
                                                                <div>
                                                                    <small class="text-muted d-block">Current Term</small>
                                                                    <strong id="modalCurrentTerm"></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Promotion Arrow -->
                                    <div class="text-center mb-4">
                                        <i class="ri-arrow-down-line text-primary fs-1 animate-bounce"></i>
                                    </div>

                                    <!-- New Information Card -->
                                    <div class="card border-primary shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-title text-primary mb-3">
                                                <i class="ri-refresh-line me-2"></i>New Assignment Details
                                            </h6>
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">
                                                        <i class="ri-book-open-line me-1"></i>New Class <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-select form-select-lg" name="new_schoolclassid" id="newClassSelect" required>
                                                        <option value="">-- Select New Class --</option>
                                                        @foreach ($schoolclasses as $class)
                                                            <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm ?? '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">
                                                        <i class="ri-calendar-event-line me-1"></i>New Session <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-select form-select-lg" name="new_sessionid" id="newSessionSelect" required>
                                                        <option value="">-- Select New Session --</option>
                                                        @foreach ($schoolsessions as $session)
                                                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">
                                                        <i class="ri-calendar-todo-line me-1"></i>New Term <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-select form-select-lg" name="new_termid" id="newTermSelect" required>
                                                        <option value="">-- Select New Term --</option>
                                                        <option value="1">First Term</option>
                                                        <option value="2">Second Term</option>
                                                        <option value="3">Third Term</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Promotion Type -->
                                    <div class="card border-0 bg-light mt-4">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-3">
                                                <i class="ri-checkbox-circle-line me-2"></i>Promotion Type <span class="text-danger">*</span>
                                            </h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-check form-check-card">
                                                        <input class="form-check-input" type="checkbox" name="promotion" id="promotionCheckbox">
                                                        <label class="form-check-label w-100" for="promotionCheckbox">
                                                            <div class="d-flex align-items-center p-3 border rounded cursor-pointer promotion-card">
                                                                <div class="flex-shrink-0">
                                                                    <div class="avatar-sm">
                                                                        <div class="avatar-title bg-success-subtle text-success rounded-circle fs-2">
                                                                            <i class="ri-arrow-up-circle-line"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex-grow-1 ms-3">
                                                                    <h6 class="mb-1">Promote Student</h6>
                                                                    <p class="text-muted mb-0 small">Move student to next class level</p>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-check-card">
                                                        <input class="form-check-input" type="checkbox" name="repeat" id="repeatCheckbox">
                                                        <label class="form-check-label w-100" for="repeatCheckbox">
                                                            <div class="d-flex align-items-center p-3 border rounded cursor-pointer repeat-card">
                                                                <div class="flex-shrink-0">
                                                                    <div class="avatar-sm">
                                                                        <div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-2">
                                                                            <i class="ri-repeat-line"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex-grow-1 ms-3">
                                                                    <h6 class="mb-1">Repeat Class</h6>
                                                                    <p class="text-muted mb-0 small">Student repeats current class</p>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">
                                    <i class="ri-close-line me-1"></i> Cancel
                                </button>
                                <button type="button" class="btn btn-primary btn-lg" onclick="submitPromotion()">
                                    <i class="ri-save-line me-1"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .form-check-card .form-check-input {
        display: none;
    }

    .promotion-card,
    .repeat-card {
        transition: all 0.3s ease;
        background-color: #fff;
    }

    .promotion-card:hover {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.1);
    }

    .repeat-card:hover {
        border-color: #ffc107 !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.1);
    }

    #promotionCheckbox:checked ~ label .promotion-card {
        border-color: #198754 !important;
        background-color: #d1e7dd !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
    }

    #repeatCheckbox:checked ~ label .repeat-card {
        border-color: #ffc107 !important;
        background-color: #fff3cd !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
    }

    .animate-bounce {
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .avatar-sm {
        height: 3rem;
        width: 3rem;
    }

    .avatar-title {
        align-items: center;
        display: flex;
        height: 100%;
        justify-content: center;
        width: 100%;
    }

    .bg-success-subtle {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }

    .bg-warning-subtle {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }

    .text-success {
        color: #198754 !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function updateSearchButtonVisibility() {
        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const searchBtn = document.getElementById("searchBtn");

        searchBtn.style.display = (classSelect.value !== 'ALL' && sessionSelect.value !== 'ALL') ? 'block' : 'none';
    }

    function filterData() {
        const classValue = document.getElementById("idclass").value;
        const sessionValue = document.getElementById("idsession").value;
        const searchValue = document.getElementById("searchInput").value.trim();

        if (classValue === 'ALL' || sessionValue === 'ALL') {
            document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center">Select class and session to view students.</td></tr>';
            document.getElementById('pagination-container').innerHTML = '';
            document.getElementById('studentcount').innerText = '0';
            return;
        }

        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML = '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center">Loading...</td></tr>';

        axios.get('{{ route("promotions.index") }}', {
            params: {
                search: searchValue,
                schoolclassid: classValue,
                sessionid: sessionValue
            },
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            document.getElementById('studentTableBody').innerHTML = response.data.tableBody || '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center">No students found.</td></tr>';
            document.getElementById('pagination-container').innerHTML = response.data.pagination || '';
            document.getElementById('studentcount').innerText = response.data.studentCount || '0';
            setupPaginationLinks();
        }).catch(function (error) {
            console.error('AJAX Error:', error);
            tableBody.innerHTML = '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error.response?.data?.message || "Failed to fetch student data.",
                showConfirmButton: true
            });
        });
    }

    function setupPaginationLinks() {
        const paginationLinks = document.querySelectorAll('#pagination-container a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                url.searchParams.set('schoolclassid', document.getElementById("idclass").value);
                url.searchParams.set('sessionid', document.getElementById("idsession").value);
                loadPage(url.toString());
            });
        });
    }

    function loadPage(url) {
        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML = '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center">Loading...</td></tr>';

        axios.get(url, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            document.getElementById('studentTableBody').innerHTML = response.data.tableBody || '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center">No students found.</td></tr>';
            document.getElementById('pagination-container').innerHTML = response.data.pagination || '';
            document.getElementById('studentcount').innerText = response.data.studentCount || '0';
            setupPaginationLinks();
        }).catch(function (error) {
            console.error('Page load error:', error);
            tableBody.innerHTML = '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error.response?.data?.message || "Failed to fetch student data.",
                showConfirmButton: true
            });
        });
    }

    let currentStudentId = null;

    function openPromotionModal(studentId, admissionNo, firstName, lastName, otherName, picture, schoolclass, schoolarm, session, termid, promotionStatus) {
        currentStudentId = studentId;
        
        document.getElementById('modalStudentName').innerHTML = `${admissionNo} - ${firstName} ${lastName} ${otherName || ''}`;
        document.getElementById('modalCurrentClass').innerText = schoolclass;
        document.getElementById('modalCurrentArm').innerText = schoolarm || 'N/A';
        document.getElementById('modalCurrentSession').innerText = session;
        document.getElementById('modalCurrentTerm').innerText = ['First Term', 'Second Term', 'Third Term'][parseInt(termid) - 1] || 'N/A';
        document.getElementById('modalStudentImage').src = picture ? `{{ asset('storage/') }}${picture}` : '{{ asset('storage/student_avatars/unnamed.jpg') }}';
        
        document.getElementById('promotionForm').reset();
        document.getElementById('newClassSelect').value = '';
        document.getElementById('newSessionSelect').value = '';
        document.getElementById('newTermSelect').value = '';
        document.getElementById('promotionCheckbox').checked = false;
        document.getElementById('repeatCheckbox').checked = false;
        
        new bootstrap.Modal(document.getElementById('promotionModal')).show();
    }

    function removeStudent(studentId, schoolclassId, sessionId, termId, admissionNo, firstName, lastName) {
        const fullName = `${admissionNo} - ${firstName} ${lastName}`;

        Swal.fire({
            title: 'Confirm Removal',
            text: `Are you sure you want to remove ${fullName} from this class? This will delete the class assignment and promotion status for the selected session and term.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Removing student from class',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const deleteUrl = `/promotions/${studentId}`;

                const deleteData = new FormData();
                deleteData.append('_method', 'DELETE');
                deleteData.append('schoolclassid', schoolclassId);
                deleteData.append('sessionid', sessionId);
                deleteData.append('termid', termId);

                axios.post(deleteUrl, deleteData, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(function (response) {
                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        filterData();
                    } else {
                        Swal.fire('Error!', response.data.message || 'Failed to remove.', 'error');
                    }
                }).catch(function (error) {
                    console.error('Removal error:', error);
                    let errorMessage = 'Failed to remove student from class.';
                    
                    if (error.response?.data?.errors) {
                        const errors = error.response.data.errors;
                        errorMessage = Object.values(errors).flat().join('<br>');
                    } else if (error.response?.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: errorMessage
                    });
                });
            }
        });
    }

    function submitPromotion() {
        if (!currentStudentId) {
            Swal.fire('Error!', 'Student ID not found.', 'error');
            return;
        }

        const newClassSelect = document.getElementById('newClassSelect');
        const newSessionSelect = document.getElementById('newSessionSelect');
        const newTermSelect = document.getElementById('newTermSelect');
        const promotionCheckbox = document.getElementById('promotionCheckbox');
        const repeatCheckbox = document.getElementById('repeatCheckbox');

        if (!newClassSelect.value) {
            Swal.fire('Error!', 'Please select a new class.', 'error');
            return;
        }

        if (!newSessionSelect.value) {
            Swal.fire('Error!', 'Please select a new session.', 'error');
            return;
        }

        if (!newTermSelect.value) {
            Swal.fire('Error!', 'Please select a new term.', 'error');
            return;
        }

        if (promotionCheckbox.checked && repeatCheckbox.checked) {
            Swal.fire('Error!', 'Cannot select both promotion and repeat.', 'error');
            return;
        }

        if (!promotionCheckbox.checked && !repeatCheckbox.checked) {
            Swal.fire('Error!', 'Please select either promotion or repeat.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('new_schoolclassid', newClassSelect.value);
        formData.append('new_sessionid', newSessionSelect.value);
        formData.append('new_termid', newTermSelect.value);
        formData.append('promotion', promotionCheckbox.checked ? '1' : '0');
        formData.append('repeat', repeatCheckbox.checked ? '1' : '0');

        const updateUrl = `/promotions/${currentStudentId}`;

        Swal.fire({
            title: 'Confirm Update',
            text: 'Are you sure you want to update this student\'s promotion?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Update',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Updating student promotion',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                axios.post(updateUrl, formData, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(function (response) {
                    if (response.data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('promotionModal')).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        filterData();
                    } else {
                        Swal.fire('Error!', response.data.message || 'Failed to update.', 'error');
                    }
                }).catch(function (error) {
                    console.error('Update error:', error);
                    let errorMessage = 'Failed to update student promotion.';
                    
                    if (error.response?.data?.errors) {
                        const errors = error.response.data.errors;
                        errorMessage = Object.values(errors).flat().join('<br>');
                    } else if (error.response?.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: errorMessage
                    });
                });
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        updateSearchButtonVisibility();

        document.getElementById("idclass").addEventListener("change", function () {
            updateSearchButtonVisibility();
            if (this.value === 'ALL') {
                document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center">Select class and session to view students.</td></tr>';
                document.getElementById('pagination-container').innerHTML = '';
                document.getElementById('studentcount').innerText = '0';
            }
        });

        document.getElementById("idsession").addEventListener("change", function () {
            updateSearchButtonVisibility();
            if (document.getElementById("idclass").value === 'ALL') {
                document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="{{ config('app.debug') ? 13 : 11 }}" class="text-center">Select class and session to view students.</td></tr>';
                document.getElementById('pagination-container').innerHTML = '';
                document.getElementById('studentcount').innerText = '0';
            }
        });

        let searchTimeout;
        document.getElementById("searchInput").addEventListener("input", function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterData, 500);
        });

        document.getElementById("searchBtn").addEventListener("click", filterData);
    });
</script>
@endsection