@extends('layouts.master')

@section('title', 'Dashboard Guru')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-warning">
            <div class="panel-heading">Honor Tertahan (Escrow)</div>
            <div class="panel-body"><h3>Rp {{ number_format($heldBalance, 0, ',', '.') }}</h3></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-success">
            <div class="panel-heading">Honor Tersedia</div>
            <div class="panel-body"><h3>Rp {{ number_format($releasedBalance, 0, ',', '.') }}</h3></div>
        </div>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Jadwal Hari Ini</div>
    <div class="panel-body">
        <ul>
            @forelse($todaySessions as $session)
                <li>{{ $session->scheduled_at }} - {{ $session->status }}</li>
            @empty
                <li>Tidak ada jadwal hari ini.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
