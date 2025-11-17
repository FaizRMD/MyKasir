@extends('layouts.app')

@section('title','Apoteker')

@push('styles')
<style>
:root{
  --maroon-50:#fff5f7; --maroon-100:#fde4e8; --maroon-500:#7a1020;
  --maroon-600:#5a0c18; --ink:#111827; --muted:#6b7280; --border:#e5e7eb; --soft:#f9fafb;
}
body{background:var(--soft);color:var(--ink);}
.page{max-width:1200px;margin:0 auto;padding:24px;}
.h-title{font-weight:800;font-size:28px;color:var(--maroon-600);margin:0;}
.sub{color:var(--muted)}

.card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,.06);overflow:hidden;}
.card .head{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;background:linear-gradient(90deg,var(--maroon-50),#fff);border-bottom:1px solid var(--border)}
.card .body{padding:16px;}

.btn{padding:10px 14px;border-radius:10px;border:1px solid var(--border);font-weight:700;cursor:pointer;transition:.15s}
.btn-maroon{background:var(--maroon-600);border-color:var(--maroon-600);color:#fff}
.btn-maroon:hover{background:var(--maroon-500)}
.btn-soft{background:#fff;border-color:var(--border)}

.input{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#fff;font-size:14px}
.input:focus{outline:none;border-color:var(--maroon-500);box-shadow:0 0 0 4px rgba(122,16,32,.15)}

.table-wrap{overflow:auto;border:1px solid var(--border);border-radius:12px;background:#fff}
.table{width:100%;border-collapse:collapse}
.table th,.table td{border:1px solid var(--border);padding:10px;vertical-align:middle;font-size:14px}
.table thead th{background:var(--maroon-50);color:var(--maroon-600);font-weight:800}
.badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 10px;font-weight:700;font-size:12px}
.badge-ok{background:#dcfce7;color:#065f46;border:1px solid #bbf7d0}
.badge-off{background:#eef2f7;color:#334155;border:1px solid #e5e7eb}

.tools{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.pagination{margin:14px 0 0}
@media(max-width:900px){.tools{flex-direction:column;align-items:stretch}}
</style>
@endpush

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item active">Apoteker</li>
@endsection

@section('content')
<div class="page">

  <div class="card">
    <div class="head">
      <div>
        <div class="h-title">Daftar Apoteker</div>
        <div class="sub">Kelola data apoteker. Gunakan pencarian untuk memfilter cepat.</div>
      </div>
      <div class="tools">
        <a href="{{ route('apoteker.create') }}" class="btn btn-maroon">+ Tambah Apoteker</a>
      </div>
    </div>

    {{-- Pencarian --}}
    <div class="body">
      <form method="get" class="tools" action="{{ route('apoteker.index') }}">
        <div style="flex:1;min-width:240px">
          <input type="text" class="input" name="q" value="{{ $q ?? '' }}"
                 placeholder="Cari nama / SIPA / STRA / telepon / email / alamat">
        </div>
        <button class="btn btn-soft" type="submit">Cari</button>
        @if(!empty($q))
          <a class="btn btn-soft" href="{{ route('apoteker.index') }}">Reset</a>
        @endif
      </form>

      {{-- Flash message --}}
      @if(session('ok'))
        <div class="alert alert-success mt-3" role="alert">{{ session('ok') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger mt-3" role="alert">{{ implode(', ', $errors->all()) }}</div>
      @endif
    </div>

    {{-- Tabel --}}
    <div class="body">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th style="width:46px">#</th>
              <th>Nama</th>
              <th>SIPA</th>
              <th>STRA</th>
              <th>Telepon</th>
              <th>Email</th>
              <th>Alamat</th>
              <th style="width:100px">Status</th>
              <th style="width:190px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $idx => $a)
              <tr>
                <td>{{ ($rows->currentPage()-1)*$rows->perPage() + $idx + 1 }}</td>
                <td>
                  <a href="{{ route('apoteker.show', $a) }}" style="font-weight:700;color:#111;text-decoration:none">
                    {{ $a->name ?? '—' }}
                  </a>
                </td>
                <td>{{ $a->no_sipa ?? '—' }}</td>
                <td>{{ $a->no_stra ?? '—' }}</td>
                <td>{{ $a->phone ?? '—' }}</td>
                <td>{{ $a->email ?? '—' }}</td>
                <td style="max-width:260px">{{ $a->address ?? '—' }}</td>
                <td>
                  @if(!empty($a->is_active))
                    <span class="badge badge-ok">Aktif</span>
                  @else
                    <span class="badge badge-off">Nonaktif</span>
                  @endif
                </td>
                <td>
                  <a href="{{ route('apoteker.edit', $a) }}" class="btn btn-soft">Edit</a>
                  <form method="post" action="{{ route('apoteker.destroy', $a) }}" class="d-inline"
                        onsubmit="return confirm('Hapus apoteker ini?')">
                    @csrf @method('delete')
                    <button class="btn btn-soft" style="border-color:#fecaca;color:#b91c1c">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center" style="color:#6b7280;padding:14px">
                  Belum ada data apoteker.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="pagination">
        {{ $rows->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
