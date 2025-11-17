@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')
<style>
    :root{
        --maroon-900:#4a0d0d;
        --maroon-800:#5e1010;
        --maroon-700:#731414;
        --maroon-600:#8a1818;
        --maroon-500:#a31d1d;
        --maroon-100:#fdeaea;
        --gray-50:#fafafa;
        --gray-100:#f4f4f5;
        --gray-200:#e5e7eb;
        --gray-300:#d1d5db;
        --gray-600:#52525b;
        --success:#16a34a;
        --warning:#d97706;
        --danger:#dc2626;
    }
    .page-wrap{ background:linear-gradient(180deg, var(--maroon-100), #fff 40%); min-height:100%; }
    .headbar{
        background:var(--maroon-700); color:#fff; border-radius:14px; padding:16px 20px;
        display:flex; align-items:center; gap:14px; box-shadow:0 6px 16px rgba(115,20,20,.25);
    }
    .headbar .badge{ background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); }
    .filter-card{
        border:1px solid var(--gray-200); border-radius:14px; background:#fff; padding:14px 16px;
    }
    .btn-maroon{ background:var(--maroon-600); color:#fff; border:1px solid var(--maroon-700); }
    .btn-maroon:hover{ background:var(--maroon-700); color:#fff; }
    .btn-ghost{ background:#fff; border:1px solid var(--gray-200); color:#111827; }
    .table thead th{ background:var(--gray-100) !important; border-bottom:1px solid var(--gray-200) !important; font-weight:700; }
    .table tbody td{ vertical-align:middle; border-color:var(--gray-200) !important; }
    .status-badge{
        border-radius:999px; font-weight:600; padding:3px 10px; font-size:.8rem; white-space:nowrap;
        border:1px solid transparent;
    }
    .status-complete{ background:#dcfce7; border-color:#bbf7d0; color:#065f46; }
    .status-partial{ background:#fff3cd; border-color:#ffe69c; color:#7a5c00; }
    .status-draft{ background:#e5e7eb; border-color:#d1d5db; color:#374151; }
    .mini{ font-size:.85rem; color:var(--gray-600); }
    .empty{
        border:2px dashed var(--gray-300); border-radius:16px; padding:36px; text-align:center; color:var(--gray-600);
        background:#fff;
    }
    .card-shadow{ border:1px solid var(--gray-200); border-radius:14px; overflow:hidden; background:#fff; }
    .sticky-actions{ position:sticky; right:0; background:#fff; }
</style>

<div class="container page-wrap py-3">

    {{-- Header --}}
    <div class="headbar mb-3">
        <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:42px;height:42px;background:rgba(255,255,255,.15);">
            <i data-feather="inbox"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2">
                <h5 class="m-0 fw-bold">Penerimaan Barang</h5>
                <span class="badge">GRN</span>
            </div>
            <div class="mini">Rekap penerimaan dari supplier, lengkap dengan filter tanggal, supplier, dan kata kunci.</div>
        </div>
        <div>
            <a href="{{ route('purchases.index') }}" class="btn btn-ghost btn-sm">Buat dari PO</a>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('grn.index') }}" class="filter-card mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label mini mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label mini mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label mini mb-1">Supplier</label>
                <input type="text" name="supplier" value="{{ request('supplier') }}" class="form-control" placeholder="Nama supplier">
            </div>
            <div class="col-md-3">
                <label class="form-label mini mb-1">Kata Kunci</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari GRN No / PO / Catatan / Produk">
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-2">
            <a href="{{ route('grn.index') }}" class="btn btn-ghost">Reset</a>
            <button type="submit" class="btn btn-maroon">Terapkan</button>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="card-shadow">
        <div class="p-3" style="background:var(--maroon-900); color:#fff;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="fw-semibold">Daftar Penerimaan</div>
                <div class="mini opacity-75">
                    @php
                        $totalRows = method_exists($grns, 'total') ? $grns->total() : $grns->count();
                    @endphp
                    Total: {{ number_format($totalRows) }} data
                </div>
            </div>
        </div>

        @if(($grns ?? collect())->isEmpty())
            <div class="p-4">
                <div class="empty">
                    <div class="mb-2"><i data-feather="search"></i></div>
                    Belum ada penerimaan yang cocok dengan filter.
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead>
                    <tr>
                        <th style="width:12%">Tanggal</th>
                        <th style="width:16%">GRN No</th>
                        <th style="width:20%">Supplier</th>
                        <th style="width:10%">PO</th>
                        <th class="text-center" style="width:8%">Item</th>
                        <th class="text-center" style="width:10%">Qty</th>
                        <th class="text-end" style="width:14%">Nilai</th>
                        <th class="text-center sticky-actions" style="width:10%">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($grns as $grn)
                        @php
                            // hitung total qty & nilai (fallback, lebih baik pre-aggregate di query)
                            $sumQty = $grn->items->sum('qty');
                            $sumVal = $grn->items->sum(function($it){ return (float)$it->qty * (float)$it->price; });

                            // status dari PO terkait (sekadar indikator)
                            $poStatus = $grn->purchase->status ?? 'draft';
                            $badgeClass = match($poStatus){
                                'received' => 'status-complete',
                                'partial_received' => 'status-partial',
                                default => 'status-draft',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ optional($grn->received_at)->format('d M Y') }}</div>
                                <div class="mini">{{ $grn->created_at?->format('H:i') }} WIB</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $grn->grn_no ?? '—' }}</div>
                                <div class="mini text-truncate" style="max-width:220px;">{{ $grn->notes ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $grn->supplier->name ?? 'Tanpa Supplier' }}</div>
                                <span class="status-badge {{ $badgeClass }}">{{ str_replace('_',' ', $poStatus) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('purchases.show', $grn->purchase_id) }}" class="text-decoration-none">#{{ $grn->purchase_id }}</a>
                            </td>
                            <td class="text-center">{{ $grn->items->count() }}</td>
                            <td class="text-center">{{ number_format($sumQty) }}</td>
                            <td class="text-end">Rp {{ number_format($sumVal, 2, ',', '.') }}</td>
                            <td class="text-center sticky-actions">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('grn.show', $grn->id) }}" class="btn btn-ghost">Lihat</a>
                                    {{-- Jika butuh cetak, ganti ke route cetak kamu --}}
                                    {{-- <a href="{{ route('grn.print', $grn->id) }}" class="btn btn-ghost">Cetak</a> --}}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        @php
                            $pageQty = $grns->sum(function($grn){ return $grn->items->sum('qty'); });
                            $pageVal = $grns->sum(function($grn){ return $grn->items->sum(function($it){ return $it->qty * $it->price;});});
                        @endphp
                        <td colspan="5" class="mini ps-3">Ringkasan halaman ini</td>
                        <td class="text-center fw-semibold">{{ number_format($pageQty) }}</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($pageVal,2,',','.') }}</td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <div class="p-3">
                {{ method_exists($grns, 'links') ? $grns->withQueryString()->links() : '' }}
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.feather) feather.replace();
    });
</script>
@endsection
