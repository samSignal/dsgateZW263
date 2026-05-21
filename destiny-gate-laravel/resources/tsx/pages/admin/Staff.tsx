import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, Table, Td, Spinner, PageHeader, Alert, Badge, Modal, FormGroup, Input, Select, Btn, Grid } from '../../components/UI';

export default function Staff() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [msg, setMsg] = useState('');
  const [err, setErr] = useState('');
  const [form, setForm] = useState({ first_name:'', last_name:'', email:'', phone:'', department:'', position:'', role:'teacher', qualifications:'', employment_date:'' });

  const { data, isLoading } = useQuery({ queryKey: ['staff'], queryFn: () => api.get('/admin/staff').then(r => r.data) });

  const create = useMutation({
    mutationFn: (d: typeof form) => api.post('/admin/staff', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['staff'] }); setOpen(false); setMsg('Staff member created.'); setForm({ first_name:'', last_name:'', email:'', phone:'', department:'', position:'', role:'teacher', qualifications:'', employment_date:'' }); },
    onError: (e: any) => setErr(e.response?.data?.message ?? 'Failed to create staff.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Staff Management" subtitle="Teachers, bursars, and admin staff"
        action={<Btn onClick={() => setOpen(true)}>+ Add Staff</Btn>} />
      {msg && <Alert type="success" message={msg} />}
      <Card>
        <Table headers={['Staff ID', 'Name', 'Email', 'Department', 'Position', 'Status']}>
          {data?.data?.map((s: any) => (
            <tr key={s.id}>
              <Td><code style={{ background: '#f3f4f6', padding: '2px 7px', borderRadius: 5, fontSize: 12 }}>{s.staff_id}</code></Td>
              <Td><strong>{s.first_name} {s.last_name}</strong></Td>
              <Td style={{ color: '#6b7280' }}>{s.email}</Td>
              <Td>{s.department ?? '—'}</Td>
              <Td>{s.position ?? '—'}</Td>
              <Td>{s.is_active ? <Badge variant="green">Active</Badge> : <Badge variant="red">Inactive</Badge>}</Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title="Add Staff Member">
        {err && <Alert type="error" message={err} />}
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="First Name"><Input value={form.first_name} onChange={e => setForm(f => ({...f, first_name: e.target.value}))} required /></FormGroup>
          <FormGroup label="Last Name"><Input value={form.last_name} onChange={e => setForm(f => ({...f, last_name: e.target.value}))} required /></FormGroup>
        </Grid>
        <FormGroup label="Email"><Input type="email" value={form.email} onChange={e => setForm(f => ({...f, email: e.target.value}))} required /></FormGroup>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Phone"><Input value={form.phone} onChange={e => setForm(f => ({...f, phone: e.target.value}))} /></FormGroup>
          <FormGroup label="Role">
            <Select value={form.role} onChange={e => setForm(f => ({...f, role: e.target.value}))}>
              <option value="teacher">Teacher</option>
              <option value="bursar">Bursar</option>
              <option value="headmaster">Headmaster</option>
              <option value="admin">Admin</option>
            </Select>
          </FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Department"><Input value={form.department} onChange={e => setForm(f => ({...f, department: e.target.value}))} /></FormGroup>
          <FormGroup label="Position"><Input value={form.position} onChange={e => setForm(f => ({...f, position: e.target.value}))} /></FormGroup>
        </Grid>
        <FormGroup label="Employment Date"><Input type="date" value={form.employment_date} onChange={e => setForm(f => ({...f, employment_date: e.target.value}))} /></FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={create.isPending} onClick={() => create.mutate(form)}>Create Staff</Btn>
        </div>
      </Modal>
    </div>
  );
}
