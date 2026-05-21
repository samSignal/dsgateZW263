import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError } from '../../lib/toast';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Btn, Badge, FormGroup, Input, Select, Grid, Alert, statusBadge } from '../../components/UI';

export default function RecordPaymentPage() {
  const qc = useQueryClient();
  const [studentSearch, setStudentSearch] = useState('');
  const [selectedStudent, setSelectedStudent] = useState<any>(null);
  const [form, setForm] = useState({ academic_year_id: '', term_id: '', amount: '', payment_method: 'cash', reference_number: '', payer_name: '', payer_phone: '', payment_date: new Date().toISOString().split('T')[0], notes: '' });
  const [err, setErr] = useState('');
  const [receipt, setReceipt] = useState<any>(null);

  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'], queryFn: () => api.get('/terms').then(r => r.data) });

  const { data: students = [], isLoading: searchLoading } = useQuery({
    queryKey: ['students-search', studentSearch],
    queryFn: () => studentSearch.length >= 2 ? api.get('/students', { params: { search: studentSearch, per_page: 10 } }).then(r => r.data.data) : Promise.resolve([]),
    enabled: studentSearch.length >= 2,
  });

  const { data: balanceData, isLoading: balanceLoading } = useQuery({
    queryKey: ['student-balance', selectedStudent?.id],
    queryFn: () => api.get(`/finance/student/${selectedStudent.id}/balance`).then(r => r.data),
    enabled: !!selectedStudent,
  });

  const filteredTerms = (terms as any[]).filter(t => !form.academic_year_id || String(t.academic_year_id) === form.academic_year_id);

  const pay = useMutation({
    mutationFn: (d: any) => api.post('/finance/payments', d),
    onSuccess: async (res) => {
      toastSuccess(`Payment recorded! Receipt: ${res.data.receipt_number}`);
      // Fetch receipt
      const r = await api.get(`/finance/payments/${res.data.payment_id}/receipt`);
      setReceipt(r.data);
      qc.invalidateQueries({ queryKey: ['student-balance', selectedStudent?.id] });
      setForm(f => ({ ...f, amount: '', reference_number: '', payer_name: '', payer_phone: '', notes: '' }));
    },
    onError: (e: any) => setErr(e.response?.data?.message ?? 'Payment failed.'),
  });

  const handlePay = () => {
    if (!selectedStudent) { setErr('Please select a student.'); return; }
    if (!form.academic_year_id || !form.term_id) { setErr('Please select academic year and term.'); return; }
    setErr('');
    pay.mutate({ student_id: selectedStudent.id, ...form });
  };

  return (
    <div>
      <PageHeader title="Record Payment" subtitle="Record fee payment for a student" />
      {err && <Alert type="error" message={err} />}

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
        {/* Left: Student selection + bills */}
        <div>
          <Card style={{ marginBottom: 16 }}>
            <CardHeader title="Select Student" />
            <CardBody>
              <FormGroup label="Search student by name or student number">
                <Input value={studentSearch} onChange={e => { setStudentSearch(e.target.value); setSelectedStudent(null); }} placeholder="Type at least 2 characters…" />
              </FormGroup>
              {searchLoading && <div style={{ fontSize: 12, color: '#6b7280' }}>Searching…</div>}
              {(students as any[]).length > 0 && !selectedStudent && (
                <div style={{ border: '1px solid #e8eaed', borderRadius: 8, overflow: 'hidden', marginTop: 4 }}>
                  {(students as any[]).map((s: any) => (
                    <div key={s.id} onClick={() => { setSelectedStudent(s); setStudentSearch(`${s.first_name} ${s.last_name}`); }}
                      style={{ padding: '10px 14px', cursor: 'pointer', borderBottom: '1px solid #f3f4f6', fontSize: 13 }}>
                      <strong>{s.first_name} {s.last_name}</strong>
                      <span style={{ color: '#6b7280', marginLeft: 8 }}>{s.student_number}</span>
                      <span style={{ color: '#9ca3af', marginLeft: 8 }}>{s.class_name ?? '—'}</span>
                    </div>
                  ))}
                </div>
              )}
              {selectedStudent && (
                <div style={{ background: '#f0faf4', borderRadius: 8, padding: '12px 14px', marginTop: 8, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <div>
                    <div style={{ fontWeight: 700, fontSize: 14 }}>{selectedStudent.first_name} {selectedStudent.last_name}</div>
                    <div style={{ fontSize: 12, color: '#6b7280' }}>{selectedStudent.student_number} · {selectedStudent.class_name ?? 'No class'}</div>
                  </div>
                  <Btn size="sm" variant="ghost" onClick={() => { setSelectedStudent(null); setStudentSearch(''); }}>✕</Btn>
                </div>
              )}
            </CardBody>
          </Card>

          {selectedStudent && (
            <Card>
              <CardHeader title="Outstanding Bills" />
              {balanceLoading ? <Spinner /> : (
                <>
                  <div style={{ padding: '12px 20px', background: '#fef2f2', borderBottom: '1px solid #f3f4f6', display: 'flex', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: 13, fontWeight: 600 }}>Total Outstanding</span>
                    <span style={{ fontSize: 16, fontWeight: 800, color: '#dc2626' }}>${Number(balanceData?.balance ?? 0).toLocaleString()}</span>
                  </div>
                  <Table headers={['Bill #', 'Description', 'Amount', 'Paid', 'Balance', 'Status']}>
                    {(balanceData?.bills ?? []).map((b: any) => (
                      <tr key={b.id}>
                        <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{b.bill_number}</code></Td>
                        <Td>{b.description}</Td>
                        <Td>${Number(b.amount).toLocaleString()}</Td>
                        <Td style={{ color: '#1a6b3c' }}>${Number(b.amount_paid).toLocaleString()}</Td>
                        <Td style={{ color: '#dc2626', fontWeight: 600 }}>${Number(b.balance).toLocaleString()}</Td>
                        <Td>{statusBadge(b.status)}</Td>
                      </tr>
                    ))}
                    {(balanceData?.bills ?? []).length === 0 && <tr><Td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af', padding: 20 }}>No outstanding bills.</Td></tr>}
                  </Table>
                </>
              )}
            </Card>
          )}
        </div>

        {/* Right: Payment form */}
        <div>
          <Card>
            <CardHeader title="Payment Details" />
            <CardBody>
              <Grid cols={2} style={{ marginBottom: 0 }}>
                <FormGroup label="Academic Year">
                  <Select value={form.academic_year_id} onChange={e => setForm(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))}>
                    <option value="">Select year…</option>
                    {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
                  </Select>
                </FormGroup>
                <FormGroup label="Term">
                  <Select value={form.term_id} onChange={e => setForm(f => ({ ...f, term_id: e.target.value }))}>
                    <option value="">Select term…</option>
                    {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
                  </Select>
                </FormGroup>
              </Grid>
              <Grid cols={2} style={{ marginBottom: 0 }}>
                <FormGroup label="Amount ($)">
                  <Input type="number" step="0.01" value={form.amount} onChange={e => setForm(f => ({ ...f, amount: e.target.value }))} placeholder="0.00" />
                </FormGroup>
                <FormGroup label="Payment Method">
                  <Select value={form.payment_method} onChange={e => setForm(f => ({ ...f, payment_method: e.target.value }))}>
                    <option value="cash">Cash</option>
                    <option value="ecocash">EcoCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="swipe">Swipe</option>
                    <option value="online">Online</option>
                    <option value="other">Other</option>
                  </Select>
                </FormGroup>
              </Grid>
              <FormGroup label="Reference Number (optional)">
                <Input value={form.reference_number} onChange={e => setForm(f => ({ ...f, reference_number: e.target.value }))} placeholder="EcoCash ref, bank ref…" />
              </FormGroup>
              <Grid cols={2} style={{ marginBottom: 0 }}>
                <FormGroup label="Payer Name"><Input value={form.payer_name} onChange={e => setForm(f => ({ ...f, payer_name: e.target.value }))} placeholder="Parent/guardian name" /></FormGroup>
                <FormGroup label="Payer Phone"><Input value={form.payer_phone} onChange={e => setForm(f => ({ ...f, payer_phone: e.target.value }))} placeholder="07XX XXX XXX" /></FormGroup>
              </Grid>
              <FormGroup label="Payment Date"><Input type="date" value={form.payment_date} onChange={e => setForm(f => ({ ...f, payment_date: e.target.value }))} /></FormGroup>
              <FormGroup label="Notes"><Input value={form.notes} onChange={e => setForm(f => ({ ...f, notes: e.target.value }))} placeholder="Optional notes" /></FormGroup>
              <Btn loading={pay.isPending} onClick={handlePay} style={{ width: '100%', justifyContent: 'center', padding: '11px' }}>
                💳 Record Payment
              </Btn>
            </CardBody>
          </Card>

          {/* Receipt preview */}
          {receipt && (
            <Card style={{ marginTop: 16 }}>
              <CardHeader title="Receipt" action={<Btn size="sm" variant="outline" onClick={() => window.print()}>🖨️ Print</Btn>} />
              <CardBody>
                <div style={{ textAlign: 'center', marginBottom: 16 }}>
                  <div style={{ fontSize: 18, fontWeight: 800, color: '#1a6b3c' }}>DestinyGate Institute</div>
                  <div style={{ fontSize: 12, color: '#6b7280' }}>Official Receipt</div>
                  <div style={{ fontSize: 20, fontWeight: 700, marginTop: 8 }}>{receipt.receipt_number}</div>
                </div>
                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                  {[
                    ['Student', receipt.student_name],
                    ['Student #', receipt.student_number],
                    ['Class', receipt.class_name ?? '—'],
                    ['Payer', receipt.payer_name ?? '—'],
                    ['Method', receipt.payment_method?.replace('_', ' ')],
                    ['Reference', receipt.reference_number ?? '—'],
                    ['Date', receipt.payment_date],
                    ['Received By', receipt.received_by_name],
                    ['Amount Paid', `$${Number(receipt.amount).toLocaleString()}`],
                    ['Remaining Balance', `$${Number(receipt.remaining_balance).toLocaleString()}`],
                  ].map(([k, v]) => (
                    <tr key={k}>
                      <td style={{ padding: '5px 0', color: '#6b7280', width: '45%' }}>{k}</td>
                      <td style={{ padding: '5px 0', fontWeight: k === 'Amount Paid' ? 700 : 400, color: k === 'Remaining Balance' ? '#dc2626' : '#111827' }}>{v}</td>
                    </tr>
                  ))}
                </table>
                {receipt.allocations?.length > 0 && (
                  <div style={{ marginTop: 12, borderTop: '1px solid #f3f4f6', paddingTop: 10 }}>
                    <div style={{ fontSize: 11, fontWeight: 700, color: '#6b7280', marginBottom: 6, textTransform: 'uppercase' }}>Bills Paid</div>
                    {receipt.allocations.map((a: any, i: number) => (
                      <div key={i} style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12, marginBottom: 4 }}>
                        <span>{a.description}</span>
                        <span style={{ fontWeight: 600 }}>${Number(a.amount_allocated).toLocaleString()}</span>
                      </div>
                    ))}
                  </div>
                )}
              </CardBody>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}
