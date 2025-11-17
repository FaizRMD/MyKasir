@extends('layouts.app')
@section('title','Buat Purchase Order')

@push('styles')
<style>
:root{
  --maroon:#7a1020; --maroon-600:#5a0c18;
  --border:#e5e7eb; --muted:#6b7280; --soft:#f9fafb; --ink:#0f172a;
}

.page-title{font-size:34px;font-weight:800;text-align:center;margin:8px 0 14px;color:var(--ink);letter-spacing:.3px;animation:fadeIn .4s ease}
.form-head{border:1px solid var(--border);border-radius:14px;padding:16px 18px;background:#fff;box-shadow:0 6px 18px rgba(16,24,40,.04);animation:slideUp .35s ease}
.form-label{font-weight:600;color:#111827}
.form-control,.form-select{border-radius:10px;transition:border-color .15s ease, box-shadow .15s ease}
.form-control:focus,.form-select:focus{border-color:var(--maroon);box-shadow:0 0 0 .15rem rgba(122,16,32,.12)}
.btn-maroon{background:var(--maroon);color:#fff;border:none;border-radius:10px;transition:transform .12s ease, box-shadow .2s ease}
.btn-maroon:hover{background:var(--maroon-600);transform:translateY(-1px);box-shadow:0 8px 18px rgba(122,16,32,.25)}
.btn-outline-maroon{border:1px solid var(--maroon);color:var(--maroon);background:#fff;border-radius:10px;transition:transform .12s ease}
.btn-outline-maroon:hover{background:#fff6f7;transform:translateY(-1px)}
.btn-square{width:42px;height:40px;display:inline-flex;align-items:center;justify-content:center}
.header-actions{display:flex;gap:.5rem;align-items:center}
.header-total{font-size:36px;font-weight:900;color:var(--maroon);animation:pulse .8s ease-in-out}
.tools-bar{border-top:1px solid var(--border);padding-top:16px;margin-top:12px;display:flex;justify-content:space-between;align-items:center}
.search-bar{display:flex;gap:8px;width:100%}
.search-bar input{flex:1}
.table thead th{background:var(--maroon);color:#fff;vertical-align:middle}
.table tbody tr:nth-child(odd){background:#fafafa}
.smallmuted{color:var(--muted)}
.tab-mrn .nav-link{border-radius:999px;padding:.45rem .9rem}
.tab-mrn .nav-link.active{background:var(--maroon);color:#fff}

@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideUp{from{transform:translateY(10px);opacity:0}to{transform:translateY(0);opacity:1}}
@keyframes pulse{0%{transform:scale(1)}50%{transform:scale(1.02)}100%{transform:scale(1)}}
</style>
@endpush

@section('content')
<div class="container-fluid" style="max-width:1100px">

  <h3 class="page-title">Purchase Order (PO)</h3>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ route('purchases.store') }}" id="poForm">
    @csrf

    {{-- HEADER --}}
    <div class="form-head mb-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Tanggal</label>
          <div class="input-group">
            <input type="date" name="po_date" class="form-control" value="{{ old('po_date', date('Y-m-d')) }}" required>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Jenis</label>
          @php $typeVal = old('type','NON KONSINYASI') @endphp
          <select name="type" class="form-select">
            <option value="NON KONSINYASI" {{ $typeVal=='NON KONSINYASI'?'selected':'' }}>NON KONSINYASI</option>
            <option value="KONSINYASI"     {{ $typeVal=='KONSINYASI'?'selected':'' }}>KONSINYASI</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">No. PO</label>
          <input type="text" id="poNo" class="form-control" placeholder="No. PO akan terisi otomatis saat simpan." readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Apoteker</label>
          <div class="input-group">
            @php
              $apoList = $apotekers ?? \App\Models\Apoteker::orderBy('name')->get();
              $apoVal  = old('apoteker_id');
            @endphp
            <select name="apoteker_id" class="form-select">
              <option value="">Pilih Apoteker</option>
              @foreach($apoList as $a)
                <option value="{{ $a->id }}" {{ (string)$apoVal===(string)$a->id ? 'selected' : '' }}>{{ $a->name }}</option>
              @endforeach
            </select>
            <a href="{{ route('apoteker.create') }}" class="btn btn-outline-maroon btn-square" title="Tambah Apoteker">+</a>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Supplier</label>
          <div class="input-group">
            @php
              $supList = $suppliers ?? \App\Models\Supplier::orderBy('name')->get();
              $supVal  = old('supplier_id');
            @endphp
            <select name="supplier_id" class="form-select" required>
              <option value="">Pilih Supplier</option>
              @foreach($supList as $s)
                <option value="{{ $s->id }}" {{ (string)$supVal===(string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
              @endforeach
            </select>
            <a href="{{ route('suppliers.create') }}" class="btn btn-outline-maroon btn-square" title="Tambah Supplier">+</a>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Catatan</label>
          <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="">
        </div>

        <div class="col-md-6">
          <label class="form-label">Kategori PO</label>
          @php $poCategories=['Reguler','Prekursor','Narkotika','Psikotropika','Obat-obat tertentu']; $catVal=old('category','Reguler'); @endphp
          <select name="category" class="form-select">
            @foreach($poCategories as $c)
              <option value="{{ $c }}" {{ $catVal==$c?'selected':'' }}>{{ $c }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Pilihan Cetak</label>
          @php
            $printOptions=[
              'INV_A5'=>'Invoice (A5)','INV_A4'=>'Invoice (A4)',
              'STRUK_58'=>'Struk Kecil 58m','STRUK_76'=>'Struk Kecil 76m',
              'PREKURSOR'=>'Prekursor','NARKOTIKA'=>'Narkotika',
              'PSIKOTROPIKA'=>'Psikotropika','OBT_TERTENTU'=>'Obat-obatan tertentu',
            ];
            $prnVal=old('print_type','INV_A5');
          @endphp
          <select name="print_type" class="form-select">
            @foreach($printOptions as $k=>$v)
              <option value="{{ $k }}" {{ $prnVal==$k?'selected':'' }}>{{ $v }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="tools-bar">
        <div class="header-actions">
          <button type="button" class="btn btn-outline-maroon" data-bs-toggle="modal" data-bs-target="#productPicker">+ Obat</button>
          <button type="reset" class="btn btn-outline-secondary">Reset</button>
          <button type="submit" class="btn btn-maroon">Simpan</button>
        </div>
        <div class="header-total" id="headerGrand">0,00</div>
      </div>
    </div>

    {{-- SEARCH BAR --}}
    <div class="mb-3">
      <div class="search-bar">
        <input type="text" id="scanInput" class="form-control" placeholder="Scan Barcode Obat / Inputkan Nama Obat">
        <button type="button" id="btnCari" class="btn btn-primary">Cari</button>
      </div>
      <div class="smallmuted mt-1">Ketik/scan lalu klik Cari untuk menambahkan dari master Product.</div>
    </div>

    {{-- TABEL ITEMS --}}
    <div class="table-responsive">
      <table class="table table-bordered align-middle" id="tblItems">
        <thead>
          <tr>
            <th style="width:60px"  class="text-center">No.</th>
            <th style="width:140px">Kode</th>
            <th>Nama Obat</th>
            <th style="width:110px" class="text-center">Jumlah</th>
            <th style="width:120px" class="text-center">Satuan</th>
            <th style="width:140px" class="text-end">Harga</th>
            <th style="width:140px" class="text-end">Subtotal</th>
            <th style="width:90px"  class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </form>
</div>

{{-- MODAL PICKER PRODUCT + TAB TAMBAH BARU --}}
<div class="modal fade" id="productPicker" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Produk</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">

        <ul class="nav nav-pills mb-3 tab-mrn" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabCari" type="button" role="tab">Cari dari Master</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabBaru" type="button" role="tab">Tambah Produk Baru</button>
          </li>
        </ul>

        <div class="tab-content">
          {{-- TAB CARI --}}
          <div class="tab-pane fade show active" id="tabCari" role="tabpanel">
            <div class="input-group mb-2">
              <input type="text" id="prodSearch" class="form-control" placeholder="Cari kode / nama...">
              <button class="btn btn-outline-secondary" id="btnSearch">Cari</button>
            </div>
            <div class="table-responsive" style="max-height:52vh">
              <table class="table table-sm table-hover mb-0">
                <thead>
                  <tr><th>Kode</th><th>Nama</th><th>Satuan</th><th class="text-end">Harga Beli</th><th class="text-center">Pilih</th></tr>
                </thead>
                <tbody id="prodResult"><tr><td colspan="5" class="text-center text-muted py-3">Ketik kata kunci lalu klik Cari.</td></tr></tbody>
              </table>
            </div>
          </div>

          {{-- TAB TAMBAH BARU --}}
          <div class="tab-pane fade" id="tabBaru" role="tabpanel">
            <form id="quickForm" class="p-2">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Kode</label>
                  <input type="text" class="form-control" id="q_code" placeholder="Opsional">
                </div>
                <div class="col-md-8">
                  <label class="form-label">Nama Obat <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="q_name" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Satuan</label>
                  <input type="text" class="form-control" id="q_uom" value="pcs">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Harga Beli</label>
                  <input type="number" step="0.01" min="0" class="form-control" id="q_price" value="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Kategori</label>
                  <input type="text" class="form-control" id="q_category" placeholder="mis. Generik / Ethical">
                </div>
              </div>
              <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" id="btnResetQuick">Reset</button>
                <button type="submit" class="btn btn-maroon" id="btnSaveQuick">Simpan & Pilih</button>
              </div>
            </form>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const q=(s,ctx)=> (ctx||document).querySelector(s);
  const ce=(t)=> document.createElement(t);
  const fmt=(n)=> new Intl.NumberFormat('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(n||0));
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  async function fetchJson(url, opts={}){
    const opt = Object.assign({
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    }, opts);
    const resp = await fetch(url, opt);
    if(!resp.ok){
      const txt = await resp.text();
      throw new Error(`HTTP ${resp.status}: ${txt.substring(0,250)}`);
    }
    const ct = resp.headers.get('content-type') || '';
    return ct.includes('application/json') ? resp.json() : {};
  }

  // ====== PO No preview (dummy saja) ======
  const poNo=q('#poNo');
  const dt=q('input[name="po_date"]');
  const previewPoNo=()=>{ const d=(dt.value||'').replaceAll('-',''); poNo.value = d ? `PO-${d.slice(0,6)}-XXX` : ''; };
  dt.addEventListener('change', previewPoNo); previewPoNo();

  // ====== Items table ======
  const tbody=q('#tblItems tbody');
  const headGrand=q('#headerGrand');

  function rowTpl(i){
    return `
      <td class="text-center idx">${i+1}</td>
      <td>
        <input type="hidden" name="items[${i}][product_id]" class="product_id">
        <input class="form-control code" placeholder="-" readonly>
      </td>
      <td><input class="form-control name" placeholder="-" readonly></td>
      <td><input name="items[${i}][qty]" type="number" min="1" value="1" class="form-control text-center qty" required></td>
      <td><input name="items[${i}][uom]" class="form-control text-center uom" value="pcs"></td>
      <td><input name="items[${i}][cost]" type="number" step="0.01" min="0" value="0" class="form-control text-end price" required></td>
      <td class="text-end sub">0,00</td>
      <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del">Hapus</button></td>
    `;
  }

  function addRow(prefill){
    const i=tbody.querySelectorAll('tr').length;
    const tr=ce('tr'); tr.innerHTML=rowTpl(i);
    tr.style.animation='slideUp .25s ease';
    tbody.appendChild(tr);
    bindRow(tr);
    if(prefill){
      tr.querySelector('.product_id').value = prefill.id;
      tr.querySelector('.code').value       = prefill.code || '';
      tr.querySelector('.name').value       = prefill.name || '';
      tr.querySelector('.uom').value        = prefill.uom || 'pcs';
      tr.querySelector('.price').value      = prefill.price ?? prefill.default_cost ?? 0;
    }
    calcRow(tr);
  }

  function reindex(){
    [...tbody.querySelectorAll('tr')].forEach((tr,i)=>{
      tr.querySelector('.idx').textContent=i+1;
      tr.querySelectorAll('input').forEach(el=>{
        el.name=el.name.replace(/items\[\d+\]/,`items[${i}]`);
      });
    });
  }

  function bindRow(tr){
    tr.querySelector('.qty').addEventListener('input',()=>calcRow(tr));
    tr.querySelector('.price').addEventListener('input',()=>calcRow(tr));
    tr.querySelector('.del').addEventListener('click',()=>{ tr.remove(); reindex(); recompute(); });
  }

  function calcRow(tr){
    const qty=Math.max(1, Number(tr.querySelector('.qty').value||0));
    const price=Math.max(0, Number(tr.querySelector('.price').value||0));
    const sub=qty*price;
    tr.dataset.sub=sub;
    tr.querySelector('.sub').textContent=fmt(sub);
    recompute();
  }

  function recompute(){
    let sum=0;
    [...tbody.querySelectorAll('tr')].forEach(tr=> sum+=Number(tr.dataset.sub||0));
    headGrand.textContent=fmt(sum);
    headGrand.style.animation='pulse .6s';
    setTimeout(()=> headGrand.style.animation='', 650);
  }

  // ====== Quick search di bar ======
  const scanInput=q('#scanInput'), btnCari=q('#btnCari');
  async function quickSearch(){
    const kw=(scanInput.value||'').trim();
    if(!kw) return;
    try{
      const data = await fetchJson(`{{ route('purchases.productsLookup') }}?q=${encodeURIComponent(kw)}`);
      if(!data.length){ alert('Produk tidak ditemukan.'); return; }
      const p=data[0];
      addRow({ id:p.id, code:p.code, name:p.name, uom:p.uom, price:p.price });
      scanInput.value='';
    }catch(e){ alert(e.message || 'Gagal mengambil data produk.'); }
  }
  btnCari.addEventListener('click', quickSearch);
  scanInput.addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); quickSearch(); }});

  // ====== Modal: Tab Cari (AJAX) ======
  const prodSearch=q('#prodSearch'), btnSearch=q('#btnSearch'), prodResult=q('#prodResult');
  async function doSearch(){
    const kw=(prodSearch.value||'').trim();
    prodResult.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">Mencari...</td></tr>`;
    try{
      const data = await fetchJson(`{{ route('purchases.productsLookup') }}?q=${encodeURIComponent(kw)}`);
      if(!data.length){ prodResult.innerHTML=`<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada hasil.</td></tr>`; return; }
      prodResult.innerHTML = data.map(p=>`
        <tr>
          <td>${p.code ?? ''}</td>
          <td>${p.name}</td>
          <td>${p.uom ?? 'pcs'}</td>
          <td class="text-end">${fmt(p.price ?? 0)}</td>
          <td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary pick" data-p='${JSON.stringify(p)}'>Pilih</button></td>
        </tr>
      `).join('');
      [...prodResult.querySelectorAll('.pick')].forEach(b=>{
        b.addEventListener('click',()=>{
          const p=JSON.parse(b.dataset.p);
          addRow({ id:p.id, code:p.code, name:p.name, uom:p.uom, price:p.price });
          bootstrap.Modal.getInstance(document.getElementById('productPicker'))?.hide();
        });
      });
    }catch(e){
      prodResult.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">${(e && e.message) ? e.message : 'Gagal mengambil data.'}</td></tr>`;
    }
  }
  btnSearch.addEventListener('click', doSearch);
  prodSearch.addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); doSearch(); }});

  // ====== Modal: Tab Tambah Produk Baru ======
  const quickForm = q('#quickForm');
  const q_code = q('#q_code'), q_name=q('#q_name'), q_uom=q('#q_uom'), q_price=q('#q_price'), q_category=q('#q_category');
  q('#btnResetQuick').addEventListener('click', ()=>{ quickForm.reset(); q_uom.value='pcs'; q_price.value='0'; });

  quickForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    if(!q_name.value.trim()){ q_name.focus(); return; }
    try{
      const body = new URLSearchParams();
      body.append('code', q_code.value.trim());
      body.append('name', q_name.value.trim());
      body.append('uom', q_uom.value.trim());
      body.append('purchase_price', q_price.value || '0');
      body.append('category', q_category.value.trim());

      const data = await fetchJson(`{{ route('products.quickStore') }}`, {
        method: 'POST',
        headers: {
          'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With':'XMLHttpRequest'
        },
        body
      });

      // Tambahkan ke tabel PO & tutup modal
      addRow({ id:data.id, code:data.code, name:data.name, uom:data.uom, price:data.price });
      bootstrap.Modal.getInstance(document.getElementById('productPicker'))?.hide();

      // reset form agar siap input berikutnya
      quickForm.reset(); q_uom.value='pcs'; q_price.value='0';

    }catch(e){
      alert(e.message || 'Gagal menyimpan produk baru.');
    }
  });

})();
</script>
@endpush
