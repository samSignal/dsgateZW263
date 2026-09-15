import React from 'react';
import PaymentQRCode from './PaymentQRCode';
import { SCHOOL_PHONE } from '../lib/schoolInfo';

const money = (n: number) => `$${Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const Rule = () => <div style={{ borderTop: '1px dashed #000', margin: '5px 0' }} />;
const Row = ({ label, value, bold }: { label: string; value: React.ReactNode; bold?: boolean }) => (
  <div style={{ display: 'flex', justifyContent: 'space-between', gap: 6, fontSize: 10, marginBottom: 2, fontWeight: bold ? 700 : 400 }}>
    <span>{label}</span>
    <span style={{ textAlign: 'right' }}>{value}</span>
  </div>
);

/** Compact single-column layout for a 58mm Bluetooth thermal receipt printer — see
 *  .thermal-mode in app.css and usePrintMode. Deliberately plain: no color, no watermark,
 *  no wide tables — just what fits ~48mm of black-and-white thermal paper. */
export default function ThermalPaymentReceipt({ receipt }: { receipt: any }) {
  const isReversed = receipt.status === 'reversed';
  const outstanding = Number(receipt.remaining_balance);

  return (
    <div style={{ color: '#000', fontSize: 10.5 }}>
      <div style={{ textAlign: 'center', marginBottom: 6 }}>
        <img src="/logo-mark.png" alt="" style={{ width: 30, height: 30, marginBottom: 3 }} />
        <div style={{ fontWeight: 800, fontSize: 12 }}>DESTINYGATE INSTITUTE</div>
        <div style={{ fontSize: 8, marginTop: 1 }}>{SCHOOL_PHONE}</div>
        <div style={{ fontSize: 9, marginTop: 1 }}>OFFICIAL RECEIPT{isReversed ? ' — REVERSED' : ''}</div>
        <div style={{ fontWeight: 700, fontSize: 11, marginTop: 3 }}>{receipt.receipt_number}</div>
      </div>
      <Rule />
      <Row label="Student" value={receipt.student_name} />
      <Row label="Adm #" value={receipt.admission_number ?? receipt.student_number} />
      <Row label="Class" value={receipt.class_name ?? receipt.class_display ?? '—'} />
      <Row label="Guardian" value={receipt.guardian_name ?? receipt.payer_name ?? '—'} />
      <Rule />
      <Row label="Date" value={receipt.payment_date} />
      <Row label="Method" value={String(receipt.payment_method ?? '').replace('_', ' ')} />
      {receipt.reference_number && <Row label="Bank/Mobile Ref" value={receipt.reference_number} />}
      <Row label="Received By" value={receipt.received_by_name} />
      <Rule />
      <Row label="Amount Paid" value={money(receipt.amount)} bold />
      <Row label={outstanding < 0 ? 'Credit' : 'Balance'} value={`${outstanding < 0 ? '-' : ''}${money(Math.abs(outstanding))}`} bold />
      {receipt.allocations?.length > 0 && (
        <>
          <Rule />
          {receipt.allocations.map((a: any, i: number) => (
            <Row key={i} label={a.description} value={money(a.amount_allocated)} />
          ))}
        </>
      )}
      <Rule />
      <div style={{ textAlign: 'center', margin: '8px 0 4px' }}>
        <PaymentQRCode reference={receipt.payment_reference} size={92} />
      </div>
      {receipt.payment_reference && (
        <div style={{ textAlign: 'center', fontSize: 8, wordBreak: 'break-all', marginBottom: 6 }}>{receipt.payment_reference}</div>
      )}
      <div style={{ textAlign: 'center', fontSize: 9, marginTop: 4 }}>Thank you!</div>
    </div>
  );
}
