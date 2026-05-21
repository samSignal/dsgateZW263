import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { StatCard, Card, CardHeader, Table, Td, Spinner, PageHeader } from '../../components/UI';

export default function FinanceDashboard() {
  const [yearId, setYearId] = useState('');
  const [termId, setTermId] = useState('');

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

  const filteredTerms = (terms as any[]).filter(t => !yearId || String(t.academic_year_id) === yearId);

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
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 14, marginBottom: 24 }}>
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
            <div style={{ height: '100%', width: `${summary?.collection_rate ?? 0}%`, background: 'linear-gradient(90deg, #1a6b3c, #2d8a52)', borderRadius: 6, transition: 'width .5s ease' }} />
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 6, fontSize: 11, color: '#6b7280' }}>
            <span>$0</span>
            <span>${Number(summary?.total_expected ?? 0).toLocaleString()}</span>
          </div>
        </div>
      </Card>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
        {/* Today's collections */}
        <Card>
          <CardHeader title={`Today's Collections (${daily?.date ?? '—'})`} action={<span style={{ fontSize: 14, fontWeight: 700, color: '#1a6b3c' }}>${Number(daily?.total ?? 0).toLocaleString()}</span>} />
          <Table headers={['Student', 'Amount', 'Method', 'Receipt']}>
            {(daily?.payments ?? []).slice(0, 8).map((p: any) => (
              <tr key={p.id}>
                <Td><strong>{p.student_name}</strong></Td>
                <Td style={{ color: '#1a6b3c', fontWeight: 600 }}>${Number(p.amount).toLocaleString()}</Td>
                <Td style={{ textTransform: 'capitalize' }}>{p.payment_method?.replace('_', ' ')}</Td>
                <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{p.receipt_number}</code></Td>
              </tr>
            ))}
            {(daily?.payments ?? []).length === 0 && <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#9ca3af', padding: 20 }}>No payments today.</Td></tr>}
          </Table>
        </Card>

        {/* Top debtors */}
        <Card>
          <CardHeader title="Top Debtors" action={<Link to="/app/finance/debtors" style={{ fontSize: 12, color: '#1a6b3c', fontWeight: 600 }}>View All →</Link>} />
          <Table headers={['Student', 'Form', 'Balance']}>
            {(debtors as any[]).slice(0, 8).map((d: any) => (
              <tr key={d.student_id}>
                <Td><strong>{d.student_name}</strong><br /><span style={{ fontSize: 11, color: '#6b7280' }}>{d.student_number}</span></Td>
                <Td>{d.form_name ?? '—'}</Td>
                <Td style={{ color: '#dc2626', fontWeight: 700 }}>${Number(d.total_balance).toLocaleString()}</Td>
              </tr>
            ))}
            {(debtors as any[]).length === 0 && <tr><Td colSpan={3} style={{ textAlign: 'center', color: '#9ca3af', padding: 20 }}>No debtors found.</Td></tr>}
          </Table>
        </Card>
      </div>
    </div>
  );
}
