<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body class="error-page">
    <div class="error-wrap">
        <div class="error-card">
            <h1>404</h1>
            <h2>Halaman Tidak Ditemukan</h2>
            <p>URL yang Anda akses tidak tersedia atau sudah dipindahkan.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
