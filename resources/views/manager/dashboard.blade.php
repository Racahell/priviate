@extends('layouts.master')

@section('title', 'Dashboard Manager')

@section('content')
<div class="grid grid-2">
    <div class="card">
        <h3 class="card-title">Open Disputes</h3>
        <p class="stat-value">{{ $openDisputes }}</p>
        <p class="card-meta">Perlu ditinjau tim operasional</p>
    </div>
    <div class="card">
        <h3 class="card-title">Pending Reschedule</h3>
        <p class="stat-value">{{ $pendingReschedule }}</p>
        <p class="card-meta">Menunggu approval</p>
    </div>
</div>
@endsection
