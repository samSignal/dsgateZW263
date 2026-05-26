import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { admissionsV2Api, AdmissionsV2Error, type CatalogAcademicYear, type CatalogCategory, type CatalogForm, type V2DraftApplication, type V2ApplicationType } from "@/lib/admissionsV2Api";
import { clearAdmissionsSession, getAdmissionsSession, setAdmissionsSession } from "@/lib/admissionsV2Session";
import { requiredDocumentsFor, validateStep, type FieldErrors } from "@/lib/admissionsV2Validation";
import { CheckCircle2, FileUp, Loader2, Save, Send } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import { toast } from "sonner";

const steps = ["Intake & Program Choice", "Learner Identity", "Guardian & Emergency", "Academic Background", "Documents Upload", "Review & Submit"];
const allDocumentTypes = ["birth_certificate", "passport_photo", "grade7_report", "latest_report", "transfer_letter", "discipline_record", "medical_record"] as const;

export default function OnlineApplicationPage() {
  const [step, setStep] = useState(1);
  const [catalogYears, setCatalogYears] = useState<CatalogAcademicYear[]>([]);
  const [catalogForms, setCatalogForms] = useState<CatalogForm[]>([]);
  const [catalogCategories, setCatalogCategories] = useState<CatalogCategory[]>([]);

  const [draft, setDraft] = useState<Partial<V2DraftApplication> & { application_type: V2ApplicationType }>({
    application_type: "new_intake",
    gender: "",
  });
  const [documents, setDocuments] = useState<{ document_type: string; file_name: string; version: number; uploaded_at: string | null }[]>([]);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [busy, setBusy] = useState<{ loading: boolean; saving: boolean; submitting: boolean }>({ loading: true, saving: false, submitting: false });
  const [session, setSession] = useState(() => getAdmissionsSession());
  const [issuedTokenOnce, setIssuedTokenOnce] = useState<string | null>(null);
  const [confirmation, setConfirmation] = useState<{ application_number: string; token_last4: string; submitted_at: string } | null>(null);
  const [revisionConflict, setRevisionConflict] = useState<{ open: boolean; currentRevision?: number }>(() => ({ open: false }));
  const [duplicateBlock, setDuplicateBlock] = useState<{ code: string; message: string } | null>(null);
  const [recoveryRequested, setRecoveryRequested] = useState(false);

  const completion = useMemo(() => Math.round((step / steps.length) * 100), [step]);
  const requiredDocs = useMemo(() => requiredDocumentsFor(draft.application_type), [draft.application_type]);
  const stepIsValid = useMemo(() => Object.keys(validateStep(step, draft)).length === 0, [draft, step]);
  const archivedReason = (draft as any).archived_reason as string | null | undefined;
  const isArchivedDraft = Boolean((draft as any).archived_at) && String((draft as any).status ?? "").toLowerCase() === "draft";

  const autosaveTimer = useRef<number | null>(null);
  const lastSavedRevision = useRef<number>(0);

  const update = (field: keyof V2DraftApplication | "application_type" | "gender", value: any) => {
    setDraft((current) => ({ ...current, [field]: value }));
    if (duplicateBlock) setDuplicateBlock(null);
    setFieldErrors((current) => {
      if (!current[field as string]) return current;
      const next = { ...current };
      delete next[field as string];
      return next;
    });
  };

  const loadCatalog = async () => {
    const [y, f, c] = await Promise.all([
      admissionsV2Api.catalogAcademicYears(),
      admissionsV2Api.catalogForms(),
      admissionsV2Api.catalogCategories(),
    ]);
    setCatalogYears(y.academic_years);
    setCatalogForms(f.forms);
    setCatalogCategories(c.categories);
  };

  const refreshDraftFromServer = async (token: string, dateOfBirth: string) => {
    const res = await admissionsV2Api.draftGet(token && dateOfBirth ? { token, date_of_birth: dateOfBirth } : {});
    setDraft((current) => ({ ...current, ...res.application, application_type: res.application.application_type }));
    setDocuments(res.documents.map((d) => ({ document_type: d.document_type, file_name: d.file_name, version: d.version, uploaded_at: d.uploaded_at })));
    lastSavedRevision.current = res.application.draft_revision;
  };

  useEffect(() => {
    (async () => {
      try {
        await loadCatalog();
        const s = getAdmissionsSession();
        if (s?.sessionToken || (s?.token && s?.dateOfBirth)) {
          setSession(s);
          const res = await admissionsV2Api.draftGet(s.token && s.dateOfBirth ? { token: s.token, date_of_birth: s.dateOfBirth } : {});
          setDraft((current) => ({ ...current, ...res.application, application_type: res.application.application_type }));
          setDocuments(res.documents.map((d) => ({ document_type: d.document_type, file_name: d.file_name, version: d.version, uploaded_at: d.uploaded_at })));
          lastSavedRevision.current = res.application.draft_revision;
          setStep(Math.min(6, Math.max(1, Number(res.application.current_step ?? 1))));
        }
      } catch (e) {
        toast.error(e instanceof Error ? e.message : "Failed to load admissions catalog");
      } finally {
        setBusy((b) => ({ ...b, loading: false }));
      }
    })();
  }, []);

  const validateCurrentStep = (targetStep = step) => {
    const errs = validateStep(targetStep, draft);
    setFieldErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const submit = async () => {
    setBusy((b) => ({ ...b, submitting: true }));
    try {
      const hasAuth = Boolean(session?.sessionToken || (session?.token && session?.dateOfBirth));
      if (!hasAuth) {
        toast.error("Please start or resume your application first.");
        return;
      }
      const allOk =
        validateCurrentStep(1) &&
        validateCurrentStep(2) &&
        validateCurrentStep(3) &&
        validateCurrentStep(4);
      if (!allOk) {
        toast.error("Please correct the highlighted fields before submitting.");
        return;
      }
      const missing = requiredDocs.filter((t) => !documents.some((d) => d.document_type === t));
      if (missing.length > 0) {
        toast.error("Please upload all required documents before submitting.");
        setStep(5);
        return;
      }
      if (!draftConsentChecked(draft)) {
        toast.error("Please confirm that the information provided is accurate.");
        return;
      }

      const idempotencyKey = crypto.randomUUID();
      const authPayload = session?.token && session?.dateOfBirth ? { token: session.token, date_of_birth: session.dateOfBirth } : {};
      const res = await admissionsV2Api.submit(authPayload, idempotencyKey);

      setIssuedTokenOnce(res.issued_token);
      setConfirmation({ application_number: res.application_number, token_last4: res.token_last4, submitted_at: res.submitted_at });
      setAdmissionsSession({
        token: res.issued_token,
        dateOfBirth: session?.dateOfBirth || "",
        applicationId: res.application_id,
        applicationNumber: res.application_number,
        tokenLast4: res.token_last4,
        sessionToken: session?.sessionToken,
        sessionExpiresAt: session?.sessionExpiresAt,
        stepupUntil: session?.stepupUntil,
      });
      setSession(getAdmissionsSession());
    } catch (error) {
      if (error instanceof AdmissionsV2Error) {
        if (error.errors && Object.keys(error.errors).length > 0) {
          setFieldErrors(error.errors);
          toast.error(error.message);
        } else if (error.code === "DUPLICATE_SUBMITTED") {
          toast.error("We cannot accept another submission for this learner for the selected intake year.");
        } else {
          toast.error(error.message);
        }
      } else {
        toast.error(error instanceof Error ? error.message : "Submission failed");
      }
    } finally {
      setBusy((b) => ({ ...b, submitting: false }));
    }
  };

  const startDraftIfNeeded = async () => {
    if (session?.token && session?.dateOfBirth) return true;
    const ok = validateCurrentStep(1) && validateCurrentStep(2);
    if (!ok) {
      toast.error("Please complete the required fields to start your application.");
      return false;
    }

    try {
      const res = await admissionsV2Api.draftStart({
        application_type: draft.application_type,
        academic_year_id: Number(draft.academic_year_id),
        applying_form_id: Number(draft.applying_form_id),
        preferred_category_id: Number(draft.preferred_category_id),
        student_first_name: String(draft.student_first_name ?? ""),
        student_last_name: String(draft.student_last_name ?? ""),
        gender: draft.gender ? (draft.gender as any) : undefined,
        date_of_birth: String(draft.date_of_birth),
        birth_certificate_number: String(draft.birth_certificate_number ?? ""),
        student_national_id: draft.student_national_id ?? undefined,
        guardian_name: draft.guardian_name ?? undefined,
        guardian_national_id: draft.guardian_national_id ?? undefined,
        guardian_phone: draft.guardian_phone ?? undefined,
        guardian_email: draft.guardian_email ?? undefined,
        emergency_phone: draft.emergency_phone ?? undefined,
        address: draft.address ?? undefined,
        occupation: draft.occupation ?? undefined,
      });

      setIssuedTokenOnce(res.issued_token);
      const s = await admissionsV2Api.sessionStart({ token: res.issued_token, date_of_birth: String(draft.date_of_birth) });
      const nextSession = {
        token: res.issued_token,
        dateOfBirth: String(draft.date_of_birth),
        applicationId: res.application_id,
        applicationNumber: res.application_number,
        tokenLast4: res.token_last4,
        sessionToken: s.session_token,
        sessionExpiresAt: s.expires_at,
      };
      setAdmissionsSession(nextSession);
      setSession(nextSession);
      lastSavedRevision.current = res.draft_revision;
      toast.success("Draft started. Keep your tracking token safe.");
      return true;
    } catch (e) {
      if (e instanceof AdmissionsV2Error) {
        if (e.code === "DUPLICATE_SUBMITTED" || e.code === "DRAFT_EXISTS") {
          setDuplicateBlock({ code: e.code, message: e.message });
          return false;
        }
        if (Object.keys(e.errors).length > 0) {
          setFieldErrors(e.errors);
          toast.error(e.message);
          return false;
        }
      }
      toast.error(e instanceof Error ? e.message : "Could not start draft");
      return false;
    }
  };

  const upload = async (documentType: string, file?: File) => {
    const hasAuth = Boolean(session?.sessionToken || (session?.token && session?.dateOfBirth));
    if (!hasAuth) {
      toast.error("Please start your application before uploading documents.");
      return;
    }
    if (!file) return;
    try {
      const res = await admissionsV2Api.uploadDocument({
        token: session?.token && session?.dateOfBirth ? session.token : undefined,
        date_of_birth: session?.token && session?.dateOfBirth ? session.dateOfBirth : undefined,
        document_type: documentType,
        document: file,
      });
      setDocuments((current) => {
        const next = current.filter((d) => d.document_type !== documentType);
        next.push({ document_type: documentType, file_name: file.name, version: res.version, uploaded_at: new Date().toISOString() });
        next.sort((a, b) => a.document_type.localeCompare(b.document_type));
        return next;
      });
      toast.success("Document uploaded.");
    } catch (e) {
      if (e instanceof AdmissionsV2Error) {
        if (e.code === "STEPUP_REQUIRED") {
          try {
            await admissionsV2Api.stepupRequestFor("replace_documents");
            toast.error("Additional verification is required. Check your email for a verification link.");
          } catch {
            toast.error("Additional verification is required. Please check your email or contact admissions.");
          }
          return;
        }
        toast.error(e.message);
      } else {
        toast.error(e instanceof Error ? e.message : "Upload failed");
      }
    }
  };

  const saveNow = async (nextStep?: number) => {
    const hasAuth = Boolean(session?.sessionToken || (session?.token && session?.dateOfBirth));
    if (!hasAuth) return;
    if (busy.saving) return;
    setBusy((b) => ({ ...b, saving: true }));
    try {
      const payload: any = {
        token: session?.token && session?.dateOfBirth ? session.token : undefined,
        date_of_birth: session?.token && session?.dateOfBirth ? session.dateOfBirth : undefined,
        draft_revision: lastSavedRevision.current,
        current_step: nextStep ?? step,
      };

      const fields = [
        "application_type",
        "academic_year_id",
        "applying_form_id",
        "preferred_category_id",
        "student_first_name",
        "student_last_name",
        "gender",
        "date_of_birth",
        "birth_certificate_number",
        "student_national_id",
        "guardian_name",
        "guardian_national_id",
        "guardian_phone",
        "guardian_email",
        "emergency_phone",
        "address",
        "occupation",
        "grade7_school",
        "grade7_results",
        "previous_school_name",
        "current_form",
        "transfer_reason",
        "last_term_average",
        "reason_for_joining",
        "medical_information",
      ] as const;

      for (const f of fields) {
        const v = (draft as any)[f];
        if (v !== undefined) payload[f] = v;
      }

      const res = await admissionsV2Api.draftSave(payload);
      lastSavedRevision.current = res.draft_revision;
      if (res.issued_token && session) {
        const next = { ...session, token: res.issued_token, tokenLast4: res.token_last4 ?? session.tokenLast4 };
        setAdmissionsSession(next);
        setSession(next);
        setIssuedTokenOnce(res.issued_token);
        toast.success("Your admissions access token was updated for security. Save it safely.");
      }
    } catch (e) {
      if (e instanceof AdmissionsV2Error && e.code === "REVISION_CONFLICT") {
        setRevisionConflict({ open: true, currentRevision: e.meta?.current_draft_revision });
      } else if (e instanceof AdmissionsV2Error && e.code === "STEPUP_REQUIRED") {
        const action = typeof e.meta?.required_action === "string" ? e.meta.required_action : "sensitive_action";
        try {
          await admissionsV2Api.stepupRequestFor(action);
          toast.error("Additional verification is required. Check your email for a verification link.");
        } catch {
          toast.error("Additional verification is required. Please check your email or contact admissions.");
        }
      } else {
        toast.error(e instanceof Error ? e.message : "Save failed");
      }
    } finally {
      setBusy((b) => ({ ...b, saving: false }));
    }
  };

  useEffect(() => {
    const hasAuth = Boolean(session?.sessionToken || (session?.token && session?.dateOfBirth));
    if (!hasAuth) return;
    if (confirmation) return;
    if (autosaveTimer.current) window.clearTimeout(autosaveTimer.current);
    autosaveTimer.current = window.setTimeout(() => {
      saveNow();
    }, 1000);
    return () => {
      if (autosaveTimer.current) window.clearTimeout(autosaveTimer.current);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [draft, step, session?.token, session?.dateOfBirth, session?.sessionToken]);

  useEffect(() => {
    const keys = Object.keys(fieldErrors);
    if (keys.length === 0) return;
    window.setTimeout(() => {
      const el = document.querySelector('[aria-invalid="true"]') as HTMLElement | null;
      if (el) el.scrollIntoView({ behavior: "smooth", block: "center" });
      if (el && "focus" in el) (el as any).focus?.();
    }, 50);
  }, [fieldErrors, step]);

  const goNext = async () => {
    if (!validateCurrentStep(step)) {
      toast.error("Please correct the highlighted fields.");
      return;
    }
    if (step === 2) {
      const started = await startDraftIfNeeded();
      if (!started) return;
    }
    await saveNow(step + 1);
    setStep((s) => Math.min(6, s + 1));
  };

  const goBack = async () => {
    await saveNow(step - 1);
    setStep((s) => Math.max(1, s - 1));
  };

  if (busy.loading) {
    return (
      <main className="min-h-screen bg-white text-slate-950">
        <div className="container py-10">
          <div className="flex items-center gap-2 text-sm text-slate-600">
            <Loader2 className="h-4 w-4 animate-spin" />
            Loading admissions application…
          </div>
        </div>
      </main>
    );
  }

  if (isArchivedDraft) {
    const message =
      archivedReason === "archived_intake_closed"
        ? "This admissions intake is now closed."
        : archivedReason === "archived_inactive"
          ? "This draft has expired due to inactivity and is now archived."
          : "This draft is archived and cannot be edited online.";

    return (
      <main className="min-h-screen bg-white text-slate-950">
        <div className="container py-10">
          <Card className="rounded-lg border-emerald-900/10">
            <CardHeader>
              <CardTitle>Admissions draft archived</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4 text-sm text-slate-700">
              <div>{message}</div>
              <div className="text-slate-600">
                You can still track submitted applications. Archived drafts may require recovery to continue, depending on intake policy.
              </div>
              <div className="flex flex-wrap gap-3">
                <Button className="bg-emerald-800 hover:bg-emerald-900" onClick={() => window.location.assign("/admissions/track")}>
                  Track or recover access
                </Button>
                <Button variant="outline" onClick={() => { clearAdmissionsSession(); window.location.reload(); }}>
                  Use a different token
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </main>
    );
  }

  if (confirmation) {
    return (
      <main className="min-h-screen bg-white text-slate-950">
        <div className="container py-10">
          <Card className="rounded-lg border-emerald-900/10">
            <CardHeader>
              <CardTitle>Application submitted</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-950">
                <div className="font-semibold">Your application has been submitted successfully.</div>
                <div className="mt-2 text-sm">Application Number: <span className="font-semibold">{confirmation.application_number}</span></div>
              </div>
              {issuedTokenOnce && (
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-emerald-950">
                  <div className="font-semibold">Tracking token (showing once)</div>
                  <div className="mt-2 font-mono text-lg">{issuedTokenOnce}</div>
                  <div className="mt-2 text-sm text-slate-700">Keep this token safe. You will need it together with the learner’s date of birth to track or continue.</div>
                </div>
              )}
              <div className="flex flex-col gap-2 sm:flex-row">
                <Button className="bg-emerald-800 hover:bg-emerald-900" onClick={() => (window.location.href = "/admissions/track")}>Track status</Button>
                <Button variant="outline" onClick={() => { clearAdmissionsSession(); setSession(null); setConfirmation(null); setIssuedTokenOnce(null); setStep(1); setDraft({ application_type: "new_intake", gender: "" }); setDocuments([]); }}>Start another application</Button>
              </div>
              <div className="text-xs text-slate-500">For privacy, the token will not be displayed again after you leave this page.</div>
            </CardContent>
          </Card>
        </div>
      </main>
    );
  }

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container py-8">
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <p className="text-sm font-medium uppercase tracking-wide text-amber-600">Online Admissions</p>
            <h1 className="text-3xl font-semibold tracking-normal text-emerald-950">Application wizard</h1>
          </div>
          {session?.tokenLast4 && (
            <div className="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-emerald-950">
              Tracking token: <span className="font-semibold">••••{session.tokenLast4}</span>
            </div>
          )}
        </div>

        <div className="mb-6">
          <div className="h-2 overflow-hidden rounded-full bg-emerald-100">
            <div className="h-full rounded-full bg-emerald-800 transition-all" style={{ width: `${completion}%` }} />
          </div>
          <div className="mt-3 grid gap-2 md:grid-cols-6">
            {steps.map((item, index) => {
              const target = index + 1;
              return (
                <button
                  key={item}
                  onClick={() => {
                    if (target <= step) setStep(target);
                  }}
                  className={`rounded-md border px-2 py-2 text-xs ${step === target ? "border-emerald-800 bg-emerald-800 text-white" : target <= step ? "border-slate-200 text-slate-700" : "border-slate-200 text-slate-400"}`}
                  type="button"
                  aria-current={step === target ? "step" : undefined}
                >
                  {target}. {item}
                </button>
              );
            })}
          </div>
        </div>

        <Card className="rounded-lg border-emerald-900/10">
          <CardHeader>
            <CardTitle>{steps[step - 1]}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-5">
            {Object.keys(fieldErrors).length > 0 && (
              <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                <div className="font-semibold">Please review the highlighted fields.</div>
                <div className="mt-2 text-xs text-amber-900">You cannot move forward until required fields are complete.</div>
              </div>
            )}

            {step === 1 && (
              <div className="space-y-4">
                {duplicateBlock && (
                  <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                    <div className="font-semibold">{duplicateBlock.message}</div>
                    <div className="mt-2 text-xs text-amber-900">
                      If an application already exists for this learner and intake year, we can help you recover access.
                    </div>
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row">
                      <Button variant="outline" onClick={() => (window.location.href = "/admissions/continue")} type="button">Continue existing</Button>
                      <Button variant="outline" onClick={() => (window.location.href = "/admissions/track")} type="button">Track existing</Button>
                      <Button
                        className="bg-emerald-800 hover:bg-emerald-900"
                        onClick={async () => {
                          if (!draft.academic_year_id || !draft.birth_certificate_number) {
                            toast.error("Select intake year and enter the learner’s birth certificate number.");
                            return;
                          }
                          if (!draft.guardian_email) {
                            toast.error("Enter the guardian email first, then request recovery.");
                            return;
                          }
                          try {
                            await admissionsV2Api.recoveryRequest({
                              academic_year_id: Number(draft.academic_year_id),
                              birth_certificate_number: String(draft.birth_certificate_number),
                              guardian_email: draft.guardian_email,
                            });
                            setRecoveryRequested(true);
                            toast.success("If we find a matching application, we will send recovery instructions.");
                          } catch (err) {
                            toast.error(err instanceof Error ? err.message : "Recovery request failed");
                          }
                        }}
                        type="button"
                      >
                        Recover access
                      </Button>
                    </div>
                    {recoveryRequested && (
                      <div className="mt-3 text-xs text-slate-700">
                        Recovery requested. Check your email/phone messages or contact admissions with your application number if available.
                      </div>
                    )}
                  </div>
                )}
                <div className="grid gap-4 md:grid-cols-2">
                  {(
                    [
                      ["new_intake", "New intake", "For learners entering high school."],
                      ["transfer", "Transfer", "For learners moving from another school."],
                    ] as const
                  ).map(([value, title, copy]) => (
                    <button
                      key={value}
                      onClick={() => update("application_type", value)}
                      type="button"
                      className={`rounded-lg border p-5 text-left ${draft.application_type === value ? "border-emerald-800 bg-emerald-50" : "border-slate-200"}`}
                      aria-pressed={draft.application_type === value}
                    >
                      <CheckCircle2 className="mb-3 h-6 w-6 text-emerald-800" />
                      <p className="font-semibold">{title}</p>
                      <p className="mt-1 text-sm text-slate-600">{copy}</p>
                    </button>
                  ))}
                </div>

                <FieldGrid>
                  <SelectField
                    label="Intake academic year"
                    value={draft.academic_year_id ? String(draft.academic_year_id) : ""}
                    onChange={(v) => update("academic_year_id", v ? Number(v) : null)}
                    options={catalogYears.map((y) => ({ value: String(y.id), label: y.name }))}
                    error={fieldErrors.academic_year_id?.[0]}
                  />
                  <SelectField
                    label="Applying form"
                    value={draft.applying_form_id ? String(draft.applying_form_id) : ""}
                    onChange={(v) => update("applying_form_id", v ? Number(v) : null)}
                    options={catalogForms.map((f) => ({ value: String(f.id), label: f.name }))}
                    error={fieldErrors.applying_form_id?.[0]}
                  />
                  <SelectField
                    label="Preferred pathway (category)"
                    value={draft.preferred_category_id ? String(draft.preferred_category_id) : ""}
                    onChange={(v) => update("preferred_category_id", v ? Number(v) : null)}
                    options={catalogCategories.map((c) => ({ value: String(c.id), label: `${c.name}${c.description ? ` — ${c.description}` : ""}` }))}
                    error={fieldErrors.preferred_category_id?.[0]}
                  />
                </FieldGrid>
                <div className="text-xs text-slate-600">
                  You select only the intake year, form, and preferred pathway. Streams are assigned later by the admissions office.
                </div>
              </div>
            )}

            {step === 2 && (
              <FieldGrid>
                <Field label="First name" value={draft.student_first_name ?? ""} onChange={(v) => update("student_first_name", v)} error={fieldErrors.student_first_name?.[0]} />
                <Field label="Last name" value={draft.student_last_name ?? ""} onChange={(v) => update("student_last_name", v)} error={fieldErrors.student_last_name?.[0]} />
                <SelectField label="Gender" value={draft.gender ?? ""} onChange={(v) => update("gender", v)} options={["male", "female", "other"]} error={fieldErrors.gender?.[0]} />
                <Field label="Date of birth" type="date" value={draft.date_of_birth ?? ""} onChange={(v) => update("date_of_birth", v)} error={fieldErrors.date_of_birth?.[0]} />
                <Field label="Birth certificate number" value={draft.birth_certificate_number ?? ""} onChange={(v) => update("birth_certificate_number", v)} error={fieldErrors.birth_certificate_number?.[0]} />
                <Field label="National ID (optional)" value={draft.student_national_id ?? ""} onChange={(v) => update("student_national_id", v)} />
              </FieldGrid>
            )}

            {step === 3 && (
              <FieldGrid>
                <Field label="Guardian name" value={draft.guardian_name ?? ""} onChange={(v) => update("guardian_name", v)} error={fieldErrors.guardian_name?.[0]} />
                <Field label="Guardian national ID (optional)" value={draft.guardian_national_id ?? ""} onChange={(v) => update("guardian_national_id", v)} />
                <Field label="Guardian phone" value={draft.guardian_phone ?? ""} onChange={(v) => update("guardian_phone", v)} error={fieldErrors.guardian_phone?.[0]} />
                <Field label="Emergency phone" value={draft.emergency_phone ?? ""} onChange={(v) => update("emergency_phone", v)} error={fieldErrors.emergency_phone?.[0]} />
                <Field label="Guardian email" type="email" value={draft.guardian_email ?? ""} onChange={(v) => update("guardian_email", v)} error={fieldErrors.guardian_email?.[0]} />
                <Field label="Occupation (optional)" value={draft.occupation ?? ""} onChange={(v) => update("occupation", v)} />
                <div className="md:col-span-2">
                  <Label>Address</Label>
                  <Textarea value={draft.address || ""} onChange={(e) => update("address", e.target.value)} />
                </div>
              </FieldGrid>
            )}

            {step === 4 && (
              <FieldGrid>
                {draft.application_type === "new_intake" ? (
                  <>
                    <Field label="Grade 7 school" value={draft.grade7_school ?? ""} onChange={(v) => update("grade7_school", v)} error={fieldErrors.grade7_school?.[0]} />
                    <Field label="Grade 7 results summary" value={draft.grade7_results ?? ""} onChange={(v) => update("grade7_results", v)} error={fieldErrors.grade7_results?.[0]} />
                  </>
                ) : (
                  <>
                    <Field label="Previous school" value={draft.previous_school_name ?? ""} onChange={(v) => update("previous_school_name", v)} error={fieldErrors.previous_school_name?.[0]} />
                    <Field label="Current form" value={draft.current_form ?? ""} onChange={(v) => update("current_form", v)} error={fieldErrors.current_form?.[0]} />
                    <Field label="Last term average (optional)" value={draft.last_term_average ?? ""} onChange={(v) => update("last_term_average", v)} />
                    <div className="md:col-span-2"><Label>Transfer reason</Label><Textarea value={draft.transfer_reason || ""} onChange={(e) => update("transfer_reason", e.target.value)} /></div>
                  </>
                )}
                <div className="md:col-span-2"><Label>Reason for joining (optional)</Label><Textarea value={draft.reason_for_joining || ""} onChange={(e) => update("reason_for_joining", e.target.value)} /></div>
                <div className="md:col-span-2"><Label>Medical information (optional)</Label><Textarea value={draft.medical_information || ""} onChange={(e) => update("medical_information", e.target.value)} /></div>
              </FieldGrid>
            )}

            {step === 5 && (
              <div className="space-y-4">
                <div className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-700">
                  Required documents must be uploaded before submission.
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                  {requiredDocs.map((type) => {
                    const doc = documents.find((d) => d.document_type === type);
                    return (
                      <label key={type} className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-4">
                        <span className="text-sm">
                          <span className="flex items-center gap-2 font-medium"><FileUp className="h-4 w-4 text-emerald-800" />{formatDocumentName(type)}</span>
                          <span className="mt-1 block text-xs text-slate-500">
                            {doc ? `Uploaded: ${doc.file_name} (v${doc.version})` : "Missing"}
                          </span>
                        </span>
                        <Input className="max-w-56" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" onChange={(e) => upload(type, e.target.files?.[0])} />
                      </label>
                    );
                  })}
                </div>
                <details className="rounded-lg border border-slate-200 p-4 text-sm">
                  <summary className="cursor-pointer font-medium">Optional documents</summary>
                  <div className="mt-3 grid gap-3 md:grid-cols-2">
                    {allDocumentTypes
                      .filter((t) => !requiredDocs.includes(t))
                      .map((type) => {
                        const doc = documents.find((d) => d.document_type === type);
                        return (
                          <label key={type} className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-4">
                            <span className="text-sm">
                              <span className="flex items-center gap-2 font-medium"><FileUp className="h-4 w-4 text-emerald-800" />{formatDocumentName(type)}</span>
                              <span className="mt-1 block text-xs text-slate-500">
                                {doc ? `Uploaded: ${doc.file_name} (v${doc.version})` : "Optional"}
                              </span>
                            </span>
                            <Input className="max-w-56" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" onChange={(e) => upload(type, e.target.files?.[0])} />
                          </label>
                        );
                      })}
                  </div>
                </details>
              </div>
            )}

            {step === 6 && (
              <div className="grid gap-4 md:grid-cols-2">
                <Summary label="Learner" value={`${draft.student_first_name || ""} ${draft.student_last_name || ""}`.trim() || "-"} />
                <Summary label="Application type" value={draft.application_type === "transfer" ? "Transfer" : "New intake"} />
                <Summary label="Guardian" value={draft.guardian_name || "-"} />
                <Summary label="Guardian email" value={draft.guardian_email || "-"} />
                <Summary label="Birth certificate" value={draft.birth_certificate_number || "-"} />
                <Summary label="Application number" value={session?.applicationNumber || "Generated when the draft starts"} />
                <div className="md:col-span-2 rounded-lg border border-slate-200 p-4">
                  <label className="flex items-start gap-3 text-sm">
                    <input type="checkbox" checked={Boolean((draft as any).__consent)} onChange={(e) => update("__consent" as any, e.target.checked)} />
                    <span>
                      <span className="font-medium">I confirm the information provided is accurate.</span>
                      <span className="mt-1 block text-xs text-slate-500">You will not be able to edit core details after submission.</span>
                    </span>
                  </label>
                </div>
              </div>
            )}

            <div className="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-between">
              <Button variant="outline" onClick={goBack} disabled={step === 1 || busy.saving || busy.submitting} type="button">Back</Button>
              <div className="flex flex-col gap-3 sm:flex-row">
                <Button
                  variant="outline"
                  onClick={async () => {
                    const started = await startDraftIfNeeded();
                    if (!started) return;
                    await saveNow(step);
                    toast.success("Draft saved.");
                  }}
                  disabled={busy.saving || busy.submitting}
                  className="gap-2"
                  type="button"
                >
                  <Save className="h-4 w-4" />
                  Save draft
                </Button>
                {step < 6 ? (
                  <Button onClick={goNext} className="bg-emerald-800 hover:bg-emerald-900" disabled={busy.saving || busy.submitting || !stepIsValid} type="button">
                    {busy.saving ? <span className="inline-flex items-center gap-2"><Loader2 className="h-4 w-4 animate-spin" />Saving…</span> : "Next"}
                  </Button>
                ) : (
                  <Button onClick={submit} disabled={busy.saving || busy.submitting} className="gap-2 bg-emerald-800 hover:bg-emerald-900" type="button">
                    {busy.submitting ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                    Submit application
                  </Button>
                )}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
      {revisionConflict.open && (session?.sessionToken || (session?.token && session?.dateOfBirth)) && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-lg rounded-lg bg-white p-6">
            <div className="text-lg font-semibold text-slate-950">Draft updated elsewhere</div>
            <div className="mt-2 text-sm text-slate-600">This application was updated from another device or browser session. Reload to avoid overwriting changes.</div>
            <div className="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
              <Button variant="outline" onClick={() => setRevisionConflict({ open: false })} type="button">Close</Button>
              <Button
                className="bg-emerald-800 hover:bg-emerald-900"
                onClick={async () => {
                  setRevisionConflict({ open: false });
                  await refreshDraftFromServer(session?.token ?? "", session?.dateOfBirth ?? "");
                  toast.success("Draft reloaded.");
                }}
                type="button"
              >
                Reload draft
              </Button>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}

function FieldGrid({ children }: { children: React.ReactNode }) {
  return <div className="grid gap-4 md:grid-cols-2">{children}</div>;
}

function Field({ label, value, onChange, type = "text", error }: { label: string; value?: string | number; onChange: (value: string) => void; type?: string; error?: string }) {
  return (
    <div className="space-y-2">
      <Label>{label}</Label>
      <Input type={type} value={value || ""} onChange={(e) => onChange(e.target.value)} aria-invalid={!!error} />
      {error && <div className="text-xs text-red-600">{error}</div>}
    </div>
  );
}

function SelectField({
  label,
  value,
  onChange,
  options,
  error,
}: {
  label: string;
  value?: string;
  onChange: (value: string) => void;
  options: { value: string; label: string }[] | string[];
  error?: string;
}) {
  const normalized = Array.isArray(options) && typeof options[0] === "string"
    ? (options as string[]).map((o) => ({ value: o, label: o }))
    : (options as { value: string; label: string }[]);

  return (
    <div className="space-y-2">
      <Label>{label}</Label>
      <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={value || ""} onChange={(e) => onChange(e.target.value)} aria-invalid={!!error}>
        <option value="">Select</option>
        {normalized.map((opt) => <option key={opt.value} value={opt.value}>{opt.label}</option>)}
      </select>
      {error && <div className="text-xs text-red-600">{error}</div>}
    </div>
  );
}

function Summary({ label, value }: { label: string; value: string }) {
  return <div className="rounded-lg border border-slate-200 p-4"><p className="text-xs uppercase text-slate-500">{label}</p><p className="mt-1 font-medium">{value}</p></div>;
}

function formatDocumentName(value: string) {
  return value.replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
}

function draftConsentChecked(draft: any): boolean {
  return Boolean(draft.__consent);
}
