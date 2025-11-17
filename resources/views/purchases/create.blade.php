@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@push('styles')
<style>
    .card-header {
        background: #8d1b1b;
        color: #fff;
        font-weight: 600;
    }
    .btn-maroon {
        background: #8d1b1b;
        color: #fff;
        border: 1px solid #5e0d0d;
    }
    .btn-maroon:hover {
        background: #5e0d0d;
        color: #fff;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px dashed #eee;
        padding: .45rem 0;
        font-size: .9rem;
    }
    table th {
        background: #f9fafb;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Penerimaan Barang untuk PO {{ $purchase->po_no ?? ('#'.$purchase->id) }}</h5>
        </div>
        <div class="card-body">
            {{-- Informasi PO --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="info-row">
                        <span>Supplier</span>
                        <span class="fw-semibold">{{ $purchase->supplier?->name ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span>Tanggal PO</span>
                        <span>{{ $purchase->po_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span>Nomor PO</span>
                        <span>{{ $purchase->po_no ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span>Status</span>
                        <span>{{ ucfirst(strtolower(str_replace('_',' ', $purchase->status))) }}</span>
                    </div>
                </div>
            </div>

            {{-- Form Penerimaan --}}
            <form action="{{ route('grn.store') }}" method="POST">
                @csrf
                <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal Penerimaan</label>
                    <input type="date" name="received_at" value="{{ now()->format('Y-m-d') }}" class="form-control" required>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Produk</th>
                                <th class="text-center">Qty Pesan</th>
                                <th class="text-center">Qty Diterima</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $i => $item)
                                @php
                                    $remaining = ($item->qty ?? 0) - ($item->qty_received ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $item->product?->name ?? '—' }}</td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-center">
                                        <input type="number"
                                               name="items[{{ $i }}][qty]"
                                               class="form-control form-control-sm text-center"
                                               value="{{ $remaining }}"
                                               min="0" max="{{ $remaining }}"
                                               required>
                                        <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item->product_id }}">
                                        <input type="hidden" name="items[{{ $i }}][cost]" value="{{ $item->cost }}">
                                    </td>
                                    <td class="text-end">Rp {{ number_format($item->cost,2,',','.') }}</td>
                                    <td class="text-end">Rp {{ number_format(($item->cost * $remaining),2,',','.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan (opsional)</label>
                    <textarea name="note" class="form-control" rows="2"></textarea>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-outline-secondary">Kembali</a>
                    <button type="submit" class="btn btn-maroon">
                        <i class="bi bi-check-circle me-1"></i> Simpan Penerimaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
