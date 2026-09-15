import React from 'react';
import PaymentQRCode from './PaymentQRCode';
import { SCHOOL_PHONE } from '../lib/schoolInfo';

const money = (n: number) => `$${Number(n ?? 0).toFixed(2)}`;

const Rule = () => <div style={{ borderTop: '1px dashed #000', margin: '5px 0' }} />;
const Row = ({ label, value, bold }: { label: string; value: React.ReactNode; bold?: boolean }) => (
  <div style={{ display: 'flex', justifyContent: 'space-between', gap: 6, fontSize: 10, marginBottom: 2, fontWeight: bold ? 700 : 400 }}>
    <span>{label}</span>
    <span style={{ textAlign: 'right' }}>{value}</span>
  </div>
);

/** Compact single-column layout for a 58mm Bluetooth thermal receipt printer — see
 *  .thermal-mode in app.css and usePrintMode. */
export default function ThermalShopReceipt({ purchase }: { purchase: any }) {
  const items = purchase.items ?? [];
  const payments = purchase.payments ?? [];
  const isCancelled = purchase.status === 'cancelled';

  return (
    <div style={{ color: '#000', fontSize: 10.5 }}>
      <div style={{ textAlign: 'center', marginBottom: 6 }}>
        <img src="/logo-mark.png" alt="" style={{ width: 30, height: 30, marginBottom: 3 }} />
        <div style={{ fontWeight: 800, fontSize: 12 }}>DESTINYGATE INSTITUTE</div>
        <div style={{ fontSize: 8, marginTop: 1 }}>{SCHOOL_PHONE}</div>
        <div style={{ fontSize: 9, marginTop: 1 }}>SHOP PURCHASE RECEIPT{isCancelled ? ' — CANCELLED' : ''}</div>
        <div style={{ fontWeight: 700, fontSize: 11, marginTop: 3 }}>{purchase.purchase_number}</div>
      </div>
      <Rule />
      <Row label="Student" value={purchase.student_name} />
      <Row label="Adm #" value={purchase.admission_number ?? purchase.student_number} />
      <Row label="Date" value={purchase.purchase_date} />
      <Rule />
      {items.map((i: any) => (
        <div key={i.id} style={{ fontSize: 10, marginBottom: 3 }}>
          <div>{i.item_name}{i.size && ` (${i.size})`}</div>
          <div style={{ display: 'flex', justifyContent: 'space-between', color: '#333' }}>
            <span>{i.quantity} x {money(i.unit_price)}</span>
            <span>{money(i.line_total)}</span>
          </div>
        </div>
      ))}
      <Rule />
      <Row label="Total" value={money(purchase.total_amount)} bold />
      <Row label="Paid" value={money(purchase.amount_paid)} bold />
      <Row label="Balance" value={money(purchase.balance)} bold />
      {payments.length > 0 && (
        <>
          <Rule />
          {payments.map((p: any) => (
            <Row key={p.id} label={String(p.payment_method).replace('_', ' ')} value={money(p.amount)} />
          ))}
        </>
      )}
      <Rule />
      <div style={{ textAlign: 'center', margin: '8px 0 4px' }}>
        <PaymentQRCode reference={purchase.purchase_reference} size={92} />
      </div>
      {purchase.purchase_reference && (
        <div style={{ textAlign: 'center', fontSize: 8, wordBreak: 'break-all', marginBottom: 6 }}>{purchase.purchase_reference}</div>
      )}
      <div style={{ textAlign: 'center', fontSize: 9, marginTop: 4 }}>Thank you!</div>
    </div>
  );
}
