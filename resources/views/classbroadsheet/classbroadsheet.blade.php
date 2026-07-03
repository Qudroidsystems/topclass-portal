@extends('layouts.master')

@section('content')
<style>
    .highlight-red { color: red !important; }
    .avatar-sm { width: 32px; height: 32px; object-fit: cover; }
    .table-active { background-color: rgba(0, 0, 0, 0.05); }
    .table-centered th, .table-centered td { text-align: center; vertical-align: middle; }
    .table-nowrap th, .table-nowrap td { white-space: nowrap; }
    .sort.cursor-pointer:hover { background-color: #f5f5f5; }
    .form-control.teacher-comment-input,
    .form-control.guidance-comment-input,
    .form-control.remark-input,
    .form-control.absence-input { width: 100%; min-width: 150px; }
    .form-control.signature-input { max-width: 300px; }
    .btn-primary { margin-top: 1rem; }
    .signature-container { display: flex; align-items: center; gap: 10px; }

    /* Mobile-specific styles */
    @media (max-width: 991px) {
        .desktop-table {
            display: none;
        }
        
        .mobile-cards {
            display: block;
        }
        
        .student-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .student-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .student-details h6 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .student-meta {
            font-size: 14px;
            color: #6c757d;
        }
        
        .student-body {
            padding: 15px;
        }
        
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .subject-item {
            text-align: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .subject-name {
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 4px;
        }
        
        .subject-score {
            font-size: 18px;
            font-weight: bold;
            color: #212529;
        }
        
        .subject-score.highlight-red {
            color: red !important;
        }
        
        .comments-section {
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
        }
        
        .comment-group {
            margin-bottom: 15px;
        }
        
        .comment-label {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
        }
        
        .form-control.mobile-comment {
            font-size: 14px;
            min-height: 38px;
        }
        
        .search-box {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            padding: 10px 0;
            margin-bottom: 15px;
        }
        
        .mobile-header-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-badge {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #0056b3;
        }
    }
    
    @media (min-width: 992px) {
        .mobile-cards {
            display: none;
        }
        
        .desktop-table {
            display: block;
        }
    }
    
    @media (max-width: 767px) {
        .subjects-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .mobile-header-info {
            flex-direction: column;
            gap: 10px;
        }
        
        .student-header {
            padding: 12px;
        }
        
        .student-body {
            padding: 12px;
        }
        
        .signature-container {
            flex-direction: column;
            align-items: flex-end;
        }
    }
    
    /* Search functionality styles */
    .search-highlight {
        background-color: #fff3cd;
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .no-results {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }
    
    .results-count {
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 15px;
        text-align: center;
    }
</style>

<div class="main-content class-broadsheet">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Class Broadsheet</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Class Broadsheet</a></li>
                                <li class="breadcrumb-item active">Class Broadsheet</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
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

            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?: session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($students->isNotEmpty())
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h5 class="card-title mb-0">Broadsheet for {{ $schoolclass ? $schoolclass->schoolclass . ' ' . $schoolclass->arm : 'N/A' }}</h5>
                            </div>
                            <div class="card-body">
                                <!-- Info Sections (Desktop & Mobile) -->
                                <div class="row g-3 desktop-table">
                                    <div class="d-flex flex-wrap flex-stack mb-4">
                                        <div class="d-flex flex-column flex-grow-1">
                                            <div class="d-flex flex-wrap">
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold">{{ $schoolclass ? $schoolclass->schoolclass . ' ' . $schoolclass->arm : 'N/A' }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Class</div>
                                                </div>
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-calendar fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $schoolterm }} | {{ $schoolsession }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Term | Session</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mobile-cards">
                                    <div class="mobile-header-info">
                                        <div class="info-badge"><i class="bi bi-building me-1"></i> Class: {{ $schoolclass ? $schoolclass->schoolclass . ' ' . $schoolclass->arm : 'N/A' }}</div>
                                        <div class="info-badge"><i class="bi bi-calendar me-1"></i> {{ $schoolterm }} | {{ $schoolsession }}</div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-sm bg-white">
                                        <form id="commentsForm" action="{{ route('classbroadsheet.updateComments', [$schoolclassid, $sessionid, $termid]) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PATCH')

                                            <!-- Search Box -->
                                            <div class="search-box mb-3">
                                                <input type="text" class="form-control search" placeholder="Search students, admission no, or comments..." id="searchInput">
                                                <div class="results-count mt-2" id="resultsCount" style="display: none;"></div>
                                            </div>

                                            <!-- Desktop Table -->
                                            <div class="desktop-table">
                                                <div class="table-responsive">
                                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                                        <thead class="table-active">
                                                            <tr>
                                                                <th>SN</th>
                                                                <th>Admission No</th>
                                                                <th>Student Name</th>
                                                                <th>Gender</th>
                                                                @foreach ($subjects as $subject)
                                                                    <th>{{ $subject->subject }}</th>
                                                                @endforeach
                                                                <th>Class Teacher's Comment</th>
                                                                <th>Guidance Counselor's Comment</th>
                                                                <th>Remark on Other Activities</th>
                                                                <th>No. of Times Absent</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="list">
                                                            @forelse ($students as $key => $student)
                                                                @php
                                                                    $picture = $student->picture ? basename($student->picture) : 'unnamed.jpg';
                                                                    $imagePath = asset('storage/student_avatars/' . $picture);
                                                                    $profile = $personalityProfiles->where('studentid', $student->id)->first();
                                                                @endphp
                                                                <tr class="student-row" data-student-id="{{ $student->id }}">
                                                                    <td>{{ $key + 1 }}</td>
                                                                    <td class="admissionno">{{ $student->admissionNo }}</td>
                                                                    <td class="name">
                                                                        <div class="d-flex align-items-center">
                                                                            <img src="{{ $imagePath }}" class="rounded-circle avatar-sm" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                                            <div class="ms-3">
                                                                                <h6 class="mb-0"><a href="{{ route('myclass.studentpersonalityprofile', [$student->id, $schoolclassid, $termid, $sessionid]) }}" class="text-reset">{{ $student->lastname }} {{ $student->firstname }} {{ $student->othername }}</a></h6>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td>{{ $student->gender ?? 'N/A' }}</td>
                                                                    @foreach ($subjects as $subject)
                                                                        @php $score = $scores->where('student_id', $student->id)->where('subject_name', $subject->subject)->first(); @endphp
                                                                        <td @if($score && is_numeric($score->total) && $score->total <= 50) class="highlight-red" @endif>{{ $score ? $score->total : '-' }}</td>
                                                                    @endforeach
                                                                    <td><input type="text" class="form-control teacher-comment-input" name="teacher_comments[{{ $student->id }}]" value="{{ $profile ? $profile->classteachercomment : '' }}" placeholder="Teacher's comment"></td>
                                                                    <td><input type="text" class="form-control guidance-comment-input" name="guidance_comments[{{ $student->id }}]" value="{{ $profile ? $profile->guidancescomment : '' }}" placeholder="Guidance comment"></td>
                                                                    <td><input type="text" class="form-control remark-input" name="remarks_on_other_activities[{{ $student->id }}]" value="{{ $profile ? $profile->remark_on_other_activities : '' }}" placeholder="Remark"></td>
                                                                    <td><input type="number" class="form-control absence-input" name="no_of_times_school_absent[{{ $student->id }}]" value="{{ $profile ? $profile->no_of_times_school_absent : '' }}" min="0"></td>
                                                                </tr>
                                                            @empty
                                                                <tr><td colspan="{{ 9 + count($subjects) }}">No students found.</td></tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Mobile Cards -->
                                            <div class="mobile-cards" id="mobileStudentCards">
                                                @forelse ($students as $key => $student)
                                                    @php
                                                        $picture = $student->picture ? basename($student->picture) : 'unnamed.jpg';
                                                        $imagePath = asset('storage/student_avatars/' . $picture);
                                                        $profile = $personalityProfiles->where('studentid', $student->id)->first();
                                                    @endphp
                                                    <div class="student-card" data-student-id="{{ $student->id }}"
                                                         data-search-content="{{ strtolower($student->lastname . ' ' . $student->firstname . ' ' . $student->othername . ' ' . $student->admissionNo . ' ' . ($profile?->classteachercomment ?? '') . ' ' . ($profile?->guidancescomment ?? '') . ' ' . ($profile?->remark_on_other_activities ?? '') . ' ' . ($profile?->no_of_times_school_absent ?? '0')) }}">
                                                        <div class="student-header">
                                                            <div class="student-info">
                                                                <img src="{{ $imagePath }}" class="rounded-circle avatar-sm" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                                <div class="student-details">
                                                                    <h6><a href="{{ route('myclass.studentpersonalityprofile', [$student->id, $schoolclassid, $termid, $sessionid]) }}" class="text-reset text-decoration-none">{{ $student->lastname }} {{ $student->firstname }} {{ $student->othername }}</a></h6>
                                                                    <div class="student-meta">
                                                                        <span class="me-3"><strong>SN:</strong> {{ $key + 1 }}</span>
                                                                        <span class="me-3"><strong>Adm:</strong> {{ $student->admissionNo }}</span>
                                                                        <span><strong>Gender:</strong> {{ $student->gender ?? 'N/A' }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="student-body">
                                                            <div class="subjects-grid">
                                                                @foreach ($subjects as $subject)
                                                                    @php $score = $scores->where('student_id', $student->id)->where('subject_name', $subject->subject)->first(); @endphp
                                                                    <div class="subject-item">
                                                                        <div class="subject-name">{{ $subject->subject }}</div>
                                                                        <div class="subject-score @if($score && is_numeric($score->total) && $score->total <= 50) highlight-red @endif">{{ $score ? $score->total : '-' }}</div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                            <div class="comments-section">
                                                                <div class="comment-group">
                                                                    <div class="comment-label">Class Teacher's Comment</div>
                                                                    <input type="text" class="form-control mobile-comment teacher-comment-input" name="teacher_comments[{{ $student->id }}]" value="{{ $profile ? $profile->classteachercomment : '' }}">
                                                                </div>
                                                                <div class="comment-group">
                                                                    <div class="comment-label">Guidance Counselor's Comment</div>
                                                                    <input type="text" class="form-control mobile-comment guidance-comment-input" name="guidance_comments[{{ $student->id }}]" value="{{ $profile ? $profile->guidancescomment : '' }}">
                                                                </div>
                                                                <div class="comment-group">
                                                                    <div class="comment-label">Remark on Other Activities</div>
                                                                    <input type="text" class="form-control mobile-comment remark-input" name="remarks_on_other_activities[{{ $student->id }}]" value="{{ $profile ? $profile->remark_on_other_activities : '' }}">
                                                                </div>
                                                                <div class="comment-group">
                                                                    <div class="comment-label">No. of Times Absent</div>
                                                                    <input type="number" class="form-control mobile-comment absence-input" name="no_of_times_school_absent[{{ $student->id }}]" value="{{ $profile ? $profile->no_of_times_school_absent : '' }}" min="0">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="no-results">No students found.</div>
                                                @endforelse
                                                <div id="noMobileResults" class="no-results" style="display:none;">No matches found.</div>
                                            </div>

                                            <!-- Submit & Signature -->
                                            <div class="d-flex justify-content-end mt-3 signature-container">
                                                <div class="form-group">
                                                    <label for="signature" class="comment-label">Upload Signature (JPG, PNG, PDF)</label>
                                                    <input type="file" class="form-control signature-input" name="signature" id="signature" accept=".jpg,.jpeg,.png,.pdf">
                                                </div>
                                                <button type="submit" class="btn btn-primary">Save Data</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">No student data found for this class, term, and session.</div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Remove duplicate inputs based on current view
        function cleanupInputs() {
            const mobileContainer = document.querySelector('.mobile-cards');
            const isMobile = window.getComputedStyle(mobileContainer).display !== 'none';

            if (isMobile) {
                // Mobile: remove desktop inputs
                document.querySelectorAll('.desktop-table .teacher-comment-input, .desktop-table .guidance-comment-input, .desktop-table .remark-input, .desktop-table .absence-input')
                    .forEach(el => el.remove());
            } else {
                // Desktop: remove mobile inputs
                document.querySelectorAll('.mobile-cards .teacher-comment-input, .mobile-cards .guidance-comment-input, .mobile-cards .remark-input, .mobile-cards .absence-input')
                    .forEach(el => el.remove());
            }
        }

        cleanupInputs();
        window.addEventListener('resize', () => {
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(cleanupInputs, 200);
        });

        // Form validation before submit
        const form = document.getElementById('commentsForm');
        form.addEventListener('submit', function(e) {
            const hasData = Array.from(document.querySelectorAll('.teacher-comment-input, .guidance-comment-input, .remark-input, .absence-input'))
                .some(input => input.value.trim() !== '');

            const hasSignature = document.getElementById('signature').files.length > 0;

            if (!hasData && !hasSignature) {
                e.preventDefault();
                alert('Please enter at least one comment, remark, absence count, or upload a signature.');
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const resultsCount = document.getElementById('resultsCount');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();

                // Desktop rows
                document.querySelectorAll('.desktop-table .student-row').forEach(row => {
                    const text = [
                        row.querySelector('.admissionno')?.textContent || '',
                        row.querySelector('.name')?.textContent || '',
                        row.querySelector('.teacher-comment-input')?.value || '',
                        row.querySelector('.guidance-comment-input')?.value || '',
                        row.querySelector('.remark-input')?.value || '',
                        row.querySelector('.absence-input')?.value || ''
                    ].join(' ').toLowerCase();

                    row.style.display = (term === '' || text.includes(term)) ? '' : 'none';
                });

                // Mobile cards
                let mobileVisible = 0;
                document.querySelectorAll('.mobile-cards .student-card').forEach(card => {
                    const content = card.getAttribute('data-search-content') || '';
                    if (term === '' || content.includes(term)) {
                        card.style.display = '';
                        mobileVisible++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                document.getElementById('noMobileResults').style.display = (mobileVisible === 0 && term) ? 'block' : 'none';

                // Results count
                const totalVisible = document.querySelectorAll('.desktop-table .student-row[style=""], .desktop-table .student-row:not([style])').length + mobileVisible;
                if (term) {
                    resultsCount.textContent = totalVisible > 0 ? `${totalVisible} result(s) found` : 'No matches found';
                    resultsCount.style.display = 'block';
                } else {
                    resultsCount.style.display = 'none';
                }
            });
        }
    });
</script>

@endsection