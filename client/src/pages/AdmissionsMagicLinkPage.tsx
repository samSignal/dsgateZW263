import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { admissionsV2Api, AdmissionsV2Error } from "@/lib/admissionsV2Api";
import { setAdmissionsSession } from "@/lib/admissionsV2Session";
import { Loader2 } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { useLocation } from "wouter";

function getQueryParam(name: string) {
  const url = new URL(window.location.href);
  return url.searchParams.get(name);
}

export default function AdmissionsMagicLinkPage({ mode }: { mode: "verify" | "recover" }) {
  const [, setLocation] = useLocation();
  const code = useMemo(() => getQueryParam("code") ?? "", []);
  const [state, setState] = useState<{ status: "loading" | "done" | "error"; message?: string }>({ status: "loading" });

  useEffect(() => {
    (async () => {
      try {
        if (!code) {
          setState({ status: "error", message: "Missing verification code." });
          return;
        }

        const res = await admissionsV2Api.verificationConsume(code);
        if (res?.type === "recovery") {
          setAdmissionsSession({
            token: res.issued_token,
            tokenLast4: res.token_last4,
            dateOfBirth: "",
            applicationId: res.application_id,
            applicationNumber: res.application_number,
            sessionToken: res.session_token,
            sessionExpiresAt: res.session_expires_at,
          });
          setState({ status: "done", message: "Recovery completed. Redirecting…" });
          window.setTimeout(() => setLocation("/admissions/apply"), 600);
          return;
        }

        if (res?.type === "stepup") {
          setState({ status: "done", message: "Verification completed. You can now continue." });
          window.setTimeout(() => setLocation("/admissions/apply"), 600);
          return;
        }

        setState({ status: "error", message: "This link is invalid or has expired." });
      } catch (e) {
        if (e instanceof AdmissionsV2Error) {
          setState({ status: "error", message: e.message });
        } else {
          setState({ status: "error", message: e instanceof Error ? e.message : "Verification failed." });
        }
      }
    })();
  }, [code, mode, setLocation]);

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container flex min-h-screen items-center justify-center py-10">
        <Card className="w-full max-w-xl rounded-lg border-emerald-900/10">
          <CardHeader>
            <CardTitle>{mode === "recover" ? "Recover access" : "Verify request"}</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-slate-700">
            {state.status === "loading" && (
              <div className="flex items-center gap-2">
                <Loader2 className="h-4 w-4 animate-spin" />
                Processing secure link…
              </div>
            )}
            {state.status === "done" && <div>{state.message}</div>}
            {state.status === "error" && <div className="text-red-700">{state.message}</div>}
          </CardContent>
        </Card>
      </div>
    </main>
  );
}
