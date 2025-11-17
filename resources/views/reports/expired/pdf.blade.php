<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Obat Expired</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .info-box {
            background: #f5f5f5;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table thead {
            background: #333;
            color: white;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }

        table th {
            font-weight: bold;
            font-size: 10px;
        }

        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
        }

        .badge-warning {
            background: #ffc107;
            color: #000;
        }

        .badge-success {
            background: #28a745;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>LAPORAN OBAT EXPIRED</h2>
        <p>Apotek MyKasir</p>
        <p>{{ $generatedAt }}</p>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td width="20%"><strong>Filter Status:</strong></td>
                <td width="30%">
                    @if ($filterStatus == 'expired')
                        Sudah Expired
                    @elseif($filterStatus == 'akan_expired')
                        Akan Expired
                    @elseif($filterStatus == 'aman')
                        Masih Aman
                    @else
                        Semua
                    @endif
                </td>
                <td width="20%"><strong>Total Item:</strong></td>
                <td width="30%">{{ $items->count() }} item</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Kode</th>
                <th width="22%">Nama Produk</th>
                <th width="10%">Batch</th>
                <th width="10%">Tgl Expired</th>
                <th width="8%">Hari</th>
                <th width="8%">Qty</th>
                <th width="12%">Nilai</th>
                <th width="10%">Invoice</th>
                <th width="5%">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $totalValue = 0; @endphp
            @foreach ($items as $index => $item)
                @php
                    $daysLeft = $item->days_until_expired;
                    $statusClass = $daysLeft < 0 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'success');
                    $statusText = $daysLeft < 0 ? 'EXPIRED' : ($daysLeft <= 30 ? 'AKAN' : 'AMAN');
                    $itemValue = $item->total_value ?? 0;
                    $totalValue += $itemValue;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product_code ?? '-' }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-center">{{ $item->batch_no ?? '-' }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->exp_date)->format('d/m/Y') }}</td>
                    <td class="text-center">
                        @if ($daysLeft < 0)
                            -{{ abs($daysLeft) }}
                        @else
                            {{ $daysLeft }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->qty, 0) }} {{ $item->uom }}</td>
                    <td class="text-right">Rp {{ number_format($itemValue, 0, ',', '.') }}</td>
                    <td>{{ $item->invoice_no ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                </tr>
            @endforeach
            <tr style="background: #f0f0f0; font-weight: bold;">
                <td colspan="7" class="text-right">TOTAL NILAI:</td>
                <td class="text-right">Rp {{ number_format($totalValue, 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini digenerate otomatis oleh sistem MyKasir Apotek</p>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>

</html>
