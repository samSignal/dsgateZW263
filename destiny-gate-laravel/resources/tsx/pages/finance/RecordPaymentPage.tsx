import React, { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { downloadReport } from '../../lib/download';
import { toastSuccess, toastError } from '../../lib/toast';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Btn, Badge, FormGroup, Input, Select, Grid, Alert, statusBadge } from '../../components/UI';
import PaymentReceipt from '../../components/PaymentReceipt';

const signedMoney = (n: number) => `${n < 0 ? '-' : ''}$${Math.abs(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function RecordPaymentPage() {
  const qc = useQueryClient();
  const [studentSearch, setStudentSearch] = useState('');
  const [selectedStudent, setSelectedStudent] = useState<any>(null);
  const [form, setForm] = useState({ academic_year_id: '', term_id: '', amount: '', payment_method: 'cash', reference_number: '', guardian_id: '', payer_name: '', payer_phone: '', payment_date: new Date().toISOString().split('T')[0], notes: '' });
  const [err, setErr] = useState('');
  const [receipt, setReceipt] = useState<any>(null);
  const [manualAllocation, setManualAllocation] = useState(false);
  const [allocations, setAllocations] = useState<Record<number, string>>({});

  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'], queryFn: () => api.get('/terms').then(r => r.data) });

  const { data: students = [], isLoading: searchLoading } = useQuery({
    queryKey: ['students-search', studentSearch],
    queryFn: () => studentSearch.length >= 2 ? api.get('/students', { params: { search: studentSearch, per_page: 10 } }).then(r => r.data.data) : Promise.resolve([]),
    enabled: studentSearch.length >= 2,
  });

  const { data: balanceData, isLoading: balanceLoading } = useQuery({
    queryKey: ['student-balance', selectedStudent?.id, form.academic_year_id, form.term_id],
    queryFn: () => api.get(`/finance/student/${selectedStudent.id}/balance`, {
      params: { academic_year_id: form.academic_year_id || undefined, term_id: form.term_id || undefined },
    }).then(r => r.data),
    enabled: !!selectedStudent,
  });

  const { data: guardians = [] } = useQuery({
    queryKey: ['student-guardians', selectedStudent?.id],
    queryFn: () => api.get(`/finance/student/${selectedStudent.id}/guardians`).then(r => r.data),
    enabled: !!selectedStudent,
  });

  useEffect(() => {
    // Reset per-student state when a different student is selected.
    setForm(f => ({ ...f, guardian_id: '', payer_name: '', payer_phone: '' }));
    setManualAllocation(false);
    setAllocations({});
  }, [selectedStudent?.id]);

  const filteredTerms = (terms as any[]).filter(t => !form.academic_year_id || String(t.academic_year_id) === form.academic_year_id);
  const bills = balanceData?.bills ?? [];
  const summary = balanceData?.account_summary;

  const allocatedTotal = Object.values(allocations).reduce((s, v) => s + (parseFloat(v) || 0), 0);

  const suggestOldestFirst = () => {
    let remaining = parseFloat(form.amount) || 0;
    const next: Record<number, string> = {};
    for (const b of bills) {
      if (remaining <= 0) break;
      const take = Math.min(remaining, Number(b.balance));
      if (take > 0) { next[b.id] = take.toFixed(2); remaining -= take; }
    }
    setAllocations(next);
  };

  const pay = useMutation({
    mutationFn: (d: any) => api.post('/finance/payments', d),
    onSuccess: async (res) => {
      toastSuccess(`Payment recorded! Receipt: ${res.data.receipt_number}`);
      const r = await api.get(`/finance/payments/${res.data.payment_id}/receipt`);
      setReceipt(r.data);
      qc.invalidateQueries({ queryKey: ['student-balance', selectedStudent?.id] });
      setForm(f => ({ ...f, amount: '', reference_number: '', payer_name: '', payer_phone: '', notes: '' }));
      setAllocations({});
    },
    onError: (e: any) => setErr(e.response?.data?.message ?? 'Payment failed.'),
  });

  const handlePay = () => {
    if (!selectedStudent) { setErr('Please select a student.'); return; }
    if (!form.academic_year_id || !form.term_id) { setErr('Please select academic year and term.'); return; }
    if (manualAllocation) {
      const amt = parseFloat(form.amount) || 0;
      if (Math.abs(allocatedTotal - amt) > 0.01) {
        setErr(`Allocated amount ($${allocatedTotal.toFixed(2)}) must equal the payment amount ($${amt.toFixed(2)}).`);
        return;
      }
    }
    setErr('');
    const payload: any = { student_id: selectedStudent.id, ...form, guardian_id: form.guardian_id || undefined };
    if (manualAllocation) {
      payload.allocations = Object.entries(allocations)
        .filter(([, v]) => (parseFloat(v) || 0) > 0)
        .map(([student_bill_id, amount]) => ({ student_bill_id: Number(student_bill_id), amount: parseFloat(amount) }));
    }
    pay.mutate(payload);
  };

  return (
    <div>
      <PageHeader title="Record Payment" subtitle="Record fee payment for a student" />
      {err && <Alert type="error" message={err} />}

      <div className="two-col-grid" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
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
                      <span style={{ color: '#6b7280', marginLeft: 8 }}>{s.student_number || s.admission_number}</span>
                      <span style={{ color: '#9ca3af', marginLeft: 8 }}>{s.class_name ?? '—'}</span>
                    </div>
                  ))}
                </div>
              )}
              {selectedStudent && (
                <div style={{ background: '#f0faf4', borderRadius: 8, padding: '12px 14px', marginTop: 8, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <a href={`/app/students/${selectedStudent.id}`} target="_blank" rel="noreferrer" style={{ textDecoration: 'none', color: 'inherit' }}>
                    <div style={{ fontWeight: 700, fontSize: 14, color: '#1a6b3c' }}>{selectedStudent.first_name} {selectedStudent.last_name}</div>
                    <div style={{ fontSize: 12, color: '#6b7280' }}>{selectedStudent.student_number || selectedStudent.admission_number} · {selectedStudent.class_name ?? 'No class'}</div>
                  </a>
                  <div style={{ display: 'flex', gap: 6 }}>
                    <Btn size="sm" variant="outline" onClick={() => downloadReport(
                      `/finance/reports/student/${selectedStudent.id}/statement`,
                      { academic_year_id: form.academic_year_id || undefined, term_id: form.term_id || undefined, format: 'pdf' },
                      `statement-${selectedStudent.student_number || selectedStudent.admission_number || selectedStudent.id}.pdf`
                    )}>📄 Statement</Btn>
                    <Btn size="sm" variant="ghost" onClick={() => { setSelectedStudent(null); setStudentSearch(''); }}>✕</Btn>
                  </div>
                </div>
              )}
            </CardBody>
          </Card>

          {selectedStudent && (
            <Card>
              <CardHeader title="Outstanding Bills" action={bills.length > 0 && (
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 12, color: '#374151', cursor: 'pointer' }}>
                  <input type="checkbox" checked={manualAllocation} onChange={e => { setManualAllocation(e.target.checked); setAllocations({}); }} />
                  Manually allocate
                </label>
              )} />
              {balanceLoading ? <Spinner /> : (
                <>
                  {summary && (
                    <div style={{ padding: '12px 20px', borderBottom: '1px solid #f3f4f6', fontSize: 12.5 }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}><span style={{ color: '#6b7280' }}>Opening Balance (b/f)</span><span>{signedMoney(Number(summary.opening_balance))}</span></div>
                      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}><span style={{ color: '#6b7280' }}>Current Term Charges</span><span>${Number(summary.current_term_charges).toLocaleString()}</span></div>
                      <div style={{ display: 'flex', justifyContent: 'space-between' }}><span style={{ color: '#6b7280' }}>Payments This Term</span><span>-${Number(summary.payments_this_term).toLocaleString()}</span></div>
                    </div>
                  )}
                  <div style={{ padding: '12px 20px', background: Number(balanceData?.balance ?? 0) < 0 ? '#f0faf4' : '#fef2f2', borderBottom: '1px solid #f3f4f6', display: 'flex', justifyContent: 'space-between' }}>
                    <span style={{ fontSize: 13, fontWeight: 600 }}>{Number(balanceData?.balance ?? 0) < 0 ? 'Credit Balance' : 'Outstanding Balance'}</span>
                    <span style={{ fontSize: 16, fontWeight: 800, color: Number(balanceData?.balance ?? 0) < 0 ? '#1a6b3c' : '#dc2626' }}>{signedMoney(Number(balanceData?.balance ?? 0))}</span>
                  </div>
                  {manualAllocation && (
                    <div style={{ padding: '10px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: '#fffbeb', borderBottom: '1px solid #f3f4f6' }}>
                      <span style={{ fontSize: 12, color: '#92400e' }}>Allocated: ${allocatedTotal.toFixed(2)} of ${(parseFloat(form.amount) || 0).toFixed(2)}</span>
                      <Btn size="sm" variant="outline" onClick={suggestOldestFirst}>Suggest oldest-first</Btn>
                    </div>
                  )}
                  <Table headers={manualAllocation ? ['Bill #', 'Description', 'Balance', 'Status', 'Apply $'] : ['Bill #', 'Description', 'Amount', 'Paid', 'Balance', 'Status']}>
                    {bills.map((b: any) => (
                      <tr key={b.id}>
                        <Td><code style={{ fontSize: 11, background: '#f1f5f9', padding: '2px 6px', borderRadius: 4 }}>{b.bill_number}</code></Td>
                        <Td>{b.description}</Td>
                        {!manualAllocation && <Td>${Number(b.amount).toLocaleString()}</Td>}
                        {!manualAllocation && <Td style={{ color: '#1a6b3c' }}>${Number(b.amount_paid).toLocaleString()}</Td>}
                        <Td style={{ color: '#dc2626', fontWeight: 600 }}>${Number(b.balance).toLocaleString()}</Td>
                        <Td>{statusBadge(b.status)}</Td>
                        {manualAllocation && (
                          <Td>
                            <Input type="number" step="0.01" min="0" max={b.balance} value={allocations[b.id] ?? ''}
                              onChange={e => setAllocations(a => ({ ...a, [b.id]: e.target.value }))}
                              style={{ width: 90, padding: '5px 8px' }} placeholder="0.00" />
                          </Td>
                        )}
                      </tr>
                    ))}
                    {bills.length === 0 && <tr><Td colSpan={manualAllocation ? 5 : 6} style={{ textAlign: 'center', color: '#9ca3af', padding: 20 }}>No outstanding bills.</Td></tr>}
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
                    <option value="swipe">Swipe / Card</option>
                    <option value="online">Online</option>
                    <option value="other">Other</option>
                  </Select>
                </FormGroup>
              </Grid>
              <FormGroup label="Reference Number (optional)">
                <Input value={form.reference_number} onChange={e => setForm(f => ({ ...f, reference_number: e.target.value }))} placeholder="EcoCash ref, bank ref…" />
              </FormGroup>

              {guardians.length > 0 ? (
                <FormGroup label="Parent / Guardian">
                  <Select value={form.guardian_id} onChange={e => setForm(f => ({ ...f, guardian_id: e.target.value }))}>
                    <option value="">Select guardian…</option>
                    {(guardians as any[]).map(g => <option key={g.id} value={g.id}>{g.name} ({g.relationship}) {g.phone ? `· ${g.phone}` : ''}</option>)}
                  </Select>
                </FormGroup>
              ) : (
                <Grid cols={2} style={{ marginBottom: 0 }}>
                  <FormGroup label="Payer Name"><Input value={form.payer_name} onChange={e => setForm(f => ({ ...f, payer_name: e.target.value }))} placeholder="Parent/guardian name" /></FormGroup>
                  <FormGroup label="Payer Phone"><Input value={form.payer_phone} onChange={e => setForm(f => ({ ...f, payer_phone: e.target.value }))} placeholder="07XX XXX XXX" /></FormGroup>
                </Grid>
              )}

              <FormGroup label="Payment Date"><Input type="date" value={form.payment_date} onChange={e => setForm(f => ({ ...f, payment_date: e.target.value }))} /></FormGroup>
              <FormGroup label="Remarks"><Input value={form.notes} onChange={e => setForm(f => ({ ...f, notes: e.target.value }))} placeholder="Optional remarks" /></FormGroup>
              <Btn loading={pay.isPending} onClick={handlePay} style={{ width: '100%', justifyContent: 'center', padding: '11px' }}>
                💳 Record Payment
              </Btn>
            </CardBody>
          </Card>

          {/* Receipt preview */}
          {receipt && (
            <div style={{ marginTop: 16 }}>
              <PaymentReceipt receipt={receipt} />
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
