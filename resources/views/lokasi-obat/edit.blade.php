@extends('layouts.app')

@section('title','Edit Lokasi Obat')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item"><a href="{{ route('lokasi-obat.index') }}">Lokasi Obat</a></li>
  <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="container py-4">
  <h4 class="mb-3">Edit Lokasi Obat</h4>
  <form method="POST" action="{{ route('lokasi-obat.update', $lokasiObat) }}">
    @csrf
    @method('PUT')
    @include('lokasi-obat._form', ['submitText' => 'Update'])
  </form>
</div>
@endsection
