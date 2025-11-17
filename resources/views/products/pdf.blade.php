<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'Daftar Produk' }}</title>
  <style>
    /* Dompdf-friendly styles (no external font) */
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #0f172a; }
    h1 { margin: 0 0 6px; font-size: 20px; }
    .muted { color: #64748b; }
    .small { font-size: 11px; }
    .mb-2 { margin-bottom: 8px; }
    .mb-3 { margin-bottom: 12px; }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 6px 8px; border: 1px solid #e5e7eb; }
    thead th { background: #eef2ff; font-weight: 700; }
    .right { text-align: right; }
    .center { text-align: center; }
    .nowrap { white-space: nowrap; }
  </style>
</head>
<body>
  <h1>{{ $title ?? 'Daftar Produk' }}</h1>

  {{-- Ringkasan filter --}}
  @php
    // Normalisasi key dari controller: dukung 'drug_class' & 'drugClass', 'supplier_id' & 'supplierId'
    $filters = $filters ?? [];
    $norm = [
      'q'           => $filters['q']          ?? null,
      'drug_class'  => $filters['drug_class'] ?? ($filters['drugClass']  ?? null),
      'status'      => $filters['status']     ?? null,
      'supplier_id' => $filters['supplier_id']?? ($filters['supplierId'] ?? null),
    ];
    $map = [
      'q'           => 'Cari',
      'drug_class'  => 'Golongan',
      'status'      => 'Status',
      'supplier_id' => 'Supplier ID',
    ];
  @endphp

  @if(collect($norm)->filter(fn($v) => filled($v))->isNotEmpty())
    <div class="small muted mb-3">
      @foreach($norm as $k => $v)
        @if(filled($v))
          <span class="nowrap"><strong>{{ $map[$k] ?? $k }}:</strong> {{ $v }}</span>&nbsp;&nbsp;
        @endif
      @endforeach
    </div>
  @endif

  @php $rows = collect($rows ?? []); @endphp

  @if($rows->isEmpty())
    <p class="muted">Tidak ada data sesuai filter.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>SKU</th>
          <th>Nama</th>
          <th>Supplier</th>
          <th>Golongan</th>
          <th class="right">Harga Beli</th>
          <th class="right">Harga Jual</th>
          <th class="center">Stok</th>
          <th class="center">Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $p)
          @php
            $stock = (int)($p->stock ?? 0);
            $min   = (int)($p->min_stock ?? 0);
            $stockState = $stock <= 0 ? 'Habis' : ($min > 0 && $stock <= $min ? 'Min' : 'OK');
          @endphp
          <tr>
            <td class="nowrap">{{ $p->sku ?: '—' }}</td>
            <td>
              <div>{{ $p->name }}</div>
              <div class="small muted">Barcode: {{ $p->barcode ?: '—' }}</div>
            </td>
            <td class="nowrap">{{ optional($p->supplier)->name ?? '—' }}</td>
            <td>{{ $p->drug_class ?? '—' }}</td>
            <td class="right">{{ number_format((float)($p->buy_price ?? 0), 0, ',', '.') }}</td>
            <td class="right">{{ number_format((float)($p->sell_price ?? 0), 0, ',', '.') }}</td>
            <td class="center">{{ $stock }}</td>
            <td class="center">{{ ($p->is_active ? 'Aktif' : 'Nonaktif').' / '.$stockState }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <div class="small muted mb-2">
    Dicetak: {{ now()->format('d/m/Y H:i') }}
  </div>
</body>
</html>
