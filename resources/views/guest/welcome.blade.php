<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BimbinganKu | Les Privat</title>
    <link rel="stylesheet" href="{{ asset('landing.css') }}">
</head>
<body>
    <header class="site-header">
        <nav class="navbar container">
            <a href="{{ route('home') }}" class="logo">BimbinganKu</a>
            <ul class="nav-links">
                <li><a href="#layanan">Layanan</a></li>
                <li><a href="#keunggulan">Keunggulan</a></li>
                <li><a href="#kontak">Kontak</a></li>
            </ul>
            <a href="{{ route('register.preverify') }}" class="btn btn-nav">Daftar</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div>
                    <p class="tagline">Platform les privat terpercaya</p>
                    <h1>Belajar Lebih Mudah dan Nyaman dari Rumah</h1>
                    <p class="hero-desc">
                        Pilih guru terbaik, atur jadwal fleksibel, dan pantau progres belajar dalam satu aplikasi.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('register.preverify') }}" class="btn btn-primary">Daftar Sekarang</a>
                        <a href="{{ route('login') }}" class="btn btn-secondary">Masuk</a>
                    </div>
                </div>
                <div class="hero-panel">
                    <p class="hero-panel-title">Kenapa pilih kami?</p>
                    <p>Kelas privat terjadwal rapi, laporan belajar jelas, dan komunikasi orang tua lebih terkontrol.</p>
                </div>
            </div>
        </section>

        <section id="layanan" class="section container">
            <h2>Layanan</h2>
            <div class="card-grid">
                <article class="card">
                    <h3>Matematika</h3>
                    <p>Pendampingan konsep dasar sampai latihan soal intensif sesuai jenjang.</p>
                </article>
                <article class="card">
                    <h3>IPA</h3>
                    <p>Pembelajaran terstruktur untuk Fisika, Kimia, dan Biologi dengan pendekatan praktis.</p>
                </article>
                <article class="card">
                    <h3>Bahasa Inggris</h3>
                    <p>Program speaking, grammar, reading, dan persiapan ujian sekolah.</p>
                </article>
            </div>
        </section>

        <section id="keunggulan" class="section section-accent">
            <div class="container">
                <h2>Keunggulan</h2>
                <div class="highlight-panel">
                    <p>Panel Keunggulan</p>
                    <h3>Belajar terarah, aman, dan terpantau</h3>
                </div>
                <div class="feature-grid">
                    <article class="feature">
                        <span class="emoji">🏠</span>
                        <h3>Belajar di Rumah</h3>
                        <p>Proses belajar lebih fokus tanpa perlu perjalanan jauh.</p>
                    </article>
                    <article class="feature">
                        <span class="emoji">🧭</span>
                        <h3>Kurikulum Personal</h3>
                        <p>Materi disesuaikan dengan kebutuhan dan target siswa.</p>
                    </article>
                    <article class="feature">
                        <span class="emoji">🗓️</span>
                        <h3>Jadwal Fleksibel</h3>
                        <p>Pilih waktu belajar yang cocok untuk siswa dan orang tua.</p>
                    </article>
                    <article class="feature">
                        <span class="emoji">📈</span>
                        <h3>Laporan Kemajuan</h3>
                        <p>Perkembangan belajar dipantau rutin setiap pertemuan.</p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer id="kontak" class="site-footer">
        <div class="container footer-grid">
            <div>
                <h3>BimbinganKu</h3>
                <p>Jl. Pendidikan No. 10, Jakarta</p>
            </div>
            <div>
                <p>Email: info@bimbinganku.id</p>
                <p>WhatsApp: +62 812-0000-0000</p>
                <p>Jam Operasional: 08.00 - 20.00 WIB</p>
            </div>
        </div>
    </footer>
</body>
</html>
