import React from 'react';
import { Card, CardBody, Btn, Badge } from './UI';
import ReceiptStamp from './ReceiptStamp';
import ReceiptWatermark from './ReceiptWatermark';
import PaymentQRCode from './PaymentQRCode';
import ThermalShopReceipt from './ThermalShopReceipt';
import { usePrintMode } from '../lib/usePrintMode';
import { SCHOOL_ADDRESS, SCHOOL_PHONE } from '../lib/schoolInfo';

const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function ShopReceipt({ purchase, onPrint }: { purchase: any; onPrint?: () => void }) {
  const payments = purchase.payments ?? [];
  const items = purchase.items ?? [];
  const isCancelled = purchase.status === 'cancelled';
  const { mode, setMode, print } = usePrintMode();

  if (mode === 'thermal') {
    return (
      <Card>
        <div className="no-print" style={{ padding: '16px 20px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <span style={{ fontSize: 14, fontWeight: 600, color: '#0f172a' }}>Receipt (58mm preview)</span>
          <div style={{ display: 'flex', gap: 8 }}>
            <Btn size="sm" variant="ghost" onClick={() => setMode('full')}>← Back</Btn>
            <Btn size="sm" variant="outline" onClick={onPrint ?? print}>🧾 Print</Btn>
          </div>
        </div>
        <CardBody>
          <div className="print-area thermal-mode" style={{ maxWidth: 260, margin: '0 auto' }}>
            <ThermalShopReceipt purchase={purchase} />
          </div>
        </CardBody>
      </Card>
    );
  }

  return (
    <Card>
      <div className="no-print" style={{ padding: '16px 20px', borderBottom: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <span style={{ fontSize: 14, fontWeight: 600, color: '#0f172a' }}>Receipt</span>
        <div style={{ display: 'flex', gap: 8 }}>
          <Btn size="sm" variant="outline" onClick={() => setMode('thermal')} title="For a 58mm Bluetooth thermal receipt printer">🧾 58mm</Btn>
          <Btn size="sm" variant="outline" onClick={onPrint ?? print}>🖨️ Print</Btn>
        </div>
      </div>
      <CardBody>
      <div className="print-area" style={{ position: 'relative' }}>
        <ReceiptWatermark text="DESTINYGATE SHOP" />
        <div style={{ position: 'relative', zIndex: 1 }}>

        <div style={{
          display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10,
          margin: '-20px -20px 18px', padding: '20px 20px 16px',
          background: 'linear-gradient(180deg, #f0faf4 0%, rgba(240,250,244,0) 100%)',
          borderBottom: '2px solid #1a6b3c',
        }}>
          <div style={{ width: 92 }} />
          <div style={{ textAlign: 'center', flex: 1 }}>
            <img src="/logo-mark.png" alt="" style={{ width: 44, height: 44, marginBottom: 6 }} />
            <div style={{ fontSize: 17, fontWeight: 800, color: '#0f3d22', letterSpacing: '.3px' }}>DESTINYGATE INSTITUTE</div>
            <div style={{ fontSize: 10.5, color: '#6b7280', fontStyle: 'italic' }}>Raising a Godly, Skilled and Confident Generation</div>
            <div style={{ fontSize: 9, color: '#6b7280', marginBottom: 8 }}>{SCHOOL_ADDRESS} · {SCHOOL_PHONE}</div>
            <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: '1px', color: '#374151' }}>SHOP PURCHASE RECEIPT</div>
            <div style={{ fontSize: 20, fontWeight: 700, marginTop: 6 }}>{purchase.purchase_number}</div>
            {isCancelled && <div style={{ marginTop: 6 }}><Badge variant="red">CANCELLED</Badge></div>}
          </div>
          <PaymentQRCode reference={purchase.purchase_reference} />
        </div>

        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13, marginBottom: 14 }}>
          <tbody>
            {[
              ['Student', purchase.student_name],
              ['Admission #', purchase.admission_number ?? purchase.student_number],
              ['Class', purchase.class_name ?? purchase.class_display ?? '—'],
              ['Parent / Guardian', purchase.guardian_name ?? '—'],
              ['Date', purchase.purchase_date],
              ['Recorded By', purchase.recorded_by_name ?? '—'],
              ...(purchase.purchase_reference ? [['Purchase Reference', purchase.purchase_reference]] : []),
            ].map(([k, v]) => (
              <tr key={k}>
                <td style={{ padding: '5px 0', color: '#6b7280', width: '40%', borderBottom: '1px solid #f8fafc' }}>{k}</td>
                <td style={{
                  padding: '5px 0', borderBottom: '1px solid #f8fafc',
                  color: k === 'Purchase Reference' ? '#0f3d22' : '#111827',
                  fontWeight: k === 'Purchase Reference' ? 700 : 400,
                  fontFamily: k === 'Purchase Reference' ? 'ui-monospace, monospace' : 'inherit',
                  fontSize: k === 'Purchase Reference' ? 12 : 'inherit',
                }}>{v}</td>
              </tr>
            ))}
          </tbody>
        </table>

        <div style={{ fontSize: 11, fontWeight: 700, color: '#6b7280', marginBottom: 6, textTransform: 'uppercase' }}>Items</div>
        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12.5, marginBottom: 14 }}>
          <thead>
            <tr style={{ background: '#f0faf4' }}>
              <th style={{ textAlign: 'left', padding: '5px 6px', color: '#0f3d22' }}>Item</th>
              <th style={{ textAlign: 'right', padding: '5px 6px', color: '#0f3d22' }}>Qty</th>
              <th style={{ textAlign: 'right', padding: '5px 6px', color: '#0f3d22' }}>Unit</th>
              <th style={{ textAlign: 'right', padding: '5px 6px', color: '#0f3d22' }}>Total</th>
            </tr>
          </thead>
          <tbody>
            {items.map((i: any) => (
              <tr key={i.id}>
                <td style={{ padding: '5px 6px', borderBottom: '1px solid #f1f5f9' }}>{i.item_name}{i.size && ` (${i.size})`}</td>
                <td style={{ padding: '5px 6px', borderBottom: '1px solid #f1f5f9', textAlign: 'right' }}>{i.quantity}</td>
                <td style={{ padding: '5px 6px', borderBottom: '1px solid #f1f5f9', textAlign: 'right' }}>{money(i.unit_price)}</td>
                <td style={{ padding: '5px 6px', borderBottom: '1px solid #f1f5f9', textAlign: 'right', fontWeight: 600 }}>{money(i.line_total)}</td>
              </tr>
            ))}
          </tbody>
        </table>

        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13, marginBottom: 14 }}>
          <tbody>
            <tr><td style={{ padding: '3px 0', color: '#6b7280' }}>Total</td><td style={{ padding: '3px 0', textAlign: 'right', fontWeight: 700 }}>{money(purchase.total_amount)}</td></tr>
            <tr><td style={{ padding: '3px 0', color: '#6b7280' }}>Amount Paid</td><td style={{ padding: '3px 0', textAlign: 'right', color: '#1a6b3c', fontWeight: 700 }}>{money(purchase.amount_paid)}</td></tr>
            <tr><td style={{ padding: '3px 0', color: '#6b7280' }}>Balance</td><td style={{ padding: '3px 0', textAlign: 'right', color: Number(purchase.balance) > 0 ? '#dc2626' : '#1a6b3c', fontWeight: 700 }}>{money(purchase.balance)}</td></tr>
          </tbody>
        </table>

        <div style={{ fontSize: 11, fontWeight: 700, color: '#6b7280', marginBottom: 6, textTransform: 'uppercase' }}>Payments</div>
        {payments.length === 0 && <div style={{ fontSize: 12, color: '#9ca3af', marginBottom: 10 }}>No payments recorded.</div>}
        {payments.map((pay: any) => (
          <div key={pay.id} style={{ border: '1px solid #f1f5f9', borderRadius: 8, padding: '8px 10px', marginBottom: 6 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 13 }}>
              <strong>{money(pay.amount)}</strong>
              <span style={{ textTransform: 'capitalize', color: '#374151' }}>{String(pay.payment_method).replace('_', ' ')}</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11, color: '#6b7280', marginTop: 3 }}>
              <span>{pay.payment_date}{pay.reference_number ? ` · Ref: ${pay.reference_number}` : ''}</span>
              <span><strong>Received By:</strong> {pay.received_by_name ?? '—'}</span>
            </div>
          </div>
        ))}

        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginTop: 24, paddingTop: 12 }}>
          <div style={{ width: '45%', textAlign: 'center' }}>
            {purchase.recorded_by_name && <div style={{ fontSize: 12.5, fontWeight: 600, color: '#111827', marginBottom: 4 }}>{purchase.recorded_by_name}</div>}
            <div style={{ borderTop: '1px solid #9ca3af', paddingTop: 4, fontSize: 10.5, color: '#6b7280' }}>Received By</div>
          </div>
          <div style={{ width: '45%', textAlign: 'center' }}>
            <ReceiptStamp center={isCancelled ? 'CANCELLED' : 'RECEIVED'} voided={isCancelled} />
            <div style={{ borderTop: '1px solid #9ca3af', paddingTop: 4, fontSize: 10.5, color: '#6b7280' }}>Authorised Signature / Stamp</div>
          </div>
        </div>
        </div>
      </div>
      </CardBody>
    </Card>
  );
}
