@extends('layouts.master')

@section('title', 'Dashboard Owner')

@section('content')
<div class="grid grid-3">
    <div class="card">
        <h3 class="card-title">Total Siswa</h3>
        <p class="stat-value">{{ $totalStudents }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Total Guru</h3>
        <p class="stat-value">{{ $totalTentors }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Revenue</h3>
        <p class="stat-value">Rp {{ number_format($revenue, 0, ',', '.') }}</p>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Escrow Outstanding</h3>
    <p class="stat-value">Rp {{ number_format($deferredRevenue, 0, ',', '.') }}</p>
    <div class="section"><a href="{{ route('owner.reports') }}" class="btn btn-primary">Laporan Keuangan</a></div>
</div>
@endsection
