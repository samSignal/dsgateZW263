import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, PageHeader, Spinner, Table, Td } from '../../components/UI';

export default function StudentAttendanceBehaviourPage() {
  const { data: attendance = [], isLoading } = useQuery<any[]>({ queryKey: ['student-attendance'], queryFn: () => api.get('/student/discipline/attendance').then(r => r.data) });
  const { data: behaviour = [] } = useQuery<any[]>({ queryKey: ['student-behaviour'], queryFn: () => api.get('/student/discipline/behaviour').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="My Attendance & Behaviour" subtitle="Your attendance history, conduct records, and discipline actions" /><div className="grid grid-cols-1 gap-5 xl:grid-cols-2"><Card><CardHeader title="Attendance" /><Table headers={['Date', 'Session', 'Status', 'Remarks']}>{attendance.map(r => <tr key={r.id}><Td>{r.attendance_date}</Td><Td>{r.session_type}</Td><Td>{r.status}</Td><Td>{r.remarks ?? '-'}</Td></tr>)}</Table></Card><Card><CardHeader title="Behaviour" /><Table headers={['Date', 'Category', 'Title', 'Status']}>{behaviour.map(i => <tr key={i.id}><Td>{i.incident_date}</Td><Td>{i.category_name}</Td><Td>{i.title}</Td><Td>{i.review_status}</Td></tr>)}</Table></Card></div></div>;
}
