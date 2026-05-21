import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { BarChart3, TrendingUp, AlertCircle, Users, LogOut, Download } from "lucide-react";
import { useState } from "react";
import { trpc } from "@/lib/trpc";
import { toast } from "sonner";

export default function HeadmasterDashboard() {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState("overview");

  const handleGenerateReport = (reportType: string) => {
    toast.success(`${reportType} report generated`);
  };

  return (
    <div className="min-h-screen bg-background">
      <div className="border-b border-border bg-card/50 backdrop-blur-sm sticky top-0 z-40">
        <div className="container flex justify-between items-center h-16">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Headmaster Dashboard</h1>
            <p className="text-sm text-muted-foreground">School Management Overview</p>
          </div>
          <Button onClick={() => logout()} variant="outline" className="gap-2">
            <LogOut size={16} />
            Logout
          </Button>
        </div>
      </div>

      <div className="container py-8">
        {/* Key Performance Indicators */}
        <div className="grid md:grid-cols-4 gap-4 mb-8">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <Users size={16} className="text-primary" />
                Total Students
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0</p>
              <p className="text-xs text-muted-foreground mt-1">Active enrollment</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <TrendingUp size={16} className="text-secondary" />
                Fees Collected
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">ZWL 0</p>
              <p className="text-xs text-muted-foreground mt-1">This term</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <BarChart3 size={16} className="text-accent" />
                Attendance Rate
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0%</p>
              <p className="text-xs text-muted-foreground mt-1">Average</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <AlertCircle size={16} className="text-red-600" />
                Behaviour Cases
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0</p>
              <p className="text-xs text-muted-foreground mt-1">This term</p>
            </CardContent>
          </Card>
        </div>

        {/* Tabs */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="reports">Reports</TabsTrigger>
            <TabsTrigger value="analytics">Analytics</TabsTrigger>
            <TabsTrigger value="management">Management</TabsTrigger>
          </TabsList>

          {/* Overview Tab */}
          <TabsContent value="overview" className="space-y-4">
            <div className="grid md:grid-cols-2 gap-4">
              <Card>
                <CardHeader>
                  <CardTitle>Academic Performance</CardTitle>
                  <CardDescription>Class-wise performance summary</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="text-center py-8 text-muted-foreground">
                    <p>No performance data available</p>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Finance Summary</CardTitle>
                  <CardDescription>Payment and fee status</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="text-center py-8 text-muted-foreground">
                    <p>No finance data available</p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Reports Tab */}
          <TabsContent value="reports" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Generate Reports</CardTitle>
                <CardDescription>Create comprehensive school reports</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="grid md:grid-cols-2 gap-3">
                  <Button className="bg-primary hover:bg-primary/90 gap-2" onClick={() => handleGenerateReport("Student Records")}>
                    <Download size={16} />
                    Student Records Report
                  </Button>
                  <Button className="bg-primary hover:bg-primary/90 gap-2" onClick={() => handleGenerateReport("Academic Performance")}>
                    <Download size={16} />
                    Academic Performance Report
                  </Button>
                  <Button className="bg-primary hover:bg-primary/90 gap-2" onClick={() => handleGenerateReport("Financial Summary")}>
                    <Download size={16} />
                    Financial Summary Report
                  </Button>
                  <Button className="bg-primary hover:bg-primary/90 gap-2" onClick={() => handleGenerateReport("Attendance Report")}>
                    <Download size={16} />
                    Attendance Report
                  </Button>
                  <Button className="bg-primary hover:bg-primary/90 gap-2" onClick={() => handleGenerateReport("Behaviour Report")}>
                    <Download size={16} />
                    Behaviour Report
                  </Button>
                  <Button className="bg-primary hover:bg-primary/90 gap-2" onClick={() => handleGenerateReport("Debtors List")}>
                    <Download size={16} />
                    Debtors List
                  </Button>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Analytics Tab */}
          <TabsContent value="analytics" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>School Analytics</CardTitle>
                <CardDescription>Performance metrics and trends</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-8 text-muted-foreground">
                  <p>Analytics dashboard coming soon</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Management Tab */}
          <TabsContent value="management" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>School Management</CardTitle>
                <CardDescription>Administrative functions</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="grid md:grid-cols-2 gap-3">
                  <Button className="bg-primary hover:bg-primary/90">Manage Classes</Button>
                  <Button className="bg-primary hover:bg-primary/90">Manage Staff</Button>
                  <Button className="bg-primary hover:bg-primary/90">Manage Subjects</Button>
                  <Button className="bg-primary hover:bg-primary/90">Fee Structures</Button>
                  <Button className="bg-primary hover:bg-primary/90">Academic Calendar</Button>
                  <Button className="bg-primary hover:bg-primary/90">System Settings</Button>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
