@extends('layouts.master')

@section('title', 'Dashboard Siswa')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">Upcoming Session</div>
            <div class="panel-body">
                @if($upcomingSession)
                    <p>{{ $upcomingSession->scheduled_at }}</p>
                    <p>Status: {{ $upcomingSession->status }}</p>
                @else
                    <p>Tidak ada sesi terjadwal.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-info">
            <div class="panel-heading">Invoice Belum Lunas</div>
            <div class="panel-body"><h3>{{ $unpaidInvoicesCount }}</h3></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-success">
            <div class="panel-heading">Saldo Wallet</div>
            <div class="panel-body"><h3>Rp {{ number_format($walletBalance, 0, ',', '.') }}</h3></div>
        </div>
    </div>
</div>
@endsection
