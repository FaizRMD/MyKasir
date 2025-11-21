@extends('layouts.app')
@section('title', 'Laporan Penjualan')

@push('styles')
    <style>
        :root {
            --maroon-700: #8d1b1b;
            --maroon-800: #6d1313;
            --ink: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --soft: #f9fafb;
            --radius: 12px;
        }

        /* kecilkan SVG pada pagination */
        .page-item svg,
        nav svg {
            width: 16px !important;
            height: 16px !important;
        }


        body {
            background: var(--soft);
            color: var(--ink);
        }

        .page {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px
        }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius)
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0
        }

        .table th,
        .table td {
            padding: 10px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top
        }

        .table thead th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--ink)
        }

        .grid {
            display: grid;
            gap: 10px
        }

        .grid-6 {
            grid-template-columns: repeat(6, 1fr)
        }

        @media(max-width:1024px) {
            .grid-6 {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        /* INPUT FIELD FIX */
        input,
        select {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 10px;
            color: var(--ink);
            font-size: 14px;
            width: 100%;
            outline: none;
            appearance: auto;
        }

        input::placeholder {
            color: var(--muted);
        }

        input:focus,
        select:focus {
            border-color: var(--maroon-700);
            box-shadow: 0 0 0 1px var(--maroon-700);
        }

        .summary {
            display: flex;
            gap: 12px;
            flex-wrap: wrap
        }

        .pill {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 6px 10px
        }

        .muted {
            color: var(--muted)
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600
        }

        .btn:hover {
            filter: brightness(.97)
        }

        .btn-ghost {
            background: #fff;
            color: var(--ink)
        }

        .btn-brand {
            background: var(--maroon-700);
            border-color: var(--maroon-700);
            color: #fff
        }

        .btn-brand:hover {
            background: var(--maroon-800);
            border-color: var(--maroon-800)
        }

        /* header tabs */
        .tabs {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px
        }

        .tablist {
            display: flex;
            gap: 8px;
            flex-wrap: wrap
        }

        .tab {
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            text-decoration: none;
            font-weight: 600
        }

        .tab.active {
            background: #fdeeee;
            border-color: #f3c7c7;
            color: var(--maroon-700)
        }
    </style>
@endpush

@section('content')
    @php
        $isItems = request()->routeIs('reports.sales.items');
        $exportUrl = $isItems
            ? route('reports.sales.items_export', request()->query())
            : route('reports.sales.export', request()->query());
    @endphp

    <div class="tabs">
        <div class="tablist">
            <a class="tab {{ $isItems ? '' : 'active' }}" href="{{ route('reports.sales.index', request()->query()) }}">
                Laporan Penjualan (Transaksi)
            </a>
            <a class="tab {{ $isItems ? 'active' : '' }}" href="{{ route('reports.sales.items', request()->query()) }}">
                Laporan Penjualan (Item/Obat)
            </a>
        </div>

        <div class="export-actions">
            <a class="btn btn-brand" href="{{ $exportUrl }}">Ekspor CSV</a>
            <a class="btn btn-brand"
                href="{{ $isItems
                    ? route('reports.sales.items_export.pdf', request()->query())
                    : route('reports.sales.export.pdf', request()->query()) }}">
                Ekspor PDF
            </a>
        </div>
    </div>



    {{-- FILTER --}}
    <form method="GET" class="grid grid-6" style="margin-bottom:12px">
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" placeholder="Dari tanggal">
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" placeholder="Sampai tanggal">
        <input type="number" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="ID Kasir">
        <input type="number" name="customer_id" value="{{ $filters['customer_id'] ?? '' }}" placeholder="ID Customer">
        <input type="text" name="payment_method" value="{{ $filters['payment_method'] ?? '' }}"
            placeholder="Metode Bayar (Tunai/Debit/QR...)">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
            placeholder="Cari invoice/customer/kasir...">

        <div style="grid-column:1/-1;display:flex;gap:8px;align-items:center">
            <select name="sort">
                <option value="sale_date_desc" @selected(($filters['sort'] ?? '') === 'sale_date_desc')>Tanggal ↓</option>
                <option value="sale_date_asc" @selected(($filters['sort'] ?? '') === 'sale_date_asc')>Tanggal ↑</option>
                <option value="grand_total_desc" @selected(($filters['sort'] ?? '') === 'grand_total_desc')>Grand Total ↓</option>
                <option value="grand_total_asc" @selected(($filters['sort'] ?? '') === 'grand_total_asc')>Grand Total ↑</option>
            </select>

            <button class="btn btn-brand" type="submit">Terapkan</button>
            <a class="btn btn-ghost" href="{{ route('reports.sales.index') }}">Reset</a>

            {{-- rentang cepat --}}
            @php
                $today = \Carbon\Carbon::today()->format('Y-m-d');
                $mStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
                $mEnd = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
                $wStart = \Carbon\Carbon::now()->startOfWeek()->format('Y-m-d');
                $wEnd = \Carbon\Carbon::now()->endOfWeek()->format('Y-m-d');
            @endphp
            <span class="muted ms-auto">Rentang cepat:</span>
            <a class="pill"
                href="{{ route('reports.sales.index', array_merge(request()->except(['date_from', 'date_to']), ['date_from' => $today, 'date_to' => $today])) }}">Hari
                ini</a>
            <a class="pill"
                href="{{ route('reports.sales.index', array_merge(request()->except(['date_from', 'date_to']), ['date_from' => $wStart, 'date_to' => $wEnd])) }}">Minggu
                ini</a>
            <a class="pill"
                href="{{ route('reports.sales.index', array_merge(request()->except(['date_from', 'date_to']), ['date_from' => $mStart, 'date_to' => $mEnd])) }}">Bulan
                ini</a>
        </div>
    </form>

    {{-- RINGKASAN --}}
    <div class="summary" style="margin-bottom:12px">
        <div class="pill">Transaksi: <strong>{{ number_format($summary->trx_count, 0, ',', '.') }}</strong></div>
        <div class="pill">Qty Item: <strong>{{ number_format($summary->qty_sum, 0, ',', '.') }}</strong></div>
        <div class="pill">Diskon: <strong>Rp {{ number_format($summary->discount_sum, 0, ',', '.') }}</strong></div>
        <div class="pill">Pajak: <strong>Rp {{ number_format($summary->tax_sum, 0, ',', '.') }}</strong></div>
        <div class="pill">Grand Total: <strong>Rp {{ number_format($summary->grand_total_sum, 0, ',', '.') }}</strong></div>
    </div>

    {{-- TABEL --}}
    <div class="card" style="overflow:auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Kasir</th>
                    <th>Metode</th>
                    <th>Jml Item</th>
                    <th>Subtotal Item</th>
                    <th>Diskon</th>
                    <th>Pajak</th>
                    <th>Grand Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $r)
                    <tr>
                        <td>{{ optional($r->sale_date)->format('Y-m-d H:i') }}</td>
                        <td><code>{{ $r->invoice_no }}</code></td>
                        <td>{{ $r->customer_name ?? '-' }}</td>
                        <td>{{ $r->cashier_name ?? '-' }}</td>
                        <td>{{ $r->payment_method ?? '-' }}</td>
                        <td>{{ (int) $r->items_count }}</td>
                        <td>Rp {{ number_format($r->items_total ?? 0, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($r->discount_total ?? 0, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($r->tax_total ?? 0, 0, ',', '.') }}</td>
                        <td><strong>Rp {{ number_format($r->grand_total ?? 0, 0, ',', '.') }}</strong></td>
                        <td>
                            <a class="btn btn-ghost" href="{{ route('reports.sales.show', $r->sale_id) }}">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="muted" style="text-align:center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:12px">
        {{ $sales->links() }}
    </div>
    </div>
@endsection
