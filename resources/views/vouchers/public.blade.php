@extends('layouts.guest')
@section('title', 'Your Resort Pass')
@section('content')
<div class="card card-primary card-outline shadow-lg border-0 rounded-4">
    <div class="card-body text-center p-4">
        <div class="mb-3">
            <span class="badge bg-success px-3 py-2 rounded-pill text-uppercase tracking-wider small">Active Stay Pass</span>
        </div>
        
        <h1 class="h3 font-weight-bold text-dark mb-1">{{ $voucher->booking->guest->full_name }}</h1>
        <p class="text-muted small mb-4">Room: <strong>{{ $voucher->booking->room_label ?? $voucher->booking->room?->label ?? 'N/A' }}</strong></p>

        <div class="p-3 bg-light rounded-4 border mb-4 d-inline-block shadow-inner">
            <x-qr-code :url="$qrImageUrl" :size="220" class="rounded-3" />
        </div>

        <div class="row text-left mb-4 bg-light p-3 rounded-3 border g-2">
            <div class="col-6">
                <span class="text-muted d-block small">Stay Dates</span>
                <span class="font-weight-bold text-dark small">{{ $voucher->booking->check_in->format('d M') }} – {{ $voucher->booking->check_out->format('d M Y') }}</span>
            </div>
            <div class="col-6">
                <span class="text-muted d-block small">Total Pax</span>
                <span class="font-weight-bold text-dark small">{{ $voucher->booking->total_pax + $voucher->booking->extra_beds }} guests</span>
            </div>
        </div>

        <h3 class="h6 font-weight-bold text-left text-dark border-bottom pb-2 mb-3">
            <i class="fas fa-concierge-bell text-primary me-2"></i> Today's Facility Statuses
        </h3>

        <div class="text-left">
            @forelse($facilityStatuses as $facility)
                <div class="mb-3 p-3 border rounded-3 bg-white shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold text-dark">{{ $facility->name }}</span>
                        @if($facility->is_available)
                            <span class="badge bg-info px-2 py-1">Available today</span>
                        @else
                            <span class="badge bg-secondary px-2 py-1">Not available today</span>
                        @endif
                    </div>
                    @if($facility->is_available)
                        <div class="progress mb-2" style="height: 8px; border-radius: 4px;">
                            @php
                                $usedPercent = $facility->quota_total > 0 ? ($facility->quota_used / $facility->quota_total) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ 100 - $usedPercent }}%" aria-valuenow="{{ $facility->quota_remaining }}" aria-valuemin="0" aria-valuemax="{{ $facility->quota_total }}"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $usedPercent }}%" aria-valuenow="{{ $facility->quota_used }}" aria-valuemin="0" aria-valuemax="{{ $facility->quota_total }}"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Remaining: <strong>{{ $facility->quota_remaining }}</strong></span>
                            <span>Used: {{ $facility->quota_used }} / {{ $facility->quota_total }}</span>
                        </div>
                    @else
                        <p class="mb-0 text-muted small">Period: {{ $facility->start_date->format('d M') }} to {{ $facility->end_date->format('d M') }}</p>
                    @endif
                </div>
            @empty
                <p class="text-muted text-center py-3">No active facilities found for this pass.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
