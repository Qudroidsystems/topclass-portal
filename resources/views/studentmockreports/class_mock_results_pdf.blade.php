<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mock Results - {{ $metadata['class_name'] }} - {{ $metadata['session'] }} - {{ $metadata['term'] }}</title>
    <style>
        /* Basic reset and font setup */
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            margin: 10mm 0 0 0;
            padding: 0;
            text-align: center;
        }

        .student-section {
            width: 190mm;
            max-height: 287mm;
            page-break-after: always;
            background: #ffffff;
            border: 3px double #000000;
            margin: 0 auto;
            padding: 12px;
            position: relative;
            overflow: hidden;
            text-align: left;
        }

        .student-section:last-child {
            page-break-after: avoid;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                text-align: center;
            }
            
            .student-section {
                width: 190mm;
                max-height: 287mm;
                margin: 0 auto;
                padding: 10mm;
                page-break-after: always;
                text-align: left;
            }
            
            .student-section:last-child {
                page-break-after: avoid;
            }
        }

        .fraction {
            display: inline-block;
            font-family: Arial, sans-serif;
            font-size: 8px;
            text-align: center;
            font-weight: bold;
        }

        .fraction .numerator {
            border-bottom: 2px solid #333;
            padding: 0 3px;
            display: block;
        }

        .fraction .denominator {
            padding-top: 3px;
            display: block;
        }

        span.text-space-on-dots,
        span.text-dot-space2 {
            border-bottom: 1px dotted #666;
            display: inline-block;
            min-height: 14px;
            font-weight: bold;
            font-size: 12px;
        }

        span.text-space-on-dots {
            width: 250px;
        }

        span.text-dot-space2 {
            width: 150px;
        }

        .school-name2 {
            font-size: 22px;
            font-weight: 900;
            color: #000000;
            text-align: left;
            margin: 1px 0;
            line-height: 1.2;
        }

        .school-logo {
            width: 100px;
            height: 100px;
            border: 0px solid #1e40af;
            border-radius: 1px;
            overflow: hidden;
            text-align: center;
        }

        .header-divider {
            width: 100%;
            height: 2px;
            background: #1e40af;
            margin: 4px 0;
        }

        .header-divider2 {
            width: 100%;
            height: 1px;
            background: #64748b;
            margin: 2px 0;
        }

        .report-title {
            background: #111827;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            margin: 8px 0;
        }

        .header {
            margin-bottom: 6px;
        }

        .header-table {
            width: 100%;
            table-layout: fixed;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0;
        }

        .header-img {
            width: 100%;
            height: 100%;
            border-radius: 1px;
        }

        .school-motto, .school-address, .school-phone, .school-website, .school-email {
            font-size: 11px;
            font-weight: 900;
            color: #000000;
            margin: 1px 0;
            text-align: left;
            line-height: 1.2;
        }

        .student-info-section {
            margin-bottom: 4px;
        }

        .result-details {
            font-size: 10px;
            font-weight: 800;
            color: #000000;
        }

        .info-value {
            font-size: 11px;
            font-weight: 900;
            color: #000000;
        }

        .photo-frame {
            border: 3px solid #090909;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            padding: 2px;
            width: 80px;
            height: 100px;
            margin: 0 auto;
            text-align: center;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
        }

        .result-table table {
            width: 100%;
            border: 2px solid #000000;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .result-table thead th {
            background: #0d1a3d;
            color: white;
            font-weight: 800;
            border: 1px solid #000000;
            padding: 6px 3px;
            text-align: center;
            font-size: 8px;
        }

        .result-table thead th:nth-child(3),
        .result-table thead th:nth-child(4) {
            width: 50px;
        }

        .result-table thead th:nth-child(5),
        .result-table thead th:nth-child(6) {
            width: 60px;
        }

        .result-table tbody tr {
            font-weight: 800;
        }

        .result-table tbody td {
            border: 1px solid #000000;
            padding: 4px 3px;
            text-align: center;
            font-size: 10px;
            background: white;
            font-weight: 900;
        }

        .result-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .result-table tbody td.subject-name {
            text-align: left !important;
            font-weight: 600;
        }

        .highlight-red {
            color: #dc2626 !important;
            font-weight: 900;
        }

        .grade-display {
            background: #fbbf24;
            color: white;
            border-radius: 10px;
            padding: 6px;
            text-align: center;
            margin-bottom: 8px;
        }

        .grade-display span {
            font-size: 9px;
            font-weight: 600;
            margin: 0 4px;
        }

        .remarks-table {
            width: 100%;
            border: 2px solid #000000;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .remarks-table td {
            border: 1px solid #000000;
            padding: 6px;
            background: white;
            vertical-align: top;
        }

        .remarks-table .h6 {
            color: #050505;
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .remarks-table .text-space-on-dots {
            color: #000000;
            font-weight: bold;
        }

        .footer-section {
            background: #f1f5f9;
            border-radius: 6px;
            padding: 8px;
            border: 1px solid #cbd5e1;
            text-align: center;
            margin-top: 6px;
        }

        .h5 {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #047857;
        }

        .student-info-table {
            width: 100%;
            margin-bottom: 4px;
            table-layout: fixed;
        }

        .student-info-table td {
            padding: 1px;
            vertical-align: top;
        }

        .footer-layout-table {
            width: 100%;
        }

        .footer-layout-table td {
            padding: 3px;
            text-align: center;
        }

        .info-row {
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .info-row .result-details {
            margin-right: 4px;
        }

        .info-row.students-count {
            margin-top: 2px;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: 900;
        }

        .text-primary {
            color: #02175e;
        }

        .student-section-inner {
            width: 100%;
            height: auto;
        }

        .powered-by {
            font-size: 12px;
            color: #000000;
            font-weight: 700;
            margin-top: 6px;
        }

        .promotion-status {
            font-weight: 900 !important;
            margin-left: 5px;
            font-size: 10px !important;
        }

        .remarks-table .promotion-status.promotion-promoted,
        .promotion-status.promotion-promoted {
            color: #1e40af !important;
        }

        .remarks-table .promotion-status.promotion-repeat,
        .promotion-status.promotion-repeat {
            color: #dc2626 !important;
        }

        .remarks-table .promotion-status.promotion-parents,
        .promotion-status.promotion-parents {
            color: #dc2626 !important;
        }

        .remarks-table .promotion-status.promotion-default,
        .promotion-status.promotion-default {
            color: #6b7280 !important;
        }
    </style>
</head>
<body>
    @foreach ($allStudentData as $index => $studentData)
        <div class="student-section">
            <div class="student-section-inner">
                <!-- Header Section -->
                <div class="header">
                    @php
                        $schoolInfo = $studentData['schoolInfo'] ?? null;
                        $student = $studentData['students'] && $studentData['students']->isNotEmpty() ? $studentData['students']->first() : null;
                    @endphp
                    <table class="header-table">
                        <tr>
                            <td width="25%">
                                <div class="school-logo">
                                     <img class="header-img" src="{{ $studentData['school_logo_path'] ?? public_path('storage/school_logos/default.jpg') }}" alt="School Logo">
                                </div>
                            </td>
                            <td width="50%">
                                <div class="info-row">
                                    <p class="school-name2">{{ $schoolInfo->school_name ?? 'QUDROID SYSTEMS' }}</p>
                                </div>
                                <div class="info-row">
                                    <span class="result-details">Motto:</span>
                                    <span class="info-value font-bold">{{ $schoolInfo->school_motto ?? 'NO INFO' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="result-details">Address:</span>
                                    <span class="info-value font-bold">{{ $schoolInfo->school_address ?? 'NO INFO' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="result-details">Phone:</span>
                                    <span class="info-value font-bold">{{ $schoolInfo->school_phone ?? 'NO INFO' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="result-details">Email:</span>
                                    <span class="info-value font-bold">{{ $schoolInfo->school_email ?? 'NO INFO' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="result-details">Website:</span>
                                    <span class="info-value font-bold">{{ $schoolInfo->school_website ?? 'NO INFO' }}</span>
                                </div>
                            </td>
                            <td width="25%">
                                <div class="photo-frame">
                                     @if ($studentData['students'] && $studentData['students']->isNotEmpty() && $student->picture)
                                        <img src="{{ $studentData['student_image_path'] ?? public_path('storage/student_avatars/unnamed.jpg') }}" alt="{{ $student->fname ?? 'Student' }}'s picture">
                                    @else
                                        <img src="{{ public_path('storage/student_avatars/unnamed.jpg') }}" alt="Default Photo">
                                    @endif
                            </td>
                        </tr>
                    </table>
                    <div class="header-divider"></div>
                    <div class="header-divider2"></div>
                    <div class="report-title">{{ strtoupper($metadata['term']) }} {{ strtoupper($metadata['session']) }} MOCK PROGRESS REPORT</div>
                </div>

                <!-- Student Information Section -->
                <div class="student-info-section">
                    <table class="student-info-table">
                        <tr>
                            <td width="100%">
                                @if ($studentData['students'] && $studentData['students']->isNotEmpty())
                                    @php 
                                        $student = $studentData['students']->first();
                                        $profile = $studentData['studentpp'] && $studentData['studentpp']->isNotEmpty() ? $studentData['studentpp']->first() : null;
                                    @endphp
                                    <table style="width: 100%; table-layout: fixed;">
                                        <tr>
                                            <td width="41%">
                                                <div class="info-row">
                                                    <span class="result-details">Name:</span>
                                                    <span class="info-value font-bold">{{ strtoupper($student->lastname ?? 'N/A') }} {{ $student->fname ?? 'N/A' }} {{ $student->othername ?? 'N/A' }}</span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="result-details">Session:</span>
                                                    <span class="info-value font-bold">{{ $studentData['schoolsession'] ?? 'NO INFO' }}</span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="result-details">Term:</span>
                                                    <span class="info-value font-bold">{{ $studentData['schoolterm'] ?? 'NO INFO' }}</span>
                                                </div>
                                            </td>
                                            <td width="29%">
                                                <div class="info-row">
                                                    <span class="result-details">Class:</span>
                                                    <span class="info-value font-bold">{{ $studentData['schoolclass']->schoolclass ?? 'NO INFO' }} {{ $studentData['schoolclass']->armRelation->arm ?? 'NO INFO' }}</span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="result-details">DOB:</span>
                                                    <span class="info-value font-bold">
                                                        @php
                                                            $dob = $student->dateofbirth ?? null;
                                                            $formattedDob = 'NO INFO';
                                                            if ($dob) {
                                                                try {
                                                                    if (is_numeric($dob)) {
                                                                        $unixTimestamp = ($dob - 25569) * 86400;
                                                                        $formattedDob = date('jS F, Y', $unixTimestamp);
                                                                    } else {
                                                                        $formattedDob = \Carbon\Carbon::parse($dob)->format('jS F, Y');
                                                                    }
                                                                } catch (\Exception $e) {
                                                                    $formattedDob = $dob;
                                                                }
                                                            }
                                                        @endphp
                                                        {{ $formattedDob }}
                                                    </span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="result-details">Adm No:</span>
                                                    <span class="info-value font-bold">{{ $student->admissionNo ?? 'NO INFO' }}</span>
                                                </div>
                                            </td>
                                            <td width="30%">
                                                <div class="info-row">
                                                    <span class="result-details">Sex:</span>
                                                    <span class="info-value font-bold">{{ $student->gender ?? 'NO INFO' }}</span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="result-details">Date School Opened:</span>
                                                    <span class="info-value font-bold">
                                                        @php
                                                            $dateSchoolOpened = $schoolInfo->date_school_opened ?? null;
                                                            $formattedDateSchoolOpened = 'NO INFO';
                                                            if ($dateSchoolOpened) {
                                                                try {
                                                                    $formattedDateSchoolOpened = \Carbon\Carbon::parse($dateSchoolOpened)->format('jS F, Y');
                                                                } catch (\Exception $e) {
                                                                    $formattedDateSchoolOpened = $dateSchoolOpened;
                                                                }
                                                            }
                                                        @endphp
                                                        {{ $formattedDateSchoolOpened }}
                                                    </span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="result-details">Times School Opened:</span>
                                                    <span class="info-value font-bold">{{ $schoolInfo->no_of_times_school_opened ?? 'NO INFO' }}</span>
                                                </div>
                                                <div class="info-row students-count">
                                                    <span class="result-details">Students in Class:</span>
                                                    <span class="info-value font-bold">{{ $studentData['numberOfStudents'] ?? 'NO INFO' }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="info-row">
                                        <span class="result-details">No student data available.</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Results Table -->
                <div class="result-table">
                    <table>
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Subjects</th>
                                <th>Exam Score</th>
                                <th>Total Score</th>
                                <th>Grade</th>
                                <th>Position</th>
                                <th>Class Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($studentData['mockScores'] as $index => $score)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="subject-name">{{ $score->subject_name ?? 'NO INFO' }}</td>
                                    <td class="@if ($score->exam < 50 && is_numeric($score->exam)) highlight-red @endif">{{ $score->exam ?? '-' }}</td>
                                    <td class="@if ($score->total < 50 && is_numeric($score->total)) highlight-red @endif">{{ $score->total ?? '-' }}</td>
                                    <td class="@if (in_array($score->grade ?? '', ['F', 'F9', 'E', 'E8'])) highlight-red @endif">{{ $score->grade ?? '-' }}</td>
                                    <td>{{ $score->position ?? '-' }}</td>
                                    <td>{{ $score->class_average ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">No scores available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Grade Display -->
                {{-- <div class="grade-display">
                    <span>A: 70-100</span>
                    <span>B: 60-69</span>
                    <span>C: 50-59</span>
                    <span>D: 40-49</span>
                    <span>F: 0-39</span>
                </div> --}}

                <!-- Remarks Section -->
                <table class="remarks-table">
                    <tbody>
                        <tr>
                            <td width="50%">
                                <div class="h6">Class Teacher's Remark Signature/Date</div>
                                <div>
                                    <span class="text-space-on-dots">{{ $profile ? ($profile->classteachercomment ?? 'NO INFO') : 'NO INFO' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="50%">
                                <div class="h6">Guidance Counselor's Remark Signature/Date</div>
                                <div>
                                    <span class="text-space-on-dots">{{ $profile ? ($profile->guidancescomment ?? 'NO INFO') : 'NO INFO' }}</span>
                                </div>
                            </td>
                            <td width="50%">
                                <div class="h6">Principal's Remark & Promotion Status</div>
                                {{-- <div>
                                    <span class="text-space-on-dots">
                                        {{ $profile ? ($profile->principalscomment ?? 'NO INFO') : 'NO INFO' }}
                                        @php
                                            $status = $studentData['promotionStatusValue'] ?? null;
                                            $statusUpper = strtoupper(trim($status ?? ''));
                                            $statusClass = 'promotion-default';
                                            if (str_contains($statusUpper, 'PROMOTED') && !str_contains($statusUpper, 'TRIAL')) {
                                                $statusClass = 'promotion-promoted';
                                            } elseif (str_contains($statusUpper, 'TRIAL') || str_contains($statusUpper, 'PROMOTED ON TRIAL')) {
                                                $statusClass = 'promotion-repeat';
                                            } elseif (str_contains($statusUpper, 'REPEAT')) {
                                                $statusClass = 'promotion-repeat';
                                            } elseif (str_contains($statusUpper, 'PRINCIPAL') || str_contains($statusUpper, 'PARENTS')) {
                                                $statusClass = 'promotion-parents';
                                            }
                                            $statusText = $status ?? 'Not applicable for this term';
                                        @endphp
                                        <br>
                                        <span class="promotion-status {{ $statusClass }}">
                                            PROMOTION STATUS: {{ $statusText }}
                                        </span>
                                    </span>
                                </div> --}}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer Section -->
                <div class="footer-section">
                    <table class="footer-layout-table">
                        <tr>
                            <td>
                                <span class="font-bold">This Result was issued on</span>
                                <span class="text-dot-space2">{{ now()->format('jS F, Y') }}</span>
                                <span class="font-bold">and collected by</span>
                                <span class="text-dot-space2"></span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="font-bold text-primary">Next Term Begins:</span>
                                <span class="text-dot-space2">
                                    @php
                                        $nextTermBegins = $schoolInfo->date_next_term_begins ?? null;
                                        $formattedNextTermBegins = $nextTermBegins ? \Carbon\Carbon::parse($nextTermBegins)->format('jS F, Y') : '........................';
                                    @endphp
                                    {{ $formattedNextTermBegins }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <div class="powered-by">Powered by Qudroid Systems | www.qudroid.co | +2349057522004</div>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>