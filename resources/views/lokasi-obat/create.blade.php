@extends('layouts.app')

@section('title','Tambah Lokasi Obat')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item"><a href="{{ route('lokasi-obat.index') }}">Lokasi Obat</a></li>
  <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
<div class="container py-4">
  <h4 class="mb-3">Tambah Lokasi Obat</h4>
  <form method="POST" action="{{ route('lokasi-obat.store') }}">
    @csrf
    @include('lokasi-obat._form', ['submitText' => 'Simpan'])
  </form>
</div>
@endsection
