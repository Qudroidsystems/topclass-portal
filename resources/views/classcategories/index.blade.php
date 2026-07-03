@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Class Category Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Class Category Management</a></li>
                                <li class="breadcrumb-item active">Categories</li>
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

            <div id="categoryList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search categories">
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
                                    <h5 class="card-title mb-0">Class Categories <span class="badge bg-dark-subtle text-dark ms-1">{{ count($classcategories) }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        @can('Create class-category')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Create Category</button>
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
                                                <th class="min-w-50px sort cursor-pointer" data-sort="categoryid">SN</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="category">Class Category</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="ca1score">CA 1 Score</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="ca2score">CA 2 Score</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="ca3score">CA 3 Score</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="examscore">Exam Score</th>
                                                <th class="min-w-100px sort cursor-pointer" data-sort="gradetype">Grade Type</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="datereg">Date Updated</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600 list form-check-all">
                                            @php $i = 0 @endphp
                                            @forelse ($classcategories as $sc)
                                                <tr data-url="{{ route('classcategories.deleteclasscategory', ['classcategoryid' => $sc->id]) }}">
                                                    <td class="id" data-id="{{ $sc->id }}">
                                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" />
                                                        </div>
                                                    </td>
                                                    <td class="categoryid">{{ ++$i }}</td>
                                                    <td class="category" data-category="{{ $sc->category }}">{{ $sc->category }}</td>
                                                    <td class="ca1score" data-ca1score="{{ $sc->ca1score }}">{{ $sc->ca1score }}</td>
                                                    <td class="ca2score" data-ca2score="{{ $sc->ca2score }}">{{ $sc->ca2score }}</td>
                                                    <td class="ca3score" data-ca3score="{{ $sc->ca3score }}">{{ $sc->ca3score }}</td>
                                                    <td class="examscore" data-examscore="{{ $sc->examscore }}">{{ $sc->examscore }}</td>
                                                    <td class="gradetype" data-issenior="{{ $sc->is_senior ? 1 : 0 }}">
                                                        <span class="badge bg-{{ $sc->is_senior ? 'success' : 'primary' }}-subtle text-{{ $sc->is_senior ? 'success' : 'primary' }}">
                                                            {{ $sc->is_senior ? 'Senior' : 'Junior' }}
                                                        </span>
                                                    </td>
                                                    <td class="datereg">{{ $sc->updated_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('Update class-category')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"><i class="ph-pencil"></i></a>
                                                                </li>
                                                            @endcan
                                                            @can('Delete class-category')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn"><i class="ph-trash"></i></a>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Category Modal -->
            <div id="addCategoryModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="exampleModalLabel" class="modal-title">Add Class Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-category-form">
                            <div class="modal-body">
                                <input type="hidden" id="add-id-field" name="id">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Class Category</label>
                                    <input type="text" name="category" id="category" class="form-control" placeholder="Enter category name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Grade Type</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_senior" id="junior" value="0" checked>
                                            <label class="form-check-label" for="junior">
                                                Junior (A, B, C, D, F)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_senior" id="senior" value="1">
                                            <label class="form-check-label" for="senior">
                                                Senior (A1, B2, B3, C4, C5, C6, D7, E8, F9)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="ca1score" class="form-label">CA 1 Score</label>
                                    <input type="number" name="ca1score" id="ca1score" class="form-control score-input" placeholder="Enter CA 1 score" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="ca2score" class="form-label">CA 2 Score</label>
                                    <input type="number" name="ca2score" id="ca2score" class="form-control score-input" placeholder="Enter CA 2 score" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="ca3score" class="form-label">CA 3 Score</label>
                                    <input type="number" name="ca3score" id="ca3score" class="form-control score-input" placeholder="Enter CA 3 score" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="examscore" class="form-label">Exam Score</label>
                                    <input type="number" name="examscore" id="examscore" class="form-control score-input" placeholder="Enter exam score" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="total_score" class="form-label">Total Score</label>
                                    <input type="number" name="total_score" id="total_score" class="form-control" readonly>
                                    <small class="form-text text-muted">Total must be exactly 400</small>
                                </div>
                                <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn" disabled>Add Category</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Category Modal -->
            <div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="editModalLabel" class="modal-title">Edit Class Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="edit-category-form">
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <div class="mb-3">
                                    <label for="edit-category" class="form-label">Class Category</label>
                                    <input type="text" name="category" id="edit-category" class="form-control" placeholder="Enter category name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Grade Type</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_senior" id="edit-junior" value="0">
                                            <label class="form-check-label" for="edit-junior">
                                                Junior (A, B, C, D, F)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_senior" id="edit-senior" value="1">
                                            <label class="form-check-label" for="edit-senior">
                                                Senior (A1, B2, B3, C4, C5, C6, D7, E8, F9)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-ca1score" class="form-label">CA 1 Score</label>
                                    <input type="number" name="ca1score" id="edit-ca1score" class="form-control score-input" placeholder="Enter CA 1 score" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-ca2score" class="form-label">CA 2 Score</label>
                                    <input type="number" name="ca2score" id="edit-ca2score" class="form-control score-input" placeholder="Enter CA 2 score" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-ca3score" class="form-label">CA 3 Score</label>
                                    <input type="number" name="ca3score" id="edit-ca3score" class="form-control score-input" placeholder="Enter CA 3 score" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-examscore" class="form-label">Exam Score</label>
                                    <input type="number" name="examscore" id="edit-examscore" class="form-control score-input" placeholder="Enter exam score" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-total_score" class="form-label">Total Score</label>
                                    <input type="number" name="total_score" id="edit-total_score" class="form-control" readonly>
                                    <small class="form-text text-muted">Total must be exactly 400</small>
                                </div>
                                <div class="alert alert-danger d-none" id="edit-alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="update-btn" disabled>Update</button>
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

        <!-- Scripts -->
        {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script src="{{ asset('theme/layouts/assets/js/list.min.js') }}"></script>
        <script src="{{ asset('theme/layouts/assets/js/sweetalert2.min.js') }}"></script>
        <script src="{{ asset('js/classcategory.init.js') }}?v={{ time() }}"></script> --}}
    </div>
</div
@endsection