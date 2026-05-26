type ApiOk<T> = { ok: true; data: T; meta?: any };
type ApiFail = { ok: false; code: string; message: string; errors?: Record<string, string[]>; meta?: any };

export class AdmissionsOfficeV2Error extends Error {
  code: string;
  status: number;
  errors: Record<string, string[]>;
  meta: any;

  constructor(status: number, payload: ApiFail) {
    super(payload.message || "Admissions office request failed");
    this.code = payload.code;
    this.status = status;
    this.errors = payload.errors ?? {};
    this.meta = payload.meta ?? {};
  }
}

const API_BASE = import.meta.env.VITE_LARAVEL_API_BASE ?? "/api";

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers ?? undefined);
  headers.set("Accept", "application/json");
  if (!headers.has("Content-Type") && !(options.body instanceof FormData)) headers.set("Content-Type", "application/json");

  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
    credentials: "include",
  });

  const data = (await res.json().catch(() => ({}))) as ApiOk<T> | ApiFail | any;
  if (!res.ok) {
    if (data && data.ok === false && typeof data.code === "string") throw new AdmissionsOfficeV2Error(res.status, data as ApiFail);
    throw new Error((data && data.message) || "Admissions office request failed");
  }
  if (data && data.ok === true) return (data as ApiOk<T>).data;
  return data as T;
}

export type OfficeQueueItem = {
  id: number;
  application_number: string;
  application_type: string;
  academic_year_id: number | null;
  applying_form_id: number | null;
  preferred_category_id: number | null;
  student_first_name: string | null;
  student_last_name: string | null;
  guardian_name: string | null;
  guardian_email: string | null;
  status: string;
  lifecycle_state: string | null;
  submitted_at: string | null;
  assigned_reviewer_id: number | null;
  assigned_reviewer_name?: string | null;
  age_days: number | null;
  is_stale?: boolean;
  pending_doc_requests?: number;
  duplicate_flags?: {
    has_flagged_link: boolean;
    has_merged_link: boolean;
    has_invalid_link: boolean;
    merged_related_count: number;
  };
};

export const admissionsOfficeV2Api = {
  queue(queue: string, params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<{ page: number; per_page: number; total: number; items: OfficeQueueItem[] }>(
      `/admissions-office/v2/queue/${encodeURIComponent(queue)}${qs.toString() ? `?${qs.toString()}` : ""}`
    );
  },

  metrics(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<{
      counts: Record<string, number>;
      average_review_age_days: number;
      pending_document_requests: number;
      duplicate_review_backlog: number;
      duplicate_link_backlog: number;
    }>(
      `/admissions-office/v2/metrics/summary${qs.toString() ? `?${qs.toString()}` : ""}`
    );
  },

  review(id: number) {
    return request<any>(`/admissions-office/v2/review/${id}`);
  },

  claim(id: number) {
    return request(`/admissions-office/v2/review/${id}/claim`, { method: "POST", body: JSON.stringify({}) });
  },

  release(id: number, payload: { reason?: string; force?: boolean } = {}) {
    return request(`/admissions-office/v2/review/${id}/release`, { method: "POST", body: JSON.stringify(payload) });
  },

  assign(id: number, payload: { assignee_user_id: number; reason: string }) {
    return request(`/admissions-office/v2/review/${id}/assign`, { method: "POST", body: JSON.stringify(payload) });
  },

  transition(id: number, payload: { to_state: string; reason: string }) {
    return request(`/admissions-office/v2/review/${id}/transition`, { method: "POST", body: JSON.stringify(payload) });
  },

  transitionWithNotify(
    id: number,
    payload: {
      to_state: string;
      reason: string;
      override?: boolean;
      force?: boolean;
      notify_applicant?: boolean;
      notify_title?: string;
      notify_message?: string;
      notify_template_key?: string;
    }
  ) {
    return request(`/admissions-office/v2/review/${id}/transition`, { method: "POST", body: JSON.stringify(payload) });
  },

  documentVerify(documentId: number) {
    return request(`/admissions-office/v2/documents/${documentId}/verify`, { method: "POST", body: JSON.stringify({}) });
  },
  documentReject(documentId: number, payload: { reason: string; notes?: string }) {
    return request(`/admissions-office/v2/documents/${documentId}/reject`, { method: "POST", body: JSON.stringify(payload) });
  },
  documentRequestReupload(documentId: number, payload: { reason: string; notes?: string; notify_applicant?: boolean }) {
    return request(`/admissions-office/v2/documents/${documentId}/request-reupload`, { method: "POST", body: JSON.stringify(payload) });
  },

  duplicateCandidates(applicationId: number) {
    return request<{ items: any[] }>(`/admissions-office/v2/duplicates/${applicationId}/candidates`);
  },
  duplicateCompare(applicationId: number, candidateId: number) {
    return request<any>(`/admissions-office/v2/duplicates/${applicationId}/compare/${candidateId}`);
  },
  duplicateLink(payload: { canonical_application_id: number; related_application_id: number; status: "flagged" | "merged" | "invalid"; reason: string }) {
    return request(`/admissions-office/v2/duplicates/link`, { method: "POST", body: JSON.stringify(payload) });
  },
  duplicateReopen(payload: { canonical_application_id: number; related_application_id: number; reason: string }) {
    return request(`/admissions-office/v2/duplicates/reopen`, { method: "POST", body: JSON.stringify(payload) });
  },

  reviewers() {
    return request<{ items: Array<{ id: number; name: string; email: string; role: string; roles: string[] }> }>(`/admissions-office/v2/reviewers`);
  },

  messageTemplates() {
    return request<{ items: Array<{ key: string; title: string; message: string }> }>(`/admissions-office/v2/messages/templates`);
  },

  addInternalNote(applicationId: number, message: string) {
    return request(`/admissions-office/v2/messages/${applicationId}/internal-note`, { method: "POST", body: JSON.stringify({ message }) });
  },
  sendApplicantMessage(applicationId: number, payload: { title?: string; message?: string; template_key?: string }) {
    return request(`/admissions-office/v2/messages/${applicationId}/applicant`, { method: "POST", body: JSON.stringify(payload) });
  },
};
