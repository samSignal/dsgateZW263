type ApiOptions = {
  query?: Record<string, string | number | boolean | null | undefined>;
};

async function apiGet<T>(path: string, options: ApiOptions = {}): Promise<T> {
  const token = localStorage.getItem("token");
  const url = new URL(path, window.location.origin);
  if (options.query) {
    for (const [k, v] of Object.entries(options.query)) {
      if (v === null || v === undefined || v === "") continue;
      url.searchParams.set(k, String(v));
    }
  }

  const res = await fetch(url.toString(), {
    headers: {
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
  });

  if (!res.ok) {
    const text = await res.text().catch(() => "");
    throw new Error(text || `Request failed (${res.status})`);
  }

  return (await res.json()) as T;
}

export type StreamNativeRankRow = {
  student_id: number;
  student_number?: string | null;
  admission_number?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  student_name?: string | null;
  score?: number | null;
  rank?: number | null;
  gpa?: number | null;
  term_average?: number | null;
  stream_id?: number | null;
  stream_name?: string | null;
  form_id?: number | null;
  form_name?: string | null;
  category_id?: number | null;
  category_name?: string | null;
  class_id?: number | null;
  class_name?: string | null;
};

export const streamNativeApi = {
  rankings(params: {
    academic_year_id: number;
    term_id: number;
    type: "stream" | "form" | "category" | "subject_stream" | "subject_form" | "subject_category";
    id?: number;
    subject_id?: number;
    page?: number;
    per_page?: number;
  }) {
    return apiGet<{
      meta: {
        academic_year_id: number;
        term_id: number;
        type: string;
        id: number | null;
        subject_id: number | null;
        page: number;
        per_page: number;
        total: number;
        last_page: number;
      };
      data: StreamNativeRankRow[];
    }>("/api/stream-native/rankings", { query: params });
  },

  topPerformers(params: {
    academic_year_id: number;
    term_id: number;
    stream_id?: number;
    form_id?: number;
    category_id?: number;
    limit?: number;
  }) {
    return apiGet<{ data: StreamNativeRankRow[] }>("/api/stream-native/results/top-performers", { query: params });
  },

  atRisk(params: {
    academic_year_id: number;
    term_id: number;
    stream_id?: number;
    form_id?: number;
    category_id?: number;
    max_average?: number;
    limit?: number;
  }) {
    return apiGet<{ data: StreamNativeRankRow[]; max_average: number }>("/api/stream-native/results/at-risk", { query: params });
  },

  transcriptStudent(studentId: number) {
    return apiGet<any>(`/api/stream-native/transcript/student/${studentId}`);
  },

  transcriptMe() {
    return apiGet<any>("/api/stream-native/me/transcript");
  },

  studentTerm(studentId: number, params: { academic_year_id: number; term_id: number }) {
    return apiGet<any>(`/api/stream-native/results/student/${studentId}/term`, { query: params });
  },

  myTerm(params: { academic_year_id: number; term_id: number }) {
    return apiGet<any>("/api/stream-native/me/results/term", { query: params });
  },

  myTermAuto() {
    return apiGet<any>("/api/stream-native/me/results/term");
  },

  myYear(params?: { academic_year_id?: number }) {
    return apiGet<any>("/api/stream-native/me/results/year", { query: params });
  },

  progressionStatusStudent(studentId: number, params: { academic_year_id: number; term_id: number }) {
    return apiGet<any>(`/api/stream-native/progression/student/${studentId}/status`, { query: params });
  },

  progressionStatusMe(params: { academic_year_id: number; term_id: number }) {
    return apiGet<any>("/api/stream-native/me/progression/status", { query: params });
  },

  progressionStatusMeAuto() {
    return apiGet<any>("/api/stream-native/me/progression/status");
  },

  graduationMe(params?: { academic_year_id?: number }) {
    return apiGet<any>("/api/stream-native/me/graduation", { query: params });
  },

  parentChildren() {
    return apiGet<{ children: any[] }>("/api/parent/stream-native/children");
  },

  parentChildTerm(studentId: number, params?: { academic_year_id?: number; term_id?: number }) {
    return apiGet<any>(`/api/parent/stream-native/child/${studentId}/term`, { query: params });
  },

  parentChildTranscript(studentId: number) {
    return apiGet<any>(`/api/parent/stream-native/child/${studentId}/transcript`);
  },

  reportCardPreviewStudent(studentId: number, params: { academic_year_id: number; term_id: number }) {
    return apiGet<any>(`/api/stream-native/report-card/student/${studentId}/term`, { query: params });
  },
};
