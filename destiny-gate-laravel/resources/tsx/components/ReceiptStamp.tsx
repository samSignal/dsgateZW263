import React from 'react';

/**
 * A circular "official stamp" seal rendered as SVG (no physical stamp image asset exists),
 * styled to look ink-stamped: rotated, semi-transparent, mix-blend-mode over the paper.
 * Shared by every receipt (Finance and Shop) so they all carry the same mark.
 *
 * Deliberately uses straight, stacked text rather than SVG <textPath> curved lettering —
 * textPath rendering is inconsistent enough across browsers that it read as garbled/broken
 * in practice. Straight lines inside concentric rings give the same "official seal" read
 * without that risk (same reasoning already applied to the dompdf/PDF version of this stamp).
 */
export default function ReceiptStamp({ center = 'RECEIVED', voided = false }: { center?: string; voided?: boolean }) {
  const cx = 60, cy = 60, rOuter = 56, rMid = 52, rInner = 44;
  const ink = voided ? '#b91c1c' : '#1a6b3c';

  return (
    <div style={{ display: 'flex', justifyContent: 'center', marginTop: 4 }}>
      <svg
        width="112" height="112" viewBox="0 0 120 120"
        style={{ transform: 'rotate(-9deg)', opacity: 0.85, mixBlendMode: 'multiply' as const }}
      >
        {/* Engraved double ring */}
        <circle cx={cx} cy={cy} r={rOuter} fill="none" stroke={ink} strokeWidth="2.5" />
        <circle cx={cx} cy={cy} r={rMid} fill="none" stroke={ink} strokeWidth="0.75" strokeDasharray="1.4 2.2" />
        <circle cx={cx} cy={cy} r={rInner} fill="none" stroke={ink} strokeWidth="1.5" />

        <text x={cx} y={cy - 26} textAnchor="middle" fill={ink} fontSize="8" fontWeight={700} letterSpacing="1">DESTINYGATE INSTITUTE</text>

        <line x1={cx - 22} y1={cy - 14} x2={cx + 22} y2={cy - 14} stroke={ink} strokeWidth="0.6" opacity="0.5" />
        <text x={cx} y={cy + 4} textAnchor="middle" fill={ink} fontSize={center.length > 8 ? '13' : '18'} fontWeight={800} letterSpacing="1">{center}</text>
        <line x1={cx - 22} y1={cy + 14} x2={cx + 22} y2={cy + 14} stroke={ink} strokeWidth="0.6" opacity="0.5" />

        <text x={cx} y={cy + 27} textAnchor="middle" fill={ink} fontSize="7.5" fontWeight={600} letterSpacing=".5">★ VERIFIED ★</text>
      </svg>
    </div>
  );
}
