@extends('layouts.master')

@section('title', 'Invoice Saya')

@section('content')
<div class="card">
    <h3 class="card-title">Riwayat Invoice</h3>
    <p class="card-meta">Kelola tagihan dan status pembayaran Anda.</p>

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
                                <form method="POST" action="{{ route('ops.payment.success') }}" class="form-inline">
                                    @csrf
                                    <input type="hidden" name="invoice_id" value="{{ $inv->id }}">
                                    <input type="hidden" name="amount" value="{{ (float) $inv->total_amount }}">
                                    <input type="hidden" name="method" value="manual_transfer">
                                    <input type="hidden" name="transaction_id" value="MANUAL-{{ $inv->id }}-{{ now()->format('YmdHis') }}">
                                    <button class="btn btn-success" type="submit">Bayar</button>
                                </form>
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
    {{ $invoices->links() }}
</div>
@endsection
