import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../lib/api';
import type { User } from '../types';

interface Props { onLogin: (token: string, user: User) => void }

function dashboardPath(role: string): string {
  const map: Record<string, string> = {
    admin: '/app/admin', headmaster: '/app/headmaster',
    teacher: '/app/teacher', bursar: '/app/bursar',
    parent: '/app/parent', student: '/app/student',
  };
  return map[role] ?? '/app/admin';
}

/* ── Small inline icon set (feather-style strokes, matches rest of app) ── */
const I = {
  gradCap: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d="M22 10 12 5 2 10l10 5 10-5Z" /><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5" /><path d="M22 10v6" />
    </svg>
  ),
  shieldCheck: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d="M12 2 4 5v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V5l-8-3Z" /><path d="M9 12l2 2 4-4" />
    </svg>
  ),
  users: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
    </svg>
  ),
  user: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" />
    </svg>
  ),
  lock: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </svg>
  ),
  eye: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" /><circle cx="12" cy="12" r="3" />
    </svg>
  ),
  eyeOff: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
      <line x1="1" y1="1" x2="23" y2="23" />
    </svg>
  ),
  arrowRight: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
      <line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" />
    </svg>
  ),
  phone: (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z" />
    </svg>
  ),
  tv: (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="2" y="7" width="20" height="15" rx="2" /><polyline points="17 2 12 7 7 2" />
    </svg>
  ),
  globe: (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10" /><line x1="2" y1="12" x2="22" y2="12" />
      <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z" />
    </svg>
  ),
  shieldSmall: (
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M12 2 4 5v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V5l-8-3Z" />
    </svg>
  ),
};

function GoogleIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18">
      <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.7-1.57 2.68-3.88 2.68-6.62Z" />
      <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.33A9 9 0 0 0 9 18Z" />
      <path fill="#FBBC05" d="M3.97 10.72A5.4 5.4 0 0 1 3.68 9c0-.6.1-1.18.29-1.72V4.95H.96A9 9 0 0 0 0 9c0 1.45.35 2.83.96 4.05l3.01-2.33Z" />
      <path fill="#EA4335" d="M9 3.58c1.32 0 2.51.46 3.44 1.35l2.59-2.59C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.95l3.01 2.33C4.68 5.16 6.66 3.58 9 3.58Z" />
    </svg>
  );
}

function MicrosoftIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 16 16">
      <rect x="0" y="0" width="7.2" height="7.2" fill="#F25022" /><rect x="8.8" y="0" width="7.2" height="7.2" fill="#7FBA00" />
      <rect x="0" y="8.8" width="7.2" height="7.2" fill="#00A4EF" /><rect x="8.8" y="8.8" width="7.2" height="7.2" fill="#FFB900" />
    </svg>
  );
}

export default function Login({ onLogin }: Props) {
  const navigate = useNavigate();
  const [login, setLogin]         = useState('');
  const [password, setPassword]   = useState('');
  const [showPassword, setShowPw] = useState(false);
  const [error, setError]         = useState('');
  const [loading, setLoading]     = useState(false);
  const [forgotMsg, setForgotMsg] = useState('');
  const [forgotBusy, setForgotBusy] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const { data } = await api.post('/login', { login, password });
      onLogin(data.token, data.user);
      navigate(dashboardPath(data.user.role), { replace: true });
    } catch (err: any) {
      setError(err.response?.data?.message ?? err.response?.data?.errors?.login?.[0] ?? 'Invalid credentials.');
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = async () => {
    setForgotMsg('');
    if (!login.trim()) {
      setForgotMsg('Enter your email above first, then click "Forgot password?" again.');
      return;
    }
    setForgotBusy(true);
    try {
      await api.post('/forgot-password', { email: login });
      setForgotMsg('If that email is registered, a reset link has been sent.');
    } catch {
      setForgotMsg('If that email is registered, a reset link has been sent.');
    } finally {
      setForgotBusy(false);
    }
  };

  const features = [
    { icon: I.gradCap,      title: 'Quality Education', sub: 'For a brighter future',  bg: '#c9a227' },
    { icon: I.shieldCheck,  title: 'Godly Values',       sub: 'Building strong character', bg: '#c9a227' },
    { icon: I.users,        title: 'Confident Leaders',  sub: 'Empowered to excel',    bg: '#1a6b3c' },
  ];

  return (
    <div style={{
      minHeight: '100vh', width: '100%', position: 'relative',
      display: 'flex', flexDirection: 'column', overflow: 'hidden',
      fontFamily: "'Inter', -apple-system, sans-serif",
      background: `linear-gradient(100deg, rgba(6,12,3,.72) 0%, rgba(9,28,13,.42) 32%, rgba(9,28,13,.18) 48%, rgba(9,28,13,.30) 100%), url(/backgroundpic.jpg) center/cover no-repeat fixed`,
    }}>
      {/* decorative top-left curved swoosh */}
      <div style={{
        position: 'absolute', top: '-18vh', left: '-14vw', width: '46vw', minWidth: 420, height: '90vh',
        borderRadius: '50%',
        background: 'radial-gradient(ellipse at 65% 65%, rgba(219,234,254,.28), rgba(147,197,253,.06) 60%, transparent 75%)',
        pointerEvents: 'none', zIndex: 0,
      }} />
      {/* decorative gold diagonal accents */}
      <div style={{ position: 'absolute', top: '4%', right: '6%', width: 160, height: 2, background: 'linear-gradient(90deg, transparent, #EAC445, transparent)', transform: 'rotate(18deg)', pointerEvents: 'none' }} />
      <div style={{ position: 'absolute', bottom: '13%', left: '2%', width: '58%', height: 3, background: 'linear-gradient(90deg, transparent, #EAC445, transparent)', transform: 'rotate(-3deg)', pointerEvents: 'none' }} />

      {/* ── Main content ── */}
      <div className="dgi-login-main" style={{
        flex: 1, display: 'flex', alignItems: 'center', gap: 40,
        padding: '52px 64px 32px', position: 'relative', zIndex: 1, flexWrap: 'wrap',
      }}>

        {/* ── LEFT: brand panel ── */}
        <div style={{ flex: '1 1 460px', color: '#fff', maxWidth: 620 }}>
          <img
            src="/logo-full.png" alt="DestinyGate Institute"
            style={{ width: 150, height: 150, objectFit: 'contain', marginBottom: 14, filter: 'drop-shadow(0 10px 22px rgba(0,0,0,.5))' }}
          />

          <h1 style={{ fontSize: 'clamp(30px,4vw,44px)', fontWeight: 800, lineHeight: 1.05, letterSpacing: '-.5px', margin: 0, textShadow: '0 2px 12px rgba(0,0,0,.4)' }}>
            <span style={{ color: '#fff' }}>DestinyGate</span><br />
            <span style={{ color: '#EAC445' }}>Institute</span>
          </h1>
          <div style={{ width: 54, height: 4, background: '#EAC445', borderRadius: 2, margin: '14px 0' }} />
          <p style={{ fontSize: 16, color: '#fff', maxWidth: 380, lineHeight: 1.5, marginBottom: 26, textShadow: '0 1px 8px rgba(0,0,0,.55)' }}>
            Raising a Godly, Skilled and Confident Generation.
          </p>

          {/* Feature list */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 14, marginBottom: 22 }}>
            {features.map(f => (
              <div key={f.title} style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                <div style={{
                  width: 40, height: 40, borderRadius: '50%', flexShrink: 0,
                  background: f.bg, boxShadow: '0 3px 10px rgba(0,0,0,.35)',
                  color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}>
                  {f.icon}
                </div>
                <div>
                  <div style={{ fontSize: 14, fontWeight: 700, color: '#fff', textShadow: '0 1px 6px rgba(0,0,0,.5)' }}>{f.title}</div>
                  <div style={{ fontSize: 12, color: 'rgba(255,255,255,.85)', textShadow: '0 1px 6px rgba(0,0,0,.5)' }}>{f.sub}</div>
                </div>
              </div>
            ))}
          </div>

          {/* Discipline quote box */}
          <div style={{
            display: 'flex', alignItems: 'stretch', gap: 16,
            background: 'rgba(5,15,8,.55)', border: '1px solid rgba(234,196,69,.35)',
            borderRadius: 12, padding: '16px 20px', maxWidth: 560, backdropFilter: 'blur(2px)',
          }}>
            <div style={{ flex: 1 }}>
              <div style={{ fontSize: 13, fontStyle: 'italic', color: '#EAC445', fontWeight: 600 }}>Walk the talk,</div>
              <div style={{ fontSize: 14, fontWeight: 800, color: '#fff', letterSpacing: '.2px' }}>EVEN WHEN NOBODY IS WATCHING.</div>
            </div>
            <div style={{ width: 1, background: 'rgba(255,255,255,.2)' }} />
            <div style={{ flex: 1, display: 'flex', alignItems: 'center', gap: 10 }}>
              <span style={{ color: '#EAC445', flexShrink: 0 }}>{I.users}</span>
              <div>
                <div style={{ fontSize: 12, fontWeight: 700, color: '#fff' }}>DISCIPLINE TODAY,</div>
                <div style={{ fontSize: 12, fontWeight: 700, color: '#fff' }}>SUCCESS TOMORROW.</div>
              </div>
            </div>
          </div>
        </div>

        {/* ── RIGHT: login card ── */}
        <div style={{ flex: '0 1 440px', position: 'relative', width: '100%', maxWidth: 440 }}>
          {/* HUMANA IMPORTA medallion */}
          <div style={{ position: 'absolute', top: -26, right: 18, zIndex: 3 }}>
            <div style={{
              width: 84, height: 84, borderRadius: '50%',
              background: 'radial-gradient(circle at 35% 30%, #F7E081, #EAC445 55%, #c9a227 100%)',
              border: '3px solid #fff', boxShadow: '0 10px 24px rgba(0,0,0,.3)',
              display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center',
            }}>
              <span style={{ fontSize: 9, letterSpacing: '.3px', color: '#123918' }}>★★★</span>
              <span style={{ fontSize: 8, fontWeight: 800, color: '#123918', letterSpacing: '.4px', lineHeight: 1.15 }}>HUMANA</span>
              <span style={{ fontSize: 8, fontWeight: 800, color: '#123918', letterSpacing: '.4px', lineHeight: 1.15 }}>IMPORTA</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'center', gap: 3, marginTop: -4 }}>
              <div style={{ width: 13, height: 22, background: '#8a1f1f', clipPath: 'polygon(0 0,100% 0,100% 100%,50% 72%,0 100%)', transform: 'rotate(-10deg)' }} />
              <div style={{ width: 13, height: 22, background: '#8a1f1f', clipPath: 'polygon(0 0,100% 0,100% 100%,50% 72%,0 100%)', transform: 'rotate(10deg)' }} />
            </div>
          </div>

          <div style={{
            background: '#fff', borderRadius: 22, padding: '34px 32px 28px',
            boxShadow: '0 30px 70px rgba(0,0,0,.35)', position: 'relative', zIndex: 2,
          }}>
            <h2 style={{ fontSize: 22, fontWeight: 800, color: '#0f172a', margin: 0 }}>Welcome Back! <span>👋</span></h2>
            <p style={{ fontSize: 13, color: '#64748b', marginTop: 4, marginBottom: 22 }}>Sign in to access your portal</p>

            {error && (
              <div style={{
                background: '#fef2f2', color: '#991b1b', border: '1px solid #fecaca',
                borderRadius: 8, padding: '10px 14px', fontSize: 13, marginBottom: 16,
              }}>
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div style={{ marginBottom: 16 }}>
                <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#374151', marginBottom: 6 }}>
                  Email / Student Number / Phone
                </label>
                <div style={{ position: 'relative' }}>
                  <span style={{ position: 'absolute', left: 13, top: '50%', transform: 'translateY(-50%)', color: '#1a6b3c' }}>{I.user}</span>
                  <input
                    id="dgi-login-user"
                    type="text" value={login}
                    onChange={e => setLogin(e.target.value)}
                    required placeholder="Email, student number, or phone"
                    style={{
                      width: '100%', padding: '11px 14px 11px 38px',
                      border: '1.5px solid #e2e8f0', borderRadius: 10,
                      fontSize: 13, color: '#0f172a', background: '#f9fafb',
                      transition: 'border-color .15s', boxSizing: 'border-box',
                    }}
                  />
                </div>
              </div>

              <div style={{ marginBottom: 10 }}>
                <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#374151', marginBottom: 6 }}>
                  Password
                </label>
                <div style={{ position: 'relative' }}>
                  <span style={{ position: 'absolute', left: 13, top: '50%', transform: 'translateY(-50%)', color: '#1a6b3c' }}>{I.lock}</span>
                  <input
                    type={showPassword ? 'text' : 'password'} value={password}
                    onChange={e => setPassword(e.target.value)}
                    required placeholder="Enter your password"
                    style={{
                      width: '100%', padding: '11px 40px 11px 38px',
                      border: '1.5px solid #e2e8f0', borderRadius: 10,
                      fontSize: 13, color: '#0f172a', background: '#f9fafb',
                      transition: 'border-color .15s', boxSizing: 'border-box',
                    }}
                  />
                  <button
                    type="button" onClick={() => setShowPw(s => !s)}
                    style={{ position: 'absolute', right: 12, top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', cursor: 'pointer', color: '#9ca3af', display: 'flex' }}
                  >
                    {showPassword ? I.eyeOff : I.eye}
                  </button>
                </div>
              </div>

              <div style={{ textAlign: 'right', marginBottom: 8 }}>
                <button type="button" disabled={forgotBusy} onClick={handleForgotPassword} style={{ background: 'none', border: 'none', color: '#1a6b3c', fontSize: 12, fontWeight: 600, cursor: forgotBusy ? 'wait' : 'pointer' }}>
                  {forgotBusy ? 'Sending…' : 'Forgot password?'}
                </button>
              </div>
              {forgotMsg && (
                <div style={{ fontSize: 11.5, color: '#0f3d22', background: '#f0faf4', border: '1px solid #c6f0d8', borderRadius: 8, padding: '8px 12px', marginBottom: 16, textAlign: 'right' }}>
                  {forgotMsg}
                </div>
              )}
              {!forgotMsg && <div style={{ marginBottom: 12 }} />}

              <button
                type="submit" disabled={loading}
                style={{
                  width: '100%', padding: '13px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                  background: loading ? '#2d8a52' : '#1a6b3c',
                  color: '#fff', border: 'none', borderRadius: 10,
                  fontSize: 14, fontWeight: 700, cursor: loading ? 'not-allowed' : 'pointer',
                  transition: 'background .15s', letterSpacing: '.2px',
                }}
              >
                {loading ? 'Signing in…' : 'Sign In'} {!loading && I.arrowRight}
              </button>
            </form>

            {/* Divider */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, margin: '20px 0 14px' }}>
              <div style={{ flex: 1, height: 1, background: '#e5e7eb' }} />
              <span style={{ fontSize: 11, color: '#9ca3af' }}>Or continue with</span>
              <div style={{ flex: 1, height: 1, background: '#e5e7eb' }} />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 8 }}>
              <button type="button" disabled title="Not yet configured" style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 5, padding: '10px 4px', border: '1px solid #e5e7eb', borderRadius: 10, background: '#fff', cursor: 'not-allowed', opacity: .55 }}>
                <GoogleIcon /><span style={{ fontSize: 10.5, color: '#374151', fontWeight: 500 }}>Google</span>
              </button>
              <button type="button" disabled title="Not yet configured" style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 5, padding: '10px 4px', border: '1px solid #e5e7eb', borderRadius: 10, background: '#fff', cursor: 'not-allowed', opacity: .55 }}>
                <MicrosoftIcon /><span style={{ fontSize: 10.5, color: '#374151', fontWeight: 500 }}>Microsoft</span>
              </button>
              <button
                type="button"
                onClick={() => { document.getElementById('dgi-login-user')?.focus(); }}
                style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 5, padding: '10px 4px', border: '1px solid #e5e7eb', borderRadius: 10, background: '#fff', cursor: 'pointer', color: '#374151' }}
              >
                {I.users}<span style={{ fontSize: 10.5, fontWeight: 500 }}>Staff / Admin</span>
              </button>
            </div>

            <p style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 5, fontSize: 11, color: '#94a3b8', marginTop: 18 }}>
              <span style={{ color: '#1a6b3c' }}>{I.shieldSmall}</span> Secure login · Your information is protected
            </p>

            <div style={{ display: 'flex', justifyContent: 'center', gap: 14, marginTop: 14, flexWrap: 'wrap' }}>
              <a href="/applications/create" style={{ fontSize: 11, color: '#1a6b3c', fontWeight: 600, textDecoration: 'none' }}>Apply for Admission</a>
              <a href="/applications/resume" style={{ fontSize: 11, color: '#64748b', fontWeight: 600, textDecoration: 'none' }}>Resume Application</a>
              <a href="/applications/track" style={{ fontSize: 11, color: '#64748b', fontWeight: 600, textDecoration: 'none' }}>Track Application</a>
            </div>
          </div>
        </div>
      </div>

      {/* ── Footer bar ── */}
      <div className="dgi-login-footer" style={{
        position: 'relative', zIndex: 1, background: '#0a2312',
        borderTop: '1px solid rgba(234,196,69,.3)', padding: '16px 64px',
        display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 16,
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 24, flexWrap: 'wrap' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <span style={{ width: 32, height: 32, borderRadius: '50%', background: '#EAC445', color: '#123918', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>{I.phone}</span>
            <div>
              <div style={{ fontSize: 11, color: '#EAC445', fontWeight: 700 }}>Call Us</div>
              <div style={{ fontSize: 11, color: 'rgba(255,255,255,.75)' }}>077 967 2246 | 071 041 5364</div>
            </div>
          </div>
          <div style={{ width: 1, height: 28, background: 'rgba(255,255,255,.18)' }} />
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <span style={{ width: 32, height: 32, borderRadius: '50%', background: '#EAC445', color: '#123918', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>{I.tv}</span>
            <div>
              <div style={{ fontSize: 11, color: '#EAC445', fontWeight: 700 }}>DGI TV</div>
              <div style={{ fontSize: 11, color: 'rgba(255,255,255,.75)' }}>Empowering Through Information</div>
            </div>
          </div>
          <div style={{ width: 1, height: 28, background: 'rgba(255,255,255,.18)' }} />
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <span style={{ width: 32, height: 32, borderRadius: '50%', background: '#EAC445', color: '#123918', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>{I.globe}</span>
            <div>
              <div style={{ fontSize: 11, color: '#EAC445', fontWeight: 700 }}>Visit Our Website</div>
              <div style={{ fontSize: 11, color: 'rgba(255,255,255,.75)' }}>www.destinygate.ac.zw</div>
            </div>
          </div>
        </div>
        <div style={{ fontSize: 11, color: 'rgba(255,255,255,.65)', textAlign: 'right' }}>
          © {new Date().getFullYear()} DestinyGate Institute<br />Masvingo, Zimbabwe
        </div>
      </div>

      <style>{`
        @media (max-width: 900px) {
          .dgi-login-main { padding: 32px 24px 24px !important; }
          .dgi-login-footer { padding: 16px 24px !important; justify-content: center !important; text-align: center; }
        }
      `}</style>
    </div>
  );
}
