@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">School Bill Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">School Bill Management</a></li>
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

            <div id="schoolBillList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search school bills">
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
                                    <h5 class="card-title mb-0">School Bills <span class="badge bg-dark-subtle text-dark ms-1">{{ $schoolbills->total() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        @can('Create school-bills')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addSchoolBillModal" id="create-school-bill-btn"><i class="bi bi-plus-circle align-baseline me-1"></i> Create School Bill</button>
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
                                                <th class="min-w-125px sort cursor-pointer" data-sort="sn" style="color: rgb(51, 35, 200)">SN</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="title" style="color: rgb(51, 35, 200)">School Bill</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="bill_amount" style="color: rgb(51, 35, 200)">Bill Amount</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="description" style="color: rgb(51, 35, 200)">Remark</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="statusId" style="color: rgb(51, 35, 200)">Student Status</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="updated_at" style="color: rgb(51, 35, 200)">Date Updated</th>
                                                <th class="min-w-100px" style="color: rgb(51, 35, 200)">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600 list form-check-all">
                                            @php $i = $schoolbills->firstItem() - 1 @endphp
                                            @forelse ($schoolbills as $bill)
                                                <tr data-url="{{ route('schoolbill.destroy', $bill->id) }}">
                                                    <td class="id" data-id="{{ $bill->id }}">
                                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" />
                                                        </div>
                                                    </td>
                                                    <td class="sn">{{ ++$i }}</td>
                                                    <td class="title">{{ $bill->title }}</td>
                                                    <td class="bill_amount">₦ {{ number_format($bill->bill_amount) }}</td>
                                                    <td class="description">{{ $bill->description }}</td>
                                                    <td class="statusId">
                                                        @if($bill->statusId == 1)
                                                            Old Student Bill
                                                        @elseif($bill->statusId == 2)
                                                            New Student Bill
                                                        @else
                                                            Unknown Status
                                                        @endif
                                                    </td>
                                                    <td class="updated_at">{{ $bill->updated_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('Update school-bills')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{ $bill->id }}" data-title="{{ $bill->title }}" data-bill_amount="{{ $bill->bill_amount }}" data-description="{{ $bill->description }}" data-statusId="{{ $bill->statusId }}"><i class="ph-pencil"></i></a>
                                                                </li>
                                                            @endcan
                                                            @can('Delete school-bills')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $bill->id }}"><i class="ph-trash"></i></a>
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
                                        {{ $schoolbills->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add School Bill Modal -->
            <div id="addSchoolBillModal" class="modal fade" tabindex="-1" aria-labelledby="addSchoolBillModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="addSchoolBillModalLabel" class="modal-title">Add School Bill</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-schoolbill-form">
                            <div class="modal-body">
                                <input type="hidden" id="add-id-field" name="id">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Bill Title</label>
                                    <input type="text" name="title" id="title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="bill_amount" class="form-label">Bill Amount (₦)</label>
                                    <input type="text" name="bill_amount" id="bill_amount" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Remark</label>
                                    <textarea name="description" id="description" class="form-control" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="statusId" class="form-label">Student Status</label>
                                    <select name="statusId" id="statusId" class="form-control" required>
                                        <option value="">Select Status</option>
                                        <option value="1">Old Student Bill</option>
                                        <option value="2">New Student Bill</option>
                                    </select>
                                </div>
                                <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Add School Bill</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit School Bill Modal -->
            <div id="editSchoolBillModal" class="modal fade" tabindex="-1" aria-labelledby="editSchoolBillModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="editSchoolBillModalLabel" class="modal-title">Edit School Bill</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="edit-schoolbill-form">
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <div class="mb-3">
                                    <label for="edit-title" class="form-label">Bill Title</label>
                                    <input type="text" name="title" id="edit-title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-bill_amount" class="form-label">Bill Amount (₦)</label>
                                    <input type="text" name="bill_amount" id="edit-bill_amount" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-description" class="form-label">Remark</label>
                                    <textarea name="description" id="edit-description" class="form-control" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-statusId" class="form-label">Student Status</label>
                                    <select name="statusId" id="edit-statusId" class="form-control" required>
                                        <option value="">Select Status</option>
                                        <option value="1">Old Student Bill</option>
                                        <option value="2">New Student Bill</option>
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

<script src="{{ asset('js/schoolbill.init.js') }}"></script>
<script>
    // Fallback to test modal manually
    document.addEventListener('DOMContentLoaded', function () {
        const createBtn = document.getElementById('create-school-bill-btn');
        if (createBtn) {
            createBtn.addEventListener('click', function () {
                console.log('Fallback: Create School Bill button clicked');
                try {
                    const modal = new bootstrap.Modal(document.getElementById('addSchoolBillModal'));
                    modal.show();
                    console.log('Fallback: Add modal opened');
                } catch (error) {
                    console.error('Fallback: Error opening add modal:', error);
                }
            });
        }
    });
</script>
@endsection