<?php

namespace App\Http\Controllers;

use App\Models\{Product, ProductBatch, InventoryMovement};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryMovementController extends Controller
{
    public function index(Request $request){
        $q = $request->string('q')->toString();
        $movements = InventoryMovement::with('product')
            ->when($q, fn($qq)=>$qq->whereHas('product', fn($p)=>$p->where('name','like',"%{$q}%")))
            ->latest()->paginate(25)->withQueryString();
        return view('movements.index', compact('movements','q'));
    }

    public function create(){
        $products = Product::orderBy('name')->get();
        return view('movements.create', compact('products'));
    }

    public function store(Request $request){
        $data = $request->validate([
            'product_id'=>['required','exists:products,id'],
            'type'=>['required','in:IN,OUT'],
            'qty'=>['required','integer','min:1'],
            'reference'=>['nullable','string','max:128'],
            'notes'=>['nullable','string'],
            'batch_no'=>['nullable','string','max:64'],
            'expiry_date'=>['nullable','date'],
        ]);
        DB::transaction(function() use ($data){
            $product = Product::findOrFail($data['product_id']);

            // tentukan batch (buat baru jika IN dan tidak ada)
            $batch = null;
            if (!empty($data['batch_no']) || !empty($data['expiry_date'])) {
                $batch = ProductBatch::firstOrCreate([
                    'product_id'=>$product->id,
                    'batch_no'=>$data['batch_no'],
                    'expiry_date'=>$data['expiry_date'] ?? null,
                ], ['qty'=>0, 'buy_price'=>0]);
            } else {
                // fallback: pakai FEFO untuk OUT, atau batch default untuk IN
                if ($data['type']==='OUT') {
                    $batch = $product->batches()->available()->orderByFEFO()->first();
                } else {
                    $batch = ProductBatch::firstOrCreate([
                        'product_id'=>$product->id, 'batch_no'=>null, 'expiry_date'=>null
                    ], ['qty'=>0, 'buy_price'=>0]);
                }
            }

            $qty = (int)$data['qty'];
            if ($data['type']==='IN') {
                if ($batch) $batch->increment('qty',$qty);
                $product->increment('stock',$qty);
            } else {
                // OUT — kurangi FEFO mulai batch terpilih jika ada
                $remaining = $qty;
                $batches = $batch ? collect([$batch]) : $product->batches()->available()->orderByFEFO()->get();
                foreach ($batches as $b) {
                    if ($remaining<=0) break;
                    $take = min($remaining, $b->qty);
                    if ($take>0) { $b->decrement('qty',$take); $remaining -= $take; }
                }
                $product->decrement('stock', $qty);
            }

            InventoryMovement::create($data);
        });
        return redirect()->route('movements.index')->with('ok','Pergerakan stok disimpan.');
    }

    public function edit(InventoryMovement $movement){
        $products = Product::orderBy('name')->get();
        return view('movements.edit', compact('movement','products'));
    }

    public function update(Request $request, InventoryMovement $movement){
        // Demi konsistensi stok, update movement tidak mengubah stok historis.
        $data = $request->validate([
            'reference'=>['nullable','string','max:128'],
            'notes'=>['nullable','string'],
        ]);
        $movement->update($data);
        return redirect()->route('movements.index')->with('ok','Catatan movement diubah (stok tidak diubah).');
    }

    public function destroy(InventoryMovement $movement){
        // Demi konsistensi, penghapusan movement tidak mengotak-atik stok historis (audit).
        $movement->delete();
        return back()->with('ok','Movement dihapus (stok tidak diubah).');
    }
}
