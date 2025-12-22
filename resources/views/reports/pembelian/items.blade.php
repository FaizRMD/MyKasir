@extends('layouts.app')

@section('title', 'Laporan Item Pembelian')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-semibold mb-1">
            <i class="bi bi-list-ul me-2"></i>
            Laporan Item Pembelian
        </h4>
        <p class="text-muted small mb-0">Detail item setiap pembelian dari supplier</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Pencarian Produk</label>
                <input type="text" name="q" class="form-control form-control-sm"
                    placeholder="Nama / Kode Produk..." value="{{ request('q') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" name="from" class="form-control form-control-sm"
                    value="{{ request('from') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" name="to" class="form-control form-control-sm"
                    value="{{ request('to') }}">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-sm btn-brand">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="{{ route('reports.pembelian.items') }}" class="btn btn-sm btn-light-soft">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-modern mb-0 small">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 120px;">Invoice</th>
                    <th style="width: 100px;">Tgl Invoice</th>
                    <th>Supplier</th>
                    <th>Produk</th>
                    <th style="width: 80px;" class="text-center">Qty</th>
                    <th style="width: 100px;" class="text-end">Harga</th>
                    <th style="width: 100px;" class="text-end">Diskon</th>
                    <th style="width: 100px;" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $key => $item)
                    <tr>
                        <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                        <td>
                            <small class="fw-semibold">{{ $item->invoice_no ?? '-' }}</small>
                        </td>
                        <td>
                            <small>{{ \Carbon\Carbon::parse($item->invoice_date)->format('d M Y') ?? '-' }}</small>
                        </td>
                        <td>
                            <small class="fw-semibold">{{ $item->supplier_name ?? '-' }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $item->product_name ?? '-' }}</div>
                            <small class="text-muted">{{ $item->product_code ?? '-' }}</small>
                        </td>
                        <td class="text-center">
                            <strong>{{ number_format($item->qty ?? 0, 2) }}</strong>
                        </td>
                        <td class="text-end">
                            <small>Rp {{ number_format($item->buy_price ?? 0, 0, ',', '.') }}</small>
                        </td>
                        <td class="text-end">
                            <small class="text-danger">
                                Rp {{ number_format((($item->disc_amount ?? 0) + ($item->disc_nominal ?? 0)), 0, ',', '.') }}
                            </small>
                        </td>
                        <td class="text-end">
                            <strong>Rp {{ number_format($item->item_total ?? 0, 0, ',', '.') }}</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <div class="text-muted mt-2">Tidak ada data item pembelian</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($items->hasPages())
        <div class="card-footer">
            {{ $items->links() }}
        </div>
    @endif
</div>
@endsection
