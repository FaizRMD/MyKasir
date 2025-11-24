@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')
    {{-- CSS kamu yg sebelumnya boleh tetap, aku skip biar jawaban nggak terlalu panjang --}}

    <div class="container page-wrap py-3">

        {{-- Header --}}
        <div class="headbar mb-3">
            <div class="d-flex align-items-center justify-content-center rounded-circle"
                style="width:42px;height:42px;background:rgba(255,255,255,.15);">
                <i data-feather="inbox"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="m-0 fw-bold">Penerimaan Barang</h5>
                    <span class="badge">GRN</span>
                </div>
                <div class="mini">Rekap penerimaan dari supplier, lengkap dengan filter tanggal, supplier, dan kata kunci.
                </div>
            </div>
            <div>
                <a href="{{ route('pembelian.create') }}" class="btn btn-ghost btn-sm">Buat dari PO</a>
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" action="{{ route('goods-receipts.index') }}" class="filter-card mb-3">
            {{-- form filter sama seperti punyamu --}}
            {{-- ... --}}
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

            @if (($grns ?? collect())->isEmpty())
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
                                <th>Tanggal</th>
                                <th>GRN No</th>
                                <th>Supplier</th>
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
                                    <td>
                                        <div class="fw-semibold">{{ optional($grn->received_at)->format('d M Y') }}</div>
                                        <div class="mini">{{ $grn->created_at?->format('H:i') }} WIB</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $grn->grn_no ?? '—' }}</div>
                                        <div class="mini text-truncate" style="max-width:220px;">{{ $grn->notes ?? '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $grn->supplier->name ?? 'Tanpa Supplier' }}</div>
                                        <span
                                            class="status-badge {{ $badgeClass }}">{{ str_replace('_', ' ', $poStatus) }}</span>
                                    </td>
                                    <td>
                                        @if ($grn->pembelian)
                                            <a href="{{ route('reports.pembelian.show', $grn->pembelian->id) }}"
                                                class="text-decoration-none">
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
                                        <a href="{{ route('goods-receipts.show', $grn->id) }}"
                                            class="btn btn-ghost btn-sm">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                @php
                                    $pageQty = $grns->sum('total_qty');
                                    $pageVal = $grns->sum('total_value');
                                @endphp
                                <td colspan="5" class="mini ps-3">Ringkasan halaman ini</td>
                                <td class="text-center fw-semibold">{{ number_format($pageQty) }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($pageVal, 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="p-3">
                    {{ $grns->withQueryString()->links() }}
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
