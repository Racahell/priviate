<div class="card">
    <h3 class="card-title">Ringkasan Tentor</h3>
    <div class="grid grid-2">
        <div class="stat-item">
            <p class="stat-label">Sesi Hari Ini</p>
            <p class="stat-value">{{ $summary['today_sessions'] ?? 0 }}</p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Open Kritik</p>
            <p class="stat-value">{{ $summary['pending_disputes'] ?? 0 }}</p>
        </div>
    </div>
    <div class="split-actions section">
        <a href="{{ route('tutor.schedule') }}" class="btn btn-outline">Lihat Jadwal</a>
        <a href="{{ route('tutor.wallet') }}" class="btn btn-outline">Buka Wallet</a>
    </div>

    @php($todaySchedule = collect($summary['today_schedule'] ?? []))
    <div class="section">
        <h4 class="card-title">Jadwal Hari Ini - Isi Absen</h4>
        @if($todaySchedule->isNotEmpty())
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Murid</th>
                            <th>Mapel</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todaySchedule as $session)
                            <tr>
                                <td>{{ optional($session->scheduled_at)->format('H:i') }}</td>
                                <td>{{ $session->student?->name ?? ('Murid #' . $session->student_id) }}</td>
                                <td>{{ $session->subject?->name ?? ('Mapel #' . $session->subject_id) }}</td>
                                <td>{{ strtoupper((string) $session->status) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('ops.attendance.mark', $session->id) }}" enctype="multipart/form-data" class="tutor-attendance-form" style="display:grid; gap:6px; max-width:260px;">
                                        @csrf
                                        <input type="hidden" name="location_status" value="DENIED" class="tutor-location-status">
                                        <input type="hidden" name="teacher_lat" class="tutor-lat">
                                        <input type="hidden" name="teacher_lng" class="tutor-lng">
                                        <label class="checkbox">
                                            <input type="checkbox" name="student_present" value="1" checked> Murid hadir
                                        </label>
                                        <input type="file" name="teacher_photo" class="form-control" accept=".jpg,.jpeg,.png" required>
                                        <button class="btn btn-outline btn-xs" type="button" data-tutor-geo>Ambil Lokasi</button>
                                        <button class="btn btn-primary btn-xs" type="submit">Absen Sekarang</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="card-meta">Tidak ada jadwal mengajar hari ini.</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    function applyGeo(form, latitude, longitude) {
        var statusEl = form.querySelector('.tutor-location-status');
        var latEl = form.querySelector('.tutor-lat');
        var lngEl = form.querySelector('.tutor-lng');
        if (statusEl) statusEl.value = 'ALLOW';
        if (latEl) latEl.value = latitude;
        if (lngEl) lngEl.value = longitude;
    }

    function clearGeo(form) {
        var statusEl = form.querySelector('.tutor-location-status');
        var latEl = form.querySelector('.tutor-lat');
        var lngEl = form.querySelector('.tutor-lng');
        if (statusEl) statusEl.value = 'DENIED';
        if (latEl) latEl.value = '';
        if (lngEl) lngEl.value = '';
    }

    document.querySelectorAll('.tutor-attendance-form [data-tutor-geo]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('.tutor-attendance-form');
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
