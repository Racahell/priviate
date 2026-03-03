@extends('layouts.master')

@section('title', 'Lupa Password')

@section('content')
<div class="row">
    <div class="col-lg-offset-3 col-lg-6 col-md-offset-2 col-md-8">
        <div class="panel panel-default" style="margin-top: 40px;">
            <div class="panel-heading">
                <h3 class="panel-title">Reset Password via WA atau Email</h3>
            </div>
            <div class="panel-body">
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
                <hr>
                <p class="text-center"><a href="{{ route('login') }}">Kembali ke Login</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
