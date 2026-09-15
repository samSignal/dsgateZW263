<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\PaymentVerificationController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\BursarApiController;
use App\Http\Controllers\Api\HeadmasterApiController;
use App\Http\Controllers\Api\TeacherApiController;
use App\Http\Controllers\Api\ParentApiController;
use App\Http\Controllers\Api\StudentPortalApiController;
use App\Http\Controllers\Api\ApplicationApiController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\TermController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\StreamController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SubjectGroupController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherAllocationController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\StaffMemberController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\Finance\FeeCategoryController;
use App\Http\Controllers\Api\Finance\FeeStructureController;
use App\Http\Controllers\Api\Finance\StudentBillController;
use App\Http\Controllers\Api\Finance\PaymentController;
use App\Http\Controllers\Api\Finance\FinanceReportController;
use App\Http\Controllers\Api\Finance\ParentFinanceController;
use App\Http\Controllers\Api\Assessment\AssessmentTypeController;
use App\Http\Controllers\Api\Assessment\AssessmentController;
use App\Http\Controllers\Api\Assessment\AssessmentMarkController;
use App\Http\Controllers\Api\Assessment\AssessmentReportController;
use App\Http\Controllers\Api\Assessment\ParentAssessmentController;
use App\Http\Controllers\Api\Assessment\StudentAssessmentController;
use App\Http\Controllers\Api\Reports\ReportCardController;
use App\Http\Controllers\Api\Reports\ReportRankingController;
use App\Http\Controllers\Api\Reports\ReportCommentController;
use App\Http\Controllers\Api\Reports\ParentReportController;
use App\Http\Controllers\Api\Reports\StudentReportController;
use App\Http\Controllers\Api\Timetable\TimetablePeriodController;
use App\Http\Controllers\Api\Timetable\TimetableRoomController;
use App\Http\Controllers\Api\Timetable\TimetableController;
use App\Http\Controllers\Api\Timetable\TimetableValidationController;
use App\Http\Controllers\Api\Timetable\ParentTimetableController;
use App\Http\Controllers\Api\Timetable\StudentTimetableController;
use App\Http\Controllers\Api\Shop\ShopCategoryController;
use App\Http\Controllers\Api\Shop\ShopItemController;
use App\Http\Controllers\Api\Shop\StudentPurchaseController;
use App\Http\Controllers\Api\Shop\StudentPurchasePaymentController;
use App\Http\Controllers\Api\Shop\ShopReportController;
use App\Http\Controllers\Api\Shop\ParentPurchaseController;
use App\Http\Controllers\Api\Discipline\AttendanceSessionController;
use App\Http\Controllers\Api\Discipline\AttendanceRecordController;
use App\Http\Controllers\Api\Discipline\AttendanceReportController;
use App\Http\Controllers\Api\Discipline\BehaviourCategoryController;
use App\Http\Controllers\Api\Discipline\BehaviourIncidentController;
use App\Http\Controllers\Api\Discipline\DisciplineActionController;
use App\Http\Controllers\Api\Discipline\ParentBehaviourAttendanceController;
use App\Http\Controllers\Api\Discipline\StudentBehaviourAttendanceController;
use App\Http\Controllers\Api\Discipline\HeadmasterDisciplineDashboardController;
use App\Http\Controllers\Api\Academics\StreamSubjectController;
use App\Http\Controllers\Api\Academics\TeacherSubjectAllocationController as AcademicTeacherSubjectAllocationController;
use App\Http\Controllers\Api\Academics\StudentSubjectController;
use App\Http\Controllers\Api\Academics\AcademicSubjectReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StreamNative\ResultsController as StreamNativeResultsController;
use App\Http\Controllers\Api\StreamNative\RankingsController as StreamNativeRankingsController;
use App\Http\Controllers\Api\StreamNative\TranscriptController as StreamNativeTranscriptController;
use App\Http\Controllers\Api\StreamNative\ProgressionController as StreamNativeProgressionController;
use App\Http\Controllers\Api\StreamNative\GraduationController as StreamNativeGraduationController;
use App\Http\Controllers\Api\StreamNative\ReportCardPreviewController as StreamNativeReportCardPreviewController;
use App\Http\Controllers\Api\StreamNative\ParentResultsController as StreamNativeParentResultsController;

// ── Public ──────────────────────────────────────────────
// Throttled per IP+login to slow down credential stuffing / brute force without
// locking out a whole office network sharing one IP.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login',                    [AuthApiController::class, 'login']);
    Route::post('/forgot-password',          [AuthApiController::class, 'forgotPassword']);
    Route::post('/guardian-forgot-password', [AuthApiController::class, 'guardianForgotPassword']);
});
Route::middleware('throttle:20,1')->post('/apply', [ApplicationApiController::class, 'store']);

// Public payment verification (QR code target) — no auth, so a parent can scan a receipt
// without logging in. Throttled since the reference includes a guessable-in-theory random
// suffix; this just makes brute-forcing it impractically slow, on top of already being ~1e9 combos.
Route::middleware('throttle:20,1')->get('/public/verify-payment/{reference}', [PaymentVerificationController::class, 'verify']);

// ── Authenticated ────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me',          [AuthApiController::class, 'me']);
    Route::post('/logout',     [AuthApiController::class, 'logout']);
    Route::post('/change-password', [AuthApiController::class, 'changePassword']);
    Route::get('/my-permissions',   [RolePermissionController::class, 'myPermissions']);

    Route::middleware('role:admin,headmaster,teacher,bursar')
        ->get('/documents/{path}', [ApplicationApiController::class, 'document'])
        ->where('path', '.*');

    // ── Admin ──────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard',                    [AdminApiController::class, 'dashboard']);
        Route::get('/users',                        [AdminApiController::class, 'users']);
        Route::patch('/users/{user}/role',          [AdminApiController::class, 'updateUserRole']);
        Route::get('/staff',                        [AdminApiController::class, 'staff']);
        Route::post('/staff',                       [AdminApiController::class, 'storeStaff']);
        Route::get('/classes',                      [AdminApiController::class, 'classes']);
        Route::post('/classes',                     [AdminApiController::class, 'storeClass']);
        Route::get('/applications',                 [AdminApiController::class, 'applications']);
        Route::get('/applications/{application}/document-requests', [AdminApiController::class, 'documentRequests']);
        Route::post('/applications/{application}/document-requests', [AdminApiController::class, 'requestDocumentResubmission']);
        Route::post('/applications/{application}/offer', [AdminApiController::class, 'offerApplication']);
        Route::post('/applications/{application}/waitlist', [AdminApiController::class, 'waitlistApplication']);
        Route::post('/applications/{application}/approve', [AdminApiController::class, 'approveApplication']);
        Route::post('/applications/{application}/reject',  [AdminApiController::class, 'rejectApplication']);
        Route::put('/applications/{application}',           [AdminApiController::class, 'updateApplicationIntake']);
    });

    // ── Admissions enrollment (deposit + verification — needs bursar/headmaster too, not admin-only) ──
    Route::middleware('role:admin,bursar')->post('/admin/applications/{application}/enroll', [AdminApiController::class, 'recordDepositAndEnroll']);
    Route::middleware('role:admin,headmaster')->group(function () {
        Route::get('/admin/students/{student}/document-checklist', [AdminApiController::class, 'studentDocumentChecklist']);
        Route::post('/admin/students/{student}/verify-documents',  [AdminApiController::class, 'verifyStudentDocuments']);
    });

    // ── Headmaster ─────────────────────────────────────
    Route::middleware('role:admin,headmaster')->prefix('headmaster')->group(function () {
        Route::get('/dashboard',                    [HeadmasterApiController::class, 'dashboard']);
        Route::post('/ai/insights',                [HeadmasterApiController::class, 'aiInsights']);
        Route::post('/ai/announcements/draft',     [HeadmasterApiController::class, 'draftAnnouncement']);
        Route::get('/announcements',                [HeadmasterApiController::class, 'announcements']);
        Route::post('/announcements',               [HeadmasterApiController::class, 'storeAnnouncement']);
        Route::get('/behaviour',                    [HeadmasterApiController::class, 'behaviourCases']);
        Route::post('/behaviour/{record}/review',   [HeadmasterApiController::class, 'reviewBehaviour']);
    });

    // ── Teacher ────────────────────────────────────────
    Route::middleware('role:admin,teacher')->prefix('teacher')->group(function () {
        Route::get('/dashboard',                    [TeacherApiController::class, 'dashboard']);
        Route::get('/classes',                      [TeacherApiController::class, 'myClasses']);
        Route::get('/classes/{class}/students',     [TeacherApiController::class, 'classStudents']);
        Route::post('/marks',                       [TeacherApiController::class, 'recordMarks']);
        Route::post('/comments',                    [TeacherApiController::class, 'addComment']);
        Route::post('/attendance',                  [TeacherApiController::class, 'markAttendance']);
    });

    // ── Bursar ─────────────────────────────────────────
    // The rest of the legacy bursar surface (fees/payments/fee-structures/debtors/paid) was
    // removed — it was disconnected from real billing (fee-structure creation was a dead
    // end, nothing ever read it to generate a bill) and fully superseded by the modern
    // Finance module, which bursar now has direct access to (see the finance route group).
    Route::middleware('role:admin,bursar')->prefix('bursar')->group(function () {
        Route::get('/dashboard',                    [BursarApiController::class, 'dashboard']);
    });

    // ── Students (staff access) ────────────────────────
    Route::middleware('role:admin,headmaster,teacher,bursar')->group(function () {
        Route::get('/students',                      [StudentApiController::class, 'index']);
        Route::post('/students',                     [StudentApiController::class, 'store']);
        Route::post('/students/bulk-import',         [StudentApiController::class, 'bulkImport'])->middleware('role:admin');
        Route::get('/students/class-list',           [StudentApiController::class, 'classList']);
        Route::get('/students/{id}',                 [StudentApiController::class, 'show']);
        Route::get('/students/{id}/enrollment-history', [StudentApiController::class, 'enrollmentHistory']);
        Route::patch('/students/{id}',               [StudentApiController::class, 'update']);
        Route::post('/students/{id}/guardians',      [StudentApiController::class, 'addGuardian']);
        Route::get('/classes',                       [StudentApiController::class, 'classes']);
        Route::get('/subjects',                      [StudentApiController::class, 'subjects']);
    });

    // ── Parent ─────────────────────────────────────────
    Route::middleware('role:parent')->prefix('parent')->group(function () {
        Route::get('/portal',                       [ParentApiController::class, 'portal']);
        Route::get('/finance/children',             [ParentFinanceController::class, 'myChildrenBalances']);
        Route::get('/finance/child/{id}/bills',     [ParentFinanceController::class, 'childBills']);
        Route::get('/finance/child/{id}/payments',  [ParentFinanceController::class, 'childPayments']);
        Route::get('/finance/child/{id}/receipts',  [ParentFinanceController::class, 'childReceipts']);
        Route::get('/finance/child/{id}/statement', [ParentFinanceController::class, 'childStatement']);
        Route::get('/shop/purchases',               [ParentPurchaseController::class, 'myChildrenPurchases']);
        Route::get('/shop/child/{id}/purchases',    [ParentPurchaseController::class, 'childPurchases']);
        Route::get('/shop/child/{id}/purchases/{purchaseId}', [ParentPurchaseController::class, 'childPurchaseDetails']);
        Route::get('/discipline/summary',           [ParentBehaviourAttendanceController::class, 'myChildrenSummary']);
        Route::get('/discipline/child/{id}/attendance', [ParentBehaviourAttendanceController::class, 'childAttendance']);
        Route::get('/discipline/child/{id}/behaviour',  [ParentBehaviourAttendanceController::class, 'childBehaviour']);
        Route::get('/discipline/child/{id}/actions',    [ParentBehaviourAttendanceController::class, 'childDiscipline']);
        Route::get('/discipline/child/{id}/notifications', [ParentBehaviourAttendanceController::class, 'childNotifications']);

        Route::prefix('stream-native')->group(function () {
            Route::get('/children',                      [StreamNativeParentResultsController::class, 'children']);
            Route::get('/child/{studentId}/term',        [StreamNativeParentResultsController::class, 'childTerm']);
            Route::get('/child/{studentId}/year',        [StreamNativeParentResultsController::class, 'childYear']);
            Route::get('/child/{studentId}/transcript',  [StreamNativeParentResultsController::class, 'childTranscript']);
        });
    });

    // ── Student ────────────────────────────────────────
    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/portal',                       [StudentPortalApiController::class, 'portal']);
        Route::get('/discipline/attendance',        [StudentBehaviourAttendanceController::class, 'myAttendance']);
        Route::get('/discipline/behaviour',         [StudentBehaviourAttendanceController::class, 'myBehaviour']);
        Route::get('/discipline/actions',           [StudentBehaviourAttendanceController::class, 'myDiscipline']);
        Route::get('/discipline/notifications',     [StudentBehaviourAttendanceController::class, 'myNotifications']);
    });

    Route::middleware('role:student')->prefix('stream-native')->group(function () {
        Route::get('/me/transcript',                [StreamNativeTranscriptController::class, 'me']);
        Route::get('/me/results/term',              [StreamNativeResultsController::class, 'myTerm']);
        Route::get('/me/results/year',              [StreamNativeResultsController::class, 'myYear']);
        Route::get('/me/progression/status',        [StreamNativeProgressionController::class, 'myStatus']);
        Route::get('/me/progression/history',       [StreamNativeProgressionController::class, 'myHistory']);
        Route::get('/me/graduation',                [StreamNativeGraduationController::class, 'me']);
    });

    // ── Academic Structure (admin + headmaster) ────────
    Route::middleware('role:admin,headmaster')->group(function () {

        // Academic Years
        Route::get   ('/academic-years',               [AcademicYearController::class, 'index']);
        Route::post  ('/academic-years',               [AcademicYearController::class, 'store']);
        Route::get   ('/academic-years/{id}',          [AcademicYearController::class, 'show']);
        Route::put   ('/academic-years/{id}',          [AcademicYearController::class, 'update']);
        Route::delete('/academic-years/{id}',          [AcademicYearController::class, 'destroy']);
        Route::post  ('/academic-years/{id}/activate', [AcademicYearController::class, 'activate']);

        // Terms
        Route::get   ('/terms',                        [TermController::class, 'index']);
        Route::post  ('/terms',                        [TermController::class, 'store']);
        Route::put   ('/terms/{id}',                   [TermController::class, 'update']);
        Route::delete('/terms/{id}',                   [TermController::class, 'destroy']);
        Route::post  ('/terms/{id}/set-current',       [TermController::class, 'setCurrent']);

        // Forms
        Route::get('/forms',       [FormController::class, 'index']);
        Route::put('/forms/{id}',  [FormController::class, 'update']);

        // Academic Categories
        Route::get   ('/categories',                 [CategoryController::class, 'index']);
        Route::post  ('/categories',                 [CategoryController::class, 'store']);
        Route::put   ('/categories/{id}',            [CategoryController::class, 'update']);
        Route::delete('/categories/{id}',            [CategoryController::class, 'destroy']);
        Route::post  ('/categories/{id}/activate',   [CategoryController::class, 'activate']);
        Route::post  ('/categories/{id}/deactivate', [CategoryController::class, 'deactivate']);

        // Streams
        Route::get   ('/streams',       [StreamController::class, 'index']);
        Route::post  ('/streams',       [StreamController::class, 'store']);
        Route::put   ('/streams/{id}',  [StreamController::class, 'update']);
        Route::delete('/streams/{id}',  [StreamController::class, 'destroy']);

        // Subject Groups
        Route::get   ('/subject-groups',       [SubjectGroupController::class, 'index']);
        Route::post  ('/subject-groups',       [SubjectGroupController::class, 'store']);
        Route::put   ('/subject-groups/{id}',  [SubjectGroupController::class, 'update']);
        Route::delete('/subject-groups/{id}',  [SubjectGroupController::class, 'destroy']);

        // Subjects
        Route::get   ('/subjects',       [SubjectController::class, 'index']);
        Route::post  ('/subjects',       [SubjectController::class, 'store']);
        Route::put   ('/subjects/{id}',  [SubjectController::class, 'update']);
        Route::delete('/subjects/{id}',  [SubjectController::class, 'destroy']);

        // Teacher Allocations
        Route::get   ('/teacher-allocations',          [TeacherAllocationController::class, 'index']);
        Route::post  ('/teacher-allocations',          [TeacherAllocationController::class, 'store']);
        Route::delete('/teacher-allocations/{id}',     [TeacherAllocationController::class, 'destroy']);
        Route::get   ('/teacher-allocations/teachers', [TeacherAllocationController::class, 'teachers']);

        // ── Roles & Permissions (admin only) ──────────────────────────
        Route::get   ('/permissions',                    [RolePermissionController::class, 'permissions']);
        Route::get   ('/roles',                          [RolePermissionController::class, 'roles']);
        Route::get   ('/roles/{id}',                     [RolePermissionController::class, 'showRole']);
        Route::post  ('/roles',                          [RolePermissionController::class, 'storeRole']);
        Route::put   ('/roles/{id}',                     [RolePermissionController::class, 'updateRole']);
        Route::delete('/roles/{id}',                     [RolePermissionController::class, 'destroyRole']);
        Route::post  ('/users/{id}/assign-role',         [RolePermissionController::class, 'assignUserRole']);

        // ── Staff Management ──────────────────────────────
        // Departments
        Route::get   ('/departments',       [DepartmentController::class, 'index']);
        Route::post  ('/departments',       [DepartmentController::class, 'store']);
        Route::put   ('/departments/{id}',  [DepartmentController::class, 'update']);
        Route::delete('/departments/{id}',  [DepartmentController::class, 'destroy']);

        // Staff Members
        Route::get   ('/staff-members',                          [StaffMemberController::class, 'index']);
        Route::post  ('/staff-members',                          [StaffMemberController::class, 'store']);
        Route::get   ('/staff-members/dropdown',                 [StaffMemberController::class, 'listForDropdown']);
        Route::get   ('/staff-members/{id}',                     [StaffMemberController::class, 'show']);
        Route::put   ('/staff-members/{id}',                     [StaffMemberController::class, 'update']);
        Route::delete('/staff-members/{id}',                     [StaffMemberController::class, 'destroy']);
        Route::post  ('/staff-members/{id}/activate',            [StaffMemberController::class, 'activate']);
        Route::post  ('/staff-members/{id}/suspend',             [StaffMemberController::class, 'suspend']);
        Route::post  ('/staff-members/{id}/on-leave',            [StaffMemberController::class, 'setOnLeave']);
        Route::post  ('/staff-members/{id}/resign',              [StaffMemberController::class, 'resign']);
        Route::post  ('/staff-members/{id}/assign-role',         [StaffMemberController::class, 'assignRoles']);
        Route::post  ('/staff-members/{id}/teacher-profile',     [StaffMemberController::class, 'createTeacherProfile']);
        Route::put   ('/staff-members/{id}/teacher-profile',     [StaffMemberController::class, 'updateTeacherProfile']);

        // ── Finance Module ─────────────────────────────────────────────────

        // Fee Categories
        Route::get   ('/finance/categories',                     [FeeCategoryController::class, 'index']);
        Route::post  ('/finance/categories',                     [FeeCategoryController::class, 'store']);
        Route::put   ('/finance/categories/{id}',                [FeeCategoryController::class, 'update']);
        Route::delete('/finance/categories/{id}',                [FeeCategoryController::class, 'destroy']);
        Route::post  ('/finance/categories/{id}/activate',       [FeeCategoryController::class, 'activate']);
        Route::post  ('/finance/categories/{id}/deactivate',     [FeeCategoryController::class, 'deactivate']);

        // Fee Structures
        Route::get   ('/finance/structures',                     [FeeStructureController::class, 'index']);
        Route::post  ('/finance/structures',                     [FeeStructureController::class, 'store']);
        Route::put   ('/finance/structures/{id}',                [FeeStructureController::class, 'update']);
        Route::post  ('/finance/structures/{id}/deactivate',     [FeeStructureController::class, 'deactivate']);
        Route::delete('/finance/structures/{id}',                [FeeStructureController::class, 'destroy']);

        // Student Bills — generate/cancel stay admin/headmaster only (billing policy
        // decisions); viewing what's billed moves to the bursar-inclusive group below,
        // since a bursar needs to see what's owed to actually collect it.
        Route::post  ('/finance/bills/generate-student',         [StudentBillController::class, 'generateForStudent']);
        Route::post  ('/finance/bills/generate-form',            [StudentBillController::class, 'generateForForm']);
        Route::post  ('/finance/bills/generate-stream',          [StudentBillController::class, 'generateForStream']);
        Route::post  ('/finance/bills/{id}/cancel',              [StudentBillController::class, 'cancelBill']);
    });

    // ── Finance Module (day-to-day money handling — bursar's actual job) ──
    Route::middleware('role:admin,headmaster,bursar')->group(function () {
        Route::get   ('/finance/bills',                          [StudentBillController::class, 'index']);
        Route::get   ('/finance/bills/status',                   [StudentBillController::class, 'billingStatus']);
        Route::get   ('/finance/bills/{id}',                     [StudentBillController::class, 'show']);
        Route::get   ('/finance/student/{id}/balance',           [StudentBillController::class, 'studentBalance']);

        Route::get   ('/finance/payments',                       [PaymentController::class, 'index']);
        Route::post  ('/finance/payments',                       [PaymentController::class, 'store']);
        Route::get   ('/finance/payments/{id}/receipt',          [PaymentController::class, 'receipt']);
        Route::get   ('/finance/student/{id}/payments',          [PaymentController::class, 'studentPayments']);
        Route::get   ('/finance/student/{id}/guardians',         [PaymentController::class, 'studentGuardians']);
        // Reversing a payment is a control action, not day-to-day collection — stays
        // gated by an explicit permission on top of the role check.
        Route::middleware('permission:finance.reverse-payment')
              ->post ('/finance/payments/{id}/reverse',          [PaymentController::class, 'reverse']);

        Route::get   ('/finance/reports/summary',                [FinanceReportController::class, 'dashboardSummary']);
        Route::get   ('/finance/reports/daily',                  [FinanceReportController::class, 'dailyCollections']);
        Route::get   ('/finance/reports/monthly',                [FinanceReportController::class, 'monthlyCollections']);
        Route::get   ('/finance/reports/term',                   [FinanceReportController::class, 'termCollections']);
        Route::get   ('/finance/reports/cashier-reconciliation', [FinanceReportController::class, 'cashierReconciliation']);
        Route::get   ('/finance/reports/debtors',                [FinanceReportController::class, 'debtors']);
        Route::get   ('/finance/reports/fully-paid',             [FinanceReportController::class, 'fullyPaid']);
        Route::get   ('/finance/reports/partially-paid',         [FinanceReportController::class, 'partiallyPaid']);
        Route::get   ('/finance/reports/balances-by-form',       [FinanceReportController::class, 'balancesByForm']);
        Route::get   ('/finance/reports/student/{id}/statement', [FinanceReportController::class, 'studentStatement']);
    });

    Route::middleware('role:admin,headmaster,teacher,bursar')->prefix('stream-native')->group(function () {
        Route::get('/results/student/{studentId}/term',        [StreamNativeResultsController::class, 'studentTerm']);
        Route::get('/results/student/{studentId}/year',        [StreamNativeResultsController::class, 'studentYear']);
        Route::get('/results/top-performers',                  [StreamNativeResultsController::class, 'topPerformers']);
        Route::get('/results/at-risk',                         [StreamNativeResultsController::class, 'atRiskStudents']);
        Route::get('/rankings',                                [StreamNativeRankingsController::class, 'rankings']);
        Route::get('/transcript/student/{studentId}',          [StreamNativeTranscriptController::class, 'student']);
        Route::get('/progression/student/{studentId}/status',  [StreamNativeProgressionController::class, 'status']);
        Route::get('/progression/student/{studentId}/history', [StreamNativeProgressionController::class, 'history']);
        Route::get('/graduation/student/{studentId}',          [StreamNativeGraduationController::class, 'student']);
        Route::get('/graduation/dashboard',                    [StreamNativeGraduationController::class, 'dashboard']);
        Route::get('/report-card/student/{studentId}/term',    [StreamNativeReportCardPreviewController::class, 'studentTerm']);
    });

    // School Shop
    Route::middleware('role:admin,bursar,storekeeper')->prefix('shop')->group(function () {
        Route::get('/categories',                 [ShopCategoryController::class, 'index']);
        Route::post('/categories',                [ShopCategoryController::class, 'store'])->middleware('role:admin');
        Route::put('/categories/{id}',            [ShopCategoryController::class, 'update'])->middleware('role:admin');
        Route::delete('/categories/{id}',         [ShopCategoryController::class, 'destroy'])->middleware('role:admin');
        Route::post('/categories/{id}/activate',  [ShopCategoryController::class, 'activate'])->middleware('role:admin');
        Route::post('/categories/{id}/deactivate',[ShopCategoryController::class, 'deactivate'])->middleware('role:admin');

        Route::get('/items/low-stock',            [ShopItemController::class, 'lowStock']);
        Route::get('/items',                      [ShopItemController::class, 'index']);
        Route::post('/items',                     [ShopItemController::class, 'store'])->middleware('role:admin,storekeeper');
        Route::get('/items/{id}',                 [ShopItemController::class, 'show']);
        Route::put('/items/{id}',                 [ShopItemController::class, 'update'])->middleware('role:admin,storekeeper');
        Route::delete('/items/{id}',              [ShopItemController::class, 'destroy'])->middleware('role:admin');
        Route::post('/items/{id}/activate',       [ShopItemController::class, 'activate'])->middleware('role:admin,storekeeper');
        Route::post('/items/{id}/deactivate',     [ShopItemController::class, 'deactivate'])->middleware('role:admin,storekeeper');

        Route::get('/purchases/search-students',  [StudentPurchaseController::class, 'searchStudents'])->middleware('role:admin,bursar,storekeeper');
        Route::get('/purchases/preorders',        [StudentPurchaseController::class, 'preorders'])->middleware('role:admin,bursar,storekeeper');
        Route::get('/purchases',                  [StudentPurchaseController::class, 'index']);
        Route::post('/purchases',                 [StudentPurchaseController::class, 'store'])->middleware('role:admin,bursar,storekeeper');
        Route::get('/purchases/{id}',             [StudentPurchaseController::class, 'show']);
        Route::post('/purchases/{id}/cancel',     [StudentPurchaseController::class, 'cancel'])->middleware('role:admin,bursar');
        Route::post('/purchases/{id}/collect',    [StudentPurchaseController::class, 'markCollected'])->middleware('role:admin,bursar,storekeeper');
        Route::post('/purchases/{id}/fulfill',    [StudentPurchaseController::class, 'fulfillPreorder'])->middleware('role:admin,bursar,storekeeper');
        Route::get('/student/{id}/purchases',     [StudentPurchaseController::class, 'studentPurchases']);

        Route::post('/payments',                  [StudentPurchasePaymentController::class, 'store'])->middleware('role:admin,bursar');
        Route::get('/purchases/{id}/payments',    [StudentPurchasePaymentController::class, 'purchasePayments']);
    });

    Route::middleware('role:admin,bursar,headmaster')->prefix('shop')->group(function () {
        Route::get('/reports/summary',            [ShopReportController::class, 'dashboardSummary']);
        Route::get('/reports/today',              [ShopReportController::class, 'salesToday']);
        Route::get('/reports/date-range',         [ShopReportController::class, 'salesByDateRange']);
        Route::get('/reports/by-item',            [ShopReportController::class, 'salesByItem']);
        Route::get('/reports/by-category',        [ShopReportController::class, 'salesByCategory']);
        Route::get('/reports/unpaid',             [ShopReportController::class, 'unpaidPurchases']);
        Route::get('/reports/low-stock',          [ShopReportController::class, 'lowStockItems']);
        Route::get('/reports/cancelled',          [ShopReportController::class, 'cancelledPurchases']);
    });

    Route::middleware('role:admin,teacher,headmaster')->prefix('discipline')->group(function () {
        Route::get('/meta',                            [AttendanceSessionController::class, 'meta']);
        Route::get('/attendance/sessions',             [AttendanceSessionController::class, 'index']);
        Route::post('/attendance/sessions',            [AttendanceSessionController::class, 'store'])->middleware('role:admin,teacher');
        Route::get('/attendance/sessions/{id}',        [AttendanceSessionController::class, 'show']);
        Route::put('/attendance/sessions/{id}',        [AttendanceSessionController::class, 'update']);
        Route::delete('/attendance/sessions/{id}',     [AttendanceSessionController::class, 'destroy'])->middleware('role:admin');
        Route::post('/attendance/sessions/{id}/submit',[AttendanceSessionController::class, 'submit'])->middleware('role:admin,teacher');
        Route::put('/attendance/records/{id}',         [AttendanceRecordController::class, 'updateRecord'])->middleware('role:admin,teacher,headmaster');
        Route::put('/attendance/sessions/{id}/records',[AttendanceRecordController::class, 'bulkUpdateRecords'])->middleware('role:admin,teacher,headmaster');
        Route::get('/attendance/student/{id}/history', [AttendanceRecordController::class, 'studentHistory']);

        Route::get('/attendance/reports/summary',      [AttendanceReportController::class, 'dashboardSummary']);
        Route::get('/attendance/reports/daily-stream', [AttendanceReportController::class, 'dailyByStream']);
        Route::get('/attendance/reports/absentees',    [AttendanceReportController::class, 'absenteeList']);
        Route::get('/attendance/reports/late',         [AttendanceReportController::class, 'lateList']);
        Route::get('/attendance/reports/student-percentage', [AttendanceReportController::class, 'studentAttendancePercentage']);
        Route::get('/attendance/reports/stream-percentage',  [AttendanceReportController::class, 'streamAttendancePercentage']);
        Route::get('/attendance/reports/monthly',      [AttendanceReportController::class, 'monthlyReport']);
        Route::get('/attendance/reports/repeated-absenteeism', [AttendanceReportController::class, 'repeatedAbsenteeism']);

        Route::get('/behaviour/categories',            [BehaviourCategoryController::class, 'index']);
        Route::post('/behaviour/categories',           [BehaviourCategoryController::class, 'store'])->middleware('role:admin');
        Route::put('/behaviour/categories/{id}',       [BehaviourCategoryController::class, 'update'])->middleware('role:admin');
        Route::delete('/behaviour/categories/{id}',    [BehaviourCategoryController::class, 'destroy'])->middleware('role:admin');
        Route::post('/behaviour/categories/{id}/activate', [BehaviourCategoryController::class, 'activate'])->middleware('role:admin');
        Route::post('/behaviour/categories/{id}/deactivate', [BehaviourCategoryController::class, 'deactivate'])->middleware('role:admin');

        Route::get('/behaviour/incidents',             [BehaviourIncidentController::class, 'index']);
        Route::post('/behaviour/incidents',            [BehaviourIncidentController::class, 'store'])->middleware('role:admin,teacher');
        Route::get('/behaviour/incidents/{id}',        [BehaviourIncidentController::class, 'show']);
        Route::put('/behaviour/incidents/{id}',        [BehaviourIncidentController::class, 'update'])->middleware('role:admin,teacher');
        Route::post('/behaviour/incidents/{id}/review',[BehaviourIncidentController::class, 'review'])->middleware('role:admin,headmaster');
        Route::post('/behaviour/incidents/{id}/escalate',[BehaviourIncidentController::class, 'escalate'])->middleware('role:admin,headmaster');
        Route::post('/behaviour/incidents/{id}/close', [BehaviourIncidentController::class, 'close'])->middleware('role:admin,headmaster');

        Route::get('/actions',                         [DisciplineActionController::class, 'index']);
        Route::post('/actions',                        [DisciplineActionController::class, 'store'])->middleware('role:admin,headmaster');
        Route::get('/actions/{id}',                    [DisciplineActionController::class, 'show']);
        Route::put('/actions/{id}',                    [DisciplineActionController::class, 'update'])->middleware('role:admin,headmaster');
        Route::post('/actions/{id}/complete',          [DisciplineActionController::class, 'complete'])->middleware('role:admin,headmaster');
        Route::post('/actions/{id}/cancel',            [DisciplineActionController::class, 'cancel'])->middleware('role:admin,headmaster');
    });

    Route::middleware('role:admin,headmaster')->prefix('headmaster/discipline')->group(function () {
        Route::get('/summary',             [HeadmasterDisciplineDashboardController::class, 'summary']);
        Route::get('/high-severity',       [HeadmasterDisciplineDashboardController::class, 'highSeverityIncidents']);
        Route::get('/repeated-offenders',  [HeadmasterDisciplineDashboardController::class, 'repeatedOffenders']);
        Route::get('/open-cases',          [HeadmasterDisciplineDashboardController::class, 'openCases']);
        Route::get('/attendance-trends',   [HeadmasterDisciplineDashboardController::class, 'attendanceTrends']);
    });

    Route::middleware('role:admin,headmaster,teacher')->prefix('academic-foundation')->group(function () {
        Route::get('/meta',                         [AcademicSubjectReportController::class, 'meta']);
        Route::get('/stream-subjects',              [StreamSubjectController::class, 'index']);
        Route::post('/stream-subjects',             [StreamSubjectController::class, 'store'])->middleware('role:admin');
        Route::put('/stream-subjects/{id}',         [StreamSubjectController::class, 'update'])->middleware('role:admin');
        Route::delete('/stream-subjects/{id}',      [StreamSubjectController::class, 'destroy'])->middleware('role:admin');
        Route::get('/streams/{id}/subjects',        [StreamSubjectController::class, 'streamSubjects']);

        Route::get('/teacher-allocations',          [AcademicTeacherSubjectAllocationController::class, 'index']);
        Route::post('/teacher-allocations',         [AcademicTeacherSubjectAllocationController::class, 'store'])->middleware('role:admin');
        Route::put('/teacher-allocations/{id}',     [AcademicTeacherSubjectAllocationController::class, 'update'])->middleware('role:admin');
        Route::delete('/teacher-allocations/{id}',  [AcademicTeacherSubjectAllocationController::class, 'destroy'])->middleware('role:admin');
        Route::get('/teachers',                     [AcademicTeacherSubjectAllocationController::class, 'teachers']);
        Route::get('/teachers/{id}/allocations',    [AcademicTeacherSubjectAllocationController::class, 'teacherAllocations']);
        Route::get('/streams/{id}/allocations',     [AcademicTeacherSubjectAllocationController::class, 'streamAllocations']);

        Route::get('/student-subjects',             [StudentSubjectController::class, 'index']);
        Route::post('/student-subjects',            [StudentSubjectController::class, 'store'])->middleware('role:admin');
        Route::post('/student-subjects/{id}/drop',  [StudentSubjectController::class, 'dropSubject'])->middleware('role:admin');
        Route::get('/students/{id}/subjects',       [StudentSubjectController::class, 'studentSubjects']);
        Route::post('/students/{id}/auto-enroll',   [StudentSubjectController::class, 'autoEnrollCompulsorySubjects'])->middleware('role:admin');

        Route::get('/reports/students-per-subject', [AcademicSubjectReportController::class, 'studentsPerSubject']);
        Route::get('/reports/subjects-per-student', [AcademicSubjectReportController::class, 'subjectsPerStudent']);
        Route::get('/reports/teacher-allocations',  [AcademicSubjectReportController::class, 'teacherAllocations']);
        Route::get('/reports/stream-subjects',      [AcademicSubjectReportController::class, 'streamSubjects']);
        Route::get('/reports/unallocated-subjects', [AcademicSubjectReportController::class, 'unallocatedSubjects']);
        Route::get('/reports/streams-without-teachers', [AcademicSubjectReportController::class, 'streamsWithoutTeachers']);
    });

    Route::middleware('role:student')->get('/student/subjects', [StudentSubjectController::class, 'mySubjects']);
    Route::middleware('role:parent')->get('/parent/subjects/child/{id}', [StudentSubjectController::class, 'childSubjects']);

    // ── Assessment Module ──────────────────────────────────────────────────

    // Assessment Types & Grading (admin + headmaster)
    Route::middleware('role:admin,headmaster')->group(function () {
        Route::get   ('/assessment-types',                          [AssessmentTypeController::class, 'index']);
        Route::post  ('/assessment-types',                          [AssessmentTypeController::class, 'store']);
        Route::put   ('/assessment-types/{id}',                     [AssessmentTypeController::class, 'update']);
        Route::post  ('/assessment-types/{id}/deactivate',          [AssessmentTypeController::class, 'deactivate']);
        Route::post  ('/assessment-types/{id}/activate',            [AssessmentTypeController::class, 'activate']);
        Route::get   ('/grading-scales',                            [AssessmentTypeController::class, 'gradingScales']);

        // Approve / reopen assessments
        Route::post  ('/assessments/{id}/approve',                  [AssessmentController::class, 'approve']);
        Route::post  ('/assessments/{id}/reopen',                   [AssessmentController::class, 'reopen']);

        // Reports
        Route::get   ('/assessments/reports/summary',               [AssessmentReportController::class, 'assessmentSummary']);
        Route::get   ('/assessments/reports/subject-performance',   [AssessmentReportController::class, 'subjectPerformance']);
        Route::get   ('/assessments/reports/weekly-trend',          [AssessmentReportController::class, 'weeklyTrend']);
        Route::get   ('/assessments/reports/student/{id}/progress', [AssessmentReportController::class, 'studentProgress']);
        Route::get   ('/assessments/reports/student/{id}/monthly',  [AssessmentReportController::class, 'monthlyTrend']);
        Route::get   ('/assessments/{id}/performance',              [AssessmentReportController::class, 'classAssessmentPerformance']);
    });

    // Assessments (admin + headmaster + teacher)
    Route::middleware('role:admin,headmaster,teacher')->group(function () {
        Route::get   ('/assessment-types',                          [AssessmentTypeController::class, 'index']);
        Route::get   ('/grading-scales',                            [AssessmentTypeController::class, 'gradingScales']);
        Route::get   ('/assessments',                               [AssessmentController::class, 'index']);
        Route::post  ('/assessments',                               [AssessmentController::class, 'store']);
        Route::get   ('/assessments/my-allocations',                [AssessmentController::class, 'myAllocations']);
        Route::get   ('/assessments/student/{id}/marks',            [AssessmentMarkController::class, 'studentMarks']);
        Route::get   ('/assessments/{id}',                          [AssessmentController::class, 'show']);
        Route::put   ('/assessments/{id}',                          [AssessmentController::class, 'update']);
        Route::post  ('/assessments/{id}/submit',                   [AssessmentController::class, 'submit']);
        Route::post  ('/assessments/{id}/cancel',                   [AssessmentController::class, 'cancel']);
        Route::post  ('/assessments/{id}/marks',                    [AssessmentMarkController::class, 'bulkSaveMarks']);
        Route::post  ('/assessments/{id}/marks/submit',             [AssessmentMarkController::class, 'submitMarks']);
    });

    // Parent assessment view
    Route::middleware('role:parent')->group(function () {
        Route::get('/parent/assessments/children',                  [ParentAssessmentController::class, 'myChildrenAssessmentSummary']);
        Route::get('/parent/assessments/child/{id}/marks',          [ParentAssessmentController::class, 'childMarks']);
        Route::get('/parent/assessments/child/{id}/progress',       [ParentAssessmentController::class, 'childProgress']);
    });

    // Student assessment view
    Route::middleware('role:student')->group(function () {
        Route::get('/student/assessments/marks',                    [StudentAssessmentController::class, 'myMarks']);
        Route::get('/student/assessments/progress',                 [StudentAssessmentController::class, 'myProgress']);
    });

    // Report Cards, Rankings & PDF Exports
    Route::middleware('role:admin,headmaster')->group(function () {
        Route::post('/reports/student/generate',                    [ReportCardController::class, 'generateStudentReport']);
        Route::post('/reports/stream/generate',                     [ReportCardController::class, 'generateStreamReports']);
        Route::post('/reports/{id}/approve',                        [ReportCardController::class, 'approve']);
        Route::post('/reports/{id}/publish',                        [ReportCardController::class, 'publish']);
        Route::post('/reports/{id}/headmaster-comment',             [ReportCommentController::class, 'saveHeadmasterComment']);
        Route::post('/reports/{id}/recommendation',                 [ReportCommentController::class, 'saveRecommendation']);
        Route::get('/reports/batch-download',                       [ReportCardController::class, 'batchDownloadPdf']);
        Route::get('/reports/rankings/stream',                      [ReportRankingController::class, 'streamRankings']);
        Route::get('/reports/rankings/subject',                     [ReportRankingController::class, 'subjectRankings']);
        Route::get('/reports/top-performers',                       [ReportRankingController::class, 'topPerformers']);
        Route::get('/reports/performance-trends',                   [ReportRankingController::class, 'performanceTrends']);
    });

    Route::middleware('role:admin,headmaster,teacher')->group(function () {
        Route::get('/reports/student/{studentId}',                  [ReportCardController::class, 'studentReport']);
        Route::get('/reports/{id}',                                 [ReportCardController::class, 'show']);
        Route::get('/reports/{id}/download',                        [ReportCardController::class, 'downloadPdf']);
        Route::post('/reports/{id}/teacher-comment',                [ReportCommentController::class, 'saveTeacherComment']);
    });

    Route::middleware('role:parent')->group(function () {
        Route::get('/parent/reports',                               [ParentReportController::class, 'myChildrenReports']);
        Route::get('/parent/reports/child/{studentId}',             [ParentReportController::class, 'childReport']);
        Route::get('/parent/reports/{reportId}/download',           [ParentReportController::class, 'downloadChildReport']);
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/student/reports',                              [StudentReportController::class, 'myReports']);
        Route::get('/student/reports/{reportId}/download',          [StudentReportController::class, 'downloadMyReport']);
    });

    // Timetable + Scheduling
    Route::middleware('role:admin,headmaster')->group(function () {
        Route::get('/timetable/periods',                             [TimetablePeriodController::class, 'index']);
        Route::post('/timetable/periods',                            [TimetablePeriodController::class, 'store']);
        Route::put('/timetable/periods/{id}',                        [TimetablePeriodController::class, 'update']);
        Route::post('/timetable/periods/{id}/deactivate',            [TimetablePeriodController::class, 'deactivate']);
        Route::get('/timetable/rooms',                               [TimetableRoomController::class, 'index']);
        Route::post('/timetable/rooms',                              [TimetableRoomController::class, 'store']);
        Route::put('/timetable/rooms/{id}',                          [TimetableRoomController::class, 'update']);
        Route::post('/timetable/rooms/{id}/deactivate',              [TimetableRoomController::class, 'deactivate']);
        Route::post('/timetable/validate/teacher',                   [TimetableValidationController::class, 'validateTeacherClash']);
        Route::post('/timetable/validate/room',                      [TimetableValidationController::class, 'validateRoomClash']);
        Route::post('/timetable/validate/stream',                    [TimetableValidationController::class, 'validateStreamClash']);
        Route::post('/timetable',                                    [TimetableController::class, 'store']);
        Route::put('/timetable/{id}',                                [TimetableController::class, 'update']);
        Route::delete('/timetable/{id}',                             [TimetableController::class, 'destroy']);
    });

    Route::middleware('role:admin,headmaster,teacher')->group(function () {
        Route::get('/timetable',                                     [TimetableController::class, 'index']);
        Route::get('/timetable/stream/{streamId}',                   [TimetableController::class, 'streamTimetable']);
        Route::get('/timetable/teacher/{teacherId}',                 [TimetableController::class, 'teacherTimetable']);
        Route::get('/timetable/room/{roomId}',                       [TimetableController::class, 'roomTimetable']);
        Route::get('/timetable/export/pdf',                          [TimetableController::class, 'exportPdf']);
    });

    Route::middleware('role:student')->get('/student/timetable',      [StudentTimetableController::class, 'myTimetable']);
    Route::middleware('role:parent')->get('/parent/timetable/child/{studentId}', [ParentTimetableController::class, 'childTimetable']);
});
