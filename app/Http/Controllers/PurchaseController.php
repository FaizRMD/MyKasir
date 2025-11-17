<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Apoteker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\PurchaseOrderMail;
use App\Services\RunningNumber;

class PurchaseController extends Controller
{
    /** INDEX — daftar PO dengan filter & pencarian */
    public function index(Request $request)
    {
        $q = Purchase::with(['supplier','apoteker'])
            ->withCount('items')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $kw = trim($request->q);
            $q->where(function ($w) use ($kw) {
                $w->where('po_no', 'like', "%{$kw}%")
                  ->orWhere('note', 'like', "%{$kw}%")
                  ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$kw}%"));
            });
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('from')) $q->whereDate('po_date', '>=', $request->from);
        if ($request->filled('to'))   $q->whereDate('po_date', '<=', $request->to);

        $purchases = $q->paginate(10)->withQueryString();

        return view('purchases.index', compact('purchases'));
    }

    /** CREATE — tampilkan form */
    public function create(Request $request)
    {
        // supplier (aman jika tidak ada kolom is_active)
        $suppliers = Supplier::select('id','name')
            ->when(schema()->hasColumn('suppliers','is_active'), fn($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        $apotekers = class_exists(Apoteker::class)
            ? Apoteker::select('id','name')->orderBy('name')->get()
            : collect();

        // produk untuk picker/JS
        $products = Product::select('id','code','name','uom','purchase_price')->orderBy('name')->get();

        $productsForJs = $products->map(fn($p)=>[
            'id'           => $p->id,
            'code'         => $p->code ?? '',
            'name'         => $p->name,
            'uom'          => $p->uom ?? 'pcs',
            'default_cost' => (float) ($p->purchase_price ?? 0),
        ])->values();

        // pilihan dropdown
        $poCategories = ['Reguler','Prekursor','Narkotika','Psikotropika','Obat-obat tertentu'];
        $printOptions = [
            'STRUK_58'      => 'Struk Kecil 58m',
            'STRUK_76'      => 'Struk Kecil 76m',
            'INV_A5'        => 'Invoice A5',
            'INV_A4'        => 'Invoice A4',
            'PREKURSOR'     => 'Prekursor',
            'NARKOTIKA'     => 'Narkotika',
            'PSIKOTROPIKA'  => 'Psikotropika',
            'OBT_TERTENTU'  => 'Obat-obatan tertentu',
        ];

        $selectedSupplier = $request->get('supplier_id');

        return view('purchases.create', compact(
            'suppliers',
            'apotekers',
            'productsForJs',
            'poCategories',
            'printOptions',
            'selectedSupplier'
        ));
    }

    /** STORE — simpan PO (draft atau submit) */
    public function store(Request $request)
    {
        // Samakan daftar category & print type dengan yang di-create()
        $categories = ['Reguler','Prekursor','Narkotika','Psikotropika','Obat-obat tertentu'];
        $printTypes = ['STRUK_58','STRUK_76','INV_A5','INV_A4','PREKURSOR','NARKOTIKA','PSIKOTROPIKA','OBT_TERTENTU'];

        $data = $request->validate([
            'supplier_id'       => ['required','exists:suppliers,id'],
            'po_date'           => ['required','date'],
            'type'              => ['required','in:NON KONSINYASI,KONSINYASI'],
            'category'          => ['required','in:'.implode(',', $categories)],
            'print_type'        => ['required','in:'.implode(',', $printTypes)],
            'note'              => ['nullable','string','max:500'],
            'apoteker_id'       => ['nullable','integer','exists:apotekers,id'],

            'items'                 => ['required','array','min:1'],
            'items.*.product_id'    => ['required','exists:products,id'],
            'items.*.qty'           => ['required','integer','min:1'],
            'items.*.uom'           => ['nullable','string','max:20'],
            'items.*.cost'          => ['required','numeric','min:0'],
            'items.*.discount'      => ['nullable','numeric','min:0'],
            'items.*.tax_pct'       => ['nullable','numeric','min:0'],

            'send_email'        => ['nullable','boolean'],
            'email_note'        => ['nullable','string','max:1000'],
            'action'            => ['nullable','in:save,submit'],
        ]);

        $purchase = DB::transaction(function () use ($request, $data) {
            // Running number
            $poNo = $this->nextPoNo('PO', 'purchases', 'po_no', $data['po_date'] ?? now()->toDateString());

            /** @var \App\Models\Purchase $purchase */
            $purchase = Purchase::create([
                'po_no'        => $poNo,
                'po_date'      => $data['po_date'],
                'supplier_id'  => $data['supplier_id'],
                'apoteker_id'  => $data['apoteker_id'] ?? null,
                'type'         => $data['type'],
                'category'     => $data['category'],
                'print_type'   => $data['print_type'],
                'note'         => $data['note'] ?? null,
                'status'       => Purchase::STATUS_DRAFT,
                'total'        => 0,
                'user_id'      => $request->user()->id ?? null,
            ]);

            // Detail items & total
            $grand = 0;
            foreach ($data['items'] as $row) {
                $qty      = (int) $row['qty'];
                $cost     = (float) $row['cost'];
                $discount = (float) ($row['discount'] ?? 0);
                $taxPct   = (float) ($row['tax_pct'] ?? 0);
                $uom      = $row['uom'] ?? null;

                $beforeTax = max(0, $qty * $cost - $discount);
                $tax       = $beforeTax * ($taxPct / 100);
                $subtotal  = $beforeTax + $tax;

                PurchaseItem::create([
                    'purchase_id'  => $purchase->id,
                    'product_id'   => $row['product_id'],
                    'qty'          => $qty,
                    'uom'          => $uom,
                    'cost'         => $cost,
                    'discount'     => $discount,
                    'tax_pct'      => $taxPct,
                    'subtotal'     => $subtotal,
                    'qty_received' => 0,
                ]);

                $grand += $subtotal;
            }

            $purchase->update(['total' => $grand]);

            if (($data['action'] ?? 'save') === 'submit') {
                $purchase->update(['status' => Purchase::STATUS_ORDERED]);
            }

            return $purchase->load(['supplier','apoteker','items.product']);
        });

        // (opsional) kirim email PO
        if ($request->boolean('send_email') && !empty($purchase->supplier?->email)) {
            try {
                Mail::to($purchase->supplier->email)
                    ->send(new PurchaseOrderMail($purchase, $request->input('email_note')));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // >>> TIDAK redirect ke GRN & TIDAK langsung buka cetak.
        // Kirim flash ke halaman detail untuk memunculkan popup tanya cetak.
        return redirect()
            ->route('purchases.show', $purchase)
            ->with([
                'success'    => 'PO berhasil disimpan.',
                'ask_print'  => true,
                'print_url'  => route('purchases.print.blanko', ['purchase' => $purchase->id, 'auto' => 1]),
            ]);
    }


    /** SHOW — detail PO */
    public function show(Purchase $purchase)
    {
        // pastikan relasi ter-load
        $purchase->loadMissing(['supplier','user','apoteker','items.product']);
        return view('purchases.show', compact('purchase'));
    }

    /** EDIT — hanya saat draft */
    public function edit(Purchase $purchase)
    {
        abort_if($purchase->status !== Purchase::STATUS_DRAFT, 403, 'PO non-draft tidak bisa diedit.');

        $suppliers = Supplier::select('id','name')
            ->when(schema()->hasColumn('suppliers','is_active'), fn($q) => $q->where('is_active', true))
            ->orderBy('name')->get();

        $apotekers = class_exists(Apoteker::class)
            ? Apoteker::select('id','name')->orderBy('name')->get()
            : collect();

        $products  = Product::select('id','code','name','uom','purchase_price')->orderBy('name')->get();
        $productsForJs = $products->map(fn($p)=>[
            'id' => $p->id,
            'code' => $p->code ?? '',
            'name' => $p->name,
            'uom' => $p->uom ?? 'pcs',
            'default_cost' => (float) ($p->purchase_price ?? 0),
        ])->values();

        $purchase->loadMissing('items.product');

        $poCategories = ['Reguler','Prekursor','Narkotika','Psikotropika','Obat-obat tertentu'];
        $printOptions = [
            'STRUK_58'=>'Struk Kecil 58m','STRUK_76'=>'Struk Kecil 76m','INV_A5'=>'Invoice A5','INV_A4'=>'Invoice A4',
            'PREKURSOR'=>'Prekursor','NARKOTIKA'=>'Narkotika','PSIKOTROPIKA'=>'Psikotropika','OBT_TERTENTU'=>'Obat-obatan tertentu',
        ];

        return view('purchases.edit', compact(
            'purchase','suppliers','apotekers','productsForJs','poCategories','printOptions'
        ));
    }

    /** UPDATE — perbarui draft */
    public function update(Request $request, Purchase $purchase)
    {
        abort_if($purchase->status !== Purchase::STATUS_DRAFT, 403, 'PO non-draft tidak bisa diupdate.');

        $categories = ['Reguler','Prekursor','Narkotika','Psikotropika','Obat-obat tertentu'];
        $printTypes = ['STRUK_58','STRUK_76','INV_A5','INV_A4','PREKURSOR','NARKOTIKA','PSIKOTROPIKA','OBT_TERTENTU'];

        $data = $request->validate([
            'supplier_id'       => ['required','exists:suppliers,id'],
            'po_date'           => ['required','date'],
            'type'              => ['required','in:NON KONSINYASI,KONSINYASI'],
            'category'          => ['required','in:'.implode(',', $categories)],
            'print_type'        => ['required','in:'.implode(',', $printTypes)],
            'note'              => ['nullable','string','max:500'],
            'apoteker_id'       => ['nullable','integer','exists:apotekers,id'],

            'items'                 => ['required','array','min:1'],
            'items.*.product_id'    => ['required','exists:products,id'],
            'items.*.qty'           => ['required','integer','min:1'],
            'items.*.uom'           => ['nullable','string','max:20'],
            'items.*.cost'          => ['required','numeric','min:0'],
            'items.*.discount'      => ['nullable','numeric','min:0'],
            'items.*.tax_pct'       => ['nullable','numeric','min:0'],
        ]);

        DB::transaction(function () use ($purchase, $data) {
            $purchase->items()->delete();

            $grand = 0;
            foreach ($data['items'] as $row) {
                $qty      = (int) $row['qty'];
                $cost     = (float) $row['cost'];
                $discount = (float) ($row['discount'] ?? 0);
                $taxPct   = (float) ($row['tax_pct'] ?? 0);
                $uom      = $row['uom'] ?? null;

                $beforeTax = max(0, $qty * $cost - $discount);
                $tax       = $beforeTax * ($taxPct / 100);
                $subtotal  = $beforeTax + $tax;

                PurchaseItem::create([
                    'purchase_id'  => $purchase->id,
                    'product_id'   => $row['product_id'],
                    'qty'          => $qty,
                    'uom'          => $uom,
                    'cost'         => $cost,
                    'discount'     => $discount,
                    'tax_pct'      => $taxPct,
                    'subtotal'     => $subtotal,
                    'qty_received' => 0,
                ]);

                $grand += $subtotal;
            }

            $purchase->update([
                'po_date'     => $data['po_date'],
                'supplier_id' => $data['supplier_id'],
                'apoteker_id' => $data['apoteker_id'] ?? null,
                'type'        => $data['type'],
                'category'    => $data['category'],
                'print_type'  => $data['print_type'],
                'note'        => $data['note'] ?? null,
                'total'       => $grand,
            ]);
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'PO berhasil diperbarui.');
    }

    /** SUBMIT — ubah status ke ORDERED */
    public function submit(Purchase $purchase)
    {
        abort_if($purchase->status !== Purchase::STATUS_DRAFT, 403, 'Hanya draft yang bisa di-submit.');
        $purchase->update(['status' => Purchase::STATUS_ORDERED]);
        return back()->with('success', 'PO disubmit (Ordered).');
    }

    /** HAPUS — hanya draft */
    public function destroy(Purchase $purchase)
    {
        abort_if($purchase->status !== Purchase::STATUS_DRAFT, 403, 'Hanya draft yang bisa dihapus.');

        DB::transaction(function () use ($purchase) {
            $purchase->items()->delete();
            $purchase->delete();
        });

        return redirect()->route('purchases.index')->with('success', 'PO berhasil dihapus.');
    }

    /**
     * AJAX LOOKUP: Produk untuk modal picker / autocomplete
     * GET /purchases/products-lookup?q=amox
     */
    public function productsLookup(Request $request)
    {
        $q = trim($request->get('q', ''));

        // Deteksi nama kolom yang tersedia di tabel products
        $has = fn(string $c) => Schema::hasColumn('products', $c);

        $colCode  = $has('code') ? 'code' : ($has('kode') ? 'kode' : null);
        $colName  = $has('name') ? 'name'
                : ($has('nama') ? 'nama'
                : ($has('nama_obat') ? 'nama_obat' : 'name')); // fallback 'name' agar SELECT tidak gagal
        $colUom   = $has('uom') ? 'uom' : ($has('satuan') ? 'satuan' : null);
        $colPrice = $has('purchase_price') ? 'purchase_price'
                : ($has('harga_beli') ? 'harga_beli'
                : ($has('price_buy') ? 'price_buy'
                : ($has('harga') ? 'harga' : null)));

        // SELECT aman walau kolom tidak ada
        $select = ['id'];
        $select[] = $colCode  ? "{$colCode} as code"            : DB::raw("'' as code");
        $select[] =             "{$colName} as name";
        $select[] = $colUom   ? "{$colUom} as uom"              : DB::raw("'pcs' as uom");
        $select[] = $colPrice ? "{$colPrice} as purchase_price" : DB::raw('0 as purchase_price');

        try {
            $query = Product::query()->select($select);

            if ($q !== '') {
                $query->where(function ($w) use ($q, $colCode, $colName) {
                    $w->where($colName, 'like', "%{$q}%");
                    if ($colCode) $w->orWhere($colCode, 'like', "%{$q}%");
                });
            }

            // kolom alias "name" selalu ada di SELECT
            $query->orderByRaw('name');

            $items = $query->limit(25)->get()->map(fn($p) => [
                'id'    => $p->id,
                'code'  => $p->code ?? '',
                'name'  => $p->name ?? '',
                'uom'   => $p->uom  ?? 'pcs',
                'price' => (float) ($p->purchase_price ?? 0),
            ]);

            return response()->json($items);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /** QUICK STORE produk baru dari modal PO */
    public function quickStore(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required','string','max:255'],
            'code'           => ['nullable','string','max:100'],
            'uom'            => ['nullable','string','max:50'],
            'purchase_price' => ['nullable','numeric','min:0'],
            'category'       => ['nullable','string','max:100'],
        ]);

        $has = fn($c) => Schema::hasColumn('products', $c);

        $map = [
            'code'           => $has('code') ? 'code' : ($has('kode') ? 'kode' : null),
            'name'           => $has('name') ? 'name' : ($has('nama') ? 'nama' : 'name'),
            'uom'            => $has('uom') ? 'uom' : ($has('satuan') ? 'satuan' : null),
            'purchase_price' => $has('purchase_price') ? 'purchase_price'
                                : ($has('harga_beli') ? 'harga_beli'
                                : ($has('harga') ? 'harga' : null)),
            'category'       => $has('category') ? 'category' : ($has('kategori') ? 'kategori' : null),
        ];

        $payload = [];
        if ($map['name'])           $payload[$map['name']]           = $data['name'];
        if ($map['code'])           $payload[$map['code']]           = $data['code'] ?: Str::upper(Str::slug($data['name'], ''));
        if ($map['uom'])            $payload[$map['uom']]            = $data['uom'] ?? 'pcs';
        if ($map['purchase_price']) $payload[$map['purchase_price']] = $data['purchase_price'] ?? 0;
        if ($map['category'])       $payload[$map['category']]       = $data['category'] ?? null;

        $p = new Product();
        foreach ($payload as $k => $v) $p->setAttribute($k, $v);
        $p->save();

        return response()->json([
            'ok'   => true,
            'id'   => $p->id,
            'code' => $map['code'] ? ($p->{$map['code']} ?? '') : '',
            'name' => $p->{$map['name']},
            'uom'  => $map['uom'] ? ($p->{$map['uom']} ?? 'pcs') : 'pcs',
            'price'=> (float) ($map['purchase_price'] ? ($p->{$map['purchase_price']} ?? 0) : 0),
        ], 201);
    }

    /**
     * Generator nomor PO. Jika App\Services\RunningNumber tidak ada,
     * pakai fallback format: PREFIX-YYYYMM-#### (auto increment per bulan).
     */
    private function nextPoNo(string $prefix, string $table, string $col, string $dateYmd): string
    {
        // Pakai service jika tersedia
        if (class_exists(RunningNumber::class) && method_exists(RunningNumber::class, 'next')) {
            try {
                return RunningNumber::next($prefix, $table, $col, $dateYmd);
            } catch (\Throwable $e) {
                report($e);
                // lanjut ke fallback
            }
        }

        // Fallback: increment per bulan berjalan
        $ym = Str::replace('-', '', substr($dateYmd, 0, 7)); // YYYYMM
        $like = $prefix . '-' . $ym . '-%';

        $last = DB::table($table)
            ->where($col, 'like', $like)
            ->orderByDesc($col)
            ->value($col);

        $n = 1;
        if ($last && preg_match('/-(\d{4})$/', $last, $m)) {
            $n = (int)$m[1] + 1;
        }
        return sprintf('%s-%s-%04d', $prefix, $ym, $n);
    }

    public function printBlanko(Purchase $purchase)
    {
        $purchase->loadMissing([
            'supplier', 'apoteker', 'items.product', 'user',
        ]);

        foreach ($purchase->items as $it) {
            $p = $it->product;
            if ($p) {
                $p->setAttribute('code', $p->code ?? $p->kode ?? $p->sku ?? '');
                $p->setAttribute('name', $p->name ?? $p->nama ?? $p->nama_obat ?? '-');
                $p->setAttribute('uom',  $p->uom  ?? $p->satuan ?? 'pcs');
            }
        }

        $grand = $purchase->items->sum(function ($i) {
            $qty   = (int) ($i->qty ?? 0);
            $cost  = (float) ($i->cost ?? 0);
            $disc  = (float) ($i->discount ?? 0);
            $bt    = max(0, $qty * $cost - $disc);
            $tax   = $bt * ((float) ($i->tax_pct ?? 0) / 100);
            return $bt + $tax;
        });

        // Informasi untuk auto-print
        $autoPrint = request()->boolean('auto', false);
        $backUrl   = route('purchases.show', $purchase);

        return view('purchases.print.blanko', [
            'purchase'  => $purchase,
            'grand'     => $grand,
            'appName'   => config('app.name', 'MyKasir'),
            'brandLogo' => 'images/logo.png',
            'autoPrint' => $autoPrint,
            'backUrl'   => $backUrl,
        ]);
    }


}

/** Helper kecil agar bisa cek kolom tanpa import Schema di atas (tidak wajib). */
if (!function_exists('schema')) {
    function schema(): \Illuminate\Support\Facades\Schema {
        return \Illuminate\Support\Facades\Schema::getFacadeRoot();
    }
}
