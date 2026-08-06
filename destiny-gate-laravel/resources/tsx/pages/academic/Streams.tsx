import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Modal, FormGroup, Input, Select, Grid, Alert } from '../../components/UI';

interface SchoolForm { id: number; name: string; level: number }
interface Category { id: number; name: string; code: string; is_active: boolean }
interface Stream {
  id: number;
  form_id: number;
  category_id: number | null;
  name: string;
  capacity: number;
  form_name: string;
  form_level: number;
  category_name: string | null;
  category_code: string | null;
  category_is_active: boolean | null;
}
const empty = { form_id: '', category_id: '', name: '', capacity: '40' };

export default function Streams() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<Stream | null>(null);
  const [form, setForm]       = useState(empty);
  const [filterForm, setFilterForm] = useState('');
  const [filterCategory, setFilterCategory] = useState('');
  const [formErr, setFormErr] = useState('');

  const { data: forms = [] } = useQuery<SchoolForm[]>({ queryKey: ['forms'], queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: categories = [] } = useQuery<Category[]>({ queryKey: ['categories'], queryFn: () => api.get('/categories').then(r => r.data) });
  const { data: streams = [], isLoading } = useQuery<Stream[]>({
    queryKey: ['streams', filterForm, filterCategory],
    queryFn: () => api.get('/streams', {
      params: {
        ...(filterForm ? { form_id: filterForm } : {}),
        ...(filterCategory ? { category_id: filterCategory } : {}),
      },
    }).then(r => r.data),
  });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (s: Stream) => {
    setEditing(s);
    setForm({
      form_id: String(s.form_id),
      category_id: s.category_id ? String(s.category_id) : '',
      name: s.name,
      capacity: String(s.capacity),
    });
    setFormErr('');
    setOpen(true);
  };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/streams/${editing.id}`, d) : api.post('/streams', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['streams'] }); setOpen(false); toastSuccess(editing ? 'Class updated.' : 'Class created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/streams/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['streams'] }); toastSuccess('Class deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete this class.'),
  });

  const handleDelete = async (s: Stream) => {
    const ok = await confirmDelete(`class "${s.form_name} — ${s.name}"`);
    if (ok) del.mutate(s.id);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Classes" subtitle="Classes under each form (e.g. Form 1A, Form 5 Sciences)" action={<Btn onClick={openAdd}>+ Add Class</Btn>} />

      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, marginBottom: 16 }}>
        <Select value={filterForm} onChange={e => setFilterForm(e.target.value)} style={{ width: 200 }}>
          <option value="">All Forms</option>
          {forms.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
        </Select>
        <Select value={filterCategory} onChange={e => setFilterCategory(e.target.value)} style={{ width: 220 }}>
          <option value="">All Categories</option>
          {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
        </Select>
      </div>

      <Card>
        <Table headers={['Form', 'Class Name', 'Category', 'Capacity', 'Actions']}>
          {streams.map(s => (
            <tr key={s.id}>
              <Td>{s.form_name}</Td>
              <Td><strong>{s.name}</strong></Td>
              <Td>{s.category_name ?? 'Unassigned'}</Td>
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

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Class' : 'Add Class'}>
        {formErr && <Alert type="error" message={formErr} />}
        {!editing && (
          <FormGroup label="Form">
            <Select value={form.form_id} onChange={e => setForm(f => ({ ...f, form_id: e.target.value }))}>
              <option value="">Select form…</option>
              {forms.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
            </Select>
          </FormGroup>
        )}
        <FormGroup label="Category">
          <Select value={form.category_id} onChange={e => setForm(f => ({ ...f, category_id: e.target.value }))}>
            <option value="">No category</option>
            {categories
              .filter(c => c.is_active || String(c.id) === form.category_id)
              .map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
          </Select>
        </FormGroup>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Class Name (e.g. 1A, Sciences)">
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
