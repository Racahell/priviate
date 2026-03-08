@extends('layouts.master')

@section('title', 'Beranda')

@section('content')
<section class="home-hero">
    <div class="home-hero-grid">
        <div class="home-hero-copy">
            <p class="home-eyebrow">Platform Les Privat Modern</p>
            <h1>Temukan Tentor Terbaik untuk Target Belajar yang Jelas</h1>
            <p class="home-lead">
                Satu platform untuk booking tentor, atur jadwal, monitor progres, dan kelola laporan belajar siswa secara rapi.
            </p>
            <div class="home-actions">
                <a href="{{ route('register') }}" class="btn btn-primary">Mulai Sekarang</a>
            </div>
        </div>

        <div class="home-hero-visual">
            <div class="home-hero-panel">
                <p class="home-panel-label">Kelas Live Hari Ini</p>
                <h3>{{ number_format((int) $liveSessionCount) }} sesi aktif berjalan</h3>
                <ul>
                    @forelse($liveSessions as $session)
                        <li>
                            <span></span>
                            {{ $session->subject?->name ?? 'Mapel belum diatur' }}
                            @if(!empty($session->subject?->level))
                                {{ ' ' . $session->subject->level }}
                            @endif
                            - {{ optional($session->scheduled_at)->format('H:i') ?? '--:--' }}
                        </li>
                    @empty
                        <li><span></span> Belum ada sesi yang sedang berjalan</li>
                    @endforelse
                </ul>
            </div>
            <div class="home-orb home-orb-1"></div>
            <div class="home-orb home-orb-2"></div>
            <div class="home-orb home-orb-3"></div>
        </div>
    </div>

    <div class="home-stats">
        <div class="home-stat">
            <h3>{{ number_format((int) $totalSessions) }}</h3>
            <p>Sesi Selesai</p>
        </div>
        <div class="home-stat">
            <h3>{{ number_format((int) $totalTentors) }}</h3>
            <p>Tentor Aktif</p>
        </div>
        <div class="home-stat">
            <h3>{{ number_format((float) $avgRating, 2) }}</h3>
            <p>Rata-rata Rating</p>
        </div>
        <div class="home-stat">
            <h3>2019</h3>
            <p>Mulai Operasional</p>
        </div>
    </div>
</section>

<section class="home-section">
    <div class="home-headline">
        <h2>Masalah yang Sering Dihadapi Orang Tua & Siswa</h2>
        <p>Fokus kami adalah menutup gap belajar dengan sistem yang terukur.</p>
    </div>
    <div class="home-card-grid">
        <article class="home-info-card">
            <h3>Jadwal Belajar Tidak Konsisten</h3>
            <p>Siswa sulit menjaga ritme belajar karena jadwal tidak terstruktur.</p>
        </article>
        <article class="home-info-card">
            <h3>Materi Tidak Tepat Sasaran</h3>
            <p>Topik yang dipelajari sering tidak sesuai kebutuhan akademik saat ini.</p>
        </article>
        <article class="home-info-card">
            <h3>Progress Sulit Dipantau</h3>
            <p>Orang tua kesulitan melihat perkembangan belajar secara periodik.</p>
        </article>
    </div>
</section>

<section class="home-section home-section-soft">
    <div class="home-headline">
        <h2>Cara Kami Meningkatkan Hasil Belajar</h2>
        <p>Tiga langkah praktis untuk memastikan belajar lebih efektif.</p>
    </div>
    <div class="home-card-grid">
        <article class="home-info-card">
            <h3>Assessment Awal</h3>
            <p>Kami petakan level siswa dan target belajar sebelum sesi dimulai.</p>
        </article>
        <article class="home-info-card">
            <h3>Rencana Belajar Personal</h3>
            <p>Tentor menyusun strategi belajar mingguan sesuai kebutuhan siswa.</p>
        </article>
        <article class="home-info-card">
            <h3>Evaluasi Berkala</h3>
            <p>Setiap sesi tercatat dan dilaporkan agar progres mudah dipantau.</p>
        </article>
    </div>
</section>

<section class="home-section">
    <div class="home-headline">
        <h2>Tentor Pilihan</h2>
        <p>Profil tentor aktif yang siap mendampingi belajar siswa.</p>
    </div>
    <div class="home-tutor-grid">
        @forelse($tutors as $tutor)
            <article class="home-tutor-card">
                <div class="home-tutor-avatar">{{ strtoupper(substr($tutor->name, 0, 1)) }}</div>
                <div>
                    <h3>{{ $tutor->name }}</h3>
                    <p>{{ $tutor->email }}</p>
                </div>
                <span class="home-status {{ $tutor->is_active ? 'is-active' : 'is-inactive' }}">
                    {{ $tutor->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </article>
        @empty
            <article class="home-info-card">
                <h3>Data tentor belum tersedia</h3>
                <p>Silakan cek kembali setelah data tentor aktif ditambahkan.</p>
            </article>
        @endforelse
    </div>
</section>
@endsection
