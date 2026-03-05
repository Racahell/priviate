@extends('layouts.master')

@section('title', 'Dompet & Honor')

@section('content')
<div class="grid grid-3">
    <div class="card">
        <h3 class="card-title">Saldo Tersedia</h3>
        <p class="stat-value">Rp {{ number_format((float) ($wallet->balance ?? 0), 0, ',', '.') }}</p>
        <p class="card-meta">Dana siap dicairkan dari sesi yang sudah settle</p>
    </div>
    <div class="card">
        <h3 class="card-title">Saldo Ditahan</h3>
        <p class="stat-value">Rp {{ number_format((float) ($wallet->held_balance ?? 0), 0, ',', '.') }}</p>
        <p class="card-meta">Menunggu penyelesaian sesi</p>
    </div>
    <div class="card">
        <h3 class="card-title">Jumlah Payout</h3>
        <p class="stat-value">{{ $payoutCount }}</p>
        <p class="card-meta">{{ $completedSessionsCount }} sesi selesai tercatat</p>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Riwayat Payout</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Dibayar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payouts as $payout)
                    <tr>
                        <td>{{ $payout->id }}</td>
                        <td>Rp {{ number_format((float) $payout->net_amount, 0, ',', '.') }}</td>
                        <td><span class="badge {{ $payout->status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($payout->status) }}</span></td>
                        <td>{{ optional($payout->paid_at)->format('d M Y H:i') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada payout.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
