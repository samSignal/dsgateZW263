import React, { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../lib/api';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge, Badge, Grid, FormGroup, Input, Select, Modal } from '../components/UI';

export default function StudentDetail() {
  const { id } = useParams();
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');
  const [guardianOpen, setGuardianOpen] = useState(false);
  const [gForm, setGForm] = useState({ first_name:'', last_name:'', phone:'', email:'', relationship:'', is_primary_contact: false });

  const { data: student, isLoading } = useQuery({ queryKey: ['student', id], queryFn: () => api.get(`/students/${id}`).then(r => r.data) });

  const addGuardian = useMutation({
    mutationFn: (d: any) => api.post(`/students/${id}/guardians`, d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['student', id] }); setGuardianOpen(false); setMsg('Guardian added.'); },
  });

  if (isLoading) return <Spinner />;
  if (!student) return <div>Student not found.</div>;

  return (
    <div>
      <PageHeader
        title={`${student.first_name} ${student.last_name}`}
        subtitle={`${student.admission_number} · ${student.school_class?.class_name ?? 'Unassigned'}`}
        action={<div style={{ display: 'flex', gap: 8 }}>{statusBadge(student.status)}</div>}
      />
      {msg && <Alert type="success" message={msg} />}

      <Grid cols={2}>
        {/* Info */}
        <Card>
          <CardHeader title="Personal Information" />
          <CardBody>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              {[
                ['Email', student.email ?? '—'],
                ['Date of Birth', student.date_of_birth ? new Date(student.date_of_birth).toLocaleDateString() : '—'],
                ['Gender', student.gender ? student.gender.charAt(0).toUpperCase() + student.gender.slice(1) : '—'],
                ['Blood Type', student.blood_type ?? '—'],
                ['Admission Date', new Date(student.admission_date).toLocaleDateString()],
                ['Allergies', student.allergies ?? '—'],
              ].map(([k, v]) => (
                <tr key={k}>
                  <td style={{ padding: '6px 0', color: '#6b7280', fontSize: 13, width: '40%' }}>{k}</td>
                  <td style={{ padding: '6px 0', fontSize: 13, color: '#111827' }}>{v}</td>
                </tr>
              ))}
            </table>
          </CardBody>
        </Card>

        {/* Guardians */}
        <Card>
          <CardHeader title="Guardians" action={<Btn size="sm" onClick={() => setGuardianOpen(true)}>+ Add</Btn>} />
          <CardBody>
            {student.guardians?.length === 0 && <p style={{ color: '#9ca3af', fontSize: 13 }}>No guardians added yet.</p>}
            {student.guardians?.map((g: any) => (
              <div key={g.id} style={{ padding: '10px 0', borderBottom: '1px solid #f3f4f6' }}>
                <div style={{ fontWeight: 600, fontSize: 13 }}>{g.first_name} {g.last_name}
                  {g.is_primary_contact && <Badge variant="green" style={{ marginLeft: 8 }}>Primary</Badge>}
                </div>
                <div style={{ fontSize: 12, color: '#6b7280', marginTop: 2 }}>{g.relationship} · {g.phone}</div>
              </div>
            ))}
          </CardBody>
        </Card>
      </Grid>

      {/* Academic Progress */}
      <Card style={{ marginBottom: 20 }}>
        <CardHeader title="Academic Progress" />
        <Table headers={['Subject', 'Term', 'Type', 'Marks', '%', 'Grade']}>
          {student.academic_progress?.length === 0 && (
            <tr><Td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af' }}>No marks recorded yet</Td></tr>
          )}
          {student.academic_progress?.map((p: any) => (
            <tr key={p.id}>
              <Td>{p.subject?.subject_name}</Td>
              <Td>{p.term?.toUpperCase()}</Td>
              <Td style={{ textTransform: 'capitalize' }}>{p.assessment_type?.replace(/_/g, ' ')}</Td>
              <Td>{p.marks}/{p.total_marks}</Td>
              <Td>{p.percentage}%</Td>
              <Td><strong>{p.grade}</strong></Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Grid cols={2}>
        {/* Attendance */}
        <Card>
          <CardHeader title="Recent Attendance" />
          <Table headers={['Date', 'Status']}>
            {student.attendance?.slice(0, 10).map((a: any) => (
              <tr key={a.id}>
                <Td>{new Date(a.date).toLocaleDateString()}</Td>
                <Td>{statusBadge(a.status)}</Td>
              </tr>
            ))}
          </Table>
        </Card>

        {/* Behaviour */}
        <Card>
          <CardHeader title="Behaviour Records" />
          <Table headers={['Date', 'Issue', 'Severity']}>
            {student.behaviour_records?.map((b: any) => (
              <tr key={b.id}>
                <Td>{new Date(b.issue_date).toLocaleDateString()}</Td>
                <Td>{b.issue_type}</Td>
                <Td>{statusBadge(b.severity)}</Td>
              </tr>
            ))}
          </Table>
        </Card>
      </Grid>

      {/* Add Guardian Modal */}
      <Modal open={guardianOpen} onClose={() => setGuardianOpen(false)} title="Add Guardian">
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="First Name"><Input value={gForm.first_name} onChange={e => setGForm(f => ({...f, first_name: e.target.value}))} required /></FormGroup>
          <FormGroup label="Last Name"><Input value={gForm.last_name} onChange={e => setGForm(f => ({...f, last_name: e.target.value}))} required /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="Phone"><Input value={gForm.phone} onChange={e => setGForm(f => ({...f, phone: e.target.value}))} required /></FormGroup>
          <FormGroup label="Relationship"><Input value={gForm.relationship} onChange={e => setGForm(f => ({...f, relationship: e.target.value}))} required /></FormGroup>
        </Grid>
        <Grid cols={2} style={{ marginBottom: 0 }}>
          <FormGroup label="National ID">
            <Input value={(gForm as any).national_id ?? ''} onChange={e => setGForm(f => ({...f, national_id: e.target.value} as any))} placeholder="e.g. 63-123456A78" />
          </FormGroup>
          <FormGroup label="Email (optional)">
            <Input type="email" value={gForm.email} onChange={e => setGForm(f => ({...f, email: e.target.value}))} placeholder="For password reset" />
          </FormGroup>
        </Grid>
        <div style={{ background: '#f0faf4', border: '1px solid #d1fae5', borderRadius: 8, padding: '8px 12px', marginBottom: 12, fontSize: 12, color: '#065f46' }}>
          🔐 Parent will login using <strong>phone number</strong> as username and <strong>National ID as default password</strong>. Email is optional but required for password reset.
        </div>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setGuardianOpen(false)}>Cancel</Btn>
          <Btn loading={addGuardian.isPending} onClick={() => addGuardian.mutate(gForm)}>Add Guardian</Btn>
        </div>
      </Modal>
    </div>
  );
}
