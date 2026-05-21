import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, Table, Td, Spinner, PageHeader, Alert, Modal, FormGroup, Input, Select, Btn, Grid } from '../../components/UI';

export default function Classes() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [msg, setMsg] = useState('');
  const [form, setForm] = useState({ class_name:'', stream:'', academic_year: new Date().getFullYear() + '/' + (new Date().getFullYear()+1), capacity:'', class_teacher_id:'' });

  const { data, isLoading } = useQuery({ queryKey: ['classes-admin'], queryFn: () => api.get('/admin/classes').then(r => r.data) });
  const { data: staffData } = useQuery({ queryKey: ['staff'], queryFn: () => api.get('/admin/staff').then(r => r.data) });

  const create = useMutation({
    mutationFn: (d: any) => api.post('/admin/classes', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['classes-admin'] }); setOpen(false); setMsg('Class created.'); },
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Classes" subtitle="Manage school classes and streams"
        action={<Btn onClick={() => setOpen(true)}>+ Create Class</Btn>} />
      {msg && <Alert type="success" message={msg} />}
      <Card>
        <Table headers={['Class Name', 'Stream', 'Academic Year', 'Class Teacher', 'Capacity']}>
          {data?.data?.map((c: any) => (
            <tr key={c.id}>
              <Td><strong>{c.class_name}</strong></Td>
              <Td>{c.stream ?? '—'}</Td>
              <Td>{c.academic_year}</Td>
              <Td>{c.class_teacher ? `${c.class_teacher.first_name} ${c.class_teacher.last_name}` : '—'}</Td>
              <Td>{c.capacity ?? '—'}</Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title="Create Class">
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Class Name"><Input value={form.class_name} onChange={e => setForm(f => ({...f, class_name: e.target.value}))} placeholder="e.g. Form 1" required /></FormGroup>
          <FormGroup label="Stream"><Input value={form.stream} onChange={e => setForm(f => ({...f, stream: e.target.value}))} placeholder="e.g. A, Science" /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Academic Year"><Input value={form.academic_year} onChange={e => setForm(f => ({...f, academic_year: e.target.value}))} required /></FormGroup>
          <FormGroup label="Capacity"><Input type="number" value={form.capacity} onChange={e => setForm(f => ({...f, capacity: e.target.value}))} /></FormGroup>
        </Grid>
        <FormGroup label="Class Teacher">
          <Select value={form.class_teacher_id} onChange={e => setForm(f => ({...f, class_teacher_id: e.target.value}))}>
            <option value="">None</option>
            {staffData?.data?.map((s: any) => <option key={s.id} value={s.id}>{s.first_name} {s.last_name}</option>)}
          </Select>
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={create.isPending} onClick={() => create.mutate(form)}>Create</Btn>
        </div>
      </Modal>
    </div>
  );
}
