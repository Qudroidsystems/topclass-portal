@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Class Teacher Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Class Teacher Management</a></li>
                                <li class="breadcrumb-item active">Class Teachers</li>
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

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('danger'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('danger') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="classTeacherList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search class teachers">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
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
                                    <h5 class="card-title mb-0">Class Teachers <span class="badge bg-dark-subtle text-dark ms-1">{{ $classteachers->total() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        @can('Create class-teacher')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addClassTeacherModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Create Class Teacher</button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_roles_view_table">
                                        <thead>
                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th class="w-10px pe-2">
                                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                        <input class="form-check-input" type="checkbox" id="checkAll" />
                                                    </div>
                                                </th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="sn">SN</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="staffname">Class Teacher</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="schoolclass">Class</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="schoolarm">Arm</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="term">Term</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="session">Session</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="datereg">Date Updated</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600 list form-check-all">
                                            @php $i = ($classteachers->currentPage() - 1) * $classteachers->perPage() @endphp
                                            @forelse ($classteachers as $classteacher)
                                                <tr data-url="{{ route('classteacher.destroy', $classteacher->id) }}">
                                                    <td class="id" data-id="{{ $classteacher->id }}">
                                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" />
                                                        </div>
                                                    </td>
                                                    <td class="sn">{{ ++$i }}</td>
                                                    <td class="staffname" data-staffid="{{ $classteacher->userid }}">
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                                                <a href="#">
                                                                    <div class="symbol-label">
                                                                        <?php $image = $classteacher->avatar ?? 'unnamed.png'; ?>
                                                                        <img src="{{ Storage::url('images/staffavatar/' . $image) }}" alt="{{ $classteacher->staffname }}" class="w-100" />
                                                                    </div>
                                                                </a>
                                                            </div>
                                                            <div class="d-flex flex-column">
                                                                <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $classteacher->staffname }}</a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="schoolclass" data-classid="{{ $classteacher->schoolclassid }}">{{ $classteacher->schoolclass }}</td>
                                                    <td class="schoolarm" data-armid="{{ $classteacher->schoolarmid }}">{{ $classteacher->schoolarm }}</td>
                                                    <td class="term" data-termid="{{ $classteacher->termid }}">{{ $classteacher->term }}</td>
                                                    <td class="session" data-sessionid="{{ $classteacher->sessionid }}">{{ $classteacher->session }}</td>
                                                    <td class="datereg">{{ $classteacher->updated_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('Update class-teacher')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"><i class="ph-pencil"></i></a>
                                                                </li>
                                                            @endcan
                                                            @can('Delete class-teacher')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn"><i class="ph-trash"></i></a>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="noresult" style="display: block;">No results found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $classteachers->count() }}</span> of <span class="fw-semibold">{{ $classteachers->total() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap hstack gap-2 justify-content-center">
                                            <a class="page-item pagination-prev {{ $classteachers->onFirstPage() ? 'disabled' : '' }}" href="javascript:void(0);" data-url="{{ $classteachers->previousPageUrl() ? url($classteachers->previousPageUrl()) : '' }}">
                                                <i class="mdi mdi-chevron-left align-middle"></i>
                                            </a>
                                            <ul class="pagination listjs-pagination mb-0">
                                                @foreach ($classteachers->links()->elements[0] as $page => $url)
                                                    <li class="page-item {{ $classteachers->currentPage() == $page ? 'active' : '' }}">
                                                        <a class="page-link" href="javascript:void(0);" data-url="{{ url($url) }}">{{ $page }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <a class="page-item pagination-next {{ $classteachers->hasMorePages() ? '' : 'disabled' }}" href="javascript:void(0);" data-url="{{ $classteachers->nextPageUrl() ? url($classteachers->nextPageUrl()) : '' }}">
                                                <i class="mdi mdi-chevron-right align-middle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Class Teacher Modal -->
            <div id="addClassTeacherModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="exampleModalLabel" class="modal-title">Add Class Teacher</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-classteacher-form">
                            <div class="modal-body">
                                <input type="hidden" id="add-id-field" name="id">
                                <div class="mb-3">
                                    <label for="staffid" class="form-label">Class Teacher</label>
                                    <select name="staffid" id="staffid" class="form-control" required>
                                        <option value="">Select Teacher</option>
                                        @foreach ($subjectteachers as $teacher)
                                            <option value="{{ $teacher->userid }}">{{ $teacher->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($schoolclass->sortBy('schoolclass') as $class)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="schoolclassid[]" value="{{ $class->id }}" id="class_{{ $class->id }}">
                                                <label class="form-check-label" for="class_{{ $class->id }}">
                                                    {{ $class->schoolclass }} ({{ $class->schoolarm }})
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Term</label>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($schoolterms as $term)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="termid" value="{{ $term->id }}" id="term_{{ $term->id }}" required>
                                                <label class="form-check-label" for="term_{{ $term->id }}">
                                                    {{ $term->term }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Session</label>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($schoolsessions as $session)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sessionid" value="{{ $session->id }}" id="session_{{ $session->id }}" required>
                                                <label class="form-check-label" for="session_{{ $session->id }}">
                                                    {{ $session->session }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Add Class Teacher</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Class Teacher Modal -->
            <div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="editModalLabel" class="modal-title">Edit Class Teacher</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="edit-classteacher-form">
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <div class="mb-3">
                                    <label for="edit-staffid" class="form-label">Class Teacher</label>
                                    <select name="staffid" id="edit-staffid" class="form-control" required>
                                        <option value="">Select Teacher</option>
                                        @foreach ($subjectteachers as $teacher)
                                            <option value="{{ $teacher->userid }}">{{ $teacher->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($schoolclass->sortBy('schoolclass') as $class)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="schoolclassid[]" value="{{ $class->id }}" id="edit_class_{{ $class->id }}">
                                                <label class="form-check-label" for="edit_class_{{ $class->id }}">
                                                    {{ $class->schoolclass }} ({{ $class->schoolarm }})
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Term</label>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($schoolterms as $term)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="termid" value="{{ $term->id }}" id="edit_term_{{ $term->id }}" required>
                                                <label class="form-check-label" for="edit_term_{{ $term->id }}">
                                                    {{ $term->term }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Session</label>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($schoolsessions as $session)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sessionid" value="{{ $session->id }}" id="edit_session_{{ $session->id }}" required>
                                                <label class="form-check-label" for="edit_session_{{ $session->id }}">
                                                    {{ $session->session }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="alert alert-danger d-none" id="edit-alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="update-btn">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteRecordModal" class="modal fade" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <h4>Are you sure?</h4>
                            <p>You won't be able to revert this!</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger" id="delete-record">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page-content -->
    </div>
</div>
@endsection