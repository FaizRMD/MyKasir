@extends('layouts.app')
@section('content')
    <style>
        .index-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left h3 {
            color: #800020;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.5px;
        }

        .header-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border-left: 4px solid;
        }

        .stat-card.total {
            border-left-color: #800020;
        }

        .stat-card.low {
            border-left-color: #dc3545;
        }

        .stat-card.optimal {
            border-left-color: #28a745;
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

        .table-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
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
            padding: 1.25rem 1rem;
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
            padding: 1.25rem 1rem;
            color: #495057;
            vertical-align: middle;
        }

        .row-number {
            color: #adb5bd;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .product-name {
            color: #212529;
            font-weight: 600;
            font-size: 1rem;
        }

        .sku-code {
            display: inline-block;
            background: #f8f9fa;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            color: #6c757d;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .stock-value {
            font-weight: 700;
            font-size: 1.1rem;
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

        .status-optimal {
            background: #d4edda;
            color: #155724;
        }

        .status-optimal .status-indicator {
            background: #28a745;
        }

        .status-low {
            background: #fff3cd;
            color: #856404;
        }

        .status-low .status-indicator {
            background: #ffc107;
        }

        .status-critical {
            background: #f8d7da;
            color: #721c24;
        }

        .status-critical .status-indicator {
            background: #dc3545;
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

        .btn-edit {
            background: #fff4e6;
            color: #e67700;
        }

        .btn-edit:hover {
            background: #ffe8cc;
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

        @media (max-width: 768px) {
            .index-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modern-table {
                font-size: 0.875rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>

    <div class="index-container">
        <div class="page-header">
            <div class="header-left">
                <h3>Manajemen Stok Obat</h3>
                <p class="header-subtitle">Kelola dan pantau stok obat Anda</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('products.create') }}" class="btn btn-primary">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Obat
                </a>
            </div>
        </div>

        @php
            // Hitung statistik dari collection products
            $totalProducts = isset($products) ? $products->count() : 0;
            $lowStock = isset($products)
                ? $products
                    ->filter(function ($p) {
                        $minStock = $p->min_stock ?? 10;
                        return $p->stock <= $minStock && $p->stock > 0;
                    })
                    ->count()
                : 0;
            $optimalStock = isset($products)
                ? $products
                    ->filter(function ($p) {
                        $minStock = $p->min_stock ?? 10;
                        return $p->stock > $minStock;
                    })
                    ->count()
                : 0;
        @endphp

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">Total Produk</div>
                <div class="stat-value">{{ $totalProducts }}</div>
            </div>
            <div class="stat-card low">
                <div class="stat-label">Stok Rendah</div>
                <div class="stat-value">{{ $lowStock }}</div>
            </div>
            <div class="stat-card optimal">
                <div class="stat-label">Stok Optimal</div>
                <div class="stat-value">{{ $optimalStock }}</div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>SKU</th>
                            <th>Stok</th>
                            <th>Min. Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products ?? [] as $index => $product)
                            @php
                                $minStock = $product->min_stock ?? 10;
                            @endphp
                            <tr>
                                <td class="row-number">{{ $index + 1 }}</td>
                                <td>
                                    <div class="product-name">{{ $product->name }}</div>
                                </td>
                                <td>
                                    <span class="sku-code">{{ $product->sku ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="stock-value">{{ $product->stock }}</span>
                                </td>
                                <td>
                                    <span style="color: #6c757d; font-weight: 600;">{{ $minStock }}</span>
                                </td>
                                <td>
                                    @if ($product->stock == 0)
                                        <span class="status-badge status-critical">
                                            <span class="status-indicator"></span>
                                            Habis
                                        </span>
                                    @elseif($product->stock <= $minStock)
                                        <span class="status-badge status-low">
                                            <span class="status-indicator"></span>
                                            Rendah
                                        </span>
                                    @else
                                        <span class="status-badge status-optimal">
                                            <span class="status-indicator"></span>
                                            Optimal
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('stockobat.show', $product->id) }}" class="btn-action btn-view">
                                            <svg width="16" height="16" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Detail
                                        </a>
                                        <a href="{{ route('stockobat.edit', $product->id) }}" class="btn-action btn-edit">
                                            <svg width="16" height="16" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <div style="font-size: 1.1rem; margin-bottom: 0.5rem;">Belum ada data produk</div>
                                    <div>Mulai tambahkan produk obat untuk mengelola stok</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
