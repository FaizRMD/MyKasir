@extends('layouts.app')
@section('content')
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .product-header {
            background: linear-gradient(135deg, #800020 0%, #a0002a 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(128, 0, 32, 0.15);
        }

        .product-title {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            letter-spacing: -0.5px;
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            color: #ffffff;
            font-weight: 500;
            font-size: 1.1rem;
        }

        .stock-number {
            background: rgba(255, 255, 255, 0.3);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 700;
        }

        .movements-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .section-title {
            color: #800020;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 28px;
            background: linear-gradient(180deg, #800020 0%, #a0002a 100%);
            border-radius: 2px;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .modern-table thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
        }

        .modern-table th {
            padding: 1rem 1.25rem;
            text-align: left;
            font-weight: 600;
            color: #800020;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #800020;
        }

        .modern-table th:first-child {
            border-top-left-radius: 12px;
        }

        .modern-table th:last-child {
            border-top-right-radius: 12px;
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f3f5;
        }

        .modern-table tbody tr:hover {
            background: #fef8f9;
            transform: translateX(4px);
        }

        .modern-table td {
            padding: 1rem 1.25rem;
            color: #495057;
        }

        .change-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .change-positive {
            background: #d4edda;
            color: #155724;
        }

        .change-negative {
            background: #f8d7da;
            color: #721c24;
        }

        .type-badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .type-in {
            background: #800020;
            color: #ffffff;
        }

        .type-out {
            background: #6c757d;
            color: #ffffff;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #adb5bd;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .product-detail-container {
                padding: 1rem;
            }

            .product-header {
                padding: 1.5rem;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .modern-table {
                font-size: 0.875rem;
            }

            .modern-table th,
            .modern-table td {
                padding: 0.75rem;
            }
        }
    </style>

    <div class="product-detail-container">
        <div class="product-header">
            <h3 class="product-title">{{ $product->name }}</h3>
            <div class="stock-badge">
                <span>Stok Tersedia:</span>
                <span class="stock-number">{{ $product->stock }}</span>
            </div>
        </div>

        <div class="movements-section">
            <h4 class="section-title">Riwayat Pergerakan Stok</h4>

            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Perubahan</th>
                        <th>Tipe</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->stockMovements as $movement)
                        <tr>
                            <td>{{ $movement->created_at->format('d-m-Y H:i') }}</td>
                            <td>
                                <span
                                    class="change-badge {{ $movement->change > 0 ? 'change-positive' : 'change-negative' }}">
                                    {{ $movement->change > 0 ? '+' : '' }}{{ $movement->change }}
                                </span>
                            </td>
                            <td>
                                <span class="type-badge {{ strtolower($movement->type) == 'in' ? 'type-in' : 'type-out' }}">
                                    {{ $movement->type }}
                                </span>
                            </td>
                            <td>{{ $movement->note ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <div>Belum ada pergerakan stok</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
