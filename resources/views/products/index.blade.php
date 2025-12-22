@extends('layouts.app')

@section('title','Produk')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
  <li class="breadcrumb-item active">Produk</li>
@endsection

@push('styles')
<style>
  .page-title{display:flex;align-items:center;gap:.6rem;font-weight:800;letter-spacing:.2px}
  .toolbar .btn{border-radius:12px}
  .filter-grid{display:grid;grid-template-columns:1.3fr 1fr 1fr .8fr;gap:.6rem}
  @media (max-width: 992px){ .filter-grid{grid-template-columns:1fr} }

  .table-wrap{border:1px solid #e7ebf5;border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.06)}
  .table-modern{margin:0;border-collapse:separate;border-spacing:0;width:100%}
  .table-modern thead th{
    position:sticky; top:0; z-index:1; background:#f3f6ff;
    font-weight:700; color:#0f172a; padding:12px 14px; border-bottom:1px solid #e7ebf5; white-space:nowrap
  }
  .table-modern tbody td{padding:12px 14px; vertical-align:middle; border-bottom:1px solid #f0f2f8}
  .table-modern tbody tr:hover td{background:rgba(13,110,253,.04)}
  .num{text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap}
  .center{text-align:center; white-space:nowrap}

  .badge-soft{border-radius:10px;padding:.25rem .5rem;font-weight:700;letter-spacing:.2px}
  .badge-soft.ok{background:rgba(24,195,126,.14);color:#0e7a4f}
  .badge-soft.muted{background:rgba(148,163,184,.18);color:#334155}
  .badge-soft.warn{background:rgba(245,193,69,.18);color:#8a5b00}
  .badge-soft.danger{background:rgba(239,68,68,.15);color:#9b1c1c}

  .actions{display:flex;gap:.45rem;justify-content:center}
  .icon-btn{height:34px;border-radius:10px}
  .icon-btn i{width:18px;height:18px}

  .empty{padding:40px 12px;color:#64748b}
  .chip{background:#eef2ff;border:1px solid #dbe4ff;border-radius:999px;padding:.2rem .6rem;font-weight:600;font-size:.85rem}
</style>
@endpush

@section('content')
<div class="card shadow-sm border-0">
  <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center toolbar">
    <h4 class="mb-0 page-title">
      <i data-feather="package"></i> Daftar Produk
    </h4>

    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i data-feather="plus"></i> Tambah Produk
      </a>
      <a href="{{ route('products.export.xlsx', request()->query()) }}" class="btn btn-outline-success">
        <i data-feather="file"></i> Excel
      </a>
      <a href="{{ route('products.export.pdf', request()->query()) }}" class="btn btn-outline-danger">
        <i data-feather="file-text"></i> PDF
      </a>
    </div>
  </div>

  <div class="card-body">
    {{-- FILTER RINGKAS --}}
    <form method="get" action="{{ route('products.index') }}" class="filter-grid mb-3">
      <div>
        <div class="input-group">
          <span class="input-group-text"><i data-feather="search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Cari nama / SKU / barcode" value="{{ $q ?? '' }}">
        </div>
      </div>

      <div>
        <select name="supplier_id" class="form-select">
          <option value="">— Semua Supplier —</option>
          @foreach(($suppliers ?? []) as $id => $name)
            <option value="{{ $id }}" @selected(($supplierId ?? '') == $id)>{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <select name="status" class="form-select">
          <option value="">— Semua Status —</option>
          <option value="active"   @selected(($status ?? '')==='active')>Aktif</option>
          <option value="inactive" @selected(($status ?? '')==='inactive')>Nonaktif</option>
          <option value="lowstock" @selected(($status ?? '')==='lowstock')>Stok Minimum</option>
          <option value="nostock"  @selected(($status ?? '')==='nostock')>Stok Habis</option>
        </select>
      </div>

      <div>
        <button class="btn btn-outline-secondary w-100">
          <i data-feather="filter"></i> Terapkan
        </button>
      </div>
    </form>

    {{-- flash --}}
    @if(session('ok'))
      <div class="alert alert-success d-flex align-items-center">
        <i data-feather="check-circle" class="me-2"></i><div>{{ session('ok') }}</div>
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger d-flex align-items-center">
        <i data-feather="alert-triangle" class="me-2"></i><div>{{ implode(', ', $errors->all()) }}</div>
      </div>
    @endif

    {{-- info jumlah --}}
    <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
      <div>
        Menampilkan <strong>{{ $products->firstItem() ?? 0 }}</strong>–<strong>{{ $products->lastItem() ?? 0 }}</strong>
        dari <strong>{{ $products->total() }}</strong> produk
      </div>
      <div>Halaman {{ $products->currentPage() }} / {{ $products->lastPage() }}</div>
    </div>

    {{-- TABEL --}}
    <div class="table-wrap">
      <div class="table-responsive">
        <table class="table-modern align-middle">
          <thead>
            <tr>
              <th>SKU</th>
              <th>Nama</th>
              <th>Supplier</th>
              <th>Konversi</th>
              <th class="num">HNA (Beli)</th>
              <th class="num">PPN %</th>
              <th class="num">Diskon %</th>
              <th class="num">HPP/ Sat. Jual</th>
              <th class="num">Harga Jual</th>
              <th class="num">Margin %</th>
              <th class="center">Stok</th>
              <th class="center">Status</th>
              <th class="center" style="width:150px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
          @forelse($products as $p)
            @php
              // ambil PPN & Diskon dari kolom baru; jika null pakai kolom lama
              $ppn  = (float) ($p->ppn_percent  ?? $p->tax_percent        ?? 0);
              $disc = (float) ($p->disc_percent ?? $p->discount_percent    ?? 0);

              // buy_price pada model adalah HPP/unit (hasil kalkulasi)
              $hppUnit = (float) ($p->buy_price ?? 0);
              $sell    = (float) ($p->sell_price ?? 0);

              $marginPct = $sell > 0 ? (($sell - $hppUnit) / $sell) * 100 : 0;

              $stockState = $p->stock <= 0 ? 'empty' : ($p->min_stock>0 && $p->stock <= $p->min_stock ? 'low' : 'ok');

              // tampilan konversi dari kemasan
              $packName = $p->pack_name ?? 'Box';
              $packQty  = (int) ($p->pack_qty ?? 1);
              $unitSell = $p->sell_unit ?? ($p->unit ?? 'Unit');
            @endphp
            <tr>
              <td class="text-nowrap">{{ $p->sku ?: '—' }}</td>

              <td class="text-truncate" style="max-width:260px">
                <a href="{{ route('products.show',$p) }}" class="fw-semibold text-decoration-none">{{ $p->name }}</a>
                @if($p->barcode)
                  <div class="small text-muted">Barcode: {{ $p->barcode }}</div>
                @endif
              </td>

              <td class="text-nowrap">
                @if($p->supplier_id && $p->supplier)
                  <a href="{{ route('suppliers.show', $p->supplier_id) }}" class="text-decoration-none">
                    <i data-feather="truck" class="me-1"></i>{{ $p->supplier->name }}
                  </a>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>

              <td class="text-nowrap">
                <span class="chip">{{ "1{$packName} → {$packQty} {$unitSell}" }}</span>
              </td>

              {{-- HNA (beli) kita tampilkan HPP/unit agar konsisten dengan vmedis (boleh ganti sesuai kebutuhan) --}}
              <td class="num">{{ number_format($hppUnit,0,',','.') }}</td>

              <td class="num">{{ number_format($ppn,2,',','.') }}</td>
              <td class="num">{{ number_format($disc,2,',','.') }}</td>

              <td class="num">{{ number_format($hppUnit,0,',','.') }}</td>
              <td class="num">{{ number_format($sell,0,',','.') }}</td>
              <td class="num">{{ number_format($marginPct,2,',','.') }}</td>

              <td class="center">
                {{ (int)($p->stock ?? 0) }}
                @if($stockState==='low')
                  <span class="badge-soft warn ms-1">Min</span>
                @elseif($stockState==='empty')
                  <span class="badge-soft danger ms-1">Habis</span>
                @endif
              </td>

              <td class="center">
                @if($p->is_active)
                  <span class="badge-soft ok">Aktif</span>
                @else
                  <span class="badge-soft muted">Nonaktif</span>
                @endif
              </td>

              <td>
                <div class="actions">
                  <a href="{{ route('products.edit',$p) }}" class="btn btn-sm btn-outline-primary icon-btn">
                    <i data-feather="edit-3"></i>
                  </a>
                  <form method="post" action="{{ route('products.destroy',$p) }}" class="d-inline" onsubmit="return confirm('Hapus produk ini? Jika produk sudah dipakai di transaksi/penerimaan, maka akan dinonaktifkan (tidak dihapus).')">
                    @csrf @method('delete')
                    <button class="btn btn-sm btn-outline-danger icon-btn" type="submit">
                      <i data-feather="trash-2"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="13" class="empty text-center">
                <div class="d-inline-flex align-items-center gap-2">
                  <i data-feather="inbox"></i>
                  <span>Tidak ada data.</span>
                </div>
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $products->links() }}
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function renderIcons(){ feather.replace({ width:18, height:18, 'stroke-width': 2.2 }); }
  renderIcons();
  const mo = new MutationObserver(renderIcons);
  mo.observe(document.body, { childList:true, subtree:true });
</script>
@endpush
