@extends('layouts.app')
@section('title', 'Pembelian Obat')

@push('styles')
    <style>
        :root {
            --maroon-50: #fff5f7;
            --maroon-100: #fde4e8;
            --maroon-500: #7a1020;
            --maroon-600: #5a0c18;
            --border: #e5e7eb;
            --soft: #f9fafb;
            --danger: #dc2626;
            --ink: #111827;
            --muted: #6b7280;
        }

        body {
            background: var(--soft);
            color: var(--ink);
            font-family: 'Inter', sans-serif;
        }

        .page {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
        }

        .h-title {
            text-align: center;
            font-weight: 800;
            font-size: 28px;
            color: var(--maroon-600);
            margin-bottom: 24px;
        }

        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            margin-bottom: 16px;
        }

        .card .head {
            background: var(--maroon-50);
            padding: 10px 16px;
            font-weight: 700;
            color: var(--maroon-600);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px
        }

        .card .body {
            padding: 16px;
        }

        .label {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 4px;
            display: block;
        }

        .input,
        .select,
        .number,
        .date {
            width: 100%;
            padding: 9px 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            color: #111;
        }

        .input:focus,
        .select:focus,
        .number:focus,
        .date:focus {
            outline: none;
            border-color: var(--maroon-500);
            box-shadow: 0 0 0 3px rgba(122, 16, 32, .1);
        }

        .select option {
            color: #111;
            background-color: #fff;
        }

        .btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border);
            transition: .15s;
        }

        .btn-primary {
            background: var(--maroon-600);
            color: #fff;
            border-color: var(--maroon-600);
        }

        .btn-primary:hover {
            background: var(--maroon-500);
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
        }

        .btn-soft {
            background: var(--soft);
        }

        .btn-sm {
            font-size: 12px;
            padding: 5px 10px;
        }

        .btn-ghost {
            background: transparent;
            color: var(--maroon-600);
            border-color: transparent
        }

        .table-wrap {
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .table th,
        .table td {
            border: 1px solid var(--border);
            padding: 6px;
            vertical-align: middle;
        }

        .table th {
            background: var(--maroon-50);
            font-weight: 700;
            color: var(--maroon-600);
        }

        .modal {
            position: fixed;
            inset: 0;
            display: none;
            place-items: center;
            background: rgba(0, 0, 0, .35);
            backdrop-filter: blur(2px);
            z-index: 100;
            padding: 20px;
        }

        .modal.show {
            display: grid;
        }

        .modal .box {
            background: white;
            border-radius: 10px;
            width: min(960px, 92vw);
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
        }

        .helper {
            font-size: 12px;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--maroon-100);
            color: var(--maroon-600);
            padding: 4px 8px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px
        }

        @media(max-width:900px) {
            .grid {
                display: block
            }
        }
    </style>
@endpush

@section('content')
    <div class="page">
        <div class="h-title">Pembelian Obat</div>

        <form id="formPembelian" method="POST" action="{{ route('pembelian.store') }}" autocomplete="off">
            @csrf

            {{-- ============== HEADER ============== --}}
            <div class="card">
                <div class="head">
                    <span>Informasi Pembelian</span>
                    <span id="modeBadge" class="badge" style="display:none">Mode PO</span>
                </div>
                <div class="body grid" style="display:grid;gap:14px;grid-template-columns:repeat(3,1fr)">
                    <div>
                        <label class="label">No. PO (jika ada)</label>
                        <div style="display:flex;gap:8px">
                            <input class="input" id="po_no" name="po_no" placeholder="Pilih nomor PO" readonly>
                            <button class="btn btn-soft" type="button" id="btnCariPO">Cari</button>
                            <button class="btn btn-ghost" type="button" id="btnClearPO" title="Kosongkan PO"
                                style="display:none">✕</button>
                        </div>
                        <small class="helper">Jika pembelian dari PO, pilih untuk isi otomatis (supplier, gudang, dan item
                            langsung terisi).</small>
                    </div>

                    <div>
                        <label class="label">No. Faktur Supplier</label>
                        <input class="input" id="invoice_no" name="invoice_no"
                            placeholder="Masukkan nomor faktur supplier">
                    </div>

                    <div>
                        <label class="label">Tanggal Faktur</label>
                        <input type="date" class="date" name="invoice_date" id="invoice_date"
                            value="{{ date('Y-m-d') }}">
                    </div>

                    <div>
                        <label class="label">Supplier / PBF</label>
                        <select class="select" id="supplier_id" name="supplier_id">
                            <option value="">— Pilih Supplier —</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">Gudang Tujuan</label>
                        <select class="select" id="warehouse_id" name="warehouse_id">
                            <option value="">— Pilih Gudang —</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">Jenis Pembayaran</label>
                        <select class="select" id="payment_type" name="payment_type">
                            <option value="TUNAI">Tunai</option>
                            <option value="HUTANG">Hutang (Tempo)</option>
                            <option value="KONSINYASI">Konsinyasi</option>
                        </select>
                    </div>

                    <div id="wrapJatuhTempo" style="display:none">
                        <label class="label">Tanggal Jatuh Tempo</label>
                        <input type="date" class="date" name="due_date" id="due_date">
                    </div>

                    <div id="wrapCashbook" style="display:none">
                        <label class="label">Kas/Bank</label>
                        <select class="select" name="cashbook" id="cashbook">
                            <option value="">— Pilih Kas/Bank —</option>
                            <option value="KAS_UMUM">Kas Umum</option>
                            <option value="BANK">Bank</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">PPN (%)</label>
                        <input class="number" name="tax_percent" id="tax_percent" value="11">
                        <small class="helper">Dipakai untuk hitung HNA+PPN & total.</small>
                    </div>

                    <div>
                        <label class="label">Biaya Tambahan</label>
                        <input class="number" name="extra_cost" id="extra_cost" value="0">
                    </div>
                </div>
            </div>

            {{-- ============== TOOLBAR ============== --}}
            <div class="card">
                <div class="body"
                    style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
                    <div style="display:flex;gap:8px">
                        <button type="button" class="btn btn-soft" id="btnCariObat">+ Obat</button>
                        <button type="reset" class="btn" id="btnResetForm">Reset</button>
                        <button type="submit" class="btn btn-primary">✔ Simpan</button>
                    </div>
                    <div style="font-size:16px;font-weight:700">Total: <span id="grandBadge">0,00</span></div>
                </div>
            </div>

            {{-- ============== TABEL OBAT ============== --}}
            <div class="card">
                <div class="head">Detail Obat Pembelian</div>
                <div class="table-wrap">
                    <table class="table" id="tblItems">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Aksi</th>
                                <th>Kode</th>
                                <th>Nama Obat</th>
                                <th>Qty</th>
                                <th>Satuan</th>
                                <th>Harga Beli</th>
                                <th>Diskon (%)</th>
                                <th>Nominal Diskon</th>
                                <th>HPP</th>
                                <th>HNA+PPN</th>
                                <th>Margin</th>
                                <th>Harga Jual</th>
                                <th>Exp</th>
                                <th>Batch</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
                <div class="body helper">💡 Jika tanpa PO, klik <b>+ Obat</b> untuk memilih dari master data.</div>
            </div>

            {{-- ============== MODAL PO ============== --}}
            <div class="modal" id="modalPO">
                <div class="box card">
                    <div class="head">Cari Surat Pesanan (PO)</div>
                    <div class="body">
                        <div style="display:flex;gap:8px;margin-bottom:10px">
                            <input class="input" id="po_query" placeholder="Ketik nomor PO">
                            <button class="btn btn-primary" id="btnPOCari" type="button">Cari</button>
                            <button class="btn btn-soft" type="button" data-close="modalPO">Tutup</button>
                        </div>
                        <div class="table-wrap">
                            <table class="table" id="tblPO">
                                <thead>
                                    <tr>
                                        <th>No PO</th>
                                        <th>Tanggal</th>
                                        <th>Supplier</th>
                                        <th>Gudang</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============== MODAL OBAT ============== --}}
            <div class="modal" id="modalObat">
                <div class="box card">
                    <div class="head">Pilih Obat dari Master Data</div>
                    <div class="body">
                        <div style="display:flex;gap:8px;margin-bottom:10px">
                            <input class="input" id="prod_query" placeholder="Cari kode / nama / barcode">
                            <button class="btn btn-primary" id="btnProdCari" type="button">Cari</button>
                            <button class="btn btn-soft" type="button" data-close="modalObat">Tutup</button>
                        </div>
                        <div class="table-wrap">
                            <table class="table" id="tblProd">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Barcode</th>
                                        <th>Satuan</th>
                                        <th>Harga Terakhir</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @php
        use Illuminate\Support\Facades\Route as R;
        $poSearch = R::has('pembelian.po.search')
            ? route('pembelian.po.search')
            : (R::has('pembelian.search-po')
                ? route('pembelian.search-po')
                : '#');

        // gunakan placeholder __PO__ agar bisa di-replace manual
        $poGet = R::has('pembelian.po.get')
            ? route('pembelian.po.get', ['poNo' => '__PO__'])
            : (R::has('pembelian.get-po')
                ? route('pembelian.get-po', ['poNo' => '__PO__'])
                : '#');

        $prodSearch = R::has('pembelian.products.search')
            ? route('pembelian.products.search')
            : (R::has('pembelian.search-products')
                ? route('pembelian.search-products')
                : '#');
    @endphp
    <script>
        (function() {
            const $$ = s => document.querySelector(s);
            const $$$ = s => Array.from(document.querySelectorAll(s));
            const show = el => el.classList.add('show');
            const hide = el => el.classList.remove('show');

            // ==== helper angka ====
            const asNum = (v) => {
                if (v === null || v === undefined) return 0;
                const s = String(v).trim();
                if (s === '') return 0;
                if (s.includes(',') && s.includes('.')) return parseFloat(s.replace(/\./g, '').replace(',', '.')) ||
                    0;
                if (s.includes(',')) return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
                let t = s.replace(/[^\d.]/g, '');
                if ((t.match(/\./g) || []).length > 1) {
                    t = t.replace(/\./g, '');
                    if (/^\d+$/.test(t)) t = t.length > 2 ? (t.slice(0, -2) + '.' + t.slice(-2)) : t;
                }
                return parseFloat(t) || 0;
            };
            const idr = n => Number(n || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            const fmt2 = n => (asNum(n)).toFixed(2);

            // ROUTES
            const routes = {
                searchPO: "{{ $poSearch }}",
                getPO: "{{ $poGet }}",
                searchProd: "{{ $prodSearch }}"
            };

            const body = $$('#itemsBody');
            let seq = 0;
            let mode = 'FREE'; // 'FREE' | 'PO'
            let poPriceMap = {}; // { product_id: {price, disc_percent, disc_amount, uom} }

            function setModePO(enabled) {
                mode = enabled ? 'PO' : 'FREE';
                $$('#modeBadge').style.display = enabled ? '' : 'none';
                $$('#btnClearPO').style.display = enabled ? '' : 'none';

                // kunci input header saat PO
                const lock = (sel, on) => {
                    if (!sel) return;
                    sel.style.pointerEvents = on ? 'none' : '';
                    sel.style.backgroundColor = on ? '#f3f4f6' : '';
                    sel.style.opacity = on ? '0.9' : '1';
                };
                lock($$('#supplier_id'), enabled);
                lock($$('#warehouse_id'), enabled);

                // tombol +Obat TETAP aktif (sesuai permintaan)
                // baris yang dari PO akan dikunci per baris
                $$$('#itemsBody tr').forEach(tr => lockRowByFlag(tr));
            }

            function lockRowByFlag(tr) {
                if (tr.dataset.locked !== 'po') return;
                const i = tr.dataset.i;
                const lock = name => {
                    const el = tr.querySelector(`[name="items[${i}][${name}]"]`);
                    if (!el) return;
                    el.readOnly = true;
                    el.style.backgroundColor = '#f3f4f6';
                    el.style.opacity = '0.9';
                    el.tabIndex = -1;
                };
                lock('buy_price');
                lock('disc_percent');
                lock('disc_amount');
                lock('uom');
            }

            function reindexRows() {
                body.querySelectorAll('tr').forEach((tr, idx) => {
                    const firstTd = tr.querySelector('td');
                    if (firstTd) firstTd.textContent = idx + 1;
                });
            }

            function makeNumberInput(el, onChange) {
                if (!el) return;
                el.addEventListener('input', () => onChange && onChange());
                el.addEventListener('blur', () => {
                    el.value = fmt2(el.value);
                    onChange && onChange();
                });
            }

            function addRow(d = {}, locked = false) {
                const i = seq++;
                const tr = document.createElement('tr');
                tr.dataset.i = i;
                if (locked) tr.dataset.locked = 'po';

                const r = Object.assign({
                    code: '',
                    product_name: '',
                    product_id: '',
                    qty: 1,
                    uom: '',
                    buy_price: 0,
                    disc_percent: 0,
                    disc_amount: 0,
                    margin: 0,
                    exp_date: '',
                    batch_no: ''
                }, d);

                tr.innerHTML = `
      <td>${i+1}</td>
      <td><button type="button" class="btn btn-danger btn-sm" title="Hapus">🗑</button></td>
      <td><input class="input" name="items[${i}][code]" value="${r.code||''}" readonly></td>
      <td>
        <input class="input" name="items[${i}][product_name]" value="${r.product_name||''}" readonly>
        <input type="hidden" name="items[${i}][product_id]" value="${r.product_id||''}">
      </td>
      <td><input class="number" name="items[${i}][qty]" value="${fmt2(r.qty)}"></td>
      <td><input class="input"  name="items[${i}][uom]" value="${r.uom||''}"></td>
      <td><input class="number" name="items[${i}][buy_price]" value="${fmt2(r.buy_price)}"></td>
      <td><input class="number" name="items[${i}][disc_percent]" value="${fmt2(r.disc_percent)}"></td>
      <td><input class="number" name="items[${i}][disc_amount]"  value="${fmt2(r.disc_amount)}"></td>
      <td><input class="number" name="items[${i}][hpp]"        value="0.00" readonly></td>
      <td><input class="number" name="items[${i}][hna_ppn]"    value="0.00" readonly></td>
      <td><input class="number" name="items[${i}][margin]"     value="${fmt2(r.margin)}"></td>
      <td><input class="number" name="items[${i}][harga_jual]" value="0.00" readonly></td>
      <td><input type="date" class="date" name="items[${i}][exp_date]" value="${r.exp_date||''}"></td>
      <td><input class="input" name="items[${i}][batch_no]" value="${r.batch_no||''}"></td>
      <td><input class="number" name="items[${i}][subtotal]" value="0.00" readonly></td>
    `;

                tr.querySelector('.btn-danger').onclick = () => {
                    tr.remove();
                    reindexRows();
                    recalc();
                };

                const recalcThis = () => {
                    calcRow(tr);
                    recalc();
                };
                ['qty', 'buy_price', 'disc_percent', 'disc_amount', 'margin'].forEach(k => {
                    makeNumberInput(tr.querySelector(`[name="items[${i}][${k}]"]`), recalcThis);
                });

                body.appendChild(tr);
                lockRowByFlag(tr);
                calcRow(tr);
                recalc();
            }

            function calcRow(tr) {
                const i = tr.dataset.i;
                const q = asNum(tr.querySelector(`[name="items[${i}][qty]"]`).value);
                const p = asNum(tr.querySelector(`[name="items[${i}][buy_price]"]`).value);
                const dp = asNum(tr.querySelector(`[name="items[${i}][disc_percent]"]`).value);
                const daManual = asNum(tr.querySelector(`[name="items[${i}][disc_amount]"]`).value);

                const daPercent = (dp / 100) * q * p;
                const discNominal = daManual + daPercent;
                const gross = q * p;
                const after = Math.max(0, gross - discNominal);
                const hpp = q ? after / q : 0;
                const taxPct = asNum($$('#tax_percent').value || 0);
                const hna = hpp * (1 + taxPct / 100);
                const margin = asNum(tr.querySelector(`[name="items[${i}][margin]"]`).value);
                const jual = hna * (1 + margin / 100);

                tr.querySelector(`[name="items[${i}][disc_amount]"]`).value = fmt2(daManual);
                tr.querySelector(`[name="items[${i}][hpp]"]`).value = fmt2(hpp);
                tr.querySelector(`[name="items[${i}][hna_ppn]"]`).value = fmt2(hna);
                tr.querySelector(`[name="items[${i}][harga_jual]"]`).value = fmt2(jual);
                tr.querySelector(`[name="items[${i}][subtotal]"]`).value = fmt2(after);
            }

            function recalc() {
                let total = 0,
                    grossAfterDisc = 0;
                const taxPct = asNum($$('#tax_percent').value || 0);
                const extra = asNum($$('#extra_cost').value || 0);

                body.querySelectorAll('tr').forEach(tr => {
                    const sub = asNum(tr.querySelector('[name$="[subtotal]"]').value);
                    total += sub;
                    grossAfterDisc += sub;
                });

                const taxAmount = grossAfterDisc * (taxPct / 100);
                const net = total + taxAmount + extra;
                $$('#grandBadge').textContent = idr(net);
            }

            // normalisasi angka sebelum submit (hindari "200.000.00")
            function normalizeAllNumberInputs() {
                $$$('input.number').forEach(inp => {
                    inp.value = fmt2(inp.value);
                });
            }

            // ===== submit =====
            document.getElementById('formPembelian').addEventListener('submit', e => {
                const hasSupplier = !!$$('#supplier_id').value;
                const hasItems = body.querySelectorAll('tr').length > 0;
                if (!hasSupplier) {
                    alert('⚠️ Harap pilih supplier atau pilih PO terlebih dahulu.');
                    e.preventDefault();
                    return;
                }
                if (!hasItems) {
                    alert('⚠️ Tambahkan minimal satu obat sebelum menyimpan!');
                    e.preventDefault();
                    return;
                }
                if ($$('#payment_type').value === 'HUTANG' && !$$('#due_date').value) {
                    alert('⚠️ Pembayaran hutang wajib mengisi tanggal jatuh tempo.');
                    e.preventDefault();
                    return;
                }
                normalizeAllNumberInputs();
            });

            // ===== payment toggle =====
            const pay = $$('#payment_type'),
                wrapTempo = $$('#wrapJatuhTempo'),
                wrapCash = $$('#wrapCashbook');

            function togglePay() {
                wrapTempo.style.display = pay.value === 'HUTANG' ? '' : 'none';
                wrapCash.style.display = pay.value === 'TUNAI' ? '' : 'none';
            }
            pay.addEventListener('change', togglePay);
            togglePay();

            // recalc saat tax/extra berubah
            ['tax_percent', 'extra_cost'].forEach(id => {
                const el = $$('#' + id);
                el.addEventListener('input', () => {
                    body.querySelectorAll('tr').forEach(calcRow);
                    recalc();
                });
                el.addEventListener('blur', () => {
                    el.value = fmt2(el.value);
                    body.querySelectorAll('tr').forEach(calcRow);
                    recalc();
                });
            });

            // ===== MODAL PO =====
            const mPO = $$('#modalPO');
            $$('#btnCariPO').onclick = () => {
                show(mPO);
                $$('#btnPOCari').click();
            };
            mPO.querySelector('[data-close="modalPO"]').onclick = () => hide(mPO);

            $$('#btnPOCari').onclick = async () => {
                const q = $$('#po_query').value;
                try {
                    const url = routes.searchPO + (q ? ('?q=' + encodeURIComponent(q)) : '');
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    const list = Array.isArray(json) ? json : (json.data || []);
                    const tb = document.querySelector('#tblPO tbody');
                    tb.innerHTML = '';
                    list.forEach(r => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
          <td>${r.po_no}</td>
          <td>${r.po_date || ''}</td>
          <td>${r.supplier_name || ''}</td>
          <td>${r.warehouse_name || ''}</td>
          <td>${r.status || ''}</td>
          <td><button class="btn btn-primary btn-sm" type="button">Pilih</button></td>
        `;
                        tr.querySelector('button').onclick = () => pickPO(r.po_no);
                        tb.appendChild(tr);
                    });
                } catch (err) {
                    console.error('PO search error:', err);
                    alert('Gagal memuat daftar PO. Periksa koneksi atau route backend.');
                }
            };

            async function pickPO(poNo) {
                try {
                    const url = routes.getPO.replace('__PO__', encodeURIComponent(poNo));
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) {
                        alert('PO tidak ditemukan / tidak valid');
                        return;
                    }
                    const po = await res.json();

                    // header dari PO
                    $$('#po_no').value = po.po_no || '';
                    if (po.supplier_id) {
                        $$('#supplier_id').value = po.supplier_id;
                        $$('#supplier_id').dispatchEvent(new Event('change'));
                    }
                    if (po.warehouse_id) {
                        $$('#warehouse_id').value = po.warehouse_id;
                        $$('#warehouse_id').dispatchEvent(new Event('change'));
                    }

                    // map harga PO untuk dipakai saat +Obat
                    poPriceMap = {};
                    (po.items || []).forEach(it => {
                        poPriceMap[Number(it.product_id)] = {
                            price: Number(it.buy_price || 0),
                            disc_percent: Number(it.disc_percent || 0),
                            disc_amount: Number(it.disc_amount || 0),
                            uom: it.uom || ''
                        };
                    });

                    setModePO(true);

                    // render items dari PO (terkunci)
                    body.innerHTML = '';
                    seq = 0;
                    (po.items || []).forEach(it => {
                        addRow({
                            code: it.code,
                            product_name: it.product_name,
                            product_id: it.product_id,
                            qty: it.qty,
                            uom: it.uom,
                            buy_price: it.buy_price,
                            disc_percent: it.disc_percent,
                            disc_amount: it.disc_amount,
                            batch_no: it.batch_no,
                            exp_date: it.exp_date
                        }, true); // locked
                    });

                    hide(mPO);
                    recalc();
                } catch (err) {
                    console.error('Get PO error:', err);
                    alert('Gagal mengambil detail PO.');
                }
            }

            $$('#btnClearPO').onclick = () => {
                $$('#po_no').value = '';
                setModePO(false);
                poPriceMap = {};
            };

            // ===== MODAL PRODUK =====
            const mProd = $$('#modalObat');
            $$('#btnCariObat').onclick = () => {
                if (!$$('#supplier_id').value && !$$('#po_no').value) {
                    alert('Pilih supplier terlebih dahulu atau gunakan PO.');
                    return;
                }
                show(mProd);
            };
            mProd.querySelector('[data-close="modalObat"]').onclick = () => hide(mProd);

            $$('#btnProdCari').onclick = async () => {
                const q = $$('#prod_query').value;
                try {
                    const url = routes.searchProd + (q ? ('?q=' + encodeURIComponent(q)) : '');
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();
                    const list = Array.isArray(json) ? json : (json.data || []);
                    const tb = document.querySelector('#tblProd tbody');
                    tb.innerHTML = '';

                    list.forEach(p => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
          <td>${p.code}</td>
          <td>${p.name}</td>
          <td>${p.barcode || ''}</td>
          <td>${p.default_uom || ''}</td>
          <td>${idr(p.last_buy_price)}</td>
          <td><button class="btn btn-primary btn-sm" type="button">Pilih</button></td>`;
                        tr.querySelector('button').onclick = () => {
                            const pid = Number(p.id);
                            const inPO = !!poPriceMap[pid];
                            const src = inPO ? poPriceMap[pid] : null;

                            // jika produk ada di PO → pakai harga/discount/UOM dari PO dan kunci barisnya
                            addRow({
                                code: p.code,
                                product_name: p.name,
                                product_id: pid,
                                qty: 1,
                                uom: inPO ? (src.uom || p.default_uom) : (p.default_uom ||
                                    ''),
                                buy_price: inPO ? src.price : (p.last_buy_price || 0),
                                disc_percent: inPO ? src.disc_percent : 0,
                                disc_amount: inPO ? src.disc_amount : 0
                            }, inPO /* locked jika dari PO */ );

                            hide(mProd);
                        };
                        tb.appendChild(tr);
                    });
                } catch (err) {
                    console.error('Product search error:', err);
                    alert('Gagal memuat data produk.');
                }
            };

            // Reset form
            $$('#btnResetForm').addEventListener('click', () => {
                setTimeout(() => {
                    body.innerHTML = '';
                    seq = 0;
                    reindexRows();
                    setModePO(false);
                    poPriceMap = {};
                    $$('#grandBadge').textContent = '0,00';
                    const e = new Event('change');
                    $$('#payment_type').dispatchEvent(e);
                });
            });

            // Inisialisasi angka default tampil rapi
            $$$('input.number').forEach(inp => {
                if (inp.value === '' || isNaN(asNum(inp.value))) return;
                inp.value = fmt2(inp.value);
            });
        })();
    </script>
@endpush
