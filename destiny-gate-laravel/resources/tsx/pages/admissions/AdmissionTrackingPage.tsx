import React, { useState, useEffect } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Link, useSearchParams } from 'react-router-dom';
import api from '../../lib/api';
import { Btn, Card, CardBody, FormGroup, Input, PageHeader, Grid, Spinner, Badge, Divider, Code } from '../../components/UI';

export default function AdmissionTrackingPage() {
  const [searchParams] = useSearchParams();
  const [token, setToken] = useState(searchParams.get('token') || '');
  const [data, setData] = useState<any>(null);

  const track = useMutation({
    mutationFn: (token: string) => api.post('/admissions/track', { tracking_token: token }).then(r => r.data),
    onSuccess: (res) => setData(res),
  });

  useEffect(() => {
    if (token && !data) {
      track.mutate(token);
    }
  }, []);

  const getStatusColor = (status: string) => {
    const colors: any = {
      submitted: 'blue',
      under_review: 'amber',
      accepted: 'green',
      rejected: 'red',
      draft: 'gray',
      enrollment_pending: 'purple',
      enrolled: 'green'
    };
    return colors[status] || 'blue';
  };

  return (
    <div style={{ minHeight: '100vh', background: '#f8fafc', padding: '60px 20px' }}>
      <div style={{ maxWidth: 800, margin: '0 auto' }}>
        <div style={{ textAlign: 'center', marginBottom: 40 }}>
          <img src="/logo.svg" alt="DGI" style={{ width: 60, height: 60, marginBottom: 20 }} />
          <h1 style={{ fontSize: 28, fontWeight: 800, color: '#0f172a', letterSpacing: '-.5px' }}>Track Application</h1>
          <p style={{ color: '#64748b', fontSize: 14 }}>Enter your tracking token to view your application status</p>
        </div>

        {!data ? (
          <Card style={{ maxWidth: 460, margin: '0 auto', border: 'none', boxShadow: '0 10px 30px rgba(0,0,0,.04)' }}>
            <CardBody style={{ padding: 40 }}>
              <FormGroup label="Tracking Token">
                <Input 
                  placeholder="DGI-XXXXXXXX" 
                  value={token} 
                  onChange={e => setToken(e.target.value.toUpperCase())}
                  style={{ textAlign: 'center', fontSize: 20, fontWeight: 700, letterSpacing: '2px', height: 56, textTransform: 'uppercase' }}
                />
              </FormGroup>
              <Btn 
                style={{ width: '100%', height: 52, background: '#0f3d22', fontSize: 15, fontWeight: 600, marginTop: 12 }} 
                onClick={() => track.mutate(token)}
                loading={track.isPending}
              >
                Find Application
              </Btn>
              
              <div style={{ marginTop: 32, textAlign: 'center' }}>
                <Link to="/admissions" style={{ color: '#64748b', fontSize: 13, textDecoration: 'none' }}>
                  ← Back to Admissions
                </Link>
              </div>
            </CardBody>
          </Card>
        ) : (
          <div style={{ animation: 'fadeUp .3s ease' }}>
            <Grid cols={3} style={{ marginBottom: 24 }}>
              <Card style={{ border: 'none', boxShadow: '0 4px 12px rgba(0,0,0,.03)' }}>
                <CardBody style={{ textAlign: 'center', padding: 24 }}>
                  <div style={{ fontSize: 11, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 8 }}>Status</div>
                  <Badge variant={getStatusColor(data.application.status)} style={{ fontSize: 12, px: 12, py: 4 }}>
                    {data.application.status.replace('_', ' ')}
                  </Badge>
                </CardBody>
              </Card>
              <Card style={{ border: 'none', boxShadow: '0 4px 12px rgba(0,0,0,.03)' }}>
                <CardBody style={{ textAlign: 'center', padding: 24 }}>
                  <div style={{ fontSize: 11, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 8 }}>App Number</div>
                  <div style={{ fontSize: 15, fontWeight: 800, color: '#0f172a' }}>{data.application.application_number}</div>
                </CardBody>
              </Card>
              <Card style={{ border: 'none', boxShadow: '0 4px 12px rgba(0,0,0,.03)' }}>
                <CardBody style={{ textAlign: 'center', padding: 24 }}>
                  <div style={{ fontSize: 11, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 8 }}>Progress</div>
                  <div style={{ fontSize: 15, fontWeight: 800, color: '#1a6b3c' }}>{data.application.progress_percentage}%</div>
                </CardBody>
              </Card>
            </Grid>

            <Card style={{ border: 'none', boxShadow: '0 10px 30px rgba(0,0,0,.04)', marginBottom: 24 }}>
              <CardBody style={{ padding: 40 }}>
                <h3 style={{ fontSize: 18, fontWeight: 800, color: '#0f172a', marginBottom: 32 }}>Application Timeline</h3>
                <div style={{ position: 'relative' }}>
                  {data.notifications.map((n: any, i: number) => (
                    <div key={n.id} style={{ display: 'flex', gap: 24, marginBottom: 32, position: 'relative' }}>
                      {i !== data.notifications.length - 1 && (
                        <div style={{ position: 'absolute', left: 7, top: 24, bottom: -32, width: 2, background: '#f1f5f9' }} />
                      )}
                      <div style={{ 
                        width: 16, height: 16, borderRadius: '50%', flexShrink: 0, marginTop: 4, zIndex: 1,
                        background: i === 0 ? '#1a6b3c' : '#e2e8f0',
                        boxShadow: i === 0 ? '0 0 0 4px #f0faf4' : 'none'
                      }} />
                      <div>
                        <div style={{ fontSize: 14, fontWeight: 700, color: '#0f172a' }}>{n.title}</div>
                        <div style={{ fontSize: 13, color: '#64748b', marginTop: 4, lineHeight: 1.5 }}>{n.message}</div>
                        <div style={{ fontSize: 11, color: '#cbd5e1', marginTop: 8, fontWeight: 600 }}>{new Date(n.created_at).toLocaleString()}</div>
                      </div>
                    </div>
                  ))}
                  {data.notifications.length === 0 && (
                    <div style={{ textAlign: 'center', py: 20, color: '#94a3b8' }}>No updates yet.</div>
                  )}
                </div>
              </CardBody>
            </Card>

            <div style={{ display: 'flex', justifyContent: 'center', gap: 16 }}>
              <Btn variant="outline" onClick={() => setData(null)} style={{ px: 24 }}>Track Different ID</Btn>
              {data.application.status === 'draft' && (
                <Link to={`/admissions/continue/${data.application.tracking_token}`} style={{ textDecoration: 'none' }}>
                  <Btn style={{ background: '#1a6b3c', px: 24 }}>Resume Application</Btn>
                </Link>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
