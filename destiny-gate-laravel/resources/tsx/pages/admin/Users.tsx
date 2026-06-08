import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, CardHeader, Table, Td, Spinner, PageHeader, Alert, Badge } from '../../components/UI';
import { RoleBadge } from '../../components/Layout';

export default function Users() {
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');

  const { data, isLoading } = useQuery({ queryKey: ['users'], queryFn: () => api.get('/admin/users').then(r => r.data) });
  const { data: roles = [] } = useQuery({
    queryKey: ['spatie-roles'],
    queryFn: () => api.get('/roles').then(r => r.data as Array<{ id: number; name: string }>),
  });

  const updateRole = useMutation({
    mutationFn: ({ id, role }: { id: number; role: string }) => api.patch(`/admin/users/${id}/role`, { role }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['users'] }); toastSuccess('Role updated successfully.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to update role.'),
  });

  const assignSpatieRole = useMutation({
    mutationFn: ({ id, role }: { id: number; role: string }) => api.post(`/users/${id}/assign-role`, { role }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['users'] }); qc.invalidateQueries({ queryKey: ['spatie-roles'] }); toastSuccess('Admissions Office role assigned.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to assign role.'),
  });

  if (isLoading) return <Spinner />;

  const baseRoles = ['admin','headmaster','teacher','bursar','parent','student','user'];
  const spatieRoleNames = roles.map(r => r.name);
  const admissionsRoleNames = spatieRoleNames.filter(n => n.startsWith('admissions_'));

  return (
    <div>
      <PageHeader title="User Management" subtitle="Manage user roles and access" />
      <Card>
        <Table headers={['Name', 'Email', 'Role', 'Status', 'Joined', 'Admissions Office Role', 'Change Base Role']}>
          {data?.data?.map((u: any) => (
            <tr key={u.id}>
              <Td><strong>{u.name}</strong></Td>
              <Td style={{ color: '#6b7280' }}>{u.email}</Td>
              <Td><RoleBadge role={u.role} /></Td>
              <Td>{u.is_active ? <Badge variant="green">Active</Badge> : <Badge variant="red">Inactive</Badge>}</Td>
              <Td style={{ color: '#6b7280' }}>{new Date(u.created_at).toLocaleDateString()}</Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  <select
                    defaultValue={admissionsRoleNames.includes(u.role) ? u.role : ''}
                    onChange={e => assignSpatieRole.mutate({ id: u.id, role: e.target.value })}
                    style={{ padding: '4px 8px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 12, cursor: 'pointer' }}
                  >
                    <option value="">—</option>
                    {admissionsRoleNames.map(r => (
                      <option key={r} value={r}>{r.replace(/_/g, ' ')}</option>
                    ))}
                  </select>
                </div>
              </Td>
              <Td>
                <div style={{ display: 'flex', gap: 6 }}>
                  <select
                    value={baseRoles.includes(u.role) ? u.role : ''}
                    onChange={e => updateRole.mutate({ id: u.id, role: e.target.value })}
                    style={{ padding: '4px 8px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 12, cursor: 'pointer' }}
                  >
                    <option value="" disabled>{baseRoles.includes(u.role) ? '' : '—'}</option>
                    {baseRoles.map(r => (
                      <option key={r} value={r}>{r.charAt(0).toUpperCase() + r.slice(1)}</option>
                    ))}
                  </select>
                </div>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}
