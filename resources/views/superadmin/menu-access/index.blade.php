@extends('layouts.master')

@section('title', 'Hak Akses Menu')

@section('content')
<div class="card">
    <h3 class="card-title">Daftar Role</h3>
    <p class="card-meta">Total menu aktif: <strong>{{ $menuCount }}</strong></p>
    <div class="table-wrap section">
        <table>
            <thead><tr><th>Role</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>{{ strtoupper($role) }}</td>
                        <td><a href="{{ route('superadmin.menu.access.role', $role) }}" class="btn btn-primary btn-sm">Lihat Hak Akses</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
