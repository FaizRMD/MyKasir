@extends('layouts.app')

@section('title','Produk')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Produk</a></li>
  <li class="breadcrumb-item active">Ubah</li>
@endsection

@section('content')
<div class="card" style="max-width:980px;">
  <div class="card-header bg-transparent">
    <h5 class="mb-0">Ubah Produk</h5>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ route('products.update', $product) }}">
      @csrf
      @method('PUT')

      @include('products._form', [
        'product'      => $product,
        // kirim $suppliers sebagai map id=>name dari controller
        // kirim $drugClasses bila ingin override default
      ])

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </form>
  </div>
</div>
@endsection
