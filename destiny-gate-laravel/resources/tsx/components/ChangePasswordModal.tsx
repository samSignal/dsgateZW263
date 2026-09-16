import React, { useState } from 'react';
import api from '../lib/api';
import { toastSuccess, toastError } from '../lib/toast';
import { Modal, Btn, FormGroup, Input } from './UI';

export default function ChangePasswordModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const empty = { current_password: '', new_password: '', new_password_confirmation: '' };
  const [form, setForm] = useState(empty);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const close = () => { setForm(empty); setError(''); onClose(); };

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
      toastSuccess('Password changed successfully.');
      close();
    } catch (err: any) {
      setError(err.response?.data?.message ?? 'Failed to change password.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal open={open} onClose={close} title="Change Password">
      {error && (
        <div style={{ background: '#fef2f2', color: '#991b1b', border: '1px solid #fecaca', borderRadius: 8, padding: '10px 14px', fontSize: 13, marginBottom: 16 }}>
          {error}
        </div>
      )}
      <form onSubmit={handleSubmit}>
        <FormGroup label="Current Password">
          <Input
            type="password"
            value={form.current_password}
            onChange={e => setForm(p => ({ ...p, current_password: e.target.value }))}
            required
          />
        </FormGroup>
        <FormGroup label="New Password (min. 8 characters)">
          <Input
            type="password"
            value={form.new_password}
            onChange={e => setForm(p => ({ ...p, new_password: e.target.value }))}
            required
          />
        </FormGroup>
        <FormGroup label="Confirm New Password">
          <Input
            type="password"
            value={form.new_password_confirmation}
            onChange={e => setForm(p => ({ ...p, new_password_confirmation: e.target.value }))}
            required
          />
        </FormGroup>
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 8 }}>
          <Btn variant="outline" onClick={close} type="button">Cancel</Btn>
          <Btn variant="primary" type="submit" loading={loading}>Change Password</Btn>
        </div>
      </form>
    </Modal>
  );
}
