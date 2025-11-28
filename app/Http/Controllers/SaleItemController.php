<?php

namespace App\Http\Controllers;

use App\Events\DashboardMetricsUpdated;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleItemController extends Controller
{
    /**
     * LIST: tampilkan item untuk sale draft aktif (di session) atau,
     * jika tidak ada, batasi sesuai role:
     *  - kasir: hanya item dari sale miliknya (sale.user_id = auth()->id())
     *  - admin: semua
     */
    public function index(Request $request)
    {
        $q = $request->get('q');
        $saleId = session('pos_current_sale_id');

        $items = SaleItem::with('product', 'sale')
            ->when($saleId, fn($qq) => $qq->where('sale_id', $saleId))
            ->when(!$saleId && auth()->user()?->hasRole('kasir'), function ($qq) {
                $qq->whereHas('sale', fn($s) => $s->where('user_id', auth()->id()));
            })
            ->when($q, function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%"));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $sale = $saleId ? Sale::find($saleId) : null;

        return view('sale_items.index', compact('items', 'q', 'sale'));
    }

    /**
     * Ambil/buat sale draft di session, kembalikan ID-nya.
     * PENTING: tempelkan user_id ke kasir yang login!
     */
    private function currentSaleId(): int
    {
        $sid = session('pos_current_sale_id');
        if ($sid && Sale::whereKey($sid)->exists()) {
            return (int) $sid;
        }

        $sale = new Sale();
        $sale->user_id = auth()->id();
        $sale->invoice_no = 'POS-' . now()->format('Ymd-His') . '-' . strtoupper(str()->random(4));
        // pakai float supaya language server tidak protes "int to decimal"
        $sale->subtotal = 0.0;
        $sale->discount = 0.0;
        $sale->tax = 0.0;
        $sale->grand_total = 0.0;
        $sale->paid = 0.0;
        $sale->change = 0.0;
        $sale->payment_method = 'CASH';
        $sale->notes = 'Draft POS (auto)';
        $sale->save();

        session(['pos_current_sale_id' => $sale->id]);
        return (int) $sale->id;
    }

    /**
     * Hitung ulang subtotal/tax/discount/grand_total dari sale_items
     */
    private function recalcSaleTotals(int $saleId): void
    {
        $items = SaleItem::where('sale_id', $saleId)->get(['qty', 'price', 'tax_percent']);

        $subtotal = 0.0;
        $tax = 0.0;

        foreach ($items as $it) {
            $line = (int) $it->qty * (float) $it->price;
            $subtotal += $line;
            $tax += $line * ((float) ($it->tax_percent ?? 0) / 100);
        }

        $discount = 0.0;
        $grand = $subtotal + $tax - $discount;

        if ($sale = Sale::find($saleId)) {
            $sale->subtotal = (float) $subtotal;
            $sale->tax = (float) $tax;
            $sale->discount = (float) $discount;
            $sale->grand_total = (float) $grand;
            $sale->save();
        }
    }

    /**
     * Lookup produk untuk POS (barcode/SKU/nama).
     */
    public function lookup(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['ok' => false, 'message' => 'Masukkan kata kunci'], 422);
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->where(function ($w) use ($q) {
                $w->where('barcode', $q)
                    ->orWhere('sku', $q)
                    ->orWhere('name', 'like', "%{$q}%");
            })
            ->orderByRaw('CASE WHEN barcode = ? THEN 0 WHEN sku = ? THEN 1 ELSE 2 END', [$q, $q])
            ->orderBy('name')
            ->first();

        if (!$product) {
            return response()->json(['ok' => false, 'message' => 'Produk tidak ditemukan / stok habis'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => (float) ($product->sell_price ?? 0),
                'stock' => (int) ($product->stock ?? 0),
            ],
        ]);
    }

    public function create()
    {
        $products = Product::where('stock', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'stock', 'sell_price']);

        return view('sale_items.create', compact('products'));
    }

    /**
     * Tambah 1 item ke sale draft (atau sale_id yang dikirim),
     * stok otomatis berkurang, totals sale direcalc.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id' => ['nullable', 'exists:sales,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        $saleId = !empty($data['sale_id']) ? (int) $data['sale_id'] : $this->currentSaleId();

        if (empty($data['product_id'])) {
            $ids = Product::where('stock', '>', 0)->pluck('id');
            if ($ids->count() === 1) {
                $data['product_id'] = $ids->first();
            }
        }
        if (empty($data['product_id'])) {
            return back()->withErrors('Pilih produk terlebih dahulu.')->withInput();
        }

        DB::transaction(function () use ($data, $saleId) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            $qty = (int) $data['qty'];
            $price = array_key_exists('price', $data) && $data['price'] !== null
                ? (float) $data['price']
                : (float) ($product->sell_price ?? 0);
            $tax = (float) ($data['tax_percent'] ?? 0);

            if ($product->stock < $qty) {
                abort(422, 'Stok produk tidak mencukupi.');
            }

            $base = $qty * $price;
            $total = $base + ($tax > 0 ? $base * ($tax / 100) : 0);

            SaleItem::create([
                'sale_id' => $saleId,
                'product_id' => $product->id,
                'name' => $product->name,
                'qty' => $qty,
                'price' => $price,
                'tax_percent' => $tax,
                'total' => (float) $total,
            ]);

            $product->decrement('stock', $qty);

            $this->recalcSaleTotals($saleId);
        });

        DashboardMetricsUpdated::dispatch();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'sale_id' => $saleId]);
        }

        return redirect()->route('sale-items.index')->with('ok', 'Item berhasil ditambahkan.');
    }

    public function show(SaleItem $saleItem)
    {
        $saleItem->load('product', 'sale');
        if (auth()->user()?->hasRole('kasir') && optional($saleItem->sale)->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak melihat item milik kasir lain.');
        }
        return view('sale_items.show', compact('saleItem'));
    }

    public function edit(SaleItem $saleItem)
    {
        $saleItem->load('product', 'sale');
        if (auth()->user()?->hasRole('kasir') && optional($saleItem->sale)->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak mengedit item milik kasir lain.');
        }

        $products = Product::where(function ($q) use ($saleItem) {
            $q->where('stock', '>', 0)
                ->orWhere('id', $saleItem->product_id);
        })
            ->orderBy('name')
            ->get(['id', 'name', 'stock', 'sell_price']);

        return view('sale_items.edit', compact('saleItem', 'products'));
    }

    public function update(Request $request, SaleItem $saleItem)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $saleItem) {
            $oldProduct = Product::lockForUpdate()->find($saleItem->product_id);
            $newProduct = Product::lockForUpdate()->findOrFail($data['product_id']);

            $newQty = (int) $data['qty'];
            $newPrice = array_key_exists('price', $data) && $data['price'] !== null
                ? (float) $data['price']
                : (float) ($newProduct->sell_price ?? 0);
            $newTax = (float) ($data['tax_percent'] ?? 0);
            $base = $newQty * $newPrice;
            $newTotal = $base + ($newTax > 0 ? $base * ($newTax / 100) : 0);

            if ($saleItem->product_id == $newProduct->id) {
                $delta = $newQty - $saleItem->qty;
                if ($delta > 0 && $newProduct->stock < $delta) {
                    abort(422, 'Stok produk tidak mencukupi untuk penambahan jumlah.');
                }

                $saleItem->qty = $newQty;
                $saleItem->price = $newPrice;
                $saleItem->tax_percent = $newTax;
                $saleItem->total = (float) $newTotal;
                $saleItem->save();

                if ($delta > 0)
                    $newProduct->decrement('stock', $delta);
                if ($delta < 0)
                    $newProduct->increment('stock', -$delta);
            } else {
                if ($newProduct->stock < $newQty) {
                    abort(422, 'Stok produk baru tidak mencukupi.');
                }

                if ($oldProduct) {
                    $oldProduct->increment('stock', $saleItem->qty);
                }

                $saleItem->product_id = $newProduct->id;
                $saleItem->qty = $newQty;
                $saleItem->price = $newPrice;
                $saleItem->tax_percent = $newTax;
                $saleItem->total = (float) $newTotal;
                $saleItem->save();

                $newProduct->decrement('stock', $newQty);
            }

            $this->recalcSaleTotals($saleItem->sale_id);
        });

        DashboardMetricsUpdated::dispatch();

        return redirect()->route('sale-items.index')->with('ok', 'Item berhasil diperbarui.');
    }

    public function destroy(SaleItem $saleItem)
    {
        if (auth()->user()?->hasRole('kasir') && optional($saleItem->sale)->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak menghapus item milik kasir lain.');
        }

        DB::transaction(function () use ($saleItem) {
            $product = Product::lockForUpdate()->find($saleItem->product_id);
            if ($product) {
                $product->increment('stock', $saleItem->qty);
            }
            $saleId = $saleItem->sale_id;
            $saleItem->delete();

            $this->recalcSaleTotals($saleId);
        });

        DashboardMetricsUpdated::dispatch();

        return redirect()->route('sale-items.index')->with('ok', 'Item berhasil dihapus & stok dikembalikan.');
    }

    /**
     * FINALIZE/Checkout transaksi draft yg ada di session.
     */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $saleId = session('pos_current_sale_id');
        abort_if(!$saleId, 422, 'Tidak ada transaksi aktif.');

        $this->recalcSaleTotals($saleId);

        $sale = Sale::with('items')->findOrFail($saleId);
        abort_if($sale->items->isEmpty(), 422, 'Keranjang masih kosong.');

        $paid = (float) $data['paid'];
        $total = (float) ($sale->grand_total ?? 0.0);
        $change = max(0, $paid - $total);

        $sale->paid = $paid;
        $sale->change = $change;
        $sale->payment_method = $data['payment_method'] ?? 'CASH';
        if (!empty($data['customer_name']))
            $sale->customer_name = $data['customer_name'];
        if (!empty($data['notes']))
            $sale->notes = $data['notes'];
        $sale->save();

        session()->forget('pos_current_sale_id');

        $printUrl = route('kasir.struk', ['sale' => $sale->id, 'print' => 1]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'sale_id' => $sale->id,
                'total' => $total,
                'paid' => $paid,
                'change' => $change,
                'print_url' => $printUrl,
            ]);
        }

        return redirect($printUrl);
    }

    /**
     * Tampilkan struk HTML 58mm dan auto-print (browser print dialog).
     */
    public function printReceipt(Sale $sale, Request $request)
    {
        if (auth()->user()?->hasRole('kasir') && $sale->user_id !== auth()->id()) {
            abort(403);
        }

        $sale->load(['items.product', 'user']);
        $autoPrint = $request->boolean('print', true);

        return view('sale_items.receipt', compact('sale', 'autoPrint'));
    }
}
