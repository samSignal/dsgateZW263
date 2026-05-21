import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Badge, Card, CardHeader, Input, PageHeader, Spinner, Table, Td } from '../../components/UI';

export default function StudentSubjectProfilePage() {
  const [search, setSearch] = useState('');
  const [student, setStudent] = useState<any>(null);
  const { data: students = [] } = useQuery<any[]>({ queryKey: ['profile-students', search], queryFn: () => api.get('/students', { params: { search } }).then(r => r.data.data), enabled: search.length >= 2 });
  const { data: subjects = [], isLoading } = useQuery<any[]>({ queryKey: ['profile-subjects', student?.id], queryFn: () => api.get(`/academic-foundation/students/${student.id}/subjects`).then(r => r.data), enabled: !!student });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Student Subject Profile" subtitle="Review the active subjects that will feed marks entry and reports" /><div className="mb-4 max-w-md"><Input placeholder="Search student" value={search} onChange={e => setSearch(e.target.value)} /></div><div className="mb-5 grid grid-cols-1 gap-2 md:grid-cols-3">{students.map(s => <button key={s.id} className="rounded border p-3 text-left" onClick={() => setStudent(s)}>{s.first_name} {s.last_name}<br /><span className="text-xs text-slate-500">{s.student_number}</span></button>)}</div><Card><CardHeader title={student ? `${student.first_name} ${student.last_name}` : 'No student selected'} /><Table headers={['Subject', 'Code', 'Type', 'Status']}>{subjects.map(s => <tr key={s.id}><Td>{s.subject_name}</Td><Td>{s.subject_code}</Td><Td><Badge variant={s.is_compulsory ? 'green' : 'blue'}>{s.is_compulsory ? 'Compulsory' : 'Optional'}</Badge></Td><Td>{s.enrollment_status}</Td></tr>)}</Table></Card></div>;
}
