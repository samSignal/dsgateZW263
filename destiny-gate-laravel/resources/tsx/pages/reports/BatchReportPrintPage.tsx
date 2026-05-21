import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import { Btn, Card, CardBody, FormGroup, Grid, PageHeader, Select } from '../../components/UI';

export default function BatchReportPrintPage() {
  const [filters, setFilters] = useState({ academic_year_id: '', term_id: '', form_id: '', stream_id: '' });
  const { data: years = [] } = useQuery({ queryKey: ['academic-years'], queryFn: () => api.get('/academic-years').then(r => r.data) });
  const { data: terms = [] } = useQuery({ queryKey: ['terms'], queryFn: () => api.get('/terms').then(r => r.data) });
  const { data: forms = [] } = useQuery({ queryKey: ['forms'], queryFn: () => api.get('/forms').then(r => r.data) });
  const { data: streams = [] } = useQuery({ queryKey: ['streams'], queryFn: () => api.get('/streams').then(r => r.data) });
  const filteredTerms = (terms as any[]).filter(t => !filters.academic_year_id || String(t.academic_year_id) === filters.academic_year_id);
  const filteredStreams = (streams as any[]).filter(s => !filters.form_id || String(s.form_id) === filters.form_id);
  async function batchDownload() {
    const res = await api.get('/reports/batch-download', { params: filters, responseType: 'blob' });
    const href = URL.createObjectURL(new Blob([res.data], { type: res.headers['content-type'] || 'application/pdf' }));
    const a = document.createElement('a'); a.href = href; a.download = 'stream-report-cards.pdf'; a.click(); URL.revokeObjectURL(href);
  }
  return <div>
    <PageHeader title="Batch Report Printing" subtitle="Download all generated report cards for a stream as one A4 portrait PDF" />
    <Card><CardBody><Grid cols={4}>
      <FormGroup label="Academic Year"><Select value={filters.academic_year_id} onChange={e => setFilters(f => ({ ...f, academic_year_id: e.target.value, term_id: '' }))}><option value="">Year</option>{(years as any[]).map(y => <option key={y.id} value={y.id}>{y.name}</option>)}</Select></FormGroup>
      <FormGroup label="Term"><Select value={filters.term_id} onChange={e => setFilters(f => ({ ...f, term_id: e.target.value }))}><option value="">Term</option>{filteredTerms.map((t: any) => <option key={t.id} value={t.id}>{t.name}</option>)}</Select></FormGroup>
      <FormGroup label="Form"><Select value={filters.form_id} onChange={e => setFilters(f => ({ ...f, form_id: e.target.value, stream_id: '' }))}><option value="">Form</option>{(forms as any[]).map(fm => <option key={fm.id} value={fm.id}>{fm.name}</option>)}</Select></FormGroup>
      <FormGroup label="Stream"><Select value={filters.stream_id} onChange={e => setFilters(f => ({ ...f, stream_id: e.target.value }))}><option value="">Stream</option>{filteredStreams.map((s: any) => <option key={s.id} value={s.id}>{s.name}</option>)}</Select></FormGroup>
    </Grid><Btn onClick={batchDownload}>Download Stream PDF</Btn></CardBody></Card>
  </div>;
}
