import React, { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import api from '../../lib/api';
import { toastError, toastSuccess } from '../../lib/toast';
import { Alert, Btn, Card, CardBody, CardHeader, Empty, FormGroup, Input, PageHeader, Select, Spinner, Textarea } from '../../components/UI';

type Student = { id: number; first_name: string; last_name: string; student_number: string | null; admission_number: string; class_name?: string; stream?: string };
type Item = { id: number; item_name: string; size: string | null; item_code: string; unit_price: number; quantity_in_stock: number; is_active: boolean; category_name: string };
type Cart = Item & { quantity: number };
const money = (v: any) => `$${Number(v ?? 0).toFixed(2)}`;

export default function RecordPurchasePage() {
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [studentSearch, setStudentSearch] = useState('');
  const [itemSearch, setItemSearch] = useState('');
  const [student, setStudent] = useState<Student | null>(null);
  const [cart, setCart] = useState<Cart[]>([]);
  const [useCredit, setUseCredit] = useState(false);
  const [remainderAmount, setRemainderAmount] = useState('0');
  const [remainderMethod, setRemainderMethod] = useState('cash');
  const [remainderTouched, setRemainderTouched] = useState(false);
  const [referenceNumber, setReferenceNumber] = useState('');
  const [notes, setNotes] = useState('');
  const [error, setError] = useState('');

  const { data: students = [] } = useQuery<Student[]>({
    queryKey: ['shop-student-search', studentSearch],
    queryFn: () => api.get('/shop/purchases/search-students', { params: { search: studentSearch } }).then(r => r.data),
    enabled: studentSearch.length >= 2,
    placeholderData: keepPreviousData,
  });
  const { data: balanceData } = useQuery({
    queryKey: ['student-balance', student?.id],
    queryFn: () => api.get(`/finance/student/${student!.id}/balance`).then(r => r.data),
    enabled: !!student,
  });
  const availableCredit = Math.max(0, -Number(balanceData?.balance ?? 0));

  useEffect(() => {
    if (availableCredit <= 0) setUseCredit(false);
  }, [student?.id, availableCredit]);

  const { data: items = [], isLoading } = useQuery<Item[]>({
    queryKey: ['shop-active-items', itemSearch],
    queryFn: () => api.get('/shop/items', { params: { search: itemSearch || undefined, active: 1 } }).then(r => r.data),
    // Without this, every keystroke in the item search box changes the query key,
    // isLoading flips true again, and the whole page — student search, cart, everything —
    // unmounts to a blank spinner and remounts. Feels like a full page reload.
    placeholderData: keepPreviousData,
  });

  // Same item name, different size = different stock rows (a school with "7 Large, 0
  // Small" needs each size's stock tracked and offered separately) — group them back
  // together here purely for display, so staff see one item with a size picker instead
  // of disconnected rows that happen to share a name.
  const groupedItems = useMemo(() => {
    const map = new Map<string, Item[]>();
    for (const i of items) {
      if (!map.has(i.item_name)) map.set(i.item_name, []);
      map.get(i.item_name)!.push(i);
    }
    return Array.from(map.values());
  }, [items]);

  const total = useMemo(() => cart.reduce((sum, i) => sum + Number(i.unit_price) * i.quantity, 0), [cart]);
  const creditApplied = useCredit ? Math.min(availableCredit, total) : 0;
  const remainingAfterCredit = Math.max(0, total - creditApplied);

  // Keep the "pay via other method" field defaulted to whatever credit doesn't cover,
  // unless the bursar has deliberately typed a different amount (e.g. paying less now).
  useEffect(() => {
    if (!remainderTouched) setRemainderAmount(remainingAfterCredit.toFixed(2));
  }, [remainingAfterCredit, remainderTouched]);

  useEffect(() => { setRemainderTouched(false); }, [total]);

  const balance = Math.max(0, total - creditApplied - Number(remainderAmount || 0));

  const addItem = (item: Item) => {
    if (!item.is_active) return toastError('This item cannot be sold.');
    setCart(current => {
      const existing = current.find(i => i.id === item.id);
      if (existing) return current.map(i => i.id === item.id ? { ...i, quantity: i.quantity + 1 } : i);
      return [...current, { ...item, quantity: 1 }];
    });
  };

  // No upper clamp to available stock here — ordering more than what's in stock is exactly
  // what turns this into a preorder (see isPreorder below); the backend still refuses a
  // non-preorder order that exceeds stock.
  const setQty = (id: number, quantity: number) => {
    setCart(current => current.map(i => i.id === id ? { ...i, quantity: Math.max(1, quantity) } : i));
  };

  const isPreorder = cart.some(i => i.quantity > i.quantity_in_stock);

  const save = useMutation({
    mutationFn: async () => {
      const paymentDate = new Date().toISOString().slice(0, 10);
      const remainder = Number(remainderAmount || 0);

      const res = await api.post('/shop/purchases', {
        student_id: student?.id,
        purchase_date: paymentDate,
        notes,
        items: cart.map(i => ({ shop_item_id: i.id, quantity: i.quantity })),
        payment_amount: remainder,
        payment_method: remainderMethod,
        reference_number: referenceNumber || null,
        payment_date: paymentDate,
        is_preorder: isPreorder,
      });

      if (creditApplied > 0) {
        await api.post('/shop/payments', {
          student_purchase_id: res.data.purchase_id,
          amount: creditApplied,
          payment_method: 'account_credit',
          payment_date: paymentDate,
        });
      }

      return res;
    },
    onSuccess: (r) => {
      qc.invalidateQueries({ queryKey: ['shop-items'] });
      qc.invalidateQueries({ queryKey: ['student-balance'] });
      toastSuccess(isPreorder ? 'Preorder recorded — fulfill it from the Preorders queue once stock arrives.' : 'Purchase recorded.');
      navigate(`/app/shop/purchases/${r.data.purchase_id}`);
    },
    onError: (e: any) => setError(e.response?.data?.message ?? 'Could not record purchase.'),
  });

  const submit = () => {
    setError('');
    if (!student) return setError('Select a student first.');
    if (cart.length === 0) return setError('Add at least one item to the cart.');
    if (creditApplied + Number(remainderAmount || 0) > total + 0.01) return setError('Total payment cannot exceed purchase total.');
    if (isPreorder && creditApplied + Number(remainderAmount || 0) <= 0) return setError('This order exceeds available stock, so it will be a preorder — a deposit or full payment is required to reserve it.');
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
                {student && (
                  <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                    <strong>{student.first_name} {student.last_name}</strong><br />
                    <span className="text-xs text-slate-600">{student.student_number ?? student.admission_number}</span>
                    {availableCredit > 0 && <div className="mt-1 text-xs font-semibold text-emerald-700">💳 {money(availableCredit)} fee credit available</div>}
                  </div>
                )}
                {students.map(s => <button key={s.id} className="rounded-lg border border-slate-200 bg-white p-3 text-left hover:border-emerald-500" onClick={() => setStudent(s)}><strong>{s.first_name} {s.last_name}</strong><br /><span className="text-xs text-slate-500">{s.student_number ?? s.admission_number} {s.class_name ? `- ${s.class_name} ${s.stream ?? ''}` : ''}</span></button>)}
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Item Search" />
            <CardBody>
              <Input placeholder="Search item name or code" value={itemSearch} onChange={e => setItemSearch(e.target.value)} />
            </CardBody>
            {groupedItems.length === 0 ? <div className="p-4"><Empty message="No matching items." /></div> : (
              <div className="divide-y divide-slate-100">
                {groupedItems.map(group => {
                  const inCart = (v: Item) => cart.find(c => c.id === v.id);
                  return (
                    <div key={group[0].item_name} className="p-3">
                      <div className="flex items-center justify-between">
                        <strong>{group[0].item_name}</strong>
                        <span className="text-xs text-slate-500">{money(group[0].unit_price)} · {group[0].category_name}</span>
                      </div>
                      <div className="mt-2 flex flex-wrap gap-2">
                        {group.map(v => {
                          const has = inCart(v);
                          const out = v.quantity_in_stock <= 0;
                          return (
                            <button
                              key={v.id}
                              disabled={!v.is_active}
                              onClick={() => addItem(v)}
                              className={`rounded-lg border px-3 py-2 text-left text-xs disabled:cursor-not-allowed disabled:opacity-50 ${
                                has ? 'border-emerald-500 bg-emerald-50' : out ? 'border-amber-300 bg-amber-50 hover:border-amber-400' : 'border-slate-200 bg-white hover:border-emerald-400'
                              }`}
                            >
                              <div className="font-semibold">{v.size ?? 'Standard'}{has ? ` · in cart (${has.quantity})` : ''}</div>
                              <div className={out ? 'font-semibold text-amber-700' : 'text-slate-500'}>{out ? 'Preorder — 0 in stock' : `${v.quantity_in_stock} in stock`}</div>
                            </button>
                          );
                        })}
                      </div>
                      <div className="mt-1 text-[11px] text-slate-400">{group[0].item_code}{group.length > 1 ? ` and ${group.length - 1} more size(s)` : ''}</div>
                    </div>
                  );
                })}
              </div>
            )}
          </Card>
        </div>

        <Card>
          <CardHeader title="Purchase Cart" />
          <CardBody>
            {cart.length === 0 ? <Empty message="Cart is empty." /> : (
              <div className="space-y-3">
                {cart.map(i => {
                  const short = i.quantity > i.quantity_in_stock;
                  return (
                    <div key={i.id} className={`rounded-lg border p-3 ${short ? 'border-amber-300 bg-amber-50' : 'border-slate-200'}`}>
                      <div className="flex justify-between gap-3">
                        <strong>{i.item_name}{i.size && ` (${i.size})`}</strong>
                        <button className="text-sm font-semibold text-red-600" onClick={() => setCart(c => c.filter(x => x.id !== i.id))}>Remove</button>
                      </div>
                      <div className="mt-2 flex items-center justify-between gap-2">
                        <Input type="number" min="1" value={i.quantity} onChange={e => setQty(i.id, Number(e.target.value))} style={{ width: 90 }} />
                        <span className="font-bold text-emerald-700">{money(Number(i.unit_price) * i.quantity)}</span>
                      </div>
                      <div className={`mt-1 text-xs ${short ? 'font-semibold text-amber-700' : 'text-slate-500'}`}>
                        {short ? `⚠ Only ${i.quantity_in_stock} in stock — this line will be a preorder` : `Available stock: ${i.quantity_in_stock}`}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
            {isPreorder && (
              <div className="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800">
                📦 This order exceeds current stock, so it will be recorded as a <strong>preorder</strong> — stock isn't reserved yet, and a deposit or full payment is required now to hold it. Fulfill it from the Preorders queue once new stock arrives.
              </div>
            )}
            <div className="mt-5 rounded-lg bg-slate-50 p-4">
              <div className="flex justify-between text-sm"><span>Total</span><strong>{money(total)}</strong></div>

              {student && (
                <label className={`mt-3 flex items-center justify-between rounded-lg border p-3 text-sm ${availableCredit > 0 ? 'cursor-pointer border-emerald-200 bg-emerald-50' : 'cursor-not-allowed border-slate-200 bg-slate-100 opacity-60'}`}>
                  <span className="flex items-center gap-2">
                    <input type="checkbox" checked={useCredit} disabled={availableCredit <= 0} onChange={e => { setUseCredit(e.target.checked); setRemainderTouched(false); }} />
                    💳 Use fee account credit
                  </span>
                  <strong className={availableCredit > 0 ? 'text-emerald-700' : 'text-slate-500'}>
                    {availableCredit > 0 ? `${money(availableCredit)} available` : 'No credit available'}
                  </strong>
                </label>
              )}
              {useCredit && (
                <div className="mt-2 flex justify-between text-sm text-emerald-700">
                  <span>Applied from credit</span><strong>-{money(creditApplied)}</strong>
                </div>
              )}

              <div className="mt-3 grid grid-cols-2 gap-3">
                <FormGroup label={useCredit ? 'Pay Remaining Via' : 'Pay Now'}>
                  <Input type="number" min="0" max={remainingAfterCredit} step="0.01" value={remainderAmount}
                    onChange={e => { setRemainderTouched(true); setRemainderAmount(e.target.value); }} />
                </FormGroup>
                <FormGroup label="Method">
                  <Select value={remainderMethod} onChange={e => setRemainderMethod(e.target.value)}>
                    <option value="cash">Cash</option>
                    <option value="ecocash">EcoCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="swipe">Swipe / Card</option>
                    <option value="online">Online</option>
                    <option value="other">Other</option>
                  </Select>
                </FormGroup>
              </div>
              {Number(remainderAmount || 0) > 0 && (
                <FormGroup label="Reference Number (optional)">
                  <Input value={referenceNumber} onChange={e => setReferenceNumber(e.target.value)} placeholder="EcoCash ref, bank ref…" />
                </FormGroup>
              )}
              {useCredit && remainingAfterCredit > 0 && Number(remainderAmount || 0) < remainingAfterCredit && (
                <div className="mt-1 text-xs text-slate-500">
                  Credit doesn't fully cover the total — add the rest above, or leave some as an outstanding balance to collect later.
                </div>
              )}

              <div className="mt-2 flex justify-between text-sm"><span>Balance</span><strong className={balance > 0 ? 'text-red-600' : 'text-emerald-700'}>{money(balance)}</strong></div>
            </div>
            <FormGroup label="Notes"><Textarea value={notes} onChange={e => setNotes(e.target.value)} /></FormGroup>
            <Btn loading={save.isPending} onClick={submit} style={{ width: '100%', justifyContent: 'center' }}>{isPreorder ? 'Save Preorder' : 'Save Purchase'}</Btn>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
