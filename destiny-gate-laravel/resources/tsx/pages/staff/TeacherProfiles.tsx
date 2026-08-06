import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import { Card, Table, Td, Spinner, PageHeader, Btn, Badge } from '../../components/UI';

interface TeacherRow {
  id: number; staff_number: string; first_name: string; last_name: string;
  email: string; department_name: string; job_title: string;
  teacher_code: string; specialization: string | null; status: string;
}

export default function TeacherProfiles() {
  const [search, setSearch] = useState('');

  const { data, isLoading } = useQuery<TeacherRow[]>({
    queryKey: ['teacher-profiles', search],
    queryFn: () => api.get('/staff-members', {
      params: { search: search || undefined, per_page: 100 }
    }).then(r => r.data.data.filter((s: any) => s.teacher_code)),
  });

  const teachers = data ?? [];

  return (
    <div>
      <PageHeader title="Teacher Profiles" subtitle="All staff members with teacher profiles" />

      <div style={{ marginBottom: 16 }}>
        <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search teachers…"
          style={{ padding: '8px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13, width: 280, outline: 'none' }} />
      </div>

      {isLoading ? <Spinner /> : (
        <Card>
          <Table headers={['Teacher Code', 'Name', 'Department', 'Specialization', 'Status', 'Actions']}>
            {teachers.map(t => (
              <tr key={t.id}>
                <Td><code style={{ background: '#eff6ff', color: '#1e40af', padding: '2px 8px', borderRadius: 5, fontSize: 12 }}>{t.teacher_code}</code></Td>
                <Td>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <div style={{ width: 32, height: 32, borderRadius: '50%', background: 'linear-gradient(135deg,#b6924c,#8a6b34)', color: '#0f1a2e', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 12, fontWeight: 700 }}>
                      {t.first_name[0]}{t.last_name[0]}
                    </div>
                    <div>
                      <div style={{ fontWeight: 600, fontSize: 13 }}>{t.first_name} {t.last_name}</div>
                      <div style={{ fontSize: 11, color: '#6b7280' }}>{t.email}</div>
                    </div>
                  </div>
                </Td>
                <Td>{t.department_name}</Td>
                <Td>{t.specialization ?? <span style={{ color: '#9ca3af' }}>—</span>}</Td>
                <Td>
                  <Badge variant={t.status === 'active' ? 'green' : t.status === 'on_leave' ? 'amber' : 'red'} style={{ textTransform: 'capitalize' }}>
                    {t.status.replace('_', ' ')}
                  </Badge>
                </Td>
                <Td>
                  <Link to={`/app/staff/${t.id}`}><Btn size="sm" variant="outline">View Profile</Btn></Link>
                </Td>
              </tr>
            ))}
            {teachers.length === 0 && (
              <tr><Td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af', padding: 40 }}>No teacher profiles found.</Td></tr>
            )}
          </Table>
        </Card>
      )}
    </div>
  );
}
