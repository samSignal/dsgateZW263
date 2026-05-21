import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, Table, Td, Spinner, PageHeader, statusBadge, Empty } from '../../components/UI';

export default function StudentPortal() {
  const { data, isLoading } = useQuery({ queryKey: ['student-portal'], queryFn: () => api.get('/student/portal').then(r => r.data) });
  if (isLoading) return <Spinner />;
  if (!data?.student) return <Empty message="No student profile found. Contact the school administration." />;

  const { student, announcements } = data;

  return (
    <div>
      <PageHeader title="Student Portal" subtitle={`${student.first_name} ${student.last_name} · ${student.admission_number} · ${student.school_class?.class_name ?? 'Unassigned'}`} />

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginBottom: 20 }}>
        <Card>
          <CardHeader title="My Results" />
          <Table headers={['Subject', 'Term', 'Type', '%', 'Grade']}>
            {student.academic_progress?.map((p: any) => (
              <tr key={p.id}>
                <Td>{p.subject?.subject_name}</Td>
                <Td style={{ textTransform: 'uppercase' }}>{p.term}</Td>
                <Td style={{ textTransform: 'capitalize' }}>{p.assessment_type?.replace(/_/g, ' ')}</Td>
                <Td>{p.percentage}%</Td>
                <Td><strong>{p.grade}</strong></Td>
              </tr>
            ))}
          </Table>
        </Card>

        <Card>
          <CardHeader title="My Timetable" />
          <Table headers={['Day', 'Period', 'Subject', 'Teacher', 'Time']}>
            {student.school_class?.timetables?.map((t: any) => (
              <tr key={t.id}>
                <Td style={{ textTransform: 'capitalize' }}>{t.day_of_week}</Td>
                <Td>{t.period_number}</Td>
                <Td>{t.subject?.subject_name}</Td>
                <Td>{t.teacher?.first_name} {t.teacher?.last_name}</Td>
                <Td style={{ color: '#6b7280' }}>{t.start_time} – {t.end_time}</Td>
              </tr>
            ))}
          </Table>
        </Card>
      </div>

      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="My Attendance" />
        <Table headers={['Date', 'Status', 'Remarks']}>
          {student.attendance?.slice(0, 20).map((a: any) => (
            <tr key={a.id}>
              <Td>{new Date(a.date).toLocaleDateString()}</Td>
              <Td>{statusBadge(a.status)}</Td>
              <Td style={{ color: '#6b7280' }}>{a.remarks ?? '—'}</Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Card>
        <CardHeader title="School Notices" />
        <div style={{ padding: 20 }}>
          {announcements?.map((a: any) => (
            <div key={a.id} style={{ padding: '12px 0', borderBottom: '1px solid #f3f4f6' }}>
              <div style={{ fontWeight: 600, fontSize: 14, marginBottom: 4 }}>{a.title}</div>
              <p style={{ fontSize: 13, color: '#374151', margin: 0 }}>{a.content}</p>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
}
