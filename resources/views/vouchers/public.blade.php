@extends('layouts.guest')
@section('title', 'Your Resort Pass')
@section('content')
<div class="voucher-public">

    {{-- Guest Info Card --}}
    <div class="card border-0 shadow-sm mb-0 rounded-4 overflow-hidden" style="background: var(--forest-cream);">
        <div class="card-body text-center px-4 pt-4 pb-3">
            <div class="mb-3">
                @if($voucherState === 'active')
                    <span class="badge px-3 py-2 rounded-pill text-uppercase fw-semibold" style="background-color: var(--bs-success); font-size: 0.7rem; letter-spacing: 0.5px;">Active Stay Pass</span>
                @elseif($voucherState === 'expired')
                    <span class="badge bg-danger px-3 py-2 rounded-pill text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Expired</span>
                @elseif($voucherState === 'not_checked_in')
                    <span class="badge bg-warning px-3 py-2 rounded-pill text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Not Checked In</span>
                @else
                    <span class="badge bg-secondary px-3 py-2 rounded-pill text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Inactive</span>
                @endif
            </div>
            <h1 class="fw-bold text-dark mb-1" style="font-size: 1.5rem;">{{ $voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest') }}</h1>
            <p class="text-muted small mb-0">Room: <strong>{{ $voucher->booking?->room?->code ?? 'TEMP' }} - {{ $voucher->booking?->room?->label ?? $voucher->booking?->room_label ?? 'Temporary' }}</strong></p>
        </div>
    </div>

    {{-- Welcome Banner --}}
    <div class="text-center position-relative overflow-hidden rounded-4" style="background: linear-gradient(135deg, var(--bs-primary-dark) 0%, var(--bs-primary) 50%, var(--bs-primary-light) 100%); padding: 2rem 1.5rem;">
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.06) 0%, transparent 60%);"></div>
        <div class="position-relative">
            <div class="d-inline-flex align-items-center gap-2 mb-2 px-3 py-1 rounded-pill" style="background: rgba(255,255,255,0.12); backdrop-filter: blur(4px);">
                <i class="fas fa-tree" style="font-size: 0.65rem; color: rgba(255,255,255,0.7);"></i>
                <span class="text-uppercase" style="font-size: 0.6rem; letter-spacing: 2px; color: rgba(255,255,255,0.7); font-weight: 500;">{{ $voucher->property?->name ?? 'Chanaya' }}</span>
            </div>
            <h2 class="fw-bold text-white mb-0" style="font-size: 1.35rem; letter-spacing: 0.3px; text-shadow: 0 1px 3px rgba(0,0,0,0.15);">
                Welcome to {{ $voucher->property?->name ?? 'Chanaya' }}
            </h2>
        </div>
    </div>

    {{-- Stay Info --}}
    <div class="mx-3 my-3 p-3 rounded-3 border" style="border-color: var(--forest-border) !important; background: #fff;">
        <div class="row text-center">
            <div class="col-6">
                <div class="text-muted small mb-1">Stay Dates</div>
                <div class="fw-bold text-dark small">
                    @if($voucher->booking)
                        {{ $voucher->booking->check_in->format('d M') }} – {{ $voucher->booking->check_out->format('d M Y') }}
                    @else
                        {{ $voucher->expires_at_local ? $voucher->expires_at_local->format('d M Y H:i') : 'N/A' }}
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="text-muted small mb-1">Total Pax</div>
                <div class="fw-bold text-dark small">
                    @php
                        $additionApplies = $voucher->additionAppliesOn(\Carbon\Carbon::today($voucher->property?->timezone ?? $voucher->booking?->property?->timezone ?? 'UTC')->toDateString());
                    @endphp
                    @if($voucher->booking)
                        {{ $voucher->booking->total_pax + $voucher->booking->extra_beds }}{{ $additionApplies ? ' (+' . $voucher->addition . ')' : '' }} guests
                    @else
                        {{ ($voucher->pax_limit ?? 1) }}{{ $additionApplies ? ' (+' . ($voucher->addition ?? 0) . ')' : '' }} guests
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Facility Statuses --}}
    <div class="mx-3 mb-3">
        <h3 class="text-center fw-semibold text-dark border-bottom pb-2 mb-3" style="font-size: 0.95rem; border-color: var(--forest-border) !important;">
            Today's Facility Statuses
        </h3>

        <div>
            @forelse($facilityStatuses as $facility)
                <div class="mb-3 p-3 rounded-3 border bg-white shadow-sm" style="border-color: var(--forest-border) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $facility->name }}</span>
                        @if($facility->status === 'available')
                            <span class="badge px-2 py-1 rounded-pill" style="background-color: var(--bs-success); font-size: 0.7rem;">Available today</span>
                        @elseif($facility->status === 'used')
                            <span class="badge bg-warning px-2 py-1 rounded-pill" style="font-size: 0.7rem;">Quota used today</span>
                        @else
                            <span class="badge bg-secondary px-2 py-1 rounded-pill" style="font-size: 0.7rem;">Not available today</span>
                        @endif
                    </div>
                    @if($facility->status === 'available')
                        @php
                            $usedPercent = $facility->quota_total > 0 ? ($facility->quota_used / $facility->quota_total) * 100 : 0;
                        @endphp
                        <div class="progress mb-2" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ 100 - $usedPercent }}%" aria-valuenow="{{ $facility->quota_remaining }}" aria-valuemin="0" aria-valuemax="{{ $facility->quota_total }}"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $usedPercent }}%" aria-valuenow="{{ $facility->quota_used }}" aria-valuemin="0" aria-valuemax="{{ $facility->quota_total }}"></div>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                            <span class="text-muted">Remaining: <strong class="text-dark">{{ $facility->quota_remaining }}</strong></span>
                            <span class="text-muted">Used: {{ $facility->quota_used }} / {{ $facility->quota_total }}</span>
                        </div>
                    @elseif($facility->status === 'used')
                        <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                            <span class="text-muted">Remaining: <strong class="text-dark">0</strong></span>
                            <span class="text-muted">Used: {{ $facility->quota_used }} / {{ $facility->quota_total }}</span>
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

    <div class="mt-2 mb-3 text-center px-3">
        <x-qr-code :url="$qrImageUrl" :size="500" class="rounded-3" />
    </div>

</div>
@endsection