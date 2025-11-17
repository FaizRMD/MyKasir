{{-- resources/views/pos/index.blade.php --}}
@extends('layouts.app')

@section('title','Kasir / Transaksi')

@push('styles')
<style>
  :root{
    --brand-900:#5e0d0d;   /* maroon */
    --brand-700:#8d1b1b;
    --ink:#0f172a; --soft:#64748b;
    --line:#e7ebf5; --bg:#f6f8fc; --panel:#ffffff;
    --ok:#16a34a; --warn:#f59e0b; --danger:#ef4444;
    --radius:16px; --shadow:0 10px 30px rgba(15,23,42,.06);
  }
  [data-theme="dark"]{
    --bg:#0b1120; --panel:#0f172a; --line:#1e293b; --ink:#e6edf9; --soft:#9fb3d9;
    --shadow:0 12px 32px rgba(0,0,0,.45);
  }

  .pos-wrap{
    display:grid; grid-template-columns: 1fr 360px; gap:14px;
  }
  @media (max-width: 1200px){ .pos-wrap{ grid-template-columns:1fr; } }

  .cardx{background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow)}
  .cardx .hd{padding:12px 14px; border-bottom:1px dashed var(--line); display:flex; align-items:center; gap:.6rem; font-weight:800; letter-spacing:.2px}
  .cardx .bd{padding:12px 14px}

  .searchbar{
    display:flex; gap:.5rem; align-items:center;
    border:1px solid var(--line); border-radius:12px; padding:.55rem .7rem; background:#fff;
    position:relative; /* agar dropdown bisa absolute ke container */
  }
  [data-theme="dark"] .searchbar{ background:#0b1530; }
  .searchbar input{ border:0; outline:0; width:100%; background:transparent; color:var(--ink) }

  /* ===== Auto-suggest box ===== */
  .suggest-wrap{ position: relative; width:100%; }
  .suggest{
    position:absolute; top:100%; left:0; right:0; margin-top:6px;
    background:#fff; border:1px solid var(--line); border-radius:12px;
    box-shadow:var(--shadow); max-height:280px; overflow:auto;
    z-index:20; display:none;
  }
  [data-theme="dark"] .suggest{ background:#0f172a; border-color:#1e293b; }
  .suggest .item{
    padding:10px 12px; display:flex; align-items:center; gap:10px; cursor:pointer;
    border-bottom:1px solid #f3f4f6;
  }
  [data-theme="dark"] .suggest .item{ border-bottom-color:#1e293b; }
  .suggest .item:last-child{ border-bottom:none; }
  .suggest .item:hover,.suggest .item.active{ background:#fff6f7; }
  [data-theme="dark"] .suggest .item:hover,[data-theme="dark"] .suggest .item.active{ background:#2a1b2a; }
  .suggest .price{margin-left:auto; font-weight:700}
  .suggest .meta{font-size:12px; color:#64748b}

  .tb{ width:100%; border-collapse:separate; border-spacing:0; }
  .tb thead th{
    position:sticky; top:0; background:#fff5f5; color:#7d1a1a;
    border-bottom:1px solid var(--line); padding:10px; font-weight:800;
  }
  [data-theme="dark"] .tb thead th{ background:#2a1b2a; color:#ffdede; }
  .tb tbody td{ border-bottom:1px solid var(--line); padding:10px; vertical-align:middle; }
  .num{ text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }

  .qty-box{ display:flex; align-items:center; gap:.4rem }
  .qty-box input{ width:64px; text-align:right }

  .chip{ display:inline-flex; align-items:center; gap:.4rem;
    background:#fdeeee; color:#7d1a1a; padding:.25rem .55rem; border-radius:999px; border:1px solid #f5d2d2; font-weight:700; font-size:.85rem }
  [data-theme="dark"] .chip{ background:rgba(211,90,90,.15); color:#ffdcdc; border-color:rgba(211,90,90,.35) }

  .total-box{ display:grid; gap:8px }
  .rowx{ display:flex; justify-content:space-between; align-items:center; gap:8px; }
  .rowx .lbl{ color:var(--soft); font-weight:700 }
  .grand{ font-size:1.4rem; font-weight:900 }
  .muted{ color:var(--soft) }

  .btn-brand{
    background:linear-gradient(90deg,var(--brand-900),var(--brand-700));
    color:#fff; border:0; border-radius:12px; padding:.7rem 1rem; font-weight:800;
    box-shadow:0 10px 24px rgba(141,27,27,.18);
  }
  .btn-ghost{ border:1px solid var(--brand-700); color:var(--brand-700); background:#fff; border-radius:12px; font-weight:700 }
  .btn-ghost:hover{ background:var(--brand-700); color:#fff }

  .pay-btn{ width:100%; display:flex; align-items:center; justify-content:center; gap:.5rem }
  .pill{ border-radius:999px; padding:.25rem .6rem; font-weight:700; }

  .empty{ padding:40px 12px; color:var(--soft); display:flex; align-items:center; justify-content:center; gap:.6rem; }
</style>
@endpush

@section('content')
<div class="pos-wrap">
  {{-- ================== LEFT: KERANJANG ================== --}}
  <div class="cardx">
    <div class="hd">
      <i data-feather="shopping-cart"></i>
      Transaksi / Kasir
      <span class="chip ms-auto" id="invoiceNo">Draft</span>
    </div>
    <div class="bd">
      {{-- Search / Scan --}}
      <div class="searchbar mb-3">
        <i data-feather="search"></i>
        <div class="suggest-wrap">
          <input id="scanInput" type="text" placeholder="Ketik / scan barcode (Ctrl+/) — Enter untuk tambah">
          <!-- dropdown suggestion -->
          <div id="suggestBox" class="suggest"></div>
        </div>
        <button class="btn btn-ghost" id="btnAddQuick">Tambah</button>
      </div>

      {{-- Tabel item --}}
      <div class="table-responsive" style="max-height:58vh; overflow:auto;">
        <table class="tb">
          <thead>
            <tr>
              <th style="width:48px">#</th>
              <th>Nama / SKU</th>
              <th class="num">Harga</th>
              <th class="num" style="width:140px">Qty</th>
              <th class="num">Diskon</th>
              <th class="num">Subtotal</th>
              <th class="num" style="width:46px"></th>
            </tr>
          </thead>
          <tbody id="cartBody">
            <tr class="empty-row">
              <td colspan="7" class="empty">
                <i data-feather="inbox"></i> Keranjang masih kosong
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      {{-- Toolbar bawah kiri --}}
      <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-ghost" id="btnHold"><i data-feather="pause"></i> Tunda</button>
        <button class="btn btn-ghost" id="btnNew"><i data-feather="file-plus"></i> Baru</button>
        <button class="btn btn-ghost" id="btnRemoveAll"><i data-feather="trash-2"></i> Hapus Semua</button>
      </div>
    </div>
  </div>

  {{-- ================== RIGHT: RINGKASAN & BAYAR ================== --}}
  <div class="cardx">
    <div class="hd">
      <i data-feather="credit-card"></i>
      Pembayaran
    </div>
    <div class="bd">
      {{-- Customer & pilihan --}}
      <div class="mb-3">
        <label class="form-label fw-bold">Pelanggan</label>
        <div class="d-flex gap-2">
          <input type="text" class="form-control" id="customerName" placeholder="Umum / ketik nama">
          <button class="btn btn-ghost" type="button"><i data-feather="user-plus"></i></button>
        </div>
      </div>

      {{-- Ringkasan --}}
      <div class="total-box mb-3">
        <div class="rowx"><div class="lbl">Subtotal</div><div class="num" id="vSubtotal">0</div></div>
        <div class="rowx"><div class="lbl">Diskon Nota</div>
          <div class="d-flex gap-2 align-items-center">
            <input id="discBill" type="number" class="form-control form-control-sm" min="0" value="0" style="width:100px; text-align:right">
            <span class="muted">Rp</span>
          </div>
        </div>
        <div class="rowx"><div class="lbl">PPN</div>
          <div class="d-flex gap-2 align-items-center">
            <input id="taxPct" type="number" class="form-control form-control-sm" min="0" step="0.01" value="0" style="width:100px; text-align:right">
            <span class="muted">%</span>
          </div>
        </div>
        <hr class="my-2">
        <div class="rowx grand"><div>Total</div><div class="num" id="vGrand">0</div></div>
      </div>

      {{-- Pembayaran --}}
      <div class="mb-3">
        <label class="form-label fw-bold">Terima (Cash)</label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input id="cashIn" type="number" class="form-control" min="0" step="1" value="0">
        </div>
        <div class="rowx mt-2"><div class="lbl">Kembali</div><div class="num fw-bold" id="vChange">0</div></div>
      </div>

      {{-- Metode --}}
      <div class="mb-3">
        <label class="form-label fw-bold">Metode</label>
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-outline-secondary pill" data-method="CASH" type="button">Tunai</button>
          <button class="btn btn-outline-secondary pill" data-method="TRANSFER" type="button">Transfer</button>
          <button class="btn btn-outline-secondary pill" data-method="QRIS" type="button">QRIS</button>
          <button class="btn btn-outline-secondary pill" data-method="DEBIT" type="button">Debit</button>
        </div>
      </div>

      {{-- Tombol bayar --}}
      <div class="d-grid gap-2">
        <button class="btn btn-brand pay-btn" id="btnPay" type="button"><i data-feather="check-circle"></i> Bayar & Simpan (Enter)</button>
        <button class="btn btn-outline-secondary pay-btn" id="btnPayNonCash" type="button"><i data-feather="send"></i> Non Tunai</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  feather.replace();

  // ---------- State ----------
  const LS_KEY = 'mkasir:pos:cart';
  let cart = loadCart();               // [{product_id,sku,name,price,qty,disc}]
  let method = 'CASH';

  // ---------- Helpers ----------
  const $ = s => document.querySelector(s);
  const money = v => new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(+v||0)));
  const csrf = document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}';

  function loadCart(){
    try{ return JSON.parse(localStorage.getItem(LS_KEY)||'[]') }catch(e){ return [] }
  }
  function saveCart(){ localStorage.setItem(LS_KEY, JSON.stringify(cart)); }
  function clearCart(){ cart = []; saveCart(); render(); }

  // ====== AUTO-SUGGEST PRODUK ======
  const suggestBox = document.getElementById('suggestBox');
  const scanInput  = document.getElementById('scanInput');
  let sugRows = [];      // cache hasil terakhir
  let sugIndex = -1;     // item aktif (untuk keyboard)
  let sugTimer = null;

  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function hideSuggest(){ suggestBox.style.display='none'; sugIndex=-1; }
  function showSuggest(){ if (sugRows.length) suggestBox.style.display='block'; }

  function normalizeProducts(json){
    if (Array.isArray(json)) return json;
    if (json && Array.isArray(json.data)) return json.data;
    if (json && json.data && typeof json.data === 'object') return [json.data];
    if (json && Array.isArray(json.rows)) return json.rows;
    return [];
  }
  function toItem(p){
    return {
      id:    p.id ?? p.product_id,
      name:  p.name ?? p.text ?? '—',
      sku:   p.sku ?? p.code ?? '',
      price: Number(p.price ?? p.sale_price ?? p.harga ?? 0),
      stock: Number(p.stock ?? p.stok ?? 0),
    };
  }

  async function fetchSuggest(q){
    try{
      const url = `{{ route('products.lookup') }}?q=${encodeURIComponent(q)}&limit=8`;
      const res = await fetch(url, { headers:{'Accept':'application/json'} });
      if (!res.ok) return [];
      const j = await res.json();
      return normalizeProducts(j).map(toItem);
    }catch(_){ return []; }
  }

  function renderSuggest(list){
    if (!list.length){ hideSuggest(); return; }
    suggestBox.innerHTML = list.map((p,i)=>`
      <div class="item ${i===sugIndex?'active':''}" data-i="${i}">
        <div>
          <div class="fw-semibold">${escapeHtml(p.name)}</div>
          <div class="meta">SKU: ${escapeHtml(p.sku||'-')} • Stok: ${p.stock ?? 0}</div>
        </div>
        <div class="price">Rp ${money(p.price||0)}</div>
      </div>
    `).join('');
    showSuggest();
    Array.from(suggestBox.querySelectorAll('.item')).forEach(el=>{
      el.addEventListener('click', ()=> pickSuggest(+el.dataset.i));
    });
  }

  function pickSuggest(i){
    const p = sugRows[i]; if (!p) return;
    const existing = cart.find(it => it.product_id === p.id);
    if (existing) {
      existing.qty = Math.min(existing.qty + 1, p.stock || existing.qty + 1);
    } else {
      if (!p.price) { addByText(p.sku || p.name); hideSuggest(); scanInput.value=''; return; }
      cart.push({ product_id: p.id, sku: p.sku, name: p.name, price: p.price, qty: 1, disc: 0 });
    }
    saveCart(); render();
    hideSuggest(); scanInput.value=''; scanInput.focus();
  }

  // Saat mengetik → debounce → fetch saran
  scanInput.addEventListener('input', ()=>{
    const q = scanInput.value.trim();
    clearTimeout(sugTimer);
    if (!q){ hideSuggest(); return; }
    sugTimer = setTimeout(async ()=>{
      sugRows = await fetchSuggest(q);
      sugIndex = (sugRows.length ? 0 : -1);
      renderSuggest(sugRows);
    }, 180);
  });

  // Navigasi keyboard pada dropdown (↑ ↓ Enter Esc)
  scanInput.addEventListener('keydown', e=>{
    if (suggestBox.style.display !== 'block') return;
    if (e.key === 'ArrowDown'){ e.preventDefault(); if (sugRows.length){ sugIndex = (sugIndex+1) % sugRows.length; renderSuggest(sugRows); } }
    if (e.key === 'ArrowUp'){   e.preventDefault(); if (sugRows.length){ sugIndex = (sugIndex-1+sugRows.length) % sugRows.length; renderSuggest(sugRows); } }
    if (e.key === 'Enter'){     e.preventDefault(); if (sugIndex>=0) { pickSuggest(sugIndex); } }
    if (e.key === 'Escape'){    hideSuggest(); }
  });

  // Klik di luar → tutup dropdown
  document.addEventListener('click', (ev)=>{
    if (!suggestBox.contains(ev.target) && ev.target !== scanInput) hideSuggest();
  });

  // ---------- Render ----------
  function render(){
    const tbody = $('#cartBody');
    tbody.innerHTML = '';

    if(cart.length === 0){
      const tr = document.createElement('tr');
      tr.className = 'empty-row';
      tr.innerHTML = `<td colspan="7" class="empty"><i data-feather="inbox"></i> Keranjang masih kosong</td>`;
      tbody.appendChild(tr);
      feather.replace();
      updateTotals();
      return;
    }

    cart.forEach((it, i)=>{
      const tr = document.createElement('tr');

      tr.innerHTML = `
        <td class="text-muted">${i+1}</td>
        <td>
          <div class="fw-semibold">${it.name}</div>
          <div class="small text-muted">SKU: ${it.sku||'-'}</div>
        </td>
        <td class="num">${money(it.price)}</td>
        <td class="num">
          <div class="qty-box ms-auto">
            <button class="btn btn-sm btn-outline-secondary" data-act="dec" data-i="${i}">−</button>
            <input type="number" class="form-control form-control-sm" data-act="qty" data-i="${i}" min="1" step="1" value="${it.qty}">
            <button class="btn btn-sm btn-outline-secondary" data-act="inc" data-i="${i}">+</button>
          </div>
        </td>
        <td class="num">
          <div class="input-group input-group-sm">
            <input type="number" class="form-control" data-act="disc" data-i="${i}" min="0" step="1" value="${it.disc||0}">
            <span class="input-group-text">Rp</span>
          </div>
        </td>
        <td class="num fw-bold">${money((it.price * it.qty) - (it.disc||0))}</td>
        <td class="num">
          <button class="btn btn-sm btn-outline-danger" data-act="del" data-i="${i}">
            <i data-feather="trash"></i>
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });
    feather.replace();
    updateTotals();
  }

  function updateTotals(){
    const sub = cart.reduce((s,it)=> s + (it.price*it.qty) - (it.disc||0), 0);
    const discBill = +$('#discBill').value || 0;
    const taxPct   = (+$('#taxPct').value || 0)/100;
    const grand = Math.max(0, (sub - discBill) * (1 + taxPct));

    $('#vSubtotal').textContent = money(sub);
    $('#vGrand').textContent    = money(grand);

    const cashIn = +$('#cashIn').value || 0;
    $('#vChange').textContent   = money(cashIn - grand);
  }

  // ---------- Events: table ----------
  $('#cartBody').addEventListener('click', e=>{
    const btn = e.target.closest('button[data-act]');
    if(!btn) return;
    const i = +btn.dataset.i;
    if(btn.dataset.act === 'del'){ cart.splice(i,1); saveCart(); render(); }
    if(btn.dataset.act === 'inc'){ cart[i].qty++; saveCart(); render(); }
    if(btn.dataset.act === 'dec'){ cart[i].qty = Math.max(1, cart[i].qty-1); saveCart(); render(); }
  });
  $('#cartBody').addEventListener('input', e=>{
    const el = e.target;
    if(el.dataset.act === 'qty'){ const i = +el.dataset.i; cart[i].qty = Math.max(1, parseInt(el.value||1,10)); saveCart(); updateTotals(); }
    if(el.dataset.act === 'disc'){ const i = +el.dataset.i; cart[i].disc = Math.max(0, +el.value||0); saveCart(); updateTotals(); }
  });

  // ---------- Events: summary ----------
  ['#discBill','#taxPct','#cashIn'].forEach(id => {
    $(id).addEventListener('input', updateTotals);
  });

  // ---------- Add item (ambil dari products.lookup) ----------
  async function addByText(txt){
    const term = (txt || '').trim();
    if(!term) return;

    try {
      const res = await fetch("{{ route('products.lookup') }}?q=" + encodeURIComponent(term), {
        headers: { 'Accept': 'application/json' }
      });

      if (res.redirected) { window.location = res.url; return; }

      if(!res.ok){
        let errMsg = 'Produk tidak ditemukan / stok habis.';
        try { const err = await res.json(); if (err && err.message) errMsg = err.message; } catch(_) {}
        alert(errMsg);
        return;
      }

      const json = await res.json();
      const data = json.data;

      const existing = cart.find(it => it.product_id === data.id);
      if (existing) {
        existing.qty = Math.min(existing.qty + 1, data.stock);
      } else {
        cart.push({
          product_id: data.id,
          sku: data.sku,
          name: data.name,
          price: data.price,
          qty: 1,
          disc: 0
        });
      }

      saveCart(); render();
    } catch (e) {
      alert('Gagal mengambil data produk.');
    }
  }

  // tombol Tambah
  $('#btnAddQuick').addEventListener('click', async ()=>{
    await addByText($('#scanInput').value);
    $('#scanInput').value=''; $('#scanInput').focus();
  });

  // Enter di scanInput:
  // - jika dropdown tampil → pilih item aktif
  // - jika dropdown tidak tampil → tambah cepat seperti biasa
  scanInput.addEventListener('keydown', async e=>{
    if(e.key === 'Enter'){
      if (suggestBox.style.display === 'block') {
        e.preventDefault();
        if (sugIndex>=0) pickSuggest(sugIndex);
      } else {
        await addByText(e.target.value);
        e.target.value='';
      }
    }
  });

  // shortcuts
  window.addEventListener('keydown', e=>{
    if((e.ctrlKey||e.metaKey) && e.key === '/'){
      e.preventDefault(); $('#scanInput').focus(); $('#scanInput').select();
    }
    if(e.key === 'Enter' && document.activeElement.id !== 'scanInput'){
      pay(true);
    }
  });

  // ---------- Method buttons (tandai yang aktif) ----------
  document.querySelectorAll('.pill').forEach(b=>{
    b.addEventListener('click', ()=>{
      document.querySelectorAll('.pill').forEach(x=>{
        x.classList.remove('btn-brand','active');
        x.classList.add('btn-outline-secondary');
      });
      b.classList.add('btn-brand','active');
      b.classList.remove('btn-outline-secondary');
      method = (b.dataset.method || 'CASH').toUpperCase();
    });
  });

  // ---------- Actions ----------
  $('#btnHold').addEventListener('click', ()=>{
    saveCart();
    alert('Draft ditahan di perangkat ini.');
  });
  $('#btnNew').addEventListener('click', ()=>{
    if(confirm('Mulai transaksi baru?')){
      clearCart(); $('#discBill').value=0; $('#taxPct').value=0; $('#cashIn').value=0; updateTotals();
    }
  });
  $('#btnRemoveAll').addEventListener('click', ()=>{
    if(confirm('Hapus semua item di keranjang?')){ clearCart(); }
  });

  // ---------- Pay: simpan semua item -> checkout -> (konfirmasi) cetak ----------
  async function pay(isCash){
    if(cart.length===0){ alert('Keranjang kosong.'); return; }

    const sub = cart.reduce((s,it)=> s + (it.price*it.qty) - (it.disc||0), 0);
    const discBill = +$('#discBill').value || 0;
    const taxPct   = (+$('#taxPct').value||0)/100;
    const grand = Math.max(0, (sub - discBill) * (1 + taxPct));
    const paid  = isCash ? (+$('#cashIn').value||0) : grand;

    if(isCash && paid < grand){ alert('Uang kurang.'); return; }

    try{
      // 1) Simpan semua item
      for(const it of cart){
        const body = new URLSearchParams();
        body.append('product_id', it.product_id);
        body.append('qty', String(it.qty));
        body.append('price', String(it.price));

        const res = await fetch("{{ route('sale-items.store') }}", {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-TOKEN': csrf
          },
          body
        });

        if (res.redirected) { window.location = res.url; return; }
        if(!res.ok){
          let errMsg = 'Gagal menyimpan item.';
          try { const err = await res.json(); if (err && err.message) errMsg = err.message; } catch(_) {}
          throw new Error(errMsg);
        }
      }

      // 2) Checkout -> dapatkan print_url
      const res2 = await fetch("{{ route('kasir.checkout') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({
          paid: paid,
          payment_method: method,
          customer_name: $('#customerName')?.value || null // NOTE: jika kolom customer_name tidak ada, abaikan di backend
        })
      });

      if (res2.redirected) { window.location = res2.url; return; }
      if(!res2.ok){
        const t = await res2.text();
        throw new Error(t || 'Checkout gagal');
      }

      const j = await res2.json();
      if(!j.ok) throw new Error(j.message || 'Checkout gagal');

      // 3) Konfirmasi cetak
      const inginCetak = confirm('Transaksi tersimpan.\nApakah Anda ingin mencetak struk?');
      if (inginCetak) {
        window.open(j.print_url, '_blank');
      }

      // 4) Reset UI
      clearCart();
      $('#cashIn').value = 0; $('#discBill').value = 0; $('#taxPct').value = 0;
      updateTotals();

    }catch(e){
      alert(e.message || 'Terjadi kesalahan saat menyimpan transaksi.');
    }
  }

  $('#btnPay').addEventListener('click', ()=>pay(true));
  $('#btnPayNonCash').addEventListener('click', ()=>{ if(method==='CASH') method='TRANSFER'; pay(false); });

  // init
  render();
})();
</script>
@endpush
