import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { admissionsApi, AdmissionApplication } from "@/lib/admissionsApi";
import { Search } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { useLocation } from "wouter";

export default function AdmissionsDashboardPage() {
  const [applications, setApplications] = useState<AdmissionApplication[]>([]);
  const [search, setSearch] = useState("");
  const [, setLocation] = useLocation();

  const load = async () => {
    try {
      const response = await admissionsApi.listApplications(search ? `?search=${encodeURIComponent(search)}` : "");
      setApplications(Array.isArray(response) ? response : response.data);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Could not load applications");
    }
  };

  useEffect(() => {
    load();
  }, []);

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container py-8">
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <p className="text-sm font-medium uppercase tracking-wide text-amber-600">Admissions Office</p>
            <h1 className="text-3xl font-semibold tracking-normal text-emerald-950">Applications dashboard</h1>
          </div>
          <div className="flex gap-2">
            <Input placeholder="Search name, token, number" value={search} onChange={(e) => setSearch(e.target.value)} />
            <Button onClick={load} className="gap-2 bg-emerald-800 hover:bg-emerald-900"><Search className="h-4 w-4" />Search</Button>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-4">
          {["submitted", "under_review", "accepted", "enrolled"].map((status) => (
            <Card key={status} className="rounded-lg border-emerald-900/10">
              <CardHeader className="pb-2"><CardTitle className="text-sm capitalize text-slate-600">{status.replaceAll("_", " ")}</CardTitle></CardHeader>
              <CardContent className="text-2xl font-semibold">{applications.filter((app) => app.status === status).length}</CardContent>
            </Card>
          ))}
        </div>

        <Card className="mt-6 rounded-lg border-emerald-900/10">
          <CardContent className="overflow-x-auto pt-6">
            <table className="w-full min-w-[760px] text-sm">
              <thead className="text-left text-slate-500">
                <tr className="border-b">
                  <th className="py-3">Application</th>
                  <th>Student</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Guardian</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {applications.map((app) => (
                  <tr key={app.id} className="border-b last:border-0">
                    <td className="py-3 font-medium">{app.application_number}</td>
                    <td>{app.student_first_name} {app.student_last_name}</td>
                    <td className="capitalize">{app.application_type?.replaceAll("_", " ")}</td>
                    <td><Badge className="bg-emerald-800 capitalize">{app.status?.replaceAll("_", " ")}</Badge></td>
                    <td>{app.guardian_name}</td>
                    <td className="text-right"><Button variant="outline" onClick={() => setLocation(`/admissions-office/applications/${app.id}`)}>Review</Button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardContent>
        </Card>
      </div>
    </main>
  );
}
