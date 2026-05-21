import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, PageHeader, Spinner, Table, Td } from '../../components/UI';

export default function StudentNotificationsPage() {
  const { data = [], isLoading } = useQuery<any[]>({ queryKey: ['student-notifications'], queryFn: () => api.get('/student/discipline/notifications').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="My Notifications" subtitle="Attendance, behaviour, and discipline notices" /><Card><CardHeader title="Notifications" /><Table headers={['Type', 'Title', 'Message', 'Date']}>{data.map(n => <tr key={n.id}><Td>{n.notification_type}</Td><Td>{n.title}</Td><Td>{n.message}</Td><Td>{n.created_at}</Td></tr>)}</Table></Card></div>;
}
