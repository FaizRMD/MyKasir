<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
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
            ->whereColumn('goods_receipt_items.goods_receipt_id','goods_receipts.id');

        $grnItemValSub = GoodsReceiptItem::query()
            ->selectRaw('COALESCE(SUM(goods_receipt_items.qty * goods_receipt_items.price),0)')
            ->whereColumn('goods_receipt_items.goods_receipt_id','goods_receipts.id');

        $q = GoodsReceipt::query()
            ->with(['supplier:id,name','purchase:id,status'])
            ->select('goods_receipts.*')
            ->addSelect(['total_qty'=>$grnItemQtySub,'total_value'=>$grnItemValSub])
            ->latest('received_at')->latest('id');

        if ($request->filled('date_from')) $q->whereDate('received_at','>=',$request->input('date_from'));
        if ($request->filled('date_to'))   $q->whereDate('received_at','<=',$request->input('date_to'));
        if ($request->filled('supplier')) {
            $supplier = trim($request->input('supplier'));
            $q->whereHas('supplier', fn($s)=>$s->where('name','like',"%{$supplier}%"));
        }
        if ($request->filled('q')) {
            $kw = trim($request->input('q'));
            $q->where(fn($w)=>$w->where('grn_no','like',"%{$kw}%")
                ->orWhere('notes','like',"%{$kw}%")
                ->orWhereHas('purchase',fn($p)=>$p->where('id','like',"%{$kw}%"))
                ->orWhereHas('items.product',fn($pi)=>$pi->where('name','like',"%{$kw}%")));
        }

        $grns = $q->paginate(15)->withQueryString();
        return view('goods-receipts.index', compact('grns'));
    }

    public function show(GoodsReceipt $grn)
    {
        $grn->loadMissing([
            'supplier:id,name,phone',
            'purchase:id,status,supplier_id',
            'items' => fn($q)=>$q->with(['product:id,sku,name'])->orderBy('id'),
        ]);
        $summary = [
            'total_items' => $grn->items->count(),
            'total_qty'   => (int)$grn->items->sum('qty'),
            'total_value' => (float)$grn->items->sum(fn($it)=>(int)$it->qty * (float)$it->price),
        ];
        return view('goods-receipts.show', compact('grn','summary'));
    }

    public function create(Purchase $purchase)
    {
        $allowed = [Purchase::STATUS_ORDERED, Purchase::STATUS_PARTIAL_RECEIVED];
        abort_if(!in_array(strtoupper($purchase->status), $allowed, true), 403, 'PO belum ORDERED atau sudah RECEIVED.');
        $purchase->loadMissing(['supplier','items.product']);
        return view('goods-receipts.create', compact('purchase'));
    }

    public function store(Request $request, Purchase $purchase)
    {
        $allowed = [Purchase::STATUS_ORDERED, Purchase::STATUS_PARTIAL_RECEIVED];
        abort_if(!in_array(strtoupper($purchase->status), $allowed, true), 403, 'Status PO tidak valid untuk penerimaan.');

        $data = $request->validate([
            'received_at'                 => 'required|date',
            'notes'                       => 'nullable|string|max:500',
            'items'                       => 'required|array|min:1',
            'items.*.purchase_item_id'    => 'required|exists:purchase_items,id',
            'items.*.rows'                => 'required|array|min:1',
            'items.*.rows.*.qty'          => 'required|integer|min:1',
            'items.*.rows.*.batch_no'     => 'nullable|string|max:100',
            'items.*.rows.*.exp_date'     => 'nullable|date',
            'items.*.rows.*.price'        => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($purchase, $data) {
                $grn = GoodsReceipt::create([
                    'purchase_id' => $purchase->id,
                    'supplier_id' => $purchase->supplier_id,
                    'received_at' => $data['received_at'],
                    'notes'       => $data['notes'] ?? null,
                ]);

                foreach ($data['items'] as $line) {
                    /** @var PurchaseItem $pi */
                    $pi = PurchaseItem::lockForUpdate()->findOrFail($line['purchase_item_id']);
                    if ((int)$pi->purchase_id !== (int)$purchase->id) {
                        throw ValidationException::withMessages([
                            "items.{$pi->id}" => "Item tidak sesuai dengan PO yang dipilih.",
                        ]);
                    }

                    $totalRecvThisLine = 0;

                    foreach ($line['rows'] as $row) {
                        $qty = (int)$row['qty'];
                        $price = array_key_exists('price',$row) && $row['price'] !== null && $row['price'] !== ''
                            ? (float)$row['price'] : (float)$pi->cost;

                        $outstanding = max(0, (int)$pi->qty - (int)$pi->qty_received - $totalRecvThisLine);
                        if ($qty > $outstanding) {
                            throw ValidationException::withMessages([
                                "items.{$pi->id}" => "Qty terima melebihi sisa (sisa: {$outstanding}).",
                            ]);
                        }

                        GoodsReceiptItem::create([
                            'goods_receipt_id' => $grn->id,
                            'purchase_item_id' => $pi->id,
                            'product_id'       => $pi->product_id,
                            'qty'              => $qty,
                            'price'            => $price,
                            'batch_no'         => $row['batch_no'] ?? null,
                            'exp_date'         => $row['exp_date'] ?? null,
                        ]);

                        Product::whereKey($pi->product_id)->lockForUpdate()->increment('stock', $qty);
                        $totalRecvThisLine += $qty;
                    }

                    if ($totalRecvThisLine > 0) {
                        $pi->increment('qty_received', $totalRecvThisLine);
                    }
                }

                $purchase->load('items:id,purchase_id,qty,qty_received');
                $totalOrdered  = (int)$purchase->items->sum('qty');
                $totalReceived = (int)$purchase->items->sum('qty_received');

                $newStatus = $purchase->status;
                if ($totalReceived === 0) {
                    $newStatus = Purchase::STATUS_ORDERED;
                } elseif ($totalReceived < $totalOrdered) {
                    $newStatus = Purchase::STATUS_PARTIAL_RECEIVED;
                } else {
                    $newStatus = Purchase::STATUS_RECEIVED;
                }

                if ($newStatus !== $purchase->status) {
                    $purchase->update(['status' => $newStatus]);
                }
            });
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->withErrors([
                'general' => 'Gagal menyimpan penerimaan: '.$e->getMessage(),
            ]);
        }

        return redirect()->route('purchases.show', $purchase)->with('success','Penerimaan barang tersimpan dan stok bertambah.');
    }
}
