import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Btn, Badge, statusBadge } from '../../components/UI';

const gradeColors: Record<string, string> = { A: '#0f1a2e', B: '#2563eb', C: '#7c3aed', D: '#d97706', E: '#f59e0b', U: '#dc2626' };

export default function AssessmentDetailsPage() {
  const { id } = useParams<{ id: string }>();

  const { data: assessment, isLoading } = useQuery({
    queryKey: ['assessment', id],
    queryFn: () => api.get(`/assessments/${id}`).then(r => r.data),
  });

  const { data: perf } = useQuery({
    queryKey: ['assessment-perf', id],
    queryFn: () => api.get(`/assessments/${id}/performance`).then(r => r.data),
    enabled: !!assessment && ['submitted', 'approved'].includes(assessment.status),
  });

  if (isLoading) return <Spinner />;
  if (!assessment) return <div>Assessment not found.</div>;

  const marks = assessment.marks ?? [];
  const entered = marks.filter((m: any) => m.status === 'entered');

  return (
    <div>
      <PageHeader
        title={assessment.title}
        subtitle={`${assessment.assessment_number} · ${assessment.subject_name} · ${assessment.form_name} ${assessment.stream_name}`}
        action={
          <div style={{ display: 'flex', gap: 8 }}>
            {['open', 'draft'].includes(assessment.status) && (
              <Link to={`/app/assessments/${id}/marks`}><Btn>Enter Marks</Btn></Link>
            )}
            <Btn variant="outline" onClick={() => window.history.back()}>← Back</Btn>
          </div>
        }
      />

      {/* Summary cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: 12, marginBottom: 20 }}>
        {[
          { label: 'Total Students', value: marks.length, color: '#2563eb' },
          { label: 'Marks Entered',  value: entered.length, color: '#8a6b34' },
          { label: 'Absent',         value: marks.filter((m: any) => m.status === 'absent').length, color: '#dc2626' },
          { label: 'Average',        value: perf?.summary?.average ? `${perf.summary.average}%` : '—', color: '#7c3aed' },
          { label: 'Pass Rate',      value: perf?.summary?.pass_rate ? `${perf.summary.pass_rate}%` : '—', color: '#059669' },
        ].map(c => (
          <div key={c.label} style={{ background: '#fff', borderRadius: 10, border: '1px solid #e8eaed', padding: '14px 16px' }}>
            <div style={{ fontSize: 10, fontWeight: 700, color: '#6b7280', textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: 6 }}>{c.label}</div>
            <div style={{ fontSize: 22, fontWeight: 800, color: c.color }}>{c.value}</div>
          </div>
        ))}
      </div>

      {/* Marks table */}
      <Card>
        <CardHeader title="Student Marks" />
        <Table headers={['#', 'Student', 'Mark', '%', 'Grade', 'Status', 'Comment']}>
          {marks.map((m: any, i: number) => (
            <tr key={m.student_id}>
              <Td style={{ color: '#9ca3af' }}>{i + 1}</Td>
              <Td>
                <div style={{ fontWeight: 600 }}>{m.student_name}</div>
                <div style={{ fontSize: 11, color: '#6b7280' }}>{m.student_number}</div>
              </Td>
              <Td>{m.mark_obtained !== null ? `${m.mark_obtained}/${assessment.total_marks}` : '—'}</Td>
              <Td>{m.percentage !== null ? `${m.percentage}%` : '—'}</Td>
              <Td>
                {m.grade ? (
                  <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 26, height: 26, borderRadius: '50%', background: gradeColors[m.grade] ?? '#6b7280', color: '#fff', fontSize: 11, fontWeight: 700 }}>
                    {m.grade}
                  </span>
                ) : '—'}
              </Td>
              <Td>{statusBadge(m.status)}</Td>
              <Td style={{ color: '#6b7280', fontSize: 12 }}>{m.teacher_comment ?? '—'}</Td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}
