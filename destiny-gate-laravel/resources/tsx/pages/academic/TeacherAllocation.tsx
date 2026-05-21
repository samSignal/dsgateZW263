import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Btn, Modal, FormGroup, Select, Grid, Alert } from '../../components/UI';

interface AcademicYear { id: number; name: string }
interface Term         { id: number; name: string; academic_year_id: number }
interface Stream       { id: number; name: string; form_id: number; form_name: string }
interface Subject      { id: number; name: string; code: string }
interface Teacher      { id: number; name: string; staff_id: string }
interface Allocation   { id: number; teacher_name: string; subject_name: string; subject_code: string; stream_name: string; form_name: string; academic_year_name: string; term_name: string }

const empty = { teacher_id: '', subject_id: '', stream_id: '', academic_year_id: '', term_id: '' };

export default function TeacherAllocation() {
  const qc = useQueryClient();
  const [open, setOpen]   = useState(false);
  const [form, setForm]   = useState(empty);
  const [filterYear, setFilterYear]     = useState('');
  const [filterTerm, setFilterTerm]     = useState('');
  const [filterStream, setFilterStream] = useState('');
  const [formErr, setFormErr] = useState('');

  const { data: years    = [] } = useQuery<AcademicYear[]>({ queryKey: ['academic-years'],  queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: allTerms = [] } = useQuery<Term[]>({         queryKey: ['terms'],            queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: streams  = [] } = useQuery<Stream[]>({       queryKey: ['streams'],          queryFn: () => api.get('/streams').then(r => r.data) });
  const { data: subjects = [] } = useQuery<Subject[]>({      queryKey: ['subjects'],         queryFn: () => api.get('/subjects').then(r => r.data) });
  const { data: teachers = [] } = useQuery<Teacher[]>({      queryKey: ['alloc-teachers'],   queryFn: () => api.get('/teacher-allocations/teachers').then(r => r.data) });

  // Filter terms by selected academic year in form
  const filteredTerms = allTerms.filter(t => !form.academic_year_id || String(t.academic_year_id) === form.academic_year_id);

  const { data: allocations = [], isLoading } = useQuery<Allocation[]>({
    queryKey: ['teacher-allocations', filterYear, filterTerm, filterStream],
    queryFn: () => api.get('/teacher-allocations', {
      params: { academic_year_id: filterYear || undefined, term_id: filterTerm || undefined, stream_id: filterStream || undefined },
    }).then(r => r.data),
  });

  const save = useMutation({
    mutationFn: (d: typeof empty) => api.post('/teacher-allocations', d),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['teacher-allocations'] });
      setOpen(false); setForm(empty);
      toastSuccess('Teacher allocation saved successfully.');
    },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/teacher-allocations/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['teacher-allocations'] }); toastSuccess('Allocation removed.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to remove allocation.'),
  });

  const handleDelete = async (a: Allocation) => {
    const ok = await confirmDelete(`allocation for ${a.teacher_name} — ${a.subject_name}`);
    if (ok) del.mutate(a.id);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader
        title="Teacher Allocation"
        subtitle="Assign teachers to subjects and streams per term"
        action={<Btn onClick={() => { setFormErr(''); setForm(empty); setOpen(true); }}>+ Add Allocation</Btn>}
      />

      {/* Filters */}
      <div style={{ display: 'flex', gap: 10, marginBottom: 16, flexWrap: 'wrap' }}>
        <Select value={filterYear} onChange={e => setFilterYear(e.target.value)} style={{ width: 160 }}>
          <option value="">All Years</option>
          {years.map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
        </Select>
        <Select value={filterTerm} onChange={e => setFilterTerm(e.target.value)} style={{ width: 160 }}>
          <option value="">All Terms</option>
          {allTerms.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={filterStream} onChange={e => setFilterStream(e.target.value)} style={{ width: 200 }}>
          <option value="">All Streams</option>
          {streams.map(s => <option key={s.id} value={s.id}>{s.form_name} — {s.name}</option>)}
        </Select>
      </div>

      <Card>
        <Table headers={['Teacher', 'Subject', 'Stream', 'Academic Year', 'Term', 'Actions']}>
          {allocations.map(a => (
            <tr key={a.id}>
              <Td><strong>{a.teacher_name}</strong></Td>
              <Td>{a.subject_name} <span style={{ fontSize: 11, color: '#9ca3af' }}>({a.subject_code})</span></Td>
              <Td>{a.form_name} — {a.stream_name}</Td>
              <Td>{a.academic_year_name}</Td>
              <Td>{a.term_name}</Td>
              <Td><Btn size="sm" variant="danger" onClick={() => handleDelete(a)}>Remove</Btn></Td>
            </tr>
          ))}
          {allocations.length === 0 && (
            <tr><Td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af', padding: 32 }}>No allocations found. Add one above.</Td></tr>
          )}
        </Table>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title="Add Teacher Allocation">
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Teacher">
          <Select value={form.teacher_id} onChange={e => setForm(f => ({ ...f, teacher_id: e.target.value }))}>
            <option value="">Select teacher…</option>
            {teachers.map(t => <option key={t.id} value={t.id}>{t.name} ({t.staff_id})</option>)}
          </Select>
        </FormGroup>
        <FormGroup label="Subject">
          <Select value={form.subject_id} onChange={e => setForm(f => ({ ...f, subject_id: e.target.value }))}>
            <option value="">Select subject…</option>
            {subjects.map(s => <option key={s.id} value={s.id}>{s.name} ({s.code})</option>)}
          </Select>
        </FormGroup>
        <FormGroup label="Stream">
          <Select value={form.stream_id} onChange={e => setForm(f => ({ ...f, stream_id: e.target.value }))}>
            <option value="">Select stream…</option>
            {streams.map(s => <option key={s.id} value={s.id}>{s.form_name} — {s.name}</option>)}
          </Select>
        </FormGroup>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Academic Year">
            <Select value={form.academic_year_id} onChange={e => setForm(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))}>
              <option value="">Select year…</option>
              {years.map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
            </Select>
          </FormGroup>
          <FormGroup label="Term">
            <Select value={form.term_id} onChange={e => setForm(f => ({ ...f, term_id: e.target.value }))}>
              <option value="">Select term…</option>
              {filteredTerms.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
            </Select>
          </FormGroup>
        </Grid>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>Save Allocation</Btn>
        </div>
      </Modal>
    </div>
  );
}
