import { getValidAdmissionsSession, touchAdmissionsSession } from "@/lib/admissionsV2Session";

type ApiOk<T> = { ok: true; data: T; meta?: any };
type ApiFail = { ok: false; code: string; message: string; errors?: Record<string, string[]>; meta?: any };

export class AdmissionsV2Error extends Error {
  code: string;
  status: number;
  errors: Record<string, string[]>;
  meta: any;

  constructor(status: number, payload: ApiFail) {
    super(payload.message || "Admissions request failed");
    this.code = payload.code;
    this.status = status;
    this.errors = payload.errors ?? {};
    this.meta = payload.meta ?? {};
  }
}

const API_BASE = import.meta.env.VITE_LARAVEL_API_BASE ?? "/api";

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const isForm = options.body instanceof FormData;
  const sess = getValidAdmissionsSession();
  const headers = new Headers(options.headers ?? undefined);
  headers.set("Accept", "application/json");
  if (!isForm && !headers.has("Content-Type")) headers.set("Content-Type", "application/json");
  if (sess?.sessionToken) headers.set("X-Admissions-Session", sess.sessionToken);
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
    credentials: "include",
  });

  const data = (await response.json().catch(() => ({}))) as ApiOk<T> | ApiFail | any;
  if (!response.ok) {
    if (data && data.ok === false && typeof data.code === "string") {
      throw new AdmissionsV2Error(response.status, data as ApiFail);
    }
    throw new Error((data && data.message) || "Admissions request failed");
  }

  if (data && data.ok === true) {
    touchAdmissionsSession();
    return (data as ApiOk<T>).data;
  }
  touchAdmissionsSession();
  return data as T;
}

export type CatalogAcademicYear = { id: number; name: string; start_date: string; end_date: string };
export type CatalogForm = { id: number; name: string; level: number };
export type CatalogCategory = { id: number; name: string; code: string; description?: string | null };

export type V2ApplicationType = "new_intake" | "transfer";
export type V2Gender = "male" | "female" | "other" | "";

export type V2DraftApplication = {
  id: number;
  application_number: string;
  application_type: V2ApplicationType;
  academic_year_id: number | null;
  term_id: number | null;
  applying_form_id: number | null;
  preferred_category_id: number | null;
  student_first_name: string | null;
  student_last_name: string | null;
  gender: V2Gender | null;
  date_of_birth: string;
  birth_certificate_number: string | null;
  student_national_id: string | null;
  guardian_name: string | null;
  guardian_national_id: string | null;
  guardian_phone: string | null;
  guardian_email: string | null;
  emergency_phone: string | null;
  address: string | null;
  occupation: string | null;
  grade7_school: string | null;
  grade7_results: string | null;
  previous_school_name: string | null;
  current_form: string | null;
  transfer_reason: string | null;
  last_term_average: string | null;
  reason_for_joining: string | null;
  medical_information: string | null;
  lifecycle_state: string | null;
  status: string | null;
  current_step: number;
  draft_revision: number;
  expires_at: string | null;
  archived_at: string | null;
  archived_reason?: string | null;
  locked_at: string | null;
};

export type V2Document = {
  id: number;
  document_type: string;
  file_name: string;
  file_path: string;
  version: number;
  uploaded_at: string | null;
};

export const admissionsV2Api = {
  catalogAcademicYears() {
    return request<{ academic_years: CatalogAcademicYear[] }>("/admissions/v2/catalog/academic-years");
  },
  catalogForms() {
    return request<{ forms: CatalogForm[] }>("/admissions/v2/catalog/forms");
  },
  catalogCategories() {
    return request<{ categories: CatalogCategory[] }>("/admissions/v2/catalog/categories");
  },

  sessionStart(payload: { token: string; date_of_birth: string }) {
    return request<{
      application_id: number;
      application_number: string;
      session_token: string;
      expires_at: string;
      stepup_until: string | null;
    }>("/admissions/v2/session/start", { method: "POST", body: JSON.stringify(payload) });
  },

  stepupRequest() {
    return request<{ message: string }>("/admissions/v2/verification/stepup/request", { method: "POST", body: JSON.stringify({}) });
  },

  stepupRequestFor(action: string) {
    return request<{ message: string }>("/admissions/v2/verification/stepup/request", { method: "POST", body: JSON.stringify({ action }) });
  },

  verificationConsume(code: string) {
    return request<any>("/admissions/v2/verification/consume", { method: "POST", body: JSON.stringify({ code }) });
  },

  draftStart(payload: {
    application_type: V2ApplicationType;
    academic_year_id: number;
    applying_form_id: number;
    preferred_category_id: number;
    student_first_name: string;
    student_last_name: string;
    gender?: Exclude<V2Gender, "">;
    date_of_birth: string;
    birth_certificate_number: string;
    student_national_id?: string;
    guardian_name?: string;
    guardian_national_id?: string;
    guardian_phone?: string;
    guardian_email?: string;
    emergency_phone?: string;
    address?: string;
    occupation?: string;
  }) {
    return request<{
      application_id: number;
      application_number: string;
      issued_token: string;
      token_last4: string;
      lifecycle_state: string;
      draft_revision: number;
      expires_at: string;
    }>("/admissions/v2/draft/start", { method: "POST", body: JSON.stringify(payload) });
  },

  draftGet(payload: { token?: string; date_of_birth?: string } = {}) {
    return request<{ application: V2DraftApplication; documents: V2Document[] }>("/admissions/v2/draft/get", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },

  draftSave(payload: Partial<V2DraftApplication> & { token?: string; date_of_birth?: string; draft_revision: number; current_step: number }) {
    return request<{
      application_id: number;
      application_number: string;
      draft_revision: number;
      current_step: number;
      expires_at: string | null;
      issued_token?: string | null;
      token_last4?: string | null;
    }>("/admissions/v2/draft/save", { method: "POST", body: JSON.stringify(payload) });
  },

  uploadDocument(payload: { token?: string; date_of_birth?: string; document_type: string; document: File }) {
    const form = new FormData();
    form.append("document_type", payload.document_type);
    form.append("document", payload.document);
    if (payload.token) form.append("token", payload.token);
    if (payload.date_of_birth) form.append("date_of_birth", payload.date_of_birth);
    return request<{
      application_id: number;
      document_id: number;
      document_type: string;
      version: number;
      file_path: string;
      required_documents: string[];
    }>("/admissions/v2/documents/upload", { method: "POST", body: form });
  },

  submit(payload: { token?: string; date_of_birth?: string } = {}, idempotencyKey: string) {
    return request<{
      application_id: number;
      application_number: string;
      submission_id: number;
      submitted_at: string;
      issued_token: string;
      token_last4: string;
      lifecycle_state: string;
    }>("/admissions/v2/submit", {
      method: "POST",
      body: JSON.stringify(payload),
      headers: { "X-Idempotency-Key": idempotencyKey },
    });
  },

  track(payload: { token?: string; date_of_birth?: string } = {}) {
    return request<{
      application: {
        id: number;
        application_number: string;
        application_type: V2ApplicationType;
        lifecycle_state: string | null;
        status: string;
        student_first_name: string | null;
        student_last_name: string | null;
        date_of_birth: string;
        academic_year_id: number;
        applying_form_id: number;
        preferred_category_id: number;
        submitted_at: string | null;
        expires_at: string | null;
        archived_at?: string | null;
        archived_reason?: string | null;
      };
      progress_percentage: number;
      documents: V2Document[];
      required_documents: string[];
      document_checklist: { document_type: string; uploaded: boolean }[];
      notifications: { id: number; title: string; message: string; notification_channel: string; notification_type: string; created_at: string }[];
    }>("/admissions/v2/track", { method: "POST", body: JSON.stringify(payload) });
  },

  recoveryRequest(payload: { academic_year_id: number; birth_certificate_number: string; guardian_email?: string; guardian_phone?: string }) {
    return request<{ message: string }>("/admissions/v2/recovery/request", { method: "POST", body: JSON.stringify(payload) });
  },
};
