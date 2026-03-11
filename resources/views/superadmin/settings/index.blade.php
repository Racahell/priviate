@extends('layouts.master')

@section('title', 'Setting Web')

@section('content')
<div class="card">
    <h3 class="card-title">Konfigurasi Item Web</h3>
    <form method="POST" action="{{ route(request()->routeIs('admin.*') ? 'admin.settings.update' : 'superadmin.settings.update') }}" enctype="multipart/form-data" class="section">
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
                <div class="form-group"><label>Kontak Email</label><input type="email" class="form-control" name="contact_email" value="{{ old('contact_email', $setting->contact_email ?? '') }}"></div>
                <div class="form-group"><label>Kontak HP</label><input type="text" class="form-control" name="contact_phone" value="{{ old('contact_phone', $setting->contact_phone ?? '') }}"></div>            </div>
        </div>

        <hr>
        <h4 class="card-title">Footer Terstruktur</h4>
        <div class="booking-tabs" id="footer-structured-tabs">
            <button type="button" class="booking-tab is-active" data-tab="identity">Identitas</button>
            <button type="button" class="booking-tab" data-tab="navigation">Navigasi</button>
            <button type="button" class="booking-tab" data-tab="legal">Legal</button>
            <button type="button" class="booking-tab" data-tab="contact">Kontak</button>
            <button type="button" class="booking-tab" data-tab="social">Sosial</button>
        </div>

        <div class="section">
            <div class="footer-tab-pane" data-tab-pane="identity">
                <div class="grid grid-2">
                    <div class="form-group"><label>Brand</label><input type="text" class="form-control" name="footer_brand" value="{{ old('footer_brand', data_get($setting, 'extra.footer_config.brand', 'Laravel')) }}"></div>
                    <div class="form-group"><label>Version</label><input type="text" class="form-control" name="footer_version" value="{{ old('footer_version', data_get($setting, 'extra.footer_config.version', 'Version 2.3.1')) }}"></div>
                </div>
                <div class="form-group"><label>Copyright</label><input type="text" class="form-control" name="footer_copyright_text" value="{{ old('footer_copyright_text', data_get($setting, 'extra.footer_config.copyright_text', '© 2026 Laravel. All rights reserved.')) }}"></div>
            </div>

            <div class="footer-tab-pane" data-tab-pane="navigation" style="display:none;">
                <div class="form-group"><label>Navigasi (1 baris 1 item)</label><textarea class="form-control" rows="5" name="footer_navigation">{{ old('footer_navigation', data_get($setting, 'extra.footer_config.navigation', "Tentang Kami\nKontak\nBlog\nFAQ\nHelp Center")) }}</textarea></div>
            </div>

            <div class="footer-tab-pane" data-tab-pane="legal" style="display:none;">
                <div class="form-group"><label>Legal (1 baris 1 item)</label><textarea class="form-control" rows="4" name="footer_legal">{{ old('footer_legal', data_get($setting, 'extra.footer_config.legal', "Privacy Policy\nTerms of Service\nCookie Policy")) }}</textarea></div>
            </div>

            <div class="footer-tab-pane" data-tab-pane="contact" style="display:none;">
                <div class="grid grid-2">
                    <div class="form-group"><label>Kontak Email</label><input type="text" class="form-control" name="footer_contact_email" value="{{ old('footer_contact_email', data_get($setting, 'extra.footer_config.contact_email', 'support@privtuition.app')) }}"></div>
                    <div class="form-group"><label>Kontak Telepon</label><input type="text" class="form-control" name="footer_contact_phone" value="{{ old('footer_contact_phone', data_get($setting, 'extra.footer_config.contact_phone', '+62 21 5550 2026')) }}"></div>
                </div>
                <div class="form-group"><label>Kontak Alamat</label><input type="text" class="form-control" name="footer_contact_address" value="{{ old('footer_contact_address', data_get($setting, 'extra.footer_config.contact_address', 'Jakarta, Indonesia')) }}"></div>
            </div>

            <div class="footer-tab-pane" data-tab-pane="social" style="display:none;">
                <div class="form-group"><label>Social (1 baris 1 item)</label><textarea class="form-control" rows="5" name="footer_social">{{ old('footer_social', data_get($setting, 'extra.footer_config.social', "Instagram\nFacebook\nLinkedIn\nTwitter/X")) }}</textarea></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Setting</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var wrap = document.getElementById('footer-structured-tabs');
    if (!wrap) return;

    var tabs = wrap.querySelectorAll('.booking-tab');
    var panes = document.querySelectorAll('.footer-tab-pane');

    function activate(tabName) {
        tabs.forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-tab') === tabName);
        });
        panes.forEach(function (pane) {
            pane.style.display = pane.getAttribute('data-tab-pane') === tabName ? '' : 'none';
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activate(tab.getAttribute('data-tab'));
        });
    });
})();
</script>
@endpush


