@extends('layouts.app')
@section('title','Edit Supplier')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Supplier</a></li>
  <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<style>
:root{--maroon:#800000;--maroon-dark:#5a0000;--maroon-100:#f4e6e6}
.card-rounded{border-radius:18px}
.btn-maroon{background:var(--maroon);color:#fff;border-radius:10px}
.btn-maroon:hover{background:var(--maroon-dark);color:#fff}
.btn-outline-maroon{background:#fff;color:var(--maroon);border:1px solid var(--maroon);border-radius:10px}
.btn-outline-maroon:hover{background:var(--maroon-100)}
</style>

<div class="container-fluid px-2 px-lg-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 style="color:var(--maroon)">Edit Supplier</h3>
    <div class="d-flex gap-2">
      <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">← Kembali</a>

      @if(Route::has('suppliers.toggle'))
      <form action="{{ route('suppliers.toggle', $supplier) }}" method="post">
        @csrf
        <button class="btn btn-outline-maroon">
          {{ $supplier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
        </button>
      </form>
      @endif
    </div>
  </div>

  <div class="card shadow-sm card-rounded mx-auto" style="max-width: 980px;">
    <div class="card-body">
      {{-- FORM UPDATE (UTAMA) --}}
      <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')

        {{-- Partial sudah berisi tombol Batal & Simpan --}}
        @include('supplier._form', ['supplier' => $supplier])
      </form>

      {{-- ROW AKSI TAMBAHAN (hapus) — DI LUAR FORM UTAMA, TIDAK BERSARANG --}}
      <div class="d-flex justify-content-end mt-3">
        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
              onsubmit="return confirm('Hapus supplier ini? Tindakan tidak dapat dibatalkan.')">
          @csrf
          @method('DELETE')
          <button class="btn btn-danger">Hapus</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
