<?php

namespace App\Exports;

use App\Models\Broadsheets;
use App\Models\SchoolInformation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Log;

/**
 * Topclass admin scoresheet export.
 *
 * Layout (must match exports/admin_scoresheet_export.blade.php):
 *   Rows 1-6 : header block (row 6 = column headings)
 *   Row 7+   : data
 *   A SN | B Admission No | C Student Name           (locked)
 *   D CA1 | E CA2 | F CA3 | G Exam                   (editable)
 *   H Total | I BF | J Cum | K Grade | L Position | M Remark | N Class Avg   (locked)
 */
class AdminRecordsheetExport implements FromView, ShouldAutoSize, WithStyles, WithEvents, WithProperties
{
    use Exportable;

    // Fixed score columns in topclass (stored directly on broadsheets)
    public const SCORE_COLUMNS = 4;   // ca1, ca2, ca3, exam
    public const CALC_COLUMNS  = 7;   // total, bf, cum, grade, position, remark, avg

    protected int $schoolclassId;
    protected int $subjectclassId;
    protected int $termId;
    protected int $sessionId;
    protected int $staffId;
    protected string $password;

    public function __construct(
        int $schoolclassId,
        int $subjectclassId,
        int $termId,
        int $sessionId,
        int $staffId
    ) {
        $this->schoolclassId  = $schoolclassId;
        $this->subjectclassId = $subjectclassId;
        $this->termId         = $termId;
        $this->sessionId      = $sessionId;
        $this->staffId        = $staffId;
        $this->password       = $this->generateFilePassword();
    }

    protected function generateFilePassword(): string
    {
        $subjectClass = \App\Models\Subjectclass::with('subject')->find($this->subjectclassId);
        $schoolclass  = \App\Models\Schoolclass::find($this->schoolclassId);
        $term         = \App\Models\Schoolterm::find($this->termId);
        $session      = \App\Models\Schoolsession::find($this->sessionId);

        $subjectCode = ($subjectClass && $subjectClass->subject) ? $subjectClass->subject->subject_code : 'SUBJ';
        $className   = $schoolclass ? $schoolclass->schoolclass : 'CLASS';
        $termName    = $term ? substr(preg_replace('/[^a-zA-Z]/', '', $term->term), 0, 3) : 'TRM';
        $sessionYear = $session ? preg_replace('/[^0-9]/', '', $session->session) : date('Y');

        $password = strtoupper($subjectCode . '_' . $className . '_' . $termName . '_' . $sessionYear);
        return preg_replace('/[^A-Z0-9_]/', '', $password);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function view(): View
    {
        $broadsheets = Broadsheets::query()
            ->where('broadsheets.term_id', $this->termId)
            ->where('broadsheets.subjectclass_id', $this->subjectclassId)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subjectclass', function ($join) {
                $join->on('subjectclass.id', '=', 'broadsheets.subjectclass_id')
                    ->on('broadsheet_records.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records.schoolclass_id', '=', 'subjectclass.schoolclassid')
                    ->where('subjectclass.id', $this->subjectclassId);
            })
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheet_records.session_id', $this->sessionId)
            ->where('schoolclass.id', $this->schoolclassId)
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get([
                'broadsheets.id',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentRegistration.othername as mname',
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolterm.term',
                'schoolsession.session',
                'subjectclass.id as subjectclid',
                'broadsheets.staff_id',
                'broadsheets.term_id',
                'broadsheet_records.session_id as sessionid',
                'users.name as staffname',
                'studentpicture.picture',

                // ── The fixed score columns that were missing from the export ──
                'broadsheets.ca1',
                'broadsheets.ca2',
                'broadsheets.ca3',
                'broadsheets.exam',

                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'broadsheets.subject_position_class as position',
                'broadsheets.remark',
                'broadsheets.avg',
                'broadsheets.cmin',
                'broadsheets.cmax',
            ]);

        Log::info('Broadsheets retrieved count: ' . $broadsheets->count());

        $school = SchoolInformation::first();

        return view('exports.admin_scoresheet_export', [
            'broadsheets' => $broadsheets,
            'school'      => $school,
        ]);
    }

    public function properties(): array
    {
        $subjectClass = \App\Models\Subjectclass::with('subject')->find($this->subjectclassId);
        $schoolclass  = \App\Models\Schoolclass::find($this->schoolclassId);
        $term         = \App\Models\Schoolterm::find($this->termId);
        $session      = \App\Models\Schoolsession::find($this->sessionId);

        $subjectName = ($subjectClass && $subjectClass->subject) ? $subjectClass->subject->subject : 'subject';
        $className   = $schoolclass ? $schoolclass->schoolclass : 'class';
        $termName    = $term ? $term->term : 'term';
        $sessionName = $session ? $session->session : 'session';

        return [
            'creator'        => auth()->user()->name ?? 'Admin',
            'lastModifiedBy' => auth()->user()->name ?? 'Admin',
            'title'          => "[ADMIN] {$subjectName}_{$className}_{$termName}_{$sessionName}_Scoresheet",
            'description'    => "Admin-exported scoresheet. Password: {$this->password}",
            'subject'        => $subjectName,
            'keywords'       => 'scoresheet,marks,excel,export,admin,password_protected',
            'category'       => 'Education',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $scoreCount   = self::SCORE_COLUMNS;
        $calcCount    = self::CALC_COLUMNS;
        $lastColIndex = 3 + $scoreCount + $calcCount;
        $lastCol      = Coordinate::stringFromColumnIndex($lastColIndex);

        // Unlock all data cells first (rows 7 and below)
        $sheet->getStyle("A7:{$lastCol}1000")
            ->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

        // Lock header rows 1-6
        $sheet->getStyle("A1:{$lastCol}6")
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock SN (A), Adm (B), Name (C)
        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getStyle("{$col}7:{$col}1000")
                ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        }

        // Lock calculated columns (after CA1/CA2/CA3/Exam)
        for ($i = 0; $i < $calcCount; $i++) {
            $col = Coordinate::stringFromColumnIndex(4 + $scoreCount + $i);
            $sheet->getStyle("{$col}7:{$col}1000")
                ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        }

        // Bold headers
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A2:{$lastCol}2")->getFont()->setBold(true);
        $sheet->getStyle("A3:{$lastCol}3")->getFont()->setBold(true);
        $sheet->getStyle("A6:{$lastCol}6")->getFont()->setBold(true);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A7');

                // Sheet protection only (workbook password often causes corruption)
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setPassword($this->password);
            },
        ];
    }
}