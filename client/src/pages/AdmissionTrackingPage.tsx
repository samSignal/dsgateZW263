import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { admissionsV2Api, AdmissionsV2Error, type CatalogAcademicYear, type CatalogCategory, type CatalogForm } from "@/lib/admissionsV2Api";
import { setAdmissionsSession } from "@/lib/admissionsV2Session";
import { requiredDocumentsFor } from "@/lib/admissionsV2Validation";
import { CheckCircle2, Circle, Clock3, FileUp, Search } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";

type TrackResult = Awaited<ReturnType<typeof admissionsV2Api.track>>;

export default function AdmissionTrackingPage() {
  const [token, setToken] = useState("");
  const [dob, setDob] = useState("");
  const [result, setResult] = useState<TrackResult | null>(null);
  const [catalogYears, setCatalogYears] = useState<CatalogAcademicYear[]>([]);
  const [catalogForms, setCatalogForms] = useState<CatalogForm[]>([]);
  const [catalogCategories, setCatalogCategories] = useState<CatalogCategory[]>([]);
  const [recovery, setRecovery] = useState({ academic_year_id: "", birth_certificate_number: "", guardian_email: "", guardian_phone: "" });

  const requiredDocs = useMemo(() => {
    if (!result) return [];
    return requiredDocumentsFor(result.application.application_type);
  }, [result]);

  useEffect(() => {
    (async () => {
      try {
        const [y, f, c] = await Promise.all([
          admissionsV2Api.catalogAcademicYears(),
          admissionsV2Api.catalogForms(),
          admissionsV2Api.catalogCategories(),
        ]);
        setCatalogYears(y.academic_years);
        setCatalogForms(f.forms);
        setCatalogCategories(c.categories);
      } catch {
      }
    })();
  }, []);

  const yearName = (id?: number) => catalogYears.find((y) => y.id === id)?.name ?? String(id ?? "");
  const formName = (id?: number) => catalogForms.find((f) => f.id === id)?.name ?? String(id ?? "");
  const categoryName = (id?: number) => catalogCategories.find((c) => c.id === id)?.name ?? String(id ?? "");

  const track = async (event: React.FormEvent) => {
    event.preventDefault();
    try {
      if (!token.trim() || !dob) {
        toast.error("Enter your token and the learner’s date of birth.");
        return;
      }
      const sess = await admissionsV2Api.sessionStart({ token: token.toUpperCase(), date_of_birth: dob });
      setAdmissionsSession({
        token: token.toUpperCase(),
        dateOfBirth: dob,
        applicationId: sess.application_id,
        applicationNumber: sess.application_number,
        sessionToken: sess.session_token,
        sessionExpiresAt: sess.expires_at,
        stepupUntil: sess.stepup_until ?? undefined,
      });
      const data = await admissionsV2Api.track({ token: token.toUpperCase(), date_of_birth: dob });
      setResult(data);
    } catch (error) {
      if (error instanceof AdmissionsV2Error) {
        toast.error(error.message);
      } else {
        toast.error(error instanceof Error ? error.message : "Tracking failed");
      }
    }
  };

  const upload = async (documentType: string, file?: File) => {
    if (!result || !file) return;
    try {
      await admissionsV2Api.uploadDocument({ token: token.toUpperCase(), date_of_birth: dob, document_type: documentType, document: file });
      const fresh = await admissionsV2Api.track({ token: token.toUpperCase(), date_of_birth: dob });
      setResult(fresh);
      toast.success("Document uploaded");
    } catch (error) {
      if (error instanceof AdmissionsV2Error) {
        if (error.code === "STEPUP_REQUIRED") {
          try {
            await admissionsV2Api.stepupRequestFor("replace_documents");
            toast.error("Additional verification is required. Check your email for a verification link.");
          } catch {
            toast.error("Additional verification is required. Please check your email or contact admissions.");
          }
        } else {
          toast.error(error.message);
        }
      }
      else toast.error(error instanceof Error ? error.message : "Upload failed");
    }
  };

  const requestRecovery = async () => {
    try {
      if (!recovery.academic_year_id || !recovery.birth_certificate_number || !recovery.guardian_email) {
        toast.error("Enter the intake academic year, birth certificate number, and guardian email.");
        return;
      }
      const res = await admissionsV2Api.recoveryRequest({
        academic_year_id: Number(recovery.academic_year_id),
        birth_certificate_number: recovery.birth_certificate_number,
        guardian_email: recovery.guardian_email,
      });
      toast.success(res.message);
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Recovery request failed");
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
            <form onSubmit={track} className="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
              <div className="space-y-2">
                <Label>Tracking Token</Label>
                <Input value={token} onChange={(e) => setToken(e.target.value.toUpperCase())} placeholder="DGI-8F4K2P9X" />
              </div>
              <div className="space-y-2">
                <Label>Learner date of birth</Label>
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
                  <Summary label="Application Type" value={result.application.application_type === "transfer" ? "Transfer" : "New intake"} />
                  <Summary label="Lifecycle" value={String(result.application.lifecycle_state || result.application.status || "-").replaceAll("_", " ")} />
                  <Summary label="Submission Date" value={result.application.submitted_at || "Not submitted"} />
                  <Summary label="Intake" value={`${formName(result.application.applying_form_id)} · ${categoryName(result.application.preferred_category_id)} · ${yearName(result.application.academic_year_id)}`.trim()} />
                </CardContent>
              </Card>

              <Card className="rounded-lg border-emerald-900/10">
                <CardHeader><CardTitle>Progress</CardTitle></CardHeader>
                <CardContent>
                  <div className="mb-2 flex justify-between text-sm"><span>{result.application.lifecycle_state ?? result.application.status}</span><span>{result.progress_percentage}%</span></div>
                  <div className="h-3 overflow-hidden rounded-full bg-emerald-100">
                    <div className="h-full rounded-full bg-emerald-800 transition-all" style={{ width: `${result.progress_percentage}%` }} />
                  </div>
                </CardContent>
              </Card>

              <Card className="rounded-lg border-emerald-900/10">
                <CardHeader><CardTitle>Timeline</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  {[
                    { key: "draft", label: "Draft" },
                    { key: "submitted", label: "Submitted" },
                    { key: "review", label: "Under Review" },
                    { key: "documents_required", label: "Documents Required" },
                    { key: "offer", label: "Offer" },
                  ].map((item, idx) => {
                    const current = String(result.application.lifecycle_state || result.application.status || "").toLowerCase();
                    const complete =
                      item.key === "draft" ? true :
                      item.key === "submitted" ? ["submitted", "under_review", "documents_required", "offer_pending", "offered"].includes(current) :
                      item.key === "review" ? ["under_review", "documents_required", "offer_pending", "offered"].includes(current) :
                      item.key === "documents_required" ? ["documents_required", "offer_pending", "offered"].includes(current) :
                      ["offer_pending", "offered"].includes(current);
                    const isCurrent = !complete && idx === 1 && current === "draft";
                    return (
                      <div key={item.key} className="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                        {complete ? <CheckCircle2 className="h-5 w-5 text-emerald-700" /> : isCurrent ? <Clock3 className="h-5 w-5 text-amber-600" /> : <Circle className="h-5 w-5 text-slate-300" />}
                        <span className="font-medium">{item.label}</span>
                      </div>
                    );
                  })}
                </CardContent>
              </Card>
            </section>

            <aside className="space-y-6">
              {String(result.application.lifecycle_state).toLowerCase() === "offered" && (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-emerald-950">
                  <p className="text-lg font-semibold">Congratulations</p>
                  <p className="mt-1 text-sm">Your application has been accepted. Visit admissions with fee confirmation and original documents to complete enrollment.</p>
                </div>
              )}
              {String(result.application.status).toLowerCase() === "rejected" && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-5 text-red-950">
                  <p className="text-lg font-semibold">Application not successful</p>
                  <p className="mt-1 text-sm">Please contact admissions for more information.</p>
                </div>
              )}

              <Card className="rounded-lg border-emerald-900/10">
                <CardHeader><CardTitle>Required documents</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  {requiredDocs.map((docType) => {
                    const uploaded = result.documents.some((d) => d.document_type === docType);
                    return (
                    <div key={docType} className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                      <div>
                        <p className="font-medium">{formatDocumentName(docType)}</p>
                        <Badge variant={uploaded ? "default" : "outline"} className={uploaded ? "bg-emerald-800" : ""}>{uploaded ? "Uploaded" : "Missing"}</Badge>
                      </div>
                      <label className="flex h-9 items-center gap-2 rounded-md border px-3 text-sm">
                        <FileUp className="h-4 w-4" />
                        <input className="hidden" type="file" onChange={(e) => upload(docType, e.target.files?.[0])} />
                        Upload
                      </label>
                    </div>
                  )})}
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

        <Card className="mt-8 rounded-lg border-emerald-900/10">
          <CardHeader><CardTitle>Recover access</CardTitle></CardHeader>
          <CardContent className="space-y-4 text-sm text-slate-700">
            <div>If you no longer have your token, you can request recovery instructions. For privacy, we will not confirm whether an application exists.</div>
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label>Intake academic year</Label>
                <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={recovery.academic_year_id} onChange={(e) => setRecovery((c) => ({ ...c, academic_year_id: e.target.value }))}>
                  <option value="">Select</option>
                  {catalogYears.map((y) => <option key={y.id} value={y.id}>{y.name}</option>)}
                </select>
              </div>
              <div className="space-y-2">
                <Label>Birth certificate number</Label>
                <Input value={recovery.birth_certificate_number} onChange={(e) => setRecovery((c) => ({ ...c, birth_certificate_number: e.target.value }))} />
              </div>
              <div className="space-y-2">
                <Label>Guardian email</Label>
                <Input type="email" value={recovery.guardian_email} onChange={(e) => setRecovery((c) => ({ ...c, guardian_email: e.target.value }))} />
              </div>
              <div className="space-y-2">
                <Label>Guardian phone (optional)</Label>
                <Input value={recovery.guardian_phone} onChange={(e) => setRecovery((c) => ({ ...c, guardian_phone: e.target.value }))} />
              </div>
            </div>
            <Button className="bg-emerald-800 hover:bg-emerald-900" onClick={requestRecovery} type="button">Request recovery</Button>
          </CardContent>
        </Card>
      </div>
    </main>
  );
}

function Summary({ label, value }: { label: string; value: string }) {
  return <div className="rounded-lg border border-slate-200 p-4"><p className="text-xs uppercase text-slate-500">{label}</p><p className="mt-1 font-medium capitalize">{value}</p></div>;
}

function formatDocumentName(value: string) {
  return value.replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
}
