@extends('layouts.app')

@section('title', 'Edit Penerimaan Barang')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('goods-receipts.index') }}">Penerimaan</a></li>
    <li class="breadcrumb-item"><a href="{{ route('goods-receipts.show', $grn->id) }}">GRN #{{ $grn->id }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
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
</style>

<div class="container">
    {{-- HEADER --}}
    <div class="hero">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0">Edit GRN #{{ $grn->id }}</h4>
                <span class="badge">Status: Draft</span>
            </div>
            <div class="mini">PO: {{ $grn->pembelian->po_no ?? '—' }} | Supplier: {{ $grn->supplier->name ?? '—' }}</div>
        </div>
    </div>

    {{-- FORM --}}
    <form action="{{ route('goods-receipts.update', $grn->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header fw-semibold bg-light">Informasi Penerimaan</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Penerimaan</label>
                        <input type="date" name="received_at" value="{{ $grn->received_at->format('Y-m-d') }}" class="form-control" required>
                        @error('received_at')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" value="{{ $grn->notes }}" class="form-control" placeholder="Opsional">
                        @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ITEMS --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold bg-light">Detail Item</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:35%">Produk</th>
                                <th style="width:12%">Qty PO</th>
                                <th style="width:12%">Qty Terima</th>
                                <th style="width:15%">Batch</th>
                                <th style="width:15%">Exp Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grn->items as $idx => $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->product->name ?? '—' }}</div>
                                        <div class="text-muted small">SKU: {{ $item->product->sku ?? '—' }}</div>
                                        <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="text-center">{{ $item->pembelianItem->qty ?? '—' }}</td>
                                    <td class="text-center">
                                        <input type="number" name="items[{{ $idx }}][qty]" value="{{ $item->qty }}" class="form-control form-control-sm text-end" step="0.01" min="0" required>
                                        @error("items.{$idx}.qty")<div class="text-danger small">{{ $message }}</div>@enderror
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][batch_no]" value="{{ $item->batch_no }}" class="form-control form-control-sm" placeholder="Opsional">
                                    </td>
                                    <td>
                                        <input type="date" name="items[{{ $idx }}][exp_date]" value="{{ $item->exp_date?->format('Y-m-d') }}" class="form-control form-control-sm">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- AKSI --}}
        <div class="d-flex justify-content-between">
            <a href="{{ route('goods-receipts.show', $grn->id) }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
