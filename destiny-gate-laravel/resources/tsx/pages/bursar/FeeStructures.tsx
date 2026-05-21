import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Alert, Btn, FormGroup, Input, Select, Textarea, Grid } from '../../components/UI';

export default function FeeStructures() {
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');
  const [form, setForm] = useState({ academic_year: new Date().getFullYear() + '/' + (new Date().getFullYear()+1), class_name: '', term: 'term1', amount: '', due_date: '', description: '' });

  const { data, isLoading } = useQuery({ queryKey: ['fee-structures'], queryFn: () => api.get('/bursar/fee-structures').then(r => r.data) });

  const create = useMutation({
    mutationFn: (d: any) => api.post('/bursar/fee-structures', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['fee-structures'] }); setMsg('Fee structure created.'); },
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Fee Structures" subtitle="Configure school fees by class and term" />
      {msg && <Alert type="success" message={msg} />}
      <Grid cols={2}>
        <Card>
          <CardHeader title="Add Fee Structure" />
          <CardBody>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Academic Year"><Input value={form.academic_year} onChange={e => setForm(f => ({...f, academic_year: e.target.value}))} /></FormGroup>
              <FormGroup label="Class Name"><Input value={form.class_name} onChange={e => setForm(f => ({...f, class_name: e.target.value}))} placeholder="e.g. Form 1" /></FormGroup>
            </Grid>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Term">
                <Select value={form.term} onChange={e => setForm(f => ({...f, term: e.target.value}))}>
                  <option value="term1">Term 1</option>
                  <option value="term2">Term 2</option>
                  <option value="term3">Term 3</option>
                </Select>
              </FormGroup>
              <FormGroup label="Amount ($)"><Input type="number" step="0.01" value={form.amount} onChange={e => setForm(f => ({...f, amount: e.target.value}))} /></FormGroup>
            </Grid>
            <FormGroup label="Due Date"><Input type="date" value={form.due_date} onChange={e => setForm(f => ({...f, due_date: e.target.value}))} /></FormGroup>
            <FormGroup label="Description"><Textarea value={form.description} onChange={e => setForm(f => ({...f, description: e.target.value}))} /></FormGroup>
            <Btn loading={create.isPending} onClick={() => create.mutate(form)}>Create Fee Structure</Btn>
          </CardBody>
        </Card>
        <Card>
          <CardHeader title="Existing Structures" />
          <Table headers={['Class', 'Term', 'Amount', 'Due Date']}>
            {data?.data?.map((s: any) => (
              <tr key={s.id}>
                <Td>{s.class_name}</Td>
                <Td style={{ textTransform: 'uppercase' }}>{s.term}</Td>
                <Td style={{ fontWeight: 600 }}>${Number(s.amount).toLocaleString()}</Td>
                <Td style={{ color: '#6b7280' }}>{new Date(s.due_date).toLocaleDateString()}</Td>
              </tr>
            ))}
          </Table>
        </Card>
      </Grid>
    </div>
  );
}
