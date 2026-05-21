import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { admissionsApi } from "@/lib/admissionsApi";
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
      const result = await admissionsApi.resume(token.toUpperCase(), dob || undefined);
      localStorage.setItem("dgi-admission-draft", JSON.stringify(result.application));
      toast.success(`Draft restored at step ${result.resume_step}`);
      setLocation("/admissions/apply");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Could not resume application");
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
                <Label>Date of Birth Verification (optional)</Label>
                <Input type="date" value={dob} onChange={(e) => setDob(e.target.value)} />
              </div>
              <Button className="w-full bg-emerald-800 hover:bg-emerald-900" disabled={loading}>
                Restore draft
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </main>
  );
}
