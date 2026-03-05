@extends('layouts.master')

@section('title', 'Setting Web')

@section('content')
<div class="card">
    <h3 class="card-title">Konfigurasi Item Web</h3>
    <form method="POST" action="{{ route('superadmin.settings.update') }}" enctype="multipart/form-data" class="section">
        @csrf
        <div class="grid grid-2">
            <div>
                <div class="form-group"><label>Nama Web</label><input type="text" class="form-control" name="site_name" value="{{ old('site_name', $setting->site_name ?? '') }}" required></div>
                <div class="form-group"><label>Upload Logo</label><input type="file" class="form-control" name="logo_file" accept=".jpg,.jpeg,.png,.webp"></div>
                @if(!empty($setting->logo_url))
                    <div class="profile-avatar-preview"><img src="{{ asset($setting->logo_url) }}" alt="Logo"></div>
                @endif
                <div class="form-group"><label>Alamat</label><textarea class="form-control" name="address">{{ old('address', $setting->address ?? '') }}</textarea></div>
            </div>
            <div>
                <div class="form-group"><label>Nama Manager</label><input type="text" class="form-control" name="manager_name" value="{{ old('manager_name', $setting->manager_name ?? '') }}"></div>
                <div class="form-group"><label>Kontak Email</label><input type="email" class="form-control" name="contact_email" value="{{ old('contact_email', $setting->contact_email ?? '') }}"></div>
                <div class="form-group"><label>Kontak HP</label><input type="text" class="form-control" name="contact_phone" value="{{ old('contact_phone', $setting->contact_phone ?? '') }}"></div>
                <div class="form-group"><label>Footer Web</label><textarea class="form-control" name="footer_content">{{ old('footer_content', data_get($setting, 'extra.footer_content')) }}</textarea></div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Setting</button>
    </form>
</div>
@endsection
