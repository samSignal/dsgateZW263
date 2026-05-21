import React from 'react';
import { Link } from 'react-router-dom';
import { Btn, Card, CardBody, PageHeader, Grid, StatCard } from '../../components/UI';

export default function AdmissionLandingPage() {
  return (
    <div style={{ minHeight: '100vh', background: '#f8fafc', paddingBottom: 60 }}>
      {/* Solid Green Header Line */}
      <div style={{ height: 6, background: '#1a6b3c' }} />
      
      <div style={{ maxWidth: 1000, margin: '0 auto', padding: '40px 20px' }}>
        <div style={{ textAlign: 'center', marginBottom: 48 }}>
          <div style={{ 
            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
            width: 80, height: 80, background: '#fff', borderRadius: 20,
            boxShadow: '0 10px 25px rgba(0,0,0,.05)', marginBottom: 24,
            border: '1px solid #e2e8f0'
          }}>
            <img src="/logo.svg" alt="DestinyGate" style={{ width: 50, height: 50 }} />
          </div>
          <h1 style={{ fontSize: 32, fontWeight: 800, color: '#0f172a', letterSpacing: '-.8px', marginBottom: 12 }}>
            Public Admissions Portal
          </h1>
          <p style={{ fontSize: 16, color: '#64748b', maxWidth: 600, margin: '0 auto', lineHeight: 1.6 }}>
            Welcome to DestinyGate Institute. Our professional online admission system 
            streamlines your journey from application to enrollment.
          </p>
        </div>

        <Grid cols={2} style={{ gap: 24, marginBottom: 32 }}>
          <Card style={{ padding: 0, border: '1.5px solid #e2e8f0' }}>
            <div style={{ height: 160, background: '#1a6b3c', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 64 }}>
              🎓
            </div>
            <CardBody style={{ padding: 32 }}>
              <h2 style={{ fontSize: 20, fontWeight: 700, color: '#0f172a', marginBottom: 12 }}>New Student Intake</h2>
              <p style={{ fontSize: 14, color: '#64748b', lineHeight: 1.6, marginBottom: 28, minHeight: 66 }}>
                For students currently in Grade 7 applying for Form 1, or those starting high school for the first time.
              </p>
              <Link to="/admissions/apply" style={{ textDecoration: 'none' }}>
                <Btn style={{ width: '100%', height: 48, background: '#1a6b3c', fontWeight: 600 }}>
                  Start Application
                </Btn>
              </Link>
            </CardBody>
          </Card>

          <Card style={{ padding: 0, border: '1.5px solid #e2e8f0' }}>
            <div style={{ height: 160, background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 64 }}>
              🔄
            </div>
            <CardBody style={{ padding: 32 }}>
              <h2 style={{ fontSize: 20, fontWeight: 700, color: '#0f172a', marginBottom: 12 }}>Transfer Student</h2>
              <p style={{ fontSize: 14, color: '#64748b', lineHeight: 1.6, marginBottom: 28, minHeight: 66 }}>
                For students transferring from another high school to join Form 1 through Form 6.
              </p>
              <Link to="/admissions/apply?type=transfer" style={{ textDecoration: 'none' }}>
                <Btn variant="outline" style={{ width: '100%', height: 48, borderColor: '#1a6b3c', color: '#1a6b3c', fontWeight: 600 }}>
                  Begin Transfer
                </Btn>
              </Link>
            </CardBody>
          </Card>
        </Grid>

        <div style={{ marginBottom: 48 }}>
          <Card style={{ border: '1.5px dashed #cbd5e1', background: '#f1f5f9/30' }}>
            <CardBody style={{ display: 'flex', alignItems: 'center', gap: 24, padding: 32 }}>
              <div style={{ fontSize: 40 }}>🔍</div>
              <div style={{ flex: 1 }}>
                <h3 style={{ fontSize: 18, fontWeight: 700, color: '#0f172a', marginBottom: 4 }}>Already Applied?</h3>
                <p style={{ fontSize: 14, color: '#64748b' }}>Track your progress or continue a saved draft using your tracking token.</p>
              </div>
              <div style={{ display: 'flex', gap: 12 }}>
                <Link to="/admissions/track" style={{ textDecoration: 'none' }}>
                  <Btn variant="outline" style={{ height: 44, borderColor: '#1a6b3c', color: '#1a6b3c' }}>Track Progress</Btn>
                </Link>
                <Link to="/admissions/continue" style={{ textDecoration: 'none' }}>
                  <Btn variant="outline" style={{ height: 44, borderColor: '#1a6b3c', color: '#1a6b3c' }}>Resume Draft</Btn>
                </Link>
              </div>
            </CardBody>
          </Card>
        </div>

        <div style={{ textAlign: 'center' }}>
          <h3 style={{ fontSize: 13, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '1px', marginBottom: 24 }}>
            Required Documentation
          </h3>
          <div style={{ display: 'flex', justifyContent: 'center', gap: 40, flexWrap: 'wrap' }}>
            {[
              { label: 'Birth Certificate', icon: '📄' },
              { label: 'Passport Photo', icon: '🖼️' },
              { label: 'Latest Reports', icon: '📊' },
              { label: 'Transfer Letter', icon: '✉️' },
            ].map((doc) => (
              <div key={doc.label} style={{ textAlign: 'center' }}>
                <div style={{ fontSize: 24, marginBottom: 8 }}>{doc.icon}</div>
                <div style={{ fontSize: 12, fontWeight: 600, color: '#475569' }}>{doc.label}</div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
