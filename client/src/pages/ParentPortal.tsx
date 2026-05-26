import { useAuth } from "@/_core/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { AlertCircle, TrendingUp, BookOpen, Users, LogOut } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { streamNativeApi } from "@/lib/streamNativeApi";
import { toast } from "sonner";

export default function ParentPortal() {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState("overview");
  const [children, setChildren] = useState<any[]>([]);
  const [selectedChildId, setSelectedChildId] = useState<string>("");
  const [childTerm, setChildTerm] = useState<any>(null);
  const [loading, setLoading] = useState(false);

  const selectedChild = useMemo(() => {
    const id = selectedChildId ? parseInt(selectedChildId, 10) : null;
    if (!id) return null;
    return children.find((c) => c.id === id) ?? null;
  }, [children, selectedChildId]);

  const loadChildren = async () => {
    setLoading(true);
    try {
      const res = await streamNativeApi.parentChildren();
      setChildren(res.children ?? []);
      if (!selectedChildId && (res.children?.length ?? 0) > 0) {
        setSelectedChildId(String(res.children[0].id));
      }
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load children");
    } finally {
      setLoading(false);
    }
  };

  const loadSelectedChildTerm = async () => {
    const id = selectedChildId ? parseInt(selectedChildId, 10) : null;
    if (!id) {
      setChildTerm(null);
      return;
    }
    setLoading(true);
    try {
      const res = await streamNativeApi.parentChildTerm(id);
      setChildTerm(res);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load child results");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadChildren();
  }, []);

  useEffect(() => {
    loadSelectedChildTerm();
  }, [selectedChildId]);

  return (
    <div className="min-h-screen bg-background">
      <div className="border-b border-border bg-card/50 backdrop-blur-sm sticky top-0 z-40">
        <div className="container flex justify-between items-center h-16">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Parent Portal</h1>
            <p className="text-sm text-muted-foreground">Monitor your child's progress</p>
          </div>
          <Button onClick={() => logout()} variant="outline" className="gap-2">
            <LogOut size={16} />
            Logout
          </Button>
        </div>
      </div>

      <div className="container py-8">
        <Card className="mb-6">
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium text-muted-foreground">Selected Child</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col md:flex-row md:items-center gap-3">
            <select
              className="h-10 w-full md:w-96 rounded-md border border-input bg-background px-3 text-sm"
              value={selectedChildId}
              onChange={(e) => setSelectedChildId(e.target.value)}
            >
              {children.map((c) => (
                <option key={c.id} value={String(c.id)}>
                  {(c.name ?? `${c.first_name ?? ""} ${c.last_name ?? ""}`.trim()) || `Student #${c.id}`}
                </option>
              ))}
              {children.length === 0 && <option value="">No children linked</option>}
            </select>
            <div className="text-sm text-muted-foreground flex-1">
              {selectedChild ? (
                <span>
                  {selectedChild.stream_name ?? "-"} • {selectedChild.form_name ?? "-"} • {selectedChild.category_name ?? "-"}
                </span>
              ) : (
                <span>No child selected</span>
              )}
            </div>
            <Button variant="outline" onClick={loadChildren} disabled={loading}>
              Refresh
            </Button>
          </CardContent>
        </Card>

        {/* Child Overview */}
        <div className="grid md:grid-cols-4 gap-4 mb-8">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Outstanding Fees</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">ZWL 0</p>
              <p className="text-xs text-muted-foreground mt-1">Amount due</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Attendance</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">0%</p>
              <p className="text-xs text-muted-foreground mt-1">This term</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Academic Grade</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">{childTerm?.aggregate?.term_average ?? "-"}</p>
              <p className="text-xs text-muted-foreground mt-1">Average</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Behaviour</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">Good</p>
              <p className="text-xs text-muted-foreground mt-1">Status</p>
            </CardContent>
          </Card>
        </div>

        {/* Tabs */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="fees">Fees</TabsTrigger>
            <TabsTrigger value="results">Results</TabsTrigger>
            <TabsTrigger value="notices">Notices</TabsTrigger>
          </TabsList>

          {/* Overview Tab */}
          <TabsContent value="overview" className="space-y-4">
            <div className="grid md:grid-cols-2 gap-4">
              <Card>
                <CardHeader>
                  <CardTitle>Attendance Summary</CardTitle>
                  <CardDescription>This term attendance record</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="text-center py-8 text-muted-foreground">
                    <p>No attendance data available</p>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Recent Notices</CardTitle>
                  <CardDescription>Latest school announcements</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="text-center py-8 text-muted-foreground">
                    <p>No notices available</p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Fees Tab */}
          <TabsContent value="fees" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Fee Statement</CardTitle>
                <CardDescription>Payment history and outstanding balance</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-8 text-muted-foreground">
                  <p>No fee data available</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Results Tab */}
          <TabsContent value="results" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Academic Results</CardTitle>
                <CardDescription>Marks and performance</CardDescription>
              </CardHeader>
              <CardContent>
                {!selectedChild ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <p>No child selected</p>
                  </div>
                ) : childTerm?.is_withheld ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <p>Results are currently withheld pending fee clearance.</p>
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
                        {(childTerm?.subjects ?? []).map((s: any) => (
                          <tr key={s.subject_id} className="border-b last:border-b-0">
                            <td className="py-2 pr-3">{s.subject_name ?? "-"}</td>
                            <td className="py-2 pr-3">{s.subject_average ?? "-"}</td>
                            <td className="py-2 pr-3">{s.grade ?? "-"}</td>
                            <td className="py-2">{s.gpa_points ?? "-"}</td>
                          </tr>
                        ))}
                        {(childTerm?.subjects ?? []).length === 0 && (
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
                <CardDescription>Important announcements and updates</CardDescription>
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
