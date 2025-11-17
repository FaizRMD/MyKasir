<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Models\InventoryMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /** Satu-satunya sumber kebenaran daftar golongan obat */
    private const DRUG_CLASSES = ['OTC','Prescription','Narcotic','Herbal','Other'];

    /* =========================
     |  LISTING
     |=========================*/
    public function index(Request $request)
    {
        $q          = $request->string('q')->toString();
        $drugClass  = $request->string('drug_class')->toString();
        $status     = $request->string('status')->toString();
        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;

        $products = Product::query()
            ->with('supplier')
            ->when($q, fn($qq) => $qq->where(fn($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%")) )
            ->when($drugClass && in_array($drugClass, self::DRUG_CLASSES, true), fn($qq) => $qq->where('drug_class', $drugClass))
            ->when($status === 'active', fn($qq) => $qq->where('is_active', true))
            ->when($status === 'inactive', fn($qq) => $qq->where('is_active', false))
            ->when($status === 'lowstock', fn($qq) => $qq->whereColumn('stock', '<=', 'min_stock'))
            ->when($status === 'nostock', fn($qq) => $qq->where('stock', '<=', 0))
            ->when($supplierId, fn($qq) => $qq->where('supplier_id', $supplierId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->pluck('name', 'id');

        return view('products.index', compact('products', 'q', 'drugClass', 'status', 'suppliers', 'supplierId'));
    }

    /* =========================
     |  LOOKUP (untuk POS/sale-items)
     |=========================*/
    public function lookup(Request $request)
    {
        $q         = trim((string) $request->query('q', ''));
        $onlyStock = $request->boolean('in_stock', true);

        if ($q === '') {
            return response()->json(['message' => 'Masukkan nama / SKU / barcode.'], 422);
        }

        $fields = ['id', 'sku', 'name', 'barcode', 'stock', 'is_active'];
        if (Schema::hasColumn('products', 'sell_price')) $fields[] = 'sell_price';
        if (Schema::hasColumn('products', 'buy_price'))  $fields[] = 'buy_price';

        $query = Product::query()->select($fields);

        if ($onlyStock) $query->where('stock', '>', 0);
        if (Schema::hasColumn('products', 'is_active')) $query->where('is_active', true);

        $query->where(function ($w) use ($q) {
            $w->where('barcode', $q)
              ->orWhere('sku', $q)
              ->orWhere('name', 'like', "%{$q}%");
        });

        $product = $query
            ->orderByRaw("CASE WHEN barcode = ? THEN 0 WHEN sku = ? THEN 1 ELSE 2 END", [$q, $q])
            ->orderBy('name')
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan atau stok habis.'], 404);
        }

        $price = 0.0;
        if (isset($product->sell_price) && $product->sell_price !== null) {
            $price = (float) $product->sell_price;
        } elseif (isset($product->buy_price) && $product->buy_price !== null) {
            $price = (float) $product->buy_price;
        }

        return response()->json([
            'data' => [
                'id'    => (int) $product->id,
                'sku'   => $product->sku,
                'name'  => $product->name,
                'price' => $price,
                'stock' => (int) $product->stock,
            ],
        ]);
    }

    /* =========================
     |  CREATE / STORE
     |=========================*/
    public function create()
    {
        $drugClasses = self::DRUG_CLASSES;
        $suppliers   = Supplier::orderBy('name')->pluck('name', 'id');

        $defaults = [
            'pack_name'      => 'box',
            'pack_qty'       => 50,
            'sell_unit'      => 'strip',
            'buy_price_pack' => 0,
            'ppn_percent'    => 11,
            'disc_percent'   => 0,
            'disc_amount'    => 0,
            'sell_price'     => 0,
        ];

        return view('products.create', compact('drugClasses', 'suppliers', 'defaults'));
    }

    public function store(Request $request)
    {
        $usingPack = Product::supportsPackPricing();
        $data      = $this->validateData($request, null, $usingPack);

        // Normalisasi boolean dan angka ringan
        $data['is_active']     = $request->boolean('is_active', true);
        $data['is_medicine']   = $request->boolean('is_medicine', true);
        $data['is_compounded'] = $request->boolean('is_compounded', false);
        $data['stock']         = $data['stock']     ?? 0;
        $data['min_stock']     = $data['min_stock'] ?? 0;

        // Pastikan drug_class tidak NULL (sesuai NOT NULL di DB)
        $data['drug_class'] = $this->resolveDrugClass(
            $data['is_medicine'],
            $data['drug_class'] ?? null
        );

        if ($usingPack) {
            if ($request->filled('ppn_percent')) {
                $data['tax_percent'] = (float) $request->input('ppn_percent', 0);
            }
            if ($request->filled('disc_percent')) {
                $data['discount_percent'] = (float) $request->input('disc_percent', 0);
            }
        }

        // Saring hanya kolom yang ada di DB
        $cols = array_flip(Schema::getColumnListing('products'));
        $data = array_intersect_key($data, $cols);

        $p = new Product($data);

        $sellUnit = (float) ($data['sell_price'] ?? 0);
        if (method_exists($p, 'recalcCostAndMargin')) {
            $p->recalcCostAndMargin($sellUnit);
        }
        $p->save();

        // Stok awal → catat movement
        if ($p->stock > 0 && class_exists(InventoryMovement::class)) {
            InventoryMovement::create([
                'product_id' => $p->id,
                'cashier_id' => auth()->id(),
                'type'       => 'IN',
                'qty'        => (int) $p->stock,
                'reference'  => 'initial-stock',
                'notes'      => 'Stok awal saat create produk',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('products.index')->with('ok', 'Produk berhasil dibuat.');
    }

    /* =========================
     |  SHOW / EDIT / UPDATE / DELETE
     |=========================*/
    public function show(Product $product)
    {
        $product->load('supplier');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $drugClasses = self::DRUG_CLASSES;
        $suppliers   = Supplier::orderBy('name')->pluck('name', 'id');

        return view('products.edit', compact('product', 'drugClasses', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $usingPack = Product::supportsPackPricing();
        $data      = $this->validateData($request, $product->id, $usingPack);

        $data['is_active']     = $request->boolean('is_active', true);
        $data['is_medicine']   = $request->boolean('is_medicine', true);
        $data['is_compounded'] = $request->boolean('is_compounded', false);

        // Pastikan drug_class tidak NULL
        $data['drug_class'] = $this->resolveDrugClass(
            $data['is_medicine'],
            $data['drug_class'] ?? null
        );

        if ($usingPack) {
            if ($request->filled('ppn_percent')) {
                $data['tax_percent'] = (float) $request->input('ppn_percent', 0);
            }
            if ($request->filled('disc_percent')) {
                $data['discount_percent'] = (float) $request->input('disc_percent', 0);
            }
        }

        // Saring hanya kolom yang ada di DB
        $cols = array_flip(Schema::getColumnListing('products'));
        $data = array_intersect_key($data, $cols);

        DB::transaction(function () use ($product, $data) {
            $oldStock = (int) $product->stock;

            $product->fill($data);
            if (method_exists($product, 'recalcCostAndMargin')) {
                $product->recalcCostAndMargin((float) ($product->sell_price ?? 0));
            }
            $product->save();

            // Catat adjustment stok jika berubah
            if (array_key_exists('stock', $data)) {
                $newStock = (int) $product->stock;
                $delta    = $newStock - $oldStock;

                if ($delta !== 0 && class_exists(InventoryMovement::class)) {
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'type'       => $delta > 0 ? 'IN' : 'OUT',
                        'qty'        => abs($delta),
                        'reference'  => 'adjustment:product-update',
                        'notes'      => 'Penyesuaian stok via Edit Produk',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('products.index')->with('ok', 'Produk berhasil diubah.');
    }

    public function destroy(Request $request, Product $product)
    {
        $hasSaleItems = method_exists($product, 'saleItems') ? $product->saleItems()->exists() : false;
        if ($hasSaleItems) {
            return back()->withErrors('Produk sudah dipakai di transaksi sehingga tidak dapat dihapus.');
        }

        $product->delete();
        return back()->with('ok', 'Produk dihapus.');
    }

    /* =========================
     |  EXPORTS
     |=========================*/

    public function exportPdf(\Illuminate\Http\Request $request)
    {
        $rows = Product::with('supplier')->orderBy('name')->get();

        $title   = 'Daftar Produk';
        $filters = [
            'q'          => $request->query('q'),
            'drugClass'  => $request->query('drug_class'),
            'status'     => $request->query('status'),
            'supplierId' => $request->query('supplier_id'),
        ];

        return Pdf::loadView('products.pdf', compact('rows','title','filters'))
            ->setPaper('a4', 'portrait')
            ->download('products_'.now()->format('Ymd_His').'.pdf');
    }

     public function exportXlsx(Request $request)
    {
        $q          = $request->string('q')->toString();
        $drugClass  = $request->string('drug_class')->toString();
        $status     = $request->string('status')->toString();
        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;

        $export = new ProductsExport($q, $drugClass, $status, $supplierId);
        return Excel::download($export, 'products_report.xlsx', ExcelFormat::XLSX);
    }

    public function exportXls(Request $request)
    {
        $q          = $request->string('q')->toString();
        $drugClass  = $request->string('drug_class')->toString();
        $status     = $request->string('status')->toString();
        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;

        $export = new ProductsExport($q, $drugClass, $status, $supplierId);
        return Excel::download($export, 'products_report.xls', ExcelFormat::XLS);
    }



    /* =========================
     |  VALIDASI
     |=========================*/
    private function validateData(Request $request, ?int $productId = null, bool $usingPack = false): array
    {
        $base = [
            'sku'           => ['nullable', 'string', 'max:64'],
            'name'          => ['required', 'string', 'max:255'],
            'category'      => ['nullable', 'string', 'max:128'],
            'unit'          => ['nullable', 'string', 'max:32'],
            'barcode'       => ['nullable', 'string', 'max:128', Rule::unique('products', 'barcode')->ignore($productId)],
            'supplier_id'   => ['nullable', 'exists:suppliers,id'],
            'stock'         => ['nullable', 'integer', 'min:0'],
            'min_stock'     => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
            'is_medicine'   => ['nullable', 'boolean'],
            // Wajib saat obat, dan harus salah satu dari daftar
            'drug_class'    => ['required_if:is_medicine,1', Rule::in(self::DRUG_CLASSES)],
            'is_compounded' => ['nullable', 'boolean'],
        ];

        if ($usingPack) {
            $rules = array_merge($base, [
                'pack_name'      => ['required', 'string', 'max:50'],
                'pack_qty'       => ['required', 'integer', 'min:1'],
                'sell_unit'      => ['required', 'string', 'max:50'],
                'buy_price_pack' => ['required', 'numeric', 'min:0'],
                'ppn_percent'    => ['required', 'numeric', 'min:0'],
                'disc_percent'   => ['nullable', 'numeric', 'min:0'],
                'disc_amount'    => ['nullable', 'numeric', 'min:0'],
                'sell_price'     => ['required', 'numeric', 'min:0'],
                'buy_price'      => ['nullable', 'numeric', 'min:0'],
            ]);
        } else {
            $rules = array_merge($base, [
                'buy_price'   => ['required', 'numeric', 'min:0'],
                'sell_price'  => ['required', 'numeric', 'min:0'],
                'tax_percent' => ['nullable', 'numeric', 'min:0'],
            ]);
        }

        return $request->validate($rules);
    }

    /* =========================
     |  BARANG MASUK (I
     |=========================*/
    public function stockInForm(Product $product)
    {
        return view('products.stock-in', compact('product'));
    }

    public function stockIn(Request $request, Product $product)
    {
        $data = $request->validate([
            'qty'         => ['required', 'integer', 'min:1'],
            'reference'   => ['nullable', 'string', 'max:128'],
            'notes'       => ['nullable', 'string', 'max:255'],
            'batch_no'    => ['nullable', 'string', 'max:64'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($product, $data) {
            $product->increment('stock', (int) $data['qty']);

            if (class_exists(InventoryMovement::class)) {
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'cashier_id' => auth()->id(),
                    'sale_id'    => null,
                    'type'       => 'IN',
                    'qty'        => (int) $data['qty'],
                    'reference'  => $data['reference'] ?? 'manual-add',
                    'notes'      => $data['notes'] ?? null,
                    'batch_no'   => $data['batch_no'] ?? null,
                    'expiry_date'=> $data['expiry_date'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('ok', 'Stok berhasil ditambahkan dan dicatat sebagai Barang Masuk.');
    }

    /* =========================
     |  UTIL
     |=========================*/
    /** Pastikan drug_class tidak NULL sesuai NOT NULL column di DB */
    private function resolveDrugClass(bool $isMedicine, ?string $value): string
    {
        $value = $value ? trim($value) : null;

        if ($isMedicine) {
            // Jika kosong atau tidak valid, fallback OTC
            return in_array($value, self::DRUG_CLASSES, true) ? $value : 'OTC';
        }

        // Bukan obat: jika kolom NOT NULL, gunakan 'Other' sebagai nilai aman
        return in_array($value, self::DRUG_CLASSES, true) ? $value : 'Other';
    }
}
