import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Alert, Btn, Card, CardBody, CardHeader, FormGroup, Input, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function PurchasePaymentsPage() {
  const { id } = useParams();
  const qc = useQueryClient();
  const [useCredit, setUseCredit] = useState(false);
  const [remainderAmount, setRemainderAmount] = useState('');
  const [remainderTouched, setRemainderTouched] = useState(false);
  const [method, setMethod] = useState('cash');
  const [reference, setReference] = useState('');
  const [error, setError] = useState('');
  const { data: p, isLoading } = useQuery({ queryKey: ['shop-purchase', id], queryFn: () => api.get(`/shop/purchases/${id}`).then(r => r.data), enabled: !!id });
  const { data: balanceData } = useQuery({
    queryKey: ['student-balance', p?.student_id],
    queryFn: () => api.get(`/finance/student/${p.student_id}/balance`).then(r => r.data),
    enabled: !!p?.student_id,
  });
  const availableCredit = Math.max(0, -Number(balanceData?.balance ?? 0));
  const purchaseBalance = Number(p?.balance ?? 0);
  const creditApplied = useCredit ? Math.min(availableCredit, purchaseBalance) : 0;
  const remainingAfterCredit = Math.max(0, purchaseBalance - creditApplied);

  useEffect(() => {
    if (availableCredit <= 0) setUseCredit(false);
  }, [p?.student_id, availableCredit]);

  useEffect(() => {
    if (!remainderTouched) setRemainderAmount(remainingAfterCredit > 0 ? remainingAfterCredit.toFixed(2) : '');
  }, [remainingAfterCredit, remainderTouched]);

  const save = useMutation({
    mutationFn: async () => {
      const paymentDate = new Date().toISOString().slice(0, 10);
      const remainder = Number(remainderAmount || 0);

      if (creditApplied > 0) {
        await api.post('/shop/payments', { student_purchase_id: Number(id), amount: creditApplied, payment_method: 'account_credit', payment_date: paymentDate });
      }
      if (remainder > 0) {
        await api.post('/shop/payments', { student_purchase_id: Number(id), amount: remainder, payment_method: method, reference_number: reference || null, payment_date: paymentDate });
      }
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['shop-purchase', id] });
      qc.invalidateQueries({ queryKey: ['student-balance'] });
      setUseCredit(false); setRemainderAmount(''); setRemainderTouched(false); setReference('');
      toastSuccess('Payment recorded.');
    },
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

            <label className={`mb-3 flex items-center justify-between rounded-lg border p-3 text-sm ${availableCredit > 0 ? 'cursor-pointer border-emerald-200 bg-emerald-50' : 'cursor-not-allowed border-slate-200 bg-slate-100 opacity-60'}`}>
              <span className="flex items-center gap-2">
                <input type="checkbox" checked={useCredit} disabled={availableCredit <= 0} onChange={e => { setUseCredit(e.target.checked); setRemainderTouched(false); }} />
                💳 Use fee account credit
              </span>
              <strong className={availableCredit > 0 ? 'text-emerald-700' : 'text-slate-500'}>
                {availableCredit > 0 ? `${money(availableCredit)} available` : 'No credit available'}
              </strong>
            </label>
            {useCredit && (
              <div className="mb-3 flex justify-between text-sm text-emerald-700">
                <span>Applied from credit</span><strong>-{money(creditApplied)}</strong>
              </div>
            )}

            <FormGroup label={useCredit ? 'Pay Remaining Via' : 'Amount'}>
              <Input type="number" min="0" max={remainingAfterCredit} step="0.01" value={remainderAmount}
                onChange={e => { setRemainderTouched(true); setRemainderAmount(e.target.value); }} placeholder="0.00" />
            </FormGroup>
            <FormGroup label="Payment Method">
              <Select value={method} onChange={e => setMethod(e.target.value)}>
                <option value="cash">Cash</option>
                <option value="ecocash">EcoCash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="swipe">Swipe / Card</option>
                <option value="online">Online</option>
                <option value="other">Other</option>
              </Select>
            </FormGroup>
            <FormGroup label="Reference"><Input value={reference} onChange={e => setReference(e.target.value)} /></FormGroup>
            <Btn disabled={p.status === 'cancelled' || Number(p.balance) <= 0} loading={save.isPending} onClick={() => {
              setError('');
              const remainder = Number(remainderAmount || 0);
              if (creditApplied <= 0 && remainder <= 0) return setError('Enter an amount, or use fee credit.');
              if (creditApplied + remainder > purchaseBalance + 0.01) return setError('Total payment cannot exceed the purchase balance.');
              save.mutate();
            }} style={{ width: '100%', justifyContent: 'center' }}>Save Payment</Btn>
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
