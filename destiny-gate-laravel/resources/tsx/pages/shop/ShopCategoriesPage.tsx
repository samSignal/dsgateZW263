import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess, confirmDelete } from '../../lib/toast';
import { Alert, Badge, Btn, Card, Empty, FormGroup, Input, Modal, PageHeader, Spinner, Table, Td, Textarea } from '../../components/UI';

type Category = { id: number; name: string; description: string | null; is_active: boolean; items_count: number };
const empty = { name: '', description: '' };

export default function ShopCategoriesPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Category | null>(null);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');
  const { data = [], isLoading } = useQuery<Category[]>({ queryKey: ['shop-categories'], queryFn: () => api.get('/shop/categories').then(r => r.data) });

  const save = useMutation({
    mutationFn: () => editing ? api.put(`/shop/categories/${editing.id}`, form) : api.post('/shop/categories', form),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-categories'] }); setOpen(false); toastSuccess(editing ? 'Category updated.' : 'Category created.'); },
    onError: (e: any) => setError(e.response?.data?.message ?? 'Could not save category.'),
  });
  const toggle = useMutation({
    mutationFn: (c: Category) => api.post(`/shop/categories/${c.id}/${c.is_active ? 'deactivate' : 'activate'}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-categories'] }); toastSuccess('Category status updated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not update status.'),
  });
  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/shop/categories/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-categories'] }); toastSuccess('Category deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete category while items are linked.'),
  });

  const openAdd = () => { setEditing(null); setForm(empty); setError(''); setOpen(true); };
  const openEdit = (c: Category) => { setEditing(c); setForm({ name: c.name, description: c.description ?? '' }); setError(''); setOpen(true); };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Shop Categories" subtitle="Manage uniform, book, stationery, sportswear, trip, and other item groups" action={<Btn onClick={openAdd}>Add Category</Btn>} />
      <Card>
        {(data as Category[]).length === 0 ? <Empty message="No shop categories found." /> : (
          <Table headers={['Category', 'Description', 'Items', 'Status', 'Actions']}>
            {data.map(c => (
              <tr key={c.id}>
                <Td><strong>{c.name}</strong></Td>
                <Td>{c.description ?? 'No description'}</Td>
                <Td>{c.items_count}</Td>
                <Td><Badge variant={c.is_active ? 'green' : 'gray'}>{c.is_active ? 'Active' : 'Inactive'}</Badge></Td>
                <Td>
                  <div className="flex flex-wrap gap-2">
                    <Btn size="sm" variant="outline" onClick={() => openEdit(c)}>Edit</Btn>
                    <Btn size="sm" variant="outline" onClick={() => toggle.mutate(c)}>{c.is_active ? 'Deactivate' : 'Activate'}</Btn>
                    <Btn size="sm" variant="danger" disabled={c.items_count > 0} onClick={async () => { if (await confirmDelete(c.name)) del.mutate(c.id); }}>Delete</Btn>
                  </div>
                </Td>
              </tr>
            ))}
          </Table>
        )}
      </Card>
      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Category' : 'Add Category'}>
        {error && <Alert type="error" message={error} />}
        <FormGroup label="Name"><Input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} /></FormGroup>
        <FormGroup label="Description"><Textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} /></FormGroup>
        <div className="flex justify-end gap-2"><Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn><Btn loading={save.isPending} onClick={() => save.mutate()}>Save</Btn></div>
      </Modal>
    </div>
  );
}
