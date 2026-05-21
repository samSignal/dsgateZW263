import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../../lib/api';
import { toastSuccess } from '../../../lib/toast';
import { Btn, Card, CardBody, PageHeader, Grid, Table, Td, Badge, Spinner, Input, Select } from '../../../components/UI';

export default function AdmissionsDashboardPage() {
  const qc = useQueryClient();
  const [filters, setFilters] = useState({ status: '', search: '' });

  const { data: applications = [], isLoading } = useQuery({
    queryKey: ['admissions-list', filters],
    queryFn: () => api.get('/admissions-office/applications', { params: filters }).then(r => r.data)
  });

  const getStatusColor = (status: string) => {
    const colors: any = {
      submitted: 'blue',
      under_review: 'amber',
      accepted: 'green',
      rejected: 'red',
      draft: 'gray'
    };
    return colors[status] || 'blue';
  };

  if (isLoading) return <Spinner />;

  return (
    <div className="p-8">
      <PageHeader title="Admissions Dashboard" subtitle="Review and manage student applications" />

      <Card className="mb-8">
        <CardBody className="p-4 flex gap-4">
          <Input 
            placeholder="Search by name or app number..." 
            value={filters.search}
            onChange={e => setFilters({...filters, search: e.target.value})}
            className="max-w-md"
          />
          <Select 
            value={filters.status}
            onChange={e => setFilters({...filters, status: e.target.value})}
            className="w-48"
          >
            <option value="">All Statuses</option>
            <option value="submitted">Submitted</option>
            <option value="under_review">Under Review</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
          </Select>
        </CardBody>
      </Card>

      <Card>
        <Table headers={['App #', 'Student Name', 'Type', 'Form', 'Status', 'Submitted', 'Action']}>
          {applications.map((app: any) => (
            <tr key={app.id}>
              <Td><span className="font-mono font-bold">{app.application_number}</span></Td>
              <Td><strong>{app.student_first_name} {app.student_last_name}</strong></Td>
              <Td><span className="capitalize">{app.application_type.replace('_', ' ')}</span></Td>
              <Td>{app.form_name}</Td>
              <Td><Badge variant={getStatusColor(app.status)}>{app.status.replace('_', ' ')}</Badge></Td>
              <Td>{app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : '—'}</Td>
              <Td>
                <Link to={`/app/admissions/${app.id}`}>
                  <Btn size="sm" variant="outline" className="border-[#0f3d22] text-[#0f3d22]">Review</Btn>
                </Link>
              </Td>
            </tr>
          ))}
          {applications.length === 0 && (
            <tr>
              <Td colSpan={7} className="text-center py-12 text-gray-500">No applications found.</Td>
            </tr>
          )}
        </Table>
      </Card>
    </div>
  );
}
