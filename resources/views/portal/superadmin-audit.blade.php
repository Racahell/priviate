@extends('layouts.master')

@section('title', 'Audit Logs')

@section('content')
<div class="card">
    <h3 class="card-title">Audit Logs (Full Read)</h3>
    @include('components.pagination-controls', ['paginator' => $logs, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Aksi</th>
                    <th>User ID</th>
                    <th>Role</th>
                    <th>IP</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                    <tr>
                        <td>{{ ($logs->currentPage() - 1) * $logs->perPage() + $index + 1 }}</td>
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
</div>

    @include('components.pagination-controls', ['paginator' => $logs, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection
