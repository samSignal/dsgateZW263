import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AdmissionTrackingPage from "./AdmissionTrackingPage";

export default function ApplicationTimelinePage() {
  return (
    <div>
      <Card className="m-6 rounded-lg border-emerald-900/10">
        <CardHeader>
          <CardTitle>Application Timeline</CardTitle>
        </CardHeader>
        <CardContent className="text-sm text-slate-600">
          Use the tracking view below to inspect the applicant-facing timeline exactly as families see it.
        </CardContent>
      </Card>
      <AdmissionTrackingPage />
    </div>
  );
}
