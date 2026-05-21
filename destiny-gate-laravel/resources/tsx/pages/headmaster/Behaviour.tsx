import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { Card, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge } from '../../components/UI';

export default function Behaviour() {
  const qc = useQueryClient();
  const [msg, setMsg] = useState('');
  const [reviews, setReviews] = useState<Record<number, string>>({});

  const { data, isLoading } = useQuery({ queryKey: ['behaviour'], queryFn: () => api.get('/headmaster/behaviour').then(r => r.data) });

  const review = useMutation({
    mutationFn: ({ id, text }: { id: number; text: string }) => api.post(`/headmaster/behaviour/${id}/review`, { headmaster_review: text }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['behaviour'] }); setMsg('Review saved.'); },
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Behaviour & Discipline" subtitle="Review and manage discipline cases" />
      {msg && <Alert type="success" message={msg} />}
      <Card>
        <Table headers={['Student', 'Issue', 'Severity', 'Date', 'Action', 'Review']}>
          {data?.data?.map((b: any) => (
            <tr key={b.id}>
              <Td><strong>{b.student?.first_name} {b.student?.last_name}</strong></Td>
              <Td>{b.issue_type}</Td>
              <Td>{statusBadge(b.severity)}</Td>
              <Td style={{ color: '#6b7280' }}>{new Date(b.issue_date).toLocaleDateString()}</Td>
              <Td>{b.action ?? '—'}</Td>
              <Td>
                {b.reviewed_at ? (
                  <span style={{ fontSize: 12, color: '#059669' }}>✓ Reviewed</span>
                ) : (
                  <div style={{ display: 'flex', gap: 6 }}>
                    <input
                      value={reviews[b.id] ?? ''}
                      onChange={e => setReviews(r => ({ ...r, [b.id]: e.target.value }))}
                      placeholder="Review notes…"
                      style={{ padding: '4px 8px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 12, width: 160 }}
                    />
                    <Btn size="sm" loading={review.isPending} onClick={() => review.mutate({ id: b.id, text: reviews[b.id] ?? '' })}>Save</Btn>
                  </div>
                )}
              </Td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}
