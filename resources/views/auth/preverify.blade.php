@extends('layouts.master')

@section('title', 'Verifikasi Email')

@section('content')
<div class="row">
    <div class="col-lg-offset-3 col-lg-6 col-md-offset-2 col-md-8">
        <div class="panel panel-default" style="margin-top: 40px;">
            <div class="panel-heading">
                <h3 class="panel-title">Verifikasi Email Sebelum Daftar</h3>
            </div>
            <div class="panel-body">
                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('register.preverify.send') }}">
                    @csrf
                    <div class="form-group @error('email') has-error @enderror">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                        @error('email') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Kirim Link Aktivasi</button>
                </form>
                <hr>
                <p class="text-muted">Setelah klik link aktivasi dari email, Anda akan diarahkan ke form registrasi.</p>
                <p class="text-center"><a href="{{ route('login') }}">Sudah punya akun? Login</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
