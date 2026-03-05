<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Terjadi Kesalahan</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body class="error-page">
    <div class="error-wrap">
        <div class="error-card">
            <h1>500</h1>
            <h2>Terjadi Kesalahan Sistem</h2>
            <p>Silakan coba lagi beberapa saat, atau hubungi admin jika masalah berlanjut.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
