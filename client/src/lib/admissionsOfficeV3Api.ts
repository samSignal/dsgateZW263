type ApiOk<T> = { ok: true; data: T; meta?: any };
type ApiFail = { ok: false; code: string; message: string; errors?: Record<string, string[]>; meta?: any };

export class AdmissionsOfficeV3Error extends Error {
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
    if (data && data.ok === false && typeof data.code === "string") throw new AdmissionsOfficeV3Error(res.status, data as ApiFail);
    throw new Error((data && data.message) || "Admissions office request failed");
  }
  if (data && data.ok === true) return (data as ApiOk<T>).data;
  return data as T;
}

export const admissionsOfficeV3Api = {
  activationExecutions(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v3/activation/executions${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  activationExecution(activationExecutionId: number) {
    return request<any>(`/admissions-office/v3/activation/executions/${activationExecutionId}`);
  },
  activationStart(payload: { provisional_enrollment_id: number; activation_package_id?: number; dry_run?: boolean; idempotency_key?: string }) {
    return request<any>(`/admissions-office/v3/activation/executions/start`, { method: "POST", body: JSON.stringify(payload) });
  },
  activationRollback(activationExecutionId: number, payload: { note?: string } = {}) {
    return request<any>(`/admissions-office/v3/activation/executions/${activationExecutionId}/rollback`, { method: "POST", body: JSON.stringify(payload) });
  },
  activationReconciliation(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v3/activation/reconciliation${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  activationReconcile(provisionalEnrollmentId: number) {
    return request<any>(`/admissions-office/v3/activation/reconciliation/${provisionalEnrollmentId}`, { method: "POST", body: JSON.stringify({}) });
  },
  activationHealth() {
    return request<any>(`/admissions-office/v3/activation/health`);
  },
  operationalExecutions(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v3/operational/executions${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  operationalExecution(operationalExecutionId: number) {
    return request<any>(`/admissions-office/v3/operational/executions/${operationalExecutionId}`);
  },
  operationalStart(payload: { provisional_enrollment_id: number; operational_activation_package_id?: number; dry_run?: boolean; idempotency_key?: string; services?: string[] }) {
    return request<any>(`/admissions-office/v3/operational/executions/start`, { method: "POST", body: JSON.stringify(payload) });
  },
  operationalRollback(operationalExecutionId: number, payload: { note?: string } = {}) {
    return request<any>(`/admissions-office/v3/operational/executions/${operationalExecutionId}/rollback`, { method: "POST", body: JSON.stringify(payload) });
  },
  operationalRetryStep(operationalExecutionId: number, stepOrder: number) {
    return request<any>(`/admissions-office/v3/operational/executions/${operationalExecutionId}/steps/${stepOrder}/retry`, { method: "POST", body: JSON.stringify({}) });
  },
  operationalReconciliation(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v3/operational/reconciliation${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  operationalReconcile(provisionalEnrollmentId: number) {
    return request<any>(`/admissions-office/v3/operational/reconciliation/${provisionalEnrollmentId}`, { method: "POST", body: JSON.stringify({}) });
  },
  operationalIncidents(params: Record<string, string | number | undefined> = {}) {
    const qs = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v === undefined || v === "") continue;
      qs.set(k, String(v));
    }
    return request<any>(`/admissions-office/v3/operational/incidents${qs.toString() ? `?${qs.toString()}` : ""}`);
  },
  operationalHealth() {
    return request<any>(`/admissions-office/v3/operational/health`);
  },
};
