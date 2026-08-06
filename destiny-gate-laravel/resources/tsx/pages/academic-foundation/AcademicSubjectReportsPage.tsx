import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, CardHeader, PageHeader, Spinner, Table, Td } from '../../components/UI';

export default function AcademicSubjectReportsPage() {
  const { data: perSubject = [], isLoading } = useQuery<any[]>({ queryKey: ['r-students-per-subject'], queryFn: () => api.get('/academic-foundation/reports/students-per-subject').then(r => r.data) });
  const { data: perStudent = [] } = useQuery<any[]>({ queryKey: ['r-subjects-per-student'], queryFn: () => api.get('/academic-foundation/reports/subjects-per-student').then(r => r.data) });
  const { data: allocations = [] } = useQuery<any[]>({ queryKey: ['r-teacher-allocations'], queryFn: () => api.get('/academic-foundation/reports/teacher-allocations').then(r => r.data) });
  const { data: unallocated = [] } = useQuery<any[]>({ queryKey: ['r-unallocated'], queryFn: () => api.get('/academic-foundation/reports/unallocated-subjects').then(r => r.data) });
  if (isLoading) return <Spinner />;
  return <div><PageHeader title="Academic Subject Reports" subtitle="Subject enrolment counts, allocations, class subjects, and gaps" action={<button className="rounded border px-3 py-2 text-sm font-semibold" onClick={() => window.print()}>Print</button>} /><div className="grid grid-cols-1 gap-5 xl:grid-cols-2"><Card><CardHeader title="Students per Subject" /><Table headers={['Subject', 'Code', 'Students']}>{perSubject.map(r => <tr key={r.subject_id}><Td>{r.subject_name}</Td><Td>{r.code}</Td><Td>{r.student_count}</Td></tr>)}</Table></Card><Card><CardHeader title="Subjects per Student" /><Table headers={['Student', 'Count', 'Subjects']}>{perStudent.map(r => <tr key={r.student_id}><Td>{r.student_name}<br /><span className="text-xs text-slate-500">{r.student_number}</span></Td><Td>{r.subject_count}</Td><Td>{r.subjects}</Td></tr>)}</Table></Card><Card><CardHeader title="Teacher Allocations" /><Table headers={['Teacher', 'Subject', 'Class']}>{allocations.map((r, i) => <tr key={i}><Td>{r.teacher_name}</Td><Td>{r.subject_name}</Td><Td>{r.form_name} {r.stream_name}</Td></tr>)}</Table></Card><Card><CardHeader title="Unallocated Subjects" /><Table headers={['Class', 'Subject']}>{unallocated.map(r => <tr key={r.id}><Td>{r.form_name} {r.stream_name}</Td><Td>{r.subject_name}</Td></tr>)}</Table></Card></div></div>;
}
