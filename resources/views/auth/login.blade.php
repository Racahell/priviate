@extends('layouts.master')

@section('title', 'Login')

@section('content')
<div class="row">
    <div class="col-lg-offset-4 col-lg-4 col-md-offset-3 col-md-6">
        <div class="panel panel-default" style="margin-top: 50px;">
            <div class="panel-heading">
                <h3 class="panel-title">Login to PrivTuition</h3>
            </div>
            <div class="panel-body">
                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    
                    <div class="form-group @error('email') has-error @enderror">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                        @error('email') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group @error('password') has-error @enderror">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                        @error('password') <span class="help-block">{{ $message }}</span> @enderror
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
                            <p class="help-block">Jika tidak bisa di-load, sistem akan pakai captcha offline di atas.</p>
                        </div>
                    @endif

                    <input type="hidden" name="location_status" id="location_status" value="DENIED">
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="remember"> Remember Me
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                    
                    <hr>
                    <p class="text-center">
                        Belum punya akun? <a href="{{ route('register.preverify') }}">Daftar disini</a>
                    </p>
                    <p class="text-center">
                        Lupa password? <a href="{{ route('password.forgot') }}">Reset via WA/Email</a>
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
