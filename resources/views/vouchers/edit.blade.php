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
                        Modify the facilities and pax limit assigned to this voucher.
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
                        <label class="form-label fw-bold">Pax Limit</label>
                        <p class="form-text text-muted mt-0 mb-2">Maximum number of people allowed per redemption. Leave empty to use booking default.</p>
                        <input type="number" name="pax_limit" class="form-control @error('pax_limit') is-invalid @enderror"
                               value="{{ old('pax_limit', $voucher->pax_limit) }}" min="1" max="50" placeholder="Leave empty for default">
                        @error('pax_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Facility Access</label>
                        <p class="form-text text-muted mt-0 mb-2">Select the facilities this voucher can redeem. Uncheck to remove access.</p>

                        @if($facilityTemplates->isEmpty())
                            <div class="alert alert-warning">No active facilities available for this property.</div>
                        @else
                            <div class="row g-2">
                                @foreach($facilityTemplates as $facility)
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-check">
                                        <input class="btn-check facility-checkbox"
                                               type="checkbox"
                                               name="facility_template_ids[]"
                                               value="{{ $facility->id }}"
                                               id="facility_{{ $facility->id }}"
                                               autocomplete="off"
                                               {{ in_array($facility->id, $currentFacilityIds) ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary btn-sm w-100 text-start" for="facility_{{ $facility->id }}">
                                            {{ $facility->name }}
                                            @if(in_array($facility->id, $currentFacilityIds))
                                                <i class="fas fa-check-circle text-success ms-1"></i>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                        @error('facility_template_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
