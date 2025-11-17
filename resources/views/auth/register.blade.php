{{-- resources/views/auth/register.blade.php --}}
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar Akun | MyKasir POS Apotek</title>

  <!-- Bootstrap 5 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#4f46e5; /* indigo-600 */
      --brand2:#22c55e; /* green-500 */
    }
    body{
      min-height:100vh;
      background:
        radial-gradient(1000px 500px at -10% -10%, #e5e9ff 0, transparent 60%),
        radial-gradient(900px 500px at 110% 0%, #e6fff5 0, transparent 60%),
        linear-gradient(180deg,#ffffff,#f8fafc);
      display:flex;align-items:center;padding:24px;
    }
    .card{
      border:0;border-radius:18px;box-shadow:0 18px 40px rgba(2,6,23,.08);background:#fff;
    }
    .brand-dot{
      width:48px;height:48px;border-radius:14px;
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      display:flex;align-items:center;justify-content:center;color:#fff
    }
    .form-control:focus, .btn:focus, .form-check-input:focus{
      box-shadow:0 0 0 .25rem rgba(79,70,229,.15);
      border-color:#c7d2fe;
    }
    .btn-success{ background:var(--brand2); border-color:var(--brand2); border-radius:12px; font-weight:600 }
    .btn-success:hover{ filter:brightness(.95) }
    .input-icon{
      position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:#94a3b8
    }
    .ps-ic{ padding-left:2.2rem !important; }
  </style>
</head>
<body>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-5">
      <!-- Header -->
      <div class="text-center mb-4">
        <div class="brand-dot mx-auto mb-2">
          <i class="bi bi-capsule"></i>
        </div>
        <h1 class="h4 mb-0 fw-bold">Buat Akun</h1>
        <div class="text-secondary small">Daftar untuk menggunakan MyKasir Apotek</div>
      </div>

      <!-- Global errors -->
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <!-- Card -->
      <div class="card p-4 p-md-4">
        <form method="POST" action="{{ route('register') }}" autocomplete="off" novalidate>
          @csrf

          <!-- Name -->
          <div class="mb-3">
            <label for="name" class="form-label fw-semibold">Nama</label>
            <div class="position-relative">
              <i class="bi bi-person input-icon"></i>
              <input
                id="name"
                type="text"
                class="form-control ps-ic @error('name') is-invalid @enderror"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name">
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email</label>
            <div class="position-relative">
              <i class="bi bi-envelope input-icon"></i>
              <input
                id="email"
                type="email"
                class="form-control ps-ic @error('email') is-invalid @enderror"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username">
              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="row g-3">
            <!-- Password -->
            <div class="col-md-6">
              <label for="password" class="form-label fw-semibold">Password</label>
              <div class="position-relative">
                <i class="bi bi-shield-lock input-icon"></i>
                <input
                  id="password"
                  type="password"
                  class="form-control ps-ic @error('password') is-invalid @enderror"
                  name="password"
                  required
                  autocomplete="new-password">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-text">Gunakan password kuat (huruf, angka, simbol).</div>
            </div>

            <!-- Confirm -->
            <div class="col-md-6">
              <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password</label>
              <div class="position-relative">
                <i class="bi bi-check2-square input-icon"></i>
                <!-- WAJIB: name="password_confirmation" -->
                <input
                  id="password_confirmation"
                  type="password"
                  class="form-control ps-ic @error('password_confirmation') is-invalid @enderror"
                  name="password_confirmation"
                  required
                  autocomplete="new-password">
                @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>

          <!-- ROLE (tambahan) -->
          <div class="mb-3 mt-2">
            <label for="role" class="form-label fw-semibold">Daftar Sebagai</label>
            <div class="position-relative">
              <i class="bi bi-people input-icon"></i>
              <select
                id="role"
                name="role"
                class="form-select ps-ic @error('role') is-invalid @enderror"
                required
              >
                <option value="" disabled {{ old('role') ? '' : 'selected' }}>-- Pilih role --</option>
                <option value="admin" {{ old('role')==='admin' ? 'selected' : '' }}>Admin</option>
                <option value="kasir" {{ old('role')==='kasir' ? 'selected' : '' }}>Kasir</option>
                <option value="owner" {{ old('role')==='owner' ? 'selected' : '' }}>Owner</option>
              </select>
              @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-text">Contoh: jika kamu pemilik toko, pilih <strong>Owner</strong>.</div>
          </div>
          <!-- END ROLE -->

          <div class="d-grid mt-4">
            <button class="btn btn-success btn-lg" type="submit">
              <i class="bi bi-person-plus me-1"></i> Daftar
            </button>
          </div>
        </form>

        <div class="text-center mt-3">
          <span class="text-secondary small">Sudah punya akun?</span>
          <a href="{{ route('login') }}" class="small text-decoration-none ms-1">Masuk</a>
        </div>
      </div>

      <p class="text-center text-muted small mt-4 mb-0">© {{ date('Y') }} MyKasir Apotek</p>
    </div>
  </div>
</div>

<!-- Optional: show/hide password -->
<script>
  // Simple UX: tekan Ctrl+Shift+X untuk toggle kedua field
  window.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'x') {
      ['password','password_confirmation'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.type = el.type === 'password' ? 'text' : 'password';
      });
    }
  });
</script>
</body>
</html>
