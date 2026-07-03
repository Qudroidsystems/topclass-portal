<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">

<head>

    <meta charset="utf-8">
    <title>{{ $pagetitle }} | Vite-ESchool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="school management software" name="description">
    <meta content="" name="author">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('theme/layouts/assets/images/favicon.ico')}}">

    <!-- Fonts css load -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link id="fontsLink" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/fonts/materialdesignicons-webfont.woff2') }}?v=6.5.95" rel="stylesheet" type="font/woff2">
    <style>
        .pagination-wrap .page-item { margin: 0 5px; }
        .pagination-wrap .page-link { padding: 5px 10px; }
        .pagination-wrap .active .page-link { background-color: #007bff; color: white; }
        .pagination-wrap .disabled .page-link { pointer-events: none; opacity: 0.5; }
/* Add these styles to your existing CSS */
.cropper-container {
    max-height: 300px;
    overflow: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 10px;
    background: #f8f9fa;
}

.cropper-controls {
    padding: 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-top: 10px;
}

.cropper-controls .row {
    margin-bottom: 10px;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.img-thumbnail {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 4px;
    background: white;
}
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    @if (Route::is('dashboard'))
                @include('layouts.pages-assets.css.users-list-css')
    @endif

    @if (Route::is('users.*'))
                @include('layouts.pages-assets.css.users-list-css')
    @endif

    @if (Route::is('profile.*'))
                @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('roles.*'))
         @include('layouts.pages-assets.css.roles-list-css')
    @endif

    @if (Route::is('permissions.*'))
          @include('layouts.pages-assets.css.permission-list-css')
   @endif

   @if (Route::is('session.*'))
        @include('layouts.pages-assets.css.session-list-css')
   @endif

   @if (Route::is('school-information.*'))
        @include('layouts.pages-assets.css.schoolinformation-list-css')
   @endif

    @if (Route::is('admin.school-info.*'))
        @include('layouts.pages-assets.css.schoolinformation-list-css')
   @endif

   @if (Route::is('term.*'))
        @include('layouts.pages-assets.css.term-list-css')
   @endif

   @if (Route::is('schoolhouse.*'))
         @include('layouts.pages-assets.css.schoolhouse-list-css')
   @endif

   @if (Route::is('schoolarm.*'))
       @include('layouts.pages-assets.css.arm-list-css')
   @endif

   @if (Route::is('classcategories.*'))
        @include('layouts.pages-assets.css.classcategory-list-css')
   @endif

   @if (Route::is('schoolclass.*'))
         @include('layouts.pages-assets.css.schoolclass-list-css')
   @endif

   @if (Route::is('classteacher.*'))
        @include('layouts.pages-assets.css.classteacher-list-css')
   @endif

   @if (Route::is('subject.*'))
       @include('layouts.pages-assets.css.subject-list-css')
   @endif


   @if (Route::is('subjects.*'))
       @include('layouts.pages-assets.css.subject-list-css')
   @endif


   @if (Route::is('subjectteacher.*'))
        @include('layouts.pages-assets.css.subjectteacher-list-css')
   @endif

   @if (Route::is('subjectclass.*'))
        @include('layouts.pages-assets.css.subjectclass-list-css')
   @endif

   @if (Route::is('schoolbill.*'))
        @include('layouts.pages-assets.css.schoolbill-list-css')
   @endif

   @if (Route::is('schoolbilltermsession.*'))
        @include('layouts.pages-assets.css.schoolbilltermsession-list-css')
   @endif

   @if (Route::is('student.*'))
        @include('layouts.pages-assets.css.student-list-css')
   @endif

    @if (Route::is('studentbatchindex'))
        @include('layouts.pages-assets.css.student-list-css')
   @endif

   @if (Route::is('myclass.*'))
       @include('layouts.pages-assets.css.myclass-list-css')
   @endif

    @if (Route::is('mysubject.*'))
         @include('layouts.pages-assets.css.mysubject-list-css')
    @endif

    @if (Route::is('viewstudent'))
        @include('layouts.pages-assets.css.viewstudent-list-css')
    @endif

    @if (Route::is('studentreports.*'))
            @include('layouts.pages-assets.css.studentreport-list-css')
    @endif

    @if (Route::is('studentmockreports.*'))
            @include('layouts.pages-assets.css.studentreport-list-css')
    @endif

    @if (Route::is('subjectoperation.*'))
        @include('layouts.pages-assets.css.subjectoperation-list-css')
    @endif

    @if (Route::is('subjects.subjectinfo'))
        @include('layouts.pages-assets.css.subjectinfo-list-css')
    @endif

    @if (Route::is('myresultroom.*'))
        @include('layouts.pages-assets.css.myresultroom-list-css')
    @endif

    @if (Route::is('subjectscoresheet'))
        @include('layouts.pages-assets.css.subjectscoresheet-list-css')
    @endif

    @if (Route::is('subjectscoresheet-mock.*'))
           @include('layouts.pages-assets.css.subjectscoresheet-mock-list-css')
    @endif

    @if (Route::is('studentresults*'))
        @include('layouts.pages-assets.css.studentresults-list-css')
    @endif

    @if (Route::is('schoolbill*'))
        @include('layouts.pages-assets.css.schoolbill-list-css')
    @endif

    @if (Route::is('schoolpayment*'))
        @include('layouts.pages-assets.css.schoolpayment-list-css')
    @endif

    @if (Route::is('analysis*'))
        @include('layouts.pages-assets.css.analysis-list-css')
    @endif

    @if (Route::is('exams*'))
        @include('layouts.pages-assets.css.exams-list-css')
    @endif

    @if (Route::is('questions*'))
        @include('layouts.pages-assets.css.questions-list-css')
    @endif

    @if (Route::is('cbt*'))
        @include('layouts.pages-assets.css.cbt-list-css')
    @endif

    @if (Route::is('classbroadsheet.*'))
        @include('layouts.pages-assets.css.classbroadsheet-list-css')
    @endif

    @if (Route::is('principalscomment.*'))
        @include('layouts.pages-assets.css.principalscomment-list-css')
    @endif

    @if (Route::is('myprincipalscomment.*'))
        @include('layouts.pages-assets.css.myprincipalscomment-list-css')
    @endif

    @if (Route::is('compulsorysubjectclass.*'))
        @include('layouts.pages-assets.css.compulsorysubjectclass-list-css')
    @endif

    @if (Route::is('subjectvetting.*'))
        @include('layouts.pages-assets.css.subjectvettings-list-css')
    @endif

    @if (Route::is('mocksubjectvetting.*'))
        @include('layouts.pages-assets.css.mocksubjectvettings-list-css')
    @endif

    @if (Route::is('mysubjectvettings.*'))
        @include('layouts.pages-assets.css.mysubjectvettings-list-css')
    @endif

    @if (Route::is('mymocksubjectvettings.*'))
        @include('layouts.pages-assets.css.mymocksubjectvettings-list-css')
    @endif

     @if (Route::is('promotions.*'))
        @include('layouts.pages-assets.css.promotions-list-css')
    @endif
</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <!-- ========== App Menu ========== -->
        <div class="app-menu navbar-menu">


            <!-- LOGO -->
                <div class="navbar-brand-box">
                    @php
                        use App\Models\SchoolInformation;
                        $schoolInfo = SchoolInformation::getActiveSchool();
                        $schoolName = $schoolInfo?->school_name ?? config('app.name', 'School System');

                        // Use app_logo if available, otherwise fall back to school_logo
                        $appLogoUrl = $schoolInfo?->app_logo_url ?? $schoolInfo?->logo_url ?? asset('theme/layouts/assets/images/logo-dark.png');
                    @endphp

                    <a href="{{ url('/') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ $appLogoUrl }}"
                                alt="{{ $schoolName }}"
                                style="height: 40px; width: auto; border-radius: 8px; object-fit: contain;"
                                onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/logo-sm.png') }}'">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ $appLogoUrl }}"
                                alt="{{ $schoolName }}"
                                style="height: 50px; width: auto; border-radius: 10px; object-fit: contain;"
                                onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/logo-dark.png') }}'">
                        </span>
                    </a>

                    <a href="{{ url('/') }}" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ $appLogoUrl }}"
                                alt="{{ $schoolName }}"
                                style="height: 40px; width: auto; border-radius: 8px; object-fit: contain;"
                                onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/logo-sm.png') }}'">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ $appLogoUrl }}"
                                alt="{{ $schoolName }}"
                                style="height: 50px; width: auto; border-radius: 10px; object-fit: contain;"
                                onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/logo-light.png') }}'">
                        </span>
                    </a>

                    <button type="button" class="btn btn-sm p-0 fs-3xl header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                        <i class="ri-record-circle-line"></i>
                    </button>
                </div>

            <div id="scrollbar">
                <div class="container-fluid">

                    <div id="two-column-menu">
                    </div>
                    <ul class="navbar-nav" id="navbar-nav">

                        <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                        <li class="nav-item">
                            <a class="nav-link menu-link collapsed " href="#sidebarDashboards" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarDashboards">
                                <i class="ph-gauge"></i> <span data-key="t-dashboards">Dashboards</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarDashboards">
                                <ul class="nav nav-sm flex-column">

                                        <li class="nav-item">
                                            <a href="{{ route('dashboard') }}" class="nav-link" data-key="t-analytics"> Administration Analytics </a>
                                        </li>

                                    @can('finance dashboard')
                                    <li class="nav-item">
                                        <a href="dashboard-crm.html" class="nav-link" data-key="t-crm"> Finance Analytics</a>
                                    </li>
                                    @endcan
                                    @can('academics dashboard')
                                    <li class="nav-item">
                                        <a href="index.html" class="nav-link" data-key="t-ecommerce"> Academics Analytics </a>
                                    </li>
                                    @endcan

                                </ul>
                            </div>
                        </li>


                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">USERS & PRIVILEDGES</span></li>
                        @can('View user')
                            <li class="nav-item">
                                <a class="nav-link menu-link collapsed" href="#sidebarusers" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAuth">
                                    <i class="ph-user-circle"></i> <span data-key="t-authentication">User Managements</span>
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarusers">
                                    <ul class="nav nav-sm flex-column">

                                            <li class="nav-item">
                                            <a href="{{ route('users.index') }}" class="nav-link" role="button" data-key="t-signin"> Users </a>
                                        </li>

                                    </ul>
                                </div>
                            </li>
                        @endcan
                        @can('View role')
                              <li class="nav-item">
                            <a class="nav-link menu-link collapsed" href="#sidebarroles" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPages">
                                <i class="ph-address-book"></i> <span data-key="t-pages">Roles And Permissions</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarroles">
                                <ul class="nav nav-sm flex-column">
                                    @can('View role')
                                         <li class="nav-item">
                                        <a href="{{ route('roles.index') }}" class="nav-link" data-key="t-starter"> Roles </a>
                                    </li>
                                    @endcan
                                   @can('View permission')
                                        <li class="nav-item">
                                        <a href="{{ route('permissions.index') }}" class="nav-link" data-key="t-profile"> Permissions </a>
                                    </li>
                                   @endcan

                                </ul>
                            </div>
                        </li>
                        @endcan


                       <li class="nav-item">
                            <a class="nav-link menu-link collapsed" href="#sidebaraccount" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebaraccount">
                                <i class="ph-address-book"></i> <span data-key="t-pages">My Account</span>
                            </a>
                                <div class="collapse menu-dropdown" id="sidebaraccount">
                                    <ul class="nav nav-sm flex-column">
                                        <!-- General Profile -->
                                        <li class="nav-item">
                                            <a href="{{ route('users.overview', ['id' => Auth::id()]) }}" class="nav-link">
                                                <i class="ri-profile-line me-2"></i> My Profile
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}" class="nav-link">
                                                <i class="ri-settings-3-line me-2"></i> Account Settings
                                            </a>
                                        </li>

                                        <!-- Staff Section -->
                                        @if(Auth::user()->isStaff())
                                        <li class="nav-item">
                                            <div class="nav-link text-muted small px-0">
                                                <i class="ri-briefcase-line me-2"></i> STAFF SECTION
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#employmentInfo" class="nav-link ps-4">
                                                <i class="ri-building-line me-2"></i> Employment Details
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#qualifications" class="nav-link ps-4">
                                                <i class="ri-graduation-cap-line me-2"></i> Academic Qualifications
                                            </a>
                                        </li>
                                        @endif

                                        <!-- Student Section -->
                                        @if(Auth::user()->isStudent())
                                        <li class="nav-item">
                                            <div class="nav-link text-muted small px-0">
                                                <i class="ri-user-star-line me-2"></i> STUDENT SECTION
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#studentInfo" class="nav-link ps-4">
                                                <i class="ri-user-line me-2"></i> Student Details
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#parentInfo" class="nav-link ps-4">
                                                <i class="ri-parent-line me-2"></i> Parent Information
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#academicInfo" class="nav-link ps-4">
                                                <i class="ri-book-open-line me-2"></i> Academic Details
                                            </a>
                                        </li>
                                        @endif

                                        <!-- Security Section -->
                                        <li class="nav-item">
                                            <div class="nav-link text-muted small px-0">
                                                <i class="ri-shield-keyhole-line me-2"></i> SECURITY
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#security" class="nav-link ps-4">
                                                <i class="ri-lock-password-line me-2"></i> Change Password
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#security" class="nav-link ps-4">
                                                <i class="ri-mail-settings-line me-2"></i> Change Email
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                        </li>




                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-apps">STUDENT & PARENTS</span></li>



                        <li class="nav-item">
                            <a href="#sidebarStudentmanagement" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarStudentmanagement">
                                <i class="ph-storefront"></i> <span data-key="t-ecommerce">Student Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarStudentmanagement">
                                <ul class="nav nav-sm flex-column">
                                    @can('View student')
                                         <li class="nav-item">
                                               <a href="{{ route('student.index') }}" class="nav-link" data-key="t-products">All Students</a>
                                         </li>
                                    @endcan
                                   @can('Create student-bulk-upload')
                                       <li class="nav-item">
                                              <a href="{{ route('studentbatchindex') }}" class="nav-link" data-key="t-products-grid">Batch Student Registration</a>
                                       </li>
                                   @endcan

                                </ul>
                            </div>
                        </li>


                        <li class="nav-item">
                            <a href="#sidebarParent" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarParent">
                                <i class="ph-storefront"></i> <span data-key="t-ecommerce">Parent Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarParent">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="apps-ecommerce-products.html" class="nav-link" data-key="t-products">All Parents</a>
                                    </li>

                                </ul>
                            </div>
                        </li>

                        <li class="menu-title"><i class="ph-folder-open"></i> <span data-key="t-apps">SUBJECT REGISTRATION</span></li>

                        <li class="nav-item">
                            <a href="#sidebarsubjectoperaton" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarsubjectoperaton">
                                <i class="ph-folder-open"></i> <span data-key="t-ecommerce">Subject Registration </span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarsubjectoperaton">
                                <ul class="nav nav-sm flex-column">
                                    @can('View my-class')
                                          <li class="nav-item">
                                               <a href="{{ route('subjectoperation.index') }}" class="nav-link" data-key="t-products">Student Subject Registration</a>
                                         </li>
                                    @endcan


                                </ul>
                            </div>
                        </li>

                        <li class="menu-title"><i class="ph-folder-open"></i> <span data-key="t-apps">CLASSES & RECORDS</span></li>

                        <li class="nav-item">
                            <a href="#sidebarClasses" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarClasses">
                                <i class="ph-folder-open"></i> <span data-key="t-ecommerce">Classes & Subjects </span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarClasses">
                                <ul class="nav nav-sm flex-column">
                                    @can('View my-class')
                                          <li class="nav-item">
                                               <a href="{{ route('myclass.index') }}" class="nav-link" data-key="t-products">My Class</a>
                                         </li>
                                    @endcan
                                  @can('View my-subject')
                                          <li class="nav-item">
                                              <a href="{{ route('mysubject.index') }}" class="nav-link" data-key="t-products">My Subject</a>
                                        </li>
                                  @endcan

                                   @can('View my-subject-vettings')
                                          <li class="nav-item">
                                              <a href="{{ route('mysubjectvettings.index') }}" class="nav-link" data-key="t-products">Subjects to Vet</a>
                                        </li>
                                  @endcan
                                  @can('View my-mock-subject-vettings')
                                          <li class="nav-item">
                                              <a href="{{ route('mymocksubjectvettings.index') }}" class="nav-link" data-key="t-products">Mock Subjects to Vet</a>
                                        </li>
                                  @endcan

                                   @can('View my-principals-comment')
                                          <li class="nav-item">
                                              <a href="{{ route('myprincipalscomment.index') }}" class="nav-link" data-key="t-products">Principal's Comment</a>
                                        </li>
                                  @endcan
                                </ul>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a href="#sidebarRecords" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRecords">
                                <i class="ph-folder-open"></i> <span data-key="t-ecommerce">Records and Results </span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarRecords">
                                <ul class="nav nav-sm flex-column">
                                    @can('View myresult-room')
                                         <li class="nav-item">
                                              <a href="{{ route('myresultroom.index') }}" class="nav-link" data-key="t-products">Terminal & Mock Records</a>
                                        </li>
                                    @endcan


                                   @can('View student-report')
                                       <li class="nav-item">
                                        <a href="{{ route('studentreports.index') }}" class="nav-link" data-key="t-products">Terminal Result Reports</a>
                                    </li>
                                   @endcan

                                   @can('View student-mock-report')
                                       <li class="nav-item">
                                        <a href="{{ route('studentmockreports.index') }}" class="nav-link" data-key="t-products">Mock Result Reports</a>
                                    </li>
                                   @endcan


                                </ul>
                            </div>
                        </li>


                         <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-apps">PROMOTION MANAGEMENT </span></li>

                         <li class="nav-item">
                            <a href="#sidebarPromotions" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRecords">
                                <i class="ph-folder-open"></i> <span data-key="t-ecommerce">Promotion Management </span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarPromotions">
                                <ul class="nav nav-sm flex-column">
                                    @can('View myresult-room')
                                         <li class="nav-item">
                                              <a href="{{ route('promotions.index') }}" class="nav-link" data-key="t-products">Student Promotion</a>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>


                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-apps">BURSARY & FINANCE </span></li>
                        <li class="nav-item">
                            <a href="#sidebarStudentpayments" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarStudentpayments">
                                <i class="ph-storefront"></i> <span data-key="t-ecommerce">Student Payments</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarStudentpayments">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('schoolpayment.index') }}" class="nav-link" data-key="t-products">Student Bill</a>
                                    </li>
                                    {{-- <li class="nav-item">
                                        <a href="apps-ecommerce-products-grid.html" class="nav-link" data-key="t-products-grid">Student Invoice</a>
                                    </li> --}}
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a href="#sidebarAnalysis" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAnalysis">
                                <i class="ph-storefront"></i> <span data-key="t-ecommerce">Payment Analysis</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarAnalysis">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('analysis.index') }}" class="nav-link" data-key="t-products">School payment  Analysis</a>
                                    </li>
                                    {{-- <li class="nav-item">
                                        <a href="{{ route('analysis.index') }}" class="nav-link" data-key="t-products-grid">Specific Analysis</a>
                                    </li> --}}
                                </ul>
                            </div>
                        </li>


                        <li class="menu-title"><i class="ph-graduation-cap"></i> <span data-key="t-apps">EXAMS AND CBT </span></li>
                        <li class="nav-item">
                            <a href="#sidebarExams" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarExams">
                                <i class="ph-graduation-cap"></i> <span data-key="t-ecommerce">Exams Managment</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarExams">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="apps-ecommerce-products.html" class="nav-link" data-key="t-products">All Examinations</a>
                                    </li>

                                </ul>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a href="#sidebarCBT" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCBT">
                                <i class="ph-graduation-cap"></i> <span data-key="t-ecommerce">CBT Managment</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarCBT">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="apps-ecommerce-products.html" class="nav-link" data-key="t-products">CBT Exercise</a>
                                    </li>

                                </ul>
                            </div>
                        </li>



                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-components">SCHOOL BASIC SETTINGS</span></li>


                        <li class="nav-item">
                            <a href="#sidebarSession" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSession">
                                <i class="ph-file-text"></i> <span data-key="t-invoices">School Information</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSession">
                                <ul class="nav nav-sm flex-column">
                                    @can('View schoolinformation')
                                          <li class="nav-item">
                                             <a href="{{ route('school-information.index') }}" class="nav-link" data-key="t-list-view">School Information</a>
                                          </li>
                                    @endcan


                                </ul>
                            </div>
                        </li>



                        <li class="nav-item">
                            <a href="#sidebarSession" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSession">
                                <i class="ph-file-text"></i> <span data-key="t-invoices">Session Term & House</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSession">
                                <ul class="nav nav-sm flex-column">
                                    @can('View session')
                                          <li class="nav-item">
                                             <a href="{{ route('session.index') }}" class="nav-link" data-key="t-list-view">School Session</a>
                                          </li>
                                    @endcan
                                    @can('View term')
                                       <li class="nav-item">
                                            <a href="{{ route('term.index') }}" class="nav-link" data-key="t-overview">School Term</a>
                                       </li>
                                   @endcan
                                   @can('View schoolhouse')
                                       <li class="nav-item">
                                             <a href="{{ route('schoolhouse.index') }}" class="nav-link" data-key="t-create-invoice">School House</a>
                                       </li>
                                   @endcan

                                </ul>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a href="#sidebarClassessettings" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarClassessettings">
                                <i class="ph-file-text"></i> <span data-key="t-invoices">Classes</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarClassessettings">
                                <ul class="nav nav-sm flex-column">
                                    @can('View school-arm')
                                         <li class="nav-item">
                                            <a href="{{ route('schoolarm.index') }}" class="nav-link" data-key="t-list-view">Class Arm</a>
                                        </li>
                                    @endcan

                                    @can('View class-category')
                                        <li class="nav-item">
                                             <a href="{{ route('classcategories.index') }}" class="nav-link" data-key="t-overview">Class Category</a>
                                        </li>
                                    @endcan
                                    @can('View school-class')
                                         <li class="nav-item">
                                            <a href="{{ route('schoolclass.index') }}" class="nav-link" data-key="t-create-invoice">Class Name</a>
                                         </li>
                                    @endcan
                                    @can('View class-teacher')
                                        <li class="nav-item">
                                            <a href="{{ route('classteacher.index') }}" class="nav-link" data-key="t-create-invoice">Class Teacher</a>
                                        </li>
                                    @endcan


                                </ul>
                            </div>
                        </li>


                        <li class="nav-item">
                            <a href="#sidebarSub" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSub">
                                <i class="ph-file-text"></i> <span data-key="t-invoices">Subject</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSub">
                                <ul class="nav nav-sm flex-column">
                                    @can('View subjects')
                                          <li class="nav-item">
                                              <a href="{{ route('subject.index') }}" class="nav-link" data-key="t-list-view">Subject</a>
                                          </li>
                                    @endcan

                                    @can('View subject-teacher')
                                          <li class="nav-item">
                                            <a href="{{ route('subjectteacher.index') }}" class="nav-link" data-key="t-overview">Assign Subject Teacher</a>
                                          </li>
                                    @endcan

                                    @can('View subject-class')
                                          <li class="nav-item">
                                            <a href="{{ route('subjectclass.index') }}" class="nav-link" data-key="t-create-invoice">Assign Class Subject</a>
                                          </li>
                                    @endcan

                                     @can('View compulsory-subject')
                                          <li class="nav-item">
                                            <a href="{{ route('compulsorysubjectclass.index') }}" class="nav-link" data-key="t-create-invoice">Assign Compulsory  Subject to classes</a>
                                          </li>
                                    @endcan

                                </ul>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a href="#sidebarPrincipal" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSub">
                                <i class="ph-file-text"></i> <span data-key="t-invoices">Principal's Comments</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarPrincipal">
                                <ul class="nav nav-sm flex-column">
                                    @can('View principals-comment')
                                          <li class="nav-item">
                                              <a href="{{ route('principalscomment.index') }}" class="nav-link" data-key="t-list-view">Assign Staff</a>
                                          </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a href="#sidebarSubjectvetting" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSub">
                                <i class="ph-file-text"></i> <span data-key="t-invoices">Terminal Subject Vettings</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSubjectvetting">
                                <ul class="nav nav-sm flex-column">
                                    @can('View subjects')
                                          <li class="nav-item">
                                              <a href="{{ route('subjectvetting.index') }}" class="nav-link" data-key="t-list-view">Assign Subjects to Staff</a>
                                          </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>


                         <li class="nav-item">
                            <a href="#mocksidebarSubjectvetting" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSub">
                                <i class="ph-file-text"></i> <span data-key="t-invoices">Mock Subject Vettings</span>
                            </a>
                            <div class="collapse menu-dropdown" id="mocksidebarSubjectvetting">
                                <ul class="nav nav-sm flex-column">
                                    @can('View subjects')
                                          <li class="nav-item">
                                              <a href="{{ route('mocksubjectvetting.index') }}" class="nav-link" data-key="t-list-view">Assign Subjects to Staff</a>
                                          </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>




                        <li class="nav-item">
                            <a href="#sidebarBills" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarBills">
                                <i class="ph-file-text"></i> <span data-key="t-invoices">School Bills</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarBills">
                                <ul class="nav nav-sm flex-column">
                                    @can('View school-bills')
                                          <li class="nav-item">
                                            <a href="{{ route('schoolbill.index') }}" class="nav-link" data-key="t-list-view">Bills</a>
                                          </li>
                                    @endcan
                                    @can('View school-bill-for-term-session')
                                           <li class="nav-item">
                                                <a href="{{ route('schoolbilltermsession.index') }}" class="nav-link" data-key="t-overview">Appy Bills</a>
                                          </li>
                                    @endcan

                                </ul>
                            </div>
                        </li>

                    </ul>
                </div>
                <!-- Sidebar -->
            </div>

            <div class="sidebar-background"></div>
        </div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>

        <header id="page-topbar">
            <div class="layout-width">
                <div class="navbar-header">
                    <div class="d-flex">
                        <!-- LOGO -->
                        <div class="navbar-brand-box horizontal-logo">
                            <a href="index.html" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="{{ asset('theme/layouts/assets/images/logo-sm.png')}}" alt="" height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ asset('theme/layouts/assets/images/logo-dark.png')}}" alt="" height="22">
                                </span>
                            </a>

                            <a href="index.html" class="logo logo-light">
                                <span class="logo-sm">
                                    <img src="{{ asset('theme/layouts/assets/images/logo-sm.png')}}" alt="" height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ asset('theme/layouts/assets/images/logo-light.png')}}" alt="" height="22">
                                </span>
                            </a>
                        </div>

                        <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger shadow-none" id="topnav-hamburger-icon">
                            <span class="hamburger-icon">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>

                        <form class="app-search d-none d-md-inline-flex">
                            <div class="position-relative">
                                <input type="text" class="form-control" placeholder="Search..." autocomplete="off" id="search-options" value="">
                                <span class="mdi mdi-magnify search-widget-icon"></span>
                                <span class="mdi mdi-close-circle search-widget-icon search-widget-icon-close d-none" id="search-close-options"></span>
                            </div>
                            <div class="dropdown-menu dropdown-menu-lg" id="search-dropdown">
                                <div data-simplebar style="max-height: 320px;">
                                    <!-- item-->
                                    <div class="dropdown-header">
                                        <h6 class="text-overflow text-muted mb-0 text-uppercase">Recent Searches</h6>
                                    </div>

                                    <div class="dropdown-item bg-transparent text-wrap">
                                        <a href="index.html" class="btn btn-subtle-secondary btn-sm btn-rounded">how to setup <i class="mdi mdi-magnify ms-1"></i></a>
                                        <a href="index.html" class="btn btn-subtle-secondary btn-sm btn-rounded">buttons <i class="mdi mdi-magnify ms-1"></i></a>
                                    </div>
                                    <!-- item-->
                                    <div class="dropdown-header mt-2">
                                        <h6 class="text-overflow text-muted mb-1 text-uppercase">Pages</h6>
                                    </div>

                                    <!-- item-->
                                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                                        <i class="ri-bubble-chart-line align-middle fs-18 text-muted me-2"></i>
                                        <span>Analytics Dashboard</span>
                                    </a>

                                    <!-- item-->
                                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                                        <i class="ri-lifebuoy-line align-middle fs-18 text-muted me-2"></i>
                                        <span>Help Center</span>
                                    </a>

                                    <!-- item-->
                                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                                        <i class="ri-user-settings-line align-middle fs-18 text-muted me-2"></i>
                                        <span>My account settings</span>
                                    </a>

                                    <!-- item-->
                                    <div class="dropdown-header mt-2">
                                        <h6 class="text-overflow text-muted mb-2 text-uppercase">Members</h6>
                                    </div>

                                    <div class="notification-list">
                                        <!-- item -->
                                        <a href="javascript:void(0);" class="dropdown-item notify-item py-2">
                                            <div class="d-flex">
                                                <img src="assets/images/users/avatar-2.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                                <div class="flex-1">
                                                    <h6 class="m-0">Angela Bernier</h6>
                                                    <span class="fs-11 mb-0 text-muted">Manager</span>
                                                </div>
                                            </div>
                                        </a>
                                        <!-- item -->
                                        <a href="javascript:void(0);" class="dropdown-item notify-item py-2">
                                            <div class="d-flex">
                                                <img src="assets/images/users/avatar-3.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                                <div class="flex-1">
                                                    <h6 class="m-0">David Grasso</h6>
                                                    <span class="fs-11 mb-0 text-muted">Web Designer</span>
                                                </div>
                                            </div>
                                        </a>
                                        <!-- item -->
                                        <a href="javascript:void(0);" class="dropdown-item notify-item py-2">
                                            <div class="d-flex">
                                                <img src="assets/images/users/avatar-5.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                                <div class="flex-1">
                                                    <h6 class="m-0">Mike Bunch</h6>
                                                    <span class="fs-11 mb-0 text-muted">React Developer</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <div class="text-center pt-3 pb-1">
                                    <a href="#" class="btn btn-primary btn-sm">View All Results <i class="ri-arrow-right-line ms-1"></i></a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex align-items-center">

                        {{-- <div class="dropdown topbar-head-dropdown ms-1 header-item">
                            <button type="button" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class='bi bi-grid fs-2xl'></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-lg p-0 dropdown-menu-end">
                                <div class="p-3 border-top-0 border-start-0 border-end-0 border-dashed border">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h6 class="m-0 fw-semibold fs-base"> Browse by Apps </h6>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#!" class="btn btn-sm btn-subtle-info"> View All Apps
                                                <i class="ri-arrow-right-s-line align-middle"></i></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-2">
                                    <div class="row g-0">
                                        <div class="col">
                                            <a class="dropdown-icon-item" href="#!">
                                                <img src="assets/images/brands/github.png')}}" alt="Github">
                                                <span>GitHub</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a class="dropdown-icon-item" href="#!">
                                                <img src="assets/images/brands/bitbucket.png')}}" alt="bitbucket">
                                                <span>Bitbucket</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a class="dropdown-icon-item" href="#!">
                                                <img src="assets/images/brands/dribbble.png')}}" alt="dribbble">
                                                <span>Dribbble</span>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="row g-0">
                                        <div class="col">
                                            <a class="dropdown-icon-item" href="#!">
                                                <img src="assets/images/brands/dropbox.png')}}" alt="dropbox">
                                                <span>Dropbox</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a class="dropdown-icon-item" href="#!">
                                                <img src="assets/images/brands/mail_chimp.png')}}" alt="mail_chimp">
                                                <span>Mail Chimp</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a class="dropdown-icon-item" href="#!">
                                                <img src="assets/images/brands/slack.png')}}" alt="slack">
                                                <span>Slack</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}





                        {{-- <div class="ms-1 header-item d-none d-sm-flex">
                            <button type="button" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle" data-toggle="fullscreen">
                                <i class='bi bi-arrows-fullscreen fs-lg'></i>
                            </button>
                        </div> --}}

                        <div class="dropdown topbar-head-dropdown ms-1 header-item">
                            <button type="button" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle mode-layout" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bi bi-sun align-middle fs-3xl"></i>
                            </button>
                            <div class="dropdown-menu p-2 dropdown-menu-end" id="light-dark-mode">
                                <a href="#!" class="dropdown-item" data-mode="light"><i class="bi bi-sun align-middle me-2"></i> Default (light mode)</a>
                                <a href="#!" class="dropdown-item" data-mode="dark"><i class="bi bi-moon align-middle me-2"></i> Dark</a>
                                <a href="#!" class="dropdown-item" data-mode="auto"><i class="bi bi-moon-stars align-middle me-2"></i> Auto (system default)</a>
                            </div>
                        </div>

                        {{-- <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                            <button type="button" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle" id="page-header-notifications-dropdown" data-bs-toggle="dropdown"  data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                                <i class='bi bi-bell fs-2xl'></i>
                                <span class="position-absolute topbar-badge fs-3xs translate-middle badge rounded-pill bg-danger"><span class="notification-badge">4</span><span class="visually-hidden">unread messages</span></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">

                                <div class="dropdown-head rounded-top">
                                    <div class="p-3 border-bottom border-bottom-dashed">
                                        <div class="row align-items-center">
                                            <div class="col">
                                                <h6 class="mb-0 fs-lg fw-semibold"> Notifications <span class="badge bg-danger-subtle text-danger fs-sm notification-badge"> 4</span></h6>
                                                <p class="fs-md text-muted mt-1 mb-0">You have <span class="fw-semibold notification-unread">3</span> unread messages</p>
                                            </div>
                                            <div class="col-auto dropdown">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" class="link-secondary fs-md"><i class="bi bi-three-dots-vertical"></i></a>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#">All Clear</a></li>
                                                    <li><a class="dropdown-item" href="#">Mark all as read</a></li>
                                                    <li><a class="dropdown-item" href="#">Archive All</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="py-2 ps-2" id="notificationItemsTabContent">
                                    <div data-simplebar style="max-height: 300px;" class="pe-2">
                                        <h6 class="text-overflow text-muted fs-sm my-2 text-uppercase notification-title">New</h6>
                                        <div class="text-reset notification-item d-block dropdown-item position-relative unread-message">
                                            <div class="d-flex">
                                                <div class="avatar-xs me-3 flex-shrink-0">
                                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-lg">
                                                        <i class="bx bx-badge-check"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="#!" class="stretched-link">
                                                        <h6 class="mt-0 fs-md mb-2 lh-base">Your <b>Elite</b> author Graphic
                                                            Optimization <span class="text-secondary">reward</span> is ready!
                                                        </h6>
                                                    </a>
                                                    <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                        <span><i class="mdi mdi-clock-outline"></i> Just 30 sec ago</span>
                                                    </p>
                                                </div>
                                                <div class="px-2 fs-base">
                                                    <div class="form-check notification-check">
                                                        <input class="form-check-input" type="checkbox" value="" id="all-notification-check01">
                                                        <label class="form-check-label" for="all-notification-check01"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-reset notification-item d-block dropdown-item position-relative unread-message">
                                            <div class="d-flex">
                                                <div class="position-relative me-3 flex-shrink-0">
                                                    <img src="assets/images/users/32/avatar-2.jpg" class="rounded-circle avatar-xs" alt="user-pic">
                                                    <span class="active-badge position-absolute start-100 translate-middle p-1 bg-success rounded-circle">
                                                        <span class="visually-hidden">New alerts</span>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="#!" class="stretched-link">
                                                        <h6 class="mt-0 mb-1 fs-md fw-semibold">Angela Bernier</h6>
                                                    </a>
                                                    <div class="fs-sm text-muted">
                                                        <p class="mb-1">Answered to your comment on the cash flow forecast's graph 🔔.</p>
                                                    </div>
                                                    <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                        <span><i class="mdi mdi-clock-outline"></i> 48 min ago</span>
                                                    </p>
                                                </div>
                                                <div class="px-2 fs-base">
                                                    <div class="form-check notification-check">
                                                        <input class="form-check-input" type="checkbox" value="" id="all-notification-check02">
                                                        <label class="form-check-label" for="all-notification-check02"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-reset notification-item d-block dropdown-item position-relative unread-message">
                                            <div class="d-flex">
                                                <div class="avatar-xs me-3 flex-shrink-0">
                                                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-lg">
                                                        <i class='bx bx-message-square-dots'></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="#!" class="stretched-link">
                                                        <h6 class="mt-0 mb-2 fs-md lh-base">You have received <b class="text-success">20</b> new messages in the conversation
                                                        </h6>
                                                    </a>
                                                    <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                        <span><i class="mdi mdi-clock-outline"></i> 2 hrs ago</span>
                                                    </p>
                                                </div>
                                                <div class="px-2 fs-base">
                                                    <div class="form-check notification-check">
                                                        <input class="form-check-input" type="checkbox" value="" id="all-notification-check03">
                                                        <label class="form-check-label" for="all-notification-check03"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="text-overflow text-muted fs-sm my-2 text-uppercase notification-title">Read Before</h6>

                                        <div class="text-reset notification-item d-block dropdown-item position-relative">
                                            <div class="d-flex">

                                                <div class="position-relative me-3 flex-shrink-0">
                                                    <img src="assets/images/users/32/avatar-8.jpg" class="rounded-circle avatar-xs" alt="user-pic">
                                                    <span class="active-badge position-absolute start-100 translate-middle p-1 bg-warning rounded-circle">
                                                        <span class="visually-hidden">New alerts</span>
                                                    </span>
                                                </div>

                                                <div class="flex-grow-1">
                                                    <a href="#!" class="stretched-link">
                                                        <h6 class="mt-0 mb-1 fs-md fw-semibold">Maureen Gibson</h6>
                                                    </a>
                                                    <div class="fs-sm text-muted">
                                                        <p class="mb-1">We talked about a project on linkedin.</p>
                                                    </div>
                                                    <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                        <span><i class="mdi mdi-clock-outline"></i> 4 hrs ago</span>
                                                    </p>
                                                </div>
                                                <div class="px-2 fs-base">
                                                    <div class="form-check notification-check">
                                                        <input class="form-check-input" type="checkbox" value="" id="all-notification-check04">
                                                        <label class="form-check-label" for="all-notification-check04"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="notification-actions" id="notification-actions">
                                        <div class="d-flex text-muted justify-content-center align-items-center">
                                            Select <div id="select-content" class="text-body fw-semibold px-1">0</div> Result <button type="button" class="btn btn-link link-danger p-0 ms-2" data-bs-toggle="modal" data-bs-target="#removeNotificationModal">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}


<!-- Place this in your header blade file -->

<div class="dropdown ms-sm-3 header-item topbar-user">
    <button type="button" class="btn shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="d-flex align-items-center">
            @php
                use App\Models\User;
                use App\Models\Student;
                use Illuminate\Support\Facades\Storage;
                use Illuminate\Support\Facades\Auth;

                $userdata = Auth::user();
                $isStudent = $userdata->hasRole('student');
                $avatarPath = '';
                $fullName = $userdata->name ?? 'User';
                $studentPicture = null;
                $srcPath = '';
                $fallbackSrc = '';

                if ($isStudent) {
                    $student = Student::where('id', $userdata->student_id)->first();
                    $studentPicture = $student ? $student->picture : null;

                    // Check if picture exists in storage
                    if ($studentPicture && Storage::disk('public')->exists('student_avatars/' . basename($studentPicture))) {
                        $srcPath = asset('storage/student_avatars/' . basename($studentPicture));
                    } else if ($studentPicture && Storage::disk('public')->exists($studentPicture)) {
                        $srcPath = asset('storage/' . $studentPicture);
                    } else {
                        // Use default avatar for students
                        $srcPath = asset('assets/images/users/avatar-1.jpg');
                    }
                    $fallbackSrc = asset('assets/images/users/avatar-1.jpg');
                } else {
                    // For staff/admin users
                    if ($userdata->avatar && Storage::disk('public')->exists('staff_avatars/' . basename($userdata->avatar))) {
                        $srcPath = asset('storage/staff_avatars/' . basename($userdata->avatar));
                    } else if ($userdata->avatar && Storage::disk('public')->exists($userdata->avatar)) {
                        $srcPath = asset('storage/' . $userdata->avatar);
                    } else {
                        // Use default avatar for staff
                        $srcPath = asset('assets/images/users/avatar-1.jpg');
                    }
                    $fallbackSrc = asset('assets/images/users/avatar-1.jpg');
                }

                // For the modal data attribute
                $dataPicture = $isStudent ? ($studentPicture ?? 'none') : ($userdata->avatar ?? 'none');
            @endphp

            <!-- Enhanced Profile Picture with rounded corners -->
            <div class="avatar-lg me-2 position-relative">
                <img src="{{ $srcPath }}"
                     alt="{{ $fullName }}"
                     class="w-100 header-profile-user-enhanced"
                     data-bs-toggle="modal"
                     data-bs-target="#imageViewModal"
                     data-image="{{ $srcPath }}"
                     data-picture="{{ $dataPicture }}"
                     onerror="this.onerror=null; this.src='{{ $fallbackSrc }}';">
            </div>

            @if ($userdata)
                <span class="text-start ms-xl-2">
                    <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ $userdata->name }}</span>
                    @php
                        $roles = $userdata->roles->pluck('name')->implode(', ') ?? 'No Role';
                    @endphp
                    <span class="d-none d-xl-block ms-1 fs-sm user-name-sub-text">{{ $roles }}</span>
                </span>
            @endif
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end">
        <h6 class="dropdown-header">Welcome {{ $userdata->name }}!</h6>
        @php
            $userRoles = $userdata->roles->pluck('name')->implode(', ') ?? 'No Role';
        @endphp
        <p class="dropdown-item-text text-muted mb-1">Roles: {{ $userRoles }}</p>

        @if (!$isStudent)
            <a class="dropdown-item" href="{{ route('users.overview', $userdata->id) }}">
                <i class="mdi mdi-account-circle text-muted fs-lg align-middle me-1"></i>
                <span class="align-middle">Profile</span>
            </a>
        @endif

        <a class="dropdown-item" href="{{ route('profile.settings', ['id' => $userdata->id]) }}">
            <i class="mdi mdi-cog text-muted fs-lg align-middle me-1"></i>
            <span class="align-middle">Settings</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                <i class="mdi mdi-logout text-muted fs-lg align-middle me-1"></i>
                <span class="align-middle" data-key="t-logout">Logout</span>
            </a>
        </form>
    </div>
</div>

<!-- Add this CSS to your main CSS file or in a <style> tag -->
<style>
/* Enhanced Profile Picture Styles */
.header-profile-user-enhanced {
    border-radius: 12px !important;
    object-fit: cover;
    height: 48px !important;
    width: 48px !important;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 2px solid #fff;
}

.header-profile-user-enhanced:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Adjust avatar container size */
.avatar-lg {
    height: 48px;
    width: 48px;
}

/* Dark mode support */
[data-bs-theme="dark"] .header-profile-user-enhanced {
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

[data-bs-theme="dark"] .header-profile-user-enhanced:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

/* Optional: Add a subtle ring effect on hover */
@keyframes ring-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0.4);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(var(--bs-primary-rgb), 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0);
    }
}

.header-profile-user-enhanced:active {
    animation: ring-pulse 0.6s ease-out;
}
</style>



                    </div>
            </div>
        </header>

        <!-- removeNotificationModal -->
        {{-- <div id="removeNotificationModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="NotificationModalbtn-close"></button>
                    </div>
                    <div class="modal-body p-md-5">
                        <div class="text-center">
                            <div class="text-danger">
                                <i class="bi bi-trash display-4"></i>
                            </div>
                            <div class="mt-4 fs-base">
                                <h4 class="mb-1">Are you sure ?</h4>
                                <p class="text-muted mx-4 mb-0">Are you sure you want to remove this Notification ?</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                            <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn w-sm btn-danger" id="delete-notification">Yes, Delete It!</button>
                        </div>
                    </div>

                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div> --}}
        <!-- /.modal -->



          @yield('content')



        <footer class="footer">
          <div class="container-fluid">
              <div class="row">
                  <div class="col-sm-6">
                     @php $school = \App\Models\SchoolInformation::getActiveSchool();@endphp
                     <script>document.write(new Date().getFullYear())</script> © {{ $school->school_name ?? 'Vite-Eschool' }}.
                  </div>
                  <div class="col-sm-6">
                      <div class="text-sm-end d-none d-sm-block">
                          Vite-Eschool 1.0 Developed by Qudroid Systems
                      </div>
                  </div>
              </div>
          </div>
      </footer>
      </div>
      <!-- end main content-->

      </div>
      <!-- END layout-wrapper -->





      <!--start back-to-top-->
      <button class="btn btn-dark btn-icon" id="back-to-top">
      <i class="bi bi-caret-up fs-3xl"></i>
      </button>
      <!--end back-to-top-->

      <!--preloader-->
      <div id="preloader">
      <div id="status">
      <div class="spinner-border text-primary avatar-sm" role="status">
          <span class="visually-hidden">Loading...</span>
      </div>
      </div>
      </div>






      <div class="customizer-setting d-none d-md-block">
      <div class="btn btn-info p-2 text-uppercase rounded-end-0 shadow-lg" data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas" aria-controls="theme-settings-offcanvas">
      <i class="bi bi-gear mb-1"></i> Customizer
      </div>
      </div>

      <!-- Theme Settings -->
      <div class="offcanvas offcanvas-end border-0" tabindex="-1" id="theme-settings-offcanvas">
            <div class="d-flex align-items-center bg-primary bg-gradient p-3 offcanvas-header">
            <div class="me-2">
                <h5 class="mb-1 text-white">Steex Builder</h5>
                <p class="text-white text-opacity-75 mb-0">Choose your themes & layouts etc.</p>
            </div>

            <button type="button" class="btn-close btn-close-white ms-auto" id="customizerclose-btn" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0">
            <div data-simplebar class="h-100">
                <div class="p-4">
                    <h6 class="fs-md mb-1">Layout</h6>
                    <p class="text-muted fs-sm">Choose your layout</p>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-check card-radio">
                                <input id="customizer-layout01" name="data-layout" type="radio" value="vertical" class="form-check-input">
                                <label class="form-check-label p-0 avatar-md w-100" for="customizer-layout01">
                                    <span class="d-flex gap-1 h-100">
                                        <span class="flex-shrink-0">
                                            <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                            </span>
                                        </span>
                                        <span class="flex-grow-1">
                                            <span class="d-flex h-100 flex-column">
                                                <span class="bg-light d-block p-1"></span>
                                                <span class="bg-light d-block p-1 mt-auto"></span>
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Vertical</h5>
                        </div>
                        <div class="col-4">
                            <div class="form-check card-radio">
                                <input id="customizer-layout02" name="data-layout" type="radio" value="horizontal" class="form-check-input">
                                <label class="form-check-label p-0 avatar-md w-100" for="customizer-layout02">
                                    <span class="d-flex h-100 flex-column gap-1">
                                        <span class="bg-light d-flex p-1 gap-1 align-items-center">
                                            <span class="d-block p-1 bg-primary-subtle rounded me-1"></span>
                                            <span class="d-block p-1 pb-0 px-2 bg-primary-subtle ms-auto"></span>
                                            <span class="d-block p-1 pb-0 px-2 bg-primary-subtle"></span>
                                        </span>
                                        <span class="bg-light d-block p-1"></span>
                                        <span class="bg-light d-block p-1 mt-auto"></span>
                                    </span>
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Horizontal</h5>
                        </div>
                        <div class="col-4">
                            <div class="form-check card-radio">
                                <input id="customizer-layout03" name="data-layout" type="radio" value="twocolumn" class="form-check-input">
                                <label class="form-check-label p-0 avatar-md w-100" for="customizer-layout03">
                                    <span class="d-flex gap-1 h-100">
                                        <span class="flex-shrink-0">
                                            <span class="bg-light d-flex h-100 flex-column gap-1">
                                                <span class="d-block p-1 bg-primary-subtle mb-2"></span>
                                                <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                            </span>
                                        </span>
                                        <span class="flex-shrink-0">
                                            <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                            </span>
                                        </span>
                                        <span class="flex-grow-1">
                                            <span class="d-flex h-100 flex-column">
                                                <span class="bg-light d-block p-1"></span>
                                                <span class="bg-light d-block p-1 mt-auto"></span>
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Two Column</h5>
                        </div>
                        <!-- end col -->
                    </div>

                    <h6 class="mt-4 fs-md mb-1">Theme</h6>
                    <p class="text-muted fs-sm">Choose your suitable Theme.</p>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-check card-radio">
                                <input id="customizer-theme01" name="data-theme" type="radio" value="default" class="form-check-input">
                                <label class="form-check-label p-0" for="customizer-theme01">
                                    <img src="{{ asset('theme/layouts/assets/images/custom-theme/light-mode.png')}}" alt="" class="img-fluid">
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Default</h5>
                        </div>
                        <div class="col-6">
                            <div class="form-check card-radio">
                                <input id="customizer-theme02" name="data-theme" type="radio" value="material" class="form-check-input">
                                <label class="form-check-label p-0" for="customizer-theme02">
                                    <img src="{{ asset('theme/layouts/assets/images/custom-theme/material.png')}}" alt="" class="img-fluid">
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Material</h5>
                        </div>
                        <div class="col-6">
                            <div class="form-check card-radio">
                                <input id="customizer-theme03" name="data-theme" type="radio" value="creative" class="form-check-input">
                                <label class="form-check-label p-0" for="customizer-theme03">
                                    <img src="{{ asset('theme/layouts/assets/images/custom-theme/creative.png')}}" alt="" class="img-fluid">
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Creative</h5>
                        </div>
                        <div class="col-6">
                            <div class="form-check card-radio">
                                <input id="customizer-theme04" name="data-theme" type="radio" value="minimal" class="form-check-input">
                                <label class="form-check-label p-0" for="customizer-theme04">
                                    <img src="{{ asset('theme/layouts/assets/images/custom-theme/minimal.png')}}" alt="" class="img-fluid">
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Minimal</h5>
                        </div>
                        <div class="col-6">
                            <div class="form-check card-radio">
                                <input id="customizer-theme05" name="data-theme" type="radio" value="modern" class="form-check-input">
                                <label class="form-check-label p-0" for="customizer-theme05">
                                    <img src="{{ asset('theme/layouts/assets/images/custom-theme/modern.png')}}" alt="" class="img-fluid">
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Modern</h5>
                        </div>
                        <!-- end col -->
                        <div class="col-6">
                            <div class="form-check card-radio">
                                <input id="customizer-theme06" name="data-theme" type="radio" value="interaction" class="form-check-input">
                                <label class="form-check-label p-0" for="customizer-theme06">
                                    <img src="{{ asset('theme/layouts/assets/images/custom-theme/interaction.png')}}" alt="" class="img-fluid">
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Interaction</h5>
                        </div><!-- end col -->
                    </div>

                    <h6 class="mt-4 fs-md mb-1">Color Scheme</h6>
                    <p class="text-muted fs-sm">Choose Light or Dark Scheme.</p>

                    <div class="colorscheme-cardradio">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-mode-light" value="light">
                                    <label class="form-check-label p-0 bg-transparent" for="layout-mode-light">
                                        <img src="{{ asset('theme/layouts/assets/images/custom-theme/light-mode.png')}}" alt="" class="img-fluid">
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Light</h5>
                            </div>

                            <div class="col-6">
                                <div class="form-check card-radio dark">
                                    <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-mode-dark" value="dark">
                                    <label class="form-check-label p-0 bg-transparent" for="layout-mode-dark">
                                        <img src="{{ asset('theme/layouts/assets/images/custom-theme/dark-mode.png')}}" alt="" class="img-fluid">
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Dark</h5>
                            </div>


                        </div>
                    </div>

                    <div id="layout-width">
                        <h6 class="mt-4 fs-md mb-1">Layout Width</h6>
                        <p class="text-muted fs-sm">Choose Fluid or Boxed layout.</p>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="data-layout-width" id="layout-width-fluid" value="fluid">
                                    <label class="form-check-label p-0 avatar-md w-100" for="layout-width-fluid">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Fluid</h5>
                            </div>
                            <div class="col-4">
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="data-layout-width" id="layout-width-boxed" value="boxed">
                                    <label class="form-check-label p-0 avatar-md w-100 px-2" for="layout-width-boxed">
                                        <span class="d-flex gap-1 h-100 border-start border-end">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Boxed</h5>
                            </div>
                        </div>
                    </div>

                    <div id="layout-position">
                        <h6 class="mt-4 fs-md mb-1">Layout Position</h6>
                        <p class="text-muted fs-sm">Choose Fixed or Scrollable Layout Position.</p>

                        <div class="btn-group radio" role="group">
                            <input type="radio" class="btn-check" name="data-layout-position" id="layout-position-fixed" value="fixed">
                            <label class="btn btn-light w-sm" for="layout-position-fixed">Fixed</label>

                            <input type="radio" class="btn-check" name="data-layout-position" id="layout-position-scrollable" value="scrollable">
                            <label class="btn btn-light w-sm ms-0" for="layout-position-scrollable">Scrollable</label>
                        </div>
                    </div>

                    <h6 class="mt-4 fs-md mb-1">Topbar Color</h6>
                    <p class="text-muted fs-sm">Choose Light or Dark Topbar Color.</p>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-topbar" id="topbar-color-light" value="light">
                                <label class="form-check-label p-0 avatar-md w-100" for="topbar-color-light">
                                    <span class="d-flex gap-1 h-100">
                                        <span class="flex-shrink-0">
                                            <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                            </span>
                                        </span>
                                        <span class="flex-grow-1">
                                            <span class="d-flex h-100 flex-column">
                                                <span class="bg-light d-block p-1"></span>
                                                <span class="bg-light d-block p-1 mt-auto"></span>
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Light</h5>
                        </div>
                        <div class="col-4">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-topbar" id="topbar-color-dark" value="dark">
                                <label class="form-check-label p-0 avatar-md w-100" for="topbar-color-dark">
                                    <span class="d-flex gap-1 h-100">
                                        <span class="flex-shrink-0">
                                            <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                            </span>
                                        </span>
                                        <span class="flex-grow-1">
                                            <span class="d-flex h-100 flex-column">
                                                <span class="bg-primary d-block p-1"></span>
                                                <span class="bg-light d-block p-1 mt-auto"></span>
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Dark</h5>
                        </div>
                    </div>

                    <div id="sidebar-size">
                        <h6 class="mt-4 fs-md mb-1">Sidebar Size</h6>
                        <p class="text-muted fs-sm">Choose a size of Sidebar.</p>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-sidebar-size" id="sidebar-size-default" value="lg">
                                    <label class="form-check-label p-0 avatar-md w-100" for="sidebar-size-default">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Default</h5>
                            </div>

                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-sidebar-size" id="sidebar-size-compact" value="md">
                                    <label class="form-check-label p-0 avatar-md w-100" for="sidebar-size-compact">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 bg-primary-subtle rounded mb-2"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Compact</h5>
                            </div>

                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-sidebar-size" id="sidebar-size-small" value="sm">
                                    <label class="form-check-label p-0 avatar-md w-100" for="sidebar-size-small">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1">
                                                    <span class="d-block p-1 bg-primary-subtle mb-2"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Small (Icon View)</h5>
                            </div>

                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-sidebar-size" id="sidebar-size-small-hover" value="sm-hover">
                                    <label class="form-check-label p-0 avatar-md w-100" for="sidebar-size-small-hover">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1">
                                                    <span class="d-block p-1 bg-primary-subtle mb-2"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Small Hover View</h5>
                            </div>
                        </div>
                    </div>

                    <div id="sidebar-view">
                        <h6 class="mt-4 fs-md mb-1">Sidebar View</h6>
                        <p class="text-muted fs-sm">Choose Default or Detached Sidebar view.</p>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-layout-style" id="sidebar-view-default" value="default">
                                    <label class="form-check-label p-0 avatar-md w-100" for="sidebar-view-default">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Default</h5>
                            </div>
                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-layout-style" id="sidebar-view-detached" value="detached">
                                    <label class="form-check-label p-0 avatar-md w-100" for="sidebar-view-detached">
                                        <span class="d-flex h-100 flex-column">
                                            <span class="bg-light d-flex p-1 gap-1 align-items-center px-2">
                                                <span class="d-block p-1 bg-primary-subtle rounded me-1"></span>
                                                <span class="d-block p-1 pb-0 px-2 bg-primary-subtle ms-auto"></span>
                                                <span class="d-block p-1 pb-0 px-2 bg-primary-subtle"></span>
                                            </span>
                                            <span class="d-flex gap-1 h-100 p-1 px-2">
                                                <span class="flex-shrink-0">
                                                    <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                        <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                        <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                        <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    </span>
                                                </span>
                                            </span>
                                            <span class="bg-light d-block p-1 mt-auto px-2"></span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Detached</h5>
                            </div>
                        </div>
                    </div>
                    <div id="sidebar-color">
                        <h6 class="mt-4 fs-md mb-1">Sidebar Color</h6>
                        <p class="text-muted fs-sm">Choose a color of Sidebar.</p>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio" data-bs-toggle="collapse" data-bs-target="#collapseBgGradient.show">
                                    <input class="form-check-input" type="radio" name="data-sidebar" id="sidebar-color-light" value="light">
                                    <label class="form-check-label p-0 avatar-md w-100" for="sidebar-color-light">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-white border-end d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Light</h5>
                            </div>
                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio" data-bs-toggle="collapse" data-bs-target="#collapseBgGradient.show">
                                    <input class="form-check-input" type="radio" name="data-sidebar" id="sidebar-color-dark" value="dark">
                                    <label class="form-check-label p-0 avatar-md w-100" for="sidebar-color-dark">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-primary d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 px-2 bg-soft-light rounded mb-2"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-soft-light"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-soft-light"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-soft-light"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Dark</h5>
                            </div>
                            <div class="col-4">
                                <button class="btn btn-link avatar-md w-100 p-0 overflow-hidden border collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBgGradient" aria-expanded="false" aria-controls="collapseBgGradient">
                                    <span class="d-flex gap-1 h-100">
                                        <span class="flex-shrink-0">
                                            <span class="bg-vertical-gradient d-flex h-100 flex-column gap-1 p-1">
                                                <span class="d-block p-1 px-2 bg-soft-light rounded mb-2"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-soft-light"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-soft-light"></span>
                                                <span class="d-block p-1 px-2 pb-0 bg-soft-light"></span>
                                            </span>
                                        </span>
                                        <span class="flex-grow-1">
                                            <span class="d-flex h-100 flex-column">
                                                <span class="bg-light d-block p-1"></span>
                                                <span class="bg-light d-block p-1 mt-auto"></span>
                                            </span>
                                        </span>
                                    </span>
                                </button>
                                <h5 class="fs-sm text-center fw-medium mt-2">Gradient</h5>
                            </div>
                        </div>
                        <!-- end row -->

                        <div class="collapse" id="collapseBgGradient">
                            <div class="d-flex gap-2 flex-wrap img-switch p-2 px-3 bg-light rounded">

                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-sidebar" id="sidebar-color-gradient" value="gradient">
                                    <label class="form-check-label p-0 avatar-xs rounded-circle" for="sidebar-color-gradient">
                                        <span class="avatar-title rounded-circle bg-vertical-gradient"></span>
                                    </label>
                                </div>
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-sidebar" id="sidebar-color-gradient-2" value="gradient-2">
                                    <label class="form-check-label p-0 avatar-xs rounded-circle" for="sidebar-color-gradient-2">
                                        <span class="avatar-title rounded-circle bg-vertical-gradient-2"></span>
                                    </label>
                                </div>
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-sidebar" id="sidebar-color-gradient-3" value="gradient-3">
                                    <label class="form-check-label p-0 avatar-xs rounded-circle" for="sidebar-color-gradient-3">
                                        <span class="avatar-title rounded-circle bg-vertical-gradient-3"></span>
                                    </label>
                                </div>
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-sidebar" id="sidebar-color-gradient-4" value="gradient-4">
                                    <label class="form-check-label p-0 avatar-xs rounded-circle" for="sidebar-color-gradient-4">
                                        <span class="avatar-title rounded-circle bg-vertical-gradient-4"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="sidebar-img">
                        <h6 class="mt-4 fw-semibold fs-base">Sidebar Images</h6>
                        <p class="text-muted fs-sm">Choose a image of Sidebar.</p>

                        <div class="d-flex gap-2 flex-wrap img-switch">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidebar-image" id="sidebarimg-none" value="none">
                                <label class="form-check-label p-0 avatar-sm h-auto" for="sidebarimg-none">
                                    <span class="avatar-md w-auto bg-light d-flex align-items-center justify-content-center">
                                        <i class="ri-close-fill fs-3xl"></i>
                                    </span>
                                </label>
                            </div>

                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidebar-image" id="sidebarimg-01" value="img-1">
                                <label class="form-check-label p-0 avatar-sm h-auto" for="sidebarimg-01">
                                    <img src="{{ asset('theme/layouts/assets/images/sidebar/img-sm-1.jpg')}}" alt="" class="avatar-md w-auto object-cover">
                                </label>
                            </div>

                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidebar-image" id="sidebarimg-02" value="img-2">
                                <label class="form-check-label p-0 avatar-sm h-auto" for="sidebarimg-02">
                                    <img src="{{ asset('theme/layouts/assets/images/sidebar/img-sm-2.jpg')}}" alt="" class="avatar-md w-auto object-cover">
                                </label>
                            </div>
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidebar-image" id="sidebarimg-03" value="img-3">
                                <label class="form-check-label p-0 avatar-sm h-auto" for="sidebarimg-03">
                                    <img src="{{ asset('theme/layouts/assets/images/sidebar/img-sm-3.jpg')}}" alt="" class="avatar-md w-auto object-cover">
                                </label>
                            </div>
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidebar-image" id="sidebarimg-04" value="img-4">
                                <label class="form-check-label p-0 avatar-sm h-auto" for="sidebarimg-04">
                                    <img src="{{ asset('theme/layouts/assets/images/sidebar/img-sm-4.jpg')}}" alt="" class="avatar-md w-auto object-cover">
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="preloader-menu">
                        <h6 class="mt-4 fw-semibold fs-base">Preloader</h6>
                        <p class="text-muted fs-sm">Choose a preloader.</p>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-preloader" id="preloader-view-custom" value="enable">
                                    <label class="form-check-label p-0 avatar-md w-100" for="preloader-view-custom">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                        <!-- <div id="preloader"> -->
                                        <span class="d-flex align-items-center justify-content-center">
                                            <span class="spinner-border text-primary avatar-xxs m-auto" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </span>
                                        </span>
                                        <!-- </div> -->
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Enable</h5>
                            </div>
                            <div class="col-4">
                                <div class="form-check sidebar-setting card-radio">
                                    <input class="form-check-input" type="radio" name="data-preloader" id="preloader-view-none" value="disable">
                                    <label class="form-check-label p-0 avatar-md w-100" for="preloader-view-none">
                                        <span class="d-flex gap-1 h-100">
                                            <span class="flex-shrink-0">
                                                <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                                                    <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                    <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                                                </span>
                                            </span>
                                            <span class="flex-grow-1">
                                                <span class="d-flex h-100 flex-column">
                                                    <span class="bg-light d-block p-1"></span>
                                                    <span class="bg-light d-block p-1 mt-auto"></span>
                                                </span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <h5 class="fs-sm text-center fw-medium mt-2">Disable</h5>
                            </div>
                        </div>

                    </div><!-- end preloader-menu -->
                </div>
            </div>

            </div>
            <div class="offcanvas-footer border-top p-3 text-center">
            <div class="row">
                <div class="col-6">
                    <button type="button" class="btn btn-light w-100" id="reset-layout">Reset</button>
                </div>

            </div>
            </div>
      </div>


      @if (Route::is('dashboard'))
            @include('layouts.pages-assets.js.dashboard-list-js')
      @endif

      @if (Route::is('users.*'))
            @include('layouts.pages-assets.js.users-list-js')
      @endif

      @if (Route::is('profile.*'))
            @include('layouts.pages-assets.js.users-list-js')
      @endif

      @if (Route::is('roles.*'))
             @include('layouts.pages-assets.js.role-list-js')
      @endif

      @if (Route::is('permissions.*'))
            @include('layouts.pages-assets.js.permissions-list-js')
      @endif

      @if (Route::is('session.*'))
           @include('layouts.pages-assets.js.session-list-js')
      @endif

      @if (Route::is('term.*'))
         @include('layouts.pages-assets.js.term-list-js')
      @endif

      @if (Route::is('school-information.*'))
            @include('layouts.pages-assets.js.schoolinformation-list-js')
      @endif

         @if (Route::is('admin.school-info.*'))
            @include('layouts.pages-assets.js.schoolinformation-list-js')
      @endif

      @if (Route::is('schoolhouse.*'))
         @include('layouts.pages-assets.js.schoolhouse-list-js')
      @endif

      @if (Route::is('schoolarm.*'))
          @include('layouts.pages-assets.js.arm-list-js')
      @endif

      @if (Route::is('classcategories.*'))
            @include('layouts.pages-assets.js.classcategory-list-js')
      @endif

      @if (Route::is('schoolclass.*'))
           @include('layouts.pages-assets.js.schoolclass-list-js')
      @endif

      @if (Route::is('classteacher.*'))
            @include('layouts.pages-assets.js.classteacher-list-js')
      @endif

      @if (Route::is('subject.*'))
            @include('layouts.pages-assets.js.subject-list-js')
      @endif

      @if (Route::is('subjects.*'))
           @include('layouts.pages-assets.js.subject-list-js')
      @endif

      @if (Route::is('subjectteacher.*'))
            @include('layouts.pages-assets.js.subjectteacher-list-js')
      @endif

      @if (Route::is('subjectclass.*'))
            @include('layouts.pages-assets.js.subjectclass-list-js')
      @endif

      @if (Route::is('schoolbill.*'))
            @include('layouts.pages-assets.js.schoolbill-list-js')
      @endif

      @if (Route::is('schoolbilltermsession.*'))
            @include('layouts.pages-assets.js.schoolbilltermsession-list-js')
      @endif

      @if (Route::is('student.*'))
             @include('layouts.pages-assets.js.student-list-js')
      @endif

      @if (Route::is('studentbatchindex'))
             @include('layouts.pages-assets.js.studentbatch-list-js')
      @endif

      @if (Route::is('myclass.*'))
          @include('layouts.pages-assets.js.myclass-list-js')
      @endif

      @if (Route::is('mysubject.*'))
        @include('layouts.pages-assets.js.mysubject-list-js')
      @endif

      @if (Route::is('viewstudent'))
          @include('layouts.pages-assets.js.viewstudent-list-js')
      @endif

       @if (Route::is('studentreports.*'))
          @include('layouts.pages-assets.js.studentreport-list-js')
      @endif

      @if (Route::is('studentmockreports.*'))
          @include('layouts.pages-assets.js.studentmockreport-list-js')
      @endif

      @if (Route::is('subjectoperation.*'))
          @include('layouts.pages-assets.js.subjectoperation-list-js')
      @endif

      @if (Route::is('subjects.subjectinfo'))
          @include('layouts.pages-assets.js.subjectinfo-list-js')
      @endif

      @if (Route::is('myresultroom.*'))
            @include('layouts.pages-assets.js.myresultroom-list-js')
      @endif

      @if (Route::is('subjectscoresheet'))
            @include('layouts.pages-assets.js.subjectscoresheet-list-js')
      @endif

      @if (Route::is('subjectscoresheet-mock.*'))
        @include('layouts.pages-assets.js.subjectscoresheet-mock-list-js')
      @endif

      @if (Route::is('studentresults*'))
            @include('layouts.pages-assets.js.studentresults-list-js')
      @endif

      @if (Route::is('schoolbill*'))
            @include('layouts.pages-assets.js.schoolbill-list-js')
      @endif

      @if (Route::is('schoolpayment*'))
            @include('layouts.pages-assets.js.schoolpayment-list-js')
      @endif

      @if (Route::is('analysis*'))
           @include('layouts.pages-assets.js.analysis-list-js')
      @endif

      @if (Route::is('exams*'))
            @include('layouts.pages-assets.js.exams-list-js')
      @endif

      @if (Route::is('questions*'))
            @include('layouts.pages-assets.js.questions-list-js')
      @endif

      @if (Route::is('cbt*'))
            @include('layouts.pages-assets.js.cbt-list-js')
      @endif

      @if (Route::is('classbroadsheet.*'))
            @include('layouts.pages-assets.js.classbroadsheet-list-js')
      @endif

      @if (Route::is('principalscomment.*'))
            @include('layouts.pages-assets.js.principalscomment-list-js')
      @endif

      @if (Route::is('myprincipalscomment.*'))
            @include('layouts.pages-assets.js.myprincipalscomment-list-js')
      @endif

      @if (Route::is('compulsorysubjectclass.*'))
            @include('layouts.pages-assets.js.compulsorysubjectclass-list-js')
      @endif

      @if (Route::is('subjectvetting.*'))
            @include('layouts.pages-assets.js.subjectvetting-list-js')
      @endif

      @if (Route::is('mocksubjectvetting.*'))
            @include('layouts.pages-assets.js.mocksubjectvetting-list-js')
      @endif

      @if (Route::is('mysubjectvettings.*'))
            @include('layouts.pages-assets.js.mysubjectvettings-list-js')
      @endif


      @if (Route::is('mymocksubjectvettings.*'))
            @include('layouts.pages-assets.js.mymocksubjectvetting-list-js')
      @endif


       @if (Route::is('promotions.*'))
            @include('layouts.pages-assets.js.promotions-list-js')
      @endif

      </body>

      </html>
