import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { BookOpen, Calendar, TrendingUp, Bell, LogOut } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { streamNativeApi } from "@/lib/streamNativeApi";
import { toast } from "sonner";

export default function StudentPortal() {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState("overview");
  const [term, setTerm] = useState<any>(null);
  const [transcript, setTranscript] = useState<any>(null);
  const [progression, setProgression] = useState<any>(null);
  const [loading, setLoading] = useState(false);

  const summary = useMemo(() => {
    const student = term?.student ?? transcript?.student ?? null;
    const aggregate = term?.aggregate ?? null;
    const subjects = term?.subjects ?? [];
    return { student, aggregate, subjects };
  }, [term, transcript]);

  const refresh = async () => {
    setLoading(true);
    try {
      const [t, tr, p] = await Promise.all([
        streamNativeApi.myTermAuto(),
        streamNativeApi.transcriptMe(),
        streamNativeApi.progressionStatusMeAuto(),
      ]);
      setTerm(t);
      setTranscript(tr);
      setProgression(p);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load academic profile");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    refresh();
  }, []);

  return (
    <div className="min-h-screen bg-background">
      <div className="border-b border-border bg-card/50 backdrop-blur-sm sticky top-0 z-40">
        <div className="container flex justify-between items-center h-16">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Student Portal</h1>
            <p className="text-sm text-muted-foreground">Your Academic Dashboard</p>
          </div>
          <Button onClick={() => logout()} variant="outline" className="gap-2">
            <LogOut size={16} />
            Logout
          </Button>
        </div>
      </div>

      <div className="container py-8">
        {/* Quick Stats */}
        <div className="grid md:grid-cols-4 gap-4 mb-8">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Class</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">{summary.student?.class_display ?? summary.student?.stream_name ?? "-"}</p>
              <p className="text-xs text-muted-foreground mt-1">Current</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Average Grade</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">{summary.aggregate?.term_average ?? "-"}</p>
              <p className="text-xs text-muted-foreground mt-1">This term</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Attendance</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0%</p>
              <p className="text-xs text-muted-foreground mt-1">Present</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Subjects</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">{summary.subjects?.length ?? 0}</p>
              <p className="text-xs text-muted-foreground mt-1">Enrolled</p>
            </CardContent>
          </Card>
        </div>

        {/* Tabs */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="timetable">Timetable</TabsTrigger>
            <TabsTrigger value="results">Results</TabsTrigger>
            <TabsTrigger value="notices">Notices</TabsTrigger>
          </TabsList>

          {/* Overview Tab */}
          <TabsContent value="overview" className="space-y-4">
            <div className="grid md:grid-cols-2 gap-4">
              <Card>
                <CardHeader>
                  <CardTitle>My Profile</CardTitle>
                  <CardDescription>Personal information</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Name:</span>
                      <span className="font-medium">{user?.name || "Not set"}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Email:</span>
                      <span className="font-medium">{user?.email || "Not set"}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Admission #:</span>
                      <span className="font-medium">{summary.student?.admission_number ?? "-"}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Cumulative GPA:</span>
                      <span className="font-medium">{transcript?.cumulative_gpa ?? "-"}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Academic Progress</CardTitle>
                  <CardDescription>Your performance summary</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Term Average:</span>
                      <span className="font-medium">{summary.aggregate?.term_average ?? "-"}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">GPA:</span>
                      <span className="font-medium">{summary.aggregate?.gpa ?? "-"}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Progression:</span>
                      <span className="font-medium">{progression?.decision?.decision ?? "-"}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Withheld:</span>
                      <span className="font-medium">{term?.is_withheld ? "Yes" : "No"}</span>
                    </div>
                    <div className="pt-2 flex gap-2">
                      <Button variant="outline" onClick={refresh} disabled={loading}>
                        Refresh
                      </Button>
                      <Button
                        onClick={() => {
                          window.location.href = "/student/academic-profile";
                        }}
                        disabled={loading}
                      >
                        Open Profile
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Timetable Tab */}
          <TabsContent value="timetable" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Class Timetable</CardTitle>
                <CardDescription>Your weekly schedule</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-8 text-muted-foreground">
                  <p>No timetable available</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Results Tab */}
          <TabsContent value="results" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>My Results</CardTitle>
                <CardDescription>Academic marks and grades</CardDescription>
              </CardHeader>
              <CardContent>
                {term?.is_withheld ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <p>Your results are currently withheld pending fee clearance.</p>
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b">
                          <th className="text-left py-2 pr-3">Subject</th>
                          <th className="text-left py-2 pr-3">Average</th>
                          <th className="text-left py-2 pr-3">Grade</th>
                          <th className="text-left py-2">GPA</th>
                        </tr>
                      </thead>
                      <tbody>
                        {(term?.subjects ?? []).map((s: any) => (
                          <tr key={s.subject_id} className="border-b last:border-b-0">
                            <td className="py-2 pr-3">{s.subject_name ?? "-"}</td>
                            <td className="py-2 pr-3">{s.subject_average ?? "-"}</td>
                            <td className="py-2 pr-3">{s.grade ?? "-"}</td>
                            <td className="py-2">{s.gpa_points ?? "-"}</td>
                          </tr>
                        ))}
                        {(term?.subjects ?? []).length === 0 && (
                          <tr>
                            <td colSpan={4} className="py-8 text-center text-muted-foreground">
                              No results available
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Notices Tab */}
          <TabsContent value="notices" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>School Notices</CardTitle>
                <CardDescription>Important announcements</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-8 text-muted-foreground">
                  <p>No notices at this time</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
