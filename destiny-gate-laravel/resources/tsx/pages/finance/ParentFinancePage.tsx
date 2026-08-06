import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, Table, Td, Spinner, PageHeader, Badge, statusBadge } from '../../components/UI';

export default function ParentFinancePage() {
  const [selectedChild, setSelectedChild] = useState<any>(null);
  const [tab, setTab] = useState<'bills' | 'payments'>('bills');

  const { data: childrenData, isLoading } = useQuery({
    queryKey: ['parent-children'],
    queryFn: () => api.get('/parent/finance/children').then(r => r.data),
  });

  const { data: bills = [] } = useQuery({
    queryKey: ['parent-child-bills', selectedChild?.id],
    queryFn: () => api.get(`/parent/finance/child/${selectedChild.id}/bills`).then(r => r.data),
    enabled: !!selectedChild,
  });

  const { data: payments = [] } = useQuery({
    queryKey: ['parent-child-payments', selectedChild?.id],
    queryFn: () => api.get(`/parent/finance/child/${selectedChild.id}/payments`).then(r => r.data),
    enabled: !!selectedChild,
  });

  if (isLoading) return <Spinner />;

  const children = childrenData?.children ?? [];

  return (
    <div>
      <PageHeader title="My Children's Fees" subtitle="View fee statements and payment history" />

      {children.length === 0 ? (
        <div style={{ textAlign: 'center', padding: '60px 20px', color: '#9ca3af' }}>
          <div style={{ fontSize: 36, marginBottom: 12 }}>👨‍👩‍👧</div>
          <p>No children linked to your account. Contact the school office.</p>
        </div>
      ) : (
        <>
          {/* Child selector */}
          <div style={{ display: 'flex', gap: 12, marginBottom: 20, flexWrap: 'wrap' }}>
            {children.map((c: any) => (
              <div key={c.id} onClick={() => setSelectedChild(c)} style={{
                padding: '14px 20px', borderRadius: 12, cursor: 'pointer',
                border: `2px solid ${selectedChild?.id === c.id ? '#b6924c' : '#e8eaed'}`,
                background: selectedChild?.id === c.id ? '#eef1f8' : '#fff',
                minWidth: 180,
              }}>
                <div style={{ fontWeight: 700, fontSize: 14 }}>{c.name}</div>
                <div style={{ fontSize: 12, color: '#6b7280' }}>{c.student_number}</div>
                <div style={{ fontSize: 12, color: '#6b7280' }}>{c.class_name ?? 'No class'}</div>
                <div style={{ marginTop: 8, fontSize: 15, fontWeight: 800, color: Number(c.balance) > 0 ? '#dc2626' : '#8a6b34' }}>
                  ${Number(c.balance).toLocaleString()} {Number(c.balance) > 0 ? 'owing' : '✓ clear'}
                </div>
              </div>
            ))}
          </div>

          {selectedChild && (
            <>
              {/* Tabs */}
              <div style={{ display: 'flex', gap: 0, marginBottom: 16, borderBottom: '2px solid #e8eaed' }}>
                {(['bills', 'payments'] as const).map(t => (
                  <button key={t} onClick={() => setTab(t)} style={{
                    padding: '10px 20px', border: 'none', background: 'none', cursor: 'pointer',
                    fontSize: 13, fontWeight: tab === t ? 700 : 400,
                    color: tab === t ? '#8a6b34' : '#6b7280',
                    borderBottom: tab === t ? '2px solid #b6924c' : '2px solid transparent',
                    marginBottom: -2, textTransform: 'capitalize',
                  }}>{t}</button>
                ))}
              </div>

              {tab === 'bills' && (
                <Card>
                  <Table headers={['Bill #', 'Description', 'Term', 'Amount', 'Paid', 'Balance', 'Status', 'Due Date']}>
                    {(bills as any[]).map((b: any) => (
                      <tr key={b.id}>
                        <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{b.bill_number}</code></Td>
                        <Td>{b.description}</Td>
                        <Td>{b.term_name}</Td>
                        <Td>${Number(b.amount).toLocaleString()}</Td>
                        <Td style={{ color: '#8a6b34' }}>${Number(b.amount_paid).toLocaleString()}</Td>
                        <Td style={{ color: Number(b.balance) > 0 ? '#dc2626' : '#8a6b34', fontWeight: 600 }}>${Number(b.balance).toLocaleString()}</Td>
                        <Td>{statusBadge(b.status)}</Td>
                        <Td style={{ color: '#6b7280' }}>{b.due_date ?? '—'}</Td>
                      </tr>
                    ))}
                    {(bills as any[]).length === 0 && <tr><Td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af', padding: 24 }}>No bills found.</Td></tr>}
                  </Table>
                </Card>
              )}

              {tab === 'payments' && (
                <Card>
                  <Table headers={['Receipt #', 'Amount', 'Method', 'Payer', 'Date', 'Received By']}>
                    {(payments as any[]).map((p: any) => (
                      <tr key={p.id}>
                        <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{p.receipt_number}</code></Td>
                        <Td style={{ color: '#8a6b34', fontWeight: 700 }}>${Number(p.amount).toLocaleString()}</Td>
                        <Td style={{ textTransform: 'capitalize' }}>{p.payment_method?.replace('_', ' ')}</Td>
                        <Td>{p.payer_name ?? '—'}</Td>
                        <Td style={{ color: '#6b7280' }}>{p.payment_date}</Td>
                        <Td>{p.received_by_name}</Td>
                      </tr>
                    ))}
                    {(payments as any[]).length === 0 && <tr><Td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af', padding: 24 }}>No payments found.</Td></tr>}
                  </Table>
                </Card>
              )}
            </>
          )}
        </>
      )}
    </div>
  );
}
