import React from 'react';

/** Faint diagonal background watermark for receipts — sits behind the content (caller must
 *  give the wrapping element position:relative and the content itself a higher z-index). */
export default function ReceiptWatermark({ text = 'DESTINYGATE INSTITUTE' }: { text?: string }) {
  return (
    <div style={{
      position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center',
      overflow: 'hidden', pointerEvents: 'none', userSelect: 'none', zIndex: 0,
    }}>
      <div style={{
        fontSize: 38, fontWeight: 800, color: '#1a6b3c', opacity: 0.055,
        transform: 'rotate(-30deg)', whiteSpace: 'nowrap', letterSpacing: 6,
        lineHeight: 1.9, textAlign: 'center',
      }}>
        {Array.from({ length: 5 }).map((_, i) => <div key={i}>{text}</div>)}
      </div>
    </div>
  );
}
