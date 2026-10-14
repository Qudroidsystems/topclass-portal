<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\MyScoreSheetController;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\ScoresheetLock;
use App\Models\Subjectclass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Score entry from the app — TopClass edition.
 *
 * TopClass keeps four fixed columns on each broadsheet row: CA1, CA2, CA3 and
 * Exam, each marked out of 100, with
 *   Total = ((CA1 + CA2 + CA3) ÷ 3 + Exam) ÷ 2
 *   Cum   = Total (first term) or (BF + Total) ÷ 2.
 * The app speaks one format for every school, so the four columns are offered
 * as "assessments" with ids 1–4. Saving goes through the web controller's
 * bulk update, so totals, grades and positions are worked out exactly as on
 * the portal. A teacher may only open the subject classes assigned to them.
 */
class ScoresheetController extends MyScoreSheetController
{
    use RespondsWithApi;

    /** Assessment id in the app → broadsheet column. */
    protected const COLUMNS = [1 => 'ca1', 2 => 'ca2', 3 => 'ca3', 4 => 'exam'];
    protected const NAMES = [1 => 'CA 1', 2 => 'CA 2', 3 => 'CA 3', 4 => 'Exam'];
    protected const MAX = 100.0;

    public function sheet(Request $request, int $subjectclass)
    {
        $ctx = $this->context($request, $subjectclass);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) return $ctx;
        [$sjc, $staffId, $classId, $termId, $sessionId] = $ctx;

        if ($request->boolean('recalculate')) $this->recalc($subjectclass, $staffId, $classId, $termId, $sessionId);

        return $this->ok($this->payload($sjc, $staffId, $classId, $termId, $sessionId));
    }

    /**
     * POST staff/scoresheets/score { broadsheet_id, assessment_id (1–4), score }
     */
    public function saveScore(Request $request)
    {
        $request->validate([
            'broadsheet_id' => 'required|integer',
            'assessment_id' => 'required|integer|in:1,2,3,4',
            'score'         => 'required|numeric|min:0|max:100',
        ]);
        $b = Broadsheets::find((int) $request->broadsheet_id);
        if (!$b) return $this->fail('Record not found.', 404, 'not_found');
        if (!$this->ownsSubjectclass($request, (int) $b->subjectclass_id)) {
            return $this->fail('This score sheet is not assigned to you.', 403, 'forbidden');
        }

        $values = ['ca1' => $b->ca1, 'ca2' => $b->ca2, 'ca3' => $b->ca3, 'exam' => $b->exam];
        $values[self::COLUMNS[(int) $request->assessment_id]] = (float) $request->score;
        $request->merge($values);

        $res = $this->update($request, $b->id);
        $body = $res->getData(true);
        if (empty($body['success'])) {
            return $this->fail($body['message'] ?? 'Could not save.', $res->getStatusCode() >= 400 ? $res->getStatusCode() : 422, !empty($body['locked']) ? 'locked' : 'save_failed');
        }
        return $this->ok(array_map(fn ($v) => is_string($v) && is_numeric($v) ? (float) $v : $v, (array) ($body['data'] ?? [])), $body['message'] ?? 'Saved.');
    }

    /**
     * POST staff/scoresheets/{subjectclass}/bulk
     * { term_id, session_id, scores: [ { id, assessments: { "1": 75, "4": 60 }, base?: { "1": 70 } } ] }
     * Columns not sent keep their value. With `base`, a score changed on the
     * school since the teacher saw it is not overwritten (reported in meta.conflicts).
     */
    public function saveBulk(Request $request, int $subjectclass)
    {
        $ctx = $this->context($request, $subjectclass);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) return $ctx;
        [$sjc, $staffId, $classId, $termId, $sessionId] = $ctx;

        $input = collect($request->input('scores', []));
        $ids = $input->pluck('id')->map(fn ($v) => (int) $v)->all();
        $rows = Broadsheets::whereIn('id', $ids)->get(['id', 'subjectclass_id', 'ca1', 'ca2', 'ca3', 'exam'])->keyBy('id');
        if ($rows->contains(fn ($r) => (int) $r->subjectclass_id !== $subjectclass) || $rows->count() !== count(array_unique($ids))) {
            return $this->fail('Some rows do not belong to this score sheet.', 422, 'invalid_rows');
        }

        $scores = [];
        $conflicts = [];
        $errors = [];
        foreach ($input as $row) {
            $id = (int) ($row['id'] ?? 0);
            $b = $rows[$id];
            $vals = ['ca1' => (float) ($b->ca1 ?? 0), 'ca2' => (float) ($b->ca2 ?? 0), 'ca3' => (float) ($b->ca3 ?? 0), 'exam' => (float) ($b->exam ?? 0)];
            $changed = false;
            foreach ((array) ($row['assessments'] ?? []) as $aid => $v) {
                $col = self::COLUMNS[(int) $aid] ?? null;
                if (!$col || !is_numeric($v)) continue;
                if ($v < 0 || $v > self::MAX) { $errors[] = "Row {$id}: " . self::NAMES[(int) $aid] . " must be 0–100."; continue; }
                $base = is_array($row['base'] ?? null) && array_key_exists($aid, $row['base']) ? $row['base'][$aid] : '__none__';
                if ($base !== '__none__') {
                    $now = $b->{$col};
                    $same = ($base === null || $base === '') ? ($now === null || (float) $now == 0.0) : ($now !== null && abs((float) $now - (float) $base) < 0.0001);
                    if (!$same) {
                        $conflicts[] = ['broadsheet_id' => $id, 'key' => (int) $aid, 'is_sub' => false, 'yours' => (float) $v, 'server' => $now !== null ? (float) $now : null];
                        continue;
                    }
                }
                $vals[$col] = (float) $v;
                $changed = true;
            }
            if ($changed) $scores[] = ['id' => $id] + $vals;
        }

        if ($errors && !$scores) return $this->fail($errors[0], 422, 'invalid_scores', ['scores' => $errors]);

        $message = null;
        if ($scores) {
            $request->merge([
                'scores' => $scores, 'term_id' => $termId, 'session_id' => $sessionId,
                'subjectclass_id' => $subjectclass, 'staff_id' => $staffId, 'schoolclass_id' => $classId,
            ]);
            $res = $this->bulkUpdateScores($request);
            $body = $res->getData(true);
            if (empty($body['success'])) {
                $status = $res->getStatusCode();
                return $this->fail($body['message'] ?? 'Could not save.', $status >= 400 ? $status : 422, $status === 423 ? 'locked' : 'save_failed');
            }
            $message = $body['message'] ?? null;
        }

        if ($conflicts) {
            $message = ($message ? $message . ' ' : '') . count($conflicts) . ' score(s) were changed elsewhere and were not overwritten.';
        }

        return $this->ok($this->payload($sjc, $staffId, $classId, $termId, $sessionId), $message ?? 'Scores saved.', 200,
            array_filter(['conflicts' => $conflicts ?: null, 'warnings' => $errors ?: null]));
    }

    // ── Excel ───────────────────────────────────────────────────────────

    /**
     * GET staff/scoresheets/{subjectclass}/excel — a protected template:
     * CA1, CA2, CA3 and Exam can be typed in; everything else is locked.
     */
    public function excelDownload(Request $request, int $subjectclass)
    {
        $ctx = $this->context($request, $subjectclass);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) return $ctx;
        [$sjc, $staffId, $classId, $termId, $sessionId] = $ctx;

        $rows = $this->getBroadsheets($staffId, $termId, $sessionId, $classId, $subjectclass)->sortBy(fn ($b) => strtolower($b->lname . ' ' . $b->fname))->values();
        $school = \App\Models\SchoolInformation::query()->value('school_name') ?? config('app.name');
        $term = DB::table('schoolterm')->where('id', $termId)->value('term');
        $session = DB::table('schoolsession')->where('id', $sessionId)->value('session');

        $book = new Spreadsheet();
        $sh = $book->getActiveSheet();
        $sh->setTitle('Scores');
        $sh->setCellValue('A1', $school);
        $sh->setCellValue('A2', "Subject: {$sjc->subject} | Class: {$sjc->class_name} | {$term} | {$session}");
        $sh->setCellValue('A3', 'Type scores (0–100) in the CA1, CA2, CA3 and Exam columns only, save, then upload the file in the app or on the portal.');
        $sh->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sh->getStyle('A3')->getFont()->setItalic(true);

        $head = ['#', 'Admission No', 'Student Name', 'CA1', 'CA2', 'CA3', 'Exam', 'Total', 'Cum', 'Grade', 'Row ID'];
        $sh->fromArray($head, null, 'A5');
        $sh->getStyle('A5:K5')->getFont()->setBold(true);
        $sh->getStyle('A5:K5')->getFill()->setFillType('solid')->getStartColor()->setRGB('1E3A5F');
        $sh->getStyle('A5:K5')->getFont()->getColor()->setRGB('FFFFFF');

        $r = 6;
        foreach ($rows as $i => $b) {
            $sh->fromArray([
                $i + 1, $b->admissionno, trim($b->lname . ' ' . $b->fname . ' ' . $b->mname),
                $b->ca1 !== null ? (float) $b->ca1 : null, $b->ca2 !== null ? (float) $b->ca2 : null,
                $b->ca3 !== null ? (float) $b->ca3 : null, $b->exam !== null ? (float) $b->exam : null,
                (float) $b->total, (float) $b->cum, $b->grade, (int) $b->id,
            ], null, 'A' . $r);
            $sh->getStyle("D{$r}:G{$r}")->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
            $sh->getStyle("D{$r}:G{$r}")->getFill()->setFillType('solid')->getStartColor()->setRGB('FFF8E1');
            $r++;
        }
        foreach (['A' => 5, 'B' => 16, 'C' => 32, 'D' => 9, 'E' => 9, 'F' => 9, 'G' => 9, 'H' => 9, 'I' => 9, 'J' => 8, 'K' => 9] as $col => $w) {
            $sh->getColumnDimension($col)->setWidth($w);
        }
        $sh->getColumnDimension('K')->setVisible(false);
        $sh->getProtection()->setSheet(true)->setPassword(substr(md5('tc' . $subjectclass . $termId . $sessionId), 0, 10));

        $name = preg_replace('/[^A-Za-z0-9]+/', '_', "{$sjc->subject}_{$sjc->class_name}_{$term}_{$session}") . '_scoresheet.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'tcx');
        (new Xlsx($book))->save($tmp);

        return response()->download($tmp, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * POST staff/scoresheets/{subjectclass}/excel (multipart: file, term_id, session_id)
     * Reads CA1/CA2/CA3/Exam by column heading; rows are matched by the hidden
     * row id, or by admission number.
     */
    public function excelUpload(Request $request, int $subjectclass)
    {
        $ctx = $this->context($request, $subjectclass);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) return $ctx;
        [$sjc, $staffId, $classId, $termId, $sessionId] = $ctx;

        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

        $payload = $this->payload($sjc, $staffId, $classId, $termId, $sessionId);
        if (!$payload['editable']) return $this->fail($payload['lock_message'] ?? 'This score sheet is locked.', 423, 'locked');

        try {
            $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return $this->fail('The file could not be read: ' . $e->getMessage(), 422, 'invalid_file');
        }

        $headerRow = null;
        $cols = [];
        foreach ($sheet as $i => $row) {
            $norm = array_map(fn ($v) => strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $v)), $row);
            if (in_array('admissionno', $norm, true)) {
                $headerRow = $i;
                foreach ($norm as $c => $h) {
                    if (in_array($h, ['ca1', 'ca2', 'ca3', 'exam', 'admissionno', 'rowid'], true)) $cols[$h] = $c;
                }
                break;
            }
        }
        if ($headerRow === null || !array_intersect_key($cols, array_flip(['ca1', 'ca2', 'ca3', 'exam']))) {
            return $this->fail('No "Admission No" heading with CA1/CA2/CA3/Exam columns was found. Please use the template from Download Excel.', 422, 'import_failed');
        }

        $existing = Broadsheets::where('subjectclass_id', $subjectclass)->where('term_id', $termId)
            ->join('broadsheet_records as br', 'br.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('br.session_id', $sessionId)
            ->leftJoin('studentRegistration as s', 's.id', '=', 'br.student_id')
            ->get(['broadsheets.id', 'broadsheets.ca1', 'broadsheets.ca2', 'broadsheets.ca3', 'broadsheets.exam', 's.admissionNO as adm']);
        $byId = $existing->keyBy('id');
        $byAdm = $existing->keyBy(fn ($r) => strtolower(trim((string) $r->adm)));

        $scores = [];
        $warnings = [];
        foreach (array_slice($sheet, $headerRow + 1, null, true) as $i => $row) {
            $adm = isset($cols['admissionno']) ? trim((string) ($row[$cols['admissionno']] ?? '')) : '';
            $rid = isset($cols['rowid']) ? (int) ($row[$cols['rowid']] ?? 0) : 0;
            if ($adm === '' && !$rid) continue;
            $b = ($rid && isset($byId[$rid])) ? $byId[$rid] : ($byAdm[strtolower($adm)] ?? null);
            if (!$b) { $warnings[] = ['row' => $i + 1, 'errors' => ["{$adm}: not on this score sheet"]]; continue; }

            $vals = ['ca1' => (float) ($b->ca1 ?? 0), 'ca2' => (float) ($b->ca2 ?? 0), 'ca3' => (float) ($b->ca3 ?? 0), 'exam' => (float) ($b->exam ?? 0)];
            $changed = false;
            $rowErrors = [];
            foreach (['ca1', 'ca2', 'ca3', 'exam'] as $col) {
                if (!isset($cols[$col])) continue;
                $v = $row[$cols[$col]] ?? null;
                if ($v === null || $v === '') continue;
                if (!is_numeric($v) || $v < 0 || $v > self::MAX) { $rowErrors[] = strtoupper($col) . ": {$v} is not 0–100"; continue; }
                if ($b->{$col} !== null && abs((float) $b->{$col} - (float) $v) < 0.0001) continue;
                $vals[$col] = (float) $v;
                $changed = true;
            }
            if ($rowErrors) $warnings[] = ['row' => $i + 1, 'errors' => array_map(fn ($e) => ($adm ?: "Row {$i}") . ' — ' . $e, $rowErrors)];
            if ($changed) $scores[] = ['id' => (int) $b->id] + $vals;
        }

        if (!$scores) {
            if (!$warnings) {
                return $this->ok($payload, 'No changes — every score in the file is already on the sheet.', 200, ['imported' => 0, 'warnings' => []]);
            }
            $lines = array_map(fn ($f) => 'Row ' . $f['row'] . ': ' . implode('; ', $f['errors']), $warnings);
            return $this->fail('No scores were imported.', 422, 'import_failed', ['rows' => $lines]);
        }

        $request->merge([
            'scores' => $scores, 'term_id' => $termId, 'session_id' => $sessionId,
            'subjectclass_id' => $subjectclass, 'staff_id' => $staffId, 'schoolclass_id' => $classId,
        ]);
        $res = $this->bulkUpdateScores($request);
        $body = $res->getData(true);
        if (empty($body['success'])) {
            return $this->fail($body['message'] ?? 'Import failed.', $res->getStatusCode() >= 400 ? $res->getStatusCode() : 422, 'import_failed');
        }

        $count = count($scores);
        return $this->ok(
            $this->payload($sjc, $staffId, $classId, $termId, $sessionId),
            $warnings ? "Imported {$count} record(s) with " . count($warnings) . ' warning(s).' : "Imported {$count} record(s).",
            200, ['imported' => $count, 'warnings' => $warnings]
        );
    }

    /** GET staff/scoresheets/{subjectclass}/marksheet — printable PDF of every score. */
    public function marksheet(Request $request, int $subjectclass)
    {
        $ctx = $this->context($request, $subjectclass);
        if ($ctx instanceof \Illuminate\Http\JsonResponse) return $ctx;
        [$sjc, $staffId, $classId, $termId, $sessionId] = $ctx;

        $data = $this->payload($sjc, $staffId, $classId, $termId, $sessionId);
        if (!$data['students']->count()) return $this->fail('No students on this score sheet.', 404, 'not_found');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('api.marksheet', [
            'sheet'   => $data,
            'school'  => \App\Models\SchoolInformation::first(),
            'teacher' => $request->user()->name,
        ])->setPaper('a4', 'landscape');

        $name = preg_replace('/[^A-Za-z0-9]+/', '_', "{$sjc->subject}_{$sjc->class_name}_marksheet") . '.pdf';
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $name . '"']);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    protected function recalc(int $subjectclass, int $staffId, int $classId, int $termId, int $sessionId): void
    {
        $this->getBroadsheets($staffId, $termId, $sessionId, $classId, $subjectclass); // re-derives total/bf/cum/grade
        $this->updateClassMetrics($subjectclass, $staffId, $termId, $sessionId);
        $this->updateSubjectPositions($subjectclass, $staffId, $termId, $sessionId);
        $this->updateClassPositions($classId, $termId, $sessionId);
    }

    protected function context(Request $request, int $subjectclass)
    {
        $request->validate([
            'term_id'    => 'required|integer|exists:schoolterm,id',
            'session_id' => 'required|integer|exists:schoolsession,id',
        ]);

        $sjc = DB::table('subjectclass as sjc')
            ->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')
            ->join('subject as s', 's.id', '=', 'sjc.subjectid')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->where('sjc.id', $subjectclass)
            ->first(['sjc.id', 'sjc.schoolclassid', 'st.staffid', 's.subject', 's.subject_code',
                     DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_name")]);

        if (!$sjc) return $this->fail('Score sheet not found.', 404, 'not_found');
        if ((int) $sjc->staffid !== (int) $request->user()->id) {
            return $this->fail('This score sheet is not assigned to you.', 403, 'forbidden');
        }

        return [$sjc, (int) $sjc->staffid, (int) $sjc->schoolclassid, (int) $request->term_id, (int) $request->session_id];
    }

    protected function ownsSubjectclass(Request $request, int $subjectclass): bool
    {
        return DB::table('subjectclass as sjc')->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')
            ->where('sjc.id', $subjectclass)->where('st.staffid', $request->user()->id)->exists();
    }

    protected function payload(object $sjc, int $staffId, int $classId, int $termId, int $sessionId): array
    {
        $rows = $this->getBroadsheets($staffId, $termId, $sessionId, $classId, (int) $sjc->id)
            ->sortBy(fn ($b) => strtolower($b->lname . ' ' . $b->fname))->values();

        $lock = ScoresheetLock::where(['subjectclass_id' => $sjc->id, 'term_id' => $termId, 'session_id' => $sessionId, 'is_active' => true])->first();
        $editingEnabled = (bool) (Subjectclass::find($sjc->id)?->teacher_editing_enabled ?? true);
        $senior = (bool) DB::table('schoolclass as c')->join('classcategories as cc', 'cc.id', '=', 'c.classcategoryid')->where('c.id', $classId)->value('cc.is_senior');

        return [
            'subjectclass_id' => (int) $sjc->id,
            'subject'         => $sjc->subject,
            'subject_code'    => $sjc->subject_code,
            'class_id'        => $classId,
            'class_name'      => $sjc->class_name,
            'term_id'         => $termId,
            'term'            => DB::table('schoolterm')->where('id', $termId)->value('term'),
            'session_id'      => $sessionId,
            'session'         => DB::table('schoolsession')->where('id', $sessionId)->value('session'),
            'is_senior'       => $senior,
            'editable'        => $editingEnabled && !$lock,
            'lock_message'    => $lock ? ($lock->reason ?? $lock->lock_reason ?? 'This score sheet has been locked by an administrator.')
                : (!$editingEnabled ? 'Teacher editing has been disabled for this subject by an administrator.' : null),
            // Total = ((CA1 + CA2 + CA3) ÷ 3 + Exam) ÷ 2 — the app previews it this way.
            'total_formula'   => 'avg_ca_exam',
            'assessments'     => collect(self::COLUMNS)->map(fn ($col, $id) => [
                'id' => $id, 'name' => self::NAMES[$id], 'max_score' => self::MAX, 'sub_assessments' => [],
            ])->values(),
            'students' => $rows->map(fn ($b) => [
                'broadsheet_id' => (int) $b->id,
                'student_id'    => (int) $b->student_id,
                'admission_no'  => $b->admissionno,
                'first_name'    => $b->fname,
                'last_name'     => $b->lname,
                'other_name'    => $b->mname,
                'photo_url'     => $this->studentPhotoUrl($b->picture),
                'scores'        => (object) collect(self::COLUMNS)->mapWithKeys(fn ($col, $id) => [(string) $id => $b->{$col} !== null ? (float) $b->{$col} : null])->all(),
                'sub_scores'    => (object) [],
                'total'         => $this->num($b->total),
                'bf'            => $this->num($b->bf),
                'cum'           => $this->num($b->cum),
                'cum_average'   => $this->num($b->cum),
                'grade'         => $b->grade,
                'remark'        => $b->remark,
                'position'      => $b->position,
                'arm_position'  => $b->arm_position,
                'class_average' => $this->num($b->avg),
                'locked'        => (bool) $b->is_locked,
                'vetted'        => (int) $b->vettedstatus === 1,
            ])->values(),
        ];
    }
}
