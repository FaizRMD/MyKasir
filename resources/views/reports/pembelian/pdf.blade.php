<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pembelian</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #800020;
        }

        .header h1 {
            color: #800020;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            color: #666;
        }

        .info-box {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .info-box table {
            width: 100%;
        }

        .info-box td {
            padding: 3px 5px;
        }

        .info-box td:first-child {
            font-weight: bold;
            width: 150px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table thead {
            background: #800020;
            color: white;
        }

        table.data-table th {
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        table.data-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }

        table.data-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
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

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #800020;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .summary-box {
            background: #fff3cd;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
            border-left: 4px solid #800020;
        }

        .summary-box table {
            width: 100%;
        }

        .summary-box td {
            padding: 5px;
            font-weight: bold;
        }

        .summary-box td:last-child {
            text-align: right;
            color: #800020;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PEMBELIAN</h1>
        <p>Dicetak pada: {{ now()->format('d F Y H:i') }}</p>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td>Total Transaksi</td>
                <td>: {{ $totals['count'] }} transaksi</td>
            </tr>
            <tr>
                <td>Total Items</td>
                <td>: {{ $totals['total_items'] }} item</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="12%">No. PO</th>
                <th width="12%">No. Invoice</th>
                <th width="10%">Tanggal</th>
                <th width="20%">Supplier</th>
                <th width="15%">Gudang</th>
                <th width="10%">Tipe</th>
                <th width="7%" class="text-center">Items</th>
                <th width="10%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pembelians as $index => $pembelian)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $pembelian->po_no }}</strong></td>
                    <td>{{ $pembelian->invoice_no ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($pembelian->invoice_date)->format('d M Y') }}</td>
                    <td>{{ $pembelian->supplier->name ?? '-' }}</td>
                    <td>{{ $pembelian->warehouse->name ?? '-' }}</td>
                    <td>
                        @php
                            $badgeClass = match($pembelian->payment_type) {
                                'TUNAI' => 'badge-success',
                                'HUTANG' => 'badge-danger',
                                'KONSINYASI' => 'badge-warning',
                                default => 'badge-info'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $pembelian->payment_type }}</span>
                    </td>
                    <td class="text-center">{{ $pembelian->items_count ?? 0 }}</td>
                    <td class="text-right">Rp {{ number_format($pembelian->net_total, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <table>
            <tr>
                <td width="80%">TOTAL KESELURUHAN</td>
                <td width="20%">Rp {{ number_format($totals['total_amount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Diskon</td>
                <td>Rp {{ number_format($totals['total_discount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Pajak</td>
                <td>Rp {{ number_format($totals['total_tax'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Dokumen ini dicetak secara otomatis oleh sistem</p>
        <p>{{ config('app.name') }} - Laporan Pembelian</p>
    </div>
</body>
</html>
