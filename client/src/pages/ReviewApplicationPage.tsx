import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { admissionsOfficeV2Api, AdmissionsOfficeV2Error } from "@/lib/admissionsOfficeV2Api";
import { admissionsV2Api, type CatalogAcademicYear, type CatalogCategory, type CatalogForm } from "@/lib/admissionsV2Api";
import { Check, FileWarning, ShieldAlert, UserCheck, X } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { useRoute } from "wouter";

export default function ReviewApplicationPage() {
  const [, params] = useRoute("/admissions-office/applications/:id");
  const id = params?.id || "";
  const [busy, setBusy] = useState(false);
  const [workspace, setWorkspace] = useState<any | null>(null);
  const [reviewers, setReviewers] = useState<Array<{ id: number; name: string }>>([]);
  const [templates, setTemplates] = useState<Array<{ key: string; title: string; message: string }>>([]);

  const [catalogYears, setCatalogYears] = useState<CatalogAcademicYear[]>([]);
  const [catalogForms, setCatalogForms] = useState<CatalogForm[]>([]);
  const [catalogCategories, setCatalogCategories] = useState<CatalogCategory[]>([]);

  const [transition, setTransition] = useState<{ to_state: string; reason: string }>({ to_state: "UNDER_REVIEW", reason: "" });
  const [transitionOps, setTransitionOps] = useState<{
    notify_applicant: boolean;
    notify_template_key: string;
    notify_title: string;
    notify_message: string;
    override: boolean;
    force: boolean;
  }>({ notify_applicant: false, notify_template_key: "", notify_title: "", notify_message: "", override: false, force: false });
  const [internalNote, setInternalNote] = useState("");
  const [applicantMessage, setApplicantMessage] = useState<{ title: string; message: string; template_key: string }>({
    title: "",
    message: "",
    template_key: "",
  });
  const [docAction, setDocAction] = useState<{ documentId: number | null; reason: string; notes: string }>({ documentId: null, reason: "", notes: "" });
  const [duplicateAction, setDuplicateAction] = useState<{ relatedId: string; status: "flagged" | "merged" | "invalid"; reason: string }>({
    relatedId: "",
    status: "flagged",
    reason: "",
  });
  const [duplicateCompare, setDuplicateCompare] = useState<any | null>(null);
  const [assign, setAssign] = useState<{ assignee_user_id: string; reason: string }>({ assignee_user_id: "", reason: "" });

  const load = async () => {
    if (!id) return;
    try {
      setBusy(true);
      const [y, f, c] = await Promise.all([admissionsV2Api.catalogAcademicYears(), admissionsV2Api.catalogForms(), admissionsV2Api.catalogCategories()]);
      setCatalogYears(y.academic_years);
      setCatalogForms(f.forms);
      setCatalogCategories(c.categories);
      const w = await admissionsOfficeV2Api.review(Number(id));
      setWorkspace(w);
      if (w?.capabilities?.can_assign) {
        try {
          const r = await admissionsOfficeV2Api.reviewers();
          setReviewers(r.items.map((u) => ({ id: u.id, name: u.name })));
        } catch {
          setReviewers([]);
        }
      } else {
        setReviewers([]);
      }
      if (w?.capabilities?.can_message) {
        try {
          const t = await admissionsOfficeV2Api.messageTemplates();
          setTemplates(t.items);
        } catch {
          setTemplates([]);
        }
      } else {
        setTemplates([]);
      }
    } catch (error) {
      if (error instanceof AdmissionsOfficeV2Error) toast.error(error.message);
      else toast.error(error instanceof Error ? error.message : "Could not load review");
    } finally {
      setBusy(false);
    }
  };

  useEffect(() => {
    load();
  }, [id]);

  const yearName = (id?: number | null) => catalogYears.find((y) => y.id === id)?.name ?? String(id ?? "");
  const formName = (id?: number | null) => catalogForms.find((f) => f.id === id)?.name ?? String(id ?? "");
  const categoryName = (id?: number | null) => catalogCategories.find((c) => c.id === id)?.name ?? String(id ?? "");

  const app = workspace?.application;
  const checklist = workspace?.document_checklist ?? [];
  const notes = workspace?.notes ?? [];
  const audit = workspace?.audit_events ?? [];
  const dupes = workspace?.duplicate_candidates ?? [];
  const caps = workspace?.capabilities ?? {};
  const assigned = workspace?.assigned_reviewer;

  const canReviewDocs = Boolean(caps.can_review_docs);
  const canClaim = Boolean(caps.can_claim);
  const canTransition = Boolean(caps.can_transition);
  const canMessage = Boolean(caps.can_message);
  const canAddNotes = Boolean(caps.can_add_notes);
  const canManageDuplicates = Boolean(caps.can_manage_duplicates);
  const canAssign = Boolean(caps.can_assign);
  const canOverride = Boolean(caps.can_override);

  const stateLabel = useMemo(() => String(app?.lifecycle_state || app?.status || "-").replaceAll("_", " "), [app]);

  const doClaim = async () => {
    try {
      await admissionsOfficeV2Api.claim(Number(id));
      toast.success("Review claimed");
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Claim failed");
    }
  };

  const doRelease = async () => {
    try {
      await admissionsOfficeV2Api.release(Number(id), { reason: "Released by reviewer" });
      toast.success("Review released");
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Release failed");
    }
  };

  const doTransition = async () => {
    try {
      if (!transition.reason.trim()) {
        toast.error("Reason is required.");
        return;
      }
      await admissionsOfficeV2Api.transitionWithNotify(Number(id), {
        ...transition,
        override: canOverride ? transitionOps.override : undefined,
        force: canOverride ? transitionOps.force : undefined,
        notify_applicant: canMessage ? transitionOps.notify_applicant : undefined,
        notify_template_key: canMessage && transitionOps.notify_template_key ? transitionOps.notify_template_key : undefined,
        notify_title: canMessage && transitionOps.notify_title ? transitionOps.notify_title : undefined,
        notify_message: canMessage && transitionOps.notify_message ? transitionOps.notify_message : undefined,
      });
      toast.success("State updated");
      setTransition((c) => ({ ...c, reason: "" }));
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Transition failed");
    }
  };

  const doVerifyDoc = async (documentId: number) => {
    try {
      await admissionsOfficeV2Api.documentVerify(documentId);
      toast.success("Document verified");
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Document update failed");
    }
  };

  const doRejectDoc = async (documentId: number) => {
    try {
      if (!docAction.reason.trim()) {
        toast.error("Reason is required.");
        return;
      }
      await admissionsOfficeV2Api.documentReject(documentId, { reason: docAction.reason, notes: docAction.notes || undefined });
      toast.success("Document rejected");
      setDocAction({ documentId: null, reason: "", notes: "" });
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Document update failed");
    }
  };

  const doRequestReupload = async (documentId: number) => {
    try {
      if (!docAction.reason.trim()) {
        toast.error("Reason is required.");
        return;
      }
      await admissionsOfficeV2Api.documentRequestReupload(documentId, { reason: docAction.reason, notes: docAction.notes || undefined, notify_applicant: true });
      toast.success("Reupload requested");
      setDocAction({ documentId: null, reason: "", notes: "" });
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Request failed");
    }
  };

  const doAddNote = async () => {
    try {
      if (!internalNote.trim()) return;
      await admissionsOfficeV2Api.addInternalNote(Number(id), internalNote.trim());
      toast.success("Note added");
      setInternalNote("");
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Note failed");
    }
  };

  const doApplicantMessage = async () => {
    try {
      if (!applicantMessage.message.trim() && !applicantMessage.template_key) {
        toast.error("Enter a message or select a template.");
        return;
      }
      await admissionsOfficeV2Api.sendApplicantMessage(Number(id), {
        title: applicantMessage.title.trim() ? applicantMessage.title.trim() : undefined,
        message: applicantMessage.message.trim() ? applicantMessage.message.trim() : undefined,
        template_key: applicantMessage.template_key ? applicantMessage.template_key : undefined,
      });
      toast.success("Message sent");
      setApplicantMessage((c) => ({ ...c, message: "" }));
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Message failed");
    }
  };

  const doDuplicateLink = async () => {
    try {
      const related = Number(duplicateAction.relatedId);
      if (!related || !duplicateAction.reason.trim()) {
        toast.error("Select a candidate and enter a reason.");
        return;
      }
      await admissionsOfficeV2Api.duplicateLink({
        canonical_application_id: Number(id),
        related_application_id: related,
        status: duplicateAction.status,
        reason: duplicateAction.reason.trim(),
      });
      toast.success("Duplicate action recorded");
      setDuplicateAction((c) => ({ ...c, reason: "" }));
      setDuplicateCompare(null);
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Duplicate action failed");
    }
  };

  const doDuplicateCompare = async (candidateId: number) => {
    try {
      const res = await admissionsOfficeV2Api.duplicateCompare(Number(id), candidateId);
      setDuplicateCompare(res);
    } catch (e) {
      setDuplicateCompare(null);
      toast.error(e instanceof Error ? e.message : "Compare failed");
    }
  };

  const doAssign = async () => {
    try {
      const assignee = Number(assign.assignee_user_id);
      if (!assignee || !assign.reason.trim()) {
        toast.error("Select a reviewer and enter a reason.");
        return;
      }
      await admissionsOfficeV2Api.assign(Number(id), { assignee_user_id: assignee, reason: assign.reason.trim() });
      toast.success("Reviewer assigned");
      setAssign((c) => ({ ...c, reason: "" }));
      await load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Assign failed");
    }
  };

  if (!workspace || !app) return <main className="container py-8">{busy ? "Loading review workspace…" : "Not found"}</main>;

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container py-8">
        <div className="mb-6">
          <p className="text-sm font-medium uppercase tracking-wide text-amber-600">Review</p>
          <h1 className="text-3xl font-semibold tracking-normal text-emerald-950">{app.application_number}</h1>
        </div>

        <div className="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
          <section className="space-y-6">
            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Application Summary</CardTitle></CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2">
                <Info label="Student" value={`${app.student_first_name} ${app.student_last_name}`} />
                <Info label="State" value={stateLabel} />
                <Info label="Type" value={app.application_type} />
                <Info label="Intake" value={`${formName(app.applying_form_id)} · ${categoryName(app.preferred_category_id)} · ${yearName(app.academic_year_id)}`.trim()} />
                <Info label="Submitted" value={app.submitted_at || "-"} />
                <Info label="Assigned Reviewer" value={assigned?.name || "-"} />
                <Info label="Review Started" value={app.review_started_at || "-"} />
                <Info label="Review Completed" value={app.review_completed_at || "-"} />
                <Info label="DOB" value={app.date_of_birth || "-"} />
                <Info label="Birth Certificate" value={app.birth_certificate_number || "-"} />
                <Info label="Student National ID" value={app.student_national_id || "Optional, not supplied"} />
                <Info label="Guardian" value={app.guardian_name || "-"} />
                <Info label="Guardian Email" value={app.guardian_email || "-"} />
                <Info label="Guardian Phone" value={app.guardian_phone || "-"} />
              </CardContent>
            </Card>

            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Document Verification</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-3 md:grid-cols-2">
                  {checklist.map((row: any) => (
                    <div key={row.document_type} className="rounded-lg border border-slate-200 p-4">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <p className="font-medium">{formatDocumentName(row.document_type)}</p>
                          <p className="mt-1 text-xs text-slate-500">{row.uploaded ? `Uploaded · v${row.version ?? "?"}` : "Not uploaded"}</p>
                        </div>
                        <Badge className="bg-emerald-800 capitalize">{String(row.verification_status || (row.uploaded ? "pending" : "missing")).replaceAll("_", " ")}</Badge>
                      </div>
                      {row.document_id && (
                        <div className="mt-3 flex flex-wrap gap-2">
                          <Button size="sm" className="gap-2 bg-emerald-800 hover:bg-emerald-900" onClick={() => doVerifyDoc(row.document_id)} disabled={!canReviewDocs}>
                            <Check className="h-4 w-4" />Verify
                          </Button>
                          <Button size="sm" variant="outline" className="gap-2" onClick={() => setDocAction((c) => ({ ...c, documentId: row.document_id }))} disabled={!canReviewDocs}>
                            <FileWarning className="h-4 w-4" />Reject / Reupload
                          </Button>
                        </div>
                      )}
                    </div>
                  ))}
                </div>

                {docAction.documentId && (
                  <div className="rounded-lg border border-slate-200 p-4">
                    <p className="font-medium">Document action</p>
                    <div className="mt-3 grid gap-3">
                      <Input placeholder="Reason (required for reject/reupload)" value={docAction.reason} onChange={(e) => setDocAction((c) => ({ ...c, reason: e.target.value }))} />
                      <Textarea placeholder="Notes (optional)" value={docAction.notes} onChange={(e) => setDocAction((c) => ({ ...c, notes: e.target.value }))} />
                      <div className="flex flex-wrap gap-2">
                        <Button variant="destructive" className="gap-2" onClick={() => doRejectDoc(docAction.documentId!)}><X className="h-4 w-4" />Reject</Button>
                        <Button variant="outline" className="gap-2" onClick={() => doRequestReupload(docAction.documentId!)}><ShieldAlert className="h-4 w-4" />Request reupload</Button>
                        <Button variant="outline" onClick={() => setDocAction({ documentId: null, reason: "", notes: "" })}>Cancel</Button>
                      </div>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </section>

          <aside className="space-y-6">
            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Review & Decisions (Pre-enrollment)</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <div className="flex flex-wrap gap-2">
                  <Button variant="outline" className="gap-2" onClick={doClaim} disabled={!canClaim}><UserCheck className="h-4 w-4" />Claim</Button>
                  <Button variant="outline" onClick={doRelease} disabled={!canClaim}>Release</Button>
                </div>
                {canAssign && (
                  <div className="rounded-lg border border-slate-200 p-3">
                    <p className="text-sm font-medium">Assign reviewer</p>
                    <div className="mt-2 grid gap-2">
                      <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={assign.assignee_user_id} onChange={(e) => setAssign((c) => ({ ...c, assignee_user_id: e.target.value }))}>
                        <option value="">Select reviewer</option>
                        {reviewers.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                      </select>
                      <Input placeholder="Reason (required)" value={assign.reason} onChange={(e) => setAssign((c) => ({ ...c, reason: e.target.value }))} />
                      <Button variant="outline" onClick={doAssign}>Assign</Button>
                    </div>
                  </div>
                )}
                <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={transition.to_state} onChange={(e) => setTransition((c) => ({ ...c, to_state: e.target.value }))}>
                  {["SUBMITTED","UNDER_REVIEW","DOCUMENTS_REQUIRED","READY_FOR_DECISION","ACCEPTED","REJECTED","WAITLISTED","DUPLICATE_FLAGGED","DUPLICATE_INVALID","ARCHIVED"].map((s) => (
                    <option key={s} value={s}>{s.replaceAll("_", " ")}</option>
                  ))}
                </select>
                <Textarea placeholder="Reason (required)" value={transition.reason} onChange={(e) => setTransition((c) => ({ ...c, reason: e.target.value }))} />
                {canMessage && (
                  <div className="rounded-lg border border-slate-200 p-3">
                    <label className="flex items-center gap-2 text-sm text-slate-700">
                      <input type="checkbox" checked={transitionOps.notify_applicant} onChange={(e) => setTransitionOps((c) => ({ ...c, notify_applicant: e.target.checked }))} />
                      Notify applicant
                    </label>
                    {transitionOps.notify_applicant && (
                      <div className="mt-2 grid gap-2">
                        <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={transitionOps.notify_template_key} onChange={(e) => setTransitionOps((c) => ({ ...c, notify_template_key: e.target.value }))}>
                          <option value="">No template</option>
                          {templates.map((t) => <option key={t.key} value={t.key}>{t.key.replaceAll("_", " ")}</option>)}
                        </select>
                        <Input placeholder="Title (optional)" value={transitionOps.notify_title} onChange={(e) => setTransitionOps((c) => ({ ...c, notify_title: e.target.value }))} />
                        <Textarea placeholder="Message override (optional)" value={transitionOps.notify_message} onChange={(e) => setTransitionOps((c) => ({ ...c, notify_message: e.target.value }))} />
                      </div>
                    )}
                  </div>
                )}
                {canOverride && (
                  <div className="rounded-lg border border-slate-200 p-3">
                    <label className="flex items-center gap-2 text-sm text-slate-700">
                      <input type="checkbox" checked={transitionOps.override} onChange={(e) => setTransitionOps((c) => ({ ...c, override: e.target.checked }))} />
                      Override rules
                    </label>
                    <label className="mt-2 flex items-center gap-2 text-sm text-slate-700">
                      <input type="checkbox" checked={transitionOps.force} onChange={(e) => setTransitionOps((c) => ({ ...c, force: e.target.checked }))} />
                      Force (bypass guards)
                    </label>
                  </div>
                )}
                <Button className="gap-2 bg-emerald-800 hover:bg-emerald-900" onClick={doTransition} disabled={!canTransition}><Check className="h-4 w-4" />Apply transition</Button>
              </CardContent>
            </Card>

            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Duplicate Management</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <div className="text-sm text-slate-600">Potential duplicates: {dupes.length}</div>
                <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={duplicateAction.relatedId} onChange={(e) => setDuplicateAction((c) => ({ ...c, relatedId: e.target.value }))} disabled={!canManageDuplicates}>
                  <option value="">Select candidate</option>
                  {dupes.map((d: any) => (
                    <option key={d.id} value={d.id}>{d.application_number} · score {d.similarity_score}</option>
                  ))}
                </select>
                <Button variant="outline" onClick={() => (duplicateAction.relatedId ? doDuplicateCompare(Number(duplicateAction.relatedId)) : toast.error("Select a candidate first."))} disabled={!canManageDuplicates || !duplicateAction.relatedId}>Compare</Button>
                <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={duplicateAction.status} onChange={(e) => setDuplicateAction((c) => ({ ...c, status: e.target.value as any }))} disabled={!canManageDuplicates}>
                  <option value="flagged">Flag as duplicate</option>
                  <option value="merged">Merge into canonical</option>
                  <option value="invalid">Invalidate duplicate</option>
                </select>
                <Textarea placeholder="Reason (required)" value={duplicateAction.reason} onChange={(e) => setDuplicateAction((c) => ({ ...c, reason: e.target.value }))} />
                <Button className="bg-emerald-800 hover:bg-emerald-900" onClick={doDuplicateLink} disabled={!canManageDuplicates}>Apply</Button>
                {duplicateCompare && (
                  <div className="rounded-lg border border-slate-200 p-3 text-sm">
                    <div className="flex items-center justify-between gap-2">
                      <p className="font-medium">Comparison</p>
                      <Button variant="outline" size="sm" onClick={() => setDuplicateCompare(null)}>Close</Button>
                    </div>
                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                      <CompareBlock title="Canonical" app={duplicateCompare.canonical?.application} docs={duplicateCompare.canonical?.documents_latest ?? []} />
                      <CompareBlock title="Candidate" app={duplicateCompare.candidate?.application} docs={duplicateCompare.candidate?.documents_latest ?? []} />
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Internal Notes</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <Textarea placeholder="Private note (not visible to applicants)" value={internalNote} onChange={(e) => setInternalNote(e.target.value)} disabled={!canAddNotes} />
                <Button className="bg-emerald-800 hover:bg-emerald-900" onClick={doAddNote} disabled={!canAddNotes}>Add note</Button>
                <div className="space-y-2">
                  {notes.filter((n: any) => n.visibility === "internal").slice(0, 8).map((n: any) => (
                    <div key={n.id} className="rounded-lg border border-slate-200 p-3">
                      <p className="text-xs text-slate-500">{n.created_by_name || "Staff"} · {n.created_at}</p>
                      <p className="mt-1 whitespace-pre-line text-sm text-slate-700">{n.message}</p>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Applicant Communication</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={applicantMessage.template_key} onChange={(e) => setApplicantMessage((c) => ({ ...c, template_key: e.target.value }))} disabled={!canMessage}>
                  <option value="">No template</option>
                  {templates.map((t) => <option key={t.key} value={t.key}>{t.key.replaceAll("_", " ")}</option>)}
                </select>
                <Input placeholder="Title (optional)" value={applicantMessage.title} onChange={(e) => setApplicantMessage((c) => ({ ...c, title: e.target.value }))} disabled={!canMessage} />
                <Textarea placeholder="Message to applicant (audited)" value={applicantMessage.message} onChange={(e) => setApplicantMessage((c) => ({ ...c, message: e.target.value }))} disabled={!canMessage} />
                <Button className="bg-emerald-800 hover:bg-emerald-900" onClick={doApplicantMessage} disabled={!canMessage}>Send message</Button>
              </CardContent>
            </Card>

            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Document History</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {(workspace?.documents ?? []).slice(0, 40).map((d: any) => (
                  <div key={`${d.application_id}-${d.id}`} className="rounded-lg border border-slate-200 p-3 text-sm">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{formatDocumentName(String(d.document_type))} · v{d.version}</p>
                        <p className="text-xs text-slate-500">{d.uploaded_at}</p>
                        {d.superseded_at ? <p className="text-xs text-slate-500">Superseded</p> : null}
                      </div>
                      <Badge variant="outline">{String(d.verification_status || "pending")}</Badge>
                    </div>
                    <p className="mt-2 text-xs text-slate-600 break-all">{d.file_name}</p>
                  </div>
                ))}
              </CardContent>
            </Card>

            <Card className="rounded-lg border-emerald-900/10">
              <CardHeader><CardTitle>Audit Timeline</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {audit.slice(0, 12).map((e: any) => (
                  <div key={e.id} className="rounded-lg border border-slate-200 p-3">
                    <p className="text-xs text-slate-500">{e.created_at} · {e.event_type}</p>
                    {e.meta && <p className="mt-1 text-xs text-slate-600 whitespace-pre-line">{typeof e.meta === "string" ? e.meta : JSON.stringify(e.meta)}</p>}
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

function formatDocumentName(value: string) {
  return value.replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
}

function CompareBlock({ title, app, docs }: { title: string; app: any; docs: any[] }) {
  if (!app) return null;
  return (
    <div className="rounded-lg border border-slate-200 p-3">
      <p className="font-medium">{title}</p>
      <div className="mt-2 grid gap-2 text-xs text-slate-700">
        <div>Application: {app.application_number}</div>
        <div>Learner: {app.student_first_name} {app.student_last_name}</div>
        <div>DOB: {app.date_of_birth}</div>
        <div>Guardian: {app.guardian_name} · {app.guardian_email}</div>
        <div>State: {String(app.lifecycle_state || app.status)}</div>
      </div>
      <div className="mt-3 space-y-2">
        {docs.map((d: any) => (
          <div key={d.id} className="rounded border border-slate-200 p-2 text-xs">
            <div className="flex items-center justify-between">
              <span>{formatDocumentName(String(d.document_type))} v{d.version}</span>
              <Badge variant="outline">{String(d.verification_status || "pending")}</Badge>
            </div>
          </div>
        ))}
        {docs.length === 0 ? <div className="text-xs text-slate-500">No documents</div> : null}
      </div>
    </div>
  );
}
