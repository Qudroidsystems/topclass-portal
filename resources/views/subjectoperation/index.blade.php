{{-- resources/views/subjectoperation/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Subject Registration</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('subjects.index') }}">Student Management</a></li>
                                <li class="breadcrumb-item active">Subject Registration</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

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

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="subjectList">
                {{-- ── Class & Session Filter ── --}}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-4 col-sm-6">
                                        <select class="form-control" id="idclass">
                                            <option value="ALL">Select Class</option>
                                            @foreach ($schoolclass as $class)
                                                <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->schoolarm }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-4 col-sm-6">
                                        <select class="form-control" id="idsession">
                                            <option value="ALL">Select Session</option>
                                            @foreach ($schoolsessions as $session)
                                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-2 col-sm-6">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData();">
                                            <i class="bi bi-funnel align-baseline me-1"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Subject Teachers Card ── --}}
                <div class="row" id="subjectTeachersCard">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Subject Teachers
                                        <span class="badge bg-primary-subtle text-primary ms-1" id="subjectTeacherCount">0</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllSubjects();">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="deselectAllSubjects();">Deselect All</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="ri-information-line me-2"></i>
                                    Select the subjects you want to register or unregister students for.
                                </div>
                                <div id="subjectTeachersContainer">
                                    @foreach ($schoolterms as $term)
                                        @if ($subjectTeachers && $subjectTeachers->where('termid', $term->id)->isNotEmpty())
                                            <h6 class="mt-3">{{ $term->term }}</h6>
                                            <div class="row">
                                                @foreach ($subjectTeachers->where('termid', $term->id) as $teacher)
                                                    <div class="col-md-4">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input subject-checkbox" type="checkbox"
                                                                id="subject-{{ $teacher->subjectclassid }}"
                                                                data-subjectclassid="{{ $teacher->subjectclassid }}"
                                                                data-staffid="{{ $teacher->userid }}"
                                                                data-termid="{{ $teacher->termid }}" checked>
                                                            <label class="form-check-label" for="subject-{{ $teacher->subjectclassid }}">
                                                                {{ $teacher->subjectname }} ({{ $teacher->staffname }})
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Student Filters ── --}}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-4">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search students">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idgender">
                                            <option value="ALL">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" id="idadmission">
                                            <option value="ALL">Select Admission No</option>
                                        </select>
                                    </div>
                                    <div class="col-xxl-2 col-sm-6">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData();">
                                            <i class="bi bi-funnel align-baseline me-1"></i> Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Students Table ── --}}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Students
                                        <span class="badge bg-dark-subtle text-dark ms-1" id="studentcount">{{ $students ? $students->total() : 0 }}</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0 d-flex align-items-center gap-2 flex-wrap">
                                    <button type="button" class="btn btn-primary d-none" id="register-selected-btn"
                                        onclick="registerSelectedStudentsBatch();" aria-label="Register selected students">
                                        Register Selected
                                    </button>
                                    {{-- Unregister now opens the snapshot-naming modal first --}}
                                    <button type="button" class="btn btn-danger d-none" id="unregister-selected-btn"
                                        onclick="openUnregisterModal();" aria-label="Unregister selected students">
                                        Unregister Selected
                                    </button>
                                    <div class="spinner-border text-primary d-none" id="register-loading-spinner" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#registeredClassesModal">
                                        <i class="ri-eye-line me-1"></i> View Registered
                                    </button>
                                    <button type="button" class="btn btn-warning" id="viewArchivedBtn" onclick="openArchivedModal();">
                                        <i class="ri-archive-line me-1"></i> Unregistered History
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="subjectListTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                                        <label class="form-check-label" for="checkAll"></label>
                                                    </div>
                                                </th>
                                                <th>SN</th>
                                                <th>Admission No</th>
                                                <th>Student Name</th>
                                                <th>Class</th>
                                                <th>Gender</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                            @include('subjectoperation.partials.student_rows')
                                        </tbody>
                                    </table>
                                    <div class="d-flex justify-content-end mt-3" id="pagination-container">
                                        {{ $students ? $students->links('pagination::bootstrap-5') : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- MODAL: Snapshot Name — shown BEFORE unregistration         --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="modal fade" id="snapshotNameModal" tabindex="-1" aria-labelledby="snapshotNameModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
                        <div class="modal-content border-0 shadow-lg overflow-hidden">

                            {{-- Header --}}
                            <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#f5576c 0%,#f093fb 100%);">
                                <div class="py-1">
                                    <h5 class="modal-title text-white fw-semibold" id="snapshotNameModalLabel">
                                        <i class="ri-archive-line me-2"></i>Name this Unregistration
                                    </h5>
                                    <p class="text-white-50 small mb-0">Give this snapshot a name so you can find it later.</p>
                                </div>
                                <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            {{-- Body --}}
                            <div class="modal-body p-4">

                                {{-- Summary pills --}}
                                <div class="d-flex gap-2 flex-wrap mb-4" id="snapshotSummaryPills">
                                    <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2" id="snapshotStudentCount"></span>
                                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis px-3 py-2" id="snapshotSubjectCount"></span>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="snapshotNameInput">
                                        Snapshot Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="snapshotNameInput"
                                        placeholder="e.g. Term 2 Corrections — June 2025"
                                        maxlength="191" autocomplete="off">
                                    <div class="invalid-feedback" id="snapshotNameError">Please enter a snapshot name.</div>
                                    <div class="form-text">
                                        <i class="ri-lightbulb-line me-1 text-warning"></i>
                                        A descriptive name helps staff identify this batch when restoring it later.
                                    </div>
                                </div>

                                <div class="mb-1">
                                    <label class="form-label fw-semibold" for="snapshotNotesInput">Notes <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea class="form-control" id="snapshotNotesInput" rows="3"
                                        placeholder="Reason for unregistration or any extra context…"
                                        maxlength="1000"></textarea>
                                    <div class="form-text text-end">
                                        <span id="snapshotNotesCount">0</span>/1000
                                    </div>
                                </div>

                                {{-- Warning box --}}
                                <div class="alert alert-warning d-flex gap-2 align-items-start mt-3 mb-0 py-2">
                                    <i class="ri-error-warning-line fs-5 flex-shrink-0"></i>
                                    <div class="small">
                                        All existing scores for these students in the selected subjects will be saved to the snapshot and can be fully restored later.
                                    </div>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger px-4" id="confirmUnregisterBtn" onclick="proceedUnregister();">
                                    <i class="ri-user-unfollow-line me-1"></i> Unregister & Save Snapshot
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- MODAL: Registered Classes                                  --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="modal fade" id="registeredClassesModal" tabindex="-1" aria-labelledby="registeredClassesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h5 class="modal-title text-white">
                                    <i class="ri-graduation-cap-line me-2"></i>Registered Classes Overview
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="background: #f8f9fc;">
                                <div id="registeredClassesContent">
                                    <div class="text-center text-muted py-5">
                                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                                        <p class="mt-3 mb-0">Loading registration data...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="ri-close-line me-1"></i>Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- MODAL: Unregistered History (snapshot list)                --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="modal fade" id="archivedModal" tabindex="-1" aria-labelledby="archivedModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <h5 class="modal-title text-white" id="archivedModalLabel">
                                    <i class="ri-archive-line me-2"></i>Unregistered History
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">

                                {{-- Toolbar --}}
                                <div class="p-3 border-bottom bg-light d-flex align-items-center flex-wrap gap-2">
                                    <div class="flex-grow-1">
                                        <input type="text" class="form-control form-control-sm" id="archiveSearch"
                                            placeholder="Search snapshot name or subject…" style="max-width:300px;">
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <select class="form-select form-select-sm" id="archiveTermFilter" style="width:auto;">
                                            <option value="">All Terms</option>
                                            @foreach($schoolterms as $term)
                                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                                            @endforeach
                                        </select>
                                        <select class="form-select form-select-sm" id="archivePerPage" style="width:auto;">
                                            <option value="20">20 per page</option>
                                            <option value="50" selected>50 per page</option>
                                            <option value="100">100 per page</option>
                                            <option value="150">150 per page</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="loadArchivedPage(1);">
                                            <i class="ri-refresh-line"></i> Refresh
                                        </button>
                                        <button class="btn btn-sm btn-success d-none" id="restoreSelectedBtn" onclick="restoreSelected();">
                                            <i class="ri-refresh-line me-1"></i> Restore Selected
                                        </button>
                                        <button class="btn btn-sm btn-danger d-none" id="deleteSelectedBtn" onclick="permanentDeleteSelected();">
                                            <i class="ri-delete-bin-line me-1"></i> Delete Selected
                                        </button>
                                        <div class="spinner-border spinner-border-sm text-warning d-none" id="archiveSpinner" role="status"></div>
                                    </div>
                                </div>

                                {{-- Snapshot cards --}}
                                <div class="p-3" id="snapshotCardsContainer">
                                    <div class="text-center text-muted py-4">
                                        Select a class and session first, then open this panel.
                                    </div>
                                </div>

                                {{-- Pagination --}}
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" id="archivePaginationWrap">
                                    <small class="text-muted" id="archiveMeta"></small>
                                    <div id="archivePagination" class="d-flex gap-1"></div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <small class="text-muted me-auto">
                                    <i class="ri-information-line me-1"></i>
                                    Click a snapshot to view student details. Restored records are fully re-registered with original scores.
                                </small>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════════════════ --}}
                {{-- MODAL: Snapshot Detail (students + scores inside a snapshot) --}}
                {{-- ══════════════════════════════════════════════════════════ --}}
                <div class="modal fade" id="snapshotDetailModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow-lg">

                            {{-- Header (dynamically filled) --}}
                            <div class="modal-header border-0" style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);">
                                <div>
                                    <h5 class="modal-title text-white fw-semibold" id="snapshotDetailTitle">Snapshot Detail</h5>
                                    <p class="text-white-50 small mb-0" id="snapshotDetailSubtitle"></p>
                                </div>
                                <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            {{-- Body --}}
                            <div class="modal-body p-0">

                                {{-- Notes banner (shown only if snapshot has notes) --}}
                                <div id="snapshotNotesBanner" class="alert alert-info d-flex gap-2 align-items-start m-3 mb-0 d-none">
                                    <i class="ri-sticky-note-line fs-5 flex-shrink-0"></i>
                                    <div id="snapshotNotesText" class="small"></div>
                                </div>

                                {{-- Toolbar --}}
                                <div class="px-3 pt-3 pb-2 border-bottom">
                                    {{-- Row 1: search --}}
                                    <div class="mb-2">
                                        <div class="input-group input-group-sm" style="max-width:340px;">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="ri-search-line text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0 ps-0"
                                                id="detailSearchInput"
                                                placeholder="Search by name or admission no…"
                                                oninput="filterDetailRows(this.value);">
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="document.getElementById('detailSearchInput').value='';filterDetailRows('');"
                                                title="Clear search">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Row 2: action buttons + meta --}}
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <button class="btn btn-sm btn-success" id="detailRestoreAllBtn" onclick="restoreEntireSnapshot();">
                                            <i class="ri-refresh-line me-1"></i> Restore All
                                        </button>
                                        <button class="btn btn-sm btn-success d-none" id="detailRestoreSelectedBtn" onclick="restoreDetailSelected();">
                                            <i class="ri-refresh-line me-1"></i> Restore Selected
                                        </button>
                                        <button class="btn btn-sm btn-danger d-none" id="detailDeleteSelectedBtn" onclick="deleteDetailSelected();">
                                            <i class="ri-delete-bin-line me-1"></i> Delete Selected
                                        </button>
                                        <div class="spinner-border spinner-border-sm text-primary d-none ms-1" id="detailSpinner" role="status"></div>
                                        <span class="text-muted small ms-auto" id="detailStudentMeta"></span>
                                    </div>
                                </div>

                                {{-- Table --}}
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr id="snapshotDetailHeaderRow">
                                                <th style="width:36px;">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="detailCheckAll">
                                                    </div>
                                                </th>
                                                <th>Student</th>
                                                <th>Adm. No</th>
                                                <th>Gender</th>
                                                {{-- Assessment columns injected by JS --}}
                                            </tr>
                                        </thead>
                                        <tbody id="snapshotDetailBody">
                                            <tr><td colspan="10" class="text-center text-muted py-4">Loading…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Image View Modal --}}
                <div id="imageViewModal" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Student Image</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="enlargedImage" src="" alt="Student Image" class="img-fluid"
                                    onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /subjectList --}}
        </div>
    </div>
</div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ============================================================================
// GLOBALS
// ============================================================================
const ROUTES = {
    batchRegister   : '{{ route("subjectregistration.batch") }}',
    unregister      : '{{ route("subjects.destroy") }}',
    getRegistered   : '{{ route("subjects.registered-classes") }}',
    getArchived     : '{{ route("subjectoperation.archived") }}',
    getSnapshot     : '{{ route("subjectoperation.snapshot.detail") }}',
    restore         : '{{ route("subjectoperation.restore") }}',
    permanentDelete : '{{ route("subjectoperation.archive.batch-delete") }}',
    index           : '{{ route("subjects.index") }}',
};
const CSRF       = '{{ csrf_token() }}';
const AVATAR_URL = '{{ asset("storage") }}';

// Archive / snapshot state
let archiveCurrentPage = 1;
let archiveMeta        = {};
let archiveSearchTimer = null;

// Current snapshot being viewed in the detail modal
let currentSnapshotMeta = null;   // { snapshot_name, subjectclassid, termid, sessionid, staffid }
let currentSnapshotRows = [];     // all rows loaded in detail modal

// ============================================================================
// SWEET ALERT HELPER
// ============================================================================
function showSweetAlert(title, message, type, success = true) {
    Swal.fire({
        title,
        html: `<div class="d-flex align-items-center justify-content-center gap-2">
                <span style="font-size:2rem;">${success ? '🎉' : '😞'}</span>
                <span>${message}</span>
               </div>`,
        icon: success ? 'success' : 'error',
        confirmButtonColor: success ? '#28a745' : '#dc3545',
        confirmButtonText: success ? 'Great!' : 'Okay',
        timer: success ? 3000 : 5000,
        showConfirmButton: true,
    });
}

// ============================================================================
// IMAGE MODAL
// ============================================================================
document.addEventListener('DOMContentLoaded', function () {
    const imgModal = document.getElementById('imageViewModal');
    if (imgModal) {
        imgModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const src = btn?.getAttribute('data-image');
            document.getElementById('enlargedImage').src = src || '{{ asset("storage/student_avatars/unnamed.jpg") }}';
        });
    }

    document.getElementById('registeredClassesModal')?.addEventListener('show.bs.modal', loadRegisteredClasses);
    document.getElementById('archivePerPage')?.addEventListener('change', () => loadArchivedPage(1));

    // Character counter for snapshot notes
    document.getElementById('snapshotNotesInput')?.addEventListener('input', function () {
        document.getElementById('snapshotNotesCount').textContent = this.value.length;
    });
});

// ============================================================================
// FILTER / SEARCH
// ============================================================================
function filterData() {
    const classId   = document.getElementById('idclass').value;
    const sessionId = document.getElementById('idsession').value;
    const search    = document.querySelector('.search')?.value ?? '';
    const gender    = document.getElementById('idgender').value;
    const admission = document.getElementById('idadmission').value;

    const params = new URLSearchParams({ class_id: classId, session_id: sessionId, search, gender, admissionno: admission });
    window.location.href = ROUTES.index + '?' + params.toString();
}

function selectAllSubjects()   { document.querySelectorAll('.subject-checkbox').forEach(cb => cb.checked = true);  updateSubjectCount(); }
function deselectAllSubjects() { document.querySelectorAll('.subject-checkbox').forEach(cb => cb.checked = false); updateSubjectCount(); }
function updateSubjectCount()  { document.getElementById('subjectTeacherCount').textContent = document.querySelectorAll('.subject-checkbox:checked').length; }
document.querySelectorAll('.subject-checkbox').forEach(cb => cb.addEventListener('change', updateSubjectCount));
updateSubjectCount();

// ============================================================================
// CHECK ALL STUDENTS
// ============================================================================
document.getElementById('checkAll')?.addEventListener('change', function () {
    document.querySelectorAll('#studentTableBody input[name="chk_child"]').forEach(cb => cb.checked = this.checked);
    toggleBatchButtons();
});
document.addEventListener('change', function (e) {
    if (e.target?.name === 'chk_child') toggleBatchButtons();
});
function toggleBatchButtons() {
    const any = document.querySelectorAll('#studentTableBody input[name="chk_child"]:checked').length > 0;
    document.getElementById('register-selected-btn')?.classList.toggle('d-none', !any);
    document.getElementById('unregister-selected-btn')?.classList.toggle('d-none', !any);
}

// ============================================================================
// HELPERS
// ============================================================================
function getSelectedStudentIds() {
    return [...document.querySelectorAll('#studentTableBody input[name="chk_child"]:checked')]
        .map(cb => parseInt(cb.closest('tr').querySelector('.id').dataset.id));
}
function getSelectedSubjectClasses() {
    return [...document.querySelectorAll('.subject-checkbox:checked')].map(cb => ({
        subjectclassid: parseInt(cb.dataset.subjectclassid),
        staffid       : parseInt(cb.dataset.staffid),
        termid        : parseInt(cb.dataset.termid),
    }));
}
function setSpinner(on) { document.getElementById('register-loading-spinner')?.classList.toggle('d-none', !on); }

async function apiFetch(url, method, body) {
    const res  = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok && !data.success) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
}

function escapeHtml(str) {
    if (!str) return str ?? '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

// ============================================================================
// REGISTER BATCH
// ============================================================================
async function registerSelectedStudentsBatch() {
    const studentIds     = getSelectedStudentIds();
    const subjectClasses = getSelectedSubjectClasses();
    const sessionId      = document.getElementById('idsession').value;

    if (!studentIds.length)    return showSweetAlert('No Students Selected', 'Please select at least one student.', 'warning', false);
    if (!subjectClasses.length)return showSweetAlert('No Subjects Selected', 'Please select at least one subject.', 'warning', false);
    if (sessionId === 'ALL')   return showSweetAlert('Session Required', 'Please select a session.', 'warning', false);

    const ok = await Swal.fire({
        title: 'Confirm Registration',
        html : `<div class="text-center"><span style="font-size:3rem;">📚</span><p class="mt-2">Register <strong>${studentIds.length}</strong> student(s) for <strong>${subjectClasses.length}</strong> subject(s)?</p></div>`,
        icon : 'question', showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, register!',
    });
    if (!ok.isConfirmed) return;

    setSpinner(true);
    try {
        const res = await apiFetch(ROUTES.batchRegister, 'POST', { studentids: studentIds, subjectclasses: subjectClasses, sessionid: parseInt(sessionId) });
        if (res.success) { showSweetAlert('Registration Successful!', res.message, 'success', true); setTimeout(() => location.reload(), 2000); }
        else showSweetAlert('Registration Failed', res.message || 'Some students could not be registered.', 'error', false);
    } catch (err) {
        showSweetAlert('Error', 'Registration failed: ' + err.message, 'error', false);
    } finally { setSpinner(false); }
}

// ============================================================================
// OPEN UNREGISTER MODAL (snapshot naming step)
// ============================================================================
function openUnregisterModal() {
    const studentIds     = getSelectedStudentIds();
    const subjectClasses = getSelectedSubjectClasses();
    const sessionId      = document.getElementById('idsession').value;

    if (!studentIds.length)    return showSweetAlert('No Students Selected', 'Please select at least one student.', 'warning', false);
    if (!subjectClasses.length)return showSweetAlert('No Subjects Selected', 'Please select at least one subject.', 'warning', false);
    if (sessionId === 'ALL')   return showSweetAlert('Session Required', 'Please select a session.', 'warning', false);

    // Populate summary pills
    document.getElementById('snapshotStudentCount').textContent = `${studentIds.length} student${studentIds.length !== 1 ? 's' : ''}`;
    document.getElementById('snapshotSubjectCount').textContent = `${subjectClasses.length} subject${subjectClasses.length !== 1 ? 's' : ''}`;

    // Reset form
    const nameInput = document.getElementById('snapshotNameInput');
    nameInput.value = '';
    nameInput.classList.remove('is-invalid');
    document.getElementById('snapshotNotesInput').value = '';
    document.getElementById('snapshotNotesCount').textContent = '0';

    // Suggest a default name with date + time
    const now     = new Date();
    const dateStr = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    nameInput.value = `Unregistration — ${dateStr} ${timeStr}`;

    new bootstrap.Modal(document.getElementById('snapshotNameModal')).show();
}

// ============================================================================
// PROCEED UNREGISTER (called from confirm button in snapshot modal)
// ============================================================================
async function proceedUnregister() {
    const nameInput  = document.getElementById('snapshotNameInput');
    const notesInput = document.getElementById('snapshotNotesInput');
    const name       = nameInput.value.trim();

    if (!name) {
        nameInput.classList.add('is-invalid');
        return;
    }
    nameInput.classList.remove('is-invalid');

    const studentIds     = getSelectedStudentIds();
    const subjectClasses = getSelectedSubjectClasses();
    const sessionId      = document.getElementById('idsession').value;

    // Close the naming modal
    bootstrap.Modal.getInstance(document.getElementById('snapshotNameModal'))?.hide();

    setSpinner(true);
    try {
        const res = await apiFetch(ROUTES.unregister, 'DELETE', {
            studentids    : studentIds,
            subjectclasses: subjectClasses,
            sessionid     : parseInt(sessionId),
            snapshot_name : name,
            snapshot_notes: notesInput.value.trim() || null,
        });

        if (res.success || res.success_count > 0) {
            showSweetAlert(
                'Unregistration Complete',
                `${res.success_count} student(s) unregistered.<br><small class="text-muted">Snapshot saved as "<strong>${escapeHtml(name)}</strong>"</small>`,
                'success', true
            );
            setTimeout(() => location.reload(), 2500);
        } else {
            showSweetAlert('Unregistration Failed', res.message || 'No students were unregistered.', 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', 'Unregistration failed: ' + err.message, 'error', false);
    } finally { setSpinner(false); }
}

// ============================================================================
// REGISTERED CLASSES MODAL
// ============================================================================
async function loadRegisteredClasses() {
    const classId   = document.getElementById('idclass').value;
    const sessionId = document.getElementById('idsession').value;
    const container = document.getElementById('registeredClassesContent');

    if (classId === 'ALL' || sessionId === 'ALL') {
        container.innerHTML = `<div class="text-center py-5"><i class="ri-error-warning-line ri-3x text-warning"></i><p class="text-muted mt-3 mb-0">Please select a class and session first.</p></div>`;
        return;
    }

    container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" style="width:3rem;height:3rem;"></div><p class="mt-3 text-muted">Loading…</p></div>`;

    try {
        const res  = await fetch(ROUTES.getRegistered + '?' + new URLSearchParams({ class_id: classId, session_id: sessionId }), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.success || !data.data.length) {
            container.innerHTML = `<div class="text-center py-5"><i class="ri-information-line ri-3x text-muted"></i><p class="text-muted mt-3 mb-0">No registered classes found.</p></div>`;
            return;
        }

        let html = `<div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;">
                <th class="fw-semibold py-3">Class</th>
                <th class="fw-semibold py-3">Session</th>
                <th class="fw-semibold py-3">Term</th>
                <th class="fw-semibold py-3 text-center">Students</th>
                <th class="fw-semibold py-3 text-center">Subjects</th>
                <th class="fw-semibold py-3">Teachers</th>
                <th class="fw-semibold py-3">Subjects List</th>
            </tr></thead><tbody>`;

        data.data.forEach((row, i) => {
            let teachersHtml = '<span class="text-muted">—</span>';
            if (row.teachers?.length) {
                teachersHtml = '<div class="d-flex flex-wrap gap-2">' + row.teachers.map(t => {
                    const pic = t.picture ? `${AVATAR_URL}/staff_avatars/${t.picture}` : `${AVATAR_URL}/staff_avatars/default.png`;
                    return `<div class="d-flex align-items-center gap-2 bg-white rounded-3 px-2 py-1 shadow-sm" style="border:1px solid #e0e0e0;">
                        <img src="${pic}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" onerror="this.src='${AVATAR_URL}/staff_avatars/default.png'">
                        <span class="fw-medium" style="font-size:.85rem;">${escapeHtml(t.name)}</span>
                    </div>`;
                }).join('') + '</div>';
            }

            html += `<tr class="${i % 2 === 0 ? 'bg-light' : ''}">
                <td class="fw-medium"><i class="ri-school-line text-primary me-2"></i>${escapeHtml(row.class_name)} ${escapeHtml(row.arm_name)}</td>
                <td><span class="badge bg-info-subtle text-info">${escapeHtml(row.session_name)}</span></td>
                <td><span class="badge bg-secondary-subtle text-secondary">${escapeHtml(row.term_name)}</span></td>
                <td class="text-center"><span class="badge bg-primary rounded-pill px-3 py-2">${row.student_count}</span></td>
                <td class="text-center"><span class="badge bg-success rounded-pill px-3 py-2">${row.subject_count}</span></td>
                <td>${teachersHtml}</td>
                <td><small class="text-muted">${escapeHtml(row.subjects)}</small></td>
            </tr>`;
        });

        html += `</tbody></table></div>`;
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = `<div class="alert alert-danger m-3">Failed to load data: ${err.message}</div>`;
    }
}

// ============================================================================
// ARCHIVE (SNAPSHOT LIST) MODAL
// ============================================================================
function openArchivedModal() {
    const classId   = document.getElementById('idclass').value;
    const sessionId = document.getElementById('idsession').value;

    if (classId === 'ALL' || sessionId === 'ALL') {
        return showSweetAlert('Selection Required', 'Please select a class and session first.', 'warning', false);
    }

    archiveCurrentPage = 1;
    new bootstrap.Modal(document.getElementById('archivedModal')).show();
    loadArchivedPage(1);
}

async function loadArchivedPage(page) {
    archiveCurrentPage = page;

    const classId   = document.getElementById('idclass').value;
    const sessionId = document.getElementById('idsession').value;
    const termId    = document.getElementById('archiveTermFilter').value;
    const search    = document.getElementById('archiveSearch').value.trim();
    const perPage   = document.getElementById('archivePerPage').value;

    if (classId === 'ALL' || sessionId === 'ALL') return;

    const spinner   = document.getElementById('archiveSpinner');
    const container = document.getElementById('snapshotCardsContainer');

    spinner.classList.remove('d-none');
    container.innerHTML = `<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning me-2"></div> Loading snapshots…</div>`;

    try {
        const params = new URLSearchParams({ class_id: classId, session_id: sessionId, page, per_page: perPage });
        if (termId) params.set('term_id', termId);
        if (search) params.set('search', search);

        const res  = await fetch(ROUTES.getArchived + '?' + params.toString(), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = `<div class="text-center text-danger py-4">${data.message}</div>`;
            return;
        }

        archiveMeta = data.meta;
        renderSnapshotCards(data.data);
        renderArchivePagination(data.meta);
        updateArchiveMeta(data.meta);

    } catch (err) {
        container.innerHTML = `<div class="text-center text-danger py-4">Error: ${err.message}</div>`;
    } finally {
        spinner.classList.add('d-none');
    }
}

// ── Render snapshot cards ────────────────────────────────────────────────────
function renderSnapshotCards(rows) {
    const container = document.getElementById('snapshotCardsContainer');
    const restoreBtn = document.getElementById('restoreSelectedBtn');
    const deleteBtn  = document.getElementById('deleteSelectedBtn');

    if (!rows.length) {
        container.innerHTML = `<div class="text-center text-muted py-5"><i class="ri-archive-line ri-3x d-block mb-2"></i>No unregistration snapshots found.</div>`;
        restoreBtn?.classList.add('d-none');
        deleteBtn?.classList.add('d-none');
        return;
    }

    restoreBtn?.classList.add('d-none');
    deleteBtn?.classList.add('d-none');

    // Group rows by snapshot_name to create "batch" cards
    const groups = {};
    rows.forEach(row => {
        const key = `${row.snapshot_name}__${row.subjectclassid}__${row.termid}`;
        if (!groups[key]) groups[key] = { ...row, subjects: [] };
        groups[key].subjects.push({
            subjectname    : row.subjectname,
            subjectcode    : row.subjectcode,
            staffname      : row.staffname,
            student_count  : row.student_count,
            subjectclassid : row.subjectclassid,
            termid         : row.termid,
            sessionid      : row.sessionid,
            staffid        : row.staffid,
            archive_id     : row.archive_id,
        });
    });

    let html = '<div class="row g-3">';

    Object.values(groups).forEach(group => {
        const unregDate = group.unregistered_at
            ? new Date(group.unregistered_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
            : '—';

        const subjectPills = group.subjects.map(s =>
            `<span class="badge bg-primary-subtle text-primary me-1 mb-1">${escapeHtml(s.subjectname)}</span>`
        ).join('');

        const metaEncoded = encodeURIComponent(JSON.stringify({
            snapshot_name  : group.snapshot_name,
            subjectclassid : group.subjectclassid,
            termid         : group.termid,
            sessionid      : group.sessionid,
            staffid        : group.staffid,
            archive_id     : group.archive_id,
        }));

        html += `
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 snapshot-card" style="cursor:pointer;transition:transform .15s,box-shadow .15s;"
                 onclick="openSnapshotDetail('${metaEncoded}')"
                 onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)';"
                 onmouseleave="this.style.transform='';this.style.boxShadow='';">
                <div class="card-body">
                    {{-- Header --}}
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div class="flex-grow-1 min-w-0">
                            <h6 class="fw-semibold mb-0 text-truncate" title="${escapeHtml(group.snapshot_name)}">
                                <i class="ri-camera-line text-danger me-1"></i>${escapeHtml(group.snapshot_name)}
                            </h6>
                            <small class="text-muted">${unregDate}</small>
                        </div>
                        <div class="flex-shrink-0 d-flex gap-1">
                            <span class="badge bg-danger-subtle text-danger rounded-pill">
                                ${group.student_count} student${group.student_count !== 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>

                    {{-- Notes --}}
                    ${group.snapshot_notes ? `<p class="text-muted small fst-italic mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">"${escapeHtml(group.snapshot_notes)}"</p>` : ''}

                    {{-- Subject pills --}}
                    <div class="mb-2">${subjectPills}</div>

                    {{-- Footer meta --}}
                    <div class="d-flex justify-content-between align-items-center mt-auto pt-1 border-top">
                        <small class="text-muted">
                            <i class="ri-user-star-line me-1"></i>${escapeHtml(group.staffname ?? '—')}
                        </small>
                        <small class="text-muted">
                            <span class="badge bg-warning-subtle text-warning-emphasis">${escapeHtml(group.termname)}</span>
                        </small>
                    </div>
                </div>

                {{-- Card actions bar --}}
                <div class="card-footer bg-light border-0 d-flex gap-2 py-2">
                    <button class="btn btn-sm btn-outline-primary flex-grow-1" onclick="event.stopPropagation();openSnapshotDetail('${metaEncoded}');">
                        <i class="ri-eye-line me-1"></i> View
                    </button>
                    <button class="btn btn-sm btn-outline-success flex-grow-1" onclick="event.stopPropagation();restoreSingleSnapshot('${metaEncoded}');">
                        <i class="ri-refresh-line me-1"></i> Restore
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation();deleteSnapshotGroup('${metaEncoded}');" title="Delete snapshot">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>
        </div>`;
    });

    html += '</div>';
    container.innerHTML = html;
}

// ── Pagination helpers ────────────────────────────────────────────────────────
function renderArchivePagination(meta) {
    const container = document.getElementById('archivePagination');
    if (!meta || meta.last_page <= 1) { container.innerHTML = ''; return; }

    let html = `<button class="btn btn-sm btn-outline-secondary ${meta.current_page === 1 ? 'disabled' : ''}" onclick="loadArchivedPage(${meta.current_page - 1})">‹</button>`;
    const delta = 3;
    for (let p = 1; p <= meta.last_page; p++) {
        if (p === 1 || p === meta.last_page || (p >= meta.current_page - delta && p <= meta.current_page + delta)) {
            html += `<button class="btn btn-sm ${p === meta.current_page ? 'btn-warning' : 'btn-outline-secondary'}" onclick="loadArchivedPage(${p})">${p}</button>`;
        } else if (p === meta.current_page - delta - 1 || p === meta.current_page + delta + 1) {
            html += `<span class="btn btn-sm btn-outline-secondary disabled">…</span>`;
        }
    }
    html += `<button class="btn btn-sm btn-outline-secondary ${meta.current_page === meta.last_page ? 'disabled' : ''}" onclick="loadArchivedPage(${meta.current_page + 1})">›</button>`;
    container.innerHTML = html;
}

function updateArchiveMeta(meta) {
    const el = document.getElementById('archiveMeta');
    if (!meta || !meta.total) { el.textContent = ''; return; }
    const from = (meta.current_page - 1) * meta.per_page + 1;
    const to   = Math.min(meta.current_page * meta.per_page, meta.total);
    el.textContent = `Showing ${from}–${to} of ${meta.total} snapshots`;
}

document.getElementById('archiveSearch')?.addEventListener('input', function () {
    clearTimeout(archiveSearchTimer);
    archiveSearchTimer = setTimeout(() => loadArchivedPage(1), 400);
});
document.getElementById('archiveTermFilter')?.addEventListener('change', () => loadArchivedPage(1));

// ============================================================================
// SNAPSHOT DETAIL MODAL
// ============================================================================
async function openSnapshotDetail(metaEncoded) {
    currentSnapshotMeta = JSON.parse(decodeURIComponent(metaEncoded));

    document.getElementById('snapshotDetailTitle').textContent   = currentSnapshotMeta.snapshot_name;
    document.getElementById('snapshotDetailSubtitle').textContent = '';
    document.getElementById('snapshotNotesBanner')?.classList.add('d-none');

    // Reset search
    const searchInput = document.getElementById('detailSearchInput');
    if (searchInput) searchInput.value = '';

    document.getElementById('snapshotDetailBody').innerHTML =
        '<tr><td colspan="10" class="text-center py-4"><div class="spinner-border spinner-border-sm me-2"></div>Loading students…</td></tr>';

    // Hide per-row selection buttons until loaded
    document.getElementById('detailRestoreSelectedBtn')?.classList.add('d-none');
    document.getElementById('detailDeleteSelectedBtn')?.classList.add('d-none');

    const modal = new bootstrap.Modal(document.getElementById('snapshotDetailModal'));
    modal.show();

    try {
        const params = new URLSearchParams({
            snapshot_name  : currentSnapshotMeta.snapshot_name,
            subjectclassid : currentSnapshotMeta.subjectclassid,
            termid         : currentSnapshotMeta.termid,
            sessionid      : currentSnapshotMeta.sessionid,
            staffid        : currentSnapshotMeta.staffid,
        });

        const res  = await fetch(ROUTES.getSnapshot + '?' + params.toString(), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.success) {
            document.getElementById('snapshotDetailBody').innerHTML =
                `<tr><td colspan="10" class="text-center text-danger py-4">${data.message}</td></tr>`;
            return;
        }

        currentSnapshotRows = data.rows;

        // Show notes banner
        if (data.snapshot_notes) {
            const banner = document.getElementById('snapshotNotesBanner');
            banner?.classList.remove('d-none');
            document.getElementById('snapshotNotesText').textContent = data.snapshot_notes;
        }

        document.getElementById('detailStudentMeta').textContent =
            `${data.total_students} student${data.total_students !== 1 ? 's' : ''} in this snapshot`;

        renderSnapshotDetailTable(data.rows, data.assessment_headers);

    } catch (err) {
        document.getElementById('snapshotDetailBody').innerHTML =
            `<tr><td colspan="10" class="text-center text-danger py-4">Error: ${err.message}</td></tr>`;
    }
}

function renderSnapshotDetailTable(rows, assessmentHeaders) {
    // Build dynamic header columns
    const headerRow = document.getElementById('snapshotDetailHeaderRow');
    // Remove old dynamic columns (leave first 4: checkbox, student, adm, gender)
    while (headerRow.cells.length > 4) headerRow.deleteCell(headerRow.cells.length - 1);

    (assessmentHeaders || []).forEach(a => {
        const th = document.createElement('th');
        th.textContent = a.assessment_name || `Assessment ${a.assessment_id}`;
        headerRow.appendChild(th);
    });

    const th = document.createElement('th');
    th.textContent = 'Total';
    headerRow.appendChild(th);

    // Build body rows
    let html = '';
    rows.forEach(row => {
        const name    = [row.lastname, row.firstname, row.othername].filter(Boolean).join(' ');
        const picFile = row.picture ? row.picture.split('/').pop() : null;
        const pic     = picFile
            ? `${AVATAR_URL}/student_avatars/${picFile}`
            : `${AVATAR_URL}/student_avatars/unnamed.jpg`;

        // Gender: solid blue for Male, solid pink/rose for Female — always high contrast
        const genderBadge = row.gender === 'Female'
            ? `<span class="badge text-white" style="background:#e84393;">${escapeHtml(row.gender)}</span>`
            : `<span class="badge text-white" style="background:#1a6fd4;">${escapeHtml(row.gender ?? '—')}</span>`;

        let scoresCells = '';
        let total       = 0;

        (assessmentHeaders || []).forEach(a => {
            const score = (row.assessment_scores || []).find(s => s.assessment_id == a.assessment_id);
            const val   = score ? parseFloat(score.score) : 0;
            total += val;
            scoresCells += `<td class="text-center fw-medium">${val > 0 ? val.toFixed(1) : '<span class="text-muted">—</span>'}</td>`;
        });

        scoresCells += `<td class="text-center fw-bold ${total > 0 ? 'text-success' : 'text-muted'}">${total > 0 ? total.toFixed(1) : '—'}</td>`;

        // Store searchable text as data attribute for client-side filtering
        const searchKey = `${name} ${row.admissionno ?? ''}`.toLowerCase();

        html += `<tr data-archive-id="${row.archive_id}" data-search="${escapeHtml(searchKey)}">
            <td><div class="form-check mb-0"><input class="form-check-input detail-chk" type="checkbox" value="${row.archive_id}"></div></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${pic}" class="rounded-circle" style="width:34px;height:34px;object-fit:cover;border:2px solid #e9ecef;"
                         onerror="this.src='${AVATAR_URL}/student_avatars/unnamed.jpg'">
                    <span class="fw-medium">${escapeHtml(name)}</span>
                </div>
            </td>
            <td class="text-muted small">${escapeHtml(row.admissionno ?? '—')}</td>
            <td>${genderBadge}</td>
            ${scoresCells}
        </tr>`;
    });

    document.getElementById('snapshotDetailBody').innerHTML = html || '<tr><td colspan="10" class="text-center text-muted py-4">No students found.</td></tr>';

    // Wire up checkboxes
    document.getElementById('detailCheckAll')?.addEventListener('change', function () {
        document.querySelectorAll('.detail-chk').forEach(cb => cb.checked = this.checked);
        toggleDetailButtons();
    });
    document.querySelectorAll('.detail-chk').forEach(cb => {
        cb.addEventListener('change', toggleDetailButtons);
    });
}

function toggleDetailButtons() {
    const any = document.querySelectorAll('.detail-chk:checked').length > 0;
    document.getElementById('detailRestoreSelectedBtn')?.classList.toggle('d-none', !any);
    document.getElementById('detailDeleteSelectedBtn')?.classList.toggle('d-none', !any);
}

// Client-side search filter for the snapshot detail table
function filterDetailRows(query) {
    const q     = query.toLowerCase().trim();
    const rows  = document.querySelectorAll('#snapshotDetailBody tr[data-search]');
    let visible = 0;

    rows.forEach(tr => {
        const match = !q || tr.dataset.search.includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    // Update the meta count to show filtered vs total
    const total = currentSnapshotRows.length;
    const meta  = document.getElementById('detailStudentMeta');
    if (meta) {
        meta.textContent = q
            ? `${visible} of ${total} student${total !== 1 ? 's' : ''} shown`
            : `${total} student${total !== 1 ? 's' : ''} in this snapshot`;
    }
}

// ============================================================================
// RESTORE — from snapshot detail modal
// ============================================================================
async function restoreEntireSnapshot() {
    if (!currentSnapshotRows.length) return;
    const ids = currentSnapshotRows.map(r => r.archive_id);
    await doRestore(ids, 'all students in this snapshot');
}

async function restoreDetailSelected() {
    const ids = [...document.querySelectorAll('.detail-chk:checked')].map(cb => parseInt(cb.value));
    if (!ids.length) return;
    await doRestore(ids, `${ids.length} selected student${ids.length !== 1 ? 's' : ''}`);
}

async function doRestore(archiveIds, label) {
    const ok = await Swal.fire({
        title: 'Restore Registration?',
        html : `<p>Restore <strong>${label}</strong>? Their original scores will be recovered.</p>`,
        icon : 'question', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Yes, restore!'
    });
    if (!ok.isConfirmed) return;

    const spinner = document.getElementById('detailSpinner');
    spinner?.classList.remove('d-none');

    try {
        const res = await apiFetch(ROUTES.restore, 'POST', { archive_ids: archiveIds });
        if (res.success || res.total_restored > 0) {
            showSweetAlert('Restored!', `${res.total_restored || archiveIds.length} registration(s) restored with original scores.`, 'success', true);
            bootstrap.Modal.getInstance(document.getElementById('snapshotDetailModal'))?.hide();
            loadArchivedPage(archiveCurrentPage);
        } else {
            showSweetAlert('Restore Failed', res.message || 'Could not restore.', 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', 'Restore failed: ' + err.message, 'error', false);
    } finally {
        spinner?.classList.add('d-none');
    }
}

// ── Restore from card button (entire snapshot group) ─────────────────────────
async function restoreSingleSnapshot(metaEncoded) {
    const meta = JSON.parse(decodeURIComponent(metaEncoded));

    const ok = await Swal.fire({
        title: 'Restore Snapshot?',
        html : `<p>Restore all students in snapshot "<strong>${escapeHtml(meta.snapshot_name)}</strong>"?<br>Original scores will be recovered.</p>`,
        icon : 'question', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Yes, restore all!'
    });
    if (!ok.isConfirmed) return;

    const spinner = document.getElementById('archiveSpinner');
    spinner?.classList.remove('d-none');

    try {
        // Load the archive_ids for this snapshot group first
        const params = new URLSearchParams({
            snapshot_name  : meta.snapshot_name,
            subjectclassid : meta.subjectclassid,
            termid         : meta.termid,
            sessionid      : meta.sessionid,
            staffid        : meta.staffid,
        });
        const detailRes  = await fetch(ROUTES.getSnapshot + '?' + params.toString(), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const detailData = await detailRes.json();

        if (!detailData.success || !detailData.rows?.length) {
            showSweetAlert('Not Found', detailData.message || 'Snapshot records not found.', 'error', false);
            return;
        }

        const ids = detailData.rows.map(r => r.archive_id);
        const res = await apiFetch(ROUTES.restore, 'POST', { archive_ids: ids });

        if (res.success || res.total_restored > 0) {
            showSweetAlert('Restored!', `${res.total_restored || ids.length} registration(s) restored.`, 'success', true);
            loadArchivedPage(archiveCurrentPage);
        } else {
            showSweetAlert('Restore Failed', res.message, 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', err.message, 'error', false);
    } finally {
        spinner?.classList.add('d-none');
    }
}

// ── Delete entire snapshot group ─────────────────────────────────────────────
async function deleteSnapshotGroup(metaEncoded) {
    const meta = JSON.parse(decodeURIComponent(metaEncoded));

    const ok = await Swal.fire({
        title: 'Delete Snapshot?',
        html : `<p class="text-danger">Permanently delete snapshot "<strong>${escapeHtml(meta.snapshot_name)}</strong>"?<br>This cannot be undone.</p>`,
        icon : 'error', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Yes, delete permanently'
    });
    if (!ok.isConfirmed) return;

    const spinner = document.getElementById('archiveSpinner');
    spinner?.classList.remove('d-none');

    try {
        const params = new URLSearchParams({
            snapshot_name  : meta.snapshot_name,
            subjectclassid : meta.subjectclassid,
            termid         : meta.termid,
            sessionid      : meta.sessionid,
            staffid        : meta.staffid,
        });
        const detailRes  = await fetch(ROUTES.getSnapshot + '?' + params.toString(), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const detailData = await detailRes.json();

        if (!detailData.success || !detailData.rows?.length) {
            showSweetAlert('Not Found', detailData.message || 'Snapshot records not found.', 'error', false);
            return;
        }

        const ids = detailData.rows.map(r => r.archive_id);
        const res = await apiFetch(ROUTES.permanentDelete, 'DELETE', { archive_ids: ids });

        if (res.success) {
            showSweetAlert('Deleted', `${res.deleted || ids.length} record(s) permanently deleted.`, 'success', false);
            loadArchivedPage(archiveCurrentPage);
        } else {
            showSweetAlert('Delete Failed', res.message, 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', err.message, 'error', false);
    } finally {
        spinner?.classList.add('d-none');
    }
}

// ── Delete selected from detail modal ────────────────────────────────────────
async function deleteDetailSelected() {
    const ids = [...document.querySelectorAll('.detail-chk:checked')].map(cb => parseInt(cb.value));
    if (!ids.length) return;

    const ok = await Swal.fire({
        title: 'Permanently Delete?',
        html : `<p class="text-danger">Delete <strong>${ids.length}</strong> record(s) permanently?</p>`,
        icon : 'error', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Yes, delete permanently'
    });
    if (!ok.isConfirmed) return;

    const spinner = document.getElementById('detailSpinner');
    spinner?.classList.remove('d-none');

    try {
        const res = await apiFetch(ROUTES.permanentDelete, 'DELETE', { archive_ids: ids });
        if (res.success) {
            showSweetAlert('Deleted', `${res.deleted || ids.length} record(s) permanently deleted.`, 'success', false);
            bootstrap.Modal.getInstance(document.getElementById('snapshotDetailModal'))?.hide();
            loadArchivedPage(archiveCurrentPage);
        } else {
            showSweetAlert('Delete Failed', res.message, 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', err.message, 'error', false);
    } finally {
        spinner?.classList.add('d-none');
    }
}

// Stubs for the top-level restore/delete buttons in archive modal (kept for UI symmetry)
async function restoreSelected()       { /* snapshot-level restore now via cards */ }
async function permanentDeleteSelected() { /* snapshot-level delete now via cards */ }
</script>
