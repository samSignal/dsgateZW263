import React, { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Btn, Card, CardBody, CardHeader, FormGroup, Grid, Input, PageHeader, Select, StatCard, Table, Td } from '../../components/UI';

export default function ReportGenerationPage() {
  const [form, setForm] = useState({ student_id: '', academic_year_id: '', term_id: '', form_id: '', stream_id: '' });
  const { data: studentsResponse } = useQuery({ queryKey: ['students'], queryFn: () => api.get('/students').then(r => r.data) });
  const students = studentsResponse?.data || [];
  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'], queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: forms = [] } = useQuery({ queryKey: ['forms'], queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'], queryFn: () => api.get('/streams').then(r => r.data) });
  const topEnabled = !!form.academic_year_id && !!form.term_id;
  const { data: topRes } = useQuery({
    queryKey: ['stream-native-top-performers', form.academic_year_id, form.term_id],
    queryFn: () =>
      api
        .get('/stream-native/results/top-performers', { params: { academic_year_id: Number(form.academic_year_id), term_id: Number(form.term_id), limit: 10 } })
        .then(r => r.data),
    enabled: topEnabled,
  });
  const top = (topRes?.data ?? []) as any[];

  const generateStudent = useMutation({
    mutationFn: () => api.post('/reports/student/generate', form),
    onSuccess: () => toastSuccess('Student report generated.'),
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not generate report.'),
  });
  const generateStream = useMutation({
    mutationFn: () => api.post('/reports/stream/generate', form),
    onSuccess: (r) => toastSuccess(r.data.message),
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not generate stream reports.'),
  });

  const filteredTerms = (terms as any[]).filter(t => !form.academic_year_id || String(t.academic_year_id) === form.academic_year_id);
  const filteredStreams = (streams as any[]).filter(s => !form.form_id || String(s.form_id) === form.form_id);

  return <div>
    <PageHeader title="Report Card Generation" subtitle="Generate professional term reports from approved assessments, attendance, behaviour, and fees" action={<Link to="/app/reports/batch"><Btn variant="outline">Batch Print</Btn></Link>} />
    <Grid cols={4}>
      <StatCard label="Top Average" value={(top as any[])[0]?.term_average ? `${(top as any[])[0].term_average}%` : '-'} color="green" />
      <StatCard label="Top Performer" value={(top as any[])[0]?.student_name ?? '-'} color="amber" />
      <StatCard label="Leaderboard" value={(top as any[]).length} color="blue" />
      <StatCard label="At Risk (Top)" value={(top as any[]).filter((r: any) => Number(r.term_average) < 50).length} color="red" />
    </Grid>
    <Card style={{ marginTop: 16 }}>
      <CardHeader title="Generate Reports" />
      <CardBody>
        <Grid cols={3}>
          <FormGroup label="Academic Year"><Select value={form.academic_year_id} onChange={e => setForm(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))}><option value="">Select year</option>{(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}</Select></FormGroup>
          <FormGroup label="Term"><Select value={form.term_id} onChange={e => setForm(f => ({ ...f, term_id: e.target.value }))}><option value="">Select term</option>{filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}</Select></FormGroup>
          <FormGroup label="Student"><Select value={form.student_id} onChange={e => setForm(f => ({ ...f, student_id: e.target.value }))}><option value="">Select student</option>{(students as any[]).map(s => <option key={s.id} value={s.id}>{s.first_name} {s.last_name} - {s.student_number ?? s.admission_number}</option>)}</Select></FormGroup>
          <FormGroup label="Form"><Select value={form.form_id} onChange={e => setForm(f => ({ ...f, form_id: e.target.value, stream_id: '' }))}><option value="">Select form</option>{(forms as any[]).map(fm => <option key={fm.id} value={fm.id}>{fm.name}</option>)}</Select></FormGroup>
          <FormGroup label="Class"><Select value={form.stream_id} onChange={e => setForm(f => ({ ...f, stream_id: e.target.value }))}><option value="">Select class</option>{filteredStreams.map((s: any) => <option key={s.id} value={s.id}>{s.form_name ?? ''} {s.name}</option>)}</Select></FormGroup>
          <FormGroup label="Report Rules"><Input value="Promote >= 50, probation 40-49, repeat < 40" disabled /></FormGroup>
        </Grid>
        <div className="flex gap-3">
          <Btn loading={generateStudent.isPending} onClick={() => generateStudent.mutate()}>Generate Student Report</Btn>
          <Btn variant="outline" loading={generateStream.isPending} onClick={() => generateStream.mutate()}>Generate Whole Class</Btn>
        </div>
      </CardBody>
    </Card>
    <Card style={{ marginTop: 16 }}>
      <CardHeader title="Top Performers" />
      <Table headers={['Student', 'Class', 'Average', 'GPA', 'Grade', 'Preview']}>
        {(top as any[]).map(r => (
          <tr key={r.student_id}>
            <Td>{r.student_name}<div className="text-xs text-slate-500">{r.student_number ?? r.admission_number}</div></Td>
            <Td>{r.form_name ?? r.resolved_form_name} {r.stream_name ?? r.resolved_stream_name}</Td>
            <Td>{r.term_average ?? '-'}%</Td>
            <Td>{r.gpa ?? '-'}</Td>
            <Td>{r.overall_grade ?? '-'}</Td>
            <Td><Link to={`/app/reports/sn-${r.student_id}-${form.academic_year_id}-${form.term_id}`}><Btn size="sm" variant="outline">Open</Btn></Link></Td>
          </tr>
        ))}
        {topEnabled && top.length === 0 && <tr><Td colSpan={6} style={{ textAlign: 'center', color: '#64748b' }}>No computed results yet for this term.</Td></tr>}
      </Table>
    </Card>
  </div>;
}
