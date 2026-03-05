@extends('layouts.master')

@section('title', 'Register')

@section('content')
@php
    $brandLogo = $webLogo
        ?: (file_exists(public_path('img/priviate-logo.png')) ? 'img/priviate-logo.png'
        : (file_exists(public_path('img/priviate-logo.svg')) ? 'img/priviate-logo.svg' : null));
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
            <form action="{{ route('register.post') }}" method="POST">
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
                    <select name="role" class="form-control">
                        <option value="siswa" {{ old('role') == 'siswa' ? 'selected' : '' }}>Siswa (Ingin Belajar)</option>
                        <option value="tentor" {{ old('role') == 'tentor' ? 'selected' : '' }}>Tentor (Ingin Mengajar)</option>
                        <option value="orang_tua" {{ old('role') == 'orang_tua' ? 'selected' : '' }}>Orang Tua</option>
                    </select>
                    @error('role') <span class="help-block">{{ $message }}</span> @enderror
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
