@extends('layouts.master')

@section('content')
<style>
    #alertContainer {
        position: fixed;
        top: 10px;
        left: 0;
        right: 0;
        z-index: 1050;
        margin: 0 auto;
        max-width: 90%;
        text-align: center;
    }
    #alertContainer .alert {
        margin-bottom: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        display: inline-block;
        width: auto;
        max-width: 600px;
    }
</style>

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
                                <li class="breadcrumb-item active">Student Mock Reports</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('status') || session('success') || session('error'))
                <div class="alert alert-{{ session('error') ? 'danger' : 'success' }} alert-dismissible fade show" role="alert">
                    {{ session('status') ?? session('success') ?? session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Dismissible Alert Notification -->
            <div id="alertContainer" aria-live="polite"></div>

            <div id="studentList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idclass" name="schoolclassid" aria-label="Select Class">
                                            <option value="ALL">Select Class</option>
                                            @foreach ($schoolclasses as $class)
                                                <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idsession" name="sessionid" aria-label="Select Session">
                                            <option value="ALL">Select Session</option>
                                            @foreach ($schoolsessions as $session)
                                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6" id="termSelectContainer" style="display: none;">
                                        <select class="form-control" id="idterm" name="termid" aria-label="Select Term">
                                            <option value="ALL">Select Term</option>
                                            @foreach ($schoolterms as $term)
                                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" id="searchInput" name="search" placeholder="Search students..." aria-label="Search students">
                                            <i class="ri-search-line search-icon" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6 d-flex gap-2">
                                        <button type="button" class="btn btn-secondary w-50" id="searchBtn" style="display: none;" onclick="filterData()">
                                            <i class="bi bi-search align-baseline me-1"></i> Search
                                        </button>
                                        <button type="button" class="btn btn-primary w-50" id="printAllBtn" style="display: none;" onclick="printAllResults()">
                                            <i class="bi bi-printer align-baseline me-1"></i> Print Selected Results
                                        </button>
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
                                <h5 class="card-title mb-0">Students <span class="badge bg-dark-subtle text-dark ms-1" id="studentcount">{{ $allstudents ? $allstudents->total() : 0 }}</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="studentListTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th><div class="form-check"><input class="form-check-input" type="checkbox" id="checkAll" aria-label="Select all students"><label class="form-check-label" for="checkAll"></label></div></th>
                                                <th>Admission No</th>
                                                <th>Picture</th>
                                                <th>Last Name</th>
                                                <th>First Name</th>
                                                <th>Other Name</th>
                                                <th>Gender</th>
                                                <th>Class</th>
                                                <th>Arm</th>
                                                <th>Session</th>
                                               
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                            @include('studentmockreports.partials.student_rows')
                                        </tbody>
                                    </table>
                                    <div class="d-flex justify-content-end mt-3" id="pagination-container">
                                        {{ $allstudents ? $allstudents->links('pagination::bootstrap-5') : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image View Modal -->
                <div id="imageViewModal" class="modal fade" tabindex="-1" aria-hidden="true" aria-labelledby="imageViewModalLabel">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageViewModalLabel">Student Image</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="enlargedImage" src="" alt="Student Image" class="img-fluid" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'; console.log('Enlarged image failed to load');">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    console.log("Mock Reports Script loaded at", new Date().toISOString());

    // Routes
    const routes = {
        index: '{{ route("studentmockreports.index") }}',
        exportPdf: '{{ route("studentmockreports.exportClassMockResultsPdf") }}'
    };

    // Update selection alert text
    function updateSelectionAlert() {
        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect = document.getElementById("idterm");
        const checkedCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');

        const classText = classSelect && classSelect.value !== 'ALL' ? classSelect.options[classSelect.selectedIndex].text : 'None';
        const sessionText = sessionSelect && sessionSelect.value !== 'ALL' ? sessionSelect.options[sessionSelect.selectedIndex].text : 'None';
        const termText = termSelect && termSelect.value !== 'ALL' ? termSelect.options[termSelect.selectedIndex].text : 'None';
        const studentCount = checkedCheckboxes.length;

        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'selectionAlert';
        const messages = [];

        if (classText !== 'None') messages.push(`Class: ${classText}`);
        if (sessionText !== 'None') messages.push(`Session: ${sessionText}`);
        if (termText !== 'None') messages.push(`Term: ${termText}`);
        messages.push(`Students Selected: ${studentCount}`);

        if (classText === 'None' || sessionText === 'None') {
            const existingAlert = document.getElementById(alertId);
            if (existingAlert) existingAlert.remove();
            return;
        }

        const alertMessage = messages.join(' | ');
        const alertHtml = `
            <div id="${alertId}" class="alert alert-info alert-dismissible fade show" role="alert">
                ${alertMessage}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // Remove existing alert
        const existingAlert = document.getElementById(alertId);
        if (existingAlert) existingAlert.remove();

        // Append new alert
        alertContainer.innerHTML = alertHtml;

        // Auto-dismiss after 10 seconds
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.remove('show');
                alert.classList.add('fade');
                setTimeout(() => alert.remove(), 150);
            }
        }, 10000);
    }

    // Update visibility of search and term select
    function updateSearchButtonVisibility() {
        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const searchBtn = document.getElementById("searchBtn");
        const termSelectContainer = document.getElementById("termSelectContainer");
        const printAllBtn = document.getElementById("printAllBtn");

        searchBtn.style.display = (classSelect.value !== 'ALL' && sessionSelect.value !== 'ALL') ? 'block' : 'none';
        termSelectContainer.style.display = 'none';
        printAllBtn.style.display = 'none';
        updateSelectionAlert();
    }

    // Update visibility of term select based on student count
    function updateTermSelectVisibility() {
        const termSelectContainer = document.getElementById("termSelectContainer");
        const printAllBtn = document.getElementById("printAllBtn");
        const studentCount = parseInt(document.getElementById("studentcount").innerText);

        termSelectContainer.style.display = studentCount > 0 ? 'block' : 'none';
        printAllBtn.style.display = 'none';
        updateSelectionAlert();
    }

    // Update visibility of print button
    function updatePrintButtonVisibility() {
        const termSelect = document.getElementById("idterm");
        const printAllBtn = document.getElementById("printAllBtn");
        const checkedCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');

        printAllBtn.style.display = (termSelect.value !== 'ALL' && checkedCheckboxes.length > 0) ? 'block' : 'none';
        updateSelectionAlert();
    }

    // Filter student data via AJAX
    function filterData() {
        console.log("filterData called");
        if (typeof axios === 'undefined') {
            console.error("Axios is not defined");
            Swal.fire({
                icon: "error",
                title: "Configuration Error",
                text: "Axios library is missing.",
                showConfirmButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            return;
        }

        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect = document.getElementById("idterm");
        const searchInput = document.getElementById("searchInput");

        if (!classSelect || !sessionSelect || !termSelect) {
            console.error("Class, session, or term select elements not found");
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Required filter elements not found.",
                showConfirmButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            return;
        }

        const classValue = classSelect.value;
        const sessionValue = sessionSelect.value;
        const termValue = termSelect.value;
        const searchValue = searchInput ? searchInput.value.trim() : '';

        if (classValue === 'ALL' || sessionValue === 'ALL') {
            document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="11" class="text-center">Select class and session to view students.</td></tr>';
            document.getElementById('pagination-container').innerHTML = '';
            document.getElementById('studentcount').innerText = '0';
            document.getElementById('printAllBtn').style.display = 'none';
            document.getElementById('termSelectContainer').style.display = 'none';
            document.getElementById('alertContainer').innerHTML = '';
            Swal.fire({
                icon: "warning",
                title: "Missing Selection",
                text: "Please select a valid class and session.",
                showConfirmButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            return;
        }

        console.log("Sending AJAX request with:", { search: searchValue, schoolclassid: classValue, sessionid: sessionValue, termid: termValue });

        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML = '<tr><td colspan="11" class="text-center">Loading...</td></tr>';

        axios.get(routes.index, {
            params: {
                search: searchValue,
                schoolclassid: classValue,
                sessionid: sessionValue,
                termid: termValue
            },
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            console.log("AJAX response received:", response.data);

            document.getElementById('studentTableBody').innerHTML = response.data.tableBody || '<tr><td colspan="11" class="text-center">No students found.</td></tr>';
            document.getElementById('pagination-container').innerHTML = response.data.pagination || '';
            document.getElementById('studentcount').innerText = response.data.studentCount || '0';

            setupPaginationLinks();
            setupCheckboxListeners();
            updateTermSelectVisibility();
            updatePrintButtonVisibility();

            if (response.data.tableBody.includes('No students found') || response.data.tableBody.includes('Select class and session')) {
                Swal.fire({
                    icon: "info",
                    title: "No Results",
                    text: "No students found for the selected class, session, and term.",
                    showConfirmButton: true,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            }
        }).catch(function (error) {
            console.error("AJAX error:", error);
            tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error.response?.data?.message || "Failed to fetch student data.",
                showConfirmButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
        });
    }

    // Print selected results as PDF
    function printAllResults() {
        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect = document.getElementById("idterm");
        const classValue = classSelect.value;
        const sessionValue = sessionSelect.value;
        const termValue = termSelect.value;
        const checkedCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const selectedStudentIds = Array.from(checkedCheckboxes).map(checkbox => checkbox.value);

        if (classValue === 'ALL' || sessionValue === 'ALL' || termValue === 'ALL') {
            Swal.fire({
                icon: "warning",
                title: "Missing Selection",
                text: "Please select a valid class, session, and term.",
                showConfirmButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            return;
        }

        if (selectedStudentIds.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No Students Selected",
                text: "Please select at least one student to generate the PDF.",
                showConfirmButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            return;
        }

        const classText = classSelect.options[classSelect.selectedIndex].text;
        const sessionText = sessionSelect.options[sessionSelect.selectedIndex].text;
        const termText = termSelect.options[termSelect.selectedIndex].text;
        const studentCount = selectedStudentIds.length;

        Swal.fire({
            title: 'Confirm Print',
            html: `
                <p>You are about to print mock results for:</p>
                <ul style="text-align: left;">
                    <li><strong>Class:</strong> ${classText}</li>
                    <li><strong>Session:</strong> ${sessionText}</li>
                    <li><strong>Term:</strong> ${termText}</li>
                    <li><strong>Selected:</strong> ${studentCount} student${studentCount === 1 ? '' : 's'}</li>
                </ul>
                <p>Do you want to proceed?</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirm',
            cancelButtonText: 'Cancel',
            buttonsStyling: true,
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Generating PDF...',
                    text: 'Please wait while the PDF is being generated.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                console.log('Generating PDF with params:', { schoolclassid: classValue, sessionid: sessionValue, termid: termValue, studentIds: selectedStudentIds });

                axios.post(routes.exportPdf, {
                    schoolclassid: classValue,
                    sessionid: sessionValue,
                    termid: termValue,
                    studentIds: selectedStudentIds,
                    response_method: 'base64'
                }, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    responseType: 'json'
                }).then(function (response) {
                    console.log("PDF response:", response.data);
                    Swal.close();
                    if (response.data.success && response.data.pdf_base64) {
                        const byteCharacters = atob(response.data.pdf_base64);
                        const byteNumbers = new Array(byteCharacters.length);
                        for (let i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        const byteArray = new Uint8Array(byteNumbers);
                        const blob = new Blob([byteArray], { type: 'application/pdf' });
                        const pdfUrl = URL.createObjectURL(blob);
                        window.open(pdfUrl, '_blank');
                        setTimeout(() => URL.revokeObjectURL(pdfUrl), 30000);
                        Swal.fire({
                            icon: "success",
                            title: "Success",
                            text: "PDF generated successfully.",
                            showConfirmButton: true,
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: response.data.message || "Failed to generate PDF.",
                            showConfirmButton: true,
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                    }
                }).catch(function (error) {
                    Swal.close();
                    console.error("PDF generation error:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: error.response?.data?.message || "Failed to generate PDF.",
                        showConfirmButton: true,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                });
            }
        });
    }

    // Setup pagination links
    function setupPaginationLinks() {
        const paginationLinks = document.querySelectorAll('#pagination-container a');
        paginationLinks.forEach(link => {
            link.removeEventListener('click', handlePaginationClick); // Prevent duplicate listeners
            link.addEventListener('click', handlePaginationClick);
        });
    }

    function handlePaginationClick(e) {
        e.preventDefault();
        const url = this.href;
        if (url && !this.classList.contains('disabled')) {
            loadPage(url);
        }
    }

    // Load paginated data
    function loadPage(url) {
        console.log("Loading page:", url);
        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML = '<tr><td colspan="11" class="text-center">Loading...</td></tr>';

        axios.get(url, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            console.log("Page load response:", response.data);
            document.getElementById('studentTableBody').innerHTML = response.data.tableBody || '<tr><td colspan="11" class="text-center">No students found.</td></tr>';
            document.getElementById('pagination-container').innerHTML = response.data.pagination || '';
            document.getElementById('studentcount').innerText = response.data.studentCount || '0';
            setupPaginationLinks();
            setupCheckboxListeners();
            updateTermSelectVisibility();
            updatePrintButtonVisibility();
        }).catch(function (error) {
            console.error("Page load error:", error);
            tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error.response?.data?.message || "Failed to fetch student data.",
                showConfirmButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
        });
    }

    // Setup checkbox listeners
    function setupCheckboxListeners() {
        const checkAll = document.getElementById("checkAll");
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');

        if (checkAll) {
            checkAll.removeEventListener("change", handleCheckAllChange); // Prevent duplicate listeners
            checkAll.addEventListener("change", handleCheckAllChange);
        }

        checkboxes.forEach(checkbox => {
            checkbox.removeEventListener("change", handleCheckboxChange); // Prevent duplicate listeners
            checkbox.addEventListener("change", handleCheckboxChange);
        });
    }

    function handleCheckAllChange() {
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = this.checked;
            const row = checkbox.closest("tr");
            row.classList.toggle("table-active", this.checked);
        });
        updatePrintButtonVisibility();
    }

    function handleCheckboxChange() {
        const row = this.closest("tr");
        row.classList.toggle("table-active", this.checked);
        const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
        const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]').length;
        document.getElementById("checkAll").checked = checkedCount === allCheckboxes && allCheckboxes > 0;
        updatePrintButtonVisibility();
    }

    // Initialize on DOM load
    document.addEventListener("DOMContentLoaded", function () {
        console.log("DOM loaded");
        setupCheckboxListeners();
        updateSearchButtonVisibility();

        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect = document.getElementById("idterm");

        if (classSelect) {
            classSelect.addEventListener("change", function () {
                updateSearchButtonVisibility();
                termSelect.value = 'ALL';
                document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="11" class="text-center">Select class and session to view students.</td></tr>';
                document.getElementById('pagination-container').innerHTML = '';
                document.getElementById('studentcount').innerText = '0';
            });
        }

        if (sessionSelect) {
            sessionSelect.addEventListener("change", function () {
                updateSearchButtonVisibility();
                termSelect.value = 'ALL';
                document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="11" class="text-center">Select class and session to view students.</td></tr>';
                document.getElementById('pagination-container').innerHTML = '';
                document.getElementById('studentcount').innerText = '0';
            });
        }

        if (termSelect) {
            termSelect.addEventListener("change", function () {
                updatePrintButtonVisibility();
                if (this.value !== 'ALL' && classSelect.value !== 'ALL' && sessionSelect.value !== 'ALL') {
                    filterData();
                }
            });
        }

        const modal = document.getElementById('imageViewModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const imageSrc = button.getAttribute('data-image');
                const modalImage = modal.querySelector('#enlargedImage');
                modalImage.src = imageSrc || '{{ asset('storage/student_avatars/unnamed.jpg') }}';
            });
        }
    });
</script>
@endsection
