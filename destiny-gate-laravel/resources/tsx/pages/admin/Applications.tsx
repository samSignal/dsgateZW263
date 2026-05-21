import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastSuccess, toastError } from '../../lib/toast';
import { Card, Table, Td, Spinner, PageHeader, Alert, Btn, statusBadge, Modal, FormGroup, Textarea } from '../../components/UI';

export default function Applications() {
  const qc = useQueryClient();
  const [rejectId, setRejectId] = useState<number | null>(null);
  const [reason, setReason] = useState('');

  const { data, isLoading } = useQuery({ queryKey: ['applications'], queryFn: () => api.get('/admin/applications').then(r => r.data) });

  const approve = useMutation({
    mutationFn: (id: number) => api.post(`/admin/applications/${id}/approve`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['applications'] }); toastSuccess('Application approved.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });
  const reject = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => api.post(`/admin/applications/${id}/reject`, { rejection_reason: reason }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['applications'] }); setRejectId(null); setReason(''); toastSuccess('Application rejected.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Failed.'),
  });

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Admission Applications" subtitle="Review and process student applications" />
      <Card>
        <Table headers={['App #', 'Applicant', 'Class', 'Year', 'Status', 'Submitted', 'Actions']}>
          {data?.data?.map((a: any) => (
            <tr key={a.id}>
              <Td><code style={{ background: '#f3f4f6', padding: '2px 7px', borderRadius: 5, fontSize: 12 }}>{a.application_number}</code></Td>
              <Td><strong>{a.first_name} {a.last_name}</strong></Td>
              <Td>{a.intended_class}</Td>
              <Td>{a.academic_year}</Td>
              <Td>{statusBadge(a.status)}</Td>
              <Td style={{ color: '#6b7280' }}>{new Date(a.created_at).toLocaleDateString()}</Td>
              <Td>
                {a.status === 'pending' && (
                  <div style={{ display: 'flex', gap: 6 }}>
                    <Btn size="sm" loading={approve.isPending} onClick={() => approve.mutate(a.id)}>Approve</Btn>
                    <Btn size="sm" variant="danger" onClick={() => setRejectId(a.id)}>Reject</Btn>
                  </div>
                )}
              </Td>
            </tr>
          ))}
        </Table>
      </Card>

      <Modal open={!!rejectId} onClose={() => setRejectId(null)} title="Reject Application">
        <FormGroup label="Rejection Reason">
          <Textarea value={reason} onChange={e => setReason(e.target.value)} placeholder="Provide a reason for rejection..." />
        </FormGroup>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Btn variant="outline" onClick={() => setRejectId(null)}>Cancel</Btn>
          <Btn variant="danger" loading={reject.isPending} onClick={() => rejectId && reject.mutate({ id: rejectId, reason })}>Reject</Btn>
        </div>
      </Modal>
    </div>
  );
}
