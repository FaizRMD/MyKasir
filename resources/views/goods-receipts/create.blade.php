@extends('layouts.app')

@section('title', 'Terima Barang')

@section('content')
@php
    $items = $pembelian->items ?? collect();
@endphp

<style>
    .grn-header {
        background: linear-gradient(120deg, #701010, #8d1b1b);
        color: #fff;
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 10px 25px rgba(141, 27, 27, 0.22);
        margin-bottom: 16px;
    }
    .card-shadow { border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 8px 24px rgba(15,23,42,.06); }
    .table thead th { background:#f8fafc; font-size:13px; text-transform:uppercase; letter-spacing:.3px; }
    .mini { opacity:.8; font-size:.9rem; }
</style>

<div class="container py-3">
    <div class="grn-header d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <p class="m-0 mini">Penerimaan Barang</p>
            <h5 class="m-0 fw-bold">PO {{ $pembelian->po_no ?? ('#'.$pembelian->id) }}</h5>
            <div class="mini">Supplier: <strong>{{ $pembelian->supplier->name ?? '-' }}</strong></div>
        </div>
        <div class="text-end">
            <div class="mini">Tanggal PO: {{ optional($pembelian->invoice_date ?? $pembelian->created_at)->format('d M Y') }}</div>
            <div class="mini">Status: {{ $pembelian->status ?? 'draft' }}</div>
        </div>
    </div>

    <form action="{{ route('goods-receipts.store', $pembelian->id) }}" method="POST" class="card-shadow p-3">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal Penerimaan</label>
                <input type="date" name="received_at" value="{{ now()->format('Y-m-d') }}" class="form-control" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Catatan</label>
                <input type="text" name="notes" class="form-control" placeholder="Opsional">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Produk</th>
                        <th class="text-center" style="width:120px">Qty PO</th>
                        <th class="text-center" style="width:140px">Qty Terima</th>
                        <th style="width:150px">Batch</th>
                        <th style="width:170px">Exp Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $i => $item)
                        @php
                            $outstanding = max(0, (int)($item->qty ?? 0) - (int)($item->qty_received ?? 0));
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $item->product->name ?? '-' }}</div>
                                <div class="mini text-muted">PO Qty: {{ $item->qty }} | Sudah terima: {{ $item->qty_received ?? 0 }}</div>
                            </td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-center">
                                <input type="number" name="items[{{ $i }}][rows][0][qty]" class="form-control form-control-sm text-end" step="0.01" min="0" max="{{ $outstanding }}" value="{{ $outstanding }}" required>
                                <input type="hidden" name="items[{{ $i }}][pembelian_item_id]" value="{{ $item->id }}">
                            </td>
                            <td>
                                <input type="text" name="items[{{ $i }}][rows][0][batch_no]" class="form-control form-control-sm" placeholder="Opsional">
                            </td>
                            <td>
                                <input type="date" name="items[{{ $i }}][rows][0][exp_date]" class="form-control form-control-sm" value="">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <a href="{{ route('goods-receipts.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
        </div>
    </form>
</div>
@endsection
