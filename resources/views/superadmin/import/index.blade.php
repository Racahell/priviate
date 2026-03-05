@extends('layouts.master')

@section('title', 'Import User & Barang')

@section('content')
@php($prefix = $routePrefix ?? 'superadmin')

<div class="grid grid-2">
    <div class="card">
        <h3 class="card-title">Import Users</h3>
        <p class="card-meta">Upload CSV untuk data user admin/superadmin.</p>
        <form action="{{ route($prefix . '.import.users') }}" method="POST" enctype="multipart/form-data" class="section">
            @csrf
            <input type="file" name="file" class="form-control" required>
            <div class="section"><button class="btn btn-primary" type="submit">Import Users</button></div>
        </form>
    </div>
    <div class="card">
        <h3 class="card-title">Import Barang</h3>
        <p class="card-meta">Upload CSV untuk data item/barang.</p>
        <form action="{{ route($prefix . '.import.items') }}" method="POST" enctype="multipart/form-data" class="section">
            @csrf
            <input type="file" name="file" class="form-control" required>
            <div class="section"><button class="btn btn-success" type="submit">Import Barang</button></div>
        </form>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Riwayat Import</h3>
    <div class="table-wrap">
        <table>
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
                        <td><span class="badge {{ in_array(strtolower((string) $job->status), ['success', 'completed', 'done'], true) ? 'badge-success' : 'badge-warning' }}">{{ $job->status }}</span></td>
                        <td>{{ $job->total_rows }}</td>
                        <td>{{ $job->success_rows }}</td>
                        <td>{{ $job->failed_rows }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada proses import.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $jobs->links() }}
</div>
@endsection
