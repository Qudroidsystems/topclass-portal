<?php

namespace App\Http\Controllers;

use PDF;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Schoolterm;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\SchoolBillModel;
use App\Models\SchoolInformation;
use App\Models\StudentBillPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\SchoolBillTermSession;
use App\Models\StudentBillPaymentBook;
use App\Models\StudentBillPaymentRecord;

class SchoolPaymentController extends Controller
{
    /**
     * Display the list of students for payment selection.
     */
    public function index(Request $request)
    {
        $pagetitle = 'Student Payments';

        $student = Student::leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('schoolsession.status', 'Current')
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.gender as gender',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm',
                'schoolterm.term as term',
                'schoolsession.session as session',
            ])
            ->get();

        return view('schoolpayment.index', compact('pagetitle', 'student'));
    }

    /**
     * Display the term and session selection form for a student.
     */
    public function termSession(string $id)
    {
        $pagetitle = 'Student Payments';

        $schoolterms = Schoolterm::all();
        $schoolsessions = Schoolsession::all();

        return view('schoolpayment.termSession', compact('pagetitle', 'schoolterms', 'schoolsessions', 'id'));
    }

    /**
     * Display payment details for a student in a specific term and session.
     */
    public function termsessionpayments(Request $request)
    {
        $pagetitle = 'Student Payment Details';
        $studentId = $request->studentId;
        $termid = $request->termid;
        $sessionid = $request->sessionid;

        // Validate input
        if (!$studentId || !$termid || !$sessionid) {
            return redirect()->route('schoolpayment.index')->with('error', 'Invalid student, term, or session selected.');
        }

        // Fetch student data
        $studentdata = Student::where('studentRegistration.id', $studentId)
            ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('parentRegistration', 'parentRegistration.id', '=', 'studentRegistration.id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('schoolsession.status', 'Current')
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.home_address2 as homeadd',
                'parentRegistration.father_phone as phone',
                'studentRegistration.statusId as statusId',
                'studentRegistration.student_status as student_status',
                'studentpicture.picture as avatar',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm',
                'schoolterm.term as term',
                'schoolsession.session as session',
                'studentclass.schoolclassid as schoolclassId',
            ])
            ->first();

        if (!$studentdata) {
            return redirect()->route('schoolpayment.index')->with('error', 'Student not found or not enrolled in the current session.');
        }

        // Fetch new payment records (delete_status = '1') for CURRENT term and session only
        $studentpaymentbill = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.termid_id', $termid)
            ->where('student_bill_payment.session_id', $sessionid)
            ->where('student_bill_payment.delete_status', '1')
            ->leftJoin('student_bill_payment_record', function ($join) {
                $join->on('student_bill_payment_record.student_bill_payment_id', '=', 'student_bill_payment.id')
                    ->whereRaw('student_bill_payment_record.id = (
                        SELECT MAX(id)
                        FROM student_bill_payment_record spr
                        WHERE spr.student_bill_payment_id = student_bill_payment.id
                    )');
            })
            ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
            ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
            ->select([
                'student_bill_payment.id as paymentId',
                'student_bill_payment.school_bill_id as school_bill_id',
                'student_bill_payment_record.id as recordId',
                'student_bill_payment.created_at as receivedDate',
                'student_bill_payment.payment_method as paymentMethod',
                'student_bill_payment.status as paymentStatus',
                'student_bill_payment.delete_status',
                'school_bill.title as title',
                'school_bill.description as description',
                'school_bill.bill_amount as billAmount',
                'student_bill_payment_record.amount_paid as totalAmountPaid',
                'student_bill_payment_record.amount_owed as balance',
                DB::raw('COALESCE(users.name, "Unknown") as receivedBy'),
            ])
            ->get();

        // Fetch payment history (all records with delete_status = '0') for CURRENT term and session only
        $paymentHistory = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.termid_id', $termid)
            ->where('student_bill_payment.session_id', $sessionid)
            ->where('student_bill_payment.delete_status', '0')
            ->leftJoin('student_bill_payment_record', 'student_bill_payment_record.student_bill_payment_id', '=', 'student_bill_payment.id')
            ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
            ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
            ->select([
                'student_bill_payment.id as paymentId',
                'student_bill_payment.school_bill_id as school_bill_id',
                'student_bill_payment.class_id as classId',
                'student_bill_payment.termid_id as termId',
                'student_bill_payment.session_id as sessionId',
                'student_bill_payment_record.id as recordId',
                'student_bill_payment_record.created_at as receivedDate',
                'student_bill_payment.payment_method as paymentMethod',
                DB::raw('CASE WHEN student_bill_payment_record.complete_payment = 1 THEN "Completed" ELSE "Pending" END as paymentStatus'),
                'student_bill_payment.delete_status',
                'school_bill.title as title',
                'school_bill.description as description',
                'school_bill.bill_amount as billAmount',
                'student_bill_payment_record.amount_paid as totalAmountPaid',
                'student_bill_payment_record.amount_owed as balance',
                DB::raw('COALESCE(users.name, "Unknown") as receivedBy'),
                'student_bill_payment_record.complete_payment as completePayment',
            ])
            ->orderBy('student_bill_payment_record.created_at', 'desc')
            ->get();

        // Fetch school bills for CURRENT term and session only
        try {
            $student_bill_info = SchoolBillTermSession::where('school_bill_class_term_session.class_id', $studentdata->schoolclassId)
                ->where('school_bill_class_term_session.termid_id', $request->termid)
                ->where('school_bill_class_term_session.session_id', $request->sessionid)
                ->leftJoin('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
                ->leftJoin('student_status', 'student_status.id', '=', 'school_bill.statusId')
                ->where('student_status.id', $studentdata->statusId)
                ->select([
                    'school_bill_class_term_session.id as id',
                    'school_bill.id as schoolbillid',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'student_status.id as statusId',
                    'school_bill.bill_amount as amount'
                ])
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), 'school_bill_class_term_session') !== false) {
                // Fallback query: Fetch all school bills if school_bill_term_session table doesn't exist
                Log::warning('Table school_bill_class_term_session not found, falling back to all school bills.');
                $student_bill_info = SchoolBillModel::select([
                    'school_bill.id as schoolbillid',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as amount',
                ])->get();
            } else {
                throw $e; // Rethrow other database errors
            }
        }

        // Fetch payment book for CURRENT term and session only
        $studentpaymentbillbook = StudentBillPaymentBook::where('student_id', $studentId)
            ->where('term_id', $termid)
            ->where('session_id', $sessionid)
            ->get();

        $paymentRecordsCount = $studentpaymentbill->count();
        $schoolterm = Schoolterm::find($termid)->term ?? 'N/A';
        $schoolsession = Schoolsession::find($sessionid)->session ?? 'N/A';
        $schoolclassId = $studentdata->schoolclassId ?? null;

    return view('schoolpayment.studentpayment', compact(
            'pagetitle',
            'studentdata',
            'studentpaymentbill',
            'student_bill_info',
            'studentpaymentbillbook',
            'paymentRecordsCount',
            'schoolterm',
            'schoolsession',
            'studentId',
            'schoolclassId',
            'termid',
            'sessionid',
            'paymentHistory'
        ));
    }

    /**
     * Store a new payment amount.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:studentRegistration,id',
            'class_id' => 'required|integer|exists:schoolclass,id',
            'term_id' => 'required|integer|exists:schoolterm,id',
            'session_id' => 'required|integer|exists:schoolsession,id',
            'school_bill_id' => 'required|integer|exists:school_bill,id',
            'actual_amount' => 'required|numeric|min:0.01',
            'balance2' => 'required|numeric|min:0',
            'last_amount_paid' => 'required|numeric|min:0',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_amount2' => 'nullable|numeric|min:0.01',
            'payment_method2' => 'required|string|in:Bank Deposit,School POS,Bank Transfer,Cheque',
        ]);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'No authenticated user found. Please log in.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $studentPayment = StudentBillPayment::where([
                'student_id' => $request->student_id,
                'school_bill_id' => $request->school_bill_id,
                'class_id' => $request->class_id,
                'termid_id' => $request->term_id,
                'session_id' => $request->session_id,
            ])->first();

            // Check if payment exists and invoice is not generated (delete_status = '1')
            if ($studentPayment && $studentPayment->delete_status == '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot make additional payments until the pending invoice is generated for this bill.'
                ], 403);
            }

            // Check if bill is fully paid
            $isFullyPaid = $studentPayment && StudentBillPaymentRecord::where('student_bill_payment_id', $studentPayment->id)
                ->where('amount_owed', 0)
                ->exists();

            if ($isFullyPaid) {
                return response()->json([
                    'success' => false,
                    'message' => 'This bill is already fully paid.'
                ], 403);
            }

            $paymentAmount = $request->payment_amount;
            $totalBill = $request->actual_amount;
            $balance = $request->balance2 - $paymentAmount;

            if ($balance < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount exceeds the outstanding balance.'
                ], 422);
            }

            $completePayment = $balance <= 0 ? 1 : 0;
            $status = $completePayment ? 'Completed' : 'Pending';
            $generatedBy = Auth::id();

            if ($studentPayment) {
                // Update existing payment
                $studentPayment->update([
                    'payment_method' => $request->payment_method2,
                    'status' => $status,
                    'generated_by' => $generatedBy,
                    'delete_status' => '1',
                ]);

                // Create new payment record
                StudentBillPaymentRecord::create([
                    'student_bill_payment_id' => $studentPayment->id,
                    'class_id' => $request->class_id,
                    'termid_id' => $request->term_id,
                    'session_id' => $request->session_id,
                    'amount_paid' => $paymentAmount,
                    'last_payment' => $paymentAmount,
                    'amount_owed' => $balance,
                    'total_bill' => $totalBill,
                    'complete_payment' => $completePayment,
                    'generated_by' => $generatedBy,
                ]);

                // Update payment book
                StudentBillPaymentBook::where([
                    'student_id' => $request->student_id,
                    'school_bill_id' => $request->school_bill_id,
                    'class_id' => $request->class_id,
                    'term_id' => $request->term_id,
                    'session_id' => $request->session_id,
                ])->update([
                    'amount_paid' => DB::raw('amount_paid + ' . $paymentAmount),
                    'amount_owed' => $balance,
                    'payment_status' => $status,
                    'generated_by' => $generatedBy,
                ]);
            } else {
                // Create new payment
                $studentPayment = StudentBillPayment::create([
                    'student_id' => $request->student_id,
                    'school_bill_id' => $request->school_bill_id,
                    'class_id' => $request->class_id,
                    'termid_id' => $request->term_id,
                    'session_id' => $request->session_id,
                    'payment_method' => $request->payment_method2,
                    'status' => $status,
                    'generated_by' => $generatedBy,
                    'delete_status' => '1',
                ]);

                StudentBillPaymentRecord::create([
                    'student_bill_payment_id' => $studentPayment->id,
                    'class_id' => $request->class_id,
                    'termid_id' => $request->term_id,
                    'session_id' => $request->session_id,
                    'amount_paid' => $paymentAmount,
                    'last_payment' => $paymentAmount,
                    'amount_owed' => $balance,
                    'total_bill' => $totalBill,
                    'complete_payment' => $completePayment,
                    'generated_by' => $generatedBy,
                ]);

                StudentBillPaymentBook::create([
                    'student_id' => $request->student_id,
                    'school_bill_id' => $request->school_bill_id,
                    'class_id' => $request->class_id,
                    'term_id' => $request->term_id,
                    'session_id' => $request->session_id,
                    'amount_paid' => $paymentAmount,
                    'amount_owed' => $balance,
                    'payment_status' => $status,
                    'generated_by' => $generatedBy,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'redirect_url' => route('schoolpayment.termsessionpayments', [
                    'studentId' => $request->student_id,
                    'termid' => $request->term_id,
                    'sessionid' => $request->session_id
                ])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment store error: ', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a payment record.
     */
    public function deletestudentpayment($recordId)
    {
        DB::beginTransaction();
        try {
            $paymentRecord = StudentBillPaymentRecord::findOrFail($recordId);
            $studentPayment = StudentBillPayment::findOrFail($paymentRecord->student_bill_payment_id);

            // Prevent deletion if invoice is already generated (delete_status = '0')
            if ($studentPayment->delete_status == '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete payment record after invoice is generated.'
                ], 403);
            }

            // Update payment book
            $paymentBook = StudentBillPaymentBook::where([
                'student_id' => $studentPayment->student_id,
                'school_bill_id' => $studentPayment->school_bill_id,
                'class_id' => $studentPayment->class_id,
                'term_id' => $studentPayment->termid_id,
                'session_id' => $studentPayment->session_id,
            ])->first();

            if ($paymentBook) {
                $newAmountPaid = $paymentBook->amount_paid - $paymentRecord->amount_paid;
                $newAmountOwed = $paymentBook->amount_owed + $paymentRecord->amount_paid;
                $newStatus = $newAmountOwed <= 0 ? 'Completed' : 'Pending';

                $paymentBook->update([
                    'amount_paid' => $newAmountPaid,
                    'amount_owed' => $newAmountOwed,
                    'payment_status' => $newStatus,
                ]);
            }

            // Delete payment record
            $paymentRecord->delete();

            // Check if there are no more payment records for this bill
            $remainingRecords = StudentBillPaymentRecord::where('student_bill_payment_id', $studentPayment->id)->count();
            if ($remainingRecords == 0) {
                $studentPayment->delete();
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete payment error: ', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage()
            ], 500);
        }
    }

    
    /**
     * Generate and display/download an invoice.
     */
    public function invoice(Request $request, $studentId, $schoolclassid, $termid, $sessionid)
    {
        $pagetitle = 'Student Payment Invoice';

        // Validate input
        if (!$studentId || !$schoolclassid || !$termid || !$sessionid) {
            return redirect()->route('schoolpayment.index')->with('error', 'Invalid parameters provided.');
        }

        // Fetch student data
        $student = Student::where('studentRegistration.id', $studentId)
            ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('parentRegistration', 'parentRegistration.id', '=', 'studentRegistration.id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('schoolsession.status', 'Current')
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.home_address2 as homeadd',
                'parentRegistration.father_phone as phone',
                'studentpicture.picture as avatar',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm',
                'schoolterm.term as term',
                'schoolsession.session as session',
            ])
            ->first();

        if (!$student) {
            return redirect()->route('schoolpayment.index')->with('error', 'Student not found or not enrolled.');
        }

        // Generate invoice number
        $invoiceNumber = 'INV-' . str_pad($studentId, 4, '0', STR_PAD_LEFT) . '-' . date('Ymd');

        // Get all school bills assigned to this class, term, and session using the proper junction table
        try {
            $allClassBills = SchoolBillTermSession::where('school_bill_class_term_session.class_id', $schoolclassid)
                ->where('school_bill_class_term_session.termid_id', $termid)
                ->where('school_bill_class_term_session.session_id', $sessionid)
                ->leftJoin('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
                ->leftJoin('student_status', 'student_status.id', '=', 'school_bill.statusId')
                ->where('student_status.id', 1) // Only active bills
                ->select([
                    'school_bill.id as school_bill_id',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as amount'
                ])
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), 'school_bill_class_term_session') !== false) {
                // Fallback query: Fetch all school bills if school_bill_class_term_session table doesn't exist
                Log::warning('Table school_bill_class_term_session not found, falling back to all school bills.');
                $allClassBills = SchoolBillModel::select([
                    'school_bill.id as school_bill_id',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as amount',
                ])->get();
            } else {
                throw $e; // Rethrow other database errors
            }
        }

        // Get all payment records for this student, term, and session
        $paidBills = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.class_id', $schoolclassid)
            ->where('student_bill_payment.termid_id', $termid)
            ->where('student_bill_payment.session_id', $sessionid)
            ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
            ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
            ->select([
                'student_bill_payment.id as paymentid',
                'student_bill_payment.school_bill_id',
                'student_bill_payment.created_at as payment_date',
                'student_bill_payment.payment_method as payment_method',
                'school_bill.title as title',
                'school_bill.description as description',
                'school_bill.bill_amount as amount',
                DB::raw('COALESCE(users.name, "Unknown") as receivedBy'),
            ])
            ->groupBy([
                'student_bill_payment.school_bill_id',
                'student_bill_payment.id',
                'student_bill_payment.created_at',
                'student_bill_payment.payment_method',
                'school_bill.title',
                'school_bill.description',
                'school_bill.bill_amount',
                'users.name'
            ])
            ->get()
            ->keyBy('school_bill_id');

        // Process all bills (both paid and unpaid)
        $payments = $allClassBills->map(function ($bill) use ($paidBills, $studentId, $schoolclassid, $termid, $sessionid, $invoiceNumber) {
            $paidBill = $paidBills->get($bill->school_bill_id);
            
            if ($paidBill) {
                // This bill has payment records
                $paymentRecords = StudentBillPaymentRecord::where('student_bill_payment_id', $paidBill->paymentid)
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Calculate totals for this bill
                $totalPaidForThisBill = $paymentRecords->sum('amount_paid');
                
                // Get the most recent payment (last payment made)
                $lastPaymentRecord = $paymentRecords->sortByDesc('created_at')->first();
                $lastPaymentAmount = $lastPaymentRecord ? $lastPaymentRecord->amount_paid : 0;
                
                // Previous paid = Total paid - Last payment amount
                $previousPaid = $totalPaidForThisBill - $lastPaymentAmount;
                
                // Calculate current balance (outstanding amount)
                $currentBalance = max(0, $bill->amount - $totalPaidForThisBill);

                // Update or set invoice number for the last payment if it doesn't have one
                if ($lastPaymentRecord && $lastPaymentAmount > 0 && !$lastPaymentRecord->invoiceNo) {
                    StudentBillPaymentRecord::where('id', $lastPaymentRecord->id)
                        ->update(['invoiceNo' => $invoiceNumber]);
                }

                // Determine payment completion status
                $isCompletePayment = $currentBalance == 0 ? 1 : 0;

                return (object) [
                    'school_bill_id' => $bill->school_bill_id,
                    'title' => $bill->title,
                    'description' => $bill->description,
                    'amount' => $bill->amount,
                    'previousPaid' => $previousPaid,
                    'todayPaid' => $lastPaymentAmount,
                    'amountPaid' => $totalPaidForThisBill,
                    'balance' => $currentBalance,
                    'paymentMethod' => $paidBill->payment_method ?? 'N/A',
                    'receivedBy' => $paidBill->receivedBy ?? 'Unknown',
                    'paymentDate' => $lastPaymentRecord ? $lastPaymentRecord->created_at : $paidBill->payment_date,
                    'complete_payment' => $isCompletePayment,
                    'invoiceNo' => $lastPaymentRecord->invoiceNo ?? $invoiceNumber,
                ];
            } else {
                // This bill has no payment records - it's completely outstanding
                return (object) [
                    'school_bill_id' => $bill->school_bill_id,
                    'title' => $bill->title,
                    'description' => $bill->description,
                    'amount' => $bill->amount,
                    'previousPaid' => 0,
                    'todayPaid' => 0,
                    'amountPaid' => 0,
                    'balance' => $bill->amount, // Full amount is outstanding
                    'paymentMethod' => 'N/A',
                    'receivedBy' => 'N/A',
                    'paymentDate' => null,
                    'complete_payment' => 0,
                    'invoiceNo' => null,
                ];
            }
        });

        // Remove duplicate bills and sort by payment date (unpaid bills will be at the end)
        $payments = $payments->groupBy('school_bill_id')->map(function ($billGroup) {
            return $billGroup->sortByDesc('paymentDate')->first();
        })->values()->sortByDesc('paymentDate');

        // Calculate totals
        $totalBillAmount = $payments->sum('amount');
        $totalPreviousPaid = $payments->sum('previousPaid');
        $totalLastPayments = $payments->sum('todayPaid');
        $totalPaid = $payments->sum('amountPaid');
        $totalOutstanding = $payments->sum('balance');

        // Fetch school information
        $schoolInfo = SchoolInformation::first() ?? (object) [
            'school_name' => 'TOPCLASS COLLEGE',
            'logo_url' => asset('assets/images/logo.png'),
            'school_email' => 'info@topclasscollege.edu',
            'school_address' => 'Your School Address Here',
            'school_phone' => 'Your Phone Number',
        ];

        // Fetch term and session
        $schoolterm = Schoolterm::find($termid)->term ?? 'N/A';
        $schoolsession = Schoolsession::find($sessionid)->session ?? 'N/A';

        // Update delete_status to '0' only for new payments (if needed)
        $deleteStatus = $request->input('historical', false) ? '0' : '1';
        if ($deleteStatus === '1') {
            StudentBillPayment::where('student_id', $studentId)
                ->where('class_id', $schoolclassid)
                ->where('termid_id', $termid)
                ->where('session_id', $sessionid)
                ->where('delete_status', '1')
                ->update(['delete_status' => '0']);
        }

        // Prepare data for view/PDF
        $data = [
            'pagetitle' => $pagetitle,
            'studentdata' => $student ? collect([$student]) : collect([]),
            'studentpaymentbill' => $payments,
            'totalBillAmount' => $totalBillAmount,
            'totalPreviousPaid' => $totalPreviousPaid,
            'totalTodayPaid' => $totalLastPayments,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'schoolInfo' => $schoolInfo,
            'invoiceNumber' => $invoiceNumber,
            'schoolterm' => $schoolterm,
            'schoolsession' => $schoolsession,
            'studentId' => $studentId,
            'termId' => $termid,
            'sessionId' => $sessionid,
            'schoolclassId' => $schoolclassid,
        ];

        // Log data for debugging
        Log::info('Invoice Data:', $data);

        // Handle PDF download or view rendering
        if ($request->has('download_pdf')) {
            $pdf = PDF::loadView('schoolpayment.studentinvoicepdf', $data);
            return $pdf->download('invoice_' . ($student->admissionNo ?? 'student') . '_' . $termid . '_' . $sessionid . '.pdf');
        }

        return view('schoolpayment.studentinvoice', $data);
    }

    /**
     * Generate and download a payment statement for all student payments.
     */
    public function statement(Request $request, $studentId, $schoolclassid, $termid, $sessionid)
    {
        $pagetitle = 'Student Payment Statement';

        // Validate input
        if (!$studentId || !$schoolclassid || !$termid || !$sessionid) {
            return redirect()->route('schoolpayment.index')->with('error', 'Invalid parameters provided.');
        }

        // Fetch student data
        $student = Student::where('studentRegistration.id', $studentId)
            ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('parentRegistration', 'parentRegistration.id', '=', 'studentRegistration.id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('schoolsession.status', 'Current')
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.home_address2 as homeadd',
                'parentRegistration.father_phone as phone',
                'studentpicture.picture as avatar',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm',
                'schoolterm.term as term',
                'schoolsession.session as session',
            ])
            ->first();

        if (!$student) {
            return redirect()->route('schoolpayment.index')->with('error', 'Student not found or not enrolled.');
        }

        // Fetch school bills for CURRENT term and session only
        try {
            $student_bill_info = SchoolBillModel::whereExists(function ($query) use ($termid, $sessionid, $schoolclassid) {
                    $query->select(DB::raw(1))
                        ->from('school_bill_term_session')
                        ->whereColumn('school_bill_term_session.school_bill_id', 'school_bill.id')
                        ->where('school_bill_term_session.term_id', $termid)
                        ->where('school_bill_term_session.session_id', $sessionid)
                        ->where('school_bill_term_session.class_id', $schoolclassid);
                })
                ->select([
                    'school_bill.id as schoolbillid',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as amount',
                ])
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), 'school_bill_term_session') !== false) {
                // Fallback query: Fetch all school bills
                Log::warning('Table school_bill_term_session not found, falling back to all school bills.');
                $student_bill_info = SchoolBillModel::select([
                    'school_bill.id as schoolbillid',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as amount',
                ])->get();
            } else {
                throw $e; // Rethrow other database errors
            }
        }

        // Fetch payment book for CURRENT term and session only
        $studentpaymentbillbook = StudentBillPaymentBook::where('student_id', $studentId)
            ->where('term_id', $termid)
            ->where('session_id', $sessionid)
            ->get();

        // Fetch payment records for the table (all records, both delete_status = '0' and '1') for CURRENT term and session only
        $studentpaymentbill = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.class_id', $schoolclassid)
            ->where('student_bill_payment.termid_id', $termid)
            ->where('student_bill_payment.session_id', $sessionid)
            ->leftJoin('student_bill_payment_record', 'student_bill_payment_record.student_bill_payment_id', '=', 'student_bill_payment.id')
            ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
            ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
            ->select([
                'student_bill_payment.id as paymentid',
                'student_bill_payment_record.created_at as payment_date',
                'student_bill_payment.payment_method as payment_method',
                'school_bill.title as title',
                'school_bill.description as description',
                'school_bill.bill_amount as amount',
                'student_bill_payment_record.amount_paid as amount_paid',
                'student_bill_payment_record.amount_owed as balance',
                DB::raw('CASE WHEN student_bill_payment_record.complete_payment = 1 THEN "Completed" ELSE "Pending" END as payment_status'),
                'student_bill_payment.delete_status as delete_status',
                DB::raw('COALESCE(users.name, "Unknown") as received_by'),
            ])
            ->orderBy('student_bill_payment_record.created_at', 'desc')
            ->get();

        // Calculate totals
        $totalSchoolBill = $student_bill_info->sum('amount');
        $totalPaid = $studentpaymentbillbook->sum('amount_paid');
        $totalOutstanding = max(0, $totalSchoolBill - $totalPaid);

        // Fetch school information
        $schoolInfo = SchoolInformation::first() ?? (object) [
            'school_name' => 'TOPCLASS COLLEGE',
            'logo_url' => asset('assets/images/logo.png'),
            'school_email' => 'info@topclasscollege.edu',
            'school_address' => 'Your School Address Here',
            'school_phone' => 'Your Phone Number',
        ];

        // Generate statement number
        $statementNumber = 'STMT-' . str_pad($studentId, 4, '0', STR_PAD_LEFT) . '-' . date('Ymd');

        // Fetch term and session
        $schoolterm = Schoolterm::find($termid)->term ?? 'N/A';
        $schoolsession = Schoolsession::find($sessionid)->session ?? 'N/A';

        // Prepare data for PDF
        $data = [
            'pagetitle' => $pagetitle,
            'studentdata' => $student ? collect([$student]) : collect([]),
            'studentpaymentbill' => $studentpaymentbill,
            'totalSchoolBill' => $totalSchoolBill,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'schoolInfo' => $schoolInfo,
            'statementNumber' => $statementNumber,
            'schoolterm' => $schoolterm,
            'schoolsession' => $schoolsession,
            'studentId' => $studentId,
            'termId' => $termid,
            'sessionId' => $sessionid,
            'schoolclassId' => $schoolclassid,
        ];

        // Log data for debugging
        Log::info('Statement Data:', $data);

        // Generate and download PDF
        $pdf = PDF::loadView('schoolpayment.studentstatement', $data);
        return $pdf->download('statement_' . ($student->admissionNo ?? 'student') . '_' . $termid . '_' . $sessionid . '.pdf');
    }
}