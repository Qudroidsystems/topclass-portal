<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CBTController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\MyClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobStatusController;
use App\Http\Controllers\MySubjectController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SchoolArmController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SchoolBillController;
use App\Http\Controllers\SchooltermController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolHouseController;
use App\Http\Controllers\ViewStudentController;
use App\Http\Controllers\ClassTeacherController;
use App\Http\Controllers\MyresultroomController;
use App\Http\Controllers\MyScoreSheetController;
use App\Http\Controllers\StudentHouseController;
use App\Http\Controllers\SubjectClassController;
use App\Http\Controllers\ClasscategoryController;
use App\Http\Controllers\SchoolPaymentController;
use App\Http\Controllers\SchoolsessionController;
use App\Http\Controllers\ClassOperationController;
use App\Http\Controllers\StudentResultsController;
use App\Http\Controllers\SubjectTeacherController;
use App\Http\Controllers\SubjectVettingController;
use App\Http\Controllers\ClassBroadsheetController;
use App\Http\Controllers\StaffImageUploadController;
use App\Http\Controllers\SubjectOperationController;
use App\Http\Controllers\MySubjectVettingsController;
use App\Http\Controllers\PrincipalsCommentController;
use App\Http\Controllers\ViewStudentReportController;
use App\Http\Controllers\SchoolInformationController;
use App\Http\Controllers\MockSubjectVettingController;
use App\Http\Controllers\StudentImageUploadController;
use App\Http\Controllers\MyPrincipalsCommentController;
use App\Http\Controllers\MyMockSubjectVettingsController;
use App\Http\Controllers\SchoolBillTermSessionController;
use App\Http\Controllers\ViewStudentMockReportController;
use App\Http\Controllers\CompulsorySubjectClassController;
use App\Http\Controllers\StudentpersonalityprofileController;

// Redirect root to the login page
Route::get('/', function () {
    return redirect('/login');
});

Auth::routes();
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('roles/bulk-remove-users', [RoleController::class, 'bulkRemoveUsers'])->name('roles.bulkremoveusers');
    // Get role users with pagination (for AJAX)
    Route::get('/roles/{role}/users', [RoleController::class, 'getRoleUsers'])->name('roles.users');
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/all', [UserController::class, 'allUsers'])->name('users.all');
    Route::get('/users/paginate', [UserController::class, 'paginate'])->name('users.paginate');
    Route::get('/user/overview/{id}', [UserController::class, 'show'])->name('users.overview');
    Route::get('/users/roles', [UserController::class, 'roles']);
    Route::resource('permissions', PermissionController::class);

    Route::get('users/add-student', [UserController::class, 'createFromStudentForm'])->name('users.add-student-form');
    Route::post('users/create-from-student', [UserController::class, 'createFromStudent'])->name('users.createFromStudent');
    Route::get('/get-students', [UserController::class, 'getStudents'])->name('get.students');

    // ===================================================================
    // PROFILE & BIODATA ROUTES - FULLY CORRECTED AND CLEANED
    // ===================================================================
    Route::prefix('profile')->name('profile.')->group(function () {
        // View profile settings
        Route::get('/settings/{id}', [BiodataController::class, 'show'])->name('settings');

        // Personal info update
        Route::post('/update-info', [BiodataController::class, 'updateProfile'])->name('update-info');

        // Avatar upload (AJAX - matches Blade JS)
        Route::post('/update-avatar', [BiodataController::class, 'updateAvatar'])->name('update-avatar');

        // Student updates
        Route::post('/update-student-info', [BiodataController::class, 'updateStudentInfo'])->name('update-student-info');
        Route::post('/update-parent-info', [BiodataController::class, 'updateParentInfo'])->name('update-parent-info');

        // Staff updates
        Route::post('/update-employment-info', [BiodataController::class, 'updateEmploymentInfo'])->name('update-employment-info');
        Route::post('/add-qualification', [BiodataController::class, 'storeQualification'])->name('add-qualification');
        Route::post('/update-qualification/{id}', [BiodataController::class, 'updateQualification'])->name('update-qualification');
        Route::delete('/delete-qualification/{id}', [BiodataController::class, 'deleteQualification'])->name('delete-qualification');

        // Security: Email & Password change (AJAX - matches Blade JS)
        Route::post('/update-email', [BiodataController::class, 'ajaxemailupdate'])->name('update-email');
        Route::post('/update-password', [BiodataController::class, 'ajaxpasswordupdate'])->name('update-password');
    });

    // Role management
    Route::get('/adduser/{id}', [RoleController::class, 'adduser'])->name('roles.adduser');
    Route::post('/updateuserrole', [RoleController::class, 'updateuserrole'])->name('roles.updateuserrole');
    Route::delete('roles/removeuserrole/{userid}/{roleid}', [RoleController::class, 'removeuserrole'])->name('roles.removeuserrole');

    // Subjects
    Route::resource('subject', SubjectController::class);
    Route::get('/subjectid/{subjectid}', [SubjectController::class, 'deletesubject'])->name('subject.deletesubject');
    Route::post('subjectid', [SubjectController::class, 'updatesubject'])->name('subject.updatesubject');

    Route::resource('subjectclass', SubjectClassController::class);
    Route::delete('subjectclass/deletesubjectclass/{subjectclassid}', [SubjectClassController::class, 'deletesubjectclass'])->name('subjectclass.deletesubjectclass');
    Route::get('/subjectclass/assignments/{subjectteacherid}', [SubjectClassController::class, 'assignments'])->name('subjectclass.assignments');
    Route::get('/subjectclass/assignments-by-teacher/{subjectTeacherId}', [SubjectClassController::class, 'assignmentsBySubjectTeacher'])->name('subjectclass.assignmentsByTeacher');

    Route::resource('staff', StaffController::class);

    Route::resource('subjectteacher', SubjectTeacherController::class)->except(['update']);
    Route::match(['put', 'post'], 'subjectteacher/{id}', [SubjectTeacherController::class, 'update'])->name('subjectteacher.update');
    Route::get('subjectteacher/{id}/subjects', [SubjectTeacherController::class, 'getSubjects'])->name('subjectteacher.subjects');
    Route::post('subjectteacher/delete', [SubjectTeacherController::class, 'deletesubjectteacher'])->name('subjectteacher.delete');

    Route::resource('classteacher', ClassTeacherController::class);
    Route::get('/classteacher/assignments/{staffId}/{termId}/{sessionId}', [ClassTeacherController::class, 'assignments'])->name('classteacher.assignments');
    Route::post('/classteacher/delete', [ClassTeacherController::class, 'deleteMultiple'])->name('classteacher.deleteMultiple');

    Route::resource('session', SchoolsessionController::class);
    Route::get('/sessionid/{sessionid}', [SchoolsessionController::class, 'deletesession'])->name('session.deletesession');
    Route::post('updatesessionid', [SchoolsessionController::class, 'updatesession'])->name('session.updatesession');

    Route::resource('schoolhouse', SchoolHouseController::class);
    Route::post('schoolhouse/deletehouse', [SchoolHouseController::class, 'deletehouse'])->name('schoolhouse.deletehouse');
    Route::post('schoolhouse/updatehouse', [SchoolHouseController::class, 'updatehouse'])->name('schoolhouse.updatehouse');



    Route::resource('term', SchooltermController::class);
    Route::patch('term/{term}/status', [SchooltermController::class, 'updateStatus'])->name('term.status.update');
    Route::post('term/deleteterm', [SchooltermController::class, 'deleteterm'])->name('term.deleteterm');
    Route::post('term/updateterm', [SchooltermController::class, 'updateterm'])->name('term.updateterm');

    Route::resource('schoolarm', SchoolArmController::class);
    Route::post('schoolarm/deletearm', [SchoolArmController::class, 'deletearm'])->name('schoolarm.deletearm');
    Route::post('schoolarm/updatearm', [SchoolArmController::class, 'updatearm'])->name('schoolarm.updatearm');

    Route::get('schoolclass', [SchoolClassController::class, 'index'])->name('schoolclass.index');
    Route::post('schoolclass', [SchoolClassController::class, 'store'])->name('schoolclass.store');
    Route::put('schoolclass/{schoolclass}', [SchoolClassController::class, 'update'])->name('schoolclass.update');
    Route::delete('schoolclass/{schoolclass}', [SchoolClassController::class, 'destroy'])->name('schoolclass.destroy');
    Route::post('schoolclass/deleteschoolclass', [SchoolClassController::class, 'deleteschoolclass'])->name('schoolclass.deleteschoolclass');
    Route::get('schoolclass/{schoolclass}/arms', [SchoolClassController::class, 'getArms'])->name('schoolclass.getarms');
    Route::put('/schoolclass/{id}', [SchoolClassController::class, 'update'])->name('schoolclass.update');

    // Route::resource('student', StudentController::class)->except(['destroy']);
    // Route::get('/students/data', [StudentController::class, 'data'])->name('student.data');
    // Route::delete('/student/{id}/destroy', [StudentController::class, 'destroy'])->name('student.destroy');
    // Route::get('/studentid/{studentid}', [StudentController::class, 'deletestudent'])->name('student.deletestudent');
    // Route::get('/studentoverview/{id}', [StudentController::class, 'overview'])->name('student.overview');
    // Route::get('/studentsettings/{id}', [StudentController::class, 'setting'])->name('student.settings');
    // Route::get('/studentbulkupload', [StudentController::class, 'bulkupload'])->name('student.bulkupload');
    // Route::post('/studentbulkuploadsave', [StudentController::class, 'bulkuploadsave'])->name('student.bulkuploadsave');
    // Route::get('/batchindex', [StudentController::class, 'batchindex'])->name('studentbatchindex');
    // Route::delete('/student/deletestudentbatch', [StudentController::class, 'deletestudentbatch'])->name('student.deletestudentbatch');
    // Route::post('/students/destroy-multiple', [StudentController::class, 'destroyMultiple'])->name('student.destroyMultiple');
    // Route::put('/student/updateclass', [StudentController::class, 'updateClass'])->name('student.updateclass');
    // Route::post('/generate-student-pdf', [StudentController::class, 'generateStudentPdf'])->name('student.pdf');
    // Route::get('/students/last-admission-number', [StudentController::class, 'getLastAdmissionNumber'])->name('student.getLastAdmissionNumber');
    // Route::get('/student/{id}/class-history', [StudentController::class, 'getStudentClassHistory'])->name('student.classHistory');
    // Route::get('/students/report', action: [StudentController::class, 'generateReport'])->name('students.report');



//     // ================================================
//     // STUDENT MANAGEMENT ROUTES
//     // ================================================
//     Route::resource('student', StudentController::class)->except(['destroy']);

//     // Additional student routes
//     Route::prefix('students')->group(function () {
//         Route::get('/data', [StudentController::class, 'data'])->name('student.data');
//         Route::get('/last-admission-number', [StudentController::class, 'getLastAdmissionNumber'])->name('student.getLastAdmissionNumber');
//         Route::get('/report', [StudentController::class, 'generateReport'])->name('students.report');
//         Route::post('/destroy-multiple', [StudentController::class, 'destroyMultiple'])->name('student.destroyMultiple');
//     });

//     // Individual student operations
//     Route::prefix('student')->group(function () {
//         Route::delete('/{id}/destroy', [StudentController::class, 'destroy'])->name('student.destroy');
//         Route::get('/studentid/{studentid}', [StudentController::class, 'deletestudent'])->name('student.deletestudent');
//         Route::get('/overview/{id}', [StudentController::class, 'overview'])->name('student.overview');
//         Route::get('/settings/{id}', [StudentController::class, 'setting'])->name('student.settings');
//         Route::put('/updateclass', [StudentController::class, 'updateClass'])->name('student.updateclass');
//         Route::post('/generate-student-pdf', [StudentController::class, 'generateStudentPdf'])->name('student.pdf');
//     });

//     // Bulk operations
//     Route::prefix('student')->group(function () {
//         Route::get('/bulkupload', [StudentController::class, 'bulkupload'])->name('student.bulkupload');
//         Route::post('/bulkuploadsave', [StudentController::class, 'bulkuploadsave'])->name('student.bulkuploadsave');
//         Route::get('/batchindex', [StudentController::class, 'batchindex'])->name('studentbatchindex');
//         Route::delete('/deletestudentbatch', [StudentController::class, 'deletestudentbatch'])->name('student.deletestudentbatch');
//     });

//     // ================================================
//     // SYSTEM INFO ROUTES
//     // ================================================
//    // System Info Routes
//     Route::get('/system/active-term-session', function() {
//         $activeTerm = \App\Models\Schoolterm::where('status', true)->first();
//         $activeSession = \App\Models\Schoolsession::where('status', 'Current')->first();

//         return response()->json([
//             'success' => true,
//             'term' => $activeTerm ? [
//                 'id' => $activeTerm->id,
//                 'term' => $activeTerm->term, // Field is 'term' not 'name'
//                 'status' => $activeTerm->status
//             ] : null,
//             'session' => $activeSession ? [
//                 'id' => $activeSession->id,
//                 'session' => $activeSession->session, // Field is 'session' not 'name'
//                 'status' => $activeSession->status
//             ] : null
//         ]);
//     })->name('system.active-term-session');

//     // ================================================
//     // STUDENT CURRENT TERM ROUTES
//     // ================================================
//     Route::prefix('student-current-term')->group(function () {
//         Route::get('/student/{studentId}', [StudentController::class, 'getCurrentTerm']); // For current marked term
//         Route::get('/student/{studentId}/active', [StudentController::class, 'getActiveTerm']); // For system active term
//         Route::put('/student/{studentId}', [StudentController::class, 'updateCurrentTerm']);
//         Route::post('/bulk-update', [StudentController::class, 'bulkUpdateCurrentTerm'])->name('student.current-term.bulk-update');
//         Route::get('/students', [StudentController::class, 'getStudentsByCurrentFilters']);
//     });

//     // ================================================
//     // STUDENT TERM HISTORY ROUTES
//     // ================================================
//     Route::prefix('student')->group(function () {
//         Route::get('/{id}/current-info', [StudentController::class, 'getCurrentInfo'])->name('student.current-info');
//         Route::get('/{id}/all-terms', [StudentController::class, 'getAllRegisteredTerms'])->name('student.all-terms');
//     });



//       // Route for checking report generation progress
//     Route::get('/reports/progress', [StudentResultsController::class, 'getReportProgress'])->name('reports.progress');

//     // Route for generating report
//     Route::post('/reports/generate', [StudentResultsController::class, 'generateReport'])->name('reports.generate');


//     Route::get('/students/data-paginated', [StudentController::class, 'dataPaginated'])->name('student.data-paginated');
//         // Add this with your other student routes
// Route::get('/students/optimized', [StudentController::class, 'getStudentsOptimized'])->name('students.optimized');




    // ================================================
    // STUDENT MANAGEMENT ROUTES
    // ================================================
    Route::resource('student', StudentController::class)->except(['destroy']);

    // Additional student routes
    Route::prefix('students')->group(function () {
        Route::get('/data', [StudentController::class, 'data'])->name('student.data');
        Route::get('/last-admission-number', [StudentController::class, 'getLastAdmissionNumber'])->name('student.getLastAdmissionNumber');
        Route::get('/report', [StudentController::class, 'generateReport'])->name('students.report');
        Route::post('/destroy-multiple', [StudentController::class, 'destroyMultiple'])->name('student.destroyMultiple');
        Route::get('/optimized', [StudentController::class, 'getStudentsOptimized'])->name('students.optimized'); // THIS IS THE KEY ROUTE

        // Add these missing routes
    Route::post('/bulk-update-status', [StudentController::class, 'bulkUpdateStatus'])->name('students.bulk-update-status');
    Route::get('/by-class-session', [StudentController::class, 'getStudentsByClassAndSession'])->name('students.by-class-session');
    });


    // Add this separate route (not inside students prefix)
Route::get('/students-in-term', [StudentController::class, 'getStudentsInTerm'])->name('students.in-term');
Route::post('/students/remove-from-term', [StudentController::class, 'removeFromTerm'])->name('students.remove-from-term');
Route::post('/students/bulk-remove-from-term', [StudentController::class, 'bulkRemoveFromTerm'])->name('students.bulk-remove-from-term');


    // Individual student operations
    Route::prefix('student')->group(function () {
        Route::delete('/{id}/destroy', [StudentController::class, 'destroy'])->name('student.destroy');
        Route::get('/studentid/{studentid}', [StudentController::class, 'deletestudent'])->name('student.deletestudent');
        Route::get('/overview/{id}', [StudentController::class, 'overview'])->name('student.overview');
        Route::get('/settings/{id}', [StudentController::class, 'setting'])->name('student.settings');
        Route::put('/updateclass', [StudentController::class, 'updateClass'])->name('student.updateclass');
        Route::post('/generate-student-pdf', [StudentController::class, 'generateStudentPdf'])->name('student.pdf');
    });

    // Bulk operations
    Route::prefix('student')->group(function () {
        Route::get('/bulkupload', [StudentController::class, 'bulkupload'])->name('student.bulkupload');
        Route::post('/bulkuploadsave', [StudentController::class, 'bulkuploadsave'])->name('student.bulkuploadsave');
        Route::get('/batchindex', [StudentController::class, 'batchindex'])->name('studentbatchindex');
        Route::delete('/deletestudentbatch', [StudentController::class, 'deletestudentbatch'])->name('student.deletestudentbatch');
    });

    // ================================================
    // SYSTEM INFO ROUTES
    // ================================================
    Route::get('/system/active-term-session', function() {
        $activeTerm = \App\Models\Schoolterm::where('status', true)->first();
        $activeSession = \App\Models\Schoolsession::where('status', 'Current')->first();

        return response()->json([
            'success' => true,
            'term' => $activeTerm ? [
                'id' => $activeTerm->id,
                'term' => $activeTerm->term,
                'status' => $activeTerm->status
            ] : null,
            'session' => $activeSession ? [
                'id' => $activeSession->id,
                'session' => $activeSession->session,
                'status' => $activeSession->status
            ] : null
        ]);
    })->name('system.active-term-session');

    // ================================================
    // STUDENT CURRENT TERM ROUTES
    // ================================================
    Route::prefix('student-current-term')->group(function () {
        Route::get('/student/{studentId}', [StudentController::class, 'getCurrentTerm']);
        Route::get('/student/{studentId}/active', [StudentController::class, 'getActiveTerm']);
        Route::put('/student/{studentId}', [StudentController::class, 'updateCurrentTerm']);
        Route::post('/bulk-update', [StudentController::class, 'bulkUpdateCurrentTerm'])->name('student.current-term.bulk-update');
        Route::get('/students', [StudentController::class, 'getStudentsByCurrentFilters']);
    });

    // ================================================
    // STUDENT TERM HISTORY ROUTES
    // ================================================
    Route::prefix('student')->group(function () {
        Route::get('/{id}/current-info', [StudentController::class, 'getCurrentInfo'])->name('student.current-info');
        Route::get('/{id}/all-terms', [StudentController::class, 'getAllRegisteredTerms'])->name('student.all-terms');
    });
// ================================================
// REPORT ROUTES
// ================================================
Route::get('/reports/progress', [StudentResultsController::class, 'getReportProgress'])->name('reports.progress');
Route::post('/reports/generate', [StudentResultsController::class, 'generateReport'])->name('reports.generate');


    Route::resource('classoperation', ClassOperationController::class);

    Route::resource('classcategories', ClasscategoryController::class);
    Route::get('/classcategoryid/{classcategoryid}', [ClasscategoryController::class, 'deleteclasscategory'])->name('classcategories.deleteclasscategory');
    Route::post('updateclasscategoryid', [ClasscategoryController::class, 'updateclasscategory'])->name('classcategories.updateclasscategory');

    Route::resource('parent', ParentController::class);
    Route::resource('studentImageUpload', StudentImageUploadController::class);
    Route::resource('myclass', MyClassController::class);
    Route::resource('mysubject', MySubjectController::class);

    Route::get('/myresultroom', [MyresultroomController::class, 'index'])->name('myresultroom.index');
    Route::post('/myresultroom', [MyresultroomController::class, 'index']);
    Route::post('/myresultroom/store', [MyresultroomController::class, 'store']);
    Route::delete('/subjects/registered-classes', [MyresultroomController::class, 'delete']);

    Route::resource('studentresults', StudentResultsController::class);

    Route::get('subjectscoresheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [MyScoreSheetController::class, 'subjectscoresheet'])->name('subjectscoresheet');
    Route::get('subjectscoresheet/edit/{id}', [MyScoreSheetController::class, 'edit'])->name('subjectscoresheet.edit');
    Route::put('subjectscoresheet/update/{id}', [MyScoreSheetController::class, 'update'])->name('subjectscoresheet.update');
    Route::delete('subjectscoresheet/delete/{id}', [MyScoreSheetController::class, 'destroy'])->name('subjectscoresheet.destroy');
    Route::get('subjectscoresheet/export', [MyScoreSheetController::class, 'export'])->name('subjectscoresheet.export');
    Route::post('subjectscoresheet/import', [MyScoreSheetController::class, 'import'])->name('subjectscoresheet.import');
    Route::get('/subjectscoresheet/results', [MyScoreSheetController::class, 'results'])->name('subjectscoresheet.results');
    Route::post('/subjectscoresheet/grade-preview', [MyScoreSheetController::class, 'calculateGradePreview'])->name('subjectscoresheet.grade-preview');
    Route::post('subjectscoresheet/bulk-update', [MyScoreSheetController::class, 'bulkUpdateScores'])->name('subjectscoresheet.bulk-update');
    Route::get('/subjectscoresheet/import-progress', [MyScoreSheetController::class, 'importProgress'])->name('subjectscoresheet.import_progress');

    Route::get('subjectscoresheet-mock', [MyScoreSheetController::class, 'mockIndex'])->name('subjectscoresheet-mock.index');
    Route::get('subjectscoresheet-mock/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [MyScoreSheetController::class, 'mockSubjectscoresheet'])->name('subjectscoresheet-mock.show');
    Route::get('subjectscoresheet-mock/export', [MyScoreSheetController::class, 'mockExport'])->name('subjectscoresheet-mock.export');
    Route::post('subjectscoresheet-mock/import', [MyScoreSheetController::class, 'mockImport'])->name('subjectscoresheet-mock.import');
    Route::get('subjectscoresheet-mock/{id}/edit', [MyScoreSheetController::class, 'mockEdit'])->name('subjectscoresheet-mock.edit');
    Route::put('subjectscoresheet-mock/{id}', [MyScoreSheetController::class, 'mockUpdate'])->name('subjectscoresheet-mock.update');
    Route::post('scoresheet-mock/destroy', [MyScoreSheetController::class, 'mockDestroy'])->name('scoresheet-mock.destroy');
    Route::post('scoresheet-mock/bulk-update', [MyScoreSheetController::class, 'mockBulkUpdateScores'])->name('scoresheet-mock.bulk-update');
    Route::get('subjectscoresheet-mock/results', [MyScoreSheetController::class, 'mockResults'])->name('subjectscoresheet-mock.results');
    Route::get('subjectscoresheet-mock/download-marksheet', [MyScoreSheetController::class, 'mockDownloadMarkSheet'])->name('subjectscoresheet-mock.download-marksheet');
    Route::post('subjectscoresheet-mock/calculate-grade', [MyScoreSheetController::class, 'calculateGradeForScore'])->name('subjectscoresheet-mock.calculate-grade');

    Route::get('/scoresheet/download-marks-sheet', [MyScoreSheetController::class, 'downloadMarkSheet'])->name('scoresheet.download-marks-sheet');
    Route::post('/subjectscoresheet/bulk-update', [MyScoreSheetController::class, 'bulkUpdateScores'])->name('subjectscoresheet.bulk-update');

  Route::prefix('school-info')->name('admin.school-info.')->group(function () {
    Route::get('/', [SchoolInformationController::class, 'index'])->name('index');
    Route::post('/', [SchoolInformationController::class, 'store'])->name('store');

    // Accept both POST and PUT methods
    Route::match(['POST', 'PUT'], '/{id}', [SchoolInformationController::class, 'update'])->name('update');

    Route::delete('/{id}', [SchoolInformationController::class, 'destroy'])->name('destroy');
    Route::get('/{id}', [SchoolInformationController::class, 'show'])->name('show');
    Route::get('/{id}/edit-json', [SchoolInformationController::class, 'editJson'])->name('edit-json');
});

    Route::resource('schoolbill', SchoolBillController::class);
    Route::get('/billid/{billid}', [SchoolBillController::class, 'deletebill'])->name('schoolbill.deletebill');
    Route::post('billid', [SchoolBillController::class, 'updatebill'])->name('schoolbill.updateschoolbill');

    Route::resource('schoolbilltermsession', SchoolBillTermSessionController::class);
    Route::get('/schoolbilltermsessionid/{schoolbilltermsessionid}', [SchoolBillTermSessionController::class, 'deleteschoolbilltermsession'])->name('schoolbilltermsession.deleteschoolbilltermsession');
    Route::post('schoolbilltermsessionbid', [SchoolBillTermSessionController::class, 'updateschoolbilltermsession'])->name('schoolbilltermsession.updateschoolbilltermsession');
    Route::get('/schoolbilltermsession/{id}/related', [SchoolBillTermSessionController::class, 'getRelated'])->name('schoolbilltermsession.related');

    Route::get('/schoolpayment', [SchoolPaymentController::class, 'index'])->name('schoolpayment.index');
    Route::get('/schoolpayment/term-session/{id}', [SchoolPaymentController::class, 'termSession'])->name('schoolpayment.termsession');
    Route::get('termsessionpayments', [SchoolPaymentController::class, 'termsessionpayments'])->name('schoolpayment.termsessionpayments');
    Route::get('/schoolpayment/term-session-payments', [SchoolPaymentController::class, 'termSessionPayments'])->name('schoolpayment.termsessionpayments');
    Route::post('/schoolpayment/store', [SchoolPaymentController::class, 'store'])->name('schoolpayment.store');
    Route::post('/schoolpayment/delete/{recordId}', [SchoolPaymentController::class, 'deletestudentpayment'])->name('schoolpayment.deletestudentpayment');
    Route::get('/schoolpayment/invoice/{studentId}/{schoolclassid}/{termid}/{sessionid}', [SchoolPaymentController::class, 'invoice'])->name('schoolpayment.invoice');
    Route::get('/schoolpayment/statement/{studentId}/{schoolclassid}/{termid}/{sessionid}', [SchoolPaymentController::class, 'statement'])->name('schoolpayment.statement');

    Route::resource('analysis', AnalysisController::class);
    Route::post('analysisClassTermSession', [AnalysisController::class, 'analysisClassTermSession'])->name('analysis.analysisClassTermSession');
    Route::get('analysis/export-pdf/{class_id}/{termid_id}/{session_id}', [AnalysisController::class, 'exportPDF'])->name('analysis.exportPDF');
    Route::get('/analysis/pdf/{class_id}/{termid_id}/{session_id}/{action?}', [AnalysisController::class, 'exportPDF'])->name('analysis.viewPDF')->where('action', 'view|download');

    Route::get('/school-wide-payment-analysis/{termid_id}/{session_id}/{action?}/{format?}', [AnalysisController::class, 'schoolWidePaymentAnalysis'])->name('school.wide.payment.analysis')->where(['action' => 'view|download','format' => 'pdf|word']);

    Route::get('/viewstudent/{schoolclassid}/{termid}/{sessionid}', [ViewStudentController::class, 'show'])->name('viewstudent');

    Route::get('/studentreports', [ViewStudentReportController::class, 'index'])->name('studentreports.index');
    Route::get('/studentresult/{id}/{schoolclassid}/{sessionid}/{termid}', [ViewStudentReportController::class, 'studentresult'])->name('studentresult');
    Route::get('/student-reports/registered-classes', [ViewStudentReportController::class, 'registeredClasses'])->name('studentreports.registeredClasses');
    Route::get('/class-broadsheet/{schoolclassid}/{sessionid}/{termid}', [ViewStudentReportController::class, 'classBroadsheet'])->name('classbroadsheet');
    Route::match(['get', 'post'], '/studentreports/export-class-results-pdf', [ViewStudentReportController::class, 'exportClassResultsPdf'])->name('studentreports.exportClassResultsPdf');

    Route::get('/studentmockreports', [ViewStudentMockReportController::class, 'index'])->name('studentmockreports.index');
    Route::get('/studentmockresult/{id}/{schoolclassid}/{sessionid}/{termid}', [ViewStudentMockReportController::class, 'studentmockresult'])->name('studentmockreports.studentmockresult');
    Route::get('/registered-classes', [ViewStudentMockReportController::class, 'registeredClasses'])->name('studentmockreports.registeredClasses');
    Route::get('/class-broadsheet/{schoolclassid}/{sessionid}/{termid}', [ViewStudentMockReportController::class, 'classBroadsheet'])->name('studentmockreports.classBroadsheet');
    Route::post('/export-class-results-pdf', [ViewStudentMockReportController::class, 'exportClassMockResultsPdf'])->name('studentmockreports.exportClassMockResultsPdf');

    // Route::resource('subjectoperation', SubjectOperationController::class);
    // Route::get('/subjects', [SubjectOperationController::class, 'index'])->name('subjects.index');
    // Route::post('/subjectregistration', [SubjectOperationController::class, 'store'])->name('subjects.store');
    // Route::get('/subjectoperation/subjectinfo/{id}/{schoolclassid}/{termid}/{sessionid}', [SubjectOperationController::class, 'subjectinfo'])->name('subjects.subjectinfo');
    // Route::delete('/subjects/registered-classes', [SubjectOperationController::class, 'destroy'])->name('subjects.destroy');
    // Route::get('/subjects/registered-classes', [SubjectOperationController::class, 'getRegisteredClasses'])->name('subjects.registered-classes');
    // Route::post('/subjectregistration/destroy', [SubjectOperationController::class, 'destroy'])->name('subjectregistration.destroy');
    // Route::post('/subjectregistration/batch', [SubjectOperationController::class, 'batchRegister'])->name('subjectregistration.batch');





        // ── Existing routes (already in your project) ────────────────────────────────
        Route::get('/subjects', [SubjectOperationController::class, 'index']) ->name('subjects.index');

        Route::post('/subjectregistration', [SubjectOperationController::class, 'store']) ->name('subjects.store');

        Route::get('/subjectoperation/subjectinfo/{id}/{schoolclassid}/{termid}/{sessionid}',
            [SubjectOperationController::class, 'subjectinfo'])->name('subjects.subjectinfo');
        Route::post('/subjectregistration/destroy', [SubjectOperationController::class, 'destroy'])->name('subjectregistration.destroy');
        // DELETE  /subjects/registered-classes  — used by the JS unregister flow
        Route::delete('/subjects/registered-classes', [SubjectOperationController::class, 'destroy'])->name('subjects.destroy');

        Route::get('/subjects/registered-classes', [SubjectOperationController::class, 'getRegisteredClasses'])->name('subjects.registered-classes');

        Route::post('/subjectregistration/batch', [SubjectOperationController::class, 'batchRegister'])->name('subjectregistration.batch');

        // ── NEW routes for snapshot / archive features ────────────────────────────────

        // GET  paginated snapshot-group list  (Unregistered History modal)
        Route::get('/subjectoperation/archived', [SubjectOperationController::class, 'getArchivedRegistrations'])->name('subjectoperation.archived');

        // GET  individual student rows + scores inside one snapshot
        Route::get('/subjectoperation/snapshot/detail', [SubjectOperationController::class, 'getSnapshotDetail'])->name('subjectoperation.snapshot.detail');

        // POST restore one or many archive records (re-registers + restores scores)
        Route::post('/subjectoperation/restore', [SubjectOperationController::class, 'restoreRegistration'])->name('subjectoperation.restore');

        // DELETE permanently remove a batch of archive records
        Route::delete('/subjectoperation/archive/batch-delete',
            [SubjectOperationController::class, 'permanentlyDeleteArchiveBatch'])->name('subjectoperation.archive.batch-delete');

        // DELETE permanently remove a single archive record
        Route::delete('/subjectoperation/archive/{archiveId}',[SubjectOperationController::class, 'permanentlyDeleteArchive']) ->name('subjectoperation.archive.delete');

        // Resource route (keep last so named routes above take priority)
        Route::resource('subjectoperation', SubjectOperationController::class);

    Route::get('/viewresults/{id}/{schoolclassid}/{sessid}/{termid}', [StudentResultsController::class, 'viewresults']);

    Route::get('/studentpersonalityprofile/{id}/{schoolclassid}/{sessid}/{termid}', [StudentpersonalityprofileController::class, 'studentpersonalityprofile'])->name('myclass.studentpersonalityprofile');
    Route::post('save', [StudentpersonalityprofileController::class, 'save'])->name('studentpersonalityprofile.save');

    Route::get('/classbroadsheet/{schoolclassid}/{sessionid}/{termid}', [ClassBroadsheetController::class, 'classBroadsheet'])->name('classbroadsheet.viewcomments');
    Route::patch('/classbroadsheet/{schoolclassid}/{sessionid}/{termid}/comments', [ClassBroadsheetController::class, 'updateComments'])->name('classbroadsheet.updateComments');

    Route::resource('compulsorysubjectclass', CompulsorySubjectClassController::class);

    Route::resource('principalscomment', PrincipalsCommentController::class);

    Route::prefix('myprincipalscomment')->name('myprincipalscomment.')->group(function () {
        Route::get('/', [MyPrincipalsCommentController::class, 'index'])->name('index');
        Route::get('/broadsheet/{schoolclassid}/{sessionid}/{termid}', [MyPrincipalsCommentController::class, 'classBroadsheet'])->name('classbroadsheet');
        Route::post('/broadsheet/{schoolclassid}/{sessionid}/{termid}', [MyPrincipalsCommentController::class, 'updateComments'])->name('updateComments');
    });
    Route::get('/my-principals-comment/broadsheet-pdf/{schoolclassid}/{sessionid}/{termid}', [MyPrincipalsCommentController::class, 'exportPdf'])->name('myprincipalscomment.exportPdf');


    // Add these routes to your routes/web.php file
    Route::get('/api/subject-classes/search', [SubjectVettingController::class, 'searchSubjectClasses'])->name('api.subject-classes.search');
    Route::post('/api/subject-classes/details', [SubjectVettingController::class, 'getSelectedSubjectClasses'])->name('api.subject-classes.details');

    // For Mock Subject Vetting (if not already added)
    Route::get('/api/mock-subject-classes/search', [MockSubjectVettingController::class, 'searchSubjectClasses'])->name('api.mock-subject-classes.search');
    Route::post('/api/mock-subject-classes/details', [MockSubjectVettingController::class, 'getSelectedSubjectClasses'])->name('api.mock-subject-classes.details');

    Route::resource('subjectvetting', SubjectVettingController::class);
    Route::resource('mocksubjectvetting', MockSubjectVettingController::class);

    // Bulk delete routes
    Route::post('/subjectvetting/bulk-delete', [SubjectVettingController::class, 'bulkDelete'])->name('subjectvetting.bulkDelete');
    Route::post('/mocksubjectvetting/bulk-delete', [MockSubjectVettingController::class, 'bulkDelete'])->name('mocksubjectvetting.bulkDelete');


    Route::get('/mysubjectvettings', [MySubjectVettingsController::class, 'index'])->name('mysubjectvettings.index');
    Route::get('/mysubjectvettings/classbroadsheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [MySubjectVettingsController::class, 'classBroadsheet'])->name('mysubjectvettings.classbroadsheet');
    Route::get('/mysubjectvettings/classbroadsheetmock/{schoolclassid}/{sessionid}/{termid}', [MySubjectVettingsController::class, 'classBroadsheetMock'])->name('mysubjectvettings.classbroadsheetmock');
    Route::put('/mysubjectvettings/{id}', [MySubjectVettingsController::class, 'update'])->name('mysubjectvettings.update');
    Route::put('/mysubjectvettings/{id}', [MySubjectVettingsController::class, 'updateMock'])->name('mysubjectvettings.updatemock');

    Route::get('/mymocksubjectvettings', [MyMockSubjectVettingsController::class, 'index'])->name('mymocksubjectvettings.index');
    Route::get('/mymocksubjectvettings/classbroadsheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [MyMockSubjectVettingsController::class, 'classBroadsheet'])->name('mymocksubjectvettings.classbroadsheet');
    Route::post('/mymocksubjectvettings/update-vetted-status', [MyMockSubjectVettingsController::class, 'updateVettedStatus'])->name('mymocksubjectvettings.update-vetted-status');
    Route::get('/mymocksubjectvettings/results', [MyMockSubjectVettingsController::class, 'results'])->name('mymocksubjectvettings.results');
    Route::put('/mymocksubjectvettings/{id}', [MyMockSubjectVettingsController::class, 'update'])->name('mymocksubjectvettings.update');

    Route::post('/broadsheets/update-vetted-status', [MySubjectVettingsController::class, 'updateVettedStatus'])->name('broadsheets.update-vetted-status');

    Route::resource('school-information', SchoolInformationController::class);

    Route::get('image-upload', [StaffImageUploadController::class, 'imageUpload'])->name('image.upload');
    Route::post('image-upload', [StaffImageUploadController::class, 'imageUploadPost'])->name('image.upload.post');

    Route::resource('exams', ExamController::class);

    Route::resource('questions', QuestionController::class);
    Route::get('/questions/{question}/details', [QuestionController::class, 'showDetails']);
    Route::get('/{question}/details', [QuestionController::class, 'details'])->name('questions.details');
    Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');

    Route::resource('cbt', CBTController::class);
    Route::get('/cbt/{examid}/takecbt', [CBTController::class, 'takeCBT'])->name('cbt.take');
    Route::post('/cbt/submit', [CBTController::class, 'submit'])->name('cbt.submit');

    Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
    Route::put('/promotions/{studentId}', [PromotionController::class, 'update'])->name('promotions.update');
    Route::delete('/promotions/{studentId}', [PromotionController::class, 'destroy'])->name('promotions.destroy');
});
