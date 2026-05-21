import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { PageHeader, StatCard, Card, CardHeader, Table, Td, Spinner, Btn } from '../../components/UI';

export default function AttendanceDashboard() {
  const { data: summary, isLoading } = useQuery({ queryKey: ['attendance-summary'], queryFn: () => api.get('/discipline/attendance/reports/summary').then(r => r.data) });
  const { data: sessions = [] } = useQuery({ queryKey: ['attendance-sessions'], queryFn: () => api.get('/discipline/attendance/sessions', { params: { limit: 8 } }).then(r => r.data) });
  if (isLoading) return <Spinner />;
  return (
    <div>
      <PageHeader title="Attendance Dashboard" subtitle="Daily register status, absenteeism, and late-coming overview" action={<Link to="/app/discipline/attendance/sessions"><Btn>Open Registers</Btn></Link>} />
      <div className="grid grid-cols-1 gap-4 md:grid-cols-4 mb-6">
        <StatCard label="Attendance Rate Today" value={`${summary?.attendance_rate_today ?? 0}%`} color="green" />
        <StatCard label="Absent Today" value={summary?.absent_today ?? 0} color="red" />
        <StatCard label="Late Today" value={summary?.late_today ?? 0} color="amber" />
        <StatCard label="Draft Sessions" value={summary?.draft_sessions_today ?? 0} color="blue" />
      </div>
      <Card>
        <CardHeader title="Recent Attendance Sessions" action={<Link className="text-sm font-semibold text-emerald-700" to="/app/discipline/attendance/reports">Reports</Link>} />
        <Table headers={['Date', 'Class', 'Session', 'Status', 'Taken By', 'Action']}>
          {(sessions as any[]).map(s => <tr key={s.id}><Td>{s.attendance_date}</Td><Td>{s.form_name} {s.stream_name}</Td><Td>{s.session_type}</Td><Td>{s.status}</Td><Td>{s.taken_by_name}</Td><Td><Link className="font-semibold text-emerald-700" to={`/app/discipline/attendance/sessions/${s.id}`}>Mark</Link></Td></tr>)}
        </Table>
      </Card>
    </div>
  );
}
