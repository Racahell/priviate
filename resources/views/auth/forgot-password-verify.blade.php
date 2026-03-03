@extends('layouts.master')

@section('title', 'Verifikasi OTP Reset')

@section('content')
<div class="row">
    <div class="col-lg-offset-3 col-lg-6 col-md-offset-2 col-md-8">
        <div class="panel panel-default" style="margin-top: 40px;">
            <div class="panel-heading">
                <h3 class="panel-title">Masukkan OTP dan Password Baru</h3>
            </div>
            <div class="panel-body">
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
            </div>
        </div>
    </div>
</div>
@endsection
