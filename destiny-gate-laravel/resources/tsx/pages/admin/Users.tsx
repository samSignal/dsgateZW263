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

  const updateRole = useMutation({
    mutationFn: ({ id, role }: { id: number; role: string }) => api.patch(`/admin/users/${id}/role`, { role }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['users'] }); toastSuccess('Role updated successfully.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to update role.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="User Management" subtitle="Manage user roles and access" />
      <Card>
        <Table headers={['Name', 'Email', 'Role', 'Status', 'Joined', 'Change Role']}>
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
                    defaultValue={u.role}
                    onChange={e => updateRole.mutate({ id: u.id, role: e.target.value })}
                    style={{ padding: '4px 8px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 12, cursor: 'pointer' }}
                  >
                    {['admin','headmaster','teacher','bursar','parent','student','user'].map(r => (
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
