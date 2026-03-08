@extends('layouts.master')

@section('title', 'Backup & Restore')

@section('content')
@php($prefix = $routePrefix ?? 'superadmin')

<div class="card">
    <h3 class="card-title">Buat Backup</h3>
    <form method="POST" action="{{ route($prefix . '.backup.create') }}" class="form-inline section">
        @csrf
        <select name="type" class="form-control">
            <option value="db">DB Backup</option>
            <option value="files">File Backup</option>
            <option value="config">Config Backup</option>
        </select>
        <select name="mode" class="form-control">
            <option value="update">Update Mode</option>
            <option value="full">Full Mode</option>
        </select>
        <input type="text" name="note" class="form-control" placeholder="Catatan backup">
        <button class="btn btn-primary" type="submit">Create Backup</button>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Daftar Backup</h3>
    @include('components.pagination-controls', ['paginator' => $backups, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipe</th>
                    <th>Mode</th>
                    <th>Path</th>
                    <th>Checksum</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td>{{ $backup->id }}</td>
                        <td>{{ $backup->type }}</td>
                        <td>{{ $backup->mode }}</td>
                        <td>{{ $backup->file_path }}</td>
                        <td><small>{{ $backup->checksum_hash }}</small></td>
                        <td>
                            @if($prefix === 'superadmin')
                                <div class="action-stack">
                                    <form method="POST" action="{{ route($prefix . '.backup.preview', $backup->id) }}">@csrf <button class="btn btn-outline btn-xs" type="submit">Preview Partial</button></form>
                                    <form method="POST" action="{{ route($prefix . '.backup.partial.restore', $backup->id) }}">@csrf <input type="hidden" name="reason" value="partial restore apply"><button class="btn btn-success btn-xs" type="submit">Apply Partial</button></form>
                                    <form method="POST" action="{{ route($prefix . '.backup.disaster.restore', $backup->id) }}">@csrf <input type="hidden" name="reason" value="disaster restore apply"><input type="hidden" name="confirm_phrase" value="DISASTER_RESTORE_APPROVED"><button class="btn btn-danger btn-xs" type="submit">Disaster Restore</button></form>
                                </div>
                            @else
                                <span class="badge badge-warning">Backup Only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada backup.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    @include('components.pagination-controls', ['paginator' => $backups, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection

