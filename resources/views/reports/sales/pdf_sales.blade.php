<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body{font-family: DejaVu Sans, Arial, Helvetica; font-size:12px;}
    table{width:100%; border-collapse:collapse}
    th,td{border:1px solid #ddd; padding:6px; vertical-align:top}
    thead th{background:#f1f5f9}
    h3{margin:0 0 8px 0}
    .right{text-align:right}
  </style>
</head>
<body>
  <h3>Laporan Penjualan (Transaksi)</h3>
  <table>
    <thead>
      <tr>
        <th>Tanggal</th>
        <th>Invoice</th>
        <th>Customer</th>
        <th>Kasir</th>
        <th>Metode</th>
        <th class="right">Item</th>
        <th class="right">Subtotal</th>
        <th class="right">Diskon</th>
        <th class="right">Pajak</th>
        <th class="right">Grand Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
        <tr>
          <td>{{ optional($r->sale_date)->format('Y-m-d H:i') }}</td>
          <td>{{ $r->invoice_no }}</td>
          <td>{{ $r->customer_name }}</td>
          <td>{{ $r->cashier_name }}</td>
          <td>{{ $r->payment_method }}</td>
          <td class="right">{{ (int) $r->items_count }}</td>
          <td class="right">{{ number_format($r->items_total,0,',','.') }}</td>
          <td class="right">{{ number_format($r->discount_total,0,',','.') }}</td>
          <td class="right">{{ number_format($r->tax_total,0,',','.') }}</td>
          <td class="right"><strong>{{ number_format($r->grand_total,0,',','.') }}</strong></td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <p style="margin-top:8px">
    <strong>Ringkasan:</strong>
    Grand Total: Rp {{ number_format($summary->grand_total_sum ?? 0,0,',','.') }},
    Diskon: Rp {{ number_format($summary->discount_sum ?? 0,0,',','.') }},
    Pajak: Rp {{ number_format($summary->tax_sum ?? 0,0,',','.') }},
    Transaksi: {{ number_format($summary->trx_count ?? 0,0,',','.') }}
  </p>
</body>
</html>
