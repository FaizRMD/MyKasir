{{-- resources/views/auth/register.blade.php --}}
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Daftar Akun | MyKasir POS Apotek</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <style>
        /* ... (style yang sama seperti sebelumnya) ... */
        :root {
            --brand-900: #5e0d0d;
            --brand-700: #8d1b1b;
            --brand-500: #b91c1c;
            --brand-light: #fef2f2;
        }

        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #5e0d0d 0%, #8d1b1b 50%, #b91c1c 100%);
            display: flex;
            align-items: center;
            padding: 24px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .card {
            border: 0;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
            background: #fff;
            position: relative;
            z-index: 10;
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--brand-900), var(--brand-700));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px rgba(94, 13, 13, 0.3);
            padding: 12px;
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .form-label {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 4px rgba(141, 27, 27, 0.1);
            border-color: var(--brand-700);
            outline: none;
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
        }

        .form-control.is-valid,
        .form-select.is-valid {
            border-color: #198754;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
            z-index: 5;
        }

        .ps-ic {
            padding-left: 3rem !important;
        }

        .btn-brand {
            background: linear-gradient(135deg, var(--brand-900), var(--brand-700));
            border: none;
            border-radius: 12px;
            font-weight: 600;
            padding: 0.875rem 1.5rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(141, 27, 27, 0.3);
        }

        .btn-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(141, 27, 27, 0.4);
            background: linear-gradient(135deg, #6f1414, #a02020);
        }

        .btn-brand:active {
            transform: translateY(0);
        }

        .form-text {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.35rem;
        }

        .invalid-feedback {
            font-size: 0.85rem;
        }

        .valid-feedback {
            font-size: 0.85rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        a {
            color: var(--brand-700);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        a:hover {
            color: var(--brand-900);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #9ca3af;
            font-size: 0.85rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }

        .divider span {
            padding: 0 1rem;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">

                <!-- Card -->
                <div class="card p-4 p-md-5">
                    <!-- Logo & Header -->
                    <div class="text-center mb-4">
                        <div class="logo-container">
                            <img src="{{ asset('images/logo.png') }}" class="logo-img" alt="MyKasir Logo">
                        </div>
                        <h1 class="h3 mb-1 fw-bold" style="color: var(--brand-900);">Buat Akun Baru</h1>
                        <div class="text-secondary">Kelola apotek Anda dengan mudah</div>
                    </div>

                    <!-- Global errors -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                                <div>
                                    <strong>Terjadi kesalahan:</strong>
                                    <ul class="mb-0 ps-3 mt-1">
                                        @foreach ($errors->all() as $e)
                                            <li>{{ $e }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" autocomplete="off" id="registerForm">
                        @csrf

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <div class="position-relative">
                                <i class="bi bi-person-fill input-icon"></i>
                                <input id="name" type="text"
                                    class="form-control ps-ic @error('name') is-invalid @enderror" name="name"
                                    value="{{ old('name') }}" placeholder="Contoh: Ahmad Fauzi" required autofocus
                                    autocomplete="name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="position-relative">
                                <i class="bi bi-envelope-fill input-icon"></i>
                                <input id="email" type="email"
                                    class="form-control ps-ic @error('email') is-invalid @enderror" name="email"
                                    value="{{ old('email') }}" placeholder="email@example.com" required
                                    autocomplete="username">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="position-relative">
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input id="password" type="password"
                                    class="form-control ps-ic @error('password') is-invalid @enderror" name="password"
                                    placeholder="Minimal 8 karakter" required autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Gunakan kombinasi huruf, angka, dan simbol
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                            <div class="position-relative">
                                <i class="bi bi-shield-check-fill input-icon"></i>
                                <input id="password_confirmation" type="password"
                                    class="form-control ps-ic @error('password_confirmation') is-invalid @enderror"
                                    name="password_confirmation" placeholder="Ulangi password" required
                                    autocomplete="new-password">
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="valid-feedback d-none" id="password-match-success">
                                        ✓ Password cocok!
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- ✅ ROLE - PENTING! -->
                        <div class="mb-4">
                            <label for="role" class="form-label">
                                Daftar Sebagai <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <i class="bi bi-people-fill input-icon"></i>
                                <select id="role" name="role"
                                    class="form-select ps-ic @error('role') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('role') ? '' : 'selected' }}>
                                        -- Pilih Role Anda --
                                    </option>
                                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>
                                        👨‍💼 Admin - Kelola sistem & laporan
                                    </option>
                                    <option value="kasir" {{ old('role') === 'kasir' ? 'selected' : '' }}>
                                        🧾 Kasir - Transaksi penjualan
                                    </option>
                                    <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>
                                        👑 Owner - Akses penuh
                                    </option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="valid-feedback d-none" id="role-selected-success">
                                        ✓ Role dipilih!
                                    </div>
                                @enderror
                            </div>
                            <div class="form-text">
                                <i class="bi bi-lightbulb me-1"></i>
                                Pilih role sesuai tanggung jawab Anda di apotek
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button class="btn btn-brand btn-lg" type="submit" id="submitBtn">
                                <i class="bi bi-person-plus-fill me-2"></i>
                                Daftar Sekarang
                            </button>
                        </div>
                    </form>

                    <div class="divider">
                        <span>atau</span>
                    </div>

                    <div class="text-center">
                        <span class="text-secondary">Sudah punya akun?</span>
                        <a href="{{ route('login') }}" class="ms-1">
                            Masuk di sini
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <p class="text-center text-white-50 small mt-4 mb-0">
                    © {{ date('Y') }} MyKasir Apotek. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        // ✅ Form validation
        const form = document.getElementById('registerForm');
        const pass = document.getElementById('password');
        const passConf = document.getElementById('password_confirmation');
        const roleSelect = document.getElementById('role');
        const submitBtn = document.getElementById('submitBtn');

        // ✅ Real-time password match validation
        passConf?.addEventListener('input', function() {
            const matchSuccess = document.getElementById('password-match-success');

            if (this.value && pass.value) {
                if (this.value === pass.value) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    matchSuccess?.classList.remove('d-none');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    matchSuccess?.classList.add('d-none');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
                matchSuccess?.classList.add('d-none');
            }
        });

        // ✅ Role selection validation
        roleSelect?.addEventListener('change', function() {
            const roleSuccess = document.getElementById('role-selected-success');

            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                roleSuccess?.classList.remove('d-none');

                // ✅ DEBUG: Console log untuk memastikan value benar
                console.log('Role dipilih:', this.value);
            } else {
                this.classList.remove('is-valid');
                roleSuccess?.classList.add('d-none');
            }
        });

        // ✅ Form submit validation
        form?.addEventListener('submit', function(e) {
            const roleValue = roleSelect.value;

            // ✅ DEBUG: Log data sebelum submit
            console.log('=== FORM SUBMIT ===');
            console.log('Role yang dipilih:', roleValue);
            console.log('Form data:', new FormData(form));

            // Validasi role wajib diisi
            if (!roleValue) {
                e.preventDefault();
                roleSelect.classList.add('is-invalid');
                alert('Silakan pilih role Anda terlebih dahulu!');
                roleSelect.focus();
                return false;
            }

            // Disable button saat submit untuk prevent double submit
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mendaftar...';
        });

        // ✅ Toggle show/hide password dengan Ctrl+Shift+X
        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'x') {
                ['password', 'password_confirmation'].forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.type = el.type === 'password' ? 'text' : 'password';
                });
            }
        });

        // ✅ Prevent form resubmit on page reload
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>
