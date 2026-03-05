@extends('layouts.master')

@section('title', 'Hak Akses: '.strtoupper($role))

@section('content')
<div class="card">
    <div class="split-header">
        <h3 class="card-title">Hak Akses Menu Role: {{ strtoupper($role) }}</h3>
        <a href="{{ route('superadmin.menu.access') }}" class="btn btn-outline btn-sm">Kembali</a>
    </div>

    <div class="split-actions section">
        <button type="button" class="btn btn-outline btn-xs" onclick="toggleAll('can_view', true)">Pilih Semua View</button>
        <button type="button" class="btn btn-outline btn-xs" onclick="toggleAll('can_create', true)">Pilih Semua Create</button>
        <button type="button" class="btn btn-outline btn-xs" onclick="toggleAll('can_update', true)">Pilih Semua Update</button>
        <button type="button" class="btn btn-outline btn-xs" onclick="toggleAll('can_delete', true)">Pilih Semua Delete</button>
        <button type="button" class="btn btn-warning btn-xs" onclick="toggleAll('*', false)">Clear Semua</button>
    </div>

    <form method="POST" action="{{ route('superadmin.menu.access.role.update', $role) }}">
        @csrf
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Menu</th><th>View</th><th>Create</th><th>Update</th><th>Delete</th></tr>
                </thead>
                <tbody>
                    @foreach($menuItems as $menu)
                        @php($perm = $permissions[$menu->id] ?? null)
                        <tr>
                            <td>{{ $menu->label }}<br><small>{{ $menu->route_name }}</small></td>
                            <td><input type="checkbox" data-key="can_view" name="permissions[{{ $menu->id }}][can_view]" {{ $perm && $perm->can_view ? 'checked' : '' }}></td>
                            <td><input type="checkbox" data-key="can_create" name="permissions[{{ $menu->id }}][can_create]" {{ $perm && $perm->can_create ? 'checked' : '' }}></td>
                            <td><input type="checkbox" data-key="can_update" name="permissions[{{ $menu->id }}][can_update]" {{ $perm && $perm->can_update ? 'checked' : '' }}></td>
                            <td><input type="checkbox" data-key="can_delete" name="permissions[{{ $menu->id }}][can_delete]" {{ $perm && $perm->can_delete ? 'checked' : '' }}></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="section"><button class="btn btn-primary" type="submit">Simpan Hak Akses</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleAll(key, state) {
    var selector = key === '*' ? 'input[type="checkbox"][data-key]' : 'input[type="checkbox"][data-key="' + key + '"]';
    var nodes = document.querySelectorAll(selector);
    nodes.forEach(function(node) { node.checked = state; });
}
</script>
@endpush
