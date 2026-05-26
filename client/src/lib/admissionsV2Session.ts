export type AdmissionsV2Session = {
  token: string;
  dateOfBirth: string;
  applicationId?: number;
  applicationNumber?: string;
  tokenLast4?: string;
  sessionToken?: string;
  sessionExpiresAt?: string;
  stepupUntil?: string;
  lastActiveAt?: string;
};

const KEY = "dgi-admissions-v2-session";

export function getAdmissionsSession(): AdmissionsV2Session | null {
  const raw = sessionStorage.getItem(KEY);
  if (!raw) return null;
  try {
    const parsed = JSON.parse(raw) as AdmissionsV2Session;
    if (!parsed?.token || !parsed?.dateOfBirth) return null;
    return parsed;
  } catch {
    return null;
  }
}

export function setAdmissionsSession(next: AdmissionsV2Session): void {
  sessionStorage.setItem(KEY, JSON.stringify({ ...next, lastActiveAt: new Date().toISOString() }));
}

export function clearAdmissionsSession(): void {
  sessionStorage.removeItem(KEY);
}

export function getValidAdmissionsSession(): AdmissionsV2Session | null {
  const s = getAdmissionsSession();
  if (!s) return null;
  if (s.sessionExpiresAt) {
    const expires = Date.parse(s.sessionExpiresAt);
    if (!Number.isNaN(expires) && Date.now() > expires) {
      return { ...s, sessionToken: undefined, sessionExpiresAt: undefined, stepupUntil: undefined };
    }
  }
  if (s.lastActiveAt) {
    const last = Date.parse(s.lastActiveAt);
    if (!Number.isNaN(last)) {
      const idleMinutes = (Date.now() - last) / 60000;
      if (idleMinutes > 30) return { ...s, sessionToken: undefined, sessionExpiresAt: undefined, stepupUntil: undefined };
    }
  }
  return s;
}

export function touchAdmissionsSession(): void {
  const s = getAdmissionsSession();
  if (!s) return;
  sessionStorage.setItem(KEY, JSON.stringify({ ...s, lastActiveAt: new Date().toISOString() }));
}
