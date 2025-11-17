@extends('layouts.app')

@section('title', 'Detail Laporan Pembelian')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">Detail Pembelian</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('reports.pembelian.index') }}">Laporan
                                        Pembelian</a></li>
                                <li class="breadcrumb-item active">{{ $pembelian->po_no }}</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <a href="{{ route('reports.pembelian.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Info Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Informasi Pembelian</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td width="180"><strong>No. PO</strong></td>
                                        <td>: {{ $pembelian->po_no }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>No. Invoice</strong></td>
                                        <td>: {{ $pembelian->invoice_no ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Invoice</strong></td>
                                        <td>: {{ \Carbon\Carbon::parse($pembelian->invoice_date)->format('d M Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Supplier</strong></td>
                                        <td>: <span class="badge bg-info">{{ $pembelian->supplier->name ?? '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Gudang</strong></td>
                                        <td>: {{ $pembelian->warehouse->name ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td width="180"><strong>Tipe Pembayaran</strong></td>
                                        <td>:
                                            @if ($pembelian->payment_type === 'TUNAI')
                                                <span class="badge bg-success">TUNAI</span>
                                            @elseif($pembelian->payment_type === 'HUTANG')
                                                <span class="badge bg-warning">HUTANG</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $pembelian->payment_type }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($pembelian->payment_type === 'HUTANG' && $pembelian->due_date)
                                        <tr>
                                            <td><strong>Jatuh Tempo</strong></td>
                                            <td>:
                                                {{ \Carbon\Carbon::parse($pembelian->due_date)->format('d M Y') }}
                                                @if (\Carbon\Carbon::parse($pembelian->due_date)->isPast())
                                                    <span class="badge bg-danger">Lewat Tempo</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($pembelian->cashbook)
                                        <tr>
                                            <td><strong>Cashbook</strong></td>
                                            <td>: {{ $pembelian->cashbook }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Pajak</strong></td>
                                        <td>: {{ $pembelian->tax_percent }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat</strong></td>
                                        <td>: {{ \Carbon\Carbon::parse($pembelian->created_at)->format('d M Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        @if ($pembelian->notes)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-light border">
                                        <strong><i class="fas fa-sticky-note"></i> Catatan:</strong><br>
                                        {{ $pembelian->notes }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-light shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Item</h6>
                                <h3 class="mb-0">{{ $stats['total_items'] }}</h3>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Qty</h6>
                                <h3 class="mb-0">{{ number_format($stats['total_qty'], 0, ',', '.') }}</h3>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-cubes fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Diskon</h6>
                                <h3 class="mb-0">{{ number_format($stats['total_discount'], 0, ',', '.') }}</h3>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-percentage fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Total Bayar</h6>
                                <h3 class="mb-0">Rp {{ number_format($stats['net_total'], 0, ',', '.') }}</h3>
                            </div>
                            <div>
                                <i class="fas fa-money-bill-wave fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Detail Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Detail Item Pembelian</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50" class="text-center">No</th>
                                        <th>Kode Produk</th>
                                        <th>Nama Produk</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-center">Satuan</th>
                                        <th class="text-end">Harga Beli</th>
                                        <th class="text-center">Diskon %</th>
                                        <th class="text-end">Diskon (Rp)</th>
                                        <th class="text-end">Subtotal</th>
                                        <th>Batch/Exp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pembelian->items as $index => $item)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td><code>{{ $item->product->code ?? '-' }}</code></td>
                                            <td>
                                                <strong>{{ $item->product->name ?? '-' }}</strong>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-secondary">{{ number_format($item->qty, 0, ',', '.') }}</span>
                                            </td>
                                            <td class="text-center">{{ $item->uom }}</td>
                                            <td class="text-end">Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ $item->disc_percent ?? 0 }}%</td>
                                            <td class="text-end text-danger">
                                                @if ($item->disc_amount > 0)
                                                    -Rp {{ number_format($item->disc_amount, 0, ',', '.') }}
                                                @else
                                                    Rp 0
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                                            </td>
                                            <td>
                                                @if ($item->batch_no)
                                                    <small class="d-block"><strong>Batch:</strong>
                                                        {{ $item->batch_no }}</small>
                                                @endif
                                                @if ($item->exp_date)
                                                    <small class="d-block"><strong>Exp:</strong>
                                                        {{ \Carbon\Carbon::parse($item->exp_date)->format('d/m/Y') }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Tidak ada item pembelian</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="row mt-4">
            <div class="col-md-8">
                <!-- Additional Info or Notes -->
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Ringkasan Pembayaran</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td><strong>Subtotal</strong></td>
                                <td class="text-end">Rp
                                    {{ number_format($stats['subtotal_before_discount'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Diskon Total</strong></td>
                                <td class="text-end text-danger">-Rp
                                    {{ number_format($stats['total_discount'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gross</strong></td>
                                <td class="text-end">Rp {{ number_format($stats['gross'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Pajak ({{ $pembelian->tax_percent }}%)</strong></td>
                                <td class="text-end">Rp {{ number_format($stats['tax_amount'], 0, ',', '.') }}</td>
                            </tr>
                            @if ($stats['extra_cost'] > 0)
                                <tr>
                                    <td><strong>Biaya Tambahan</strong></td>
                                    <td class="text-end">Rp {{ number_format($stats['extra_cost'], 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="border-top">
                                <td>
                                    <h5 class="mb-0"><strong>Total Bayar</strong></h5>
                                </td>
                                <td class="text-end">
                                    <h5 class="mb-0 text-primary"><strong>Rp
                                            {{ number_format($stats['net_total'], 0, ',', '.') }}</strong></h5>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Styles -->
    <style>
        @media print {

            .btn,
            .breadcrumb,
            nav {
                display: none !important;
            }

            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }

            .card-header {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                background: white !important;
            }

            .container-fluid {
                padding: 0 !important;
            }
        }
    </style>
@endsection
