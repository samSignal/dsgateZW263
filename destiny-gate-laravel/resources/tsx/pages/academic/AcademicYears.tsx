import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete, confirmAction } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Grid, Alert } from '../../components/UI';
import { useAuth } from '../../hooks/useAuth';

interface AcademicYear { id: number; name: string; start_date: string; end_date: string; is_active: boolean }
const empty = { name: '', start_date: '', end_date: '' };

export default function AcademicYears() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<AcademicYear | null>(null);
  const [form, setForm]       = useState(empty);
  const [formErr, setFormErr] = useState('');

  const { data = [], isLoading } = useQuery<AcademicYear[]>({
    queryKey: ['academic-years'],
    queryFn: () => api.get('/academic-years').then(r => r.data),
  });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (y: AcademicYear) => { setEditing(y); setForm({ name: y.name, start_date: y.start_date, end_date: y.end_date }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing
      ? api.put(`/academic-years/${editing.id}`, d)
      : api.post('/academic-years', d),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['academic-years'] });
      setOpen(false);
      toastSuccess(editing ? 'Academic year updated successfully.' : 'Academic year created successfully.');
    },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const activate = useMutation({
    mutationFn: (id: number) => api.post(`/academic-years/${id}/activate`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['academic-years'] }); toastSuccess('Academic year activated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to activate.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/academic-years/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['academic-years'] }); toastSuccess('Academic year deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete this academic year.'),
  });

  const handleActivate = async (y: AcademicYear) => {
    const ok = await confirmAction('Activate Academic Year?', `Set "${y.name}" as the active academic year? All other years will be deactivated.`, 'Yes, activate');
    if (ok) activate.mutate(y.id);
  };

  const handleDelete = async (y: AcademicYear) => {
    const ok = await confirmDelete(`academic year "${y.name}"`);
    if (ok) del.mutate(y.id);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Academic Years" subtitle="Manage school academic years — only one can be active at a time" action={<Btn onClick={openAdd}>+ Add Academic Year</Btn>} />

      <Card>
        <Table headers={['Name', 'Start Date', 'End Date', 'Status', 'Actions']}>
          {data.map(y => (
            <tr key={y.id}>
              <Td><strong>{y.name}</strong></Td>
              <Td>{y.start_date}</Td>
              <Td>{y.end_date}</Td>
              <Td>{y.is_active ? <Badge variant="green">Active</Badge> : <Badge variant="gray">Inactive</Badge>}</Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  {!y.is_active && isAdmin && <Btn size="sm" variant="outline" loading={activate.isPending} onClick={() => handleActivate(y)}>Activate</Btn>}
                  <Btn size="sm" variant="outline" onClick={() => openEdit(y)}>Edit</Btn>
                  {!y.is_active && <Btn size="sm" variant="danger" onClick={() => handleDelete(y)}>Delete</Btn>}
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Academic Year' : 'Add Academic Year'}>
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Year Name (e.g. 2026)">
          <Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="2026" />
        </FormGroup>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Start Date"><Input type="date" value={form.start_date} onChange={e => setForm(f => ({ ...f, start_date: e.target.value }))} /></FormGroup>
          <FormGroup label="End Date">  <Input type="date" value={form.end_date}   onChange={e => setForm(f => ({ ...f, end_date:   e.target.value }))} /></FormGroup>
        </Grid>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save</Btn>
        </div>
      </Modal>
    </div>
  );
}
