import React, { useEffect, useMemo, useState } from 'react';
import { useQuery, keepPreviousData } from '@tanstack/react-query';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import api from '../../lib/api';
import { downloadReport } from '../../lib/download';
import { StatCard, Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Btn, FormGroup, Select, Input, Grid, Badge } from '../../components/UI';

const chartTooltipStyle = { fontSize: 12, borderRadius: 8, border: '1px solid #e8eaed', boxShadow: '0 4px 12px rgba(0,0,0,.08)' };
const PAGE_SIZES = [20, 50, 100];

export default function OwingStudentsPage() {
  // Lets other pages (e.g. the "Outstanding Balance by Form" chart on the Finance
  // Dashboard) deep-link straight into a filtered view, like /finance/debtors?form_id=3.
  const navigate = useNavigate();
  const [urlParams] = useSearchParams();
  const [yearId, setYearId] = useState(urlParams.get('academic_year_id') ?? '');
  const [termId, setTermId] = useState(urlParams.get('term_id') ?? '');
  const [formId, setFormId] = useState(urlParams.get('form_id') ?? '');
  const [streamId, setStreamId] = useState(urlParams.get('stream_id') ?? '');
  const [minBalance, setMinBalance] = useState('');
  const [search, setSearch] = useState('');
  const [downloadingClass, setDownloadingClass] = useState<'csv' | 'pdf' | null>(null);
  const [downloading, setDownloading] = useState<'csv' | 'pdf' | null>(null);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(PAGE_SIZES[0]);

  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'], queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: forms = [] } = useQuery({ queryKey: ['forms-list'], queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams-list'], queryFn: () => api.get('/streams').then(r => r.data) });
  const filteredTerms = (terms as any[]).filter(t => !yearId || String(t.academic_year_id) === yearId);
  const filteredStreams = (streams as any[]).filter(s => !formId || String(s.form_id) === formId);

  const params = {
    academic_year_id: yearId || undefined,
    term_id: termId || undefined,
    form_id: formId || undefined,
    stream_id: streamId || undefined,
    min_balance: minBalance || undefined,
    search: search || undefined,
  };

  const { data: debtors = [], isLoading } = useQuery({
    queryKey: ['owing-students', yearId, termId, formId, streamId, minBalance, search],
    queryFn: () => api.get('/finance/reports/debtors', { params }).then(r => r.data),
    placeholderData: keepPreviousData,
  });

  useEffect(() => { setPage(1); }, [yearId, termId, formId, streamId, minBalance, search, pageSize]);

  const rows = debtors as any[];
  const totalOwing = rows.reduce((s, r) => s + Number(r.total_balance), 0);
  const totalBilled = rows.reduce((s, r) => s + Number(r.total_billed), 0);
  const totalPaid = rows.reduce((s, r) => s + Number(r.total_paid), 0);
  const avgBalance = rows.length ? totalOwing / rows.length : 0;

  // Both charts are derived from the same filtered `rows`, so they move with every filter
  // change instead of needing their own separate fetch.
  const byFormChart = useMemo(() => {
    const groups = new Map<string, { formId: string | null; balance: number }>();
    rows.forEach(r => {
      const key = r.form_name ?? 'Unassigned';
      const existing = groups.get(key);
      groups.set(key, { formId: r.form_id != null ? String(r.form_id) : null, balance: (existing?.balance ?? 0) + Number(r.total_balance) });
    });
    return Array.from(groups, ([name, v]) => ({ name, formId: v.formId, balance: v.balance }));
  }, [rows]);

  const topOwing = useMemo(() => rows.slice(0, 10).map(d => ({
    name: d.student_name.length > 18 ? d.student_name.slice(0, 17) + '…' : d.student_name,
    fullName: d.student_name,
    studentId: d.student_id,
    balance: Number(d.total_balance),
  })), [rows]);

  const onStudentBarClick = (data: any) => {
    if (data?.studentId) navigate(`/app/students/${data.studentId}`);
  };

  const total = rows.length;
  const lastPage = Math.max(1, Math.ceil(total / pageSize));
  const pageRows = useMemo(() => rows.slice((page - 1) * pageSize, page * pageSize), [rows, page, pageSize]);

  const download = async (format: 'csv' | 'pdf') => {
    setDownloading(format);
    try {
      await downloadReport('/finance/reports/debtors', { ...params, format }, `owing-students.${format}`);
    } finally {
      setDownloading(null);
    }
  };

  const downloadByClass = async (format: 'csv' | 'pdf') => {
    setDownloadingClass(format);
    try {
      await downloadReport('/finance/reports/balances-by-class', { academic_year_id: yearId || undefined, term_id: termId || undefined, format }, `outstanding-balance-by-class.${format}`);
    } finally {
      setDownloadingClass(null);
    }
  };

  // Clicking a form's bar drills into just that form — same page, filters applied in place.
  const onFormBarClick = (data: any) => {
    if (!data?.formId) return;
    setFormId(data.formId);
    setStreamId('');
  };

  return (
    <div>
      <PageHeader title="Owing Students" subtitle="Every student with an outstanding balance — filterable, with live totals and charts" />

      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Filters" />
        <CardBody>
          <Grid cols={6} style={{ marginBottom: 0, alignItems: 'end' }}>
            <FormGroup label="Academic Year">
              <Select value={yearId} onChange={e => { setYearId(e.target.value); setTermId(''); }}>
                <option value="">All Years</option>
                {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Term">
              <Select value={termId} onChange={e => setTermId(e.target.value)}>
                <option value="">All Terms</option>
                {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Form">
              <Select value={formId} onChange={e => { setFormId(e.target.value); setStreamId(''); }}>
                <option value="">All Forms</option>
                {(forms as any[]).map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Class">
              <Select value={streamId} onChange={e => setStreamId(e.target.value)}>
                <option value="">All Classes</option>
                {filteredStreams.map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Min. Balance ($)">
              <Input type="number" min="0" value={minBalance} onChange={e => setMinBalance(e.target.value)} placeholder="e.g. 50" />
            </FormGroup>
            <FormGroup label="Search">
              <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Name or student number…" />
            </FormGroup>
          </Grid>
        </CardBody>
      </Card>

      {/* KPIs — recompute from the filtered set, so they always describe exactly what's on screen */}
      <div className="grid-4" style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 14, marginBottom: 20 }}>
        <StatCard label="Students Owing"  value={isLoading ? '…' : total}                                   icon="🔴" color="red" />
        <StatCard label="Total Owing"     value={`$${totalOwing.toLocaleString(undefined, { maximumFractionDigits: 2 })}`} icon="⚠️" color="red" />
        <StatCard label="Total Billed"    value={`$${totalBilled.toLocaleString(undefined, { maximumFractionDigits: 2 })}`} icon="📋" color="blue" />
        <StatCard label="Avg. Balance"    value={`$${avgBalance.toLocaleString(undefined, { maximumFractionDigits: 2 })}`}  icon="📊" color="green" />
      </div>

      {/* Charts — driven by the same filtered rows as the table below */}
      <div className="two-col-grid" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginBottom: 20 }}>
        <Card>
          <CardHeader title="Outstanding Balance by Form" action={
            <div style={{ display: 'flex', gap: 8 }}>
              <Btn size="sm" variant="outline" loading={downloadingClass === 'pdf'} onClick={() => downloadByClass('pdf')}>⬇ By Class (PDF)</Btn>
              <Btn size="sm" variant="outline" loading={downloadingClass === 'csv'} onClick={() => downloadByClass('csv')}>⬇ By Class (CSV)</Btn>
            </div>
          } />
          <div style={{ padding: '4px 8px 14px' }}>
            {byFormChart.length === 0 ? (
              <div style={{ padding: 24, textAlign: 'center', color: '#9ca3af', fontSize: 13 }}>No matching students.</div>
            ) : (
              <>
                <div style={{ fontSize: 11, color: '#9ca3af', padding: '0 8px 6px' }}>Click a bar to filter the table below to that form.</div>
                <ResponsiveContainer width="100%" height={220}>
                  <BarChart data={byFormChart} barSize={28}>
                    <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#9ca3af' }} axisLine={false} tickLine={false} />
                    <YAxis tick={{ fontSize: 10, fill: '#9ca3af' }} axisLine={false} tickLine={false} tickFormatter={v => `$${v}`} />
                    <Tooltip formatter={(v: number) => `$${Number(v).toLocaleString()}`} contentStyle={chartTooltipStyle} cursor={{ fill: '#f9fafb' }} />
                    <Bar dataKey="balance" fill="#dc2626" radius={[4, 4, 0, 0]} cursor="pointer" onClick={onFormBarClick} />
                  </BarChart>
                </ResponsiveContainer>
              </>
            )}
          </div>
        </Card>

        <Card>
          <CardHeader title="Top 10 Owing (within current filters)" />
          <div style={{ padding: '4px 8px 14px' }}>
            {topOwing.length === 0 ? (
              <div style={{ padding: 24, textAlign: 'center', color: '#9ca3af', fontSize: 13 }}>No matching students.</div>
            ) : (
              <>
                <div style={{ fontSize: 11, color: '#9ca3af', padding: '0 8px 6px' }}>Click a bar to open that student's profile.</div>
                <ResponsiveContainer width="100%" height={Math.max(180, topOwing.length * 28)}>
                  <BarChart data={topOwing} layout="vertical" barSize={16} margin={{ left: 8, right: 16 }}>
                    <XAxis type="number" tick={{ fontSize: 10, fill: '#9ca3af' }} axisLine={false} tickLine={false} tickFormatter={v => `$${v}`} />
                    <YAxis type="category" dataKey="name" tick={{ fontSize: 11, fill: '#374151' }} axisLine={false} tickLine={false} width={110} />
                    <Tooltip formatter={(v: number) => `$${Number(v).toLocaleString()}`} labelFormatter={(_, p) => (p?.[0]?.payload as any)?.fullName ?? ''} contentStyle={chartTooltipStyle} cursor={{ fill: '#f9fafb' }} />
                    <Bar dataKey="balance" fill="#dc2626" radius={[0, 4, 4, 0]} cursor="pointer" onClick={onStudentBarClick}>
                      {topOwing.map((_: any, i: number) => <Cell key={i} />)}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </>
            )}
          </div>
        </Card>
      </div>

      <Card>
        <CardHeader
          title="Owing Students"
          action={
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <Badge variant="red">{isLoading ? '…' : total} student(s)</Badge>
              <Btn size="sm" variant="outline" loading={downloading === 'csv'} onClick={() => download('csv')}>⬇ CSV</Btn>
              <Btn size="sm" variant="outline" loading={downloading === 'pdf'} onClick={() => download('pdf')}>⬇ PDF</Btn>
            </div>
          }
        />
        {isLoading ? <div style={{ padding: 24 }}><Spinner /></div> : (
          <>
            <Table headers={['#', 'Student', 'Student #', 'Form / Class', 'Billed', 'Paid', 'Balance']}>
              {pageRows.length === 0 && (
                <tr><Td colSpan={7} style={{ textAlign: 'center', color: '#9ca3af' }}>No students match this filter.</Td></tr>
              )}
              {pageRows.map((d, i) => (
                <tr key={d.student_id}>
                  <Td style={{ color: '#9ca3af' }}>{(page - 1) * pageSize + i + 1}</Td>
                  <Td><Link to={`/app/students/${d.student_id}`} style={{ color: '#1a6b3c', fontWeight: 700, textDecoration: 'none' }}>{d.student_name}</Link></Td>
                  <Td><code style={{ background: '#eff6ff', color: '#1e40af', padding: '2px 7px', borderRadius: 5, fontSize: 11 }}>{d.student_number ?? d.admission_number}</code></Td>
                  <Td>{trim(`${d.form_name ?? '—'} ${d.stream_name ?? ''}`)}</Td>
                  <Td>${Number(d.total_billed).toLocaleString()}</Td>
                  <Td style={{ color: '#1a6b3c' }}>${Number(d.total_paid).toLocaleString()}</Td>
                  <Td style={{ color: '#dc2626', fontWeight: 700 }}>${Number(d.total_balance).toLocaleString()}</Td>
                </tr>
              ))}
            </Table>

            {total > 0 && (
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 20px', borderTop: '1px solid #f1f5f9' }}>
                <div style={{ fontSize: 12, color: '#6b7280' }}>
                  Showing {(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} of {total}
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                  <Select value={String(pageSize)} onChange={e => setPageSize(Number(e.target.value))} style={{ width: 100 }}>
                    {PAGE_SIZES.map(n => <option key={n} value={n}>{n} / page</option>)}
                  </Select>
                  <Btn size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => Math.max(1, p - 1))}>← Prev</Btn>
                  <span style={{ fontSize: 12, color: '#374151', minWidth: 70, textAlign: 'center' }}>Page {page} of {lastPage}</span>
                  <Btn size="sm" variant="outline" disabled={page >= lastPage} onClick={() => setPage(p => Math.min(lastPage, p + 1))}>Next →</Btn>
                </div>
              </div>
            )}
          </>
        )}
      </Card>
    </div>
  );
}

function trim(s: string) { return s.replace(/\s+/g, ' ').trim() || '—'; }
