@extends('layouts.app')
@section('content')
    <style>
        .report-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Batasi ukuran semua SVG di halaman ini (supaya tidak jadi raksasa) */
        .report-container svg {
            max-width: 24px;
            max-height: 24px;
        }

        .page-header {
            background: linear-gradient(135deg, #800020 0%, #a0002a 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 4px 20px rgba(128, 0, 32, 0.2);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .filter-title {
            color: #800020;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            color: #495057;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .form-input,
        .form-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #800020;
            box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
        }

        .button-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #800020 0%, #a0002a 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(128, 0, 32, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(128, 0, 32, 0.35);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .btn-export {
            background: #28a745;
            color: white;
        }

        .btn-export:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-card.draft {
            border-left-color: #ffc107;
        }

        .stat-card.ordered {
            border-left-color: #007bff;
        }

        .stat-card.received {
            border-left-color: #28a745;
        }

        .stat-card.total {
            border-left-color: #800020;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
        }

        .stat-subvalue {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 2px solid #f1f3f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            color: #212529;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .table-container {
            overflow-x: auto;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table thead {
            background: linear-gradient(135deg, #800020 0%, #a0002a 100%);
        }

        .modern-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: white;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid #f1f3f5;
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: #fef8f9;
        }

        .modern-table td {
            padding: 1rem;
            color: #495057;
            vertical-align: middle;
        }

        .po-number {
            font-weight: 700;
            color: #800020;
            font-family: 'Courier New', monospace;
        }

        .supplier-name {
            font-weight: 600;
            color: #212529;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-draft {
            background: #fff3cd;
            color: #856404;
        }

        .status-draft .status-indicator {
            background: #ffc107;
        }

        .status-ordered {
            background: #cfe2ff;
            color: #084298;
        }

        .status-ordered .status-indicator {
            background: #0d6efd;
        }

        .status-partial {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-partial .status-indicator {
            background: #17a2b8;
        }

        .status-received {
            background: #d4edda;
            color: #155724;
        }

        .status-received .status-indicator {
            background: #28a745;
        }

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #f8f9fa;
            color: #495057;
        }

        .amount {
            font-weight: 700;
            color: #212529;
            font-size: 1.05rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .btn-view {
            background: #e7f5ff;
            color: #0c5aa6;
        }

        .btn-view:hover {
            background: #d0ebff;
        }

        .btn-print {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        .btn-print:hover {
            background: #e1bee7;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #adb5bd;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            opacity: 0.5;
        }

        .pagination-wrapper {
            padding: 1.5rem 2rem;
            border-top: 2px solid #f1f3f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* ========== PAGINATION CUSTOM TANPA ICON ========== */
        .pagination-info {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .pagination-nav {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .page-btn {
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background: #ffffff;
            font-size: 0.85rem;
            color: #495057;
            text-decoration: none;
            min-width: 34px;
            text-align: center;
        }

        .page-btn:hover {
            background: #f1f3f5;
            color: #212529;
        }

        .page-btn.disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        .page-btn.active {
            background: #800020;
            border-color: #800020;
            color: #ffffff;
        }

        /* ================================================== */

        @media (max-width: 768px) {
            .report-container {
                padding: 1rem;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>

    <div class="report-container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">📊 Laporan Purchase Order</h1>
            <p class="page-subtitle">Kelola dan pantau semua pesanan pembelian Anda</p>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <div class="filter-title">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter & Pencarian
            </div>

            <form method="GET" action="{{ route('purchases.index') }}">
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label">Pencarian</label>
                        <input type="text" name="q" class="form-input" placeholder="No. PO, Supplier, Catatan..."
                            value="{{ request('q') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                            <option value="ORDERED" {{ request('status') === 'ORDERED' ? 'selected' : '' }}>Ordered</option>
                            <option value="PARTIAL_RECEIVED"
                                {{ request('status') === 'PARTIAL_RECEIVED' ? 'selected' : '' }}>Sebagian Diterima</option>
                            <option value="RECEIVED" {{ request('status') === 'RECEIVED' ? 'selected' : '' }}>Diterima
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="from" class="form-input" value="{{ request('from') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="to" class="form-input" value="{{ request('to') }}">
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Cari
                    </button>
                    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset
                    </a>
                    <a href="{{ route('purchases.create') }}" class="btn btn-export">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Buat PO Baru
                    </a>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        @php
            $stats = [
                'total' => $purchases->total(),
                'draft' => 0,
                'ordered' => 0,
                'received' => 0,
                'totalAmount' => 0,
            ];

            foreach ($purchases as $po) {
                $stats['totalAmount'] += $po->total;
                if ($po->status === 'DRAFT') {
                    $stats['draft']++;
                } elseif ($po->status === 'ORDERED') {
                    $stats['ordered']++;
                } elseif ($po->status === 'RECEIVED') {
                    $stats['received']++;
                }
            }
        @endphp

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">Total PO</div>
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-subvalue">Purchase Orders</div>
            </div>
            <div class="stat-card draft">
                <div class="stat-label">Draft</div>
                <div class="stat-value">{{ $stats['draft'] }}</div>
                <div class="stat-subvalue">Belum Disubmit</div>
            </div>
            <div class="stat-card ordered">
                <div class="stat-label">Ordered</div>
                <div class="stat-value">{{ $stats['ordered'] }}</div>
                <div class="stat-subvalue">Menunggu Barang</div>
            </div>
            <div class="stat-card received">
                <div class="stat-label">Diterima</div>
                <div class="stat-value">{{ $stats['received'] }}</div>
                <div class="stat-subvalue">Selesai</div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Daftar Purchase Order</div>
                <div>Total Nilai: <strong>Rp {{ number_format($stats['totalAmount'], 0, ',', '.') }}</strong></div>
            </div>

            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>No. PO</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td>
                                    <div class="po-number">{{ $purchase->po_no }}</div>
                                    <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem;">
                                        {{ $purchase->type }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $purchase->po_date->format('d M Y') }}</div>
                                    <div style="font-size: 0.75rem; color: #6c757d;">
                                        {{ $purchase->po_date->diffForHumans() }}
                                    </div>
                                </td>
                                <td>
                                    <div class="supplier-name">{{ $purchase->supplier->name ?? '-' }}</div>
                                    @if ($purchase->apoteker)
                                        <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem;">
                                            👨‍⚕️ {{ $purchase->apoteker->name }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="category-badge">{{ $purchase->category }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match ($purchase->status) {
                                            'DRAFT' => 'status-draft',
                                            'ORDERED' => 'status-ordered',
                                            'PARTIAL_RECEIVED' => 'status-partial',
                                            'RECEIVED' => 'status-received',
                                            default => 'status-draft',
                                        };
                                        $statusLabel = match ($purchase->status) {
                                            'DRAFT' => 'Draft',
                                            'ORDERED' => 'Ordered',
                                            'PARTIAL_RECEIVED' => 'Sebagian',
                                            'RECEIVED' => 'Diterima',
                                            default => $purchase->status,
                                        };
                                    @endphp
                                    <span class="status-badge {{ $statusClass }}">
                                        <span class="status-indicator"></span>
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $purchase->items_count ?? 0 }}</strong> item(s)
                                </td>
                                <td>
                                    <div class="amount">Rp {{ number_format($purchase->total, 0, ',', '.') }}</div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('purchases.show', $purchase) }}" class="btn-action btn-view">
                                            <svg width="16" height="16" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Detail
                                        </a>
                                        <a href="{{ route('purchases.print.blanko', $purchase) }}"
                                            class="btn-action btn-print" target="_blank">
                                            <svg width="16" height="16" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Print
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div style="font-size: 1.1rem; margin-bottom: 0.5rem;">Belum ada Purchase Order</div>
                                    <div>Buat PO baru untuk memulai pemesanan</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($purchases->hasPages())
                @php
                    $paginator = $purchases->appends(request()->query());
                @endphp
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Menampilkan {{ $purchases->firstItem() }} - {{ $purchases->lastItem() }}
                        dari {{ $purchases->total() }} data
                    </div>

                    <div class="pagination-nav">
                        {{-- Previous --}}
                        <a href="{{ $purchases->onFirstPage() ? '#' : $paginator->previousPageUrl() }}"
                            class="page-btn {{ $purchases->onFirstPage() ? 'disabled' : '' }}">
                            Previous
                        </a>

                        {{-- Nomor halaman --}}
                        @for ($page = 1; $page <= $purchases->lastPage(); $page++)
                            <a href="{{ $paginator->url($page) }}"
                                class="page-btn {{ $page == $purchases->currentPage() ? 'active' : '' }}">
                                {{ $page }}
                            </a>
                        @endfor

                        {{-- Next --}}
                        <a href="{{ $purchases->hasMorePages() ? $paginator->nextPageUrl() : '#' }}"
                            class="page-btn {{ $purchases->hasMorePages() ? '' : 'disabled' }}">
                            Next
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
