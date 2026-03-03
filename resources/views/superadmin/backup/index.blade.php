@extends('layouts.master')

@section('title', 'Backup & Restore')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@php($prefix = $routePrefix ?? 'superadmin')

<div class="panel panel-default">
    <div class="panel-heading">Buat Backup</div>
    <div class="panel-body">
        <form method="POST" action="{{ route($prefix . '.backup.create') }}" class="form-inline">
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
</div>

<div class="panel panel-default">
    <div class="panel-heading">Daftar Backup</div>
    <div class="panel-body table-responsive">
        <table class="table table-bordered">
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
                                <form method="POST" action="{{ route($prefix . '.backup.preview', $backup->id) }}" style="display:inline-block;">
                                    @csrf
                                    <button class="btn btn-info btn-xs" type="submit">Preview Partial</button>
                                </form>
                                <form method="POST" action="{{ route($prefix . '.backup.partial.restore', $backup->id) }}" style="display:inline-block;">
                                    @csrf
                                    <input type="hidden" name="reason" value="partial restore apply">
                                    <button class="btn btn-success btn-xs" type="submit">Apply Partial</button>
                                </form>
                                <form method="POST" action="{{ route($prefix . '.backup.disaster.restore', $backup->id) }}" style="display:inline-block;">
                                    @csrf
                                    <input type="hidden" name="reason" value="disaster restore apply">
                                    <input type="hidden" name="confirm_phrase" value="DISASTER_RESTORE_APPROVED">
                                    <button class="btn btn-danger btn-xs" type="submit">Disaster Restore</button>
                                </form>
                            @else
                                <span class="label label-info">Backup Only</span>
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

{{ $backups->links() }}
@endsection
