@extends('layouts.master')

@section('title', 'Whitelabel Settings')

@section('content')
<div class="grid grid-2">
    @foreach($tenants as $tenant)
    <div class="card">
        <h3 class="card-title">{{ $tenant->domain }}</h3>
        <p class="card-meta">Konfigurasi branding per instance</p>
        <form action="{{ route('superadmin.whitelabel.update', $tenant->id) }}" method="POST" class="section">
            @csrf
            @method('PUT')
            <div class="form-group"><label>Instance Name</label><input type="text" name="name" class="form-control" value="{{ $tenant->name }}"></div>
            <div class="form-group"><label>Primary Color</label><input type="color" name="primary_color" class="form-control" value="{{ $tenant->primary_color }}" style="height:44px;"></div>
            <div class="form-group"><label>Logo URL</label><input type="text" name="logo_url" class="form-control" value="{{ $tenant->logo_url }}"></div>
            <div class="form-group"><label>Footer Content</label><textarea name="footer_content" class="form-control" rows="3">{{ $tenant->footer_content }}</textarea></div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
    @endforeach
</div>
@endsection
