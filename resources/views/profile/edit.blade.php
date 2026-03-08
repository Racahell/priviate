@extends('layouts.master')

@section('title', 'Profil')

@section('content')
<div class="card">
    <h3 class="card-title">Profil Saya</h3>
    <p class="card-meta">Perbarui data akun agar proses belajar berjalan lancar.</p>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="section">
        @csrf
        <div class="grid grid-2">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="{{ $user->email }}" readonly>
            </div>
        </div>

        @if($user->hasRole('siswa'))
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Kode Siswa</label>
                    <input type="text" class="form-control" value="{{ $user->code }}" readonly>
                    <small class="text-muted">Berikan kode ini ke akun orang tua untuk monitoring progress belajar.</small>
                </div>
            </div>
        @endif

        <div class="grid grid-3">
            <div class="form-group">
                <label>No HP</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="form-group">
                <label>Kota</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $user->city) }}">
            </div>
            <div class="form-group">
                <label>Provinsi</label>
                <input type="text" name="province" class="form-control" value="{{ old('province', $user->province) }}">
            </div>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" class="form-control">{{ old('address', $user->address) }}</textarea>
            </div>
            <div class="form-group">
                <label>Kode Pos</label>
                <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $user->postal_code) }}">
            </div>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Avatar</label>
                <input type="file" name="avatar" class="form-control">
                @if(!empty($user->avatar))
                    <div class="profile-avatar-preview">
                        <img src="{{ asset($user->avatar) }}" alt="Avatar">
                    </div>
                @endif
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Profil</button>
    </form>
</div>

<div class="card profile-otp-card">
    <h3 class="card-title">Reset Password via OTP</h3>
    <p class="card-meta">Kirim kode OTP melalui WhatsApp atau Email untuk reset password secara aman.</p>
    <div class="profile-otp-actions">
        <a href="{{ route('password.forgot', ['email' => $user->email]) }}" class="btn btn-outline profile-otp-btn">Kirim OTP Reset Password</a>
    </div>
</div>
@endsection
