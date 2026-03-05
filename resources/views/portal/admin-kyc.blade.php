@extends('layouts.master')

@section('title', 'KYC Tentor')

@section('content')
<div class="card">
    <h3 class="card-title">Verifikasi Tentor Pending</h3>
    <p class="card-meta">Daftar tentor yang menunggu validasi administrasi.</p>

    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tentors as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td>{{ $t->name }}</td>
                        <td>{{ $t->email }}</td>
                        <td>{{ $t->phone ?: '-' }}</td>
                        <td><span class="badge {{ $t->is_active ? 'badge-success' : 'badge-warning' }}">{{ $t->is_active ? 'Aktif' : 'Pending' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada data pending KYC.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $tentors->links() }}
</div>
@endsection
