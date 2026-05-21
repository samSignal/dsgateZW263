import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Card, CardHeader, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

export default function ChildSubjectsPage() {
  const [childId, setChildId] = useState('');
  const { data: summary, isLoading } = useQuery({ queryKey: ['parent-discipline-summary'], queryFn: () => api.get('/parent/discipline/summary').then(r => r.data) });
  const children = summary?.children ?? [];
  const selectedId = childId || String(children[0]?.id ?? '');
  const { data: subjects = [] } = useQuery<any[]>({ queryKey: ['child-subjects', selectedId], queryFn: () => api.get(`/parent/subjects/child/${selectedId}`).then(r => r.data), enabled: !!selectedId });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Child Subjects" subtitle="Subjects enrolled for your linked child" /><div className="mb-4 max-w-sm"><Select value={selectedId} onChange={e => setChildId(e.target.value)}>{children.map((c: any) => <option key={c.id} value={c.id}>{c.first_name} {c.last_name}</option>)}</Select></div><Card><CardHeader title="Subjects" /><Table headers={['Subject', 'Code', 'Type', 'Term']}>{subjects.map(s => <tr key={s.id}><Td>{s.subject_name}</Td><Td>{s.subject_code}</Td><Td><Badge variant={s.is_compulsory ? 'green' : 'blue'}>{s.is_compulsory ? 'Compulsory' : 'Optional'}</Badge></Td><Td>{s.term_name}</Td></tr>)}</Table></Card></div>;
}
