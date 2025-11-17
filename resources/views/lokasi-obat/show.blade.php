@extends('layouts.app')

@section('title', 'Detail Lokasi: '.$lokasiObat->name)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item"><a href="{{ route('lokasi-obat.index') }}">Lokasi Obat</a></li>
  <li class="breadcrumb-item active">Detail</li>
@endsection

@section('content')
@once
<style>
  :root{ --maroon-700:#5a142b; --maroon:#7b1e3b; }
  .kv{ border:1px solid rgba(123,30,59,.12); border-radius:1rem; padding:1rem; }
  .kv .k{ color:var(--maroon-700); width:160px; font-weight:600; }
</style>
@endonce

<div class="container py-4">
  @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0">Detail Lokasi</h4>
    <div>
      <a href="{{ route('lokasi-obat.edit',$lokasiObat) }}" class="btn btn-outline-secondary">Edit</a>
      <a href="{{ route('lokasi-obat.index') }}" class="btn btn-light">Kembali</a>
    </div>
  </div>

  <div class="kv mb-4">
    <div class="d-flex py-1"><div class="k">Kode</div><div class="v">{{ $lokasiObat->code }}</div></div>
    <div class="d-flex py-1"><div class="k">Nama</div><div class="v">{{ $lokasiObat->name }}</div></div>
    <div class="d-flex py-1"><div class="k">Urutan</div><div class="v">{{ $lokasiObat->sort_order ?? '—' }}</div></div>
    <div class="d-flex py-1"><div class="k">Keterangan</div><div class="v">{{ $lokasiObat->description ?? '—' }}</div></div>
    <div class="d-flex py-1"><div class="k">Dibuat</div><div class="v">{{ $lokasiObat->created_at?->format('d M Y H:i') }}</div></div>
    <div class="d-flex py-1"><div class="k">Diubah</div><div class="v">{{ $lokasiObat->updated_at?->format('d M Y H:i') }}</div></div>
  </div>
</div>
@endsection
