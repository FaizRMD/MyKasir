{{-- resources/views/auth/login.blade.php --}}
<!doctype html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | MyKasir POS Apotek</title>

    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --brand: #800020;
            /* maroon primary */
            --brand-2: #a52a3a;
            /* maroon gradient */
            --brand-light: #f8e8eb;
            /* maroon light */
            --bg-1: #faf5f6;
            /* page bg */
            --card-rad: 24px;
            --shadow-lg: 0 20px 60px rgba(128, 0, 32, .15);
            --shadow-hover: 0 25px 70px rgba(128, 0, 32, .2);
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(1400px 700px at -20% -20%, #ffe8ee 0, transparent 65%),
                radial-gradient(1000px 600px at 120% 10%, #fff0f3 0, transparent 65%),
                linear-gradient(180deg, #ffffff, var(--bg-1));
            display: flex;
            align-items: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .auth-card {
            border: 1px solid #f0d0d8;
            border-radius: var(--card-rad);
            box-shadow: var(--shadow-lg);
            background: #fff;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .auth-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .brand-bar {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-2) 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .brand-bar::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1)
            }

            50% {
                transform: scale(1.1)
            }
        }

        .logo-container {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .95);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, .4);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
            position: relative;
            z-index: 1;
        }

        .logo-container img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 1.5px solid #e6d5da;
            padding: .7rem 1rem;
            transition: all 0.2s;
        }

        .form-control:focus,
        .form-check-input:focus,
        .btn:focus,
        .form-select:focus {
            box-shadow: 0 0 0 .25rem rgba(128, 0, 32, .15);
            border-color: var(--brand);
        }

        .form-control:hover,
        .form-select:hover {
            border-color: var(--brand-2);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0616f;
            z-index: 2;
        }

        .ps-ic {
            padding-left: 2.8rem !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            border: none;
            border-radius: 12px;
            font-weight: 600;
            padding: .75rem 1.5rem;
            letter-spacing: .3px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #6b001a, #8a2230);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, .3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-outline-secondary {
            border-radius: 8px;
            border-color: #d0b5be;
            color: #800020;
        }

        .btn-outline-secondary:hover {
            background: var(--brand-light);
            border-color: var(--brand);
            color: var(--brand);
        }

        .muted {
            color: #8a5f6b;
        }

        .divider {
            position: relative;
            text-align: center;
            color: #a0757f;
            font-size: .9rem;
            font-weight: 500;
        }

        .divider:before,
        .divider:after {
            content: "";
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: linear-gradient(to right, transparent, #e6d5da, transparent);
        }

        .divider:before {
            left: 0
        }

        .divider:after {
            right: 0
        }

        .alert-slim {
            border-radius: 12px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
        }

        .alert-danger {
            background: #ffebee;
            border-left-color: #f44336;
            color: #c62828;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand-light), #ffe0e8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand);
            font-size: 1.2rem;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge-admin {
            background: linear-gradient(135deg, #800020, #a52a3a);
            color: #fff;
        }

        .badge-kasir {
            background: linear-gradient(135deg, #155eef, #3b7cff);
            color: #fff;
        }

        .link-primary {
            color: var(--brand);
            font-weight: 600;
            transition: all 0.2s;
        }

        .link-primary:hover {
            color: var(--brand-2);
            text-decoration: underline !important;
        }

        @media (max-width: 991.98px) {
            .side-illustration {
                display: none
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .auth-card {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center g-0">
            <div class="col-lg-10 col-xl-9">
                <div class="auth-card">
                    <!-- Header/Brand bar -->
                    <div class="brand-bar p-4 d-flex align-items-center gap-3">
                        <div class="logo-container">
                            <img src="{{ asset('logo.png') }}" alt="Logo MyKasir"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <i class="bi bi-capsule-pill" style="font-size:2rem;color:var(--brand);display:none"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-5">MyKasir POS Apotek</div>
                            <div class="opacity-90 small">Sistem manajemen apotek terpadu</div>
                        </div>
                        <div class="d-none d-md-block">
                            <i class="bi bi-shield-check opacity-75" style="font-size:1.8rem"></i>
                        </div>
                    </div>

                    <div class="row g-0">
                        <!-- Form side -->
                        <div class="col-lg-6 p-4 p-md-5">
                            <div class="mb-4">
                                <h4 class="fw-bold mb-2" style="color:var(--brand)">Selamat Datang Kembali</h4>
                                <p class="text-secondary mb-0">Masuk ke akun Anda untuk melanjutkan</p>
                            </div>

                            <!-- Status (misal link reset terkirim) -->
                            @if (session('status'))
                                <div class="alert alert-success alert-slim d-flex align-items-center mb-3">
                                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                                    <div>{{ session('status') }}</div>
                                </div>
                            @endif

                            <!-- Error list -->
                            @if ($errors->any())
                                <div class="alert alert-danger alert-slim mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                                        <ul class="mb-0 ps-2">
                                            @foreach ($errors->all() as $e)
                                                <li>{{ $e }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
                                @csrf

                                <!-- Role Selection -->
                                <div class="mb-3">
                                    <label for="role" class="form-label fw-semibold">Masuk Sebagai</label>
                                    <div class="position-relative">
                                        <i class="bi bi-person-badge input-icon"></i>
                                        <select id="role" name="role"
                                            class="form-select ps-ic @error('role') is-invalid @enderror" required>
                                            <option value="" selected disabled>Pilih role Anda...</option>
                                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>
                                                👤 Admin - Akses Penuh Sistem
                                            </option>
                                            <option value="kasir" {{ old('role') == 'kasir' ? 'selected' : '' }}>
                                                🛒 Kasir - Transaksi & Penjualan
                                            </option>
                                        </select>
                                        @error('role')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">Pilih sesuai role saat registrasi</small>
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">Email</label>
                                    <div class="position-relative">
                                        <i class="bi bi-envelope input-icon"></i>
                                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                                            class="form-control ps-ic @error('email') is-invalid @enderror"
                                            placeholder="nama@domain.com" required autocomplete="username">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="password" class="form-label fw-semibold mb-0">Password</label>
                                        @if (Route::has('password.request'))
                                            <a href="{{ route('password.request') }}"
                                                class="small link-primary text-decoration-none">
                                                <i class="bi bi-key me-1"></i>Lupa password?
                                            </a>
                                        @endif
                                    </div>
                                    <div class="position-relative">
                                        <i class="bi bi-shield-lock input-icon"></i>
                                        <input type="password" id="password" name="password"
                                            class="form-control ps-ic @error('password') is-invalid @enderror"
                                            placeholder="Masukkan password" required autocomplete="current-password"
                                            style="padding-right:3rem">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                            id="btnTogglePass" tabindex="-1" aria-label="Tampilkan password"
                                            style="z-index:3">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Remember me -->
                                <div class="form-check my-3">
                                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember"
                                        {{ old('remember') ? 'checked' : '' }} style="border-color:var(--brand)">
                                    <label class="form-check-label" for="remember_me">
                                        Ingat saya selama 30 hari
                                    </label>
                                </div>

                                <button class="btn btn-primary w-100 py-2 mb-3" type="submit">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>MASUK KE SISTEM
                                </button>

                                <div class="text-center mt-4 small" style="color:#a0757f">
                                    <i class="bi bi-shield-check me-1"></i>
                                    © {{ date('Y') }} MyKasir Apotek. Semua hak dilindungi.
                                </div>
                        </div>

                        <!-- Illustration / marketing side -->
                        <div class="col-lg-6 side-illustration d-flex align-items-stretch">
                            <div class="w-100 p-4 p-md-5"
                                style="background:
                  radial-gradient(700px 400px at 20% -10%, #ffe8ee 0, transparent 65%),
                  radial-gradient(800px 450px at 110% 20%, #fff5f7 0, transparent 65%),
                  linear-gradient(180deg, #fafafa, #f5f5f5);">
                                <div class="h-100 d-flex flex-column justify-content-center">
                                    <div class="mb-4">
                                        <span class="role-badge badge-admin mb-2">
                                            <i class="bi bi-star-fill"></i> Admin
                                        </span>
                                        <span class="role-badge badge-kasir ms-2">
                                            <i class="bi bi-cart-check-fill"></i> Kasir
                                        </span>
                                    </div>

                                    <h4 class="fw-bold mb-3" style="color:var(--brand)">
                                        Point of Sale Modern & Powerful
                                    </h4>
                                    <p class="text-secondary mb-4">
                                        Kelola produk, supplier, dan transaksi apotek dengan sistem role-based yang
                                        aman.
                                        Setiap pengguna memiliki akses sesuai kebutuhan.
                                    </p>

                                    <div class="mb-3 d-flex align-items-start gap-3">
                                        <div class="feature-icon">
                                            <i class="bi bi-people-fill"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold mb-1">Multi-Role Access</div>
                                            <small class="text-secondary">Admin & Kasir dengan hak akses
                                                berbeda</small>
                                        </div>
                                    </div>

                                    <div class="mb-3 d-flex align-items-start gap-3">
                                        <div class="feature-icon">
                                            <i class="bi bi-upc-scan"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold mb-1">Scan Barcode Cepat</div>
                                            <small class="text-secondary">Pencarian produk instan dengan
                                                barcode</small>
                                        </div>
                                    </div>

                                    <div class="mb-3 d-flex align-items-start gap-3">
                                        <div class="feature-icon">
                                            <i class="bi bi-graph-up-arrow"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold mb-1">Laporan Real-time</div>
                                            <small class="text-secondary">Dashboard analytics & export
                                                PDF/Excel</small>
                                        </div>
                                    </div>

                                    <div class="mb-3 d-flex align-items-start gap-3">
                                        <div class="feature-icon">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold mb-1">Keamanan Terjamin</div>
                                            <small class="text-secondary">Enkripsi data & audit trail lengkap</small>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4 p-3 rounded-3" style="background:rgba(128,0,32,.05)">
                                        <i class="bi bi-heart-pulse-fill display-6" style="color:var(--brand)"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /illustration -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle show/hide password
        const btn = document.getElementById('btnTogglePass');
        const input = document.getElementById('password');
        btn?.addEventListener('click', () => {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            const icon = btn.querySelector('i');
            icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });

        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Role selection feedback
        const roleSelect = document.getElementById('role');
        roleSelect?.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            console.log('Role dipilih:', selectedOption.value);
        });
    </script>
</body>

</html>
