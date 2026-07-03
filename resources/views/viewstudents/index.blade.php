@extends('layouts.master')

@section('content')
<?php
use Spatie\Permission\Models\Role;
?>

<style>
/* Ensure modal image is responsive and centered */
#imageViewModal .modal-body img {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}
#imageViewModal .modal-body .placeholder-text {
    color: #6c757d;
    font-size: 1.2rem;
    text-align: center;
    display: none;
}
/* Ensure table images are visible and styled correctly */
.rounded-circle.avatar-sm {
    width: 40px;
    height: 40px;
    object-fit: cover;
    display: block !important;
}
@media (max-width: 768px) {
    #imageViewModal .modal-dialog {
        max-width: 90%;
    }
    #imageViewModal .modal-body img {
        max-height: 300px;
    }
    .rounded-circle.avatar-sm {
        width: 32px;
        height: 32px;
    }
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">My Class Students</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('myclass.index') }}">Class Management</a></li>
                                <li class="breadcrumb-item active">Students</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Students by Gender Chart -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Students by Gender</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="studentsByGenderChart" height="100"></canvas>
                            <div id="chartError" class="text-danger text-center d-none">Failed to load chart. Please check data.</div>
                        </div>
                    </div>
                </div>
            </div>

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

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="studentList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search students">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <div>
                                            <select class="form-control" id="idGender" data-choices data-choices-search-false data-choices-removeItem>
                                                <option value="all">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <div>
                                            <select class="form-control" id="idAdmissionNo" data-choices data-choices-search-false data-choices-removeItem>
                                                <option value="all">Select Admission No</option>
                                                @foreach ($allstudents as $student)
                                                    <option value="{{ $student->admissionno }}">{{ $student->admissionno }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-1 col-sm-6">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData();"><i class="bi bi-funnel align-baseline me-1"></i> Filters</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">Students <span class="badge bg-dark-subtle text-dark ms-1">{{ $allstudents->count() }}</span></h5>
                                    <p class="text-muted mb-0">Class: {{ $schoolclass[0]->schoolclass }} {{ $schoolclass[0]->arm }} | Term: {{ $term[0]->term }} | Session: {{ $session[0]->session }}</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <a href="{{ route('myclass.index') }}" class="btn btn-light">Back</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="studentListTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th class="sort cursor-pointer" data-sort="sn">SN</th>
                                                <th class="sort cursor-pointer" data-sort="admissionno">Admission No</th>
                                                <th class="sort cursor-pointer" data-sort="name">Student Name</th>
                                                <th class="sort cursor-pointer" data-sort="gender">Gender</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list">
                                            @forelse ($allstudents as $key => $student)
                                                <?php
                                                $picture = $student->picture ? basename($student->picture) : 'unnamed.jpg';
                                                $imagePath = asset('storage/student_avatars/' . $picture);
                                                $fileExists = file_exists(storage_path('app/public/student_avatars/' . $picture));
                                                $defaultImageExists = file_exists(storage_path('app/public/student_avatars/unnamed.jpg'));
                                                ?>
                                                <tr>
                                                    <td class="sn">{{ $key + 1 }}</td>
                                                    <td class="admissionno" data-admissionno="{{ $student->admissionno }}">{{ $student->admissionno }}</td>
                                                    <td class="name" data-name="{{ $student->firstname }} {{ $student->lastname }} {{ $student->othername }}">
                                                        <div class="d-flex align-items-center">
                                                            <img src="{{ $student->picture ? asset('storage/student_avatars/' . basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                                                 alt="{{ $student->firstname }} {{ $student->lastname }} {{ $student->othername }}"
                                                                 class="rounded-circle avatar-sm student-image"
                                                                 data-bs-toggle="modal"
                                                                 data-bs-target="#imageViewModal"
                                                                 data-image="{{ $student->picture ? asset('storage/student_avatars/' . basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                                                 data-picture="{{ $student->picture ?? 'none' }}"
                                                                 data-admissionno="{{ $student->admissionno }}"
                                                                 data-file-exists="{{ $fileExists ? 'true' : 'false' }}"
                                                                 data-default-exists="{{ $defaultImageExists ? 'true' : 'false' }}"
                                                                 onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'; console.log('Table image failed to load for admissionno: {{ $student->admissionno ?? 'unknown' }}, picture: {{ $student->picture ?? 'none' }}');" />
                                                            <div class="ms-3">
                                                                <h6 class="mb-0"><a href="{{ route('myclass.studentpersonalityprofile', [$student->stid, $schoolclassid, $termid, $sessionid]) }}" class="text-reset">{{ $student->firstname }} {{ $student->lastname }} {{ $student->othername }}</a></h6>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="gender" data-gender="{{ $student->gender }}">{{ $student->gender }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('View student')
                                                                <li>
                                                                    <a href="{{ route('myclass.studentpersonalityprofile', [$student->stid, $schoolclassid, $sessionid,$termid]) }}" class="btn btn-subtle-primary btn-icon btn-sm"><i class="ph-eye"></i></a>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="noresult" style="display: block;">No results found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold" id="list-count">0</span> of <span class="fw-semibold">{{ $allstudents->count() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <ul class="pagination" id="studentListPagination"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image View Modal -->
        <div id="imageViewModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Student Picture</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="enlargedImage" src="" alt="Student Picture" class="img-fluid" />
                        <div class="placeholder-text">No image available</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Page-content -->

    <script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        // List.js configuration
        document.addEventListener("DOMContentLoaded", function () {
            const studentListElement = document.getElementById("studentList");
            if (!studentListElement) {
                console.error("Student list element (#studentList) not found in DOM");
                return;
            }

            const options = {
                valueNames: ["sn", "admissionno", "name", "gender"],
                page: 5,
                pagination: {
                    innerWindow: 2,
                    outerWindow: 1,
                    left: 0,
                    right: 0,
                    item: '<li class="page-item"><a class="page-link" href="#"></a></li>'
                }
            };

            let studentList;
            try {
                studentList = new List("studentList", options);
                console.log("List.js initialized successfully, items:", studentList.items.length);
            } catch (error) {
                console.error("Error initializing List.js:", error);
                return;
            }

            studentList.on("updated", function (e) {
                console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", studentList.items.length);
                const noResult = document.getElementsByClassName("noresult")[0];
                if (noResult) {
                    noResult.style.display = e.matchingItems.length === 0 ? "block" : "none";
                }
                const listCount = document.getElementById("list-count");
                if (listCount) {
                    listCount.innerText = e.matchingItems.length;
                }
            });

            // Initialize Chart.js for Students by Gender
            const ctx = document.getElementById("studentsByGenderChart")?.getContext("2d");
            const chartError = document.getElementById("chartError");
            const maleCount = {{ $male ?? 0 }};
            const femaleCount = {{ $female ?? 0 }};

            if (!ctx) {
                console.error("Canvas element 'studentsByGenderChart' not found");
                if (chartError) chartError.classList.remove("d-none");
                return;
            }

            if (typeof maleCount !== 'number' || typeof femaleCount !== 'number' || isNaN(maleCount) || isNaN(femaleCount)) {
                console.error("Invalid chart data", { maleCount, femaleCount });
                if (chartError) chartError.classList.remove("d-none");
                return;
            }

            try {
                new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: ["Male", "Female"],
                        datasets: [{
                            label: "Students by Gender",
                            data: [maleCount, femaleCount],
                            backgroundColor: ["#4e73df", "#e74a3b"],
                            borderColor: ["#4e73df", "#e74a3b"],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: "Number of Students"
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: "Gender"
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: "top"
                            }
                        }
                    }
                });
                console.log("Chart initialized successfully with data:", { maleCount, femaleCount });
            } catch (error) {
                console.error("Error initializing chart:", error);
                if (chartError) chartError.classList.remove("d-none");
            }

            // Handle image view modal
            const imageViewModal = document.getElementById('imageViewModal');
            if (imageViewModal) {
                imageViewModal.addEventListener('show.bs.modal', async function (event) {
                    const button = event.relatedTarget;
                    const imageSrc = button.getAttribute('data-image') || '{{ asset('storage/student_avatars/unnamed.jpg') }}';
                    const pictureName = button.getAttribute('data-picture') || 'none';
                    const admissionNo = button.getAttribute('data-admissionno') || 'unknown';
                    const fileExists = button.getAttribute('data-file-exists') === 'true';
                    const defaultImageExists = button.getAttribute('data-default-exists') === 'true';
                    const modalImage = this.querySelector('#enlargedImage');
                    const placeholderText = this.querySelector('.placeholder-text');

                    console.log(`Opening imageViewModal for admissionno: ${admissionNo}, picture: ${pictureName}, src: ${imageSrc}, fileExists: ${fileExists}, defaultImageExists: ${defaultImageExists}`);

                    // Reset modal content
                    modalImage.src = '';
                    modalImage.style.display = 'none';
                    placeholderText.style.display = 'none';

                    // Use server-side file existence check
                    if (!fileExists) {
                        console.log(`Server-side check indicates image does not exist for admissionno: ${admissionNo}`);
                        modalImage.src = '{{ asset('storage/student_avatars/unnamed.jpg') }}';
                        if (defaultImageExists) {
                            modalImage.style.display = 'block';
                        } else {
                            console.error(`Default image /storage/student_avatars/unnamed.jpg does not exist for admissionno: ${admissionNo}`);
                            placeholderText.textContent = `No image available for ${admissionNo}`;
                            placeholderText.style.display = 'block';
                        }
                    } else {
                        // Verify image accessibility client-side
                        const imageExists = await checkImageExists(imageSrc);
                        if (imageExists) {
                            modalImage.src = imageSrc;
                            modalImage.style.display = 'block';
                            console.log(`Set enlarged image for admissionno: ${admissionNo}, src: ${imageSrc}`);
                        } else {
                            console.error(`Image does not exist for admissionno: ${admissionNo}, picture: ${pictureName}, attempted URL: ${imageSrc}`);
                            modalImage.src = '{{ asset('storage/student_avatars/unnamed.jpg') }}';
                            if (defaultImageExists) {
                                modalImage.style.display = 'block';
                            } else {
                                console.error(`Default image /storage/student_avatars/unnamed.jpg does not exist for admissionno: ${admissionNo}`);
                                placeholderText.textContent = `No image available for ${admissionNo}`;
                                placeholderText.style.display = 'block';
                            }
                        }
                    }

                    // Handle image load success
                    modalImage.onload = () => {
                        console.log(`Successfully loaded enlarged image for admissionno: ${admissionNo}, src: ${modalImage.src}`);
                        modalImage.style.display = 'block';
                        placeholderText.style.display = 'none';
                    };

                    // Handle image load failure
                    modalImage.onerror = () => {
                        console.error(`Failed to load enlarged image for admissionno: ${admissionNo}, picture: ${pictureName}, attempted URL: ${imageSrc}`);
                        modalImage.src = '{{ asset('storage/student_avatars/unnamed.jpg') }}';
                        if (defaultImageExists) {
                            modalImage.style.display = 'block';
                        } else {
                            console.error(`Default image /storage/student_avatars/unnamed.jpg failed to load for admissionno: ${admissionNo}`);
                            placeholderText.textContent = `No image available for ${admissionNo}`;
                            placeholderText.style.display = 'block';
                        }
                    };
                });

                // Clear image when modal is hidden
                imageViewModal.addEventListener('hidden.bs.modal', function () {
                    const modalImage = this.querySelector('#enlargedImage');
                    const placeholderText = this.querySelector('.placeholder-text');
                    modalImage.src = '';
                    modalImage.style.display = 'none';
                    placeholderText.style.display = 'none';
                    console.log('imageViewModal closed, cleared enlargedImage src and placeholder');
                });
            } else {
                console.warn('imageViewModal not found in DOM');
            }

            // Function to test if an image exists
            function checkImageExists(url) {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => {
                        console.log(`Image check succeeded for URL: ${url}`);
                        resolve(true);
                    };
                    img.onerror = () => {
                        console.log(`Image check failed for URL: ${url}`);
                        resolve(false);
                    };
                    img.src = url;
                });
            }

            // Initialize Choices.js
            if (typeof Choices !== 'undefined') {
                const genderSelect = document.getElementById("idGender");
                const admissionNoSelect = document.getElementById("idAdmissionNo");
                if (genderSelect && admissionNoSelect) {
                    window.genderFilterVal = new Choices(genderSelect, { searchEnabled: true });
                    window.admissionNoFilterVal = new Choices(admissionNoSelect, { searchEnabled: true });
                    console.log("Choices.js initialized successfully");
                } else {
                    console.warn("Choices.js elements not found");
                }
            } else {
                console.warn("Choices.js not available, falling back to native select");
            }

            // Filter data function
            window.filterData = function () {
                if (!studentList) {
                    console.error("studentList is not initialized. Cannot filter data.");
                    return;
                }

                const searchInput = document.querySelector(".search-box input.search")?.value.toLowerCase() || "";
                const genderSelect = document.getElementById("idGender");
                const admissionNoSelect = document.getElementById("idAdmissionNo");
                const selectedGender = typeof Choices !== 'undefined' && window.genderFilterVal ? window.genderFilterVal.getValue(true) : (genderSelect?.value || "all");
                const selectedAdmissionNo = typeof Choices !== 'undefined' && window.admissionNoFilterVal ? window.admissionNoFilterVal.getValue(true) : (admissionNoSelect?.value || "all");

                console.log("Filtering with:", { search: searchInput, gender: selectedGender, admissionNo: selectedAdmissionNo });

                try {
                    studentList.filter(function (item) {
                        const nameMatch = item.values().name.toLowerCase().includes(searchInput);
                        const admissionNoMatch = item.values().admissionno.toLowerCase().includes(searchInput);
                        const genderMatch = selectedGender === "all" || item.values().gender === selectedGender;
                        const admissionNoSelectMatch = selectedAdmissionNo === "all" || item.values().admissionno === selectedAdmissionNo;

                        return (nameMatch || admissionNoMatch) && genderMatch && admissionNoSelectMatch;
                    });
                } catch (error) {
                    console.error("Error in filterData:", error);
                }
            };
        });
    </script>
</div>
@endsection