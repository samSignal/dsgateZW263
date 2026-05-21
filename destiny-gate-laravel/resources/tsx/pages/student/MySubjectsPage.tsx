import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Card, CardHeader, PageHeader, Spinner, Table, Td } from '../../components/UI';

export default function MySubjectsPage() {
  const { data = [], isLoading } = useQuery<any[]>({ queryKey: ['my-subjects'], queryFn: () => api.get('/student/subjects').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="My Subjects" subtitle="Active subjects selected for marks entry and reports" /><Card><CardHeader title="Enrolled Subjects" /><Table headers={['Subject', 'Code', 'Type', 'Term']}>{data.map(s => <tr key={s.id}><Td>{s.subject_name}</Td><Td>{s.subject_code}</Td><Td><Badge variant={s.is_compulsory ? 'green' : 'blue'}>{s.is_compulsory ? 'Compulsory' : 'Optional'}</Badge></Td><Td>{s.term_name}</Td></tr>)}</Table></Card></div>;
}
