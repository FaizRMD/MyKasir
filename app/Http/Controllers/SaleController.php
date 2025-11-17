<?php

namespace App\Http\Controllers;

use App\Models\{Sale, SaleItem, Product, Customer, InventoryMovement, User};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaleController extends Controller
{
    /**
     * Helper: apakah user sekarang ber-role kasir?
     */
    private function isCashier(): bool
    {
        return (bool) auth()->user()?->hasRole('kasir');
    }

    /**
     * Dashboard ringkas (hari ini), otomatis filter per kasir bila role = kasir.
     */
    public function dashboard()
    {
        $today = Carbon::today();

        $salesQ = Sale::whereDate('created_at', $today);
        if ($this->isCashier()) {
            $salesQ->where('user_id', auth()->id()); // filter kasir
        }

        $salesCount = (clone $salesQ)->count();
        $grand      = (clone $salesQ)->sum('grand_total');

        $itemsQ = SaleItem::whereDate('created_at', $today);
        if ($this->isCashier()) {
            // filter via relasi sale → sale.user_id
            $itemsQ->whereHas('sale', fn($q) => $q->where('user_id', auth()->id()));
        }
        $items = (int) $itemsQ->sum('qty');

        return view('sales.dashboard', compact('salesCount','grand','items'));
    }

    /**
     * Listing penjualan: kasir hanya lihat transaksi miliknya.
     */
    public function index()
    {
        $query = Sale::with('customer')->latest();

        if ($this->isCashier()) {
            // pakai scope dari trait OwnedByUser (user_id)
            $query->ownedByLoggedIn();
        }

        $sales = $query->paginate(20);

        return view('sales.index', compact('sales'));
    }

    /**
     * Form create transaksi.
     */
    public function create(Request $request)
    {
        $q = $request->get('q');

        $products  = Product::active()
            ->when($q, fn($qq) => $qq->where('name','like',"%$q%"))
            ->orderBy('name')
            ->limit(25)
            ->get();

        $customers = Customer::orderBy('name')->get();

        return view('sales.create', compact('products','customers'));
    }

    /**
     * Simpan transaksi baru:
     * - tempelkan user_id = kasir yang login
     * - kurangi stok per FEFO + catat InventoryMovement (OUT)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'           => 'nullable|exists:customers,id',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.qty'           => 'required|integer|min:1',
            'items.*.price'         => 'required|numeric|min:0',
            'items.*.tax_percent'   => 'nullable|numeric|min:0',
            'discount'              => 'nullable|numeric|min:0',
            'payment_method'        => 'required|string|max:32',
            'paid'                  => 'required|numeric|min:0',
            'notes'                 => 'nullable|string|max:255'
        ]);

        $invoice = 'INV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));

        return DB::transaction(function () use ($data, $invoice) {
            // Hitung subtotal & pajak
            $subtotal = 0;
            $tax      = 0;

            foreach ($data['items'] as $row) {
                $line     = $row['qty'] * $row['price'];
                $subtotal += $line;
                $tax      += $line * (($row['tax_percent'] ?? 0) / 100);
            }

            $discount = $data['discount'] ?? 0;
            $grand    = $subtotal + $tax - $discount;
            $change   = ($data['paid'] ?? 0) - $grand;

            // 1) Simpan header penjualan + "tempel" ke kasir (user_id)
            $sale = Sale::create([
                'user_id'        => auth()->id(),            // 🔑 nempel ke kasir yang login
                'invoice_no'     => $invoice,
                'customer_id'    => $data['customer_id'] ?? null,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'grand_total'    => $grand,
                'paid'           => $data['paid'],
                'change'         => $change,
                'payment_method' => $data['payment_method'],
                'notes'          => $data['notes'] ?? null,
            ]);

            // 2) Simpan item satu per satu + kurangi stok FEFO
            foreach ($data['items'] as $row) {
                /** @var \App\Models\Product $product */
                $product = Product::find($row['product_id']);

                // simpan detail item
                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $product->id,
                    'name'         => $product->name,
                    'qty'          => $row['qty'],
                    'price'        => $row['price'],
                    'tax_percent'  => $row['tax_percent'] ?? 0,
                    'total'        => ($row['qty'] * $row['price']) * (1 + (($row['tax_percent'] ?? 0)/100)),
                    'batch_no'     => optional($product->batches()->available()->orderByFEFO()->first())->batch_no,
                ]);

                // FEFO: kurangi stok per-batch
                $qtyToDeduct = (int) $row['qty'];
                $batches     = $product->batches()->available()->orderByFEFO()->get();

                foreach ($batches as $b) {
                    if ($qtyToDeduct <= 0) break;

                    $take = min($qtyToDeduct, $b->qty);
                    if ($take > 0) {
                        $b->decrement('qty', $take);

                        InventoryMovement::create([
                            'product_id'   => $product->id,
                            'type'         => 'OUT',
                            'qty'          => $take,
                            'reference'    => $sale->invoice_no.' / SALE',
                            'notes'        => 'Sale FEFO',
                            'batch_no'     => $b->batch_no,
                            'expiry_date'  => $b->expiry_date,
                            // 'sale_id'    => $sale->id,      // ← BUKA kalau kolom ini ada di tabel (lebih rapi)
                            // 'user_id'    => auth()->id(),    // ← BUKA kalau kamu ingin catat siapa yang melakukan OUT
                        ]);

                        $qtyToDeduct -= $take;
                    }
                }

                // stok agregat produk
                $product->decrement('stock', (int) $row['qty']);
            }

            return redirect()->route('sales.show', $sale)->with('ok', 'Transaksi tersimpan.');
        });
    }

    public function show(Sale $sale)
    {
        // Kasir hanya boleh akses penjualan miliknya (opsional—tambahkan jika perlu hard-guard)
        if ($this->isCashier() && $sale->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak melihat transaksi kasir lain.');
        }

        $sale->load('items','customer');
        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        if ($this->isCashier() && $sale->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak mencetak transaksi kasir lain.');
        }

        $sale->load('items','customer');
        return view('sales.receipt', compact('sale'));
    }
}
