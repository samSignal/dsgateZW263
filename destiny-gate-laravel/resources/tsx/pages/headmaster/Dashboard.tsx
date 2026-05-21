import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { StatCard, Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge, FormGroup, Input, Select, Textarea, Grid } from '../../components/UI';

export default function HeadmasterDashboard() {
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');
  const [ann, setAnn] = useState({ title: '', content: '', audience: 'all' });

  const { data, isLoading } = useQuery({ queryKey: ['headmaster-dashboard'], queryFn: () => api.get('/headmaster/dashboard').then(r => r.data) });

  const postAnn = useMutation({
    mutationFn: (d: any) => api.post('/headmaster/announcements', d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['headmaster-dashboard'] }); setAnn({ title: '', content: '', audience: 'all' }); setMsg('Announcement posted.'); },
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Headmaster Dashboard" subtitle="School overview and management" />
      {msg && <Alert type="success" message={msg} />}

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16, marginBottom: 24 }}>
        <StatCard label="Active Students"  value={data.total_students}  icon="🎓" color="green" />
        <StatCard label="Staff Members"    value={data.total_staff}     icon="🧑‍💼" color="blue" />
        <StatCard label="Fees Collected"   value={`$${Number(data.fees_collected).toLocaleString()}`} icon="💰" color="green" />
        <StatCard label="Behaviour Cases"  value={data.behaviour_cases} icon="⚠️" color="red" />
      </div>

      <Grid cols={2}>
        <Card>
          <CardHeader title="Recent Applications" />
          <Table headers={['Applicant', 'Class', 'Status']}>
            {data.recent_applications?.map((a: any) => (
              <tr key={a.id}>
                <Td><strong>{a.first_name} {a.last_name}</strong></Td>
                <Td>{a.intended_class}</Td>
                <Td>{statusBadge(a.status)}</Td>
              </tr>
            ))}
          </Table>
        </Card>

        <Card>
          <CardHeader title="Recent Behaviour Cases" />
          <Table headers={['Student', 'Issue', 'Severity']}>
            {data.recent_behaviour?.map((b: any) => (
              <tr key={b.id}>
                <Td><strong>{b.student?.first_name} {b.student?.last_name}</strong></Td>
                <Td>{b.issue_type}</Td>
                <Td>{statusBadge(b.severity)}</Td>
              </tr>
            ))}
          </Table>
        </Card>
      </Grid>

      {/* Post Announcement */}
      <Card>
        <CardHeader title="Post Announcement" />
        <CardBody>
          <Grid cols={2} style={{ marginBottom: 0 }}>
            <FormGroup label="Title"><Input value={ann.title} onChange={e => setAnn(a => ({...a, title: e.target.value}))} placeholder="Announcement title" /></FormGroup>
            <FormGroup label="Audience">
              <Select value={ann.audience} onChange={e => setAnn(a => ({...a, audience: e.target.value}))}>
                <option value="all">All</option>
                <option value="parents">Parents</option>
                <option value="students">Students</option>
                <option value="staff">Staff</option>
              </Select>
            </FormGroup>
          </Grid>
          <FormGroup label="Content"><Textarea value={ann.content} onChange={e => setAnn(a => ({...a, content: e.target.value}))} placeholder="Write your announcement…" /></FormGroup>
          <Btn loading={postAnn.isPending} onClick={() => postAnn.mutate(ann)}>Post Announcement</Btn>
        </CardBody>
      </Card>

      {/* Active Announcements */}
      <Card>
        <CardHeader title="Active Announcements" />
        <CardBody>
          {data.announcements?.map((a: any) => (
            <div key={a.id} style={{ padding: '12px 0', borderBottom: '1px solid #f3f4f6' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                <strong style={{ fontSize: 14 }}>{a.title}</strong>
                <span style={{ fontSize: 11, color: '#6b7280', background: '#f3f4f6', padding: '2px 8px', borderRadius: 20 }}>{a.audience}</span>
              </div>
              <p style={{ fontSize: 13, color: '#374151', margin: 0 }}>{a.content}</p>
            </div>
          ))}
        </CardBody>
      </Card>
    </div>
  );
}
