import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Modal, FormGroup, Input, Textarea, Alert } from '../../components/UI';

interface SubjectGroup { id: number; name: string; description: string | null }
const empty = { name: '', description: '' };

export default function SubjectGroups() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<SubjectGroup | null>(null);
  const [form, setForm]       = useState(empty);
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<SubjectGroup[]>({
    queryKey: ['subject-groups'],
    queryFn: () => api.get('/subject-groups').then(r => r.data),
  });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (g: SubjectGroup) => { setEditing(g); setForm({ name: g.name, description: g.description ?? '' }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/subject-groups/${editing.id}`, d) : api.post('/subject-groups', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['subject-groups'] }); setOpen(false); toastSuccess(editing ? 'Group updated.' : 'Subject group created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/subject-groups/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['subject-groups'] }); toastSuccess('Subject group deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete — group has subjects linked.'),
  });

  const handleDelete = async (g: SubjectGroup) => {
    const ok = await confirmDelete(`subject group "${g.name}"`);
    if (ok) del.mutate(g.id);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Subject Groups" subtitle="Group subjects by category (Sciences, Arts, Commercials…)" action={<Btn onClick={openAdd}>+ Add Group</Btn>} />

      <Card>
        <Table headers={['Name', 'Description', 'Actions']}>
          {data.map(g => (
            <tr key={g.id}>
              <Td><strong>{g.name}</strong></Td>
              <Td style={{ color: '#6b7280' }}>{g.description ?? '—'}</Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Btn size="sm" variant="outline" onClick={() => openEdit(g)}>Edit</Btn>
                  <Btn size="sm" variant="danger"  onClick={() => handleDelete(g)}>Delete</Btn>
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Subject Group' : 'Add Subject Group'}>
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Group Name">
          <Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="e.g. Sciences" />
        </FormGroup>
        <FormGroup label="Description">
          <Textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Optional description" />
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
