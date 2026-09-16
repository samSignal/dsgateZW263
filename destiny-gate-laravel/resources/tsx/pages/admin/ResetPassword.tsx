import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmAction } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Alert, Badge, Btn, Modal, Code, Input } from '../../components/UI';
import { RoleBadge } from '../../components/Layout';

export default function ResetPassword() {
  const [search, setSearch] = useState('');
  const [resetResult, setResetResult] = useState<{ userName: string; password: string } | null>(null);

  const { data, isLoading } = useQuery({ queryKey: ['users'], queryFn: () => api.get('/admin/users').then(r => r.data) });

  const resetPassword = useMutation({
    mutationFn: (user: { id: number; name: string }) =>
      api.post(`/admin/users/${user.id}/reset-password`).then(r => ({ userName: user.name, password: r.data.temporary_password as string })),
    onSuccess: ({ userName, password }) => { setResetResult({ userName, password }); toastSuccess('Password reset.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed to reset password.'),
  });

  const handleResetPassword = async (user: { id: number; name: string }) => {
    const ok = await confirmAction(
      "Reset this user's password?",
      `A new temporary password will be generated for ${user.name}. They will be required to change it on next login.`,
      'Yes, reset it'
    );
    if (ok) resetPassword.mutate(user);
  };

  if (isLoading) return <Spinner />;

  const users = (data?.data ?? []).filter((u: any) =>
    !search || u.name.toLowerCase().includes(search.toLowerCase()) || u.email?.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div>
      <PageHeader title="Reset Password" subtitle="Issue a new temporary password for any user in the portal" />
      <Card>
        <div style={{ marginBottom: 16, maxWidth: 320 }}>
          <Input placeholder="Search by name or email…" value={search} onChange={e => setSearch(e.target.value)} />
        </div>
        <Table headers={['Name', 'Email', 'Role', 'Status', '']}>
          {users.map((u: any) => (
            <tr key={u.id}>
              <Td><strong>{u.name}</strong></Td>
              <Td style={{ color: '#6b7280' }}>{u.email}</Td>
              <Td><RoleBadge role={u.role} /></Td>
              <Td>{u.is_active ? <Badge variant="green">Active</Badge> : <Badge variant="red">Inactive</Badge>}</Td>
              <Td>
                <Btn
                  variant="outline"
                  size="sm"
                  loading={resetPassword.isPending && resetPassword.variables?.id === u.id}
                  onClick={() => handleResetPassword({ id: u.id, name: u.name })}
                >
                  Reset Password
                </Btn>
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={!!resetResult} onClose={() => setResetResult(null)} title="Password Reset">
        {resetResult && (
          <>
            <Alert type="success" message={`New temporary password for ${resetResult.userName}:`} />
            <Code>{resetResult.password}</Code>
            <p style={{ fontSize: 12, color: '#6b7280', marginTop: 14 }}>
              Share this with the user through a secure channel. It won't be shown again, and they'll be
              required to set a new password the next time they log in.
            </p>
            <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: 16 }}>
              <Btn variant="primary" onClick={() => setResetResult(null)}>Done</Btn>
            </div>
          </>
        )}
      </Modal>
    </div>
  );
}
