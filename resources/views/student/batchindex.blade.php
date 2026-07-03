@extends('layouts.master')

@section('content')
<style>
    #batch-loader, #update-class-loader {
        backdrop-filter: blur(2px);
        font-size: 1.1rem;
        color: #333;
    }
    #batch-loader .spinner-border, #update-class-loader .spinner-border {
        width: 2rem;
        height: 2rem;
    }
    #deleteRecordModal .spinner-border {
        width: 1.5rem;
        height: 1.5rem;
    }
</style>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Batch Uploads</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Student Management</a></li>
                                <li class="breadcrumb-item active">Batch Uploads</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Batch Status Chart -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Batch Upload Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="batchStatusChart" height="100"></canvas>
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

            <div id="batchList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-4">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search batches">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idStatus" data-choices data-choices-search-false>
                                            <option value="all">Select Status</option>
                                            <option value="Success">Success</option>
                                            <option value="Failed">Failed</option>
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idClass" data-choices data-choices-search-false>
                                            <option value="all">Select Class</option>
                                            @foreach ($batch->pluck('schoolclass')->unique() as $class)
                                                <option value="{{ $class }}">{{ $class }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-2 col-sm-6">
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
                                    <h5 class="card-title mb-0">Batch Uploads <span class="badge bg-dark-subtle text-dark ms-1">{{ $batch->count() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        @can('Create student-bulk-upload')
                                            <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addBatchModal"><i class="bi bi-plus-circle align-baseline me-1"></i> New Batch Upload</button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="batchListTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th><div class="form-check"><input class="form-check-input" type="checkbox" value="option" id="checkAll"><label class="form-check-label" for="checkAll"></label></div></th>
                                                <th class="sort cursor-pointer" data-sort="sn">SN</th>
                                                <th class="sort cursor-pointer" data-sort="title">Batch Title</th>
                                                <th class="sort cursor-pointer" data-sort="schoolclass">School Class</th>
                                                <th class="sort cursor-pointer" data-sort="arm">School Arm</th>
                                                <th class="sort cursor-pointer" data-sort="term">Term</th>
                                                <th class="sort cursor-pointer" data-sort="session">Session</th>
                                                <th class="sort cursor-pointer" data-sort="status">Status</th>
                                                <th class="sort cursor-pointer" data-sort="upload_date">Upload Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @php $i = 0 @endphp
                                            @forelse ($batch as $sc)
                                                <tr>
                                                    <td class="id" data-id="{{ $sc->id }}">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="chk_child">
                                                            <label class="form-check-label"></label>
                                                        </div>
                                                    </td>
                                                    <td class="sn">{{ ++$i }}</td>
                                                    <td class="title">{{ $sc->title }}</td>
                                                    <td class="schoolclass">{{ $sc->schoolclass }}</td>
                                                    <td class="arm">{{ $sc->arm }}</td>
                                                    <td class="term">{{ $sc->term }}</td>
                                                    <td class="session">{{ $sc->session }}</td>
                                                    <td class="status" data-status="{{ $sc->status }}">
                                                        <span class="badge bg-{{ $sc->status == 'Success' ? 'success' : 'danger' }}">{{ $sc->status }}</span>
                                                    </td>
                                                    <td class="upload_date">{{ Carbon\Carbon::parse($sc->upload_date)->format('Y-m-d') }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('Create student-bulk-upload')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-primary btn-icon btn-sm update-item-btn" data-id="{{ $sc->id }}" data-schoolclass="{{ $sc->schoolclass }}" data-arm="{{ $sc->arm }}" data-schoolclassid="{{ $sc->schoolclassid }}" data-armid="{{ $sc->armid }}" data-classcategoryid="{{ $sc->classcategoryid ?? '' }}"><i class="ph-pencil"></i></a>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $sc->id }}"><i class="ph-trash"></i></a>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="noresult" style="display: block;">No results found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $batch->count() }}</span> of <span class="fw-semibold">{{ $batch->count() }}</span> Results
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
           <!-- Add Batch Modal -->
            <div id="addBatchModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="addModalLabel" class="modal-title">Add Batch Upload</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-batch-form" action="{{ route('student.bulkuploadsave') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body position-relative">
                                <!-- Loader Overlay -->
                                <div id="batch-loader" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.8); z-index: 1000;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2">Processing Batch...</span>
                                </div>
                                <!-- Form Fields -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Batch Title</label>
                                    <input type="text" id="title" name="title" class="form-control" placeholder="Enter batch title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="schoolclassid" class="form-label">School Class & Arm</label>
                                    <select id="schoolclassid" name="schoolclassid" class="form-control" data-choices data-choices-search-true required>
                                        <option value="">Select Class</option>
                                        @foreach ($schoolclasses as $sc)
                                            <option value="{{ $sc->id }}">{{ $sc->class_display }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="termid" class="form-label">Term</label>
                                    <select id="termid" name="termid" class="form-control" data-choices data-choices-search-true required>
                                        <option value="">Select Term</option>
                                        @foreach ($schoolterms as $sc)
                                            <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="sessionid" class="form-label">Session</label>
                                    <select id="sessionid" name="sessionid" class="form-control" data-choices data-choices-search-true required>
                                        <option value="">Select Session</option>
                                        @foreach ($schoolsessions as $sc)
                                            <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="filesheet" class="form-label">Upload File</label>
                                    <input type="file" id="filesheet" name="filesheet" class="form-control" accept=".xlsx,.xls,.csv" required>
                                </div>
                                <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Add Batch</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Update Class Modal -->
            <div id="updateClassModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Class</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="update-class-form" action="{{ route('student.updateclass') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body position-relative">
                                <!-- Loader Overlay -->
                                <div id="update-class-loader" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.8); z-index: 1000;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2">Updating Class...</span>
                                </div>
                                <!-- Form Fields -->
                                <div class="mb-3">
                                    <label for="update_batch_id" class="form-label">Batch ID</label>
                                    <input type="text" id="update_batch_id" name="batch_id" class="form-control" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="update_schoolclass" class="form-label">School Class Name</label>
                                    <input type="text" id="update_schoolclass" name="schoolclass" class="form-control" placeholder="Enter school class name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_arm" class="form-label">Arm Name</label>
                                    <input type="text" id="update_arm" name="arm" class="form-control" placeholder="Enter arm name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_schoolclassid" class="form-label">School Class ID</label>
                                    <input type="text" id="update_schoolclassid" name="schoolclassid" class="form-control" placeholder="Enter school class ID" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_armid" class="form-label">Arm ID</label>
                                    <input type="text" id="update_armid" name="armid" class="form-control" placeholder="Enter arm ID" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_classcategoryid" class="form-label">Class Category ID</label>
                                    <input type="text" id="update_classcategoryid" name="classcategoryid" class="form-control" placeholder="Enter class category ID" required>
                                </div>
                                <div class="alert alert-danger d-none" id="update-alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="update-btn">Update Class</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Batch Modal -->
            <div id="deleteRecordModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close" id="deleteRecord-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-md-5">
                            <div class="text-center">
                                <div class="text-danger">
                                    <i class="bi bi-trash display-4"></i>
                                </div>
                                <div class="mt-4">
                                    <h3 class="mb-2">Are you sure?</h3>
                                    <p class="text-muted fs-lg mx-3 mb-0">Are you sure you want to remove this batch?</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                <button type="button" class="btn w-sm btn-light btn-hover" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn w-sm btn-danger btn-hover" id="delete-record">
                                    <span id="delete-btn-text">Yes, Delete It!</span>
                                    <span id="delete-btn-loader" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>Deleting...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentDeleteId = null;
    let currentUpdateId = null;

    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.remove-item-btn');
        const updateButtons = document.querySelectorAll('.update-item-btn');
        const deleteRecordModal = document.getElementById('deleteRecordModal');
        const updateClassModal = document.getElementById('updateClassModal');
        const deleteBtn = document.getElementById('delete-record');
        const updateBtn = document.getElementById('update-btn');
        const updateForm = document.getElementById('update-class-form');

        // Handle Delete Buttons
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                currentDeleteId = this.getAttribute('data-id');
                console.log("Delete button clicked for batch ID:", currentDeleteId);
                if (deleteRecordModal) {
                    const modal = new bootstrap.Modal(deleteRecordModal);
                    modal.show();
                }
            });
        });

        // Handle Update Buttons
        updateButtons.forEach(button => {
            button.addEventListener('click', function () {
                currentUpdateId = this.getAttribute('data-id');
                const schoolclass = this.getAttribute('data-schoolclass');
                const arm = this.getAttribute('data-arm');
                const schoolclassid = this.getAttribute('data-schoolclassid');
                const armid = this.getAttribute('data-armid');
                const classcategoryid = this.getAttribute('data-classcategoryid');

                // Populate form fields
                document.getElementById('update_batch_id').value = currentUpdateId;
                document.getElementById('update_schoolclass').value = schoolclass;
                document.getElementById('update_arm').value = arm;
                document.getElementById('update_schoolclassid').value = schoolclassid;
                document.getElementById('update_armid').value = armid;
                document.getElementById('update_classcategoryid').value = classcategoryid || '';

                console.log("Update button clicked for batch ID:", currentUpdateId);
                if (updateClassModal) {
                    const modal = new bootstrap.Modal(updateClassModal);
                    modal.show();
                }
            });
        });

        // Handle Delete Confirmation
        if (deleteBtn) {
            deleteBtn.addEventListener('click', handleDeleteConfirmation);
        }

        function handleDeleteConfirmation() {
            if (!currentDeleteId) {
                console.error("No batch ID set for deletion");
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Error',
                    text: 'No batch selected for deletion',
                    showConfirmButton: true
                });
                return;
            }

            if (!axios) {
                console.error("Axios is not available");
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Error',
                    text: 'Axios library not loaded',
                    showConfirmButton: true
                });
                return;
            }

            const deleteBtnText = document.getElementById('delete-btn-text');
            const deleteBtnLoader = document.getElementById('delete-btn-loader');
            deleteBtnText.classList.add('d-none');
            deleteBtnLoader.classList.remove('d-none');
            deleteBtn.disabled = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                console.error("CSRF token not found");
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Error',
                    text: 'CSRF token missing',
                    showConfirmButton: true
                });
                deleteBtnText.classList.remove('d-none');
                deleteBtnLoader.classList.add('d-none');
                deleteBtn.disabled = false;
                return;
            }

            console.log("Sending DELETE request for batch ID:", currentDeleteId);
            axios.delete(`/student/deletestudentbatch?studentbatchid=${currentDeleteId}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .then(function (response) {
                console.log("Delete response:", response.data);
                const modal = bootstrap.Modal.getInstance(deleteRecordModal);
                if (modal) modal.hide();

                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Success',
                    text: response.data.message || 'Batch deleted successfully!',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch(function (error) {
                console.error("Delete error:", error.response ? error.response.data : error.message);
                deleteBtnText.classList.remove('d-none');
                deleteBtnLoader.classList.add('d-none');
                deleteBtn.disabled = false;

                const modal = bootstrap.Modal.getInstance(deleteRecordModal);
                if (modal) modal.hide();

                const errorMessage = error.response?.data?.message || 'Error deleting batch';
                Swal.fire({
                    position: 'center',
                    icon: error.response?.status === 404 ? 'warning' : 'error',
                    title: error.response?.status === 404 ? 'Batch Not Found' : 'Error',
                    text: errorMessage,
                    showConfirmButton: true
                });
            });
        }

        // Handle Update Form Submission
        if (updateForm) {
            updateForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const updateBtnText = document.getElementById('update-btn');
                const updateLoader = document.getElementById('update-class-loader');
                const errorMsg = document.getElementById('update-alert-error-msg');

                updateBtnText.disabled = true;
                updateLoader.classList.remove('d-none');

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrfToken) {
                    console.error("CSRF token not found");
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'Error',
                        text: 'CSRF token missing',
                        showConfirmButton: true
                    });
                    updateBtnText.disabled = false;
                    updateLoader.classList.add('d-none');
                    return;
                }

                const formData = new FormData(updateForm);
                axios.post(updateForm.action, formData, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(function (response) {
                    console.log("Update response:", response.data);
                    const modal = bootstrap.Modal.getInstance(updateClassModal);
                    if (modal) modal.hide();

                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message || 'Class updated successfully!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(function (error) {
                    console.error("Update error:", error.response ? error.response.data : error.message);
                    updateBtnText.disabled = false;
                    updateLoader.classList.add('d-none');

                    const errorMessage = error.response?.data?.message || 'Error updating class';
                    errorMsg.textContent = errorMessage;
                    errorMsg.classList.remove('d-none');
                });
            });
        }
    });
</script>
@endsection