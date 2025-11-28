@extends('layouts.app')

@section('content')
    <style>
        .reports-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* SVG DI HALAMAN INI DIKUNCI SUPAYA TIDAK JADI RAKSASA */
        .reports-container svg {
            max-width: 24px;
            max-height: 24px;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }

        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
        }

        .filter-title {
            color: #800020;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #495057;
        }

        .form-control {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #800020;
            box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #800020;
            color: white;
        }

        .btn-primary:hover {
            background: #a0002a;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #800020;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #212529;
        }

        .data-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .data-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
            padding: 1.5rem;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .data-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: linear-gradient(135deg, #800020 0%, #a0002a 100%);
        }

        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: white;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #f1f3f5;
            transition: background 0.2s ease;
        }

        .data-table tbody tr:hover {
            background: #fef8f9;
        }

        .data-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .pagination-wrapper {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .pagination-info {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* ===== CUSTOM PAGINATION TANPA ICON ===== */
        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
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

        /* ======================================== */

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-text {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-recalculate {
            background: #ffc107;
            color: #000;
        }

        .btn-recalculate:hover {
            background: #e0a800;
            color: #000;
        }

        @media (max-width: 768px) {
            .reports-container {
                padding: 1rem;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 0.85rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.75rem 0.5rem;
            }

            .filter-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="reports-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">📊 Laporan Pembelian</h1>
            <p class="page-subtitle">Kelola dan analisis data pembelian barang</p>
        </div>

        <!-- Alert Messages -->
        @if (session('success'))
            <div class="alert alert-success">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.586l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Filter Card -->
        <div class="filter-card">
            <div class="filter-title">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter Pencarian
            </div>

            <form method="GET" action="{{ route('reports.pembelian.index') }}">
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label">Pencarian</label>
                        <input type="text" name="q" class="form-control"
                            placeholder="Cari PO, Invoice, Supplier..." value="{{ request('q') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipe Pembayaran</label>
                        <select name="payment_type" class="form-control">
                            <option value="">Semua Tipe</option>
                            <option value="TUNAI" {{ request('payment_type') == 'TUNAI' ? 'selected' : '' }}>Tunai</option>
                            <option value="HUTANG" {{ request('payment_type') == 'HUTANG' ? 'selected' : '' }}>Hutang
                            </option>
                            <option value="KONSINYASI" {{ request('payment_type') == 'KONSINYASI' ? 'selected' : '' }}>
                                Konsinyasi</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Cari
                    </button>
                    <a href="{{ route('reports.pembelian.index') }}" class="btn btn-secondary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        @if ($pembelians->total() > 0)
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Pembelian</div>
                    <div class="stat-value">{{ number_format($pembelians->total(), 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Items</div>
                    <div class="stat-value">{{ number_format($pembelians->sum('items_count'), 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Nilai</div>
                    <div class="stat-value">Rp {{ number_format($pembelians->sum('net_total'), 0, ',', '.') }}</div>
                </div>
            </div>
        @endif

        <!-- Data Table -->
        <div class="data-card">
            <div class="data-header">
                <h2 class="data-title">Data Pembelian</h2>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="{{ route('reports.pembelian.export.pdf') }}" class="btn btn-danger btn-sm">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z"
                                clip-rule="evenodd" />
                        </svg>
                        Export PDF
                    </a>
                    <a href="{{ route('reports.pembelian.export.excel') }}" class="btn btn-success btn-sm">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        Export Excel
                    </a>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>No. PO</th>
                            <th>No. Invoice</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th style="text-align: center;">Items</th>
                            <th>Tipe Bayar</th>
                            <th style="text-align: right;">Total</th>
                            <th style="text-align: center; width: 200px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pembelians as $index => $pembelian)
                            <tr>
                                <td style="text-align: center;">{{ $pembelians->firstItem() + $index }}</td>
                                <td><strong>{{ $pembelian->po_no }}</strong></td>
                                <td>{{ $pembelian->invoice_no ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($pembelian->invoice_date)->format('d M Y') }}</td>
                                <td><strong>{{ $pembelian->supplier->name ?? '-' }}</strong></td>
                                <td style="text-align: center;">
                                    <span class="badge badge-info">{{ $pembelian->items_count }} items</span>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($pembelian->payment_type) {
                                            'TUNAI' => 'badge-success',
                                            'HUTANG' => 'badge-danger',
                                            'KONSINYASI' => 'badge-warning',
                                            default => 'badge-info',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $pembelian->payment_type }}</span>
                                </td>
                                <td style="text-align: right;">
                                    <strong>Rp {{ number_format($pembelian->net_total, 0, ',', '.') }}</strong>
                                    @if ($pembelian->net_total == 0)
                                        <br><small style="color: #dc3545;">⚠️ Total kosong</small>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <a href="{{ route('reports.pembelian.show', $pembelian->id) }}"
                                        class="btn btn-primary btn-sm">
                                        <svg width="16" height="16" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Detail
                                    </a>
                                    @if ($pembelian->net_total == 0)
                                        <form action="{{ route('pembelian.recalculate', $pembelian->id) }}"
                                            method="POST" style="display: inline-block; margin-left: 0.25rem;">
                                            @csrf
                                            <button type="submit" class="btn btn-recalculate btn-sm"
                                                onclick="return confirm('Hitung ulang total untuk PO ini?')">
                                                <svg width="16" height="16" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Fix
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-icon">📦</div>
                                        <div class="empty-text">Tidak ada data pembelian</div>
                                        <p style="color: #adb5bd; font-size: 0.9rem;">
                                            Silakan ubah filter pencarian atau tambah data pembelian baru
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pembelians->hasPages())
                @php
                    $paginator = $pembelians->appends(request()->query());
                @endphp
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Menampilkan {{ $pembelians->firstItem() }} - {{ $pembelians->lastItem() }}
                        dari {{ $pembelians->total() }} data
                    </div>

                    <div class="pagination-nav">
                        {{-- Previous --}}
                        <a href="{{ $pembelians->onFirstPage() ? '#' : $paginator->previousPageUrl() }}"
                            class="page-btn {{ $pembelians->onFirstPage() ? 'disabled' : '' }}">
                            Previous
                        </a>

                        {{-- Numbered pages --}}
                        @for ($page = 1; $page <= $pembelians->lastPage(); $page++)
                            <a href="{{ $paginator->url($page) }}"
                                class="page-btn {{ $page == $pembelians->currentPage() ? 'active' : '' }}">
                                {{ $page }}
                            </a>
                        @endfor

                        {{-- Next --}}
                        <a href="{{ $pembelians->hasMorePages() ? $paginator->nextPageUrl() : '#' }}"
                            class="page-btn {{ $pembelians->hasMorePages() ? '' : 'disabled' }}">
                            Next
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
