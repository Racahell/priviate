<div class="card">
    <h3 class="card-title">Ringkasan Superadmin</h3>
    <div class="grid grid-2">
        <div class="stat-item"><p class="stat-label">User Aktif</p><p class="stat-value">{{ $summary['active_users'] ?? 0 }}</p></div>
        <div class="stat-item"><p class="stat-label">Open Kritik</p><p class="stat-value">{{ $summary['open_disputes'] ?? 0 }}</p></div>
    </div>
    <div class="split-actions section">
        <a class="btn btn-outline" href="{{ route('superadmin.settings') }}">Setting Web</a>
        <a class="btn btn-outline" href="{{ route('superadmin.menu.access') }}">Hak Akses</a>
        <a class="btn btn-outline" href="{{ route('superadmin.backup.center') }}">Backup Center</a>
    </div>
</div>
