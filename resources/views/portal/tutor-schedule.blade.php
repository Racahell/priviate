@extends('layouts.master')

@section('title', 'Jadwal Mengajar')

@section('content')
<div class="card">
    <h3 class="card-title">Jadwal Saya</h3>
    <p class="card-meta">Kelola kelas, absensi, dan ringkasan materi dari satu halaman.</p>

    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>ID Sesi</th>
                    <th>Siswa</th>
                    <th>Mapel</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->id }}</td>
                        <td>{{ $session->student_id }}</td>
                        <td>{{ $session->subject_id }}</td>
                        <td>{{ optional($session->scheduled_at)->format('d M Y H:i') }}</td>
                        <td>
                            <span class="badge {{ $session->status === 'completed' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($session->status) }}</span>
                        </td>
                        <td>
                            <div class="action-stack">
                                <form method="POST" action="{{ route('ops.session.start', $session->id) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">Mulai</button>
                                </form>
                                <form method="POST" action="{{ route('ops.attendance.mark', $session->id) }}">
                                    @csrf
                                    <input type="hidden" name="student_present" value="1">
                                    <input type="hidden" name="location_status" value="DENIED">
                                    <button class="btn btn-outline" type="submit">Absen</button>
                                </form>
                                <form method="POST" action="{{ route('ops.material.submit', $session->id) }}" class="material-inline">
                                    @csrf
                                    <input type="text" name="summary" class="form-control" placeholder="Ringkasan materi" required>
                                    <button class="btn btn-success" type="submit">Materi</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada jadwal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $sessions->links() }}
</div>
@endsection
