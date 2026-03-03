@extends('layouts.master')

@section('title', 'Dashboard Orang Tua')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="panel panel-success">
            <div class="panel-heading">Sesi Selesai</div>
            <div class="panel-body"><h3>{{ $completedSessions }}</h3></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-danger">
            <div class="panel-heading">Invoice Belum Bayar</div>
            <div class="panel-body"><h3>{{ $unpaidInvoices }}</h3></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-warning">
            <div class="panel-heading">Reschedule Pending</div>
            <div class="panel-body"><h3>{{ $pendingReschedule }}</h3></div>
        </div>
    </div>
</div>
@endsection
