import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { StatCard, Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Btn } from '../../components/UI';

export default function BursarDashboard() {
  const { data, isLoading } = useQuery({ queryKey: ['bursar-dashboard'], queryFn: () => api.get('/bursar/dashboard').then(r => r.data) });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Bursar Dashboard" subtitle="Finance and fee management" />

      <div className="grid-4" style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16, marginBottom: 24 }}>
        <StatCard label="Collected (This Year)" value={`$${Number(data.total_collected).toLocaleString()}`} icon="💰" color="green" />
        <StatCard label="Outstanding Balance"   value={`$${Number(data.total_outstanding).toLocaleString()}`} icon="⚠️" color="red" />
        <StatCard label="Paid in Full"          value={data.paid_in_full} icon="✅" color="green" />
        <StatCard label="Debtors"               value={data.debtors_count} icon="📋" color="amber" />
      </div>

      {/* Record Payment now lives on the same Finance module page admin/headmaster use —
          it needs a real student search and bill allocation, which belongs in one place
          rather than a second, thinner copy here. */}
      <Card style={{ marginBottom: 20 }}>
        <CardBody style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
          <div>
            <div style={{ fontSize: 14, fontWeight: 700, color: '#0f172a' }}>Ready to record a payment?</div>
            <div style={{ fontSize: 12, color: '#6b7280', marginTop: 2 }}>Search for a student, see what they owe, and allocate the payment against their bills.</div>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <Link to="/app/finance/bills"><Btn variant="outline">View Student Bills</Btn></Link>
            <Link to="/app/finance/payments"><Btn>Record Payment →</Btn></Link>
          </div>
        </CardBody>
      </Card>

      {/* Recent Payments */}
      <Card>
        <CardHeader title="Recent Payments" action={<Link to="/app/finance/history" style={{ fontSize: 12, color: '#1a6b3c', fontWeight: 600 }}>View All</Link>} />
        <Table headers={['Student', 'Amount', 'Method', 'Date']}>
          {(!data.recent_payments || data.recent_payments.length === 0) && (
            <tr><Td colSpan={4} style={{ textAlign: 'center', color: '#9ca3af' }}>No payments recorded yet</Td></tr>
          )}
          {data.recent_payments?.map((p: any) => (
            <tr key={p.id}>
              <Td><strong>{p.student?.first_name} {p.student?.last_name}</strong></Td>
              <Td style={{ color: '#1a6b3c', fontWeight: 600 }}>${Number(p.amount).toLocaleString()}</Td>
              <Td style={{ textTransform: 'capitalize' }}>{p.payment_method?.replace(/_/g, ' ')}</Td>
              <Td style={{ color: '#6b7280' }}>{new Date(p.payment_date).toLocaleDateString()}</Td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}
