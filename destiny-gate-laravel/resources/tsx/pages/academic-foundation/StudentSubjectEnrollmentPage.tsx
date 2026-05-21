import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Badge, Btn, Card, CardBody, CardHeader, FormGroup, Input, PageHeader, Select, Spinner, Table, Td } from '../../components/UI';

export default function StudentSubjectEnrollmentPage() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [student, setStudent] = useState<any>(null);
  const [yearId, setYearId] = useState('');
  const [termId, setTermId] = useState('');
  const [selected, setSelected] = useState<number[]>([]);
  const { data: meta = {} } = useQuery<any>({ queryKey: ['af-meta'], queryFn: () => api.get('/academic-foundation/meta').then(r => r.data) });
  const { data: students = [] } = useQuery<any[]>({ queryKey: ['af-students', search], queryFn: () => api.get('/students', { params: { search } }).then(r => r.data.data), enabled: search.length >= 2 });
  const { data: streamSubjects = [], isLoading } = useQuery<any[]>({ queryKey: ['student-stream-subjects', student?.class_id, yearId, termId], queryFn: async () => {
    if (!student) return [];
    const form = (meta.forms ?? []).find((f: any) => f.name === student.class_name);
    const stream = (meta.streams ?? []).find((s: any) => s.form_id === form?.id && s.name === student.stream);
    if (!stream) return [];
    return api.get(`/academic-foundation/streams/${stream.id}/subjects`, { params: { academic_year_id: yearId, term_id: termId } }).then(r => r.data);
  }, enabled: !!student && !!yearId && !!termId && !!meta.forms });
  const { data: enrolled = [] } = useQuery<any[]>({ queryKey: ['student-subjects', student?.id], queryFn: () => api.get(`/academic-foundation/students/${student.id}/subjects`).then(r => r.data), enabled: !!student });
  const save = useMutation({ mutationFn: () => api.post('/academic-foundation/student-subjects', { student_id: student.id, academic_year_id: yearId, term_id: termId, stream_subject_ids: selected }), onSuccess: () => { qc.invalidateQueries({ queryKey: ['student-subjects', student?.id] }); toastSuccess('Subjects enrolled.'); }, onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not enrol subjects.') });
  const compulsoryIds = streamSubjects.filter(s => s.is_compulsory).map(s => s.id);
  const toggle = (id: number) => setSelected(x => x.includes(id) ? x.filter(v => v !== id) : [...x, id]);
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Student Subject Enrolment" subtitle="Auto-select compulsory subjects and choose optional subjects from the student stream" /><div className="grid grid-cols-1 gap-5 xl:grid-cols-[360px_1fr]"><Card><CardHeader title="Student Search" /><CardBody><Input placeholder="Search student" value={search} onChange={e => setSearch(e.target.value)} /><div className="mt-3 space-y-2">{students.map(s => <button key={s.id} className="w-full rounded border p-2 text-left" onClick={() => { setStudent(s); setSelected([]); }}>{s.first_name} {s.last_name}<br /><span className="text-xs text-slate-500">{s.student_number} - {s.class_name} {s.stream}</span></button>)}</div><FormGroup label="Year"><Select value={yearId} onChange={e => setYearId(e.target.value)}>{(meta.academic_years ?? []).map((y: any) => <option key={y.id} value={y.id}>{y.name}</option>)}</Select></FormGroup><FormGroup label="Term"><Select value={termId} onChange={e => setTermId(e.target.value)}>{(meta.terms ?? []).map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}</Select></FormGroup></CardBody></Card><Card><CardHeader title={student ? `${student.first_name} ${student.last_name} Subjects` : 'Select a student'} /><CardBody><div className="mb-4 text-sm text-slate-600">Selected: {new Set([...compulsoryIds, ...selected]).size} subjects</div><div className="grid grid-cols-1 gap-2 md:grid-cols-2">{streamSubjects.map(s => <label key={s.id} className="flex items-center justify-between rounded-lg border p-3"><span><strong>{s.subject_name}</strong><br /><span className="text-xs text-slate-500">{s.subject_code}</span></span><span className="flex items-center gap-2">{s.is_compulsory && <Badge variant="green">Compulsory</Badge>}<input type="checkbox" disabled={s.is_compulsory} checked={s.is_compulsory || selected.includes(s.id)} onChange={() => toggle(s.id)} /></span></label>)}</div><Btn className="mt-4" loading={save.isPending} disabled={!student || selected.length === 0} onClick={() => save.mutate()}>Save Optional Subjects</Btn></CardBody><Table headers={['Active Subject', 'Type', 'Status']}>{enrolled.map(e => <tr key={e.id}><Td>{e.subject_name}</Td><Td>{e.is_compulsory ? 'Compulsory' : 'Optional'}</Td><Td>{e.enrollment_status}</Td></tr>)}</Table></Card></div></div>;
}
