@extends('layouts.app')

@section('title', 'Detail Penerimaan Barang')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('grn.index') }}">Penerimaan</a></li>
    <li class="breadcrumb-item active" aria-current="page">GRN #{{ $grn->id }}</li>
@endsection

@section('content')
<style>
    .hero{
        border-radius:16px;
        background:linear-gradient(90deg,#5e0d0d,#8d1b1b);
        color:#fff;
        padding:18px 20px;
        box-shadow:0 8px 20px rgba(93,13,13,.28);
        margin-bottom:18px;
    }
    .hero .badge{
        background:rgba(255,255,255,.12);
        border:1px solid rgba(255,255,255,.25);
        font-weight:600;
    }
    .mini{font-size:.9rem; opacity:.9}
    .card { border-radius:16px }
    .table thead th{ background:#f8fafc; font-weight:700 }
    .status-badge{ border-radius:999px; padding:.25rem .65rem; font-size:.78rem; font-weight:700; border:1px solid transparent; text-transform:capitalize }
    .status-received{ background:#dcfce7; color:#065f46; border-color:#bbf7d0 }
    .status-partial_received{ background:#fff3cd; color:#7a5c00; border-color:#ffe69c }
    .status-ordered,.status-draft{ background:#e5e7eb; color:#374151; border-color:#d1d5db }
    .btn-maroon{ background:#8d1b1b; color:#fff; border:1px solid #5e0d0d }
    .btn-maroon:hover{ background:#5e0d0d; color:#fff }
    .badge-soft{ border-radius:10px; padding:.22rem .5rem; font-weight:600 }
    .badge-soft.info{ background:rgba(15,185,177,.14); color:#0e6b67 }
    .badge-soft.warn{ background:rgba(245,193,69,.18); color:#7a5c00 }
</style>

<div class="container">

    {{-- HEADER --}}
    <div class="hero d-flex justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0">GRN #{{ $grn->id }}</h4>
                <span class="badge">Penerimaan Barang</span>
            </div>
            <div class="mini">
                Tanggal terima: <strong>{{ $grn->received_at?->format('d M Y') }}</strong>
                • Dibuat: {{ $grn->created_at?->format('d M Y H:i') }} WIB
            </div>
        </div>
        <div class="text-end">
            @php $poStatus = $grn->purchase->status ?? 'draft'; @endphp
            <div class="mb-1">
                <span class="status-badge status-{{ $poStatus }}">{{ str_replace('_',' ', $poStatus) }}</span>
            </div>
            <a href="{{ route('purchases.show', $grn->purchase_id) }}" class="btn btn-light btn-sm">
                Lihat PO #{{ $grn->purchase_id }}
            </a>
        </div>
    </div>

    {{-- META --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header fw-semibold bg-light">Informasi GRN</div>
                <div class="card-body">
                    <div class="row gy-2">
                        <div class="col-6">Nomor GRN</div>
                        <div class="col-6 fw-semibold text-end">{{ $grn->grn_no ?? '—' }}</div>

                        <div class="col-6">Supplier</div>
                        <div class="col-6 fw-semibold text-end">{{ $grn->supplier->name ?? 'Tanpa Supplier' }}</div>

                        <div class="col-6">Catatan</div>
                        <div class="col-6 text-end">{{ $grn->notes ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            @php
                $totalItems  = $summary['total_items'] ?? $grn->items->count();
                $totalQty    = $summary['total_qty']   ?? (int)$grn->items->sum('qty');
                $totalValue  = $summary['total_value'] ?? (float)$grn->items->sum(fn($it)=>$it->qty*$it->price);
            @endphp
            <div class="card">
                <div class="card-header fw-semibold bg-light">Ringkasan</div>
                <div class="card-body">
                    <div class="row gy-2">
                        <div class="col-6">Jumlah baris</div>
                        <div class="col-6 fw-semibold text-end">{{ number_format($totalItems) }}</div>

                        <div class="col-6">Total kuantitas</div>
                        <div class="col-6 fw-semibold text-end">{{ number_format($totalQty) }}</div>

                        <div class="col-6">Total nilai</div>
                        <div class="col-6 fw-bold text-end">Rp {{ number_format($totalValue, 2, ',', '.') }}</div>
                    </div>
                    <div class="mt-2 text-end">
                        <span class="badge-soft info">Harga diambil per-batch</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ITEMS --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold bg-light d-flex justify-content-between align-items-center">
            <span>Detail Penerimaan</span>
            <div class="d-flex gap-2">
                {{-- Siapkan route cetak/export sesuai kebutuhanmu --}}
                {{-- <a href="{{ route('grn.print', $grn->id) }}" class="btn btn-sm btn-outline-secondary">Cetak</a> --}}
                {{-- <a href="{{ route('grn.export', $grn->id) }}" class="btn btn-sm btn-outline-secondary">Excel</a> --}}
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:4%">No</th>
                            <th style="width:28%">Produk</th>
                            <th style="width:12%">Batch No</th>
                            <th style="width:12%">Exp Date</th>
                            <th class="text-center" style="width:10%">Qty</th>
                            <th class="text-end" style="width:12%">Harga</th>
                            <th class="text-end" style="width:12%">Subtotal</th>
                            <th class="text-center" style="width:10%">Rack</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grand = 0; @endphp
                        @foreach ($grn->items as $idx => $it)
                            @php
                                $sub = (float)$it->qty * (float)$it->price;
                                $grand += $sub;
                            @endphp
                            <tr>
                                <td>{{ $idx+1 }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $it->product->name ?? 'Produk' }}</div>
                                    <div class="text-muted small">SKU: {{ $it->product->sku ?? '—' }}</div>
                                </td>
                                <td>{{ $it->batch_no ?? '—' }}</td>
                                <td>{{ $it->exp_date?->format('d M Y') ?? '—' }}</td>
                                <td class="text-center">{{ number_format($it->qty) }}</td>
                                <td class="text-end">Rp {{ number_format($it->price, 2, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($sub, 2, ',', '.') }}</td>
                                <td class="text-center">{{ $it->rack ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-end">Total</th>
                            <th class="text-end fw-bold">Rp {{ number_format($grand, 2, ',', '.') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- AKSI --}}
    <div class="d-flex justify-content-between">
        <a href="{{ route('grn.index') }}" class="btn btn-outline-secondary">Kembali</a>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.show', $grn->purchase_id) }}" class="btn btn-light">Lihat PO</a>
            {{-- Tambahkan aksi lain bila perlu --}}
            {{-- <a href="{{ route('grn.print', $grn->id) }}" class="btn btn-maroon">Cetak</a> --}}
        </div>
    </div>
</div>
@endsection
