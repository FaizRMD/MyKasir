@extends('layouts.app')
@section('title','Tambah Supplier')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Supplier</a></li>
  <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
<style>
:root{--maroon:#800000}
.card-rounded{border-radius:18px}
</style>

<div class="container-fluid px-2 px-lg-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 style="color:var(--maroon)">Tambah Supplier</h3>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">← Kembali</a>
  </div>

  <div class="card shadow-sm card-rounded mx-auto" style="max-width: 980px;">
    <div class="card-body">
      <form method="POST" action="{{ route('suppliers.store') }}">
        @csrf
        {{-- Partial form sudah berisi tombol Batal & Simpan --}}
        @include('supplier._form', ['supplier' => null])
      </form>
    </div>
  </div>
</div>
@endsection
