import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Card, CardHeader, CardBody, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Select, Alert, Grid } from '../../components/UI';

const statusColor: Record<string, 'green' | 'amber' | 'red' | 'gray'> = {
  active: 'green', on_leave: 'amber', suspended: 'red', resigned: 'gray',
};

export default function StaffProfilePage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [roleOpen, setRoleOpen]     = useState(false);
  const [teacherOpen, setTeacherOpen] = useState(false);
  const [roleForm, setRoleForm]     = useState({ role: 'teacher', create_account: false });
  const [teacherForm, setTeacherForm] = useState({ specialization: '' });
  const [formErr, setFormErr]       = useState('');

  const { data: staff, isLoading } = useQuery({
    queryKey: ['staff-member', id],
    queryFn: () => api.get(`/staff-members/${id}`).then(r => r.data),
  });

  const statusAction = useMutation({
    mutationFn: (action: string) => api.post(`/staff-members/${id}/${action}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['staff-member', id] }); toastSuccess('Status updated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  const assignRole = useMutation({
    mutationFn: (d: typeof roleForm) => api.post(`/staff-members/${id}/assign-role`, d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['staff-member', id] }); setRoleOpen(false); toastSuccess('Role assigned.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'Failed.'),
  });

  const createTeacher = useMutation({
    mutationFn: (d: typeof teacherForm) => api.post(`/staff-members/${id}/teacher-profile`, d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['staff-member', id] }); setTeacherOpen(false); toastSuccess('Teacher profile created.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'Failed.'),
  });

  const updateTeacher = useMutation({
    mutationFn: (d: typeof teacherForm) => api.put(`/staff-members/${id}/teacher-profile`, d),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['staff-member', id] }); setTeacherOpen(false); toastSuccess('Teacher profile updated.'); },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'Failed.'),
  });

  const handleStatus = async (action: string, label: string) => {
    const ok = await confirmAction(`${label}?`, `This will change ${staff?.first_name}'s status.`, `Yes, ${label}`);
    if (ok) statusAction.mutate(action);
  };

  if (isLoading) return <Spinner />;
  if (!staff) return <div style={{ padding: 40, textAlign: 'center', color: '#9ca3af' }}>Staff member not found.</div>;

  const hasTeacherProfile = !!staff.teacher_code;

  return (
    <div>
      <PageHeader
        title={`${staff.first_name} ${staff.last_name}`}
        subtitle={`${staff.staff_number} · ${staff.job_title} · ${staff.department_name}`}
        action={<Btn variant="outline" onClick={() => navigate('/app/staff')}>← Back to Staff</Btn>}
      />

      {/* Top info bar */}
      <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e8eaed', padding: '20px 24px', marginBottom: 20, display: 'flex', alignItems: 'center', gap: 20, flexWrap: 'wrap' }}>
        <div style={{ width: 64, height: 64, borderRadius: '50%', background: 'linear-gradient(135deg,#1a6b3c,#0f3d22)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 22, fontWeight: 700, flexShrink: 0 }}>
          {staff.first_name[0]}{staff.last_name[0]}
        </div>
        <div style={{ flex: 1 }}>
          <div style={{ fontSize: 18, fontWeight: 800, color: '#111827' }}>{staff.first_name} {staff.last_name}</div>
          <div style={{ fontSize: 13, color: '#6b7280', marginTop: 2 }}>{staff.email} · {staff.phone}</div>
          <div style={{ display: 'flex', gap: 8, marginTop: 8, flexWrap: 'wrap' }}>
            <Badge variant={statusColor[staff.status] ?? 'gray'} style={{ textTransform: 'capitalize' }}>{staff.status.replace('_', ' ')}</Badge>
            {staff.user_role && <Badge variant="blue" style={{ textTransform: 'capitalize' }}>{staff.user_role}</Badge>}
            {hasTeacherProfile && <Badge variant="green">Teacher · {staff.teacher_code}</Badge>}
            <Badge variant="gray" style={{ textTransform: 'capitalize' }}>{staff.employment_type.replace('_', ' ')}</Badge>
          </div>
        </div>
        {/* Status actions */}
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
          {staff.status !== 'active'    && <Btn size="sm" onClick={() => handleStatus('activate', 'Activate')}>Activate</Btn>}
          {staff.status === 'active'    && <Btn size="sm" variant="outline" onClick={() => handleStatus('on-leave', 'Set On Leave')}>Set On Leave</Btn>}
          {staff.status !== 'suspended' && staff.status !== 'resigned' && <Btn size="sm" variant="danger" onClick={() => handleStatus('suspend', 'Suspend')}>Suspend</Btn>}
          {staff.status !== 'resigned'  && <Btn size="sm" variant="danger" onClick={() => handleStatus('resign', 'Mark Resigned')}>Resign</Btn>}
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginBottom: 20 }}>
        {/* Personal Details */}
        <Card>
          <CardHeader title="Personal Information" />
          <CardBody>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              {[
                ['Staff Number', staff.staff_number],
                ['Gender',       staff.gender.charAt(0).toUpperCase() + staff.gender.slice(1)],
                ['National ID',  staff.national_id ?? '—'],
                ['Phone',        staff.phone],
                ['Email',        staff.email],
                ['Address',      staff.address ?? '—'],
              ].map(([k, v]) => (
                <tr key={k}>
                  <td style={{ padding: '7px 0', color: '#6b7280', fontSize: 12, width: '40%', fontWeight: 500 }}>{k}</td>
                  <td style={{ padding: '7px 0', fontSize: 13, color: '#111827' }}>{v}</td>
                </tr>
              ))}
            </table>
          </CardBody>
        </Card>

        {/* Employment Details */}
        <Card>
          <CardHeader title="Employment Information" />
          <CardBody>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              {[
                ['Department',       staff.department_name],
                ['Job Title',        staff.job_title],
                ['Employment Type',  staff.employment_type.replace('_', ' ')],
                ['Employment Date',  staff.employment_date],
                ['Status',           staff.status.replace('_', ' ')],
              ].map(([k, v]) => (
                <tr key={k}>
                  <td style={{ padding: '7px 0', color: '#6b7280', fontSize: 12, width: '40%', fontWeight: 500 }}>{k}</td>
                  <td style={{ padding: '7px 0', fontSize: 13, color: '#111827', textTransform: 'capitalize' }}>{v}</td>
                </tr>
              ))}
            </table>
          </CardBody>
        </Card>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
        {/* System Access */}
        <Card>
          <CardHeader title="System Access & Role"
            action={<Btn size="sm" onClick={() => { setRoleForm({ role: staff.user_role ?? 'teacher', create_account: !staff.user_id }); setFormErr(''); setRoleOpen(true); }}>
              {staff.user_id ? 'Change Role' : 'Create Account'}
            </Btn>}
          />
          <CardBody>
            {staff.user_id ? (
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 12 }}>
                  <div style={{ width: 36, height: 36, borderRadius: 8, background: '#f0faf4', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 }}>🔑</div>
                  <div>
                    <div style={{ fontSize: 13, fontWeight: 600 }}>Account Active</div>
                    <div style={{ fontSize: 11, color: '#6b7280' }}>{staff.user_email}</div>
                  </div>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <span style={{ fontSize: 12, color: '#6b7280' }}>Current role:</span>
                  <Badge variant="blue" style={{ textTransform: 'capitalize' }}>{staff.user_role}</Badge>
                </div>
              </div>
            ) : (
              <div style={{ textAlign: 'center', padding: '20px 0', color: '#9ca3af' }}>
                <div style={{ fontSize: 28, marginBottom: 8 }}>🔒</div>
                <p style={{ fontSize: 13 }}>No system account yet.</p>
                <p style={{ fontSize: 12 }}>Click "Create Account" to give this staff member login access.</p>
              </div>
            )}
          </CardBody>
        </Card>

        {/* Teacher Profile */}
        <Card>
          <CardHeader title="Teacher Profile"
            action={
              hasTeacherProfile
                ? <Btn size="sm" variant="outline" onClick={() => { setTeacherForm({ specialization: staff.specialization ?? '' }); setFormErr(''); setTeacherOpen(true); }}>Edit</Btn>
                : <Btn size="sm" onClick={() => { setTeacherForm({ specialization: '' }); setFormErr(''); setTeacherOpen(true); }}>+ Create Profile</Btn>
            }
          />
          <CardBody>
            {hasTeacherProfile ? (
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 12 }}>
                  <div style={{ width: 36, height: 36, borderRadius: 8, background: '#eff6ff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 }}>📚</div>
                  <div>
                    <div style={{ fontSize: 13, fontWeight: 600 }}>Teacher Code</div>
                    <code style={{ fontSize: 13, background: '#f1f5f9', padding: '2px 8px', borderRadius: 5 }}>{staff.teacher_code}</code>
                  </div>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <span style={{ fontSize: 12, color: '#6b7280' }}>Specialization:</span>
                  <span style={{ fontSize: 13, color: '#111827' }}>{staff.specialization ?? '—'}</span>
                </div>
              </div>
            ) : (
              <div style={{ textAlign: 'center', padding: '20px 0', color: '#9ca3af' }}>
                <div style={{ fontSize: 28, marginBottom: 8 }}>📖</div>
                <p style={{ fontSize: 13 }}>No teacher profile yet.</p>
                <p style={{ fontSize: 12 }}>Create a teacher profile to enable subject allocation.</p>
              </div>
            )}
          </CardBody>
        </Card>
      </div>

      {/* Role Modal */}
      <Modal open={roleOpen} onClose={() => setRoleOpen(false)} title={staff.user_id ? 'Change System Role' : 'Create Login Account'}>
        {formErr && <Alert type="error" message={formErr} />}
        {!staff.user_id && (
          <div style={{ background: '#fffbeb', border: '1px solid #fde68a', borderRadius: 8, padding: '10px 14px', marginBottom: 16, fontSize: 12, color: '#92400e' }}>
            A login account will be created with the default password: <strong>DGI@{new Date().getFullYear()}!</strong>
          </div>
        )}
        <FormGroup label="System Role">
          <Select value={roleForm.role} onChange={e => setRoleForm(f => ({ ...f, role: e.target.value }))}>
            <option value="teacher">Teacher</option>
            <option value="headmaster">Headmaster</option>
            <option value="bursar">Bursar</option>
            <option value="admin">Admin</option>
            <option value="user">User (no special access)</option>
          </Select>
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setRoleOpen(false)}>Cancel</Btn>
          <Btn loading={assignRole.isPending} onClick={() => assignRole.mutate({ ...roleForm, create_account: !staff.user_id })}>
            {staff.user_id ? 'Update Role' : 'Create Account & Assign Role'}
          </Btn>
        </div>
      </Modal>

      {/* Teacher Profile Modal */}
      <Modal open={teacherOpen} onClose={() => setTeacherOpen(false)} title={hasTeacherProfile ? 'Edit Teacher Profile' : 'Create Teacher Profile'}>
        {formErr && <Alert type="error" message={formErr} />}
        <FormGroup label="Specialization">
          <Input value={teacherForm.specialization} onChange={e => setTeacherForm(f => ({ ...f, specialization: e.target.value }))} placeholder="e.g. Mathematics, Sciences, Commercials" />
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <Btn variant="outline" onClick={() => setTeacherOpen(false)}>Cancel</Btn>
          <Btn loading={createTeacher.isPending || updateTeacher.isPending}
            onClick={() => hasTeacherProfile ? updateTeacher.mutate(teacherForm) : createTeacher.mutate(teacherForm)}>
            {hasTeacherProfile ? 'Update' : 'Create Teacher Profile'}
          </Btn>
        </div>
      </Modal>
    </div>
  );
}
