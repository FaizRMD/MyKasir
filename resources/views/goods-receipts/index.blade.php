@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')
    <style>
        :root {
            --maroon-700: #8d1b1b;
            --maroon-800: #701010;
            --maroon-50: #fff1f2;
        }

        .grn-hero {
            background: linear-gradient(120deg, var(--maroon-800), var(--maroon-700));
            color: #f8fafc;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 12px 30px rgba(141, 27, 27, 0.25);
        }

        .grn-hero .mini {
            opacity: 0.8;
        }

        .grn-chip {
            background: rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 8px 12px;
            min-width: 140px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .filter-grid .form-control,
        .filter-grid .form-select {
            background-color: #f8fafc;
        }

        .card-shadow {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .table thead th {
            border-color: #e5e7eb;
            color: #475569;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .table-hover tbody tr:hover {
            background-color: #f8fafc;
        }

        .empty-state {
            text-align: center;
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            text-transform: capitalize;
        }

        .status-complete {
            background: #ecfdf3;
            color: #166534;
        }

        .status-partial {
            background: #fff7ed;
            color: #c2410c;
        }

        .status-draft {
            background: var(--maroon-50);
            color: #9f1239;
        }

        .legend-chip {
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.16);
            min-width: 150px;
        }
        .legend-chip .label { opacity: .8; font-size: 12px; }
    </style>

    @php
        $totalRows = method_exists($grns, 'total') ? $grns->total() : $grns->count();
        $pageQty = $grns->sum('total_qty');
        $pageVal = $grns->sum('total_value');
        $collection = $grns instanceof \Illuminate\Pagination\AbstractPaginator ? $grns->getCollection() : collect($grns);
        $receivedCount = $collection->filter(fn($g) => ($g->pembelian->status ?? 'draft') === 'received')->count();
        $partialCount = $collection->filter(fn($g) => ($g->pembelian->status ?? 'draft') === 'partial_received')->count();
        $draftCount = $collection->filter(fn($g) => ($g->pembelian->status ?? 'draft') === 'draft')->count();
    @endphp

    <div class="container page-wrap py-3">
        <div class="grn-hero d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div class="d-flex gap-3 align-items-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle"
                    style="width:48px;height:48px;background:rgba(255,255,255,0.1);">
                    <i data-feather="inbox"></i>
                </div>
                <div>
                    <p class="m-0 mini text-uppercase fw-semibold">Penerimaan Barang</p>
                    <h4 class="m-0 fw-bold">Goods Receipt Note</h4>
                    <div class="mini">Pantau penerimaan per supplier, per PO, dan per periode.</div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div class="grn-chip">
                    <div class="mini">Total data</div>
                    <div class="fw-bold fs-6 mb-0">{{ number_format($totalRows) }}</div>
                </div>
                <div class="grn-chip">
                    <div class="mini">Qty halaman ini</div>
                    <div class="fw-bold fs-6 mb-0">{{ number_format($pageQty) }}</div>
                </div>
                <div class="grn-chip">
                    <div class="mini">Nilai halaman ini</div>
                    <div class="fw-bold fs-6 mb-0">Rp {{ number_format($pageVal, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <div class="legend-chip">
                <div class="label">Selesai diterima</div>
                <div class="fw-bold">{{ number_format($receivedCount) }} GRN</div>
            </div>
            <div class="legend-chip">
                <div class="label">Parsial</div>
                <div class="fw-bold">{{ number_format($partialCount) }} GRN</div>
            </div>
            <div class="legend-chip">
                <div class="label">Draft / belum terima</div>
                <div class="fw-bold">{{ number_format($draftCount) }} GRN</div>
            </div>
        </div>

        <div class="card-shadow mb-3">
            <form method="GET" action="{{ route('goods-receipts.index') }}" class="filter-grid p-3">
                <div>
                    <label class="form-label mini text-uppercase fw-semibold">Dari tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label mini text-uppercase fw-semibold">Sampai tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label mini text-uppercase fw-semibold">Supplier</label>
                    <input type="text" name="supplier" value="{{ request('supplier') }}" placeholder="Cari nama supplier" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label mini text-uppercase fw-semibold">Kata kunci</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="GRN No / PO / Produk / Catatan" class="form-control form-control-sm">
                </div>
                <div class="d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Terapkan</button>
                    <a href="{{ route('goods-receipts.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-shadow">
            <div class="p-3" style="background:var(--maroon-900); color:#fff; border-radius:14px 14px 0 0;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">Daftar Penerimaan</div>
                    <div class="mini opacity-75">Total: {{ number_format($totalRows) }} data</div>
                </div>
            </div>

            @if (($grns ?? collect())->isEmpty())
                <div class="p-4">
                    <div class="empty-state">
                        <div class="mb-2"><i data-feather="search"></i></div>
                        Belum ada penerimaan yang cocok dengan filter.
                    </div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover m-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th>GRN</th>
                                <th>Supplier / Status</th>
                                <th>PO</th>
                                <th class="text-center">Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Nilai</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grns as $grn)
                                @php
                                    $poStatus = $grn->pembelian->status ?? 'draft';
                                    $badgeClass = match ($poStatus) {
                                        'received' => 'status-complete',
                                        'partial_received' => 'status-partial',
                                        default => 'status-draft',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ optional($grn->received_at)->format('d M Y') }}</div>
                                        <div class="mini text-muted">{{ $grn->created_at?->format('H:i') }} WIB</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $grn->grn_no ?? '—' }}</div>
                                        <div class="mini text-truncate" style="max-width:220px;">{{ $grn->notes ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $grn->supplier->name ?? 'Tanpa Supplier' }}</div>
                                        <span class="status-badge {{ $badgeClass }}">{{ str_replace('_', ' ', $poStatus) }}</span>
                                    </td>
                                    <td>
                                        @if ($grn->pembelian)
                                            <a href="{{ route('reports.pembelian.show', $grn->pembelian->id) }}" class="text-decoration-none">
                                                {{ $grn->pembelian->po_no ?? '#' . $grn->pembelian->id }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $grn->items()->count() }}</td>
                                    <td class="text-center">{{ number_format($grn->total_qty ?? 0) }}</td>
                                    <td class="text-end">Rp {{ number_format($grn->total_value ?? 0, 2, ',', '.') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('goods-receipts.show', $grn->id) }}" class="btn btn-ghost btn-sm">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="mini ps-3">Ringkasan halaman ini</td>
                                <td class="text-center fw-semibold">{{ number_format($pageQty) }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($pageVal, 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted mini">
                            Halaman {{ $grns->currentPage() }} dari {{ $grns->lastPage() }}
                            (Total: {{ number_format($grns->total()) }} data)
                        </div>
                        <div class="d-flex gap-2">
                            @if ($grns->onFirstPage())
                                <button class="btn btn-sm btn-outline-secondary" disabled>← Sebelumnya</button>
                            @else
                                <a href="{{ $grns->previousPageUrl() }}" class="btn btn-sm btn-outline-secondary">← Sebelumnya</a>
                            @endif

                            @if ($grns->hasMorePages())
                                <a href="{{ $grns->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary">Berikutnya →</a>
                            @else
                                <button class="btn btn-sm btn-outline-secondary" disabled>Berikutnya →</button>
                            @endif
                        </div>
                    </div>
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
