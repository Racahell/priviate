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
                    @endif
                    <th>Mapel</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $index => $session)
                    <tr>
                        <td>{{ ($sessions->currentPage() - 1) * $sessions->perPage() + $index + 1 }}</td>
                        <td>{{ $session->student?->name ?: $session->student_id }}</td>
                        @if($isAdminViewer ?? false)
                            <td>{{ $session->tentor?->name ?: $session->tentor_id }}</td>
                        @endif
                        <td>{{ $session->subject?->name ?: $session->subject_id }}</td>
                        <td>{{ optional($session->scheduled_at)->format('d M Y H:i') }}</td>
                        <td>
                            <span class="badge {{ $session->status === 'completed' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($session->status) }}</span>
                        </td>
                        <td>
                            @if($isAdminViewer ?? false)
                                <span class="text-muted">Monitoring</span>
                            @else
                                @php($canAction = !$session->scheduled_at || now()->greaterThanOrEqualTo($session->scheduled_at))
                                <button
                                    type="button"
                                    class="btn btn-outline btn-xs open-tutor-session-detail"
                                    data-session-id="{{ $session->id }}"
                                    data-student="{{ $session->student?->name ?: $session->student_id }}"
                                    data-subject="{{ $session->subject?->name ?: $session->subject_id }}"
                                    data-time="{{ optional($session->scheduled_at)->format('d M Y H:i') }}"
                                    data-status="{{ strtoupper((string) $session->status) }}"
                                    data-can-action="{{ $canAction ? '1' : '0' }}"
                                    data-start-route="{{ route('ops.session.start', $session->id) }}"
                                    data-attendance-route="{{ route('ops.attendance.mark', $session->id) }}"
                                    data-material-route="{{ route('ops.material.submit', $session->id) }}">
                                    Detail
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ ($isAdminViewer ?? false) ? 7 : 6 }}">Belum ada jadwal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!($isAdminViewer ?? false))
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
        </div>
        <div class="alert alert-info" id="detailActionInfo" style="display:none;"></div>
        <div class="section action-stack">
            <form method="POST" id="detailStartForm">
                @csrf
                <button class="btn btn-primary" type="submit" id="detailStartBtn">Mulai</button>
            </form>
            <form method="POST" id="detailAttendanceForm">
                @csrf
                <input type="hidden" name="student_present" value="1">
                <input type="hidden" name="location_status" value="DENIED">
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
@if(!($isAdminViewer ?? false))
<script>
(function () {
    var modal = document.getElementById('tutorSessionDetailModal');
    var closeBtn = document.getElementById('closeTutorSessionDetailModal');
    if (!modal || !closeBtn) return;

    var detailStudent = document.getElementById('detailStudent');
    var detailSubject = document.getElementById('detailSubject');
    var detailTime = document.getElementById('detailTime');
    var detailStatus = document.getElementById('detailStatus');
    var info = document.getElementById('detailActionInfo');

    var startForm = document.getElementById('detailStartForm');
    var attendanceForm = document.getElementById('detailAttendanceForm');
    var materialForm = document.getElementById('detailMaterialForm');
    var startBtn = document.getElementById('detailStartBtn');
    var attendanceBtn = document.getElementById('detailAttendanceBtn');
    var materialBtn = document.getElementById('detailMaterialBtn');
    var materialSummary = document.getElementById('detailMaterialSummary');

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
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

            startForm.action = btn.getAttribute('data-start-route') || '#';
            attendanceForm.action = btn.getAttribute('data-attendance-route') || '#';
            materialForm.action = btn.getAttribute('data-material-route') || '#';

            setActionEnabled((btn.getAttribute('data-can-action') || '0') === '1');
            openModal();
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
})();
</script>
@endif
@endpush

