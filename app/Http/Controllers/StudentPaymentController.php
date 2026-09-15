<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\SchoolBillModel;
use App\Models\SchoolBillTermSession;
use App\Models\StudentBillPayment;
use App\Models\ScholarshipAssignment;
use App\Models\DiscountAssignment;
use App\Models\SchoolInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;

class StudentPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View student payments', ['only' => ['index', 'printReceipt']]);
    }

    // =========================================================================
    // INDEX — Student's Payment Portal
    // =========================================================================
    public function index(Request $request)
    {
        $pagetitle  = 'My Payments';
        $studentId  = auth()->user()->student_id;

        if (!$studentId) {
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        $student = Student::where('id', $studentId)
            ->select('id', 'firstname', 'lastname', 'admissionNo', 'gender')
            ->first();

        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        $sessions = Schoolsession::whereIn('status', ['Current', 'Previous'])
            ->orderBy('id', 'desc')->get(['id', 'session']);

        $terms = Schoolterm::orderBy('id', 'desc')->get(['id', 'term']);

        $selectedSessionId = $request->get('session_id', $sessions->first()?->id);
        $selectedTermId    = $request->get('term_id');

        // Get student's current class data
        $studentClassData = DB::table('studentclass')
            ->where('studentId', $studentId)
            ->join('schoolclass',   'schoolclass.id',   '=', 'studentclass.schoolclassid')
            ->join('schoolterm',    'schoolterm.id',    '=', 'studentclass.termid')
            ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->when($selectedSessionId, fn ($q) => $q->where('schoolsession.id', $selectedSessionId))
            ->select(
                'schoolclass.id   as class_id',
                'schoolclass.schoolclass as class_name',
                'schoolterm.id    as term_id',
                'schoolterm.term  as term_name',
                'schoolsession.id as session_id',
                'schoolsession.session as session_name'
            )
            ->first();

        if (!$studentClassData) {
            return view('student.payments.index', compact(
                'pagetitle', 'student', 'terms', 'sessions', 'selectedSessionId', 'selectedTermId'
            ))->with('error', 'No class registration found for this session.');
        }

        if (!$selectedTermId) {
            $selectedTermId = $studentClassData->term_id;
        }

        // Fetch bills for this class, term & session
        $billAssignments = SchoolBillTermSession::where('class_id', $studentClassData->class_id)
            ->where('termid_id', $selectedTermId)
            ->where('session_id', $selectedSessionId)
            ->where('is_active', true)
            ->with('schoolBill')
            ->orderBy('display_order')
            ->get();

        // Active Scholarship & Discounts
        $scholarshipAssignment = ScholarshipAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->with('scholarship')
            ->first();

        $discountAssignments = DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->with('discount')
            ->get();

        // Process bills
        $bills = collect();
        $totals = ['original' => 0, 'adjusted' => 0, 'paid' => 0, 'outstanding' => 0, 'savings' => 0];

        foreach ($billAssignments as $assignment) {
            $bill = $assignment->schoolBill;
            if (!$bill || !$bill->is_active) continue;

            $adjusted = $this->computeAdjustedAmount(
                $bill, $studentId, $scholarshipAssignment, $discountAssignments
            );

            $amountPaid = (float) StudentBillPayment::where('student_id', $studentId)
                ->where('school_bill_id', $bill->id)
                ->where('termid_id', $selectedTermId)
                ->where('session_id', $selectedSessionId)
                ->value('total_paid') ?? 0;

            $balance  = max(0, $adjusted['adjusted_amount'] - $amountPaid);
            $progress = $adjusted['adjusted_amount'] > 0
                        ? min(100, ($amountPaid / $adjusted['adjusted_amount']) * 100)
                        : 100;

            $bills->push([
                'id'                    => $bill->id,
                'title'                 => $bill->title,
                'description'           => $bill->description,
                'original_amount'       => $adjusted['original_amount'],
                'adjusted_amount'       => $adjusted['adjusted_amount'],
                'scholarship_deduction' => $adjusted['scholarship_deduction'],
                'discount_deduction'    => $adjusted['discount_deduction'],
                'total_savings'         => $adjusted['savings'],
                'amount_paid'           => $amountPaid,
                'balance'               => $balance,
                'progress'              => round($progress, 1),
                'is_paid'               => $balance <= 0,
                'is_partial'            => $amountPaid > 0 && $balance > 0,
                'due_date'              => $bill->due_date?->format('d M Y'),
                'category'              => $bill->category,
            ]);

            $totals['original']    += $adjusted['original_amount'];
            $totals['adjusted']    += $adjusted['adjusted_amount'];
            $totals['paid']        += $amountPaid;
            $totals['outstanding'] += $balance;
            $totals['savings']     += $adjusted['savings'];
        }

        // Payment History
        $paymentHistory = StudentBillPayment::where('student_id', $studentId)
            ->where('termid_id', $selectedTermId)
            ->where('session_id', $selectedSessionId)
            ->with('schoolBill')
            ->orderBy('created_at', 'desc')
            ->get();

        // Payment Trend
        $paymentTrend = $this->buildPaymentTrend($studentId, $selectedSessionId);

        $studentPicture = DB::table('studentpicture')->where('studentid', $studentId)->value('picture');
        $schoolInfo     = SchoolInformation::first();

        $class   = (object) ['id' => $studentClassData->class_id, 'schoolclass' => $studentClassData->class_name];
        $term    = (object) ['id' => $studentClassData->term_id,  'term' => $studentClassData->term_name];
        $session = (object) ['id' => $studentClassData->session_id, 'session' => $studentClassData->session_name];

        return view('student.payments.index', compact(
            'pagetitle', 'student', 'class', 'term', 'session',
            'bills', 'totals', 'paymentHistory', 'paymentTrend',
            'scholarshipAssignment', 'discountAssignments',
            'terms', 'sessions', 'selectedSessionId', 'selectedTermId',
            'studentPicture', 'schoolInfo'
        ));
    }

    // =========================================================================
    // PRINT RECEIPT
    // =========================================================================
    public function printReceipt(Request $request)
    {
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', '512M');

        $studentId         = auth()->user()->student_id;
        $selectedSessionId = $request->get('session_id');
        $selectedTermId    = $request->get('term_id');

        if (!$studentId) {
            return back()->with('error', 'Student profile not found.');
        }

        $student = Student::find($studentId);
        if (!$student) {
            return back()->with('error', 'Student not found.');
        }

        $studentClassData = DB::table('studentclass')
            ->where('studentId', $studentId)
            ->join('schoolclass',   'schoolclass.id',   '=', 'studentclass.schoolclassid')
            ->join('schoolterm',    'schoolterm.id',    '=', 'studentclass.termid')
            ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->when($selectedSessionId, fn ($q) => $q->where('schoolsession.id', $selectedSessionId))
            ->select(
                'schoolclass.id   as class_id',
                'schoolclass.schoolclass as class_name',
                'schoolterm.id    as term_id',
                'schoolterm.term  as term_name',
                'schoolsession.id as session_id',
                'schoolsession.session as session_name'
            )
            ->first();

        if (!$studentClassData) {
            return back()->with('error', 'No class data found.');
        }

        if (!$selectedTermId) {
            $selectedTermId = $studentClassData->term_id;
        }

        $billAssignments = SchoolBillTermSession::where('class_id', $studentClassData->class_id)
            ->where('termid_id', $selectedTermId)
            ->where('session_id', $selectedSessionId ?? $studentClassData->session_id)
            ->where('is_active', true)
            ->with('schoolBill')
            ->get();

        $scholarshipAssignment = ScholarshipAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->with('scholarship')
            ->first();

        $discountAssignments = DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->with('discount')
            ->get();

        $bills  = collect();
        $totals = ['original' => 0, 'adjusted' => 0, 'paid' => 0, 'outstanding' => 0, 'savings' => 0];

        foreach ($billAssignments as $assignment) {
            $bill = $assignment->schoolBill;
            if (!$bill || !$bill->is_active) continue;

            $adjusted = $this->computeAdjustedAmount($bill, $studentId, $scholarshipAssignment, $discountAssignments);

            $amountPaid = (float) StudentBillPayment::where('student_id', $studentId)
                ->where('school_bill_id', $bill->id)
                ->where('termid_id', $selectedTermId)
                ->where('session_id', $selectedSessionId ?? $studentClassData->session_id)
                ->value('total_paid') ?? 0;

            $balance = max(0, $adjusted['adjusted_amount'] - $amountPaid);

            $bills->push([
                'title'                 => $bill->title,
                'description'           => $bill->description,
                'original_amount'       => $adjusted['original_amount'],
                'adjusted_amount'       => $adjusted['adjusted_amount'],
                'amount_paid'           => $amountPaid,
                'balance'               => $balance,
                'scholarship_deduction' => $adjusted['scholarship_deduction'],
                'discount_deduction'    => $adjusted['discount_deduction'],
                'savings'               => $adjusted['savings'],
                'is_paid'               => $balance <= 0,
            ]);

            $totals['original']    += $adjusted['original_amount'];
            $totals['adjusted']    += $adjusted['adjusted_amount'];
            $totals['paid']        += $amountPaid;
            $totals['outstanding'] += $balance;
            $totals['savings']     += $adjusted['savings'];
        }

        $schoolInfo    = SchoolInformation::first();
        $logoBase64    = $this->logoToBase64($schoolInfo);
        $picturePath   = DB::table('studentpicture')->where('studentid', $studentId)->value('picture');
        $pictureBase64 = $this->imageToBase64ForPdf($picturePath);

        $termName    = Schoolterm::find($selectedTermId)?->term ?? 'Term';
        $sessionName = Schoolsession::find($selectedSessionId ?? $studentClassData->session_id)?->session ?? 'Session';

        // Safe Filename (Fix for "/" and "\" error)
        $safeAdmission = Str::slug($student->admissionNo ?? 'student', '-');
        $safeTerm      = Str::slug($termName, '-');
        $filename      = "Payment_Receipt_{$safeAdmission}_{$safeTerm}.pdf";

        $receiptNo = 'RCP-' . strtoupper(substr(md5($studentId . $selectedTermId . $selectedSessionId), 0, 8));

        $pdf = Pdf::loadView('student.payments.receipt-pdf', [
            'student'       => $student,
            'bills'         => $bills,
            'totals'        => $totals,
            'termName'      => $termName,
            'sessionName'   => $sessionName,
            'schoolInfo'    => $schoolInfo,
            'logoBase64'    => $logoBase64,
            'pictureBase64' => $pictureBase64,
            'receiptNo'     => $receiptNo,
            'className'     => $studentClassData->class_name,
            'generatedAt'   => Carbon::now()->format('d M Y, h:i A'),
        ])
        ->setPaper('A4', 'portrait')
        ->setOptions([
            'dpi'                     => 150,
            'defaultFont'             => 'DejaVu Sans',
            'isRemoteEnabled'         => true,
            'isHtml5ParserEnabled'    => true,
        ]);

        return $pdf->download($filename);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function computeAdjustedAmount(
        SchoolBillModel $bill,
        int $studentId,
        ?ScholarshipAssignment $scholarshipAssignment,
        $discountAssignments
    ): array {
        $original = (float) $bill->bill_amount;
        $scholarshipDeduction = 0.0;
        $discountDeduction = 0.0;

        // Scholarship
        if ($scholarshipAssignment && $bill->is_scholarship_eligible) {
            $scholarship = $scholarshipAssignment->scholarship;
            if ($scholarship) {
                if ($scholarshipAssignment->value_type === 'percentage') {
                    $deduction = $original * ($scholarshipAssignment->value / 100);
                    if ($scholarshipAssignment->cap_amount) {
                        $deduction = min($deduction, $scholarshipAssignment->cap_amount);
                    }
                } else {
                    $deduction = min((float) $scholarshipAssignment->value, $original);
                }
                $scholarshipDeduction = $deduction;
            }
        }

        $afterScholarship = max(0, $original - $scholarshipDeduction);

        // Discounts
        if ($bill->is_discount_eligible && $discountAssignments->isNotEmpty()) {
            foreach ($discountAssignments as $da) {
                $discount = $da->discount;
                if (!$discount) continue;

                if (!$discount->stackable_with_scholarship && $scholarshipDeduction > 0) continue;

                if ($discount->applicable_to === 'specific_bills') {
                    $applicableBills = $discount->applicable_bill_ids ?? [];
                    if (!in_array($bill->id, $applicableBills)) continue;
                } elseif ($discount->applicable_to === 'specific_categories') {
                    $applicableCategories = $discount->applicable_categories ?? [];
                    if (!in_array($bill->category, $applicableCategories)) continue;
                }

                $base = max(0, $afterScholarship - $discountDeduction);
                if ($da->value_type === 'percentage') {
                    $d = $base * ($da->value / 100);
                    if ($da->max_amount) $d = min($d, (float) $da->max_amount);
                } else {
                    $d = min((float) $da->value, $base);
                }
                $discountDeduction += $d;
            }
        }

        $adjustedAmount = max(0, $afterScholarship - $discountDeduction);

        return [
            'original_amount'       => $original,
            'scholarship_deduction' => round($scholarshipDeduction, 2),
            'discount_deduction'    => round($discountDeduction, 2),
            'adjusted_amount'       => round($adjustedAmount, 2),
            'savings'               => round($scholarshipDeduction + $discountDeduction, 2),
        ];
    }

    private function buildPaymentTrend(int $studentId, ?int $sessionId): array
    {
        $trend = [];
        $terms = Schoolterm::orderBy('id')->get();

        foreach ($terms as $t) {
            $paid = StudentBillPayment::where('student_id', $studentId)
                ->where('termid_id', $t->id)
                ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
                ->sum('total_paid');

            if ($paid > 0) {
                $trend[$t->term] = (float) $paid;
            }
        }

        return $trend;
    }

    private function logoToBase64($schoolInfo): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                <rect width="80" height="80" rx="40" fill="#1e3a5f"/>
                <text x="40" y="46" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">SCH</text>
            </svg>'
        );

        if (!$schoolInfo || empty($schoolInfo->school_logo)) return $placeholder;

        $paths = [
            storage_path('app/public/' . $schoolInfo->school_logo),
            public_path('storage/' . $schoolInfo->school_logo),
            public_path($schoolInfo->school_logo),
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && filesize($path) > 100) {
                $mime = mime_content_type($path) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        return $placeholder;
    }

    private function imageToBase64ForPdf(?string $path): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="95" viewBox="0 0 80 95">
                <rect width="80" height="95" fill="#e2e8f0"/>
                <circle cx="40" cy="32" r="18" fill="#94a3b8"/>
                <rect x="20" y="56" width="40" height="28" rx="4" fill="#94a3b8"/>
            </svg>'
        );

        if (!$path) return $placeholder;

        $possiblePaths = [
            public_path('storage/student_avatars/' . $path),
            storage_path('app/public/student_avatars/' . $path),
            public_path('storage/' . $path),
            storage_path('app/public/' . $path),
            public_path($path),
        ];

        foreach ($possiblePaths as $fullPath) {
            if (file_exists($fullPath) && filesize($fullPath) > 100) {
                $mime = mime_content_type($fullPath) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }

        return $placeholder;
    }
}
