import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../../lib/api';
import { toastSuccess, toastError } from '../../../lib/toast';
import { Btn, Card, CardBody, CardHeader, PageHeader, Grid, Badge, Spinner, Table, Td, FormGroup, Select, Textarea } from '../../../components/UI';

export default function ReviewApplicationPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [remarks, setRemarks] = useState('');
  const [status, setStatus] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['admission-detail', id],
    queryFn: () => api.get(`/admissions-office/applications/${id}`).then(r => r.data)
  });

  const updateStatus = useMutation({
    mutationFn: (newStatus: string) => api.patch(`/admissions-office/applications/${id}`, { status: newStatus, remarks }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admission-detail', id] });
      toastSuccess('Status updated.');
    }
  });

  const accept = useMutation({
    mutationFn: () => api.post(`/admissions-office/applications/${id}/accept`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admission-detail', id] });
      toastSuccess('Application accepted! Student and Guardian accounts created.');
    },
    onError: (e: any) => toastError(e.response?.data?.message || 'Acceptance failed.')
  });

  if (isLoading) return <Spinner />;
  const app = data.application;

  return (
    <div className="p-8 space-y-8">
      <PageHeader 
        title={`Review: ${app.student_first_name} ${app.student_last_name}`}
        subtitle={`Application ${app.application_number} · ${app.application_type.replace('_', ' ')}`}
        action={<Badge variant="blue" className="text-sm px-4 py-1">{app.status.replace('_', ' ')}</Badge>}
      />

      <Grid cols={3}>
        <div className="col-span-2 space-y-8">
          <Card>
            <CardHeader title="Student Details" />
            <CardBody className="p-6">
              <Grid cols={2}>
                <div>
                  <div className="text-xs text-gray-500 uppercase font-bold">Gender</div>
                  <div className="capitalize">{app.gender}</div>
                </div>
                <div>
                  <div className="text-xs text-gray-500 uppercase font-bold">Date of Birth</div>
                  <div>{app.date_of_birth}</div>
                </div>
                <div className="mt-4">
                  <div className="text-xs text-gray-500 uppercase font-bold">Birth Certificate</div>
                  <div>{app.birth_certificate_number}</div>
                </div>
                <div className="mt-4">
                  <div className="text-xs text-gray-500 uppercase font-bold">National ID</div>
                  <div>{app.student_national_id || '—'}</div>
                </div>
              </Grid>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Guardian Information" />
            <CardBody className="p-6">
              <Grid cols={2}>
                <div>
                  <div className="text-xs text-gray-500 uppercase font-bold">Name</div>
                  <div>{app.guardian_name}</div>
                </div>
                <div>
                  <div className="text-xs text-gray-500 uppercase font-bold">National ID</div>
                  <div>{app.guardian_national_id}</div>
                </div>
                <div className="mt-4">
                  <div className="text-xs text-gray-500 uppercase font-bold">Phone</div>
                  <div>{app.guardian_phone}</div>
                </div>
                <div className="mt-4">
                  <div className="text-xs text-gray-500 uppercase font-bold">Email</div>
                  <div>{app.guardian_email}</div>
                </div>
              </Grid>
              <div className="mt-4 pt-4 border-t">
                <div className="text-xs text-gray-500 uppercase font-bold">Address</div>
                <div>{app.address}</div>
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Uploaded Documents" />
            <CardBody className="p-0">
              <Table headers={['Document Type', 'Uploaded', 'Action']}>
                {data.documents.map((doc: any) => (
                  <tr key={doc.id}>
                    <Td><span className="capitalize">{doc.document_type.replace('_', ' ')}</span></Td>
                    <Td>{new Date(doc.uploaded_at).toLocaleString()}</Td>
                    <Td>
                      <a href={`/storage/${doc.file_path}`} target="_blank" rel="noreferrer">
                        <Btn size="sm" variant="outline">View</Btn>
                      </a>
                    </Td>
                  </tr>
                ))}
                {data.documents.length === 0 && (
                  <tr><Td colSpan={3} className="text-center py-8 text-gray-500">No documents uploaded.</Td></tr>
                )}
              </Table>
            </CardBody>
          </Card>
        </div>

        <div className="space-y-8">
          <Card className="border-[#0f3d22]/20 bg-[#0f3d22]/5">
            <CardHeader title="Take Action" />
            <CardBody className="p-6 space-y-4">
              <FormGroup label="Remarks">
                <textarea 
                  className="w-full rounded-md border-gray-300 shadow-sm focus:border-[#0f3d22] focus:ring-[#0f3d22] text-sm"
                  rows={4}
                  value={remarks}
                  onChange={e => setRemarks(e.target.value)}
                  placeholder="Internal notes or notification message..."
                />
              </FormGroup>
              
              <div className="space-y-2">
                <Btn 
                  className="w-full bg-[#0f3d22]" 
                  loading={accept.isPending}
                  onClick={() => accept.mutate()}
                  disabled={app.status === 'accepted'}
                >
                  Accept & Create Accounts
                </Btn>
                <div className="grid grid-cols-2 gap-2">
                  <Btn 
                    variant="outline" 
                    className="border-amber-600 text-amber-700"
                    onClick={() => updateStatus.mutate('under_review')}
                  >
                    Under Review
                  </Btn>
                  <Btn 
                    variant="outline" 
                    className="border-red-600 text-red-700"
                    onClick={() => updateStatus.mutate('rejected')}
                  >
                    Reject
                  </Btn>
                </div>
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Application History" />
            <CardBody className="p-0">
              <div className="p-4 space-y-4 max-h-[400px] overflow-y-auto">
                {data.notifications.map((n: any) => (
                  <div key={n.id} className="pb-4 border-b last:border-0">
                    <div className="font-bold text-sm text-gray-900">{n.title}</div>
                    <div className="text-xs text-gray-600 mt-1">{n.message}</div>
                    <div className="text-[10px] text-gray-400 mt-1">{new Date(n.created_at).toLocaleString()}</div>
                  </div>
                ))}
              </div>
            </CardBody>
          </Card>
        </div>
      </Grid>
    </div>
  );
}
