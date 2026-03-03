@extends('layouts.master')

@section('title', 'Dashboard Owner')

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="panel panel-primary">
            <div class="panel-heading">Total Siswa</div>
            <div class="panel-body"><h3>{{ $totalStudents }}</h3></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-primary">
            <div class="panel-heading">Total Guru</div>
            <div class="panel-body"><h3>{{ $totalTentors }}</h3></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-success">
            <div class="panel-heading">Revenue</div>
            <div class="panel-body"><h3>Rp {{ number_format($revenue, 0, ',', '.') }}</h3></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-warning">
            <div class="panel-heading">Escrow Outstanding</div>
            <div class="panel-body"><h3>Rp {{ number_format($deferredRevenue, 0, ',', '.') }}</h3></div>
        </div>
    </div>
</div>
<div class="text-right">
    <a href="{{ route('owner.reports') }}" class="btn btn-primary">Laporan Grafik</a>
</div>
@endsection
