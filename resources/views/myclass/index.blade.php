@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Class Management</a></li>
                                <li class="breadcrumb-item active">My Classes</li>
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

            <div id="classList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" id="searchInput" placeholder="Search classes" value="{{ request()->query('search') }}">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3">
                                        <select class="form-select" id="idclass">
                                            <option value="ALL">All Classes</option>
                                            @foreach ($schoolclasses as $class)
                                                <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3">
                                        <select class="form-select" id="idsession">
                                            <option value="ALL">All Sessions</option>
                                            @foreach ($schoolsessions as $session)
                                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3">
                                        <button type="button" class="btn btn-primary" onclick="filterData()">Search</button>
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
                                    <h5 class="card-title mb-0">Current Classes <span class="badge bg-dark-subtle text-dark ms-1" id="classcount">{{ $myclass->total() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        @can('Create my-class')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addClassModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Create Class</button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Replace the existing table in the myclass.index template with this -->
                                    <div class="table-responsive">
                                        <table class="table_magic table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_classes_view_table">
                                            <thead>
                                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                    <th class="w-10px pe-2">
                                                        <div class="form-check form-check-sm form-check-solid me-3">
                                                            <input class="form-check-input" type="checkbox" id="checkAll" />
                                                        </div>
                                                    </th>
                                                    <th class="min-w-125px sort cursor-pointer" data-sort="classid">SN</th>
                                                    <th class="min-w-125px sort cursor-pointer" data-sort="schoolclass">Class</th>
                                                    <th class="min-w-125px sort cursor-pointer" data-sort="schoolarm">Arm</th>
                                                    <th class="min-w-125px sort cursor-pointer" data-sort="term">Term</th>
                                                    <th class="min-w-125px sort cursor-pointer" data-sort="session">Session</th>
                                                    <th class="min-w-125px sort cursor-pointer" data-sort="classcategory">View Students</th>
                                                    <th class="min-w-125px sort cursor-pointer" data-sort="updated_at">View Broadsheet</th>
                                                 
                                                </tr>
                                            </thead>
                                            <tbody class="fw-semibold text-gray-600 list form-check-all" id="classTableBody">
                                                @php $i = ($myclass->currentPage() - 1) * $myclass->perPage() @endphp
                                                @forelse ($myclass as $sc)
                                                    <tr>
                                                        <td class="id" data-id="{{ $sc->id }}">
                                                            <div class="form-check form-check-sm form-check-solid">
                                                                <input class="form-check-input" type="checkbox" name="chk_child" />
                                                            </div>
                                                        </td>
                                                        <td class="classid">{{ ++$i }}</td>
                                                        <td class="schoolclass" data-schoolclass="{{ $sc->schoolclass }}">{{ $sc->schoolclass }}</td>
                                                        <td class="schoolarm" data-schoolarm="{{ $sc->schoolarm }}">{{ $sc->schoolarm }}</td>
                                                        <td class="term" data-term="{{ $sc->term }}">{{ $sc->term }}</td>
                                                        <td class="session" data-session="{{ $sc->session }}">{{ $sc->session }}</td>
                                                        <td class="classcategory">
                                                            <a href="{{ route('viewstudent', [$sc->schoolclassid, $sc->termid, $sc->sessionid]) }}" class="btn btn-primary btn-sm">View Students</a>
                                                        </td>
                                                        <td class="updated_at">
                                                            <a href="{{ route('classbroadsheet.viewcomments', [$sc->schoolclassid, $sc->sessionid,$sc->termid ]) }}" class="btn btn-info btn-sm">View Broadsheet</a>
                                                        </td>
                                                       
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="9" class="noresult" style="display: none;">No classes found</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $myclass->count() }}</span> of <span class="fw-semibold" id="classcount">{{ $myclass->total() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap hstack gap-2 justify-content-center" id="pagination-container">
                                            <a class="page-item pagination-prev {{ $myclass->onFirstPage() ? 'disabled' : '' }}" href="javascript:void(0);" data-url="{{ $myclass->previousPageUrl() }}">
                                                <i class="mdi mdi-chevron-left align-middle"></i>
                                            </a>
                                            <ul class="pagination listjs-pagination mb-0">
                                                @foreach ($myclass->links()->elements[0] as $page => $url)
                                                    <li class="page-item {{ $myclass->currentPage() == $page ? 'active' : '' }}">
                                                        <a class="page-link" href="javascript:void(0);" data-url="{{ $url }}">{{ $page }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <a class="page-item pagination-next {{ $myclass->hasMorePages() ? '' : 'disabled' }}" href="javascript:void(0);" data-url="{{ $myclass->nextPageUrl() }}">
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

        </div>
    </div>

    <style>
        /* Enlarge checkboxes */
        #addClassModal .form-check-input,
        #editModal .form-check-input {
            width: 1.5em;
            height: 1.5em;
            margin-top: 0.15em;
        }
        #addClassModal .form-check-label,
        #editModal .form-check-label {
            font-size: 1.1em;
            line-height: 1.5em;
            margin-left: 0.5em;
        }
        /* Ensure delete modal is above other modals and backdrop */
        #deleteRecordModal {
            z-index: 1055;
        }
        #deleteRecordModal .modal-backdrop {
            z-index: 1050;
        }
        /* Fix font path for Material Design Icons */
        @font-face {
            font-family: 'Material Design Icons';
            src: url('{{ asset('theme/layouts/assets/fonts/materialdesignicons-webfont.woff2') }}?v=6.5.95') format('woff2'),
                 url('{{ asset('theme/layouts/assets/fonts/materialdesignicons-webfont.ttf') }}?v=6.5.95') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
    <script src="{{ asset('theme/layouts/assets/js/myclass-list.init.js') }}"></script>
</div>
@endsection