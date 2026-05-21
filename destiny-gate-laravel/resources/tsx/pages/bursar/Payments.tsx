import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, Table, Td, Spinner, PageHeader } from '../../components/UI';

export default function Payments() {
  const { data, isLoading } = useQuery({ queryKey: ['payments'], queryFn: () => api.get('/bursar/payments').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return (
    <div>
      <PageHeader title="Payment History" subtitle="All recorded payments" />
      <Card>
        <Table headers={['Receipt #', 'Student', 'Amount', 'Method', 'Date', 'Recorded By']}>
          {data?.data?.map((p: any) => (
            <tr key={p.id}>
              <Td><code style={{ background: '#f3f4f6', padding: '2px 7px', borderRadius: 5, fontSize: 12 }}>{p.receipt_number}</code></Td>
              <Td><strong>{p.student?.first_name} {p.student?.last_name}</strong></Td>
              <Td style={{ color: '#1a6b3c', fontWeight: 600 }}>${Number(p.amount).toLocaleString()}</Td>
              <Td style={{ textTransform: 'capitalize' }}>{p.payment_method?.replace(/_/g, ' ')}</Td>
              <Td style={{ color: '#6b7280' }}>{new Date(p.payment_date).toLocaleDateString()}</Td>
              <Td>{p.recorded_by?.name}</Td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}
