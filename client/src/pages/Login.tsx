import { getLoginUrl } from "@/const";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export default function Login() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-primary/5 via-background to-secondary/5 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Logo and Header */}
        <div className="text-center mb-8">
          <div className="flex justify-center mb-4">
            <div className="w-[72px] h-[72px] rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center p-2.5">
              <img src="/logo-mark.png" alt="DestinyGate Institute" className="w-full h-full object-contain" />
            </div>
          </div>
          <h1 className="text-3xl font-bold text-foreground mb-2">DestinyGate Institute</h1>
          <p className="text-muted-foreground italic">"Raising a Godly, Skilled and Confident Generation"</p>
        </div>

        {/* Login Card */}
        <Card className="shadow-lg border-border">
          <CardHeader className="space-y-2">
            <CardTitle>Welcome Back</CardTitle>
            <CardDescription>Sign in to access your account and manage school operations</CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Features List */}
            <div className="space-y-3 py-4">
              <div className="flex items-start gap-3">
                <div className="text-secondary mt-1">✓</div>
                <div>
                  <p className="font-medium text-sm">Student Management</p>
                  <p className="text-xs text-muted-foreground">Complete student records and profiles</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="text-secondary mt-1">✓</div>
                <div>
                  <p className="font-medium text-sm">Finance Tracking</p>
                  <p className="text-xs text-muted-foreground">Fee management and payment records</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="text-secondary mt-1">✓</div>
                <div>
                  <p className="font-medium text-sm">Academic Progress</p>
                  <p className="text-xs text-muted-foreground">Marks, results, and performance tracking</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="text-secondary mt-1">✓</div>
                <div>
                  <p className="font-medium text-sm">Role-Based Access</p>
                  <p className="text-xs text-muted-foreground">Secure portals for all user types</p>
                </div>
              </div>
            </div>

            {/* Login Button */}
            <Button
              onClick={() => {
                window.location.href = getLoginUrl();
              }}
              className="w-full bg-primary hover:bg-primary/90 text-primary-foreground h-10"
              size="lg"
            >
              Sign in with Manus
            </Button>

            {/* Footer */}
            <p className="text-xs text-center text-muted-foreground">
              Secure authentication powered by Manus OAuth
            </p>
          </CardContent>
        </Card>

        {/* Info Section */}
        <div className="mt-8 grid grid-cols-3 gap-4 text-center">
          <div>
            <p className="text-2xl font-bold text-secondary">21</p>
            <p className="text-xs text-muted-foreground">Database Tables</p>
          </div>
          <div>
            <p className="text-2xl font-bold text-secondary">12</p>
            <p className="text-xs text-muted-foreground">Core Modules</p>
          </div>
          <div>
            <p className="text-2xl font-bold text-secondary">7</p>
            <p className="text-xs text-muted-foreground">User Roles</p>
          </div>
        </div>
      </div>
    </div>
  );
}
