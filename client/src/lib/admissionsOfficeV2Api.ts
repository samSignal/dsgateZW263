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
  time_in_state_days?: number | null;
  total_review_days?: number | null;
  is_overdue?: boolean;
  days_to_intake_close?: number | null;
  approaching_intake_close?: boolean;
  duplicate_risk_score?: number;
  duplicate_cluster_size?: number;
  reviewer_active_load?: number;
  urgency_score?: number;
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
      overdue_count: number;
      approaching_intake_close_count: number;
    }>(
      `/admissions-office/v2/metrics/summary${qs.toString() ? `?${qs.toString()}` : ""}`
    );
  },

  analyticsSummary(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/analytics/summary${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  analyticsReviewers(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/analytics/reviewers${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  analyticsFunnel(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/analytics/funnel${qs.toString() ? `?${qs.toString()}` : ""}`);
  },

  workloadAlerts(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/workload/alerts${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  workloadReviewers(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/workload/reviewers${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  workloadRecommendations(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/workload/recommendations${qs.toString() ? `?${qs.toString()}` : ""}`);
  },

  executiveSummary(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/summary${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveReviewerPerformance(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/reviewer-performance${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveQueueAging(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/queue-aging${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveIntakeProgression(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/intake-progression${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveDocumentsBacklog(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/documents-backlog${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveSlaBreaches(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/sla-breaches${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveThroughputTrends(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/throughput-trends${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveDuplicateTrends(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/duplicate-trends${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveDeliveryHealth(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/delivery-health${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  executiveWorkloadImbalance(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/executive/workload-imbalance${qs.toString() ? `?${qs.toString()}` : ""}`);
  },

  offersQueue(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/offers/queue${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  offerIssue(applicationId: number, payload: { force?: boolean; justification?: string; escalation_note?: string } = {}) {
    return request<any>(`/admissions-office/v2/offers/${applicationId}/issue`, { method: "POST", body: JSON.stringify(payload) });
  },
  offerHold(offerId: number, payload: { reason: string; force?: boolean; escalation_note?: string }) {
    return request<any>(`/admissions-office/v2/offers/${offerId}/hold`, { method: "POST", body: JSON.stringify(payload) });
  },
  offerRegenerateLetter(offerId: number) {
    return request<any>(`/admissions-office/v2/offers/${offerId}/regenerate-letter`, { method: "POST", body: JSON.stringify({}) });
  },

  provision(applicationId: number, payload: { idempotency_key?: string } = {}) {
    return request<any>(`/admissions-office/v2/provision/${applicationId}`, { method: "POST", body: JSON.stringify(payload) });
  },
  provisionalWorkspace(applicationId: number) {
    return request<any>(`/admissions-office/v2/provision/${applicationId}/workspace`);
  },
  onboardingTaskComplete(taskId: number, payload: { note?: string } = {}) {
    return request<any>(`/admissions-office/v2/onboarding/tasks/${taskId}/complete`, { method: "POST", body: JSON.stringify(payload) });
  },
  allocationAllocate(provisionalEnrollmentId: number, payload: { stream_id?: number; force?: boolean; justification?: string; escalation_note?: string } = {}) {
    return request<any>(`/admissions-office/v2/allocations/${provisionalEnrollmentId}/allocate`, { method: "POST", body: JSON.stringify(payload) });
  },
  allocationOverride(provisionalEnrollmentId: number, payload: { stream_id: number; force?: boolean; justification: string; escalation_note?: string }) {
    return request<any>(`/admissions-office/v2/allocations/${provisionalEnrollmentId}/override`, { method: "POST", body: JSON.stringify(payload) });
  },
  activationQueues(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/activation/queues${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  activationSummary(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/activation/summary${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  activationReadiness(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/activation/readiness${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  activationBlockers(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/activation/blockers${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  activationEvaluate(provisionalEnrollmentId: number) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/evaluate`, { method: "POST", body: JSON.stringify({}) });
  },
  activationApprove(provisionalEnrollmentId: number, payload: { justification: string; force?: boolean; escalation_note?: string }) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/approve`, { method: "POST", body: JSON.stringify(payload) });
  },
  activationReject(provisionalEnrollmentId: number, payload: { justification: string }) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/reject`, { method: "POST", body: JSON.stringify(payload) });
  },
  activationRemediate(provisionalEnrollmentId: number, payload: { justification: string }) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/remediate`, { method: "POST", body: JSON.stringify(payload) });
  },
  activationEscalate(provisionalEnrollmentId: number, payload: { justification: string; force?: boolean; escalation_note?: string }) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/escalate`, { method: "POST", body: JSON.stringify(payload) });
  },
  activationRevoke(provisionalEnrollmentId: number, payload: { target_event_id: number; revocation_note: string; stage?: string }) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/revoke`, { method: "POST", body: JSON.stringify(payload) });
  },
  activationResolveBlock(blockId: number) {
    return request<any>(`/admissions-office/v2/activation/blocks/${blockId}/resolve`, { method: "POST", body: JSON.stringify({}) });
  },
  activationPlanBuild(provisionalEnrollmentId: number) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/plan/build`, { method: "POST", body: JSON.stringify({}) });
  },
  activationPlanValidate(provisionalEnrollmentId: number) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/plan/validate`, { method: "POST", body: JSON.stringify({}) });
  },
  activationSimulate(provisionalEnrollmentId: number) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/simulate`, { method: "POST", body: JSON.stringify({}) });
  },
  activationPackageGenerate(provisionalEnrollmentId: number) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/packages/generate`, { method: "POST", body: JSON.stringify({}) });
  },
  activationPackages(provisionalEnrollmentId: number) {
    return request<any>(`/admissions-office/v2/activation/${provisionalEnrollmentId}/packages`);
  },
  activationPackage(activationPackageId: number) {
    return request<any>(`/admissions-office/v2/activation/packages/${activationPackageId}`);
  },
  activationSimulationSummary(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/activation/simulation/summary${qs.toString() ? `?${qs.toString()}` : ""}`);
  },

  incidents(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/incidents${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  incidentCreate(payload: { incident_type: string; severity: "low" | "medium" | "high" | "critical"; subject_type: "application" | "reviewer" | "system" | "notification"; subject_id?: number; meta?: Record<string, any> }) {
    return request<any>(`/admissions-office/v2/incidents`, { method: "POST", body: JSON.stringify(payload) });
  },
  incidentResolve(incidentId: number, payload: { resolution_note: string }) {
    return request<any>(`/admissions-office/v2/incidents/${incidentId}/resolve`, { method: "POST", body: JSON.stringify(payload) });
  },

  qualityFlags(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v2/quality/flags${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  qualityRun(payload: { window_days?: number } = {}) {
    return request<any>(`/admissions-office/v2/quality/run`, { method: "POST", body: JSON.stringify(payload) });
  },
  qualityAcknowledge(flagId: number) {
    return request<any>(`/admissions-office/v2/quality/flags/${flagId}/acknowledge`, { method: "POST", body: JSON.stringify({}) });
  },
  qualityResolve(flagId: number, payload: { resolution_note: string }) {
    return request<any>(`/admissions-office/v2/quality/flags/${flagId}/resolve`, { method: "POST", body: JSON.stringify(payload) });
  },

  automationRun(payload: { dry_run?: boolean } = {}) {
    return request<any>(`/admissions-office/v2/automation/run`, { method: "POST", body: JSON.stringify(payload) });
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
      override_reason?: string;
      escalation_note?: string;
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
  duplicateCluster(applicationId: number) {
    return request<any>(`/admissions-office/v2/duplicates/${applicationId}/cluster`);
  },
  duplicateInvestigationNote(applicationId: number, message: string) {
    return request<any>(`/admissions-office/v2/duplicates/${applicationId}/investigation-note`, { method: "POST", body: JSON.stringify({ message }) });
  },
  duplicateLink(payload: { canonical_application_id: number; related_application_id: number; status: "flagged" | "merged" | "invalid"; reason: string; decision_notes?: string }) {
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
  messagePreview(payload: { application_id: number; template_key?: string; title?: string; message?: string }) {
    return request<any>(`/admissions-office/v2/messages/preview`, { method: "POST", body: JSON.stringify(payload) });
  },
  messageResend(applicationId: number, notificationId: number) {
    return request<any>(`/admissions-office/v2/messages/${applicationId}/resend/${notificationId}`, { method: "POST", body: JSON.stringify({}) });
  },

  addInternalNote(applicationId: number, message: string) {
    return request(`/admissions-office/v2/messages/${applicationId}/internal-note`, { method: "POST", body: JSON.stringify({ message }) });
  },
  sendApplicantMessage(applicationId: number, payload: { title?: string; message?: string; template_key?: string }) {
    return request(`/admissions-office/v2/messages/${applicationId}/applicant`, { method: "POST", body: JSON.stringify(payload) });
  },

  recentViews() {
    return request<{ items: any[] }>(`/admissions-office/v2/productivity/recent`);
  },
  bookmarks() {
    return request<{ items: any[] }>(`/admissions-office/v2/productivity/bookmarks`);
  },
  bookmarkToggle(payload: { application_id: number; note?: string }) {
    return request<{ bookmarked: boolean }>(`/admissions-office/v2/productivity/bookmarks/toggle`, { method: "POST", body: JSON.stringify(payload) });
  },
  savedFilters() {
    return request<{ items: any[] }>(`/admissions-office/v2/productivity/filters`);
  },
  savedFiltersUpsert(payload: { name: string; filters: Record<string, any> }) {
    return request(`/admissions-office/v2/productivity/filters`, { method: "POST", body: JSON.stringify(payload) });
  },
  savedFiltersDelete(filterId: number) {
    return request(`/admissions-office/v2/productivity/filters/${filterId}`, { method: "DELETE" });
  },

  batchAssign(payload: { application_ids: number[]; assignee_user_id: number; reason: string }) {
    return request(`/admissions-office/v2/review/batch/assign`, { method: "POST", body: JSON.stringify(payload) });
  },
};
