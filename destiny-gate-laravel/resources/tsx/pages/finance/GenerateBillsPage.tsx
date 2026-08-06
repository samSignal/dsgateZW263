import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Card, CardHeader, CardBody, Spinner, PageHeader, Btn, FormGroup, Select, Alert } from '../../components/UI';

export default function GenerateBillsPage() {
  const [mode, setMode] = useState<'form' | 'stream' | 'student'>('form');
  const [form, setForm] = useState({ academic_year_id: '', term_id: '', form_id: '', stream_id: '', student_id: '', fee_structure_ids: [] as string[] });
  const [err, setErr] = useState('');
  const [result, setResult] = useState('');

  const { data: years = [] }   = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] }   = useQuery({ queryKey: ['terms'],           queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: forms = [] }   = useQuery({ queryKey: ['forms'],           queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'],         queryFn: () => api.get('/streams').then(r => r.data) });

  const filteredTerms   = (terms as any[]).filter(t => !form.academic_year_id || String(t.academic_year_id) === form.academic_year_id);
  const filteredStreams  = (streams as any[]).filter(s => !form.form_id || String(s.form_id) === form.form_id);

  const { data: structures = [] } = useQuery({
    queryKey: ['fee-structures-for-gen', form.academic_year_id, form.term_id, form.form_id],
    queryFn: () => form.academic_year_id && form.term_id
      ? api.get('/finance/structures', { params: { academic_year_id: form.academic_year_id, term_id: form.term_id, form_id: form.form_id || undefined, is_active: true } }).then(r => r.data)
      : Promise.resolve([]),
    enabled: !!(form.academic_year_id && form.term_id),
  });

  const generate = useMutation({
    mutationFn: (d: any) => {
      if (mode === 'form')   return api.post('/finance/bills/generate-form', d);
      if (mode === 'stream') return api.post('/finance/bills/generate-stream', d);
      return api.post('/finance/bills/generate-student', d);
    },
    onSuccess: (res) => { setResult(res.data.message); toastSuccess(res.data.message); setErr(''); },
    onError: (e: any) => { setErr(e.response?.data?.message ?? 'Failed to generate bills.'); setResult(''); },
  });

  const handleGenerate = async () => {
    const ok = await confirmAction('Generate Bills?', 'This will create bills for the selected students. Existing bills will not be duplicated.', 'Generate');
    if (!ok) return;
    setErr(''); setResult('');
    const payload: any = { academic_year_id: form.academic_year_id, term_id: form.term_id };
    if (mode === 'form')   payload.form_id = form.form_id;
    if (mode === 'stream') payload.stream_id = form.stream_id;
    if (mode === 'student') { payload.student_id = form.student_id; payload.fee_structure_ids = form.fee_structure_ids; }
    generate.mutate(payload);
  };

  return (
    <div>
      <PageHeader title="Generate Bills" subtitle="Create fee bills for students from fee structures" />
      {err    && <Alert type="error"   message={err} />}
      {result && <Alert type="success" message={result} />}

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
        <Card>
          <CardHeader title="Bill Generation Options" />
          <CardBody>
            {/* Mode selector */}
            <div style={{ display: 'flex', gap: 8, marginBottom: 20 }}>
              {(['form', 'stream', 'student'] as const).map(m => (
                <button key={m} onClick={() => setMode(m)} style={{
                  padding: '8px 16px', borderRadius: 8, border: '1.5px solid',
                  borderColor: mode === m ? '#b6924c' : '#e8eaed',
                  background: mode === m ? '#eef1f8' : '#fff',
                  color: mode === m ? '#8a6b34' : '#374151',
                  fontWeight: mode === m ? 600 : 400, fontSize: 13, cursor: 'pointer',
                  textTransform: 'capitalize',
                }}>{m === 'form' ? 'By Form' : m === 'stream' ? 'By Class' : 'Single Student'}</button>
              ))}
            </div>

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

            {(mode === 'form' || mode === 'stream') && (
              <FormGroup label="Form">
                <Select value={form.form_id} onChange={e => setForm(f => ({ ...f, form_id: e.target.value, stream_id: '' }))}>
                  <option value="">Select form…</option>
                  {(forms as any[]).map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
                </Select>
              </FormGroup>
            )}

            {mode === 'stream' && (
              <FormGroup label="Class">
                <Select value={form.stream_id} onChange={e => setForm(f => ({ ...f, stream_id: e.target.value }))}>
                  <option value="">Select stream…</option>
                  {filteredStreams.map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </Select>
              </FormGroup>
            )}

            {mode === 'student' && (
              <FormGroup label="Student ID">
                <input value={form.student_id} onChange={e => setForm(f => ({ ...f, student_id: e.target.value }))} placeholder="Enter student ID" style={{ width: '100%', padding: '9px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13 }} />
              </FormGroup>
            )}

            <Btn loading={generate.isPending} onClick={handleGenerate} style={{ width: '100%', justifyContent: 'center', padding: 11, marginTop: 8 }}>
              📄 Generate Bills
            </Btn>
          </CardBody>
        </Card>

        {/* Fee structures preview */}
        <Card>
          <CardHeader title="Available Fee Structures" />
          <CardBody>
            {(structures as any[]).length === 0 ? (
              <div style={{ textAlign: 'center', padding: '32px 20px', color: '#9ca3af' }}>
                <div style={{ fontSize: 28, marginBottom: 8 }}>📋</div>
                <p style={{ fontSize: 13 }}>Select year, term, and form to see available fee structures.</p>
              </div>
            ) : (
              <div>
                {(structures as any[]).map((s: any) => (
                  <div key={s.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 0', borderBottom: '1px solid #f3f4f6' }}>
                    <div>
                      <div style={{ fontSize: 13, fontWeight: 600 }}>{s.name}</div>
                      <div style={{ fontSize: 11, color: '#6b7280' }}>{s.category_name} · {s.form_name ?? 'All Forms'}</div>
                    </div>
                    <div style={{ fontSize: 15, fontWeight: 700, color: '#8a6b34' }}>${Number(s.amount).toLocaleString()}</div>
                  </div>
                ))}
                <div style={{ marginTop: 12, padding: '10px 14px', background: '#eef1f8', borderRadius: 8, display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ fontSize: 13, fontWeight: 600 }}>Total per student</span>
                  <span style={{ fontSize: 15, fontWeight: 800, color: '#8a6b34' }}>
                    ${(structures as any[]).reduce((sum: number, s: any) => sum + Number(s.amount), 0).toLocaleString()}
                  </span>
                </div>
              </div>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
