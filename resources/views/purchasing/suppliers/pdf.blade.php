@php($showWarning = isset($dompdf_missing) && $dompdf_missing)
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Supplier</title>
  <style>
    body{font-family:DejaVu Sans, sans-serif; font-size:12px; color:#111}
    h2{text-align:center;margin:0 0 10px}
    .meta{font-size:11px;margin-bottom:8px}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{border:1px solid #ccc;padding:6px;text-align:left}
    th{background:#f5f5f5}
    .right{text-align:right}
    .warn{padding:8px;background:#fff3cd;border:1px solid #ffeeba;margin-bottom:10px}
  </style>
</head>
<body>
  @if($showWarning)
  <div class="warn">Package DomPDF belum terpasang. Ini adalah tampilan HTML yang bisa langsung dicetak dari browser.</div>
  @endif

  <h2>Laporan Supplier</h2>
  <div class="meta">
    @if(($filters['date_from'] ?? null) || ($filters['date_to'] ?? null))
      Periode: {{ $filters['date_from'] ?? '–' }} s/d {{ $filters['date_to'] ?? '–' }}<br>
    @endif
    Dicetak: {{ now()->format('d-m-Y H:i') }}
  </div>

  <table>
    <thead>
      <tr>
        <th>Supplier</th>
        <th class="right">Total Invoice</th>
        <th class="right">Total Pembelian</th>
        <th class="right">Total Pembayaran</th>
        <th class="right">Saldo Terbuka</th>
      </tr>
    </thead>
    <tbody>
    @php($sumInv=0)
    @php($sumBuy=0)
    @php($sumPay=0)
    @php($sumOut=0)

    @foreach($summary as $row)
      @php($sumInv += (int) $row->total_invoices)
      @php($sumBuy += (float) $row->total_purchase)
      @php($sumPay += (float) $row->total_payment)
      @php($sumOut += (float) $row->outstanding)
      <tr>
        <td>{{ $row->supplier_name }}</td>
        <td class="right">{{ number_format($row->total_invoices) }}</td>
        <td class="right">{{ number_format($row->total_purchase, 0, ',', '.') }}</td>
        <td class="right">{{ number_format($row->total_payment, 0, ',', '.') }}</td>
        <td class="right"><strong>{{ number_format($row->outstanding, 0, ',', '.') }}</strong></td>
      </tr>
    @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th>Total</th>
        <th class="right">{{ number_format($sumInv) }}</th>
        <th class="right">{{ number_format($sumBuy, 0, ',', '.') }}</th>
        <th class="right">{{ number_format($sumPay, 0, ',', '.') }}</th>
        <th class="right">{{ number_format($sumOut, 0, ',', '.') }}</th>
      </tr>
    </tfoot>
  </table>
</body>
</html>
