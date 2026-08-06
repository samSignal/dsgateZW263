import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { StatCard, Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge, FormGroup, Input, Select, Grid } from '../../components/UI';

export default function BursarDashboard() {
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');
  const [err, setErr] = useState('');
  const [form, setForm] = useState({ student_id: '', student_fee_id: '', amount: '', payment_method: 'cash', payment_date: new Date().toISOString().split('T')[0], notes: '' });

  const { data, isLoading } = useQuery({ queryKey: ['bursar-dashboard'], queryFn: () => api.get('/bursar/dashboard').then(r => r.data) });

  const pay = useMutation({
    mutationFn: (d: any) => api.post('/bursar/payments', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['bursar-dashboard'] }); setMsg('Payment recorded.'); setForm(f => ({ ...f, student_id: '', student_fee_id: '', amount: '', notes: '' })); },
    onError: (e: any) => setErr(e.response?.data?.message ?? 'Failed to record payment.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Bursar Dashboard" subtitle="Finance and fee management" />
      {msg && <Alert type="success" message={msg} />}
      {err && <Alert type="error" message={err} />}

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16, marginBottom: 24 }}>
        <StatCard label="Collected (This Year)" value={`$${Number(data.total_collected).toLocaleString()}`} icon="💰" color="green" />
        <StatCard label="Outstanding Balance"   value={`$${Number(data.total_outstanding).toLocaleString()}`} icon="⚠️" color="red" />
        <StatCard label="Paid in Full"          value={data.paid_in_full} icon="✅" color="green" />
        <StatCard label="Debtors"               value={data.debtors_count} icon="📋" color="amber" />
      </div>

      <Grid cols={2}>
        {/* Record Payment */}
        <Card>
          <CardHeader title="Record Payment" />
          <CardBody>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Student ID"><Input type="number" value={form.student_id} onChange={e => setForm(f => ({...f, student_id: e.target.value}))} placeholder="Student ID" /></FormGroup>
              <FormGroup label="Fee Record ID"><Input type="number" value={form.student_fee_id} onChange={e => setForm(f => ({...f, student_fee_id: e.target.value}))} placeholder="Fee ID" /></FormGroup>
            </Grid>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Amount ($)"><Input type="number" step="0.01" value={form.amount} onChange={e => setForm(f => ({...f, amount: e.target.value}))} /></FormGroup>
              <FormGroup label="Method">
                <Select value={form.payment_method} onChange={e => setForm(f => ({...f, payment_method: e.target.value}))}>
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="check">Check</option>
                  <option value="online">Online</option>
                </Select>
              </FormGroup>
            </Grid>
            <FormGroup label="Payment Date"><Input type="date" value={form.payment_date} onChange={e => setForm(f => ({...f, payment_date: e.target.value}))} /></FormGroup>
            <FormGroup label="Notes"><Input value={form.notes} onChange={e => setForm(f => ({...f, notes: e.target.value}))} placeholder="Optional notes…" /></FormGroup>
            <Btn loading={pay.isPending} onClick={() => pay.mutate(form)}>Record Payment</Btn>
          </CardBody>
        </Card>

        {/* Recent Payments */}
        <Card>
          <CardHeader title="Recent Payments" />
          <Table headers={['Student', 'Amount', 'Method', 'Date']}>
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
      </Grid>
    </div>
  );
}
