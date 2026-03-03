@extends('layouts.master')

@section('title', 'Hak Akses Menu')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading">Checklist Hak Akses Menu</div>
    <div class="panel-body table-responsive">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <form method="POST" action="{{ route('superadmin.menu.access.update') }}">
            @csrf
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Menu</th>
                        @foreach($roles as $role)
                            <th>{{ strtoupper($role) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($menuItems as $menu)
                        <tr>
                            <td>{{ $menu->label }}<br><small>{{ $menu->route_name }}</small></td>
                            @foreach($roles as $role)
                                @php
                                    $key = $menu->id . ':' . $role;
                                    $perm = ($permissions[$key][0] ?? null);
                                @endphp
                                <td>
                                    <label><input type="checkbox" name="permissions[{{ $menu->id }}][{{ $role }}][can_view]" {{ $perm && $perm->can_view ? 'checked' : '' }}> View</label><br>
                                    <label><input type="checkbox" name="permissions[{{ $menu->id }}][{{ $role }}][can_create]" {{ $perm && $perm->can_create ? 'checked' : '' }}> Create</label><br>
                                    <label><input type="checkbox" name="permissions[{{ $menu->id }}][{{ $role }}][can_update]" {{ $perm && $perm->can_update ? 'checked' : '' }}> Update</label><br>
                                    <label><input type="checkbox" name="permissions[{{ $menu->id }}][{{ $role }}][can_delete]" {{ $perm && $perm->can_delete ? 'checked' : '' }}> Delete</label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button class="btn btn-primary" type="submit">Simpan Hak Akses</button>
        </form>
    </div>
</div>
@endsection
