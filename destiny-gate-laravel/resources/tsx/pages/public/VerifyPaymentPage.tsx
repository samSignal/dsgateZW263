import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

function formatLongDate(iso: string): string {
  const d = new Date(iso + 'T00:00:00');
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });
}

export default function VerifyPaymentPage() {
  const { reference: routeReference } = useParams();
  const navigate = useNavigate();
  const [input, setInput] = useState(routeReference ?? '');
  const [searchedRef, setSearchedRef] = useState(routeReference ?? '');

  const { data, isFetching, isError } = useQuery({
    queryKey: ['verify-payment', searchedRef],
    queryFn: () => api.get(`/public/verify-payment/${encodeURIComponent(searchedRef)}`).then(r => r.data).catch(e => {
      if (e.response?.status === 404) return e.response.data;
      throw e;
    }),
    enabled: !!searchedRef,
    retry: false,
  });

  const submit = () => {
    const ref = input.trim().toUpperCase();
    if (!ref) return;
    setSearchedRef(ref);
    navigate(`/verify-payment/${encodeURIComponent(ref)}`, { replace: true });
  };

  return (
    <div style={{ minHeight: '100vh', background: '#f5f6fa', display: 'flex', justifyContent: 'center', padding: '40px 16px', fontFamily: "'Inter', -apple-system, sans-serif" }}>
      <div style={{ width: '100%', maxWidth: 480 }}>
        <div style={{ textAlign: 'center', marginBottom: 24 }}>
          <img src="/logo-mark.png" alt="" style={{ width: 52, height: 52, marginBottom: 8 }} />
          <div style={{ fontSize: 19, fontWeight: 800, color: '#0f3d22' }}>DESTINYGATE INSTITUTE</div>
          <div style={{ fontSize: 13, color: '#6b7280', marginTop: 4 }}>Payment Verification</div>
        </div>

        <div style={{ background: '#fff', borderRadius: 14, border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,.05)', padding: 24, marginBottom: 20 }}>
          <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#374151', marginBottom: 6 }}>
            Payment / Purchase Reference or Document Number
          </label>
          <div style={{ display: 'flex', gap: 8 }}>
            <input
              value={input}
              onChange={e => setInput(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && submit()}
              placeholder="DGS-PAY-20260907-101532-7K4P9X"
              style={{ flex: 1, padding: '10px 12px', border: '1.5px solid #e2e8f0', borderRadius: 8, fontSize: 13.5, fontFamily: 'ui-monospace, monospace' }}
            />
            <button onClick={submit} disabled={isFetching} style={{ padding: '10px 18px', background: '#1a6b3c', color: '#fff', border: 'none', borderRadius: 8, fontWeight: 600, fontSize: 13.5, cursor: 'pointer' }}>
              {isFetching ? '…' : 'Verify'}
            </button>
          </div>
          <p style={{ fontSize: 11.5, color: '#9ca3af', marginTop: 10, lineHeight: 1.5 }}>
            Never trust a reference shown only on a screenshot or forwarded message — always check it here first. Works for both fee payments and shop purchase receipts.
          </p>
        </div>

        {searchedRef && !isFetching && data?.verified && (
          <div style={{ background: '#fff', borderRadius: 14, border: `2px solid ${data.status === 'CONFIRMED' ? '#16a34a' : '#dc2626'}`, boxShadow: `0 4px 16px ${data.status === 'CONFIRMED' ? 'rgba(22,163,74,.12)' : 'rgba(220,38,38,.12)'}`, overflow: 'hidden' }}>
            <div style={{ background: data.status === 'CONFIRMED' ? '#16a34a' : '#dc2626', color: '#fff', padding: '14px 20px', display: 'flex', alignItems: 'center', gap: 8 }}>
              <span style={{ fontSize: 18 }}>{data.status === 'CONFIRMED' ? '🟢' : '🔴'}</span>
              <span style={{ fontWeight: 800, fontSize: 14, letterSpacing: '.3px' }}>
                {data.type === 'shop_purchase' ? 'PURCHASE' : 'PAYMENT'} {data.status === 'CONFIRMED' ? 'VERIFIED' : data.status}
              </span>
            </div>
            <div style={{ padding: 20 }}>
              <div style={{ fontSize: 13, fontWeight: 700, color: '#0f3d22', marginBottom: 14 }}>{data.school_name}</div>
              <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13.5 }}>
                <tbody>
                  {[
                    ['Student', `${data.student_name} (${data.admission_number})`],
                    [data.type === 'shop_purchase' ? 'Total' : 'Amount', `USD ${Number(data.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`],
                    ...(data.type === 'shop_purchase' ? [
                      ['Amount Paid', `USD ${Number(data.amount_paid).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`],
                      ['Items', `${data.item_count} item(s)`],
                    ] : [
                      ['Payment Method', String(data.payment_method).replace('_', ' ')],
                    ]),
                    ['Date', formatLongDate(data.payment_date)],
                    [data.document_label, data.document_number],
                    ['Reference', data.reference],
                    ['Processed By', data.processed_by ?? '—'],
                    ['Status', data.status],
                  ].map(([k, v]) => (
                    <tr key={k}>
                      <td style={{ padding: '6px 0', color: '#6b7280', width: '40%', textTransform: 'capitalize' }}>{k}</td>
                      <td style={{ padding: '6px 0', fontWeight: k === 'Status' ? 700 : 500, color: k === 'Status' ? (data.status === 'CONFIRMED' ? '#16a34a' : '#dc2626') : '#111827', textTransform: k === 'Payment Method' ? 'capitalize' : 'none' }}>{v}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {data.status !== 'CONFIRMED' && (
                <div style={{ marginTop: 14, padding: '10px 12px', background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 8, fontSize: 12, color: '#991b1b' }}>
                  This {data.type === 'shop_purchase' ? 'purchase was cancelled' : 'payment was reversed'} after it was recorded and should not be treated as valid.
                </div>
              )}
            </div>
          </div>
        )}

        {searchedRef && !isFetching && (isError || data?.verified === false) && (
          <div style={{ background: '#fff', borderRadius: 14, border: '2px solid #dc2626', boxShadow: '0 4px 16px rgba(220,38,38,.12)', overflow: 'hidden' }}>
            <div style={{ background: '#dc2626', color: '#fff', padding: '14px 20px', display: 'flex', alignItems: 'center', gap: 8 }}>
              <span style={{ fontSize: 18 }}>🔴</span>
              <span style={{ fontWeight: 800, fontSize: 14, letterSpacing: '.3px' }}>NOT VERIFIED</span>
            </div>
            <div style={{ padding: 20, fontSize: 13, color: '#374151', lineHeight: 1.6 }}>
              {data?.message ?? 'No payment matches this reference. Do not treat it as a valid receipt.'}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
