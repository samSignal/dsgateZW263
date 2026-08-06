import React from 'react';

// ── Stat Card ─────────────────────────────────────────────────────────────────
const iconColors = {
  green:  { bg: '#f0faf4', color: '#1a6b3c' },
  red:    { bg: '#fef2f2', color: '#dc2626' },
  amber:  { bg: '#fffbeb', color: '#d97706' },
  blue:   { bg: '#eff6ff', color: '#2563eb' },
  purple: { bg: '#f5f3ff', color: '#7c3aed' },
};
const valColors = { green: '#1a6b3c', red: '#dc2626', amber: '#d97706', blue: '#2563eb', purple: '#7c3aed' };

interface StatCardProps { label: string; value: string | number; icon?: string; color?: keyof typeof iconColors; trend?: string }

export function StatCard({ label, value, icon, color = 'green', trend }: StatCardProps) {
  const ic = iconColors[color];
  return (
    <div style={{
      background: '#fff', borderRadius: 12,
      border: '1px solid #e2e8f0',
      boxShadow: '0 1px 3px rgba(0,0,0,.05)',
      padding: '20px 22px',
      transition: 'box-shadow .15s',
    }}>
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 14 }}>
        <div style={{ fontSize: 11, fontWeight: 600, color: '#64748b', textTransform: 'uppercase', letterSpacing: '.6px' }}>{label}</div>
        {icon && (
          <div style={{ width: 36, height: 36, borderRadius: 9, background: ic.bg, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 }}>
            {icon}
          </div>
        )}
      </div>
      <div style={{ fontSize: 28, fontWeight: 800, color: valColors[color], letterSpacing: '-.5px', lineHeight: 1 }}>{value}</div>
      {trend && <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 6 }}>{trend}</div>}
    </div>
  );
}

// ── Card ──────────────────────────────────────────────────────────────────────
export function Card({ children, style }: { children: React.ReactNode; style?: React.CSSProperties }) {
  return (
    <div style={{
      background: '#fff', borderRadius: 12,
      border: '1px solid #e2e8f0',
      boxShadow: '0 1px 3px rgba(0,0,0,.05)',
      overflow: 'hidden',
      ...style,
    }}>{children}</div>
  );
}

export function CardHeader({ title, action }: { title: string; action?: React.ReactNode }) {
  return (
    <div style={{
      padding: '16px 20px',
      borderBottom: '1px solid #f1f5f9',
      display: 'flex', alignItems: 'center', justifyContent: 'space-between',
    }}>
      <span style={{ fontSize: 14, fontWeight: 600, color: '#0f172a' }}>{title}</span>
      {action}
    </div>
  );
}

export function CardBody({ children, style }: { children: React.ReactNode; style?: React.CSSProperties }) {
  return <div style={{ padding: 20, ...style }}>{children}</div>;
}

// ── Table ─────────────────────────────────────────────────────────────────────
export function Table({ headers, children }: { headers: string[]; children: React.ReactNode }) {
  return (
    <div style={{ overflowX: 'auto' }}>
      <table style={{ width: '100%', borderCollapse: 'collapse' }}>
        <thead>
          <tr>
            {headers.map(h => (
              <th key={h} style={{
                background: '#fafafa',
                padding: '10px 16px',
                textAlign: 'left',
                fontSize: 11, fontWeight: 700,
                color: '#64748b',
                textTransform: 'uppercase',
                letterSpacing: '.7px',
                borderBottom: '1px solid #e2e8f0',
                whiteSpace: 'nowrap',
              }}>{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>{children}</tbody>
      </table>
    </div>
  );
}

export function Td({ children, style, colSpan }: { children?: React.ReactNode; style?: React.CSSProperties; colSpan?: number }) {
  return (
    <td colSpan={colSpan} style={{
      padding: '11px 16px',
      borderBottom: '1px solid #f1f5f9',
      fontSize: 13, color: '#0f172a',
      verticalAlign: 'middle',
      ...style,
    }}>{children}</td>
  );
}

// ── Badge ─────────────────────────────────────────────────────────────────────
type BV = 'green' | 'red' | 'amber' | 'blue' | 'purple' | 'gray';
const BS: Record<BV, { bg: string; color: string }> = {
  green:  { bg: '#ecfdf5', color: '#065f46' },
  red:    { bg: '#fef2f2', color: '#991b1b' },
  amber:  { bg: '#fffbeb', color: '#92400e' },
  blue:   { bg: '#eff6ff', color: '#1e40af' },
  purple: { bg: '#f5f3ff', color: '#5b21b6' },
  gray:   { bg: '#f1f5f9', color: '#475569' },
};

export function Badge({ children, variant = 'gray', style }: { children: React.ReactNode; variant?: BV; style?: React.CSSProperties }) {
  const s = BS[variant];
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center',
      padding: '3px 10px', borderRadius: 20,
      fontSize: 11, fontWeight: 600, letterSpacing: '.3px',
      background: s.bg, color: s.color, whiteSpace: 'nowrap',
      ...style,
    }}>{children}</span>
  );
}

export function statusBadge(status: string) {
  const map: Record<string, BV> = {
    active: 'green', paid: 'green', approved: 'green', offered: 'green', present: 'green', enrolled: 'green',
    pending: 'amber', waiting_list: 'amber', partial: 'amber', late: 'amber', moderate: 'amber',
    overdue: 'red', rejected: 'red', suspended: 'red', absent: 'red', severe: 'red',
    transferred: 'blue', graduated: 'blue', inactive: 'gray', minor: 'green', deceased: 'gray',
    excused: 'blue', sick: 'amber', early_departure: 'amber',
  };
  return <Badge variant={map[status] ?? 'gray'}>{status.replace(/_/g, ' ')}</Badge>;
}

// ── Button ────────────────────────────────────────────────────────────────────
interface BtnProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'outline' | 'danger' | 'ghost';
  size?: 'sm' | 'md';
  loading?: boolean;
}

const BV_STYLES = {
  primary: { background: '#1a6b3c', color: '#fff', border: 'none' },
  outline: { background: '#fff', color: '#374151', border: '1px solid #d1d5db' },
  danger:  { background: '#dc2626', color: '#fff', border: 'none' },
  ghost:   { background: 'transparent', color: '#64748b', border: 'none' },
};
const BS_SIZES = {
  sm: { padding: '5px 12px', fontSize: 12, borderRadius: 6 },
  md: { padding: '8px 16px', fontSize: 13, borderRadius: 8 },
};

export function Btn({ variant = 'primary', size = 'md', loading, children, style, disabled, ...rest }: BtnProps) {
  const type = rest.type ?? 'button';
  return (
    <button {...rest} type={type} disabled={disabled || loading} style={{
      display: 'inline-flex', alignItems: 'center', gap: 6,
      fontWeight: 500, cursor: (disabled || loading) ? 'not-allowed' : 'pointer',
      fontFamily: 'inherit', transition: 'all .12s', whiteSpace: 'nowrap',
      opacity: (disabled || loading) ? .6 : 1,
      ...BV_STYLES[variant], ...BS_SIZES[size], ...style,
    }}>
      {loading ? '…' : children}
    </button>
  );
}

// ── Form ──────────────────────────────────────────────────────────────────────
export function FormGroup({ label, children, error }: { label: string; children: React.ReactNode; error?: string }) {
  return (
    <div style={{ marginBottom: 16 }}>
      <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#374151', marginBottom: 5, letterSpacing: '.2px' }}>
        {label}
      </label>
      {children}
      {error && <p style={{ fontSize: 11, color: '#dc2626', marginTop: 4 }}>{error}</p>}
    </div>
  );
}

const inputBase: React.CSSProperties = {
  width: '100%', padding: '9px 12px',
  border: '1.5px solid #e2e8f0', borderRadius: 8,
  fontSize: 13, color: '#0f172a', background: '#fff',
  fontFamily: 'inherit', boxSizing: 'border-box',
  transition: 'border-color .15s',
};

export function Input(props: React.InputHTMLAttributes<HTMLInputElement>) {
  return <input {...props} style={{ ...inputBase, ...props.style }} />;
}

export function Select({ children, ...props }: React.SelectHTMLAttributes<HTMLSelectElement>) {
  return <select {...props} style={{ ...inputBase, ...props.style }}>{children}</select>;
}

export function Textarea(props: React.TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return <textarea {...props} style={{ ...inputBase, minHeight: 80, resize: 'vertical', ...props.style }} />;
}

// ── Alert ─────────────────────────────────────────────────────────────────────
export function Alert({ type, message }: { type: 'success' | 'error'; message: string }) {
  const s = type === 'success'
    ? { bg: '#f0fdf4', color: '#166534', border: '#bbf7d0', icon: '✓' }
    : { bg: '#fef2f2', color: '#991b1b', border: '#fecaca', icon: '!' };
  return (
    <div style={{
      display: 'flex', alignItems: 'flex-start', gap: 10,
      padding: '12px 16px', borderRadius: 10, marginBottom: 20,
      fontSize: 13, background: s.bg, color: s.color,
      border: `1px solid ${s.border}`,
    }}>
      <span style={{ fontWeight: 700, flexShrink: 0 }}>{s.icon}</span>
      <span>{message}</span>
    </div>
  );
}

// ── Page Header ───────────────────────────────────────────────────────────────
export function PageHeader({ title, subtitle, action }: { title: string; subtitle?: string; action?: React.ReactNode }) {
  return (
    <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 24 }}>
      <div>
        <h1 style={{ fontSize: 20, fontWeight: 800, color: '#0f172a', letterSpacing: '-.4px', margin: 0, lineHeight: 1.2 }}>{title}</h1>
        {subtitle && <p style={{ color: '#64748b', fontSize: 13, marginTop: 4 }}>{subtitle}</p>}
      </div>
      {action}
    </div>
  );
}

// ── Spinner ───────────────────────────────────────────────────────────────────
export function Spinner() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 80 }}>
      <div style={{
        width: 28, height: 28,
        border: '2.5px solid #e2e8f0',
        borderTopColor: '#1a6b3c',
        borderRadius: '50%',
        animation: 'spin .65s linear infinite',
      }} />
    </div>
  );
}

// ── Empty ─────────────────────────────────────────────────────────────────────
export function Empty({ message = 'No data found' }: { message?: string }) {
  return (
    <div style={{ textAlign: 'center', padding: '52px 20px', color: '#94a3b8' }}>
      <div style={{ fontSize: 32, marginBottom: 10, opacity: .35 }}>📭</div>
      <p style={{ fontSize: 13 }}>{message}</p>
    </div>
  );
}

// ── Modal ─────────────────────────────────────────────────────────────────────
export function Modal({
  open,
  onClose,
  title,
  children,
  maxWidth = 520,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  maxWidth?: number;
}) {
  if (!open) return null;
  return (
    <div style={{ position: 'fixed', inset: 0, zIndex: 200, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
      <div onClick={onClose} style={{ position: 'absolute', inset: 0, background: 'rgba(15,23,42,.4)', backdropFilter: 'blur(2px)' }} />
      <div style={{
        position: 'relative', background: '#fff', borderRadius: 16,
        padding: 28, width: '100%', maxWidth,
        maxHeight: '90vh', overflowY: 'auto',
        boxShadow: '0 24px 64px rgba(0,0,0,.15)',
        animation: 'fadeUp .2s ease',
      }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 22 }}>
          <h2 style={{ fontSize: 16, fontWeight: 700, color: '#0f172a', margin: 0 }}>{title}</h2>
          <button onClick={onClose} style={{
            background: '#f1f5f9', border: 'none', cursor: 'pointer',
            width: 28, height: 28, borderRadius: 6,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            fontSize: 16, color: '#64748b', lineHeight: 1,
          }}>×</button>
        </div>
        {children}
      </div>
    </div>
  );
}

// ── Grid ──────────────────────────────────────────────────────────────────────
export function Grid({ cols = 2, children, style }: { cols?: number; children: React.ReactNode; style?: React.CSSProperties }) {
  return (
    <div style={{ display: 'grid', gridTemplateColumns: `repeat(${cols}, 1fr)`, gap: 16, marginBottom: 16, ...style }}>
      {children}
    </div>
  );
}

// ── Code ──────────────────────────────────────────────────────────────────────
export function Code({ children }: { children: React.ReactNode }) {
  return (
    <code style={{
      background: '#f1f5f9', padding: '2px 7px',
      borderRadius: 5, fontSize: 12, color: '#475569',
      fontFamily: 'ui-monospace, monospace',
    }}>{children}</code>
  );
}

// ── Divider ───────────────────────────────────────────────────────────────────
export function Divider() {
  return <hr style={{ border: 'none', borderTop: '1px solid #f1f5f9', margin: '16px 0' }} />;
}
