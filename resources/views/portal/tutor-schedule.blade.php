@extends('layouts.master')

@section('title', 'Jadwal Mengajar')

@section('content')
<div class="card">
    <h3 class="card-title">{{ ($isAdminViewer ?? false) ? 'Jadwal Mengajar Semua Tentor' : 'Jadwal Saya' }}</h3>
    <p class="card-meta">{{ ($isAdminViewer ?? false) ? 'Pantau siswa diajar oleh guru siapa, mapel, dan status sesi.' : 'Kelola kelas, absensi, dan ringkasan materi dari satu halaman.' }}</p>
    @include('components.pagination-controls', ['paginator' => $sessions, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Siswa</th>
                    @if($isAdminViewer ?? false)
                        <th>Tentor</th>
                        <th>Paket</th>
                        <th>Total Jadwal</th>
                        <th>Aksi</th>
                    @else
                        <th>Mapel</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $index => $session)
                    <tr>
                        <td>{{ ($sessions->currentPage() - 1) * $sessions->perPage() + $index + 1 }}</td>
                        @if($isAdminViewer ?? false)
                            <td>{{ $session->student_name }}</td>
                            <td>{{ $session->tentor_name }}</td>
                            <td>{{ $session->package_label }}</td>
                            <td>{{ $session->session_count }} sesi</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-outline btn-xs open-admin-tutor-session-detail"
                                    data-student="{{ $session->student_name }}"
                                    data-tentor="{{ $session->tentor_name }}"
                                    data-package="{{ $session->package_label }}"
                                    data-sessions='@json($session->detail_rows)'>
                                    Detail
                                </button>
                            </td>
                        @else
                            <td>{{ $session->student?->name ?: $session->student_id }}</td>
                            <td>{{ $session->subject?->name ?: $session->subject_id }}</td>
                            <td>{{ optional($session->scheduled_at)->format('d M Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $session->status === 'completed' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($session->status) }}</span>
                            </td>
                            <td>
                                @php($canAction = !$session->scheduled_at || now()->greaterThanOrEqualTo($session->scheduled_at))
                                @php($deliveryMode = strtoupper((string) ($session->delivery_mode ?? 'online')))
                                @php($studentAddressParts = array_filter([
                                    $session->student?->address,
                                    $session->student?->village,
                                    $session->student?->district,
                                    $session->student?->city,
                                    $session->student?->province,
                                    $session->student?->postal_code,
                                ]))
                                @php($studentAddress = !empty($studentAddressParts) ? implode(', ', $studentAddressParts) : '')
                                @php($studentLat = $session->student?->latitude)
                                @php($studentLng = $session->student?->longitude)
                                @php($mapsUrl = ($studentLat !== null && $studentLng !== null) ? ('https://maps.google.com/?q=' . $studentLat . ',' . $studentLng) : '')
                                @php($studentLocationNotes = (string) ($session->student?->location_notes ?? ''))
                                <button
                                    type="button"
                                    class="btn btn-outline btn-xs open-tutor-session-detail"
                                    data-session-id="{{ $session->id }}"
                                    data-student="{{ $session->student?->name ?: $session->student_id }}"
                                    data-subject="{{ $session->subject?->name ?: $session->subject_id }}"
                                    data-time="{{ optional($session->scheduled_at)->format('d M Y H:i') }}"
                                    data-status="{{ strtoupper((string) $session->status) }}"
                                    data-mode="{{ $deliveryMode }}"
                                    data-address="{{ $studentAddress }}"
                                    data-location-notes="{{ $studentLocationNotes }}"
                                    data-maps-url="{{ $mapsUrl }}"
                                    data-can-action="{{ $canAction ? '1' : '0' }}"
                                    data-start-route="{{ route('ops.session.start', $session->id) }}"
                                    data-attendance-route="{{ route('ops.attendance.mark', $session->id) }}"
                                    data-material-route="{{ route('ops.material.submit', $session->id) }}">
                                    Detail
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ ($isAdminViewer ?? false) ? 6 : 6 }}">Belum ada jadwal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($isAdminViewer ?? false)
<div class="modal-overlay" id="adminTutorSessionDetailModal" aria-hidden="true">
    <div class="modal-card">
        <div class="split-header">
            <h3 class="card-title">Detail Jadwal Mengajar</h3>
            <button type="button" class="btn btn-outline btn-xs" id="closeAdminTutorSessionDetailModal">Tutup</button>
        </div>
        <div class="grid grid-3 section">
            <div class="form-group">
                <label>Siswa</label>
                <input type="text" id="adminDetailStudent" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>Tentor</label>
                <input type="text" id="adminDetailTentor" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>Paket</label>
                <input type="text" id="adminDetailPackage" class="form-control" readonly>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Mapel</th>
                        <th>Jadwal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="adminTutorSessionDetailRows">
                    <tr><td colspan="4">Belum ada detail jadwal.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="modal-overlay" id="tutorSessionDetailModal" aria-hidden="true">
    <div class="modal-card">
        <div class="split-header">
            <h3 class="card-title">Detail Sesi</h3>
            <button type="button" class="btn btn-outline btn-xs" id="closeTutorSessionDetailModal">Tutup</button>
        </div>
        <div class="grid grid-2 section">
            <div class="form-group">
                <label>Siswa</label>
                <input type="text" id="detailStudent" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>Mapel</label>
                <input type="text" id="detailSubject" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>Waktu</label>
                <input type="text" id="detailTime" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>Status</label>
                <input type="text" id="detailStatus" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>Mode</label>
                <input type="text" id="detailMode" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>Alamat Siswa</label>
                <textarea id="detailAddress" class="form-control" rows="3" readonly placeholder="Alamat hanya tampil untuk sesi offline."></textarea>
            </div>
            <div class="form-group">
                <label>Catatan Lokasi</label>
                <textarea id="detailLocationNotes" class="form-control" rows="3" readonly placeholder="Catatan lokasi siswa akan tampil di sini."></textarea>
            </div>
        </div>
        <div class="section" id="detailMapsWrap" style="display:none;">
            <a href="#" target="_blank" rel="noopener" class="btn btn-outline" id="detailMapsLink">Buka Google Maps</a>
        </div>
        <div class="alert alert-info" id="detailActionInfo" style="display:none;"></div>
        <div class="section action-stack">
            <form method="POST" id="detailStartForm">
                @csrf
                <input type="hidden" name="location_status" id="detailStartLocationStatus" value="DENIED">
                <input type="hidden" name="latitude" id="detailStartLatitude">
                <input type="hidden" name="longitude" id="detailStartLongitude">
                <button class="btn btn-primary" type="submit" id="detailStartBtn">Mulai</button>
            </form>
            <form method="POST" id="detailAttendanceForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="student_present" id="detailStudentPresentValue" value="1">
                <input type="hidden" name="location_status" id="detailAttendanceLocationStatus" value="DENIED">
                <input type="hidden" name="teacher_lat" id="detailAttendanceLatitude">
                <input type="hidden" name="teacher_lng" id="detailAttendanceLongitude">
                <label class="checkbox" style="display:block; margin-bottom:8px;">
                    <input type="checkbox" id="detailStudentPresentToggle" checked> Murid hadir (divalidasi guru)
                </label>
                <input type="file" name="teacher_photo" class="form-control" id="detailTeacherPhotoInput" accept=".jpg,.jpeg,.png" required>
                <button class="btn btn-outline" type="submit" id="detailAttendanceBtn">Absen</button>
            </form>
            <form method="POST" id="detailMaterialForm" class="material-inline">
                @csrf
                <input type="text" name="summary" id="detailMaterialSummary" class="form-control" placeholder="Ringkasan materi" required>
                <button class="btn btn-success" type="submit" id="detailMaterialBtn">Materi</button>
            </form>
        </div>
    </div>
</div>
@endif

    @include('components.pagination-controls', ['paginator' => $sessions, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection

@push('scripts')
@if($isAdminViewer ?? false)
<script>
(function () {
    var modal = document.getElementById('adminTutorSessionDetailModal');
    var closeBtn = document.getElementById('closeAdminTutorSessionDetailModal');
    var studentEl = document.getElementById('adminDetailStudent');
    var tentorEl = document.getElementById('adminDetailTentor');
    var packageEl = document.getElementById('adminDetailPackage');
    var rowsEl = document.getElementById('adminTutorSessionDetailRows');
    if (!modal || !closeBtn || !rowsEl) return;

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.open-admin-tutor-session-detail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var rows = [];
            try {
                rows = JSON.parse(btn.getAttribute('data-sessions') || '[]');
            } catch (e) {
                rows = [];
            }

            studentEl.value = btn.getAttribute('data-student') || '-';
            tentorEl.value = btn.getAttribute('data-tentor') || '-';
            packageEl.value = btn.getAttribute('data-package') || '-';

            if (!Array.isArray(rows) || rows.length === 0) {
                rowsEl.innerHTML = '<tr><td colspan="4">Belum ada detail jadwal.</td></tr>';
            } else {
                rowsEl.innerHTML = rows.map(function (row, idx) {
                    var badgeClass = String(row.status || '').toUpperCase() === 'COMPLETED' ? 'badge-success' : 'badge-warning';
                    return '<tr>'
                        + '<td>' + (idx + 1) + '</td>'
                        + '<td>' + (row.subject || '-') + '</td>'
                        + '<td>' + (row.schedule || '-') + '</td>'
                        + '<td><span class="badge ' + badgeClass + '">' + (row.status || '-') + '</span></td>'
                        + '</tr>';
                }).join('');
            }

            openModal();
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
})();
</script>
@else
<script>
(function () {
    var modal = document.getElementById('tutorSessionDetailModal');
    var closeBtn = document.getElementById('closeTutorSessionDetailModal');
    if (!modal || !closeBtn) return;

    var detailStudent = document.getElementById('detailStudent');
    var detailSubject = document.getElementById('detailSubject');
    var detailTime = document.getElementById('detailTime');
    var detailStatus = document.getElementById('detailStatus');
    var detailMode = document.getElementById('detailMode');
    var detailAddress = document.getElementById('detailAddress');
    var detailLocationNotes = document.getElementById('detailLocationNotes');
    var detailMapsWrap = document.getElementById('detailMapsWrap');
    var detailMapsLink = document.getElementById('detailMapsLink');
    var info = document.getElementById('detailActionInfo');

    var startForm = document.getElementById('detailStartForm');
    var attendanceForm = document.getElementById('detailAttendanceForm');
    var materialForm = document.getElementById('detailMaterialForm');
    var startBtn = document.getElementById('detailStartBtn');
    var attendanceBtn = document.getElementById('detailAttendanceBtn');
    var materialBtn = document.getElementById('detailMaterialBtn');
    var materialSummary = document.getElementById('detailMaterialSummary');
    var studentPresentToggle = document.getElementById('detailStudentPresentToggle');
    var studentPresentValue = document.getElementById('detailStudentPresentValue');
    var startLocationStatus = document.getElementById('detailStartLocationStatus');
    var startLatitude = document.getElementById('detailStartLatitude');
    var startLongitude = document.getElementById('detailStartLongitude');
    var attendanceLocationStatus = document.getElementById('detailAttendanceLocationStatus');
    var attendanceLatitude = document.getElementById('detailAttendanceLatitude');
    var attendanceLongitude = document.getElementById('detailAttendanceLongitude');

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function applyLocation(latitude, longitude) {
        if (startLocationStatus) startLocationStatus.value = 'ALLOW';
        if (startLatitude) startLatitude.value = latitude;
        if (startLongitude) startLongitude.value = longitude;
        if (attendanceLocationStatus) attendanceLocationStatus.value = 'ALLOW';
        if (attendanceLatitude) attendanceLatitude.value = latitude;
        if (attendanceLongitude) attendanceLongitude.value = longitude;
    }

    function clearLocation() {
        if (startLocationStatus) startLocationStatus.value = 'DENIED';
        if (startLatitude) startLatitude.value = '';
        if (startLongitude) startLongitude.value = '';
        if (attendanceLocationStatus) attendanceLocationStatus.value = 'DENIED';
        if (attendanceLatitude) attendanceLatitude.value = '';
        if (attendanceLongitude) attendanceLongitude.value = '';
    }

    function refreshLocation() {
        clearLocation();
        if (!navigator.geolocation) return;

        navigator.geolocation.getCurrentPosition(
            function (position) {
                applyLocation(position.coords.latitude, position.coords.longitude);
            },
            function () {
                clearLocation();
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    function setActionEnabled(enabled) {
        var disabled = !enabled;
        startBtn.disabled = disabled;
        attendanceBtn.disabled = disabled;
        materialBtn.disabled = disabled;
        materialSummary.disabled = disabled;
        if (disabled) {
            info.style.display = '';
            info.textContent = 'Aksi hanya bisa dilakukan saat jam sesi sudah dimulai.';
        } else {
            info.style.display = 'none';
            info.textContent = '';
        }
    }

    document.querySelectorAll('.open-tutor-session-detail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            detailStudent.value = btn.getAttribute('data-student') || '-';
            detailSubject.value = btn.getAttribute('data-subject') || '-';
            detailTime.value = btn.getAttribute('data-time') || '-';
            detailStatus.value = btn.getAttribute('data-status') || '-';
            detailMode.value = btn.getAttribute('data-mode') || '-';

            var modeValue = String(btn.getAttribute('data-mode') || '').toUpperCase();
            var addressValue = btn.getAttribute('data-address') || '';
            var locationNotesValue = btn.getAttribute('data-location-notes') || '';
            var mapsUrl = btn.getAttribute('data-maps-url') || '';
            if (modeValue === 'OFFLINE') {
                detailAddress.value = addressValue || 'Alamat siswa belum diisi.';
                detailLocationNotes.value = locationNotesValue || 'Catatan lokasi belum diisi.';
                if (detailMapsWrap && detailMapsLink && mapsUrl) {
                    detailMapsWrap.style.display = '';
                    detailMapsLink.href = mapsUrl;
                } else if (detailMapsWrap && detailMapsLink) {
                    detailMapsWrap.style.display = 'none';
                    detailMapsLink.href = '#';
                }
            } else {
                detailAddress.value = 'Sesi online. Alamat siswa tidak ditampilkan.';
                detailLocationNotes.value = 'Sesi online. Catatan lokasi tidak ditampilkan.';
                if (detailMapsWrap && detailMapsLink) {
                    detailMapsWrap.style.display = 'none';
                    detailMapsLink.href = '#';
                }
            }

            startForm.action = btn.getAttribute('data-start-route') || '#';
            attendanceForm.action = btn.getAttribute('data-attendance-route') || '#';
            materialForm.action = btn.getAttribute('data-material-route') || '#';

            setActionEnabled((btn.getAttribute('data-can-action') || '0') === '1');
            if (studentPresentToggle) studentPresentToggle.checked = true;
            if (studentPresentValue) studentPresentValue.value = '1';
            refreshLocation();
            openModal();
        });
    });

    if (studentPresentToggle && studentPresentValue) {
        studentPresentToggle.addEventListener('change', function () {
            studentPresentValue.value = studentPresentToggle.checked ? '1' : '0';
        });
    }

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
})();
</script>
@endif
@endpush
