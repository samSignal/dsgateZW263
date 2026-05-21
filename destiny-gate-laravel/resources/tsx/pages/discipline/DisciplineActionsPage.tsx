import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Card, CardHeader, PageHeader, Spinner, Table, Td } from '../../components/UI';

export default function DisciplineActionsPage() {
  const { data = [], isLoading } = useQuery<any[]>({ queryKey: ['discipline-actions'], queryFn: () => api.get('/discipline/actions').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Disciplinary Actions" subtitle="Warnings, meetings, suspensions, counselling, and follow-ups" /><Card><CardHeader title="Actions" /><Table headers={['Action', 'Student', 'Incident', 'Type', 'Status', 'Follow Up']}>{data.map(a => <tr key={a.id}><Td><strong>{a.action_number}</strong><br /><span className="text-xs">{a.action_date}</span></Td><Td>{a.student_name}<br /><span className="text-xs text-slate-500">{a.student_number}</span></Td><Td>{a.incident_number}<br /><span className="text-xs">{a.incident_title}</span></Td><Td>{a.action_type.replaceAll('_', ' ')}</Td><Td><Badge variant={a.status === 'open' ? 'amber' : a.status === 'completed' ? 'green' : 'gray'}>{a.status}</Badge></Td><Td>{a.follow_up_date ?? '-'}</Td></tr>)}</Table></Card></div>;
}
