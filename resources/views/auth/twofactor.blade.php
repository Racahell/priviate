@extends('layouts.master')

@section('title', 'Verifikasi 2FA')

@section('content')
<div class="auth-shell narrow">
    <div class="card auth-card">
        <div class="auth-card-head">
            <h1 class="page-title text-center">OTP 2FA</h1>
            <p class="page-subtitle text-center">Masukkan kode 6 digit yang dikirim ke email Anda.</p>
        </div>
        <div class="auth-card-body">
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            <form action="{{ route('twofactor.verify') }}" method="POST">
                @csrf
                <div class="form-group @error('otp_code') has-error @enderror">
                    <label>Kode OTP (6 digit)</label>
                    <input type="text" name="otp_code" class="form-control" maxlength="6" required>
                    @error('otp_code') <span class="help-block">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-block">Verifikasi</button>
            </form>
        </div>
    </div>
</div>
@endsection
