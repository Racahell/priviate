@extends('layouts.master')

@section('title', 'Dashboard Superadmin')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="panel panel-success">
            <div class="panel-heading">DB Status</div>
            <div class="panel-body"><h3>{{ $dbStatus }}</h3></div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">Recent Audit Logs</div>
            <div class="panel-body">
                <ul>
                    @forelse($recentActivities as $activity)
                        <li>{{ $activity->created_at }} - {{ $activity->action ?? $activity->event }} - User: {{ $activity->user_id ?? 'SYSTEM' }}</li>
                    @empty
                        <li>Belum ada audit log.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
