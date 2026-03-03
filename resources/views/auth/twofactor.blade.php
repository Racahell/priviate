@extends('layouts.master')

@section('title', 'Verifikasi 2FA')

@section('content')
<div class="row">
    <div class="col-lg-offset-4 col-lg-4 col-md-offset-3 col-md-6">
        <div class="panel panel-default" style="margin-top: 50px;">
            <div class="panel-heading">
                <h3 class="panel-title">OTP 2FA</h3>
            </div>
            <div class="panel-body">
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
</div>
@endsection
