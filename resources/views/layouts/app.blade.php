<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MyKasir POS Apotek')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --brand-900: #5e0d0d;
            --brand-700: #8d1b1b;
            --bg: #f6f8fc;
            --surface: #fff;
            --ink: #0f172a;
            --muted: #667085;
            --line: #e7ebf5;
            --shadow: 0 10px 30px rgba(15, 23, 42, .06);
            --r-2xl: 22px;
            --r-xl: 18px;
            --r-lg: 14px;
            --r: 12px;
            --pill: 999px;
            --topbar-h: 64px;
            --sidebar-w: 240px;
            --sidebar-w-collapsed: 88px;
        }

        html,
        body {
            height: 100%
        }

        body {
            font-family: Inter, system-ui, Segoe UI, Roboto, Helvetica, Arial;
            background: var(--bg);
            color: var(--ink);
            padding-top: var(--topbar-h);
            overflow-x: auto
        }

        .topbar {
            position: fixed;
            inset: 0 0 auto 0;
            height: var(--topbar-h);
            z-index: 1030;
            color: #fff;
            background: linear-gradient(90deg, var(--brand-900), var(--brand-700));
            border-bottom: 1px solid #ffffff22;
            box-shadow: 0 12px 34px rgba(2, 6, 23, .18)
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .7rem;
            font-weight: 700
        }

        .logo {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: #ffffff1f;
            border: 1px solid #ffffff3a;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: .5rem
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #fff;
            color: #1f2937;
            border: 1px solid #ffffff33
        }

        .icon-btn i {
            width: 20px;
            height: 20px
        }

        .avatar-btn {
            padding: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #ffffff55;
            background: #fff
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block
        }

        .notif-wrap {
            position: relative
        }

        .badge-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 10px;
            background: #ef4444;
            color: #fff;
            font-size: .7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            font-weight: 600;
        }

        .dropdown-menu {
            border-radius: 14px;
            border: 1px solid var(--line);
            box-shadow: var(--shadow)
        }

        .notif-item {
            display: flex;
            gap: .6rem;
            align-items: flex-start
        }

        .notif-item .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ef4444;
            margin-top: .45rem;
            flex-shrink: 0;
        }

        .notif-empty {
            padding: 2rem;
            text-align: center;
            color: var(--muted);
        }

        .wrap {
            display: grid;
            grid-template-columns: var(--sidebar-w) 1fr;
            min-height: calc(100vh - var(--topbar-h))
        }

        .sidebar {
            background: var(--surface);
            border-right: 1px solid var(--line)
        }

        .sidebar-inner {
            height: 100%;
            display: flex;
            flex-direction: column
        }

        .profile {
            display: flex;
            gap: .75rem;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid var(--line)
        }

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid var(--line);
            background: #fff
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block
        }

        .role {
            font-size: .8rem;
            color: #6b7280;
            font-weight: 600;
        }

        .menu-title {
            font-size: .72rem;
            color: #8a93a7;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 12px 16px 6px
        }

        .nav-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: .7rem;
            color: var(--ink);
            border-radius: 12px;
            padding: .56rem .8rem;
            margin: 2px 10px;
            font-weight: 500
        }

        .nav-sidebar .nav-link .icon {
            width: 19px;
            height: 19px
        }

        .nav-sidebar .nav-link:hover {
            background: rgba(141, 27, 27, .08);
            color: #5e0d0d
        }

        .nav-sidebar .nav-link.active {
            background: #fdeeee;
            color: #7d1a1a;
            border: 1px solid #f5d2d2
        }

        .submenu {
            padding-left: 44px;
            margin: .15rem 0 .55rem
        }

        .submenu a {
            display: block;
            padding: .42rem .6rem;
            margin: .12rem 10px;
            border-radius: 10px;
            color: #334155;
            border: 1px solid transparent;
            text-decoration: none
        }

        .submenu a:hover,
        .submenu a.active {
            background: #fff4f4;
            color: #7d1a1a;
            border-color: #f1caca
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 12px 16px;
            border-top: 1px solid var(--line);
            font-size: .85rem;
            color: var(--muted)
        }

        .content {
            padding: 18px 20px 26px
        }

        .breadcrumb {
            --bs-breadcrumb-divider: "›";
            margin: 0 0 12px;
            font-size: .9rem;
            color: var(--muted)
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--r-2xl);
            box-shadow: var(--shadow)
        }

        .card-header {
            background: transparent;
            border-bottom: 1px dashed var(--line)
        }

        .table-modern thead th {
            background: #f9fafb;
            border-bottom: 1px solid #eff2f7;
            font-weight: 600
        }

        .table-modern tbody td {
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top
        }

        .btn-brand {
            background: var(--brand-700);
            border-color: var(--brand-700);
            color: #fff;
            border-radius: 12px
        }

        .btn-brand:hover {
            background: #6f1414;
            border-color: #6f1414
        }

        .btn-light-soft {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--ink);
            border-radius: 12px
        }

        @media(max-width:992px) {
            .wrap {
                grid-template-columns: 1fr
            }

            .sidebar {
                position: fixed;
                inset: var(--topbar-h) 0 0 0;
                transform: translateX(-100%);
                transition: .25s;
                max-width: 282px;
                z-index: 1031
            }

            .sidebar.show {
                transform: translateX(0)
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    @php
        // User & avatar
        $currentUserId = Auth::id();
        $me = $currentUserId ? \App\Models\User::find($currentUserId) : null;

        if (session('_switched_at') && $me) {
            Auth::setUser($me);
        }

        $cacheKey = session('_switched_at', $me?->updated_at?->timestamp ?? time());
        $avatarUrl = $me ? route('profile.avatar', $me->id) . '?v=' . $cacheKey : null;

        // Notifikasi: hutang, stok menipis, expired
        $unpaidPurchases = collect();
        try {
            $unpaidPurchases = \App\Models\Pembelian::with('supplier')
                ->whereNotNull('jatuh_tempo')
                ->where('status_bayar', '!=', 'lunas')
                ->orderBy('jatuh_tempo', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Error loading unpaid purchases: ' . $e->getMessage());
        }

        $lowStockProducts = collect();
        try {
            $lowStockProducts = \App\Models\Product::whereColumn('stock', '<=', 'min_stock')
                ->orderBy('stock', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
        }

        $expiringProducts = collect();
        try {
            $expiringProducts = DB::table('pembelian_items as pi')
                ->leftJoin('products as p', 'p.id', '=', 'pi.product_id')
                ->whereNotNull('pi.exp_date')
                ->whereDate('pi.exp_date', '<=', now()->addDays(30))
                ->whereDate('pi.exp_date', '>=', now())
                ->orderBy('pi.exp_date', 'asc')
                ->limit(5)
                ->select(
                    'p.name as product_name',
                    'pi.batch_no',
                    'pi.exp_date',
                    DB::raw('DATEDIFF(pi.exp_date, CURDATE()) as sisa_hari'),
                )
                ->get();
        } catch (\Exception $e) {
        }

        $notifCount = $unpaidPurchases->count() + $lowStockProducts->count() + $expiringProducts->count();
    @endphp

    {{-- TOPBAR --}}
    <nav class="navbar topbar navbar-expand-lg">
        <div class="container-fluid py-2">
            <div class="d-flex align-items-center gap-2">
                <button class="icon-btn d-lg-none" id="btnSidebar" aria-label="Menu">
                    <i data-feather="menu"></i>
                </button>
                <a class="navbar-brand text-white brand" href="{{ url('/') }}">
                    <span class="logo">
                        <img src="{{ asset('images/logo.png') }}" class="logo-img" alt="Logo">
                    </span>
                    <span>MyKasir Apotek</span>
                </a>
            </div>

            <div class="top-actions">
                {{-- Notifikasi --}}
                <div class="dropdown notif-wrap">
                    <button class="icon-btn position-relative" data-bs-toggle="dropdown" title="Notifikasi">
                        <i data-feather="bell"></i>
                        @if ($notifCount > 0)
                            <span class="badge-dot">{{ $notifCount }}</span>
                        @endif
                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:420px; max-height: 600px;">
                        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="fw-semibold">
                                <i class="bi bi-bell-fill me-1"></i> Notifikasi
                                @if ($notifCount > 0)
                                    <span class="badge bg-danger rounded-pill ms-1">{{ $notifCount }}</span>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0"
                                onclick="window.location.reload()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>

                        <div class="p-2" style="max-height: 450px; overflow-y: auto;">
                            @if ($notifCount == 0)
                                <div class="notif-empty">
                                    <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                                    <div class="mt-2 fw-semibold">Semua Baik-Baik Saja</div>
                                    <small class="text-muted">Tidak ada notifikasi penting</small>
                                </div>
                            @else
                                {{-- HUTANG / JATUH TEMPO --}}
                                @if ($unpaidPurchases->count())
                                    <div class="px-1 pb-1">
                                        <small class="text-uppercase text-muted fw-semibold d-block mb-1">
                                            Hutang / Jatuh Tempo
                                        </small>
                                        @foreach ($unpaidPurchases as $p)
                                            @php
                                                $jt = $p->jatuh_tempo ? \Carbon\Carbon::parse($p->jatuh_tempo) : null;
                                            @endphp
                                            <div class="notif-item py-2 px-2 rounded-3 mb-1"
                                                style="background:#fff7f7;">
                                                <span class="dot" style="background:#ef4444;"></span>
                                                <div>
                                                    <div class="fw-semibold">
                                                        {{ $p->supplier?->nama ?? ($p->supplier?->name ?? 'Supplier') }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        Jatuh tempo:
                                                        <strong>
                                                            {{ $jt ? $jt->format('d M Y') : '-' }}
                                                        </strong>
                                                    </div>
                                                    @if (!empty($p->no_faktur ?? ($p->kode ?? null)))
                                                        <div class="small text-muted">
                                                            Faktur:
                                                            {{ $p->no_faktur ?? $p->kode }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="text-end mt-1">
                                            <a href="{{ route('reports.pembelian.hutang') }}"
                                                class="small text-decoration-none">
                                                Lihat detail hutang <i class="bi bi-chevron-right small"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                @endif

                                {{-- STOK MENIPIS --}}
                                @if ($lowStockProducts->count())
                                    <div class="px-1 pb-1">
                                        <small class="text-uppercase text-muted fw-semibold d-block mb-1">
                                            Stok Menipis
                                        </small>
                                        @foreach ($lowStockProducts as $prod)
                                            <div class="notif-item py-2 px-2 rounded-3 mb-1"
                                                style="background:#fffdf5;">
                                                <span class="dot" style="background:#f97316;"></span>
                                                <div>
                                                    <div class="fw-semibold">
                                                        {{ $prod->name ?? 'Produk' }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        Stok:
                                                        <strong>{{ $prod->stock ?? 0 }}</strong>
                                                        @if (!is_null($prod->min_stock))
                                                            &nbsp;• Min:
                                                            <strong>{{ $prod->min_stock }}</strong>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="text-end mt-1">
                                            <a href="{{ route('stockobat.index') }}"
                                                class="small text-decoration-none">
                                                Lihat stok <i class="bi bi-chevron-right small"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                @endif

                                {{-- OBAT MENDekati EXPIRED --}}
                                @if ($expiringProducts->count())
                                    <div class="px-1 pb-1">
                                        <small class="text-uppercase text-muted fw-semibold d-block mb-1">
                                            Obat Mendekati Expired
                                        </small>
                                        @foreach ($expiringProducts as $row)
                                            @php
                                                $expDate = $row->exp_date
                                                    ? \Carbon\Carbon::parse($row->exp_date)
                                                    : null;
                                                $sisaHari = $row->sisa_hari ?? null;
                                            @endphp
                                            <div class="notif-item py-2 px-2 rounded-3 mb-1"
                                                style="background:#f5f9ff;">
                                                <span class="dot" style="background:#0ea5e9;"></span>
                                                <div>
                                                    <div class="fw-semibold">
                                                        {{ $row->product_name ?? 'Produk' }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        Exp:
                                                        <strong>
                                                            {{ $expDate ? $expDate->format('d M Y') : '-' }}
                                                        </strong>
                                                        @if (!empty($row->batch_no))
                                                            • Batch: {{ $row->batch_no }}
                                                        @endif
                                                    </div>
                                                    @if (!is_null($sisaHari))
                                                        <div class="small text-muted">
                                                            Sisa {{ $sisaHari }} hari
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="text-end mt-1">
                                            <a href="{{ route('reports.expired.index') }}"
                                                class="small text-decoration-none">
                                                Lihat laporan expired <i class="bi bi-chevron-right small"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Settings --}}
                <div class="dropdown">
                    <button class="icon-btn" data-bs-toggle="dropdown" title="Pengaturan">
                        <i data-feather="settings"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person me-2"></i>Profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                data-bs-target="#switchAccountModal">
                                <i class="bi bi-arrow-left-right me-2"></i>Beralih Akun
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

                {{-- Avatar --}}
                <div class="dropdown">
                    <button class="avatar-btn" data-bs-toggle="dropdown" title="Akun">
                        <img class="avatar-img" src="{{ $avatarUrl }}" alt="Avatar" id="topAvatar">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <div class="fw-semibold">{{ $me?->name ?? 'Pengguna' }}</div>
                            <div class="small text-muted">{{ $me?->email ?? '-' }}</div>
                            <div class="badge bg-primary mt-1 text-uppercase">{{ $me?->role ?? '-' }}</div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear me-2"></i>Pengaturan Profil
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    {{-- Modal Switch Account --}}
    <div class="modal fade" id="switchAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 18px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-left-right me-2"></i>Beralih Akun
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="switchAccountForm" method="POST" action="{{ route('switch.account') }}">
                        @csrf
                        <div class="alert alert-info d-flex align-items-start">
                            <i class="bi bi-info-circle me-2 mt-1"></i>
                            <div class="small">
                                Masukkan email dan password akun yang ingin Anda gunakan untuk beralih tanpa logout.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Akun Tujuan</label>
                            <input type="email" name="email" class="form-control"
                                placeholder="email@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Akun Tujuan</label>
                            <input type="password" name="password" class="form-control"
                                placeholder="Masukkan password" required>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start mb-3">
                            <i class="bi bi-shield-check me-2 mt-1"></i>
                            <div class="small">
                                Untuk keamanan, Anda harus memasukkan password dari akun yang akan digunakan.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-brand">
                                <i class="bi bi-arrow-repeat me-1"></i> Beralih Akun
                            </button>
                            <button type="button" class="btn btn-light-soft" data-bs-dismiss="modal">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- SIDEBAR & CONTENT --}}
    <div class="wrap">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-inner">
                <div class="profile">
                    <div class="avatar">
                        <img src="{{ $avatarUrl }}" alt="Avatar" id="sidebarAvatar">
                    </div>
                    <div>
                        <div class="fw-semibold" id="sidebarName">{{ $me?->name ?? 'Pengguna' }}</div>
                        <div class="role text-uppercase" id="sidebarRole">{{ $me?->role ?? '-' }}</div>
                    </div>
                </div>

                <div class="menu-title">Utama</div>
                <nav class="nav nav-sidebar flex-column mb-1">
                    @php
                        $dashUrl = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/');
                        $isDash = request()->is('/') || request()->is('dashboard') || request()->is('dashboard/*');
                    @endphp
                    <a class="nav-link {{ $isDash ? 'active' : '' }}" href="{{ $dashUrl }}">
                        <i data-feather="home" class="icon"></i><span class="text">Dashboard</span>
                    </a>
                </nav>

                <div class="menu-title">Kasir</div>
                <nav class="nav nav-sidebar flex-column">
                    <a href="#kasirSub"
                        class="nav-link {{ request()->is('pos*') || request()->is('sale-items*') ? 'active' : '' }}"
                        data-bs-toggle="collapse" role="button"
                        aria-expanded="{{ request()->is('pos*') || request()->is('sale-items*') ? 'true' : 'false' }}">
                        <i data-feather="shopping-cart" class="icon"></i><span class="text">Kasir</span>
                        <span class="ms-auto" data-feather="chevron-down"></span>
                    </a>
                    <div class="collapse submenu {{ request()->is('pos*') || request()->is('sale-items*') ? 'show' : '' }}"
                        id="kasirSub">
                        <a href="{{ url('/sale-items') }}"
                            class="{{ request()->is('sale-items') ? 'active' : '' }}">Transaksi</a>
                    </div>
                </nav>

                <div class="menu-title">Pembelian</div>
                <nav class="nav nav-sidebar flex-column">
                    <a href="#purchaseSub"
                        class="nav-link {{ request()->is('purchases*') || request()->is('goods-receipt*') || request()->is('pembelian*') ? 'active' : '' }}"
                        data-bs-toggle="collapse" role="button">
                        <i data-feather="archive" class="icon"></i><span class="text">Pembelian</span>
                        <span class="ms-auto" data-feather="chevron-down"></span>
                    </a>
                    <div class="collapse submenu {{ request()->is('purchases*') || request()->is('goods-receipt*') || request()->is('pembelian*') ? 'show' : '' }}"
                        id="purchaseSub">
                        <a href="{{ route('purchases.index') }}"
                            class="{{ request()->is('purchases*') ? 'active' : '' }}">Purchase Order</a>
                        <a href="{{ route('pembelian.create') }}"
                            class="{{ request()->is('pembelian*') ? 'active' : '' }}">Pembelian</a>
                        <a href="{{ route('goods-receipt.index') }}"
                            class="{{ request()->is('goods-receipt*') ? 'active' : '' }}">Penerimaan Barang</a>
                    </div>
                </nav>

                <div class="menu-title">Master Data</div>
                <nav class="nav nav-sidebar flex-column">
                    <a href="#masterSub"
                        class="nav-link {{ request()->is('products*') || request()->is('suppliers*') || request()->is('golongan-obat*') || request()->is('lokasi-obat*') || request()->is('apoteker*') ? 'active' : '' }}"
                        data-bs-toggle="collapse" role="button">
                        <i data-feather="grid" class="icon"></i><span class="text">Master Data</span>
                        <span class="ms-auto" data-feather="chevron-down"></span>
                    </a>
                    <div class="collapse submenu {{ request()->is('products*') || request()->is('suppliers*') || request()->is('golongan-obat*') || request()->is('lokasi-obat*') || request()->is('apoteker*') ? 'show' : '' }}"
                        id="masterSub">
                        <a href="{{ route('products.index') }}"
                            class="{{ request()->is('products*') ? 'active' : '' }}">Produk</a>
                        <a href="{{ route('suppliers.index') }}"
                            class="{{ request()->is('suppliers*') ? 'active' : '' }}">Supplier</a>
                        <a href="{{ route('golongan-obat.index') }}"
                            class="{{ request()->is('golongan-obat*') ? 'active' : '' }}">Golongan Obat</a>
                        <a href="{{ route('lokasi-obat.index') }}"
                            class="{{ request()->is('lokasi-obat*') ? 'active' : '' }}">Lokasi Obat</a>
                        <a href="{{ route('apoteker.index') }}"
                            class="{{ request()->is('apoteker*') ? 'active' : '' }}">Apoteker</a>
                    </div>
                </nav>

                <div class="menu-title">Laporan</div>
                <nav class="nav nav-sidebar flex-column mb-2">
                    <a href="#reportSub"
                        class="nav-link {{ request()->is('reports*') || request()->is('stockobat*') ? 'active' : '' }}"
                        data-bs-toggle="collapse" role="button">
                        <i data-feather="bar-chart-2" class="icon"></i><span class="text">Laporan</span>
                        <span class="ms-auto" data-feather="chevron-down"></span>
                    </a>
                    <div class="collapse submenu {{ request()->is('reports*') || request()->is('stockobat*') ? 'show' : '' }}"
                        id="reportSub">
                        <a href="{{ route('reports.sales.index') }}"
                            class="{{ request()->routeIs('reports.sales.*') ? 'active' : '' }}">Laporan Penjualan</a>
                        <a href="{{ route('purchasing.suppliers.report') }}"
                            class="{{ request()->routeIs('purchasing.suppliers.report') ? 'active' : '' }}">Laporan
                            Supplier</a>
                        <a href="{{ route('stockobat.index') }}"
                            class="{{ request()->routeIs('stockobat.*') ? 'active' : '' }}">Laporan Stok Obat</a>
                        <a href="{{ route('reports.purchases.index') }}"
                            class="{{ request()->routeIs('reports.purchases.*') ? 'active' : '' }}">Laporan Purchase
                            Order</a>
                        <a href="{{ route('reports.pembelian.index') }}"
                            class="{{ request()->routeIs('reports.pembelian.*') ? 'active' : '' }}">Laporan
                            Pembelian</a>
                        <a href="{{ route('reports.expired.index') }}"
                            class="{{ request()->routeIs('reports.expired.*') ? 'active' : '' }}">
                            <i class="bi bi-calendar-x text-danger me-1"></i> Laporan Expired
                        </a>
                    </div>
                </nav>

                <div class="sidebar-footer">
                    <div>© {{ date('Y') }} MyKasir Apotek</div>
                    <div class="small">v1.0</div>
                </div>
            </div>
        </aside>

        <main class="content">
            @hasSection('breadcrumb')
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">@yield('breadcrumb')</ol>
                </nav>
            @endif

            @stack('page-header')

            @if (session('ok'))
                <div class="alert alert-success d-flex align-items-center">
                    <i data-feather="check-circle" class="me-2"></i>
                    <div>{{ session('ok') }}</div>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center">
                    <i data-feather="alert-triangle" class="me-2"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-center">
                    <i data-feather="alert-triangle" class="me-2"></i>
                    <div>{{ implode(', ', $errors->all()) }}</div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar mobile toggle
        const btnSidebar = document.getElementById('btnSidebar'),
            sidebar = document.getElementById('sidebar');
        btnSidebar?.addEventListener('click', () => sidebar.classList.toggle('show'));

        // Switch Account Form Handler dengan reload otomatis
        document.getElementById('switchAccountForm')?.addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

            // Set flag untuk reload setelah switch
            sessionStorage.setItem('switching_account', 'true');
        });

        // Auto reload setelah switch account berhasil
        window.addEventListener('DOMContentLoaded', function() {
            const switched = sessionStorage.getItem('switching_account');

            if (switched === 'true') {
                sessionStorage.removeItem('switching_account');

                // Hard reload untuk memastikan semua data ter-refresh dari server
                setTimeout(() => {
                    window.location.reload(true);
                }, 300);
            }
        });

        // Initialize Feather Icons
        feather.replace();

        // Initialize Bootstrap Tooltips
        Array.from(document.querySelectorAll('[data-bs-title]')).forEach(el => new bootstrap.Tooltip(el));
    </script>

    @stack('scripts')
</body>

</html>
