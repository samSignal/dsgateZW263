import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import api from '../../lib/api';
import { downloadReport } from '../../lib/download';
import { StatCard, Card, CardHeader, Table, Td, Spinner, PageHeader, Btn } from '../../components/UI';

const chartTooltipStyle = { fontSize: 12, borderRadius: 8, border: '1px solid #e8eaed', boxShadow: '0 4px 12px rgba(0,0,0,.08)' };

export default function FinanceDashboard() {
  const navigate = useNavigate();
  const [yearId, setYearId] = useState('');
  const [termId, setTermId] = useState('');
  const [downloadingClass, setDownloadingClass] = useState<'csv' | 'pdf' | null>(null);

  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'],           queryFn: () => api.get('/terms').then(r => r.data) });

  const { data: summary, isLoading } = useQuery({
    queryKey: ['finance-summary', yearId, termId],
    queryFn: () => api.get('/finance/reports/summary', { params: { academic_year_id: yearId || undefined, term_id: termId || undefined } }).then(r => r.data),
  });

  const { data: debtors = [] } = useQuery({
    queryKey: ['finance-debtors', yearId, termId],
    queryFn: () => api.get('/finance/reports/debtors', { params: { academic_year_id: yearId || undefined, term_id: termId || undefined } }).then(r => r.data),
  });

  const { data: daily } = useQuery({
    queryKey: ['finance-daily'],
    queryFn: () => api.get('/finance/reports/daily').then(r => r.data),
  });

  const { data: byForm = [] } = useQuery({
    queryKey: ['finance-balances-by-form', yearId, termId],
    queryFn: () => api.get('/finance/reports/balances-by-form', { params: { academic_year_id: yearId || undefined, term_id: termId || undefined } }).then(r => r.data),
  });

  const filteredTerms = (terms as any[]).filter(t => !yearId || String(t.academic_year_id) === yearId);

  const byFormChart = (byForm as any[]).map((f: any) => ({ name: f.form_name ?? 'Unassigned', formId: f.form_id, balance: Number(f.total_balance) }));
  const topOwing = (debtors as any[]).slice(0, 10).map((d: any) => ({
    name: d.student_name.length > 18 ? d.student_name.slice(0, 17) + '…' : d.student_name,
    fullName: d.student_name,
    studentId: d.student_id,
    balance: Number(d.total_balance),
  }));

  const onStudentBarClick = (data: any) => {
    if (data?.studentId) navigate(`/app/students/${data.studentId}`);
  };

  // Clicking a form's bar jumps to the full Owing Students page, pre-filtered to that form —
  // this dashboard doesn't have its own student table to filter in place.
  const onFormBarClick = (data: any) => {
    if (!data?.formId) return;
    const qs = new URLSearchParams({ form_id: String(data.formId) });
    if (yearId) qs.set('academic_year_id', yearId);
    if (termId) qs.set('term_id', termId);
    navigate(`/app/finance/debtors?${qs.toString()}`);
  };

  const downloadByClass = async (format: 'csv' | 'pdf') => {
    setDownloadingClass(format);
    try {
      await downloadReport('/finance/reports/balances-by-class', { academic_year_id: yearId || undefined, term_id: termId || undefined, format }, `outstanding-balance-by-class.${format}`);
    } finally {
      setDownloadingClass(null);
    }
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Finance Dashboard" subtitle="School fee collections and financial overview" />

      {/* Filters */}
      <div style={{ display: 'flex', gap: 10, marginBottom: 20 }}>
        <select value={yearId} onChange={e => { setYearId(e.target.value); setTermId(''); }} style={{ padding: '7px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13 }}>
          <option value="">All Years</option>
          {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </select>
        <select value={termId} onChange={e => setTermId(e.target.value)} style={{ padding: '7px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13 }}>
          <option value="">All Terms</option>
          {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
        </select>
      </div>

      {/* KPI Cards */}
      <div className="grid-3" style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 14, marginBottom: 24 }}>
        <StatCard label="Expected Fees"    value={`$${Number(summary?.total_expected ?? 0).toLocaleString()}`}   icon="📋" color="blue" />
        <StatCard label="Collected"        value={`$${Number(summary?.total_collected ?? 0).toLocaleString()}`}  icon="💰" color="green" />
        <StatCard label="Outstanding"      value={`$${Number(summary?.total_outstanding ?? 0).toLocaleString()}`} icon="⚠️" color="red" />
        <StatCard label="Collection Rate"  value={`${summary?.collection_rate ?? 0}%`}                           icon="📊" color="green" />
        <StatCard label="Fully Paid"       value={summary?.fully_paid ?? 0}                                       icon="✅" color="green" />
        <StatCard label="Debtors"          value={summary?.debtors ?? 0}                                          icon="🔴" color="red" />
      </div>

      {/* Collection rate bar */}
      <Card style={{ marginBottom: 20 }}>
        <div style={{ padding: '16px 20px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
            <span style={{ fontSize: 13, fontWeight: 600 }}>Collection Progress</span>
            <span style={{ fontSize: 13, fontWeight: 700, color: '#1a6b3c' }}>{summary?.collection_rate ?? 0}%</span>
          </div>
          <div style={{ height: 12, background: '#f3f4f6', borderRadius: 6, overflow: 'hidden' }}>
            <div style={{ height: '100%', width: `${summary?.collection_rate ?? 0}%`, background: 'linear-gradient(90deg, #1a6b3c, #1a6b3c)', borderRadius: 6, transition: 'width .5s ease' }} />
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 6, fontSize: 11, color: '#6b7280' }}>
            <span>$0</span>
            <span>${Number(summary?.total_expected ?? 0).toLocaleString()}</span>
          </div>
        </div>
      </Card>

      {/* Charts row */}
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
              <div style={{ padding: 24, textAlign: 'center', color: '#9ca3af', fontSize: 13 }}>No billing data yet.</div>
            ) : (
              <>
                <div style={{ fontSize: 11, color: '#9ca3af', padding: '0 8px 6px' }}>Click a bar to see that form's owing students.</div>
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
          <CardHeader title="Top 10 Owing Students" />
          <div style={{ padding: '4px 8px 14px' }}>
            {topOwing.length === 0 ? (
              <div style={{ padding: 24, textAlign: 'center', color: '#9ca3af', fontSize: 13 }}>No outstanding balances.</div>
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

      <div className="two-col-grid" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
        {/* Today's collections */}
        <Card>
          <CardHeader title={`Today's Collections (${daily?.date ?? '—'})`} action={
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
              <span style={{ fontSize: 14, fontWeight: 700, color: '#1a6b3c' }}>${Number(daily?.total ?? 0).toLocaleString()}</span>
              <Btn size="sm" variant="outline" onClick={() => downloadReport('/finance/reports/daily', { format: 'pdf' }, `daily-register-${daily?.date ?? 'today'}.pdf`)}>PDF</Btn>
              <Btn size="sm" variant="outline" onClick={() => downloadReport('/finance/reports/daily', { format: 'csv' }, `daily-register-${daily?.date ?? 'today'}.csv`)}>CSV</Btn>
            </div>
          } />
          <Table headers={['#', 'Student', 'Amount', 'Method', 'Receipt']}>
            {(daily?.payments ?? []).slice(0, 8).map((p: any, i: number) => (
              <tr key={p.id}>
                <Td style={{ color: '#9ca3af' }}>{i + 1}</Td>
                <Td><Link to={`/app/students/${p.student_id}`} style={{ textDecoration: 'none', color: '#1a6b3c', fontWeight: 700 }}>{p.student_name}</Link></Td>
                <Td style={{ color: '#1a6b3c', fontWeight: 600 }}>${Number(p.amount).toLocaleString()}</Td>
                <Td style={{ textTransform: 'capitalize' }}>{p.payment_method?.replace('_', ' ')}</Td>
                <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{p.receipt_number}</code></Td>
              </tr>
            ))}
            {(daily?.payments ?? []).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af', padding: 20 }}>No payments today.</Td></tr>}
          </Table>
        </Card>

        {/* Top debtors */}
        <Card>
          <CardHeader title="Top Debtors" action={
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
              <Btn size="sm" variant="outline" onClick={() => downloadReport('/finance/reports/debtors', { academic_year_id: yearId || undefined, term_id: termId || undefined, format: 'pdf' }, 'fees-arrears-report.pdf')}>PDF</Btn>
              <Btn size="sm" variant="outline" onClick={() => downloadReport('/finance/reports/debtors', { academic_year_id: yearId || undefined, term_id: termId || undefined, format: 'csv' }, 'fees-arrears-report.csv')}>CSV</Btn>
            </div>
          } />
          <Table headers={['#', 'Student', 'Form', 'Balance']}>
            {(debtors as any[]).slice(0, 8).map((d: any, i: number) => (
              <tr key={d.student_id}>
                <Td style={{ color: '#9ca3af' }}>{i + 1}</Td>
                <Td>
                  <Link to={`/app/students/${d.student_id}`} style={{ textDecoration: 'none' }}>
                    <strong style={{ color: '#1a6b3c' }}>{d.student_name}</strong><br /><span style={{ fontSize: 11, color: '#6b7280' }}>{d.student_number || d.admission_number}</span>
                  </Link>
                </Td>
                <Td>{d.form_name ?? '—'}</Td>
                <Td style={{ color: '#dc2626', fontWeight: 700 }}>${Number(d.total_balance).toLocaleString()}</Td>
              </tr>
            ))}
            {(debtors as any[]).length === 0 && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#9ca3af', padding: 20 }}>No debtors found.</Td></tr>}
          </Table>
        </Card>
      </div>

      <Card style={{ marginTop: 20 }}>
        <div style={{ padding: '14px 20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div>
            <div style={{ fontSize: 13, fontWeight: 600 }}>Fee Collection by Term / Academic Year</div>
            <div style={{ fontSize: 11.5, color: '#9ca3af', marginTop: 2 }}>{yearId && termId ? 'Uses the year/term filter above' : 'Select a year and term above to export this report'}</div>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <Btn size="sm" variant="outline" disabled={!yearId || !termId} onClick={() => downloadReport('/finance/reports/term', { academic_year_id: yearId, term_id: termId, format: 'pdf' }, 'term-collection-report.pdf')}>Export PDF</Btn>
            <Btn size="sm" variant="outline" disabled={!yearId || !termId} onClick={() => downloadReport('/finance/reports/term', { academic_year_id: yearId, term_id: termId, format: 'csv' }, 'term-collection-report.csv')}>Export CSV</Btn>
          </div>
        </div>
      </Card>

      <Card style={{ marginTop: 20 }}>
        <div style={{ padding: '14px 20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div>
            <div style={{ fontSize: 13, fontWeight: 600 }}>Fee Migration Verification Statement</div>
            <div style={{ fontSize: 11.5, color: '#9ca3af', marginTop: 2 }}>Every student, grouped by form/stream, with opening balance, term fees billed, paid, and outstanding balance — for the headmaster, bursar and clerk to cross-check against paper records. Uses the year/term filter above (defaults to the current term).</div>
          </div>
          <div style={{ display: 'flex', gap: 8, flexShrink: 0 }}>
            <Btn size="sm" variant="outline" onClick={() => downloadReport('/finance/reports/opening-balances', { academic_year_id: yearId || undefined, term_id: termId || undefined, format: 'pdf' }, 'fee-migration-statement.pdf')}>Export PDF</Btn>
            <Btn size="sm" variant="outline" onClick={() => downloadReport('/finance/reports/opening-balances', { academic_year_id: yearId || undefined, term_id: termId || undefined, format: 'csv' }, 'fee-migration-statement.csv')}>Export CSV</Btn>
          </div>
        </div>
      </Card>
    </div>
  );
}
