import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { StatCard, Card, CardHeader, Table, Td, Spinner, PageHeader, statusBadge, Empty } from '../../components/UI';

export default function ParentPortal() {
  const { data, isLoading } = useQuery({ queryKey: ['parent-portal'], queryFn: () => api.get('/parent/portal').then(r => r.data) });
  if (isLoading) return <Spinner />;
  if (!data?.student) return <Empty message="No student linked to your account. Contact the school administration." />;

  const { student, stats, announcements } = data;

  return (
    <div>
      <PageHeader title="Parent Portal" subtitle={`Monitoring: ${student.first_name} ${student.last_name} · ${student.school_class?.class_name ?? 'Unassigned'}`} />

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16, marginBottom: 24 }}>
        <StatCard label="Outstanding Fees"  value={`$${Number(stats.outstanding_fees).toLocaleString()}`} icon="💰" color="red" />
        <StatCard label="Attendance Rate"   value={`${stats.attendance_rate}%`} icon="📅" color="green" />
        <StatCard label="Average Grade"     value={stats.avg_grade} icon="📚" color="blue" />
        <StatCard label="Behaviour Cases"   value={stats.behaviour_cases} icon="⚠️" color="amber" />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginBottom: 20 }}>
        <Card>
          <CardHeader title="Fee Statement" />
          <Table headers={['Year', 'Term', 'Amount', 'Paid', 'Balance', 'Status']}>
            {student.fees?.map((f: any) => (
              <tr key={f.id}>
                <Td>{f.academic_year}</Td>
                <Td style={{ textTransform: 'uppercase' }}>{f.term}</Td>
                <Td>${Number(f.amount).toLocaleString()}</Td>
                <Td style={{ color: '#1a6b3c' }}>${Number(f.amount_paid).toLocaleString()}</Td>
                <Td style={{ color: Number(f.balance) > 0 ? '#dc2626' : '#1a6b3c', fontWeight: 600 }}>${Number(f.balance).toLocaleString()}</Td>
                <Td>{statusBadge(f.status)}</Td>
              </tr>
            ))}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Academic Results" />
          <Table headers={['Subject', 'Term', '%', 'Grade']}>
            {student.academic_progress?.map((p: any) => (
              <tr key={p.id}>
                <Td>{p.subject?.subject_name}</Td>
                <Td style={{ textTransform: 'uppercase' }}>{p.term}</Td>
                <Td>{p.percentage}%</Td>
                <Td><strong>{p.grade}</strong></Td>
              </tr>
            ))}
          </Table>
        </Card>
      </div>

      <Card>
        <CardHeader title="School Notices" />
        <div style={{ padding: 20 }}>
          {announcements?.length === 0 && <Empty message="No announcements." />}
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
