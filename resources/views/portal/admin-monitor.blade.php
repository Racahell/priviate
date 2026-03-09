@extends('layouts.master')

@section('title', 'Monitoring Operasional')

@section('content')
<div class="card">
    <h3 class="card-title">Sesi Hari Ini</h3>
    <p class="card-meta">Monitor jadwal live, kirim reminder, dan buat payout tentor.</p>
    @include('components.pagination-controls', ['paginator' => $todaySessions, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Siswa</th>
                    <th>Tentor</th>
                    <th>Mapel</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($todaySessions as $index => $s)
                    <tr>
                        <td>{{ ($todaySessions->currentPage() - 1) * $todaySessions->perPage() + $index + 1 }}</td>
                        <td>{{ $s->student?->name ?: $s->student_id }}</td>
                        <td>{{ $s->tentor?->name ?: $s->tentor_id }}</td>
                        <td>{{ $s->subject?->name ?: $s->subject_id }}</td>
                        <td>{{ optional($s->scheduled_at)->format('d M Y H:i') }}</td>
                        <td><span class="badge badge-warning">{{ strtoupper($s->status) }}</span></td>
                        <td>
                            <div class="action-stack">
                                <form method="POST" action="{{ route('ops.session.reminder', $s->id) }}">
                                    @csrf
                                    <button class="btn btn-outline" type="submit">Kirim Reminder</button>
                                </form>
                                <form method="POST" action="{{ route('ops.payout.create') }}" class="material-inline">
                                    @csrf
                                    <input type="hidden" name="session_id" value="{{ $s->id }}">
                                    <input type="number" min="1" name="net_amount" class="form-control" placeholder="Honor" required>
                                    @php($status = strtolower((string) $s->status))
                                    @php($canCreatePayout = in_array($status, ['completed', 'auto_completed'], true) && $s->materialReport && !$s->payout)
                                    <button class="btn btn-primary" type="submit" {{ $canCreatePayout ? '' : 'disabled' }}>Buat Payout</button>
                                </form>
                                @if(!$canCreatePayout)
                                    <small>
                                        @if($s->payout)
                                            Payout untuk sesi ini sudah dibuat.
                                        @elseif(!$s->materialReport)
                                            Payout menunggu laporan materi.
                                        @else
                                            Payout hanya untuk sesi selesai.
                                        @endif
                                    </small>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada sesi hari ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Approval Withdrawal Tentor</h3>
    <p class="card-meta">Review request pencairan saldo tentor sebelum transfer diselesaikan.</p>
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tentor</th>
                    <th>Nominal</th>
                    <th>Bank</th>
                    <th>Status</th>
                    <th>Diajukan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($withdrawals ?? collect()) as $index => $withdrawal)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $withdrawal->wallet?->user?->name ?: 'Tentor' }}</td>
                        <td>Rp {{ number_format((float) $withdrawal->amount, 0, ',', '.') }}</td>
                        <td>{{ $withdrawal->bank_name }} / {{ $withdrawal->account_number }}</td>
                        <td><span class="badge {{ $withdrawal->status === 'completed' ? 'badge-success' : ($withdrawal->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ strtoupper((string) $withdrawal->status) }}</span></td>
                        <td>{{ optional($withdrawal->created_at)->format('d M Y H:i') ?: '-' }}</td>
                        <td>
                            @if($withdrawal->status === 'requested')
                                <div class="action-stack">
                                    <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal->id) }}" class="form-inline">
                                        @csrf
                                        <input type="text" name="admin_note" class="form-control input-sm" placeholder="Catatan approve">
                                        <button class="btn btn-primary btn-xs" type="submit">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal->id) }}" class="form-inline">
                                        @csrf
                                        <input type="text" name="admin_note" class="form-control input-sm" placeholder="Alasan reject" required>
                                        <button class="btn btn-danger btn-xs" type="submit">Reject</button>
                                    </form>
                                </div>
                            @elseif($withdrawal->status === 'processing')
                                <form method="POST" action="{{ route('admin.withdrawals.paid', $withdrawal->id) }}" class="form-inline">
                                    @csrf
                                    <input type="text" name="admin_note" class="form-control input-sm" placeholder="Catatan transfer">
                                    <button class="btn btn-success btn-xs" type="submit">Mark Paid</button>
                                </form>
                            @else
                                <small>{{ $withdrawal->admin_note ?: '-' }}</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada request withdrawal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    @include('components.pagination-controls', ['paginator' => $todaySessions, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection
