@extends('layouts.app')
@section('title', 'Laporan Penjualan - Item (Apotek)')

@push('styles')
<style>
  :root{
    --maroon-700:#8d1b1b;
    --maroon-800:#6d1313;
    --ink:#111827;
    --muted:#6b7280;
    --line:#e5e7eb;
    --soft:#f9fafb;
    --radius:12px;
  }

  body{background:var(--soft);color:var(--ink);}
  .page{max-width:1280px;margin:0 auto;padding:20px}
  .card{background:#fff;border:1px solid var(--line);border-radius:var(--radius)}
  .table{width:100%;border-collapse:separate;border-spacing:0}
  .table th,.table td{padding:10px;border-bottom:1px solid #f3f4f6;vertical-align:top}
  .table thead th{background:#f9fafb;font-weight:600;color:var(--ink)}
  .grid{display:grid;gap:10px}
  .grid-6{grid-template-columns:repeat(6,1fr)}
  @media(max-width:1024px){.grid-6{grid-template-columns:repeat(2,1fr)}}

  /* INPUT FIELD FIX */
  input, select {
    background:#fff;
    border:1px solid var(--line);
    border-radius:8px;
    padding:8px 10px;
    color:var(--ink);
    font-size:14px;
    width:100%;
    outline:none;
    appearance:auto;
  }
  input::placeholder {
    color:var(--muted);
  }
  input:focus, select:focus {
    border-color:var(--maroon-700);
    box-shadow:0 0 0 1px var(--maroon-700);
  }

  .summary{display:flex;gap:12px;flex-wrap:wrap}
  .pill{background:#fff;border:1px solid var(--line);border-radius:999px;padding:6px 10px}
  .muted{color:var(--muted)}
  .btn{display:inline-block;padding:8px 12px;border:1px solid var(--line);border-radius:10px;text-decoration:none;font-weight:600}
  .btn:hover{filter:brightness(.97)}
  .btn-ghost{background:#fff;color:var(--ink)}
  .btn-brand{background:var(--maroon-700);border-color:var(--maroon-700);color:#fff}
  .btn-brand:hover{background:var(--maroon-800);border-color:var(--maroon-800)}

  /* header tabs */
  .tabs{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
  .tablist{display:flex;gap:8px;flex-wrap:wrap}
  .tab{padding:8px 12px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--ink);text-decoration:none;font-weight:600}
  .tab.active{background:#fdeeee;border-color:#f3c7c7;color:var(--maroon-700)}
</style>
@endpush

@section('content')
@php
  $isItems   = request()->routeIs('reports.sales.items');
  $exportUrl = $isItems
    ? route('reports.sales.items_export', request()->query())
    : route('reports.sales.export',       request()->query());
@endphp

<div class="tabs">
  <div class="tablist">
    <a class="tab {{ $isItems ? '' : 'active' }}"
       href="{{ route('reports.sales.index', request()->query()) }}">
      Laporan Penjualan (Transaksi)
    </a>
    <a class="tab {{ $isItems ? 'active' : '' }}"
       href="{{ route('reports.sales.items', request()->query()) }}">
      Laporan Penjualan (Item/Obat)
    </a>
  </div>

  <div class="export-actions">
    <a class="btn btn-brand" href="{{ $exportUrl }}">Ekspor CSV</a>
    <a class="btn btn-brand" href="{{ $isItems
      ? route('reports.sales.items_export.pdf', request()->query())
      : route('reports.sales.export.pdf',       request()->query()) }}">
      Ekspor PDF
    </a>
  </div>
</div>


  {{-- FILTER --}}
  <form method="GET" class="grid grid-6" style="margin-bottom:12px">
    <input type="date"   name="date_from"      value="{{ $filters['date_from'] ?? '' }}">
    <input type="date"   name="date_to"        value="{{ $filters['date_to'] ?? '' }}">
    <input type="number" name="user_id"        placeholder="ID Kasir"        value="{{ $filters['user_id'] ?? '' }}">
    <input type="number" name="customer_id"    placeholder="ID Customer"     value="{{ $filters['customer_id'] ?? '' }}">
    <input type="text"   name="payment_method" placeholder="Metode Bayar"    value="{{ $filters['payment_method'] ?? '' }}">
    <input type="number" name="product_id"     placeholder="ID Produk/Obat"  value="{{ $filters['product_id'] ?? '' }}">
    <input type="text"   name="q"              placeholder="Cari nama obat..." value="{{ $filters['q'] ?? '' }}" style="grid-column:1/-1">

    <div style="grid-column:1/-1;display:flex;gap:8px;align-items:center">
      <select name="sort">
        <option value="date_desc"  @selected(($filters['sort'] ?? '')==='date_desc')>Tanggal ↓</option>
        <option value="date_asc"   @selected(($filters['sort'] ?? '')==='date_asc')>Tanggal ↑</option>
        <option value="total_desc" @selected(($filters['sort'] ?? '')==='total_desc')>Total ↓</option>
        <option value="total_asc"  @selected(($filters['sort'] ?? '')==='total_asc')>Total ↑</option>
        <option value="qty_desc"   @selected(($filters['sort'] ?? '')==='qty_desc')>Qty ↓</option>
        <option value="qty_asc"    @selected(($filters['sort'] ?? '')==='qty_asc')>Qty ↑</option>
      </select>

      <button class="btn btn-brand" type="submit">Terapkan</button>
      <a class="btn btn-ghost" href="{{ route('reports.sales.items') }}">Reset</a>

      @php
        $today  = \Carbon\Carbon::today()->format('Y-m-d');
        $mStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $mEnd   = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
        $wStart = \Carbon\Carbon::now()->startOfWeek()->format('Y-m-d');
        $wEnd   = \Carbon\Carbon::now()->endOfWeek()->format('Y-m-d');
      @endphp
      <span class="muted ms-auto">Rentang cepat:</span>
      <a class="pill" href="{{ route('reports.sales.items', array_merge(request()->except(['date_from','date_to']), ['date_from'=>$today ,'date_to'=>$today ])) }}">Hari ini</a>
      <a class="pill" href="{{ route('reports.sales.items', array_merge(request()->except(['date_from','date_to']), ['date_from'=>$wStart,'date_to'=>$wEnd])) }}">Minggu ini</a>
      <a class="pill" href="{{ route('reports.sales.items', array_merge(request()->except(['date_from','date_to']), ['date_from'=>$mStart,'date_to'=>$mEnd])) }}">Bulan ini</a>
    </div>
  </form>

  {{-- RINGKASAN --}}
  <div style="margin-bottom:12px">
    <span class="pill">Total Qty: <strong>{{ number_format($summary->qty_sum,0,',','.') }}</strong></span>
    <span class="pill">Total Rupiah: <strong>Rp {{ number_format($summary->total_sum,0,',','.') }}</strong></span>
  </div>

  {{-- TABEL --}}
  <div class="card" style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Invoice</th>
          <th>Customer</th>
          <th>Kasir</th>
          <th>Metode</th>
          <th>Obat / Produk</th>
          <th>Batch</th>
          <th>Qty</th>
          <th>Harga</th>
          <th>Pajak (%)</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $it)
          <tr>
            <td>{{ optional($it->item_date)->format('Y-m-d H:i') }}</td>
            <td><code>{{ $it->invoice_no }}</code></td>
            <td>{{ $it->customer_name ?? '-' }}</td>
            <td>{{ $it->cashier_name ?? '-' }}</td>
            <td>{{ $it->payment_method ?? '-' }}</td>
            <td>{{ $it->product_name }}</td>
            <td>{{ $it->batch_no }}</td>
            <td>{{ (int) $it->qty }}</td>
            <td>Rp {{ number_format($it->price ?? 0,0,',','.') }}</td>
            <td>{{ number_format($it->tax_percent ?? 0,0,',','.') }}</td>
            <td><strong>Rp {{ number_format($it->total ?? 0,0,',','.') }}</strong></td>
          </tr>
        @empty
          <tr><td colspan="11" class="muted" style="text-align:center">Tidak ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="margin-top:12px">
    {{ $items->links() }}
  </div>
</div>
@endsection
