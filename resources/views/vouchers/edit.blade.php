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
                        <label class="form-label fw-bold">Additional Pax per Facility</label>
                        <p class="form-text text-muted mt-0 mb-2">Set extra pax for each facility. Leave 0 for no addition.</p>
                        @php $additionMap = $voucher->addition_map ?? []; @endphp
                        <div class="row g-2">
                            @foreach($facilityTemplates as $facility)
                            @php $isGranted = in_array($facility->id, $currentFacilityIds); @endphp
                            <div class="col-md-4 col-lg-3">
                                <div class="border rounded p-2 h-100">
                                    <div class="fw-bold small mb-1">{{ $facility->name }}</div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">+</span>
                                        <input type="number" name="addition_map[{{ $facility->id }}]"
                                               class="form-control @error('addition_map.{{ $facility->id }}') is-invalid @enderror"
                                               value="{{ old('addition_map.' . $facility->id, $additionMap[$facility->id] ?? 0) }}"
                                               min="0" max="50" {{ !$isGranted ? 'disabled' : '' }}>
                                        <span class="input-group-text">pax</span>
                                    </div>
                                    @error('addition_map.{{ $facility->id }}')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            @endforeach
                        </div>
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


