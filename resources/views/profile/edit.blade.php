@extends('layouts.app')

@section('title', 'Edit Profil')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
    <li class="breadcrumb-item active">Profil</li>
@endsection

@push('styles')
    <style>
        .profile-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px
        }

        .avatar-lg {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 6px 18px rgba(2, 6, 23, .15)
        }

        .hint {
            color: #64748b
        }

        .grid {
            display: grid;
            gap: 1rem
        }

        @media (min-width: 992px) {
            .grid-2 {
                grid-template-columns: 320px 1fr
            }
        }
    </style>
@endpush

@section('content')
    @php
        $u = $user ?? Auth::user();
        // avatar dari route privat + cache-busting
        $avatarUrl = route('profile.avatar', $u->id) . '?v=' . ($u?->updated_at?->timestamp ?? time());
    @endphp

    {{-- ✅ Success Alert --}}
    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center alert-dismissible fade show" role="alert">
            <i data-feather="check-circle" class="me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ✅ Error Alert --}}
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-center alert-dismissible fade show" role="alert">
            <i data-feather="alert-triangle" class="me-2"></i>
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-2">
            {{-- Sidebar profil --}}
            <div class="profile-card">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ $avatarUrl }}" class="avatar-lg" id="avatarPreview" alt="Avatar">
                    <div>
                        <div class="fw-semibold">{{ $u->name }}</div>
                        <div class="small text-muted">{{ $u->email }}</div>
                        <div class="badge bg-primary mt-1 text-uppercase">
                            {{ strtoupper($u->role ?? 'kasir') }}
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Foto Profil</label>
                    <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo"
                        id="photoInput" accept="image/*">
                    <div class="form-text">jpg/png/webp maks 2MB.</div>
                    @error('photo')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    @if ($u->profile_photo_path)
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="removePhoto"
                                name="remove_photo">
                            <label class="form-check-label" for="removePhoto">Hapus foto saat simpan</label>
                        </div>
                    @endif
                </div>

                <hr class="my-3">
                <div class="hint small">
                    Terakhir diperbarui: {{ optional($u->updated_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            {{-- Form data --}}
            <div class="profile-card">
                <h5 class="mb-3">Data Akun</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $u->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $u->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password Baru (opsional)</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                            placeholder="Min 6 karakter">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control"
                            placeholder="Ulangi password baru">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                        <i data-feather="x" class="me-1"></i> Batal
                    </a>
                    <button class="btn btn-primary" type="submit">
                        <i data-feather="save" class="me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Preview foto sebelum upload
        const photoInput = document.getElementById('photoInput');
        const avatarPreview = document.getElementById('avatarPreview');
        const removePhotoCheckbox = document.getElementById('removePhoto');

        photoInput?.addEventListener('change', (e) => {
            const f = e.target.files?.[0];
            if (!f) return;

            // Validasi ukuran file (max 2MB)
            if (f.size > 2 * 1024 * 1024) {
                alert('Ukuran file maksimal 2MB!');
                photoInput.value = '';
                return;
            }

            // Validasi tipe file
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(f.type)) {
                alert('Format file harus jpg, jpeg, png, atau webp!');
                photoInput.value = '';
                return;
            }

            // Preview gambar
            const url = URL.createObjectURL(f);
            avatarPreview.src = url;

            // Uncheck "hapus foto" jika user upload foto baru
            if (removePhotoCheckbox) {
                removePhotoCheckbox.checked = false;
            }
        });

        // Jika user centang "hapus foto", kosongkan input file
        removePhotoCheckbox?.addEventListener('change', (e) => {
            if (e.target.checked) {
                photoInput.value = '';
            }
        });

        // Initialize feather icons
        feather.replace();
    </script>
@endpush
