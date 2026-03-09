@extends('layouts.master')

@section('title', 'Invoices')

@section('content')
<div class="card">
    <div class="split-header">
        <div>
            <h3 class="card-title">Riwayat Invoice</h3>
            <p class="card-meta">Invoice bersifat immutable. Gunakan status `CANCELLED` untuk pembatalan, bukan delete.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.invoices.bulkDelete') }}" id="invoice-bulk-delete-form" class="form-inline section">
        @csrf
        <input type="text" name="reason" class="form-control" placeholder="Alasan cancel bulk" required>
        <button class="btn btn-warning btn-sm" type="submit" onclick="return confirm('Batalkan semua invoice yang dipilih?');">Cancel Selected</button>
    </form>
    @include('components.pagination-controls', ['paginator' => $invoices, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="invoice-check-all"></th>
                    <th>No</th>
                    <th>No Invoice</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Issue</th>
                    <th>Jatuh Tempo</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $index => $invoice)
                    <tr>
                        <td><input type="checkbox" class="invoice-row-check" value="{{ $invoice->id }}" {{ strtoupper((string) $invoice->status) === 'PAID' ? 'disabled' : '' }}></td>
                        <td>{{ ($invoices->currentPage() - 1) * $invoices->perPage() + $index + 1 }}</td>
                        <td>{{ $invoice->invoice_number ?: '-' }}</td>
                        <td>Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td>
                        <td><span class="badge badge-info">{{ strtoupper((string) $invoice->status) }}</span></td>
                        <td>{{ optional($invoice->issue_date)->format('d M Y') ?: '-' }}</td>
                        <td>{{ optional($invoice->due_date)->format('d M Y') ?: '-' }}</td>
                        <td>
                            @if(strtoupper((string) $invoice->status) === 'PAID')
                                <span class="badge badge-success">Locked</span>
                            @elseif(strtoupper((string) $invoice->status) === 'CANCELLED')
                                <span class="badge badge-warning">Cancelled</span>
                            @else
                                <form method="POST" action="{{ route('admin.invoices.delete', $invoice->id) }}" class="form-inline">
                                    @csrf
                                    @method('DELETE')
                                    <input type="text" class="form-control input-sm" name="reason" placeholder="Alasan cancel" required>
                                    <button class="btn btn-warning btn-xs" type="submit">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada invoice.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    @include('components.pagination-controls', ['paginator' => $invoices, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('invoice-bulk-delete-form');
        var checkAll = document.getElementById('invoice-check-all');
        if (!form || !checkAll) return;

        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.invoice-row-check').forEach(function (node) {
                node.checked = checkAll.checked;
            });
        });

        form.addEventListener('submit', function (event) {
            form.querySelectorAll('input[name="ids[]"]').forEach(function (node) {
                node.remove();
            });

            var selected = document.querySelectorAll('.invoice-row-check:checked');
            if (!selected.length) {
                event.preventDefault();
                alert('Pilih minimal satu invoice.');
                return;
            }

            selected.forEach(function (node) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = node.value;
                form.appendChild(input);
            });
        });
    })();
</script>
@endpush

