@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Subject Information for {{ $studentdata->first()->firstname }} {{ $studentdata->first()->lastname }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('subjectoperation.index') }}">Subjects</a></li>
                                <li class="breadcrumb-item active">Subject Information</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Subjects for 
                                @if($classname->isNotEmpty())
                                    {{ $classname->first()->schoolclass }} {{ $classname->first()->arm ?? '' }}
                                @else
                                    Unknown Class
                                @endif
                                (Term: {{ $subjectclass->isNotEmpty() ? $subjectclass->first()->term : 'N/A' }})
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($subjectclass as $sc)
                                            <tr class="subject-class-row" data-subjectclassid="{{ $sc->subjectclassid }}" data-staffid="{{ $sc->staffid }}">
                                                <td>{{ $sc->subject }}</td>
                                                <td>{{ $sc->title }} {{ $sc->name }}</td>
                                                <td>{{ $subjectRegistrations[$sc->subjectid][$sc->staffid]['status']['status'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">No subjects found for this class, term, and session.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <p><strong>Total Subjects:</strong> {{ $totalreg }}</p>
                                <p><strong>Registered Subjects:</strong> {{ $regcount }}</p>
                                <p><strong>Unregistered Subjects:</strong> {{ $noregcount }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection