<div class="card">
    <h3 class="card-title">Ringkasan Owner</h3>
    <div class="grid grid-2">
        <div class="stat-item"><p class="stat-label">Total Siswa</p><p class="stat-value">{{ $summary['students'] ?? 0 }}</p></div>
        <div class="stat-item"><p class="stat-label">Total Tentor</p><p class="stat-value">{{ $summary['tentors'] ?? 0 }}</p></div>
    </div>
    <div class="split-actions section">
        <a class="btn btn-outline" href="{{ route('owner.financials') }}">Financial Ledger</a>
        <a class="btn btn-outline" href="{{ route('owner.reports') }}">Laporan</a>
    </div>
</div>
