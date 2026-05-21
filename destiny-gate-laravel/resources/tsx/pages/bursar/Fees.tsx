import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, Table, Td, Spinner, PageHeader, statusBadge } from '../../components/UI';

export default function Fees() {
  const { data, isLoading } = useQuery({ queryKey: ['fees'], queryFn: () => api.get('/bursar/fees').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return (
    <div>
      <PageHeader title="Student Fees" subtitle="All fee records" />
      <Card>
        <Table headers={['Student', 'Class', 'Year', 'Term', 'Amount', 'Paid', 'Balance', 'Status']}>
          {data?.data?.map((f: any) => (
            <tr key={f.id}>
              <Td><strong>{f.student?.first_name} {f.student?.last_name}</strong></Td>
              <Td>{f.student?.school_class?.class_name ?? '—'}</Td>
              <Td>{f.academic_year}</Td>
              <Td style={{ textTransform: 'uppercase' }}>{f.term}</Td>
              <Td>${Number(f.amount).toLocaleString()}</Td>
              <Td style={{ color: '#1a6b3c' }}>${Number(f.amount_paid).toLocaleString()}</Td>
              <Td style={{ color: Number(f.balance) > 0 ? '#dc2626' : '#1a6b3c', fontWeight: 600 }}>${Number(f.balance).toLocaleString()}</Td>
              <Td>{statusBadge(f.status)}</Td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}
