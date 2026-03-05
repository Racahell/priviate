<div class="card">
    <h3 class="card-title">Ringkasan Manager</h3>
    <div class="grid grid-2">
        <div class="stat-item"><p class="stat-label">Open Disputes</p><p class="stat-value">{{ $summary['open_disputes'] ?? 0 }}</p></div>
        <div class="stat-item"><p class="stat-label">Pending Reschedule</p><p class="stat-value">{{ $summary['pending_reschedule'] ?? 0 }}</p></div>
    </div>
</div>
