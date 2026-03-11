<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body class="error-page">
    @php
        $backUrl = route('home');
        if (auth()->check()) {
            $role = (string) (auth()->user()->getRoleNames()->first() ?? '');
            $routeByRole = [
                'orang_tua' => 'parent.dashboard',
                'siswa' => 'student.dashboard',
                'tentor' => 'tutor.dashboard',
                'admin' => 'admin.dashboard',
                'owner' => 'owner.dashboard',
                'superadmin' => 'superadmin.dashboard',
            ];
            $targetRoute = $routeByRole[$role] ?? 'dashboard';
            if (\Illuminate\Support\Facades\Route::has($targetRoute)) {
                $backUrl = route($targetRoute);
            } elseif (\Illuminate\Support\Facades\Route::has('dashboard')) {
                $backUrl = route('dashboard');
            }
        }
    @endphp
    <div class="error-wrap">
        <div class="error-card">
            <h1>404</h1>
            <h2>Halaman Tidak Ditemukan</h2>
            <p>URL yang Anda akses tidak tersedia atau sudah dipindahkan.</p>
            <a href="{{ $backUrl }}" class="btn btn-primary">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
