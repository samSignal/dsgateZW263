import { useEffect, useState } from 'react';

/**
 * Drives the two print paths a receipt offers: the normal full-page layout, and a compact
 * layout sized for a 58mm Bluetooth thermal receipt printer (see .thermal-mode in app.css).
 * Switching mode just re-renders the content so the user can preview it before printing;
 * mode reverts to 'full' once the print dialog closes (or is cancelled), so the on-screen
 * view never gets stuck showing the narrow layout.
 */
export function usePrintMode() {
  const [mode, setMode] = useState<'full' | 'thermal'>('full');

  useEffect(() => {
    const revert = () => setMode('full');
    window.addEventListener('afterprint', revert);
    return () => window.removeEventListener('afterprint', revert);
  }, []);

  return { mode, setMode, print: () => window.print() };
}
