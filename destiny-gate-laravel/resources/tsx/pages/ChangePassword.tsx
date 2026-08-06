import React, { useState } from 'react';
import api from '../lib/api';
import { toastSuccess, toastError } from '../lib/toast';

interface Props { onDone: () => void; userName: string }

export default function ChangePassword({ onDone, userName }: Props) {
  const [form, setForm] = useState({ current_password: '', new_password: '', new_password_confirmation: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (form.new_password !== form.new_password_confirmation) {
      setError('New passwords do not match.');
      return;
    }
    if (form.new_password.length < 8) {
      setError('New password must be at least 8 characters.');
      return;
    }
    setError('');
    setLoading(true);
    try {
      await api.post('/change-password', form);
      toastSuccess('Password changed successfully. Welcome!');
      onDone();
    } catch (err: any) {
      setError(err.response?.data?.message ?? 'Failed to change password.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{
      minHeight: '100vh',
      background: '#0f1a2e',
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      fontFamily: "'Inter', -apple-system, sans-serif", padding: 20,
    }}>
      <div style={{ width: '100%', maxWidth: 420 }}>
        <div style={{ textAlign: 'center', marginBottom: 28 }}>
          <div style={{ width: 64, height: 64, borderRadius: 16, background: 'rgba(255,255,255,.15)', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 16px', fontSize: 28 }}>🔐</div>
          <h1 style={{ color: '#fff', fontSize: 20, fontWeight: 800, margin: 0 }}>Change Your Password</h1>
          <p style={{ color: 'rgba(255,255,255,.6)', fontSize: 13, marginTop: 6 }}>
            Welcome, <strong style={{ color: '#fff' }}>{userName}</strong>. You must set a new password before continuing.
          </p>
        </div>

        <div style={{ background: '#fff', borderRadius: 16, padding: 28, boxShadow: '0 24px 64px rgba(0,0,0,.25)' }}>
          <div style={{ background: '#fffbeb', border: '1px solid #fde68a', borderRadius: 8, padding: '10px 14px', marginBottom: 20, fontSize: 12, color: '#92400e' }}>
            ⚠️ Your account is using a default password. Please set a secure personal password to continue.
          </div>

          {error && (
            <div style={{ background: '#fef2f2', color: '#991b1b', border: '1px solid #fecaca', borderRadius: 8, padding: '10px 14px', fontSize: 13, marginBottom: 16 }}>
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            {[
              { label: 'Current Password (your National ID or default password)', key: 'current_password' },
              { label: 'New Password (min. 8 characters)', key: 'new_password' },
              { label: 'Confirm New Password', key: 'new_password_confirmation' },
            ].map(f => (
              <div key={f.key} style={{ marginBottom: 16 }}>
                <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#374151', marginBottom: 5 }}>{f.label}</label>
                <input
                  type="password"
                  value={form[f.key as keyof typeof form]}
                  onChange={e => setForm(p => ({ ...p, [f.key]: e.target.value }))}
                  required
                  style={{ width: '100%', padding: '10px 14px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13, boxSizing: 'border-box' }}
                />
              </div>
            ))}

            <button type="submit" disabled={loading} style={{
              width: '100%', padding: 11, background: '#b6924c', color: '#fff',
              border: 'none', borderRadius: 8, fontSize: 14, fontWeight: 600,
              cursor: loading ? 'not-allowed' : 'pointer', opacity: loading ? .7 : 1,
            }}>
              {loading ? 'Saving…' : 'Set New Password & Continue'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
