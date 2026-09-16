{{-- resources/views/broadsheet/pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Class Broadsheet — {{ ($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arm_name ?? '') }}</title>
<style>
/* ═══════════════════════════════════════════════════
   BASE RESET & PAGE
═══════════════════════════════════════════════════ */
* { margin:0; padding:0; box-sizing:border-box; }

@page {
    size: {{ $pdf_paper_size ?? 'A3' }} landscape;
    margin: 10mm 8mm;
}

body {
    font-family: 'DejaVu Sans', 'Arial', sans-serif;
    font-size: 8px;
    color: #0f1923;
    background: #fff;
    line-height: 1.3;
}

/* ═══════════════════════════════════════════════════
   SCHOOL HEADER
═══════════════════════════════════════════════════ */
.school-header {
    width: 100%;
    border: 2.5px solid #0f2342;
    border-radius: 4px;
    margin-bottom: 6px;
    overflow: hidden;
}

.school-header-top {
    background: #0f2342;
    padding: 8px 14px;
}

.school-header-top table {
    width: 100%;
    border-collapse: collapse;
}

.school-logo-cell {
    width: 70px;
    vertical-align: middle;
    text-align: center;
}

.school-logo-cell img {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.3);
    object-fit: contain;
    background: #fff;
}

.school-name-cell {
    vertical-align: middle;
    text-align: center;
    color: #fff;
    padding: 0 10px;
}

.school-name-cell .s-name {
    font-size: 16px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    line-height: 1.2;
}

.school-name-cell .s-address {
    font-size: 8px;
    opacity: .8;
    margin-top: 3px;
}

.school-name-cell .s-motto {
    font-size: 7.5px;
    font-style: italic;
    opacity: .75;
    margin-top: 2px;
}

.school-header-bottom {
    background: #1e3a5f;
    color: #fff;
    text-align: center;
    padding: 5px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    border-top: 1px solid rgba(255,255,255,.15);
}

/* ═══════════════════════════════════════════════════
   META INFO STRIP
═══════════════════════════════════════════════════ */
.meta-strip {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    background: #f8fafc;
    margin-bottom: 5px;
    border-collapse: collapse;
}

.meta-strip .meta-cell {
    padding: 5px 10px;
    border-right: 1px solid #cbd5e1;
    vertical-align: middle;
    text-align: center;
}

.meta-strip .meta-cell:last-child { border-right: none; }
.meta-cell .m-lbl { font-size: 6.5px; color: #64748b; text-transform: uppercase; letter-spacing: .4px; display: block; }
.meta-cell .m-val { font-size: 8.5px; font-weight: 700; color: #0f2342; display: block; margin-top: 1px; }

/* ═══════════════════════════════════════════════════
   GRADE KEY
═══════════════════════════════════════════════════ */
.grade-key {
    width: 100%;
    margin-bottom: 5px;
    border: 1px solid #e2e8f0;
    border-radius: 3px;
    padding: 4px 8px;
    background: #fafafa;
}

.grade-key-inner {
    font-size: 7px;
    color: #374151;
    line-height: 1.8;
}

.gk-badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 6.5px;
    margin: 0 2px;
    color: #fff;
}

.gk-legend {
    display: inline-block;
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 6.5px;
    font-weight: 700;
    margin: 0 2px;
}

/* ═══════════════════════════════════════════════════
   BROADSHEET TABLE
═══════════════════════════════════════════════════ */
.broadsheet-wrap {
    width: 100%;
    overflow: hidden;
    margin-bottom: 6px;
}

table.bst {
    width: 100%;
    border-collapse: collapse;
    border: 1.5px solid #0f2342;
    font-size: 7px;
}

/* ── Column group headers (subject names) ── */
table.bst thead tr.row-subjects th {
    background: #0f2342;
    color: #fff;
    text-align: center;
    padding: 5px 3px;
    border: 0.5px solid rgba(255,255,255,.15);
    font-weight: 700;
    font-size: 7.5px;
    white-space: nowrap;
}

table.bst thead tr.row-subjects th.th-fixed {
    background: #0a1930;
    text-align: left;
    padding-left: 5px;
    white-space: nowrap;
}

table.bst thead tr.row-subjects th.th-subj {
    background: #163562;
    border-left: 1.5px solid #2563eb;
    font-size: 7px;
    white-space: normal;
    word-break: break-word;
    min-width: 40px;
}

/* ── Sub-column headers ── */
table.bst thead tr.row-subs th {
    background: #1a3d6a;
    color: #a8d4ef;
    text-align: center;
    padding: 3px 2px;
    border: 0.5px solid rgba(255,255,255,.1);
    font-size: 6px;
    white-space: nowrap;
}

table.bst thead tr.row-subs th.sub-boundary {
    border-left: 1.5px solid #2563eb;
}

table.bst thead tr.row-subs th.th-pos-class {
    background: #1a2f1a;
    color: #fef9c3;
    font-size: 6px;
}

table.bst thead tr.row-subs th.th-pos-arm {
    background: #0a1e38;
    color: #bfdbfe;
    font-size: 6px;
}

/* ── Body rows ── */
table.bst tbody tr:nth-child(odd)  { background: #ffffff; }
table.bst tbody tr:nth-child(even) { background: #f0f4fa; }

table.bst tbody td {
    padding: 3px 2px;
    border: 0.5px solid #c5d3e8;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    font-size: 7px;
}

table.bst tbody td.td-student {
    text-align: left;
    padding-left: 5px;
    font-weight: 700;
    font-size: 7px;
    white-space: nowrap;
    min-width: 120px;
    background: inherit;
}

table.bst tbody td.td-sn {
    width: 20px;
    font-size: 7px;
    color: #64748b;
}

table.bst tbody td.td-adm {
    width: 60px;
    font-size: 6.5px;
    color: #374151;
}

/* ── Position column (overall T/C) ── */
table.bst tbody td.td-pos {
    width: 46px;
    padding: 2px;
    text-align: center;
}

.pos-pair { display: inline-block; text-align: center; }

.pos-t {
    display: block;
    font-size: 6.5px;
    font-weight: 700;
    background: #fef3c7;
    color: #92400e;
    border-radius: 2px;
    padding: 1px 3px;
    margin-bottom: 1px;
    white-space: nowrap;
}

.pos-c {
    display: block;
    font-size: 6.5px;
    font-weight: 700;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 2px;
    padding: 1px 3px;
    white-space: nowrap;
}

/* ── Grade colour cells — Junior (A–F) ── */
.g-a { background: #dcfce7 !important; color: #166534; font-weight: 700; }
.g-b { background: #dbeafe !important; color: #1e40af; }
.g-c { background: #fef9c3 !important; color: #854d0e; }
.g-d { background: #ffedd5 !important; color: #9a3412; }
.g-f { background: #fee2e2 !important; color: #991b1b; font-weight: 700; }

/* ── Grade colour cells — Senior (A1–F9) ── */
.g-a1 { background: #dcfce7 !important; color: #166534; font-weight: 700; }
.g-b2 { background: #dbeafe !important; color: #1e40af; }
.g-b3 { background: #e0eeff !important; color: #1e40af; }
.g-c4 { background: #fef9c3 !important; color: #854d0e; }
.g-c5 { background: #fef3c7 !important; color: #92400e; }
.g-c6 { background: #fde68a !important; color: #78350f; }
.g-d7 { background: #ffedd5 !important; color: #9a3412; }
.g-e8 { background: #fed7aa !important; color: #9a3412; }
.g-f9 { background: #fee2e2 !important; color: #991b1b; font-weight: 700; }

/* ── Per-subject position cells ── */
.sp-cc { background: #f0fdf4 !important; color: #166534; font-weight: 700; font-size: 6.5px; }
.sp-ct { background: #fefce8 !important; color: #854d0e; font-weight: 700; font-size: 6.5px; }
.sp-at { background: #eff6ff !important; color: #1e40af; font-weight: 700; font-size: 6.5px; }
.sp-ak { background: #f5f3ff !important; color: #5b21b6; font-weight: 700; font-size: 6.5px; }

/* ── BF cell ── */
.td-bf-has  { color: #0369a1; font-weight: 700; }
.td-bf-none { color: #94a3b8; }

/* ── Stats rows ── */
table.bst tbody tr.stats-avg td { background: #0f2342 !important; color: #fff; font-weight: 700; border-color: #163785; }
table.bst tbody tr.stats-hi  td { background: #0a2240 !important; color: #fff; font-weight: 700; border-color: #163785; }
table.bst tbody tr.stats-lo  td { background: #111c2a !important; color: #fff; font-weight: 700; border-color: #163785; }
table.bst tbody tr.stats-avg td.stats-lbl,
table.bst tbody tr.stats-hi  td.stats-lbl,
table.bst tbody tr.stats-lo  td.stats-lbl {
    text-align: left;
    padding-left: 5px;
    font-size: 6.5px;
}

/* ═══════════════════════════════════════════════════
   PROMOTION COLUMNS
═══════════════════════════════════════════════════ */
table.bst thead tr.row-subjects th.th-promo {
    background: #3b0764;
    border-left: 2px solid #7c3aed;
    font-size: 7px;
}

table.bst thead tr.row-subs th.promo-sub {
    background: #3b0764;
    color: #d8b4fe;
    border-left: 2px solid #7c3aed;
    font-size: 6px;
}

table.bst tbody td.td-promo {
    border-left: 2px solid #7c3aed;
    font-size: 6.5px;
    text-align: center;
}

.promo-pill {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 8px;
    font-size: 6px;
    font-weight: 700;
    white-space: nowrap;
}
.promo-promoted      { background: #d1fae5; color: #065f46; }
.promo-trial         { background: #fef3c7; color: #92400e; }
.promo-see_principal { background: #dbeafe; color: #1e40af; }
.promo-repeated      { background: #fee2e2; color: #991b1b; }
.promo-awaiting      { background: #f1f5f9; color: #475569; }

/* ═══════════════════════════════════════════════════
   SUBJECT PERFORMANCE SUMMARY
═══════════════════════════════════════════════════ */
.subj-summary {
    width: 100%;
    margin-top: 8px;
    page-break-inside: avoid;
}

.subj-summary h3 {
    background: #0f2342;
    color: #fff;
    padding: 5px 10px;
    font-size: 8.5px;
    font-weight: 700;
    border-radius: 3px 3px 0 0;
}

table.subj-tbl {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #cbd5e1;
    font-size: 7.5px;
}

table.subj-tbl thead th {
    background: #1e3a5f;
    color: #fff;
    padding: 4px 8px;
    text-align: center;
    border: 0.5px solid rgba(255,255,255,.15);
    font-weight: 600;
    font-size: 7px;
}

table.subj-tbl thead th:first-child { text-align: left; }

table.subj-tbl tbody tr:nth-child(odd)  { background: #f8fafc; }
table.subj-tbl tbody tr:nth-child(even) { background: #fff; }

table.subj-tbl tbody td {
    padding: 3px 8px;
    border: 0.5px solid #e2e8f0;
    text-align: center;
    font-size: 7px;
}

table.subj-tbl tbody td:first-child { text-align: left; font-weight: 600; color: #0f2342; }
.pass-rate-ok  { color: #16a34a; font-weight: 700; }
.pass-rate-bad { color: #dc2626; font-weight: 700; }

/* ═══════════════════════════════════════════════════
   SIGNATURE BLOCK
═══════════════════════════════════════════════════ */
.sig-block {
    margin-top: 14px;
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 3px;
    padding: 10px 16px;
    page-break-inside: avoid;
}

.sig-block table {
    width: 100%;
    border-collapse: collapse;
}

.sig-block td {
    width: 25%;
    text-align: center;
    padding: 0 10px;
    vertical-align: bottom;
}

.sig-line {
    border-top: 1.5px solid #64748b;
    padding-top: 4px;
    font-size: 7px;
    color: #374151;
    font-weight: 600;
    margin-top: 30px;
}
</style>
</head>
<body>

@php
/*
 * ─────────────────────────────────────────────────────────────────
 *  PHP HELPERS — local to this view
 * ─────────────────────────────────────────────────────────────────
 */

/** Ordinal suffix: 1→1st, 2→2nd, etc. */
if (!function_exists('bsOrdinal')) {
    function bsOrdinal($n) {
        if (!$n) return '—';
        $n = (int) $n;
        $s = ['th','st','nd','rd'];
        $v = $n % 100;
        return $n . ($s[($v-20)%10] ?? $s[$v] ?? $s[0]);
    }
}

/** Map grade string to CSS class — handles BOTH junior and senior scales. */
if (!function_exists('bsGradeClass')) {
    function bsGradeClass($g) {
        return match($g) {
            'A'  => 'g-a',  'B'  => 'g-b',  'C'  => 'g-c',  'D'  => 'g-d',  'F'  => 'g-f',
            'A1' => 'g-a1', 'B2' => 'g-b2', 'B3' => 'g-b3',
            'C4' => 'g-c4', 'C5' => 'g-c5', 'C6' => 'g-c6',
            'D7' => 'g-d7', 'E8' => 'g-e8', 'F9' => 'g-f9',
            default => '',
        };
    }
}

/*
 * ─────────────────────────────────────────────────────────────────
 *  COLUMN VISIBILITY FLAGS
 * ─────────────────────────────────────────────────────────────────
 */
$sel     = $selectedColumns ?? [];
$showAll = empty($sel);

// Student Info
$showAdmNo  = $showAll || in_array('admission_no', $sel);
$showGender = in_array('gender', $sel);

// Fixed CA columns
$showCA1  = $showAll || in_array('ca1',  $sel);
$showCA2  = $showAll || in_array('ca2',  $sel);
$showCA3  = $showAll || in_array('ca3',  $sel);
$showExam = $showAll || in_array('exam', $sel);

// Score metrics
$showTotal  = $showAll || in_array('total',         $sel);
$showBF     = $showAll || in_array('bf',            $sel);
$showCum    = $showAll || in_array('cum',           $sel);
$showGrade  = $showAll || in_array('grade',         $sel);
$showAvg    = $showAll || in_array('class_average', $sel);
$showRemark = in_array('remark', $sel);

// Overall positions
$showPosTerm = $showAll || in_array('position_term', $sel);
$showPosCum  = $showAll || in_array('position_cum',  $sel);

// Per-subject positions
$showSubPosCC = $showAll || in_array('pos_class_cum',   $sel);
$showSubPosCT = $showAll || in_array('pos_class_total', $sel);
$showSubPosAT = $showAll || in_array('pos_arm_total',   $sel);
$showSubPosAK = $showAll || in_array('pos_arm_cum',     $sel);

// Promotion
$showPromoStatus = $showAll || in_array('promotion_status',       $sel);
$showPromoLabel  = in_array('promotion_label', $sel);
$showPromoRule   = $showAll || in_array('promotion_rule_applied', $sel);

$promoColspan = ($showPromoStatus ? 1 : 0)
              + ($showPromoLabel  ? 1 : 0)
              + ($showPromoRule   ? 1 : 0);

/*
 * Sub-columns per subject block
 */
$subColspan = 0;
if ($showCA1)  $subColspan++;
if ($showCA2)  $subColspan++;
if ($showCA3)  $subColspan++;
if ($showExam) $subColspan++;
if ($showTotal) $subColspan++;
if ($showBF)    $subColspan++;
if ($showCum)   $subColspan++;
if ($showGrade) $subColspan++;
if ($showSubPosCC) $subColspan++;
if ($showSubPosCT) $subColspan++;
if ($showSubPosAT) $subColspan++;
if ($showSubPosAK) $subColspan++;
if ($showAvg)    $subColspan++;
if ($showRemark) $subColspan++;
$subColspan = max(1, $subColspan);

/*
 * Frozen student-info columns
 */
$frozenCols = 1
    + (($showPosTerm || $showPosCum) ? 1 : 0)
    + ($showAdmNo ? 1 : 0)
    + 1
    + ($showGender ? 1 : 0);

/*
 * Is this class a senior class? Drives which grade-key is displayed.
 */
$isSeniorClass = $schoolclass && $schoolclass->classcategory
    ? $schoolclass->classcategory->is_senior
    : false;
@endphp

{{-- ══════════════════════════════════════════════════════════════
     SCHOOL HEADER
══════════════════════════════════════════════════════════════ --}}
<div class="school-header">
    <div class="school-header-top">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="width:70px;text-align:center;vertical-align:middle;">
                    @if(!empty($school_logo_base64))
                        <img src="{{ $school_logo_base64 }}" alt="Logo">
                    @endif
                </td>
                <td style="text-align:center;vertical-align:middle;">
                    <div class="s-name">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</div>
                    @if(!empty($schoolInfo->school_address))
                        <div class="s-address">{{ $schoolInfo->school_address }}</div>
                    @endif
                    @if(!empty($schoolInfo->school_motto))
                        <div class="s-motto">"{{ $schoolInfo->school_motto }}"</div>
                    @endif
                </td>
                <td style="width:70px;"></td>
            </tr>
        </table>
    </div>
    <div class="school-header-bottom">
        CLASS ACADEMIC BROADSHEET
        @if(!empty($is_combined))
            <span style="font-size:7px;opacity:.7;font-weight:400;margin-left:10px;">— Combined Arms</span>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     META INFO STRIP
══════════════════════════════════════════════════════════════ --}}
<table class="meta-strip">
    <tr>
        <td class="meta-cell" style="width:25%;">
            <span class="m-lbl">Class</span>
            <span class="m-val">{{ ($schoolclass->schoolclass ?? '-') . ' ' . ($schoolclass->arm_name ?? '') }}</span>
        </td>
        <td class="meta-cell" style="width:25%;">
            <span class="m-lbl">Session</span>
            <span class="m-val">{{ $schoolsession->session ?? '-' }}</span>
        </td>
        <td class="meta-cell" style="width:20%;">
            <span class="m-lbl">Term</span>
            <span class="m-val">{{ $schoolterm->term ?? '-' }}</span>
        </td>
        <td class="meta-cell" style="width:15%;">
            <span class="m-lbl">Students</span>
            <span class="m-val">{{ $totalStudents }}</span>
        </td>
        <td class="meta-cell" style="width:15%;">
            <span class="m-lbl">Generated</span>
            <span class="m-val" style="font-size:7px;">{{ $generatedAt }}</span>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════════════════
     GRADE KEY
══════════════════════════════════════════════════════════════ --}}
<div class="grade-key">
    <div class="grade-key-inner">
        <strong>GRADING SCALE ({{ $isSeniorClass ? 'Senior' : 'Junior' }}):</strong>
        @php
        if ($isSeniorClass) {
            $gkItems = [
                ['A1','75-100','#16a34a'],['B2','70-74','#1d4ed8'],['B3','65-69','#2563eb'],
                ['C4','60-64','#d97706'],['C5','55-59','#b45309'],['C6','50-54','#92400e'],
                ['D7','45-49','#ea580c'],['E8','40-44','#c2410c'],['F9','0-39','#dc2626'],
            ];
        } else {
            $gkItems = [
                ['A','70-100','#16a34a'],['B','60-69','#1d4ed8'],['C','50-59','#7c3aed'],
                ['D','40-49','#d97706'],['F','0-39','#dc2626'],
            ];
        }
        @endphp
        @foreach($gkItems as $gki)
            <span class="gk-badge" style="background:{{ $gki[2] }};">{{ $gki[0] }}({{ $gki[1] }})</span>
        @endforeach
        &nbsp;&nbsp;
        <strong>Total</strong>=((CA1+CA2+CA3)÷3 + Exam)÷2 &nbsp;
        <strong>BF</strong>=Brought Forward &nbsp;
        <strong>Cum</strong>=Term 1: Total; Terms 2–3: (BF+Total)÷2 &nbsp;
        <span class="gk-legend" style="background:#fef3c7;color:#92400e;">T-POS</span>=Overall Term Pos &nbsp;
        <span class="gk-legend" style="background:#dbeafe;color:#1e40af;">C-POS</span>=Overall Cum Pos &nbsp;
        <span class="gk-legend" style="background:#f0fdf4;color:#166534;">CC</span>=Class Pos (Cum) &nbsp;
        <span class="gk-legend" style="background:#fefce8;color:#854d0e;">CT</span>=Class Pos (Total) &nbsp;
        <span class="gk-legend" style="background:#eff6ff;color:#1e40af;">AC</span>=Arm Pos (Total) &nbsp;
        <span class="gk-legend" style="background:#f5f3ff;color:#5b21b6;">AK</span>=Arm Pos (Cum)
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MAIN BROADSHEET TABLE
══════════════════════════════════════════════════════════════ --}}
<div class="broadsheet-wrap">
<table class="bst">
    <thead>

        {{-- ══ Row 1: Subject group headers ══ --}}
        <tr class="row-subjects">
            <th class="th-fixed" rowspan="2" style="width:20px;">#</th>
            @if($showPosTerm || $showPosCum)
                <th class="th-fixed" rowspan="2" style="width:46px;">Position</th>
            @endif
            @if($showAdmNo)
                <th class="th-fixed" rowspan="2" style="min-width:60px;">Adm. No</th>
            @endif
            <th class="th-fixed" rowspan="2" style="min-width:130px;text-align:left;padding-left:5px;">Student Name</th>
            @if($showGender)
                <th class="th-fixed" rowspan="2" style="width:28px;">Sex</th>
            @endif

            @foreach($subjects as $subId => $subInfo)
                <th class="th-subj" colspan="{{ $subColspan }}">
                    {{ $subInfo['subject_name'] }}
                    @if(!empty($subInfo['subject_code']))
                        <br><span style="font-size:6px;opacity:.75;">({{ $subInfo['subject_code'] }})</span>
                    @endif
                </th>
            @endforeach

            @if($promoColspan > 0)
                <th class="th-promo" colspan="{{ $promoColspan }}">🎓 PROMOTION</th>
            @endif
        </tr>

        {{-- ══ Row 2: Sub-column headers ══ --}}
        <tr class="row-subs">
            @foreach($subjects as $subId => $subInfo)
                @if($showCA1)
                    <th class="sub-boundary" style="min-width:26px;">CA1</th>
                @endif
                @if($showCA2)
                    <th style="min-width:26px;">CA2</th>
                @endif
                @if($showCA3)
                    <th style="min-width:26px;">CA3</th>
                @endif
                @if($showExam)
                    <th style="min-width:28px;">Exam</th>
                @endif
                @if($showTotal)
                    <th style="min-width:28px;">Total</th>
                @endif
                @if($showBF)
                    <th style="min-width:22px;">BF</th>
                @endif
                @if($showCum)
                    <th style="min-width:30px;">Cum</th>
                @endif
                @if($showGrade)
                    <th style="min-width:22px;">Grd</th>
                @endif
                @if($showSubPosCC)
                    <th class="th-pos-class" style="min-width:24px;" title="Class-wide, cum">CC</th>
                @endif
                @if($showSubPosCT)
                    <th class="th-pos-class" style="min-width:24px;" title="Class-wide, total">CT</th>
                @endif
                @if($showSubPosAT)
                    <th class="th-pos-arm" style="min-width:24px;" title="Arm, total">AC</th>
                @endif
                @if($showSubPosAK)
                    <th class="th-pos-arm" style="min-width:24px;" title="Arm, cum">AK</th>
                @endif
                @if($showAvg)
                    <th style="min-width:24px;">Avg</th>
                @endif
                @if($showRemark)
                    <th style="min-width:34px;">Rmk</th>
                @endif
            @endforeach

            @if($showPromoStatus)
                <th class="promo-sub" style="min-width:90px;">Status</th>
            @endif
            @if($showPromoLabel)
                <th class="promo-sub" style="min-width:110px;">Label</th>
            @endif
            @if($showPromoRule)
                <th class="promo-sub" style="min-width:80px;">Rule</th>
            @endif
        </tr>

    </thead>
    <tbody>

        {{-- ══ Student rows ══ --}}
        @foreach($studentRows as $idx => $stu)
            @php
                $sid      = $stu['id'];
                $posCum   = $stu['position_cum']  ?? 0;
                $posTerm  = $stu['position_term'] ?? 0;
                $fullName = strtoupper($stu['lastname'] ?? '') . ', ' . ($stu['firstname'] ?? '');
            @endphp
            <tr>
                {{-- Fixed columns --}}
                <td class="td-sn">{{ $idx + 1 }}</td>

                @if($showPosTerm || $showPosCum)
                    <td class="td-pos">
                        <div class="pos-pair">
                            @if($showPosTerm)
                                <span class="pos-t">T:{{ bsOrdinal($posTerm) }}</span>
                            @endif
                            @if($showPosCum)
                                <span class="pos-c">C:{{ bsOrdinal($posCum) }}</span>
                            @endif
                        </div>
                    </td>
                @endif

                @if($showAdmNo)
                    <td class="td-adm">{{ $stu['admissionno'] }}</td>
                @endif

                <td class="td-student">
                    {{ $fullName }}
                    @if(!empty($stu['arm']))
                        <span style="font-size:5.5px;color:#64748b;"> ({{ $stu['arm'] }})</span>
                    @endif
                </td>

                @if($showGender)
                    <td style="font-size:6.5px;">{{ substr($stu['gender'] ?? '', 0, 1) }}</td>
                @endif

                {{-- Per-subject columns --}}
                @foreach($subjects as $subId => $subInfo)
                    @php
                        $sd = $stu['subjects'][$subId] ?? [];
                        $g   = $sd['grade'] ?? '-';
                        $gc  = bsGradeClass($g);
                        $bf  = (float) ($sd['bf']    ?? 0);
                        $tot = (float) ($sd['total'] ?? 0);
                        $cum = (float) ($sd['cum']   ?? 0);

                        $ca1  = (float) ($sd['ca1']  ?? 0);
                        $ca2  = (float) ($sd['ca2']  ?? 0);
                        $ca3  = (float) ($sd['ca3']  ?? 0);
                        $exam = (float) ($sd['exam'] ?? 0);

                        $spCC = $sd['pos_class_cum']   ?? null;
                        $spCT = $sd['pos_class_total'] ?? null;
                        $spAT = $sd['pos_arm_total']   ?? null;
                        $spAK = $sd['pos_arm_cum']     ?? null;
                    @endphp

                    {{-- CA1/CA2/CA3/Exam --}}
                    @if($showCA1)
                        <td class="sub-boundary" style="border-left:1.5px solid #2563eb;">
                            {{ $ca1 > 0 ? number_format($ca1, 1) : '—' }}
                        </td>
                    @endif
                    @if($showCA2)
                        <td>{{ $ca2 > 0 ? number_format($ca2, 1) : '—' }}</td>
                    @endif
                    @if($showCA3)
                        <td>{{ $ca3 > 0 ? number_format($ca3, 1) : '—' }}</td>
                    @endif
                    @if($showExam)
                        <td>{{ $exam > 0 ? number_format($exam, 1) : '—' }}</td>
                    @endif

                    {{-- Total --}}
                    @if($showTotal)
                        <td class="{{ $gc }}">{{ $tot > 0 ? number_format($tot, 1) : '—' }}</td>
                    @endif

                    {{-- BF --}}
                    @if($showBF)
                        <td class="{{ $bf > 0 ? 'td-bf-has' : 'td-bf-none' }}">
                            {{ $bf > 0 ? number_format($bf, 1) : '—' }}
                        </td>
                    @endif

                    {{-- Cum --}}
                    @if($showCum)
                        <td class="{{ $gc }}" style="font-weight:700;"
                            title="{{ $bf > 0
                                ? '('.number_format($bf,1).' + '.number_format($tot,1).') ÷ 2 = '.number_format($cum,2)
                                : 'Term 1 — Cum = Total' }}">
                            {{ $cum > 0 ? number_format($cum, 2) : '—' }}
                        </td>
                    @endif

                    {{-- Grade --}}
                    @if($showGrade)
                        <td class="{{ $gc }}" style="font-weight:700;">{{ $g }}</td>
                    @endif

                    {{-- Class Pos (Cum) --}}
                    @if($showSubPosCC)
                        <td class="sp-cc" title="Class Pos (Cum)">{{ bsOrdinal($spCC) }}</td>
                    @endif

                    {{-- Class Pos (Total) --}}
                    @if($showSubPosCT)
                        <td class="sp-ct" title="Class Pos (Total)">{{ bsOrdinal($spCT) }}</td>
                    @endif

                    {{-- Arm Pos (Total) --}}
                    @if($showSubPosAT)
                        <td class="sp-at" title="Arm Pos (Total)">{{ bsOrdinal($spAT) }}</td>
                    @endif

                    {{-- Arm Pos (Cum) --}}
                    @if($showSubPosAK)
                        <td class="sp-ak" title="Arm Pos (Cum)">{{ bsOrdinal($spAK) }}</td>
                    @endif

                    {{-- Class Avg --}}
                    @if($showAvg)
                        <td style="color:#64748b;font-size:6.5px;">{{ $subjectStats[$subId]['avg'] ?? '—' }}</td>
                    @endif

                    {{-- Remark --}}
                    @if($showRemark)
                        <td style="font-size:6px;">{{ $sd['remark'] ?? '—' }}</td>
                    @endif
                @endforeach

                {{-- Promotion --}}
                @if($showPromoStatus)
                    @php
                        $pStatus = $stu['promotion_status'] ?? 'awaiting';
                        $pLabel  = $stu['promotion_label']  ?? 'Awaiting';
                        $pIcon   = match($pStatus) {
                            'promoted'      => '✅',
                            'trial'         => '⚠️',
                            'see_principal' => '👤',
                            'repeated'      => '🔁',
                            default         => '⏳',
                        };
                    @endphp
                    <td class="td-promo">
                        <span class="promo-pill promo-{{ $pStatus }}" title="{{ $pLabel }}">
                            {{ $pIcon }} {{ ucfirst(str_replace('_', ' ', $pStatus)) }}
                        </span>
                    </td>
                @endif

                @if($showPromoLabel)
                    <td class="td-promo" style="font-weight:600;color:#5b21b6;">
                        {{ $stu['promotion_label'] ?? '—' }}
                    </td>
                @endif

                @if($showPromoRule)
                    <td class="td-promo" style="color:#64748b;">
                        {{ $stu['promotion_rule_applied'] ? \Illuminate\Support\Str::limit($stu['promotion_rule_applied'], 20) : '—' }}
                    </td>
                @endif
            </tr>
        @endforeach

        {{-- ══ Stats rows ══ --}}
        @php
        $statDefs = [
            ['stats-avg','CLASS AVG','avg'],
            ['stats-hi', 'HIGHEST',  'highest'],
            ['stats-lo', 'LOWEST',   'lowest'],
        ];
        @endphp
        @foreach($statDefs as [$rowClass, $label, $key])
            <tr class="{{ $rowClass }}">
                <td class="stats-lbl" colspan="{{ $frozenCols }}">{{ $label }}</td>
                @foreach($subjects as $subId => $subInfo)
                    @php $st = $subjectStats[$subId] ?? []; @endphp
                    @if($showCA1) <td>—</td> @endif
                    @if($showCA2) <td>—</td> @endif
                    @if($showCA3) <td>—</td> @endif
                    @if($showExam) <td>—</td> @endif
                    @if($showTotal)    <td>{{ $st[$key] ?? '—' }}</td> @endif
                    @if($showBF)       <td>—</td> @endif
                    @if($showCum)      <td>—</td> @endif
                    @if($showGrade)    <td>—</td> @endif
                    @if($showSubPosCC) <td>—</td> @endif
                    @if($showSubPosCT) <td>—</td> @endif
                    @if($showSubPosAT) <td>—</td> @endif
                    @if($showSubPosAK) <td>—</td> @endif
                    @if($showAvg)      <td>{{ $key === 'avg' ? ($st['avg'] ?? '—') : '—' }}</td> @endif
                    @if($showRemark)   <td>—</td> @endif
                @endforeach
                @if($showPromoStatus) <td>—</td> @endif
                @if($showPromoLabel)  <td>—</td> @endif
                @if($showPromoRule)   <td>—</td> @endif
            </tr>
        @endforeach

    </tbody>
</table>
</div>

{{-- ══════════════════════════════════════════════════════════════
     SUBJECT PERFORMANCE SUMMARY
══════════════════════════════════════════════════════════════ --}}
<div class="subj-summary">
    <h3>■ Subject Performance Summary</h3>
    <table class="subj-tbl">
        <thead>
            <tr>
                <th style="min-width:120px;">Subject</th>
                <th>Class Avg</th>
                <th>Highest</th>
                <th>Lowest</th>
                <th>Students</th>
                <th>Passed</th>
                <th>Failed</th>
                <th>Pass Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subId => $subInfo)
                @php
                    $st = $subjectStats[$subId] ?? [];
                    $p  = $st['passed'] ?? 0;
                    $f  = $st['failed'] ?? 0;
                    $t  = $p + $f;
                    $pr = $t > 0 ? round($p / $t * 100) : 0;
                @endphp
                <tr>
                    <td>
                        {{ $subInfo['subject_name'] }}
                        @if(!empty($subInfo['subject_code']))
                            <span style="color:#94a3b8;font-size:6px;"> ({{ $subInfo['subject_code'] }})</span>
                        @endif
                    </td>
                    <td style="font-weight:700;">{{ $st['avg'] ?? '—' }}</td>
                    <td style="color:#16a34a;font-weight:700;">{{ $st['highest'] ?? '—' }}</td>
                    <td style="color:#dc2626;font-weight:700;">{{ $st['lowest'] ?? '—' }}</td>
                    <td>{{ $t }}</td>
                    <td style="color:#16a34a;">{{ $p }}</td>
                    <td style="color:#dc2626;">{{ $f }}</td>
                    <td class="{{ $pr >= 50 ? 'pass-rate-ok' : 'pass-rate-bad' }}">{{ $pr }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ══════════════════════════════════════════════════════════════
     SIGNATURE BLOCK
══════════════════════════════════════════════════════════════ --}}
<div class="sig-block">
    <table>
        <tr>
            @foreach(['Class Teacher','Head of Department','Vice Principal','Principal'] as $sig)
                <td>
                    <div class="sig-line">{{ $sig }}</div>
                </td>
            @endforeach
        </tr>
    </table>
</div>

</body>
</html>