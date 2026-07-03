@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">School House Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">School House Management</a></li>
                                <li class="breadcrumb-item active">School House</li>
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

            <div id="houseList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search houses">
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
                                    <h5 class="card-title mb-0">School House <span class="badge bg-dark-subtle text-dark ms-1">{{ $schoolhouses->total() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        @can('Create schoolhouse')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addHouseModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Create House</button>
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
                                                {{-- <th class="min-w-125px sort cursor-pointer" data-sort="schoolhouseid">SN</th> --}}
                                                <th class="min-w-125px sort cursor-pointer" data-sort="house">House</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="housecolour">House Colour</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="housemaster">House Master</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="term">Term</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="session">Session</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="datereg">Date Updated</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600 list form-check-all">
                                            @php $i = ($schoolhouses->currentPage() - 1) * $schoolhouses->perPage() @endphp
                                            @forelse ($schoolhouses as $sc)
                                                <tr data-url="{{ route('schoolhouse.deletehouse',$sc->id) }}">
                                                    <td class="id" data-id="{{ $sc->id }}">
                                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" />
                                                        </div>
                                                    </td>
                                                    {{-- <td class="schoolhouseid">{{ ++$i }}</td> --}}
                                                    <td class="house" data-house="{{ $sc->house }}">{{ $sc->house }}</td>
                                                    <td class="housecolour" data-housecolour="{{ $sc->housecolour }}">
                                                        <span class="badge text-white" style="background-color: {{ htmlspecialchars($sc->housecolour) }}">{{ $sc->housecolour }}</span>
                                                    </td>
                                                    <td class="housemaster" data-housemaster="{{ $sc->housemaster }}">{{ $sc->housemaster }}</td>
                                                    <td class="term" data-term="{{ $sc->term }}">{{ $sc->term }}</td>
                                                    <td class="session" data-session="{{ $sc->session }}">{{ $sc->session }}</td>
                                                    <td class="datereg">{{ $sc->updated_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('Update schoolhouse')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"><i class="ph-pencil"></i></a>
                                                                </li>
                                                            @endcan
                                                            @can('Delete schoolhouse')
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
                                            Showing <span class="fw-semibold">{{ $schoolhouses->count() }}</span> of <span class="fw-semibold">{{ $schoolhouses->total() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap hstack gap-2 justify-content-center">
                                            <a class="page-item pagination-prev {{ $schoolhouses->onFirstPage() ? 'disabled' : '' }}" href="{{ $schoolhouses->previousPageUrl() }}">
                                                <i class="mdi mdi-chevron-left align-middle"></i>
                                            </a>
                                            <ul class="pagination listjs-pagination mb-0">
                                                @foreach ($schoolhouses->links()->elements[0] as $page => $url)
                                                    <li class="page-item {{ $schoolhouses->currentPage() == $page ? 'active' : '' }}">
                                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <a class="page-item pagination-next {{ $schoolhouses->hasMorePages() ? '' : 'disabled' }}" href="{{ $schoolhouses->nextPageUrl() }}">
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

            <!-- Add House Modal -->
            <div id="addHouseModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="exampleModalLabel" class="modal-title">Add School House</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-house-form">
                            <div class="modal-body">
                                <input type="hidden" id="add-id-field" name="id">
                                <div class="mb-3">
                                    <label for="house" class="form-label">House Name</label>
                                    <input type="text" name="house" id="house" class="form-control" placeholder="Enter house name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="housecolour" class="form-label">House Colour</label>
                                    <input type="text" name="housecolour" id="housecolour" class="form-control" placeholder="Enter house colour (e.g., red, #FF0000)" required>
                                </div>
                                <div class="mb-3">
                                    <label for="housemasterid" class="form-label">House Master</label>
                                    <select name="housemasterid" id="housemasterid" class="form-control" required>
                                        <option value="" selected>Select House Master</option>
                                        @foreach ($staff as $s)
                                            <option value="{{ $s->userid }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="termid" class="form-label">Term</label>
                                    <select name="termid" id="termid" class="form-control" required>
                                        <option value="" selected>Select Term</option>
                                        @foreach ($schoolterm as $term)
                                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="sessionid" class="form-label">Session</label>
                                    <select name="sessionid" id="sessionid" class="form-control" required>
                                        <option value="" selected>Select Session</option>
                                        @foreach ($schoolsession as $session)
                                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Add House</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit House Modal -->
            <div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="editModalLabel" class="modal-title">Edit School House</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="edit-house-form">
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <div class="mb-3">
                                    <label for="edit-house" class="form-label">House Name</label>
                                    <input type="text" name="house" id="edit-house" class="form-control" placeholder="Enter house name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-housecolour" class="form-label">House Colour</label>
                                    <input type="text" name="housecolour" id="edit-housecolour" class="form-control" placeholder="Enter house colour (e.g., red, #FF0000)" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-housemasterid" class="form-label">House Master</label>
                                    <select name="housemasterid" id="edit-housemasterid" class="form-control" required>
                                        <option value="" selected>Select House Master</option>
                                        @foreach ($staff as $s)
                                            <option value="{{ $s->userid }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-termid" class="form-label">Term</label>
                                    <select name="termid" id="edit-termid" class="form-control" required>
                                        <option value="" selected>Select Term</option>
                                        @foreach ($schoolterm as $term)
                                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-sessionid" class="form-label">Session</label>
                                    <select name="sessionid" id="edit-sessionid" class="form-control" required>
                                        <option value="" selected>Select Session</option>
                                        @foreach ($schoolsession as $session)
                                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                                        @endforeach
                                    </select>
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
{{-- 
        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script src="{{ asset('theme/layouts/assets/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('theme/layouts/assets/js/list.min.js') }}"></script>
        <script src="{{ asset('theme/layouts/assets/js/sweetalert2.min.js') }}"></script>
        <script src="{{ asset('js/schoolhouse-list.init.js') }}"></script> --}}
    </div>
</div>
@endsection