import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import { admissionsApi, AdmissionApplication, AdmissionDocument, AdmissionNotification, formatDocumentName } from "@/lib/admissionsApi";
import { Check, FileWarning, GraduationCap, X } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { useRoute } from "wouter";

export default function ReviewApplicationPage() {
  const [, params] = useRoute("/admissions-office/applications/:id");
  const id = params?.id || "";
  const [application, setApplication] = useState<AdmissionApplication | null>(null);
  const [documents, setDocuments] = useState<AdmissionDocument[]>([]);
  const [notifications, setNotifications] = useState<AdmissionNotification[]>([]);
  const [remarks, setRemarks] = useState("");

  const load = async () => {
    if (!id) return;
    try {
      const data = await admissionsApi.review(id);
      setApplication(data.application);
      setDocuments(data.documents);
      setNotifications(data.notifications);
      setRemarks(data.application.remarks || "");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Could not load review");
    }
  };

  useEffect(() => {
    load();
  }, [id]);

  const act = async (action: "under_review" | "documents" | "accept" | "reject" | "enroll") => {
    try {
      if (action === "under_review") await admissionsApi.updateStatus(id, "under_review", remarks);
      if (action === "documents") await admissionsApi.requestDocuments(id, ["birth_certificate", "passport_photo"], remarks || "Please upload the missing required documents.");
      if (action === "accept") await admissionsApi.accept(id, remarks);
      if (action === "reject") await admissionsApi.reject(id, remarks || "Application did not meet admissions requirements.");
      if (action === "enroll") await admissionsApi.enroll(id, remarks);
      toast.success("Application updated");
      await load();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Action failed");
    }
  };

  if (!application) return <main className="container py-8">Loading application...</main>;

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container py-8">
        <div className="mb-6">
          <p className="text-sm font-medium uppercase tracking-wide text-amber-600">Review</p>
          <h1 className="text-3xl font-semibold tracking-normal text-emerald-950">{application.application_number}</h1>
        </div>

        <div className="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
          <section className="space-y-6">
            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Applicant Details</CardTitle></CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2">
                <Info label="Student" value={`${application.student_first_name} ${application.student_last_name}`} />
                <Info label="Status" value={application.status || "-"} />
                <Info label="Application Type" value={application.application_type} />
                <Info label="Date of Birth" value={application.date_of_birth || "-"} />
                <Info label="Birth Certificate" value={application.birth_certificate_number || "-"} />
                <Info label="Student National ID" value={application.student_national_id || "Optional, not supplied"} />
                <Info label="Guardian" value={application.guardian_name || "-"} />
                <Info label="Guardian Email" value={application.guardian_email || "-"} />
                <Info label="Guardian Phone" value={application.guardian_phone || "-"} />
                <Info label="Emergency Phone" value={application.emergency_phone || "-"} />
              </CardContent>
            </Card>

            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Documents</CardTitle></CardHeader>
              <CardContent className="grid gap-3 md:grid-cols-2">
                {documents.map((doc) => (
                  <div key={doc.id} className="rounded-lg border border-slate-200 p-4">
                    <p className="font-medium">{formatDocumentName(doc.document_type)}</p>
                    <p className="mt-1 text-sm text-slate-500">{doc.file_name}</p>
                  </div>
                ))}
                {documents.length === 0 && <p className="text-sm text-slate-500">No documents uploaded yet.</p>}
              </CardContent>
            </Card>
          </section>

          <aside className="space-y-6">
            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Decision Actions</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <Textarea placeholder="Remarks, interview notes, document request details" value={remarks} onChange={(e) => setRemarks(e.target.value)} />
                <div className="grid gap-2">
                  <Button variant="outline" className="gap-2" onClick={() => act("under_review")}><FileWarning className="h-4 w-4" />Mark under review</Button>
                  <Button variant="outline" className="gap-2" onClick={() => act("documents")}><FileWarning className="h-4 w-4" />Request documents</Button>
                  <Button className="gap-2 bg-emerald-800 hover:bg-emerald-900" onClick={() => act("accept")}><Check className="h-4 w-4" />Accept and create accounts</Button>
                  <Button className="gap-2 bg-emerald-700 hover:bg-emerald-800" onClick={() => act("enroll")}><GraduationCap className="h-4 w-4" />Complete enrollment</Button>
                  <Button variant="destructive" className="gap-2" onClick={() => act("reject")}><X className="h-4 w-4" />Reject</Button>
                </div>
              </CardContent>
            </Card>

            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Latest Notifications</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                {notifications.slice(0, 5).map((notice) => (
                  <div key={notice.id} className="rounded-lg border border-slate-200 p-3">
                    <p className="font-medium">{notice.title}</p>
                    <p className="mt-1 whitespace-pre-line text-sm text-slate-600">{notice.message}</p>
                  </div>
                ))}
              </CardContent>
            </Card>
          </aside>
        </div>
      </div>
    </main>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  return <div className="rounded-lg border border-slate-200 p-4"><p className="text-xs uppercase text-slate-500">{label}</p><p className="mt-1 font-medium capitalize">{value.replaceAll("_", " ")}</p></div>;
}
