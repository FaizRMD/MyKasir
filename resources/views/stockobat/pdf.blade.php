<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Stock Obat</title>
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
            display: table;
            width: 100%;
        }

        .info-item {
            display: inline-block;
            width: 24%;
            padding: 5px;
            text-align: center;
        }

        .info-label {
            font-size: 9px;
            color: #666;
            display: block;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 14px;
            font-weight: bold;
            color: #800020;
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
            background: #28a745;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: #000;
        }

        .badge-info {
            background: #17a2b8;
            color: white;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #800020;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .alert-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .alert-box strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN STOCK OBAT</h1>
        <p>Dicetak pada: {{ now()->format('d F Y H:i') }}</p>
    </div>

    <div class="info-box">
        <div class="info-item">
            <span class="info-label">Total Produk</span>
            <span class="info-value">{{ $stats['total_products'] }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Stok Aman</span>
            <span class="info-value" style="color: #28a745;">{{ $stats['stock_aman'] }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Stok Menipis</span>
            <span class="info-value" style="color: #ffc107;">{{ $stats['stock_menipis'] }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Nilai Inventory</span>
            <span class="info-value">Rp {{ number_format($stats['total_value'], 0, ',', '.') }}</span>
        </div>
    </div>

    @if($stats['stock_menipis'] > 0)
        <div class="alert-box">
            <strong>⚠ Perhatian:</strong> {{ $stats['stock_menipis'] }} produk dengan stok menipis perlu segera di-restock
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="25%">Nama Obat</th>
                <th width="12%">SKU</th>
                <th width="15%">Kategori</th>
                <th width="8%" class="text-center">Stok</th>
                <th width="8%" class="text-center">Min</th>
                <th width="10%" class="text-center">Status</th>
                <th width="10%" class="text-right">Harga</th>
                <th width="10%" class="text-right">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $product)
                @php
                    $stock = $product->stock ?? 0;
                    $minStock = $product->min_stock ?? 0;
                    $price = $product->price ?? 0;
                    $totalValue = $price * $stock;

                    if ($stock <= $minStock) {
                        $status = 'Menipis';
                        $badgeClass = 'badge-warning';
                    } elseif ($stock <= $minStock * 1.5) {
                        $status = 'Perlu Isi';
                        $badgeClass = 'badge-info';
                    } else {
                        $status = 'Aman';
                        $badgeClass = 'badge-success';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>{{ $product->sku ?? '-' }}</td>
                    <td>{{ $product->category ?? '-' }}</td>
                    <td class="text-center"><strong>{{ $stock }}</strong></td>
                    <td class="text-center">{{ $minStock }}</td>
                    <td class="text-center">
                        <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                    </td>
                    <td class="text-right">Rp {{ number_format($price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalValue, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini dicetak secara otomatis oleh sistem</p>
        <p>{{ config('app.name', 'Apotek') }} - Laporan Stock Obat</p>
    </div>
</body>
</html>
