import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Card, PageHeader, Select, Table, Td } from '../../components/UI';

export default function SubjectRankingPage() {
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', stream_id: '', subject_id: '' });
  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'], queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'], queryFn: () => api.get('/streams').then(r => r.data) });
  const { data: subjects = [] } = useQuery({ queryKey: ['subjects'], queryFn: () => api.get('/subjects').then(r => r.data) });
  const enabled = !!filters.academic_year_id && !!filters.term_id && !!filters.stream_id;
  const { data = [] } = useQuery({ queryKey: ['subject-rankings', filters], queryFn: () => api.get('/reports/rankings/subject', { params: filters }).then(r => r.data), enabled });
  const filteredTerms = (terms as any[]).filter(t => !filters.academic_year_id || String(t.academic_year_id) === filters.academic_year_id);

  return <div>
    <PageHeader title="Subject Rankings" subtitle="Subject positions within each stream, with tied averages sharing the same position" />
    <div className="mb-4 flex flex-wrap gap-3">
      <Select value={filters.academic_year_id} onChange={e => setFilters(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))} style={{ width: 150 }}><option value="">Year</option>{(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}</Select>
      <Select value={filters.term_id} onChange={e => setFilters(f => ({ ...f, term_id: e.target.value }))} style={{ width: 140 }}><option value="">Term</option>{filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}</Select>
      <Select value={filters.stream_id} onChange={e => setFilters(f => ({ ...f, stream_id: e.target.value }))} style={{ width: 170 }}><option value="">Stream</option>{(streams as any[]).map(s => <option key={s.id} value={s.id}>{s.form_name ?? ''} {s.name}</option>)}</Select>
      <Select value={filters.subject_id} onChange={e => setFilters(f => ({ ...f, subject_id: e.target.value }))} style={{ width: 180 }}><option value="">All Subjects</option>{(subjects as any[]).map(s => <option key={s.id} value={s.id}>{s.name}</option>)}</Select>
    </div>
    <Card>
      <Table headers={['Subject', 'Position', 'Student', 'Average', 'Grade']}>
        {(data as any[]).map((r, i) => <tr key={`${r.subject_id}-${r.student_id}-${i}`}><Td>{r.subject_name}<div className="text-xs text-slate-500">{r.subject_code}</div></Td><Td><Badge variant="green">#{r.subject_position}</Badge></Td><Td>{r.student_name}<div className="text-xs text-slate-500">{r.student_number}</div></Td><Td>{r.subject_average}%</Td><Td>{r.subject_grade}</Td></tr>)}
        {enabled && (data as any[]).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#64748b' }}>No subject ranking data yet.</Td></tr>}
      </Table>
    </Card>
  </div>;
}
