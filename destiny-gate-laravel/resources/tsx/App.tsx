import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './hooks/useAuth';
import Layout from './components/Layout';
import Login from './pages/Login';
import ChangePassword from './pages/ChangePassword';
import VerifyPaymentPage from './pages/public/VerifyPaymentPage';

// Pages
import AdminDashboard    from './pages/admin/Dashboard';
import Users             from './pages/admin/Users';
import Staff             from './pages/admin/Staff';
import Classes           from './pages/admin/Classes';
import Applications      from './pages/admin/Applications';
import Students          from './pages/Students';
import ClassListPage     from './pages/ClassListPage';
import StudentDetail     from './pages/StudentDetail';
import HeadmasterDash    from './pages/headmaster/Dashboard';
import Behaviour         from './pages/headmaster/Behaviour';
import BursarDash        from './pages/bursar/Dashboard';
import TeacherDash       from './pages/teacher/Dashboard';
import ClassStudents     from './pages/teacher/ClassStudents';
import ParentPortal      from './pages/parent/Portal';
import StudentPortal     from './pages/student/Portal';
import AcademicYears     from './pages/academic/AcademicYears';
import Terms             from './pages/academic/Terms';
import Forms             from './pages/academic/Forms';
import Streams           from './pages/academic/Streams';
import SubjectGroups     from './pages/academic/SubjectGroups';
import Subjects          from './pages/academic/Subjects';
import TeacherAllocation from './pages/academic/TeacherAllocation';
import Departments       from './pages/staff/Departments';
import StaffPage         from './pages/staff/StaffPage';
import StaffProfilePage  from './pages/staff/StaffProfilePage';
import TeacherProfiles   from './pages/staff/TeacherProfiles';
import RolesPermissions  from './pages/admin/RolesPermissions';
// Finance Module
import FinanceDashboard  from './pages/finance/FinanceDashboard';
import FeeCategoriesPage from './pages/finance/FeeCategoriesPage';
import FeeStructuresPage from './pages/finance/FeeStructuresPage';
import StudentBillsPage  from './pages/finance/StudentBillsPage';
import GenerateBillsPage from './pages/finance/GenerateBillsPage';
import RecordPaymentPage from './pages/finance/RecordPaymentPage';
import PaymentHistoryPage from './pages/finance/PaymentHistoryPage';
import CashierReconciliationPage from './pages/finance/CashierReconciliationPage';
import ParentFinancePage from './pages/finance/ParentFinancePage';
// Assessment Module
import AssessmentTypesPage    from './pages/assessments/AssessmentTypesPage';
import AssessmentsPage        from './pages/assessments/AssessmentsPage';
import CreateAssessmentPage   from './pages/assessments/CreateAssessmentPage';
import EnterMarksPage         from './pages/assessments/EnterMarksPage';
import AssessmentDetailsPage  from './pages/assessments/AssessmentDetailsPage';
import AssessmentReportsPage  from './pages/assessments/AssessmentReportsPage';
import ParentAssessmentPage   from './pages/assessments/ParentAssessmentPage';
import StudentAssessmentPage  from './pages/assessments/StudentAssessmentPage';
// School Shop
import ShopDashboard from './pages/shop/ShopDashboard';
import ShopCategoriesPage from './pages/shop/ShopCategoriesPage';
import ShopItemsPage from './pages/shop/ShopItemsPage';
import RecordPurchasePage from './pages/shop/RecordPurchasePage';
import PreordersPage from './pages/shop/PreordersPage';
import PurchaseDetailsPage from './pages/shop/PurchaseDetailsPage';
import PurchasePaymentsPage from './pages/shop/PurchasePaymentsPage';
import ShopReportsPage from './pages/shop/ShopReportsPage';
import ParentPurchasesPage from './pages/parent/ParentPurchasesPage';
import ParentPurchaseDetailsPage from './pages/parent/ParentPurchaseDetailsPage';
// Attendance + Behaviour / Discipline
import AttendanceDashboard from './pages/discipline/AttendanceDashboard';
import AttendanceSessionsPage from './pages/discipline/AttendanceSessionsPage';
import MarkAttendancePage from './pages/discipline/MarkAttendancePage';
import AttendanceReportsPage from './pages/discipline/AttendanceReportsPage';
import BehaviourCategoriesPage from './pages/discipline/BehaviourCategoriesPage';
import BehaviourIncidentsPage from './pages/discipline/BehaviourIncidentsPage';
import BehaviourIncidentDetailsPage from './pages/discipline/BehaviourIncidentDetailsPage';
import DisciplineActionsPage from './pages/discipline/DisciplineActionsPage';
import HeadmasterDisciplineDashboard from './pages/discipline/HeadmasterDisciplineDashboard';
import ParentAttendanceBehaviourPage from './pages/parent/ParentAttendanceBehaviourPage';
import ParentNotificationsPage from './pages/parent/ParentNotificationsPage';
import StudentAttendanceBehaviourPage from './pages/student/StudentAttendanceBehaviourPage';
import StudentNotificationsPage from './pages/student/StudentNotificationsPage';
// Academic Progress Foundation
import StreamSubjectsPage from './pages/academic-foundation/StreamSubjectsPage';
import TeacherAllocationsPage from './pages/academic-foundation/TeacherAllocationsPage';
import StudentSubjectEnrollmentPage from './pages/academic-foundation/StudentSubjectEnrollmentPage';
import StudentSubjectProfilePage from './pages/academic-foundation/StudentSubjectProfilePage';
import AcademicSubjectReportsPage from './pages/academic-foundation/AcademicSubjectReportsPage';
import MyTeachingAllocationsPage from './pages/academic-foundation/MyTeachingAllocationsPage';
import MySubjectsPage from './pages/student/MySubjectsPage';
import ChildSubjectsPage from './pages/parent/ChildSubjectsPage';
// Report Cards
import ReportGenerationPage from './pages/reports/ReportGenerationPage';
import StreamRankingPage from './pages/reports/StreamRankingPage';
import SubjectRankingPage from './pages/reports/SubjectRankingPage';
import ReportCardPreviewPage from './pages/reports/ReportCardPreviewPage';
import BatchReportPrintPage from './pages/reports/BatchReportPrintPage';
import TeacherReportCommentsPage from './pages/reports/TeacherReportCommentsPage';
import ParentReportsPage from './pages/reports/ParentReportsPage';
import StudentReportsPage from './pages/reports/StudentReportsPage';

// Timetable Module
import TimetableBuilderPage from './pages/timetable/TimetableBuilderPage';
import TimetablePeriodsPage from './pages/timetable/TimetablePeriodsPage';
import TimetableRoomsPage from './pages/timetable/TimetableRoomsPage';
import StreamTimetablePage  from './pages/timetable/StreamTimetablePage';
import TeacherTimetablePage from './pages/timetable/TeacherTimetablePage';
import MyClassTimetablePage from './pages/timetable/MyClassTimetablePage';
import ChildTimetablePage   from './pages/timetable/ChildTimetablePage';

function dashboardPath(role: string): string {
  const map: Record<string, string> = {
    admin: '/app/admin', headmaster: '/app/headmaster',
    teacher: '/app/teacher', bursar: '/app/bursar',
    storekeeper: '/app/shop/items',
    parent: '/app/parent', student: '/app/student',
  };
  return map[role] ?? '/app/admin';
}

export default function App() {
  const { user, login, logout, clearMustChangePassword } = useAuth();

  const publicRoutes = (
    <>
      <Route path="/verify-payment" element={<VerifyPaymentPage />} />
      <Route path="/verify-payment/:reference" element={<VerifyPaymentPage />} />
    </>
  );

  // Not logged in
  if (!user) {
    return (
      <Routes>
        {publicRoutes}
        <Route path="*" element={<Login onLogin={login} />} />
      </Routes>
    );
  }

  // Logged in but must change password first
  if (user.must_change_password) {
    return <ChangePassword userName={user.name} onDone={clearMustChangePassword} />;
  }

  // Fully authenticated
  return (
    <Layout user={user} onLogout={logout}>
      <Routes>
        {publicRoutes}
        
        {/* Root redirect */}
        <Route path="/"    element={<Navigate to={dashboardPath(user.role)} replace />} />
        <Route path="/app" element={<Navigate to={dashboardPath(user.role)} replace />} />

        {/* Admin */}
        <Route path="/app/admin"              element={<AdminDashboard />} />
        <Route path="/app/admin/users"        element={<Users />} />
        <Route path="/app/admin/staff"        element={<Staff />} />
        <Route path="/app/admin/classes"      element={<Classes />} />
        <Route path="/app/admin/applications" element={<Applications />} />

        {/* Headmaster */}
        <Route path="/app/headmaster"               element={<HeadmasterDash />} />
        <Route path="/app/headmaster/behaviour"     element={<Behaviour />} />
        <Route path="/app/headmaster/announcements" element={<HeadmasterDash />} />
        <Route path="/app/headmaster/reports"       element={<HeadmasterDash />} />

        {/* Teacher */}
        <Route path="/app/teacher"                  element={<TeacherDash />} />
        <Route path="/app/teacher/classes"          element={<TeacherDash />} />
        <Route path="/app/teacher/classes/:classId" element={<ClassStudents />} />

        {/* Bursar — day-to-day finance now lives under /app/finance/* (see Finance Module) */}
        <Route path="/app/bursar"                element={<BursarDash />} />

        {/* Students */}
        <Route path="/app/students"            element={<Students />} />
        <Route path="/app/students/class-list" element={<ClassListPage />} />
        <Route path="/app/students/:id"        element={<StudentDetail />} />

        {/* Portals */}
        <Route path="/app/parent"  element={<ParentPortal />} />
        <Route path="/app/student" element={<StudentPortal />} />

        {/* Academic Structure */}
        <Route path="/app/academic/years"          element={<AcademicYears />} />
        <Route path="/app/academic/terms"          element={<Terms />} />
        <Route path="/app/academic/forms"          element={<Forms />} />
        <Route path="/app/academic/streams"        element={<Streams />} />
        <Route path="/app/academic/subject-groups" element={<SubjectGroups />} />
        <Route path="/app/academic/subjects"       element={<Subjects />} />
        <Route path="/app/academic/allocations"    element={<TeacherAllocation />} />

        {/* Staff Management */}
        <Route path="/app/staff/departments" element={<Departments />} />
        <Route path="/app/staff/teachers"    element={<TeacherProfiles />} />
        <Route path="/app/staff/:id"         element={<StaffProfilePage />} />
        <Route path="/app/staff"             element={<StaffPage />} />

        {/* Roles & Permissions */}
        <Route path="/app/admin/roles" element={<RolesPermissions />} />

        {/* Finance Module */}
        <Route path="/app/finance"              element={<FinanceDashboard />} />
        <Route path="/app/finance/categories"   element={<FeeCategoriesPage />} />
        <Route path="/app/finance/structures"   element={<FeeStructuresPage />} />
        <Route path="/app/finance/bills"        element={<StudentBillsPage />} />
        <Route path="/app/finance/generate"     element={<GenerateBillsPage />} />
        <Route path="/app/finance/payments"     element={<RecordPaymentPage />} />
        <Route path="/app/finance/history"      element={<PaymentHistoryPage />} />
        <Route path="/app/finance/reconciliation" element={<CashierReconciliationPage />} />
        <Route path="/app/finance/parent"       element={<ParentFinancePage />} />

        {/* Assessment Module */}
        <Route path="/app/assessments"                  element={<AssessmentsPage />} />
        <Route path="/app/assessments/create"           element={<CreateAssessmentPage />} />
        <Route path="/app/assessments/types"            element={<AssessmentTypesPage />} />
        <Route path="/app/assessments/reports"          element={<AssessmentReportsPage />} />
        <Route path="/app/assessments/parent"           element={<ParentAssessmentPage />} />
        <Route path="/app/assessments/student"          element={<StudentAssessmentPage />} />
        <Route path="/app/assessments/:id"              element={<AssessmentDetailsPage />} />
        <Route path="/app/assessments/:id/marks"        element={<EnterMarksPage />} />

        {/* School Shop */}
        <Route path="/app/shop"                         element={<ShopDashboard />} />
        <Route path="/app/shop/categories"              element={<ShopCategoriesPage />} />
        <Route path="/app/shop/items"                   element={<ShopItemsPage />} />
        <Route path="/app/shop/record"                  element={<RecordPurchasePage />} />
        <Route path="/app/shop/preorders"               element={<PreordersPage />} />
        <Route path="/app/shop/purchases/:id"           element={<PurchaseDetailsPage />} />
        <Route path="/app/shop/purchases/:id/payments"  element={<PurchasePaymentsPage />} />
        <Route path="/app/shop/reports"                 element={<ShopReportsPage />} />
        <Route path="/app/parent/shop/purchases"        element={<ParentPurchasesPage />} />
        <Route path="/app/parent/shop/purchases/:studentId/:purchaseId" element={<ParentPurchaseDetailsPage />} />

        {/* Attendance + Behaviour / Discipline */}
        <Route path="/app/discipline/attendance"                  element={<AttendanceDashboard />} />
        <Route path="/app/discipline/attendance/sessions"         element={<AttendanceSessionsPage />} />
        <Route path="/app/discipline/attendance/sessions/:id"     element={<MarkAttendancePage />} />
        <Route path="/app/discipline/attendance/reports"          element={<AttendanceReportsPage />} />
        <Route path="/app/discipline/behaviour/categories"        element={<BehaviourCategoriesPage />} />
        <Route path="/app/discipline/behaviour/incidents"         element={<BehaviourIncidentsPage />} />
        <Route path="/app/discipline/behaviour/incidents/:id"     element={<BehaviourIncidentDetailsPage />} />
        <Route path="/app/discipline/actions"                     element={<DisciplineActionsPage />} />
        <Route path="/app/headmaster/discipline"                  element={<HeadmasterDisciplineDashboard />} />
        <Route path="/app/parent/discipline"                      element={<ParentAttendanceBehaviourPage />} />
        <Route path="/app/parent/notifications"                   element={<ParentNotificationsPage />} />
        <Route path="/app/student/discipline"                     element={<StudentAttendanceBehaviourPage />} />
        <Route path="/app/student/notifications"                  element={<StudentNotificationsPage />} />

        {/* Academic Progress Foundation */}
        <Route path="/app/academic-foundation/stream-subjects"     element={<StreamSubjectsPage />} />
        <Route path="/app/academic-foundation/teacher-allocations" element={<TeacherAllocationsPage />} />
        <Route path="/app/academic-foundation/enrolment"           element={<StudentSubjectEnrollmentPage />} />
        <Route path="/app/academic-foundation/student-profile"     element={<StudentSubjectProfilePage />} />
        <Route path="/app/academic-foundation/reports"             element={<AcademicSubjectReportsPage />} />
        <Route path="/app/teacher/allocations"                     element={<MyTeachingAllocationsPage />} />
        <Route path="/app/student/subjects"                        element={<MySubjectsPage />} />
        <Route path="/app/parent/subjects"                         element={<ChildSubjectsPage />} />

        {/* Report Cards */}
        <Route path="/app/reports"                                  element={<ReportGenerationPage />} />
        <Route path="/app/reports/rankings/stream"                  element={<StreamRankingPage />} />
        <Route path="/app/reports/rankings/subject"                 element={<SubjectRankingPage />} />
        <Route path="/app/reports/batch"                            element={<BatchReportPrintPage />} />
        <Route path="/app/reports/comments"                         element={<TeacherReportCommentsPage />} />
        <Route path="/app/reports/:id"                              element={<ReportCardPreviewPage />} />
        <Route path="/app/parent/reports"                           element={<ParentReportsPage />} />
        <Route path="/app/student/reports"                          element={<StudentReportsPage />} />

        {/* Timetable Module */}
        <Route path="/app/timetable"                    element={<TimetableBuilderPage />} />
        <Route path="/app/timetable/periods"            element={<TimetablePeriodsPage />} />
        <Route path="/app/timetable/rooms"              element={<TimetableRoomsPage />} />
        <Route path="/app/timetable/builder"            element={<TimetableBuilderPage />} />
        <Route path="/app/timetable/stream/:streamId"   element={<StreamTimetablePage />} />
        <Route path="/app/timetable/teachers"           element={<TeacherTimetablePage />} />
        <Route path="/app/timetable/my-timetable"       element={<MyClassTimetablePage />} />
        <Route path="/app/timetable/child"              element={<ChildTimetablePage />} />
        {/* Fallback */}
        <Route path="*" element={<Navigate to={dashboardPath(user.role)} replace />} />
      </Routes>
    </Layout>
  );
}


