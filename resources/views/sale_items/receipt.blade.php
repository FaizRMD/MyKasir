<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Struk #{{ $sale->invoice_no ?? $sale->id }}</title>
<style>
  /* ===================== PARAMETER MUDAH DISESUAIKAN ===================== */
  :root{
    --paper: 55mm;      /* lebar kertas roll */
    --content: 46.5mm;  /* lebar area CETAK aman (kecilkan kalau masih kepotong) */
    --pad-h: 2.5mm;     /* padding kiri/kanan ekstra agar aman dari tepi */
    --fs: 12px;         /* font ukuran cetak (lebih tebal dari 11px) */
    --fs-small: 10.5px;
    --gap: 4px;
    --pricech: 10ch;    /* lebar kolom harga: muat "1.400.000" */
  }

  /* ================= PREVIEW (LAYAR) ================= */
  @media screen {
    html, body {
      margin: 0;
      background: #f2f4f7;
      font-family: 'Courier New', monospace;
      font-size: 12px;
      line-height: 1.25;
      color: #000;
    }
    .preview {
      min-height: 100vh;
      display: grid;
      place-items: start center;
      padding: 24px;
    }
    .paper {
      width: var(--paper);
      background: #fff;
      box-shadow: 0 8px 24px rgba(0,0,0,.12);
      padding: 0 var(--pad-h);
      transform: scale(1.7);
      transform-origin: top center;
    }
    .core { width: var(--content); margin: 0 auto; }
  }

  /* ================= CETAK ================= */
  @media print {
    @page { size: var(--paper) auto; margin: 0; }
    html, body {
      width: var(--paper) !important;
      margin: 0 !important;
      padding: 0;
      font-family: 'Courier New', monospace;
      font-size: var(--fs);
      line-height: 1.22;
      color: #000;
      -webkit-print-color-adjust: exact;
              print-color-adjust: exact;
    }
    .paper {
      width: var(--paper) !important;
      padding: 0 var(--pad-h) !important;
      box-shadow: none !important;
      transform: none !important;
    }
    .core {
      width: var(--content) !important;  /* kuncinya agar tidak kena tepi */
      margin: 0 auto !important;
    }
    .no-print { display: none !important; }
  }

  /* ================= GAYA KONTEN ================= */
  * { box-sizing: border-box; }
  .center { text-align: center; }
  .right  { text-align: right; }
  .row    { display: flex; justify-content: space-between; gap: var(--gap); }
  hr { border: 0; border-top: 1px dashed #000; margin: 4px 0; }
  .small { font-size: var(--fs-small); }
  .bold  { font-weight: 700; }
  .num   { font-variant-numeric: tabular-nums; }

  /* Baris item: nama fleksibel, harga fixed 10ch, selalu muat */
  .item-line {
    display: grid;
    grid-template-columns: 1fr var(--pricech);
    gap: var(--gap);
    align-items: start;
  }
  .item-name {
    word-break: break-word;
    overflow-wrap: anywhere;
  }
  .item-price {
    text-align: right;
    white-space: nowrap;     /* jangan patah angka */
  }
  .item-sub {
    display: flex;
    justify-content: space-between;
    gap: var(--gap);
    font-size: var(--fs-small);
  }
</style>
</head>
<body>
<div class="preview">
  <div class="paper">
    <div class="core">
      <div class="center bold">
        MY KASIR<br>
        <span class="small">Jl. Jendral Soedirman No. 123 • 0883812876990</span>
      </div>
      <hr>
      <div class="row small">
        <div>No: {{ $sale->invoice_no ?? $sale->id }}</div>
        <div>{{ optional($sale->created_at)->format('d/m/Y H:i') }}</div>
      </div>
      <div class="small">Kasir: {{ optional($sale->user)->name ?? '-' }}</div>
      @if(!empty($sale->customer_name))
        <div class="small">Pelanggan: {{ $sale->customer_name }}</div>
      @endif
      <hr>

      @foreach($sale->items as $it)
        @php
          $nama  = $it->product->name ?? $it->name ?? 'Item';
          $qty   = (int) $it->qty;
          $harga = (float) $it->price;
          $ppn   = (float) ($it->tax_percent ?? 0);
          $base  = $qty * $harga;
          $sub   = $base + ($ppn > 0 ? $base * ($ppn/100) : 0);
        @endphp
        <div class="item-line">
          <div class="item-name">{{ $nama }}</div>
          <div class="item-price num">{{ number_format($sub,0,',','.') }}</div>
        </div>
        <div class="item-sub">
          <div>
            {{ $qty }} x {{ number_format($harga,0,',','.') }}
            @if($ppn>0) • PPN {{ rtrim(rtrim(number_format($ppn,2,',','.'),'0'),',') }}% @endif
          </div>
          <div></div>
        </div>
      @endforeach

      <hr>
      <div class="row">
        <div>Subtotal</div>
        <div class="num">{{ number_format($sale->subtotal ?? 0,0,',','.') }}</div>
      </div>
      @if(($sale->tax ?? 0) > 0)
      <div class="row">
        <div>PPN</div>
        <div class="num">{{ number_format($sale->tax,0,',','.') }}</div>
      </div>
      @endif
      @if(($sale->discount ?? 0) > 0)
      <div class="row">
        <div>Diskon</div>
        <div class="num">-{{ number_format($sale->discount,0,',','.') }}</div>
      </div>
      @endif
      <div class="row bold">
        <div>Total</div>
        <div class="num">{{ number_format($sale->grand_total ?? 0,0,',','.') }}</div>
      </div>
      <div class="row">
        <div>Bayar</div>
        <div class="num">{{ number_format($sale->paid ?? 0,0,',','.') }}</div>
      </div>
      <div class="row">
        <div>Kembali</div>
        <div class="num">{{ number_format($sale->change ?? 0,0,',','.') }}</div>
      </div>
      <div class="small">Metode: {{ strtoupper($sale->payment_method ?? 'CASH') }}</div>
      <hr>
      <div class="center small">Terima kasih 🙏<br>Barang yang sudah dibeli tidak dapat ditukar</div>

      <div class="no-print center" style="margin-top:8px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.location.href='{{ route('kasir.index') }}'" style="margin-left:8px">Kembali ke POS</button>
      </div>
    </div>
  </div>
</div>

@if($autoPrint)
<script>window.addEventListener('load', () => window.print());</script>
@endif
</body>
</html>
