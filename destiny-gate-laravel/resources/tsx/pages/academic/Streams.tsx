import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Modal, FormGroup, Input, Select, Grid, Alert } from '../../components/UI';

interface SchoolForm { id: number; name: string; level: number }
interface Stream { id: number; form_id: number; name: string; capacity: number; form_name: string; form_level: number }
const empty = { form_id: '', name: '', capacity: '40' };

export default function Streams() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<Stream | null>(null);
  const [form, setForm]       = useState(empty);
  const [filterForm, setFilterForm] = useState('');
  const [formErr, setFormErr] = useState('');

  const { data: forms = [] } = useQuery<SchoolForm[]>({ queryKey: ['forms'], queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams = [], isLoading } = useQuery<Stream[]>({
    queryKey: ['streams', filterForm],
    queryFn: () => api.get('/streams', { params: filterForm ? { form_id: filterForm } : {} }).then(r => r.data),
  });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (s: Stream) => { setEditing(s); setForm({ form_id: String(s.form_id), name: s.name, capacity: String(s.capacity) }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/streams/${editing.id}`, d) : api.post('/streams', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['streams'] }); setOpen(false); toastSuccess(editing ? 'Stream updated.' : 'Stream created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/streams/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['streams'] }); toastSuccess('Stream deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete this stream.'),
  });

  const handleDelete = async (s: Stream) => {
    const ok = await confirmDelete(`stream "${s.form_name} — ${s.name}"`);
    if (ok) del.mutate(s.id);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Streams" subtitle="Class groups under each form (e.g. Form 1A, Form 5 Sciences)" action={<Btn onClick={openAdd}>+ Add Stream</Btn>} />

      <div style={{ marginBottom: 16 }}>
        <Select value={filterForm} onChange={e => setFilterForm(e.target.value)} style={{ width: 200 }}>
          <option value="">All Forms</option>
          {forms.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
        </Select>
      </div>

      <Card>
        <Table headers={['Form', 'Stream Name', 'Capacity', 'Actions']}>
          {streams.map(s => (
            <tr key={s.id}>
              <Td>{s.form_name}</Td>
              <Td><strong>{s.name}</strong></Td>
              <Td>{s.capacity}</Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Btn size="sm" variant="outline" onClick={() => openEdit(s)}>Edit</Btn>
                  <Btn size="sm" variant="danger"  onClick={() => handleDelete(s)}>Delete</Btn>
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Stream' : 'Add Stream'}>
        {formErr && <Alert type="error" message={formErr} />}
        {!editing && (
          <FormGroup label="Form">
            <Select value={form.form_id} onChange={e => setForm(f => ({ ...f, form_id: e.target.value }))}>
              <option value="">Select form…</option>
              {forms.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
            </Select>
          </FormGroup>
        )}
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Stream Name (e.g. 1A, Sciences)">
            <Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="1A" />
          </FormGroup>
          <FormGroup label="Capacity">
            <Input type="number" value={form.capacity} onChange={e => setForm(f => ({ ...f, capacity: e.target.value }))} min="1" max="200" />
          </FormGroup>
        </Grid>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
