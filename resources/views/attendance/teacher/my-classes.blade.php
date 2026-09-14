@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Page Title --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Attendance</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Attendance</a></li>
                                <li class="breadcrumb-item active">My Classes</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    <i class="ri-calendar-check-line me-2 text-primary"></i>
                                    My Classes – Attendance
                                    <span class="badge bg-dark-subtle text-dark ms-1">{{ $classes->count() }}</span>
                                </h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">Select a class to mark or review attendance for the current session.</p>

                            @if($classes->isNotEmpty())
                                <div class="row g-3">
                                    @foreach($classes as $cls)
                                    <div class="col-xl-3 col-lg-4 col-md-6">
                                        <div class="card border shadow-sm h-100 mb-0" style="border-left: 4px solid #2563eb !important; transition: transform .15s;">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3 mb-3">
                                                    <div class="avatar-sm flex-shrink-0">
                                                        <span class="avatar-title bg-primary-subtle text-primary rounded-3 fs-4">
                                                            <i class="ri-home-3-line"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-bold mb-1">{{ $cls->schoolclass }} {{ $cls->arm }}</h6>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <span class="badge bg-primary-subtle text-primary">{{ $cls->term }}</span>
                                                            <span class="badge bg-info-subtle text-info">{{ $cls->session }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2 mt-3">
                                                    @can('Create attendance-register')
                                                    <a href="{{ route('attendance.register', [$cls->schoolclassid, $cls->termid, $cls->sessionid]) }}"
                                                       class="btn btn-primary btn-sm flex-grow-1">
                                                        <i class="ri-check-line me-1"></i> Mark Attendance
                                                    </a>
                                                    @endcan
                                                    @can('View attendance-class-summary')
                                                    <a href="{{ route('attendance.class-summary', [$cls->schoolclassid, $cls->termid, $cls->sessionid]) }}"
                                                       class="btn btn-outline-secondary btn-sm">
                                                        <i class="ri-bar-chart-2-line"></i>
                                                    </a>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="ri-book-open-line" style="font-size: 48px; color: #cbd5e1;"></i>
                                    </div>
                                    <h5 class="text-muted">No Classes Assigned</h5>
                                    <p class="text-muted mb-0">You have no classes assigned for the current session.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
