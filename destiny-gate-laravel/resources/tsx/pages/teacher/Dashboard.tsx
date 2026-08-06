import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { StatCard, Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Alert, Btn, FormGroup, Input, Select, Grid } from '../../components/UI';

export default function TeacherDashboard() {
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');
  const { data, isLoading } = useQuery({ queryKey: ['teacher-dashboard'], queryFn: () => api.get('/teacher/dashboard').then(r => r.data) });
  const { data: classes } = useQuery({ queryKey: ['teacher-classes'], queryFn: () => api.get('/teacher/classes').then(r => r.data) });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader
        title="Teacher Dashboard"
        subtitle={`Welcome, ${data?.staff?.first_name} ${data?.staff?.last_name}`}
        action={
          <div className="flex gap-2">
            <Link to="/app/reports/rankings/stream"><Btn variant="outline">Rankings</Btn></Link>
            <Link to="/app/reports"><Btn variant="outline">Results</Btn></Link>
          </div>
        }
      />
      {msg && <Alert type="success" message={msg} />}

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16, marginBottom: 24 }}>
        <StatCard label="My Classes"     value={data.my_classes}     icon="🏫" color="blue" />
        <StatCard label="Total Students" value={data.total_students} icon="🎓" color="green" />
        <StatCard label="Marks Recorded" value={data.marks_recorded} icon="📝" color="green" />
        <StatCard label="Comments Added" value={data.comments_added} icon="💬" color="amber" />
      </div>

      <Card>
        <CardHeader title="My Classes" />
        <Table headers={['Class', 'Subject', 'Academic Year', 'Students', 'Actions']}>
          {classes?.map((a: any) => (
            <tr key={a.id}>
              <Td><strong>{a.school_class?.class_name} {a.school_class?.stream}</strong></Td>
              <Td>{a.subject?.subject_name}</Td>
              <Td>{a.academic_year}</Td>
              <Td>{a.school_class?.students?.length ?? 0}</Td>
              <Td>
                <Link to={`/app/teacher/classes/${a.class_id}`} style={{ fontSize: 12, color: '#1a6b3c', textDecoration: 'none', fontWeight: 500 }}>View Students →</Link>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}
