{{-- ============================================
=  FORM PRODUK (VMEDIS-STYLE: BELI BOX → JUAL UNIT)
=  Sinkron dengan ProductController & Model
=============================================== --}}

@php
  /** @var \App\Models\Product|null $product */
  $product = $product ?? null;

  $val = fn($key, $default=null) => old($key, $product?->{$key} ?? $default);

  // suppliers berupa id=>name
  $suppliers = $suppliers ?? collect();
  $suppliersCount = ($suppliers instanceof \Illuminate\Support\Collection) ? $suppliers->count() : (is_array($suppliers) ? count($suppliers) : 0);

  $drugClasses = $drugClasses ?? ['OTC','Prescription','Narcotic','Herbal','Other'];
@endphp

<style>
  .cardx{background:var(--bg-elev,#fff);border:1px solid var(--line,#e5e7eb);border-radius:14px;padding:16px;margin-bottom:14px;box-shadow:var(--shadow,0 8px 20px rgba(2,6,23,.06))}
  .muted{color:#6b7280}
  .section-title{font-weight:800;letter-spacing:.2px;margin-bottom:.65rem;display:flex;align-items:center;gap:.5rem}
  .kpi{background:rgba(141,27,27,.03);border:1px dashed var(--line,#e5e7eb);border-radius:12px;padding:.8rem 1rem}
  [data-theme="dark"] .kpi{background:rgba(211,90,90,.08)}
  .kpi .lbl{font-size:.82rem;color:#6b7280;font-weight:600}
  .kpi .val{font-weight:800}
  .input-group-text{min-width:46px;justify-content:center}
  .btn-ghost{border:1px solid var(--line,#e5e7eb);background:var(--bg-elev,#fff);border-radius:10px}
  .grid{display:grid;gap:.75rem}
  .grid-2{grid-template-columns:1fr 1fr}
  .grid-3{grid-template-columns:repeat(3,1fr)}
  .grid-4{grid-template-columns:repeat(4,1fr)}
  @media (max-width: 992px){
    .grid-2,.grid-3,.grid-4{grid-template-columns:1fr}
  }
</style>

{{-- ====== IDENTITAS & SUPPLIER ====== --}}
<div class="cardx">
  <div class="section-title">
    <i data-feather="tag"></i> Identitas Produk
  </div>

  <div class="grid grid-4">
    <div>
      <label class="form-label">SKU</label>
      <input type="text" name="sku" class="form-control" value="{{ $val('sku') }}" placeholder="Opsional">
      <div class="muted small mt-1">Boleh kosong (auto/manual)</div>
    </div>

    <div>
      <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control" value="{{ $val('name') }}" required>
    </div>

    <div>
      <label class="form-label">Satuan Terkecil</label>
      <input type="text" name="unit" class="form-control" value="{{ $val('unit','Strip') }}" placeholder="Strip/Tablet/PCS">
      <div class="muted small mt-1">Informasi satuan dasar (opsional)</div>
    </div>

    <div>
      <label class="form-label">Barcode</label>
      <input type="text" name="barcode" class="form-control" value="{{ $val('barcode') }}" placeholder="Opsional, unik">
    </div>
  </div>

  <div class="grid grid-3 mt-2">
    <div>
      <label class="form-label">Status</label>
      {{-- hidden fallback agar selalu kirim 0/1 --}}
      <input type="hidden" name="is_active" value="0">
      <select name="is_active" class="form-select">
        <option value="1" {{ $val('is_active', true) ? 'selected' : '' }}>Aktif</option>
        <option value="0" {{ !$val('is_active', true) ? 'selected' : '' }}>Nonaktif</option>
      </select>
    </div>

    <div>
      <label class="form-label">Golongan Obat</label>
      <select name="drug_class" id="drug_class" class="form-select">
        <option value="">— Pilih —</option>
        @foreach($drugClasses as $dc)
          <option value="{{ $dc }}" @selected($val('drug_class')===$dc)>{{ $dc }}</option>
        @endforeach
      </select>
      <div class="muted small mt-1">Wajib saat produk ditandai Obat.</div>
    </div>

    <div>
      <label class="form-label">Jenis</label>
      <div class="d-flex gap-3 align-items-center">
        {{-- hidden fallback untuk checkbox --}}
        <input type="hidden" name="is_medicine" value="0">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_medicine" value="1" id="is_medicine"
                 {{ $val('is_medicine', true) ? 'checked' : '' }}>
          <label class="form-check-label" for="is_medicine">Obat</label>
        </div>

        <input type="hidden" name="is_compounded" value="0">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_compounded" value="1" id="is_compounded"
                 {{ $val('is_compounded', false) ? 'checked' : '' }}>
          <label class="form-check-label" for="is_compounded">Racikan</label>
        </div>
      </div>
    </div>
  </div>

  <div class="section-title mt-3">
    <i data-feather="truck"></i> Supplier (Opsional)
  </div>
  @if($suppliersCount > 0)
    <div class="grid">
      <div>
        <label class="form-label">Pilih Supplier</label>
        <select name="supplier_id" class="form-select">
          <option value="">— Pilih Supplier —</option>
          @foreach($suppliers as $id => $name)
            <option value="{{ $id }}" @selected((int)$val('supplier_id') === (int)$id)>{{ $name }}</option>
          @endforeach
        </select>
        <div class="muted small mt-1">Tidak wajib. Hubungkan ke pemasok utama.</div>
      </div>
    </div>
  @else
    <div class="grid">
      <div>
        <label class="form-label">Pilih Supplier</label>
        <div class="d-flex gap-2">
          <input type="text" class="form-control" value="Belum ada supplier" disabled>
          <a href="{{ route('suppliers.create') }}" target="_blank" class="btn btn-outline-primary">
            Tambah Supplier
          </a>
        </div>
        <div class="muted small mt-1">Buat supplier agar dapat dihubungkan ke produk.</div>
      </div>
    </div>
  @endif
</div>

{{-- ====== KEMASAN (BOX) & UNIT JUAL ====== --}}
<div class="cardx">
  <div class="section-title">
    <i data-feather="package"></i> Kemasan & Unit Jual
  </div>

  <div class="grid grid-3">
    <div>
      <label class="form-label">Nama Kemasan (Beli)</label>
      <input type="text" name="pack_name" class="form-control" value="{{ $val('pack_name','Box') }}" required>
      <div class="muted small mt-1">Contoh: Box</div>
    </div>

    <div>
      <label class="form-label">Isi / Kemasan (Qty)</label>
      <input type="number" min="1" step="1" name="pack_qty" id="pack_qty" class="form-control"
             value="{{ (int)$val('pack_qty',50) }}" required>
      <div class="muted small mt-1">Contoh: 1 Box = 50 (strip/tablet/pcs)</div>
    </div>

    <div>
      <label class="form-label">Unit Jual</label>
      <input type="text" name="sell_unit" id="sell_unit" class="form-control" value="{{ $val('sell_unit','Strip') }}" required>
      <div class="muted small mt-1">Contoh: Strip</div>
    </div>
  </div>
</div>

{{-- ====== HARGA BELI (PER BOX), PPN & DISKON (BOX) ====== --}}
<div class="cardx">
  <div class="section-title">
    <i data-feather="shopping-bag"></i> Harga Beli (Per Box) + PPN & Diskon
  </div>

  <div class="grid grid-4">
    <div>
      <label class="form-label">Harga Beli / Box <span class="text-danger">*</span></label>
      <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="number" min="0" step="1" name="buy_price_pack" id="buy_price_pack" class="form-control"
               value="{{ (float)$val('buy_price_pack',0) }}" required>
      </div>
      <div class="muted small mt-1">Harga neto (sebelum PPN & diskon)</div>
    </div>

    <div>
      <label class="form-label">PPN (Box)</label>
      <div class="input-group">
        <input type="number" min="0" step="0.01" name="ppn_percent" id="ppn_percent" class="form-control"
               value="{{ (float)$val('ppn_percent',11) }}">
        <span class="input-group-text">%</span>
      </div>
    </div>

    <div>
      <label class="form-label">Diskon % (Box)</label>
      <div class="input-group">
        <input type="number" min="0" step="0.01" name="disc_percent" id="disc_percent" class="form-control"
               value="{{ (float)$val('disc_percent',0) }}">
        <span class="input-group-text">%</span>
      </div>
    </div>

    <div>
      <label class="form-label">Diskon Rp (Box)</label>
      <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="number" min="0" step="1" name="disc_amount" id="disc_amount" class="form-control"
               value="{{ (float)$val('disc_amount',0) }}">
      </div>
    </div>
  </div>

  {{-- Hidden legacy agar backend lama tetep happy --}}
  <input type="hidden" name="tax_percent" id="tax_percent_legacy" value="{{ (float)$val('ppn_percent',11) }}">
  <input type="hidden" name="discount_percent" id="discount_percent_legacy" value="{{ (float)$val('disc_percent',0) }}">
</div>

{{-- ====== HARGA JUAL (PER UNIT), MARGIN & KPI ====== --}}
<div class="cardx">
  <div class="section-title">
    <i data-feather="dollar-sign"></i> Harga Jual (Per Unit) & Margin
  </div>

  <div class="grid grid-3">
    <div>
      <label class="form-label">Target Margin (opsional)</label>
      <div class="input-group">
        <input type="number" min="0" step="0.01" id="target_margin" class="form-control" value="25">
        <span class="input-group-text">%</span>
      </div>
      <button type="button" class="btn btn-ghost w-100 mt-2" id="btnFromMargin">
        Hitung Harga Jual dari Margin
      </button>
    </div>

    <div>
      <label class="form-label">Harga Jual / Unit <span class="text-danger">*</span></label>
      <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="number" min="0" step="1" name="sell_price" id="sell_price" class="form-control"
               value="{{ (float)$val('sell_price',5000) }}" required>
      </div>
      <div class="muted small mt-1">Harga untuk 1 {{ $val('sell_unit','Strip') }}</div>
    </div>

    <div>
      <label class="form-label">Pratinjau HPP / Unit (auto)</label>
      <input type="text" id="hpp_preview" class="form-control" value="Rp 0" readonly>
      <div class="muted small mt-1">Disimpan ke <code>buy_price</code> oleh model</div>
    </div>
  </div>

  {{-- KPI ringkas --}}
  <div class="grid grid-3 mt-3">
    <div class="kpi">
      <div class="lbl">Harga + PPN / Box</div>
      <div class="val" id="v_box_plus_vat">Rp 0</div>
    </div>
    <div class="kpi">
      <div class="lbl">Total Diskon / Box</div>
      <div class="val" id="v_disc_total">Rp 0</div>
    </div>
    <div class="kpi">
      <div class="lbl">HPP / Unit (disimpan)</div>
      <div class="val" id="v_hpp_unit">Rp 0</div>
    </div>
  </div>

  <div class="grid grid-2 mt-2">
    <div class="kpi">
      <div class="lbl">Margin / Unit (Rp)</div>
      <div class="val" id="v_margin_nom">Rp 0</div>
    </div>
    <div class="kpi">
      <div class="lbl">Margin (%)</div>
      <div class="val" id="v_margin_pct">0 %</div>
    </div>
  </div>
</div>

{{-- ====== STOK & MINIMUM ====== --}}
<div class="cardx">
  <div class="section-title">
    <i data-feather="layers"></i> Stok
  </div>

  <div class="grid grid-2">
    <div>
      <label class="form-label">Stok (Unit Jual)</label>
      <input type="number" name="stock" class="form-control" min="0" step="1" value="{{ (int)$val('stock',0) }}">
      <div class="muted small mt-1">Disarankan: stok pakai unit jual ({{ $val('sell_unit','Strip') }})</div>
    </div>
    <div>
      <label class="form-label">Stok Minimum</label>
      <input type="number" name="min_stock" class="form-control" min="0" step="1" value="{{ (int)$val('min_stock',0) }}">
    </div>
  </div>
</div>

{{-- ====== JS Kalkulator Live + Sinkronisasi Legacy + Safety drug_class ====== --}}
<script>
(function(){
  const $ = id => document.getElementById(id);
  const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(+n||0)));

  const packQty     = $('pack_qty');
  const sellUnitInp = $('sell_unit');

  const buyPack     = $('buy_price_pack');
  const ppnPct      = $('ppn_percent');
  const discPct     = $('disc_percent');
  const discAmt     = $('disc_amount');

  const sellPrice   = $('sell_price');
  const targetMargin= $('target_margin');

  const vBoxVAT     = $('v_box_plus_vat');
  const vDiscTot    = $('v_disc_total');
  const vHppUnit    = $('v_hpp_unit');
  const vMarginNom  = $('v_margin_nom');
  const vMarginPct  = $('v_margin_pct');
  const hppPreview  = $('hpp_preview');

  // legacy hidden
  const taxLegacy   = $('tax_percent_legacy');
  const discLegacy  = $('discount_percent_legacy');

  // medicine toggle
  const isMedCB     = $('is_medicine');
  const drugClass   = $('drug_class');

  function syncLegacy(){
    if (taxLegacy)  taxLegacy.value  = (parseFloat(ppnPct?.value||0)  || 0).toFixed(2);
    if (discLegacy) discLegacy.value = (parseFloat(discPct?.value||0) || 0).toFixed(2);
  }

  function compute(){
    const qty  = Math.max(1, parseInt(packQty?.value||1,10));
    const pricePack = +buyPack?.value || 0;        // harga beli per BOX (sebelum PPN)
    const ppn   = (+ppnPct?.value||0) / 100;
    const dPct  = (+discPct?.value||0) / 100;
    const dAmt  = +discAmt?.value || 0;

    const jual  = +sellPrice?.value || 0;

    // 1) Harga + PPN per BOX
    const withVAT = pricePack * (1 + ppn);
    // 2) Diskon total per BOX
    const discTotal = (pricePack * dPct) + dAmt;
    // 3) Net BOX
    const netBox = Math.max(0, withVAT - discTotal);
    // 4) HPP per UNIT
    const hppUnit = qty > 0 ? (netBox / qty) : 0;
    // 5) Margin
    const marginNom = Math.max(0, jual - hppUnit);
    const marginPct = jual > 0 ? (marginNom / jual) * 100 : 0;

    vBoxVAT.textContent  = fmt(withVAT);
    vDiscTot.textContent = fmt(discTotal);
    vHppUnit.textContent = fmt(hppUnit);
    vMarginNom.textContent = fmt(marginNom);
    vMarginPct.textContent = marginPct.toFixed(2) + ' %';
    hppPreview.value = fmt(hppUnit);

    syncLegacy();
  }

  function calcFromMargin(){
    const qty  = Math.max(1, parseInt(packQty?.value||1,10));
    const pricePack = +buyPack?.value || 0;
    const ppn   = (+ppnPct?.value||0) / 100;
    const dPct  = (+discPct?.value||0) / 100;
    const dAmt  = +discAmt?.value || 0;
    const m     = (+targetMargin?.value||0) / 100;

    const withVAT = pricePack * (1 + ppn);
    const discTotal = (pricePack * dPct) + dAmt;
    const netBox = Math.max(0, withVAT - discTotal);
    const hppUnit = qty > 0 ? (netBox / qty) : 0;

    const jual = (m >= 1) ? hppUnit : (hppUnit / (1 - m));
    sellPrice.value = Math.max(0, Math.round(jual));
    compute();
  }

  function toggleDrugClass(){
    const isMed = isMedCB?.checked;
    if (!drugClass) return;

    if (isMed) {
      drugClass.removeAttribute('disabled');
    } else {
      // lock jadi 'Other' biar tidak NULL ke server
      if (!drugClass.value) drugClass.value = 'Other';
      drugClass.setAttribute('disabled','disabled');
    }
  }

  // sebelum submit: validasi ringan drug_class
  const formEl = document.currentScript.closest('form');
  if (formEl) {
    formEl.addEventListener('submit', function(e){
      if (!isMedCB || !drugClass) return;
      if (isMedCB.checked) {
        if (!drugClass.value) {
          e.preventDefault();
          alert('Golongan Obat wajib dipilih karena produk ditandai sebagai Obat.');
          drugClass.focus();
          return false;
        }
      } else {
        // set paksa Other agar tidak null ke server
        if (!drugClass.value) drugClass.value = 'Other';
        drugClass.disabled = true; // sesuai toggleDrugClass
      }
    });
  }

  [packQty,sellUnitInp,buyPack,ppnPct,discPct,discAmt,sellPrice,targetMargin]
    .filter(Boolean)
    .forEach(el=>{
      el.addEventListener('input', compute);
      el.addEventListener('change', compute);
    });

  const btn = document.getElementById('btnFromMargin');
  btn?.addEventListener('click', calcFromMargin);

  isMedCB?.addEventListener('change', toggleDrugClass);

  // init
  compute();
  toggleDrugClass();
})();
</script>
