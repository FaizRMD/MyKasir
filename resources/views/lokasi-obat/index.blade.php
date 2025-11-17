@extends('layouts.app')

@section('title','Lokasi Obat')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item active">Lokasi Obat</li>
@endsection

@section('content')
@once
<style>
  :root{
    --maroon-700:#5a142b; --maroon-600:#6a1832; --maroon:#7b1e3b; --maroon-100:#f7ecf1;
  }
  .maroon-hero{
    background: linear-gradient(135deg, var(--maroon-700), var(--maroon));
    border-radius: 1.25rem; padding: 1.25rem 1.25rem; color:#fff;
    box-shadow: 0 10px 25px rgba(123,30,59,.25);
  }
  .maroon-card{
    background:#fff; border:1px solid rgba(123,30,59,.12); border-radius:1rem;
    box-shadow: 0 6px 16px rgba(123,30,59,.08);
  }
  .toolbar{ background:var(--maroon-100); border-bottom:1px solid rgba(123,30,59,.12);
    border-top-left-radius:1rem; border-top-right-radius:1rem; padding:.9rem 1rem; }
  .form-control:focus{ border-color:var(--maroon); box-shadow:0 0 0 .2rem rgba(123,30,59,.15); }
  .btn-maroon{ background:var(--maroon); border-color:var(--maroon); color:#fff; }
  .btn-maroon:hover{ background:var(--maroon-600); border-color:var(--maroon-600); color:#fff; }
  .btn-outline-maroon{ background:transparent; border:1px solid var(--maroon); color:var(--maroon); }
  .btn-outline-maroon:hover{ background:var(--maroon); color:#fff; }
  .table thead th{ background:linear-gradient(180deg,#fff,#fdf8fb); border-bottom:1px solid rgba(123,30,59,.18)!important; color:var(--maroon-700); font-weight:700; }
  .table tbody tr:hover{ background:#fff7fa; }
  .font-mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
  .soft{ color:#666 }
  .pagination .page-item.active .page-link{ background:var(--maroon); border-color:var(--maroon); color:#fff; box-shadow:0 4px 10px rgba(123,30,59,.3); }
  .pagination .page-link{ border-color:rgba(123,30,59,.25); color:var(--maroon-700); }
  .pagination .page-link:hover{ background:var(--maroon-100); color:var(--maroon-700); }
</style>
@endonce

<div class="container py-4">
  @if(session('ok')) <div class="alert alert-success shadow-sm border-0">{{ session('ok') }}</div> @endif
  @if($errors->any()) <div class="alert alert-danger">{{ implode(', ', $errors->all()) }}</div> @endif

  <div class="maroon-hero mb-3 d-flex justify-content-between align-items-center">
    <div>
      <h4 class="m-0">Daftar Lokasi Obat</h4>
      <div class="soft small">
        @if(!empty($q)) Menampilkan hasil untuk: <strong>“{{ $q }}”</strong>
        @else Kelola lokasi penyimpanan obat dengan cepat & elegan. @endif
      </div>
    </div>
    <a href="{{ route('lokasi-obat.create') }}" class="btn btn-light text-nowrap" style="color:#5a142b">+ Tambah Lokasi</a>
  </div>

  <div class="maroon-card">
    <div class="toolbar">
      <form method="get" class="row g-2 align-items-center" action="{{ route('lokasi-obat.index') }}">
        <div class="col-12 col-md-6">
          <div class="input-group">
            <span class="input-group-text border-0" style="background:#fff">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M21 21l-4.2-4.2M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <input type="text" class="form-control border-0" name="q" value="{{ $q }}"
                   placeholder="Cari kode / nama / keterangan">
          </div>
        </div>
        <div class="col-12 col-md-auto ms-md-auto">
          <button class="btn btn-outline-maroon me-2">Cari</button>
          <a href="{{ route('lokasi-obat.index') }}" class="btn btn-light">Reset</a>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-nowrap align-middle mb-0">
        <thead>
          <tr>
            <th style="width:110px">Urutan</th>
            <th style="width:160px">Kode</th>
            <th>Nama</th>
            <th>Keterangan</th>
            <th style="width:190px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
          <tr>
            <td>{{ $r->sort_order ?? '—' }}</td>
            <td class="font-mono">{{ $r->code }}</td>
            <td><a href="{{ route('lokasi-obat.show',$r) }}"><strong>{{ $r->name }}</strong></a></td>
            <td class="soft">{{ $r->description ?: '—' }}</td>
            <td class="text-end">
              <a href="{{ route('lokasi-obat.edit',$r) }}" class="btn btn-sm btn-outline-maroon">Edit</a>
              <form method="post" action="{{ route('lokasi-obat.destroy',$r) }}" class="d-inline"
                    onsubmit="return confirm('Hapus lokasi ini?')">
                @csrf @method('delete')
                <button class="btn btn-sm btn-danger">Hapus</button>
              </form>
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center py-4">Belum ada data lokasi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3">
      <div class="soft small mb-2 mb-md-0">Total: <strong>{{ $rows->total() }}</strong> data</div>
      {{ $rows->links() }}
    </div>
  </div>
</div>
@endsection
