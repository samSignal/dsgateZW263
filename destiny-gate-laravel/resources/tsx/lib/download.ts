import api from './api';

/** Fetches a report export (PDF/CSV) through the authenticated axios client and triggers a browser download. */
export async function downloadReport(url: string, params: Record<string, any>, filename: string) {
  const res = await api.get(url, { params, responseType: 'blob' });
  const href = URL.createObjectURL(new Blob([res.data], { type: res.headers['content-type'] || 'application/octet-stream' }));
  const a = document.createElement('a');
  a.href = href;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(href);
}
