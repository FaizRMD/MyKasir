<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembelianRequest;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PembelianController extends Controller
{
    /**
     * List pembelian.
     */
    public function index()
    {
        $pembelians = Pembelian::with(['supplier', 'warehouse'])
            ->withCount('items')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15);

        // Auto-recalc pembelian lama yang net_total-nya 0 tapi punya item
        $pembelians->getCollection()->transform(function (Pembelian $pembelian) {
            if ((float) $pembelian->net_total === 0.0 && $pembelian->items_count > 0) {
                $pembelian->loadMissing('items');
                $pembelian->recalculateTotalsFromItems(true);
            }
            return $pembelian;
        });

        return view('pembelian.index', compact('pembelians'));
    }

    /**
     * Form buat pembelian baru.
     */
    public function create()
    {
        $suppliers = Supplier::select('id', 'name')
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::select('id', 'name')
            ->when(
                DB::getSchemaBuilder()->hasColumn('warehouses', 'is_active'),
                fn($q) => $q->where('is_active', true)
            )
            ->orderBy('name')
            ->get();

        return view('pembelian.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * AJAX: cari PO yang MASIH BISA dipakai untuk pembelian.
     *
     * Kriteria:
     * - Status: DRAFT / ORDERED / PARTIAL_RECEIVED (open)
     * - Masih punya outstanding qty (qty_received < qty)
     * - BELUM pernah dipakai di tabel pembelian.po_no
     */
    public function searchPO(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = Purchase::query()
            ->with('supplier')
            // status open
            ->whereIn('status', [
                Purchase::STATUS_DRAFT,
                Purchase::STATUS_ORDERED,
                Purchase::STATUS_PARTIAL_RECEIVED,
            ])
            // punya outstanding item
            ->whereHas('items', function ($q) {
                $q->whereColumn('qty_received', '<', 'qty');
            })
            // belum ada di tabel pembelian
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('pembelian')
                    ->whereColumn('pembelian.po_no', 'purchases.po_no');
            })
            ->orderByDesc('po_date')
            ->orderBy('po_no');

        if ($term !== '') {
            $query->where('po_no', 'like', '%' . $term . '%');
        }

        $rows = $query->limit(25)->get()->map(function (Purchase $po) {
            // hitung total outstanding qty dari semua item di PO ini
            $outstanding = $po->items->sum(function (PurchaseItem $it) {
                return max(0, (int) $it->qty - (int) $it->qty_received);
            });

            return [
                'id' => $po->id,
                'po_no' => $po->po_no,
                'po_date' => optional($po->po_date)->format('Y-m-d'),
                'supplier_name' => $po->supplier->name ?? '-',
                'status' => $po->status,
                'outstanding' => $outstanding,
            ];
        });

        return response()->json([
            'data' => $rows,
        ]);
    }

    /**
     * Ambil detail 1 PO untuk di-load ke form Pembelian.
     *
     * Route:
     *  - GET /pembelian/po/{poNo}
     *  - GET /pembelian/get-po/{poNo}
     *
     * Param {poNo} bisa berisi:
     *  - nomor PO (mis: "PO-2025-0001")
     *  - ID numerik (mis: "12")
     */
    public function getPO(string $poNo)
    {
        $poKey = trim($poNo);

        // 1) Coba cari berdasarkan nomor PO
        $po = Purchase::query()
            ->with(['items.product', 'supplier'])
            ->where('po_no', $poKey)
            ->first();

        // 2) Kalau tidak ketemu dan param berupa angka murni → coba cari by id
        if (!$po && ctype_digit($poKey)) {
            $po = Purchase::query()
                ->with(['items.product', 'supplier'])
                ->find((int) $poKey);
        }

        if (!$po) {
            return response()->json([
                'ok' => false,
                'message' => 'PO tidak ditemukan atau tidak valid.',
            ], 404);
        }

        // 3) Cek apakah PO ini SUDAH dipakai di pembelian
        $alreadyUsed = Pembelian::where('po_no', $po->po_no)->exists();
        if ($alreadyUsed) {
            return response()->json([
                'ok' => false,
                'message' => 'PO ini sudah pernah dikonversi menjadi pembelian. Tidak boleh dipakai dua kali.',
            ], 409);
        }

        // 4) Filter item yang masih punya outstanding qty
        $itemsWithOutstanding = $po->items->filter(function (PurchaseItem $item) {
            return $item->qty > $item->qty_received;
        });

        if ($itemsWithOutstanding->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'PO ini tidak punya sisa barang (semua sudah diterima).',
            ], 409);
        }

        // 5) Susun detail item untuk form pembelian
        $items = $itemsWithOutstanding->map(function (PurchaseItem $item) {
            $outQty = (int) $item->qty - (int) $item->qty_received;

            return [
                'product_id' => $item->product_id,
                'code' => $item->product->code ?? '',
                'product_name' => $item->product->name ?? '',
                'qty' => (float) $outQty,
                'uom' => $item->uom ?? 'PCS',
                'buy_price' => (float) $item->cost,
                'disc_percent' => 0,
                'disc_nominal' => (float) ($item->discount ?? 0),
                'batch_no' => null,
                'exp_date' => null,
            ];
        })->values();

        // 6) Response sukses → kirim supplier_id & supplier_name di top-level
        return response()->json([
            'ok' => true,
            'po_no' => $po->po_no,
            'po_date' => optional($po->po_date)->format('Y-m-d'),

            // ⬇️ INI YANG PENTING BUAT AUTOFILL SUPPLIER
            'supplier_id' => $po->supplier_id,
            'supplier_name' => $po->supplier->name ?? '-',

            // kalau nanti ada warehouse di Purchase, bisa disesuaikan
            'warehouse_id' => $po->warehouse_id ?? null,
            'warehouse_name' => $po->warehouse->name ?? '-',

            'items' => $items,
        ]);
    }

    /**
     * AJAX: lookup products untuk form pembelian.
     */
    public function searchProducts(Request $request)
    {
        $search = trim($request->get('q', ''));

        $query = DB::table('products')
            ->select([
                'id',
                DB::raw("COALESCE(code, '') as code"),
                DB::raw("COALESCE(name, '') as name"),
                DB::raw("COALESCE(barcode, '') as barcode"),
                DB::raw("COALESCE(default_uom, uom, unit, satuan, 'PCS') as default_uom"),
                DB::raw("COALESCE(last_buy_price, purchase_price, buy_price, harga_beli, 0) as last_buy_price"),
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->limit(25)->get();

        return response()->json($products);
    }

    /**
     * Simpan pembelian (dari form).
     */
    public function store(StorePembelianRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            Log::info('=== PEMBELIAN STORE START ===', [
                'request_all' => $request->all(),
                'validated' => $validated,
            ]);

            if (empty($validated['items']) || count($validated['items']) === 0) {
                throw new \Exception('Tidak ada items untuk disimpan');
            }

            // Kalau pembelian dari PO → gunakan po_no dari form
            // Kalau pembelian langsung → generate nomor pseudo PO
            $poNo = $validated['po_no'] ?? $this->generatePoNo();

            $totalGross = 0;
            $totalDiscount = 0;
            $itemsToSave = [];

            foreach ($validated['items'] as $idx => $itemData) {
                $qty = (float) ($itemData['qty'] ?? 0);
                $buyPrice = (float) ($itemData['buy_price'] ?? 0);
                $discPercent = (float) ($itemData['disc_percent'] ?? 0);
                $discNominal = (float) ($itemData['disc_nominal'] ?? 0);

                Log::info("Processing item #{$idx}", [
                    'product_id' => $itemData['product_id'] ?? null,
                    'qty' => $qty,
                    'buy_price' => $buyPrice,
                    'disc_percent' => $discPercent,
                    'disc_nominal' => $discNominal,
                ]);

                if ($qty <= 0) {
                    Log::warning("Item #{$idx} skipped: qty is zero");
                    continue;
                }

                if ($buyPrice < 0) {
                    Log::warning("Item #{$idx} skipped: negative price");
                    continue;
                }

                $itemGross = $qty * $buyPrice;
                $discAmountFromPct = ($itemGross * $discPercent) / 100;
                $totalItemDiscount = $discAmountFromPct + $discNominal;
                $subtotal = $itemGross - $totalItemDiscount;
                $hpp = $qty > 0 ? ($subtotal / $qty) : 0;

                Log::info("Item #{$idx} calculated", [
                    'item_gross' => $itemGross,
                    'disc_from_percent' => $discAmountFromPct,
                    'disc_nominal' => $discNominal,
                    'total_item_discount' => $totalItemDiscount,
                    'subtotal' => $subtotal,
                    'hpp' => $hpp,
                ]);

                $totalGross += $itemGross;
                $totalDiscount += $totalItemDiscount;

                $itemsToSave[] = [
                    'product_id' => (int) $itemData['product_id'],
                    'qty' => $qty,
                    'uom' => $itemData['uom'] ?? 'PCS',
                    'buy_price' => $buyPrice,
                    'disc_percent' => $discPercent,
                    'disc_amount' => $discAmountFromPct,
                    'disc_nominal' => $discNominal,
                    'subtotal' => $subtotal,
                    'hpp' => $hpp,
                    'batch_no' => $itemData['batch_no'] ?? null,
                    'exp_date' => !empty($itemData['exp_date']) ? $itemData['exp_date'] : null,
                ];
            }

            if (empty($itemsToSave)) {
                throw new \Exception('Tidak ada items valid untuk disimpan');
            }

            $gross = $totalGross - $totalDiscount;
            $taxPercent = (float) ($validated['tax_percent'] ?? 0);
            $taxAmount = ($gross * $taxPercent) / 100;
            $extraCost = (float) ($validated['extra_cost'] ?? 0);
            $netTotal = $gross + $taxAmount + $extraCost;

            Log::info('=== TOTALS CALCULATED ===', [
                'total_gross' => $totalGross,
                'total_discount' => $totalDiscount,
                'gross' => $gross,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'extra_cost' => $extraCost,
                'net_total' => $netTotal,
            ]);

            $pembelian = Pembelian::create([
                'po_no' => $poNo,
                'invoice_no' => $validated['invoice_no'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'supplier_id' => (int) $validated['supplier_id'],
                'warehouse_id' => !empty($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null,
                'payment_type' => strtoupper($validated['payment_type']),
                'cashbook' => $validated['cashbook'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'extra_cost' => $extraCost,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'gross' => $gross,
                'discount_total' => $totalDiscount,
                'net_total' => $netTotal,
            ]);

            Log::info('Header created successfully', [
                'pembelian_id' => $pembelian->id,
                'po_no' => $pembelian->po_no,
                'net_total' => $pembelian->net_total,
            ]);

            $itemsSaved = 0;

            foreach ($itemsToSave as $itemData) {
                $hnaPpn = 0;
                if ($taxPercent > 0) {
                    $hnaPpn = $itemData['hpp'] * (1 + ($taxPercent / 100));
                }

                $item = PembelianItem::create([
                    'pembelian_id' => $pembelian->id,
                    'product_id' => $itemData['product_id'],
                    'qty' => $itemData['qty'],
                    'uom' => $itemData['uom'],
                    'buy_price' => $itemData['buy_price'],
                    'disc_percent' => $itemData['disc_percent'],
                    'disc_amount' => $itemData['disc_amount'],
                    'disc_nominal' => $itemData['disc_nominal'],
                    'subtotal' => $itemData['subtotal'],
                    'hpp' => $itemData['hpp'],
                    'hna_ppn' => $hnaPpn,
                    'batch_no' => $itemData['batch_no'],
                    'exp_date' => $itemData['exp_date'],
                ]);

                Log::info("Item saved", [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'subtotal' => $item->subtotal,
                ]);

                $itemsSaved++;

                // update last_buy_price di tabel products
                if ($itemData['buy_price'] > 0) {
                    DB::table('products')
                        ->where('id', $itemData['product_id'])
                        ->update([
                            'last_buy_price' => $itemData['buy_price'],
                            'updated_at' => now(),
                        ]);
                }
            }

            $pembelian->refresh();

            Log::info('=== PEMBELIAN STORE SUCCESS ===', [
                'pembelian_id' => $pembelian->id,
                'items_saved' => $itemsSaved,
                'gross' => $pembelian->gross,
                'discount_total' => $pembelian->discount_total,
                'tax_amount' => $pembelian->tax_amount,
                'net_total' => $pembelian->net_total,
            ]);

            DB::commit();

            return redirect()
                ->route('goods-receipts.create', $pembelian->id)
                ->with('success', "✅ Pembelian berhasil disimpan! Silakan lakukan Penerimaan Barang untuk PO {$pembelian->po_no}.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== PEMBELIAN STORE ERROR ===', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menyimpan pembelian: ' . $e->getMessage()]);
        }
    }

    /**
     * Hitung ulang total pembelian dari items.
     */
    public function recalculate($id)
    {
        try {
            DB::beginTransaction();

            $pembelian = Pembelian::with('items')->findOrFail($id);

            Log::info("=== RECALCULATE START ===", ['pembelian_id' => $id]);

            $result = $pembelian->recalculateTotalsFromItems(true);

            Log::info("=== RECALCULATE SUCCESS ===", [
                'pembelian_id' => $id,
                'gross' => $result['gross'],
                'tax_amount' => $result['tax_amount'],
                'net_total' => $result['net_total'],
            ]);

            DB::commit();

            return redirect()
                ->back()
                ->with('success', "✅ Total berhasil dihitung ulang! Total: Rp " . number_format((float) $pembelian->net_total, 0, ',', '.'));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== RECALCULATE ERROR ===', [
                'pembelian_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Gagal menghitung ulang: ' . $e->getMessage());
        }
    }

    /**
     * Generate nomor pseudo PO kalau pembelian tidak berasal dari PO.
     */
    private function generatePoNo(): string
    {
        $prefix = now()->format('Ym');

        $lastPo = Pembelian::where('po_no', 'like', "PO-{$prefix}%")
            ->orderBy('po_no', 'desc')
            ->first();

        $newNumber = $lastPo
            ? (intval(substr($lastPo->po_no, -4)) + 1)
            : 1;

        return "PO-{$prefix}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
