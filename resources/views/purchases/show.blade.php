@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
<style>
    .header-card{
        border-radius:16px;
        background:linear-gradient(90deg,#5e0d0d,#8d1b1b);
        color:#fff;
        padding:20px 24px;
        box-shadow:0 4px 12px rgba(93,13,13,.35);
        margin-bottom:24px;
    }
    .header-card .badge{
        background:rgba(255,255,255,.15);
        border:1px solid rgba(255,255,255,.2);
        font-size:.8rem;
    }
    .info-row{ display:flex; justify-content:space-between; border-bottom:1px dashed #eee; padding:.45rem 0; font-size:.9rem;}
    .table th{ background:#f9fafb; font-weight:600;}
    .table td, .table th{ vertical-align:middle;}
    .btn-maroon{ background:#8d1b1b; color:#fff; border:1px solid #5e0d0d; }
    .btn-maroon:hover{ background:#5e0d0d; color:#fff; }
    .status-badge{
        border-radius:999px; padding:.25rem .65rem; font-size:.8rem; font-weight:600;
        border:1px solid transparent; text-transform:capitalize;
    }
    .status-draft{ background:#e5e7eb; color:#374151; border-color:#d1d5db; }
    .status-ordered{ background:#dbeafe; color:#1e40af; border-color:#bfdbfe; }
    .status-partial_received{ background:#fff3cd; color:#7a5c00; border-color:#ffe69c; }
    .status-received{ background:#dcfce7; color:#065f46; border-color:#bbf7d0; }
</style>

@php
    use Illuminate\Support\Carbon;

    // Normalisasi status
    $statusRaw  = strtoupper($purchase->status ?? '');
    $statusCss  = strtolower($statusRaw);
    $statusText = ucwords(strtolower(str_replace('_',' ', $statusRaw)));

    // Koleksi aman
    $items = $purchase->items ?? collect();
    $grns  = $purchase->goodsReceipts ?? collect();

    // Tanggal aman
    $createdAtWib = $purchase->created_at ? $purchase->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') : null;
    $poDateFmt    = $purchase->po_date
        ? ($purchase->po_date instanceof \Carbon\Carbon
            ? $purchase->po_date->format('d M Y')
            : (is_string($purchase->po_date) && Carbon::hasFormat($purchase->po_date, 'Y-m-d')
                ? Carbon::parse($purchase->po_date)->format('d M Y')
                : $purchase->po_date))
        : null;

    // Nama route kirim PO (opsional)
    $sendPoRouteName = \Illuminate\Support\Facades\Route::has('supplier_returns.suppliers.send_po')
        ? 'supplier_returns.suppliers.send_po'
        : (\Illuminate\Support\Facades\Route::has('suppliers.send_po') ? 'suppliers.send_po' : null);
@endphp

<div class="container">

    {{-- ALERT SUKSES --}}
    @if(session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="header-card d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1">Purchase Order #{{ $purchase->po_no ?? $purchase->id }}</h4>
            <div class="small opacity-75">
                Dibuat {{ $createdAtWib ?? '—' }} WIB
                oleh {{ $purchase->user?->name ?? '—' }}
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="status-badge status-{{ $statusCss }}">{{ $statusText }}</span>

            {{-- Tombol kirim email (opsional) --}}
            @if($sendPoRouteName && ($purchase->supplier?->email))
                <form action="{{ route($sendPoRouteName, [$purchase->supplier_id, $purchase->id]) }}"
                      method="POST"
                      class="d-inline"
                      onsubmit="return confirm('Kirim PO {{ $purchase->po_no ?? ('#'.$purchase->id) }} ke {{ $purchase->supplier->email }}?')">
                    @csrf
                    <button class="btn btn-outline-light btn-sm border">
                        Kirim ke Supplier
                    </button>
                </form>
            @elseif($sendPoRouteName && !$purchase->supplier?->email)
                <span class="badge bg-warning text-dark">Supplier belum punya email</span>
            @endif
        </div>
    </div>

    {{-- INFO PO --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold bg-light">
            Informasi Pembelian
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span>Supplier</span>
                        <span class="fw-semibold">{{ $purchase->supplier?->name ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span>Tanggal PO</span>
                        <span>{{ $poDateFmt ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span>Nomor PO</span>
                        <span>{{ $purchase->po_no ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span>Status</span>
                        <span>{{ $statusText }}</span>
                    </div>
                    <div class="info-row">
                        <span>Catatan</span>
                        <span>{{ $purchase->note ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ITEM PO --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold bg-light">Detail Barang</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th class="text-center">Qty Pesan</th>
                            <th class="text-center">Qty Diterima</th>
                            <th class="text-end">Harga Beli</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grand = 0; @endphp
                        @forelse ($items as $i => $item)
                            @php
                                $qty       = (int) ($item->qty ?? 0);
                                $cost      = (float) ($item->cost ?? 0);
                                $received  = (int) ($item->qty_received ?? 0);
                                $subtotal  = $qty * $cost;
                                $grand    += $subtotal;
                                $class     = $received >= $qty
                                    ? 'text-success fw-semibold'
                                    : ($received > 0 ? 'text-warning' : '');
                            @endphp
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $item->product?->name ?? '—' }}</td>
                                <td class="text-center">{{ number_format($qty) }}</td>
                                <td class="text-center {{ $class }}">{{ number_format($received) }}</td>
                                <td class="text-end">Rp {{ number_format($cost, 2, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($subtotal, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Belum ada item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total</th>
                            <th class="text-end fw-bold">Rp {{ number_format($grand, 2, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PENERIMAAN (opsional; hanya tampil, tidak ada tombol ke GRN) --}}
    @if (($grns->count() ?? 0) > 0)
        <div class="card mb-4">
            <div class="card-header fw-semibold bg-light">Riwayat Penerimaan Barang</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>GRN No</th>
                                <th>Item</th>
                                <th class="text-center">Total Qty</th>
                                <th class="text-end">Total Nilai</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grns as $grn)
                                @php
                                    $grnItems = $grn->items ?? collect();
                                    $qty   = (int) $grnItems->sum('qty');
                                    $value = (float) $grnItems->sum(fn($it) => ((int)($it->qty ?? 0)) * ((float)($it->price ?? 0)));
                                @endphp
                                <tr>
                                    <td>{{ $grn->received_at?->format('d M Y') ?? '—' }}</td>
                                    <td>{{ $grn->grn_no ?? '—' }}</td>
                                    <td>{{ $grnItems->count() }}</td>
                                    <td class="text-center">{{ number_format($qty) }}</td>
                                    <td class="text-end">Rp {{ number_format($value, 2, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if(Route::has('grn.show'))
                                            <a href="{{ route('grn.show', $grn->id) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- AKSI --}}
    <div class="d-flex justify-content-between">
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Kembali</a>

        <div class="d-flex gap-2">
            @if($statusRaw === 'DRAFT' && Route::has('purchases.submit'))
                <form action="{{ route('purchases.submit', $purchase) }}" method="POST"
                      onsubmit="return confirm('Yakin submit PO ini ke status Ordered?')">
                    @csrf
                    <button type="submit" class="btn btn-maroon">Submit PO</button>
                </form>
            @endif

            <a href="{{ route('purchases.print.blanko', $purchase) }}"
               target="_blank"
               class="btn btn-outline-secondary">
               Cetak Blanko
            </a>
        </div>
    </div>
</div>
@endsection
