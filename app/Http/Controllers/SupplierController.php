<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\GoodsReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

// Tambahan untuk email
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseOrderMail;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    /**
     * Tampilkan daftar supplier dengan pencarian + metrik ringan (ala vmedis).
     */
    public function index(Request $request)
    {
        $q       = trim((string) $request->get('q'));
        $status  = $request->get('status'); // active|inactive|all

        $rows = Supplier::query()
            ->when($q, function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('code','like',"%{$q}%")
                      ->orWhere('name','like',"%{$q}%")
                      ->orWhere('phone','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%")
                      ->orWhere('city','like',"%{$q}%");
                });
            })
            ->when($status === 'active',   fn($w) => $w->where('is_active', true))
            ->when($status === 'inactive', fn($w) => $w->where('is_active', false))
            ->withCount(['purchases'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('supplier.index', compact('rows','q','status'));
    }

    /** Form tambah supplier. */
    public function create()
    {
        return view('supplier.create');
    }

    /** Simpan supplier baru. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'           => ['nullable','string','max:32','unique:suppliers,code'],
            'name'           => ['required','string','max:255'],
            'contact_person' => ['nullable','string','max:128'],
            'phone'          => ['nullable','string','max:64'],
            'email'          => ['nullable','email','max:128'],
            'address'        => ['nullable','string'],
            'city'           => ['nullable','string','max:64'],
            'npwp'           => ['nullable','string','max:64'],
            'notes'          => ['nullable','string','max:255'],
            'is_active'      => ['nullable','boolean'],
        ]);

        if (!empty($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        $data['is_active'] = (bool)($data['is_active'] ?? true);

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('ok','Supplier berhasil ditambahkan');
    }

    /**
     * Detail supplier + ringkasan ala vmedis.
     */
    public function show(Supplier $supplier)
    {
        $supplier->load([
            'purchases' => function ($q) {
                $q->with(['items:id,purchase_id,product_id,qty,qty_received,subtotal','items.product:id,name'])
                  ->latest('tanggal');
            },
        ]);

        $grnCount   = GoodsReceipt::where('supplier_id',$supplier->id)->count();

        $totalPo      = $supplier->purchases->count();
        $totalBelanja = $supplier->purchases->sum('total');
        $lastPoDate   = optional($supplier->purchases->first())->tanggal;

        $outstandingQty = $supplier->purchases
            ->whereIn('status',['draft','ordered','partial_received'])
            ->flatMap->items
            ->sum(function ($i) {
                $out = (int)$i->qty - (int)$i->qty_received;
                return $out > 0 ? $out : 0;
            });

        $topProducts = $supplier->purchases
            ->flatMap->items
            ->groupBy('product_id')
            ->map(function ($rows) {
                $name = optional($rows->first()->product)->name ?? '—';
                return [
                    'product_id' => $rows->first()->product_id,
                    'name'       => $name,
                    'qty'        => (int) $rows->sum('qty'),
                    'subtotal'   => (float)$rows->sum('subtotal'),
                ];
            })
            ->sortByDesc('qty')
            ->values()
            ->take(5);

        $stats = [
            'total_po'        => $totalPo,
            'total_belanja'   => $totalBelanja,
            'last_po_date'    => $lastPoDate,
            'outstanding_qty' => $outstandingQty,
            'total_grn'       => $grnCount,
            'top_products'    => $topProducts,
        ];

        return view('supplier.show', compact('supplier','stats'));
    }

    /** Form edit supplier. */
    public function edit(Supplier $supplier)
    {
        return view('supplier.edit', compact('supplier'));
    }

    /** Update supplier. */
    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'code'           => ['nullable','string','max:32', Rule::unique('suppliers','code')->ignore($supplier->id)],
            'name'           => ['required','string','max:255'],
            'contact_person' => ['nullable','string','max:128'],
            'phone'          => ['nullable','string','max:64'],
            'email'          => ['nullable','email','max:128'],
            'address'        => ['nullable','string'],
            'city'           => ['nullable','string','max:64'],
            'npwp'           => ['nullable','string','max:64'],
            'notes'          => ['nullable','string','max:255'],
            'is_active'      => ['nullable','boolean'],
        ]);

        if (!empty($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        $data['is_active'] = (bool)($data['is_active'] ?? true);

        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('ok','Supplier berhasil diperbarui');
    }

    /** Hapus supplier — dicegah jika sudah ada PO. */
    public function destroy(Supplier $supplier)
    {
        $used = Purchase::where('supplier_id',$supplier->id)->exists();
        if ($used) {
            return back()->withErrors('Supplier ini sudah dipakai dalam Purchase Order. Tidak bisa dihapus.');
        }

        $supplier->delete();

        return back()->with('ok','Supplier berhasil dihapus');
    }

    /** Shortcut: buat PO dengan supplier terpilih. */
    public function createPurchase(Supplier $supplier)
    {
        return redirect()->route('purchases.create', ['supplier_id' => $supplier->id]);
    }

    /* ================================
     * Tambahan ala VMEDIS
     * ================================ */

    /** AJAX lookup untuk Select2/autocomplete. */
    public function lookup(Request $request)
    {
        $q     = trim((string)$request->get('q'));
        $limit = (int)($request->get('limit', 10));

        $rows = Supplier::query()
            ->when($q, function ($w) use ($q) {
                $w->where(function($x) use ($q){
                    $x->where('name','like',"%{$q}%")
                      ->orWhere('code','like',"%{$q}%")
                      ->orWhere('phone','like',"%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id','code','name','phone','city','email']);

        return response()->json($rows->map(function($s){
            return [
                'id'   => $s->id,
                'text' => trim(($s->code ? "{$s->code} - " : '').$s->name),
                'meta' => [
                    'phone' => $s->phone,
                    'city'  => $s->city,
                    'email' => $s->email,
                ],
            ];
        }));
    }

    /** Toggle aktif/nonaktif supplier (quick action). */
    public function toggleActive(Supplier $supplier)
    {
        $supplier->is_active = !$supplier->is_active;
        $supplier->save();

        return back()->with('ok', 'Status supplier diperbarui.');
    }

    /** Analytics ringkas (widget dashboard supplier). */
    public function analytics(Supplier $supplier, Request $request)
    {
        $from = $request->date('from');
        $to   = $request->date('to');

        $po = Purchase::where('supplier_id',$supplier->id)
            ->when($from, fn($q)=>$q->whereDate('tanggal','>=',$from))
            ->when($to,   fn($q)=>$q->whereDate('tanggal','<=',$to));

        $grn = GoodsReceipt::where('supplier_id',$supplier->id)
            ->when($from, fn($q)=>$q->whereDate('received_at','>=',$from))
            ->when($to,   fn($q)=>$q->whereDate('received_at','<=',$to));

        $data = [
            'po_count'       => $po->count(),
            'po_total'       => (float) (clone $po)->sum('total'),
            'grn_count'      => $grn->count(),
        ];

        return response()->json($data);
    }

    /** Export CSV daftar supplier (sesuai filter). */
    public function exportCsv(Request $request)
    {
        $q      = trim((string)$request->get('q'));
        $status = $request->get('status');

        $rows = Supplier::query()
            ->when($q, function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('code','like',"%{$q}%")
                      ->orWhere('name','like',"%{$q}%")
                      ->orWhere('phone','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%")
                      ->orWhere('city','like',"%{$q}%");
                });
            })
            ->when($status === 'active',   fn($w) => $w->where('is_active', true))
            ->when($status === 'inactive', fn($w) => $w->where('is_active', false))
            ->orderBy('name')
            ->get([
                'code','name','contact_person','phone','email','city','is_active','npwp'
            ]);

        $filename = 'supplier_export_'.now()->format('Ymd_His').'.csv';

        $callback = function() use ($rows) {
            $out = fopen('php://output','w');
            fputcsv($out, ['Kode','Nama','Kontak','Telepon','Email','Kota','Aktif','NPWP']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->code, $r->name, $r->contact_person, $r->phone, $r->email, $r->city,
                    $r->is_active ? 'YA' : 'TIDAK', $r->npwp
                ]);
            }
            fclose($out);
        };

        return Response::stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /* ============================================================
     |            >>>>>  EMAIL PO KE SUPPLIER  <<<<<
     * ============================================================ */

    /**
     * Kirim PO tertentu milik supplier ke email supplier (queue).
     * Route contoh:
     * POST /suppliers/{supplier}/send-po/{purchaseId}
     *   -> name: suppliers.send_po
     */
    public function sendPo(Supplier $supplier, $purchaseId)
    {
        // PO harus milik supplier yang sama
        $purchase = Purchase::with(['supplier','items.product'])
            ->where('supplier_id', $supplier->id)
            ->findOrFail($purchaseId);

        $email = $supplier->email;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors('Supplier belum memiliki email yang valid.');
        }

        try {
            // gunakan queue; jika belum ada worker, sementara ganti ke ->send()
            Mail::to($email)->queue(new PurchaseOrderMail($purchase));

            // (opsional) cc internal:
            // Mail::to($email)->bcc('purchasing@kantor.com')->queue(new PurchaseOrderMail($purchase));

            return back()->with('ok', "Email PO #{$purchase->id} dikirim ke {$email}.");
        } catch (\Throwable $e) {
            Log::error("Gagal kirim email PO {$purchase->id} ke {$email}: ".$e->getMessage());
            return back()->withErrors('Gagal mengirim email. Periksa konfigurasi mailer atau log.');
        }
    }

    /**
     * Kirim PO terbaru milik supplier ke email supplier (praktis dari halaman show).
     * Route contoh:
     * POST /suppliers/{supplier}/send-last-po
     *   -> name: suppliers.send_last_po
     */
    public function sendLastPo(Supplier $supplier)
    {
        $purchase = Purchase::with(['supplier','items.product'])
            ->where('supplier_id', $supplier->id)
            ->latest('tanggal')
            ->first();

        if (! $purchase) {
            return back()->withErrors('Supplier belum memiliki Purchase Order.');
        }

        $email = $supplier->email;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors('Supplier belum memiliki email yang valid.');
        }

        try {
            Mail::to($email)->queue(new PurchaseOrderMail($purchase));
            return back()->with('ok', "Email PO terbaru (#{$purchase->id}) dikirim ke {$email}.");
        } catch (\Throwable $e) {
            Log::error("Gagal kirim email PO terbaru {$purchase->id} ke {$email}: ".$e->getMessage());
            return back()->withErrors('Gagal mengirim email. Periksa konfigurasi mailer atau log.');
        }
    }
}
