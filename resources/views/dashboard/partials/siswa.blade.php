@php
    $totalSessions = (int) ($summary['total_sessions'] ?? 0);
    $completedSessions = (int) ($summary['completed_sessions'] ?? 0);
    $weeklySessions = (int) ($summary['weekly_sessions'] ?? 0);
    $progressPct = $totalSessions > 0 ? min(100, (int) round(($completedSessions / $totalSessions) * 100)) : 0;
    $weeklyChart = $summary['weekly_chart'] ?? [];
@endphp

<div class="card student-premium">
    <div class="split-header">
        <h3 class="card-title">Ringkasan Belajar Siswa</h3>
        <span class="badge badge-success">{{ $progressPct }}% target tercapai</span>
    </div>

    <div class="grid grid-3">
        <div class="stat-item">
            <p class="stat-label">Sesi Selesai</p>
            <p class="stat-value">{{ $completedSessions }}</p>
            <p class="card-meta">Dari total {{ $totalSessions }} sesi</p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Sesi Minggu Ini</p>
            <p class="stat-value">{{ $weeklySessions }}</p>
            <p class="card-meta">Target belajar rutin mingguan</p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Invoice Belum Lunas</p>
            <p class="stat-value">{{ $summary['unpaid_invoices'] ?? 0 }}</p>
            <p class="card-meta">Perlu penyelesaian pembayaran</p>
        </div>
    </div>

    <div class="student-progress">
        <div class="student-progress-head">
            <span>Progress Belajar</span>
            <span>{{ $progressPct }}%</span>
        </div>
        <div class="student-progress-track">
            <div class="student-progress-fill" style="width: {{ $progressPct }}%;"></div>
        </div>
    </div>

    @if(!empty($weeklyChart))
        <div class="student-weekly-chart">
            <div class="student-progress-head">
                <span>Aktivitas Mingguan</span>
                <span>{{ array_sum(array_column($weeklyChart, 'count')) }} sesi</span>
            </div>
            <div class="spark-bars">
                @foreach($weeklyChart as $day)
                    <div class="spark-bar-item">
                        <div class="spark-bar" style="height: {{ $day['height'] }}%;"></div>
                        <small>{{ $day['day'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="student-upcoming">
        <h4>Upcoming Session</h4>
        @if(!empty($summary['upcoming']))
            <p class="student-upcoming-main">{{ \Carbon\Carbon::parse($summary['upcoming']->scheduled_at)->translatedFormat('d M Y, H:i') }}</p>
            <p class="card-meta">Status: {{ strtoupper((string) $summary['upcoming']->status) }}</p>
        @else
            <p class="card-meta">Belum ada sesi terjadwal. Yuk booking paket belajar baru.</p>
        @endif
    </div>

    <div class="student-upcoming">
        <h4>Validasi Kehadiran Guru Hari Ini</h4>
        @php($todaySessions = collect($summary['today_sessions'] ?? []))
        @if($todaySessions->isNotEmpty())
            <div class="table-wrap section">
                <table>
                    <thead>
                        <tr>
                            <th>Jadwal</th>
                            <th>Mapel</th>
                            <th>Guru</th>
                            <th>Status Validasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todaySessions as $session)
                            @php($record = $session->attendanceRecord)
                            <tr>
                                <td>{{ optional($session->scheduled_at)->format('d M Y H:i') }}</td>
                                <td>{{ $session->subject?->name ?? ('Mapel #' . $session->subject_id) }}</td>
                                <td>{{ $session->tentor?->name ?? ('Guru #' . $session->tentor_id) }}</td>
                                <td>
                                    @if($record?->student_validated_teacher)
                                        <span class="badge badge-success">Sudah Divalidasi</span>
                                    @else
                                        <span class="badge badge-warning">Belum Divalidasi</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('ops.attendance.student', $session->id) }}" enctype="multipart/form-data" class="student-attendance-form" style="display:grid; gap:6px; max-width:260px;">
                                        @csrf
                                        <input type="hidden" name="location_status" value="DENIED" class="student-location-status">
                                        <input type="hidden" name="student_lat" class="student-lat">
                                        <input type="hidden" name="student_lng" class="student-lng">
                                        <label class="checkbox">
                                            <input type="checkbox" name="teacher_present" value="1" checked> Guru hadir
                                        </label>
                                        <input type="file" name="student_photo" class="form-control" accept=".jpg,.jpeg,.png" required>
                                        <button class="btn btn-outline btn-xs" type="button" data-student-geo>Ambil Lokasi</button>
                                        <button class="btn btn-primary btn-xs" type="submit">Validasi</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="card-meta">Belum ada sesi hari ini.</p>
        @endif
    </div>

    <div class="split-actions">
        <a href="{{ route('student.packages') }}" class="btn btn-primary">Pilih Paket</a>
        <a href="{{ route('student.booking') }}" class="btn btn-outline">Booking Sesi</a>
        <a href="{{ route('student.invoices') }}" class="btn btn-outline">Lihat Invoice</a>
        <a href="{{ route('profile.edit') }}" class="btn btn-outline">Perbarui Profil</a>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function applyGeo(form, latitude, longitude) {
        var statusEl = form.querySelector('.student-location-status');
        var latEl = form.querySelector('.student-lat');
        var lngEl = form.querySelector('.student-lng');
        if (statusEl) statusEl.value = 'ALLOW';
        if (latEl) latEl.value = latitude;
        if (lngEl) lngEl.value = longitude;
    }

    function clearGeo(form) {
        var statusEl = form.querySelector('.student-location-status');
        var latEl = form.querySelector('.student-lat');
        var lngEl = form.querySelector('.student-lng');
        if (statusEl) statusEl.value = 'DENIED';
        if (latEl) latEl.value = '';
        if (lngEl) lngEl.value = '';
    }

    document.querySelectorAll('.student-attendance-form [data-student-geo]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('.student-attendance-form');
            if (!form || !navigator.geolocation) return;
            clearGeo(form);
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    applyGeo(form, position.coords.latitude, position.coords.longitude);
                    btn.textContent = 'Lokasi Tersimpan';
                },
                function () {
                    clearGeo(form);
                    btn.textContent = 'Gagal Ambil Lokasi';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    });
})();
</script>
@endpush
