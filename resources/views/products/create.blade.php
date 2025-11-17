@extends('layouts.app')

@section('title','Tambah Produk')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Produk</a></li>
  <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
  @if($errors->any())
    <div class="alert alert-danger">
      <strong>Periksa kembali:</strong>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="post" action="{{ route('products.store') }}" class="needs-validation" novalidate>
    @csrf

    {{--
      Partial ini sudah selaras dengan controller & model:
      - pack_name, pack_qty, sell_unit
      - buy_price_pack, ppn_percent, disc_percent, disc_amount
      - sell_price, supplier_id
      - serta field opsional umum (sku, name, unit, barcode, dll)
    --}}
    @include('products._form', [
      // opsional: kirim koleksi suppliers & daftar drugClasses bila belum dikirim otomatis
      'suppliers' => $suppliers ?? collect(),
      'drugClasses' => $drugClasses ?? ['OTC','Prescription','Narcotic','Herbal','Other'],
      // untuk create, $product tidak ada
    ])

    <div class="d-flex gap-2 mt-3">
      <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Batal</a>
      <button type="submit" class="btn btn-primary">Simpan Produk</button>
    </div>
  </form>
@endsection
