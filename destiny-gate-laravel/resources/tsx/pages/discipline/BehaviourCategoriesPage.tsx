import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Badge, Btn, Card, FormGroup, Input, Modal, PageHeader, Select, Spinner, Table, Td, Textarea } from '../../components/UI';

export default function BehaviourCategoriesPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<any>(null);
  const [form, setForm] = useState({ name: '', type: 'negative', description: '' });
  const { data = [], isLoading } = useQuery<any[]>({ queryKey: ['behaviour-categories'], queryFn: () => api.get('/discipline/behaviour/categories').then(r => r.data) });
  const save = useMutation({ mutationFn: () => editing ? api.put(`/discipline/behaviour/categories/${editing.id}`, form) : api.post('/discipline/behaviour/categories', form), onSuccess: () => { qc.invalidateQueries({ queryKey: ['behaviour-categories'] }); setOpen(false); toastSuccess('Category saved.'); }, onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not save category.') });
  const toggle = useMutation({ mutationFn: (c: any) => api.post(`/discipline/behaviour/categories/${c.id}/${c.is_active ? 'deactivate' : 'activate'}`), onSuccess: () => { qc.invalidateQueries({ queryKey: ['behaviour-categories'] }); toastSuccess('Status updated.'); } });
  const openEdit = (c: any) => { setEditing(c); setForm({ name: c.name, type: c.type, description: c.description ?? '' }); setOpen(true); };
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Behaviour Categories" subtitle="Manage positive and negative behaviour categories" action={<Btn onClick={() => { setEditing(null); setForm({ name: '', type: 'negative', description: '' }); setOpen(true); }}>Add Category</Btn>} /><Card><Table headers={['Name', 'Type', 'Incidents', 'Status', 'Actions']}>{data.map(c => <tr key={c.id}><Td><strong>{c.name}</strong><br /><span className="text-xs text-slate-500">{c.description}</span></Td><Td><Badge variant={c.type === 'positive' ? 'green' : 'red'}>{c.type}</Badge></Td><Td>{c.incidents_count}</Td><Td>{c.is_active ? 'Active' : 'Inactive'}</Td><Td><div className="flex gap-2"><Btn size="sm" variant="outline" onClick={() => openEdit(c)}>Edit</Btn><Btn size="sm" variant="outline" onClick={() => toggle.mutate(c)}>{c.is_active ? 'Deactivate' : 'Activate'}</Btn></div></Td></tr>)}</Table></Card><Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Category' : 'Add Category'}><FormGroup label="Name"><Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} /></FormGroup><FormGroup label="Type"><Select value={form.type} onChange={e => setForm(f => ({ ...f, type: e.target.value }))}><option value="negative">Negative</option><option value="positive">Positive</option></Select></FormGroup><FormGroup label="Description"><Textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} /></FormGroup><div className="flex justify-end gap-2"><Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn><Btn loading={save.isPending} onClick={() => save.mutate()}>Save</Btn></div></Modal></div>;
}
