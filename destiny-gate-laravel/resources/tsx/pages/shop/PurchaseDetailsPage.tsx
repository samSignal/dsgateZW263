import React from 'react';
import { Link, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { confirmAction, toastError, toastSuccess } from '../../lib/toast';
import { Badge, Btn, Card, CardBody, CardHeader, PageHeader, Spinner, Table, Td, statusBadge } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function PurchaseDetailsPage() {
  const { id } = useParams();
  const qc = useQueryClient();
  const { data: p, isLoading } = useQuery({ queryKey: ['shop-purchase', id], queryFn: () => api.get(`/shop/purchases/${id}`).then(r => r.data), enabled: !!id });
  const cancel = useMutation({
    mutationFn: () => api.post(`/shop/purchases/${id}/cancel`, { admin_confirm_refund: true, refund_note: 'Cancelled from purchase details.' }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-purchase', id] }); toastSuccess('Purchase cancelled and stock restored.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not cancel purchase.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title={`Purchase ${p.purchase_number}`} subtitle={`${p.student_name} - ${p.student_number ?? p.admission_number}`} action={<div className="flex gap-2"><Link to={`/app/shop/purchases/${id}/payments`}><Btn variant="outline">Record Payment</Btn></Link><Btn variant="outline" onClick={() => window.print()}>Print Receipt</Btn>{p.status !== 'cancelled' && <Btn variant="danger" onClick={async () => { if (await confirmAction('Cancel purchase?', 'Stock will be restored and this purchase will be marked cancelled.', 'Cancel purchase')) cancel.mutate(); }}>Cancel</Btn>}</div>} />
      <div className="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_360px]">
        <Card>
          <CardHeader title="Items Bought" />
          <Table headers={['Item', 'Category', 'Qty', 'Unit Price', 'Line Total']}>
            {(p.items ?? []).map((i: any) => <tr key={i.id}><Td><strong>{i.item_name}</strong><br /><span className="text-xs text-slate-500">{i.item_code}</span></Td><Td>{i.category_name}</Td><Td>{i.quantity}</Td><Td>{money(i.unit_price)}</Td><Td><strong>{money(i.line_total)}</strong></Td></tr>)}
          </Table>
        </Card>
        <div className="space-y-5">
          <Card><CardHeader title="Summary" /><CardBody><div className="space-y-3 text-sm"><div className="flex justify-between"><span>Date</span><strong>{p.purchase_date}</strong></div><div className="flex justify-between"><span>Status</span>{statusBadge(p.status)}</div><div className="flex justify-between"><span>Total</span><strong>{money(p.total_amount)}</strong></div><div className="flex justify-between"><span>Paid</span><strong className="text-emerald-700">{money(p.amount_paid)}</strong></div><div className="flex justify-between"><span>Balance</span><strong className="text-red-600">{money(p.balance)}</strong></div></div></CardBody></Card>
          <Card><CardHeader title="Payments" /><CardBody><div className="space-y-2">{(p.payments ?? []).length === 0 && <div className="text-sm text-slate-500">No payments recorded.</div>}{(p.payments ?? []).map((pay: any) => <div key={pay.id} className="rounded-lg border border-slate-200 p-3"><div className="flex justify-between"><strong>{money(pay.amount)}</strong><Badge variant="blue">{pay.payment_method.replace('_', ' ')}</Badge></div><div className="mt-1 text-xs text-slate-500">{pay.payment_date} by {pay.received_by_name}</div></div>)}</div></CardBody></Card>
        </div>
      </div>
    </div>
  );
}
