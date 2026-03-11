@extends('layouts.master')

@section('title', 'Dashboard Superadmin')

@section('content')
<div class="grid grid-3">
    <div class="card">
        <h3 class="card-title">DB Status</h3>
        <p class="stat-value">{{ $dbStatus }}</p>
    </div>
    <div class="card" style="grid-column: span 2;">
        <h3 class="card-title">Recent Audit Logs</h3>
        <div class="table-wrap section">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Event</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentActivities as $activity)
                        <tr>
                            <td>{{ $activity->created_at }}</td>
                            <td>{{ $activity->action ?? $activity->event }}</td>
                            <td>{{ $activity->user_id ?? 'SYSTEM' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Belum ada audit log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
