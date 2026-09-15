import React from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { confirmAction, toastError, toastSuccess } from '../../lib/toast';
import { Badge, Btn, Card, Empty, PageHeader, Spinner, Table, Td } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function PreordersPage() {
  const qc = useQueryClient();
  const { data: preorders = [], isLoading } = useQuery<any[]>({
    queryKey: ['shop-preorders'],
    queryFn: () => api.get('/shop/purchases/preorders').then(r => r.data),
  });

  const fulfill = useMutation({
    mutationFn: (id: number) => api.post(`/shop/purchases/${id}/fulfill`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-preorders'] }); qc.invalidateQueries({ queryKey: ['shop-items'] }); toastSuccess('Preorder fulfilled — stock allocated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not fulfill preorder.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Uniform Preorders" subtitle="Orders placed for items that were out of stock, waiting on new stock to arrive" />
      <Card>
        {preorders.length === 0 ? <Empty message="No pending preorders." /> : (
          <Table headers={['Purchase #', 'Student', 'Items Requested', 'Paid / Total', 'Readiness', 'Actions']}>
            {preorders.map((p: any) => (
              <tr key={p.id}>
                <Td><Link to={`/app/shop/purchases/${p.id}`} className="font-semibold text-emerald-700 no-underline">{p.purchase_number}</Link><br /><span className="text-xs text-slate-500">{p.purchase_date}</span></Td>
                <Td><strong>{p.student_name}</strong><br /><span className="text-xs text-slate-500">{p.student_number ?? p.admission_number}</span></Td>
                <Td>
                  {(p.items ?? []).map((i: any, idx: number) => (
                    <div key={idx} className="text-xs">
                      {i.quantity}x {i.item_name}{i.size ? ` (${i.size})` : ''} — <span className={Number(i.quantity_in_stock) >= Number(i.quantity) ? 'text-emerald-700' : 'text-red-600'}>{i.quantity_in_stock} in stock</span>
                    </div>
                  ))}
                </Td>
                <Td>{money(p.amount_paid)} / {money(p.total_amount)} <Badge variant={p.payment_status === 'paid' ? 'green' : 'amber'}>{p.payment_status}</Badge></Td>
                <Td>{p.ready_to_fulfill ? <Badge variant="green">Ready to Fulfill</Badge> : <Badge variant="gray">Awaiting Stock</Badge>}</Td>
                <Td>
                  <Btn size="sm" disabled={!p.ready_to_fulfill} loading={fulfill.isPending} onClick={async () => { if (await confirmAction('Fulfill this preorder?', 'Stock will be pulled for this order now that enough is available.', 'Fulfill')) fulfill.mutate(p.id); }}>Fulfill</Btn>
                </Td>
              </tr>
            ))}
          </Table>
        )}
      </Card>
    </div>
  );
}
