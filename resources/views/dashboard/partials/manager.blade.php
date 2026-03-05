<div class="card">
    <h3 class="card-title">Ringkasan Manager</h3>
    <div class="grid grid-2">
        <div class="stat-item"><p class="stat-label">Open Kritik</p><p class="stat-value">{{ $summary['open_disputes'] ?? 0 }}</p></div>
        <div class="stat-item"><p class="stat-label">Pending Reschedule</p><p class="stat-value">{{ $summary['pending_reschedule'] ?? 0 }}</p></div>
    </div>
    <div class="split-actions section">
        <a href="{{ route('manager.disputes') }}" class="btn btn-outline">Kelola Kritik</a>
        <a href="{{ route('manager.monitor') }}" class="btn btn-outline">Monitor Sesi</a>
    </div>
</div>
