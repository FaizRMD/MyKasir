# Dokumen Pengujian Manual - MyKasir POS Apotek

## Daftar Fitur yang Diuji
1. Autentikasi & Otorisasi
2. Manajemen Pengguna (Admin)
3. Dashboard
4. Profile
5. Kasir/POS
6. Purchase Order (PO)
7. Penerimaan Barang (Goods Receipt)
8. Master Data (Produk, Supplier, Golongan, Lokasi, Apoteker)
9. Stock Obat
10. Laporan (Sales, Purchase, Pembelian, Expired, Supplier)

---

## 1. PENGUJIAN AUTENTIKASI & OTORISASI

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| AUTH-BB-01 | Login | Black-box | Akun sudah dibuat oleh admin | 1. Buka halaman login<br>2. Masukkan email dan password<br>3. Klik Login | Email: admin@test.com<br>Password: password123 | Login berhasil, redirect ke dashboard (admin/owner) atau kasir (kasir) | High |
| AUTH-BB-02 | Login | Black-box | - | 1. Buka halaman login<br>2. Masukkan email salah<br>3. Klik Login | Email: salah@test.com<br>Password: apapun | Muncul pesan error "These credentials do not match our records" | High |
| AUTH-BB-03 | Login | Black-box | - | 1. Buka halaman login<br>2. Masukkan email benar, password salah<br>3. Klik Login | Email: admin@test.com<br>Password: salah123 | Muncul pesan error autentikasi gagal | High |
| AUTH-BB-04 | Forgot Password | Black-box | Akun ada di database | 1. Klik "Forgot Password"<br>2. Masukkan email<br>3. Submit | Email: admin@test.com | Email reset password terkirim | Medium |
| AUTH-BB-05 | Reset Password | Black-box | Link reset valid dari email | 1. Buka link reset<br>2. Masukkan password baru<br>3. Konfirmasi password<br>4. Submit | Password: newpass123<br>Confirm: newpass123 | Password berhasil direset, bisa login dengan password baru | Medium |
| AUTH-BB-06 | Logout | Black-box | User sudah login | 1. Klik menu user di topbar<br>2. Klik Logout | - | Logout berhasil, redirect ke login | High |
| AUTH-WB-01 | Middleware Auth | White-box | - | Akses route protected tanpa login | URL: /dashboard | Redirect ke login | High |
| AUTH-WB-02 | Middleware Role | White-box | Login sebagai kasir | Akses route admin only (products.create) | URL: /products/create | Redirect atau 403 Forbidden | High |
| AUTH-WB-03 | Middleware Role | White-box | Login sebagai kasir | Akses route kasir dapat akses | URL: /sale-items | Halaman kasir tampil normal | High |

---

## 2. PENGUJIAN MANAJEMEN PENGGUNA (ADMIN)

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| USER-BB-01 | Tambah User | Black-box | Login sebagai admin/owner | 1. Buka menu Master Data > Pengguna<br>2. Isi form nama, email, password, role<br>3. Submit | Nama: Kasir Budi<br>Email: kasir1@test.com<br>Password: kasir123<br>Role: kasir | User berhasil dibuat, muncul pesan sukses | High |
| USER-BB-02 | Tambah User | Black-box | Login sebagai admin | 1. Buka form tambah user<br>2. Isi email yang sudah ada<br>3. Submit | Email: admin@test.com (sudah ada) | Muncul error "email already exists" | High |
| USER-BB-03 | Tambah User | Black-box | Login sebagai admin | 1. Isi password < 8 karakter<br>2. Submit | Password: 123 | Muncul error "password min 8 characters" | Medium |
| USER-BB-04 | Tambah User | Black-box | Login sebagai admin | 1. Password dan konfirmasi tidak sama<br>2. Submit | Password: kasir123<br>Confirm: kasir456 | Muncul error "password confirmation doesn't match" | Medium |
| USER-BB-05 | Tambah User | Black-box | Login sebagai admin | 1. Tidak pilih role<br>2. Submit | Role: kosong | Muncul error "role is required" | Medium |
| USER-WB-01 | Validasi User | White-box | - | Controller menerima data tanpa role | Data POST tanpa field role | ValidationException, rollback | High |
| USER-WB-02 | Role Assignment | White-box | - | User dibuat dengan role kasir | Role: kasir | User punya role kasir di spatie permissions | High |
| USER-WB-03 | Password Hash | White-box | - | Password di-hash sebelum disimpan | Password: kasir123 | Password tersimpan sebagai bcrypt hash | High |

---

## 3. PENGUJIAN DASHBOARD

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| DASH-BB-01 | Dashboard Admin | Black-box | Login sebagai admin/owner | Buka dashboard | - | Tampil metrik: total sales, purchase, stock, expired items | High |
| DASH-BB-02 | Dashboard Kasir | Black-box | Login sebagai kasir | Buka URL /dashboard | - | Redirect ke /sale-items (kasir tidak boleh ke dashboard) | High |
| DASH-BB-03 | Metrik Real-time | Black-box | Ada transaksi hari ini | Refresh dashboard | - | Metrik ter-update sesuai data terbaru | Medium |
| DASH-WB-01 | Query Efficiency | White-box | - | Dashboard load dengan 1000+ data | - | Query optimized, load time < 2 detik | Low |

---

## 4. PENGUJIAN PROFILE

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| PROF-BB-01 | Edit Profile | Black-box | Login | 1. Buka menu Profile<br>2. Ubah nama<br>3. Save | Nama: Admin Baru | Nama ter-update, muncul pesan sukses | Medium |
| PROF-BB-02 | Edit Email | Black-box | Login | 1. Ubah email<br>2. Save | Email: newemail@test.com | Email ter-update jika unik | Medium |
| PROF-BB-03 | Ganti Password | Black-box | Login | 1. Masukkan current password<br>2. Password baru + confirm<br>3. Save | Current: lama123<br>New: baru123 | Password berhasil diganti | Medium |
| PROF-BB-04 | Ganti Password | Black-box | Login | 1. Current password salah<br>2. Submit | Current: salah | Error "current password incorrect" | Medium |

---

## 5. PENGUJIAN KASIR/POS

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| POS-BB-01 | Tambah Item Jual | Black-box | Login kasir, ada produk | 1. Buka /sale-items<br>2. Pilih produk<br>3. Set qty<br>4. Add to cart | Produk: Paracetamol<br>Qty: 2 | Item masuk cart, subtotal = qty * harga | High |
| POS-BB-02 | Update Qty Item | Black-box | Item sudah di cart | 1. Ubah qty item di cart<br>2. Update | Qty: 5 | Subtotal ter-update otomatis | High |
| POS-BB-03 | Hapus Item | Black-box | Item sudah di cart | 1. Klik hapus item<br>2. Confirm | - | Item hilang dari cart | Medium |
| POS-BB-04 | Checkout | Black-box | Ada item di cart | 1. Klik Checkout<br>2. Pilih pembayaran<br>3. Confirm | Bayar: Cash | Transaksi tersimpan, stok berkurang, cart kosong | High |
| POS-BB-05 | Cetak Struk | Black-box | Transaksi selesai | 1. Klik Cetak Struk | - | Struk PDF/print muncul dengan detail lengkap | Medium |
| POS-BB-06 | Stok Kurang | Black-box | Produk stok = 5 | 1. Pilih produk<br>2. Qty = 10<br>3. Checkout | Qty: 10 (stok 5) | Error "insufficient stock" | High |
| POS-WB-01 | Stock Deduction | White-box | - | Checkout dengan item qty=3 | - | Product.stock berkurang 3, dalam transaksi DB | High |
| POS-WB-02 | Transaction Log | White-box | - | Sale berhasil | - | Sale & SaleItem tersimpan dengan relasi benar | High |

---

## 6. PENGUJIAN PURCHASE ORDER (PO)

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| PO-BB-01 | Buat PO | Black-box | Login admin, ada supplier & produk | 1. Buka Purchases > Create<br>2. Pilih supplier<br>3. Tambah item (produk, qty, harga)<br>4. Submit | Supplier: PT ABC<br>Item: Paracetamol qty 100 @ 5000 | PO tersimpan, status draft/ordered | High |
| PO-BB-02 | Edit PO | Black-box | PO status draft | 1. Buka PO<br>2. Edit qty/harga<br>3. Save | Qty: 150 | PO ter-update, total dihitung ulang | Medium |
| PO-BB-03 | Hapus Item PO | Black-box | PO punya 2+ item | 1. Hapus 1 item<br>2. Save | - | Item hilang, total ter-recalculate | Medium |
| PO-BB-04 | Submit PO | Black-box | PO draft | 1. Klik Submit/Confirm<br>2. Confirm | - | Status jadi ordered/submitted | Medium |
| PO-BB-05 | Print PO | Black-box | PO ada | 1. Klik Print Blanko | - | PDF blanko PO muncul dengan detail lengkap | Low |
| PO-WB-01 | PO Total Calc | White-box | - | Buat PO dengan 2 item | Item1: qty 10 @ 5000<br>Item2: qty 5 @ 10000 | Total = (10*5000)+(5*10000) = 100000 | High |
| PO-WB-02 | PO Status Flow | White-box | - | PO dibuat → received penuh | - | Status: draft → ordered → received | High |

---

## 7. PENGUJIAN PENERIMAAN BARANG (GOODS RECEIPT)

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| GRN-BB-01 | Buat GRN Draft | Black-box | Login admin, ada PO dengan 2 item (qty 10, 5) | 1. Buka Goods Receipts > Create dari PO<br>2. Isi tanggal terima<br>3. Item A qty: 6 (< 10)<br>Item B qty: 5<br>4. Isi batch/exp<br>5. Submit | Tanggal: hari ini<br>Qty A: 6<br>Qty B: 5<br>Batch: LOT123 | GRN tersimpan status draft, stok TIDAK berubah, qty_received tetap 0 | High |
| GRN-BB-02 | Cek Detail Draft | Black-box | GRN draft ada | Buka detail GRN | - | Status: draft, batch/exp tampil, tombol Approve muncul | High |
| GRN-BB-03 | Approve GRN | Black-box | GRN draft dari test GRN-BB-01 | 1. Buka detail GRN<br>2. Klik Approve<br>3. Confirm | - | Status: received, stok A +6, stok B +5, qty_received A=6 B=5, status PO partial_received | High |
| GRN-BB-04 | Approve Ganda | Black-box | GRN sudah approved | 1. Klik Approve lagi | - | Muncul info "sudah dikonfirmasi", stok tidak berubah | High |
| GRN-BB-05 | Hapus GRN Draft | Black-box | GRN draft | 1. Klik Hapus<br>2. Confirm | - | GRN hilang, stok tetap, qty_received tetap 0 | Medium |
| GRN-BB-06 | Hapus GRN Received | Black-box | GRN received (dari GRN-BB-03) | 1. Klik Hapus<br>2. Confirm | - | GRN hilang, stok A -6 B -5 (revert), qty_received A=0 B=0, status PO kembali draft | High |
| GRN-BB-07 | Validasi Qty > Outstanding | Black-box | PO item qty=10, sudah terima 6 | 1. Buat GRN baru<br>2. Qty = 10 (seharusnya max 4) | Qty: 10 | Error validasi atau front-end block | Medium |
| GRN-BB-08 | Filter Tanggal | Black-box | Ada GRN dengan tanggal berbeda | 1. Filter date_from - date_to<br>2. Submit | 01/12/2025 - 10/12/2025 | Hanya GRN di rentang tersebut yang muncul | Medium |
| GRN-BB-09 | Filter Supplier | Black-box | Ada GRN dari supplier berbeda | 1. Isi filter supplier<br>2. Submit | Supplier: PT ABC | Hanya GRN dari PT ABC yang tampil | Medium |
| GRN-BB-10 | Pagination | Black-box | Ada 10+ GRN | Navigasi halaman dengan prev/next | - | Paginasi 5 per halaman berfungsi, prev/next aktif | Low |
| GRN-WB-01 | Store Draft Logic | White-box | - | Controller store() dipanggil | - | GRN dibuat, GoodsReceiptItem dibuat, TIDAK ada update qty_received/stock | High |
| GRN-WB-02 | Approve Logic | White-box | GRN draft | Controller approve() | - | Dalam transaksi: lockForUpdate, qty_received += qty (clamp ≤ qty PO), stock +=, status received | High |
| GRN-WB-03 | Destroy Received | White-box | GRN received | Controller destroy() | - | Dalam transaksi: qty_received -=, stock -=, status PO recalculate, delete GRN | High |
| GRN-WB-04 | Destroy Draft | White-box | GRN draft | Controller destroy() | - | Hanya delete GRN, TIDAK revert qty_received/stock | Medium |
| GRN-WB-05 | Clamp Qty | White-box | - | Approve GRN qty 6, PO qty 10, sudah terima 7 | Receive: 6 (total jadi 13) | qty_received di-clamp jadi min(10, 7+6) = 10 (tidak over) | High |

---

## 8. PENGUJIAN MASTER DATA - PRODUK

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| PROD-BB-01 | Tambah Produk | Black-box | Login admin | 1. Buka Products > Create<br>2. Isi semua field<br>3. Submit | Nama: Paracetamol<br>SKU: PAR001<br>Harga: 5000<br>Stok: 100 | Produk tersimpan | High |
| PROD-BB-02 | Tambah Produk Duplikat SKU | Black-box | Produk PAR001 ada | 1. Tambah produk baru<br>2. SKU sama | SKU: PAR001 | Error "SKU already exists" | High |
| PROD-BB-03 | Edit Produk | Black-box | Produk ada | 1. Edit nama/harga<br>2. Save | Harga: 6000 | Perubahan tersimpan | Medium |
| PROD-BB-04 | Hapus Produk | Black-box | Produk tidak pernah dipakai | 1. Klik Delete<br>2. Confirm | - | Produk terhapus dari daftar | Medium |
| PROD-BB-05 | Export PDF | Black-box | Ada produk | 1. Klik Export PDF | - | PDF list produk ter-download | Low |
| PROD-BB-06 | Export Excel | Black-box | Ada produk | 1. Klik Export Excel | - | File Excel ter-download | Low |
| PROD-BB-07 | Search Produk | Black-box | Ada banyak produk | 1. Ketik keyword di search<br>2. Enter | Keyword: "Para" | Produk yang mengandung "Para" tampil | Medium |
| PROD-WB-01 | SKU Unique | White-box | - | Insert 2 produk SKU sama | SKU: PAR001 | Database constraint/validation error | High |

---

## 9. PENGUJIAN MASTER DATA - SUPPLIER

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| SUPP-BB-01 | Tambah Supplier | Black-box | Login admin | 1. Buka Suppliers > Create<br>2. Isi nama, alamat, kontak<br>3. Submit | Nama: PT ABC<br>Alamat: Jakarta<br>Phone: 081234567890 | Supplier tersimpan | High |
| SUPP-BB-02 | Edit Supplier | Black-box | Supplier ada | 1. Edit data supplier<br>2. Save | Phone: 089999999999 | Data ter-update | Medium |
| SUPP-BB-03 | Hapus Supplier | Black-box | Supplier tidak dipakai | 1. Delete supplier<br>2. Confirm | - | Supplier terhapus | Medium |
| SUPP-BB-04 | Toggle Active | Black-box | Supplier aktif | 1. Klik toggle active/inactive | - | Status berubah | Medium |
| SUPP-BB-05 | Export CSV | Black-box | Ada supplier | 1. Klik Export CSV | - | CSV file ter-download | Low |

---

## 10. PENGUJIAN MASTER DATA - GOLONGAN, LOKASI, APOTEKER

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| MAST-BB-01 | Tambah Golongan | Black-box | Login admin | 1. Buka Golongan > Create<br>2. Isi nama golongan<br>3. Submit | Nama: Obat Keras | Golongan tersimpan | Medium |
| MAST-BB-02 | Tambah Lokasi | Black-box | Login admin | 1. Buka Lokasi > Create<br>2. Isi kode lokasi<br>3. Submit | Kode: RAK-A-01 | Lokasi tersimpan | Medium |
| MAST-BB-03 | Tambah Apoteker | Black-box | Login admin | 1. Buka Apoteker > Create<br>2. Isi data apoteker<br>3. Submit | Nama: Apt. Budi<br>SIPA: 123456 | Apoteker tersimpan | Medium |
| MAST-BB-04 | Edit Golongan | Black-box | Golongan ada | 1. Edit nama<br>2. Save | - | Data ter-update | Low |
| MAST-BB-05 | Hapus Lokasi | Black-box | Lokasi tidak dipakai | 1. Delete<br>2. Confirm | - | Lokasi terhapus | Low |

---

## 11. PENGUJIAN STOCK OBAT

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| STOCK-BB-01 | Lihat Stock | Black-box | Login admin, ada produk | 1. Buka Stock Obat | - | Daftar produk dengan stok tampil | High |
| STOCK-BB-02 | Edit Stock | Black-box | Produk ada | 1. Edit stok produk<br>2. Save | Stok: 500 | Stok ter-update | Medium |
| STOCK-BB-03 | Export PDF | Black-box | Ada data stock | 1. Klik Export PDF | - | PDF stock list ter-download | Low |
| STOCK-BB-04 | Export Excel | Black-box | Ada data stock | 1. Klik Export Excel | - | Excel file ter-download | Low |
| STOCK-BB-05 | Filter Stock | Black-box | Banyak produk | 1. Filter by golongan/lokasi<br>2. Submit | - | Hasil sesuai filter | Medium |
| STOCK-WB-01 | Stock Consistency | White-box | - | Setelah GRN approve + Sale checkout | - | Stock = stok_awal + GRN - Sale | High |

---

## 12. PENGUJIAN LAPORAN PENJUALAN

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| REP-SALES-01 | Laporan Penjualan | Black-box | Login admin, ada transaksi sales | 1. Buka Reports > Laporan Penjualan<br>2. Filter tanggal<br>3. Submit | Date: 01/12 - 15/12 | List sales sesuai tanggal | High |
| REP-SALES-02 | Export PDF Sales | Black-box | Ada data sales | 1. Klik Export PDF | - | PDF laporan sales ter-download | Medium |
| REP-SALES-03 | Export CSV Sales | Black-box | Ada data sales | 1. Klik Export CSV | - | CSV sales ter-download | Low |
| REP-SALES-04 | Laporan Sales Items | Black-box | Ada transaksi | 1. Buka Items Report<br>2. Filter | - | Detail per item terjual tampil | Medium |
| REP-SALES-05 | Detail Transaksi | Black-box | Transaksi ada | 1. Klik detail sale | - | Detail item, total, payment tampil | Medium |

---

## 13. PENGUJIAN LAPORAN PURCHASE & PEMBELIAN

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| REP-PUR-01 | Laporan Purchase Order | Black-box | Login admin, ada PO | 1. Buka Reports > Purchase Order<br>2. Filter status/tanggal | Status: received | List PO status received | High |
| REP-PUR-02 | Export PDF PO | Black-box | Ada PO | 1. Klik Export PDF | - | PDF laporan PO ter-download | Medium |
| REP-PUR-03 | Laporan Pembelian | Black-box | Ada transaksi pembelian | 1. Buka Laporan Pembelian<br>2. Filter | - | List pembelian tampil | High |
| REP-PUR-04 | Detail Pembelian | Black-box | Pembelian ada | 1. Klik detail pembelian | - | Detail item, total, GRN terkait tampil | Medium |
| REP-PUR-05 | Laporan Hutang | Black-box | Ada pembelian belum lunas | 1. Buka Hutang Report | - | List hutang supplier tampil | Medium |

---

## 14. PENGUJIAN LAPORAN EXPIRED & SUPPLIER

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| REP-EXP-01 | Laporan Expired | Black-box | Ada produk dengan exp_date | 1. Buka Reports > Expired<br>2. Filter range bulan | Range: 1 bulan ke depan | Produk yang akan expired tampil | High |
| REP-EXP-02 | Export PDF Expired | Black-box | Ada data expired | 1. Klik Export PDF | - | PDF expired list ter-download | Medium |
| REP-EXP-03 | Dashboard Expired Widget | Black-box | Ada produk expired < 30 hari | 1. Buka dashboard | - | Widget/notif expired tampil | Medium |
| REP-SUPP-01 | Laporan Supplier | Black-box | Ada transaksi dengan supplier | 1. Buka Reports > Supplier<br>2. Filter supplier | Supplier: PT ABC | Riwayat transaksi supplier tampil | Medium |

---

## 15. PENGUJIAN INTEGRASI WORKFLOW LENGKAP

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| INT-01 | PO → GRN → Stock | Black-box | Login admin | 1. Buat PO (item A qty 100)<br>2. Buat GRN draft (terima 60)<br>3. Cek stok (belum naik)<br>4. Approve GRN<br>5. Cek stok lagi | Stok awal: 50 | Stok setelah approve: 50+60=110 | Critical |
| INT-02 | GRN → Sale → Stock | Black-box | Stok produk 110 dari INT-01 | 1. Kasir jual item A qty 20<br>2. Checkout<br>3. Cek stok | - | Stok: 110-20=90 | Critical |
| INT-03 | Delete GRN → Revert | Black-box | GRN approved, stok 90 | 1. Hapus GRN (terima 60)<br>2. Cek stok | - | Stok: 90-60=30 (kembali seperti sebelum GRN) | Critical |
| INT-04 | Full Cycle | Black-box | Fresh data | 1. Admin buat akun kasir<br>2. Kasir login<br>3. Admin buat PO<br>4. Admin terima barang<br>5. Kasir jual<br>6. Admin cek laporan | - | Semua data konsisten di laporan | Critical |

---

## 16. PENGUJIAN KEAMANAN

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| SEC-01 | SQL Injection | White-box | - | Input field dengan SQL command | Input: ' OR '1'='1 | Query di-escape, tidak error | High |
| SEC-02 | XSS | White-box | - | Input field dengan script tag | Input: `<script>alert('xss')</script>` | Script tidak dieksekusi, di-escape | High |
| SEC-03 | CSRF Token | White-box | - | Submit form tanpa CSRF token | - | Request ditolak 419 | High |
| SEC-04 | Password Hash | White-box | - | Cek password di database | - | Password tidak plain text, bcrypt hash | High |
| SEC-05 | Direct URL Access | Black-box | Kasir login | Akses /products/create langsung | - | 403 atau redirect | High |

---

## 17. PENGUJIAN PERFORMA

| ID | Modul | Jenis | Prasyarat | Langkah Pengujian | Input/Data Uji | Expected Result | Priority |
|----|-------|-------|-----------|-------------------|----------------|-----------------|----------|
| PERF-01 | Dashboard Load | Black-box | Database 1000+ records | 1. Login<br>2. Akses dashboard<br>3. Ukur waktu load | - | Load < 2 detik | Medium |
| PERF-02 | Laporan Large Data | Black-box | 10000+ transaksi | 1. Filter tanggal 1 tahun<br>2. Export PDF | - | PDF berhasil generate < 10 detik | Medium |
| PERF-03 | Pagination | Black-box | 1000+ produk | 1. Buka products index<br>2. Navigate pages | - | Smooth, no lag | Low |

---

## CATATAN EKSEKUSI
- **Priority High**: Wajib diuji sebelum production
- **Priority Medium**: Diuji untuk stability
- **Priority Low**: Optional, polish
- **Priority Critical**: Core business logic, blocking issue

## TEMPLATE HASIL UJI
Gunakan tabel berikut untuk mencatat hasil:

| ID Test | Status | Bug Found | Notes | Tested By | Date |
|---------|--------|-----------|-------|-----------|------|
| AUTH-BB-01 | ✅ Pass | - | - | Tester | 15/12/2025 |
| GRN-BB-01 | ❌ Fail | Stok berubah saat draft | Perlu fix di controller | Tester | 15/12/2025 |

**Status Options**: ✅ Pass | ❌ Fail | ⚠️ Partial | 🔄 Retest

---

**Dokumen ini mencakup 100+ test case untuk seluruh fitur aplikasi MyKasir POS Apotek.**
