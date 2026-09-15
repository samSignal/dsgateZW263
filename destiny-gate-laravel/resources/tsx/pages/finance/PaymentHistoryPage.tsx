import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { toastSuccess } from '../../lib/toast';
import { useAuth } from '../../hooks/useAuth';
import { StatCard, Card, Table, Td, Spinner, PageHeader, Btn, Badge, Select, Input, Textarea, Modal, Alert } from '../../components/UI';
import PaymentReceipt from '../../components/PaymentReceipt';

const METHOD_VARIANT: Record<string, 'green' | 'blue' | 'purple' | 'amber' | 'gray'> = {
  cash: 'green', ecocash: 'blue', bank_transfer: 'purple', swipe: 'amber', online: 'blue', other: 'gray',
};

export default function PaymentHistoryPage() {
  const qc = useQueryClient();
  const { user } = useAuth();
  const canReverse = user?.role === 'admin';
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', payment_method: '', date_from: '', date_to: '' });
  const [page, setPage] = useState(1);
  const [receiptId, setReceiptId] = useState<number | null>(null);
  const [reversingPayment, setReversingPayment] = useState<any>(null);
  const [reverseReason, setReverseReason] = useState('');
  const [reverseErr, setReverseErr] = useState('');

  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'],           queryFn: () => api.get('/terms').then(r => r.data) });

  const { data: summary } = useQuery({
    queryKey: ['finance-summary', filters.academic_year_id, filters.term_id],
    queryFn: () => api.get('/finance/reports/summary', {
      params: { academic_year_id: filters.academic_year_id || undefined, term_id: filters.term_id || undefined },
    }).then(r => r.data),
  });

  const { data, isLoading } = useQuery({
    queryKey: ['finance-payments', search, filters, page],
    queryFn: () => api.get('/finance/payments', {
      params: {
        search: search || undefined,
        academic_year_id: filters.academic_year_id || undefined,
        term_id: filters.term_id || undefined,
        payment_method: filters.payment_method || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        page,
      },
    }).then(r => r.data),
  });

  const { data: receipt, isLoading: receiptLoading } = useQuery({
    queryKey: ['payment-receipt', receiptId],
    queryFn: () => api.get(`/finance/payments/${receiptId}/receipt`).then(r => r.data),
    enabled: !!receiptId,
  });

  const filteredTerms = (terms as any[]).filter(t => !filters.academic_year_id || String(t.academic_year_id) === filters.academic_year_id);
  const payments = data?.data ?? [];

  const reverse = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => api.post(`/finance/payments/${id}/reverse`, { reason }),
    onSuccess: () => {
      toastSuccess('Payment reversed.');
      qc.invalidateQueries({ queryKey: ['finance-payments'] });
      qc.invalidateQueries({ queryKey: ['finance-summary'] });
      setReversingPayment(null);
      setReverseReason('');
    },
    onError: (e: any) => setReverseErr(e.response?.data?.message ?? 'Reversal failed.'),
  });

  return (
    <div>
      <PageHeader title="Payment History" subtitle="Browse and search every fee payment recorded" />

      <div className="grid grid-cols-2 gap-3 md:grid-cols-4 mb-6">
        <StatCard label="Collected" value={`$${Number(summary?.total_collected ?? 0).toLocaleString()}`} icon="💰" color="green" />
        <StatCard label="Collection Rate" value={`${summary?.collection_rate ?? 0}%`} icon="📊" color="green" />
        <StatCard label="Outstanding" value={`$${Number(summary?.total_outstanding ?? 0).toLocaleString()}`} icon="⚠️" color="red" />
        <StatCard label="Payments Found" value={data?.total ?? 0} icon="🧾" color="blue" trend={filters.payment_method || search || filters.date_from ? 'matching filters' : 'all time'} />
      </div>

      <div style={{ display: 'flex', gap: 10, marginBottom: 16, flexWrap: 'wrap' }}>
        <Input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} placeholder="Search student, receipt #…" style={{ width: 240 }} />
        <Select value={filters.academic_year_id} onChange={e => setFilters(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))} style={{ width: 130 }}>
          <option value="">All Years</option>
          {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </Select>
        <Select value={filters.term_id} onChange={e => setFilters(f => ({ ...f, term_id: e.target.value }))} style={{ width: 130 }}>
          <option value="">All Terms</option>
          {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={filters.payment_method} onChange={e => setFilters(f => ({ ...f, payment_method: e.target.value }))} style={{ width: 150 }}>
          <option value="">All Methods</option>
          <option value="cash">Cash</option>
          <option value="ecocash">EcoCash</option>
          <option value="bank_transfer">Bank Transfer</option>
          <option value="swipe">Swipe</option>
          <option value="online">Online</option>
          <option value="other">Other</option>
        </Select>
        <Input type="date" value={filters.date_from} onChange={e => setFilters(f => ({ ...f, date_from: e.target.value }))} style={{ width: 150 }} />
        <Input type="date" value={filters.date_to} onChange={e => setFilters(f => ({ ...f, date_to: e.target.value }))} style={{ width: 150 }} />
      </div>

      <Card>
        {isLoading ? <Spinner /> : (
          <>
            <Table headers={['Receipt #', 'Student', 'Amount', 'Method', 'Date', 'Received By', 'Actions']}>
              {payments.map((p: any) => (
                <tr key={p.id}>
                  <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{p.receipt_number}</code></Td>
                  <Td>
                    <Link to={`/app/students/${p.student_id}`} style={{ textDecoration: 'none', color: 'inherit' }}>
                      <div style={{ fontWeight: 600, fontSize: 13, color: '#1a6b3c' }}>{p.student_name}</div>
                      <div style={{ fontSize: 11, color: '#6b7280' }}>{p.student_number || p.admission_number}</div>
                    </Link>
                  </Td>
                  <Td style={{ color: p.status === 'reversed' ? '#9ca3af' : '#1a6b3c', fontWeight: 700, textDecoration: p.status === 'reversed' ? 'line-through' : 'none' }}>${Number(p.amount).toLocaleString()}</Td>
                  <Td>
                    <Badge variant={METHOD_VARIANT[p.payment_method] ?? 'gray'}>{String(p.payment_method).replace('_', ' ')}</Badge>
                    {p.status === 'reversed' && <Badge variant="red" style={{ marginLeft: 6 }}>Reversed</Badge>}
                  </Td>
                  <Td style={{ color: '#6b7280' }}>{p.payment_date}</Td>
                  <Td>{p.received_by_name}</Td>
                  <Td>
                    <div style={{ display: 'flex', gap: 6 }}>
                      <Btn size="sm" variant="outline" onClick={() => setReceiptId(p.id)}>View Receipt</Btn>
                      {canReverse && p.status !== 'reversed' && (
                        <Btn size="sm" variant="danger" onClick={() => { setReversingPayment(p); setReverseReason(''); setReverseErr(''); }}>Reverse</Btn>
                      )}
                    </div>
                  </Td>
                </tr>
              ))}
              {payments.length === 0 && <tr><Td colSpan={7} style={{ textAlign: 'center', color: '#9ca3af', padding: 32 }}>No payments found.</Td></tr>}
            </Table>
            {data && data.last_page > 1 && (
              <div style={{ padding: '14px 20px', borderTop: '1px solid #f3f4f6', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <span style={{ fontSize: 12, color: '#6b7280' }}>Showing {payments.length} of {data.total}</span>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Btn size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>← Prev</Btn>
                  <span style={{ padding: '5px 12px', fontSize: 12 }}>Page {page} of {data.last_page}</span>
                  <Btn size="sm" variant="outline" disabled={page >= data.last_page} onClick={() => setPage(p => p + 1)}>Next →</Btn>
                </div>
              </div>
            )}
          </>
        )}
      </Card>

      <Modal open={!!receiptId} onClose={() => setReceiptId(null)} title="Payment Receipt" maxWidth={480}>
        {receiptLoading || !receipt ? <Spinner /> : <PaymentReceipt receipt={receipt} />}
      </Modal>

      <Modal open={!!reversingPayment} onClose={() => setReversingPayment(null)} title="Reverse Payment">
        {reverseErr && <Alert type="error" message={reverseErr} />}
        <p style={{ fontSize: 13, color: '#374151', marginBottom: 14 }}>
          Reversing <strong>{reversingPayment?.receipt_number}</strong> (${Number(reversingPayment?.amount ?? 0).toLocaleString()}) restores the balance on
          the bill(s) it paid and permanently flags the payment as reversed — it is never deleted, and this action is logged with your name and the reason below.
        </p>
        <Textarea value={reverseReason} onChange={e => setReverseReason(e.target.value)} placeholder="Reason for reversal (required)…" />
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 14 }}>
          <Btn variant="outline" onClick={() => setReversingPayment(null)}>Cancel</Btn>
          <Btn variant="danger" loading={reverse.isPending} disabled={!reverseReason.trim()}
            onClick={() => reverse.mutate({ id: reversingPayment.id, reason: reverseReason.trim() })}>
            Confirm Reversal
          </Btn>
        </div>
      </Modal>
    </div>
  );
}
