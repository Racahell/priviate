@extends('layouts.master')

@section('title', 'Dashboard Siswa')

@section('content')
<div class="grid grid-3">
    <div class="card">
        <h3 class="card-title">Upcoming Session</h3>
        @if($upcomingSession)
            <p class="card-meta">{{ $upcomingSession->scheduled_at }}</p>
            <p class="badge badge-warning">Status: {{ $upcomingSession->status }}</p>
        @else
            <p class="card-meta">Tidak ada sesi terjadwal.</p>
        @endif
    </div>

    <div class="card">
        <h3 class="card-title">Invoice Belum Lunas</h3>
        <p class="stat-value">{{ $unpaidInvoicesCount }}</p>
        <p class="card-meta">Perlu ditindaklanjuti</p>
    </div>

    <div class="card">
        <h3 class="card-title">Saldo Wallet</h3>
        <p class="stat-value">Rp {{ number_format($walletBalance, 0, ',', '.') }}</p>
        <p class="card-meta">Saldo aktif siswa</p>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Aksi Cepat</h3>
    <div class="grid grid-3">
        <a href="{{ route('student.packages') }}" class="btn btn-outline">Pilih Paket Belajar</a>
        <a href="{{ route('student.booking') }}" class="btn btn-outline">Booking Sesi</a>
        <a href="{{ route('student.invoices') }}" class="btn btn-outline">Lihat Invoice</a>
        <a href="{{ route('profile.edit') }}" class="btn btn-outline">Update Profil</a>
    </div>
</div>
@endsection
