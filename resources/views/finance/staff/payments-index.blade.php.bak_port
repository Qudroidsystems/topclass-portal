
{{-- resources/views/finance/staff/payments-index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.payment-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.payment-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: white;
    padding: 20px;
}
.filter-bar {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 15px 20px;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="payment-header">
        <h1 class="text-white"><i class="ri-wallet-line me-2"></i>{{ $pagetitle }}</h1>
        <p class="text-white-50 mb-0">Manage and track all staff payments, salaries, bonuses, and reimbursements.</p>
    </div>

    <div class="payment-card mt-3">
        <div class="filter-bar">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <label class="form-label mb-0">Staff Member</label>
                    <select class="form-select" id="staffFilter">
                        <option value="">All Staff</option>
                        @foreach($staff as $s)
                            <option value="{{ $s->id }}">{{ $s->user->name ?? 'N/A' }} ({{ $s->employmentid ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-0">Payment Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="salary">Salary</option>
                        <option value="bonus">Bonus</option>
                        <option value="loan_disbursement">Loan Disbursement</option>
                        <option value="reimbursement">Reimbursement</option>
                        <option value="advance">Advance</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-0">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processed">Processed</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="reversed">Reversed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-0">Date Range</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="startDate" placeholder="Start Date">
                        <input type="date" class="form-control" id="endDate" placeholder="End Date">
                    </div>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 mt-3" id="searchBtn">
                        <i class="ri-search-line me-1"></i>Search
                    </button>
                </div>
            </div>
        </div>
        <div class="p-3 text-end">
            <a href="{{ route('staff.payments.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i>Record Payment
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 w-100" id="paymentsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Staff Name</th>
                        <th>Staff ID</th>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>
</div>
</div>

{{-- View Payment Modal --}}
<div class="modal fade" id="viewPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="ri-eye-line me-2"></i>Payment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewPaymentContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Reverse Payment Modal --}}
<div class="modal fade" id="reverseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-refund-line me-2"></i>Reverse Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="reverseForm">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to reverse this payment?</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reverseReason" class="form-control" rows="3" required placeholder="Please provide a reason for reversal..."></textarea>
                    </div>
                    <div class="alert alert-danger d-none" id="reverseErrors"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reversal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
let currentPaymentId = null;
let dataTable;

$(document).ready(function() {
    dataTable = $('#paymentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("staff.payments.index") }}',
            type: 'GET',
            data: function(d) {
                d.staff_id = $('#staffFilter').val();
                d.payment_type = $('#typeFilter').val();
                d.status = $('#statusFilter').val();
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'staff_name', name: 'staff_name' },
            { data: 'staff_id', name: 'staff_id' },
            { data: 'payment_reference', name: 'payment_reference' },
            { data: 'formatted_amount', name: 'amount' },
            { data: 'payment_type_badge', name: 'payment_type', orderable: false },
            { data: 'payment_date', name: 'payment_date' },
            { data: 'payment_method', name: 'payment_method' },
            { data: 'status_badge', name: 'payment_status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[6, 'desc']]
    });

    $('#searchBtn').on('click', function() {
        dataTable.ajax.reload();
    });

    // View Payment
    $(document).on('click', '.view-payment', function() {
        const id = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('viewPaymentModal'));

        $.ajax({
            url: `/staff/payments/${id}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#viewPaymentContent').html(`
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Staff Name</div>
                                    <div class="fw-bold">${data.staff_name}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Staff ID</div>
                                    <div class="fw-bold">${data.staff_id}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Reference</div>
                                    <div class="fw-bold">${data.payment_reference}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Amount</div>
                                    <div class="fw-bold text-success">${data.formatted_amount}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Payment Type</div>
                                    <div class="fw-bold">${data.payment_type_badge}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Payment Method</div>
                                    <div>${data.payment_method}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Payment Date</div>
                                    <div>${new Date(data.payment_date).toLocaleDateString()}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Status</div>
                                    <div>${data.status_badge}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Purpose</div>
                                    <div>${data.purpose || 'N/A'}</div>
                                </div>
                            </div>
                            ${data.bank_name ? `
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Bank Details</div>
                                    <div><strong>Bank:</strong> ${data.bank_name}</div>
                                    <div><strong>Account:</strong> ${data.account_number}</div>
                                    <div><strong>Transaction Ref:</strong> ${data.transaction_ref || 'N/A'}</div>
                                </div>
                            </div>
                            ` : ''}
                            ${data.notes ? `
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Notes</div>
                                    <div>${data.notes}</div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `);
                    modal.show();
                }
            },
            error: function() {
                $('#viewPaymentContent').html('<div class="alert alert-danger">Failed to load payment details.</div>');
            }
        });
    });

    // Mark as Paid
    $(document).on('click', '.mark-paid', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Mark as Paid?',
            text: 'Confirm that this payment has been disbursed to the staff member.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, mark as paid'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/staff/payments/mark-paid/${id}`,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success!', response.message, 'success').then(() => {
                                dataTable.ajax.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to update status', 'error');
                    }
                });
            }
        });
    });

    // Reverse Payment
    $(document).on('click', '.reverse-payment', function() {
        currentPaymentId = $(this).data('id');
        $('#reverseReason').val('');
        $('#reverseErrors').addClass('d-none');
        new bootstrap.Modal(document.getElementById('reverseModal')).show();
    });

    $('#reverseForm').on('submit', function(e) {
        e.preventDefault();
        const reason = $('#reverseReason').val();

        if (!reason) {
            $('#reverseErrors').removeClass('d-none').html('Please provide a reason for reversal.');
            return;
        }

        $.ajax({
            url: `/staff/payments/reverse/${currentPaymentId}`,
            type: 'POST',
            data: { reason: reason, _token: CSRF_TOKEN },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Reversed!', response.message, 'success').then(() => {
                        $('#reverseModal').modal('hide');
                        dataTable.ajax.reload();
                    });
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to reverse payment', 'error');
            }
        });
    });
});
</script>
@endsection
