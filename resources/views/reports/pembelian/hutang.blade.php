@extends('layouts.app')

@section('title', 'Laporan Hutang / Payables')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-semibold mb-1">
            <i class="bi bi-cash-coin me-2 text-danger"></i>
            Laporan Hutang / Payables
        </h4>
        <p class="text-muted small mb-0">Kelola hutang pembelian dan update status pembayaran</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-sm btn-light-soft" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak
        </button>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Pencarian</label>
                <input type="text" name="q" class="form-control form-control-sm"
                    placeholder="Supplier / Ref No..." value="{{ request('q') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">- Semua Status -</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="belum lunas" {{ request('status') === 'belum lunas' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Sebagian Lunas</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">&nbsp;</label>
                <div class="form-check mt-1">
                    <input type="checkbox" class="form-check-input" name="show_overdue" value="1"
                        id="showOverdue" {{ request('show_overdue') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label small" for="showOverdue">
                        Jatuh Tempo
                    </label>
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-brand w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">&nbsp;</label>
                <a href="{{ route('reports.pembelian.hutang') }}" class="btn btn-sm btn-light-soft w-100">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-modern mb-0">
            <thead>
                <tr>
                    <th style="width: 120px;">Tanggal</th>
                    <th>Supplier</th>
                    <th>Ref No</th>
                    <th style="width: 120px;">Jatuh Tempo</th>
                    <th style="width: 130px;" class="text-end">Total Hutang</th>
                    <th style="width: 130px;" class="text-end">Sudah Bayar</th>
                    <th style="width: 130px;" class="text-end">Sisa Hutang</th>
                    <th style="width: 140px;">Status</th>
                    <th style="width: 100px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payables as $payable)
                    @php
                        $isOverdue = $payable->is_overdue;
                        $daysUntilDue = $payable->due_date
                            ? now()->diffInDays($payable->due_date, false)
                            : null;
                        $statusClass = match($payable->status) {
                            'paid', 'lunas' => 'success',
                            'partial' => 'warning',
                            'pending' => 'info',
                            default => 'danger'
                        };
                    @endphp
                    <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                        <td>
                            <small class="fw-semibold">
                                {{ $payable->issue_date?->format('d M Y') ?? '-' }}
                            </small>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $payable->supplier?->name ?? 'Unknown' }}</div>
                            <small class="text-muted">{{ $payable->supplier?->code ?? '-' }}</small>
                        </td>
                        <td>
                            <small>{{ $payable->ref_no ?? '-' }}</small>
                        </td>
                        <td>
                            <small>
                                {{ $payable->due_date?->format('d M Y') ?? '-' }}
                                @if ($isOverdue)
                                    <span class="badge bg-danger">Overdue</span>
                                @elseif ($daysUntilDue && $daysUntilDue <= 7)
                                    <span class="badge bg-warning">{{ $daysUntilDue }} hari</span>
                                @endif
                            </small>
                        </td>
                        <td class="text-end">
                            <strong>Rp {{ number_format($payable->amount, 0, ',', '.') }}</strong>
                        </td>
                        <td class="text-end">
                            <span class="text-success">
                                Rp {{ number_format($payable->paid_amount, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold">
                                Rp {{ number_format($payable->balance, 0, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusClass }}">
                                {{ ucfirst(str_replace(['_'], ' ', $payable->status)) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#updatePayableModal"
                                onclick="prepareUpdatePayable({{ $payable }})">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <div class="text-muted mt-2">Tidak ada data hutang</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payables->hasPages())
        <div class="card-footer">
            {{ $payables->links() }}
        </div>
    @endif
</div>

<!-- Modal Update Payable Status -->
<div class="modal fade" id="updatePayableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Status Pembayaran Hutang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updatePayableForm">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div id="payableInfo" class="mb-3 p-2 bg-light rounded">
                        <!-- Info will be populated via JS -->
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Pembayaran</label>
                        <select name="status" id="payableStatus" class="form-select" required>
                            <option value="">- Pilih Status -</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="belum lunas">Belum Lunas</option>
                            <option value="partial">Sebagian Lunas</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="lunas">Lunas</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jumlah Pembayaran (Rp)</label>
                        <input type="number" name="paid_amount" id="paidAmount" class="form-control"
                            step="1" min="0" placeholder="Kosongkan untuk tidak mengubah">
                        <small class="text-muted">Biarkan kosong jika tidak ingin mengubah</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentPayable = null;

    function prepareUpdatePayable(payable) {
        currentPayable = payable;

        // Set info
        const info = `
            <div class="small">
                <strong>${payable.supplier?.name || 'Unknown'}</strong><br>
                Total: Rp ${(payable.amount).toLocaleString('id-ID')}<br>
                Sudah Bayar: Rp ${(payable.paid_amount).toLocaleString('id-ID')}<br>
                Sisa: Rp ${(payable.balance).toLocaleString('id-ID')}
            </div>
        `;
        document.getElementById('payableInfo').innerHTML = info;

        // Set form values
        document.getElementById('payableStatus').value = payable.status || '';
        document.getElementById('paidAmount').value = payable.paid_amount || '';

        // Set form action
        document.getElementById('updatePayableForm').action = `/payables/${payable.id}/status`;
    }

    // Handle form submit
    document.getElementById('updatePayableForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const payableId = currentPayable.id;

        fetch(`/payables/${payableId}/status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                status: document.getElementById('payableStatus').value,
                paid_amount: document.getElementById('paidAmount').value || null
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Error'));
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
    });
</script>

@endsection
