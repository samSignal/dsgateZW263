import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, PageHeader, Spinner, StatCard, Table, Td } from '../../components/UI';

export default function HeadmasterDisciplineDashboard() {
  const { data: summary, isLoading } = useQuery({ queryKey: ['hm-discipline-summary'], queryFn: () => api.get('/headmaster/discipline/summary').then(r => r.data) });
  const { data: high = [] } = useQuery<any[]>({ queryKey: ['hm-high'], queryFn: () => api.get('/headmaster/discipline/high-severity').then(r => r.data) });
  const { data: repeated = [] } = useQuery<any[]>({ queryKey: ['hm-repeat'], queryFn: () => api.get('/headmaster/discipline/repeated-offenders').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Headmaster Discipline Dashboard" subtitle="Attendance risks, high severity incidents, repeated offences, and open cases" /><div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6"><StatCard label="Attendance Rate" value={`${summary?.attendance_rate ?? 0}%`} color="green" /><StatCard label="Absent Today" value={summary?.absent_today ?? 0} color="red" /><StatCard label="Late Today" value={summary?.late_today ?? 0} color="amber" /><StatCard label="Open Cases" value={summary?.open_discipline_cases ?? 0} color="blue" /><StatCard label="High Severity" value={summary?.high_severity_incidents ?? 0} color="red" /><StatCard label="Notifications" value={summary?.parent_notifications ?? 0} color="purple" /></div><div className="grid grid-cols-1 gap-5 xl:grid-cols-2"><Card><CardHeader title="High Severity Incidents" /><Table headers={['Student', 'Incident', 'Severity', 'Status']}>{high.map(i => <tr key={i.id}><Td>{i.student_name}</Td><Td>{i.incident_number}<br />{i.title}</Td><Td>{i.severity}</Td><Td>{i.review_status}</Td></tr>)}</Table></Card><Card><CardHeader title="Repeated Offenders" /><Table headers={['Student', 'Student #', 'Negative Incidents']}>{repeated.map(r => <tr key={r.student_id}><Td>{r.student_name}</Td><Td>{r.student_number}</Td><Td>{r.negative_incidents}</Td></tr>)}</Table></Card></div></div>;
}
