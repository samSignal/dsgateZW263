import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { streamNativeApi, StreamNativeRankRow } from "@/lib/streamNativeApi";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";

function StudentName({ row }: { row: StreamNativeRankRow }) {
  const name = row.student_name ?? `${row.first_name ?? ""} ${row.last_name ?? ""}`.trim();
  return <span className="font-medium">{name || "-"}</span>;
}

function RankTable({ rows }: { rows: StreamNativeRankRow[] }) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b">
            <th className="text-left py-2 pr-3">Rank</th>
            <th className="text-left py-2 pr-3">Student</th>
            <th className="text-left py-2 pr-3">Student #</th>
            <th className="text-left py-2 pr-3">Average</th>
            <th className="text-left py-2 pr-3">GPA</th>
            <th className="text-left py-2 pr-3">Class (Legacy)</th>
            <th className="text-left py-2">Stream</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((r) => (
            <tr key={r.student_id} className="border-b last:border-b-0">
              <td className="py-2 pr-3">{r.rank ?? "-"}</td>
              <td className="py-2 pr-3">
                <StudentName row={r} />
              </td>
              <td className="py-2 pr-3">{r.student_number ?? "-"}</td>
              <td className="py-2 pr-3">{typeof r.score === "number" ? r.score.toFixed(2) : "-"}</td>
              <td className="py-2 pr-3">{r.gpa ?? "-"}</td>
              <td className="py-2 pr-3">{r.class_name ?? "-"}</td>
              <td className="py-2">{r.stream_name ?? "-"}</td>
            </tr>
          ))}
          {rows.length === 0 && (
            <tr>
              <td colSpan={7} className="py-8 text-center text-muted-foreground">
                No data
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}

export default function StreamNativeResultsDashboardPage() {
  const [academicYearId, setAcademicYearId] = useState("1");
  const [termId, setTermId] = useState("1");
  const [streamId, setStreamId] = useState("");
  const [activeTab, setActiveTab] = useState("rankings");

  const [rankRows, setRankRows] = useState<StreamNativeRankRow[]>([]);
  const [topRows, setTopRows] = useState<StreamNativeRankRow[]>([]);
  const [riskRows, setRiskRows] = useState<StreamNativeRankRow[]>([]);
  const [loading, setLoading] = useState(false);

  const parsed = useMemo(() => {
    const ay = parseInt(academicYearId, 10);
    const term = parseInt(termId, 10);
    const stream = streamId ? parseInt(streamId, 10) : undefined;
    return { ay, term, stream };
  }, [academicYearId, termId, streamId]);

  const loadRankings = async () => {
    if (!parsed.ay || !parsed.term || !parsed.stream) {
      toast.error("Enter academic_year_id, term_id and stream_id");
      return;
    }
    setLoading(true);
    try {
      const res = await streamNativeApi.rankings({
        academic_year_id: parsed.ay,
        term_id: parsed.term,
        type: "stream",
        id: parsed.stream,
        page: 1,
        per_page: 100,
      });
      setRankRows(res.data);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load rankings");
    } finally {
      setLoading(false);
    }
  };

  const loadTop = async () => {
    if (!parsed.ay || !parsed.term) {
      toast.error("Enter academic_year_id and term_id");
      return;
    }
    setLoading(true);
    try {
      const res = await streamNativeApi.topPerformers({
        academic_year_id: parsed.ay,
        term_id: parsed.term,
        stream_id: parsed.stream,
        limit: 20,
      });
      setTopRows(res.data);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load top performers");
    } finally {
      setLoading(false);
    }
  };

  const loadAtRisk = async () => {
    if (!parsed.ay || !parsed.term) {
      toast.error("Enter academic_year_id and term_id");
      return;
    }
    setLoading(true);
    try {
      const res = await streamNativeApi.atRisk({
        academic_year_id: parsed.ay,
        term_id: parsed.term,
        stream_id: parsed.stream,
        max_average: 50,
        limit: 50,
      });
      setRiskRows(res.data);
    } catch (e: any) {
      toast.error(e?.message ?? "Failed to load at-risk students");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (activeTab === "rankings") setRankRows([]);
    if (activeTab === "top") setTopRows([]);
    if (activeTab === "risk") setRiskRows([]);
  }, [activeTab]);

  return (
    <div className="min-h-screen bg-background p-6 space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Stream-Native Results Dashboard</CardTitle>
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
          <div className="space-y-2">
            <div className="text-xs text-muted-foreground">Stream ID (optional for Top/At-Risk)</div>
            <Input value={streamId} onChange={(e) => setStreamId(e.target.value)} placeholder="1" />
          </div>
          <div className="flex items-end gap-2">
            <Button onClick={loadRankings} disabled={loading}>
              Load Rankings
            </Button>
            <Button onClick={loadTop} variant="outline" disabled={loading}>
              Top
            </Button>
            <Button onClick={loadAtRisk} variant="outline" disabled={loading}>
              At-Risk
            </Button>
          </div>
        </CardContent>
      </Card>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid grid-cols-3 w-full max-w-xl">
          <TabsTrigger value="rankings">Rankings</TabsTrigger>
          <TabsTrigger value="top">Top Performers</TabsTrigger>
          <TabsTrigger value="risk">At-Risk</TabsTrigger>
        </TabsList>

        <TabsContent value="rankings">
          <Card>
            <CardHeader>
              <CardTitle>Stream Rankings</CardTitle>
            </CardHeader>
            <CardContent>
              <RankTable rows={rankRows} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="top">
          <Card>
            <CardHeader>
              <CardTitle>Top Performers</CardTitle>
            </CardHeader>
            <CardContent>
              <RankTable rows={topRows.map((r, idx) => ({ ...r, rank: idx + 1, score: (r as any).term_average ?? r.score }))} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="risk">
          <Card>
            <CardHeader>
              <CardTitle>At-Risk Students</CardTitle>
            </CardHeader>
            <CardContent>
              <RankTable rows={riskRows.map((r, idx) => ({ ...r, rank: idx + 1, score: (r as any).term_average ?? r.score }))} />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

