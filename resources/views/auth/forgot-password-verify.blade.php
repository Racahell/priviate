@extends('layouts.master')

@section('title', 'Verifikasi OTP Reset')

@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <div class="auth-card-head">
            <h1 class="page-title text-center">Masukkan OTP dan Password Baru</h1>
            <p class="page-subtitle text-center">
                Gunakan OTP yang barusan kamu terima
                @if(!empty($destinationLabel))
                    di {{ strtoupper($channel ?? 'EMAIL') === 'EMAIL' ? 'email' : 'nomor' }} {{ $destinationLabel }}
                @endif
                .
            </p>
        </div>
        <div class="auth-card-body">
            <form action="{{ route('password.forgot.reset', $requestId) }}" method="POST">
                @csrf
                <div class="form-group @error('otp_code') has-error @enderror">
                    <label>Kode OTP</label>
                    <input type="text" name="otp_code" class="form-control" maxlength="6" required>
                    @error('otp_code') <span class="help-block">{{ $message }}</span> @enderror
                </div>
                <div class="form-group @error('password') has-error @enderror">
                    <label>Password baru</label>
                    <input type="password" name="password" class="form-control" required>
                    @error('password') <span class="help-block">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Konfirmasi password baru</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <button class="btn btn-success btn-block" type="submit">Reset Password</button>
            </form>
            <hr>
            <form action="{{ route('password.forgot.resend', $requestId) }}" method="POST">
                @csrf
                <p class="text-center">Belum menerima OTP?</p>
                <button class="btn btn-default btn-block" type="submit">
                    Kirim Ulang OTP{{ strtoupper($channel ?? 'EMAIL') === 'EMAIL' ? ' via Email' : ' via WhatsApp' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
