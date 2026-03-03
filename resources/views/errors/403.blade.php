<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Unauthorized</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f7f4ea; color: #2d2d2d; }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { max-width: 560px; width: 100%; background: #fff; border: 1px solid #ded7c6; border-radius: 12px; padding: 24px; text-align: center; }
        h1 { margin: 0; font-size: 54px; color: #c18400; }
        p { color: #555; }
        a { display: inline-block; margin-top: 12px; background: #2f6f57; color: #fff; text-decoration: none; padding: 10px 14px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>403</h1>
            <h2>Unauthorized</h2>
            <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
            <a href="{{ route('home') }}">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
