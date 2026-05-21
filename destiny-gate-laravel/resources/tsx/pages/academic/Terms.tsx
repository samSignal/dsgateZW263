import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete, confirmAction } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Select, Grid, Alert } from '../../components/UI';

interface AcademicYear { id: number; name: string }
interface Term { id: number; academic_year_id: number; name: string; start_date: string; end_date: string; is_current: boolean; academic_year_name: string }
const empty = { academic_year_id: '', name: '', start_date: '', end_date: '' };

export default function Terms() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<Term | null>(null);
  const [form, setForm]       = useState(empty);
  const [filterYear, setFilterYear] = useState('');
  const [formErr, setFormErr] = useState('');

  const { data: years = [] } = useQuery<AcademicYear[]>({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [], isLoading } = useQuery<Term[]>({
    queryKey: ['terms', filterYear],
    queryFn: () => api.get('/terms', { params: filterYear ? { academic_year_id: filterYear } : {} }).then(r => r.data),
  });

  const openAdd  = () => { setEditing(null); setForm(empty); setFormErr(''); setOpen(true); };
  const openEdit = (t: Term) => { setEditing(t); setForm({ academic_year_id: String(t.academic_year_id), name: t.name, start_date: t.start_date, end_date: t.end_date }); setFormErr(''); setOpen(true); };

  const save = useMutation({
    mutationFn: (d: typeof empty) => editing ? api.put(`/terms/${editing.id}`, d) : api.post('/terms', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['terms'] }); setOpen(false); toastSuccess(editing ? 'Term updated.' : 'Term created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const setCurrent = useMutation({
    mutationFn: (id: number) => api.post(`/terms/${id}/set-current`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['terms'] }); toastSuccess('Term set as current.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/terms/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['terms'] }); toastSuccess('Term deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete this term.'),
  });

  const handleSetCurrent = async (t: Term) => {
    const ok = await confirmAction('Set as Current Term?', `Set "${t.name}" as the current term? Other terms in this year will be unset.`, 'Yes, set current');
    if (ok) setCurrent.mutate(t.id);
  };

  const handleDelete = async (t: Term) => {
    const ok = await confirmDelete(`term "${t.name}"`);
    if (ok) del.mutate(t.id);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Terms" subtitle="Divide academic years into Term 1, 2, 3" action={<Btn onClick={openAdd}>+ Add Term</Btn>} />

      <div style={{ marginBottom: 16 }}>
        <Select value={filterYear} onChange={e => setFilterYear(e.target.value)} style={{ width: 220 }}>
          <option value="">All Academic Years</option>
          {years.map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </Select>
      </div>

      <Card>
        <Table headers={['Academic Year', 'Term', 'Start Date', 'End Date', 'Status', 'Actions']}>
          {terms.map(t => (
            <tr key={t.id}>
              <Td>{t.academic_year_name}</Td>
              <Td><strong>{t.name}</strong></Td>
              <Td>{t.start_date}</Td>
              <Td>{t.end_date}</Td>
              <Td>{t.is_current ? <Badge variant="green">Current</Badge> : <Badge variant="gray">—</Badge>}</Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  {!t.is_current && <Btn size="sm" variant="outline" onClick={() => handleSetCurrent(t)}>Set Current</Btn>}
                  <Btn size="sm" variant="outline" onClick={() => openEdit(t)}>Edit</Btn>
                  <Btn size="sm" variant="danger"  onClick={() => handleDelete(t)}>Delete</Btn>
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Term' : 'Add Term'}>
        {formErr && <Alert type="error" message={formErr} />}
        {!editing && (
          <FormGroup label="Academic Year">
            <Select value={form.academic_year_id} onChange={e => setForm(f => ({ ...f, academic_year_id: e.target.value }))}>
              <option value="">Select academic year…</option>
              {years.map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
            </Select>
          </FormGroup>
        )}
        <FormGroup label="Term Name (e.g. Term 1)">
          <Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="Term 1" />
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
