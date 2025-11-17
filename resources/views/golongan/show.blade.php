@extends('layouts.app')

@section('title','Detail Golongan Obat')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item"><a href="{{ route('golongan-obat.index') }}">Golongan Obat</a></li>
  <li class="breadcrumb-item active">Detail</li>
@endsection

@section('content')
<div class="card p-3" style="max-width: 800px;">
  <div class="d-flex justify-content-between align-items-center">
    <h4 class="mb-0">{{ $golonganObat->name }}</h4>
    <div>
      <a href="{{ route('golongan-obat.edit',$golonganObat) }}" class="btn btn-secondary">Edit</a>
      <a href="{{ route('golongan-obat.index') }}" class="btn btn-light">Kembali</a>
    </div>
  </div>

  <table class="table table-bordered mt-3">
    <tr><th width="200">Kode</th><td>{{ $golonganObat->code }}</td></tr>
    <tr><th>Nama</th><td>{{ $golonganObat->name }}</td></tr>
    <tr><th>Deskripsi</th><td>{{ $golonganObat->description ?: '—' }}</td></tr>
    <tr><th>Status</th>
      <td>{!! $golonganObat->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' !!}</td>
    </tr>
  </table>

  @if(method_exists($golonganObat,'products') && $golonganObat->products()->exists())
    <h5 class="mt-4">Produk dengan Golongan ini</h5>
    <ul>
      @foreach($golonganObat->products()->limit(10)->get() as $p)
        <li>{{ $p->name }} ({{ $p->sku }})</li>
      @endforeach
    </ul>
  @endif
</div>
@endsection
