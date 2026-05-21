import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import api from '../../lib/api';
import { toastSuccess, toastError } from '../../lib/toast';
import { Card, CardHeader, CardBody, Spinner, PageHeader, Btn, FormGroup, Input, Select, Grid, Alert } from '../../components/UI';
import { useAuth } from '../../hooks/useAuth';

export default function CreateAssessmentPage() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [form, setForm] = useState({ academic_year_id: '', term_id: '', stream_id: '', subject_id: '', assessment_type_id: '', title: '', total_marks: '100', assessment_date: new Date().toISOString().split('T')[0] });
  const [err, setErr] = useState('');

  const { data: years = [] }   = useQuery({ queryKey: ['academic-years'],  queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] }   = useQuery({ queryKey: ['terms'],            queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: types = [] }   = useQuery({ queryKey: ['assessment-types'], queryFn: () => api.get('/assessment-types').then(r => r.data) });

  // Teachers see only their allocations; admin/headmaster see all
  const isTeacher = user?.role === 'teacher';
  const { data: allocations = [] } = useQuery({
    queryKey: ['my-allocations'],
    queryFn: () => api.get('/assessments/my-allocations').then(r => r.data),
    enabled: isTeacher,
  });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'],  queryFn: () => api.get('/streams').then(r => r.data), enabled: !isTeacher });
  const { data: subjects = [] }= useQuery({ queryKey: ['subjects'], queryFn: () => api.get('/subjects').then(r => r.data), enabled: !isTeacher });

  const filteredTerms = (terms as any[]).filter(t => !form.academic_year_id || String(t.academic_year_id) === form.academic_year_id);

  // For teachers: derive streams and subjects from allocations
  const teacherStreams  = [...new Map((allocations as any[]).map(a => [a.stream_id, { id: a.stream_id, name: `${a.form_name} — ${a.stream_name}` }])).values()];
  const teacherSubjects = (allocations as any[]).filter(a => !form.stream_id || String(a.stream_id) === form.stream_id)
    .map(a => ({ id: a.subject_id, name: `${a.subject_name} (${a.subject_code})` }));

  const availableStreams  = isTeacher ? teacherStreams  : (streams as any[]).map(s => ({ id: s.id, name: `${s.form_name} — ${s.name}` }));
  const availableSubjects = isTeacher ? teacherSubjects : (subjects as any[]).map(s => ({ id: s.id, name: `${s.name} (${s.code})` }));

  const create = useMutation({
    mutationFn: (d: typeof form) => api.post('/assessments', d),
    onSuccess: (res) => {
      toastSuccess(`Assessment created: ${res.data.assessment.assessment_number}`);
      navigate(`/app/assessments/${res.data.assessment.id}/marks`);
    },
    onError: (e: any) => setErr(e.response?.data?.message ?? 'Failed to create assessment.'),
  });

  return (
    <div>
      <PageHeader title="Create Assessment" subtitle="Set up a new assessment for a stream and subject" />
      {err && <Alert type="error" message={err} />}

      <div style={{ maxWidth: 680 }}>
        <Card>
          <CardHeader title="Assessment Details" />
          <CardBody>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Academic Year">
                <Select value={form.academic_year_id} onChange={e => setForm(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))}>
                  <option value="">Select year…</option>
                  {(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}
                </Select>
              </FormGroup>
              <FormGroup label="Term">
                <Select value={form.term_id} onChange={e => setForm(f => ({ ...f, term_id: e.target.value }))}>
                  <option value="">Select term…</option>
                  {filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}
                </Select>
              </FormGroup>
            </Grid>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Stream">
                <Select value={form.stream_id} onChange={e => setForm(f => ({ ...f, stream_id: e.target.value, subject_id: '' }))}>
                  <option value="">Select stream…</option>
                  {availableStreams.map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </Select>
              </FormGroup>
              <FormGroup label="Subject">
                <Select value={form.subject_id} onChange={e => setForm(f => ({ ...f, subject_id: e.target.value }))}>
                  <option value="">Select subject…</option>
                  {availableSubjects.map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </Select>
              </FormGroup>
            </Grid>
            <FormGroup label="Assessment Type">
              <Select value={form.assessment_type_id} onChange={e => setForm(f => ({ ...f, assessment_type_id: e.target.value }))}>
                <option value="">Select type…</option>
                {(types as any[]).filter((t: any) => t.is_active).map((t: any) => <option key={t.id} value={t.id}>{t.name} ({t.weight_percentage}%)</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Assessment Title">
              <Input value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))} placeholder="e.g. Form 1A Mathematics Weekly Test 1" />
            </FormGroup>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Total Marks">
                <Input type="number" value={form.total_marks} onChange={e => setForm(f => ({ ...f, total_marks: e.target.value }))} min="1" />
              </FormGroup>
              <FormGroup label="Assessment Date">
                <Input type="date" value={form.assessment_date} onChange={e => setForm(f => ({ ...f, assessment_date: e.target.value }))} />
              </FormGroup>
            </Grid>
            <div style={{ display: 'flex', gap: 10, marginTop: 8 }}>
              <Btn loading={create.isPending} onClick={() => create.mutate(form)}>Create & Enter Marks</Btn>
              <Btn variant="outline" onClick={() => navigate('/app/assessments')}>Cancel</Btn>
            </div>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
