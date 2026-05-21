import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Card, CardHeader, Empty, PageHeader, Spinner, Table, Td } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function ParentPurchasesPage() {
  const { data, isLoading } = useQuery({ queryKey: ['parent-shop-purchases'], queryFn: () => api.get('/parent/shop/purchases').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return (
    <div>
      <PageHeader title="My Children Purchases" subtitle="School shop items bought for your linked children" />
      <Card>
        <CardHeader title="Purchase History" />
        {(data?.purchases ?? []).length === 0 ? <Empty message="No purchases found for your children." /> : (
          <Table headers={['Date', 'Child', 'Purchase', 'Total', 'Paid', 'Balance', 'Status']}>
            {data.purchases.map((p: any) => (
              <tr key={p.id}>
                <Td>{p.purchase_date}</Td>
                <Td><strong>{p.student_name}</strong><br /><span className="text-xs text-slate-500">{p.student_number}</span></Td>
                <Td><Link className="font-semibold text-emerald-700" to={`/app/parent/shop/purchases/${p.student_id}/${p.id}`}>{p.purchase_number}</Link></Td>
                <Td>{money(p.total_amount)}</Td>
                <Td>{money(p.amount_paid)}</Td>
                <Td><strong className={Number(p.balance) > 0 ? 'text-red-600' : 'text-emerald-700'}>{money(p.balance)}</strong></Td>
                <Td><Badge variant={p.status === 'paid' ? 'green' : p.status === 'partial' ? 'amber' : p.status === 'cancelled' ? 'gray' : 'red'}>{p.status}</Badge></Td>
              </tr>
            ))}
          </Table>
        )}
      </Card>
    </div>
  );
}
