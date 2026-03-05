<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Unauthorized</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body class="error-page">
    <div class="error-wrap">
        <div class="error-card">
            <h1>403</h1>
            <h2>Unauthorized</h2>
            <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
