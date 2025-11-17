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
    :root{
      --brand:#155eef;         /* primary */
      --brand-2:#3b7cff;       /* gradient */
      --bg-1:#f6f8fc;          /* page bg */
      --card-rad:20px;
      --shadow-lg:0 18px 50px rgba(16,24,40,.12);
    }
    body{
      min-height:100vh;
      background:
        radial-gradient(1200px 600px at -20% -20%, #dce6ff 0, transparent 60%),
        radial-gradient(900px 500px at 120% 10%, #d7f7ff 0, transparent 60%),
        linear-gradient(180deg, #ffffff, var(--bg-1));
      display:flex; align-items:center; padding:24px;
    }
    .auth-card{
      border:1px solid #e6e9f4; border-radius:var(--card-rad);
      box-shadow:var(--shadow-lg); background:#fff; overflow:hidden;
    }
    .brand-bar{
      background:linear-gradient(135deg,var(--brand),var(--brand-2));
      color:#fff;
    }
    .brand-badge{
      width:54px;height:54px;border-radius:16px;
      background:rgba(255,255,255,.18);
      display:flex;align-items:center;justify-content:center;
      border:1px solid rgba(255,255,255,.32);
    }
    .form-control:focus, .form-check-input:focus, .btn:focus{
      box-shadow:0 0 0 .25rem rgba(21,94,239,.2);
      border-color:#b8ccff;
    }
    .input-icon{
      position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:#98a2b3
    }
    .ps-ic{ padding-left:2.2rem !important; }
    .btn-primary{
      background:var(--brand); border-color:var(--brand); border-radius:12px; font-weight:600
    }
    .btn-primary:hover{ background:#0f49d7; border-color:#0f49d7 }
    .muted{ color:#667085; }
    .divider{ position:relative; text-align:center; color:#98a2b3; font-size:.9rem }
    .divider:before,.divider:after{
      content:""; position:absolute; top:50%; width:40%; height:1px; background:#e6e9f4
    }
    .divider:before{ left:0 } .divider:after{ right:0 }
    .alert-slim{ border-radius:12px }
    @media (max-width: 991.98px){ .side-illustration{ display:none } }
  </style>
</head>
<body>

  <div class="container">
    <div class="row justify-content-center g-0">
      <div class="col-lg-10 col-xl-8">
        <div class="auth-card">
          <!-- Header/Brand bar -->
          <div class="brand-bar p-4 d-flex align-items-center gap-3">
            <div class="brand-badge">
              <i class="bi bi-capsule text-white fs-4"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-bold">MyKasir POS Apotek</div>
              <div class="opacity-75 small">Silakan masuk ke akun Anda</div>
            </div>
          </div>

          <div class="row g-0">
            <!-- Form side -->
            <div class="col-lg-6 p-4 p-md-5">
              <!-- Status (misal link reset terkirim) -->
              @if (session('status'))
                <div class="alert alert-success alert-slim d-flex align-items-center">
                  <i class="bi bi-check-circle me-2"></i>
                  <div>{{ session('status') }}</div>
                </div>
              @endif

              <!-- Error list -->
              @if ($errors->any())
                <div class="alert alert-danger alert-slim">
                  <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                  </ul>
                </div>
              @endif

              <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
                @csrf

                <!-- Email -->
                <div class="mb-3">
                  <label for="email" class="form-label fw-semibold">Email</label>
                  <div class="position-relative">
                    <i class="bi bi-envelope input-icon"></i>
                    <input
                      type="email"
                      id="email"
                      name="email"
                      value="{{ old('email') }}"
                      class="form-control ps-ic @error('email') is-invalid @enderror"
                      placeholder="nama@domain.com"
                      required
                      autocomplete="username">
                    @error('email')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <!-- Password -->
                <div class="mb-2">
                  <div class="d-flex justify-content-between">
                    <label for="password" class="form-label fw-semibold mb-1">Password</label>
                    @if (Route::has('password.request'))
                      <a href="{{ route('password.request') }}" class="small text-decoration-none">Lupa password?</a>
                    @endif
                  </div>
                  <div class="position-relative">
                    <i class="bi bi-shield-lock input-icon"></i>
                    <input
                      type="password"
                      id="password"
                      name="password"
                      class="form-control ps-ic @error('password') is-invalid @enderror"
                      placeholder="••••••••"
                      required
                      autocomplete="current-password">
                    <button type="button" class="btn btn-sm btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2" id="btnTogglePass" tabindex="-1" aria-label="Tampilkan password">
                      <i class="bi bi-eye"></i>
                    </button>
                    @error('password')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <!-- Remember me -->
                <div class="form-check my-3">
                  <input class="form-check-input" type="checkbox" id="remember_me" name="remember" {{ old('remember') ? 'checked' : '' }}>
                  <label class="form-check-label" for="remember_me">Ingat saya</label>
                </div>

                <button class="btn btn-primary w-100 py-2" type="submit">
                  <i class="bi bi-box-arrow-in-right me-1"></i> MASUK
                </button>

                <div class="divider my-4">atau</div>

                <!-- CTA Register -->
                @if (Route::has('register'))
                  <div class="text-center">
                    <span class="muted">Belum punya akun?</span>
                    <a href="{{ route('register') }}" class="link-primary fw-semibold text-decoration-none">Daftar sekarang</a>
                  </div>
                @endif
              </form>

              <div class="text-center mt-4 small text-secondary">
                © {{ date('Y') }} MyKasir Apotek. All rights reserved.
              </div>
            </div>

            <!-- Illustration / marketing side -->
            <div class="col-lg-6 side-illustration d-flex align-items-stretch">
              <div class="w-100 p-4 p-md-5 bg-light" style="background:
                  radial-gradient(600px 300px at 20% -10%, #e8f0ff 0, transparent 60%),
                  radial-gradient(700px 380px at 110% 20%, #e6fff8 0, transparent 60%);">
                <div class="h-100 d-flex flex-column justify-content-center">
                  <h5 class="fw-bold mb-2">Point of Sale yang gesit</h5>
                  <p class="text-secondary mb-4">
                    Kelola produk, supplier, dan transaksi apotek dengan cepat—
                    modern, aman, dan mudah dipakai tim kasir Anda.
                  </p>
                  <ul class="list-unstyled text-secondary small">
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i> Scan barcode & pencarian cepat</li>
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i> Manajemen stok & kedaluwarsa</li>
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i> Laporan PDF/Excel</li>
                  </ul>
                  <div class="text-center mt-4">
                    <i class="bi bi-upc-scan display-6 text-primary"></i>
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
      btn.querySelector('i').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
  </script>
</body>
</html>
