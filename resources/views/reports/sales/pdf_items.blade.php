<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>{{ $title ?? 'Laporan Penjualan (Item/Obat)' }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
    h2 { text-align: center; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 6px; }
    th { background: #f5f5f5; text-align:left; }
    .right { text-align: right; }
    .muted { color:#666; }
  </style>
</head>
<body>
  <h2>{{ $title ?? 'Laporan Penjualan (Item/Obat)' }}</h2>

  @php
    // fallback: bisa pakai $items atau $rows
    $data = isset($items) ? $items : ($rows ?? collect());
    // helper number format aman
    $nf = fn($v) => number_format((float)($v ?? 0), 0, ',', '.');
  @endphp

  @if(($data instanceof \Illuminate\Support\Collection ? $data->count() : count($data ?? [])) === 0)
    <p class="muted">Tidak ada data untuk filter yang dipilih.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Invoice</th>
          <th>Produk</th>
          <th>Batch</th>
          <th class="right">Qty</th>
          <th class="right">Harga</th>
          <th class="right">Pajak (%)</th>
          <th class="right">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($data as $it)
          <tr>
            <td>{{ optional($it->item_date)->format('Y-m-d H:i') }}</td>
            <td>{{ $it->invoice_no }}</td>
            <td>{{ $it->product_name }}</td>
            <td>{{ $it->batch_no }}</td>
            <td class="right">{{ (int)($it->qty ?? 0) }}</td>
            <td class="right">Rp {{ $nf($it->price ?? 0) }}</td>
            <td class="right">{{ $nf($it->tax_percent ?? 0) }}</td>
            <td class="right">Rp {{ $nf($it->total ?? 0) }}</td>
          </tr>
        @endforeach
      </tbody>
      @isset($summary)
        <tfoot>
          <tr>
            <th colspan="4" class="right">Total</th>
            <th class="right">{{ $nf($summary->qty_sum ?? 0) }}</th>
            <th></th>
            <th></th>
            <th class="right">Rp {{ $nf($summary->total_sum ?? 0) }}</th>
          </tr>
        </tfoot>
      @endisset
    </table>
  @endif

  <p class="muted">Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
