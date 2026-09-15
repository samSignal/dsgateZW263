import React, { useEffect, useState } from 'react';
import QRCode from 'qrcode';

/** Renders a QR code pointing at the public payment-verification page for the given reference. */
export default function PaymentQRCode({ reference, size = 92 }: { reference: string; size?: number }) {
  const [dataUrl, setDataUrl] = useState<string | null>(null);

  useEffect(() => {
    if (!reference) { setDataUrl(null); return; }
    let cancelled = false;
    const url = `${window.location.origin}/verify-payment/${encodeURIComponent(reference)}`;
    QRCode.toDataURL(url, { margin: 1, width: size * 4, color: { dark: '#0f3d22', light: '#ffffff' } })
      .then(u => { if (!cancelled) setDataUrl(u); })
      .catch(() => { if (!cancelled) setDataUrl(null); });
    return () => { cancelled = true; };
  }, [reference, size]);

  if (!reference) return null;

  return (
    <div style={{ textAlign: 'center' }}>
      {dataUrl
        ? <img src={dataUrl} alt="Scan to verify this payment" width={size} height={size} style={{ display: 'block', margin: '0 auto' }} />
        : <div style={{ width: size, height: size, background: '#f1f5f9', borderRadius: 6 }} />}
      <div style={{ fontSize: 8.5, color: '#9ca3af', marginTop: 3 }}>Scan to verify</div>
    </div>
  );
}
