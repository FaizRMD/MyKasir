<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StockObatController extends Controller
{
    /**
     * Display a listing of the resource (index page).
     */
    public function index()
    {
        // Ambil semua produk dengan relasi jika ada
        $products = Product::orderBy('name', 'asc')->get();

        return view('stockobat.index', compact('products'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Ambil satu produk berdasarkan ID
        $product = Product::findOrFail($id);

        return view('stockobat.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($id);

        // Hitung perubahan stok
        $oldStock = $product->stock;
        $newStock = $request->stock;
        $change = $newStock - $oldStock;

        // Update stok
        $product->stock = $newStock;
        $product->save();

        // Catat pergerakan stok (jika ada relasi stockMovements)
        if (method_exists($product, 'stockMovements')) {
            $product->stockMovements()->create([
                'change' => $change,
                'type' => $change > 0 ? 'in' : 'out',
                'note' => 'Manual update stok dari ' . $oldStock . ' menjadi ' . $newStock,
            ]);
        }

        return redirect()->route('stockobat.index')
            ->with('success', 'Stok berhasil diperbarui!');
    }

    /**
     * Display the specified resource (detail page).
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);

        return view('stockobat.show', compact('product'));
    }

    /**
     * Export to PDF.
     */
    public function exportPdf()
    {
        $products = Product::orderBy('name', 'asc')->get();

        // Hitung statistik
        $stats = [
            'total_products' => $products->count(),
            'stock_aman' => $products->filter(fn($p) => $p->stock > ($p->min_stock ?? 0))->count(),
            'stock_menipis' => $products->filter(fn($p) => $p->stock <= ($p->min_stock ?? 0))->count(),
            'total_value' => $products->sum(function($p) {
                return ($p->price ?? 0) * ($p->stock ?? 0);
            }),
        ];

        $pdf = Pdf::loadView('stockobat.pdf', compact('products', 'stats'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        return $pdf->download('laporan-stock-obat-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export to Excel.
     */
    public function exportExcel()
    {
        $products = Product::orderBy('name', 'asc')->get();

        return Excel::download(
            new StockObatExport($products),
            'laporan-stock-obat-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}

/**
 * Export Class untuk Excel
 */
class StockObatExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return $this->products->map(function ($product, $index) {
            $stock = $product->stock ?? 0;
            $minStock = $product->min_stock ?? 0;

            // Tentukan status
            if ($stock <= $minStock) {
                $status = 'Menipis';
            } elseif ($stock <= $minStock * 1.5) {
                $status = 'Perlu Isi';
            } else {
                $status = 'Aman';
            }

            return [
                $index + 1,
                $product->name,
                $product->sku ?? '-',
                $product->category ?? '-',
                $stock,
                $minStock,
                $status,
                $product->price ?? 0,
                ($product->price ?? 0) * $stock, // Nilai total
            ];
        });
    }

    public function headings(): array
    {
        $totalProducts = $this->products->count();
        $stockAman = $this->products->filter(fn($p) => ($p->stock ?? 0) > ($p->min_stock ?? 0))->count();
        $stockMenipis = $this->products->filter(fn($p) => ($p->stock ?? 0) <= ($p->min_stock ?? 0))->count();
        $totalValue = $this->products->sum(fn($p) => ($p->price ?? 0) * ($p->stock ?? 0));

        return [
            ['LAPORAN STOCK OBAT'],
            ['Tanggal Export: ' . now()->format('d M Y H:i')],
            ['Total Produk: ' . $totalProducts . ' | Stok Aman: ' . $stockAman . ' | Stok Menipis: ' . $stockMenipis . ' | Nilai Inventory: Rp ' . number_format($totalValue, 0, ',', '.')],
            [''],
            ['No', 'Nama Obat', 'SKU', 'Kategori', 'Stok', 'Min Stok', 'Status', 'Harga', 'Nilai Total']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style title
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '800020']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]
        ]);

        // Style tanggal export
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Style summary
        $sheet->mergeCells('A3:I3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Style table headers
        $sheet->getStyle('A5:I5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '800020']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ]);

        // Auto height for header row
        $sheet->getRowDimension(5)->setRowHeight(25);

        // Number format for currency columns
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('H6:I' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        // Center align untuk kolom tertentu
        $sheet->getStyle('A6:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E6:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add borders to data
        $sheet->getStyle('A5:I' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Alternate row colors
        for ($row = 6; $row <= $lastRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9F9F9']
                    ]
                ]);
            }
        }

        // Conditional formatting untuk status
        for ($row = 6; $row <= $lastRow; $row++) {
            $statusCell = $sheet->getCell('G' . $row)->getValue();

            if ($statusCell === 'Menipis') {
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFC107'] // Warning color
                    ],
                    'font' => ['bold' => true, 'color' => ['rgb' => '000000']]
                ]);
            } elseif ($statusCell === 'Aman') {
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28A745'] // Success color
                    ],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]
                ]);
            }
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 35,  // Nama Obat
            'C' => 15,  // SKU
            'D' => 20,  // Kategori
            'E' => 10,  // Stok
            'F' => 12,  // Min Stok
            'G' => 15,  // Status
            'H' => 15,  // Harga
            'I' => 18,  // Nilai Total
        ];
    }

    public function title(): string
    {
        return 'Laporan Stock Obat';
    }
}
