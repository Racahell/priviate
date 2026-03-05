@extends('layouts.master')

@section('title', 'Audit Logs')

@section('content')
<div class="card">
    <h3 class="card-title">Audit Logs (Full Read)</h3>
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Aksi</th>
                    <th>User ID</th>
                    <th>Role</th>
                    <th>IP</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->user_id ?: '-' }}</td>
                        <td>{{ strtoupper($log->role ?? '-') }}</td>
                        <td>{{ $log->ip_address ?: '-' }}</td>
                        <td>{{ optional($log->created_at)->format('d M Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada log.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
