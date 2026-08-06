import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Alert, Btn, Card, CardBody, CardHeader, FormGroup, Input, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

export default function AttendanceSessionsPage() {
  const qc = useQueryClient();
  const today = new Date().toISOString().slice(0, 10);
  const [form, setForm] = useState({ academic_year_id: '', term_id: '', form_id: '', stream_id: '', attendance_date: today, session_type: 'morning', subject_id: '' });
  const [error, setError] = useState('');
  const { data: meta = {} } = useQuery<any>({ queryKey: ['discipline-meta'], queryFn: () => api.get('/discipline/meta').then(r => r.data) });
  const years = meta.academic_years ?? [];
  const terms = meta.terms ?? [];
  const forms = meta.forms ?? [];
  const streams = meta.streams ?? [];
  const subjects = meta.subjects ?? [];
  const { data: sessions = [], isLoading } = useQuery<any[]>({ queryKey: ['attendance-sessions'], queryFn: () => api.get('/discipline/attendance/sessions').then(r => r.data) });
  const create = useMutation({
    mutationFn: () => api.post('/discipline/attendance/sessions', { ...form, subject_id: form.session_type === 'lesson' && form.subject_id ? Number(form.subject_id) : null }),
    onSuccess: (r) => { qc.invalidateQueries({ queryKey: ['attendance-sessions'] }); toastSuccess('Attendance session created.'); window.location.href = `/app/discipline/attendance/sessions/${r.data.session_id}`; },
    onError: (e: any) => setError(e.response?.data?.message ?? 'Could not create attendance session.'),
  });
  const filteredStreams = streams.filter(s => !form.form_id || String(s.form_id) === form.form_id);
  if (isLoading) return <Spinner />;
  return (
    <div>
      <PageHeader title="Attendance Sessions" subtitle="Create and manage daily stream registers" />
      {error && <Alert type="error" message={error} />}
      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Create Register" />
        <CardBody>
          <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
            <FormGroup label="Academic Year"><Select value={form.academic_year_id} onChange={e => setForm(f => ({ ...f, academic_year_id: e.target.value }))}><option value="">Select</option>{years.map(y => <option key={y.id} value={y.id}>{y.name}</option>)}</Select></FormGroup>
            <FormGroup label="Term"><Select value={form.term_id} onChange={e => setForm(f => ({ ...f, term_id: e.target.value }))}><option value="">Select</option>{terms.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}</Select></FormGroup>
            <FormGroup label="Date"><Input type="date" value={form.attendance_date} onChange={e => setForm(f => ({ ...f, attendance_date: e.target.value }))} /></FormGroup>
            <FormGroup label="Form"><Select value={form.form_id} onChange={e => setForm(f => ({ ...f, form_id: e.target.value, stream_id: '' }))}><option value="">Select</option>{forms.map(fm => <option key={fm.id} value={fm.id}>{fm.name}</option>)}</Select></FormGroup>
            <FormGroup label="Class"><Select value={form.stream_id} onChange={e => setForm(f => ({ ...f, stream_id: e.target.value }))}><option value="">Select</option>{filteredStreams.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}</Select></FormGroup>
            <FormGroup label="Session"><Select value={form.session_type} onChange={e => setForm(f => ({ ...f, session_type: e.target.value }))}><option value="morning">Morning</option><option value="afternoon">Afternoon</option><option value="lesson">Lesson</option></Select></FormGroup>
            {form.session_type === 'lesson' && <FormGroup label="Subject"><Select value={form.subject_id} onChange={e => setForm(f => ({ ...f, subject_id: e.target.value }))}><option value="">Optional</option>{subjects.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}</Select></FormGroup>}
          </div>
          <Btn loading={create.isPending} onClick={() => create.mutate()}>Create Register</Btn>
        </CardBody>
      </Card>
      <Card><CardHeader title="Registers" /><Table headers={['Date', 'Class', 'Session', 'Subject', 'Status', 'Action']}>{sessions.map(s => <tr key={s.id}><Td>{s.attendance_date}</Td><Td>{s.form_name} {s.stream_name}</Td><Td>{s.session_type}</Td><Td>{s.subject_name ?? '-'}</Td><Td>{s.status}</Td><Td><Link className="font-semibold text-emerald-700" to={`/app/discipline/attendance/sessions/${s.id}`}>Open</Link></Td></tr>)}</Table></Card>
    </div>
  );
}
