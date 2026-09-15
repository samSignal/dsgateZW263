import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import api from '../../lib/api';
import { toastError, toastSuccess, confirmDelete } from '../../lib/toast';
import { Alert, Badge, Btn, Card, Empty, FormGroup, Input, Modal, PageHeader, Select, Spinner, Table, Td, Textarea } from '../../components/UI';

type Category = { id: number; name: string };
type Item = { id: number; shop_category_id: number; category_name: string; item_name: string; size: string | null; item_code: string; description: string | null; unit_price: number; quantity_in_stock: number; reorder_level: number; is_active: boolean; is_low_stock: number };
const empty = { shop_category_id: '', item_name: '', size: '', item_code: '', description: '', unit_price: '', quantity_in_stock: '0', reorder_level: '0', is_active: true };
const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function ShopItemsPage() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Item | null>(null);
  const [form, setForm] = useState(empty);
  const [error, setError] = useState('');

  const { data: categories = [] } = useQuery<Category[]>({ queryKey: ['shop-categories'], queryFn: () => api.get('/shop/categories').then(r => r.data) });
  const { data: items = [], isLoading } = useQuery<Item[]>({
    queryKey: ['shop-items', search, categoryId],
    queryFn: () => api.get('/shop/items', { params: { search: search || undefined, category_id: categoryId || undefined } }).then(r => r.data),
    // Without this, every keystroke in the search box changes the query key, isLoading
    // flips true again, and the whole page (search box included) unmounts to a blank
    // spinner and remounts — looks and feels exactly like a full page reload.
    placeholderData: keepPreviousData,
  });

  const payload = () => ({
    ...form,
    shop_category_id: Number(form.shop_category_id),
    unit_price: Number(form.unit_price),
    quantity_in_stock: Number(form.quantity_in_stock),
    reorder_level: Number(form.reorder_level),
  });
  const save = useMutation({
    mutationFn: () => editing ? api.put(`/shop/items/${editing.id}`, payload()) : api.post('/shop/items', payload()),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-items'] }); setOpen(false); toastSuccess(editing ? 'Item updated.' : 'Item created.'); },
    onError: (e: any) => setError(e.response?.data?.message ?? 'Could not save item.'),
  });
  const toggle = useMutation({
    mutationFn: (i: Item) => api.post(`/shop/items/${i.id}/${i.is_active ? 'deactivate' : 'activate'}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-items'] }); toastSuccess('Item status updated.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Could not update item.'),
  });
  const del = useMutation({
    mutationFn: (id: number) => api.delete(`/shop/items/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['shop-items'] }); toastSuccess('Item deleted.'); },
    onError: (e: any) => toastError(e.response?.data?.message ?? 'Cannot delete item with purchase history.'),
  });

  const openAdd = () => { setEditing(null); setForm({ ...empty, shop_category_id: String(categories[0]?.id ?? '') }); setError(''); setOpen(true); };
  const openEdit = (i: Item) => {
    setEditing(i);
    setForm({ shop_category_id: String(i.shop_category_id), item_name: i.item_name, size: i.size ?? '', item_code: i.item_code, description: i.description ?? '', unit_price: String(i.unit_price), quantity_in_stock: String(i.quantity_in_stock), reorder_level: String(i.reorder_level), is_active: i.is_active });
    setError('');
    setOpen(true);
  };
  // Same item, a different size — each size needs its own stock row (a school might have
  // 7 Large in stock and 0 Small), so this is a *new* item, just pre-filled from an
  // existing one to save re-typing category/price/description. Code and size are left for
  // the item_code-unique constraint and the new size to be filled in.
  const openAddSize = (i: Item) => {
    setEditing(null);
    setForm({ shop_category_id: String(i.shop_category_id), item_name: i.item_name, size: '', item_code: '', description: i.description ?? '', unit_price: String(i.unit_price), quantity_in_stock: '0', reorder_level: String(i.reorder_level), is_active: true });
    setError('');
    setOpen(true);
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Shop Items" subtitle="Manage saleable school items, prices, and stock levels" action={<Btn onClick={openAdd}>Add Item</Btn>} />
      <div className="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <Input placeholder="Search item name or code" value={search} onChange={e => setSearch(e.target.value)} />
        <Select value={categoryId} onChange={e => setCategoryId(e.target.value)}><option value="">All categories</option>{categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</Select>
      </div>
      <Card>
        {items.length === 0 ? <Empty message="No shop items found." /> : (
          <Table headers={['Item', 'Size', 'Category', 'Price', 'Stock', 'Status', 'Actions']}>
            {items.map(i => (
              <tr key={i.id}>
                <Td><strong>{i.item_name}</strong><br /><span className="text-xs text-slate-500">{i.item_code}</span></Td>
                <Td>{i.size ?? <span className="text-slate-400">—</span>}</Td>
                <Td>{i.category_name}</Td>
                <Td>{money(i.unit_price)}</Td>
                <Td><Badge variant={i.is_low_stock ? 'red' : 'green'}>{i.quantity_in_stock} in stock</Badge><br /><span className="text-xs text-slate-500">Reorder {i.reorder_level}</span></Td>
                <Td><Badge variant={i.is_active ? 'green' : 'gray'}>{i.is_active ? 'Active' : 'Inactive'}</Badge></Td>
                <Td><div className="flex flex-wrap gap-2"><Btn size="sm" variant="outline" onClick={() => openEdit(i)}>Edit</Btn><Btn size="sm" variant="outline" onClick={() => openAddSize(i)}>+ Size</Btn><Btn size="sm" variant="outline" onClick={() => toggle.mutate(i)}>{i.is_active ? 'Deactivate' : 'Activate'}</Btn><Btn size="sm" variant="danger" onClick={async () => { if (await confirmDelete(i.item_name)) del.mutate(i.id); }}>Delete</Btn></div></Td>
              </tr>
            ))}
          </Table>
        )}
      </Card>
      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit Item' : 'Add Item'}>
        {error && <Alert type="error" message={error} />}
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
          <FormGroup label="Category"><Select value={form.shop_category_id} onChange={e => setForm(f => ({ ...f, shop_category_id: e.target.value }))}>{categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</Select></FormGroup>
          <FormGroup label="Item Code"><Input value={form.item_code} onChange={e => setForm(f => ({ ...f, item_code: e.target.value }))} /></FormGroup>
          <FormGroup label="Item Name"><Input value={form.item_name} onChange={e => setForm(f => ({ ...f, item_name: e.target.value }))} /></FormGroup>
          <FormGroup label="Size / Option"><Input placeholder="e.g. 34, S/M/L, Standard" value={form.size} onChange={e => setForm(f => ({ ...f, size: e.target.value }))} /></FormGroup>
          <FormGroup label="Unit Price"><Input type="number" min="0" step="0.01" value={form.unit_price} onChange={e => setForm(f => ({ ...f, unit_price: e.target.value }))} /></FormGroup>
          <FormGroup label="Stock"><Input type="number" min="0" value={form.quantity_in_stock} onChange={e => setForm(f => ({ ...f, quantity_in_stock: e.target.value }))} /></FormGroup>
          <FormGroup label="Reorder Level"><Input type="number" min="0" value={form.reorder_level} onChange={e => setForm(f => ({ ...f, reorder_level: e.target.value }))} /></FormGroup>
        </div>
        <FormGroup label="Description"><Textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} /></FormGroup>
        <div className="flex justify-end gap-2"><Btn variant="outline" onClick={() => setOpen(false)}>Cancel</Btn><Btn loading={save.isPending} onClick={() => save.mutate()}>Save</Btn></div>
      </Modal>
    </div>
  );
}
