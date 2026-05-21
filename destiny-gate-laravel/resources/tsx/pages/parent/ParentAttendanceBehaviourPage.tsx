import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, PageHeader, Spinner, StatCard, Table, Td } from '../../components/UI';

export default function ParentAttendanceBehaviourPage() {
  const { data, isLoading } = useQuery({ queryKey: ['parent-discipline-summary'], queryFn: () => api.get('/parent/discipline/summary').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Attendance & Behaviour" subtitle="Attendance, behaviour, actions, and notices for your linked children" /><div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3"><StatCard label="Absences" value={data?.absences ?? 0} color="red" /><StatCard label="Late Records" value={data?.late ?? 0} color="amber" /><StatCard label="Open Actions" value={data?.open_actions ?? 0} color="blue" /></div><Card><CardHeader title="Children" /><Table headers={['Child', 'Student #', 'Class']}>{(data?.children ?? []).map((c: any) => <tr key={c.id}><Td>{c.first_name} {c.last_name}</Td><Td>{c.student_number}</Td><Td>{c.class_name} {c.stream}</Td></tr>)}</Table></Card></div>;
}
