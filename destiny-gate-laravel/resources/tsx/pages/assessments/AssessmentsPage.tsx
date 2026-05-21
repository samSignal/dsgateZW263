import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Select, statusBadge } from '../../components/UI';

const statusColors: Record<string, 'gray' | 'blue' | 'amber' | 'green' | 'red'> = {
  draft: 'gray', open: 'blue', submitted: 'amber', approved: 'green', cancelled: 'red',
};

export default function AssessmentsPage() {
  const qc = useQueryClient();
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', stream_id: '', subject_id: '', status: '' });

  const { data: years = [] }   = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] }   = useQuery({ queryKey: ['terms'],           queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'],         queryFn: () => api.get('/streams').then(r => r.data) });
  const { data: subjects = [] }= useQuery({ queryKey: ['subjects'],        queryFn: () => api.get('/subjects').then(r => r.data) });

  const { data = [], isLoading } = useQuery({
    queryKey: ['assessments', filters],
    queryFn: () => api.get('/assessments', { params: filters }).then(r => r.data),
  });

  const filteredTerms = (terms as any[]).filter(t => !filters.academic_year_id || String(t.academic_year_id) === filters.academic_year_id);

  const approve = useMutation({
    mutationFn: (id: number) => api.post(`/assessments/${id}/approve`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['assessments'] }); toastSuccess('Assessment approved.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const reopen = useMutation({
    mutationFn: (id: number) => api.post(`/assessments/${id}/reopen`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['assessments'] }); toastSuccess('Assessment reopened.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const cancel = useMutation({
    mutationFn: (id: number) => api.post(`/assessments/${id}/cancel`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['assessments'] }); toastSuccess('Assessment cancelled.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const assessments = data as any[];

  return (
    <div>
      <PageHeader title="Assessments" subtitle="All assessments across streams and subjects"
        action={<Link to="/app/assessments/create"><Btn>+ Create Assessment</Btn></Link>} />

      <div style={{ display: 'flex', gap: 10, marginBottom: 16, flexWrap: 'wrap' }}>
        <Select value={filters.academic_year_id} onChange={e => setFilters(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))} style={{ width: 150 }}>
          <option value="">All Years</option>
          {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </Select>
        <Select value={filters.term_id} onChange={e => setFilters(f => ({ ...f, term_id: e.target.value }))} style={{ width: 140 }}>
          <option value="">All Terms</option>
          {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={filters.stream_id} onChange={e => setFilters(f => ({ ...f, stream_id: e.target.value }))} style={{ width: 160 }}>
          <option value="">All Streams</option>
          {(streams as any[]).map(s => <option key={s.id} value={s.id}>{s.form_name} — {s.name}</option>)}
        </Select>
        <Select value={filters.subject_id} onChange={e => setFilters(f => ({ ...f, subject_id: e.target.value }))} style={{ width: 180 }}>
          <option value="">All Subjects</option>
          {(subjects as any[]).map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
        </Select>
        <Select value={filters.status} onChange={e => setFilters(f => ({ ...f, status: e.target.value }))} style={{ width: 140 }}>
          <option value="">All Statuses</option>
          {['draft','open','submitted','approved','cancelled'].map(s => <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>)}
        </Select>
      </div>

      <Card>
        {isLoading ? <Spinner /> : (
          <Table headers={['Assessment #', 'Title', 'Subject', 'Stream', 'Type', 'Date', 'Marks', 'Progress', 'Status', 'Actions']}>
            {assessments.map(a => (
              <tr key={a.id}>
                <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{a.assessment_number}</code></Td>
                <Td><strong>{a.title}</strong></Td>
                <Td>{a.subject_name} <span style={{ fontSize: 11, color: '#9ca3af' }}>({a.subject_code})</span></Td>
                <Td>{a.form_name} — {a.stream_name}</Td>
                <Td>{a.type_name}</Td>
                <Td style={{ color: '#6b7280' }}>{a.assessment_date}</Td>
                <Td>{a.total_marks}</Td>
                <Td>
                  <span style={{ fontSize: 12 }}>{a.entered_count}/{a.marks_count}</span>
                  <div style={{ width: 60, height: 4, background: '#f3f4f6', borderRadius: 2, marginTop: 3 }}>
                    <div style={{ height: '100%', width: a.marks_count > 0 ? `${(a.entered_count / a.marks_count) * 100}%` : '0%', background: '#1a6b3c', borderRadius: 2 }} />
                  </div>
                </Td>
                <Td><Badge variant={statusColors[a.status] ?? 'gray'} style={{ textTransform: 'capitalize' }}>{a.status}</Badge></Td>
                <Td>
                  <div style={{ display: 'flex', gap: 5, flexWrap: 'wrap' }}>
                    <Link to={`/app/assessments/${a.id}`}><Btn size="sm" variant="outline">View</Btn></Link>
                    {(a.status === 'open' || a.status === 'draft') && (
                      <Link to={`/app/assessments/${a.id}/marks`}><Btn size="sm">Enter Marks</Btn></Link>
                    )}
                    {a.status === 'submitted' && (
                      <Btn size="sm" onClick={async () => { if (await confirmAction('Approve?', `Approve "${a.title}"?`, 'Approve')) approve.mutate(a.id); }}>Approve</Btn>
                    )}
                    {(a.status === 'submitted' || a.status === 'approved') && (
                      <Btn size="sm" variant="outline" onClick={async () => { if (await confirmAction('Reopen?', 'Reopen for editing?', 'Reopen')) reopen.mutate(a.id); }}>Reopen</Btn>
                    )}
                    {a.status !== 'cancelled' && a.status !== 'approved' && (
                      <Btn size="sm" variant="danger" onClick={async () => { if (await confirmAction('Cancel?', `Cancel "${a.title}"?`, 'Cancel')) cancel.mutate(a.id); }}>Cancel</Btn>
                    )}
                  </div>
                </Td>
              </tr>
            ))}
            {assessments.length === 0 && <tr><Td colSpan={10} style={{ textAlign: 'center', color: '#9ca3af', padding: 32 }}>No assessments found.</Td></tr>}
          </Table>
        )}
      </Card>
    </div>
  );
}
