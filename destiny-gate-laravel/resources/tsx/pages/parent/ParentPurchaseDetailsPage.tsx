import React from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Card, CardBody, CardHeader, PageHeader, Spinner, Table, Td } from '../../components/UI';

const money = (value: any) => `$${Number(value ?? 0).toFixed(2)}`;

export default function ParentPurchaseDetailsPage() {
  const { studentId, purchaseId } = useParams();

  const { data: purchase, isLoading } = useQuery({
    queryKey: ['parent-shop-purchase', studentId, purchaseId],
    queryFn: () => api.get(`/parent/shop/child/${studentId}/purchases/${purchaseId}`).then(r => r.data),
    enabled: !!studentId && !!purchaseId,
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader
        title={`Purchase ${purchase.purchase_number}`}
        subtitle="School shop purchase details for your linked child"
      />

      <div className="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_360px]">
        <Card>
          <CardHeader title="Items Bought" />
          <Table headers={['Item', 'Category', 'Quantity', 'Unit Price', 'Line Total']}>
            {(purchase.items ?? []).map((item: any) => (
              <tr key={item.id}>
                <Td>
                  <strong>{item.item_name}</strong>
                  <br />
                  <span className="text-xs text-slate-500">{item.item_code}</span>
                </Td>
                <Td>{item.category_name}</Td>
                <Td>{item.quantity}</Td>
                <Td>{money(item.unit_price)}</Td>
                <Td><strong>{money(item.line_total)}</strong></Td>
              </tr>
            ))}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Purchase Summary" />
          <CardBody>
            <div className="space-y-3 text-sm">
              <div className="flex justify-between gap-3">
                <span className="text-slate-500">Date</span>
                <strong>{purchase.purchase_date}</strong>
              </div>
              <div className="flex justify-between gap-3">
                <span className="text-slate-500">Status</span>
                <Badge variant={purchase.status === 'paid' ? 'green' : purchase.status === 'partial' ? 'amber' : purchase.status === 'cancelled' ? 'gray' : 'red'}>
                  {purchase.status}
                </Badge>
              </div>
              <div className="flex justify-between gap-3">
                <span className="text-slate-500">Total</span>
                <strong>{money(purchase.total_amount)}</strong>
              </div>
              <div className="flex justify-between gap-3">
                <span className="text-slate-500">Paid</span>
                <strong className="text-emerald-700">{money(purchase.amount_paid)}</strong>
              </div>
              <div className="flex justify-between gap-3 border-t border-slate-100 pt-3">
                <span className="text-slate-500">Balance</span>
                <strong className={Number(purchase.balance) > 0 ? 'text-red-600' : 'text-emerald-700'}>
                  {money(purchase.balance)}
                </strong>
              </div>
            </div>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
