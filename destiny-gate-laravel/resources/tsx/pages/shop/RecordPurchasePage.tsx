import React, { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Alert, Badge, Btn, Card, CardBody, CardHeader, Empty, FormGroup, Input, PageHeader, Select, Spinner, Table, Td, Textarea } from '../../components/UI';

type Student = { id: number; first_name: string; last_name: string; student_number: string | null; admission_number: string; class_name?: string; stream?: string };
type Item = { id: number; item_name: string; item_code: string; unit_price: number; quantity_in_stock: number; is_active: boolean; category_name: string };
type Cart = Item & { quantity: number };
const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function RecordPurchasePage() {
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [studentSearch, setStudentSearch] = useState('');
  const [itemSearch, setItemSearch] = useState('');
  const [student, setStudent] = useState<Student | null>(null);
  const [cart, setCart] = useState<Cart[]>([]);
  const [paymentAmount, setPaymentAmount] = useState('0');
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [notes, setNotes] = useState('');
  const [error, setError] = useState('');

  const { data: students = [] } = useQuery<Student[]>({
    queryKey: ['shop-student-search', studentSearch],
    queryFn: () => api.get('/shop/purchases/search-students', { params: { search: studentSearch } }).then(r => r.data),
    enabled: studentSearch.length >= 2,
  });
  const { data: items = [], isLoading } = useQuery<Item[]>({
    queryKey: ['shop-active-items', itemSearch],
    queryFn: () => api.get('/shop/items', { params: { search: itemSearch || undefined, active: 1 } }).then(r => r.data),
  });

  const total = useMemo(() => cart.reduce((sum, i) => sum + Number(i.unit_price) * i.quantity, 0), [cart]);
  const balance = Math.max(0, total - Number(paymentAmount || 0));

  const addItem = (item: Item) => {
    if (!item.is_active || item.quantity_in_stock <= 0) return toastError('This item cannot be sold.');
    setCart(current => {
      const existing = current.find(i => i.id === item.id);
      if (existing) {
        if (existing.quantity + 1 > item.quantity_in_stock) return current;
        return current.map(i => i.id === item.id ? { ...i, quantity: i.quantity + 1 } : i);
      }
      return [...current, { ...item, quantity: 1 }];
    });
  };

  const setQty = (id: number, quantity: number) => {
    setCart(current => current.map(i => i.id === id ? { ...i, quantity: Math.max(1, Math.min(quantity, i.quantity_in_stock)) } : i));
  };

  const save = useMutation({
    mutationFn: () => api.post('/shop/purchases', {
      student_id: student?.id,
      purchase_date: new Date().toISOString().slice(0, 10),
      notes,
      items: cart.map(i => ({ shop_item_id: i.id, quantity: i.quantity })),
      payment_amount: Number(paymentAmount || 0),
      payment_method: paymentMethod,
      payment_date: new Date().toISOString().slice(0, 10),
    }),
    onSuccess: (r) => {
      qc.invalidateQueries({ queryKey: ['shop-items'] });
      toastSuccess('Purchase recorded.');
      navigate(`/app/shop/purchases/${r.data.purchase_id}`);
    },
    onError: (e: any) => setError(e.response?.data?.message ?? 'Could not record purchase.'),
  });

  const submit = () => {
    setError('');
    if (!student) return setError('Select a student first.');
    if (cart.length === 0) return setError('Add at least one item to the cart.');
    if (Number(paymentAmount || 0) > total) return setError('Payment cannot exceed purchase total.');
    save.mutate();
  };

  if (isLoading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Record Student Purchase" subtitle="Search a student, add items to the cart, and capture payment status immediately" />
      {error && <Alert type="error" message={error} />}
      <div className="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_420px]">
        <div className="space-y-5">
          <Card>
            <CardHeader title="Student Search" />
            <CardBody>
              <Input placeholder="Search by student number, admission number, or name" value={studentSearch} onChange={e => setStudentSearch(e.target.value)} />
              <div className="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                {student && <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3"><strong>{student.first_name} {student.last_name}</strong><br /><span className="text-xs text-slate-600">{student.student_number ?? student.admission_number}</span></div>}
                {students.map(s => <button key={s.id} className="rounded-lg border border-slate-200 bg-white p-3 text-left hover:border-emerald-500" onClick={() => setStudent(s)}><strong>{s.first_name} {s.last_name}</strong><br /><span className="text-xs text-slate-500">{s.student_number ?? s.admission_number} {s.class_name ? `- ${s.class_name} ${s.stream ?? ''}` : ''}</span></button>)}
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Item Search" />
            <CardBody>
              <Input placeholder="Search item name or code" value={itemSearch} onChange={e => setItemSearch(e.target.value)} />
            </CardBody>
            <Table headers={['Item', 'Price', 'Stock', 'Action']}>
              {items.map(i => (
                <tr key={i.id}>
                  <Td><strong>{i.item_name}</strong><br /><span className="text-xs text-slate-500">{i.item_code} - {i.category_name}</span></Td>
                  <Td>{money(i.unit_price)}</Td>
                  <Td><Badge variant={i.quantity_in_stock <= 0 ? 'red' : 'green'}>{i.quantity_in_stock}</Badge></Td>
                  <Td><Btn size="sm" variant="outline" disabled={!i.is_active || i.quantity_in_stock <= 0} onClick={() => addItem(i)}>Add</Btn></Td>
                </tr>
              ))}
              {items.length === 0 && <tr><Td colSpan={4}><Empty message="No matching items." /></Td></tr>}
            </Table>
          </Card>
        </div>

        <Card>
          <CardHeader title="Purchase Cart" />
          <CardBody>
            {cart.length === 0 ? <Empty message="Cart is empty." /> : (
              <div className="space-y-3">
                {cart.map(i => (
                  <div key={i.id} className="rounded-lg border border-slate-200 p-3">
                    <div className="flex justify-between gap-3"><strong>{i.item_name}</strong><button className="text-sm font-semibold text-red-600" onClick={() => setCart(c => c.filter(x => x.id !== i.id))}>Remove</button></div>
                    <div className="mt-2 flex items-center justify-between gap-2">
                      <Input type="number" min="1" max={i.quantity_in_stock} value={i.quantity} onChange={e => setQty(i.id, Number(e.target.value))} style={{ width: 90 }} />
                      <span className="font-bold text-emerald-700">{money(Number(i.unit_price) * i.quantity)}</span>
                    </div>
                    <div className="mt-1 text-xs text-slate-500">Available stock: {i.quantity_in_stock}</div>
                  </div>
                ))}
              </div>
            )}
            <div className="mt-5 rounded-lg bg-slate-50 p-4">
              <div className="flex justify-between text-sm"><span>Total</span><strong>{money(total)}</strong></div>
              <div className="mt-3 grid grid-cols-2 gap-3">
                <FormGroup label="Pay Now"><Input type="number" min="0" step="0.01" value={paymentAmount} onChange={e => setPaymentAmount(e.target.value)} /></FormGroup>
                <FormGroup label="Method"><Select value={paymentMethod} onChange={e => setPaymentMethod(e.target.value)}><option value="cash">Cash</option><option value="ecocash">EcoCash</option><option value="bank_transfer">Bank Transfer</option><option value="swipe">Swipe</option><option value="online">Online</option><option value="other">Other</option></Select></FormGroup>
              </div>
              <div className="flex justify-between text-sm"><span>Balance</span><strong className={balance > 0 ? 'text-red-600' : 'text-emerald-700'}>{money(balance)}</strong></div>
            </div>
            <FormGroup label="Notes"><Textarea value={notes} onChange={e => setNotes(e.target.value)} /></FormGroup>
            <Btn loading={save.isPending} onClick={submit} style={{ width: '100%', justifyContent: 'center' }}>Save Purchase</Btn>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
