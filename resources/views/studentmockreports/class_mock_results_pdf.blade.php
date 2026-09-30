<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <title>Student Mock Report</title>

    <style>
        /* =========================================================
           RESET
        ========================================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background: #f5f5f5;
            text-align: center;
        }


        /* =========================================================
           A4 PAGE
        ========================================================= */
        @page {
            size: A4 portrait;
            margin: 5mm;
        }


        /* =========================================================
           WATERMARK
        ========================================================= */
        .watermark-text {
            position: fixed;

            top: 50%;
            left: 50%;

            transform: translate(-50%, -50%) rotate(-25deg);

            font-size: 70px;
            font-weight: 900;

            color: rgba(0, 0, 0, 0.035);

            font-family: 'Arial Black', sans-serif;

            letter-spacing: 5px;

            white-space: nowrap;

            pointer-events: none;

            z-index: 1000;

            width: 100%;

            text-align: center;

            text-transform: uppercase;
        }


        /* =========================================================
           MAIN STUDENT REPORT
           A4 usable area ≈ 200mm x 287mm
        ========================================================= */
        .student-section {
            width: 200mm;
            height: 287mm;
            min-height: 287mm;

            margin: 0 auto;

            background: #ffffff;

            border: 3px double #000000;

            padding: 0;

            position: relative;

            text-align: left;

            overflow: hidden;

            page-break-after: always;

            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .student-section:last-child {
            page-break-after: avoid;
        }


        /* =========================================================
           SCHOOL NAME HEADER
        ========================================================= */
        .school-name-header {
            width: 100%;

            background: #111827;

            color: white;

            padding: 10px 12px 8px;

            text-align: center;

            border-bottom: 2px solid #1e40af;
        }

        .school-name-header .school-full-name {
            font-family: 'Arial Black', sans-serif;

            font-size: 20px;

            font-weight: 900;

            letter-spacing: 1.5px;

            text-transform: uppercase;

            line-height: 1.1;
        }

        .school-name-header .motto {
            font-size: 9.5px;

            font-weight: 700;

            letter-spacing: 2px;

            opacity: .95;

            margin-top: 4px;
        }


        /* =========================================================
           HEADER TABLE
           LOGO | CONTACT | STUDENT PHOTO
        ========================================================= */
        .header-table {
            width: 100%;

            border-collapse: collapse;

            padding: 0;
        }


        /* SCHOOL LOGO */
        .school-logo {
            width: 75px;
            height: 82px;

            border: 2px solid #47b492;

            border-radius: 5px;

            background: white;

            padding: 4px;

            overflow: hidden;

            display: block;

            text-align: center;
        }

        .school-logo img {
            max-width: 100%;
            max-height: 100%;

            object-fit: contain;
        }


        /* STUDENT PHOTO */
        .photo-frame {
            width: 72px;
            height: 82px;

            border: 2px solid #47b492;

            border-radius: 5px;

            background: white;

            padding: 3px;

            overflow: hidden;

            display: block;

            text-align: center;

            margin-left: auto;
            margin-right: 0;
        }

        .photo-frame img {
            max-width: 100%;
            max-height: 100%;

            object-fit: contain;
        }


        /* =========================================================
           CONTACT INFORMATION
        ========================================================= */
        .contact-table {
            border: none;

            border-collapse: collapse;

            width: 100%;

            font-size: 10px;
        }

        .contact-table td {
            padding: 3px 5px 3px 0;

            vertical-align: top;
        }

        .contact-key {
            font-weight: 900;

            color: #1e40af;

            white-space: nowrap;
        }


        /* =========================================================
           HEADER DIVIDERS
        ========================================================= */
        .header-divider {
            width: 100%;

            height: 3px;

            background: #1e40af;

            margin: 0;
        }

        .header-divider2 {
            width: 100%;

            height: 1px;

            background: #64748b;

            margin: 2px 0;
        }


        /* =========================================================
           REPORT TITLE
        ========================================================= */
        .report-title {
            background: #111827;

            color: white;

            padding: 8px 10px;

            font-size: 12px;

            font-weight: 700;

            text-align: center;

            letter-spacing: .3px;
        }


        /* =========================================================
           STUDENT INFORMATION BAR
        ========================================================= */
        .student-info-bar {
            background: linear-gradient(
                to bottom,
                #f0f7ff 0%,
                #ffffff 100%
            );

            border: 2px solid #2aa886;

            border-radius: 5px;

            padding: 9px 12px;

            margin: 10px 10px 9px;

            font-size: 9.5px;

            text-align: center;
        }

        .info-table {
            width: 100%;

            border-collapse: collapse;

            margin: 0 auto;
        }

        .info-table td {
            padding: 4px 7px;

            text-align: center;

            vertical-align: middle;
        }

        .info-bar-label {
            color: #1e40af;

            font-weight: 900;

            font-size: 8.5px;

            white-space: nowrap;
        }

        .info-bar-value {
            font-weight: 900;

            font-size: 9.5px;

            padding-left: 2px;
        }


        /* =========================================================
           RESULT TABLE CONTAINER
        ========================================================= */
        .result-table {
            padding: 0 10px;

            margin: 7px 0 0;
        }

        .result-table table {
            width: 100%;

            table-layout: fixed;

            border: 2px solid #000;

            border-collapse: collapse;

            font-size: 8.5px;

            margin: 0;
        }


        /* =========================================================
           RESULT TABLE HEADER
        ========================================================= */
        .result-table thead th {
            background: #0d1a3d;

            color: white;

            font-weight: 800;

            border: 1px solid #000;

            padding: 5px 2px;

            font-size: 7.5px;

            text-align: center;

            vertical-align: middle;
        }

        .result-table thead th.col-sn {
            width: 30px;
        }

        .result-table thead th.col-subject {
            width: 30%;

            text-align: left;

            padding-left: 6px;
        }


        /* =========================================================
           RESULT TABLE BODY
        ========================================================= */
        .result-table tbody td {
            border: 1px solid #000;

            padding: 3px 2px;

            text-align: center;

            font-size: 8.5px;

            background: white;

            font-weight: 800;

            height: 19px;

            line-height: 16px;

            overflow: hidden;

            vertical-align: middle;
        }

        .result-table tbody td.subject-name {
            text-align: left;

            padding-left: 6px;

            font-size: 8.5px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /* =========================================================
           GRADE / POSITION COLOURS
        ========================================================= */
        .highlight-red {
            color: #dc2626;

            font-weight: 900;
        }

        .grade-A {
            color: #16a34a;

            font-weight: 900;
        }

        .grade-B {
            color: #2563eb;

            font-weight: 900;
        }

        .grade-C {
            color: #0ea5e9;

            font-weight: 900;
        }

        .grade-D {
            color: #ea580c;

            font-weight: 900;
        }

        .grade-F {
            color: #dc2626;

            font-weight: 900;
        }

        .position-1 {
            background: gold;

            color: black;

            font-weight: 900;

            border-radius: 2px;
        }

        .position-2 {
            background: silver;

            color: black;

            font-weight: 900;
        }

        .position-3 {
            background: #cd7f32;

            color: white;

            font-weight: 900;
        }


        /* =========================================================
           TOTALS SUMMARY
        ========================================================= */
        .totals-summary {
            width: calc(100% - 20px);

            background: #0d1a3d;

            color: #ffffff;

            font-weight: 900;

            font-size: 8.5px;

            padding: 7px 10px;

            border: 2px solid #000;

            border-top: none;

            text-align: center;

            margin: 0 10px 9px;
        }


        /* =========================================================
           REMARKS
        ========================================================= */
        .remarks-table {
            width: calc(100% - 20px);

            border: 2px solid #000;

            border-collapse: collapse;

            margin: 0 10px 7px;
        }

        .remarks-table td {
            border: 1px solid #000;

            padding: 9px 10px;

            background: white;

            vertical-align: top;

            font-size: 9px;

            line-height: 1.4;

            height: 50px;
        }

        .remarks-table .h6 {
            font-weight: 700;

            margin-bottom: 4px;

            font-size: 9.5px;

            border-bottom: 1px solid #ccc;

            display: inline-block;
        }


        /* =========================================================
           BOTTOM STRIP
        ========================================================= */
        .bottom-strip {
            width: 100%;

            border-top: 2px solid #cbd5e1;

            background: #f1f5f9;

            margin-top: 7px;
        }

        .bottom-strip table {
            width: 100%;

            border-collapse: collapse;
        }

        .bottom-strip td {
            padding: 9px 10px;

            vertical-align: middle;
        }


        /* QR */
        .cell-qr {
            width: 100px;

            text-align: center;

            vertical-align: middle;
        }

        /* CENTER FOOTER */
        .cell-footer {
            text-align: center;

            font-size: 9px;

            vertical-align: middle;

            line-height: 1.5;
        }

        /* STAMP */
        .cell-stamp {
            width: 130px;

            text-align: center;

            vertical-align: middle;
        }

        .cell-qr img {
            width: 82px;

            height: 82px;

            display: block;

            margin: 0 auto 3px;
        }

        .qr-label {
            font-size: 6.5px;

            color: #333;

            font-weight: 600;

            text-align: center;
        }

        .cell-stamp img {
            width: 105px;

            height: 105px;

            transform: rotate(-8deg);

            display: block;

            margin: 0 auto;
        }


        /* =========================================================
           FOOTER LINES
        ========================================================= */
        .text-dot-space2 {
            border-bottom: 1px dotted #333;

            display: inline-block;

            min-width: 120px;

            font-weight: bold;

            margin: 0 3px;
        }

        .powered-by {
            font-size: 8px;

            margin-top: 5px;

            color: #64748b;
        }


        /* =========================================================
           PRINT
        ========================================================= */
        @media print {

            html,
            body {
                width: 210mm;

                height: 297mm;

                margin: 0;

                padding: 0;

                background: white;
            }

            body {
                font-size: 10px;
            }

            .student-section {
                width: 200mm;

                height: 287mm;

                min-height: 287mm;

                margin: 0 auto;

                padding: 0;

                box-shadow: none;

                overflow: hidden;

                page-break-after: always;

                break-after: page;
            }

            .student-section:last-child {
                page-break-after: avoid;

                break-after: auto;
            }
        }
    </style>
</head>

<body>

    {{-- =========================================================
         WATERMARK
    ========================================================== --}}
    <div class="watermark-text">
        MOCK EXAMINATION
    </div>


    @php

        /* =========================================================
           SELECTED COLUMNS
        ========================================================== */

        $selectedColumns = $metadata['selected_columns'] ?? [];

        $defaultColumns = [
            'sn',
            'name',
            'exam',
            'total',
            'grade',
            'position',
            'class_average'
        ];

        $columnsToShow = !empty($selectedColumns)
            ? $selectedColumns
            : $defaultColumns;


        /* =========================================================
           RESULT COLUMN COUNT
        ========================================================== */

        $resultColumnKeys = [
            'sn',
            'name',
            'exam',
            'total',
            'grade',
            'position',
            'class_average',
            'cmin',
            'cmax'
        ];

        $visibleColCount = max(
            1,
            count(
                array_intersect(
                    $resultColumnKeys,
                    $columnsToShow
                )
            )
        );

    @endphp


    {{-- =========================================================
         LOOP THROUGH STUDENTS
    ========================================================== --}}

    @foreach ($allStudentData as $studentData)

        @php

            /* =====================================================
               BASIC DATA
            ====================================================== */

            $schoolInfo = $studentData['schoolInfo'] ?? null;

            $student = $studentData['students']->isNotEmpty()
                ? $studentData['students']->first()
                : null;

            $mockScores = $studentData['mockScores'] ?? collect();

            $totals = $studentData['totals_summary'] ?? [];

            $profile = $studentData['studentpp']->isNotEmpty()
                ? $studentData['studentpp']->first()
                : null;


            /* =====================================================
               STUDENT DETAILS
            ====================================================== */

            $fullName =
                strtoupper($student->lastname ?? '') . ' ' .
                ($student->fname ?? '') . ' ' .
                ($student->othername ?? '');


            $admNo =
                $student->admissionNo ?? '—';


            $classVal =
                ($studentData['schoolclass']->schoolclass ?? '') .
                ' ' .
                ($studentData['schoolclass']->arms->arm ?? '');


            $session =
                $metadata['session'] ?? '2025/2026';


            $term =
                $metadata['term'] ?? 'SECOND TERM';


            /* =====================================================
               QR CODE DATA
            ====================================================== */

            $qrData =
                "Name: {$fullName}\n" .
                "Adm No: {$admNo}\n" .
                "Class: {$classVal}\n" .
                "Term: {$term}\n" .
                "Session: {$session}\n" .
                "School: " .
                ($schoolInfo->school_name ?? 'School');


            $qrCodeBase64 = base64_encode(

                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(260)
                    ->errorCorrection('H')
                    ->generate($qrData)

            );


            /* =====================================================
               SCHOOL STAMP
            ====================================================== */

            $stampSrc = !empty(
                $studentData['school_stamp_base64']
            )
                ? $studentData['school_stamp_base64']
                : asset('stamp.jpeg');


            /* =====================================================
               SCHOOL LOGO
            ====================================================== */

            $logoSrc = !empty(
                $studentData['school_logo_base64']
            )
                ? $studentData['school_logo_base64']
                : 'data:image/svg+xml;base64,' .
                    base64_encode(
                        '<svg xmlns="http://www.w3.org/2000/svg"
                              width="62"
                              height="68"
                              viewBox="0 0 100 100">

                            <rect
                                width="100"
                                height="100"
                                fill="#f8f9fa"
                                stroke="#47b492"
                                stroke-width="2"
                            />

                            <text
                                x="50"
                                y="55"
                                text-anchor="middle"
                                fill="#1e40af"
                                font-size="8"
                                font-weight="bold">
                                LOGO
                            </text>

                        </svg>'
                    );

        @endphp


        {{-- =========================================================
             STUDENT REPORT PAGE
        ========================================================== --}}

        <div class="student-section">


            {{-- =====================================================
                 SCHOOL NAME HEADER
            ====================================================== --}}

            <div class="school-name-header">

                <div class="school-full-name">

                    {{ $schoolInfo->school_name
                        ?? 'CLARET SECONDARY SCHOOL KABBA' }}

                </div>

                <div class="motto">

                    {{ $schoolInfo->school_motto
                        ?? 'KNOWLEDGE AND VIRTUE' }}

                </div>

            </div>


            {{-- =====================================================
                 SCHOOL LOGO / CONTACT / PHOTO
            ====================================================== --}}

            <table class="header-table">

                <tr>

                    {{-- SCHOOL LOGO --}}
                    <td
                        width="15%"
                        style="
                            text-align:center;
                            vertical-align:middle;
                            padding:8px 8px 8px 10px;
                        "
                    >

                        <div class="school-logo">

                            <img
                                src="{{ $logoSrc }}"
                                alt="School Logo"
                            >

                        </div>

                    </td>


                    {{-- CONTACT INFORMATION --}}
                    <td
                        style="
                            vertical-align:middle;
                            padding:8px 8px;
                        "
                    >

                        <table class="contact-table">

                            <tr>

                                <td class="contact-key">
                                    Address:
                                </td>

                                <td>
                                    {{ $schoolInfo->school_address ?? '—' }}
                                </td>

                            </tr>

                            <tr>

                                <td class="contact-key">
                                    Phone:
                                </td>

                                <td>
                                    {{
                                        $schoolInfo->formatted_phones
                                        ?? ($schoolInfo->school_phone ?? '—')
                                    }}
                                </td>

                            </tr>

                            <tr>

                                <td class="contact-key">
                                    Email:
                                </td>

                                <td>
                                    {{ $schoolInfo->school_email ?? '—' }}
                                </td>

                            </tr>

                            <tr>

                                <td class="contact-key">
                                    Website:
                                </td>

                                <td>
                                    {{ $schoolInfo->school_website ?? '—' }}
                                </td>

                            </tr>

                        </table>

                    </td>


                    {{-- STUDENT PHOTO --}}
                    <td
                        width="17%"
                        style="
                            text-align:right;
                            padding:8px 10px 8px 5px;
                            vertical-align:middle;
                        "
                    >

                        @if(in_array('picture', $columnsToShow))

                            <div class="photo-frame">

                                @if(!empty(
                                    $studentData['student_image_base64']
                                ))

                                    <img
                                        src="{{ $studentData['student_image_base64'] }}"
                                        alt="Student Photo"
                                    >

                                @else

                                    <img
                                        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='68' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23e2e8f0'/%3E%3Ccircle cx='50' cy='40' r='20' fill='%2394a3b8'/%3E%3Crect x='35' y='65' width='30' height='25' fill='%2394a3b8' rx='4'/%3E%3C/svg%3E"
                                        alt="Default"
                                    >

                                @endif

                            </div>

                        @endif

                    </td>

                </tr>

            </table>


            {{-- HEADER DIVIDERS --}}

            <div class="header-divider"></div>

            <div class="header-divider2"></div>


            {{-- =====================================================
                 REPORT TITLE
            ====================================================== --}}

            <div class="report-title">

                {{ strtoupper($term) }}
                {{ strtoupper($session) }}
                MOCK EXAMINATION RESULT

            </div>


            {{-- =====================================================
                 STUDENT INFORMATION
            ====================================================== --}}

            <div class="student-info-bar">

                <table class="info-table">

                    <tr>

                        <td>

                            <span class="info-bar-label">
                                NAME:
                            </span>

                            <span class="info-bar-value">
                                {{ $fullName }}
                            </span>

                        </td>


                        <td>

                            <span class="info-bar-label">
                                SESSION:
                            </span>

                            <span class="info-bar-value">
                                {{ $session }}
                            </span>

                        </td>


                        <td>

                            <span class="info-bar-label">
                                TERM:
                            </span>

                            <span class="info-bar-value">
                                {{ $term }}
                            </span>

                        </td>


                        <td>

                            <span class="info-bar-label">
                                CLASS:
                            </span>

                            <span class="info-bar-value">
                                {{ $classVal }}
                            </span>

                        </td>

                    </tr>


                    <tr>

                        <td>

                            <span class="info-bar-label">
                                ADM NO:
                            </span>

                            <span class="info-bar-value">
                                {{ $admNo }}
                            </span>

                        </td>


                        <td>

                            <span class="info-bar-label">
                                NO. IN CLASS:
                            </span>

                            <span class="info-bar-value">
                                {{ $studentData['numberOfStudents'] ?? '—' }}
                            </span>

                        </td>


                        @if(in_array('gender', $columnsToShow))

                            <td>

                                <span class="info-bar-label">
                                    SEX:
                                </span>

                                <span class="info-bar-value">
                                    {{ $student->gender ?? '—' }}
                                </span>

                            </td>

                        @endif


                        @if(in_array('dob', $columnsToShow))

                            <td>

                                <span class="info-bar-label">
                                    D.O.B:
                                </span>

                                <span class="info-bar-value">

                                    {{
                                        $student->dateofbirth
                                            ? \Carbon\Carbon::parse(
                                                $student->dateofbirth
                                            )->format('jS F, Y')
                                            : '—'
                                    }}

                                </span>

                            </td>

                        @endif

                    </tr>

                </table>

            </div>


            {{-- =====================================================
                 RESULTS TABLE
            ====================================================== --}}

            <div class="result-table">

                <table>

                    <thead>

                        <tr>

                            @if(in_array('sn', $columnsToShow))

                                <th class="col-sn">
                                    S/N
                                </th>

                            @endif


                            @if(in_array('name', $columnsToShow))

                                <th class="col-subject">
                                    Subject
                                </th>

                            @endif


                            @if(in_array('exam', $columnsToShow))

                                <th>
                                    Exam
                                </th>

                            @endif


                            @if(in_array('total', $columnsToShow))

                                <th>
                                    Total
                                </th>

                            @endif


                            @if(in_array('grade', $columnsToShow))

                                <th>
                                    Grade
                                </th>

                            @endif


                            @if(in_array('position', $columnsToShow))

                                <th>
                                    Pos
                                </th>

                            @endif


                            @if(in_array('class_average', $columnsToShow))

                                <th>
                                    Avg
                                </th>

                            @endif


                            @if(in_array('cmin', $columnsToShow))

                                <th>
                                    Min
                                </th>

                            @endif


                            @if(in_array('cmax', $columnsToShow))

                                <th>
                                    Max
                                </th>

                            @endif

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($mockScores as $i => $score)

                            @if(empty($score->subject_name))

                                @continue

                            @endif


                            <tr>

                                {{-- S/N --}}
                                @if(in_array('sn', $columnsToShow))

                                    <td>
                                        {{ $i + 1 }}
                                    </td>

                                @endif


                                {{-- SUBJECT --}}
                                @if(in_array('name', $columnsToShow))

                                    <td class="subject-name">

                                        {{ $score->subject_name ?? 'N/A' }}

                                    </td>

                                @endif


                                {{-- EXAM --}}
                                @if(in_array('exam', $columnsToShow))

                                    <td
                                        @if(($score->exam ?? 0) < 50)
                                            class="highlight-red"
                                        @endif
                                    >

                                        {{
                                            $score->exam !== null
                                                ? number_format(
                                                    $score->exam,
                                                    1
                                                )
                                                : '-'
                                        }}

                                    </td>

                                @endif


                                {{-- TOTAL --}}
                                @if(in_array('total', $columnsToShow))

                                    <td
                                        @if(($score->total ?? 0) < 50)
                                            class="highlight-red"
                                        @endif
                                    >

                                        {{
                                            $score->total !== null
                                                ? number_format(
                                                    $score->total,
                                                    1
                                                )
                                                : '-'
                                        }}

                                    </td>

                                @endif


                                {{-- GRADE --}}
                                @if(in_array('grade', $columnsToShow))

                                    @php

                                        $g =
                                            $score->grade ?? '-';

                                        $gUpper =
                                            strtoupper(
                                                trim(
                                                    (string) $g
                                                )
                                            );


                                        $gc = match(true) {

                                            $gUpper === 'F'
                                                => 'grade-F',

                                            $gUpper === 'E8'
                                                => 'grade-F',

                                            $gUpper === 'F9'
                                                => 'grade-F',

                                            str_starts_with(
                                                $gUpper,
                                                'A'
                                            )
                                                => 'grade-A',

                                            str_starts_with(
                                                $gUpper,
                                                'B'
                                            )
                                                => 'grade-B',

                                            str_starts_with(
                                                $gUpper,
                                                'C'
                                            )
                                                => 'grade-C',

                                            str_starts_with(
                                                $gUpper,
                                                'D'
                                            )
                                                => 'grade-D',

                                            default
                                                => ''
                                        };

                                    @endphp


                                    <td class="{{ $gc }}">

                                        {{ $g }}

                                    </td>

                                @endif


                                {{-- POSITION --}}
                                @if(in_array('position', $columnsToShow))

                                    @php

                                        $pos =
                                            $score->position ?? null;


                                        if (
                                            $pos === null ||
                                            $pos === '' ||
                                            $pos === '0' ||
                                            $pos === 0
                                        ) {

                                            $pos = '-';

                                        }


                                        $posNum =
                                            preg_replace(
                                                '/\D/',
                                                '',
                                                (string) $pos
                                            );


                                        $posC = match(
                                            (int) $posNum
                                        ) {

                                            1 => 'position-1',

                                            2 => 'position-2',

                                            3 => 'position-3',

                                            default => ''

                                        };

                                    @endphp


                                    <td class="{{ $posC }}">

                                        {{ $pos }}

                                    </td>

                                @endif


                                {{-- CLASS AVERAGE --}}
                                @if(in_array(
                                    'class_average',
                                    $columnsToShow
                                ))

                                    <td>

                                        {{
                                            $score->class_average !== null
                                                ? number_format(
                                                    $score->class_average,
                                                    1
                                                )
                                                : '-'
                                        }}

                                    </td>

                                @endif


                                {{-- MINIMUM --}}
                                @if(in_array('cmin', $columnsToShow))

                                    <td>

                                        {{
                                            $score->cmin !== null
                                                ? number_format(
                                                    $score->cmin,
                                                    1
                                                )
                                                : '-'
                                        }}

                                    </td>

                                @endif


                                {{-- MAXIMUM --}}
                                @if(in_array('cmax', $columnsToShow))

                                    <td>

                                        {{
                                            $score->cmax !== null
                                                ? number_format(
                                                    $score->cmax,
                                                    1
                                                )
                                                : '-'
                                        }}

                                    </td>

                                @endif

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="{{ $visibleColCount }}"
                                    style="
                                        text-align:center;
                                        padding:10px;
                                    "
                                >

                                    No mock scores available.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =====================================================
                 TOTALS
            ====================================================== --}}

            <div class="totals-summary">

                TOTAL OBTAINED:

                {{ number_format(
                    $totals['obtained'] ?? 0,
                    1
                ) }}

                &nbsp;&nbsp;|&nbsp;&nbsp;

                TOTAL OBTAINABLE:

                {{ $totals['obtainable'] ?? 0 }}

                &nbsp;&nbsp;|&nbsp;&nbsp;

                % OBTAINED:

                {{ $totals['percentage'] ?? 0 }}%

            </div>


            {{-- =====================================================
                 REMARKS
            ====================================================== --}}

            <table class="remarks-table">

                <tbody>

                    <tr>

                        <td width="50%">

                            <div class="h6">
                                Class Teacher's Remark
                            </div>

                            <div>

                                {{
                                    $profile
                                        ? (
                                            $profile->classteachercomment
                                            ?? 'NO INFO'
                                        )
                                        : 'NO INFO'
                                }}

                            </div>

                        </td>


                        <td width="50%">

                            <div class="h6">
                                Principal's Remark
                            </div>

                            <div>

                                {{
                                    $profile
                                        ? (
                                            $profile->principalscomment
                                            ?? 'NO INFO'
                                        )
                                        : 'NO INFO'
                                }}

                            </div>

                        </td>

                    </tr>

                </tbody>

            </table>


            {{-- =====================================================
                 BOTTOM STRIP
            ====================================================== --}}

            <div class="bottom-strip">

                <table>

                    <tr>


                        {{-- =================================================
                             QR CODE
                        ================================================== --}}

                        <td class="cell-qr">

                            <img
                                src="data:image/png;base64,{{ $qrCodeBase64 }}"
                                alt="QR Code"
                            >

                            <div class="qr-label">
                                Scan for Verification
                            </div>

                        </td>


                        {{-- =================================================
                             FOOTER INFORMATION
                        ================================================== --}}

                        <td class="cell-footer">

                            <div>

                                <strong>
                                    Issued:
                                </strong>

                                <span class="text-dot-space2">

                                    {{ now()->format('jS F, Y') }}

                                </span>

                            </div>


                            <div style="margin-top:6px;">

                                <strong>
                                    Collected by:
                                </strong>

                                <span class="text-dot-space2">

                                    .......................................

                                </span>

                            </div>


                            <div style="margin-top:6px;">

                                <strong>
                                    Next Term Begins:
                                </strong>

                                <span class="text-dot-space2">

                                    @php

                                        $ntb =
                                            $schoolInfo
                                                ->date_next_term_begins
                                                ?? null;


                                        echo $ntb

                                            ? \Carbon\Carbon::parse(
                                                $ntb
                                            )->format('jS F, Y')

                                            : '........................';

                                    @endphp

                                </span>

                            </div>


                            <div class="powered-by">

                                Powered by Qudroid Systems

                            </div>

                        </td>


                        {{-- =================================================
                             SCHOOL STAMP
                        ================================================== --}}

                        <td class="cell-stamp">

                            <img
                                src="{{ $stampSrc }}"
                                alt="School Stamp"
                            >

                        </td>

                    </tr>

                </table>

            </div>


        </div>

    @endforeach

</body>
</html>
