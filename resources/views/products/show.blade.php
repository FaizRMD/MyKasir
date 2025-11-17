@extends('layouts.app')

@section('title','Produk')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item active">Produk</li>
@endsection

@section('content')
<div class="card" style="max-width:1000px;">
  <div style="display:flex;justify-content:space-between;align-items:center;">
    <h2>Detail Produk</h2>
    <div>
      <a href="{{ route('products.edit',$product) }}" class="btn btn-secondary">Edit</a>
      <a href="{{ route('products.index') }}" class="btn btn-light">Kembali</a>
    </div>
  </div>

  <table class="table table-bordered" style="margin-top:12px;">
    <tr><th width="220">Nama</th><td>{{ $product->name }}</td></tr>
    <tr><th>SKU</th><td>{{ $product->sku ?: '—' }}</td></tr>
    <tr><th>Barcode</th><td>{{ $product->barcode ?: '—' }}</td></tr>
    <tr><th>Kategori</th><td>{{ $product->category ?: '—' }}</td></tr>
    <tr><th>Satuan</th><td>{{ $product->unit ?: '—' }}</td></tr>
    <tr><th>Golongan</th><td>{{ $product->drug_class ?? '—' }}</td></tr>
    <tr><th>Harga Beli</th><td>{{ number_format($product->buy_price,0,',','.') }}</td></tr>
    <tr><th>Harga Jual</th><td>{{ number_format($product->sell_price,0,',','.') }}</td></tr>
    <tr><th>Pajak (%)</th><td>{{ rtrim(rtrim(number_format($product->tax_percent,2,',','.'),'0'),',') }}</td></tr>
    <tr><th>Stok</th><td>{{ $product->stock }} (min: {{ $product->min_stock }})</td></tr>
    <tr><th>Status</th><td>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</td></tr>
    <tr><th>Obat?</th><td>{{ $product->is_medicine ? 'Ya' : 'Bukan' }} @if($product->is_compounded) — Racikan @endif</td></tr>
  </table>

  @if(method_exists($product,'batches') && $product->batches()->exists())
    <h3 style="margin-top:24px;">Batch & Kedaluwarsa</h3>
    <table class="table table-sm table-striped">
      <thead><tr><th>Batch</th><th>Kedaluwarsa</th><th>Qty</th></tr></thead>
      <tbody>
      @foreach($product->batches()->orderBy('expiry_date')->get() as $b)
        <tr>
          <td>{{ $b->batch_no ?: '—' }}</td>
          <td>{{ optional($b->expiry_date)->format('d/m/Y') ?: '—' }}</td>
          <td>{{ $b->qty }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
