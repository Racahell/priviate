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
                    <th>ID</th>
                    <th>Siswa</th>
                    <th>Tentor</th>
                    <th>Mapel</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($todaySessions as $s)
                    <tr>
                        <td>{{ $s->id }}</td>
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
                                    <input type="hidden" name="teacher_id" value="{{ $s->tentor_id }}">
                                    <input type="number" min="1" name="net_amount" class="form-control" placeholder="Honor" required>
                                    <button class="btn btn-primary" type="submit">Buat Payout</button>
                                </form>
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

    @include('components.pagination-controls', ['paginator' => $todaySessions, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection

