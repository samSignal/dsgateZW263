import React, { useEffect, useRef, useState } from 'react';
import { Modal, Btn } from './UI';

const IDLE_LIMIT_MS = 5 * 60 * 1000;   // total idle time before auto-logout
const WARNING_LEAD_MS = 60 * 1000;      // show the "still there?" warning this long before it happens
const ACTIVITY_EVENTS: (keyof WindowEventMap)[] = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];

/**
 * Security requirement: sessions left unattended must not stay signed in indefinitely.
 * Tracks real user activity (mouse/keyboard/touch/scroll) across the whole app and signs
 * out automatically after IDLE_LIMIT_MS of silence, warning the user for the last
 * WARNING_LEAD_MS so a genuinely-idle tab doesn't surprise someone mid-task.
 */
export default function IdleLogoutGuard({ onIdleLogout }: { onIdleLogout: () => void }) {
  const lastActivityRef = useRef(Date.now());
  const warningShownRef = useRef(false);
  const onIdleLogoutRef = useRef(onIdleLogout);
  onIdleLogoutRef.current = onIdleLogout;
  const [secondsLeft, setSecondsLeft] = useState<number | null>(null);

  useEffect(() => {
    const markActivity = () => {
      lastActivityRef.current = Date.now();
      if (warningShownRef.current) {
        warningShownRef.current = false;
        setSecondsLeft(null);
      }
    };
    ACTIVITY_EVENTS.forEach(ev => window.addEventListener(ev, markActivity, { passive: true }));

    const interval = setInterval(() => {
      const remaining = IDLE_LIMIT_MS - (Date.now() - lastActivityRef.current);
      if (remaining <= 0) {
        onIdleLogoutRef.current();
      } else if (remaining <= WARNING_LEAD_MS) {
        warningShownRef.current = true;
        setSecondsLeft(Math.ceil(remaining / 1000));
      }
    }, 1000);

    return () => {
      ACTIVITY_EVENTS.forEach(ev => window.removeEventListener(ev, markActivity));
      clearInterval(interval);
    };
  }, []);

  const stayLoggedIn = () => {
    lastActivityRef.current = Date.now();
    warningShownRef.current = false;
    setSecondsLeft(null);
  };

  return (
    <Modal open={secondsLeft !== null} onClose={stayLoggedIn} title="Still there?">
      <p style={{ fontSize: 14, color: '#374151', marginBottom: 20, lineHeight: 1.6 }}>
        You've been inactive for a while. For security, you'll be signed out automatically in{' '}
        <strong style={{ color: '#dc2626' }}>{secondsLeft}</strong> second{secondsLeft === 1 ? '' : 's'} unless you stay logged in.
      </p>
      <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
        <Btn onClick={stayLoggedIn}>Stay logged in</Btn>
      </div>
    </Modal>
  );
}
