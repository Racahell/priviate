@extends('layouts.master')

@section('title', 'Profil')

@section('content')
@php
    $requiresAddressRole = $user->hasRole('siswa') || $user->hasRole('tentor') || $user->hasRole('orang_tua');
@endphp
<div class="card">
    <h3 class="card-title">Profil Saya</h3>
    <p class="card-meta">Perbarui data akun agar proses belajar berjalan lancar.</p>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="section">
        @csrf
        <div class="grid grid-2">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="{{ $user->email }}" readonly>
            </div>
        </div>

        @if($user->hasRole('siswa'))
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Kode Siswa</label>
                    <input type="text" class="form-control" value="{{ $user->code }}" readonly>
                    <small class="text-muted">Berikan kode ini ke akun orang tua untuk monitoring progress belajar.</small>
                </div>
            </div>
        @endif

        <div class="grid grid-3">
            <div class="form-group">
                <label>No HP</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="form-group">
                <label>Provinsi</label>
                <select name="province" id="profileProvince" class="form-control" data-old="{{ old('province', $user->province) }}">
                    <option value="">Pilih provinsi</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kota / Kabupaten</label>
                <select name="city" id="profileCity" class="form-control" data-old="{{ old('city', $user->city) }}">
                    <option value="">Pilih kota/kabupaten</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kecamatan</label>
                <select name="district" id="profileDistrict" class="form-control" data-old="{{ old('district', $user->district) }}">
                    <option value="">Pilih kecamatan</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kelurahan / Desa</label>
                <select name="village" id="profileVillage" class="form-control" data-old="{{ old('village', $user->village) }}">
                    <option value="">Pilih kelurahan/desa</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kode Pos</label>
                <select name="postal_code" id="profilePostalCode" class="form-control" data-old="{{ old('postal_code', $user->postal_code) }}">
                    <option value="">Pilih kode pos</option>
                </select>
            </div>
        </div>

        <div class="grid grid-1">
            <div class="form-group">
                <label>Detail Alamat</label>
                <textarea name="address" id="profileAddressDetail" class="form-control" rows="3">{{ old('address', $user->address) }}</textarea>
            </div>
        </div>

        @if($requiresAddressRole)
            <div class="grid grid-1">
                <div class="form-group">
                    <label>Titik Lokasi</label>
                    <div id="profileAddressMap" style="height:320px; border:1px solid #d7deea; border-radius:10px; margin-bottom:8px;"></div>
                    <input type="text" id="profileCoordinatesPreview" class="form-control" value="{{ old('latitude', $user->latitude) && old('longitude', $user->longitude) ? old('latitude', $user->latitude) . ', ' . old('longitude', $user->longitude) : (($user->latitude && $user->longitude) ? $user->latitude . ', ' . $user->longitude : '') }}" placeholder="Belum ada titik lokasi" readonly>
                    <input type="hidden" name="latitude" id="profileLatitude" value="{{ old('latitude', $user->latitude) }}">
                    <input type="hidden" name="longitude" id="profileLongitude" value="{{ old('longitude', $user->longitude) }}">
                    <small class="text-muted">Dipakai untuk membantu tentor menemukan lokasi saat sesi offline.</small>
                </div>
                <div class="form-group profile-location-actions">
                    <label>Aksi Lokasi</label>
                    <div class="split-actions">
                        <button type="button" class="btn btn-outline" id="captureProfileLocationBtn">Gunakan Lokasi Saat Ini</button>
                        <a href="{{ ($user->latitude && $user->longitude) ? ('https://maps.google.com/?q=' . $user->latitude . ',' . $user->longitude) : '#' }}" target="_blank" rel="noopener" class="btn btn-outline" id="profileMapsLink" style="{{ ($user->latitude && $user->longitude) ? '' : 'display:none;' }}">Lihat di Google Maps</a>
                    </div>
                </div>
            </div>
            @if($supportsLocationNotes ?? false)
            <div class="grid grid-1">
                <div class="form-group">
                    <label>Catatan Lokasi</label>
                    <textarea name="location_notes" class="form-control" rows="3" placeholder="Contoh: Rumah warna putih dekat masjid, pagar hitam, masuk gang sebelah minimarket.">{{ old('location_notes', $user->location_notes) }}</textarea>
                    <small class="text-muted">Catatan ini akan ditampilkan ke tentor hanya untuk sesi offline yang sudah ditugaskan.</small>
                </div>
            </div>
            @endif
        @endif

        <div class="grid grid-2">
            <div class="form-group">
                <label>Avatar</label>
                <input type="file" name="avatar" class="form-control">
                @if(!empty($user->avatar))
                    <div class="profile-avatar-preview">
                        <img src="{{ asset($user->avatar) }}" alt="Avatar">
                    </div>
                @endif
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Profil</button>
    </form>
</div>

<div class="card profile-otp-card">
    <h3 class="card-title">Reset Password via OTP</h3>
    <p class="card-meta">Kirim kode OTP melalui WhatsApp atau Email untuk reset password secara aman.</p>
    <div class="profile-otp-actions">
        <a href="{{ route('password.forgot', ['email' => $user->email]) }}" class="btn btn-outline profile-otp-btn">Kirim OTP Reset Password</a>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
    var provinceSelect = document.getElementById('profileProvince');
    var citySelect = document.getElementById('profileCity');
    var districtSelect = document.getElementById('profileDistrict');
    var villageSelect = document.getElementById('profileVillage');
    var postalSelect = document.getElementById('profilePostalCode');
    var addressInput = document.getElementById('profileAddressDetail');
    var captureBtn = document.getElementById('captureProfileLocationBtn');
    var latitudeInput = document.getElementById('profileLatitude');
    var longitudeInput = document.getElementById('profileLongitude');
    var previewInput = document.getElementById('profileCoordinatesPreview');
    var mapsLink = document.getElementById('profileMapsLink');
    var lookup = {
        provinces: '/location-lookup/provinces',
        regenciesBase: '/location-lookup/regencies',
        districtsBase: '/location-lookup/districts',
        villagesBase: '/location-lookup/villages',
        postalCodes: '/location-lookup/postal-codes'
    };

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
    var setCoordinate = function (lat, lng) {
        var latFixed = Number(lat).toFixed(8);
        var lngFixed = Number(lng).toFixed(8);
        latitudeInput.value = latFixed;
        longitudeInput.value = lngFixed;
        previewInput.value = latFixed + ', ' + lngFixed;
        if (mapsLink) {
            mapsLink.href = 'https://maps.google.com/?q=' + latFixed + ',' + lngFixed;
            mapsLink.style.display = '';
        }
    };

    if (typeof L !== 'undefined' && document.getElementById('profileAddressMap') && latitudeInput && longitudeInput && previewInput) {
        var initialLat = Number(latitudeInput.value || 1.1303);
        var initialLng = Number(longitudeInput.value || 104.0530);
        map = L.map('profileAddressMap').setView([initialLat, initialLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

        setCoordinate = function (lat, lng) {
            var latFixed = Number(lat).toFixed(8);
            var lngFixed = Number(lng).toFixed(8);
            latitudeInput.value = latFixed;
            longitudeInput.value = lngFixed;
            previewInput.value = latFixed + ', ' + lngFixed;
            if (mapsLink) {
                mapsLink.href = 'https://maps.google.com/?q=' + latFixed + ',' + lngFixed;
                mapsLink.style.display = '';
            }
            reverseGeocode(latFixed, lngFixed);
        }

        function reverseGeocode(lat, lng) {
            fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng))
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    var addr = json.address || {};
                    if (addr.postcode) {
                        setPostalOptions([addr.postcode], addr.postcode);
                    }
                    if (json.display_name && addressInput && !addressInput.value) {
                        addressInput.value = json.display_name;
                    }
                })
                .catch(function () {});
        }

        marker.on('dragend', function () {
            var latlng = marker.getLatLng();
            setCoordinate(latlng.lat, latlng.lng);
        });
        map.on('click', function (event) {
            marker.setLatLng(event.latlng);
            setCoordinate(event.latlng.lat, event.latlng.lng);
        });
    }

    if (captureBtn && latitudeInput && longitudeInput && previewInput) {
        captureBtn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung geolocation.');
            return;
        }

        captureBtn.disabled = true;
        captureBtn.textContent = 'Mengambil lokasi...';

        navigator.geolocation.getCurrentPosition(
            function (position) {
                var latitude = Number(position.coords.latitude).toFixed(8);
                var longitude = Number(position.coords.longitude).toFixed(8);
                setCoordinate(latitude, longitude);
                if (map && marker) {
                    marker.setLatLng([latitude, longitude]);
                    map.setView([latitude, longitude], 16);
                }
                if (mapsLink) {
                    mapsLink.href = 'https://maps.google.com/?q=' + latitude + ',' + longitude;
                    mapsLink.style.display = '';
                }
                captureBtn.disabled = false;
                captureBtn.textContent = 'Gunakan Lokasi Saat Ini';
            },
            function () {
                captureBtn.disabled = false;
                captureBtn.textContent = 'Gunakan Lokasi Saat Ini';
                alert('Lokasi gagal diambil. Pastikan izin lokasi di browser aktif.');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
        });
    }
})();
</script>
@endpush
