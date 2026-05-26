import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { admissionsV2Api, AdmissionsV2Error } from "@/lib/admissionsV2Api";
import { setAdmissionsSession } from "@/lib/admissionsV2Session";
import { RotateCcw } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { useLocation } from "wouter";

export default function ContinueApplicationPage() {
  const [token, setToken] = useState("");
  const [dob, setDob] = useState("");
  const [loading, setLoading] = useState(false);
  const [, setLocation] = useLocation();

  const resume = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    try {
      if (!token.trim() || !dob) {
        toast.error("Enter your token and the learner’s date of birth.");
        return;
      }
      const s = await admissionsV2Api.sessionStart({ token: token.toUpperCase(), date_of_birth: dob });
      const res = await admissionsV2Api.draftGet({ token: token.toUpperCase(), date_of_birth: dob });
      setAdmissionsSession({
        token: token.toUpperCase(),
        dateOfBirth: dob,
        applicationId: res.application.id,
        applicationNumber: res.application.application_number,
        sessionToken: s.session_token,
        sessionExpiresAt: s.expires_at,
      });
      toast.success("Application restored successfully.");
      setLocation("/admissions/apply");
    } catch (error) {
      if (error instanceof AdmissionsV2Error) {
        toast.error(error.message);
      } else {
        toast.error(error instanceof Error ? error.message : "Could not resume application");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container flex min-h-screen items-center justify-center py-8">
        <Card className="w-full max-w-xl rounded-lg border-emerald-900/10">
          <CardHeader>
            <RotateCcw className="h-8 w-8 text-emerald-800" />
            <CardTitle className="text-2xl">Continue application</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={resume} className="space-y-4">
              <div className="space-y-2">
                <Label>Tracking Token</Label>
                <Input placeholder="DGI-8F4K2P9X" value={token} onChange={(e) => setToken(e.target.value.toUpperCase())} required />
              </div>
              <div className="space-y-2">
                <Label>Learner date of birth</Label>
                <Input type="date" value={dob} onChange={(e) => setDob(e.target.value)} required />
              </div>
              <Button className="w-full bg-emerald-800 hover:bg-emerald-900" disabled={loading}>
                Restore draft
              </Button>
              <div className="text-xs text-slate-500">
                For privacy, access requires your token and the learner’s date of birth. If you no longer have your token, use the tracking page to request recovery.
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </main>
  );
}
