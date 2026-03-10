@extends('layouts.master')

@section('title', 'Booking Sesi')

@section('content')
<div class="booking-page">
<div class="card section">
    <h3 class="card-title">Booking Paket</h3>
    <p class="card-meta">Pilih invoice paket yang ingin dibooking. Status booking mengikuti status pembayaran invoice.</p>

    @include('components.pagination-controls', ['paginator' => $bookingInvoices, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No Invoice</th>
                    <th>Tipe Paket</th>
                    <th>Jatah Sesi</th>
                    <th>Status Pembayaran</th>
                    <th>Status Booking</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookingInvoices as $index => $row)
                    <tr>
                        <td>{{ ($bookingInvoices->currentPage() - 1) * $bookingInvoices->perPage() + $index + 1 }}</td>
                        <td>{{ $row->invoice_number }}</td>
                        <td>
                            {{ $row->package_label }}
                            @if(!empty($row->is_trial))
                                (Trial: 1 pertemuan)
                            @else
                                ({{ $row->weekly_quota }} sesi/minggu)
                            @endif
                        </td>
                        <td>
                            {{ $row->used_sessions }}/{{ $row->total_sessions }}
                            <br>
                            <small>Sisa: {{ $row->remaining_sessions }}</small>
                        </td>
                        <td>
                            <span class="badge {{ strtoupper($row->payment_status) === 'PAID' ? 'badge-success' : 'badge-warning' }}">
                                {{ strtoupper($row->payment_status) === 'PAID' ? 'SUDAH BAYAR' : 'BELUM BAYAR' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $row->booking_status === 'Booked' ? 'badge-success' : 'badge-info' }}">
                                {{ strtoupper($row->booking_status) }}
                            </span>
                        </td>
                        <td>
                            @if($row->can_book)
                                <button
                                    type="button"
                                    class="btn btn-primary btn-xs open-booking-modal"
                                    data-invoice-id="{{ $row->invoice_id }}"
                                    data-invoice-number="{{ $row->invoice_number }}"
                                    data-weekly-quota="{{ $row->weekly_quota }}"
                                    data-booking-weeks="{{ $row->booking_weeks }}">
                                    Booking
                                </button>
                            @elseif($row->booking_status === 'Booked')
                                <button
                                    type="button"
                                    class="btn btn-outline btn-xs open-booking-detail"
                                    data-invoice-id="{{ $row->invoice_id }}"
                                    data-invoice-number="{{ $row->invoice_number }}">
                                    Detail
                                </button>
                            @else
                                <button type="button" class="btn btn-outline btn-xs" disabled>
                                    {{ $row->booking_status === 'Belum Bayar' ? 'Belum Bayar' : 'Booked' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada invoice paket.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('components.pagination-controls', ['paginator' => $bookingInvoices, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
</div>

</div>

<div class="modal-overlay" id="bookingModal" aria-hidden="true">
    <div class="modal-card payment-modal-card">
        <div class="split-header">
            <h3 class="card-title">Booking Sesi</h3>
            <button type="button" class="btn btn-outline btn-xs" id="closeBookingModal">Tutup</button>
        </div>
        <p class="card-meta" id="bookingInvoiceLabel">Invoice: -</p>

        <form method="POST" action="{{ route('ops.slot.book') }}" id="bookingForm" class="section modal-form">
            @csrf
            <input type="hidden" name="invoice_id" id="bookingInvoiceId">
            <p class="card-meta" id="bookingRuleText">Pilih hari dan jam sesi. Jadwal akan dibuat berulang otomatis selama 1 bulan.</p>
            <div class="alert alert-info" id="bookingAvailabilityInfo" style="display:none; margin-bottom:10px;"></div>
            <div class="form-group">
                <label>Mapel</label>
                <select name="subject_id" class="form-control" id="bookingSubject" required>
                    <option value="">Pilih mapel</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}{{ $subject->level ? ' - '.$subject->level : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Mode Kelas</label>
                <select name="delivery_mode" class="form-control" required>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                </select>
            </div>
            <div id="bookingRowsWrap"></div>
            <button class="btn btn-primary" type="submit">Booking Sesi</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="bookingDetailModal" aria-hidden="true">
    <div class="modal-card">
        <div class="split-header">
            <h3 class="card-title">Detail Booking</h3>
            <button type="button" class="btn btn-outline btn-xs" id="closeBookingDetailModal">Tutup</button>
        </div>
        <p class="card-meta" id="bookingDetailInvoiceLabel">Invoice: -</p>
        <div class="table-wrap section">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Mapel</th>
                        <th>Guru</th>
                        <th>Jadwal</th>
                        <th>Mode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="bookingDetailRows">
                    <tr><td colspan="6">Belum ada hasil booking.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<template id="bookingRowTemplate">
    <div class="grid grid-2 booking-row">
        <div class="form-group">
            <label class="booking-day-label">Hari Les</label>
            <select name="booking_days[]" class="form-control booking-day" required>
                <option value="">Pilih hari</option>
                <option value="1">Senin</option>
                <option value="2">Selasa</option>
                <option value="3">Rabu</option>
                <option value="4">Kamis</option>
                <option value="5">Jumat</option>
                <option value="6">Sabtu</option>
                <option value="0">Minggu</option>
            </select>
        </div>
        <div class="form-group">
            <label class="booking-slot-label">Jam Sesi</label>
            <select name="slot_ids[]" class="form-control booking-slot" required>
                <option value="">Pilih jam sesi</option>
                @foreach($openSlots as $slot)
                    <option value="{{ $slot->id }}">Sesi {{ $loop->iteration }} | {{ optional($slot->start_at)->format('H:i') }} - {{ optional($slot->end_at)->format('H:i') }}</option>
                @endforeach
            </select>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('bookingModal');
    var closeBtn = document.getElementById('closeBookingModal');
    var invoiceIdInput = document.getElementById('bookingInvoiceId');
    var invoiceLabel = document.getElementById('bookingInvoiceLabel');
    var rowsWrap = document.getElementById('bookingRowsWrap');
    var tpl = document.getElementById('bookingRowTemplate');
    var subjectEl = document.getElementById('bookingSubject');
    var infoBox = document.getElementById('bookingAvailabilityInfo');
    var ruleText = document.getElementById('bookingRuleText');
    var detailModal = document.getElementById('bookingDetailModal');
    var closeDetailBtn = document.getElementById('closeBookingDetailModal');
    var detailInvoiceLabel = document.getElementById('bookingDetailInvoiceLabel');
    var detailRows = document.getElementById('bookingDetailRows');
    var bookedByInvoice = @json(($bookedByInvoice ?? collect())->toArray());
    var endpoint = @json(route('ops.slot.availability'));
    if (!modal || !closeBtn || !invoiceIdInput || !rowsWrap || !tpl || !subjectEl || !infoBox) return;

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        infoBox.style.display = 'none';
        infoBox.textContent = '';
    }

    function openDetailModal(invoiceId, invoiceNumber) {
        if (!detailModal || !detailRows || !detailInvoiceLabel) return;
        var rows = bookedByInvoice[String(invoiceId)] || bookedByInvoice[Number(invoiceId)] || [];
        detailInvoiceLabel.textContent = 'Invoice: ' + (invoiceNumber || '-');
        if (!Array.isArray(rows) || rows.length === 0) {
            detailRows.innerHTML = '<tr><td colspan="6">Belum ada hasil booking.</td></tr>';
        } else {
            detailRows.innerHTML = rows.map(function (row, idx) {
                return '<tr>'
                    + '<td>' + (idx + 1) + '</td>'
                    + '<td>' + (row.subject || '-') + '</td>'
                    + '<td>' + (row.tentor || '-') + '</td>'
                    + '<td>' + (row.schedule || '-') + '</td>'
                    + '<td>' + (row.mode || '-') + '</td>'
                    + '<td><span class="badge badge-info">' + (row.status || '-') + '</span></td>'
                    + '</tr>';
            }).join('');
        }
        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
    }

    function closeDetailModal() {
        if (!detailModal) return;
        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
    }

    function renderRows(quota) {
        rowsWrap.innerHTML = '';
        var total = Math.max(1, Number(quota || 1));
        for (var i = 0; i < total; i++) {
            var node = tpl.content.firstElementChild.cloneNode(true);
            node.setAttribute('data-row-index', String(i));
            var dayLabel = node.querySelector('.booking-day-label');
            var slotLabel = node.querySelector('.booking-slot-label');
            var dayInput = node.querySelector('.booking-day');
            if (dayLabel) dayLabel.textContent = 'Hari Les ' + (i + 1);
            if (slotLabel) slotLabel.textContent = 'Jam Sesi ' + (i + 1);
            if (dayInput) {
                dayInput.value = '';
            }
            rowsWrap.appendChild(node);
        }
        bindRowsLogic();
    }

    function getRows() {
        return Array.prototype.slice.call(rowsWrap.querySelectorAll('.booking-row'));
    }

    function selectedPairs() {
        return getRows().map(function (row) {
            var dayEl = row.querySelector('.booking-day');
            var slotEl = row.querySelector('.booking-slot');
            return {
                row: row,
                day: dayEl ? dayEl.value : '',
                slot: slotEl ? slotEl.value : ''
            };
        });
    }

    function applyDuplicateDisable() {
        var pairs = selectedPairs();
        getRows().forEach(function (row) {
            var dayEl = row.querySelector('.booking-day');
            var slotEl = row.querySelector('.booking-slot');
            if (!dayEl || !slotEl) return;
            var rowDay = dayEl.value;
            var duplicates = pairs
                .filter(function (p) { return p.row !== row && p.day === rowDay && p.slot; })
                .map(function (p) { return String(p.slot); });

            Array.prototype.slice.call(slotEl.options).forEach(function (opt) {
                if (!opt.value) return;
                if (duplicates.indexOf(String(opt.value)) >= 0) {
                    opt.disabled = true;
                    if (slotEl.value === opt.value) slotEl.value = '';
                } else if (!opt.getAttribute('data-server-disabled')) {
                    opt.disabled = false;
                }
            });
        });
    }

    function refreshRowAvailability(row) {
        var dayEl = row.querySelector('.booking-day');
        var slotEl = row.querySelector('.booking-slot');
        if (!dayEl || !slotEl) return;

        var subjectId = subjectEl.value;
        var bookingDay = dayEl.value;
        if (!subjectId || bookingDay === '') {
            infoBox.style.display = 'none';
            infoBox.textContent = '';
            return;
        }

        fetch(endpoint + '?subject_id=' + encodeURIComponent(subjectId) + '&booking_day=' + encodeURIComponent(bookingDay), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var available = Array.isArray(data.available_slot_ids) ? data.available_slot_ids.map(String) : [];
            if (available.length === 0) {
                infoBox.style.display = '';
                infoBox.textContent = 'Tidak ada jam sesi tersedia untuk mapel + hari ini. Silakan pilih hari lain atau mapel lain.';
            } else {
                infoBox.style.display = 'none';
                infoBox.textContent = '';
            }
            Array.prototype.slice.call(slotEl.options).forEach(function (opt) {
                if (!opt.value) return;
                var ok = available.indexOf(String(opt.value)) >= 0;
                opt.disabled = !ok;
                if (!ok) {
                    opt.setAttribute('data-server-disabled', '1');
                    if (slotEl.value === opt.value) slotEl.value = '';
                } else {
                    opt.removeAttribute('data-server-disabled');
                }
            });
            applyDuplicateDisable();
        })
        .catch(function () {
            infoBox.style.display = '';
            infoBox.textContent = 'Gagal memuat ketersediaan jam sesi. Coba ulangi.';
        });
    }

    function bindRowsLogic() {
        getRows().forEach(function (row) {
            var dayEl = row.querySelector('.booking-day');
            var slotEl = row.querySelector('.booking-slot');
            if (dayEl) {
                dayEl.addEventListener('change', function () { refreshRowAvailability(row); });
            }
            if (slotEl) {
                slotEl.addEventListener('change', applyDuplicateDisable);
            }
            refreshRowAvailability(row);
        });
    }

    subjectEl.addEventListener('change', function () {
        getRows().forEach(refreshRowAvailability);
    });

    document.querySelectorAll('.open-booking-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            invoiceIdInput.value = btn.getAttribute('data-invoice-id') || '';
            invoiceLabel.textContent = 'Invoice: ' + (btn.getAttribute('data-invoice-number') || '-');
            var weeks = Number(btn.getAttribute('data-booking-weeks') || '4');
            if (ruleText) {
                ruleText.textContent = weeks <= 1
                    ? 'Paket trial hanya 1 pertemuan. Pilih 1 hari dan 1 jam sesi.'
                    : 'Pilih hari dan jam sesi. Jadwal akan dibuat berulang otomatis selama 1 bulan.';
            }
            renderRows(btn.getAttribute('data-weekly-quota') || '1');
            openModal();
        });
    });

    document.querySelectorAll('.open-booking-detail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openDetailModal(
                btn.getAttribute('data-invoice-id') || '',
                btn.getAttribute('data-invoice-number') || '-'
            );
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    if (closeDetailBtn) {
        closeDetailBtn.addEventListener('click', closeDetailModal);
    }
    if (detailModal) {
        detailModal.addEventListener('click', function (e) {
            if (e.target === detailModal) closeDetailModal();
        });
    }
})();
</script>
@endpush
