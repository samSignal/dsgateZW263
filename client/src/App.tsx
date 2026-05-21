import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { useAuth } from "@/_core/hooks/useAuth";
import { Route, Switch } from "wouter";
import ErrorBoundary from "./components/ErrorBoundary";
import { ThemeProvider } from "./contexts/ThemeContext";
import Home from "./pages/Home";
import NotFound from "./pages/NotFound";
import AdminDashboard from "./pages/AdminDashboard";
import HeadmasterDashboard from "./pages/HeadmasterDashboard";
import TeacherDashboard from "./pages/TeacherDashboard";
import BursarDashboard from "./pages/BursarDashboard";
import ParentPortal from "./pages/ParentPortal";
import StudentPortal from "./pages/StudentPortal";
import Login from "./pages/Login";
import AdmissionLandingPage from "./pages/AdmissionLandingPage";
import OnlineApplicationPage from "./pages/OnlineApplicationPage";
import ContinueApplicationPage from "./pages/ContinueApplicationPage";
import AdmissionTrackingPage from "./pages/AdmissionTrackingPage";
import AdmissionsDashboardPage from "./pages/AdmissionsDashboardPage";
import ReviewApplicationPage from "./pages/ReviewApplicationPage";
import ApplicationTimelinePage from "./pages/ApplicationTimelinePage";
import AdmissionNotificationsPage from "./pages/AdmissionNotificationsPage";

function Router() {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="flex-center min-h-screen">
        <div className="animate-pulse text-foreground">Loading...</div>
      </div>
    );
  }

  // Public routes
  if (!user) {
    return (
      <Switch>
        <Route path="/" component={Home} />
        <Route path="/login" component={Login} />
        <Route path="/admissions" component={AdmissionLandingPage} />
        <Route path="/admissions/apply" component={OnlineApplicationPage} />
        <Route path="/admissions/continue" component={ContinueApplicationPage} />
        <Route path="/admissions/track" component={AdmissionTrackingPage} />
        <Route path="/404" component={NotFound} />
        <Route component={NotFound} />
      </Switch>
    );
  }

  // Role-based routing
  return (
    <Switch>
      {/* Admin Routes */}
      {user.role === "admin" && (
        <>
          <Route path="/admin/*" component={AdminDashboard} />
          <Route path="/admissions-office" component={AdmissionsDashboardPage} />
          <Route path="/admissions-office/applications/:id" component={ReviewApplicationPage} />
          <Route path="/admissions-office/timeline" component={ApplicationTimelinePage} />
          <Route path="/admissions-office/notifications" component={AdmissionNotificationsPage} />
          <Route path="/dashboard" component={AdminDashboard} />
        </>
      )}

      {/* Headmaster Routes */}
      {user.role === "headmaster" && (
        <>
          <Route path="/headmaster/*" component={HeadmasterDashboard} />
          <Route path="/admissions-office" component={AdmissionsDashboardPage} />
          <Route path="/admissions-office/applications/:id" component={ReviewApplicationPage} />
          <Route path="/dashboard" component={HeadmasterDashboard} />
        </>
      )}

      {/* Teacher Routes */}
      {user.role === "teacher" && (
        <>
          <Route path="/teacher/*" component={TeacherDashboard} />
          <Route path="/dashboard" component={TeacherDashboard} />
        </>
      )}

      {/* Bursar Routes */}
      {user.role === "bursar" && (
        <>
          <Route path="/bursar/*" component={BursarDashboard} />
          <Route path="/dashboard" component={BursarDashboard} />
        </>
      )}

      {user.role === "admissions_office" && (
        <>
          <Route path="/admissions-office" component={AdmissionsDashboardPage} />
          <Route path="/admissions-office/applications/:id" component={ReviewApplicationPage} />
          <Route path="/admissions-office/timeline" component={ApplicationTimelinePage} />
          <Route path="/admissions-office/notifications" component={AdmissionNotificationsPage} />
          <Route path="/dashboard" component={AdmissionsDashboardPage} />
        </>
      )}

      {/* Parent Routes */}
      {user.role === "parent" && (
        <>
          <Route path="/parent/*" component={ParentPortal} />
          <Route path="/dashboard" component={ParentPortal} />
        </>
      )}

      {/* Student Routes */}
      {user.role === "student" && (
        <>
          <Route path="/student/*" component={StudentPortal} />
          <Route path="/dashboard" component={StudentPortal} />
        </>
      )}

      {/* Default redirect to dashboard */}
      <Route path="/admissions" component={AdmissionLandingPage} />
      <Route path="/admissions/apply" component={OnlineApplicationPage} />
      <Route path="/admissions/continue" component={ContinueApplicationPage} />
      <Route path="/admissions/track" component={AdmissionTrackingPage} />
      <Route path="/" component={Home} />
      <Route path="/404" component={NotFound} />
      <Route component={NotFound} />
    </Switch>
  );
}

function App() {
  return (
    <ErrorBoundary>
      <ThemeProvider defaultTheme="light">
        <TooltipProvider>
          <Toaster />
          <Router />
        </TooltipProvider>
      </ThemeProvider>
    </ErrorBoundary>
  );
}

export default App;
