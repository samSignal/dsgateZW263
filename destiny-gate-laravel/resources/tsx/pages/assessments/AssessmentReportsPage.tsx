import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, Grid, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

export default function AssessmentReportsPage() {
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', stream_id: '', subject_id: '' });

  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'], queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'], queryFn: () => api.get('/streams').then(r => r.data) });
  const { data: subjects = [] } = useQuery({ queryKey: ['subjects'], queryFn: () => api.get('/subjects').then(r => r.data) });

  const reportParams = {
    academic_year_id: filters.academic_year_id || undefined,
    term_id: filters.term_id || undefined,
    stream_id: filters.stream_id || undefined,
    subject_id: filters.subject_id || undefined,
  };

  const { data: summary, isLoading: summaryLoading } = useQuery({
    queryKey: ['assessment-summary', filters],
    queryFn: () => api.get('/assessments/reports/summary', { params: reportParams }).then(r => r.data),
  });

  const subjectEnabled = !!filters.academic_year_id && !!filters.term_id && !!filters.stream_id;
  const { data: subjectPerformance = [], isLoading: subjectLoading } = useQuery({
    queryKey: ['assessment-subject-performance', filters],
    queryFn: () => api.get('/assessments/reports/subject-performance', { params: reportParams }).then(r => r.data),
    enabled: subjectEnabled,
  });

  const { data: weeklyTrend = [], isLoading: trendLoading } = useQuery({
    queryKey: ['assessment-weekly-trend', filters],
    queryFn: () => api.get('/assessments/reports/weekly-trend', { params: reportParams }).then(r => r.data),
  });

  const filteredTerms = (terms as any[]).filter(t => !filters.academic_year_id || String(t.academic_year_id) === filters.academic_year_id);

  const statCards = [
    { label: 'Total', value: summary?.total ?? 0 },
    { label: 'Open', value: summary?.open ?? 0 },
    { label: 'Submitted', value: summary?.submitted ?? 0 },
    { label: 'Approved', value: summary?.approved ?? 0 },
    { label: 'Cancelled', value: summary?.cancelled ?? 0 },
  ];

  return (
    <div>
      <PageHeader title="Assessment Reports" subtitle="Track subject performance, pass rates, and weekly progress trends" />

      <div className="mb-5 flex flex-wrap gap-3">
        <Select value={filters.academic_year_id} onChange={e => setFilters(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))} style={{ width: 160 }}>
          <option value="">All Years</option>
          {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </Select>
        <Select value={filters.term_id} onChange={e => setFilters(f => ({ ...f, term_id: e.target.value }))} style={{ width: 150 }}>
          <option value="">All Terms</option>
          {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={filters.stream_id} onChange={e => setFilters(f => ({ ...f, stream_id: e.target.value }))} style={{ width: 170 }}>
          <option value="">All Streams</option>
          {(streams as any[]).map(s => <option key={s.id} value={s.id}>{s.form_name} - {s.name}</option>)}
        </Select>
        <Select value={filters.subject_id} onChange={e => setFilters(f => ({ ...f, subject_id: e.target.value }))} style={{ width: 190 }}>
          <option value="">All Subjects</option>
          {(subjects as any[]).map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
        </Select>
      </div>

      {summaryLoading ? <Spinner /> : (
        <Grid cols={5}>
          {statCards.map(card => (
            <Card key={card.label}>
              <div className="p-4">
                <div className="text-xs font-semibold uppercase text-slate-500">{card.label}</div>
                <div className="mt-2 text-3xl font-bold text-emerald-800">{card.value}</div>
              </div>
            </Card>
          ))}
        </Grid>
      )}

      <div className="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader title="Subject Performance" />
          {!subjectEnabled ? (
            <div className="p-6 text-sm text-slate-500">Select academic year, term, and stream to view subject performance.</div>
          ) : subjectLoading ? <Spinner /> : (
            <Table headers={['Subject', 'Average', 'Highest', 'Lowest', 'Entered', 'Pass Rate']}>
              {(subjectPerformance as any[]).map(row => (
                <tr key={row.subject_id}>
                  <Td>{row.subject_name}<div className="text-xs text-slate-500">{row.subject_code}</div></Td>
                  <Td>{row.average_percentage ? `${Number(row.average_percentage).toFixed(1)}%` : '-'}</Td>
                  <Td>{row.highest ? `${Number(row.highest).toFixed(1)}%` : '-'}</Td>
                  <Td>{row.lowest ? `${Number(row.lowest).toFixed(1)}%` : '-'}</Td>
                  <Td>{row.entered_count}</Td>
                  <Td><span className="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{row.pass_rate ?? 0}%</span></Td>
                </tr>
              ))}
              {(subjectPerformance as any[]).length === 0 && <tr><Td colSpan={6} className="py-8 text-center text-slate-500">No subject performance yet.</Td></tr>}
            </Table>
          )}
        </Card>

        <Card>
          <CardHeader title="Weekly Trend" />
          {trendLoading ? <Spinner /> : (
            <Table headers={['Week', 'Subject', 'Average', 'Highest', 'Lowest', 'Entries']}>
              {(weeklyTrend as any[]).map((row, idx) => (
                <tr key={`${row.year}-${row.week}-${row.subject_id}-${idx}`}>
                  <Td>{row.year} W{row.week}</Td>
                  <Td>{row.subject_name}</Td>
                  <Td>{row.average ? `${Number(row.average).toFixed(1)}%` : '-'}</Td>
                  <Td>{row.highest ? `${Number(row.highest).toFixed(1)}%` : '-'}</Td>
                  <Td>{row.lowest ? `${Number(row.lowest).toFixed(1)}%` : '-'}</Td>
                  <Td>{row.entered_count}</Td>
                </tr>
              ))}
              {(weeklyTrend as any[]).length === 0 && <tr><Td colSpan={6} className="py-8 text-center text-slate-500">No weekly trend data yet.</Td></tr>}
            </Table>
          )}
        </Card>
      </div>
    </div>
  );
}
