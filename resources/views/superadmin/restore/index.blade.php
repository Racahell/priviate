@extends('layouts.master')

@section('title', 'Restore Data')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@php
    $groups = [
        'user' => $deletedUsers,
        'invoice' => $deletedInvoices,
        'subject' => $deletedSubjects,
        'session' => $deletedSessions,
        'item' => $deletedItems,
    ];
@endphp

@foreach($groups as $type => $rows)
    <div class="panel panel-default">
        <div class="panel-heading">Deleted {{ strtoupper($type) }}</div>
        <div class="panel-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Info</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->name ?? $row->invoice_number ?? ($row->status ?? '-') }}</td>
                            <td>
                                <form method="POST" action="{{ route('superadmin.restore.apply') }}" class="form-inline">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                    <input type="text" name="reason" class="form-control" placeholder="Alasan restore" required>
                                    <button class="btn btn-success" type="submit">Restore</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Tidak ada data terhapus.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach

<div class="panel panel-danger">
    <div class="panel-heading">Hard Delete (Superadmin Only)</div>
    <div class="panel-body">
        <form method="POST" action="{{ route('superadmin.harddelete.request.otp') }}" style="margin-bottom:10px;">
            @csrf
            <button type="submit" class="btn btn-warning">Request OTP Hard Delete</button>
        </form>

        <form method="POST" action="{{ route('superadmin.harddelete.apply') }}" class="form-inline">
            @csrf
            <select name="type" class="form-control" required>
                <option value="user">User</option>
                <option value="invoice">Invoice</option>
                <option value="subject">Subject</option>
                <option value="session">Session</option>
                <option value="item">Item</option>
            </select>
            <input type="number" name="id" class="form-control" placeholder="Record ID" required>
            <input type="text" name="reason" class="form-control" placeholder="Alasan wajib" required>
            <input type="text" name="otp_code" class="form-control" placeholder="OTP 6 digit" maxlength="6" required>
            <button type="submit" class="btn btn-danger">Hard Delete</button>
        </form>
    </div>
</div>
@endsection
