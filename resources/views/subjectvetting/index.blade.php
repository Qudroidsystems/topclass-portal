@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Subject Vetting Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Academics</a></li>
                                <li class="breadcrumb-item active">Subject Vetting</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-checkbox-circle-line me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('danger'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-2"></i>
                    {{ session('danger') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <style>
                /* Stats Card Styles */
                .stats-card {
                    transition: all 0.3s ease;
                    border: none;
                    border-radius: 1rem;
                    overflow: hidden;
                    cursor: pointer;
                }
                .stats-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
                }
                .stats-icon {
                    width: 48px;
                    height: 48px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 12px;
                    font-size: 24px;
                }

                /* Status Badge Styles */
                .badge-status {
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-weight: 500;
                    font-size: 11px;
                }
                .badge-pending {
                    background-color: #ffe5e5;
                    color: #dc3545;
                }
                .badge-completed {
                    background-color: #e3f5ec;
                    color: #28a745;
                }
                .badge-rejected {
                    background-color: #fff4e5;
                    color: #ffc107;
                }

                /* Table Row Status Background Colors with border-left */
                .table-row-pending {
                    background-color: #fff5f5 !important;
                    border-left: 3px solid #dc3545;
                }
                .table-row-completed {
                    background-color: #f0fff4 !important;
                    border-left: 3px solid #28a745;
                }
                .table-row-rejected {
                    background-color: #fffbf0 !important;
                    border-left: 3px solid #ffc107;
                }

                /* Hover effects - border color disappears */
                .table-row-hover {
                    transition: all 0.2s ease;
                }
                .table-row-hover:hover {
                    transform: scale(1.01);
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                    border-left-color: transparent !important;
                }

                /* Specific hover states for each status */
                .table-row-pending:hover,
                .table-row-completed:hover,
                .table-row-rejected:hover {
                    border-left-color: transparent !important;
                }

                /* Action Buttons */
                .action-btn {
                    width: 32px;
                    height: 32px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 8px;
                    transition: all 0.2s ease;
                }
                .action-btn:hover {
                    transform: scale(1.1);
                }

                /* Filter Card */
                .filter-card {
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    border: none;
                    border-radius: 1rem;
                }

                /* Animations */
                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .animate-fade-in-up {
                    animation: fadeInUp 0.5s ease-out;
                }

                /* Selected Subject Items */
                .selected-subject-item {
                    transition: all 0.2s ease;
                }
                .selected-subject-item:hover {
                    background-color: #f8f9fa;
                    transform: translateX(5px);
                }

                /* Search Results */
                .subject-search-item {
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .subject-search-item:hover {
                    background-color: #f8f9fa;
                }

                /* Term Colors */
                .term-first {
                    color: #0d6efd !important;
                    font-weight: 600;
                }
                .term-second {
                    color: #198754 !important;
                    font-weight: 600;
                }
                .term-third {
                    color: #f59e0b !important;
                    font-weight: 600;
                }
                .term-bg-first {
                    background-color: #0d6efd10;
                    border-left: 3px solid #0d6efd;
                }
                .term-bg-second {
                    background-color: #19875410;
                    border-left: 3px solid #198754;
                }
                .term-bg-third {
                    background-color: #f59e0b10;
                    border-left: 3px solid #f59e0b;
                }

                /* Search Box */
                .search-box .search-icon {
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #6c757d;
                    pointer-events: none;
                }
                .search-box {
                    position: relative;
                }

                /* Stat Card Active State */
                .stat-card-clickable {
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                .stat-card-clickable.active-stat {
                    border: 2px solid #0d6efd;
                    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
                }
            </style>

            <div id="subjectVettingList">
                <!-- Filter Row -->
                <div class="row mb-4 animate-fade-in-up">
                    <div class="col-12">
                        <div class="card filter-card">
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold text-muted mb-2">
                                            <i class="ri-calendar-line me-1"></i> Select Term
                                        </label>
                                        <select class="form-select form-select-lg" id="term-filter-stats">
                                            <option value="">All Terms</option>
                                            @foreach ($terms as $term)
                                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold text-muted mb-2">
                                            <i class="ri-calendar-event-line me-1"></i> Select Session
                                        </label>
                                        <select class="form-select form-select-lg" id="session-filter-stats">
                                            <option value="">All Sessions</option>
                                            @foreach ($sessions as $session)
                                                <option value="{{ $session->id }}" {{ ($currentSession && $currentSession->id == $session->id) ? 'selected' : '' }}>
                                                    {{ $session->session }} @if($session->status == 'Current') (Current) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-secondary w-100" id="reset-stats-btn">
                                            <i class="ri-refresh-line me-1"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4 animate-fade-in-up" id="statsCardsRow">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card stat-card-clickable" data-status="all">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase fw-semibold fs-12">Total Assignments</p>
                                        <h2 class="mb-0 fw-bold" id="stat-total">0</h2>
                                    </div>
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="ri-file-list-line fs-24"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card stat-card-clickable" data-status="pending">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase fw-semibold fs-12">Pending</p>
                                        <h2 class="mb-0 fw-bold text-danger" id="stat-pending">0</h2>
                                    </div>
                                    <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                                        <i class="ri-timer-line fs-24"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card stat-card-clickable" data-status="completed">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase fw-semibold fs-12">Completed</p>
                                        <h2 class="mb-0 fw-bold text-success" id="stat-completed">0</h2>
                                    </div>
                                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                                        <i class="ri-checkbox-circle-line fs-24"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card stat-card-clickable" data-status="rejected">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-muted mb-1 text-uppercase fw-semibold fs-12">Rejected</p>
                                        <h2 class="mb-0 fw-bold text-warning" id="stat-rejected">0</h2>
                                    </div>
                                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="ri-close-circle-line fs-24"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search & Actions -->
                <div class="row mb-4 animate-fade-in-up">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-6">
                                        <div class="search-box">
                                            <label class="form-label text-muted mb-2 fw-semibold">Search Assignments</label>
                                            <div class="position-relative">
                                                <input type="text" class="form-control search" id="tableSearchInput" placeholder="Search by staff, subject, class, teacher...">
                                                <i class="ri-search-line search-icon"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        @can('Create subject-vettings')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addSubjectVettingModal">
                                                <i class="ri-add-line me-1"></i> Create Assignment
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div class="row animate-fade-in-up">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="kt_subject_vetting_table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="w-10px pe-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkAll" />
                                                    </div>
                                                </th>
                                                <th>#</th>
                                                <th>Vetting Staff</th>
                                                <th>Subject</th>
                                                <th>Class</th>
                                                <th>Arm</th>
                                                <th>Teacher</th>
                                                <th>Term</th>
                                                <th>Session</th>
                                                <th>Status</th>
                                                <th>Updated</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list">
                                            @php $i = 0 @endphp
                                            @forelse ($subjectvettings as $sv)
                                                @php
                                                    $statusClass = match ($sv->status ?? 'pending') {
                                                        'completed' => 'badge-completed',
                                                        'pending' => 'badge-pending',
                                                        'rejected' => 'badge-rejected',
                                                        default => 'badge-pending'
                                                    };
                                                    $rowStatusClass = match ($sv->status ?? 'pending') {
                                                        'completed' => 'table-row-completed',
                                                        'pending' => 'table-row-pending',
                                                        'rejected' => 'table-row-rejected',
                                                        default => ''
                                                    };
                                                    $termColorClass = match ($sv->termid ?? 0) {
                                                        1 => 'term-first',
                                                        2 => 'term-second',
                                                        3 => 'term-third',
                                                        default => ''
                                                    };
                                                @endphp
                                                <tr data-id="{{ $sv->svid }}"
                                                    data-status="{{ $sv->status ?? 'pending' }}"
                                                    data-term="{{ $sv->termid }}"
                                                    data-session="{{ $sv->sessionid }}"
                                                    data-subjectclassid="{{ $sv->subjectclassid }}"
                                                    data-vetting-userid="{{ $sv->vetting_userid }}"
                                                    class="table-row-hover {{ $rowStatusClass }}">
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $sv->svid }}" />
                                                        </div>
                                                    </div>
                                                    <td class="sn fw-bold">{{ ++$i }}</td>
                                                    <td class="vetting_username">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0">
                                                                <div class="avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center">
                                                                    <img src="{{ asset('storage/staff_avatars/' . ($sv->vetting_picture ?? 'unnamed.jpg')) }}"
                                                                        alt="{{ $sv->vetting_username ?? 'Unknown' }}"
                                                                        class="rounded-circle avatar-xs"
                                                                        style="width:38px;height:38px;object-fit:cover;" />
                                                                </div>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <h6 class="mb-0">{{ $sv->vetting_username ?? 'N/A' }}</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <td class="subjectname">{{ $sv->subjectname ?? 'N/A' }}</div>
                                                    <td class="sclass">{{ $sv->sclass ?? 'N/A' }}</div>
                                                    <td class="schoolarm">{{ $sv->schoolarm ?? 'N/A' }}</div>
                                                    <td class="teachername">{{ $sv->teachername ?? 'N/A' }}</div>
                                                    <td class="termname {{ $termColorClass }}">{{ $sv->termname ?? 'N/A' }}</div>
                                                    <td class="sessionname">{{ $sv->sessionname ?? 'N/A' }}</div>
                                                    <td class="status">
                                                        <span class="badge-status {{ $statusClass }}">
                                                            {{ ucfirst($sv->status ?? 'pending') }}
                                                        </span>
                                                    </div>
                                                    <td class="datereg">{{ $sv->updated_at ? $sv->updated_at->format('d M, Y') : 'N/A' }}</div>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            @can('Update subject-vettings')
                                                                <a href="javascript:void(0);" class="action-btn btn btn-light btn-sm edit-item-btn" title="Edit">
                                                                    <i class="ri-pencil-line"></i>
                                                                </a>
                                                            @endcan
                                                            @can('Delete subject-vettings')
                                                                <a href="javascript:void(0);" class="action-btn btn btn-light btn-sm remove-item-btn text-danger" title="Delete">
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </a>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                </tr>
                                            @empty
                                                <tr class="noresult">
                                                    <td colspan="12" class="text-center py-5">
                                                        <i class="ri-inbox-line fs-48 text-muted"></i>
                                                        <h5 class="mt-3">No Subject Vetting Assignments Found</h5>
                                                        <p class="text-muted">No assignments found for the selected filters.</p>
                                                        @can('Create subject-vettings')
                                                            <button type="button" class="btn btn-primary add-btn mt-2" data-bs-toggle="modal" data-bs-target="#addSubjectVettingModal">
                                                                <i class="ri-add-line me-1"></i> Create Your First Assignment
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row mt-4 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span id="showing-records">0</span> of <span id="total-records-footer">{{ $subjectvettings->count() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap">
                                            <ul class="pagination listjs-pagination mb-0"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Modal -->
                <div id="addSubjectVettingModal" class="modal fade" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="ri-add-circle-line me-2"></i>Add Subject Vetting Assignment</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="add-subjectvetting-form" action="{{ route('subjectvetting.store') }}" method="POST">
                                @csrf
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Vetting Staff <span class="text-danger">*</span></label>
                                                <select name="userid" id="subject-userid" class="form-select select2" required>
                                                    <option value="">Select Staff</option>
                                                    @foreach ($staff as $staff_member)
                                                        <option value="{{ $staff_member->id }}">{{ $staff_member->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
                                                <select name="sessionid" id="subject-sessionid" class="form-select" required>
                                                    <option value="">Select Session</option>
                                                    @foreach ($sessions as $session)
                                                        <option value="{{ $session->id }}" {{ ($currentSession && $currentSession->id == $session->id) ? 'selected' : '' }}>
                                                            {{ $session->session }} @if($session->status == 'Current') (Current Session) @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Terms <span class="text-danger">*</span></label>
                                        <div class="p-3 bg-light rounded">
                                            @foreach ($terms as $term)
                                                @php
                                                    $termColor = match($term->id) {
                                                        1 => 'text-primary',
                                                        2 => 'text-success',
                                                        3 => 'text-warning',
                                                        default => ''
                                                    };
                                                @endphp
                                                <div class="form-check form-check-inline me-3">
                                                    <input class="form-check-input" type="checkbox" name="termid[]" value="{{ $term->id }}" id="subject-term-{{ $term->id }}">
                                                    <label class="form-check-label {{ $termColor }}" for="subject-term-{{ $term->id }}">
                                                        <strong>{{ $term->term }}</strong>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- AJAX Subject Search Section -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Subject-Class Assignments <span class="text-danger">*</span></label>

                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                                            <input type="text" id="subjectSearchInput" class="form-control"
                                                   placeholder="Search by subject, class, teacher, term, or session... (min 2 characters)"
                                                   autocomplete="off">
                                            <button type="button" id="subjectClearSearchBtn" class="btn btn-outline-secondary" style="display: none;">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>

                                        <div id="subjectSearchResults" class="list-group mb-3" style="max-height: 300px; overflow-y: auto; display: none;"></div>
                                        <div id="subjectSearchLoading" class="text-center p-3" style="display: none;">
                                            <div class="spinner-border spinner-border-sm text-primary"></div>
                                            <span class="ms-2">Searching...</span>
                                        </div>

                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">Selected Subjects (<span id="subjectSelectedCount">0</span>)</h6>
                                                <button type="button" id="subjectClearAllSelectedBtn" class="btn btn-sm btn-danger" style="display: none;">
                                                    <i class="ri-delete-bin-line me-1"></i>Clear All
                                                </button>
                                            </div>
                                            <div id="subjectSelectedSubjectsContainer" class="border rounded p-2" style="min-height: 100px; max-height: 300px; overflow-y: auto;">
                                                <div class="text-center text-muted py-3">No subjects selected</div>
                                            </div>
                                            <input type="hidden" name="subjectclassid[]" id="subjectSelectedSubjectIds">
                                        </div>
                                    </div>

                                    <div class="alert alert-danger d-none" id="subject-alert-error-msg"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" id="subject-add-btn">Add Assignment(s)</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal with AJAX Search -->
                <div id="editModal" class="modal fade" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-dark">
                                <h5 class="modal-title"><i class="ri-edit-line me-2"></i>Edit Subject Vetting Assignment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="edit-subjectvetting-form" action="" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <input type="hidden" name="id" id="edit-id-field">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Vetting Staff <span class="text-danger">*</span></label>
                                                <select name="userid" id="edit-userid" class="form-select select2" required>
                                                    <option value="">Select Staff</option>
                                                    @foreach ($staff as $staff_member)
                                                        <option value="{{ $staff_member->id }}">{{ $staff_member->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
                                                <select name="sessionid" id="edit-sessionid" class="form-select" required>
                                                    <option value="">Select Session</option>
                                                    @foreach ($sessions as $session)
                                                        <option value="{{ $session->id }}" {{ ($currentSession && $currentSession->id == $session->id) ? 'selected' : '' }}>
                                                            {{ $session->session }} @if($session->status == 'Current') (Current Session) @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Term <span class="text-danger">*</span></label>
                                        <div class="p-3 bg-light rounded">
                                            @foreach ($terms as $term)
                                                @php
                                                    $termColor = match($term->id) {
                                                        1 => 'text-primary',
                                                        2 => 'text-success',
                                                        3 => 'text-warning',
                                                        default => ''
                                                    };
                                                @endphp
                                                <div class="form-check form-check-inline me-3">
                                                    <input class="form-check-input edit-term-checkbox" type="radio" name="termid" value="{{ $term->id }}" id="edit-term-{{ $term->id }}">
                                                    <label class="form-check-label {{ $termColor }}" for="edit-term-{{ $term->id }}">
                                                        <strong>{{ $term->term }}</strong>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- AJAX Subject Search Section for Edit (Single selection) -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Subject-Class Assignment <span class="text-danger">*</span></label>

                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                                            <input type="text" id="editSubjectSearchInput" class="form-control"
                                                   placeholder="Search by subject, class, teacher, term, or session... (min 2 characters)"
                                                   autocomplete="off">
                                            <button type="button" id="editClearSearchBtn" class="btn btn-outline-secondary" style="display: none;">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>

                                        <div id="editSearchResults" class="list-group mb-3" style="max-height: 300px; overflow-y: auto; display: none;"></div>
                                        <div id="editSearchLoading" class="text-center p-3" style="display: none;">
                                            <div class="spinner-border spinner-border-sm text-primary"></div>
                                            <span class="ms-2">Searching...</span>
                                        </div>

                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">Selected Subject</h6>
                                                <button type="button" id="editClearSelectedBtn" class="btn btn-sm btn-danger" style="display: none;">
                                                    <i class="ri-delete-bin-line me-1"></i>Clear Selection
                                                </button>
                                            </div>
                                            <div id="editSelectedSubjectContainer" class="border rounded p-2" style="min-height: 80px;">
                                                <div class="text-center text-muted py-3">No subject selected</div>
                                            </div>
                                            <input type="hidden" name="subjectclassid" id="editSelectedSubjectId">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="edit-status" class="form-select" required>
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>

                                    <div class="alert alert-danger d-none" id="edit-alert-error-msg"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" id="edit-btn">Update Assignment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div id="deleteRecordModal" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-4">
                                <i class="ri-delete-bin-line text-danger fs-48 mb-3 d-block"></i>
                                <h4 class="mb-2">Are you sure?</h4>
                                <p class="text-muted mb-4">You won't be able to revert this!</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger" id="delete-record">Yes, Delete It!</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>

<script>
// Escape HTML helper
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Get term color class based on term ID
function getTermColorClass(termId) {
    if (termId == 1) return 'term-first';
    if (termId == 2) return 'term-second';
    if (termId == 3) return 'term-third';
    return '';
}

function getTermBgClass(termId) {
    if (termId == 1) return 'term-bg-first';
    if (termId == 2) return 'term-bg-second';
    if (termId == 3) return 'term-bg-third';
    return '';
}

// Main Subject Vetting Management
let subjectSelectedSubjects = new Map();
let subjectVettingList = null;
let deleteId = null;

// Edit modal variables
let editSelectedSubject = null;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeSubjectListJS();
    initializeSubjectFilters();
    initializeSubjectTableSearch();
    initializeSubjectAddForm();
    initializeSubjectEditForm();
    initializeSubjectDelete();
    initializeSubjectBulkDelete();
    initializeSubjectSelect2();
    updateSubjectStatsFromList();
});

function initializeSubjectListJS() {
    try {
        subjectVettingList = new List('subjectVettingList', {
            valueNames: ['sn', 'vetting_username', 'subjectname', 'sclass', 'schoolarm', 'teachername', 'termname', 'sessionname', 'status', 'datereg'],
            page: 10,
            pagination: { paginationClass: 'listjs-pagination' }
        });
        subjectVettingList.on('updated', updateSubjectStatsFromList);
        console.log('List.js initialized successfully');
    } catch(e) {
        console.error('ListJS init error:', e);
    }
}

function initializeSubjectTableSearch() {
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput && subjectVettingList) {
        searchInput.addEventListener('keyup', function() {
            subjectVettingList.search(this.value);
        });
    }
}

function updateSubjectStatsFromList() {
    if (!subjectVettingList) return;
    const items = subjectVettingList.matchingItems;
    let total = items.length, pending = 0, completed = 0, rejected = 0;
    items.forEach(item => {
        const status = (item.elm.getAttribute('data-status') || 'pending').toLowerCase();
        if (status === 'pending') pending++;
        else if (status === 'completed') completed++;
        else if (status === 'rejected') rejected++;
    });
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-pending').textContent = pending;
    document.getElementById('stat-completed').textContent = completed;
    document.getElementById('stat-rejected').textContent = rejected;
    const showingEl = document.getElementById('showing-records');
    if (showingEl) showingEl.textContent = Math.min(total, 10);
    const totalEl = document.getElementById('total-records-footer');
    if (totalEl) totalEl.textContent = total;
}

function initializeSubjectFilters() {
    const termFilter = document.getElementById('term-filter-stats');
    const sessionFilter = document.getElementById('session-filter-stats');
    const resetBtn = document.getElementById('reset-stats-btn');

    if (termFilter) {
        termFilter.addEventListener('change', () => applySubjectFilters());
    }
    if (sessionFilter) {
        sessionFilter.addEventListener('change', () => applySubjectFilters());
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (termFilter) termFilter.value = '';
            if (sessionFilter) sessionFilter.value = '';
            applySubjectFilters();
        });
    }

    // Stat card filters
    document.querySelectorAll('#statsCardsRow .stat-card-clickable').forEach(card => {
        card.addEventListener('click', () => {
            const status = card.getAttribute('data-status');
            if (!subjectVettingList) return;

            // Remove active class from all stat cards
            document.querySelectorAll('#statsCardsRow .stat-card-clickable').forEach(c => c.classList.remove('active-stat'));
            card.classList.add('active-stat');

            if (status === 'all') {
                subjectVettingList.filter();
            } else {
                subjectVettingList.filter(item => {
                    const itemStatus = (item.elm.getAttribute('data-status') || 'pending').toLowerCase();
                    return itemStatus === status;
                });
            }
        });
    });
}

function applySubjectFilters() {
    if (!subjectVettingList) return;
    const termFilter = document.getElementById('term-filter-stats').value;
    const sessionFilter = document.getElementById('session-filter-stats').value;
    if (!termFilter && !sessionFilter) {
        subjectVettingList.filter();
    } else {
        subjectVettingList.filter(item => {
            const rowTerm = item.elm.getAttribute('data-term') || '';
            const rowSession = item.elm.getAttribute('data-session') || '';
            const termOk = !termFilter || rowTerm === termFilter;
            const sessionOk = !sessionFilter || rowSession === sessionFilter;
            return termOk && sessionOk;
        });
    }
}

// ========== ADD FORM AJAX SUBJECT SEARCH ==========
function initializeSubjectAddForm() {
    const form = document.getElementById('add-subjectvetting-form');
    if (!form) return;

    const searchInput = document.getElementById('subjectSearchInput');
    const resultsDiv = document.getElementById('subjectSearchResults');
    const loadingDiv = document.getElementById('subjectSearchLoading');
    const clearSearchBtn = document.getElementById('subjectClearSearchBtn');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchTimeout);
            if (query.length < 2) {
                resultsDiv.style.display = 'none';
                clearSearchBtn.style.display = 'none';
                return;
            }
            clearSearchBtn.style.display = 'block';
            loadingDiv.style.display = 'block';
            resultsDiv.style.display = 'none';

            searchTimeout = setTimeout(() => {
                const excludeIds = Array.from(subjectSelectedSubjects.keys()).join(',');
                fetch(`/api/subject-classes/search?q=${encodeURIComponent(query)}&exclude_ids=${excludeIds}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(response => {
                    loadingDiv.style.display = 'none';
                    if (!response.success) {
                        resultsDiv.innerHTML = `<div class="list-group-item text-danger">${response.message || 'Search failed'}</div>`;
                        resultsDiv.style.display = 'block';
                        return;
                    }
                    const data = response.data;
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
                        resultsDiv.style.display = 'block';
                        return;
                    }
                    resultsDiv.innerHTML = data.map(item => {
                        const termColorClass = getTermColorClass(item.termid);
                        const termBgClass = getTermBgClass(item.termid);
                        return `
                            <div class="list-group-item subject-search-item ${termBgClass}" data-id="${item.id}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">
                                            ${escapeHtml(item.subjectname)}
                                            ${item.subjectcode ? `<span class="text-muted">(${escapeHtml(item.subjectcode)})</span>` : ''}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <i class="ri-group-line me-1"></i> Class: ${escapeHtml(item.sclass)} ${item.schoolarm ? `(${escapeHtml(item.schoolarm)})` : ''}<br>
                                            <i class="ri-user-line me-1"></i> Teacher: ${escapeHtml(item.teachername)}<br>
                                            <i class="ri-calendar-line me-1"></i> Session: ${escapeHtml(item.sessionname)}<br>
                                            <i class="ri-calendar-event-line me-1"></i> Term: <span class="${termColorClass} fw-bold">${escapeHtml(item.termname)}</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary add-subject-btn">
                                        <i class="ri-add-line me-1"></i>Add
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('');
                    resultsDiv.style.display = 'block';
                })
                .catch(error => {
                    console.error('Search error:', error);
                    loadingDiv.style.display = 'none';
                    resultsDiv.innerHTML = '<div class="list-group-item text-danger">Network error</div>';
                    resultsDiv.style.display = 'block';
                });
            }, 500);
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            resultsDiv.style.display = 'none';
            clearSearchBtn.style.display = 'none';
        });
    }

    // Add subject handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-subject-btn')) {
            const btn = e.target.closest('.add-subject-btn');
            const item = btn.closest('.subject-search-item');
            if (item) {
                const id = item.dataset.id;
                const name = item.querySelector('.fw-bold')?.innerText || '';
                if (!subjectSelectedSubjects.has(id)) {
                    const detailsHtml = item.querySelector('.small')?.innerHTML || '';
                    subjectSelectedSubjects.set(id, { id, name, detailsHtml });
                    updateSubjectSelectedDisplay();
                    item.remove();
                    showTempMessage('Subject added', 'success');
                    if (resultsDiv.children.length === 0) {
                        resultsDiv.style.display = 'none';
                        searchInput.value = '';
                        clearSearchBtn.style.display = 'none';
                    }
                } else {
                    showTempMessage('Already selected', 'warning');
                }
            }
        }
    });

    // Clear all button
    const clearAllBtn = document.getElementById('subjectClearAllSelectedBtn');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            if (confirm('Clear all selected subjects?')) {
                subjectSelectedSubjects.clear();
                updateSubjectSelectedDisplay();
            }
        });
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitSubjectAddForm();
    });

    // Reset on modal close
    document.getElementById('addSubjectVettingModal')?.addEventListener('hidden.bs.modal', () => {
        form.reset();
        subjectSelectedSubjects.clear();
        updateSubjectSelectedDisplay();
        if (searchInput) searchInput.value = '';
        if (resultsDiv) resultsDiv.style.display = 'none';
        const errorEl = document.getElementById('subject-alert-error-msg');
        if (errorEl) errorEl.classList.add('d-none');
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#subject-userid').val('').trigger('change');
        }
    });
}

function updateSubjectSelectedDisplay() {
    const container = document.getElementById('subjectSelectedSubjectsContainer');
    const countSpan = document.getElementById('subjectSelectedCount');
    const hiddenInput = document.getElementById('subjectSelectedSubjectIds');
    const clearAllBtn = document.getElementById('subjectClearAllSelectedBtn');
    const count = subjectSelectedSubjects.size;

    if (countSpan) countSpan.textContent = count;
    if (hiddenInput) hiddenInput.value = Array.from(subjectSelectedSubjects.keys()).join(',');
    if (clearAllBtn) clearAllBtn.style.display = count > 0 ? 'block' : 'none';

    if (count === 0) {
        if (container) container.innerHTML = '<div class="text-center text-muted py-3">No subjects selected</div>';
        return;
    }

    let html = '';
    for (let [id, subject] of subjectSelectedSubjects) {
        html += `
            <div class="selected-subject-item d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded">
                <div>
                    <strong>${escapeHtml(subject.name)}</strong>
                    <div class="small">${subject.detailsHtml || ''}</div>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger remove-subject-btn" data-id="${id}">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        `;
    }
    if (container) container.innerHTML = html;

    document.querySelectorAll('.remove-subject-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            subjectSelectedSubjects.delete(id);
            updateSubjectSelectedDisplay();
            showTempMessage('Subject removed', 'info');
        });
    });
}

function submitSubjectAddForm() {
    const form = document.getElementById('add-subjectvetting-form');
    const errorEl = document.getElementById('subject-alert-error-msg');
    const submitBtn = document.getElementById('subject-add-btn');

    const formData = new FormData(form);
    const selectedIds = Array.from(subjectSelectedSubjects.keys());

    if (!formData.get('userid')) {
        showError(errorEl, 'Please select a vetting staff member.');
        return;
    }
    if (!formData.get('sessionid')) {
        showError(errorEl, 'Please select a session.');
        return;
    }
    const terms = formData.getAll('termid[]');
    if (terms.length === 0) {
        showError(errorEl, 'Please select at least one term.');
        return;
    }
    if (selectedIds.length === 0) {
        showError(errorEl, 'Please select at least one subject-class assignment.');
        return;
    }

    formData.delete('subjectclassid[]');
    selectedIds.forEach(id => {
        formData.append('subjectclassid[]', id);
    });

    if (errorEl) errorEl.classList.add('d-none');
    if (submitBtn) submitBtn.disabled = true;

    const actionUrl = form.getAttribute('action');

    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('addSubjectVettingModal'));
            if (modal) modal.hide();
            showToast(data.message || 'Assignment(s) added successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            let msg = data.message || 'An error occurred';
            if (data.errors) {
                msg = Object.values(data.errors).flat().join('<br>');
            }
            showError(errorEl, msg);
        }
    })
    .catch(error => {
        console.error('Submit error:', error);
        showError(errorEl, 'Network error. Please try again.');
    })
    .finally(() => {
        if (submitBtn) submitBtn.disabled = false;
    });
}

// ========== EDIT FORM WITH AJAX SUBJECT SEARCH ==========
function initializeSubjectEditForm() {
    document.querySelector('#kt_subject_vetting_table tbody')?.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-item-btn');
        if (!btn) return;
        const row = btn.closest('tr');
        if (row) populateEditModal(row);
    });

    const form = document.getElementById('edit-subjectvetting-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitEditForm();
        });
    }

    // Initialize edit modal search
    initializeEditSubjectSearch();
}

function initializeEditSubjectSearch() {
    const searchInput = document.getElementById('editSubjectSearchInput');
    const resultsDiv = document.getElementById('editSearchResults');
    const loadingDiv = document.getElementById('editSearchLoading');
    const clearSearchBtn = document.getElementById('editClearSearchBtn');
    let searchTimeout;

    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchTimeout);
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            clearSearchBtn.style.display = 'none';
            return;
        }
        clearSearchBtn.style.display = 'block';
        loadingDiv.style.display = 'block';
        resultsDiv.style.display = 'none';

        searchTimeout = setTimeout(() => {
            const excludeId = editSelectedSubject ? editSelectedSubject.id : '';
            fetch(`/api/subject-classes/search?q=${encodeURIComponent(query)}&exclude_ids=${excludeId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(response => {
                loadingDiv.style.display = 'none';
                if (!response.success) {
                    resultsDiv.innerHTML = `<div class="list-group-item text-danger">${response.message || 'Search failed'}</div>`;
                    resultsDiv.style.display = 'block';
                    return;
                }
                const data = response.data;
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
                    resultsDiv.style.display = 'block';
                    return;
                }
                resultsDiv.innerHTML = data.map(item => {
                    const termColorClass = getTermColorClass(item.termid);
                    const termBgClass = getTermBgClass(item.termid);
                    return `
                        <div class="list-group-item edit-subject-search-item ${termBgClass}" data-id="${item.id}"
                             data-name="${escapeHtml(item.subjectname)}"
                             data-details='${JSON.stringify(item)}'>
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">
                                        ${escapeHtml(item.subjectname)}
                                        ${item.subjectcode ? `<span class="text-muted">(${escapeHtml(item.subjectcode)})</span>` : ''}
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <i class="ri-group-line me-1"></i> Class: ${escapeHtml(item.sclass)} ${item.schoolarm ? `(${escapeHtml(item.schoolarm)})` : ''}<br>
                                        <i class="ri-user-line me-1"></i> Teacher: ${escapeHtml(item.teachername)}<br>
                                        <i class="ri-calendar-line me-1"></i> Session: ${escapeHtml(item.sessionname)}<br>
                                        <i class="ri-calendar-event-line me-1"></i> Term: <span class="${termColorClass} fw-bold">${escapeHtml(item.termname)}</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary edit-select-subject-btn">
                                    <i class="ri-check-line me-1"></i>Select
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
                resultsDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Search error:', error);
                loadingDiv.style.display = 'none';
                resultsDiv.innerHTML = '<div class="list-group-item text-danger">Network error</div>';
                resultsDiv.style.display = 'block';
            });
        }, 500);
    });

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            resultsDiv.style.display = 'none';
            clearSearchBtn.style.display = 'none';
        });
    }

    // Handle subject selection in edit modal
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-select-subject-btn')) {
            const btn = e.target.closest('.edit-select-subject-btn');
            const item = btn.closest('.edit-subject-search-item');
            if (item) {
                const id = item.dataset.id;
                const name = item.dataset.name;
                const details = JSON.parse(item.dataset.details);
                editSelectedSubject = { id, name, details };
                updateEditSelectedDisplay();
                resultsDiv.style.display = 'none';
                searchInput.value = '';
                clearSearchBtn.style.display = 'none';
                showTempMessage('Subject selected', 'success');
            }
        }
    });

    // Clear selection button
    const clearSelectedBtn = document.getElementById('editClearSelectedBtn');
    if (clearSelectedBtn) {
        clearSelectedBtn.addEventListener('click', () => {
            editSelectedSubject = null;
            updateEditSelectedDisplay();
            showTempMessage('Selection cleared', 'info');
        });
    }
}

function updateEditSelectedDisplay() {
    const container = document.getElementById('editSelectedSubjectContainer');
    const hiddenInput = document.getElementById('editSelectedSubjectId');
    const clearBtn = document.getElementById('editClearSelectedBtn');

    if (!editSelectedSubject) {
        if (container) container.innerHTML = '<div class="text-center text-muted py-3">No subject selected</div>';
        if (hiddenInput) hiddenInput.value = '';
        if (clearBtn) clearBtn.style.display = 'none';
        return;
    }

    const termColorClass = getTermColorClass(editSelectedSubject.details.termid);
    const termBgClass = getTermBgClass(editSelectedSubject.details.termid);

    if (hiddenInput) hiddenInput.value = editSelectedSubject.id;
    if (clearBtn) clearBtn.style.display = 'block';

    const html = `
        <div class="selected-subject-item p-2 bg-light rounded ${termBgClass}">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${escapeHtml(editSelectedSubject.name)}</strong>
                    <div class="small text-muted mt-1">
                        <i class="ri-group-line me-1"></i> Class: ${escapeHtml(editSelectedSubject.details.sclass)} ${editSelectedSubject.details.schoolarm ? `(${escapeHtml(editSelectedSubject.details.schoolarm)})` : ''}<br>
                        <i class="ri-user-line me-1"></i> Teacher: ${escapeHtml(editSelectedSubject.details.teachername)}<br>
                        <i class="ri-calendar-line me-1"></i> Session: ${escapeHtml(editSelectedSubject.details.sessionname)}<br>
                        <i class="ri-calendar-event-line me-1"></i> Term: <span class="${termColorClass} fw-bold">${escapeHtml(editSelectedSubject.details.termname)}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    if (container) container.innerHTML = html;
}

function populateEditModal(row) {
    const id = row.getAttribute('data-id');
    const vettingUserId = row.getAttribute('data-vetting-userid') || '';
    const termid = row.getAttribute('data-term') || '';
    const sessionid = row.getAttribute('data-session') || '';
    const subjectclassid = row.getAttribute('data-subjectclassid') || '';
    const status = row.getAttribute('data-status') || 'pending';
    const subjectname = row.querySelector('.subjectname')?.innerText || '';
    const sclass = row.querySelector('.sclass')?.innerText || '';
    const schoolarm = row.querySelector('.schoolarm')?.innerText || '';
    const teachername = row.querySelector('.teachername')?.innerText || '';
    const termname = row.querySelector('.termname')?.innerText || '';
    const sessionname = row.querySelector('.sessionname')?.innerText || '';

    document.getElementById('edit-id-field').value = id;
    document.getElementById('edit-userid').value = vettingUserId;
    document.getElementById('edit-sessionid').value = sessionid;
    document.getElementById('edit-status').value = status;

    // Set term radio
    const termRadio = document.querySelector(`input[name="termid"][value="${termid}"]`);
    if (termRadio) termRadio.checked = true;

    // Set selected subject for edit
    editSelectedSubject = {
        id: subjectclassid,
        name: subjectname,
        details: {
            subjectname: subjectname,
            sclass: sclass,
            schoolarm: schoolarm,
            teachername: teachername,
            termname: termname,
            termid: termid,
            sessionname: sessionname
        }
    };
    updateEditSelectedDisplay();

    // Set form action
    const form = document.getElementById('edit-subjectvetting-form');
    form.action = `/subjectvetting/${id}`;

    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#edit-userid').trigger('change');
    }

    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function submitEditForm() {
    const id = document.getElementById('edit-id-field').value;
    const errorEl = document.getElementById('edit-alert-error-msg');
    const submitBtn = document.getElementById('edit-btn');
    const form = document.getElementById('edit-subjectvetting-form');
    const formData = new FormData(form);

    if (!formData.get('userid')) {
        showError(errorEl, 'Please select a vetting staff member.');
        return;
    }
    if (!formData.get('sessionid')) {
        showError(errorEl, 'Please select a session.');
        return;
    }
    if (!formData.get('termid')) {
        showError(errorEl, 'Please select a term.');
        return;
    }
    if (!editSelectedSubject) {
        showError(errorEl, 'Please select a subject-class assignment.');
        return;
    }

    // Ensure subjectclassid is set
    formData.set('subjectclassid', editSelectedSubject.id);

    if (errorEl) errorEl.classList.add('d-none');
    if (submitBtn) submitBtn.disabled = true;

    fetch(`/subjectvetting/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json',
            'X-HTTP-Method-Override': 'PUT'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            if (modal) modal.hide();
            showToast('Assignment updated successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            let msg = data.message || 'Update failed';
            if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
            if (errorEl) {
                errorEl.innerHTML = msg;
                errorEl.classList.remove('d-none');
            }
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        if (errorEl) {
            errorEl.innerHTML = 'Network error. Please try again.';
            errorEl.classList.remove('d-none');
        }
    })
    .finally(() => {
        if (submitBtn) submitBtn.disabled = false;
    });
}

// Reset edit modal on close
document.getElementById('editModal')?.addEventListener('hidden.bs.modal', () => {
    editSelectedSubject = null;
    updateEditSelectedDisplay();
    const searchInput = document.getElementById('editSubjectSearchInput');
    const resultsDiv = document.getElementById('editSearchResults');
    const errorEl = document.getElementById('edit-alert-error-msg');
    if (searchInput) searchInput.value = '';
    if (resultsDiv) resultsDiv.style.display = 'none';
    if (errorEl) errorEl.classList.add('d-none');
});

// ========== DELETE ==========
function initializeSubjectDelete() {
    document.querySelector('#kt_subject_vetting_table tbody')?.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-item-btn');
        if (!btn) return;
        const row = btn.closest('tr');
        if (row) {
            deleteId = row.getAttribute('data-id');
            const modal = new bootstrap.Modal(document.getElementById('deleteRecordModal'));
            modal.show();
        }
    });

    document.getElementById('delete-record')?.addEventListener('click', function() {
        if (!deleteId) return;
        this.disabled = true;

        fetch(`/subjectvetting/${deleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal'));
            if (modal) modal.hide();
            if (data.success) {
                showToast('Assignment deleted successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Delete failed', 'danger');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showToast('Network error', 'danger');
        })
        .finally(() => { this.disabled = false; deleteId = null; });
    });
}

// ========== BULK DELETE ==========
function initializeSubjectBulkDelete() {
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            document.querySelectorAll('input[name="chk_child"]').forEach(cb => cb.checked = this.checked);
            toggleRemoveBtn();
        });
    }
    document.querySelector('#kt_subject_vetting_table tbody')?.addEventListener('change', function(e) {
        if (e.target.name === 'chk_child') toggleRemoveBtn();
    });
}

function toggleRemoveBtn() {
    const anyChecked = document.querySelectorAll('input[name="chk_child"]:checked').length > 0;
    const removeBtn = document.getElementById('remove-actions');
    if (removeBtn) removeBtn.classList.toggle('d-none', !anyChecked);
}

window.deleteMultiple = function() {
    const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked')).map(cb => cb.value);
    if (!ids.length) return;
    if (!confirm(`Delete ${ids.length} record(s)?`)) return;

    fetch('/subjectvetting/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ ids })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(`${ids.length} record(s) deleted.`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Delete failed', 'danger');
        }
    })
    .catch(error => {
        console.error('Bulk delete error:', error);
        showToast('Network error', 'danger');
    });
};

// ========== HELPER FUNCTIONS ==========
function showError(el, message) {
    if (!el) return;
    el.innerHTML = message;
    el.classList.remove('d-none');
    setTimeout(() => el.classList.add('d-none'), 5000);
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = 9999;
    toast.style.minWidth = '280px';
    toast.style.maxWidth = '400px';
    toast.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function showTempMessage(message, type = 'info') {
    const div = document.createElement('div');
    div.className = `alert alert-${type} alert-dismissible fade show position-fixed bottom-0 end-0 m-3`;
    div.style.zIndex = 9999;
    div.style.minWidth = '250px';
    div.innerHTML = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 2000);
}

function initializeSubjectSelect2() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#subject-userid').select2({
            dropdownParent: $('#addSubjectVettingModal'),
            placeholder: 'Select Staff',
            width: '100%'
        });
        $('#edit-userid').select2({
            dropdownParent: $('#editModal'),
            placeholder: 'Select Staff',
            width: '100%'
        });
    }
}
</script>
@endsection
