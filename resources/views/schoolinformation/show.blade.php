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
                                <li class="breadcrumb-item"><a href="{{ route('admin.school-info.index') }}">School Management</a></li>
                                <li class="breadcrumb-item active">School Overview</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Page Title -->

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">{{ $school->school_name }}</h5>
                            <div>
                                @can('Update schoolinformation')
                                    <a href="javascript:void(0);" class="btn btn-soft-secondary btn-sm edit-item-btn" data-id="{{ $school->id }}">
                                        <i class="ph-pencil"></i> Edit
                                    </a>
                                @endcan
                                <a href="{{ route('admin.school-info.index') }}" class="btn btn-soft-primary btn-sm">
                                    <i class="ph-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- School Logo -->
                                <div class="col-md-4 text-center mb-4">
                                    <h6 class="card-title mb-3">School Logo</h6>
                                    @if($school->getLogoUrlAttribute())
                                        <img src="{{ $school->getLogoUrlAttribute() }}" alt="School Logo" class="img-fluid rounded" style="max-height: 200px;">
                                    @else
                                        <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <span class="text-muted">No logo uploaded</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- App Logo -->
                                <div class="col-md-4 text-center mb-4">
                                    <h6 class="card-title mb-3">App / Website Logo</h6>
                                    @if($school->getAppLogoUrlAttribute())
                                        <img src="{{ $school->getAppLogoUrlAttribute() }}" alt="App Logo" class="img-fluid rounded" style="max-height: 200px;">
                                    @else
                                        <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <span class="text-muted">No app logo uploaded</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Status Badge -->
                                <div class="col-md-4 text-center mb-4 d-flex align-items-center justify-content-center">
                                    <div>
                                        <h6 class="card-title mb-3">Status</h6>
                                        <span class="badge bg-{{ $school->is_active ? 'success' : 'secondary' }} fs-14">
                                            {{ $school->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- School Details -->
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="30%">School Name</th>
                                            <td>{{ $school->school_name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td>{{ $school->school_email }}</td>
                                        </tr>
                                        <tr>
                                            <th>Phone</th>
                                            <td>{{ $school->school_phone ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Address</th>
                                            <td>{{ $school->school_address }}</td>
                                        </tr>
                                        <tr>
                                            <th>Website</th>
                                            <td>
                                                @if($school->school_website)
                                                    <a href="{{ $school->school_website }}" target="_blank">{{ $school->school_website }}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Motto</th>
                                            <td>{{ $school->school_motto ?: '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="40%">Times School Opened</th>
                                            <td>{{ $school->no_of_times_school_opened }}</td>
                                        </tr>
                                        <tr>
                                            <th>Date School Opened</th>
                                            <td>{{ $school->date_school_opened ? $school->date_school_opened->format('d M Y') : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Next Term Begins</th>
                                            <td>{{ $school->date_next_term_begins ? $school->date_next_term_begins->format('d M Y') : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Date Created</th>
                                            <td>{{ $school->created_at->format('d M Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Last Updated</th>
                                            <td>{{ $school->updated_at->format('d M Y h:i A') }}</td>
                                        </tr>
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
