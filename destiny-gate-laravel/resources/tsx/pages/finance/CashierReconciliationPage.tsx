import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { downloadReport } from '../../lib/download';
import { Card, CardHeader, Table, Td, Spinner, PageHeader, Btn, Input } from '../../components/UI';

export default function CashierReconciliationPage() {
  const today = new Date().toISOString().split('T')[0];
  const [dateFrom, setDateFrom] = useState(today);
  const [dateTo, setDateTo] = useState(today);

  const { data, isLoading } = useQuery({
    queryKey: ['cashier-reconciliation', dateFrom, dateTo],
    queryFn: () => api.get('/finance/reports/cashier-reconciliation', { params: { date_from: dateFrom, date_to: dateTo } }).then(r => r.data),
  });

  const exportReport = (format: 'pdf' | 'csv') =>
    downloadReport('/finance/reports/cashier-reconciliation', { date_from: dateFrom, date_to: dateTo, format },
      `cashier-reconciliation-${dateFrom}.${format}`);

  const cashiers = data?.cashiers ?? [];

  return (
    <div>
      <PageHeader title="Cashier Reconciliation" subtitle="Per-cashier collections for shift handover and reconciliation" />

      <div style={{ display: 'flex', gap: 10, marginBottom: 16, alignItems: 'center', flexWrap: 'wrap' }}>
        <Input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} style={{ width: 160 }} />
        <span style={{ color: '#6b7280', fontSize: 13 }}>to</span>
        <Input type="date" value={dateTo} onChange={e => setDateTo(e.target.value)} style={{ width: 160 }} />
        <div style={{ flex: 1 }} />
        <Btn size="sm" variant="outline" onClick={() => exportReport('pdf')}>Export PDF</Btn>
        <Btn size="sm" variant="outline" onClick={() => exportReport('csv')}>Export CSV</Btn>
      </div>

      {isLoading ? <Spinner /> : (
        <>
          {cashiers.map((c: any) => (
            <Card key={c.received_by} style={{ marginBottom: 16 }}>
              <CardHeader title={`${c.cashier_name} · ${c.payment_count} payment(s)`} action={<span style={{ fontWeight: 700, color: '#1a6b3c' }}>${Number(c.total_amount).toLocaleString()}</span>} />
              <Table headers={['Method', 'Amount']}>
                {Object.entries(c.by_method ?? {}).map(([method, amount]) => (
                  <tr key={method}>
                    <Td style={{ textTransform: 'capitalize' }}>{method.replace('_', ' ')}</Td>
                    <Td style={{ fontWeight: 600 }}>${Number(amount as number).toLocaleString()}</Td>
                  </tr>
                ))}
              </Table>
            </Card>
          ))}
          {cashiers.length === 0 && (
            <Card><div style={{ textAlign: 'center', padding: 40, color: '#9ca3af' }}>No payments recorded in this date range.</div></Card>
          )}
          <Card>
            <div style={{ padding: '16px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span style={{ fontWeight: 700 }}>Grand Total</span>
              <span style={{ fontSize: 20, fontWeight: 800, color: '#1a6b3c' }}>${Number(data?.grand_total ?? 0).toLocaleString()}</span>
            </div>
          </Card>
        </>
      )}
    </div>
  );
}
