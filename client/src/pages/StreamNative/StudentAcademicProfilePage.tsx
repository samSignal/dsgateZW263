import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { streamNativeApi } from "@/lib/streamNativeApi";
import { useEffect, useMemo, useState } from "react";
import { useRoute } from "wouter";
import { toast } from "sonner";

type Mode = "admin" | "student";

export default function StudentAcademicProfilePage({ mode }: { mode: Mode }) {
  const [, params] = useRoute("/admin/students/:id/academic");
  const [academicYearId, setAcademicYearId] = useState("1");
  const [termId, setTermId] = useState("1");
  const [activeTab, setActiveTab] = useState("profile");

  const studentId = useMemo(() => {
    if (mode === "student") return null;
    const raw = params?.id;
    if (!raw) return null;
    const id = parseInt(raw, 10);
    return Number.isFinite(id) ? id : null;
  }, [mode, params?.id]);

  const [profile, setProfile] = useState<any>(null);
  const [term, setTerm] = useState<any>(null);
  const [loading, setLoading] = useState(false);

  const parsed = useMemo(() => {
    const ay = parseInt(academicYearId, 10);
    const termIdNum = parseInt(termId, 10);
    return { ay, termIdNum };
  }, [academicYearId, termId]);

  const loadTranscript = async () => {
    setLoading(true);
    try {
      const res =
        mode === "student"
          ? await streamNativeApi.transcriptMe()
          : studentId
            ? await streamNativeApi.transcriptStudent(studentId)
            : null;
      if (!res) throw new Error("Student ID not found");
      setProfile(res);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load transcript");
    } finally {
      setLoading(false);
    }
  };

  const loadTerm = async () => {
    if (!parsed.ay || !parsed.termIdNum) {
      toast.error("Enter academic_year_id and term_id");
      return;
    }
    setLoading(true);
    try {
      const res =
        mode === "student"
          ? await streamNativeApi.myTerm({ academic_year_id: parsed.ay, term_id: parsed.termIdNum })
          : studentId
            ? await streamNativeApi.studentTerm(studentId, { academic_year_id: parsed.ay, term_id: parsed.termIdNum })
            : null;
      if (!res) throw new Error("Student ID not found");
      setTerm(res);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load term results");
    } finally {
      setLoading(false);
    }
  };

  const loadProgression = async () => {
    if (!parsed.ay || !parsed.termIdNum) {
      toast.error("Enter academic_year_id and term_id");
      return;
    }
    setLoading(true);
    try {
      const res =
        mode === "student"
          ? await streamNativeApi.progressionStatusMe({ academic_year_id: parsed.ay, term_id: parsed.termIdNum })
          : studentId
            ? await streamNativeApi.progressionStatusStudent(studentId, { academic_year_id: parsed.ay, term_id: parsed.termIdNum })
            : null;
      if (!res) throw new Error("Student ID not found");
      toast.success(`Progression: ${res.decision?.decision ?? "-"}`);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load progression");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadTranscript();
  }, [mode, studentId]);

  return (
    <div className="min-h-screen bg-background p-6 space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Student Academic Profile (Stream-Native)</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-4">
          <div className="space-y-2">
            <div className="text-xs text-muted-foreground">Academic Year ID</div>
            <Input value={academicYearId} onChange={(e) => setAcademicYearId(e.target.value)} placeholder="1" />
          </div>
          <div className="space-y-2">
            <div className="text-xs text-muted-foreground">Term ID</div>
            <Input value={termId} onChange={(e) => setTermId(e.target.value)} placeholder="1" />
          </div>
          <div className="flex items-end gap-2">
            <Button onClick={loadTerm} disabled={loading}>
              Load Term Results
            </Button>
            <Button onClick={loadProgression} variant="outline" disabled={loading}>
              Progression
            </Button>
          </div>
        </CardContent>
      </Card>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid grid-cols-3 w-full max-w-xl">
          <TabsTrigger value="profile">Profile</TabsTrigger>
          <TabsTrigger value="term">Term</TabsTrigger>
          <TabsTrigger value="history">History</TabsTrigger>
        </TabsList>

        <TabsContent value="profile">
          <Card>
            <CardHeader>
              <CardTitle>Academic Context</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-2 text-sm">
              <div className="flex justify-between"><span className="text-muted-foreground">Student</span><span className="font-medium">{profile?.student ? `${profile.student.first_name ?? ""} ${profile.student.last_name ?? ""}`.trim() : "-"}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Student #</span><span className="font-medium">{profile?.student?.student_number ?? "-"}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Stream</span><span className="font-medium">{profile?.student?.stream_name ?? "-"}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Form</span><span className="font-medium">{profile?.student?.form_name ?? "-"}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Category</span><span className="font-medium">{profile?.student?.category_name ?? "-"}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Legacy Class</span><span className="font-medium">{profile?.student?.class_name ?? "-"}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Cumulative GPA</span><span className="font-medium">{profile?.cumulative_gpa ?? "-"}</span></div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="term">
          <Card>
            <CardHeader>
              <CardTitle>Term Summary</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-2 text-sm">
                <div className="flex justify-between"><span className="text-muted-foreground">Average</span><span className="font-medium">{term?.aggregate?.term_average ?? "-"}</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">GPA</span><span className="font-medium">{term?.aggregate?.gpa ?? "-"}</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">Withheld</span><span className="font-medium">{term?.aggregate?.is_withheld ? "Yes" : "No"}</span></div>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-2 pr-3">Subject</th>
                      <th className="text-left py-2 pr-3">Avg</th>
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
                          No subjects
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="history">
          <Card>
            <CardHeader>
              <CardTitle>Year Aggregates</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-2 pr-3">Academic Year</th>
                      <th className="text-left py-2 pr-3">Average</th>
                      <th className="text-left py-2 pr-3">GPA</th>
                      <th className="text-left py-2">Withheld</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(profile?.year_aggregates ?? []).map((y: any) => (
                      <tr key={y.academic_year_id} className="border-b last:border-b-0">
                        <td className="py-2 pr-3">{y.academic_year_name ?? y.academic_year_id}</td>
                        <td className="py-2 pr-3">{y.year_average ?? "-"}</td>
                        <td className="py-2 pr-3">{y.gpa ?? "-"}</td>
                        <td className="py-2">{y.is_withheld ? "Yes" : "No"}</td>
                      </tr>
                    ))}
                    {(profile?.year_aggregates ?? []).length === 0 && (
                      <tr>
                        <td colSpan={4} className="py-8 text-center text-muted-foreground">
                          No year aggregates
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

