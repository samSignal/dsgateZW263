import Swal from 'sweetalert2';

// ── Branded mixin ─────────────────────────────────────────────────────────────
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3500,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer);
    toast.addEventListener('mouseleave', Swal.resumeTimer);
  },
  customClass: {
    popup: 'dg-toast',
  },
});

export function toastSuccess(message: string) {
  Toast.fire({ icon: 'success', title: message });
}

export function toastError(message: string) {
  Toast.fire({ icon: 'error', title: message, timer: 5000 });
}

export function toastWarning(message: string) {
  Toast.fire({ icon: 'warning', title: message });
}

export function toastInfo(message: string) {
  Toast.fire({ icon: 'info', title: message });
}

// ── Confirm dialog ────────────────────────────────────────────────────────────
export async function confirmDelete(itemName = 'this record'): Promise<boolean> {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: `This will permanently delete ${itemName}.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
    customClass: { popup: 'dg-confirm' },
  });
  return result.isConfirmed;
}

export async function confirmAction(title: string, text: string, confirmText = 'Yes, proceed'): Promise<boolean> {
  const result = await Swal.fire({
    title,
    text,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#8a6b34',
    cancelButtonColor: '#6b7280',
    confirmButtonText: confirmText,
    cancelButtonText: 'Cancel',
    reverseButtons: true,
    customClass: { popup: 'dg-confirm' },
  });
  return result.isConfirmed;
}
