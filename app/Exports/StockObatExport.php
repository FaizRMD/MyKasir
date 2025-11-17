<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockObatExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::all();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Obat',
            'SKU',
            'Harga',
            'Stok',
            'Min Stok',
            'Status'
        ];
    }

    public function map($product): array
    {
        static $no = 0;
        $no++;

        $status = $product->stock <= $product->min_stock ? 'Menipis' : 'Aman';

        return [
            $no,
            $product->name,
            $product->sku ?? '-',
            $product->price,
            $product->stock,
            $product->min_stock,
            $status
        ];
    }
}
