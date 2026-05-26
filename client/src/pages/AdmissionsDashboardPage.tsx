import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { admissionsV2Api, type CatalogAcademicYear, type CatalogCategory, type CatalogForm } from "@/lib/admissionsV2Api";
import { admissionsOfficeV2Api, AdmissionsOfficeV2Error, type OfficeQueueItem } from "@/lib/admissionsOfficeV2Api";
import { Search } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { useLocation } from "wouter";

export default function AdmissionsDashboardPage() {
  const [items, setItems] = useState<OfficeQueueItem[]>([]);
  const [metrics, setMetrics] = useState<{
    counts: Record<string, number>;
    average_review_age_days: number;
    pending_document_requests: number;
    duplicate_review_backlog: number;
    duplicate_link_backlog: number;
  } | null>(null);
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState("");
  const [queue, setQueue] = useState("NEW_SUBMISSIONS");
  const [, setLocation] = useLocation();

  const [catalogYears, setCatalogYears] = useState<CatalogAcademicYear[]>([]);
  const [catalogForms, setCatalogForms] = useState<CatalogForm[]>([]);
  const [catalogCategories, setCatalogCategories] = useState<CatalogCategory[]>([]);
  const [reviewers, setReviewers] = useState<Array<{ id: number; name: string }>>([]);
  const [filters, setFilters] = useState<{
    academic_year_id: string;
    applying_form_id: string;
    preferred_category_id: string;
    assigned_reviewer_id: string;
    from_date: string;
    to_date: string;
    stale: boolean;
    duplicate_only: boolean;
  }>({
    academic_year_id: "",
    applying_form_id: "",
    preferred_category_id: "",
    assigned_reviewer_id: "",
    from_date: "",
    to_date: "",
    stale: false,
    duplicate_only: false,
  });

  const yearName = (id?: number | null) => catalogYears.find((y) => y.id === id)?.name ?? String(id ?? "");
  const formName = (id?: number | null) => catalogForms.find((f) => f.id === id)?.name ?? String(id ?? "");
  const categoryName = (id?: number | null) => catalogCategories.find((c) => c.id === id)?.name ?? String(id ?? "");

  const queueDefs = useMemo(
    () => [
      { key: "NEW_SUBMISSIONS", label: "New submissions", stateKey: "SUBMITTED" },
      { key: "UNDER_REVIEW", label: "Under review", stateKey: "UNDER_REVIEW" },
      { key: "DOCUMENTS_REQUIRED", label: "Documents required", stateKey: "DOCUMENTS_REQUIRED" },
      { key: "READY_FOR_DECISION", label: "Ready for decision", stateKey: "READY_FOR_DECISION" },
      { key: "ACCEPTED", label: "Accepted", stateKey: "ACCEPTED" },
      { key: "REJECTED", label: "Rejected", stateKey: "REJECTED" },
      { key: "WAITLISTED", label: "Waitlisted", stateKey: "WAITLISTED" },
      { key: "DUPLICATE_FLAGGED", label: "Duplicate flagged", stateKey: "DUPLICATE_FLAGGED" },
      { key: "DUPLICATE_INVALID", label: "Duplicate invalid", stateKey: "DUPLICATE_INVALID" },
      { key: "ARCHIVED", label: "Archived", stateKey: "ARCHIVED" },
    ],
    []
  );

  const loadCatalog = async () => {
    try {
      const [y, f, c] = await Promise.all([admissionsV2Api.catalogAcademicYears(), admissionsV2Api.catalogForms(), admissionsV2Api.catalogCategories()]);
      setCatalogYears(y.academic_years);
      setCatalogForms(f.forms);
      setCatalogCategories(c.categories);
    } catch {
    }
    try {
      const r = await admissionsOfficeV2Api.reviewers();
      setReviewers(r.items.map((u) => ({ id: u.id, name: u.name })));
    } catch {
      setReviewers([]);
    }
  };

  const load = async () => {
    try {
      setLoading(true);
      const [m, q] = await Promise.all([
        admissionsOfficeV2Api.metrics({
          academic_year_id: filters.academic_year_id ? Number(filters.academic_year_id) : undefined,
        }),
        admissionsOfficeV2Api.queue(queue, {
          search: search.trim() ? search.trim() : undefined,
          academic_year_id: filters.academic_year_id ? Number(filters.academic_year_id) : undefined,
          applying_form_id: filters.applying_form_id ? Number(filters.applying_form_id) : undefined,
          preferred_category_id: filters.preferred_category_id ? Number(filters.preferred_category_id) : undefined,
          assigned_reviewer_id: filters.assigned_reviewer_id ? Number(filters.assigned_reviewer_id) : undefined,
          from_date: filters.from_date ? filters.from_date : undefined,
          to_date: filters.to_date ? filters.to_date : undefined,
          stale: filters.stale ? 1 : undefined,
          duplicate_only: filters.duplicate_only ? 1 : undefined,
          per_page: 25,
          page: 1,
        }),
      ]);
      setMetrics(m);
      setItems(q.items);
    } catch (error) {
      if (error instanceof AdmissionsOfficeV2Error) toast.error(error.message);
      else toast.error(error instanceof Error ? error.message : "Could not load admissions queue");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadCatalog();
    load();
  }, []);

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [queue]);

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <div className="container py-8">
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <p className="text-sm font-medium uppercase tracking-wide text-amber-600">Admissions Office</p>
            <h1 className="text-3xl font-semibold tracking-normal text-emerald-950">Applications dashboard</h1>
          </div>
          <div className="flex gap-2">
            <Input placeholder="Search application number, learner, guardian email" value={search} onChange={(e) => setSearch(e.target.value)} />
            <Button onClick={load} disabled={loading} className="gap-2 bg-emerald-800 hover:bg-emerald-900"><Search className="h-4 w-4" />Search</Button>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-5">
          {queueDefs.slice(0, 5).map((q) => (
            <Card key={q.key} className="cursor-pointer rounded-lg border-emerald-900/10" onClick={() => setQueue(q.key)}>
              <CardHeader className="pb-2"><CardTitle className="text-sm text-slate-600">{q.label}</CardTitle></CardHeader>
              <CardContent className="text-2xl font-semibold">{metrics?.counts?.[q.stateKey] ?? 0}</CardContent>
            </Card>
          ))}
        </div>
        <div className="mt-4 grid gap-4 md:grid-cols-5">
          {queueDefs.slice(5, 10).map((q) => (
            <Card key={q.key} className="cursor-pointer rounded-lg border-emerald-900/10" onClick={() => setQueue(q.key)}>
              <CardHeader className="pb-2"><CardTitle className="text-sm text-slate-600">{q.label}</CardTitle></CardHeader>
              <CardContent className="text-2xl font-semibold">{metrics?.counts?.[q.stateKey] ?? 0}</CardContent>
            </Card>
          ))}
        </div>

        <div className="mt-6 grid gap-4 md:grid-cols-4">
          <Card className="rounded-lg border-emerald-900/10">
            <CardHeader className="pb-2"><CardTitle className="text-sm text-slate-600">Average review age</CardTitle></CardHeader>
            <CardContent className="text-2xl font-semibold">{Math.round(metrics?.average_review_age_days ?? 0)} days</CardContent>
          </Card>
          <Card className="rounded-lg border-emerald-900/10">
            <CardHeader className="pb-2"><CardTitle className="text-sm text-slate-600">Pending doc requests</CardTitle></CardHeader>
            <CardContent className="text-2xl font-semibold">{metrics?.pending_document_requests ?? 0}</CardContent>
          </Card>
          <Card className="rounded-lg border-emerald-900/10">
            <CardHeader className="pb-2"><CardTitle className="text-sm text-slate-600">Duplicate backlog</CardTitle></CardHeader>
            <CardContent className="text-2xl font-semibold">{metrics?.duplicate_review_backlog ?? 0}</CardContent>
          </Card>
          <Card className="rounded-lg border-emerald-900/10">
            <CardHeader className="pb-2"><CardTitle className="text-sm text-slate-600">Duplicate links pending</CardTitle></CardHeader>
            <CardContent className="text-2xl font-semibold">{metrics?.duplicate_link_backlog ?? 0}</CardContent>
          </Card>
          <Card className="rounded-lg border-emerald-900/10 md:col-span-4">
            <CardHeader className="pb-2"><CardTitle className="text-sm text-slate-600">Filters</CardTitle></CardHeader>
            <CardContent className="grid gap-3 md:grid-cols-4">
              <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={filters.academic_year_id} onChange={(e) => setFilters((c) => ({ ...c, academic_year_id: e.target.value }))}>
                <option value="">All intakes</option>
                {catalogYears.map((y) => <option key={y.id} value={y.id}>{y.name}</option>)}
              </select>
              <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={filters.applying_form_id} onChange={(e) => setFilters((c) => ({ ...c, applying_form_id: e.target.value }))}>
                <option value="">All forms</option>
                {catalogForms.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
              </select>
              <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={filters.preferred_category_id} onChange={(e) => setFilters((c) => ({ ...c, preferred_category_id: e.target.value }))}>
                <option value="">All categories</option>
                {catalogCategories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
              <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={filters.assigned_reviewer_id} onChange={(e) => setFilters((c) => ({ ...c, assigned_reviewer_id: e.target.value }))}>
                <option value="">All reviewers</option>
                {reviewers.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
              </select>
              <Input type="date" value={filters.from_date} onChange={(e) => setFilters((c) => ({ ...c, from_date: e.target.value }))} />
              <Input type="date" value={filters.to_date} onChange={(e) => setFilters((c) => ({ ...c, to_date: e.target.value }))} />
              <label className="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" checked={filters.stale} onChange={(e) => setFilters((c) => ({ ...c, stale: e.target.checked }))} />
                Stale only
              </label>
              <label className="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" checked={filters.duplicate_only} onChange={(e) => setFilters((c) => ({ ...c, duplicate_only: e.target.checked }))} />
                Duplicates only
              </label>
            </CardContent>
          </Card>
        </div>

        <Card className="mt-6 rounded-lg border-emerald-900/10">
          <CardHeader className="pb-2">
            <CardTitle className="text-base">Queue: {queueDefs.find((q) => q.key === queue)?.label ?? queue}</CardTitle>
          </CardHeader>
          <CardContent className="overflow-x-auto pt-6">
            <table className="w-full min-w-[760px] text-sm">
              <thead className="text-left text-slate-500">
                <tr className="border-b">
                  <th className="py-3">Application</th>
                  <th>Student</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Intake</th>
                  <th>Reviewer</th>
                  <th>Age</th>
                  <th>Flags</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {items.map((app) => (
                  <tr key={app.id} className="border-b last:border-0">
                    <td className="py-3 font-medium">{app.application_number}</td>
                    <td>{app.student_first_name} {app.student_last_name}</td>
                    <td className="capitalize">{app.application_type?.replaceAll("_", " ")}</td>
                    <td><Badge className="bg-emerald-800 capitalize">{String(app.lifecycle_state || app.status).replaceAll("_", " ").toLowerCase()}</Badge></td>
                    <td className="text-slate-600">{`${formName(app.applying_form_id)} · ${categoryName(app.preferred_category_id)} · ${yearName(app.academic_year_id)}`.trim()}</td>
                    <td className="text-slate-600">{app.assigned_reviewer_name ?? "-"}</td>
                    <td className="text-slate-600">{app.age_days === null ? "-" : `${app.age_days}d`}</td>
                    <td className="text-slate-600">
                      <div className="flex flex-wrap gap-1">
                        {app.is_stale ? <Badge variant="outline">stale</Badge> : null}
                        {(app.pending_doc_requests ?? 0) > 0 ? <Badge variant="outline">{app.pending_doc_requests} doc</Badge> : null}
                        {app.duplicate_flags?.has_flagged_link || app.lifecycle_state === "DUPLICATE_FLAGGED" ? <Badge variant="outline">dup</Badge> : null}
                      </div>
                    </td>
                    <td className="text-right"><Button variant="outline" onClick={() => setLocation(`/admissions-office/applications/${app.id}`)}>Open</Button></td>
                  </tr>
                ))}
                {items.length === 0 && (
                  <tr><td className="py-6 text-center text-slate-500" colSpan={9}>{loading ? "Loading…" : "No applications in this queue."}</td></tr>
                )}
              </tbody>
            </table>
          </CardContent>
        </Card>
      </div>
    </main>
  );
}
