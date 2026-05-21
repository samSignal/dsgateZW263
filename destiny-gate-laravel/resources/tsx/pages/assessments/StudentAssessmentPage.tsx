import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Alert, Btn, Card, CardBody, CardHeader, Table, Td, Spinner, PageHeader } from '../../components/UI';

const gradeColors: Record<string, string> = { A: '#1a6b3c', B: '#2563eb', C: '#7c3aed', D: '#d97706', E: '#f59e0b', U: '#dc2626' };

export default function StudentAssessmentPage() {
  const [yearId, setYearId] = useState('');
  const [termId, setTermId] = useState('');

  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'],           queryFn: () => api.get('/terms').then(r => r.data) });

  const { data: marks = [], isLoading, error: marksError } = useQuery({
    queryKey: ['student-marks', yearId, termId],
    queryFn: () => api.get('/student/assessments/marks', { params: { academic_year_id: yearId || undefined, term_id: termId || undefined } }).then(r => r.data),
  });

  const { data: progress = [], error: progressError } = useQuery({
    queryKey: ['student-progress'],
    queryFn: () => api.get('/student/assessments/progress').then(r => r.data),
  });

  const filteredTerms = (terms as any[]).filter(t => !yearId || String(t.academic_year_id) === yearId);

  return (
    <div>
      <PageHeader title="My Results" subtitle="Your academic marks and progress" />
      {((marksError as any)?.response?.status === 403 || (progressError as any)?.response?.status === 403) && (
        <Card style={{ marginBottom: 16, borderColor: '#fecaca', background: '#fff7f7' }}>
          <CardBody>
            <div className="font-semibold text-red-800">{(marksError as any)?.response?.data?.message ?? (progressError as any)?.response?.data?.message}</div>
            <div className="mt-2 text-sm text-red-700">Outstanding Balance: ${Number(((marksError as any)?.response?.data?.financial_clearance ?? (progressError as any)?.response?.data?.financial_clearance)?.outstanding_balance ?? 0).toFixed(2)}</div>
            <Btn variant="outline" style={{ marginTop: 12 }}>Contact Bursar</Btn>
          </CardBody>
        </Card>
      )}

      {/* Progress by subject */}
      {(progress as any[]).length > 0 && (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))', gap: 12, marginBottom: 20 }}>
          {(progress as any[]).map((p: any) => (
            <div key={p.subject_name} style={{ background: '#fff', borderRadius: 10, border: '1px solid #e8eaed', padding: '14px 16px' }}>
              <div style={{ fontSize: 11, fontWeight: 700, color: '#374151', marginBottom: 6 }}>{p.subject_name}</div>
              <div style={{ fontSize: 20, fontWeight: 800, color: '#1a6b3c' }}>{p.average ? `${Number(p.average).toFixed(1)}%` : '—'}</div>
              <div style={{ fontSize: 10, color: '#6b7280', marginTop: 3 }}>{p.count} assessments</div>
              <div style={{ height: 4, background: '#f3f4f6', borderRadius: 2, marginTop: 6 }}>
                <div style={{ height: '100%', width: `${p.average ?? 0}%`, background: '#1a6b3c', borderRadius: 2 }} />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Filters */}
      <div style={{ display: 'flex', gap: 10, marginBottom: 16 }}>
        <select value={yearId} onChange={e => { setYearId(e.target.value); setTermId(''); }} style={{ padding: '7px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13 }}>
          <option value="">All Years</option>
          {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </select>
        <select value={termId} onChange={e => setTermId(e.target.value)} style={{ padding: '7px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13 }}>
          <option value="">All Terms</option>
          {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
        </select>
      </div>

      <Card>
        <CardHeader title="My Marks" />
        {isLoading ? <Spinner /> : (
          <Table headers={['Assessment', 'Subject', 'Type', 'Date', 'Mark', '%', 'Grade', 'Teacher Comment']}>
            {(marks as any[]).map((m: any) => (
              <tr key={m.id}>
                <Td><strong>{m.title}</strong><br /><span style={{ fontSize: 11, color: '#6b7280' }}>{m.term_name}</span></Td>
                <Td>{m.subject_name}</Td>
                <Td>{m.type_name}</Td>
                <Td style={{ color: '#6b7280' }}>{m.assessment_date}</Td>
                <Td>{m.mark_obtained !== null ? `${m.mark_obtained}/${m.total_marks}` : m.status === 'absent' ? 'Absent' : '—'}</Td>
                <Td>{m.percentage !== null ? `${m.percentage}%` : '—'}</Td>
                <Td>
                  {m.grade ? (
                    <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 26, height: 26, borderRadius: '50%', background: gradeColors[m.grade] ?? '#6b7280', color: '#fff', fontSize: 11, fontWeight: 700 }}>
                      {m.grade}
                    </span>
                  ) : '—'}
                </Td>
                <Td style={{ color: '#6b7280', fontSize: 12 }}>{m.teacher_comment ?? '—'}</Td>
              </tr>
            ))}
            {(marks as any[]).length === 0 && <tr><Td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af', padding: 24 }}>No marks available yet.</Td></tr>}
          </Table>
        )}
      </Card>
    </div>
  );
}
