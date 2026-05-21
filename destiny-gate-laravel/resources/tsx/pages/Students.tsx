import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { Card, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge, Modal, FormGroup, Input, Select, Grid } from '../components/UI';

export default function Students() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [msg, setMsg] = useState('');
  const [search, setSearch] = useState('');
  const [form, setForm] = useState({ first_name:'', last_name:'', email:'', date_of_birth:'', gender:'', class_id:'', admission_date: new Date().toISOString().split('T')[0], national_id:'', blood_type:'', allergies:'', medical_conditions:'' });

  const { data, isLoading } = useQuery({ queryKey: ['students', search], queryFn: () => api.get('/students', { params: { search } }).then(r => r.data) });
  const { data: classes } = useQuery({ queryKey: ['classes-list'], queryFn: () => api.get('/classes').then(r => r.data) });

  const create = useMutation({
    mutationFn: (d: any) => api.post('/students', d),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['students'] });
      setOpen(false);
      const sn = res.data?.student_number;
      toastSuccess(`Student enrolled! Student Number: ${sn ?? 'generated'}`);
    },
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Students" subtitle="All enrolled students"
        action={<Btn onClick={() => setOpen(true)}>+ Enroll Student</Btn>} />
      {msg && <Alert type="success" message={msg} />}

      <div style={{ marginBottom: 16 }}>
        <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search by name or admission number…"
          style={{ padding: '9px 14px', border: '1px solid #d1d5db', borderRadius: 8, fontSize: 13, width: 300, outline: 'none' }} />
      </div>

      <Card>
        <Table headers={['Student #', 'Name', 'Class', 'Gender', 'Status', 'Actions']}>
          {data?.data?.map((s: any) => (
            <tr key={s.id}>
              <Td>
                <div>
                  <code style={{ background: '#eff6ff', color: '#1e40af', padding: '2px 7px', borderRadius: 5, fontSize: 11, display: 'block', marginBottom: 2 }}>{s.student_number ?? '—'}</code>
                  <code style={{ background: '#f1f5f9', color: '#475569', padding: '2px 7px', borderRadius: 5, fontSize: 10 }}>{s.admission_number}</code>
                </div>
              </Td>
              <Td><strong>{s.first_name} {s.last_name}</strong></Td>
              <Td>{s.class_name ?? '—'}</Td>
              <Td style={{ textTransform: 'capitalize' }}>{s.gender ?? '—'}</Td>
              <Td>{statusBadge(s.status)}</Td>
              <Td>
                <Link to={`/app/students/${s.id}`} style={{ fontSize: 12, color: '#1a6b3c', textDecoration: 'none', fontWeight: 500 }}>View →</Link>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title="Enroll New Student">
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="First Name"><Input value={form.first_name} onChange={e => setForm(f => ({...f, first_name: e.target.value}))} required /></FormGroup>
          <FormGroup label="Last Name"><Input value={form.last_name} onChange={e => setForm(f => ({...f, last_name: e.target.value}))} required /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Email"><Input type="email" value={form.email} onChange={e => setForm(f => ({...f, email: e.target.value}))} /></FormGroup>
          <FormGroup label="Date of Birth"><Input type="date" value={form.date_of_birth} onChange={e => setForm(f => ({...f, date_of_birth: e.target.value}))} /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Gender">
            <Select value={form.gender} onChange={e => setForm(f => ({...f, gender: e.target.value}))}>
              <option value="">Select…</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </Select>
          </FormGroup>
          <FormGroup label="Class">
            <Select value={form.class_id} onChange={e => setForm(f => ({...f, class_id: e.target.value}))}>
              <option value="">Select class…</option>
              {classes?.map((c: any) => <option key={c.id} value={c.id}>{c.class_name} {c.stream} ({c.academic_year})</option>)}
            </Select>
          </FormGroup>
        </Grid>
        <FormGroup label="Admission Date"><Input type="date" value={form.admission_date} onChange={e => setForm(f => ({...f, admission_date: e.target.value}))} required /></FormGroup>
        <FormGroup label="National ID">
          <Input value={form.national_id} onChange={e => setForm(f => ({...f, national_id: e.target.value}))} placeholder="e.g. 63-123456A78" />
          <p style={{ fontSize: 11, color: '#059669', marginTop: 4, background: '#f0faf4', padding: '6px 10px', borderRadius: 6 }}>
            🔐 Student will login using their <strong>Student Number</strong> as username and <strong>National ID as default password</strong>. Student number is auto-generated on save.
          </p>
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={create.isPending} onClick={() => create.mutate(form)}>Enroll Student</Btn>
        </div>
      </Modal>
    </div>
  );
}
