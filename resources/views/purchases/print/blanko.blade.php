{{-- resources/views/purchases/print/blanko.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Blanko Pemesanan — {{ $purchase->po_no ?? 'PO' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Style @page akan disuntik dinamis via JS --}}
  <style id="printPageStyle">
    @page { size: A4 portrait; margin: 14mm; } /* default */
  </style>

  <style>
    :root{
      --maroon:#7a1020; --maroon-600:#5a0c18;
      --ink:#111827; --muted:#6b7280; --border:#e5e7eb; --soft:#fafafa;
    }
    @media print{
      .no-print{ display:none !important; }
      body{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .sheet{ box-shadow:none; margin:0; }
      a{ text-decoration:none; color:inherit; }
    }
    *{ box-sizing:border-box; }
    html,body{ padding:0; margin:0; background:#fff; color:var(--ink); }
    body{ font:12px/1.45 ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; }

    /* Toolbar (hanya layar) */
    .toolbar.no-print{ position:sticky; top:0; z-index:9; background:#fff; border-bottom:1px solid #f3f4f6; padding:10px 12px; margin-bottom:8px; }
    .toolbar__inner{ max-width:920px; margin:0 auto; display:flex; gap:10px; align-items:center; justify-content:flex-end; flex-wrap:wrap; }
    .sel,.btn{ height:36px; border:1px solid #d1d5db; background:#fff; border-radius:10px; padding:6px 10px; font-weight:600; font-size:12px; }
    .btn-maroon{ background:var(--maroon); color:#fff; border-color:var(--maroon-600); cursor:pointer; }
    .btn-maroon:hover{ background:var(--maroon-600); }

    .sheet{ max-width:920px; margin:0 auto; background:#fff; border:1px solid #f0f0f0; border-radius:14px; overflow:hidden; box-shadow:0 10px 24px rgba(0,0,0,.05); }

    /* Header */
    .header{ display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px;
      background:linear-gradient(90deg, rgba(122,16,32,.09), rgba(122,16,32,.03)); border-bottom:1px solid #f1f1f1; }
    .brand{ display:flex; align-items:center; gap:12px; }
    /* Fallback kotak gradasi bila gambar tak ada */
    .brand__logo{ width:44px; height:44px; border-radius:12px; background:radial-gradient(circle at 30% 30%, #ad2137, #6e0f1e); box-shadow:inset 0 0 0 2px rgba(255,255,255,.35); }
    /* Gambar logo dari public/ */
    .brand__img{
      width:44px; height:44px; border-radius:12px;
      object-fit:contain; background:#fff;
      box-shadow:inset 0 0 0 2px rgba(255,255,255,.35);
      display:block; image-rendering:auto;
    }
    @media print{
      .brand__img{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    }

    .brand__text{ line-height:1.15; }
    .appname{ font-size:16px; font-weight:800; color:var(--maroon); letter-spacing:.3px; }
    .subtitle{ font-size:11px; color:var(--muted); }
    .address{ font-size:11px; color:#374151; }

    .doc-meta{ text-align:right; }
    .doc-title{ font-size:18px; font-weight:800; margin:0 0 4px; }
    .badge{ display:inline-block; padding:3px 10px; border:1px solid var(--maroon); color:var(--maroon); border-radius:999px; font-weight:700; font-size:11px; background:#fff; }
    .subline{ color:var(--muted); font-size:11px; margin-top:6px; }

    .grid{ display:grid; grid-template-columns:1.2fr .8fr; gap:12px; padding:14px 20px 8px; }
    .card{ border:1px solid var(--border); border-radius:12px; background:#fff; overflow:hidden; }
    .card__hd{ background:var(--soft); padding:8px 12px; font-weight:700; font-size:12px; color:#1f2937; border-bottom:1px solid var(--border); }
    .card__bd{ padding:10px 12px; }
    .row{ display:flex; justify-content:space-between; gap:12px; padding:5px 0; border-bottom:1px dashed #eee; }
    .row:last-child{ border-bottom:none; }
    .label{ color:#4b5563; min-width:110px; }
    .val{ font-weight:600; color:#111827; text-align:right; }

    table{ width:100%; border-collapse:collapse; }
    thead th{ background:var(--maroon); color:#fff; border:1px solid var(--maroon-600); font-weight:700; padding:8px; text-align:left; font-size:12px; }
    tbody td{ border:1px solid var(--border); padding:8px; vertical-align:top; font-size:12px; }
    tbody tr:nth-child(odd) td{ background:#fcfcfc; }
    .text-end{text-align:right}.text-center{text-align:center}
    .w-no{width:40px}.w-code{width:120px}.w-uom{width:90px}.w-qty{width:90px}.w-money{width:120px}

    .summary{ display:grid; grid-template-columns:1fr .6fr; gap:14px; padding:12px 20px 16px; align-items:start; }
    .notebox{ border:1px dashed var(--border); border-radius:12px; padding:12px; min-height:84px; background:#fff; }
    .totals{ border:1px solid var(--border); border-radius:12px; overflow:hidden; }
    .totals th,.totals td{ padding:8px 10px; border-bottom:1px solid var(--border); font-size:12px; }
    .totals tr:last-child td{ border-bottom:none; }
    .totals .grand{ font-size:14px; font-weight:800; color:var(--maroon); }

    .signs{ display:grid; grid-template-columns:1fr 1fr; gap:18px; padding:0 20px 20px; }
    .sign-card{ border:1px dashed var(--border); border-radius:12px; padding:16px; min-height:120px; background:#fff; }
    .sign-title{ font-weight:700; margin-bottom:28px; }
    .sign-name{ margin-top:40px; border-top:1px solid var(--border); padding-top:6px; text-align:center; font-weight:600; }

    .footer{ padding:10px 20px 18px; color:#6b7280; font-size:11px; display:flex; justify-content:space-between; gap:12px; align-items:center; border-top:1px solid #f1f1f1; background:#fff; }
    .terms{ line-height:1.35; }
  </style>
</head>
<body>
  @php
    // Branding
    $brandName    = 'MYKASIR';
    $brandAddress = 'Indramayu — Kec. Bongas, Kabupaten Indramayu';

    /**
     * Kirim dari controller bila ada: with('brandLogo', 'images/mykasir.png')
     * Jika tidak, fallback ke public/images/logo.png
     */
    $brandLogo    = $brandLogo ?? 'images/logo.png';
    $brandLogoUrl = $brandLogo ? asset(trim($brandLogo, '/')) : null;

    // Auto print & back URL (bisa dikirim dari controller; fallback dari query ?auto=1)
    $autoPrint = isset($autoPrint) ? (bool)$autoPrint : request()->boolean('auto', false);
    $backUrl   = $backUrl
                  ?? (\Illuminate\Support\Facades\Route::has('purchases.show') ? route('purchases.show', $purchase) : null)
                  ?? (\Illuminate\Support\Facades\Route::has('purchases.index') ? route('purchases.index') : url('/'));
  @endphp

  {{-- Toolbar (pilihan ukuran cetak) --}}
  <div class="toolbar no-print">
    <div class="toolbar__inner">
      <label>Ukuran:
        <select id="selSize" class="sel">
          <option value="A4" selected>A4</option>
          <option value="A5">A5</option>
          <option value="Letter">Letter</option>
          <option value="Legal">Legal</option>
        </select>
      </label>
      <label>Orientasi:
        <select id="selOrient" class="sel">
          <option value="portrait" selected>Portrait</option>
          <option value="landscape">Landscape</option>
        </select>
      </label>
      <label>Margin:
        <select id="selMargin" class="sel">
          <option value="8">Narrow (8mm)</option>
          <option value="14" selected>Normal (14mm)</option>
          <option value="20">Wide (20mm)</option>
        </select>
      </label>
      <button class="btn btn-maroon" id="btnPrint">Cetak</button>
      <a class="btn" id="btnBack" href="{{ $backUrl }}">Kembali</a>
    </div>
  </div>

  <div class="sheet">
    {{-- HEADER --}}
    <div class="header">
      <div class="brand">
        @if(!empty($brandLogoUrl))
          <img src="{{ $brandLogoUrl }}" alt="Logo {{ $brandName }}" class="brand__img">
        @else
          <div class="brand__logo" aria-hidden="true"></div>
        @endif
        <div class="brand__text">
          <div class="appname">{{ $brandName }}</div>
          <div class="subtitle">Formulir Pemesanan Barang (PO)</div>
          <div class="address">{{ $brandAddress }}</div>
        </div>
      </div>
      <div class="doc-meta">
        <div class="doc-title">Blanko Pemesanan</div>
        <div class="badge">{{ $purchase->po_no ?? 'PO' }}</div>
        <div class="subline">
          Dibuat: {{ optional($purchase->created_at)->timezone('Asia/Jakarta')?->format('d M Y H:i') ?? '-' }} WIB ·
          Oleh: {{ $purchase->user->name ?? '—' }}
        </div>
      </div>
    </div>

    {{-- INFO --}}
    <div class="grid">
      <div class="card">
        <div class="card__hd">Informasi Supplier</div>
        <div class="card__bd">
          <div class="row"><div class="label">Nama</div><div class="val">{{ $purchase->supplier->name ?? '—' }}</div></div>
          <div class="row"><div class="label">Alamat</div><div class="val">{{ $purchase->supplier->address ?? '—' }}</div></div>
          <div class="row"><div class="label">Telepon</div><div class="val">{{ $purchase->supplier->phone ?? '—' }}</div></div>
          <div class="row"><div class="label">Email</div><div class="val">{{ $purchase->supplier->email ?? '—' }}</div></div>
        </div>
      </div>

      <div class="card">
        <div class="card__hd">Detail PO</div>
        <div class="card__bd">
          <div class="row">
            <div class="label">Nomor Faktur</div>
            <div class="val">{{ $purchase->invoice_no ?? '-' }}</div>
          </div>
          <div class="row">
            <div class="label">Tanggal Faktur</div>
            <div class="val">{{ optional($purchase->invoice_date ?? $purchase->created_at)->timezone('Asia/Jakarta')?->format('d M Y') ?? '-' }}</div>
          </div>
          <div class="row">
            <div class="label">Tanggal PO</div>
            <div class="val">{{ optional($purchase->po_date ?? $purchase->tanggal)->format('d M Y') ?? '—' }}</div>
          </div>
          <div class="row"><div class="label">Kategori</div><div class="val">{{ $purchase->category ?? '—' }}</div></div>
          <div class="row"><div class="label">Jenis</div><div class="val">{{ $purchase->type ?? '—' }}</div></div>
          <div class="row"><div class="label">Apoteker</div><div class="val">{{ $purchase->apoteker->name ?? '—' }}</div></div>
          <div class="row"><div class="label">Status</div><div class="val" style="text-transform:uppercase">{{ $purchase->status ?? '—' }}</div></div>
        </div>
      </div>
    </div>

    {{-- TABEL ITEM --}}
    <div style="padding:0 20px 12px">
      <table>
        <thead>
          <tr>
            <th class="w-no text-center">No</th>
            <th class="w-code">Kode</th>
            <th>Nama Barang</th>
            <th class="w-uom text-center">Satuan</th>
            <th class="w-qty text-center">Qty</th>
            <th class="w-money text-end">Harga</th>
            <th class="w-money text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          @php $no=1; $grand = 0; @endphp
          @forelse($purchase->items as $it)
            @php
              $code  = $it->product->code ?? $it->product->kode ?? '';
              $name  = $it->product->name ?? $it->product->nama ?? '-';
              $uom   = $it->product->uom ?? $it->product->satuan ?? ($it->uom ?? 'pcs');
              $qty   = (int) ($it->qty ?? 0);
              $price = (float) ($it->cost ?? 0);
              $disc  = (float) ($it->discount ?? 0);
              $beforeTax = max(0, $qty * $price - $disc);
              $taxPct = (float) ($it->tax_pct ?? 0);
              $tax    = $beforeTax * ($taxPct/100);
              $sub    = $beforeTax + $tax;
              $grand += $sub;
            @endphp
            <tr>
              <td class="text-center">{{ $no++ }}</td>
              <td>{{ $code }}</td>
              <td>{{ $name }}</td>
              <td class="text-center">{{ $uom }}</td>
              <td class="text-center">{{ number_format($qty) }}</td>
              <td class="text-end">{{ number_format($price,2,',','.') }}</td>
              <td class="text-end">{{ number_format($sub,2,',','.') }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center" style="padding:14px; color:#6b7280">Tidak ada item.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- RINGKASAN & CATATAN --}}
    <div class="summary">
      <div class="notebox">
        <strong>Catatan:</strong>
        <div style="margin-top:6px">{{ $purchase->note ?? '—' }}</div>
      </div>
      <div class="totals">
        <table>
          <tr>
            <td>Total Item</td>
            <td class="text-end">{{ number_format($purchase->items->count()) }}</td>
          </tr>
          <tr>
            <td>Total Kuantitas</td>
            <td class="text-end">{{ number_format($purchase->items->sum('qty')) }}</td>
          </tr>
          <tr>
            <td class="grand">Grand Total</td>
            <td class="text-end grand">Rp {{ number_format($grand,2,',','.') }}</td>
          </tr>
        </table>
      </div>
    </div>

    {{-- TANDA TANGAN --}}
    <div class="signs">
      <div class="sign-card">
        <div class="sign-title">Pihak Pemesan (Apotek)</div>
        <div style="height:54px"></div>
        <div class="sign-name">{{ $purchase->apoteker->name ?? $purchase->user->name ?? '(Nama Jelas)' }}</div>
      </div>
      <div class="sign-card">
        <div class="sign-title">Pihak Pemasok (Supplier)</div>
        <div style="height:54px"></div>
        <div class="sign-name">{{ $purchase->supplier->name ?? '(Nama Perusahaan / Petugas)' }}</div>
      </div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
      <div class="terms">
        <div><strong>Ketentuan:</strong></div>
        <div>- Harga sudah termasuk potongan yang disepakati.</div>
        <div>- Barang yang diterima harap diperiksa; selisih/retur laporkan maksimal 3×24 jam.</div>
      </div>
      <div>
        <div style="font-weight:700; color:var(--maroon)">{{ $brandName }}</div>
        <div>{{ $brandAddress }}</div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      const elStyle   = document.getElementById('printPageStyle');
      const selSize   = document.getElementById('selSize');
      const selOrient = document.getElementById('selOrient');
      const selMargin = document.getElementById('selMargin');
      const btnPrint  = document.getElementById('btnPrint');
      const btnBack   = document.getElementById('btnBack');
      const backUrl   = @json($backUrl);

      function buildAtPage(){
        const size = selSize.value;
        const orient = selOrient.value;
        const marginMm = Number(selMargin.value)||14;
        return `@page { size: ${size} ${orient}; margin: ${marginMm}mm; }`;
      }
      function applyAtPage(){ elStyle.textContent = buildAtPage(); }
      [selSize, selOrient, selMargin].forEach(el=> el.addEventListener('change', applyAtPage));
      btnPrint.addEventListener('click', () => { applyAtPage(); setTimeout(()=>window.print(), 150); });

      // Pastikan tombol kembali memakai URL yang benar
      if (btnBack && backUrl) {
        btnBack.addEventListener('click', function(e){
          // anchor sudah punya href=backUrl; cukup biarkan default
        });
      }

      applyAtPage();
    })();
  </script>

  {{-- AUTO PRINT + auto back setelah print --}}
  @if($autoPrint)
  <script>
    (function(){
      const backUrl = @json($backUrl);
      // panggil print begitu halaman siap
      window.addEventListener('load', function(){
        try { window.print(); } catch(e) {}
        if ('onafterprint' in window) {
          window.onafterprint = function(){ if (backUrl) window.location.href = backUrl; };
        } else {
          // fallback jika onafterprint tidak tersedia
          setTimeout(function(){ if (backUrl) window.location.href = backUrl; }, 8000);
        }
      });
    })();
  </script>
  @endif
</body>
</html>
