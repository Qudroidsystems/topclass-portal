@extends('layouts.master')
@section('content')
@php
    use App\Models\SubjectTeacher;
    \Log::info('SubjectTeacher count: ' . $subjectteacher->count());
@endphp
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Subject Teacher Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Subject Teacher Management</a></li>
                                <li class="breadcrumb-item active">Subject Teachers</li>
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

            <div id="subjectTeacherList" data-initial-rows="{{ $subjectteacher->count() }}">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search subject teachers">
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
                                    <h5 class="card-title mb-0">Subject Teachers <span class="badge bg-dark-subtle text-dark ms-1" id="total-count">{{ $subjectteacher->count() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        @can('Create subject-teacher')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addSubjectTeacherModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Create Subject Teacher</button>
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
                                                <th class="min-w-125px sort cursor-pointer" data-sort="subjectteacher">Subject Teacher</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="subject">Subject</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="subjectcode">Subject Code</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="term">Term</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="session">Session</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="datereg">Date Updated</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600 list form-check-all">
                                            @php $i = 0 @endphp
                                            @forelse ($subjectteacher as $sc)
                                                <?php
                                                $picture = $sc->avatar ?? 'unnamed.jpg';
                                                $imagePath = asset('storage/staff_avatars/' . $picture);
                                                ?>
                                                <tr data-url="{{ route('subjectteacher.destroy', $sc->id) }}">
                                                    <td class="id" data-id="{{ $sc->id }}">
                                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" />
                                                        </div>
                                                    </td>
                                                    <td class="sn">{{ ++$i }}</td>
                                                    <td class="subjectteacher" data-staffid="{{ $sc->userid }}">
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                                                <a href="javascript:void(0);">
                                                                    <div class="symbol-label">
                                                                        <img src="{{ $imagePath }}"
                                                                             alt="{{ $sc->staffname }}"
                                                                             class="rounded-circle avatar-md staff-image"
                                                                             data-bs-toggle="modal"
                                                                             data-bs-target="#imageViewModal"
                                                                             data-image="{{ $imagePath }}"
                                                                             data-staffname="{{ $sc->staffname }}"
                                                                             data-picture="{{ $sc->avatar ?? 'none' }}"
                                                                             data-file-exists="true"
                                                                             data-default-exists="true"
                                                                             onerror="this.src='{{ asset('storage/staff_avatars/unnamed.jpg') }}'; if (!this.dataset.errorLogged) { console.log('Table image failed to load for teacher: {{ $sc->staffname ?? 'unknown' }}, picture: {{ $sc->avatar ?? 'none' }}'); this.dataset.errorLogged = 'true'; }" />
                                                                    </div>
                                                                </a>
                                                            </div>
                                                            <div class="d-flex flex-column">
                                                                <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $sc->staffname }}</a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="subject" data-subjectid="{{ $sc->subjectid }}">{{ $sc->subjectname }}</td>
                                                    <td class="subjectcode">{{ $sc->subjectcode }}</td>
                                                    <td class="term" data-termid="{{ $sc->termid }}">
                                                        @php
                                                        $term = SubjectTeacher::where('staffid', $sc->userid)
                                                            ->where('subjectid', $sc->subjectid)
                                                            ->where('sessionid', $sc->sessionid)
                                                            ->join('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                                                            ->pluck('schoolterm.term')
                                                            ->toArray();
                                                        
                                                        $coloredTerms = [];
                                                        foreach($term as $t) {
                                                            $color = '';
                                                            $termLower = strtolower($t);
                                                            if(preg_match('/(first|1st|one)/', $termLower)) {
                                                                $color = 'color: green;';
                                                            } elseif(preg_match('/(second|2nd|two)/', $termLower)) {
                                                                $color = 'color: blue;';
                                                            } elseif(preg_match('/(third|3rd|three)/', $termLower)) {
                                                                $color = 'color: red;';
                                                            }
                                                            $coloredTerms[] = "<span style='$color'>$t</span>";
                                                        }
                                                        @endphp
                                                        {!! implode(', ', $coloredTerms) !!}
                                                    </td>
                                                    <td class="session" data-sessionid="{{ $sc->sessionid }}">{{ $sc->sessionname }}</td>
                                                    <td class="datereg">{{ $sc->updated_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('Update subject-teacher')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"><i class="ph-pencil"></i></a>
                                                                </li>
                                                            @endcan
                                                            @can('Delete subject-teacher')
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
                                <!-- List.js pagination container -->
                                <div class="row mt-3 align-items-center">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold" id="showing-count">{{ $subjectteacher->count() }}</span> of <span class="fw-semibold" id="total-count-footer">{{ $subjectteacher->count() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <ul class="pagination listjs-pagination mb-0"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Subject Teacher Modal -->
                <div id="addSubjectTeacherModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="exampleModalLabel" class="modal-title">Add Subject Teacher</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form class="tablelist-form" autocomplete="off" id="add-subjectteacher-form">
                                <div class="modal-body">
                                    <input type="hidden" id="add-id-field" name="id">
                                    <div class="mb-3">
                                        <label for="staffid" class="form-label">Subject Teacher</label>
                                        <select name="staffid" id="staffid" class="form-control" required>
                                            <option value="">Select Teacher</option>
                                            @foreach ($staffs as $staff)
                                                <option value="{{ $staff->userid }}">{{ $staff->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <div class="checkbox-group" style="max-height: 150px; overflow-y: auto;">
                                            @foreach ($subjects as $subject)
                                                <div class="form-check me-3">
                                                    <input class="form-check-input modal-checkbox" type="checkbox" name="subjectid[]" id="add-subject-{{ $subject->id }}" value="{{ $subject->id }}">
                                                    <label class="form-check-label" for="add-subject-{{ $subject->id }}">
                                                        {{ $subject->subject }} ({{ $subject->subject_code }})
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Term</label>
                                        <div class="checkbox-group">
                                            @foreach ($terms as $term)
                                                <div class="form-check me-3">
                                                    <input class="form-check-input modal-checkbox" type="checkbox" name="termid[]" id="add-term-{{ $term->id }}" value="{{ $term->id }}">
                                                    <label class="form-check-label" for="add-term-{{ $term->id }}">
                                                        {{ $term->term }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Session</label>
                                        <div class="checkbox-group">
                                            @foreach ($schoolsessions as $session)
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="radio" name="sessionid" id="add-session-{{ $session->id }}" value="{{ $session->id }}" required>
                                                    <label class="form-check-label" for="add-session-{{ $session->id }}">
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
                                    <button type="submit" class="btn btn-primary" id="add-btn">Add Subject Teacher</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Subject Teacher Modal -->
                <div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="editModalLabel" class="modal-title">Edit Subject Teacher</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form class="tablelist-form" autocomplete="off" id="edit-subjectteacher-form">
                                <div class="modal-body">
                                    <input type="hidden" id="edit-id-field" name="id">
                                    <div class="mb-3">
                                        <label for="edit-staffid" class="form-label">Subject Teacher</label>
                                        <select name="staffid" id="edit-staffid" class="form-control" required>
                                            <option value="">Select Teacher</option>
                                            @foreach ($staffs as $staff)
                                                <option value="{{ $staff->userid }}">{{ $staff->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <div class="checkbox-group" style="max-height: 150px; overflow-y: auto;">
                                            @foreach ($subjects as $subject)
                                                <div class="form-check me-3">
                                                    <input class="form-check-input modal-checkbox" type="checkbox" name="subjectid[]" id="edit-subject-{{ $subject->id }}" value="{{ $subject->id }}">
                                                    <label class="form-check-label" for="edit-subject-{{ $subject->id }}">
                                                        {{ $subject->subject }} ({{ $subject->subject_code }})
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Term</label>
                                        <div class="checkbox-group">
                                            @foreach ($terms as $term)
                                                <div class="form-check me-3">
                                                    <input class="form-check-input modal-checkbox" type="checkbox" name="termid[]" id="edit-term-{{ $term->id }}" value="{{ $term->id }}">
                                                    <label class="form-check-label" for="edit-term-{{ $term->id }}">
                                                        {{ $term->term }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Session</label>
                                        <div class="checkbox-group">
                                            @foreach ($schoolsessions as $session)
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="radio" name="sessionid" id="edit-session-{{ $session->id }}" value="{{ $session->id }}" required>
                                                    <label class="form-check-label" for="edit-session-{{ $session->id }}">
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

                <!-- Image Preview Modal -->
                <div id="imageViewModal" class="modal fade" tabindex="-1" aria-labelledby="imageViewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="imageViewModalLabel" class="modal-title">Staff Image Preview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="preview-image" src="" alt="Staff Image" class="img-fluid" style="max-height: 400px;" />
                                <p id="preview-staffname" class="mt-3"></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page-content -->
    </div>
</div>

@section('styles')
<style>
/* Enlarge checkboxes in the table */
#kt_roles_view_table .form-check-input {
    width: 2em;
    height: 2em;
    margin-top: 0.1em;
}
/* Adjust label alignment for table checkboxes */
#kt_roles_view_table .form-check-label {
    font-size: 1.1em;
    line-height: 2em;
    margin-left: 0.75em;
}
/* Style subject checkboxes in modals */
.modal-checkbox {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    width: 2em;
    height: 2em;
    border: 2px solid #ced4da;
    border-radius: 4px;
    background-color: #fff;
    cursor: pointer;
    vertical-align: middle;
    margin-top: 0.1em;
}
.modal-checkbox:checked {
    background-color: #405189;
    border-color: #405189;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3E%3C/svg%3E");
    background-size: 70%;
}
.modal-checkbox:focus {
    outline: none;
    box-shadow: 0 0 0 0.25rem rgba(64, 81, 137, 0.25);
}
/* Adjust label alignment for modal checkboxes and radio buttons */
.modal .form-check-label {
    font-size: 1em;
    line-height: 1.5em;
    margin-left: 0.75em;
    margin-right: 1.5em;
    cursor: pointer;
}
/* Horizontal checkbox/radio group */
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.checkbox-group .form-check {
    margin-bottom: 0;
    display: flex;
    align-items: center;
}
/* Ensure native radio button styling */
.checkbox-group input[type="radio"] {
    -webkit-appearance: radio;
    -moz-appearance: radio;
    appearance: radio;
    margin-top: 0.1em;
}
/* List.js pagination styles */
.listjs-pagination {
    display: flex;
    justify-content: center;
}
.listjs-pagination li {
    margin: 0 2px;
}
.listjs-pagination li a {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #405189;
    text-decoration: none;
}
.listjs-pagination li.active a {
    background-color: #405189;
    color: white;
}
.listjs-pagination li.disabled a {
    color: #ccc;
    cursor: not-allowed;
}
</style>
@endsection
@endsection