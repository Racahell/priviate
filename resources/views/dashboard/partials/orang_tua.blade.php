<div class="card">
    <h3 class="card-title">Ringkasan Orang Tua</h3>
    <div class="grid grid-3">
        <div class="stat-item"><p class="stat-label">Anak Terhubung</p><p class="stat-value">{{ $summary['children_count'] ?? 0 }}</p></div>
        <div class="stat-item"><p class="stat-label">Sesi Selesai</p><p class="stat-value">{{ $summary['completed_sessions'] ?? 0 }}</p></div>
        <div class="stat-item"><p class="stat-label">Invoice Belum Bayar</p><p class="stat-value">{{ $summary['unpaid_invoices'] ?? 0 }}</p></div>
    </div>
    <div class="section">
        <a href="{{ route('parent.children') }}" class="btn btn-primary">Masukkan Kode Anak</a>
    </div>
</div>
