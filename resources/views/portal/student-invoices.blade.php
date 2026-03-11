@extends('layouts.master')

@section('title', 'Invoice Saya')

@section('content')
<div class="card">
    <h3 class="card-title">Riwayat Invoice</h3>
    <p class="card-meta">Kelola tagihan, buka detail pembayaran, pilih metode, lalu bayar invoice Anda.</p>
    @include('components.pagination-controls', ['paginator' => $invoices, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No Invoice</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td>{{ $inv->invoice_number }}</td>
                        <td>Rp {{ number_format((float) $inv->total_amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $inv->status === 'unpaid' ? 'badge-warning' : 'badge-success' }}">
                                {{ strtoupper($inv->status) }}
                            </span>
                        </td>
                        <td>{{ optional($inv->issue_date)->format('d M Y H:i') }}</td>
                        <td>
                            @if($inv->status === 'unpaid')
                                <button
                                    type="button"
                                    class="btn btn-primary open-payment-modal"
                                    data-invoice-id="{{ $inv->id }}"
                                    data-invoice-number="{{ $inv->invoice_number }}"
                                    data-amount="{{ (float) $inv->total_amount }}"
                                    data-purpose="Pembayaran tagihan invoice {{ $inv->invoice_number }}"
                                >
                                    Detail Pembayaran
                                </button>
                            @else
                                <span class="badge badge-success">Selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada invoice.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="paymentModal" aria-hidden="true">
    <div class="modal-card payment-modal-card">
        <div class="split-header">
            <div>
                <h4 class="card-title" style="margin:0;">Detail Pembayaran</h4>
                <p class="card-meta" id="paymentInvoiceLabel" style="margin:4px 0 0;">-</p>
            </div>
            <button type="button" class="btn btn-sm btn-default" id="paymentModalClose">Tutup</button>
        </div>

        <form method="POST" action="{{ route('ops.payment.success') }}" class="section">
            @csrf
            <input type="hidden" name="invoice_id" id="paymentInvoiceId">
            <input type="hidden" name="transaction_id" id="paymentTransactionId">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Nama Pembayar</label>
                    <input type="text" id="paymentPayerName" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Jumlah Pembayaran</label>
                    <input type="text" id="paymentAmountLabel" class="form-control" readonly>
                    <input type="hidden" name="amount" id="paymentAmountValue">
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="method" class="form-control" required>
                        <option value="bank_transfer">Transfer Bank</option>
                        <option value="virtual_account">Virtual Account</option>
                        <option value="ewallet">E-Wallet</option>
                        <option value="qris">QRIS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Pembayaran</label>
                    <input type="text" id="paymentDateLabel" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Nomor Transaksi / Referensi</label>
                    <input type="text" id="paymentTransactionLabel" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Tujuan Pembayaran</label>
                    <input type="text" id="paymentPurposeLabel" class="form-control" readonly>
                </div>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Pembelian paket belajar"></textarea>
            </div>
            <button class="btn btn-success" type="submit">Bayar Sekarang</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('paymentModal');
    var closeBtn = document.getElementById('paymentModalClose');
    if (!modal || !closeBtn) return;

    var invoiceIdInput = document.getElementById('paymentInvoiceId');
    var amountValueInput = document.getElementById('paymentAmountValue');
    var amountLabelInput = document.getElementById('paymentAmountLabel');
    var payerNameInput = document.getElementById('paymentPayerName');
    var paymentDateInput = document.getElementById('paymentDateLabel');
    var trxInput = document.getElementById('paymentTransactionId');
    var trxLabelInput = document.getElementById('paymentTransactionLabel');
    var purposeLabelInput = document.getElementById('paymentPurposeLabel');
    var invoiceLabel = document.getElementById('paymentInvoiceLabel');
    var currentUserName = @json(auth()->user()->name ?? '-');

    function formatRupiah(amount) {
        return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
    }

    function openModal(data) {
        var id = data.getAttribute('data-invoice-id');
        var number = data.getAttribute('data-invoice-number');
        var amount = data.getAttribute('data-amount');
        var purpose = data.getAttribute('data-purpose') || ('Pembayaran invoice ' + number);
        var ts = new Date().toISOString().replace(/[-:TZ.]/g, '').slice(0, 14);
        var transactionRef = 'TRX' + id + ts;
        var paymentDate = new Date().toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        invoiceIdInput.value = id;
        amountValueInput.value = amount;
        amountLabelInput.value = formatRupiah(amount);
        payerNameInput.value = currentUserName;
        paymentDateInput.value = paymentDate;
        invoiceLabel.textContent = 'Invoice: ' + number;
        trxInput.value = transactionRef;
        trxLabelInput.value = transactionRef;
        purposeLabelInput.value = purpose;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.open-payment-modal').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(btn); });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
})();
</script>
@endpush

    @include('components.pagination-controls', ['paginator' => $invoices, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection

