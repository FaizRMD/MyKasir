<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private ?string $q;
    private ?string $drugClass;
    private ?string $status;
    private ?int $supplierId;

    public function __construct(?string $q, ?string $drugClass, ?string $status, ?int $supplierId)
    {
        $this->q          = trim((string) $q);
        $this->drugClass  = $drugClass ?: null;
        $this->status     = $status ?: null;
        $this->supplierId = $supplierId ?: null;
    }

    public function query()
    {
        $DRUG_CLASSES = ['OTC','Prescription','Narcotic','Herbal','Other'];

        $q          = $this->q;
        $drugClass  = $this->drugClass;
        $status     = $this->status;
        $supplierId = $this->supplierId;

        return Product::query()
            ->with('supplier')
            ->select(['id','sku','name','barcode','stock','sell_price','buy_price','drug_class','supplier_id','is_active'])
            ->when($q, fn($qq) => $qq->where(fn($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%")))
            ->when($drugClass && in_array($drugClass, $DRUG_CLASSES, true), fn($qq) => $qq->where('drug_class', $drugClass))
            ->when($status === 'active', fn($qq) => $qq->where('is_active', true))
            ->when($status === 'inactive', fn($qq) => $qq->where('is_active', false))
            ->when($status === 'lowstock', fn($qq) => $qq->whereColumn('stock', '<=', 'min_stock'))
            ->when($status === 'nostock', fn($qq) => $qq->where('stock', '<=', 0))
            ->when($supplierId, fn($qq) => $qq->where('supplier_id', $supplierId))
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'ID', 'SKU', 'Nama', 'Barcode', 'Stok',
            'Harga Jual', 'Harga Beli', 'Golongan', 'Supplier', 'Aktif'
        ];
    }

    public function map($p): array
    {
        return [
            (int) $p->id,
            (string) ($p->sku ?? ''),
            (string) ($p->name ?? ''),
            (string) ($p->barcode ?? ''),
            (int) ($p->stock ?? 0),
            (float) ($p->sell_price ?? 0),
            (float) ($p->buy_price ?? 0),
            (string) ($p->drug_class ?? ''),
            optional($p->supplier)->name ?? '',
            $p->is_active ? 'Yes' : 'No',
        ];
    }
}
