import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Alert, Btn, Card, CardBody, CardHeader, FormGroup, Input, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function PurchasePaymentsPage() {
  const { id } = useParams();
  const qc = useQueryClient();
  const [amount, setAmount] = useState('');
  const [method, setMethod] = useState('cash');
  const [reference, setReference] = useState('');
  const [error, setError] = useState('');
  const { data: p, isLoading } = useQuery({ queryKey: ['shop-purchase', id], queryFn: () => api.get(`/shop/purchases/${id}`).then(r => r.data), enabled: !!id });
  const save = useMutation({
    mutationFn: () => api.post('/shop/payments', { student_purchase_id: Number(id), amount: Number(amount), payment_method: method, reference_number: reference || null, payment_date: new Date().toISOString().slice(0, 10) }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-purchase', id] }); setAmount(''); setReference(''); toastSuccess('Payment recorded.'); },
    onError: (e: any) => setError(e.response?.data?.message ?? 'Could not record payment.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Purchase Payments" subtitle={`${p.purchase_number} - ${p.student_name}`} />
      {error && <Alert type="error" message={error} />}
      <div className="grid grid-cols-1 gap-5 xl:grid-cols-[380px_1fr]">
        <Card>
          <CardHeader title="Record Payment" />
          <CardBody>
            <div className="mb-4 rounded-lg bg-slate-50 p-4 text-sm">
              <div className="flex justify-between"><span>Total</span><strong>{money(p.total_amount)}</strong></div>
              <div className="flex justify-between"><span>Paid</span><strong>{money(p.amount_paid)}</strong></div>
              <div className="flex justify-between"><span>Balance</span><strong className="text-red-600">{money(p.balance)}</strong></div>
            </div>
            <FormGroup label="Amount"><Input type="number" min="0.01" max={p.balance} step="0.01" value={amount} onChange={e => setAmount(e.target.value)} /></FormGroup>
            <FormGroup label="Payment Method"><Select value={method} onChange={e => setMethod(e.target.value)}><option value="cash">Cash</option><option value="ecocash">EcoCash</option><option value="bank_transfer">Bank Transfer</option><option value="swipe">Swipe</option><option value="online">Online</option><option value="other">Other</option></Select></FormGroup>
            <FormGroup label="Reference"><Input value={reference} onChange={e => setReference(e.target.value)} /></FormGroup>
            <Btn disabled={p.status === 'cancelled' || Number(p.balance) <= 0} loading={save.isPending} onClick={() => save.mutate()} style={{ width: '100%', justifyContent: 'center' }}>Save Payment</Btn>
          </CardBody>
        </Card>
        <Card>
          <CardHeader title="Payment History" />
          <Table headers={['Date', 'Amount', 'Method', 'Reference', 'Received By']}>
            {(p.payments ?? []).map((pay: any) => <tr key={pay.id}><Td>{pay.payment_date}</Td><Td><strong>{money(pay.amount)}</strong></Td><Td style={{ textTransform: 'capitalize' }}>{pay.payment_method?.replace('_', ' ')}</Td><Td>{pay.reference_number ?? '-'}</Td><Td>{pay.received_by_name}</Td></tr>)}
            {(p.payments ?? []).length === 0 && <tr><Td colSpan={5} style={{ textAlign: 'center', color: '#94a3b8' }}>No payments yet.</Td></tr>}
          </Table>
        </Card>
      </div>
    </div>
  );
}
