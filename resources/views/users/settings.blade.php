@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $user->name }} - Profile Settings</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active">Profile Settings</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-check-line me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-2"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-xxl-12">
                    <!-- Tabs -->
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-4">
                        <ul class="nav nav-pills arrow-navtabs nav-secondary gap-2 flex-grow-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails">
                                    <i class="ri-user-line me-1"></i> Personal Details
                                </a>
                            </li>
                            @if($isStaff)
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#employmentInfo">
                                    <i class="ri-briefcase-line me-1"></i> Employment
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#qualifications">
                                    <i class="ri-graduation-cap-line me-1"></i> Qualifications
                                </a>
                            </li>
                            @endif
                            @if($isStudent && $studentData)
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#studentInfo">
                                    <i class="ri-user-star-line me-1"></i> Student Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#parentInfo">
                                    <i class="ri-parent-line me-1"></i> Parent Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#academicInfo">
                                    <i class="ri-book-line me-1"></i> Academic
                                </a>
                            </li>
                            @endif
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#security">
                                    <i class="ri-lock-line me-1"></i> Security
                                </a>
                            </li>
                        </ul>
                        <div class="flex-shrink-0 ms-auto">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Users
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Personal Details -->
                                <div class="tab-pane active" id="personalDetails" role="tabpanel">
                                    <div class="text-center mb-5">
                                        <div class="position-relative d-inline-block">
                                            <div class="avatar-xxl">
                                                @php
                                                    // Determine avatar URL
                                                    $avatarUrl = asset('images/default-avatar.png');
                                                    $hasAvatar = false;

                                                    // Priority 1: User's avatar field
                                                    if ($user->avatar) {
                                                        if ($user->isStaff()) {
                                                            $avatarUrl = asset('storage/staff_avatars/' . $user->avatar);
                                                            $hasAvatar = true;
                                                        } elseif ($user->isStudent()) {
                                                            $avatarUrl = asset('storage/student_avatars/' . $user->avatar);
                                                            $hasAvatar = true;
                                                        } else {
                                                            $avatarUrl = asset('storage/avatars/' . $user->avatar);
                                                            $hasAvatar = true;
                                                        }
                                                    }
                                                    // Priority 2: Staff picture model
                                                    elseif ($user->isStaff() && $staffPicture && $staffPicture->picture) {
                                                        $avatarUrl = asset('storage/staff_avatars/' . $staffPicture->picture);
                                                        $hasAvatar = true;
                                                    }
                                                    // Priority 3: Student picture model
                                                    elseif ($user->isStudent() && $studentPicture && $studentPicture->picture) {
                                                        $avatarUrl = asset('storage/student_avatars/' . $studentPicture->picture);
                                                        $hasAvatar = true;
                                                    }

                                                    // Get user initials
                                                    $initials = strtoupper(
                                                        substr($user->first_name ?? ($user->name ? explode(' ', $user->name)[0] : 'U'), 0, 1) .
                                                        substr($user->last_name ?? ($user->name && isset(explode(' ', $user->name)[1]) ? explode(' ', $user->name)[1] : ''), 0, 1)
                                                    );
                                                @endphp

                                                @if($hasAvatar)
                                                    <img src="{{ $avatarUrl }}?t={{ time() }}"
                                                         alt="Profile"
                                                         class="rounded-circle img-thumbnail"
                                                         id="profilePreview"
                                                         style="width: 150px; height: 150px; object-fit: cover;"
                                                         onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}'; this.classList.add('d-none'); this.nextElementSibling?.classList.remove('d-none');">
                                                    <div class="avatar-title rounded-circle bg-light text-primary fs-1 d-none"
                                                         style="width: 150px; height: 150px; line-height: 150px;"
                                                         id="profileFallback">
                                                        {{ $initials }}
                                                    </div>
                                                @else
                                                    <div class="avatar-title rounded-circle bg-light text-primary fs-1"
                                                         style="width: 150px; height: 150px; line-height: 150px;"
                                                         id="profilePreview">
                                                        {{ $initials }}
                                                    </div>
                                                @endif
                                            </div>
                                            <label for="avatar" class="position-absolute bottom-0 end-0 btn btn-sm btn-icon btn-primary rounded-circle">
                                                <i class="ri-camera-line fs-16"></i>
                                                <input type="file" id="avatar" name="avatar" class="d-none" accept="image/*">
                                            </label>
                                        </div>
                                        <h4 class="mt-3 mb-1">{{ $user->name }}</h4>
                                        <p class="text-muted mb-0">{{ $user->email }}</p>
                                        <div class="mt-2">
                                            @foreach($user->roles as $role)
                                                <span class="badge bg-info me-1">{{ $role->name }}</span>
                                            @endforeach
                                            @if($isStudent && $studentData?->admissionNo)
                                                <span class="badge bg-success">Admission: {{ $studentData->admissionNo }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <form action="{{ route('profile.update-info') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $user->id }}">
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="mb-3 border-bottom pb-2">Personal Information</h5></div>
                                            <div class="col-md-6">
                                                <label>First Name <span class="text-danger">*</span></label>
                                                <input type="text" name="fname" class="form-control" value="{{ old('fname', $userbio?->firstname ?? $studentData?->firstname ?? '') }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Last Name <span class="text-danger">*</span></label>
                                                <input type="text" name="lname" class="form-control" value="{{ old('lname', $userbio?->lastname ?? $studentData?->lastname ?? '') }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Other Names</label>
                                                <input type="text" name="oname" class="form-control" value="{{ old('oname', $userbio?->othernames ?? $studentData?->othername ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Phone Number</label>
                                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $userbio?->phone ?? $studentData?->phone_number ?? $user->phone_number ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Gender</label>
                                                <select name="gender" class="form-control">
                                                    <option value="">Select</option>
                                                    <option value="male" {{ old('gender', $userbio?->gender ?? $studentData?->gender ?? '') == 'male' ? 'selected' : '' }}>Male</option>
                                                    <option value="female" {{ old('gender', $userbio?->gender ?? $studentData?->gender ?? '') == 'female' ? 'selected' : '' }}>Female</option>
                                                    <option value="other" {{ old('gender', $userbio?->gender ?? $studentData?->gender ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                                                </select>
                                            </div>
                                            @if(!$isStudent)
                                            <div class="col-md-6">
                                                <label>Marital Status</label>
                                                <select name="maritalstatus" class="form-control">
                                                    <option value="">Select</option>
                                                    <option value="single" {{ old('maritalstatus', $userbio?->maritalstatus ?? '') == 'single' ? 'selected' : '' }}>Single</option>
                                                    <option value="married" {{ old('maritalstatus', $userbio?->maritalstatus ?? '') == 'married' ? 'selected' : '' }}>Married</option>
                                                    <option value="divorced" {{ old('maritalstatus', $userbio?->maritalstatus ?? '') == 'divorced' ? 'selected' : '' }}>Divorced</option>
                                                    <option value="widowed" {{ old('maritalstatus', $userbio?->maritalstatus ?? '') == 'widowed' ? 'selected' : '' }}>Widowed</option>
                                                </select>
                                            </div>
                                            @endif
                                            <div class="col-md-6">
                                                <label>Nationality</label>
                                                <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $userbio?->nationality ?? $studentData?->nationality ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Date of Birth</label>
                                                <input type="date" name="dob" class="form-control"
                                                       value="{{ old('dob', $userbio?->dob ?? ($studentData?->dateofbirth ? \Carbon\Carbon::parse($studentData?->dateofbirth)->format('Y-m-d') : '')) }}">
                                            </div>
                                            <div class="col-12">
                                                <label>Address</label>
                                                <textarea name="address" class="form-control" rows="3">{{ old('address', $userbio?->address ?? $studentData?->home_address ?? '') }}</textarea>
                                            </div>
                                            <div class="col-12 text-end mt-4">
                                                <button type="reset" class="btn btn-light me-2">Reset</button>
                                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Employment Info (Staff) -->
                                @if($isStaff)
                                <div class="tab-pane" id="employmentInfo" role="tabpanel">
                                    <form action="{{ route('profile.update-employment-info') }}" method="POST">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="mb-3 border-bottom pb-2">Employment Information</h5></div>
                                            <div class="col-md-6"><label>Employment ID *</label><input type="text" name="employmentid" class="form-control" value="{{ old('employmentid', $staffInfo?->employmentid ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Job Title *</label><input type="text" name="title" class="form-control" value="{{ old('title', $staffInfo?->title ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Work Phone *</label><input type="text" name="phonenumber" class="form-control" value="{{ old('phonenumber', $staffInfo?->phonenumber ?? '') }}" required></div>
                                            <div class="col-md-6">
                                                <label>Marital Status *</label>
                                                <select name="maritalstatus" class="form-control" required>
                                                    <option value="">Select</option>
                                                    <option value="single" {{ old('maritalstatus', $staffInfo?->maritalstatus ?? '') == 'single' ? 'selected' : '' }}>Single</option>
                                                    <option value="married" {{ old('maritalstatus', $staffInfo?->maritalstatus ?? '') == 'married' ? 'selected' : '' }}>Married</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6"><label>Number of Children</label><input type="number" name="numberofchildren" class="form-control" value="{{ old('numberofchildren', $staffInfo?->numberofchildren ?? 0) }}"></div>
                                            <div class="col-md-6"><label>Spouse Phone</label><input type="text" name="spousenumber" class="form-control" value="{{ old('spousenumber', $staffInfo?->spousenumber ?? '') }}"></div>
                                            <div class="col-12"><label>Residential Address *</label><textarea name="address" class="form-control" rows="3" required>{{ old('address', $staffInfo?->address ?? '') }}</textarea></div>
                                            <div class="col-md-6"><label>State *</label><input type="text" name="state" class="form-control" value="{{ old('state', $staffInfo?->state ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Local Government *</label><input type="text" name="local" class="form-control" value="{{ old('local', $staffInfo?->local ?? '') }}" required></div>
                                            <div class="col-12"><label>Religion *</label><input type="text" name="religion" class="form-control" value="{{ old('religion', $staffInfo?->religion ?? '') }}" required></div>
                                            <div class="col-12 text-end mt-4">
                                                <button type="reset" class="btn btn-light me-2">Reset</button>
                                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Update Employment Info</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Qualifications (Staff) -->
                                <div class="tab-pane" id="qualifications" role="tabpanel">
                                    <div class="card mb-4 border">
                                        <div class="card-header bg-light"><h5 class="card-title mb-0"><i class="ri-add-circle-line me-2"></i>Add New Qualification</h5></div>
                                        <div class="card-body">
                                            <form action="{{ route('profile.add-qualification') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="row g-3">
                                                    <div class="col-md-6"><label>Institution *</label><input type="text" name="institution" class="form-control" required></div>
                                                    <div class="col-md-6"><label>Qualification *</label><input type="text" name="qualification" class="form-control" required></div>
                                                    <div class="col-md-6"><label>Field of Study *</label><input type="text" name="field_of_study" class="form-control" required></div>
                                                    <div class="col-md-6"><label>Year Obtained *</label><input type="number" name="year_obtained" class="form-control" min="1900" max="{{ date('Y')+1 }}" required></div>
                                                    <div class="col-md-6"><label>Certificate</label><input type="file" name="certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
                                                    <div class="col-md-6"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                                                    <div class="col-12 text-end">
                                                        <button type="reset" class="btn btn-light me-2">Clear</button>
                                                        <button type="submit" class="btn btn-success"><i class="ri-add-line me-1"></i>Add</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="card border">
                                        <div class="card-header bg-light"><h5 class="card-title mb-0"><i class="ri-graduation-cap-line me-2"></i>Qualifications <span class="badge bg-primary ms-2">{{ $qualifications->count() }}</span></h5></div>
                                        <div class="card-body">
                                            @if($qualifications->count() > 0)
                                                <table class="table table-hover">
                                                    <thead><tr><th>#</th><th>Institution</th><th>Qualification</th><th>Field</th><th>Year</th><th>Certificate</th><th>Remarks</th><th>Action</th></tr></thead>
                                                    <tbody>
                                                        @foreach($qualifications as $i => $q)
                                                        <tr>
                                                            <td>{{ $i + 1 }}</td>
                                                            <td>{{ $q->institution }}</td>
                                                            <td>{{ $q->qualification }}</td>
                                                            <td>{{ $q->field_of_study }}</td>
                                                            <td>{{ $q->year_obtained }}</td>
                                                            <td>@if($q->certificate_file)<a href="{{ asset('storage/' . $q->certificate_file) }}" target="_blank">View</a>@else - @endif</td>
                                                            <td>{{ $q->remarks ?? '-' }}</td>
                                                            <td>
                                                                <form action="{{ route('profile.delete-qualification', $q->id) }}" method="POST" class="d-inline">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="ri-delete-bin-line"></i></button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-center text-muted py-4">No qualifications added.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Student Info -->
                                @if($isStudent && $studentData)
                                <div class="tab-pane" id="studentInfo" role="tabpanel">
                                    <form action="{{ route('profile.update-student-info') }}" method="POST">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="mb-3 border-bottom pb-2">Student Information</h5></div>
                                            <div class="col-md-6"><label>Admission Number</label><input type="text" class="form-control" value="{{ $studentData->admissionNo ?? 'N/A' }}" readonly></div>
                                            <div class="col-md-6"><label>Admission Date</label><input type="text" class="form-control" value="{{ $studentData->admission_date ? \Carbon\Carbon::parse($studentData->admission_date)->format('d M Y') : 'N/A' }}" readonly></div>
                                            <div class="col-md-6"><label>Phone Number *</label><input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $studentData?->phone_number ?? $user->phone_number ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
                                            <div class="col-md-6"><label>State *</label><input type="text" name="state" class="form-control" value="{{ old('state', $studentData?->state ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Local Government *</label><input type="text" name="local" class="form-control" value="{{ old('local', $studentData?->local ?? '') }}" required></div>
                                            <div class="col-12"><label>Home Address *</label><textarea name="home_address" class="form-control" rows="3" required>{{ old('home_address', $studentData?->home_address ?? '') }}</textarea></div>
                                            <div class="col-md-6"><label>Emergency Contact *</label><input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $parentData?->father_phone ?? '') }}" required></div>
                                            <div class="col-12 text-end mt-4">
                                                <button type="reset" class="btn btn-light me-2">Reset</button>
                                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Update Student Info</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Parent Info -->
                                <div class="tab-pane" id="parentInfo" role="tabpanel">
                                    <form action="{{ route('profile.update-parent-info') }}" method="POST">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="mb-3 border-bottom pb-2">Parent/Guardian Information</h5></div>
                                            <div class="col-md-6"><label>Father's Name *</label><input type="text" name="father" class="form-control" value="{{ old('father', $parentData?->father ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Mother's Name *</label><input type="text" name="mother" class="form-control" value="{{ old('mother', $parentData?->mother ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Father's Phone *</label><input type="text" name="father_phone" class="form-control" value="{{ old('father_phone', $parentData?->father_phone ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Mother's Phone *</label><input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone', $parentData?->mother_phone ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Father's Occupation *</label><input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation', $parentData?->father_occupation ?? '') }}" required></div>
                                            <div class="col-md-6"><label>Religion *</label><input type="text" name="religion" class="form-control" value="{{ old('religion', $parentData?->religion ?? '') }}" required></div>
                                            <div class="col-12"><label>Home Address *</label><textarea name="parent_address" class="form-control" rows="3" required>{{ old('parent_address', $parentData?->parent_address ?? '') }}</textarea></div>
                                            <div class="col-12"><label>Office Address</label><textarea name="office_address" class="form-control" rows="2">{{ old('office_address', $parentData?->office_address ?? '') }}</textarea></div>
                                            <div class="col-12 text-end mt-4">
                                                <button type="reset" class="btn btn-light me-2">Reset</button>
                                                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Update Parent Info</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Academic Info -->
                                <div class="tab-pane" id="academicInfo" role="tabpanel">
                                    <div class="row">
                                        <div class="col-lg-6 mb-4">
                                            <div class="card border">
                                                <div class="card-header bg-light"><h5 class="card-title mb-0"><i class="ri-book-open-line me-2"></i>Current Class</h5></div>
                                                <div class="card-body">
                                                    @if($currentClass?->schoolclass)
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class="avatar-sm"><div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-16"><i class="ri-building-line"></i></div></div>
                                                            <div class="ms-3">
                                                                <h6>{{ $currentClass->schoolclass->schoolclass ?? 'Not Assigned' }}</h6>
                                                                <p class="text-muted mb-0">{{ $currentClass->schoolclass->armRelation?->schoolarm ?? '' }}</p>
                                                            </div>
                                                        </div>
                                                        <ul class="list-unstyled">
                                                            <li><strong>Session:</strong> {{ $currentClass->session?->session ?? 'N/A' }}</li>
                                                            <li><strong>Term:</strong> {{ $currentClass->term?->term ?? 'N/A' }}</li>
                                                            <li><strong>Class Teacher:</strong> {{ $currentClass->schoolclass?->classteacher ?? 'Not Assigned' }}</li>
                                                        </ul>
                                                    @else
                                                        <p class="text-center text-muted py-4">No class assigned.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-4">
                                            <div class="card border">
                                                <div class="card-header bg-light"><h5 class="card-title mb-0"><i class="ri-history-line me-2"></i>Class History</h5></div>
                                                <div class="card-body">
                                                    @if($classHistory?->count() > 0)
                                                        <table class="table table-sm table-borderless">
                                                            <thead><tr><th>Class</th><th>Session</th><th>Term</th></tr></thead>
                                                            <tbody>
                                                                @foreach($classHistory as $h)
                                                                <tr>
                                                                    <td>{{ $h->schoolclass?->schoolclass ?? 'N/A' }}</td>
                                                                    <td>{{ $h->session?->session ?? 'N/A' }}</td>
                                                                    <td>{{ $h->term?->term ?? 'N/A' }}</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    @else
                                                        <p class="text-center text-muted py-4">No history available.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Security -->
                                <div class="tab-pane" id="security" role="tabpanel">
                                    <div class="row">
                                        <div class="col-lg-6 mb-4">
                                            <div class="card border">
                                                <div class="card-header bg-light"><h5 class="card-title mb-0"><i class="ri-mail-line me-2"></i>Update Email</h5></div>
                                                <div class="card-body">
                                                    <form id="updateEmailForm">
                                                        @csrf
                                                        <input type="hidden" name="userid" value="{{ $user->id }}">
                                                        <div class="mb-3"><label>Current Email</label><input type="email" class="form-control" value="{{ $user->email }}" readonly></div>
                                                        <div class="mb-3"><label>New Email *</label><input type="email" name="emailaddress" class="form-control" required></div>
                                                        <div class="mb-3"><label>Confirm New Email *</label><input type="email" name="emailaddress_confirmation" class="form-control" required></div>
                                                        <button type="submit" class="btn btn-primary"><span class="spinner-border spinner-border-sm d-none me-1"></span> Update Email</button>
                                                    </form>
                                                    <div id="emailMessage" class="mt-3"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-4">
                                            <div class="card border">
                                                <div class="card-header bg-light"><h5 class="card-title mb-0"><i class="ri-lock-line me-2"></i>Change Password</h5></div>
                                                <div class="card-body">
                                                    <form id="updatePasswordForm">
                                                        @csrf
                                                        <input type="hidden" name="userid" value="{{ $user->id }}">
                                                        <div class="mb-3"><label>New Password *</label><input type="password" name="password" class="form-control" required></div>
                                                        <div class="mb-3"><label>Confirm Password *</label><input type="password" name="password_confirmation" class="form-control" required></div>
                                                        <div class="alert alert-info"><i class="ri-information-line me-2"></i> Minimum 8 characters</div>
                                                        <button type="submit" class="btn btn-primary"><span class="spinner-border spinner-border-sm d-none me-1"></span> Update Password</button>
                                                    </form>
                                                    <div id="passwordMessage" class="mt-3"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Session messages
@if(session('success'))
    Swal.fire({icon:'success', title:'Success', text:'{{ session('success') }}', toast:true, position:'top-end', timer:3000, showConfirmButton: false});
@endif
@if(session('error'))
    Swal.fire({icon:'error', title:'Error', text:'{{ session('error') }}', toast:true, position:'top-end', timer:3000, showConfirmButton: false});
@endif

// Avatar upload with preview and progress
document.getElementById('avatar')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    console.log('File selected:', file.name, 'Size:', file.size);

    if (file.size > 5 * 1024 * 1024) {
        Swal.fire('Error', 'Image must be less than 5MB', 'error');
        this.value = '';
        return;
    }

    if (!file.type.match('image.*')) {
        Swal.fire('Error', 'Please select an image file', 'error');
        this.value = '';
        return;
    }

    // Immediate preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('profilePreview');
        const fallback = document.getElementById('profileFallback');

        if (preview.tagName === 'IMG') {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            if (fallback) fallback.classList.add('d-none');
        } else {
            // Replace initials div with img
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Profile';
            img.className = 'rounded-circle img-thumbnail';
            img.id = 'profilePreview';
            img.style.cssText = 'width: 150px; height: 150px; object-fit: cover;';
            img.onerror = function() {
                this.onerror = null;
                this.src = '{{ asset("images/default-avatar.png") }}';
            };
            preview.parentNode.replaceChild(img, preview);
        }
    };
    reader.readAsDataURL(file);

    // Upload
    const fd = new FormData();
    fd.append('avatar', file);
    fd.append('id', '{{ $user->id }}');
    fd.append('_token', '{{ csrf_token() }}');

    console.log('Uploading avatar...');
    Swal.fire({
        title: 'Uploading...',
        html: 'Please wait while we upload your profile picture',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch('{{ route("profile.update-avatar") }}', {
        method: 'POST',
        body: fd,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        Swal.close();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });

            // Update avatar image
            if (data.avatar_url) {
                const preview = document.getElementById('profilePreview');
                const fallback = document.getElementById('profileFallback');

                if (preview.tagName === 'IMG') {
                    preview.src = data.avatar_url + '?t=' + Date.now();
                    preview.classList.remove('d-none');
                    if (fallback) fallback.classList.add('d-none');
                } else {
                    // Replace initials div with image
                    const img = document.createElement('img');
                    img.src = data.avatar_url + '?t=' + Date.now();
                    img.alt = 'Profile';
                    img.className = 'rounded-circle img-thumbnail';
                    img.id = 'profilePreview';
                    img.style.cssText = 'width: 150px; height: 150px; object-fit: cover;';
                    img.onerror = function() {
                        this.onerror = null;
                        this.src = '{{ asset("images/default-avatar.png") }}';
                    };
                    preview.parentNode.replaceChild(img, preview);
                }
            }
        } else {
            Swal.fire('Error!', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        Swal.fire('Error!', 'Network error occurred. Please try again.', 'error');
    });
});

// Email Update Form
document.getElementById('updateEmailForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const fd = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');
    const spinner = btn.querySelector('.spinner-border');
    const msgDiv = document.getElementById('emailMessage');

    console.log('Email update form submitted');
    console.log('Form data:', Object.fromEntries(fd.entries()));

    btn.disabled = true;
    spinner?.classList.remove('d-none');

    fetch('{{ route("profile.update-email") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd
    })
    .then(response => {
        console.log('Email response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Email response data:', data);

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });
            msgDiv.innerHTML = `<div class="alert alert-success alert-dismissible fade show">
                <i class="ri-check-line me-2"></i>${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            form.reset();
        } else {
            Swal.fire('Error!', data.message, 'error');
            msgDiv.innerHTML = `<div class="alert alert-danger alert-dismissible fade show">
                <i class="ri-error-warning-line me-2"></i>${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        }
    })
    .catch(error => {
        console.error('Email update error:', error);
        Swal.fire('Error!', 'Request failed. Please try again.', 'error');
        msgDiv.innerHTML = `<div class="alert alert-danger alert-dismissible fade show">
            <i class="ri-error-warning-line me-2"></i>Request failed. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
    })
    .finally(() => {
        btn.disabled = false;
        spinner?.classList.add('d-none');
    });
});

// Password Update Form
document.getElementById('updatePasswordForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const fd = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');
    const spinner = btn.querySelector('.spinner-border');
    const msgDiv = document.getElementById('passwordMessage');

    console.log('Password update form submitted');

    btn.disabled = true;
    spinner?.classList.remove('d-none');

    fetch('{{ route("profile.update-password") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd
    })
    .then(response => {
        console.log('Password response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Password response data:', data);

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });
            msgDiv.innerHTML = `<div class="alert alert-success alert-dismissible fade show">
                <i class="ri-check-line me-2"></i>${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            form.reset();
        } else {
            Swal.fire('Error!', data.message, 'error');
            msgDiv.innerHTML = `<div class="alert alert-danger alert-dismissible fade show">
                <i class="ri-error-warning-line me-2"></i>${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        }
    })
    .catch(error => {
        console.error('Password update error:', error);
        Swal.fire('Error!', 'Request failed. Please try again.', 'error');
        msgDiv.innerHTML = `<div class="alert alert-danger alert-dismissible fade show">
            <i class="ri-error-warning-line me-2"></i>Request failed. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
    })
    .finally(() => {
        btn.disabled = false;
        spinner?.classList.add('d-none');
    });
});

// Tab persistence
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', e => {
            localStorage.setItem('activeProfileTab', e.target.getAttribute('href'));
        });
    });

    const saved = localStorage.getItem('activeProfileTab');
    if (saved) {
        const tab = document.querySelector(`a[href="${saved}"]`);
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
});
</script>
@endsection
