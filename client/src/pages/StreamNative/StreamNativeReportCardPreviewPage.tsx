import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { streamNativeApi } from "@/lib/streamNativeApi";
import { useMemo, useState } from "react";
import { useRoute } from "wouter";
import { toast } from "sonner";

export default function StreamNativeReportCardPreviewPage() {
  const [, params] = useRoute("/admin/report-card-native/:id");
  const studentId = useMemo(() => {
    const raw = params?.id;
    if (!raw) return null;
    const id = parseInt(raw, 10);
    return Number.isFinite(id) ? id : null;
  }, [params?.id]);

  const [academicYearId, setAcademicYearId] = useState("1");
  const [termId, setTermId] = useState("1");
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(false);

  const load = async () => {
    if (!studentId) {
      toast.error("Missing student id");
      return;
    }
    const ay = parseInt(academicYearId, 10);
    const t = parseInt(termId, 10);
    if (!ay || !t) {
      toast.error("Enter academic_year_id and term_id");
      return;
    }
    setLoading(true);
    try {
      const res = await streamNativeApi.reportCardPreviewStudent(studentId, { academic_year_id: ay, term_id: t });
      setData(res);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load report card preview");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-background p-6 space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Stream-Native Report Card Preview</CardTitle>
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
          <div className="flex items-end">
            <Button onClick={load} disabled={loading}>
              Load Preview
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Summary</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-2 text-sm">
          <div className="flex justify-between"><span className="text-muted-foreground">Student</span><span className="font-medium">{data?.student ? `${data.student.first_name ?? ""} ${data.student.last_name ?? ""}`.trim() : "-"}</span></div>
          <div className="flex justify-between"><span className="text-muted-foreground">Stream</span><span className="font-medium">{data?.student?.stream_name ?? "-"}</span></div>
          <div className="flex justify-between"><span className="text-muted-foreground">Form</span><span className="font-medium">{data?.student?.form_name ?? "-"}</span></div>
          <div className="flex justify-between"><span className="text-muted-foreground">Average</span><span className="font-medium">{data?.aggregate?.term_average ?? "-"}</span></div>
          <div className="flex justify-between"><span className="text-muted-foreground">GPA</span><span className="font-medium">{data?.aggregate?.gpa ?? "-"}</span></div>
          <div className="flex justify-between"><span className="text-muted-foreground">Stream Rank</span><span className="font-medium">{data?.ranking?.stream_rank ?? "-"}</span></div>
          <div className="flex justify-between"><span className="text-muted-foreground">Stream Total</span><span className="font-medium">{data?.ranking?.stream_total ?? "-"}</span></div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Subjects</CardTitle>
        </CardHeader>
        <CardContent>
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
                {(data?.subjects ?? []).map((s: any) => (
                  <tr key={s.subject_id} className="border-b last:border-b-0">
                    <td className="py-2 pr-3">{s.subject_name ?? "-"}</td>
                    <td className="py-2 pr-3">{s.subject_average ?? "-"}</td>
                    <td className="py-2 pr-3">{s.grade ?? "-"}</td>
                    <td className="py-2">{s.gpa_points ?? "-"}</td>
                  </tr>
                ))}
                {(data?.subjects ?? []).length === 0 && (
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
    </div>
  );
}

