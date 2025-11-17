{{-- resources/views/stockobat/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan Stock Obat')

@section('content')
    <div class="stock-wrapper">
        <div class="container-fluid px-4 py-4">

            {{-- Header --}}
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="page-title">Laporan Stock Obat</h2>
                        <p class="page-subtitle">Monitoring Persediaan Obat</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button class="btn btn-outline-secondary me-2">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="{{ route('stockobat.exportExcel') }}" class="btn btn-success me-2">
                            <i class="bi bi-file-excel"></i> Cetak Excel
                        </a>
                        <a href="{{ route('stockobat.exportPdf') }}" class="btn btn-primary">
                            <i class="bi bi-file-pdf"></i> Cetak PDF
                        </a>
                    </div>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card summary-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="summary-label mb-2">Total Produk</p>
                                    <h3 class="summary-value mb-0">{{ $products->count() }}</h3>
                                </div>
                                <div class="summary-icon bg-primary-subtle">
                                    <i class="bi bi-box-seam text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card summary-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="summary-label mb-2">Stok Aman</p>
                                    @php
                                        $stockAman = $products->filter(fn($p) => $p->stock > $p->min_stock)->count();
                                    @endphp
                                    <h3 class="summary-value mb-0 text-success">{{ $stockAman }}</h3>
                                </div>
                                <div class="summary-icon bg-success-subtle">
                                    <i class="bi bi-check-circle text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card summary-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="summary-label mb-2">Stok Menipis</p>
                                    @php
                                        $lowStock = $products->filter(fn($p) => $p->stock <= $p->min_stock);
                                    @endphp
                                    <h3 class="summary-value mb-0 text-warning">{{ $lowStock->count() }}</h3>
                                </div>
                                <div class="summary-icon bg-warning-subtle">
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card summary-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="summary-label mb-2">Nilai Inventory</p>
                                    @php
                                        $totalValue = $products->sum(function ($p) {
                                            return ($p->price ?? 0) * ($p->stock ?? 0);
                                        });
                                    @endphp
                                    <h3 class="summary-value mb-0">
                                        @if ($totalValue >= 1000000)
                                            Rp {{ number_format($totalValue / 1000000, 1) }}M
                                        @else
                                            Rp {{ number_format($totalValue, 0, ',', '.') }}
                                        @endif
                                    </h3>
                                </div>
                                <div class="summary-icon bg-info-subtle">
                                    <i class="bi bi-currency-dollar text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alert Stok Menipis --}}
            @if ($lowStock->count() > 0)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Perhatian:</strong> {{ $lowStock->count() }} produk dengan stok menipis perlu segera di-restock
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Search Bar --}}
            <div class="card search-bar border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0"
                                    placeholder="Cari nama obat atau SKU..." id="searchInput">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterStatus">
                                <option selected>Semua Status</option>
                                <option value="aman">Stok Aman</option>
                                <option value="menipis">Stok Menipis</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="sortBy">
                                <option selected>Urutkan: A-Z</option>
                                <option value="stock-low">Stok Terendah</option>
                                <option value="stock-high">Stok Tertinggi</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-maroon">
                                <tr>
                                    <th class="text-center" style="width: 60px;">No</th>
                                    <th>Nama Obat</th>
                                    <th style="width: 120px;">SKU</th>
                                    <th class="text-center" style="width: 100px;">Stok</th>
                                    <th class="text-center" style="width: 100px;">Min Stok</th>
                                    <th class="text-center" style="width: 120px;">Status</th>
                                    <th class="text-center" style="width: 180px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $index => $product)
                                    <tr>
                                        <td class="text-center fw-semibold">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="product-name fw-semibold">{{ $product->name }}</div>
                                            @if ($product->category)
                                                <small class="text-muted">{{ $product->category }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark font-monospace">
                                                {{ $product->sku ?? '-' }}
                                            </span>
                                        </td>
                                        
                                        <td class="text-center">
                                            <span
                                                class="badge {{ ($product->stock ?? 0) <= ($product->min_stock ?? 0) ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }} fw-bold px-3 py-2">
                                                {{ $product->stock ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted">{{ $product->min_stock ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $stock = $product->stock ?? 0;
                                                $minStock = $product->min_stock ?? 0;
                                            @endphp
                                            @if ($stock <= $minStock)
                                                <span class="badge bg-warning">Menipis</span>
                                            @elseif($stock <= $minStock * 1.5)
                                                <span class="badge bg-info">Perlu Isi</span>
                                            @else
                                                <span class="badge bg-success">Aman</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('stockobat.show', $product->id) }}"
                                                    class="btn btn-outline-maroon" title="Detail">
                                                    Detail
                                                </a>
                                                <a href="{{ route('stockobat.edit', $product->id) }}"
                                                    class="btn btn-outline-maroon" title="Edit">
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                <p class="mb-0">Tidak ada data produk</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Pagination --}}
            @if ($products->count() > 0)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <p class="text-muted mb-md-0 mb-3">
                                    Menampilkan <strong>{{ $products->count() }}</strong> produk
                                </p>
                            </div>
                            <div class="col-md-6">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-md-end justify-content-center mb-0">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1">
                                                <i class="bi bi-chevron-left"></i> Prev
                                            </a>
                                        </li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item">
                                            <a class="page-link" href="#">
                                                Next <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <style>
        :root {
            --maroon: #800020;
            --maroon-dark: #5c0017;
            --maroon-light: rgba(128, 0, 32, 0.1);
        }

        .stock-wrapper {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Header */
        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--maroon);
            margin: 0;
        }

        .page-subtitle {
            color: #6c757d;
            margin: 0.25rem 0 0 0;
            font-size: 0.95rem;
        }

        /* Summary Cards */
        .summary-card {
            transition: transform 0.2s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .summary-label {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
        }

        .summary-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Table */
        .table-maroon {
            background-color: var(--maroon);
            color: white;
        }

        .table-maroon th {
            font-weight: 600;
            font-size: 0.875rem;
            padding: 1rem;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background-color 0.2s;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table tbody td {
            padding: 1rem;
            font-size: 0.925rem;
            vertical-align: middle;
        }

        .text-maroon {
            color: var(--maroon) !important;
        }

        .btn-outline-maroon {
            color: var(--maroon);
            border-color: var(--maroon);
        }

        .btn-outline-maroon:hover {
            background-color: var(--maroon);
            border-color: var(--maroon);
            color: white;
        }

        /* Custom Badges */
        .badge {
            padding: 0.5rem 0.875rem;
            font-weight: 600;
            font-size: 0.813rem;
        }

        /* Pagination */
        .pagination .page-link {
            color: var(--maroon);
            border-color: #dee2e6;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--maroon);
            border-color: var(--maroon);
        }

        .pagination .page-link:hover {
            background-color: var(--maroon-light);
            border-color: var(--maroon);
            color: var(--maroon);
        }

        /* Search Bar */
        .input-group-text {
            border-color: #dee2e6;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--maroon);
            box-shadow: 0 0 0 0.2rem var(--maroon-light);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header .col-md-6:last-child {
                margin-top: 1rem;
                text-align: left !important;
            }

            .summary-value {
                font-size: 1.5rem;
            }

            .btn-group-sm .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>

    @push('scripts')
        <script>
            // Simple search functionality
            document.getElementById('searchInput')?.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('tbody tr');

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchValue) ? '' : 'none';
                });
            });
        </script>
    @endpush
@endsection
