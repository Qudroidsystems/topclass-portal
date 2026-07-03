@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">School Bill Term Session Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">School Bill Term Session Management</a></li>
                                <li class="breadcrumb-item active">School Bills</li>
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

            <div id="schoolBillTermSessionList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search school bill term sessions">
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
                                    <h5 class="card-title mb-0">School Bill Term Sessions <span class="badge bg-dark-subtle text-dark ms-1">{{ $schoolbillclasstermsessions->total() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        @can('Create school-bill-for-term-session')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addSchoolBillTermSessionModal" id="create-school-bill-termsession-btn"><i class="bi bi-plus-circle align-baseline me-1"></i> Create School Bill Term Session</button>
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
                                                <th class="min-w-125px sort cursor-pointer" data-sort="schoolbill">School Bill</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="schoolclass">School Class</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="schoolterm">Term | Session</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="createdBy">Created By</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="updated_at">Date Updated</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600 list form-check-all">
                                            @php $i = $schoolbillclasstermsessions->firstItem() - 1 @endphp
                                            @forelse ($schoolbillclasstermsessions as $sc)
                                                <tr data-url="{{ route('schoolbilltermsession.destroy', $sc->id) }}">
                                                    <td class="id" data-id="{{ $sc->id }}">
                                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" />
                                                        </div>
                                                    </td>
                                                    <td class="sn">{{ ++$i }}</td>
                                                    <td class="schoolbill">{{ $sc->schoolbill }}</td>
                                                    <td class="schoolclass">{{ $sc->schoolclass }} {{ $sc->schoolarm }}</td>
                                                    <td class="schoolterm">{{ $sc->schoolterm }} | {{ $sc->schoolsession }}</td>
                                                    <td class="createdBy">{{ $sc->createdBy }}</td>
                                                    <td class="updated_at">{{ $sc->updated_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('Update school-bill-for-term-session')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" 
                                                                       data-id="{{ $sc->id }}"
                                                                       data-bill_id="{{ $sc->bill_id ?? '' }}"
                                                                       data-class_id="{{ $sc->class_id ?? '' }}"
                                                                       data-termid_id="{{ $sc->termid_id ?? '' }}"
                                                                       data-session_id="{{ $sc->session_id ?? '' }}"
                                                                       data-schoolbill="{{ $sc->schoolbill }}"
                                                                       data-schoolclass="{{ $sc->schoolclass }} {{ $sc->schoolarm }}"
                                                                       data-schoolterm="{{ $sc->schoolterm }}"
                                                                       data-schoolsession="{{ $sc->schoolsession }}"
                                                                       data-createdBy="{{ $sc->createdBy }}"><i class="ph-pencil"></i></a>
                                                                </li>
                                                            @endcan
                                                            @can('Delete school-bill-for-term-session')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $sc->id }}"><i class="ph-trash"></i></a>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="noresult" style="display: none;">No results found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <div class="d-flex justify-content-end mt-4">
                                        {{ $schoolbillclasstermsessions->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add School Bill Term Session Modal -->
            <div id="addSchoolBillTermSessionModal" class="modal fade" tabindex="-1" aria-labelledby="addSchoolBillTermSessionModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="addSchoolBillTermSessionModalLabel" class="modal-title">Add School Bill Term Session</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-schoolbilltermsession-form">
                            <div class="modal-body">
                                <input type="hidden" id="add-id-field" name="id">
                                <div class="mb-3">
                                    <label for="bill_id" class="form-label">School Bill</label>
                                    <select name="bill_id" id="bill_id" class="form-control" required>
                                        <option value="">Select School Bill</option>
                                        @foreach ($schoolbills as $bill)
                                            <option value="{{ $bill->id }}">{{ $bill->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Classes</label>
                                    <div class="d-flex flex-wrap gap-3 mb-3">
                                        <div class="form-check form-check-outline form-check-primary">
                                            <input class="form-check-input" type="checkbox" id="add-class-select-all">
                                            <label class="form-check-label" for="add-class-select-all">Select All</label>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3" id="add-class-checkboxes">
                                        @foreach ($schoolclasses as $class)
                                            <div class="form-check form-check-outline form-check-primary">
                                                <input class="form-check-input class-checkbox" type="checkbox" value="{{ $class->id }}" name="class_id[]" id="add-class-{{ $class->id }}">
                                                <label class="form-check-label" for="add-class-{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Terms</label>
                                    <div class="d-flex flex-wrap gap-3" id="add-term-checkboxes">
                                        @foreach ($terms as $term)
                                            <div class="form-check form-check-outline form-check-primary">
                                                <input class="form-check-input term-checkbox" type="checkbox" value="{{ $term->id }}" name="termid_id[]" id="add-term-{{ $term->id }}">
                                                <label class="form-check-label" for="add-term-{{ $term->id }}">{{ $term->term }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Session</label>
                                    <div class="d-flex flex-wrap gap-3" id="add-session-radio">
                                        @foreach ($schoolsessions as $session)
                                            <div class="form-check form-check-outline form-check-primary">
                                                <input class="form-check-input session-radio" type="radio" value="{{ $session->id }}" name="session_id" id="add-session-{{ $session->id }}" required>
                                                <label class="form-check-label" for="add-session-{{ $session->id }}">{{ $session->session }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Add School Bill Term Session</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit School Bill Term Session Modal -->
            <div id="editSchoolBillTermSessionModal" class="modal fade" tabindex="-1" aria-labelledby="editSchoolBillTermSessionModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="editSchoolBillTermSessionModalLabel" class="modal-title">Edit School Bill Term Session</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="edit-schoolbilltermsession-form">
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <div class="mb-3">
                                    <label for="edit-bill_id" class="form-label">School Bill</label>
                                    <select name="bill_id" id="edit-bill_id" class="form-control" required>
                                        <option value="">Select School Bill</option>
                                        @foreach ($schoolbills as $bill)
                                            <option value="{{ $bill->id }}">{{ $bill->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Classes</label>
                                    <div class="d-flex flex-wrap gap-3 mb-3">
                                        <div class="form-check form-check-outline form-check-primary">
                                            <input class="form-check-input" type="checkbox" id="edit-class-select-all">
                                            <label class="form-check-label" for="edit-class-select-all">Select All</label>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3" id="edit-class-checkboxes">
                                        @foreach ($schoolclasses as $class)
                                            <div class="form-check form-check-outline form-check-primary">
                                                <input class="form-check-input class-checkbox" type="checkbox" value="{{ $class->id }}" name="class_id[]" id="edit-class-{{ $class->id }}">
                                                <label class="form-check-label" for="edit-class-{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Terms</label>
                                    <div class="d-flex flex-wrap gap-3" id="edit-term-checkboxes">
                                        @foreach ($terms as $term)
                                            <div class="form-check form-check-outline form-check-primary">
                                                <input class="form-check-input term-checkbox" type="checkbox" value="{{ $term->id }}" name="termid_id[]" id="edit-term-{{ $term->id }}">
                                                <label class="form-check-label" for="edit-term-{{ $term->id }}">{{ $term->term }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Session</label>
                                    <div class="d-flex flex-wrap gap-3" id="edit-session-radio">
                                        @foreach ($schoolsessions as $session)
                                            <div class="form-check form-check-outline form-check-primary">
                                                <input class="form-check-input session-radio" type="radio" value="{{ $session->id }}" name="session_id" id="edit-session-{{ $session->id }}" required>
                                                <label class="form-check-label" for="edit-session-{{ $session->id }}">{{ $session->session }}</label>
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
            <div id="deleteRecordModal" class="modal fade" tabindex="-1" aria-labelledby="deleteRecordModalLabel" aria-hidden="true">
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

<script src="{{ asset('js/schoolbilltermsession.init.js') }}"></script>
<script>
    // Select All functionality for Add modal
    document.getElementById('add-class-select-all')?.addEventListener('change', function () {
        document.querySelectorAll('#add-class-checkboxes .class-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    // Select All functionality for Edit modal
    document.getElementById('edit-class-select-all')?.addEventListener('change', function () {
        document.querySelectorAll('#edit-class-checkboxes .class-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });
</script>
@endsection
