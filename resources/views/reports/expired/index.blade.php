@extends('layouts.app')

@section('title', 'Laporan Obat Expired')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Laporan</li>
    <li class="breadcrumb-item active">Obat Expired</li>
@endsection

@section('content')

    <!-- FIX: CSS supaya ikon pagination tidak membesar -->
    <style>
        /* Ikon pada tombol pagination */
        .pagination-icon,
        .pagination-icon::before {
            font-size: 16px !important;
            width: 16px !important;
            height: 16px !important;
            line-height: 1 !important;
            display: inline-block !important;
        }

        /* Handle khusus ikon bootstrap */
        .bi-chevron-left,
        .bi-chevron-right {
            font-size: 16px !important;
            width: 16px !important;
            height: 16px !important;
        }

        /* Tombol pagination */
        .btn-pagination {
            padding: 4px 12px !important;
            font-size: 14px !important;
        }
    </style>

    <div class="container-fluid">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="bi bi-calendar-x text-danger"></i> Laporan Obat Expired
                </h2>
                <p class="text-muted mb-0">Monitoring obat yang sudah atau akan expired</p>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" onclick="exportReport('excel')">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </button>
                <button type="button" class="btn btn-danger" onclick="exportReport('pdf')">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </button>
            </div>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">

            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Sudah Expired</p>
                            <h3 class="text-danger fw-bold">{{ $stats['total_expired'] }}</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-x-circle text-danger" style="font-size: 1.8rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Akan Expired</p>
                            <h3 class="text-warning fw-bold">{{ $stats['total_will_expire'] }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 1.8rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Masih Aman</p>
                            <h3 class="text-success fw-bold">{{ $stats['total_safe'] }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-check-circle text-success" style="font-size: 1.8rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-dark">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Nilai Kerugian</p>
                            <h3 class="fw-bold">
                                Rp {{ number_format($stats['loss_value'], 0, ',', '.') }}
                            </h3>
                        </div>
                        <div class="bg-dark bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-cash-stack text-dark" style="font-size: 1.8rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-3" method="GET" action="{{ route('reports.expired.index') }}">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="semua" {{ $filterStatus == 'semua' ? 'selected' : '' }}>Semua</option>
                            <option value="expired" {{ $filterStatus == 'expired' ? 'selected' : '' }}>Sudah Expired</option>
                            <option value="akan_expired" {{ $filterStatus == 'akan_expired' ? 'selected' : '' }}>Akan Expired
                            </option>
                            <option value="aman" {{ $filterStatus == 'aman' ? 'selected' : '' }}>Masih Aman</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Rentang Hari</label>
                        <select name="days" class="form-select" onchange="this.form.submit()">
                            <option value="7" {{ $filterDays == 7 ? 'selected' : '' }}>7 Hari</option>
                            <option value="14" {{ $filterDays == 14 ? 'selected' : '' }}>14 Hari</option>
                            <option value="30" {{ $filterDays == 30 ? 'selected' : '' }}>30 Hari</option>
                            <option value="60" {{ $filterDays == 60 ? 'selected' : '' }}>60 Hari</option>
                            <option value="90" {{ $filterDays == 90 ? 'selected' : '' }}>90 Hari</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pencarian</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari produk, kode, batch..." value="{{ $search }}">
                            <button class="btn btn-brand" type="submit"><i class="bi bi-search"></i></button>
                            @if ($search)
                                <a href="{{ route('reports.expired.index') }}" class="btn btn-light-soft">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Produk</th>
                                <th>Batch</th>
                                <th>Tgl Expired</th>
                                <th class="text-center">Hari</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Nilai</th>
                                <th>Invoice</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($items as $index => $item)
                                @php
                                    $daysLeft = $item->days_until_expired;
                                    $statusClass = $daysLeft < 0 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'success');
                                    $statusText =
                                        $daysLeft < 0 ? 'EXPIRED' : ($daysLeft <= 30 ? 'AKAN EXPIRED' : 'AMAN');
                                @endphp
                                <tr>
                                    <td>{{ $items->firstItem() + $index }}</td>
                                    <td>{{ $item->product_code }}</td>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong><br>
                                        <small class="text-muted">{{ $item->supplier_name }}</small>
                                    </td>
                                    <td>{{ $item->batch_no ?: '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->exp_date)->format('d M Y') }}</td>

                                    <td class="text-center">
                                        <span class="badge bg-{{ $statusClass }}">{{ $daysLeft }} hari</span>
                                    </td>

                                    <td class="text-end">{{ number_format($item->qty, 0) }} {{ $item->uom }}</td>

                                    <td class="text-end">
                                        <strong>Rp {{ number_format($item->qty * $item->buy_price, 0, ',', '.') }}</strong>
                                    </td>

                                    <td>
                                        <small>{{ $item->invoice_no }}</small><br>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($item->invoice_date)->format('d/m/Y') }}
                                        </small>
                                    </td>

                                    <td>
                                        <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color:#ccc;"></i>
                                        <p class="text-muted">Tidak ada data</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($items->hasPages())
                    @php $paginator = $items->appends(request()->query()); @endphp

                    <div class="d-flex justify-content-between align-items-center mt-3">

                        <div class="small text-muted">
                            Menampilkan {{ $items->firstItem() }} - {{ $items->lastItem() }}
                            dari {{ $items->total() }} data
                        </div>

                        <div class="btn-group">

                            <a href="{{ $items->onFirstPage() ? '#' : $paginator->previousPageUrl() }}"
                                class="btn btn-outline-secondary btn-sm btn-pagination
                               {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <i class="bi bi-chevron-left pagination-icon"></i> Previous
                            </a>

                            <a href="{{ $items->hasMorePages() ? $paginator->nextPageUrl() : '#' }}"
                                class="btn btn-outline-secondary btn-sm btn-pagination
                               {{ $items->hasMorePages() ? '' : 'disabled' }}">
                                Next <i class="bi bi-chevron-right pagination-icon"></i>
                            </a>

                        </div>

                    </div>
                @endif

            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            function exportReport(format) {
                const params = new URLSearchParams(window.location.search);
                params.set('format', format);
                window.location.href = "{{ route('reports.expired.export') }}?" + params.toString();
            }
        </script>
    @endpush

@endsection
