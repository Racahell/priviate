<div class="card">
    <h3 class="card-title">Ringkasan Tentor</h3>
    <div class="grid grid-2">
        <div class="stat-item">
            <p class="stat-label">Sesi Hari Ini</p>
            <p class="stat-value">{{ $summary['today_sessions'] ?? 0 }}</p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Open Kritik</p>
            <p class="stat-value">{{ $summary['pending_disputes'] ?? 0 }}</p>
        </div>
    </div>
    <div class="split-actions section">
        <a href="{{ route('tutor.schedule') }}" class="btn btn-outline">Lihat Jadwal</a>
        <a href="{{ route('tutor.wallet') }}" class="btn btn-outline">Buka Wallet</a>
    </div>
</div>
