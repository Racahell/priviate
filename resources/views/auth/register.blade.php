@extends('layouts.master')

@section('title', 'Register')

@section('content')
@php
    $brandLogo = $webLogo
        ?: (file_exists(public_path('img/priviate-logo.png')) ? 'img/priviate-logo.png'
        : (file_exists(public_path('img/priviate-logo.svg')) ? 'img/priviate-logo.svg' : null));
    $subjects = $subjects ?? collect();
@endphp
<div class="auth-shell">
    <div class="card auth-card">
        <div class="auth-card-head">
            <div class="auth-brand">
                @if(!empty($brandLogo))
                    <img src="{{ asset($brandLogo) }}" alt="PriviAte" class="auth-logo">
                @else
                    <strong>PriviAte</strong>
                @endif
            </div>
            <h1 class="page-title text-center">Bergabung dengan PrivTuition</h1>
            <p class="page-subtitle text-center">Buat akun untuk mulai perjalanan belajar yang fleksibel.</p>
        </div>

        <div class="auth-card-body">
            <form action="{{ route('register.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="alert alert-info">
                    Setelah submit, link verifikasi email akan dikirim ke email yang Anda isi.
                </div>

                <div class="form-group @error('name') has-error @enderror">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('email') has-error @enderror">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    @error('email') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('phone') has-error @enderror">
                    <label>No HP</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
                    @error('phone') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-2">
                    <div class="form-group @error('password') has-error @enderror">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                        @error('password') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <div class="form-group @error('role') has-error @enderror">
                    <label>Daftar Sebagai</label>
                    <select name="role" id="register-role" class="form-control">
                        <option value="siswa" {{ old('role') == 'siswa' ? 'selected' : '' }}>Siswa (Ingin Belajar)</option>
                        <option value="tentor" {{ old('role') == 'tentor' ? 'selected' : '' }}>Tentor (Ingin Mengajar)</option>
                        <option value="orang_tua" {{ old('role') == 'orang_tua' ? 'selected' : '' }}>Orang Tua</option>
                    </select>
                    @error('role') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div id="tentor-register-fields" style="display:none;">
                    <div class="alert alert-warning">
                        Data tentor akan masuk status <strong>PENDING_REVIEW</strong> dan diverifikasi admin sebelum aktif mengajar.
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group @error('education') has-error @enderror">
                            <label>Pendidikan Terakhir</label>
                            <input type="text" name="education" class="form-control" value="{{ old('education') }}">
                            @error('education') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('experience_years') has-error @enderror">
                            <label>Pengalaman Mengajar (tahun)</label>
                            <input type="number" min="0" max="60" name="experience_years" class="form-control" value="{{ old('experience_years') }}">
                            @error('experience_years') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group @error('domicile') has-error @enderror">
                            <label>Domisili</label>
                            <input type="text" name="domicile" class="form-control" value="{{ old('domicile') }}">
                            @error('domicile') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('teaching_mode') has-error @enderror">
                            <label>Mode Mengajar</label>
                            <select name="teaching_mode" class="form-control">
                                <option value="online" {{ old('teaching_mode') === 'online' ? 'selected' : '' }}>Online</option>
                                <option value="offline" {{ old('teaching_mode') === 'offline' ? 'selected' : '' }}>Offline</option>
                                <option value="hybrid" {{ old('teaching_mode') === 'hybrid' ? 'selected' : '' }}>Keduanya</option>
                            </select>
                            @error('teaching_mode') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-group @error('offline_coverage') has-error @enderror">
                        <label>Area Jangkauan Offline (opsional)</label>
                        <input type="text" name="offline_coverage" class="form-control" value="{{ old('offline_coverage') }}" placeholder="Contoh: Jakarta Selatan">
                        @error('offline_coverage') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group @error('tentor_bio') has-error @enderror">
                        <label>Ringkasan Pengalaman</label>
                        <textarea name="tentor_bio" class="form-control" rows="3">{{ old('tentor_bio') }}</textarea>
                        @error('tentor_bio') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group @error('teaching_subject_ids') has-error @enderror">
                        <label>Mapel yang Ingin Diajarkan</label>
                        <div class="grid grid-2" style="margin-top:8px;">
                            @foreach($subjects as $subject)
                                <label class="checkbox">
                                    <input type="checkbox" name="teaching_subject_ids[]" value="{{ $subject->id }}" {{ in_array((int) $subject->id, collect(old('teaching_subject_ids', []))->map(fn($v) => (int) $v)->all(), true) ? 'checked' : '' }}>
                                    {{ $subject->name }} ({{ $subject->level }})
                                </label>
                            @endforeach
                        </div>
                        @error('teaching_subject_ids') <span class="help-block">{{ $message }}</span> @enderror
                        @error('teaching_subject_ids.*') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group @error('cv_file') has-error @enderror">
                            <label>Upload CV</label>
                            <input type="file" name="cv_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @error('cv_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('diploma_file') has-error @enderror">
                            <label>Upload Ijazah</label>
                            <input type="file" name="diploma_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @error('diploma_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('certificate_file') has-error @enderror">
                            <label>Upload Sertifikat (opsional)</label>
                            <input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @error('certificate_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('id_card_file') has-error @enderror">
                            <label>Upload KTP</label>
                            <input type="file" name="id_card_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @error('id_card_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('profile_photo_file') has-error @enderror">
                            <label>Foto Profil</label>
                            <input type="file" name="profile_photo_file" class="form-control" accept=".jpg,.jpeg,.png">
                            @error('profile_photo_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('intro_video_url') has-error @enderror">
                            <label>Link Video Perkenalan (opsional)</label>
                            <input type="url" name="intro_video_url" class="form-control" value="{{ old('intro_video_url') }}" placeholder="https://...">
                            @error('intro_video_url') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div id="offline-captcha-group" class="form-group @error('captcha') has-error @enderror">
                    <label>Keamanan: {{ $captchaQuestion }}</label>
                    <input id="offline-captcha-input" type="number" name="captcha" class="form-control" placeholder="Hasil perhitungan...">
                    @error('captcha') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                @if(config('services.recaptcha.enabled') && !empty($recaptchaSiteKey))
                    <div id="online-recaptcha-group" class="form-group">
                        <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                    </div>
                @endif

                <input type="hidden" name="connection_status" id="connection_status" value="online">
                <input type="hidden" name="location_status" id="location_status" value="DENIED">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                <div class="checkbox @error('terms') has-error @enderror">
                    <label>
                        <input type="checkbox" name="terms" required> Saya setuju dengan Syarat & Ketentuan dan Kebijakan Privasi.
                    </label>
                    @error('terms') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn btn-success btn-block">Daftar Sekarang</button>

                <div class="auth-links text-center">
                    <p>Sudah punya akun? <a href="{{ route('login') }}">Login disini</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

@if(config('services.recaptcha.enabled') && !empty($recaptchaSiteKey))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    var hasRecaptcha = {{ config('services.recaptcha.enabled') && !empty($recaptchaSiteKey) ? 'true' : 'false' }};
    var offlineGroup = document.getElementById('offline-captcha-group');
    var offlineInput = document.getElementById('offline-captcha-input');
    var onlineGroup = document.getElementById('online-recaptcha-group');
    var connectionInput = document.getElementById('connection_status');
    var roleSelect = document.getElementById('register-role');
    var tentorFields = document.getElementById('tentor-register-fields');

    function updateCaptchaMode() {
        var isOnline = navigator.onLine;
        if (connectionInput) {
            connectionInput.value = isOnline ? 'online' : 'offline';
        }

        if (!hasRecaptcha) {
            if (offlineGroup) offlineGroup.style.display = '';
            if (offlineInput) offlineInput.required = true;
            return;
        }

        if (isOnline) {
            if (onlineGroup) onlineGroup.style.display = '';
            if (offlineGroup) offlineGroup.style.display = 'none';
            if (offlineInput) offlineInput.required = false;
        } else {
            if (onlineGroup) onlineGroup.style.display = 'none';
            if (offlineGroup) offlineGroup.style.display = '';
            if (offlineInput) offlineInput.required = true;
        }
    }

    updateCaptchaMode();
    window.addEventListener('online', updateCaptchaMode);
    window.addEventListener('offline', updateCaptchaMode);

    function toggleTentorFields() {
        if (!roleSelect || !tentorFields) return;
        tentorFields.style.display = roleSelect.value === 'tentor' ? '' : 'none';
    }
    if (roleSelect) {
        roleSelect.addEventListener('change', toggleTentorFields);
    }
    toggleTentorFields();

    if (!navigator.geolocation) {
        return;
    }
    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('location_status').value = 'ALLOW';
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
        },
        function() {
            document.getElementById('location_status').value = 'DENIED';
        }
    );
});
</script>
@endsection
