@extends('layouts.master')

@section('title', 'Register')

@section('content')
@php
    $brandLogo = $webLogo
        ?: (file_exists(public_path('img/priviate-logo.png')) ? 'img/priviate-logo.png'
        : (file_exists(public_path('img/priviate-logo.svg')) ? 'img/priviate-logo.svg' : null));
    $subjects = $subjects ?? collect();
    $educationOptions = $educationOptions ?? [];
@endphp
<div class="auth-shell">
    <div class="card auth-card">
        <div class="auth-card-head">
            <div class="auth-brand">
                @if(!empty($brandLogo))
                    <img src="{{ asset($brandLogo) }}" alt="PriviAte" class="auth-logo">
                @else
                    <strong>PriviAte</strong>
                @endif
            </div>
            <h1 class="page-title text-center">Bergabung dengan PrivTuition</h1>
            <p class="page-subtitle text-center">Buat akun untuk mulai perjalanan belajar yang fleksibel.</p>
        </div>

        <div class="auth-card-body">
            <form action="{{ route('register.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="alert alert-info">
                    Setelah submit, link verifikasi email akan dikirim ke email yang Anda isi.
                </div>

                <div class="form-group @error('name') has-error @enderror">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('email') has-error @enderror">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    @error('email') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('phone') has-error @enderror">
                    <label>No HP</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
                    @error('phone') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-3">
                    <div class="form-group @error('province') has-error @enderror">
                        <label>Provinsi</label>
                        <select name="province" id="regProvince" class="form-control" data-old="{{ old('province') }}" required>
                            <option value="">Pilih provinsi</option>
                        </select>
                        @error('province') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group @error('city') has-error @enderror">
                        <label>Kota / Kabupaten</label>
                        <select name="city" id="regCity" class="form-control" data-old="{{ old('city') }}" required>
                            <option value="">Pilih kota/kabupaten</option>
                        </select>
                        @error('city') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group @error('district') has-error @enderror">
                        <label>Kecamatan</label>
                        <select name="district" id="regDistrict" class="form-control" data-old="{{ old('district') }}" required>
                            <option value="">Pilih kecamatan</option>
                        </select>
                        @error('district') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group @error('village') has-error @enderror">
                        <label>Kelurahan / Desa</label>
                        <select name="village" id="regVillage" class="form-control" data-old="{{ old('village') }}" required>
                            <option value="">Pilih kelurahan/desa</option>
                        </select>
                        @error('village') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group @error('postal_code') has-error @enderror">
                        <label>Kode Pos</label>
                        <select name="postal_code" id="regPostalCode" class="form-control" data-old="{{ old('postal_code') }}" required>
                            <option value="">Pilih kode pos</option>
                        </select>
                        @error('postal_code') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-group @error('address') has-error @enderror">
                    <label>Detail Alamat</label>
                    <textarea name="address" id="regAddressDetail" class="form-control" rows="3" placeholder="Jl. Raya Bengkong No. 12, RT 02 RW 05, dekat Indomaret Bengkong" required>{{ old('address') }}</textarea>
                    @error('address') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Pilih Titik Rumah di Peta</label>
                    <div id="registerAddressMap" style="height:320px; border:1px solid #d7deea; border-radius:10px; margin-bottom:8px;"></div>
                    <div class="grid grid-2">
                        <input type="text" id="registerCoordinatePreview" class="form-control" placeholder="Koordinat belum dipilih" readonly>
                        <button type="button" class="btn btn-outline" id="registerUseMyLocation">Gunakan Lokasi Saya</button>
                    </div>
                    <small class="text-muted" id="registerMapHint">Geser pin atau klik peta untuk menentukan lokasi rumah.</small>
                    @error('latitude') <span class="help-block">{{ $message }}</span> @enderror
                    @error('longitude') <span class="help-block">{{ $message }}</span> @enderror
                    @error('location_status') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-2">
                    <div class="form-group @error('password') has-error @enderror">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                        @error('password') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <div class="form-group @error('role') has-error @enderror">
                    <label>Daftar Sebagai</label>
                    <select name="role" id="register-role" class="form-control">
                        <option value="siswa" {{ old('role') == 'siswa' ? 'selected' : '' }}>Siswa (Ingin Belajar)</option>
                        <option value="tentor" {{ old('role') == 'tentor' ? 'selected' : '' }}>Tentor (Ingin Mengajar)</option>
                        <option value="orang_tua" {{ old('role') == 'orang_tua' ? 'selected' : '' }}>Orang Tua</option>
                    </select>
                    @error('role') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <div id="tentor-register-fields" style="display:none;">
                    <div class="alert alert-warning">
                        Data tentor akan masuk status <strong>PENDING_REVIEW</strong> dan diverifikasi admin sebelum aktif mengajar.
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group @error('education') has-error @enderror">
                            <label>Pendidikan Terakhir</label>
                            <select name="education" class="form-control">
                                <option value="">Pilih Pendidikan Terakhir</option>
                                @foreach($educationOptions as $educationOpt)
                                    <option value="{{ $educationOpt }}" {{ old('education') === $educationOpt ? 'selected' : '' }}>{{ $educationOpt }}</option>
                                @endforeach
                            </select>
                            @error('education') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('experience_years') has-error @enderror">
                            <label>Pengalaman Mengajar (tahun)</label>
                            <input type="number" min="0" max="60" name="experience_years" class="form-control" value="{{ old('experience_years') }}">
                            @error('experience_years') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-group @error('teaching_mode') has-error @enderror">
                        <label>Mode Mengajar</label>
                        <select name="teaching_mode" class="form-control">
                            <option value="online" {{ old('teaching_mode') === 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ old('teaching_mode') === 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="hybrid" {{ old('teaching_mode') === 'hybrid' ? 'selected' : '' }}>Keduanya</option>
                        </select>
                        @error('teaching_mode') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group @error('tentor_bio') has-error @enderror">
                        <label>Ringkasan Pengalaman</label>
                        <textarea name="tentor_bio" class="form-control" rows="3">{{ old('tentor_bio') }}</textarea>
                        @error('tentor_bio') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group @error('teaching_subject_ids') has-error @enderror">
                        <label>Mapel yang Ingin Diajarkan</label>
                        <div class="grid grid-2" style="margin-top:8px;">
                            @foreach($subjects as $subject)
                                <label class="checkbox">
                                    <input type="checkbox" name="teaching_subject_ids[]" value="{{ $subject->id }}" {{ in_array((int) $subject->id, collect(old('teaching_subject_ids', []))->map(fn($v) => (int) $v)->all(), true) ? 'checked' : '' }}>
                                    {{ $subject->name }} ({{ ucfirst((string) $subject->level) }}{{ $subject->classLevel?->name ? ' - ' . $subject->classLevel->name : '' }})
                                </label>
                            @endforeach
                        </div>
                        @error('teaching_subject_ids') <span class="help-block">{{ $message }}</span> @enderror
                        @error('teaching_subject_ids.*') <span class="help-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group @error('cv_file') has-error @enderror">
                            <label>Upload CV</label>
                            <input type="file" name="cv_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @error('cv_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('diploma_file') has-error @enderror">
                            <label>Upload Ijazah</label>
                            <input type="file" name="diploma_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @error('diploma_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('certificate_file') has-error @enderror">
                            <label>Upload Sertifikat (opsional)</label>
                            <input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @error('certificate_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('id_card_file') has-error @enderror">
                            <label>Upload KTP</label>
                            <input type="file" name="id_card_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @error('id_card_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('profile_photo_file') has-error @enderror">
                            <label>Foto Profil</label>
                            <input type="file" name="profile_photo_file" class="form-control" accept=".jpg,.jpeg,.png">
                            @error('profile_photo_file') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group @error('intro_video_url') has-error @enderror">
                            <label>Link Video Perkenalan (opsional)</label>
                            <input type="url" name="intro_video_url" class="form-control" value="{{ old('intro_video_url') }}" placeholder="https://...">
                            @error('intro_video_url') <span class="help-block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div id="offline-captcha-group" class="form-group @error('captcha') has-error @enderror">
                    <label>Keamanan: {{ $captchaQuestion }}</label>
                    <input id="offline-captcha-input" type="number" name="captcha" class="form-control" placeholder="Hasil perhitungan...">
                    @error('captcha') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                @if(config('services.recaptcha.enabled') && !empty($recaptchaSiteKey))
                    <div id="online-recaptcha-group" class="form-group">
                        <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                    </div>
                @endif

                <input type="hidden" name="connection_status" id="connection_status" value="online">
                <input type="hidden" name="location_status" id="location_status" value="{{ old('location_status', 'DENIED') }}">
                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

                <div class="checkbox @error('terms') has-error @enderror">
                    <label>
                        <input type="checkbox" name="terms" required> Saya setuju dengan Syarat & Ketentuan dan Kebijakan Privasi.
                    </label>
                    @error('terms') <span class="help-block">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn btn-success btn-block">Daftar Sekarang</button>

                <div class="auth-links text-center">
                    <p>Sudah punya akun? <a href="{{ route('login') }}">Login disini</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

@if(config('services.recaptcha.enabled') && !empty($recaptchaSiteKey))
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var hasRecaptcha = {{ config('services.recaptcha.enabled') && !empty($recaptchaSiteKey) ? 'true' : 'false' }};
    var offlineGroup = document.getElementById('offline-captcha-group');
    var offlineInput = document.getElementById('offline-captcha-input');
    var onlineGroup = document.getElementById('online-recaptcha-group');
    var connectionInput = document.getElementById('connection_status');
    var roleSelect = document.getElementById('register-role');
    var tentorFields = document.getElementById('tentor-register-fields');

    function updateCaptchaMode() {
        var isOnline = navigator.onLine;
        if (connectionInput) {
            connectionInput.value = isOnline ? 'online' : 'offline';
        }

        if (!hasRecaptcha) {
            if (offlineGroup) offlineGroup.style.display = '';
            if (offlineInput) offlineInput.required = true;
            return;
        }

        if (isOnline) {
            if (onlineGroup) onlineGroup.style.display = '';
            if (offlineGroup) offlineGroup.style.display = 'none';
            if (offlineInput) offlineInput.required = false;
        } else {
            if (onlineGroup) onlineGroup.style.display = 'none';
            if (offlineGroup) offlineGroup.style.display = '';
            if (offlineInput) offlineInput.required = true;
        }
    }

    updateCaptchaMode();
    window.addEventListener('online', updateCaptchaMode);
    window.addEventListener('offline', updateCaptchaMode);

    function toggleTentorFields() {
        if (!roleSelect || !tentorFields) return;
        tentorFields.style.display = roleSelect.value === 'tentor' ? '' : 'none';
    }
    if (roleSelect) {
        roleSelect.addEventListener('change', toggleTentorFields);
    }
    toggleTentorFields();

    var provinceSelect = document.getElementById('regProvince');
    var citySelect = document.getElementById('regCity');
    var districtSelect = document.getElementById('regDistrict');
    var villageSelect = document.getElementById('regVillage');
    var postalSelect = document.getElementById('regPostalCode');
    var addressInput = document.getElementById('regAddressDetail');
    var statusInput = document.getElementById('location_status');
    var latitudeInput = document.getElementById('latitude');
    var longitudeInput = document.getElementById('longitude');
    var coordinatePreview = document.getElementById('registerCoordinatePreview');
    var useMyLocationBtn = document.getElementById('registerUseMyLocation');
    var mapHint = document.getElementById('registerMapHint');

    var lookup = {
        provinces: '/location-lookup/provinces',
        regenciesBase: '/location-lookup/regencies',
        districtsBase: '/location-lookup/districts',
        villagesBase: '/location-lookup/villages',
        postalCodes: '/location-lookup/postal-codes',
        debugGeolocation: '/location-lookup/debug-geolocation'
    };

    var csrfTokenInput = document.querySelector('input[name="_token"]');
    var csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

    function sendGeoDebug(stage, extra) {
        if (!csrfToken) return;
        var body = Object.assign({ stage: stage }, extra || {});
        fetch(lookup.debugGeolocation, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(body)
        }).catch(function () {});
    }

    function clearSelect(selectEl, placeholder) {
        if (!selectEl) return;
        selectEl.innerHTML = '';
        var option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        selectEl.appendChild(option);
    }

    function fillSelect(selectEl, rows, valueKey, textKey, oldValue) {
        if (!selectEl) return;
        var sortedRows = (rows || []).slice().sort(function (a, b) {
            return String(a[textKey] || '').localeCompare(String(b[textKey] || ''), 'id', { sensitivity: 'base' });
        });
        sortedRows.forEach(function (row) {
            var option = document.createElement('option');
            option.value = row[valueKey];
            option.textContent = row[textKey];
            option.setAttribute('data-name', row[textKey]);
            if (oldValue && String(oldValue).toLowerCase() === String(row[textKey]).toLowerCase()) {
                option.selected = true;
            }
            selectEl.appendChild(option);
        });
    }

    function selectedName(selectEl) {
        if (!selectEl) return '';
        var option = selectEl.options[selectEl.selectedIndex];
        if (!option) return '';
        return String(option.getAttribute('data-name') || option.textContent || option.value || '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function normalizeAreaName(value) {
        var v = String(value || '').toUpperCase().replace(/\s+/g, ' ').trim();
        v = v.replace(/^(KOTA|KABUPATEN|KAB\.|KECAMATAN|KELURAHAN|DESA)\s+/, '');
        var raw = v.replace(/[^A-Z0-9]/g, '');
        var aliases = {
            'RIAUISLANDS': 'KEPULAUANRIAU',
            'RIAUISLANDPROVINCE': 'KEPULAUANRIAU',
            'JAKARTA': 'DKIJAKARTA',
            'SPECIALCAPITALREGIONOFJAKARTA': 'DKIJAKARTA',
            'YOGYAKARTA': 'DIYOGYAKARTA',
            'SPECIALREGIONOFYOGYAKARTA': 'DIYOGYAKARTA'
        };
        return aliases[raw] || raw;
    }

    function selectOptionByName(selectEl, targetName) {
        if (!selectEl || !targetName) return false;
        var needle = normalizeAreaName(targetName);
        if (!needle) return false;

        for (var i = 0; i < selectEl.options.length; i++) {
            var opt = selectEl.options[i];
            var label = opt.getAttribute('data-name') || opt.textContent || opt.value;
            var hay = normalizeAreaName(label);
            if (hay === needle || hay.indexOf(needle) !== -1 || needle.indexOf(hay) !== -1) {
                selectEl.value = opt.value;
                return true;
            }
        }
        return false;
    }

    function autoFillRegionFromAddress(addr) {
        var provinceName = addr.state || addr.province || '';
        var cityName = addr.city || addr.town || addr.county || addr.municipality || '';
        var districtName = addr.city_district || addr.suburb || addr.district || '';
        var villageName = addr.village || addr.hamlet || addr.neighbourhood || addr.quarter || addr.suburb || '';

        if (!provinceName) return Promise.resolve();

        if (!provinceSelect.value) {
            if (!selectOptionByName(provinceSelect, provinceName)) return Promise.resolve();
        }

        return loadCities(provinceSelect.value)
            .then(function () {
                if (!citySelect.value && cityName) {
                    selectOptionByName(citySelect, cityName);
                }
                if (!citySelect.value) return;
                return loadDistricts(citySelect.value);
            })
            .then(function () {
                if (!districtSelect.value && districtName) {
                    selectOptionByName(districtSelect, districtName);
                }
                if (!districtSelect.value) return;
                return loadVillages(districtSelect.value);
            })
            .then(function () {
                if (!villageSelect.value && villageName) {
                    selectOptionByName(villageSelect, villageName);
                }
                if (villageSelect.value) {
                    return resolvePostalByArea();
                }
            })
            .catch(function () {});
    }

    function ensureRegionOptionsLoaded() {
        if (provinceSelect && provinceSelect.options && provinceSelect.options.length > 1) {
            return Promise.resolve();
        }
        return loadProvinces();
    }

    function setPostalOptions(values, preferred) {
        if (!postalSelect) return;
        var unique = Array.from(new Set((values || []).map(function (v) { return String(v || '').trim(); }).filter(Boolean)))
            .sort(function (a, b) { return a.localeCompare(b, 'id', { numeric: true, sensitivity: 'base' }); });
        var oldValue = String(postalSelect.getAttribute('data-old') || '');
        postalSelect.innerHTML = '';
        var first = document.createElement('option');
        first.value = '';
        first.textContent = 'Pilih kode pos';
        postalSelect.appendChild(first);
        unique.forEach(function (code) {
            var option = document.createElement('option');
            option.value = code;
            option.textContent = code;
            postalSelect.appendChild(option);
        });
        var pick = preferred || oldValue || '';
        if (!pick && unique.length === 1) {
            pick = unique[0];
        }
        if (pick) {
            var found = Array.prototype.find.call(postalSelect.options, function (opt) { return opt.value === pick; });
            if (!found) {
                var injected = document.createElement('option');
                injected.value = pick;
                injected.textContent = pick;
                postalSelect.appendChild(injected);
            }
            postalSelect.value = pick;
            postalSelect.setAttribute('data-old', '');
        }
    }

    function resolvePostalByArea() {
        var village = selectedName(villageSelect);
        var district = selectedName(districtSelect);
        var city = selectedName(citySelect);
        var province = selectedName(provinceSelect);
        if (!province && !city && !district && !village) return Promise.resolve();
        function fetchPostal(p, c, d, v) {
            return fetch(
                lookup.postalCodes
                + '?province=' + encodeURIComponent(p || '')
                + '&city=' + encodeURIComponent(c || '')
                + '&district=' + encodeURIComponent(d || '')
                + '&village=' + encodeURIComponent(v || '')
                + '&province_id=' + encodeURIComponent(provinceSelect ? (provinceSelect.value || '') : '')
                + '&city_id=' + encodeURIComponent(citySelect ? (citySelect.value || '') : '')
                + '&district_id=' + encodeURIComponent(districtSelect ? (districtSelect.value || '') : '')
                + '&village_id=' + encodeURIComponent(villageSelect ? (villageSelect.value || '') : '')
            )
                .then(function (res) {
                    if (!res.ok) throw new Error('postal lookup failed');
                    return res.json();
                })
                .then(function (rows) {
                    return Array.isArray(rows) ? rows : Object.values(rows || {});
                });
        }

        return fetchPostal(province, city, district, village)
            .then(function (rows) {
                if (rows.length > 0) {
                    setPostalOptions(rows);
                    return;
                }
                return fetchPostal(province, city, district, '')
                    .then(function (fallbackRows) {
                        if (fallbackRows.length > 0) {
                            setPostalOptions(fallbackRows);
                            return;
                        }
                        return fetchPostal(province, city, '', '')
                            .then(function (cityRows) {
                                setPostalOptions(cityRows);
                            });
                    });
            })
            .catch(function () {});
    }

    function loadProvinces() {
        clearSelect(provinceSelect, 'Pilih provinsi');
        return fetch(lookup.provinces)
            .then(function (res) { return res.json(); })
            .then(function (rows) {
                fillSelect(provinceSelect, rows, 'id', 'name', provinceSelect ? provinceSelect.getAttribute('data-old') : '');
                if (provinceSelect && provinceSelect.value) {
                    return loadCities(provinceSelect.value);
                }
            })
            .catch(function () {
                if (mapHint) mapHint.textContent = 'Daftar wilayah gagal dimuat. Periksa koneksi internet.';
            });
    }

    function loadCities(provinceId) {
        clearSelect(citySelect, 'Pilih kota/kabupaten');
        clearSelect(districtSelect, 'Pilih kecamatan');
        clearSelect(villageSelect, 'Pilih kelurahan/desa');
        if (!provinceId) return Promise.resolve();

        return fetch(lookup.regenciesBase + '/' + encodeURIComponent(provinceId))
            .then(function (res) { return res.json(); })
            .then(function (rows) {
                fillSelect(citySelect, rows, 'id', 'name', citySelect ? citySelect.getAttribute('data-old') : '');
                if (citySelect && citySelect.value) {
                    return loadDistricts(citySelect.value);
                }
            });
    }

    function loadDistricts(cityId) {
        clearSelect(districtSelect, 'Pilih kecamatan');
        clearSelect(villageSelect, 'Pilih kelurahan/desa');
        if (!cityId) return Promise.resolve();

        return fetch(lookup.districtsBase + '/' + encodeURIComponent(cityId))
            .then(function (res) { return res.json(); })
            .then(function (rows) {
                fillSelect(districtSelect, rows, 'id', 'name', districtSelect ? districtSelect.getAttribute('data-old') : '');
                if (districtSelect && districtSelect.value) {
                    return loadVillages(districtSelect.value);
                }
            });
    }

    function loadVillages(districtId) {
        clearSelect(villageSelect, 'Pilih kelurahan/desa');
        setPostalOptions([]);
        if (!districtId) return Promise.resolve();

        return fetch(lookup.villagesBase + '/' + encodeURIComponent(districtId))
            .then(function (res) { return res.json(); })
            .then(function (rows) {
                fillSelect(villageSelect, rows, 'id', 'name', villageSelect ? villageSelect.getAttribute('data-old') : '');
                if (villageSelect && villageSelect.value) {
                    return resolvePostalByArea();
                }
            });
    }

    if (provinceSelect) {
        provinceSelect.addEventListener('change', function () {
            provinceSelect.setAttribute('data-old', '');
            citySelect.setAttribute('data-old', '');
            districtSelect.setAttribute('data-old', '');
            villageSelect.setAttribute('data-old', '');
            loadCities(provinceSelect.value);
        });
    }
    if (citySelect) {
        citySelect.addEventListener('change', function () {
            citySelect.setAttribute('data-old', '');
            districtSelect.setAttribute('data-old', '');
            villageSelect.setAttribute('data-old', '');
            loadDistricts(citySelect.value);
        });
    }
    if (districtSelect) {
        districtSelect.addEventListener('change', function () {
            districtSelect.setAttribute('data-old', '');
            villageSelect.setAttribute('data-old', '');
            loadVillages(districtSelect.value);
        });
    }
    if (villageSelect) {
        villageSelect.addEventListener('change', function () {
            resolvePostalByArea();
            setTimeout(resolvePostalByArea, 300);
        });
    }
    if (postalSelect) {
        postalSelect.addEventListener('focus', function () {
            if (postalSelect.options.length <= 1 && villageSelect && villageSelect.value) {
                resolvePostalByArea();
            }
        });
        postalSelect.addEventListener('click', function () {
            if (postalSelect.options.length <= 1 && villageSelect && villageSelect.value) {
                resolvePostalByArea();
            }
        });
    }
    loadProvinces().then(function () {
        if (villageSelect && villageSelect.value) {
            resolvePostalByArea();
            setTimeout(resolvePostalByArea, 300);
        }
    });
    setPostalOptions([], postalSelect ? postalSelect.getAttribute('data-old') : '');

    var map = null;
    var marker = null;
    var locationSource = 'manual';
    var setCoordinate = function (lat, lng, source) {
        locationSource = source || 'manual';
        var latFixed = Number(lat).toFixed(8);
        var lngFixed = Number(lng).toFixed(8);
        latitudeInput.value = latFixed;
        longitudeInput.value = lngFixed;
        statusInput.value = 'ALLOW';
        coordinatePreview.value = latFixed + ', ' + lngFixed;
        if (mapHint) {
            mapHint.textContent = 'Koordinat tersimpan. Anda bisa lanjut isi form.';
        }
        reverseGeocode(latFixed, lngFixed);
    };

    function reverseGeocode(lat, lng) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng))
            .then(function (res) { return res.json(); })
            .then(function (json) {
                var addr = json.address || {};
                if (json.display_name && !addressInput.value) {
                    addressInput.value = json.display_name;
                }
                ensureRegionOptionsLoaded()
                    .then(function () {
                        if (!provinceSelect.value || !citySelect.value || !districtSelect.value || !villageSelect.value) {
                            return autoFillRegionFromAddress(addr);
                        }
                    })
                    .then(function () {
                        if (provinceSelect.value && citySelect.value && districtSelect.value && villageSelect.value) {
                            return resolvePostalByArea();
                        }
                        if (addr.postcode) {
                            setPostalOptions([addr.postcode], addr.postcode);
                        }
                    });
                sendGeoDebug('reverse_geocode_success', {
                    coords: { lat: lat, lng: lng },
                    meta: {
                        state: addr.state || null,
                        city: addr.city || addr.town || addr.county || null,
                        district: addr.city_district || addr.district || null,
                        village: addr.village || addr.hamlet || addr.neighbourhood || null,
                        postcode: addr.postcode || null
                    }
                });
            })
            .catch(function (err) {
                sendGeoDebug('reverse_geocode_error', {
                    message: err && err.message ? err.message : 'reverse geocode failed',
                    coords: { lat: lat, lng: lng }
                });
            });
    }

    if (typeof L !== 'undefined') {
        var initialLat = Number(latitudeInput.value || 1.1303);
        var initialLng = Number(longitudeInput.value || 104.0530);
        map = L.map('registerAddressMap').setView([initialLat, initialLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

        marker.on('dragend', function () {
            var latlng = marker.getLatLng();
            setCoordinate(latlng.lat, latlng.lng, 'manual');
        });

        map.on('click', function (event) {
            marker.setLatLng(event.latlng);
            setCoordinate(event.latlng.lat, event.latlng.lng, 'manual');
        });

        if (latitudeInput.value && longitudeInput.value) {
            coordinatePreview.value = Number(latitudeInput.value).toFixed(8) + ', ' + Number(longitudeInput.value).toFixed(8);
            statusInput.value = 'ALLOW';
        } else {
            statusInput.value = 'DENIED';
        }

    } else if (mapHint) {
        mapHint.textContent = 'Peta gagal dimuat. Muat ulang halaman untuk mencoba kembali.';
    }

    if (useMyLocationBtn) {
        useMyLocationBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                if (mapHint) mapHint.textContent = 'Browser ini tidak mendukung geolocation.';
                return;
            }
            useMyLocationBtn.disabled = true;
            useMyLocationBtn.textContent = 'Mengambil lokasi...';
            var onSuccess = function (position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                if (map && marker) {
                    map.setView([lat, lng], 16);
                    marker.setLatLng([lat, lng]);
                }
                setCoordinate(lat, lng, 'geolocation');
                if (mapHint) mapHint.textContent = 'Lokasi berhasil diambil. Silakan lanjut isi form.';
                sendGeoDebug('geolocation_success', {
                    coords: { lat: lat, lng: lng },
                    meta: {
                        accuracy: position.coords.accuracy || null,
                        altitude: position.coords.altitude || null,
                        heading: position.coords.heading || null,
                        speed: position.coords.speed || null
                    }
                });
                useMyLocationBtn.disabled = false;
                useMyLocationBtn.textContent = 'Gunakan Lokasi Saya';
            };

            var finishError = function (error) {
                var message = 'Lokasi gagal diambil. Pastikan izin lokasi browser aktif.';
                if (error && typeof error.code !== 'undefined') {
                    if (error.code === 1) message = 'Izin lokasi ditolak. Aktifkan izin lokasi di browser Anda.';
                    if (error.code === 2) message = 'Lokasi tidak tersedia. Coba lagi beberapa saat.';
                    if (error.code === 3) message = 'Sinyal GPS sedang lambat.';
                }

                // Fallback: use current map center so flow can continue.
                if (map) {
                    var center = map.getCenter();
                    if (center && typeof center.lat === 'number' && typeof center.lng === 'number') {
                        if (marker) {
                            marker.setLatLng(center);
                        }
                        setCoordinate(center.lat, center.lng, 'fallback');
                        if (mapHint) {
                            mapHint.textContent = message + ' Lokasi sementara diambil dari titik tengah peta. Sistem akan mencoba sinkron ulang GPS otomatis.';
                        }
                        sendGeoDebug('geolocation_fallback_center', {
                            error_code: error && typeof error.code !== 'undefined' ? error.code : null,
                            message: message,
                            coords: { lat: center.lat, lng: center.lng }
                        });
                        // Silent retry in background: if GPS becomes available, replace fallback coordinate.
                        navigator.geolocation.getCurrentPosition(
                            function (position) {
                                var lat = position.coords.latitude;
                                var lng = position.coords.longitude;
                                if (map && marker) {
                                    map.setView([lat, lng], 16);
                                    marker.setLatLng([lat, lng]);
                                }
                                setCoordinate(lat, lng, 'geolocation');
                                if (mapHint) mapHint.textContent = 'Lokasi GPS berhasil disinkronkan.';
                                sendGeoDebug('geolocation_background_success', {
                                    coords: { lat: lat, lng: lng },
                                    meta: { accuracy: position.coords.accuracy || null }
                                });
                            },
                            function (bgErr) {
                                sendGeoDebug('geolocation_background_error', {
                                    error_code: bgErr && typeof bgErr.code !== 'undefined' ? bgErr.code : null,
                                    message: 'background retry failed'
                                });
                            },
                            { enableHighAccuracy: true, timeout: 45000, maximumAge: 0 }
                        );
                        useMyLocationBtn.disabled = false;
                        useMyLocationBtn.textContent = 'Gunakan Lokasi Saya';
                        return;
                    }
                }

                statusInput.value = 'DENIED';
                if (mapHint) mapHint.textContent = message;
                sendGeoDebug('geolocation_error', {
                    error_code: error && typeof error.code !== 'undefined' ? error.code : null,
                    message: message
                });
                useMyLocationBtn.disabled = false;
                useMyLocationBtn.textContent = 'Gunakan Lokasi Saya';
            };
            // Strategy: try cached/low-accuracy first (faster), then high-accuracy.
            navigator.geolocation.getCurrentPosition(
                onSuccess,
                function () {
                    navigator.geolocation.getCurrentPosition(
                        onSuccess,
                        finishError,
                        { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 }
                    );
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
            );
        });
    }

    var registerForm = document.querySelector('form[action=\"{{ route('register.post') }}\"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function (event) {
            var hasLocation = latitudeInput && longitudeInput
                && String(latitudeInput.value || '').trim() !== ''
                && String(longitudeInput.value || '').trim() !== '';
            if (!hasLocation) {
                event.preventDefault();
                statusInput.value = 'DENIED';
                if (mapHint) mapHint.textContent = 'Lokasi rumah wajib dipilih (klik peta atau gunakan lokasi saya) sebelum daftar.';
            }
        });
    }
});
</script>
@endsection
