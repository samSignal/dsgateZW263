import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { useAuth } from "@/_core/hooks/useAuth";
import { Route, Switch } from "wouter";
import ErrorBoundary from "./components/ErrorBoundary";
import { ThemeProvider } from "./contexts/ThemeContext";
import Home from "./pages/Home";
import ComponentShowcase from "./pages/ComponentShowcase";
import NotFound from "./pages/NotFound";
import Login from "./pages/Login";

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
        <Route path="/404" component={NotFound} />
        <Route component={NotFound} />
      </Switch>
    );
  }

  return (
    <Switch>
      <Route path="/dashboard" component={ComponentShowcase} />
      <Route path="/" component={ComponentShowcase} />
      <Route path="/404" component={NotFound} />
      <Route component={NotFound} />
    </Switch>
  );
}

function App() {
  return (
    <ErrorBoundary>
      <ThemeProvider defaultTheme="light" switchable>
        <TooltipProvider>
          <Toaster />
          <Router />
        </TooltipProvider>
      </ThemeProvider>
    </ErrorBoundary>
  );
}

export default App;
