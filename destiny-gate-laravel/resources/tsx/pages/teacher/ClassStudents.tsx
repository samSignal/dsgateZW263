import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Alert, Btn, FormGroup, Input, Select, Grid } from '../../components/UI';

export default function ClassStudents() {
  const { classId } = useParams();
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');
  const [marksForm, setMarksForm] = useState({ student_id: '', subject_id: '', academic_year: new Date().getFullYear() + '/' + (new Date().getFullYear()+1), term: 'term1', assessment_type: 'exam', marks: '', total_marks: '100' });
  const [attForm, setAttForm] = useState({ student_id: '', date: new Date().toISOString().split('T')[0], status: 'present', remarks: '' });

  const { data, isLoading } = useQuery({ queryKey: ['class-students', classId], queryFn: () => api.get(`/teacher/classes/${classId}/students`).then(r => r.data) });

  const recordMarks = useMutation({
    mutationFn: (d: any) => api.post('/teacher/marks', { ...d, class_id: classId }),
    onSuccess: () => { setMsg('Marks recorded.'); setMarksForm(f => ({ ...f, student_id: '', marks: '' })); },
  });
  const markAtt = useMutation({
    mutationFn: (d: any) => api.post('/teacher/attendance', { ...d, class_id: classId }),
    onSuccess: () => { setMsg('Attendance marked.'); },
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title={`${data?.class?.class_name} ${data?.class?.stream ?? ''} — Students`} subtitle="Record marks and attendance" />
      {msg && <Alert type="success" message={msg} />}

      <Card style={{ marginBottom: 20 }}>
        <CardHeader title={`Students (${data?.students?.length ?? 0})`} />
        <Table headers={['Admission #', 'Name', 'Gender']}>
          {data?.students?.map((s: any) => (
            <tr key={s.id}>
              <Td><code style={{ background: '#f3f4f6', padding: '2px 7px', borderRadius: 5, fontSize: 12 }}>{s.admission_number}</code></Td>
              <Td><strong>{s.first_name} {s.last_name}</strong></Td>
              <Td style={{ textTransform: 'capitalize' }}>{s.gender ?? '—'}</Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Grid cols={2}>
        <Card>
          <CardHeader title="Record Marks" />
          <CardBody>
            <FormGroup label="Student">
              <Select value={marksForm.student_id} onChange={e => setMarksForm(f => ({...f, student_id: e.target.value}))}>
                <option value="">Select student…</option>
                {data?.students?.map((s: any) => <option key={s.id} value={s.id}>{s.first_name} {s.last_name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Subject">
              <Select value={marksForm.subject_id} onChange={e => setMarksForm(f => ({...f, subject_id: e.target.value}))}>
                <option value="">Select subject…</option>
                {data?.subjects?.map((s: any) => <option key={s.id} value={s.id}>{s.subject_name}</option>)}
              </Select>
            </FormGroup>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Term">
                <Select value={marksForm.term} onChange={e => setMarksForm(f => ({...f, term: e.target.value}))}>
                  <option value="term1">Term 1</option><option value="term2">Term 2</option><option value="term3">Term 3</option>
                </Select>
              </FormGroup>
              <FormGroup label="Assessment Type">
                <Select value={marksForm.assessment_type} onChange={e => setMarksForm(f => ({...f, assessment_type: e.target.value}))}>
                  <option value="weekly_test">Weekly Test</option><option value="monthly_test">Monthly Test</option>
                  <option value="assignment">Assignment</option><option value="exam">Exam</option><option value="project">Project</option>
                </Select>
              </FormGroup>
            </Grid>
            <Grid cols={2} style={{ marginBottom: 0 }}>
              <FormGroup label="Marks"><Input type="number" value={marksForm.marks} onChange={e => setMarksForm(f => ({...f, marks: e.target.value}))} /></FormGroup>
              <FormGroup label="Out of"><Input type="number" value={marksForm.total_marks} onChange={e => setMarksForm(f => ({...f, total_marks: e.target.value}))} /></FormGroup>
            </Grid>
            <Btn loading={recordMarks.isPending} onClick={() => recordMarks.mutate(marksForm)}>Record Marks</Btn>
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Mark Attendance" />
          <CardBody>
            <FormGroup label="Student">
              <Select value={attForm.student_id} onChange={e => setAttForm(f => ({...f, student_id: e.target.value}))}>
                <option value="">Select student…</option>
                {data?.students?.map((s: any) => <option key={s.id} value={s.id}>{s.first_name} {s.last_name}</option>)}
              </Select>
            </FormGroup>
            <FormGroup label="Date"><Input type="date" value={attForm.date} onChange={e => setAttForm(f => ({...f, date: e.target.value}))} /></FormGroup>
            <FormGroup label="Status">
              <Select value={attForm.status} onChange={e => setAttForm(f => ({...f, status: e.target.value}))}>
                <option value="present">Present</option><option value="absent">Absent</option>
                <option value="late">Late</option><option value="excused">Excused</option>
                <option value="sick">Sick</option><option value="early_departure">Early Departure</option>
              </Select>
            </FormGroup>
            <FormGroup label="Remarks"><Input value={attForm.remarks} onChange={e => setAttForm(f => ({...f, remarks: e.target.value}))} placeholder="Optional…" /></FormGroup>
            <Btn loading={markAtt.isPending} onClick={() => markAtt.mutate(attForm)}>Mark Attendance</Btn>
          </CardBody>
        </Card>
      </Grid>
    </div>
  );
}
