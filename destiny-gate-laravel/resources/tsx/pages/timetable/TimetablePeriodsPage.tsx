import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Grid, Alert } from '../../components/UI';

interface Period { id: number; name: string; period_number: number; start_time: string; end_time: string; is_break: boolean; is_active: boolean }
const empty = { name: '', period_number: '', start_time: '', end_time: '', is_break: false };

export default function TimetablePeriodsPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Period | null>(null);
  const [form, setForm] = useState(empty);
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<Period[]>({ queryKey: ['tt-periods'], queryFn: () => api.get('/timetable/periods').then(r => r.data) });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (p: Period) => { setEditing(p); setForm({ name: p.name, period_number: String(p.period_number), start_time: p.start_time.slice(0,5), end_time: p.end_time.slice(0,5), is_break: p.is_break }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/timetable/periods/${editing.id}`, d) : api.post('/timetable/periods', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['tt-periods'] }); setOpen(false); toastSuccess(editing ? 'Period updated.' : 'Period created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const toggle = useMutation({
    mutationFn: ({ id, active }: { id: number; active: boolean }) => api.post(`/timetable/periods/${id}/${active ? 'deactivate' : 'activate'}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['tt-periods'] }); toastSuccess('Status updated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Timetable Periods" subtitle="Manage school lesson periods and breaks" action={<Btn onClick={openAdd}>+ Add Period</Btn>} />
      <Card>
        <Table headers={['#', 'Name', 'Start', 'End', 'Duration', 'Type', 'Status', 'Actions']}>
          {data.map(p => {
            const start = new Date(`2000-01-01T${p.start_time}`);
            const end   = new Date(`2000-01-01T${p.end_time}`);
            const mins  = Math.round((end.getTime() - start.getTime()) / 60000);
            return (
              <tr key={p.id}>
                <Td><span style={{ fontWeight: 700, color: '#8a6b34' }}>{p.period_number}</span></Td>
                <Td><strong>{p.name}</strong></Td>
                <Td>{p.start_time.slice(0,5)}</Td>
                <Td>{p.end_time.slice(0,5)}</Td>
                <Td style={{ color: '#6b7280' }}>{mins} min</Td>
                <Td>{p.is_break ? <Badge variant="amber">Break</Badge> : <Badge variant="blue">Lesson</Badge>}</Td>
                <Td><Badge variant={p.is_active ? 'green' : 'gray'}>{p.is_active ? 'Active' : 'Inactive'}</Badge></Td>
                <Td>
                  <div style={{ display: 'flex', gap: 6 }}>
                    <Btn size="sm" variant="outline" onClick={() => openEdit(p)}>Edit</Btn>
                    <Btn size="sm" variant="ghost" onClick={() => toggle.mutate({ id: p.id, active: p.is_active })}>{p.is_active ? 'Deactivate' : 'Activate'}</Btn>
                  </div>
                </Td>
              </tr>
            );
          })}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Period' : 'Add Period'}>
        {formErr && <Alert type="error" message={formErr} />}
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Period Name"><Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="e.g. Period 1" /></FormGroup>
          {!editing && <FormGroup label="Period Number"><Input type="number" value={form.period_number} onChange={e => setForm(f => ({ ...f, period_number: e.target.value }))} min="1" /></FormGroup>}
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Start Time"><Input type="time" value={form.start_time} onChange={e => setForm(f => ({ ...f, start_time: e.target.value }))} /></FormGroup>
          <FormGroup label="End Time">  <Input type="time" value={form.end_time}   onChange={e => setForm(f => ({ ...f, end_time:   e.target.value }))} /></FormGroup>
        </Grid>
        <FormGroup label="Type">
          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: 13 }}>
            <input type="checkbox" checked={form.is_break} onChange={e => setForm(f => ({ ...f, is_break: e.target.checked }))} />
            Mark as Break / Lunch
          </label>
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
