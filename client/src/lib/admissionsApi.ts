export type AdmissionApplication = {
  id?: number;
  application_number?: string;
  tracking_token?: string;
  application_type: "new_intake" | "transfer";
  academic_year_id?: number | string;
  term_id?: number | string;
  applying_form_id?: number | string;
  student_first_name?: string;
  student_last_name?: string;
  gender?: "male" | "female" | "other" | "";
  date_of_birth?: string;
  birth_certificate_number?: string;
  student_national_id?: string;
  guardian_name?: string;
  guardian_national_id?: string;
  guardian_phone?: string;
  emergency_phone?: string;
  guardian_email?: string;
  address?: string;
  occupation?: string;
  grade7_school?: string;
  grade7_results?: string;
  previous_school_name?: string;
  current_form?: string;
  transfer_reason?: string;
  last_term_average?: string;
  reason_for_joining?: string;
  medical_information?: string;
  current_step?: number;
  status?: string;
  submitted_at?: string;
  remarks?: string;
  completion_percentage?: number;
  progress_percentage?: number;
};

export type AdmissionDocument = {
  id: number;
  document_type: string;
  file_name: string;
  file_path: string;
  uploaded_at?: string;
};

export type AdmissionNotification = {
  id: number;
  title: string;
  message: string;
  notification_channel: "system" | "email" | "whatsapp";
  notification_type: "info" | "success" | "warning" | "rejected";
  created_at: string;
};

const API_BASE = import.meta.env.VITE_LARAVEL_API_BASE ?? "/api";

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const isForm = options.body instanceof FormData;
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      ...(isForm ? {} : { "Content-Type": "application/json" }),
      Accept: "application/json",
      ...(options.headers ?? {}),
    },
    credentials: "include",
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || "Admissions request failed");
  }
  return data;
}

export const admissionsApi = {
  saveDraft(payload: AdmissionApplication) {
    return request<{ message: string; application_number: string; tracking_token: string; current_step: number; completion_percentage: number; continue_link: string }>("/admissions/draft", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
    submit(payload: AdmissionApplication) {
      return request<{ message: string; application_number: string; tracking_token: string; tracking_link: string; continue_link: string }>("/admissions/apply", {
        method: "POST",
        body: JSON.stringify(payload),
      });
    },
  resume(token: string, dateOfBirth?: string) {
    return request<{ application: AdmissionApplication; documents: AdmissionDocument[]; required_documents: string[]; resume_step: number }>(`/admissions/continue/${encodeURIComponent(token)}`, {
      method: "POST",
      body: JSON.stringify(dateOfBirth ? { date_of_birth: dateOfBirth } : {}),
    });
  },
  track(payload: { tracking_token?: string; application_number?: string; date_of_birth?: string }) {
    return request<{ application: AdmissionApplication; progress_percentage: number; timeline: { key: string; label: string; state: string }[]; documents: AdmissionDocument[]; document_checklist: { document_type: string; uploaded: boolean }[]; notifications: AdmissionNotification[] }>("/admissions/track", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
  upload(token: string, documentType: string, file: File) {
    const form = new FormData();
    form.append("document_type", documentType);
    form.append("document", file);
    return request<{ message: string; file_path: string }>(`/admissions/upload/${encodeURIComponent(token)}`, {
      method: "POST",
      body: form,
    });
  },
  listApplications(query = "") {
    return request<{ data: AdmissionApplication[] } | AdmissionApplication[]>(`/admissions-office/applications${query}`);
  },
  review(id: string | number) {
    return request<{ application: AdmissionApplication; documents: AdmissionDocument[]; notifications: AdmissionNotification[] }>(`/admissions-office/applications/${id}`);
  },
  updateStatus(id: string | number, status: string, remarks?: string) {
    return request<{ message: string }>(`/admissions-office/applications/${id}`, {
      method: "PATCH",
      body: JSON.stringify({ status, remarks }),
    });
  },
  requestDocuments(id: string | number, documents: string[], remarks?: string) {
    return request<{ message: string }>(`/admissions-office/applications/${id}/documents/request`, {
      method: "POST",
      body: JSON.stringify({ documents, remarks }),
    });
  },
  accept(id: string | number, remarks?: string) {
    return request<{ message: string }>(`/admissions-office/applications/${id}/accept`, {
      method: "POST",
      body: JSON.stringify({ remarks }),
    });
  },
  reject(id: string | number, remarks: string) {
    return request<{ message: string }>(`/admissions-office/applications/${id}/reject`, {
      method: "POST",
      body: JSON.stringify({ remarks }),
    });
  },
  enroll(id: string | number, remarks?: string) {
    return request<{ message: string; student_number: string }>(`/admissions-office/applications/${id}/enroll`, {
      method: "POST",
      body: JSON.stringify({ remarks }),
    });
  },
};

export const formatDocumentName = (value: string) =>
  value.replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
