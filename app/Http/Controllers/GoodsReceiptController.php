<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $grnItemQtySub = GoodsReceiptItem::query()
            ->selectRaw('COALESCE(SUM(goods_receipt_items.qty),0)')
            ->whereColumn('goods_receipt_items.goods_receipt_id', 'goods_receipts.id');

        $grnItemValSub = GoodsReceiptItem::query()
            ->selectRaw('COALESCE(SUM(goods_receipt_items.qty * goods_receipt_items.price),0)')
            ->whereColumn('goods_receipt_items.goods_receipt_id', 'goods_receipts.id');

        $q = GoodsReceipt::query()
            ->with(['supplier:id,name', 'pembelian:id,po_no,status'])
            ->select('goods_receipts.*')
            ->addSelect([
                'total_qty' => $grnItemQtySub,
                'total_value' => $grnItemValSub,
            ])
            ->latest('received_at')
            ->latest('id');

        if ($request->filled('date_from')) {
            $q->whereDate('received_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $q->whereDate('received_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('supplier')) {
            $supplier = trim($request->input('supplier'));
            $q->whereHas('supplier', fn($s) => $s->where('name', 'like', "%{$supplier}%"));
        }

        if ($request->filled('q')) {
            $kw = trim($request->input('q'));
            $q->where(function ($w) use ($kw) {
                $w->where('grn_no', 'like', "%{$kw}%")
                    ->orWhere('notes', 'like', "%{$kw}%")
                    ->orWhereHas('pembelian', fn($p) => $p->where('po_no', 'like', "%{$kw}%"))
                    ->orWhereHas('items.product', fn($pi) => $pi->where('name', 'like', "%{$kw}%"));
            });
        }

        $grns = $q->paginate(15)->withQueryString();

        return view('goods-receipts.index', compact('grns'));
    }

    public function show(GoodsReceipt $grn)
    {
        $grn->loadMissing([
            'supplier:id,name,phone',
            'pembelian:id,po_no,status,supplier_id',
            'items' => fn($q) => $q->with(['product:id,code,name'])->orderBy('id'),
        ]);

        $summary = [
            'total_items' => $grn->items->count(),
            'total_qty' => (float) $grn->items->sum('qty'),
            'total_value' => (float) $grn->items->sum(fn($it) => (float) $it->qty * (float) $it->price),
        ];

        return view('goods-receipts.show', compact('grn', 'summary'));
    }

    public function create(Pembelian $pembelian)
    {
        $pembelian->loadMissing(['supplier', 'items.product']);

        return view('goods-receipts.create', compact('pembelian'));
    }

    public function store(Request $request, Pembelian $pembelian)
    {
        $data = $request->validate([
            'received_at' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.pembelian_item_id' => 'required|exists:pembelian_items,id',
            'items.*.rows' => 'required|array|min:1',
            'items.*.rows.*.qty' => 'required|numeric|min:0.0001',
            'items.*.rows.*.batch_no' => 'nullable|string|max:100',
            'items.*.rows.*.exp_date' => 'nullable|date',
        ]);

        try {
            DB::transaction(function () use ($pembelian, $data) {
                $grn = GoodsReceipt::create([
                    'pembelian_id' => $pembelian->id,
                    'supplier_id' => $pembelian->supplier_id,
                    'received_at' => $data['received_at'],
                    'notes' => $data['notes'] ?? null,
                ]);

                foreach ($data['items'] as $lineIndex => $line) {
                    /** @var PembelianItem $pi */
                    $pi = PembelianItem::lockForUpdate()->findOrFail($line['pembelian_item_id']);

                    if ((int) $pi->pembelian_id !== (int) $pembelian->id) {
                        throw ValidationException::withMessages([
                            "items.{$lineIndex}" => "Item tidak sesuai dengan PO yang dipilih.",
                        ]);
                    }

                    $priceFromPO = (float) $pi->buy_price; // ✅ harga fix dari pembelian

                    foreach ($line['rows'] as $rowIndex => $row) {
                        $qty = (float) $row['qty'];

                        GoodsReceiptItem::create([
                            'goods_receipt_id' => $grn->id,
                            'pembelian_item_id' => $pi->id,
                            'product_id' => $pi->product_id,
                            'qty' => $qty,
                            'price' => $priceFromPO,
                            'batch_no' => $row['batch_no'] ?? null,
                            'exp_date' => $row['exp_date'] ?? null,
                        ]);

                        Product::whereKey($pi->product_id)
                            ->lockForUpdate()
                            ->increment('stock', $qty);
                    }
                }
            });
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Gagal menyimpan penerimaan: ' . $e->getMessage(),
                ]);
        }

        return redirect()
            ->route('reports.pembelian.show', $pembelian->id)
            ->with('success', 'Penerimaan barang tersimpan dan stok bertambah.');
    }
}
