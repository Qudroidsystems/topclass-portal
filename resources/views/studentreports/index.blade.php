@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Dismissible Alert Notification -->
            <div id="selectionAlert" class="alert alert-info alert-dismissible fade show" role="alert" style="display: none; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; margin: 0 auto; max-width: 90%;">
                <span id="selectionAlertText">No selections made.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <!-- Start page title -->
            <div class="row" style="margin-top: 60px;">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item active">Student Reports</li>
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
                                            <option value="ALL">All Classes</option>
                                            @foreach ($schoolclasses as $class)
                                                <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idsession" name="sessionid">
                                            <option value="ALL">All Sessions</option>
                                            @foreach ($schoolsessions as $session)
                                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6" id="termSelectContainer" style="display: none;">
                                        <select class="form-control" id="idterm" name="termid">
                                            <option value="ALL">All Terms</option>
                                            <option value="1">First Term</option>
                                            <option value="2">Second Term</option>
                                            <option value="3">Third Term</option>
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" id="searchInput" name="search" placeholder="Search students...">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6 d-flex gap-2">
                                        <button type="button" class="btn btn-secondary w-50" id="searchBtn" style="display: none;" onclick="filterData()"><i class="bi bi-search align-baseline me-1"></i> Search</button>
                                        <button type="button" class="btn btn-primary w-50" id="printAllBtn" style="display: none;" onclick="printAllResults()"><i class="bi bi-printer align-baseline me-1"></i> Print Selected Results</button>
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
                                                <th><div class="form-check"><input class="form-check-input" type="checkbox" id="checkAll"><label class="form-check-label" for="checkAll"></label></div></th>
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
                                            @include('studentreports.partials.student_rows')
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
                <div id="imageViewModal" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Student Image</h5>
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
    console.log("Script loaded at", new Date().toISOString());

    function updateSelectionAlert() {
        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect = document.getElementById("idterm");
        const checkedCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const selectionAlert = document.getElementById("selectionAlert");
        const selectionAlertText = document.getElementById("selectionAlertText");

        let alertText = [];
        if (classSelect.value !== 'ALL') {
            alertText.push(`Class: ${classSelect.options[classSelect.selectedIndex].text}`);
        }
        if (sessionSelect.value !== 'ALL') {
            alertText.push(`Session: ${sessionSelect.options[sessionSelect.selectedIndex].text}`);
        }
        if (termSelect.value !== 'ALL') {
            alertText.push(`Term: ${termSelect.options[termSelect.selectedIndex].text}`);
        }
        alertText.push(`Students Selected: ${checkedCheckboxes.length}`);

        if (classSelect.value !== 'ALL' && sessionSelect.value !== 'ALL') {
            selectionAlert.style.display = 'block';
            selectionAlertText.innerText = alertText.join(' | ');
        } else {
            selectionAlert.style.display = 'none';
            selectionAlertText.innerText = 'No selections made.';
        }
    }

    function updateSearchButtonVisibility() {
        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const searchBtn = document.getElementById("searchBtn");
        const termSelectContainer = document.getElementById("termSelectContainer");
        const printAllBtn = document.getElementById("printAllBtn");

        // Show search button if class OR session is selected (or both)
        searchBtn.style.display = (classSelect.value !== 'ALL' || sessionSelect.value !== 'ALL') ? 'block' : 'none';
        termSelectContainer.style.display = 'none';
        printAllBtn.style.display = 'none';
        updateSelectionAlert();
    }

    function updateTermSelectVisibility() {
        const termSelectContainer = document.getElementById("termSelectContainer");
        const printAllBtn = document.getElementById("printAllBtn");
        const studentCount = parseInt(document.getElementById("studentcount").innerText);
        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");

        // Show term select only when both class and session are selected AND there are students
        termSelectContainer.style.display = (classSelect.value !== 'ALL' && sessionSelect.value !== 'ALL' && studentCount > 0) ? 'block' : 'none';
        printAllBtn.style.display = 'none';
        updateSelectionAlert();
    }

    function updatePrintButtonVisibility() {
        const termSelect = document.getElementById("idterm");
        const printAllBtn = document.getElementById("printAllBtn");
        const checkedCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");

        printAllBtn.style.display = (classSelect.value !== 'ALL' && sessionSelect.value !== 'ALL' && termSelect.value !== 'ALL' && checkedCheckboxes.length > 0) ? 'block' : 'none';
        updateSelectionAlert();
    }

    function filterData() {
        console.log("filterData called");
        if (typeof axios === 'undefined') {
            console.error("Axios is not defined");
            Swal.fire({
                icon: "error",
                title: "Configuration Error",
                text: "Axios library is missing.",
                showConfirmButton: true
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
                showConfirmButton: true
            });
            return;
        }

        const classValue = classSelect.value;
        const sessionValue = sessionSelect.value;
        const termValue = termSelect.value;
        const searchValue = searchInput ? searchInput.value.trim() : '';

        // Allow search when either class or session is selected
        if (classValue === 'ALL' && sessionValue === 'ALL') {
            document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="11" class="text-center">Select a class or session to view students.</td></tr>';
            document.getElementById('pagination-container').innerHTML = '';
            document.getElementById('studentcount').innerText = '0';
            document.getElementById('printAllBtn').style.display = 'none';
            document.getElementById('termSelectContainer').style.display = 'none';
            updateSelectionAlert();
            Swal.fire({
                icon: "warning",
                title: "Missing Selection",
                text: "Please select at least a class or a session.",
                showConfirmButton: true
            });
            return;
        }

        console.log("Sending AJAX request with:", { search: searchValue, schoolclassid: classValue, sessionid: sessionValue, termid: termValue });

        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML = '<tr><td colspan="11" class="text-center">Loading...</td></tr>';

        axios.get('{{ route("studentreports.index") }}', {
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
                    text: "No students found for the selected criteria.",
                    showConfirmButton: true
                });
            }
        }).catch(function (error) {
            console.error("AJAX error:", error);
            tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error.response?.data?.message || "Failed to fetch student data.",
                showConfirmButton: true
            });
        });
    }

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
                showConfirmButton: true
            });
            return;
        }

        if (selectedStudentIds.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No Students Selected",
                text: "Please select at least one student to generate the PDF.",
                showConfirmButton: true
            });
            return;
        }

        // Show SweetAlert confirmation
        Swal.fire({
            title: 'Confirm Print',
            html: `
                <p><strong>Class:</strong> ${classSelect.options[classSelect.selectedIndex].text}</p>
                <p><strong>Session:</strong> ${sessionSelect.options[sessionSelect.selectedIndex].text}</p>
                <p><strong>Term:</strong> ${termSelect.options[termSelect.selectedIndex].text}</p>
                <p><strong>Students Selected:</strong> ${selectedStudentIds.length}</p>
                <p>Do you want to proceed with printing the results?</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Print',
            cancelButtonText: 'Cancel'
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

                axios.post('{{ route("studentreports.exportClassResultsPdf") }}', {
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
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: response.data.message || "Failed to generate PDF.",
                            showConfirmButton: true
                        });
                    }
                }).catch(function (error) {
                    Swal.close();
                    console.error("PDF generation error:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: error.response?.data?.message || "Failed to generate PDF.",
                        showConfirmButton: true
                    });
                });
            }
        });
    }

    function setupPaginationLinks() {
        const paginationLinks = document.querySelectorAll('#pagination-container a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.href;
                if (url && !this.classList.contains('disabled')) {
                    loadPage(url);
                }
            });
        });
    }

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
                showConfirmButton: true
            });
        });
    }

    function setupCheckboxListeners() {
        const checkAll = document.getElementById("checkAll");
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');

        if (checkAll) {
            checkAll.addEventListener("change", function () {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = this.checked;
                    const row = checkbox.closest("tr");
                    row.classList.toggle("table-active", this.checked);
                });
                updatePrintButtonVisibility();
            });
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener("change", function () {
                const row = this.closest("tr");
                row.classList.toggle("table-active", this.checked);
                const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
                const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]').length;
                document.getElementById("checkAll").checked = checkedCount === allCheckboxes && allCheckboxes > 0;
                updatePrintButtonVisibility();
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        console.log("DOM loaded");
        setupCheckboxListeners();

        const classSelect = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect = document.getElementById("idterm");

        classSelect.addEventListener("change", function () {
            updateSearchButtonVisibility();
            termSelect.value = 'ALL';
            // If class is ALL and session is ALL, show all students
            if (this.value === 'ALL' && sessionSelect.value === 'ALL') {
                filterData();
            } else if (this.value !== 'ALL' || sessionSelect.value !== 'ALL') {
                filterData();
            } else {
                document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="11" class="text-center">Select class and session to view students.</td></tr>';
                document.getElementById('pagination-container').innerHTML = '';
                document.getElementById('studentcount').innerText = '0';
            }
        });

        sessionSelect.addEventListener("change", function () {
            updateSearchButtonVisibility();
            termSelect.value = 'ALL';
            // If session is ALL and class is ALL, show all students
            if (this.value === 'ALL' && classSelect.value === 'ALL') {
                filterData();
            } else if (this.value !== 'ALL' || classSelect.value !== 'ALL') {
                filterData();
            } else {
                document.getElementById('studentTableBody').innerHTML = '<tr><td colspan="11" class="text-center">Select class and session to view students.</td></tr>';
                document.getElementById('pagination-container').innerHTML = '';
                document.getElementById('studentcount').innerText = '0';
            }
        });

        termSelect.addEventListener("change", function () {
            updatePrintButtonVisibility();
            if (this.value !== 'ALL') {
                filterData();
            } else {
                filterData();
            }
        });

        const modal = document.getElementById('imageViewModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const imageSrc = button.getAttribute('data-image');
                const modalImage = modal.querySelector('#enlargedImage');
                modalImage.src = imageSrc || '{{ asset('storage/student_avatars/unnamed.jpg') }}';
            });
        }

        // Initial load - show all students if both class and session are ALL
        if (classSelect.value === 'ALL' && sessionSelect.value === 'ALL') {
            filterData();
        }
    });
</script>
@endsection
