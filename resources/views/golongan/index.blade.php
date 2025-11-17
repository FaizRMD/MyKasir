@extends('layouts.app')

@section('content')
<style>
  :root{
    --maroon-700:#5a142b;
    --maroon-600:#6a1832;
    --maroon:#7b1e3b;      /* warna utama */
    --maroon-300:#b46984;
    --maroon-100:#f7ecf1;  /* lembut untuk background */
    --ink:#222;
  }

  /* Header gradient */
  .maroon-hero{
    background: linear-gradient(135deg, var(--maroon-700), var(--maroon));
    border-radius: 1.25rem;
    padding: 1.25rem 1.25rem;
    color:#fff;
    box-shadow: 0 10px 25px rgba(123,30,59,.25);
  }
  .maroon-hero h1{
    font-weight:700; letter-spacing:.2px; margin:0;
  }

  /* Card pembungkus tabel */
  .maroon-card{
    background:#fff;
    border:1px solid rgba(123,30,59,.12);
    border-radius:1rem;
    box-shadow: 0 6px 16px rgba(123,30,59,.08);
  }

  /* Tombol maroon */
  .btn-maroon{
    background:var(--maroon);
    border-color:var(--maroon);
    color:#fff;
  }
  .btn-maroon:hover{ background:var(--maroon-600); border-color:var(--maroon-600); color:#fff; }
  .btn-outline-maroon{
    background:transparent;
    border:1px solid var(--maroon);
    color:var(--maroon);
  }
  .btn-outline-maroon:hover{
    background:var(--maroon);
    color:#fff;
  }

  /* Input & toolbar */
  .toolbar{
    background:var(--maroon-100);
    border-bottom:1px solid rgba(123,30,59,.12);
    border-top-left-radius:1rem;
    border-top-right-radius:1rem;
    padding: .9rem 1rem;
  }
  .form-control:focus{
    border-color: var(--maroon);
    box-shadow: 0 0 0 .2rem rgba(123,30,59,.15);
  }

  /* Tabel */
  .table thead th{
    background:linear-gradient(180deg, #fff, #fdf8fb);
    border-bottom:1px solid rgba(123,30,59,.18) !important;
    color:var(--maroon-700);
    font-weight:700;
  }
  .table tbody tr{
    transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
  }
  .table tbody tr:hover{
    background:#fff7fa;
  }
  .table td, .table th { vertical-align: middle; }

  /* Badge status */
  .badge-maroon{
    background: var(--maroon-100);
    color: var(--maroon-700);
    border:1px solid rgba(123,30,59,.25);
    font-weight:600;
  }

  /* Pagination */
  .pagination .page-link{
    border-color: rgba(123,30,59,.25);
    color: var(--maroon-700);
  }
  .pagination .page-link:hover{
    background: var(--maroon-100);
    color: var(--maroon-700);
  }
  .pagination .page-item.active .page-link{
    background: var(--maroon);
    border-color: var(--maroon);
    color:#fff;
    box-shadow: 0 4px 10px rgba(123,30,59,.3);
  }

  /* Small helpers */
  .font-mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
  .soft{
    color:#666
  }
</style>

<div class="container py-4">

  {{-- Flash --}}
  @if(session('ok'))
    <div class="alert alert-success shadow-sm border-0">{{ session('ok') }}</div>
  @endif

  {{-- Hero Header --}}
  <div class="maroon-hero mb-3 d-flex justify-content-between align-items-center">
    <div>
      <h1 class="h4 mb-1">Golongan Obat</h1>
      <div class="soft small">
        @if(!empty($q))
          Menampilkan hasil untuk: <strong>“{{ $q }}”</strong>
        @else
          Kelola daftar golongan obat dengan cepat & elegan.
        @endif
      </div>
    </div>
    <a href="{{ route('golongan-obat.create') }}" class="btn btn-lg btn-light text-nowrap" style="color:var(--maroon-700)">
      + Tambah
    </a>
  </div>

  {{-- Card --}}
  <div class="maroon-card">
    {{-- Toolbar / Search --}}
    <div class="toolbar">
      <form method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-6">
          <div class="input-group">
            <span class="input-group-text border-0" style="background:#fff">
              {{-- icon search --}}
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M21 21l-4.2-4.2M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <input type="text" name="q" value="{{ $q }}"
                   class="form-control border-0"
                   placeholder="Cari kode / nama...">
          </div>
        </div>
        <div class="col-12 col-md-auto ms-md-auto">
          <button class="btn btn-outline-maroon me-2" type="submit">Cari</button>
          <a href="{{ route('golongan-obat.index') }}" class="btn btn-light">Reset</a>
        </div>
      </form>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
      <table class="table table-hover table-nowrap mb-0">
        <thead>
          <tr>
            <th style="width:160px">Kode</th>
            <th>Nama</th>
            <th>Keterangan</th>
            <th style="width:130px">Status</th>
            <th style="width:160px"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($golongan as $g)
            <tr>
              <td class="font-mono">{{ $g->code }}</td>
              <td class="fw-semibold">{{ $g->name }}</td>
              <td class="soft">
                {{ \Illuminate\Support\Str::limit($g->description, 90) }}
              </td>
              <td>
                @if($g->is_active)
                  <span class="badge badge-maroon rounded-pill px-3 py-2">Aktif</span>
                @else
                  <span class="badge text-bg-secondary rounded-pill px-3 py-2">Nonaktif</span>
                @endif
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-maroon me-1" href="{{ route('golongan-obat.edit', $g) }}">Edit</a>
                <form action="{{ route('golongan-obat.destroy', $g) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Hapus data ini?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-5">
                <div class="soft mb-2">Belum ada data yang cocok.</div>
                <a href="{{ route('golongan-obat.create') }}" class="btn btn-maroon">Tambah Data Pertama</a>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Footer card: total & pagination --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3">
      <div class="soft small mb-2 mb-md-0">
        Total: <strong>{{ $golongan->total() }}</strong> data
      </div>
      <nav>
        {{ $golongan->links() }}
      </nav>
    </div>
  </div>
</div>
@endsection
