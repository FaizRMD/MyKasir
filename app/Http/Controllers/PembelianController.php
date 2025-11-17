<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembelianRequest;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PembelianController extends Controller
{
    /** Form create */
    public function create()
    {
        $suppliers = DB::table('suppliers')
            ->select('id','name')
            ->orderBy('name')->get();

        $warehouses = DB::table('warehouses')
            ->select('id','name')
            ->when(DB::getSchemaBuilder()->hasColumn('warehouses','is_active'), fn($q) => $q->where('is_active', true))
            ->orderBy('name')->get();

        return view('pembelian.create', [
            'suppliers'  => $suppliers,
            'warehouses' => $warehouses,
            'today'      => now()->toDateString(),
        ]);
    }

    /** Cari PO (list) - tahan terhadap typo warehaouse/warehouse & kolom gudang opsional */
    public function searchPO(Request $request)
    {
        $q = trim($request->get('q',''));
        $schema = DB::getSchemaBuilder();

        // Deteksi FK gudang di purchases (warehouse_id / warehaouse_id)
        $whFk = $schema->hasColumn('purchases','warehouse_id')
            ? 'warehouse_id'
            : ($schema->hasColumn('purchases','warehaouse_id') ? 'warehaouse_id' : null);

        $selectWhId   = $whFk ? DB::raw("p.$whFk as warehouse_id") : DB::raw('NULL as warehouse_id');
        $selectWhName = $whFk ? DB::raw("COALESCE(w.name,'-') as warehouse_name") : DB::raw("'-' as warehouse_name");

        $qb = DB::table('purchases as p')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id');

        if ($whFk) {
            $qb->leftJoin('warehouses as w', 'w.id', '=', "p.$whFk");
        }

        $base = $qb->select(
                'p.po_no','p.po_date','p.status','p.supplier_id',
                DB::raw("COALESCE(s.name,'-') as supplier_name"),
                $selectWhId, $selectWhName
            )
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('p.po_no', 'like', "%{$q}%")
                      ->orWhere('s.name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('p.po_date')
            ->limit(20);

        // izinkan ORDERED juga
        $rows = (clone $base)->whereIn('p.status', ['ORDERED','APPROVED','SENT','PARTIAL','OPEN'])->get();

        // Fallback kalau user mengetik query tapi kosong
        if ($q !== '' && $rows->isEmpty()) {
            $rows = $base->get();
        }

        return response()->json($rows);
    }

    /** Ambil detail PO + items (kompatibel dengan kolom price/cost, disc_amount/discount) */
    public function getPO(string $poNo)
    {
        $poKey = trim($poNo);

        // Cari po_no / po_number normalize
        $po = DB::table('purchases')->where('po_no', $poKey)->first();

        if (!$po && Schema::hasColumn('purchases', 'po_no')) {
            $po = DB::table('purchases')
                ->whereRaw('LOWER(TRIM(po_no)) = LOWER(TRIM(?))', [$poKey])
                ->first();
        }
        if (!$po && Schema::hasColumn('purchases', 'po_number')) {
            $po = DB::table('purchases')
                ->whereRaw('LOWER(TRIM(po_number)) = LOWER(TRIM(?))', [$poKey])
                ->first();
        }

        if (!$po) {
            return response()->json(['message' => 'Nomor PO tidak ditemukan.'], 404);
        }

        // ❗Jika ada kolom status: tolak halus hanya jika benar2 selesai/dibatalkan
        if (Schema::hasColumn('purchases','status')) {
            $status = strtoupper($po->status ?? '');
            if (in_array($status, ['RECEIVED','CANCELLED'])) {
                return response()->json(['message' => 'PO sudah selesai/dibatalkan.'], 409);
            }
        }

        // Gudang (antisipasi typo)
        $warehouseId = property_exists($po, 'warehouse_id')
            ? $po->warehouse_id
            : (property_exists($po, 'warehaouse_id') ? $po->warehaouse_id : null);

        // Items defensif
        $items = collect();
        if (Schema::hasTable('purchase_items')) {
            $tbl = 'purchase_items';
            $has = fn(string $c) => Schema::hasColumn($tbl, $c);

            $qtyExpr = $has('qty') ? 'i.qty' : '0';
            $uomExpr = $has('uom') ? 'i.uom'
                    : (Schema::hasColumn('products','default_uom') ? 'p.default_uom' : "''");

            if ($has('price') && $has('cost'))   $priceExpr = 'COALESCE(i.price,i.cost,0)';
            elseif ($has('price'))               $priceExpr = 'COALESCE(i.price,0)';
            elseif ($has('cost'))                $priceExpr = 'COALESCE(i.cost,0)';
            else                                 $priceExpr = '0';

            $discPctExpr = $has('disc_percent') ? 'COALESCE(i.disc_percent,0)' : '0';
            $discAmtExpr = $has('disc_amount')  ? 'COALESCE(i.disc_amount,0)'
                        : ($has('discount')     ? 'COALESCE(i.discount,0)' : '0');
            $batchExpr   = $has('batch_no') ? 'i.batch_no' : 'NULL';
            $expExpr     = $has('exp_date') ? 'i.exp_date' : 'NULL';

            try {
                $items = DB::table("$tbl as i")
                    ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
                    ->where('i.purchase_id', $po->id)
                    ->get([
                        'i.product_id',
                        DB::raw("COALESCE(p.code,'') as code"),
                        DB::raw("COALESCE(p.name,'') as product_name"),
                        DB::raw("$qtyExpr as qty"),
                        DB::raw("$uomExpr as uom"),
                        DB::raw("$priceExpr as buy_price"),
                        DB::raw("$discPctExpr as disc_percent"),
                        DB::raw("$discAmtExpr as disc_amount"),
                        DB::raw("$batchExpr as batch_no"),
                        DB::raw("$expExpr as exp_date"),
                    ])
                    ->map(fn($it) => [
                        'product_id'   => (int) $it->product_id,
                        'code'         => (string) $it->code,
                        'product_name' => (string) $it->product_name,
                        'qty'          => (float) $it->qty,
                        'uom'          => (string) $it->uom,
                        'buy_price'    => (float) $it->buy_price,
                        'disc_percent' => (float) $it->disc_percent,
                        'disc_amount'  => (float) $it->disc_amount,
                        'batch_no'     => $it->batch_no,
                        'exp_date'     => $it->exp_date,
                    ]);
            } catch (\Throwable $e) {
                Log::warning('getPO items fallback', ['err' => $e->getMessage()]);
                $items = collect();
            }
        }

        return response()->json([
            'po_no'        => $po->po_no ?? ($po->po_number ?? $poKey),
            'supplier_id'  => $po->supplier_id ?? null,
            'warehouse_id' => $warehouseId,
            'po_date'      => $po->po_date ?? null,
            'items'        => $items,
        ]);
    }

    /** Cari product (master) */
    public function searchProducts(Request $request)
    {
        $q = trim($request->get('q', ''));
        $tbl = 'products';

        if (!Schema::hasTable($tbl)) {
            return response()->json(['message' => 'Tabel products tidak ditemukan.'], 500);
        }

        // Petakan kemungkinan nama kolom di berbagai skema
        $codeCol    = Schema::hasColumn($tbl,'code')   ? 'code'   : (Schema::hasColumn($tbl,'kode') ? 'kode' : null);
        $nameCol    = Schema::hasColumn($tbl,'name')   ? 'name'   : (Schema::hasColumn($tbl,'nama') ? 'nama' : null);
        $barcodeCol = Schema::hasColumn($tbl,'barcode')? 'barcode': (Schema::hasColumn($tbl,'barcode_no') ? 'barcode_no' : null);

        // UOM bisa default_uom / uom / unit / satuan
        $uomCol = null;
        foreach (['default_uom','uom','unit','satuan'] as $cand) {
            if (Schema::hasColumn($tbl, $cand)) { $uomCol = $cand; break; }
        }

        // Harga beli terakhir: cari kolom yang tersedia
        $priceCol = null;
        foreach (['last_buy_price','last_purchase_price','purchase_price','buy_price','harga_beli'] as $cand) {
            if (Schema::hasColumn($tbl, $cand)) { $priceCol = $cand; break; }
        }

        // Susun SELECT aman (pakai alias supaya view selalu menerima field yang sama)
        $selects = ['id'];
        $selects[] = $codeCol    ? DB::raw("$codeCol as code")       : DB::raw("'' as code");
        $selects[] = $nameCol    ? DB::raw("$nameCol as name")       : DB::raw("'' as name");
        $selects[] = $barcodeCol ? DB::raw("$barcodeCol as barcode") : DB::raw("'' as barcode");
        $selects[] = $uomCol     ? DB::raw("$uomCol as default_uom") : DB::raw("'' as default_uom");
        $selects[] = $priceCol   ? DB::raw("COALESCE($priceCol,0) as last_buy_price")
                                 : DB::raw("0 as last_buy_price");

        $qb = DB::table($tbl)->select($selects);

        if ($q !== '') {
            $qb->where(function ($w) use ($q, $codeCol, $nameCol, $barcodeCol) {
                if ($codeCol)    $w->orWhere($codeCol, 'like', "%{$q}%");
                if ($nameCol)    $w->orWhere($nameCol, 'like', "%{$q}%");
                if ($barcodeCol) $w->orWhere($barcodeCol, 'like', "%{$q}%");
            });
        }

        if ($nameCol) $qb->orderBy($nameCol); else $qb->orderBy('id');

        $rows = $qb->limit(25)->get();

        return response()->json($rows);
    }

    /** Simpan pembelian — kembali ke halaman Pembelian (tidak redirect ke GRN) */
    public function store(StorePembelianRequest $request)
    {
        $data = $request->validated();

        // Bangun peta harga/discount dari PO jika ada
        $poMap = [];
        if (!empty($data['po_no'])) {
            $poMap = $this->getPoPriceMap($data['po_no']);

            // Filter: hanya item yang ada di PO
            $data['items'] = array_values(array_filter($data['items'] ?? [], function ($row) use ($poMap) {
                $pid = (int)($row['product_id'] ?? 0);
                return $pid && isset($poMap[$pid]);
            }));
        }

        // Hitung totals dengan normalisasi angka + override harga/discount dari PO (bila ada)
        $calc = $this->calculateTotals($data, $poMap);

        $newId = null;
        $priceCol = $this->resolveLastBuyPriceColumn();

        DB::transaction(function () use ($data, $calc, $priceCol, &$newId) {
            $header = Pembelian::create([
                'po_no'          => $data['po_no'] ?? null,
                'invoice_no'     => $data['invoice_no'] ?? null,
                'invoice_date'   => $data['invoice_date'],
                'supplier_id'    => $data['supplier_id'],
                'warehouse_id'   => $data['warehouse_id'] ?? null,
                'payment_type'   => $data['payment_type'],
                'cashbook'       => $data['cashbook'] ?? null,
                'due_date'       => $data['due_date'] ?? null,
                'gross'          => $calc['gross'],
                'discount_total' => $calc['discount_total'],
                'tax_percent'    => $calc['tax_percent'],
                'tax_amount'     => $calc['tax_amount'],
                'extra_cost'     => $calc['extra_cost'],
                'net_total'      => $calc['net_total'],
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($calc['items'] as $row) {
                PembelianItem::create([
                    'pembelian_id' => $header->id,
                    'product_id'   => $row['product_id'],
                    'qty'          => $row['qty'],
                    'uom'          => $row['uom'],
                    'buy_price'    => $row['buy_price'],    // sudah hasil override/normalisasi
                    'disc_percent' => $row['disc_percent'],
                    'disc_amount'  => $row['disc_amount'],
                    'subtotal'     => $row['subtotal'],
                    'disc_nominal' => $row['disc_nominal'],
                    'hpp'          => $row['hpp'],
                    'hna_ppn'      => $row['hna_ppn'],
                    'batch_no'     => $row['batch_no'],
                    'exp_date'     => $row['exp_date'],
                ]);

                // update harga beli terakhir hanya jika kolomnya ada
                if ($priceCol) {
                    DB::table('products')
                        ->where('id', $row['product_id'])
                        ->update([$priceCol => $row['buy_price']]);
                }
            }

            // ❌ JANGAN tutup PO di sini.
            // if (!empty($data['po_no'])) {
            //     DB::table('purchases')->where('po_no', $data['po_no'])->update(['status' => 'RECEIVED']);
            // }

            $newId = $header->id;
        });

        // Cari purchase_id dari po_no untuk GRN
        $purchaseId = null;
        if (!empty($data['po_no'])) {
            $poKey = trim($data['po_no']);
            $purchase = DB::table('purchases')
                ->select('id')
                ->whereRaw('LOWER(TRIM(po_no)) = LOWER(TRIM(?))', [$poKey])
                ->orWhere(function ($q) use ($poKey) {
                    if (Schema::hasColumn('purchases','po_number')) {
                        $q->whereRaw('LOWER(TRIM(po_number)) = LOWER(TRIM(?))', [$poKey]);
                    }
                })
                ->first();
            $purchaseId = $purchase->id ?? null;
        }

        // Redirect ke PENERIMAAN
        if ($purchaseId) {
            return redirect()
                ->route('grn.create', ['purchase' => $purchaseId])
                ->with('success', "✅ Pembelian tersimpan (ID: {$newId}). Lanjutkan proses Penerimaan Barang.");
        }

        // Tanpa PO → ke daftar penerimaan
        return redirect()
            ->route('grn.index')
            ->with('success', "✅ Pembelian tersimpan (ID: {$newId}). Buka Penerimaan untuk memproses barang.");
    }

    /** ===================== UTILITIES ===================== */

    /**
     * Normalisasi string angka (ID/EN) ke float aman.
     * Contoh: "200.000,50" -> 200000.50 , "200,50" -> 200.50 , "200.000.00" -> 200000.00
     */
    private function parseMoney($v): float
    {
        if (is_null($v)) return 0.0;
        if (is_numeric($v)) return (float)$v;

        $s = trim((string)$v);

        // Jika mengandung koma dan titik (ID style umum: 200.000,50)
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);   // buang pemisah ribuan '.'
            $s = str_replace(',', '.', $s);  // koma -> titik
            return is_numeric($s) ? (float)$s : 0.0;
        }

        // Jika hanya koma, anggap itu desimal
        if (strpos($s, ',') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            return is_numeric($s) ? (float)$s : 0.0;
        }

        // Jika hanya titik, biarkan (EN style). Buang karakter non-digit/non-dot.
        $s = preg_replace('/[^\d\.]/', '', $s);

        // Jika ada lebih dari satu titik (kasus "200.000.00"), buang semua titik kecuali terakhir sebagai desimal
        if (substr_count($s, '.') > 1) {
            // buang semua titik lalu set 2 digit terakhir sebagai desimal bila cocok pola
            $digits = str_replace('.', '', $s);
            // jika akhirannya 2 digit, sisipkan titik sebelum 2 digit terakhir
            if (preg_match('/^\d+$/', $digits)) {
                if (strlen($digits) > 2) {
                    $s = substr($digits, 0, -2) . '.' . substr($digits, -2);
                } else {
                    // kurang dari 2 digit, anggap integer
                    $s = $digits;
                }
            }
        }

        return is_numeric($s) ? (float)$s : 0.0;
    }

    /**
     * Ambil peta harga/discount/uom dari purchase_items berdasarkan PO.
     * return [product_id => ['price'=>..., 'disc_percent'=>..., 'disc_amount'=>..., 'uom'=>...], ...]
     */
    private function getPoPriceMap(string $poNo): array
    {
        $poKey = trim($poNo);

        $poQuery = DB::table('purchases')->select('id')
            ->whereRaw('LOWER(TRIM(po_no)) = LOWER(TRIM(?))', [$poKey]);

        if (Schema::hasColumn('purchases', 'po_number')) {
            $poQuery->orWhereRaw('LOWER(TRIM(po_number)) = LOWER(TRIM(?))', [$poKey]);
        }

        $po = $poQuery->first();
        if (!$po) return [];

        $tbl = 'purchase_items';
        if (!Schema::hasTable($tbl)) return [];

        $has = fn($c) => Schema::hasColumn($tbl, $c);

        $priceExpr = $has('price') && $has('cost') ? DB::raw('COALESCE(price,cost,0) as price')
            : ($has('price') ? DB::raw('COALESCE(price,0) as price')
                             : ($has('cost')  ? DB::raw('COALESCE(cost,0)  as price')
                                              : DB::raw('0 as price')));

        $discPct = $has('disc_percent') ? DB::raw('COALESCE(disc_percent,0) as disc_percent') : DB::raw('0 as disc_percent');
        $discAmt = $has('disc_amount')  ? DB::raw('COALESCE(disc_amount,0)  as disc_amount')
                 : ($has('discount')    ? DB::raw('COALESCE(discount,0)     as disc_amount')
                                        : DB::raw('0 as disc_amount'));
        $uomCol  = $has('uom') ? 'uom' : null;

        $selects = array_filter(array_merge(
            ['product_id', $priceExpr, $discPct, $discAmt],
            $uomCol ? [DB::raw("$uomCol as uom")] : []
        ));

        $rows = DB::table($tbl)
            ->where('purchase_id', $po->id)
            ->get($selects);

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->product_id] = [
                'price'        => (float)$r->price,
                'disc_percent' => (float)($r->disc_percent ?? 0),
                'disc_amount'  => (float)($r->disc_amount ?? 0),
                'uom'          => $r->uom ?? null,
            ];
        }
        return $map;
    }

    /** Hitung total (dengan opsi override harga/discount dari PO per product) */
    private function calculateTotals(array $data, array $poPriceMap = []): array
    {
        $taxPercent = $this->parseMoney($data['tax_percent'] ?? 0);
        $extraCost  = $this->parseMoney($data['extra_cost'] ?? 0);

        $grossAfterDisc = 0.0;
        $discountTotal  = 0.0;
        $itemsOut = [];

        foreach ($data['items'] as $row) {
            $qty   = $this->parseMoney($row['qty'] ?? 0);

            // Override dari PO jika ada
            $poPrice = null;
            if (!empty($poPriceMap) && !empty($row['product_id'])) {
                $pid = (int)$row['product_id'];
                if (isset($poPriceMap[$pid])) {
                    $poPrice = (float)$poPriceMap[$pid]['price'];
                    // Override disc & uom juga (kalau tersedia)
                    $row['disc_percent'] = $poPriceMap[$pid]['disc_percent'];
                    $row['disc_amount']  = $poPriceMap[$pid]['disc_amount'];
                    if (!empty($poPriceMap[$pid]['uom'])) {
                        $row['uom'] = $poPriceMap[$pid]['uom'];
                    }
                }
            }

            $price = $poPrice !== null ? $poPrice : $this->parseMoney($row['buy_price'] ?? 0);
            $dp    = $this->parseMoney($row['disc_percent'] ?? 0);
            $da    = $this->parseMoney($row['disc_amount'] ?? 0);

            $subtotal = $qty * $price;
            $discPercentNominal = ($dp / 100 * $subtotal);
            $discNominal = $discPercentNominal + $da;
            $afterDisc = max(0, $subtotal - $discNominal);
            $hppPerUnit = $qty > 0 ? $afterDisc / $qty : 0;
            $hnaPpnPerUnit = $hppPerUnit * (1 + ($taxPercent / 100));

            $grossAfterDisc += $afterDisc;
            $discountTotal  += $discNominal;

            $itemsOut[] = [
                'product_id'   => (int)($row['product_id'] ?? 0),
                'qty'          => $qty,
                'uom'          => $row['uom'] ?? '',
                'buy_price'    => round($price, 2),
                'disc_percent' => round($dp, 2),
                'disc_amount'  => round($da, 2),
                'subtotal'     => round($afterDisc, 2),
                'disc_nominal' => round($discNominal, 2),
                'hpp'          => round($hppPerUnit, 2),
                'hna_ppn'      => round($hnaPpnPerUnit, 2),
                'batch_no'     => $row['batch_no'] ?? null,
                'exp_date'     => $row['exp_date'] ?? null,
            ];
        }

        $taxAmount = round($grossAfterDisc * ($taxPercent / 100), 2);
        $netTotal  = $grossAfterDisc + $taxAmount + $extraCost;

        return [
            'items'          => $itemsOut,
            'gross'          => round($grossAfterDisc, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_percent'    => $taxPercent,
            'tax_amount'     => $taxAmount,
            'extra_cost'     => round($extraCost, 2),
            'net_total'      => round($netTotal, 2),
        ];
    }

    private function resolveLastBuyPriceColumn(): ?string
    {
        // Coba beberapa kemungkinan nama kolom yang umum dipakai
        $candidates = [
            'last_buy_price',
            'last_purchase_price',
            'purchase_price',
            'buy_price',
            'harga_beli',
        ];

        // Jika tabel products tidak ada, langsung batal
        if (!Schema::hasTable('products')) {
            return null;
        }

        foreach ($candidates as $col) {
            if (Schema::hasColumn('products', $col)) {
                return $col;
            }
        }

        return null; // tidak ditemukan kolom yang cocok
    }
}
