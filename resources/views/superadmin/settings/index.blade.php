@extends('layouts.master')

@section('title', 'Setting Web')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading">Konfigurasi Item Web</div>
    <div class="panel-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <form method="POST" action="{{ route('superadmin.settings.update') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Nama Web</label>
                        <input type="text" class="form-control" name="site_name" value="{{ old('site_name', $setting->site_name ?? '') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Logo URL</label>
                        <input type="text" class="form-control" name="logo_url" value="{{ old('logo_url', $setting->logo_url ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea class="form-control" name="address">{{ old('address', $setting->address ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Nama Manager</label>
                        <input type="text" class="form-control" name="manager_name" value="{{ old('manager_name', $setting->manager_name ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Kontak Email</label>
                        <input type="email" class="form-control" name="contact_email" value="{{ old('contact_email', $setting->contact_email ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Kontak HP</label>
                        <input type="text" class="form-control" name="contact_phone" value="{{ old('contact_phone', $setting->contact_phone ?? '') }}">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Setting</button>
        </form>
    </div>
</div>
@endsection
