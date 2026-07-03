<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title>Sign In | {{ $school->school_name ?? 'Vite-ESchool' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="school App" name="description">
    <meta content="Themesbrand" name="author">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ $schoolInfo?->app_logo_url ?? $schoolInfo?->logo_url ?? asset('theme/layouts/assets/images/favicon.ico') }}">
    <!-- School Logo for browsers that support it -->
    <link rel="icon" type="image/png" href="{{ $schoolInfo?->getLogoUrlAttribute() ?? asset('theme/layouts/assets/images/logo-dark.png') }}">

    <!-- Fonts css load -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link id="fontsLink" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">

    <!-- Layout config Js -->
    <script src="{{ asset('theme/layouts/assets/js/layout.js')}}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css">
    <!-- Icons Css -->
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css">
    <!-- App Css-->
    <link href="{{ asset('theme/layouts/assets/css/app.min.css')}}" rel="stylesheet" type="text/css">
    <!-- custom Css-->
    <link href="{{ asset('theme/layouts/assets/css/custom.min.css')}}" rel="stylesheet" type="text/css">

    <style>
        @media (max-width: 576px) {
            .auth-effect-main {
                width: 200px;
                height: 200px;
            }
            .auth-user-list li {
                width: 40px;
                height: 40px;
            }
            .auth-user-list li:nth-child(1) { transform: translate(80px, 0); }
            .auth-user-list li:nth-child(2) { transform: rotate(72deg) translate(78px, 0); }
            .auth-user-list li:nth-child(3) { transform: rotate(144deg) translate(82px, 0); }
            .auth-user-list li:nth-child(4) { transform: rotate(216deg) translate(79px, 0); }
            .auth-user-list li:nth-child(5) { transform: rotate(288deg) translate(81px, 0); }
        }

        @media (max-width: 576px) {
            .avatar-tooltip {
                font-size: 12px;
                padding: 3px 8px;
                bottom: 50px;
            }
        }

        /* School logo styling */
        .school-login-logo {
            height: 50px;
            width: auto;
            border-radius: 8px;
            object-fit: contain;
            margin-bottom: 20px;
        }

        /* Logo container */
        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }

        /* Ensure the parent container is positioned relatively to act as the reference point */
        .auth-effect-main {
            position: relative;
            width: 300px;
            height: 300px;
        }

        /* Style the auth-user-list to be a container for orbiting avatars */
        .auth-user-list {
            position: absolute;
            width: 100%;
            height: 100%;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        /* Style each avatar item */
        .auth-user-list li {
            position: absolute;
            width: 50px;
            height: 50px;
            transform-origin: center center;
            cursor: pointer;
            transition: transform 0.3s ease, z-index 0.3s ease;
        }

        /* Define animations for each avatar with different directions, speeds, and radii */
        .auth-user-list li:nth-child(1) {
            animation: orbit-clockwise 9s linear infinite;
            transform: translate(120px, 0);
        }

        .auth-user-list li:nth-child(2) {
            animation: orbit-counterclockwise 11s linear infinite;
            transform: rotate(72deg) translate(115px, 0);
        }

        .auth-user-list li:nth-child(3) {
            animation: orbit-clockwise 10s linear infinite;
            transform: rotate(144deg) translate(125px, 0);
        }

        .auth-user-list li:nth-child(4) {
            animation: orbit-counterclockwise 8s linear infinite;
            transform: rotate(216deg) translate(118px, 0);
        }

        .auth-user-list li:nth-child(5) {
            animation: orbit-clockwise 12s linear infinite;
            transform: rotate(288deg) translate(122px, 0);
        }

        /* Pause animation on hover */
        .auth-user-list li:hover {
            animation-play-state: paused !important;
            transform: scale(1.2) !important;
            z-index: 10;
        }

        /* Glow effect on hover */
        .auth-user-list li:hover .avatar-title {
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.7);
        }

        /* Keyframes for clockwise orbit */
        @keyframes orbit-clockwise {
            from {
                transform: rotate(0deg) translate(120px, 0) rotate(0deg);
            }
            to {
                transform: rotate(360deg) translate(120px, 0) rotate(-360deg);
            }
        }

        /* Keyframes for counterclockwise orbit */
        @keyframes orbit-counterclockwise {
            from {
                transform: rotate(0deg) translate(120px, 0) rotate(0deg);
            }
            to {
                transform: rotate(-360deg) translate(120px, 0) rotate(360deg);
            }
        }

        /* Ensure avatars remain circular and visible */
        .avatar-sm {
            width: 50px;
            height: 50px;
        }

        .avatar-title {
            width: 100%;
            height: 100%;
            overflow: hidden;
            border: 2px solid white;
            transition: box-shadow 0.3s ease;
        }

        .avatar-title img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-tooltip {
            background-color: #6c757d;
            color: #fff;
            border: 1px solid #fff;
        }

        /* Login form styling */
        .login-form-container {
            padding: 40px 20px;
        }

        /* No staff online message */
        .no-staff-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }

        .no-staff-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    @php
        use App\Models\SchoolInformation;
        use App\Models\User;

        $schoolInfo = SchoolInformation::getActiveSchool();

        // Get recently active staff users (based on updated_at - last 7 days)
        $recentStaff = User::whereHas('roles', function($query) {
                $query->where('name', 'staff');
            })
            ->with(['staffPicture'])
            ->where('updated_at', '>=', now()->subDays(7)) // Staff active in last 7 days
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // If no recently active staff, show empty
        if($recentStaff->isEmpty()) {
            $recentStaff = collect([]);
        }
    @endphp

    <section class="auth-page-wrapper position-relative d-flex align-items-center justify-content-center min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-11">
                    <div class="card mb-0">
                        <div class="row g-0 align-items-center">
                            <div class="col-xxl-5">
                                <div class="card auth-card bg-secondary h-100 border-0 shadow-none d-none d-sm-block mb-0">
                                    <div class="card-body py-5 d-flex justify-content-between flex-column">
                                        <div class="text-center">
                                            <h3 class="text-white">Start your journey with us.</h3>
                                            <p class="text-white opacity-75 fs-base">It makes school operations SEAMLESS...</p>
                                        </div>

                                        <div class="auth-effect-main my-5 position-relative rounded-circle d-flex align-items-center justify-content-center mx-auto">
                                            <div class="effect-circle-1 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center">
                                                <div class="effect-circle-2 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center">
                                                    <div class="effect-circle-3 mx-auto rounded-circle position-relative text-white fs-4xl d-flex align-items-center justify-content-center">
                                                       <span class="text-primary ms-1">Vite-eSchool 1.1</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <ul class="auth-user-list list-unstyled">
                                                @if($recentStaff->isNotEmpty())
                                                    @foreach($recentStaff as $staff)
                                                        <li>
                                                            <a href="{{ route('users.show', $staff->id) }}"
                                                               class="avatar-sm d-inline-block"
                                                               data-bs-toggle="tooltip"
                                                               data-bs-placement="top"
                                                               title="{{ $staff->name }}">
                                                                <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                                    @php
                                                                        // Simplified avatar check
                                                                        $avatarUrl = $staff->avatar
                                                                            ? asset('storage/staff_avatars/' . $staff->avatar)
                                                                            : ($staff->staffPicture?->picture
                                                                                ? asset('storage/staff_avatars/' . $staff->staffPicture->picture)
                                                                                : asset('theme/layouts/assets/images/users/avatar-default.jpg'));
                                                                    @endphp

                                                                    <img src="{{ $avatarUrl }}"
                                                                         alt="{{ $staff->name }}"
                                                                         class="img-fluid"
                                                                         style="width: 100%; height: 100%; object-fit: cover;"
                                                                         onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/users/avatar-default.jpg') }}'">
                                                                </div>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                @else
                                                    <!-- Show message when no staff are active -->
                                                    <div class="no-staff-message">
                                                        <i class="ri-user-line no-staff-icon"></i>
                                                        <p class="text-white opacity-75">No active staff</p>
                                                    </div>
                                                @endif
                                            </ul>
                                        </div>

                                        <div class="text-center">
                                            <p class="text-white opacity-75 mb-0 mt-3">
                                                © <script>document.write(new Date().getFullYear())</script> {{ $schoolInfo?->school_name ?? 'Vite-ESchool' }}. Created with <i class="mdi mdi-heart text-danger"></i> by Qudroid Systems
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end col-->

                            <div class="col-xxl-6 mx-auto">
                                <div class="card mb-0 border-0 shadow-none mb-0">
                                    <div class="card-body p-sm-5 m-lg-4">
                                        <!-- School Logo on Login Form -->
                                     
                                            <div class="logo-container">
                                                @if($schoolInfo?->app_logo_url)
                                                    <img src="{{ $schoolInfo->app_logo_url }}"
                                                        alt="{{ $schoolInfo->school_name }}"
                                                        class="school-login-logo"
                                                        onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/logo-dark.png') }}'">
                                                @elseif($schoolInfo?->logo_url)
                                                    <img src="{{ $schoolInfo->logo_url }}"
                                                        alt="{{ $schoolInfo->school_name }}"
                                                        class="school-login-logo"
                                                        onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/logo-dark.png') }}'">
                                                @else
                                                    <img src="{{ asset('theme/layouts/assets/images/logo-dark.png') }}"
                                                        alt="School Logo"
                                                        class="school-login-logo">
                                                @endif
                                            </div>
                                            <div class="text-center mt-3">
                                            <h5 class="fs-3xl">{{ $schoolInfo?->school_name ?? 'TopClass College' }} Portal</h5>
                                            <p class="text-muted">Sign in to continue</p>
                                        </div>
                                        <div class="p-2 mt-3">
                                            <form method="POST" action="{{ route('login') }}">
                                                @csrf

                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Email <span class="text-danger">*</span></label>
                                                    <div class="position-relative">
                                                        <input type="email" class="form-control password-input @error('email') is-invalid @enderror" id="email" name="email" placeholder="Enter email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                                        @error('email')
                                                            <span class="invalid-feedback" role="alert">
                                                                <strong>{{ $message }}</strong>
                                                            </span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    @if (Route::has('password.request'))
                                                        <div class="float-end">
                                                            <a href="{{ route('password.request') }}" class="text-muted">Forgot password?</a>
                                                        </div>
                                                    @endif
                                                    <label class="form-label" for="password-input">Password <span class="text-danger">*</span></label>
                                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                                        <input type="password" id="password" class="form-control pe-5 password-input @error('password') is-invalid @enderror" name="password" autocomplete="current-password" placeholder="Enter password" id="password-input" required>
                                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon">
                                                            <i class="ri-eye-fill align-middle"></i>
                                                        </button>
                                                    </div>
                                                    @error('password')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="auth-remember-check">Remember me</label>
                                                </div>

                                                <div class="mt-4">
                                                    <button class="btn btn-primary w-100" type="submit">Sign In</button>
                                                </div>
                                            </form>

                                            <div class="text-center mt-5">
                                                <p class="mb-0">Don't have an account? <a href="{{ route('register') }}" class="fw-semibold text-secondary text-decoration-underline"> Sign Up</a></p>
                                            </div>
                                        </div>
                                    </div><!-- end card body -->
                                </div><!-- end card -->
                            </div>
                            <!--end col-->
                        </div>
                        <!--end row-->
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{ asset('theme/layouts/assets/libs/simplebar/simplebar.min.js')}}"></script>
    <script src="{{ asset('theme/layouts/assets/js/plugins.js')}}"></script>
    <script src="{{ asset('theme/layouts/assets/js/pages/password-addon.init.js')}}"></script>

    <!--Swiper slider js-->
    <script src="{{ asset('theme/layouts/assets/libs/swiper/swiper-bundle.min.js')}}"></script>

    <!-- swiper.init js -->
    <script src="{{ asset('theme/layouts/assets/js/pages/swiper.init.js')}}"></script>

    <script>
        // Initialize Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Enhanced hover pause functionality
            const avatarItems = document.querySelectorAll('.auth-user-list li');

            avatarItems.forEach(item => {
                // Pause this avatar on hover
                item.addEventListener('mouseenter', function() {
                    this.style.animationPlayState = 'paused';
                });

                // Resume this avatar when mouse leaves
                item.addEventListener('mouseleave', function() {
                    this.style.animationPlayState = 'running';
                });
            });
        });
    </script>
</body>
</html>
