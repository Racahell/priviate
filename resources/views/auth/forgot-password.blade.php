@extends('layouts.master')

@section('title', 'Lupa Password')

@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <div class="auth-card-head">
            <h1 class="page-title text-center">Reset Password via WA atau Email</h1>
            <p class="page-subtitle text-center">Pilih channel OTP yang paling nyaman untuk Anda.</p>
        </div>
        <div class="auth-card-body">
            <form action="{{ route('password.forgot.send') }}" method="POST">
                @csrf
                <div class="form-group @error('email') has-error @enderror">
                    <label>Email akun</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                    @error('email') <span class="help-block">{{ $message }}</span> @enderror
                </div>
                <div class="form-group @error('channel') has-error @enderror">
                    <label>Kirim kode OTP melalui</label>
                    <select name="channel" class="form-control">
                        <option value="EMAIL">Email</option>
                        <option value="WHATSAPP">WhatsApp</option>
                    </select>
                    @error('channel') <span class="help-block">{{ $message }}</span> @enderror
                </div>
                <button class="btn btn-primary btn-block" type="submit">Kirim OTP</button>
            </form>
            <div class="auth-links text-center">
                <p><a href="{{ route('login') }}">Kembali ke Login</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
