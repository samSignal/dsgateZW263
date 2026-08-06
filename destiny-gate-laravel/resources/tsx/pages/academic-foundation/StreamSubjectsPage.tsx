import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Badge, Btn, Card, CardBody, CardHeader, FormGroup, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

export default function StreamSubjectsPage() {
  const qc = useQueryClient();
  const [form, setForm] = useState({ academic_year_id: '', term_id: '', form_id: '', stream_id: '', subject_id: '', is_compulsory: false });
  const { data: meta = {} } = useQuery<any>({ queryKey: ['af-meta'], queryFn: () => api.get('/academic-foundation/meta').then(r => r.data) });
  const { data = [], isLoading } = useQuery<any[]>({ queryKey: ['stream-subjects'], queryFn: () => api.get('/academic-foundation/stream-subjects').then(r => r.data) });
  const streams = (meta.streams ?? []).filter((s: any) => !form.form_id || String(s.form_id) === form.form_id);
  const save = useMutation({
    mutationFn: () => api.post('/academic-foundation/stream-subjects', { academic_year_id: form.academic_year_id, term_id: form.term_id, form_id: form.form_id, stream_id: form.stream_id, subjects: [{ subject_id: form.subject_id, is_compulsory: form.is_compulsory }] }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['stream-subjects'] }); toastSuccess('Subject assigned to stream.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not assign subject.'),
  });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Class Subjects" subtitle="Assign compulsory and optional subjects offered by each class" /><Card style={{ marginBottom: 20 }}><CardHeader title="Assign Subject" /><CardBody><div className="grid grid-cols-1 gap-3 md:grid-cols-3"><FormGroup label="Year"><Select value={form.academic_year_id} onChange={e => setForm(f => ({ ...f, academic_year_id: e.target.value }))}>{(meta.academic_years ?? []).map((y: any) => <option key={y.id} value={y.id}>{y.name}</option>)}</Select></FormGroup><FormGroup label="Term"><Select value={form.term_id} onChange={e => setForm(f => ({ ...f, term_id: e.target.value }))}>{(meta.terms ?? []).map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}</Select></FormGroup><FormGroup label="Form"><Select value={form.form_id} onChange={e => setForm(f => ({ ...f, form_id: e.target.value, stream_id: '' }))}>{(meta.forms ?? []).map((x: any) => <option key={x.id} value={x.id}>{x.name}</option>)}</Select></FormGroup><FormGroup label="Class"><Select value={form.stream_id} onChange={e => setForm(f => ({ ...f, stream_id: e.target.value }))}>{streams.map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}</Select></FormGroup><FormGroup label="Subject"><Select value={form.subject_id} onChange={e => setForm(f => ({ ...f, subject_id: e.target.value }))}>{(meta.subjects ?? []).map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}</Select></FormGroup><label className="mt-7 flex items-center gap-2 text-sm"><input type="checkbox" checked={form.is_compulsory} onChange={e => setForm(f => ({ ...f, is_compulsory: e.target.checked }))} /> Compulsory</label></div><Btn loading={save.isPending} onClick={() => save.mutate()}>Assign Subject</Btn></CardBody></Card><Card><CardHeader title="Assigned Class Subjects" /><Table headers={['Class', 'Subject', 'Type', 'Year', 'Term']}>{data.map(r => <tr key={r.id}><Td>{r.form_name} {r.stream_name}</Td><Td>{r.subject_name}<br /><span className="text-xs text-slate-500">{r.subject_code}</span></Td><Td><Badge variant={r.is_compulsory ? 'green' : 'blue'}>{r.is_compulsory ? 'Compulsory' : 'Optional'}</Badge></Td><Td>{r.academic_year_name}</Td><Td>{r.term_name}</Td></tr>)}</Table></Card></div>;
}
