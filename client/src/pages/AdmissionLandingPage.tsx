import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { BadgeCheck, ClipboardList, FileCheck2, Search, ShieldCheck } from "lucide-react";
import { useLocation } from "wouter";

export default function AdmissionLandingPage() {
  const [, setLocation] = useLocation();

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <section className="border-b border-emerald-900/10 bg-gradient-to-b from-emerald-950 to-emerald-900 text-white">
        <div className="container grid gap-8 py-12 lg:grid-cols-[1.15fr_0.85fr] lg:py-16">
          <div className="flex flex-col justify-center gap-6">
            <BadgeCheck className="h-12 w-12 text-amber-300" />
            <div className="space-y-4">
              <h1 className="text-4xl font-semibold tracking-normal md:text-6xl">DestinyGate Institute Admissions</h1>
              <p className="max-w-2xl text-base leading-7 text-emerald-50 md:text-lg">
                Apply for Form 1 intake or transfer admission, save drafts securely, continue later with a tracking token, and follow every admissions decision online.
              </p>
            </div>
            <div className="flex flex-col gap-3 sm:flex-row">
              <Button className="bg-amber-400 text-emerald-950 hover:bg-amber-300" onClick={() => setLocation("/admissions/apply")}>
                Start application
              </Button>
              <Button variant="outline" className="border-white/40 bg-transparent text-white hover:bg-white/10" onClick={() => setLocation("/admissions/continue")}>
                Continue later
              </Button>
              <Button variant="outline" className="border-white/40 bg-transparent text-white hover:bg-white/10" onClick={() => setLocation("/admissions/track")}>
                Track status
              </Button>
            </div>
          </div>
          <div className="grid content-end gap-3">
            {[
              ["No applicant accounts", "A secure token unlocks draft continuation and tracking."],
              ["Document uploads", "Birth certificate, reports, transfer letters, and medical records."],
              ["Professional review", "Admissions staff can review, request documents, accept, reject, and enroll."],
            ].map(([title, copy]) => (
              <div key={title} className="rounded-lg border border-white/15 bg-white/10 p-4 backdrop-blur">
                <p className="font-medium text-amber-200">{title}</p>
                <p className="mt-1 text-sm text-emerald-50">{copy}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="container grid gap-4 py-10 md:grid-cols-4">
        {[
          { icon: ClipboardList, title: "Apply", copy: "Choose Form 1 intake or transfer admission." },
          { icon: ShieldCheck, title: "Save Draft", copy: "Receive an application number and secure token." },
          { icon: FileCheck2, title: "Upload", copy: "Attach the required documents as your application progresses." },
          { icon: Search, title: "Track", copy: "View status, timeline, notices, and enrollment next steps." },
        ].map((item) => (
          <Card key={item.title} className="rounded-lg border-emerald-900/10 shadow-sm">
            <CardHeader>
              <item.icon className="h-7 w-7 text-emerald-800" />
              <CardTitle className="text-lg">{item.title}</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-slate-600">{item.copy}</CardContent>
          </Card>
        ))}
      </section>
    </main>
  );
}
