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

export default function Login({ onLogin }: Props) {
  const navigate = useNavigate();
  const [login, setLogin]       = useState('');
  const [password, setPassword] = useState('');
  const [error, setError]       = useState('');
  const [loading, setLoading]   = useState(false);

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

  return (
    <div style={{
      display: 'flex', height: '100vh', width: '100vw',
      background: '#0f3d22', // Primary green theme
      fontFamily: "'Inter', sans-serif",
      overflow: 'hidden',
    }}>
      {/* ── Left panel ── */}
      <div style={{
        flex: '0 0 420px',
        background: '#fff',
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        padding: '48px 52px',
        position: 'relative',
        zIndex: 1,
        boxShadow: '4px 0 24px rgba(0,0,0,.06)',
      }}>
        {/* Logo */}
        <div style={{ marginBottom: 40 }}>
          <div style={{
            width: 72, height: 72,
            background: '#f0faf4',
            borderRadius: 16,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            marginBottom: 20,
            border: '1px solid #d1fae5',
          }}>
            <img src="/logo-mark.png" alt="DestinyGate Institute" style={{ width: 52, height: 52, objectFit: 'contain' }} />
          </div>
          <h1 style={{ fontSize: 22, fontWeight: 800, color: '#0f172a', letterSpacing: '-.4px', marginBottom: 4 }}>
            DestinyGate Institute
          </h1>
          <p style={{ fontSize: 12, color: '#94a3b8', fontStyle: 'italic' }}>
            "Raising a Godly, Skilled and Confident Generation"
          </p>
        </div>

        {/* Form */}
        <div>
          <h2 style={{ fontSize: 18, fontWeight: 700, color: '#0f172a', marginBottom: 4 }}>Welcome back</h2>
          <p style={{ fontSize: 13, color: '#64748b', marginBottom: 28 }}>Sign in to your portal</p>

          {error && (
            <div style={{
              background: '#fef2f2', color: '#991b1b',
              border: '1px solid #fecaca', borderRadius: 8,
              padding: '10px 14px', fontSize: 13, marginBottom: 20,
            }}>
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <div style={{ marginBottom: 16 }}>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#374151', marginBottom: 6, letterSpacing: '.2px' }}>
                Email / Student Number / Phone
              </label>
              <input
                type="text" value={login}
                onChange={e => setLogin(e.target.value)}
                required placeholder="Email, student number, or phone"
                style={{
                  width: '100%', padding: '10px 14px',
                  border: '1.5px solid #e2e8f0', borderRadius: 8,
                  fontSize: 13, color: '#0f172a', background: '#fff',
                  transition: 'border-color .15s', boxSizing: 'border-box',
                }}
              />
            </div>

            <div style={{ marginBottom: 28 }}>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#374151', marginBottom: 6, letterSpacing: '.2px' }}>
                Password
              </label>
              <input
                type="password" value={password}
                onChange={e => setPassword(e.target.value)}
                required placeholder="••••••••"
                style={{
                  width: '100%', padding: '10px 14px',
                  border: '1.5px solid #e2e8f0', borderRadius: 8,
                  fontSize: 13, color: '#0f172a', background: '#fff',
                  transition: 'border-color .15s', boxSizing: 'border-box',
                }}
              />
            </div>

            <button
              type="submit" disabled={loading}
              style={{
                width: '100%', padding: '11px',
                background: loading ? '#2d8a52' : '#1a6b3c',
                color: '#fff', border: 'none', borderRadius: 8,
                fontSize: 14, fontWeight: 700, cursor: loading ? 'not-allowed' : 'pointer',
                transition: 'background .15s', letterSpacing: '.2px',
              }}
            >
              {loading ? 'Signing in…' : 'Sign in'}
            </button>
          </form>

          {/* Demo hint */}
          <div style={{
            marginTop: 28, padding: '14px 16px',
            background: '#f8fafc', borderRadius: 8,
            border: '1px solid #e2e8f0',
          }}>
            <p style={{ fontSize: 11, fontWeight: 700, color: '#374151', marginBottom: 6, textTransform: 'uppercase', letterSpacing: '.5px' }}>Demo accounts</p>
            {[
              ['Admin',      'admin@destinygate.ac.zw'],
              ['Headmaster', 'headmaster@destinygate.ac.zw'],
              ['Bursar',     'bursar@destinygate.ac.zw'],
              ['Teacher',    'tmutasa@destinygate.ac.zw'],
              ['Parent',     'jdube@gmail.com'],
            ].map(([role, em]) => (
              <div key={role} style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 3 }}>
                <span style={{ fontSize: 11, color: '#64748b', fontWeight: 500 }}>{role}</span>
                <button
                  type="button"
                  onClick={() => { setLogin(em); setPassword('password'); }}
                  style={{ fontSize: 11, color: '#1a6b3c', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 600 }}
                >
                  {em}
                </button>
              </div>
            ))}
            <p style={{ fontSize: 11, color: '#94a3b8', marginTop: 6 }}>Password: <strong style={{ color: '#374151' }}>password</strong></p>
          </div>
        </div>

        <p style={{ marginTop: 32, fontSize: 11, color: '#cbd5e1', textAlign: 'center' }}>
          © {new Date().getFullYear()} DestinyGate Institute · Masvingo, Zimbabwe
        </p>
      </div>

      {/* ── Right panel ── */}
      <div style={{
        flex: 1,
        background: 'linear-gradient(135deg, #0f3d22 0%, #2d8a52 100%)',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'flex-start',
        padding: 60,
        position: 'relative',
        overflowY: 'auto',
        overflowX: 'hidden',
      }}>
        {/* Background pattern */}
        <div style={{
          position: 'absolute', inset: 0,
          backgroundImage: 'radial-gradient(circle at 20% 20%, rgba(255,255,255,.04) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(255,255,255,.04) 0%, transparent 50%)',
          pointerEvents: 'none',
        }} />

        {/* Content container */}
        <div style={{ position: 'relative', zIndex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', width: '100%', maxWidth: 480 }}>
          {/* Big logo */}
          <div style={{
            width: 180, height: 180,
            background: 'rgba(255,255,255,.95)',
            borderRadius: 28,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            marginBottom: 36,
            boxShadow: '0 24px 64px rgba(0,0,0,.25)',
            padding: 16,
          }}>
            <img src="/logo-full.png" alt="DestinyGate Institute" style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
          </div>

          <h2 style={{ color: '#fff', fontSize: 28, fontWeight: 800, textAlign: 'center', marginBottom: 12, letterSpacing: '-.4px' }}>
            Comprehensive School Management System
          </h2>
          <p style={{ color: 'rgba(255,255,255,.65)', fontSize: 14, textAlign: 'center', maxWidth: 360, lineHeight: 1.7, marginBottom: 40 }}>
            Elegant school management for the modern institution — a complete platform for managing students, staff, finances, academics, and school communications.
          </p>

          {/* Login method cards */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10, width: '100%', maxWidth: 360 }}>
            {[
              { icon: '🧑‍💼', title: 'Staff & Admin', desc: 'Login with your email address', color: '#4ade80' },
              { icon: '🎓', title: 'Students',      desc: 'Login with Student Number (e.g. D0261234A)', color: '#60a5fa' },
              { icon: '👨‍👩‍👧', title: 'Parents',       desc: 'Login with your phone number', color: '#fbbf24' },
            ].map(m => (
              <div key={m.title} style={{
                display: 'flex', alignItems: 'center', gap: 14,
                padding: '12px 16px',
                background: 'rgba(255,255,255,.08)',
                border: '1px solid rgba(255,255,255,.15)',
                borderRadius: 10,
                backdropFilter: 'blur(4px)',
              }}>
                <span style={{ fontSize: 22, flexShrink: 0 }}>{m.icon}</span>
                <div>
                  <div style={{ fontSize: 13, fontWeight: 700, color: m.color }}>{m.title}</div>
                  <div style={{ fontSize: 12, color: 'rgba(255,255,255,.6)' }}>{m.desc}</div>
                </div>
              </div>
            ))}
          </div>

          <a
            href="/applications/create"
            style={{
              marginTop: 18,
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              width: '100%',
              maxWidth: 360,
              padding: '12px 16px',
              background: 'rgba(255,255,255,.95)',
              color: '#0f3d22',
              border: '1px solid rgba(255,255,255,.35)',
              borderRadius: 10,
              fontSize: 13,
              fontWeight: 800,
              textDecoration: 'none',
              letterSpacing: '.2px',
              boxShadow: '0 10px 30px rgba(0,0,0,.18)',
            }}
          >
            Online Application
          </a>

          <a
            href="/applications/resume"
            style={{
              marginTop: 10,
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              width: '100%',
              maxWidth: 360,
              padding: '12px 16px',
              background: 'rgba(255,255,255,.10)',
              color: 'rgba(255,255,255,.92)',
              border: '1px solid rgba(255,255,255,.22)',
              borderRadius: 10,
              fontSize: 13,
              fontWeight: 800,
              textDecoration: 'none',
              letterSpacing: '.2px',
              backdropFilter: 'blur(4px)',
            }}
          >
            Resume Application
          </a>

          <a
            href="/applications/track"
            style={{
              marginTop: 10,
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              width: '100%',
              maxWidth: 360,
              padding: '12px 16px',
              background: 'rgba(255,255,255,.10)',
              color: 'rgba(255,255,255,.92)',
              border: '1px solid rgba(255,255,255,.22)',
              borderRadius: 10,
              fontSize: 13,
              fontWeight: 800,
              textDecoration: 'none',
              letterSpacing: '.2px',
              backdropFilter: 'blur(4px)',
            }}
          >
            Track Application
          </a>
        </div>
      </div>
    </div>
  );
}
