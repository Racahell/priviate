@extends('layouts.master')

@section('title', 'Master Sesi')

@section('content')
<div class="card">
    <div class="split-header">
        <h3 class="card-title">Master Sesi (Jam Booking)</h3>
        <p class="card-meta">Admin menentukan slot waktu yang bisa dipilih siswa.</p>
    </div>

    <form method="POST" action="{{ route('admin.sessions.store') }}" class="form-inline section">
        @csrf
        <div class="form-group">
            <label>Mulai</label>
            <input type="datetime-local" name="start_at" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Selesai</label>
            <input type="datetime-local" name="end_at" class="form-control" required>
        </div>
        <button class="btn btn-primary" type="submit">Tambah Sesi</button>
    </form>

    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Status</th>
                    <th>Siswa</th>
                    <th>Tentor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $slot)
                    <tr>
                        <td>{{ $slot->id }}</td>
                        <td>{{ optional($slot->start_at)->format('d M Y H:i') }}</td>
                        <td>{{ optional($slot->end_at)->format('d M Y H:i') }}</td>
                        <td><span class="badge {{ strtoupper((string) $slot->status) === 'OPEN' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper((string) $slot->status) }}</span></td>
                        <td>{{ $slot->student_id ?: '-' }}</td>
                        <td>{{ $slot->tentor_id ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada master sesi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $slots->links() }}
</div>
@endsection

