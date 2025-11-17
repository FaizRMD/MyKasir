@extends('layouts.app')

@section('title', 'Laporan Obat Expired')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Laporan</li>
    <li class="breadcrumb-item active">Obat Expired</li>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Header & Actions -->
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Sudah Expired</p>
                                <h3 class="mb-0 text-danger fw-bold">{{ $stats['total_expired'] }}</h3>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-x-circle text-danger" style="font-size: 1.8rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Akan Expired</p>
                                <h3 class="mb-0 text-warning fw-bold">{{ $stats['total_will_expire'] }}</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 1.8rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Masih Aman</p>
                                <h3 class="mb-0 text-success fw-bold">{{ $stats['total_safe'] }}</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-check-circle text-success" style="font-size: 1.8rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Nilai Kerugian</p>
                                <h3 class="mb-0 fw-bold">Rp {{ number_format($stats['loss_value'], 0, ',', '.') }}</h3>
                            </div>
                            <div class="bg-dark bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-cash-stack text-dark" style="font-size: 1.8rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.expired.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="semua" {{ $filterStatus == 'semua' ? 'selected' : '' }}>Semua</option>
                            <option value="expired" {{ $filterStatus == 'expired' ? 'selected' : '' }}>Sudah Expired
                            </option>
                            <option value="akan_expired" {{ $filterStatus == 'akan_expired' ? 'selected' : '' }}>Akan
                                Expired</option>
                            <option value="aman" {{ $filterStatus == 'aman' ? 'selected' : '' }}>Masih Aman</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Rentang Hari (untuk "Akan Expired")</label>
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
                                placeholder="Cari nama produk, kode, atau batch..." value="{{ $search }}">
                            <button class="btn btn-brand" type="submit">
                                <i class="bi bi-search"></i> Cari
                            </button>
                            @if ($search)
                                <a href="{{ route('reports.expired.index') }}" class="btn btn-light-soft">
                                    <i class="bi bi-x-circle"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Kode</th>
                                <th width="20%">Nama Produk</th>
                                <th width="10%">Batch No</th>
                                <th width="10%">Tgl Expired</th>
                                <th width="10%" class="text-center">Hari Tersisa</th>
                                <th width="8%" class="text-end">Qty</th>
                                <th width="12%" class="text-end">Nilai</th>
                                <th width="10%">Invoice</th>
                                <th width="5%" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $item)
                                @php
                                    $daysLeft = $item->days_until_expired;
                                    $statusClass = $daysLeft < 0 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'success');
                                    $statusText =
                                        $daysLeft < 0 ? 'EXPIRED' : ($daysLeft <= 30 ? 'AKAN EXPIRED' : 'AMAN');
                                    $totalValue = $item->qty * $item->buy_price;
                                @endphp
                                <tr>
                                    <td>{{ $items->firstItem() + $index }}</td>
                                    <td><code class="text-dark">{{ $item->product_code ?? '-' }}</code></td>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        @if ($item->supplier_name)
                                            <br><small class="text-muted">{{ $item->supplier_name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->batch_no)
                                            <span class="badge bg-secondary">{{ $item->batch_no }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($item->exp_date)->format('d M Y') }}</td>
                                    <td class="text-center">
                                        @if ($daysLeft < 0)
                                            <span class="badge bg-danger">
                                                <i class="bi bi-x-circle"></i> Lewat {{ abs($daysLeft) }} hari
                                            </span>
                                        @elseif($daysLeft <= 7)
                                            <span class="badge bg-danger">
                                                <i class="bi bi-exclamation-triangle"></i> {{ $daysLeft }} hari
                                            </span>
                                        @elseif($daysLeft <= 30)
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-clock"></i> {{ $daysLeft }} hari
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> {{ $daysLeft }} hari
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($item->qty, 0) }}</strong> {{ $item->uom }}
                                    </td>
                                    <td class="text-end">
                                        <strong>Rp {{ number_format($totalValue, 0, ',', '.') }}</strong>
                                        <br><small class="text-muted">@ Rp
                                            {{ number_format($item->buy_price, 0, ',', '.') }}</small>
                                    </td>
                                    <td>
                                        @if ($item->invoice_no)
                                            <small>{{ $item->invoice_no }}</small>
                                            <br><small
                                                class="text-muted">{{ \Carbon\Carbon::parse($item->invoice_date)->format('d/m/Y') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">Tidak ada data</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $items->links() }}
                </div>
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
