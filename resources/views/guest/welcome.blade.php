@extends('layouts.master')

@section('title', 'Welcome to PrivTuition')

@section('content')
<!-- Hero Section -->
<div class="row">
    <div class="col-lg-12">
        <div class="jumbotron text-center" style="background: #f7f7f7; padding: 50px 20px; border-radius: 10px;">
            <h1>Cari Tentor Terbaik di Sekitarmu</h1>
            <p>Platform les privat terpercaya dengan sistem pembayaran aman.</p>
            
            <form action="{{ route('home') }}" method="GET" class="form-inline" style="margin-top: 30px;">
                <div class="form-group">
                    <input type="text" class="form-control input-lg" placeholder="Subjek (Matematika, Fisika...)" name="subject">
                </div>
                <div class="form-group">
                    <input type="text" class="form-control input-lg" placeholder="Lokasi (Kecamatan/Kota)" name="location">
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Cari Sekarang</button>
            </form>
        </div>
    </div>
</div>

<!-- Stats Widget -->
<div class="row text-center">
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
        <div class="info-box blue-bg">
            <i class="fa fa-graduation-cap"></i>
            <div class="count">{{ $totalSessions }}</div>
            <div class="title">Sesi Berhasil</div>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
        <div class="info-box brown-bg">
            <i class="fa fa-users"></i>
            <div class="count">{{ $totalTentors }}</div>
            <div class="title">Tentor Aktif</div>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
        <div class="info-box dark-bg">
            <i class="fa fa-star"></i>
            <div class="count">{{ number_format($avgRating, 1) }}</div>
            <div class="title">Rata-rata Rating</div>
        </div>
    </div>
</div>

<!-- Tutor Discovery -->
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header"><i class="fa fa-search"></i> Rekomendasi Tentor</h3>
    </div>
    
    @forelse($tutors as $tutor)
    <div class="col-lg-4 col-md-4 col-sm-6">
        <div class="panel panel-default">
            <div class="panel-body text-center">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($tutor->name) }}&background=random" class="img-circle" width="100">
                <h4>{{ $tutor->name }}</h4>
                <p class="text-muted">Tentor Profesional</p>
                <hr>
                <p><i class="fa fa-star text-warning"></i> {{ $tutor->average_rating ?? 'New' }}</p>
                <a href="#" class="btn btn-info btn-sm">Lihat Profil</a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-lg-12 text-center">
        <p class="text-muted">Belum ada tentor yang tersedia saat ini.</p>
    </div>
    @endforelse
</div>

<!-- CTA -->
<div class="row" style="margin-top: 50px;">
    <div class="col-lg-6 text-center">
        <div class="panel panel-primary">
            <div class="panel-heading">Untuk Siswa</div>
            <div class="panel-body">
                <h3>Ingin Belajar Lebih Efektif?</h3>
                <p>Temukan tentor yang cocok dengan gaya belajarmu.</p>
                <a href="/register" class="btn btn-primary">Daftar sebagai Siswa</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6 text-center">
        <div class="panel panel-success">
            <div class="panel-heading">Untuk Tentor</div>
            <div class="panel-body">
                <h3>Punya Keahlian Mengajar?</h3>
                <p>Bergabunglah dan dapatkan penghasilan tambahan.</p>
                <a href="/register" class="btn btn-success">Gabung sebagai Tentor</a>
            </div>
        </div>
    </div>
</div>
@endsection
