import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { admissionsApi, formatDocumentName } from "@/lib/admissionsApi";
import { CheckCircle2, Circle, Clock3, FileUp, Search } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

type TrackResult = Awaited<ReturnType<typeof admissionsApi.track>>;

export default function AdmissionTrackingPage() {
  const [token, setToken] = useState("");
  const [applicationNumber, setApplicationNumber] = useState("");
  const [dob, setDob] = useState("");
  const [result, setResult] = useState<TrackResult | null>(null);

  const track = async (event: React.FormEvent) => {
    event.preventDefault();
    try {
      const data = await admissionsApi.track(token ? { tracking_token: token.toUpperCase() } : { application_number: applicationNumber, date_of_birth: dob });
      setResult(data);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Tracking failed");
    }
  };

  const upload = async (documentType: string, file?: File) => {
    if (!result?.application.tracking_token || !file) return;
    try {
      await admissionsApi.upload(result.application.tracking_token, documentType, file);
      const fresh = await admissionsApi.track({ tracking_token: result.application.tracking_token });
      setResult(fresh);
      toast.success(`${formatDocumentName(documentType)} uploaded`);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Upload failed");
    }
  };

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container py-8">
        <div className="mb-6">
          <p className="text-sm font-medium uppercase tracking-wide text-amber-600">Admissions Tracking</p>
          <h1 className="text-3xl font-semibold tracking-normal text-emerald-950">Track application progress</h1>
        </div>

        <Card className="mb-6 rounded-lg border-emerald-900/10">
          <CardContent className="pt-6">
            <form onSubmit={track} className="grid gap-4 md:grid-cols-[1fr_auto_1fr_1fr_auto] md:items-end">
              <div className="space-y-2">
                <Label>Tracking Token</Label>
                <Input value={token} onChange={(e) => setToken(e.target.value.toUpperCase())} placeholder="DGI-8F4K2P9X" />
              </div>
              <p className="pb-3 text-center text-xs font-medium text-slate-400">OR</p>
              <div className="space-y-2">
                <Label>Application Number</Label>
                <Input value={applicationNumber} onChange={(e) => setApplicationNumber(e.target.value.toUpperCase())} placeholder="APP-2026-00125" />
              </div>
              <div className="space-y-2">
                <Label>Date of Birth</Label>
                <Input type="date" value={dob} onChange={(e) => setDob(e.target.value)} />
              </div>
              <Button className="gap-2 bg-emerald-800 hover:bg-emerald-900"><Search className="h-4 w-4" />Track</Button>
            </form>
          </CardContent>
        </Card>

        {result && (
          <div className="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
            <section className="space-y-6">
              <Card className="rounded-lg border-emerald-900/10">
                <CardHeader>
                  <CardTitle>Application Summary</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
                  <Summary label="Student" value={`${result.application.student_first_name} ${result.application.student_last_name}`} />
                  <Summary label="Application Number" value={result.application.application_number || "-"} />
                  <Summary label="Application Type" value={result.application.application_type === "transfer" ? "Transfer Student" : "New Form 1 Intake"} />
                  <Summary label="Current Status" value={String(result.application.status || "-").replaceAll("_", " ")} />
                  <Summary label="Submission Date" value={result.application.submitted_at || "Not submitted"} />
                </CardContent>
              </Card>

              <Card className="rounded-lg border-emerald-900/10">
                <CardHeader><CardTitle>Progress</CardTitle></CardHeader>
                <CardContent>
                  <div className="mb-2 flex justify-between text-sm"><span>{result.application.status}</span><span>{result.progress_percentage}%</span></div>
                  <div className="h-3 overflow-hidden rounded-full bg-emerald-100">
                    <div className="h-full rounded-full bg-emerald-800 transition-all" style={{ width: `${result.progress_percentage}%` }} />
                  </div>
                </CardContent>
              </Card>

              <Card className="rounded-lg border-emerald-900/10">
                <CardHeader><CardTitle>Timeline</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  {result.timeline.map((item) => (
                    <div key={item.key} className="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                      {item.state === "complete" ? <CheckCircle2 className="h-5 w-5 text-emerald-700" /> : item.state === "current" ? <Clock3 className="h-5 w-5 text-amber-600" /> : <Circle className="h-5 w-5 text-slate-300" />}
                      <span className="font-medium">{item.label}</span>
                    </div>
                  ))}
                </CardContent>
              </Card>
            </section>

            <aside className="space-y-6">
              {result.application.status === "accepted" && (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-emerald-950">
                  <p className="text-lg font-semibold">Congratulations</p>
                  <p className="mt-1 text-sm">Your application has been accepted. Visit admissions with fee confirmation and original documents to complete enrollment.</p>
                </div>
              )}
              {result.application.status === "rejected" && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-5 text-red-950">
                  <p className="text-lg font-semibold">Application not successful</p>
                  <p className="mt-1 text-sm">{result.application.remarks || "Please contact admissions for more information."}</p>
                </div>
              )}

              <Card className="rounded-lg border-emerald-900/10">
                <CardHeader><CardTitle>Document Checklist</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  {result.document_checklist.map((item) => (
                    <div key={item.document_type} className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                      <div>
                        <p className="font-medium">{formatDocumentName(item.document_type)}</p>
                        <Badge variant={item.uploaded ? "default" : "outline"} className={item.uploaded ? "bg-emerald-800" : ""}>{item.uploaded ? "Uploaded" : "Missing"}</Badge>
                      </div>
                      <label className="flex h-9 items-center gap-2 rounded-md border px-3 text-sm"><FileUp className="h-4 w-4" /><input className="hidden" type="file" onChange={(e) => upload(item.document_type, e.target.files?.[0])} />Upload</label>
                    </div>
                  ))}
                </CardContent>
              </Card>

              <Card className="rounded-lg border-emerald-900/10">
                <CardHeader><CardTitle>Notifications</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  {result.notifications.map((notice) => (
                    <div key={notice.id} className="rounded-lg border border-slate-200 p-3">
                      <p className="font-medium">{notice.title}</p>
                      <p className="mt-1 whitespace-pre-line text-sm text-slate-600">{notice.message}</p>
                    </div>
                  ))}
                </CardContent>
              </Card>
            </aside>
          </div>
        )}
      </div>
    </main>
  );
}

function Summary({ label, value }: { label: string; value: string }) {
  return <div className="rounded-lg border border-slate-200 p-4"><p className="text-xs uppercase text-slate-500">{label}</p><p className="mt-1 font-medium capitalize">{value}</p></div>;
}
