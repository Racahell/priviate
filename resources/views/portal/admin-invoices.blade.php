@extends('layouts.master')

@section('title', 'Invoices')

@section('content')
<div class="card">
    <div class="split-header">
        <div>
            <h3 class="card-title">Riwayat Invoice</h3>
            <p class="card-meta">Kelola data invoice pada sistem.</p>
        </div>
        @if(($isSuperadmin ?? false) === true)
            <div class="split-actions">
                <a href="{{ route(request()->routeIs('superadmin.*') ? 'superadmin.invoices' : 'admin.invoices', ['tab' => 'active']) }}" class="btn {{ ($tab ?? 'active') === 'active' ? 'btn-primary' : 'btn-outline' }}">Active</a>
                <a href="{{ route(request()->routeIs('superadmin.*') ? 'superadmin.invoices' : 'admin.invoices', ['tab' => 'deleted']) }}" class="btn {{ ($tab ?? 'active') === 'deleted' ? 'btn-primary' : 'btn-outline' }}">Deleted</a>
            </div>
        @endif
    </div>

    @if(($tab ?? 'active') === 'active')
        <form method="POST" action="{{ route('admin.invoices.bulkDelete') }}" id="invoice-bulk-delete-form" class="form-inline section">
            @csrf
            <button class="btn btn-warning btn-sm" type="submit" onclick="return confirm('Hapus semua invoice yang dipilih?');">Delete Selected</button>
        </form>
    @endif
    @include('components.pagination-controls', ['paginator' => $invoices, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    @if(($tab ?? 'active') === 'active')
                        <th><input type="checkbox" id="invoice-check-all"></th>
                    @endif
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
                        @if(($tab ?? 'active') === 'active')
                            <td><input type="checkbox" class="invoice-row-check" value="{{ $invoice->id }}"></td>
                        @endif
                        <td>{{ ($invoices->currentPage() - 1) * $invoices->perPage() + $index + 1 }}</td>
                        <td>{{ $invoice->invoice_number ?: '-' }}</td>
                        <td>Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td>
                        <td><span class="badge badge-info">{{ strtoupper((string) $invoice->status) }}</span></td>
                        <td>{{ optional($invoice->issue_date)->format('d M Y') ?: '-' }}</td>
                        <td>{{ optional($invoice->due_date)->format('d M Y') ?: '-' }}</td>
                        <td>
                            @if(($tab ?? 'active') === 'active')
                                <form method="POST" action="{{ route('admin.invoices.delete', $invoice->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-warning btn-xs" type="submit">Delete</button>
                                </form>
                            @elseif(($isSuperadmin ?? false) === true && request()->routeIs('superadmin.*'))
                                <div class="action-stack">
                                    <form method="POST" action="{{ route('superadmin.invoices.restore', $invoice->id) }}">
                                        @csrf
                                        <button class="btn btn-success btn-xs" type="submit">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('superadmin.invoices.forceDelete', $invoice->id) }}" class="form-inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="text" class="form-control input-sm" name="reason" placeholder="Alasan hard delete" required>
                                        <button class="btn btn-danger btn-xs" type="submit">Hard Delete</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ ($tab ?? 'active') === 'active' ? 8 : 7 }}">Belum ada invoice.</td></tr>
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

