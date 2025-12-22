<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PembelianExport implements FromCollection, WithHeadings, WithStyles
{
    protected $pembelians;

    public function __construct($pembelians)
    {
        $this->pembelians = $pembelians;
    }

    public function collection()
    {
        return $this->pembelians->map(function ($pembelian, $index) {
            return [
                'no' => $index + 1,
                'invoice_no' => $pembelian->invoice_no ?? '-',
                'po_no' => $pembelian->po_no ?? '-',
                'invoice_date' => $pembelian->invoice_date?->format('d/m/Y') ?? '-',
                'due_date' => $pembelian->due_date?->format('d/m/Y') ?? '-',
                'supplier' => $pembelian->supplier?->name ?? '-',
                'warehouse' => $pembelian->warehouse?->name ?? '-',
                'items_count' => $pembelian->items_count ?? 0,
                'gross' => $pembelian->gross ?? 0,
                'discount' => $pembelian->discount_total ?? 0,
                'tax' => $pembelian->tax_amount ?? 0,
                'extra_cost' => $pembelian->extra_cost ?? 0,
                'net_total' => $pembelian->net_total ?? 0,
                'payment_type' => $pembelian->payment_type ?? '-',
                'status' => $pembelian->status ?? '-',
                'notes' => $pembelian->notes ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Invoice No',
            'PO No',
            'Tgl Invoice',
            'Tgl Jatuh Tempo',
            'Supplier',
            'Gudang',
            'Jml Item',
            'Gross',
            'Diskon',
            'Pajak',
            'Extra Cost',
            'Total Netto',
            'Tipe Bayar',
            'Status',
            'Catatan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
            ],
        ];
    }
}
