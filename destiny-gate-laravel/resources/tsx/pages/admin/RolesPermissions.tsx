import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError, confirmDelete } from '../../lib/toast';
import { Card, CardHeader, CardBody, Table, Td, Spinner, PageHeader, Btn, Badge, Modal, FormGroup, Input, Alert } from '../../components/UI';

interface Permission { name: string }
interface Role { id: number; name: string; permissions: string[]; users_count: number }

const PROTECTED = ['admin','headmaster','teacher','bursar','parent','student','user'];

const roleColors: Record<string, 'red'|'purple'|'blue'|'amber'|'green'|'blue'|'gray'> = {
  admin: 'red', headmaster: 'purple', teacher: 'blue',
  bursar: 'amber', parent: 'green', student: 'blue', user: 'gray',
};

export default function RolesPermissions() {
  const qc = useQueryClient();
  const [open, setOpen]       = useState(false);
  const [editing, setEditing] = useState<Role | null>(null);
  const [form, setForm]       = useState({ name: '', permissions: [] as string[] });
  const [formErr, setFormErr] = useState('');
  const [activeRole, setActiveRole] = useState<Role | null>(null);

  const { data: rolesData = [], isLoading: rolesLoading } = useQuery<Role[]>({
    queryKey: ['roles'],
    queryFn: () => api.get('/roles').then(r => r.data),
  });

  const { data: permissionsData = {} } = useQuery<Record<string, Permission[]>>({
    queryKey: ['permissions'],
    queryFn: () => api.get('/permissions').then(r => r.data),
  });

  const allPermissions = Object.values(permissionsData).flat().map(p => p.name);

  const displayPerm = (group: string, full: string) => {
    if (full.startsWith(group + '.')) return full.slice(group.length + 1);
    if (full.startsWith(group + '-')) return full.slice(group.length + 1);
    return full;
  };

  const openAdd = () => {
    setEditing(null);
    setForm({ name: '', permissions: [] });
    setFormErr('');
    setOpen(true);
  };

  const openEdit = (r: Role) => {
    setEditing(r);
    setForm({ name: r.name, permissions: [...r.permissions] });
    setFormErr('');
    setOpen(true);
  };

  const togglePerm = (perm: string) => {
    setForm(f => ({
      ...f,
      permissions: f.permissions.includes(perm)
        ? f.permissions.filter(p => p !== perm)
        : [...f.permissions, perm],
    }));
  };

  const toggleGroup = (group: string, perms: Permission[]) => {
    const names = perms.map(p => p.name);
    const allSelected = names.every(n => form.permissions.includes(n));
    setForm(f => ({
      ...f,
      permissions: allSelected
        ? f.permissions.filter(p => !names.includes(p))
        : [...new Set([...f.permissions, ...names])],
    }));
  };

  const save = useMutation({
    mutationFn: (d: typeof form) => editing
      ? api.put(`/roles/${editing.id}`, d)
      : api.post('/roles', d),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['roles'] });
      setOpen(false);
      toastSuccess(editing ? 'Role updated.' : 'Role created.');
    },
    onError: (e: any) => setFormErr(e.response?.data?.message ?? 'An error occurred.'),
  });

  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/roles/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['roles'] }); toastSuccess('Role deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete this role.'),
  });

  const handleDelete = async (r: Role) => {
    const ok = await confirmDelete(`role "${r.name}"`);
    if (ok) del.mutate(r.id);
  };

  if (rolesLoading) return <Spinner />;

  return (
    <div>
      <PageHeader
        title="Roles & Permissions"
        subtitle="Manage system roles and their permissions"
        action={<Btn onClick={openAdd}>+ Create Role</Btn>}
      />

      <div style={{ display: 'grid', gridTemplateColumns: '280px 1fr', gap: 20 }}>

        {/* Role list */}
        <div>
          <Card>
            <CardHeader title="Roles" />
            <div style={{ padding: '8px 0' }}>
              {rolesData.map(r => (
                <div
                  key={r.id}
                  onClick={() => setActiveRole(r)}
                  style={{
                    padding: '10px 16px', cursor: 'pointer',
                    background: activeRole?.id === r.id ? '#f0faf4' : 'transparent',
                    borderLeft: activeRole?.id === r.id ? '3px solid #1a6b3c' : '3px solid transparent',
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    transition: 'all .12s',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <Badge variant={roleColors[r.name] ?? 'gray'} style={{ textTransform: 'capitalize' }}>{r.name}</Badge>
                    <span style={{ fontSize: 11, color: '#9ca3af' }}>{r.users_count} users</span>
                  </div>
                  <div style={{ display: 'flex', gap: 4 }}>
                    <Btn size="sm" variant="ghost" onClick={e => { e.stopPropagation(); openEdit(r); }}>✏️</Btn>
                    {!PROTECTED.includes(r.name) && (
                      <Btn size="sm" variant="ghost" onClick={e => { e.stopPropagation(); handleDelete(r); }}>🗑️</Btn>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </Card>
        </div>

        {/* Permission detail */}
        <div>
          {activeRole ? (
            <Card>
              <CardHeader
                title={`Permissions for: ${activeRole.name}`}
                action={<Btn size="sm" onClick={() => openEdit(activeRole)}>Edit Permissions</Btn>}
              />
              <CardBody>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: 8 }}>
                  {activeRole.permissions.map(p => (
                    <div key={p} style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '5px 10px', background: '#f0faf4', borderRadius: 6, fontSize: 12 }}>
                      <span style={{ color: '#1a6b3c' }}>✓</span>
                      <span style={{ color: '#374151' }}>{p}</span>
                    </div>
                  ))}
                  {activeRole.permissions.length === 0 && (
                    <p style={{ color: '#9ca3af', fontSize: 13 }}>No permissions assigned.</p>
                  )}
                </div>
              </CardBody>
            </Card>
          ) : (
            <Card>
              <CardBody>
                <div style={{ textAlign: 'center', padding: '48px 20px', color: '#9ca3af' }}>
                  <div style={{ fontSize: 36, marginBottom: 12, opacity: .4 }}>🔐</div>
                  <p style={{ fontSize: 13 }}>Select a role to view its permissions.</p>
                </div>
              </CardBody>
            </Card>
          )}

          {/* All permissions overview */}
          <Card style={{ marginTop: 20 }}>
            <CardHeader title="All Permissions by Module" />
            <CardBody>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: 16 }}>
                {Object.entries(permissionsData).map(([group, perms]) => (
                  <div key={group} style={{ background: '#f9fafb', borderRadius: 8, padding: '12px 14px', border: '1px solid #e8eaed' }}>
                    <div style={{ fontSize: 11, fontWeight: 700, color: '#6b7280', textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: 8 }}>{group}</div>
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                      {perms.map((p: Permission) => (
                        <span key={p.name} style={{ fontSize: 10, background: '#fff', border: '1px solid #e8eaed', borderRadius: 4, padding: '2px 6px', color: '#374151' }}>
                          {displayPerm(group, p.name)}
                        </span>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </CardBody>
          </Card>
        </div>
      </div>

      {/* Create/Edit Role Modal */}
      <Modal open={open} onClose={() => setOpen(false)} title={editing ? `Edit Role: ${editing.name}` : 'Create New Role'}>
        {formErr && <Alert type="error" message={formErr} />}

        {(!editing || !PROTECTED.includes(editing.name)) && (
          <FormGroup label="Role Name">
            <Input
              value={form.name}
              onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
              placeholder="e.g. librarian"
              disabled={editing ? PROTECTED.includes(editing.name) : false}
            />
          </FormGroup>
        )}

        <div style={{ marginBottom: 8 }}>
          <div style={{ fontSize: 12, fontWeight: 600, color: '#374151', marginBottom: 10 }}>
            Permissions ({form.permissions.length} selected)
          </div>
          <div style={{ maxHeight: 400, overflowY: 'auto', border: '1px solid #e8eaed', borderRadius: 8, padding: 12 }}>
            {Object.entries(permissionsData).map(([group, perms]) => {
              const names = perms.map((p: Permission) => p.name);
              const allSelected = names.every(n => form.permissions.includes(n));
              const someSelected = names.some(n => form.permissions.includes(n));
              return (
                <div key={group} style={{ marginBottom: 14 }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
                    <input
                      type="checkbox"
                      checked={allSelected}
                      ref={el => { if (el) el.indeterminate = someSelected && !allSelected; }}
                      onChange={() => toggleGroup(group, perms)}
                      style={{ cursor: 'pointer' }}
                    />
                    <span style={{ fontSize: 11, fontWeight: 700, color: '#374151', textTransform: 'uppercase', letterSpacing: '.5px' }}>{group}</span>
                  </div>
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, paddingLeft: 20 }}>
                    {perms.map((p: Permission) => (
                      <label key={p.name} style={{ display: 'flex', alignItems: 'center', gap: 5, cursor: 'pointer', fontSize: 12, color: '#374151', background: form.permissions.includes(p.name) ? '#f0faf4' : '#f9fafb', border: `1px solid ${form.permissions.includes(p.name) ? '#d1fae5' : '#e8eaed'}`, borderRadius: 6, padding: '3px 8px' }}>
                        <input
                          type="checkbox"
                          checked={form.permissions.includes(p.name)}
                          onChange={() => togglePerm(p.name)}
                          style={{ cursor: 'pointer' }}
                        />
                        {p.name.replace(group + '-', '')}
                      </label>
                    ))}
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 12 }}>
          <Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn>
          <Btn loading={save.isPending} onClick={() => save.mutate(form)}>
            {editing ? 'Update Role' : 'Create Role'}
          </Btn>
        </div>
      </Modal>
    </div>
  );
}
