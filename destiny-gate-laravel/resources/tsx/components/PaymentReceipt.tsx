import React from 'react';
import { Card, CardBody, Btn, Badge } from './UI';
import ReceiptStamp from './ReceiptStamp';
import ReceiptWatermark from './ReceiptWatermark';
import PaymentQRCode from './PaymentQRCode';
import ThermalPaymentReceipt from './ThermalPaymentReceipt';
import { usePrintMode } from '../lib/usePrintMode';
import { SCHOOL_ADDRESS, SCHOOL_PHONE } from '../lib/schoolInfo';

const signedMoney = (n: number) => `${n < 0 ? '-' : ''}$${Math.abs(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function PaymentReceipt({ receipt, onPrint }: { receipt: any; onPrint?: () => void }) {
  const isReversed = receipt.status === 'reversed';
  const outstanding = Number(receipt.remaining_balance);
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
            <ThermalPaymentReceipt receipt={receipt} />
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
        <ReceiptWatermark />
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
            <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: '1px', color: '#374151' }}>OFFICIAL RECEIPT</div>
            <div style={{ fontSize: 20, fontWeight: 700, marginTop: 6 }}>{receipt.receipt_number}</div>
            {isReversed && <div style={{ marginTop: 6 }}><Badge variant="red">REVERSED</Badge></div>}
          </div>
          <PaymentQRCode reference={receipt.payment_reference} />
        </div>

        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
          <tbody>
            {[
              ['Student', receipt.student_name],
              ['Admission #', receipt.admission_number ?? receipt.student_number],
              ['Class', receipt.class_name ?? receipt.class_display ?? '—'],
              ['Parent / Guardian', receipt.guardian_name ?? receipt.payer_name ?? '—'],
              ['Description', (receipt.allocations ?? []).map((a: any) => a.category_name).filter(Boolean).join(', ') || 'Fee payment'],
              ['Method', receipt.payment_method?.replace('_', ' ')],
              ['Bank / Mobile Money Ref', receipt.reference_number ?? '—'],
              ['Date', receipt.payment_date],
              ['Received By', receipt.received_by_name],
              ...(receipt.payment_reference ? [['Payment Reference', receipt.payment_reference]] : []),
              ...(receipt.previous_balance != null ? [['Previous Balance', signedMoney(Number(receipt.previous_balance))]] : []),
              ['Amount Paid', `$${Number(receipt.amount).toLocaleString()}`],
              [outstanding < 0 ? 'Credit Balance' : 'Amount Outstanding', signedMoney(outstanding)],
            ].map(([k, v]) => (
              <tr key={k}>
                <td style={{ padding: '6px 0', color: '#6b7280', width: '40%', borderBottom: '1px solid #f8fafc' }}>{k}</td>
                <td style={{
                  padding: '6px 0', borderBottom: '1px solid #f8fafc',
                  fontWeight: k === 'Amount Paid' || k === 'Payment Reference' ? 700 : 400,
                  fontFamily: k === 'Payment Reference' ? 'ui-monospace, monospace' : 'inherit',
                  fontSize: k === 'Payment Reference' ? 12 : 'inherit',
                  color: k === 'Amount Outstanding' ? '#dc2626' : (k === 'Credit Balance' ? '#1a6b3c' : (k === 'Payment Reference' ? '#0f3d22' : '#111827')),
                }}>{v}</td>
              </tr>
            ))}
          </tbody>
        </table>
        {receipt.allocations?.length > 0 && (
          <div style={{ marginTop: 12, borderTop: '1px solid #f3f4f6', paddingTop: 10 }}>
            <div style={{ fontSize: 11, fontWeight: 700, color: '#6b7280', marginBottom: 6, textTransform: 'uppercase' }}>Bills Paid</div>
            {receipt.allocations.map((a: any, i: number) => (
              <div key={i} style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12, marginBottom: 4 }}>
                <span>{a.category_code ? `${a.category_code} — ` : ''}{a.description}</span>
                <span style={{ fontWeight: 600 }}>${Number(a.amount_allocated).toLocaleString()}</span>
              </div>
            ))}
          </div>
        )}

        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginTop: 24, paddingTop: 12 }}>
          <div style={{ width: '45%', textAlign: 'center' }}>
            {receipt.received_by_name && <div style={{ fontSize: 12.5, fontWeight: 600, color: '#111827', marginBottom: 4 }}>{receipt.received_by_name}</div>}
            <div style={{ borderTop: '1px solid #9ca3af', paddingTop: 4, fontSize: 10.5, color: '#6b7280' }}>Received By</div>
          </div>
          <div style={{ width: '45%', textAlign: 'center' }}>
            <ReceiptStamp center={isReversed ? 'REVERSED' : 'RECEIVED'} voided={isReversed} />
            <div style={{ borderTop: '1px solid #9ca3af', paddingTop: 4, fontSize: 10.5, color: '#6b7280' }}>Authorised Signature / Stamp</div>
          </div>
        </div>
        </div>
      </div>
      </CardBody>
    </Card>
  );
}
