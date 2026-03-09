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
    <h3 class="card-title">Request Payout</h3>
    <p class="card-meta">Ajukan pencairan saldo ke rekening bank lain.</p>
    <form method="POST" action="{{ route('tutor.wallet.withdraw') }}" class="section">
        @csrf
        <div class="grid grid-2">
            <div class="form-group">
                <label>Nominal Payout</label>
                <input type="number" name="amount" class="form-control" min="10000" step="1000" placeholder="Contoh: 150000" required>
            </div>
            <div class="form-group">
                <label>Nama Bank</label>
                <select name="bank_name" class="form-control" required>
                    <option value="">Pilih bank</option>
                    <option value="BCA">BCA</option>
                    <option value="BRI">BRI</option>
                    <option value="Mandiri">Mandiri</option>
                    <option value="BNI">BNI</option>
                    <option value="CIMB Niaga">CIMB Niaga</option>
                    <option value="Danamon">Danamon</option>
                    <option value="Permata">Permata</option>
                    <option value="BSI">BSI</option>
                    <option value="BTN">BTN</option>
                    <option value="Bank Lainnya">Bank Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label>No Rekening</label>
                <input type="text" name="account_number" class="form-control" placeholder="Nomor rekening tujuan" required>
            </div>
            <div class="form-group">
                <label>Atas Nama Rekening</label>
                <input type="text" name="account_holder" class="form-control" placeholder="Nama pemilik rekening" required>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Ajukan Payout</button>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Riwayat Request Payout</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nominal</th>
                    <th>Bank Tujuan</th>
                    <th>No Rekening</th>
                    <th>Atas Nama</th>
                    <th>Status</th>
                    <th>Diajukan</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($withdrawals ?? collect()) as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>Rp {{ number_format((float) $row->amount, 0, ',', '.') }}</td>
                        <td>{{ $row->bank_name }}</td>
                        <td>{{ $row->account_number }}</td>
                        <td>{{ $row->account_holder }}</td>
                        <td><span class="badge {{ $row->status === 'completed' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($row->status) }}</span></td>
                        <td>{{ optional($row->created_at)->format('d M Y H:i') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada request payout.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Riwayat Payout</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Dibayar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payouts as $index => $payout)
                    <tr>
                        <td>{{ $index + 1 }}</td>
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
