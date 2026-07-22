@extends('layouts.app')
@section('title', 'Edit Voucher Facilities')
@section('page_title', 'Edit Voucher: ' . ($voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest')))
@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('vouchers.update', $voucher) }}">
                    @csrf

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Modify the facilities and addition assigned to this voucher.
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Guest Name:</strong></p>
                            <p class="text-muted">{{ $voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Status:</strong></p>
                            <p><span class="badge bg-{{ $voucher->status->value === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($voucher->status->value) }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Room:</strong></p>
                            <p class="text-muted">{{ $voucher->booking?->room_label ?? $voucher->booking?->room?->label ?? 'Temporary' }} ({{ $voucher->booking?->room?->code ?? 'TEMP' }})</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Booking Code:</strong></p>
                            <p class="text-muted">{{ $voucher->booking?->booking_code ?? $voucher->booking?->reference ?? 'N/A' }}</p>
                        </div>
                        @if($voucher->booking)
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Stay Dates:</strong></p>
                            <p class="text-muted">{{ $voucher->booking->check_in->format('Y-m-d') }} – {{ $voucher->booking->check_out->format('Y-m-d') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Total Pax (Booking):</strong></p>
                            <p class="text-muted">{{ $voucher->booking->total_pax + $voucher->booking->extra_beds }}</p>
                        </div>
                        @else
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Expires At:</strong></p>
                            <p class="text-muted">{{ $voucher->expires_at ? $voucher->expires_at->format('Y-m-d H:i') : 'N/A' }}</p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Property:</strong></p>
                            <p class="text-muted">{{ $voucher->property?->name ?? $voucher->booking?->property?->name ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Addition</label>
                        <p class="form-text text-muted mt-0 mb-2">Extra pax to add on top of the booking's Total Pax. Leave empty for no addition.</p>
                        <input type="number" name="addition" class="form-control @error('addition') is-invalid @enderror"
                               value="{{ old('addition', $voucher->addition) }}" min="0" max="50" placeholder="0">
                        @error('addition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Facility Access</label>
                        <p class="form-text text-muted mt-0 mb-2">Mark each facility as Granted or Not Granted. Changes take effect on save.</p>

                        @if($facilityTemplates->isEmpty())
                            <div class="alert alert-warning">No active facilities available for this property.</div>
                        @else
                            <div class="row g-2">
                                @foreach($facilityTemplates as $facility)
                                @php $isGranted = in_array($facility->id, $currentFacilityIds); @endphp
                                <div class="col-md-6 col-lg-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="fw-bold small mb-1">{{ $facility->name }}</div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio"
                                                   name="facility_status[{{ $facility->id }}]"
                                                   value="granted"
                                                   id="facility_{{ $facility->id }}_granted"
                                                   {{ $isGranted ? 'checked' : '' }}>
                                            <label class="form-check-label small text-success" for="facility_{{ $facility->id }}_granted">Granted</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio"
                                                   name="facility_status[{{ $facility->id }}]"
                                                   value="not_granted"
                                                   id="facility_{{ $facility->id }}_not_granted"
                                                   {{ !$isGranted ? 'checked' : '' }}>
                                            <label class="form-check-label small text-muted" for="facility_{{ $facility->id }}_not_granted">Not Granted</label>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                        @error('facility_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3" id="addition-facility-section" style="display: {{ ($voucher->addition ?? 0) > 0 ? 'block' : 'none' }}">
                        <label class="form-label fw-bold">Apply Addition To</label>
                        <p class="form-text text-muted mt-0 mb-2">Select which facilities get the extra pax. Leave all unchecked to apply to none (addition stored but not allocated).</p>
                        @php $selectedAdditionIds = $voucher->addition_facility_ids ? array_map('intval', explode(',', $voucher->addition_facility_ids)) : []; @endphp
                        <div class="row g-2">
                            @foreach($facilityTemplates as $facility)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="addition_facility_ids[]"
                                           value="{{ $facility->id }}"
                                           id="addition_facility_{{ $facility->id }}"
                                           {{ in_array($facility->id, $selectedAdditionIds) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="addition_facility_{{ $facility->id }}">
                                        {{ $facility->name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @error('addition_facility_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    const additionInput = document.querySelector('input[name="addition"]');
    const additionSection = document.getElementById('addition-facility-section');
    if (additionInput && additionSection) {
        additionInput.addEventListener('input', function () {
            additionSection.style.display = parseInt(this.value) > 0 ? 'block' : 'none';
        });
    }
</script>
@endpush
