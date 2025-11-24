<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembelianRequest;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PembelianController extends Controller
{
    public function index()
    {
        $pembelians = Pembelian::with(['supplier', 'warehouse'])
            ->withCount('items')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15);

        $pembelians->getCollection()->transform(function (Pembelian $pembelian) {
            if ((float) $pembelian->net_total === 0.0 && $pembelian->items_count > 0) {
                $pembelian->loadMissing('items');
                $pembelian->recalculateTotalsFromItems(true);
            }
            return $pembelian;
        });

        return view('pembelian.index', compact('pembelians'));
    }

    public function create()
    {
        $suppliers = Supplier::select('id', 'name')->orderBy('name')->get();
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

    public function searchPO(Request $request)
    {
        $search = trim($request->get('q', ''));

        $query = Pembelian::query()
            ->select(['id', 'po_no', 'invoice_date as po_date', 'status', 'supplier_id', 'warehouse_id'])
            ->with(['supplier:id,name', 'warehouse:id,name'])
            ->whereNotNull('po_no');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $query->whereIn('status', ['draft', 'ordered', 'approved', 'partial']);

        $results = $query->orderByDesc('invoice_date')
            ->limit(20)
            ->get()
            ->map(function ($pembelian) {
                return [
                    'po_no' => $pembelian->po_no,
                    'po_date' => $pembelian->po_date,
                    'status' => $pembelian->status ?? 'draft',
                    'supplier_id' => $pembelian->supplier_id,
                    'supplier_name' => $pembelian->supplier->name ?? '-',
                    'warehouse_id' => $pembelian->warehouse_id,
                    'warehouse_name' => $pembelian->warehouse->name ?? '-',
                ];
            });

        return response()->json($results);
    }

    public function getPO(string $poNo)
    {
        $pembelian = Pembelian::with(['items.product', 'supplier', 'warehouse'])
            ->where('po_no', trim($poNo))
            ->first();

        if (!$pembelian) {
            return response()->json(['message' => 'PO tidak ditemukan'], 404);
        }

        if (in_array(strtolower($pembelian->status ?? ''), ['received', 'cancelled', 'completed'])) {
            return response()->json(['message' => 'PO sudah selesai/dibatalkan'], 409);
        }

        $items = $pembelian->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'code' => $item->product->code ?? '',
                'product_name' => $item->product->name ?? '',
                'qty' => (float) $item->qty,
                'uom' => $item->uom,
                'buy_price' => (float) $item->buy_price ?: 0,
                'disc_percent' => (float) $item->disc_percent ?: 0,
                'disc_nominal' => (float) $item->disc_nominal ?: 0,
                'batch_no' => $item->batch_no,
                'exp_date' => $item->exp_date?->format('Y-m-d'),
            ];
        });

        return response()->json([
            'po_no' => $pembelian->po_no,
            'supplier_id' => $pembelian->supplier_id,
            'warehouse_id' => $pembelian->warehouse_id,
            'po_date' => $pembelian->invoice_date ? \Carbon\Carbon::parse($pembelian->invoice_date)->format('Y-m-d') : null,
            'items' => $items,
        ]);
    }

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

    public function store(StorePembelianRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            Log::info('=== PEMBELIAN STORE START ===', [
                'request_all' => $request->all(),
                'validated' => $validated,
            ]);

            if (empty($validated['items']) || count($validated['items']) == 0) {
                throw new \Exception('Tidak ada items untuk disimpan');
            }

            $poNo = $validated['po_no'] ?? $this->generatePoNo();

            $totalGross = 0;
            $totalDiscount = 0;
            $itemsToSave = [];

            foreach ($validated['items'] as $idx => $itemData) {
                $qty = floatval($itemData['qty'] ?? 0);
                $buyPrice = floatval($itemData['buy_price'] ?? 0);
                $discPercent = floatval($itemData['disc_percent'] ?? 0);
                $discNominal = floatval($itemData['disc_nominal'] ?? 0);

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
                $discAmountFromPercent = ($itemGross * $discPercent) / 100;
                $totalItemDiscount = $discAmountFromPercent + $discNominal;
                $subtotal = $itemGross - $totalItemDiscount;
                $hpp = $qty > 0 ? ($subtotal / $qty) : 0;

                Log::info("Item #{$idx} calculated", [
                    'item_gross' => $itemGross,
                    'disc_from_percent' => $discAmountFromPercent,
                    'disc_nominal' => $discNominal,
                    'total_item_discount' => $totalItemDiscount,
                    'subtotal' => $subtotal,
                    'hpp' => $hpp,
                ]);

                $totalGross += $itemGross;
                $totalDiscount += $totalItemDiscount;

                $itemsToSave[] = [
                    'product_id' => intval($itemData['product_id']),
                    'qty' => $qty,
                    'uom' => $itemData['uom'] ?? 'PCS',
                    'buy_price' => $buyPrice,
                    'disc_percent' => $discPercent,
                    'disc_amount' => $discAmountFromPercent,
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
            $taxPercent = floatval($validated['tax_percent'] ?? 0);
            $taxAmount = ($gross * $taxPercent) / 100;
            $extraCost = floatval($validated['extra_cost'] ?? 0);
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
                'supplier_id' => intval($validated['supplier_id']),
                'warehouse_id' => !empty($validated['warehouse_id']) ? intval($validated['warehouse_id']) : null,
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

            // ➜ setelah pembelian, langsung ke Penerimaan Barang
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

    private function generatePoNo(): string
    {
        $prefix = now()->format('Ym');

        $lastPo = Pembelian::where('po_no', 'like', "PO-{$prefix}%")
            ->orderBy('po_no', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = intval(substr($lastPo->po_no, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "PO-{$prefix}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
