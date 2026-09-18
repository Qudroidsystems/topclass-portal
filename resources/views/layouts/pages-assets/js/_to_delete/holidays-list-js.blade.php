<!-- JAVASCRIPT -->
<script src="{{ asset('theme/layouts/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Toggle cut-off time visibility based on full day checkbox
    $('#is_full_day, #edit_is_full_day').on('change', function() {
        const isFullDay = $(this).is(':checked');
        const groupId = $(this).attr('id') === 'is_full_day' ? 'cutoff_time_group' : 'edit_cutoff_time_group';
        $('#' + groupId).toggle(!isFullDay);
    });

    // Add Holiday
    $('#addHolidayForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        // Convert checkbox values to boolean
        data.is_full_day = data.is_full_day ? true : false;
        data.affects_timetable = data.affects_timetable ? true : false;

        $('#saveHolidayBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        $.ajax({
            url: '{{ route("holidays.store") }}',
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                });
            },
            complete: function() {
                $('#saveHolidayBtn').prop('disabled', false).html('Save Holiday');
            }
        });
    });

    // Edit Holiday - Load data into modal
    $('.edit-holiday').on('click', function() {
        const id = $(this).data('id');
        const date = $(this).data('date');
        const title = $(this).data('title');
        const isFullDay = $(this).data('is_full_day') == 1;
        const cutoffTime = $(this).data('cutoff_time');
        const sessionId = $(this).data('session_id');
        const termId = $(this).data('term_id');

        $('#edit_holiday_id').val(id);
        $('#edit_date').val(date);
        $('#edit_title').val(title);
        $('#edit_is_full_day').prop('checked', isFullDay);
        $('#edit_cutoff_time').val(cutoffTime || '');
        $('#edit_session_id').val(sessionId || '');
        $('#edit_term_id').val(termId || '');
        
        // Show/hide cutoff time
        $('#edit_cutoff_time_group').toggle(!isFullDay);
    });

    // Update Holiday
    $('#editHolidayForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#edit_holiday_id').val();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        data.is_full_day = data.is_full_day ? true : false;
        data.affects_timetable = data.affects_timetable ? true : false;

        $('#updateHolidayBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');

        $.ajax({
            url: `/holidays/${id}`,
            method: 'POST',
            data: {
                ...data,
                _method: 'PUT'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                });
            },
            complete: function() {
                $('#updateHolidayBtn').prop('disabled', false).html('Update Holiday');
            }
        });
    });

    // Delete Holiday
    $('.delete-holiday').on('click', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');

        Swal.fire({
            title: 'Delete Holiday',
            text: `Are you sure you want to delete "${title}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/holidays/${id}`,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: response.message,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'An error occurred'
                        });
                    }
                });
            }
        });
    });

    // Auto-dismiss modal on close
    $('#addHolidayModal, #editHolidayModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
    });
});
</script>
