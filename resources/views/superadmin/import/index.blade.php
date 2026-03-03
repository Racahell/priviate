@extends('layouts.master')

@section('title', 'Import User & Barang')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@php($prefix = $routePrefix ?? 'superadmin')

<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">Import Users (Admin/Superadmin)</div>
            <div class="panel-body">
                <form action="{{ route($prefix . '.import.users') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control" required>
                    <br>
                    <button class="btn btn-primary" type="submit">Import Users</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">Import Barang</div>
            <div class="panel-body">
                <form action="{{ route($prefix . '.import.items') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control" required>
                    <br>
                    <button class="btn btn-success" type="submit">Import Barang</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">Riwayat Import</div>
    <div class="panel-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Sukses</th>
                    <th>Gagal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td>{{ $job->id }}</td>
                        <td>{{ $job->type }}</td>
                        <td>{{ $job->status }}</td>
                        <td>{{ $job->total_rows }}</td>
                        <td>{{ $job->success_rows }}</td>
                        <td>{{ $job->failed_rows }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada proses import.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $jobs->links() }}
    </div>
</div>
@endsection
