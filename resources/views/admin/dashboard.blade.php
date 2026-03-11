@extends('layouts.master')

@section('title', 'Dashboard Admin')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">KYC Pending</div>
            <div class="panel-body"><h3>{{ $pendingTentors->count() }}</h3></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">Kritik / Rating Rendah</div>
            <div class="panel-body"><h3>{{ $disputedSessions->count() }}</h3></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">Fraud Alerts</div>
            <div class="panel-body"><h3>{{ $fraudAlerts->count() }}</h3></div>
        </div>
    </div>
</div>
@endsection
