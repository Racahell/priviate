@extends('layouts.master')

@section('title', 'Register')

@section('content')
<div class="row">
    <div class="col-lg-offset-3 col-lg-6 col-md-offset-2 col-md-8">
        <div class="panel panel-default" style="margin-top: 50px;">
            <div class="panel-heading">
                <h3 class="panel-title">Bergabung dengan PrivTuition</h3>
            </div>
            <div class="panel-body">
                <form action="{{ route('register.post') }}" method="POST">
                    @csrf
                    <div class="alert alert-info">
                        Email terverifikasi: <strong>{{ $preVerifiedEmail ?? '-' }}</strong>
                    </div>
                    
                    <div class="form-group @error('name') has-error @enderror">
                        <label>Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        @error('name') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group @error('email') has-error @enderror">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $preVerifiedEmail ?? '') }}" required readonly>
                        @error('email') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group @error('password') has-error @enderror">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                                @error('password') <span class="help-block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group @error('role') has-error @enderror">
                        <label>Daftar Sebagai</label>
                        <select name="role" class="form-control">
                            <option value="siswa" {{ old('role') == 'siswa' ? 'selected' : '' }}>Siswa (Ingin Belajar)</option>
                            <option value="tentor" {{ old('role') == 'tentor' ? 'selected' : '' }}>Tentor (Ingin Mengajar)</option>
                        </select>
                        @error('role') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group @error('captcha') has-error @enderror">
                        <label>Keamanan: {{ $captchaQuestion }}</label>
                        <input type="number" name="captcha" class="form-control" placeholder="Hasil perhitungan..." required>
                        @error('captcha') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    @if(config('services.recaptcha.enabled') && !empty($recaptchaSiteKey))
                        <div class="form-group">
                            <label>Verifikasi Online (Google reCAPTCHA)</label>
                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                            <p class="help-block">Jika tidak tersedia, sistem memakai captcha offline.</p>
                        </div>
                    @endif

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
                    
                    <hr>
                    <p class="text-center">
                        Sudah punya akun? <a href="{{ route('login') }}">Login disini</a><br>
                        Belum verifikasi email? <a href="{{ route('register.preverify') }}">Kirim link aktivasi</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

@if(config('services.recaptcha.enabled') && !empty($recaptchaSiteKey))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
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
