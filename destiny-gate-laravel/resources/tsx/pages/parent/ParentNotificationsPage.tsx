import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, PageHeader, Spinner, Table, Td } from '../../components/UI';

export default function ParentNotificationsPage() {
  const { data, isLoading } = useQuery({ queryKey: ['parent-notifications'], queryFn: () => api.get('/parent/discipline/summary').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Parent Notifications" subtitle="Portal notices for attendance, behaviour, and discipline" /><Card><CardHeader title="Notifications" /><Table headers={['Type', 'Title', 'Message', 'Date']}>{(data?.notifications ?? []).map((n: any) => <tr key={n.id}><Td>{n.notification_type}</Td><Td>{n.title}</Td><Td>{n.message}</Td><Td>{n.created_at}</Td></tr>)}</Table></Card></div>;
}
