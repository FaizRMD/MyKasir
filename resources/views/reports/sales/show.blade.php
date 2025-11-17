@extends('layouts.app')
@section('title', 'Detail Penjualan')

@push('styles')
<style>
  :root{
    --maroon-700:#8d1b1b;
    --maroon-800:#7a1616;
    --line:#e5e7eb;
    --muted:#6b7280;
    --soft:#f9fafb;
  }

  .page{max-width:1024px;margin:0 auto;padding:20px}
  .card{background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden;margin-bottom:16px}
  .card-header{padding:16px;border-bottom:1px solid var(--line);font-weight:600;background:var(--soft)}
  .card-body{padding:16px}

  .table{width:100%;border-collapse:separate;border-spacing:0}
  .table th,.table td{padding:10px;border-bottom:1px solid #f3f4f6;vertical-align:top}
  .table thead th{background:#f9fafb;font-weight:600}
  .pill{background:#fff;border:1px solid var(--line);border-radius:999px;padding:6px 10px}
  .muted{color:var(--muted)}

  .btn{display:inline-block;padding:8px 12px;border:1px solid var(--line);border-radius:10px;text-decoration:none;font-weight:600}
  .btn:hover{filter:brightness(.98)}
  .btn-brand{background:var(--maroon-700);border-color:var(--maroon-700);color:#fff}
  .btn-brand:hover{background:var(--maroon-800);border-color:var(--maroon-800)}
  .btn-ghost{background:#fff;color:var(--maroon-700)}
  .btn-ghost:hover{background:#fdeeee}
</style>
@endpush

@section('content')
<div class="page">
  {{-- Tombol kembali --}}
  <a href="{{ url()->previous() }}" class="btn btn-ghost">← Kembali ke Laporan</a>

  {{-- Ringkasan --}}
  <div class="card">
    <div class="card-header">Ringkasan Transaksi</div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div>
        <div class="muted">Invoice</div>
        <h2 style="margin:4px 0;color:var(--maroon-700)">{{ $sale->invoice_no }}</h2>
        <div class="muted">{{ optional($sale->created_at)->format('Y-m-d H:i') }}</div>
      </div>
      <div>
        <div>Customer: <strong>{{ $sale->customer->name ?? '-' }}</strong></div>
        <div>Kasir: <strong>{{ $sale->user->name ?? '-' }}</strong></div>
        <div>Metode Bayar: <strong>{{ $sale->payment_method ?? '-' }}</strong></div>
      </div>
    </div>
  </div>

  {{-- Item --}}
  <div class="card">
    <div class="card-header">Obat / Item Terjual</div>
    <div class="card-body" style="overflow:auto">
      <table class="table">
        <thead>
          <tr>
            <th>Obat / Produk</th>
            <th>Batch</th>
            <th>Qty</th>
            <th>Harga</th>
            <th>Pajak (%)</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse($sale->items as $it)
            <tr>
              <td>{{ $it->product->name ?? $it->name }}</td>
              <td>{{ $it->batch_no }}</td>
              <td>{{ (int) $it->qty }}</td>
              <td>Rp {{ number_format($it->price ?? 0,0,',','.') }}</td>
              <td>{{ number_format($it->tax_percent ?? 0,0,',','.') }}</td>
              <td><strong>Rp {{ number_format($it->total ?? 0,0,',','.') }}</strong></td>
            </tr>
          @empty
            <tr><td colspan="6" class="muted" style="text-align:center">Tidak ada data item.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Total --}}
  <div class="card">
    <div class="card-header">Total Transaksi</div>
    <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap">
      <span class="pill">Subtotal Item: <strong>Rp {{ number_format($sale->items->sum('total'),0,',','.') }}</strong></span>
      <span class="pill">Diskon (Trx): <strong>Rp {{ number_format($sale->discount ?? 0,0,',','.') }}</strong></span>
      <span class="pill">Pajak (Trx): <strong>Rp {{ number_format($sale->tax ?? 0,0,',','.') }}</strong></span>
      <span class="pill" style="background:#fdeeee;border-color:#f3c7c7;color:var(--maroon-700)">
        Grand Total: <strong>Rp {{ number_format($sale->grand_total ?? 0,0,',','.') }}</strong>
      </span>
    </div>
  </div>
</div>
@endsection
