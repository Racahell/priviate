<div class="card">
    <h3 class="card-title">Ringkasan Admin</h3>
    <div class="grid grid-2">
        <div class="stat-item">
            <p class="stat-label">KYC Pending</p>
            <p class="stat-value">{{ $summary['pending_tentors'] ?? 0 }}</p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Sesi Dispute / Rating Rendah</p>
            <p class="stat-value">{{ $summary['disputed_sessions'] ?? 0 }}</p>
        </div>
    </div>
    <div class="split-actions section">
        <a href="{{ route('admin.kyc') }}" class="btn btn-outline">Review KYC</a>
        <a href="{{ route('admin.disputes') }}" class="btn btn-outline">Kelola Dispute</a>
        <a href="{{ route('admin.monitor') }}" class="btn btn-outline">Monitor Sesi</a>
    </div>
</div>
