@extends('layouts.master')

@section('title', 'Dashboard Manager')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-warning">
            <div class="panel-heading">Open Disputes</div>
            <div class="panel-body"><h3>{{ $openDisputes }}</h3></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-info">
            <div class="panel-heading">Pending Reschedule</div>
            <div class="panel-body"><h3>{{ $pendingReschedule }}</h3></div>
        </div>
    </div>
</div>
@endsection
