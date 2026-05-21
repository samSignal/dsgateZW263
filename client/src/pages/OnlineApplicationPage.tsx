import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { admissionsApi, AdmissionApplication, formatDocumentName } from "@/lib/admissionsApi";
import { CheckCircle2, FileUp, Save, Send } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";

const steps = ["Application Type", "Student Information", "Guardian Information", "Academic Background", "Documents Upload", "Review & Submit"];
const initialForm: AdmissionApplication = { application_type: "new_intake", current_step: 1, gender: "" };
const documentTypes = ["birth_certificate", "passport_photo", "grade7_report", "latest_report", "transfer_letter", "discipline_record", "medical_record"];

export default function OnlineApplicationPage() {
  const [step, setStep] = useState(1);
  const [form, setForm] = useState<AdmissionApplication>(() => {
    const saved = localStorage.getItem("dgi-admission-draft");
    return saved ? { ...initialForm, ...JSON.parse(saved) } : initialForm;
  });
  const [token, setToken] = useState(form.tracking_token || "");
  const [saving, setSaving] = useState(false);

  const completion = useMemo(() => Math.round((step / steps.length) * 100), [step]);

  useEffect(() => {
    const next = { ...form, current_step: step, completion_percentage: completion };
    localStorage.setItem("dgi-admission-draft", JSON.stringify(next));
  }, [form, step, completion]);

  const update = (field: keyof AdmissionApplication, value: string | number) => {
    setForm((current) => ({ ...current, [field]: value }));
  };

  const saveDraft = async () => {
    setSaving(true);
    try {
      const result = await admissionsApi.saveDraft({ ...form, tracking_token: token || form.tracking_token, current_step: step });
      setToken(result.tracking_token);
      setForm((current) => ({ ...current, tracking_token: result.tracking_token, application_number: result.application_number }));
      toast.success(`Draft saved. Token: ${result.tracking_token}`);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Draft save failed");
    } finally {
      setSaving(false);
    }
  };

  const submit = async () => {
    setSaving(true);
    try {
      if (!form.applying_form_id) {
        toast.error('Applying Form ID is required before submission.');
        return;
      }
      if (!form.academic_year_id) {
        toast.error('Academic Year ID is required before submission.');
        return;
      }
      if (!token && !form.tracking_token) {
        toast.error('Save the draft first to receive a tracking token.');
        return;
      }
      // Prepare payload with correct backend field names
      const payload = {
        ...form,
        applying_form_id: Number(form.applying_form_id),
        // Backend expects `academic_year` instead of `academic_year_id`
        academic_year: form.academic_year_id ? Number(form.academic_year_id) : undefined,
      };
      const result = await admissionsApi.submit(payload);
      setToken(result.tracking_token);
      setForm((current) => ({ ...current, tracking_token: result.tracking_token, application_number: result.application_number }));
      localStorage.removeItem("dgi-admission-draft");
      toast.success("Application submitted successfully");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Submission failed");
    } finally {
      setSaving(false);
    }
  };

  const upload = async (documentType: string, file?: File) => {
    const trackingToken = token || form.tracking_token;
    if (!trackingToken) {
      toast.error("Save the draft first to receive a tracking token");
      return;
    }
    if (!file) return;
    try {
      await admissionsApi.upload(trackingToken, documentType, file);
      toast.success(`${formatDocumentName(documentType)} uploaded`);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Upload failed");
    }
  };

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container py-8">
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <p className="text-sm font-medium uppercase tracking-wide text-amber-600">Online Admissions</p>
            <h1 className="text-3xl font-semibold tracking-normal text-emerald-950">Application wizard</h1>
          </div>
          {token && (
            <div className="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-emerald-950">
              Token: <span className="font-semibold">{token}</span>
            </div>
          )}
        </div>

        <div className="mb-6">
          <div className="h-2 overflow-hidden rounded-full bg-emerald-100">
            <div className="h-full rounded-full bg-emerald-800 transition-all" style={{ width: `${completion}%` }} />
          </div>
          <div className="mt-3 grid gap-2 md:grid-cols-6">
            {steps.map((item, index) => (
              <button key={item} onClick={() => setStep(index + 1)} className={`rounded-md border px-2 py-2 text-xs ${step === index + 1 ? "border-emerald-800 bg-emerald-800 text-white" : "border-slate-200 text-slate-600"}`}>
                {index + 1}. {item}
              </button>
            ))}
          </div>
        </div>

        <Card className="rounded-lg border-emerald-900/10">
          <CardHeader>
            <CardTitle>{steps[step - 1]}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-5">
            {step === 1 && (
              <div className="grid gap-4 md:grid-cols-2">
                {[
                  ["new_intake", "New Form 1 Intake", "For Grade 7 learners entering high school."],
                  ["transfer", "Transfer Student", "For learners moving from another school."],
                ].map(([value, title, copy]) => (
                  <button key={value} onClick={() => update("application_type", value)} className={`rounded-lg border p-5 text-left ${form.application_type === value ? "border-emerald-800 bg-emerald-50" : "border-slate-200"}`}>
                    <CheckCircle2 className="mb-3 h-6 w-6 text-emerald-800" />
                    <p className="font-semibold">{title}</p>
                    <p className="mt-1 text-sm text-slate-600">{copy}</p>
                  </button>
                ))}
              </div>
            )}

            {step === 2 && (
              <FieldGrid>
                <Field label="First Name" value={form.student_first_name} onChange={(v) => update("student_first_name", v)} />
                <Field label="Last Name" value={form.student_last_name} onChange={(v) => update("student_last_name", v)} />
                <SelectField label="Gender" value={form.gender} onChange={(v) => update("gender", v)} options={["male", "female", "other"]} />
                <Field label="Date of Birth" type="date" value={form.date_of_birth} onChange={(v) => update("date_of_birth", v)} />
                <Field label="Birth Certificate Number" value={form.birth_certificate_number} onChange={(v) => update("birth_certificate_number", v)} />
                <Field label="Student National ID (optional)" value={form.student_national_id} onChange={(v) => update("student_national_id", v)} />
                <Field label="Academic Year ID" type="number" value={form.academic_year_id} onChange={(v) => update("academic_year_id", v)} />
                <Field label="Applying Form ID" type="number" value={form.applying_form_id} onChange={(v) => update("applying_form_id", v)} />
                <Field label="Term ID (optional)" type="number" value={form.term_id} onChange={(v) => update("term_id", v)} />
              </FieldGrid>
            )}

            {step === 3 && (
              <FieldGrid>
                <Field label="Guardian Name" value={form.guardian_name} onChange={(v) => update("guardian_name", v)} />
                <Field label="Guardian National ID" value={form.guardian_national_id} onChange={(v) => update("guardian_national_id", v)} />
                <Field label="Guardian Phone" value={form.guardian_phone} onChange={(v) => update("guardian_phone", v)} />
                <Field label="Emergency Phone" value={form.emergency_phone} onChange={(v) => update("emergency_phone", v)} />
                <Field label="Guardian Email" type="email" value={form.guardian_email} onChange={(v) => update("guardian_email", v)} />
                <Field label="Occupation" value={form.occupation} onChange={(v) => update("occupation", v)} />
                <div className="md:col-span-2">
                  <Label>Address</Label>
                  <Textarea value={form.address || ""} onChange={(e) => update("address", e.target.value)} />
                </div>
              </FieldGrid>
            )}

            {step === 4 && (
              <FieldGrid>
                {form.application_type === "new_intake" ? (
                  <>
                    <Field label="Grade 7 School" value={form.grade7_school} onChange={(v) => update("grade7_school", v)} />
                    <Field label="Grade 7 Results" value={form.grade7_results} onChange={(v) => update("grade7_results", v)} />
                  </>
                ) : (
                  <>
                    <Field label="Previous School" value={form.previous_school_name} onChange={(v) => update("previous_school_name", v)} />
                    <Field label="Current Form" value={form.current_form} onChange={(v) => update("current_form", v)} />
                    <Field label="Last Term Average" value={form.last_term_average} onChange={(v) => update("last_term_average", v)} />
                    <div className="md:col-span-2"><Label>Transfer Reason</Label><Textarea value={form.transfer_reason || ""} onChange={(e) => update("transfer_reason", e.target.value)} /></div>
                  </>
                )}
                <div className="md:col-span-2"><Label>Reason for Joining</Label><Textarea value={form.reason_for_joining || ""} onChange={(e) => update("reason_for_joining", e.target.value)} /></div>
                <div className="md:col-span-2"><Label>Medical Information</Label><Textarea value={form.medical_information || ""} onChange={(e) => update("medical_information", e.target.value)} /></div>
              </FieldGrid>
            )}

            {step === 5 && (
              <div className="grid gap-3 md:grid-cols-2">
                {documentTypes.map((type) => (
                  <label key={type} className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-4">
                    <span className="flex items-center gap-2 text-sm font-medium"><FileUp className="h-4 w-4 text-emerald-800" />{formatDocumentName(type)}</span>
                    <Input className="max-w-56" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" onChange={(e) => upload(type, e.target.files?.[0])} />
                  </label>
                ))}
              </div>
            )}

            {step === 6 && (
              <div className="grid gap-4 md:grid-cols-2">
                <Summary label="Student" value={`${form.student_first_name || ""} ${form.student_last_name || ""}`} />
                <Summary label="Application Type" value={form.application_type === "transfer" ? "Transfer Student" : "New Form 1 Intake"} />
                <Summary label="Guardian" value={form.guardian_name || "-"} />
                <Summary label="Guardian Email" value={form.guardian_email || "-"} />
                <Summary label="Birth Certificate" value={form.birth_certificate_number || "-"} />
                <Summary label="Application Number" value={form.application_number || "Generated after saving"} />
              </div>
            )}

            <div className="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-between">
              <Button variant="outline" onClick={() => setStep(Math.max(1, step - 1))} disabled={step === 1}>Back</Button>
              <div className="flex flex-col gap-3 sm:flex-row">
                <Button variant="outline" onClick={saveDraft} disabled={saving} className="gap-2"><Save className="h-4 w-4" />Save & continue later</Button>
                {step < 6 ? <Button onClick={() => setStep(Math.min(6, step + 1))} className="bg-emerald-800 hover:bg-emerald-900">Next</Button> : <Button onClick={submit} disabled={saving} className="gap-2 bg-emerald-800 hover:bg-emerald-900"><Send className="h-4 w-4" />Final submit</Button>}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </main>
  );
}

function FieldGrid({ children }: { children: React.ReactNode }) {
  return <div className="grid gap-4 md:grid-cols-2">{children}</div>;
}

function Field({ label, value, onChange, type = "text" }: { label: string; value?: string | number; onChange: (value: string) => void; type?: string }) {
  return <div className="space-y-2"><Label>{label}</Label><Input type={type} value={value || ""} onChange={(e) => onChange(e.target.value)} /></div>;
}

function SelectField({ label, value, onChange, options }: { label: string; value?: string; onChange: (value: string) => void; options: string[] }) {
  return <div className="space-y-2"><Label>{label}</Label><select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={value || ""} onChange={(e) => onChange(e.target.value)}><option value="">Select</option>{options.map((option) => <option key={option} value={option}>{option}</option>)}</select></div>;
}

function Summary({ label, value }: { label: string; value: string }) {
  return <div className="rounded-lg border border-slate-200 p-4"><p className="text-xs uppercase text-slate-500">{label}</p><p className="mt-1 font-medium">{value}</p></div>;
}
