import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, Table, Td, Spinner, PageHeader, statusBadge } from '../../components/UI';

export default function Debtors() {
  const { data, isLoading } = useQuery({ queryKey: ['debtors'], queryFn: () => api.get('/bursar/reports/debtors').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return (
    <div>
      <PageHeader title="Debtors List" subtitle="Students with outstanding fee balances" />
      <Card>
        <Table headers={['Student', 'Class', 'Term', 'Amount', 'Paid', 'Balance', 'Status']}>
          {data?.map((f: any) => (
            <tr key={f.id}>
              <Td><strong>{f.student?.first_name} {f.student?.last_name}</strong></Td>
              <Td>{f.student?.school_class?.class_name ?? '—'}</Td>
              <Td style={{ textTransform: 'uppercase' }}>{f.term}</Td>
              <Td>${Number(f.amount).toLocaleString()}</Td>
              <Td>${Number(f.amount_paid).toLocaleString()}</Td>
              <Td style={{ color: '#dc2626', fontWeight: 700 }}>${Number(f.balance).toLocaleString()}</Td>
              <Td>{statusBadge(f.status)}</Td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}
