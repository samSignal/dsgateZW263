import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { admissionsApi, AdmissionNotification } from "@/lib/admissionsApi";
import { Bell, Search } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

export default function AdmissionNotificationsPage() {
  const [applicationId, setApplicationId] = useState("");
  const [notifications, setNotifications] = useState<AdmissionNotification[]>([]);

  const load = async () => {
    try {
      const data = await admissionsApi.review(applicationId);
      setNotifications(data.notifications);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Could not load notifications");
    }
  };

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container py-8">
        <div className="mb-6">
          <p className="text-sm font-medium uppercase tracking-wide text-amber-600">Admissions Office</p>
          <h1 className="text-3xl font-semibold tracking-normal text-emerald-950">Notification log</h1>
        </div>
        <Card className="rounded-lg border-emerald-900/10">
          <CardContent className="flex flex-col gap-3 pt-6 sm:flex-row">
            <Input placeholder="Application ID" value={applicationId} onChange={(e) => setApplicationId(e.target.value)} />
            <Button onClick={load} className="gap-2 bg-emerald-800 hover:bg-emerald-900"><Search className="h-4 w-4" />Load</Button>
          </CardContent>
        </Card>
        <div className="mt-6 grid gap-3">
          {notifications.map((notice) => (
            <Card key={notice.id} className="rounded-lg border-emerald-900/10">
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-base"><Bell className="h-4 w-4 text-emerald-800" />{notice.title}</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="whitespace-pre-line text-sm text-slate-600">{notice.message}</p>
                <p className="mt-3 text-xs uppercase text-slate-400">{notice.notification_channel} | {notice.notification_type}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </main>
  );
}
