@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">My Subjects</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0)">Subjects</a></li>
                                <li class="breadcrumb-item active">My Subjects</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Total Subjects</h5>
                            <p class="text-muted mb-3">{{ $unique_subject_count }}</p>
                            @can('Create my-subject')
                                <div class="text-end">
                                    <a href="javascript:void(0);" class="btn btn-subtle-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">Add New Subject</a>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
               
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">Current Subjects</h5>
                                </div>
                               
                            </div>
                        </div>
                        <div class="card-body">
                          
                                <!-- Find this section in your original code and replace it -->
                                <div class="row" id="subjectCardContainer">
                                    @foreach ($mysubjects as $mysubject)
                                        <div class="col-md-4 mb-4">
                                            <div class="card bg-dark text-white">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <input type="hidden" class="subject-id" value="{{ $mysubject->id }}">
                                                        </div>
                                                    
                                                    </div>
                                                    <div class="text-center mt-4 mb-2">
                                                        <h6 class="fs-md mt-4 mb-1 text-white">{{ $mysubject->subject }}</h6>
                                                        <h2 class="text-light">Code: {{ $mysubject->subjectcode }}</h2>
                                                    </div>
                                                    <ul class="list-unstyled text-light vstack gap-2 mb-0">
                                                        <li><i class="bi bi-book align-baseline me-1"></i> Class: {{ $mysubject->schoolclass }} ({{ $mysubject->arm }})</li>
                                                        <li><i class="bi bi-calendar align-baseline me-1"></i> Term: {{ $mysubject->term }}</li>
                                                        <li><i class="bi bi-clock align-baseline me-1"></i> Session: {{ $mysubject->session }}</li>
                                                    </ul>
                                                </div>
                                                <div class="card-body border-top border-secondary d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-star-fill align-baseline me-1"></i>Teacher: {{ $mysubject->staffname }}</span>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <a href="javascript:void(0);" class="text-warning text-decoration-none">View Details <i class="bi bi-arrow-right align-baseline ms-1"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-hover table-nowrap align-middle mb-0" id="subjectListTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Subject</th>
                                            <th scope="col">Code</th>
                                            <th scope="col">Class</th>
                                            <th scope="col">Arm</th>
                                            <th scope="col">Term</th>
                                            <th scope="col">Session</th>
                                         
                                      
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mysubjects as $mysubject)
                                            <tr>
                                                <td class="id" data-id="{{ $mysubject->id }}">{{ $mysubject->id }}</td>
                                                <td class="subject">{{ $mysubject->subject }}</td>
                                                <td class="subjectcode">{{ $mysubject->subjectcode }}</td>
                                                <td class="schoolclass">{{ $mysubject->schoolclass }}</td>
                                                <td class="arm">{{ $mysubject->arm }}</td>
                                                <td class="term">{{ $mysubject->term }}</td>
                                                <td class="session">{{ $mysubject->session }}</td>
                                              
                                                
                                            </tr>
                                        @endforeach
                                        <tr class="noresult" style="display: none;">
                                            <td colspan="9" class="text-center">No results found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        @can('Delete my-subject')
                                            <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">Delete Selected</button>
                                        @endcan
                                    </div>
                                    <div>
                                        {{ $mysubjects->links() }}
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
                        <div class="card-header">
                            <h5 class="card-title mb-0">Subject History</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="historyTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Subject</th>
                                            <th scope="col">Code</th>
                                            <th scope="col">Class</th>
                                            <th scope="col">Arm</th>
                                            <th scope="col">Term</th>
                                            <th scope="col">Session</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mysubjectshistory as $subject)
                                            <tr>
                                                <td>{{ $subject->id }}</td>
                                                <td>{{ $subject->subject }}</td>
                                                <td>{{ $subject->subjectcode }}</td>
                                                <td>{{ $subject->schoolclass }}</td>
                                                <td>{{ $subject->arm }}</td>
                                                <td>{{ $subject->term }}</td>
                                                <td>{{ $subject->session }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal -->
            <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Subject</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="add-subject-form" autocomplete="off">
                                @csrf
                                <input type="hidden" id="add-id-field">
                                <input type="hidden" id="staffid" name="staffid" value="{{ Auth::id() }}">
                                <div id="alert-error-msg" class="alert alert-danger d-none"></div>
                                <div class="mb-3">
                                    <label for="subjectid" class="form-label">Subject</label>
                                    <select id="subjectid" name="subjectid" class="form-select" data-choices required>
                                        <option value="">Select subject</option>
                                        @foreach ($subjects as $subject)
                                            <option value="{{ $subject->id }}">{{ $subject->subject }} ({{ $subject->subject_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="schoolclassid" class="form-label">Class</label>
                                    <select id="schoolclassid" name="schoolclassid" class="form-select" data-choices required>
                                        <option value="">Select class</option>
                                        @foreach ($schoolclasses as $class)
                                            <option value="{{ $class->id }}">{{ $class->schoolclass }} ({{ $class->arm }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="termid" class="form-label">Term</label>
                                    <select id="termid" name="termid" class="form-select" data-choices required>
                                        <option value="">Select term</option>
                                        @foreach ($terms as $term)
                                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="sessionid" class="form-label">Session</label>
                                    <select id="sessionid" name="sessionid" class="form-select" data-choices required>
                                        <option value="">Select session</option>
                                        @foreach ($schoolsessions as $session)
                                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="hstack gap-2 justify-content-end">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" id="add-btn" class="btn btn-primary">Add Subject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit Subject</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-subject-form" autocomplete="off">
                                @csrf
                                <input type="hidden" id="edit-id-field">
                                <input type="hidden" id="edit-staffid" name="staffid" value="{{ Auth::id() }}">
                                <div id="alert-error-msg" class="alert alert-danger d-none"></div>
                                <div class="mb-3">
                                    <label for="edit-subjectid" class="form-label">Subject</label>
                                    <select id="edit-subjectid" name="subjectid" class="form-select" data-choices required>
                                        <option value="">Select subject</option>
                                        @foreach ($subjects as $subject)
                                            <option value="{{ $subject->id }}">{{ $subject->subject }} ({{ $subject->subject_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-schoolclassid" class="form-label">Class</label>
                                    <select id="edit-schoolclassid" name="schoolclassid" class="form-select" data-choices required>
                                        <option value="">Select class</option>
                                        @foreach ($schoolclasses as $class)
                                            <option value="{{ $class->id }}">{{ $class->schoolclass }} ({{ $class->arm }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-termid" class="form-label">Term</label>
                                    <select id="edit-termid" name="termid" class="form-select" data-choices required>
                                        <option value="">Select term</option>
                                        @foreach ($terms as $term)
                                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-sessionid" class="form-label">Session</label>
                                    <select id="edit-sessionid" name="sessionid" class="form-select" data-choices required>
                                        <option value="">Select session</option>
                                        @foreach ($schoolsessions as $session)
                                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="hstack gap-2 justify-content-end">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" id="update-btn" class="btn btn-primary">Update Subject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Modal -->
            <div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btn-close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mt-2 text-center">
                                <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                                <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                                    <h4>Are you Sure?</h4>
                                    <p class="text-muted mx-4 mb-0">Are you sure you want to remove this subject?</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                <button type="button" class="btn btn-light w-sm" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-danger w-sm" id="delete-record">Yes, Delete It!</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    // Chart.js configuration for termsChart
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('termsChart')?.getContext('2d');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json(array_keys($term_counts)),
                    datasets: [{
                        label: 'Subjects per Term',
                        data: @json(array_values($term_counts)),
                        backgroundColor: '#405189',
                        borderColor: '#405189',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
@endsection