@extends('layouts.master')

@section('title', 'Import Center')

@section('content')
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
