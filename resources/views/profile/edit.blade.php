@extends('layouts.master')

@section('title', 'Profil')

@section('content')
<div class="card">
    <h3 class="card-title">Profil Saya</h3>
    <p class="card-meta">Perbarui data akun agar proses belajar berjalan lancar.</p>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="section">
        @csrf
        <div class="grid grid-2">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="{{ $user->email }}" readonly>
            </div>
        </div>

        @if($user->hasRole('siswa'))
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Kode Siswa</label>
                    <input type="text" class="form-control" value="{{ $user->code }}" readonly>
                    <small class="text-muted">Berikan kode ini ke akun orang tua untuk monitoring progress belajar.</small>
                </div>
            </div>
        @endif

        <div class="grid grid-3">
            <div class="form-group">
                <label>No HP</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="form-group">
                <label>Kota</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $user->city) }}">
            </div>
            <div class="form-group">
                <label>Provinsi</label>
                <input type="text" name="province" class="form-control" value="{{ old('province', $user->province) }}">
            </div>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" class="form-control">{{ old('address', $user->address) }}</textarea>
            </div>
            <div class="form-group">
                <label>Kode Pos</label>
                <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $user->postal_code) }}">
            </div>
        </div>

        @if($user->hasRole('siswa'))
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Titik Lokasi</label>
                    <input type="text" id="profileCoordinatesPreview" class="form-control" value="{{ old('latitude', $user->latitude) && old('longitude', $user->longitude) ? old('latitude', $user->latitude) . ', ' . old('longitude', $user->longitude) : (($user->latitude && $user->longitude) ? $user->latitude . ', ' . $user->longitude : '') }}" placeholder="Belum ada titik lokasi" readonly>
                    <input type="hidden" name="latitude" id="profileLatitude" value="{{ old('latitude', $user->latitude) }}">
                    <input type="hidden" name="longitude" id="profileLongitude" value="{{ old('longitude', $user->longitude) }}">
                    <small class="text-muted">Dipakai untuk membantu tentor menemukan lokasi saat sesi offline.</small>
                </div>
                <div class="form-group">
                    <label>Aksi Lokasi</label>
                    <button type="button" class="btn btn-outline" id="captureProfileLocationBtn">Gunakan Lokasi Saat Ini</button>
                    <a href="{{ ($user->latitude && $user->longitude) ? ('https://maps.google.com/?q=' . $user->latitude . ',' . $user->longitude) : '#' }}" target="_blank" rel="noopener" class="btn btn-outline" id="profileMapsLink" style="{{ ($user->latitude && $user->longitude) ? '' : 'display:none;' }}">Lihat di Google Maps</a>
                </div>
            </div>
            @if($supportsLocationNotes ?? false)
            <div class="grid grid-1">
                <div class="form-group">
                    <label>Catatan Lokasi</label>
                    <textarea name="location_notes" class="form-control" rows="3" placeholder="Contoh: Rumah warna putih dekat masjid, pagar hitam, masuk gang sebelah minimarket.">{{ old('location_notes', $user->location_notes) }}</textarea>
                    <small class="text-muted">Catatan ini akan ditampilkan ke tentor hanya untuk sesi offline yang sudah ditugaskan.</small>
                </div>
            </div>
            @endif
        @endif

        <div class="grid grid-2">
            <div class="form-group">
                <label>Avatar</label>
                <input type="file" name="avatar" class="form-control">
                @if(!empty($user->avatar))
                    <div class="profile-avatar-preview">
                        <img src="{{ asset($user->avatar) }}" alt="Avatar">
                    </div>
                @endif
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Profil</button>
    </form>
</div>

<div class="card profile-otp-card">
    <h3 class="card-title">Reset Password via OTP</h3>
    <p class="card-meta">Kirim kode OTP melalui WhatsApp atau Email untuk reset password secara aman.</p>
    <div class="profile-otp-actions">
        <a href="{{ route('password.forgot', ['email' => $user->email]) }}" class="btn btn-outline profile-otp-btn">Kirim OTP Reset Password</a>
    </div>
</div>
@endsection

@push('scripts')
@if($user->hasRole('siswa'))
<script>
(function () {
    var captureBtn = document.getElementById('captureProfileLocationBtn');
    var latitudeInput = document.getElementById('profileLatitude');
    var longitudeInput = document.getElementById('profileLongitude');
    var previewInput = document.getElementById('profileCoordinatesPreview');
    var mapsLink = document.getElementById('profileMapsLink');
    if (!captureBtn || !latitudeInput || !longitudeInput || !previewInput) return;

    captureBtn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung geolocation.');
            return;
        }

        captureBtn.disabled = true;
        captureBtn.textContent = 'Mengambil lokasi...';

        navigator.geolocation.getCurrentPosition(
            function (position) {
                var latitude = Number(position.coords.latitude).toFixed(8);
                var longitude = Number(position.coords.longitude).toFixed(8);
                latitudeInput.value = latitude;
                longitudeInput.value = longitude;
                previewInput.value = latitude + ', ' + longitude;
                if (mapsLink) {
                    mapsLink.href = 'https://maps.google.com/?q=' + latitude + ',' + longitude;
                    mapsLink.style.display = '';
                }
                captureBtn.disabled = false;
                captureBtn.textContent = 'Gunakan Lokasi Saat Ini';
            },
            function () {
                captureBtn.disabled = false;
                captureBtn.textContent = 'Gunakan Lokasi Saat Ini';
                alert('Lokasi gagal diambil. Pastikan izin lokasi di browser aktif.');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
})();
</script>
@endif
@endpush
