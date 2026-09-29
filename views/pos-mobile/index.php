<?php
$org = Tenant::current();
$base = '/' . $org['slug'];
// This screen is the one place in the app that must survive with zero network —
// so, unlike every other view, it has NO external CDN dependency (no Tailwind,
// no Alpine). Everything is inline in this one file so the service worker can
// cache it completely; a CDN script that never loaded offline would leave this
// exact screen broken, which defeats the point of it.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
<title>Quick Sale · <?= h($org['name']) ?></title>
<link rel="manifest" href="/assets/manifest.webmanifest">
<meta name="theme-color" content="#4f46e5">
<style>
  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
  body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #0f172a; }
  .topbar { position: sticky; top: 0; z-index: 20; background: #0f172a; color: #fff; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; }
  .topbar a { color: #cbd5e1; text-decoration: none; font-size: 13px; }
  .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
  .status-online { background: #22c55e; }
  .status-offline { background: #ef4444; }
  .search-wrap { padding: 10px 12px; background: #fff; border-bottom: 1px solid #e2e8f0; position: sticky; top: 44px; z-index: 19; }
  .search-wrap input { width: 100%; padding: 12px 14px; font-size: 16px; border: 1px solid #cbd5e1; border-radius: 10px; }
  .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 12px; padding-bottom: 100px; }
  .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; text-align: left; }
  .card .name { font-weight: 600; font-size: 15px; line-height: 1.25; margin-bottom: 4px; }
  .card .cat { font-size: 12px; color: #94a3b8; margin-bottom: 8px; }
  .card .price { font-size: 17px; font-weight: 700; color: #4f46e5; }
  .card .stock { float: right; font-size: 12px; color: #94a3b8; }
  .card .stock.low { color: #ef4444; }
  .cart-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #0f172a; color: #fff; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; z-index: 30; }
  .cart-bar .count { background: #4f46e5; border-radius: 999px; padding: 2px 10px; font-size: 13px; margin-right: 8px; }
  .empty { text-align: center; color: #94a3b8; padding: 60px 20px; grid-column: 1 / -1; }

  .sheet-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 40; display: none; }
  .sheet-overlay.open { display: block; }
  .sheet { position: fixed; left: 0; right: 0; bottom: 0; background: #fff; border-radius: 20px 20px 0 0; z-index: 41; max-height: 85vh; overflow-y: auto; transform: translateY(100%); transition: transform 0.2s ease-out; padding: 16px; }
  .sheet.open { transform: translateY(0); }
  .sheet h2 { margin: 0 0 12px; font-size: 17px; }
  .line { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
  .line .lname { flex: 1; font-size: 14px; font-weight: 500; min-width: 0; }
  .line .lprice { font-size: 12px; color: #94a3b8; }
  .qty-input { width: 52px; padding: 6px; text-align: center; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
  .line-total { width: 70px; text-align: right; font-weight: 600; font-size: 14px; }
  .rm-btn { color: #ef4444; background: none; border: none; font-size: 16px; padding: 4px 6px; }
  .totals { padding: 10px 0; font-size: 14px; }
  .totals .row { display: flex; justify-content: space-between; padding: 3px 0; }
  .totals .grand { font-weight: 700; font-size: 17px; border-top: 1px solid #e2e8f0; margin-top: 6px; padding-top: 8px; }
  select, .pill-btn { font-size: 15px; }
  select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 10px; margin-bottom: 10px; }
  .pill-row { display: flex; gap: 8px; margin-bottom: 12px; }
  .pill-btn { flex: 1; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; background: #fff; font-weight: 600; }
  .pill-btn.active { border-color: #4f46e5; background: #eef2ff; color: #4f46e5; }
  .checkout-btn { width: 100%; padding: 15px; background: #4f46e5; color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; }
  .checkout-btn:disabled { background: #cbd5e1; }
  .close-btn { position: absolute; top: 14px; right: 16px; background: none; border: none; font-size: 20px; color: #94a3b8; }
  .toast { position: fixed; top: 54px; left: 12px; right: 12px; z-index: 50; padding: 12px 14px; border-radius: 10px; font-size: 14px; text-align: center; display: none; }
  .toast.show { display: block; }
  .toast.success { background: #dcfce7; color: #166534; }
  .toast.offline { background: #fef3c7; color: #92400e; }
  .toast.error { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>

<div class="topbar">
  <span><span class="status-dot" id="statusDot"></span><span id="statusText">Online</span></span>
  <span><span id="queueBadge"></span> <a href="<?= $base ?>/dashboard">Exit</a></span>
</div>
<div class="search-wrap">
  <input type="text" id="searchBox" placeholder="Search or scan barcode…" autofocus>
</div>
<div id="toast" class="toast"></div>

<div class="grid" id="productGrid"></div>

<div class="cart-bar" onclick="openSheet()">
  <span><span class="count" id="cartCount">0</span> items</span>
  <span id="cartTotal">₹0.00 — View Cart</span>
</div>

<div class="sheet-overlay" id="overlay" onclick="closeSheet()"></div>
<div class="sheet" id="sheet">
  <button class="close-btn" onclick="closeSheet()">✕</button>
  <h2>Cart</h2>
  <div id="cartLines"></div>
  <div class="totals" id="totals"></div>
  <select id="customerSelect"><option value="">Walk-in customer</option></select>
  <div class="pill-row">
    <button class="pill-btn active" data-method="CASH" onclick="selectMethod('CASH')">Cash</button>
    <button class="pill-btn" data-method="UPI" onclick="selectMethod('UPI')">UPI</button>
    <button class="pill-btn" data-method="CREDIT" onclick="selectMethod('CREDIT')">Udhaar</button>
  </div>
  <button class="checkout-btn" id="checkoutBtn" onclick="completeSale()" disabled>Complete Sale</button>
</div>

<script>
const BASE = <?= json_encode($base) ?>;
const DB_NAME = 'tradeposx_offline';
const STORE = 'pending_sales';
let products = [];
let customers = [];
let csrfToken = '';
let cart = [];
let paymentMethod = 'CASH';

function h(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ---- IndexedDB offline queue ----
function openDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, 1);
    req.onupgradeneeded = () => req.result.createObjectStore(STORE, { keyPath: 'client_ref' });
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}
async function queueSale(payload) {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE, 'readwrite');
    tx.objectStore(STORE).put(payload);
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
}
async function getQueuedSales() {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE, 'readonly');
    const req = tx.objectStore(STORE).getAll();
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}
async function removeQueuedSale(clientRef) {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE, 'readwrite');
    tx.objectStore(STORE).delete(clientRef);
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
}
async function updateQueueBadge() {
  const pending = await getQueuedSales();
  document.getElementById('queueBadge').textContent = pending.length > 0 ? `⏳ ${pending.length} pending` : '';
}

async function trySyncQueue() {
  if (!navigator.onLine) return;
  const pending = await getQueuedSales();
  for (const sale of pending) {
    try {
      const res = await fetch(BASE + '/pos-mobile/sync', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(sale)
      });
      const data = await res.json();
      if (data.success) await removeQueuedSale(sale.client_ref);
    } catch (e) { break; } // still offline / server unreachable — stop, retry later
  }
  await updateQueueBadge();
}

// ---- Online/offline UI ----
function updateStatus() {
  const online = navigator.onLine;
  document.getElementById('statusDot').className = 'status-dot ' + (online ? 'status-online' : 'status-offline');
  document.getElementById('statusText').textContent = online ? 'Online' : 'Offline';
  if (online) trySyncQueue();
}
window.addEventListener('online', updateStatus);
window.addEventListener('offline', updateStatus);

// ---- Data load (works offline once cached by the service worker) ----
async function loadData() {
  try {
    const res = await fetch(BASE + '/pos-mobile/data');
    const data = await res.json();
    products = data.products; customers = data.customers; csrfToken = data.csrf_token;
    localStorage.setItem('pos_cache', JSON.stringify({ products, customers, csrfToken }));
  } catch (e) {
    const cached = localStorage.getItem('pos_cache');
    if (cached) { const d = JSON.parse(cached); products = d.products; customers = d.customers; csrfToken = d.csrfToken; }
  }
  renderProducts();
  renderCustomerOptions();
}

function renderCustomerOptions() {
  const sel = document.getElementById('customerSelect');
  sel.innerHTML = '<option value="">Walk-in customer</option>' +
    customers.map(c => `<option value="${c.id}">${h(c.name)} · ${h(c.mobile)}</option>`).join('');
}

function renderProducts(filter) {
  const q = (filter || '').trim().toLowerCase();
  const list = !q ? products.slice(0, 40) : products.filter(p =>
    p.name.toLowerCase().includes(q) || (p.sku||'').toLowerCase().includes(q) || (p.barcode||'') === q
  ).slice(0, 40);
  const grid = document.getElementById('productGrid');
  if (list.length === 0) { grid.innerHTML = '<div class="empty">No products found</div>'; return; }
  grid.innerHTML = list.map(p => `
    <button class="card" onclick="addToCart(${p.id})" style="border:none;cursor:pointer;">
      <div class="stock ${Number(p.current_stock) <= 0 ? 'low' : ''}">${Number(p.current_stock)} ${h(p.unit)}</div>
      <div class="name">${h(p.name)}</div>
      <div class="cat">${h(p.category||'')}</div>
      <div class="price">₹${Number(p.selling_price).toFixed(2)}</div>
    </button>
  `).join('');
}

document.getElementById('searchBox').addEventListener('input', (e) => {
  const val = e.target.value;
  renderProducts(val);
  // Barcode scanners type the full code then send Enter almost instantly — an
  // exact barcode match (not just a substring hit) adds it straight to cart.
  const exact = products.find(p => p.barcode && p.barcode === val.trim());
  if (exact) { addToCart(exact.id); e.target.value = ''; renderProducts(''); }
});

function addToCart(id) {
  const p = products.find(x => x.id === id);
  if (!p) return;
  const existing = cart.find(l => l.id === id);
  if (existing) existing.qty += 1;
  else cart.push({ id: p.id, name: p.name, price: p.selling_price, qty: 1 });
  renderCartBar();
}

function cartTotal() { return cart.reduce((s,l) => s + l.qty * l.price, 0); }
function cartGst() { return cart.reduce((s,l) => { const p = products.find(x=>x.id===l.id); return s + (l.qty*l.price)*((p?.gst_percent||0)/100); }, 0); }
function cartGrandTotal() { return cartTotal() + cartGst(); }

function renderCartBar() {
  document.getElementById('cartCount').textContent = cart.reduce((s,l)=>s+l.qty, 0);
  document.getElementById('cartTotal').textContent = '₹' + cartGrandTotal().toFixed(2) + ' — View Cart';
  document.getElementById('checkoutBtn').disabled = cart.length === 0;
}

function renderSheet() {
  const linesEl = document.getElementById('cartLines');
  if (cart.length === 0) { linesEl.innerHTML = '<div class="empty">Cart is empty</div>'; }
  else {
    linesEl.innerHTML = cart.map((l, idx) => `
      <div class="line">
        <div class="lname">${h(l.name)}<div class="lprice">₹${l.price.toFixed(2)} each</div></div>
        <input type="number" step="1" min="1" class="qty-input" value="${l.qty}" onchange="setQty(${idx}, this.value)">
        <div class="line-total">₹${(l.qty*l.price).toFixed(2)}</div>
        <button class="rm-btn" onclick="removeLine(${idx})">✕</button>
      </div>
    `).join('');
  }
  document.getElementById('totals').innerHTML = `
    <div class="row"><span>Subtotal</span><span>₹${cartTotal().toFixed(2)}</span></div>
    <div class="row"><span>GST</span><span>₹${cartGst().toFixed(2)}</span></div>
    <div class="row grand"><span>Total</span><span>₹${cartGrandTotal().toFixed(2)}</span></div>
  `;
  renderCartBar();
}

function setQty(idx, val) { cart[idx].qty = Math.max(1, parseFloat(val) || 1); renderSheet(); }
function removeLine(idx) { cart.splice(idx, 1); renderSheet(); }
function selectMethod(m) {
  paymentMethod = m;
  document.querySelectorAll('.pill-btn').forEach(b => b.classList.toggle('active', b.dataset.method === m));
}

function openSheet() { renderSheet(); document.getElementById('overlay').classList.add('open'); document.getElementById('sheet').classList.add('open'); }
function closeSheet() { document.getElementById('overlay').classList.remove('open'); document.getElementById('sheet').classList.remove('open'); }

function showToast(msg, type) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'toast show ' + type;
  setTimeout(() => t.classList.remove('show'), 3500);
}

function uuid() { return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => { const r = Math.random()*16|0; return (c==='x'?r:(r&0x3|0x8)).toString(16); }); }

async function completeSale() {
  if (cart.length === 0) return;
  if (paymentMethod === 'CREDIT' && !document.getElementById('customerSelect').value) {
    showToast('Select a customer for a Udhaar sale', 'error');
    return;
  }
  const payload = {
    client_ref: uuid(),
    cart: cart.map(l => ({ id: l.id, qty: l.qty })),
    customer_id: document.getElementById('customerSelect').value || null,
    payment_method: paymentMethod,
    csrf_token: csrfToken,
  };

  document.getElementById('checkoutBtn').disabled = true;
  document.getElementById('checkoutBtn').textContent = 'Saving…';

  let synced = false;
  if (navigator.onLine) {
    try {
      const res = await fetch(BASE + '/pos-mobile/sync', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        synced = true;
        showToast('Sale ' + data.saleNumber + ' completed', 'success');
      } else {
        showToast(data.error || 'Could not complete sale', 'error');
      }
    } catch (e) { /* fall through to offline queue */ }
  }

  if (!synced && !document.getElementById('toast').classList.contains('show')) {
    await queueSale(payload);
    await updateQueueBadge();
    showToast('No connection — sale saved, will sync automatically', 'offline');
  }

  if (synced || !navigator.onLine) {
    cart = [];
    closeSheet();
    renderCartBar();
    loadData();
  }
  document.getElementById('checkoutBtn').disabled = false;
  document.getElementById('checkoutBtn').textContent = 'Complete Sale';
}

// ---- Boot ----
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register(BASE + '/pos-mobile/sw').catch(() => {});
}
updateStatus();
updateQueueBadge();
loadData();
setInterval(trySyncQueue, 30000);
</script>
</body>
</html>
