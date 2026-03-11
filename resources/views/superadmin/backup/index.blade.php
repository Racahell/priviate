@extends('layouts.master')

@section('title', 'Backup & Restore')

@section('content')
@php($prefix = $routePrefix ?? 'superadmin')

<div class="card">
    <h3 class="card-title">Buat Backup SQL</h3>
    <form method="POST" action="{{ route($prefix . '.backup.create') }}" class="form-inline section">
        @csrf
        <select name="type" class="form-control">
            <option value="db">SQL Database</option>
        </select>
        <select name="mode" class="form-control">
            <option value="update">Update Mode</option>
            <option value="full">Full Mode</option>
        </select>
        <input type="text" name="note" class="form-control" placeholder="Catatan backup">
        <button class="btn btn-primary" type="submit">Buat Backup SQL</button>
    </form>
    <p class="card-meta">Backup akan membuat file `.sql` yang berisi struktur dan isi semua tabel database.</p>
</div>

<div class="card section">
    <h3 class="card-title">Restore File SQL</h3>
    <form method="POST" action="{{ route($prefix . '.backup.upload.restore') }}" enctype="multipart/form-data" class="section">
        @csrf
        <div class="grid grid-2">
            <div class="form-group">
                <label>File SQL</label>
                <input type="file" name="sql_file" class="form-control" accept=".sql,.txt" required>
            </div>
            <div class="form-group">
                <label>Alasan Restore</label>
                <input type="text" name="reason" class="form-control" placeholder="Contoh: restore dari backup produksi" required>
            </div>
        </div>
        <div class="form-group checkbox">
            <label><input type="checkbox" name="wipe_first" value="1" checked> Kosongkan data database dulu sebelum restore</label>
        </div>
        <button class="btn btn-success" type="submit">Upload dan Restore SQL</button>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Kosongkan Data Database</h3>
    <p class="card-meta">Aksi ini akan menghapus data aplikasi agar database siap diisi ulang dari file backup SQL.</p>
    <form method="POST" action="{{ route($prefix . '.backup.wipe') }}" class="section">
        @csrf
        <div class="grid grid-2">
            <div class="form-group">
                <label>Alasan</label>
                <input type="text" name="reason" class="form-control" placeholder="Contoh: reset data sebelum restore" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi</label>
                <input type="text" name="confirm_phrase" class="form-control" value="DELETE_DATABASE_DATA" required>
            </div>
        </div>
        <button class="btn btn-danger" type="submit">Hapus Semua Data Database</button>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Daftar Backup</h3>
    <p class="card-meta">Unduh file SQL, restore dari backup tersimpan, atau lihat metadata backup terbaru.</p>
    @include('components.pagination-controls', ['paginator' => $backups, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama File</th>
                    <th>Tipe</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th>Ukuran</th>
                    <th>Dibuat</th>
                    <th>Catatan</th>
                    <th>Checksum</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $index => $backup)
                    <tr>
                        <td>{{ ($backups->currentPage() - 1) * $backups->perPage() + $index + 1 }}</td>
                        <td>{{ basename((string) $backup->file_path) }}</td>
                        <td>{{ $backup->type }}</td>
                        <td>{{ $backup->mode }}</td>
                        <td><span class="badge {{ strtoupper((string) $backup->status) === 'CREATED' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper((string) $backup->status) }}</span></td>
                        <td>{{ number_format((float) ($backup->file_size ?? 0) / 1024, 1) }} KB</td>
                        <td>{{ optional($backup->created_at)->format('d M Y H:i') ?: '-' }}</td>
                        <td>{{ $backup->note ?: '-' }}</td>
                        <td><small>{{ \Illuminate\Support\Str::limit((string) $backup->checksum_hash, 18, '...') }}</small></td>
                        <td>
                            @if($prefix === 'superadmin')
                                <div class="action-stack">
                                    <a href="{{ route($prefix . '.backup.download', $backup->id) }}" class="btn btn-outline btn-xs">Download SQL</a>
                                    <form method="POST" action="{{ route($prefix . '.backup.partial.restore', $backup->id) }}">@csrf <input type="hidden" name="reason" value="merge restore from stored sql"><button class="btn btn-success btn-xs" type="submit">Merge Restore</button></form>
                                    <form method="POST" action="{{ route($prefix . '.backup.disaster.restore', $backup->id) }}">@csrf <input type="hidden" name="reason" value="full restore from stored sql"><input type="hidden" name="confirm_phrase" value="DISASTER_RESTORE_APPROVED"><button class="btn btn-danger btn-xs" type="submit">Full Restore</button></form>
                                </div>
                            @else
                                <span class="badge badge-warning">Backup Only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">Belum ada backup.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    @include('components.pagination-controls', ['paginator' => $backups, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection
