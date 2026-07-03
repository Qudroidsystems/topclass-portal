@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
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

            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?: session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Filter section --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="ri-filter-3-line me-2"></i>Filter Subjects
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-xxl-4 col-sm-6">
                                    <label for="idsession" class="form-label">Session</label>
                                    <select class="form-control" id="idsession" name="idsession">
                                        <option value="ALL">Select Session</option>
                                        @foreach ($sessions as $session)
                                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xxl-4 col-sm-6">
                                    <label for="idterm" class="form-label">Term</label>
                                    <select class="form-control" id="idterm" name="idterm">
                                        <option value="ALL">Select Term</option>
                                        @foreach ($terms as $term)
                                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xxl-4 col-sm-6">
                                    <label class="form-label"> </label>
                                    <button type="button" class="btn btn-primary w-100 d-block" id="filterButton">
                                        <i class="bi bi-funnel align-baseline me-1"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="subjectList">
                <div class="row" id="subjectTeachersCard" style="display: {{ $subjectTeachers->isNotEmpty() ? 'block' : 'none' }}">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">My Subjects <span class="badge bg-primary-subtle text-primary ms-1" id="subjectTeacherCount">{{ $subjectTeachers->count() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllSubjects()">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="deselectAllSubjects()">Deselect All</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="ri-information-line me-2"></i>
                                    Select the subjects to manage. Only selected subjects will be removable.
                                </div>
                                <div id="subjectTeachersContainer">
                                    @if ($subjectTeachers->isNotEmpty())
                                        @foreach ($terms as $term)
                                            @if ($subjectTeachers->where('termid', $term->id)->isNotEmpty())
                                                <h6 class="mt-3">{{ $term->term }}</h6>
                                                <div class="row">
                                                    @foreach ($subjectTeachers->where('termid', $term->id) as $teacher)
                                                        <div class="col-md-6 col-lg-4">
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input subject-checkbox" type="checkbox" id="subject-{{ $teacher->subjectclassid }}"
                                                                    data-subjectclassid="{{ $teacher->subjectclassid }}" data-staffid="{{ $teacher->userid }}"
                                                                    data-termid="{{ $term->id }}" checked>
                                                                <label class="form-check-label" for="subject-{{ $teacher->subjectclassid }}">
                                                                    <strong>{{ $teacher->subjectname }}</strong><br>
                                                                    <small class="text-muted">{{ $teacher->schoolclass }} - {{ $teacher->staffname }}</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endforeach
                                    @else
                                        <p class="text-center text-muted">No subject teachers found.</p>
                                    @endif
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
                                    <h5 class="card-title mb-0">Subjects <span class="badge bg-dark-subtle text-dark ms-1" id="subjectcount">{{ $mysubjects->count() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="searchInput" placeholder="Search subjects by class, subject, or code..." style="min-width: 200px;">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                @if ($mysubjects->isEmpty())
                                    <div class="alert alert-info text-center" id="noDataAlert" style="display: block;">
                                        <i class="ri-information-line me-2"></i>
                                        No subjects found. Please select session and term to load your subjects.
                                    </div>
                                @else
                                    <div class="alert alert-info text-center" id="noDataAlert" style="display: none;">
                                        <i class="ri-information-line me-2"></i>
                                        No subjects found. Please select session and term to load your subjects.
                                    </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0 list" id="subjectListTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th style="width: 50px;">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                                        <label class="form-check-label" for="checkAll"></label>
                                                    </div>
                                                </th>
                                                <th style="width: 50px;" class="sort cursor-pointer" data-sort="sn">SN</th>
                                                <th class="sort cursor-pointer" data-sort="schoolclass">Class</th>
                                                <th class="sort cursor-pointer" data-sort="subject">Subject</th>
                                                <th class="sort cursor-pointer" data-sort="subjectcode">Subject Code</th>
                                                <th class="sort cursor-pointer" data-sort="term">Term</th>
                                                <th class="sort cursor-pointer" data-sort="session">Session</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="subjectTableBody" class="list form-check-all">
                                            @if ($mysubjects->isNotEmpty())
                                                @foreach ($mysubjects as $index => $subject)
                                                    <tr>
                                                        <td class="id" data-id="{{ $subject->id }}">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="chk_child"
                                                                       data-subjectclassid="{{ $subject->subjectclassid }}"
                                                                       data-termid="{{ $subject->termid }}"
                                                                       data-sessionid="{{ $subject->session_id }}"
                                                                       data-staffid="{{ $subject->userid }}">
                                                                <label class="form-check-label"></label>
                                                            </div>
                                                        </td>
                                                        <td class="sn">{{ $index + 1 }}</td>
                                                        <td class="schoolclass" data-schoolclass="{{ $subject->schoolclass }}">{{ $subject->schoolclass }}</td>
                                                        <td class="subject" data-subject="{{ $subject->subject }}">{{ $subject->subject }}</td>
                                                        <td class="subjectcode" data-subjectcode="{{ $subject->subjectcode }}">{{ $subject->subjectcode }}</td>
                                                        <td class="term" data-term="{{ $subject->term }}">{{ $subject->term }}</td>
                                                        <td class="session" data-session="{{ $subject->session }}">{{ $subject->session }}</td>
                                                        <td>
                                                            <ul class="d-flex gap-2 list-unstyled mb-0">
                                                                @if ($subject->broadsheet_exists)
                                                                    <li>
                                                                        <a href="{{ route('subjectscoresheet.index', [$subject->schoolclassid, $subject->subjectclassid, $subject->userid, $subject->termid, $subject->session_id]) }}"
                                                                           class="btn btn-success btn-icon btn-sm" title="View Terminal Record">
                                                                            <i class="ph-file-list"></i>
                                                                        </a>
                                                                    </li>
                                                                @else
                                                                    <li><span class="badge bg-warning" title="No Terminal Record Available">N/A</span></li>
                                                                @endif
                                                                @if ($subject->broadsheet_mock_exists)
                                                                    <li>
                                                                        <a href="{{ route('subjectscoresheet-mock.show', [$subject->schoolclassid, $subject->subjectclassid, $subject->userid, $subject->termid, $subject->session_id]) }}"
                                                                           class="btn btn-warning btn-icon btn-sm" title="View Mock Record">
                                                                            <i class="ph-clipboard"></i>
                                                                        </a>
                                                                    </li>
                                                                @else
                                                                    <li><span class="badge bg-warning" title="No Mock Record Available">N/A</span></li>
                                                                @endif
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr class="noresult">
                                                    <td colspan="8" class="text-center text-muted">No results found</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection