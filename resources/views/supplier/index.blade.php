@extends('layouts.app')
@section('title', 'Supplier')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
    <li class="breadcrumb-item active">Supplier</li>
@endsection

@section('content')
    <style>
        :root {
            --maroon: #800000;
            --maroon-dark: #5a0000;
            --maroon-100: #f4e6e6;
        }

        .btn-maroon {
            background: var(--maroon);
            color: #fff;
            border: none;
            border-radius: 12px
        }

        .btn-maroon:hover {
            background: var(--maroon-dark);
            color: #fff
        }

        .btn-outline-maroon {
            background: #fff;
            color: var(--maroon);
            border: 1px solid var(--maroon);
            border-radius: 12px
        }

        .btn-outline-maroon:hover {
            background: var(--maroon-100)
        }

        .table thead th {
            background: var(--maroon);
            color: #fff
        }

        .badge-soft {
            padding: .3rem .6rem;
            border-radius: 8px;
            font-weight: 600
        }

        .badge-soft.success {
            background: rgba(24, 195, 126, .15);
            color: #0e7a4f
        }

        .badge-soft.muted {
            background: rgba(107, 114, 128, .1);
            color: #374151
        }

        .badge-maroon {
            background: var(--maroon-100);
            color: var(--maroon);
            border: 1px solid var(--maroon);
            padding: .2rem .5rem;
            border-radius: 8px;
            font-weight: 600
        }

        .card {
            border-radius: 18px
        }

        th,
        td {
            vertical-align: middle
        }
    </style>

    <div class="container-fluid px-2 px-lg-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 style="color:var(--maroon)">Daftar Supplier</h3>
            <a href="{{ route('suppliers.create') }}" class="btn btn-maroon">+ Tambah Supplier</a>
        </div>

        <form method="get" class="row g-2 mb-3" action="{{ route('suppliers.index') }}">
            <div class="col-md-5">
                <input type="text" class="form-control" name="q" value="{{ $q ?? request('q') }}"
                    placeholder="Cari kode / nama / telepon / email / kota">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100">Cari</button>
            </div>
        </form>

        @if (session('ok'))
            <div class="alert alert-success d-flex align-items-center"><i data-feather="check-circle"
                    class="me-2"></i>{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger d-flex align-items-center"><i data-feather="alert-triangle"
                    class="me-2"></i>{{ implode(', ', $errors->all()) }}</div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:10%">Kode</th>
                                <th style="width:20%">Nama</th>
                                <th style="width:14%">Kontak</th>
                                <th style="width:12%">Telepon</th>
                                <th style="width:16%">Email</th>
                                <th style="width:10%">Kota</th>
                                <th style="width:8%">Status</th>
                                <th style="width:10%" class="text-end">Total Belanja</th>
                                <th style="width:10%">PO Terakhir</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $s)
                                @php
                                    $lastDate = optional($s->last_purchase_date)->format('d M Y');
                                    $totalSpend = number_format($s->total_spend ?? 0, 0, ',', '.');
                                @endphp
                                <tr>
                                    <td>{{ $s->code ?: '—' }}</td>
                                    <td>
                                        <a href="{{ route('suppliers.show', $s) }}"
                                            class="fw-semibold text-decoration-none text-dark">
                                            {{ $s->name }}
                                        </a>
                                    </td>
                                    <td>{{ $s->contact_person ?: '—' }}</td>
                                    <td>{{ $s->phone ?: '—' }}</td>
                                    <td>{{ $s->email ?: '—' }}</td>
                                    <td>{{ $s->city ?: '—' }}</td>
                                    <td>
                                        @if ($s->is_active)
                                            <span class="badge-soft success">Aktif</span>
                                        @else
                                            <span class="badge-soft muted">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ $totalSpend }}</td>
                                    <td>{{ $lastDate ?: '—' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                                            {{-- Tombol Edit --}}
                                            <a href="{{ route('suppliers.edit', $s) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Edit Supplier">
                                                Edit
                                            </a>

                                            {{-- Tombol Hapus --}}
                                            <form action="{{ route('suppliers.destroy', $s) }}" method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus supplier {{ addslashes($s->name) }}?')"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Hapus Supplier">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Belum ada data supplier.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">{{ $rows->links() }}</div>
    </div>

    @push('scripts')
        <script>
            feather.replace();
        </script>
    @endpush
@endsection
