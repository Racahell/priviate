@php
    $totalSessions = (int) ($summary['total_sessions'] ?? 0);
    $completedSessions = (int) ($summary['completed_sessions'] ?? 0);
    $weeklySessions = (int) ($summary['weekly_sessions'] ?? 0);
    $progressPct = $totalSessions > 0 ? min(100, (int) round(($completedSessions / $totalSessions) * 100)) : 0;
    $weeklyChart = $summary['weekly_chart'] ?? [];
@endphp

<div class="card student-premium">
    <div class="split-header">
        <h3 class="card-title">Ringkasan Belajar Siswa</h3>
        <span class="badge badge-success">{{ $progressPct }}% target tercapai</span>
    </div>

    <div class="grid grid-3">
        <div class="stat-item">
            <p class="stat-label">Sesi Selesai</p>
            <p class="stat-value">{{ $completedSessions }}</p>
            <p class="card-meta">Dari total {{ $totalSessions }} sesi</p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Sesi Minggu Ini</p>
            <p class="stat-value">{{ $weeklySessions }}</p>
            <p class="card-meta">Target belajar rutin mingguan</p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Invoice Belum Lunas</p>
            <p class="stat-value">{{ $summary['unpaid_invoices'] ?? 0 }}</p>
            <p class="card-meta">Perlu penyelesaian pembayaran</p>
        </div>
    </div>

    <div class="student-progress">
        <div class="student-progress-head">
            <span>Progress Belajar</span>
            <span>{{ $progressPct }}%</span>
        </div>
        <div class="student-progress-track">
            <div class="student-progress-fill" style="width: {{ $progressPct }}%;"></div>
        </div>
    </div>

    @if(!empty($weeklyChart))
        <div class="student-weekly-chart">
            <div class="student-progress-head">
                <span>Aktivitas Mingguan</span>
                <span>{{ array_sum(array_column($weeklyChart, 'count')) }} sesi</span>
            </div>
            <div class="spark-bars">
                @foreach($weeklyChart as $day)
                    <div class="spark-bar-item">
                        <div class="spark-bar" style="height: {{ $day['height'] }}%;"></div>
                        <small>{{ $day['day'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="student-upcoming">
        <h4>Upcoming Session</h4>
        @if(!empty($summary['upcoming']))
            <p class="student-upcoming-main">{{ \Carbon\Carbon::parse($summary['upcoming']->scheduled_at)->translatedFormat('d M Y, H:i') }}</p>
            <p class="card-meta">Status: {{ strtoupper((string) $summary['upcoming']->status) }}</p>
        @else
            <p class="card-meta">Belum ada sesi terjadwal. Yuk booking paket belajar baru.</p>
        @endif
    </div>

    <div class="split-actions">
        <a href="{{ route('student.booking') }}" class="btn btn-primary">Booking Paket</a>
        <a href="{{ route('student.invoices') }}" class="btn btn-outline">Lihat Invoice</a>
        <a href="{{ route('profile.edit') }}" class="btn btn-outline">Perbarui Profil</a>
    </div>
</div>
