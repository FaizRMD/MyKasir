@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
    <style>
        :root {
            --maroon-900: #4a0d0d;
            --maroon-800: #5e1010;
            --maroon-700: #731414;
            --maroon-600: #8a1818;
            --maroon-100: #fdeaea;
            --gray-50: #fafafa;
            --gray-100: #f4f4f5;
            --gray-200: #e5e7eb;
            --gray-600: #52525b;
        }

        .shell {
            background: linear-gradient(180deg, var(--maroon-100), #fff 40%);
            min-height: 100%;
            padding-bottom: 3rem;
        }

        .headbar {
            background: var(--maroon-700);
            color: #fff;
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 6px 16px rgba(115, 20, 20, .28);
        }

        .head-icon {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .meta-card {
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            background: #fff;
            padding: 12px 14px;
            height: 100%;
        }

        .meta-label {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--gray-600);
            margin-bottom: 2px;
        }

        .meta-value {
            font-weight: 600;
        }

        .summary-card {
            border-radius: 14px;
            background: #fff;
            padding: 12px 14px;
            border: 1px solid var(--gray-200);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: .9rem;
            padding: 2px 0;
        }

        .summary-row strong {
            font-weight: 600;
        }

        .table thead th {
            background: var(--gray-100) !important;
            border-bottom: 1px solid var(--gray-200) !important;
            font-weight: 700;
            font-size: .85rem;
        }

        .table tbody td {
            vertical-align: middle;
            border-color: var(--gray-200) !important;
            font-size: .87rem;
        }

        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            font-size: .75rem;
            color: var(--gray-600);
        }

        .mini {
            font-size: .8rem;
            color: var(--gray-600);
        }
    </style>

    <div class="container shell py-3">
        {{-- Header --}}
        <div class="headbar mb-3">
            <div class="head-icon">
                <i data-feather="file-text"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="m-0 fw-bold">Detail Pembelian</h5>
                    <span class="badge bg-light text-dark">
                        PO {{ $pembelian->po_no ?? '—' }}
                    </span>
                </div>
                <div style="font-size:.85rem;opacity:.8;">
                    Supplier: {{ $pembelian->supplier->name ?? 'Tanpa Supplier' }}
                    • Tanggal: {{ \Carbon\Carbon::parse($pembelian->invoice_date)->format('d M Y') }}
                </div>
            </div>
            <div class="d-flex flex-column align-items-end" style="font-size:.9rem;">
                <span class="text-uppercase" style="font-size:.75rem;opacity:.8;">Total</span>
                <strong>Rp {{ number_format($summary['net_total'], 0, ',', '.') }}</strong>
            </div>
        </div>

        {{-- Meta --}}
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="meta-card">
                    <div class="meta-label">Supplier</div>
                    <div class="meta-value">{{ $pembelian->supplier->name ?? '—' }}</div>

                    <div class="meta-label mt-3">Gudang</div>
                    <div class="meta-value">{{ $pembelian->warehouse->name ?? '—' }}</div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="meta-card">
                    <div class="meta-label">Tipe Pembayaran</div>
                    <div class="meta-value">{{ $pembelian->payment_type ?? '—' }}</div>

                    <div class="meta-label mt-3">Jatuh Tempo</div>
                    <div class="meta-value">
                        {{ $pembelian->due_date ? \Carbon\Carbon::parse($pembelian->due_date)->format('d M Y') : '—' }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-row">
                        <span>Jumlah Item</span>
                        <strong>{{ $summary['total_items'] }}</strong>
                    </div>
                    <div class="summary-row">
                        <span>Total Qty</span>
                        <strong>{{ number_format($summary['total_qty'], 0, ',', '.') }}</strong>
                    </div>
                    <hr class="my-2">
                    <div class="summary-row">
                        <span>Gross</span>
                        <span>Rp {{ number_format($summary['gross'], 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Diskon</span>
                        <span>Rp {{ number_format($summary['discount'], 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>PPN</span>
                        <span>Rp {{ number_format($summary['tax'], 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Lain</span>
                        <span>Rp {{ number_format($summary['extra_cost'], 0, ',', '.') }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="summary-row">
                        <span><strong>Net Total</strong></span>
                        <span><strong>Rp {{ number_format($summary['net_total'], 0, ',', '.') }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Item --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header" style="background:var(--maroon-900);color:#fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Daftar Item</span>
                    <span style="font-size:.8rem;opacity:.8;">
                        Total baris: {{ $pembelian->items->count() }}
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:32%;">Produk</th>
                                <th class="text-center" style="width:8%;">Qty</th>
                                <th class="text-center" style="width:8%;">Satuan</th>
                                <th class="text-end" style="width:12%;">Harga Beli</th>
                                <th class="text-end" style="width:10%;">Diskon</th>
                                <th class="text-end" style="width:14%;">Subtotal</th>
                                <th style="width:16%;">Batch / Exp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pembelian->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->product->name ?? 'Produk tidak ditemukan' }}
                                        </div>
                                        <div class="mini">
                                            Kode:
                                            {{ $item->product->code ?? ($item->product->sku ?? ($item->product->barcode ?? '—')) }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="pill">{{ number_format($item->qty, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center">{{ $item->uom }}</td>
                                    <td class="text-end">
                                        Rp {{ number_format($item->buy_price, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $discText = [];
                                            if ($item->disc_percent > 0) {
                                                $discText[] = $item->disc_percent . '%';
                                            }
                                            if ($item->disc_nominal > 0) {
                                                $discText[] = 'Rp ' . number_format($item->disc_nominal, 0, ',', '.');
                                            }
                                        @endphp
                                        {{ $discText ? implode(' + ', $discText) : '—' }}
                                    </td>
                                    <td class="text-end">
                                        Rp {{ number_format($item->subtotal, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <div class="mini">
                                            Batch: {{ $item->batch_no ?? '—' }}<br>
                                            Exp:
                                            {{ $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->format('d-m-Y') : '—' }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Tidak ada item pada pembelian ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        @if ($pembelian->items->count())
                            @php
                                $totalSub = $pembelian->items->sum('subtotal');
                            @endphp
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-semibold">Total</td>
                                    <td></td>
                                    <td class="text-end fw-semibold">
                                        Qty: {{ number_format($summary['total_qty'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format($totalSub, 2, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between bg-white">
                <a href="{{ route('reports.pembelian.index') }}" class="btn btn-outline-secondary btn-sm">
                    &laquo; Kembali ke Laporan Pembelian
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.feather) feather.replace();
        });
    </script>
@endsection
