import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { Badge, Btn, Card, PageHeader, Select, Table, Td } from '../../components/UI';

export default function StreamRankingPage() {
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', stream_id: '' });
  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'], queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'], queryFn: () => api.get('/streams').then(r => r.data) });
  const enabled = !!filters.academic_year_id && !!filters.term_id && !!filters.stream_id;
  const { data = [] } = useQuery({ queryKey: ['stream-rankings', filters], queryFn: () => api.get('/reports/rankings/stream', { params: filters }).then(r => r.data), enabled });
  const filteredTerms = (terms as any[]).filter(t => !filters.academic_year_id || String(t.academic_year_id) === filters.academic_year_id);

  return <div>
    <PageHeader title="Stream Rankings" subtitle="Overall term ranking with shared positions for tied averages" />
    <div className="mb-4 flex flex-wrap gap-3">
      <Select value={filters.academic_year_id} onChange={e => setFilters(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))} style={{ width: 160 }}><option value="">Year</option>{(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}</Select>
      <Select value={filters.term_id} onChange={e => setFilters(f => ({ ...f, term_id: e.target.value }))} style={{ width: 150 }}><option value="">Term</option>{filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}</Select>
      <Select value={filters.stream_id} onChange={e => setFilters(f => ({ ...f, stream_id: e.target.value }))} style={{ width: 180 }}><option value="">Stream</option>{(streams as any[]).map(s => <option key={s.id} value={s.id}>{s.form_name ?? ''} {s.name}</option>)}</Select>
    </div>
    <Card>
      <Table headers={['Rank', 'Student', 'Average', 'Grade', 'Trend', 'Status', 'Report']}>
        {(data as any[]).map(r => <tr key={r.id}><Td><Badge variant="amber">#{r.class_position}</Badge></Td><Td>{r.student_name}<div className="text-xs text-slate-500">{r.student_number}</div></Td><Td>{r.overall_average}%</Td><Td>{r.overall_grade}</Td><Td>{r.performance_trend}</Td><Td>{r.status}</Td><Td><Link to={`/app/reports/${r.id}`}><Btn size="sm" variant="outline">Preview</Btn></Link></Td></tr>)}
        {enabled && (data as any[]).length === 0 && <tr><Td colSpan={7} style={{ textAlign: 'center', color: '#64748b' }}>No generated reports for this stream yet.</Td></tr>}
      </Table>
    </Card>
  </div>;
}
