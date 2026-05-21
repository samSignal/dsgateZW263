import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, PageHeader, Spinner, Table, Td } from '../../components/UI';

export default function MyTeachingAllocationsPage() {
  const { data = [], isLoading } = useQuery<any[]>({ queryKey: ['my-teaching-allocations'], queryFn: () => api.get('/academic-foundation/teacher-allocations').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="My Teaching Allocations" subtitle="Subjects and streams assigned to you" /><Card><CardHeader title="Allocations" /><Table headers={['Subject', 'Stream', 'Year', 'Term']}>{data.map(a => <tr key={a.id}><Td>{a.subject_name}<br /><span className="text-xs text-slate-500">{a.subject_code}</span></Td><Td>{a.form_name} {a.stream_name}</Td><Td>{a.academic_year_name}</Td><Td>{a.term_name}</Td></tr>)}</Table></Card></div>;
}
