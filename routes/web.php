<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request; // <-- PENTING: tambahkan ini

// Controllers
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\GolonganObatController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ApotekerController;
use App\Http\Controllers\PabrikController;
use App\Http\Controllers\LokasiObatController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\SaleItemController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Purchasing\SupplierReportController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\Reports\PurchaseReportController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\StockObatController;
use App\Http\Controllers\Reports\PembelianReportController;
use App\Http\Controllers\SwitchAccountController;
use App\Http\Controllers\Reports\LaporanExpiredController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

/*
|--------------------------------------------------------------------------
| PUBLIC / AUTH REDIRECT
|--------------------------------------------------------------------------
|
| - Jika belum login  -> redirect ke login
| - Jika login sebagai kasir -> redirect ke kasir.index (Transaksi)
| - Jika login sebagai admin/owner -> redirect ke dashboard
|
*/
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();

        if ($user && $user->hasRole('kasir')) {
            // Kasir langsung ke halaman kasir POS
            return redirect()->route('kasir.index');
        }

        // Admin / Owner tetap ke dashboard
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
|
| Semua user yang sudah login & verified boleh akses route ini,
| tapi kalau role = kasir akan LANGSUNG diarahkan ke kasir.index.
| Admin / owner akan tetap melihat halaman Dashboard.
|
| DI SINI kita panggil DashboardController::index($request)
| dengan parameter Request, supaya tidak error lagi.
|
*/
Route::middleware(['auth', 'verified'])->get('/dashboard', function (Request $request) {
    $user = $request->user();

    if ($user && $user->hasRole('kasir')) {
        // Kasir tidak boleh ke dashboard, langsung lempar ke Transaksi
        return redirect()->route('kasir.index');
    }

    // Selain kasir (admin/owner), jalankan DashboardController@index dengan Request
    return app(DashboardController::class)->index($request);
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (All Roles)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /* ================= PROFILE ================= */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');

        Route::match(['PUT', 'PATCH'], '/', [ProfileController::class, 'update'])->name('update');

        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

        Route::get('/avatar/{user?}', [ProfileController::class, 'avatar'])
            ->whereNumber('user')
            ->name('avatar');
    });

    /* ================= SWITCH ACCOUNT (Multi-Role Users) ================= */
    Route::post('/switch-account', [SwitchAccountController::class, 'switch'])
        ->name('switch.account');
});

/*
|--------------------------------------------------------------------------
| KASIR ROUTES (Cashier - POS & Sales)
|--------------------------------------------------------------------------
| Akses: kasir, admin, owner
*/
Route::middleware(['auth', 'role:kasir|admin|owner'])->group(function () {

    /* ================= REDIRECT OLD URL ================= */
    Route::get('/sale-items', function () {
        return redirect()->route('kasir.index');
    })->name('sale-items.index');

    /* ================= KASIR / POS ================= */
    Route::prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/', [SaleItemController::class, 'index'])->name('index');
        Route::get('/create', [SaleItemController::class, 'create'])->name('create');
        Route::post('/checkout', [SaleItemController::class, 'checkout'])->name('checkout');

        Route::get('/{sale}', [SaleItemController::class, 'show'])
            ->whereNumber('sale')
            ->name('show');

        Route::get('/{sale}/struk', [SaleItemController::class, 'printReceipt'])
            ->whereNumber('sale')
            ->name('struk');
    });

    /* ================= SALE ITEMS API (untuk AJAX/API POS) ================= */
    Route::prefix('sale-items')->name('sale-items.')->group(function () {
        Route::post('/', [SaleItemController::class, 'store'])->name('store');

        Route::get('/{saleItem}', [SaleItemController::class, 'show'])
            ->whereNumber('saleItem')
            ->name('show');

        Route::put('/{saleItem}', [SaleItemController::class, 'update'])
            ->whereNumber('saleItem')
            ->name('update');

        Route::delete('/{saleItem}', [SaleItemController::class, 'destroy'])
            ->whereNumber('saleItem')
            ->name('destroy');
    });

    /* ================= PRODUCT LOOKUP UNTUK POS =================
     * Route inilah yang dipakai di Blade:
     *   route('products.lookup')
     * HARUS bisa diakses kasir, admin, dan owner.
     */
    Route::get('/products/lookup', [ProductController::class, 'lookup'])
        ->name('products.lookup');
});

/*
|--------------------------------------------------------------------------
| LOKASI OBAT – boleh kasir, admin, owner
|--------------------------------------------------------------------------
| Kasir punya hak akses ke master data Lokasi Obat.
*/
Route::middleware(['auth', 'role:kasir|admin|owner'])->group(function () {
    Route::resource('lokasi-obat', LokasiObatController::class)
        ->parameters(['lokasi-obat' => 'lokasiObat']);
});

/*
|--------------------------------------------------------------------------
| ADMIN & OWNER ROUTES (Full Access)
|--------------------------------------------------------------------------
| Akses: admin, owner
*/
Route::middleware(['auth', 'role:admin|owner'])->group(function () {

    /* ================= USER MANAGEMENT (ADMIN CREATES CASHIER/OTHERS) ================= */
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/create', [AdminUserController::class, 'create'])->name('create');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
    });

    /* ================= PRODUCTS MANAGEMENT ================= */
    Route::prefix('products')->name('products.')->group(function () {
        // Export routes
        Route::get('/export/pdf', [ProductController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/xlsx', [ProductController::class, 'exportXlsx'])->name('export.xlsx');
        Route::get('/export/xls', [ProductController::class, 'exportXls'])->name('export.xls');

        // Quick store untuk form tambah cepat (khusus admin/owner)
        Route::post('/quick-store', [ProductController::class, 'quickStore'])->name('quickStore');

        // ⚠️ TIDAK ADA /lookup di sini, supaya tidak menimpa route POS
        // Route::get('/lookup', [ProductController::class, 'lookup'])->name('lookup');
    });

    // Resource Products
    Route::resource('products', ProductController::class)
        ->parameters(['products' => 'product'])
        ->whereNumber('product');

    /* ================= SUPPLIERS MANAGEMENT ================= */
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/lookup', [SupplierController::class, 'lookup'])->name('lookup');
        Route::get('/export/csv', [SupplierController::class, 'exportCsv'])->name('export.csv');
    });

    Route::resource('suppliers', SupplierController::class)
        ->whereNumber('supplier');

    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/{supplier}/create-po', [SupplierController::class, 'createPurchase'])
            ->whereNumber('supplier')
            ->name('create_po');
        Route::post('/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])
            ->whereNumber('supplier')
            ->name('toggle');
        Route::get('/{supplier}/analytics', [SupplierController::class, 'analytics'])
            ->whereNumber('supplier')
            ->name('analytics');
    });

    /* ================= MASTER DATA ================= */
    Route::resource('golongan-obat', GolonganObatController::class)
        ->parameters(['golongan-obat' => 'golonganObat']);

    // lokasi-obat TIDAK lagi di sini, karena sudah digroup di atas (kasir+admin+owner)

    Route::resource('apoteker', ApotekerController::class);

    if (class_exists(PabrikController::class)) {
        Route::resource('pabrik', PabrikController::class);
    }

    /* ================= PURCHASE ORDERS (PO) ================= */
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/products-lookup', [PurchaseController::class, 'productsLookup'])
            ->name('productsLookup');
    });

    Route::resource('purchases', PurchaseController::class)
        ->whereNumber('purchase');

    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::post('/{purchase}/submit', [PurchaseController::class, 'submit'])
            ->whereNumber('purchase')
            ->name('submit');
        Route::get('/{purchase}/print/blanko', [PurchaseController::class, 'printBlanko'])
            ->whereNumber('purchase')
            ->name('print.blanko');
    });

    /* ================= GOODS RECEIPT NOTE (GRN / PENERIMAAN BARANG) ================= */
    Route::prefix('goods-receipts')->name('goods-receipts.')->group(function () {
        Route::get('/', [GoodsReceiptController::class, 'index'])->name('index');

        Route::get('/create/{pembelian}', [GoodsReceiptController::class, 'create'])
            ->whereNumber('pembelian')
            ->name('create');

        Route::post('/{pembelian}', [GoodsReceiptController::class, 'store'])
            ->whereNumber('pembelian')
            ->name('store');

        Route::get('/{grn}', [GoodsReceiptController::class, 'show'])
            ->whereNumber('grn')
            ->name('show');

        Route::post('/{grn}/approve', [GoodsReceiptController::class, 'approve'])
            ->whereNumber('grn')
            ->name('approve');

        Route::delete('/{grn}', [GoodsReceiptController::class, 'destroy'])
            ->whereNumber('grn')
            ->name('destroy');
    });

    // Alias tunggal (jika masih dipakai di UI lama)
    Route::prefix('goods-receipt')->name('goods-receipt.')->group(function () {
        Route::get('/', [GoodsReceiptController::class, 'index'])->name('index');
        Route::get('/create/{purchase}', [GoodsReceiptController::class, 'create'])
            ->whereNumber('purchase')
            ->name('create');
        Route::post('/{purchase}', [GoodsReceiptController::class, 'store'])
            ->whereNumber('purchase')
            ->name('store');
        Route::get('/{grn}', [GoodsReceiptController::class, 'show'])
            ->whereNumber('grn')
            ->name('show');
    });

    /* ================= PEMBELIAN (DIRECT PURCHASE) ================= */
    Route::prefix('pembelian')->name('pembelian.')->group(function () {
        Route::get('/po/search', [PembelianController::class, 'searchPO'])->name('po.search');
        Route::get('/products/search', [PembelianController::class, 'searchProducts'])->name('products.search');

        Route::get('/search-po', [PembelianController::class, 'searchPO'])->name('search-po');
        Route::get('/search-products', [PembelianController::class, 'searchProducts'])->name('search-products');

        Route::get('/', [PembelianController::class, 'create'])->name('index');
        Route::get('/create', [PembelianController::class, 'create'])->name('create');
        Route::post('/', [PembelianController::class, 'store'])->name('store');

        Route::get('/po/{poNo}', [PembelianController::class, 'getPO'])->name('po.get');
        Route::get('/get-po/{poNo}', [PembelianController::class, 'getPO'])->name('get-po');

        Route::post('/{id}/recalculate', [PembelianController::class, 'recalculate'])
            ->whereNumber('id')
            ->name('recalculate');
    });

    /* ================= STOCK OBAT (INVENTORY) ================= */
    Route::prefix('stockobat')->name('stockobat.')->group(function () {
        Route::get('/export-pdf', [StockObatController::class, 'exportPdf'])->name('exportPdf');
        Route::get('/export-excel', [StockObatController::class, 'exportExcel'])->name('exportExcel');

        Route::get('/', [StockObatController::class, 'index'])->name('index');
        Route::get('/create', [StockObatController::class, 'create'])->name('create');
        Route::post('/', [StockObatController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [StockObatController::class, 'edit'])->name('edit');
        Route::put('/{id}', [StockObatController::class, 'update'])->name('update');
        Route::delete('/{id}', [StockObatController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [StockObatController::class, 'show'])->name('show');
    });

    /* ================= REPORTS / LAPORAN ================= */
    Route::prefix('reports')->name('reports.')->group(function () {

        // Laporan Penjualan (Sales)
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/export-csv', [SalesReportController::class, 'exportSalesCsv'])->name('export');
            Route::get('/export-pdf', [SalesReportController::class, 'exportSalesPdf'])->name('export.pdf');
            Route::get('/items-export-csv', [SalesReportController::class, 'exportItemsCsv'])->name('items_export');
            Route::get('/items-export-pdf', [SalesReportController::class, 'exportItemsPdf'])->name('items_export.pdf');

            Route::get('/', [SalesReportController::class, 'index'])->name('index');
            Route::get('/items', [SalesReportController::class, 'items'])->name('items');
            Route::get('/{sale}', [SalesReportController::class, 'show'])
                ->whereNumber('sale')
                ->name('show');
        });

        // Laporan Purchase Orders
        Route::prefix('purchases')->name('purchases.')->group(function () {
            Route::get('/export/pdf', [PurchaseReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [PurchaseReportController::class, 'exportExcel'])->name('export.excel');
            Route::get('/api/statistics', [PurchaseReportController::class, 'statistics'])->name('statistics');
            Route::get('/items/report', [PurchaseReportController::class, 'itemsReport'])->name('items');

            Route::get('/', [PurchaseReportController::class, 'index'])->name('index');
            Route::get('/{purchase}', [PurchaseReportController::class, 'show'])
                ->whereNumber('purchase')
                ->name('show');
        });

        // Laporan Pembelian
        Route::prefix('pembelian')->name('pembelian.')->group(function () {
            Route::get('/export/pdf', [PembelianReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [PembelianReportController::class, 'exportExcel'])->name('export.excel');
            Route::get('/api/statistics', [PembelianReportController::class, 'statistics'])->name('statistics');
            Route::get('/items/report', [PembelianReportController::class, 'itemsReport'])->name('items');
            Route::get('/hutang/report', [PembelianReportController::class, 'hutangReport'])->name('hutang');

            Route::get('/', [PembelianReportController::class, 'index'])->name('index');
            Route::get('/{pembelian}', [PembelianReportController::class, 'show'])
                ->whereNumber('pembelian')
                ->name('show');
        });

        // Laporan Expired
        Route::prefix('expired')->name('expired.')->group(function () {
            Route::get('/export', [LaporanExpiredController::class, 'export'])->name('export');
            Route::get('/', [LaporanExpiredController::class, 'index'])->name('index');
        });
    });

    /* ================= LAPORAN SUPPLIER ================= */
    Route::get('/purchasing/suppliers-report', [SupplierReportController::class, 'index'])
        ->name('purchasing.suppliers.report');

    /* ================= API ROUTES ================= */
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/upcoming-expired', [LaporanExpiredController::class, 'getUpcomingExpired'])
            ->name('upcoming-expired');
    });
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
