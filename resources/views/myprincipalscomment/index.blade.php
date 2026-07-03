@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Principal Comments</a></li>
                                <li class="breadcrumb-item active">My Assignments</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Search Box + Counters -->
            <div class="row mb-4">
                <div class="col-lg-5">
                    <div class="search-box">
                        <input type="text" class="form-control search" id="classSearchInput" placeholder="Search by class name or arm...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div class="col-lg-7 d-flex align-items-center justify-content-end gap-3">
                    <span class="text-muted">Total Classes: <strong id="totalClasses">{{ $assignments->count() }}</strong></span>
                    <span class="text-muted">Showing: <strong id="visibleClasses">{{ $assignments->count() }}</strong></span>
                </div>
            </div>

            <!-- Assignments Card -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Classes Assigned for Principal's Comment</h5>
                            <span class="badge bg-primary fs-6">{{ $assignments->count() }} Assigned</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0" id="assignmentsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Class</th>
                                            <th>Last Updated</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="assignmentsBody">
                                        @forelse($assignments as $index => $assignment)
                                            <tr class="assignment-row"
                                                data-search-text="{{ strtolower($assignment->sclass . ' ' . ($assignment->schoolarm ?? '')) }}">
                                                <td class="sn">{{ $loop->iteration }}</td>
                                                <td>
                                                    <strong>{{ $assignment->sclass }} {{ $assignment->schoolarm ?? '' }}</strong>
                                                </td>
                                                <td>
                                                    {{ $assignment->updated_at ? $assignment->updated_at->format('d M Y, h:i A') : 'Never' }}
                                                </td>
                                                <td>
                                                    <a href="{{ route('myprincipalscomment.classbroadsheet', [
                                                        $assignment->schoolclassid,
                                                        $currentSession->id ?? 1,
                                                        2
                                                    ]) }}"
                                                       class="btn btn-soft-success btn-sm">
                                                        <i class="ph-eye me-1"></i> View Broadsheet & Enter Comments
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="emptyStateRow">
                                                <td colspan="4" class="text-center py-5">
                                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json"
                                                               trigger="loop"
                                                               colors="primary:#121331,secondary:#08a88a"
                                                               style="width:120px;height:120px">
                                                    </lord-icon>
                                                    <h5 class="mt-4 text-muted">No Classes Assigned</h5>
                                                    <p class="text-muted mb-0">You have not been assigned any class for entering Principal's comments yet.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <!-- No Search Results -->
                                <div id="noSearchResults" class="text-center py-5" style="display: none;">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json"
                                               trigger="loop"
                                               colors="primary:#121331,secondary:#08a88a"
                                               style="width:120px;height:120px">
                                    </lord-icon>
                                    <h5 class="mt-4 text-muted">No Classes Found</h5>
                                    <p class="text-muted mb-0">Try adjusting your search term.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput    = document.getElementById('classSearchInput');
    var tbody          = document.getElementById('assignmentsBody');
    var noSearchResults = document.getElementById('noSearchResults');
    var visibleClasses = document.getElementById('visibleClasses');
    var emptyStateRow  = document.getElementById('emptyStateRow');

    if (!searchInput || !tbody) return;

    var rows = tbody.querySelectorAll('.assignment-row');
    if (rows.length === 0) return;

    searchInput.addEventListener('input', function () {
        var term = this.value.toLowerCase().trim();
        var visibleCount = 0;

        rows.forEach(function(row) {
            var searchText = row.getAttribute('data-search-text') || '';
            if (term === '' || searchText.includes(term)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        visibleClasses.textContent = visibleCount;

        if (visibleCount === 0 && term !== '') {
            noSearchResults.style.display = 'block';
            if (emptyStateRow) emptyStateRow.style.display = 'none';
        } else {
            noSearchResults.style.display = 'none';
            if (emptyStateRow && term === '') emptyStateRow.style.display = 'table-row';
        }
    });
});
</script>
@endsection
