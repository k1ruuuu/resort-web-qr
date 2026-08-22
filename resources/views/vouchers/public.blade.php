@extends('layouts.guest')
@section('title', 'Your Resort Pass')
@section('content')
<div class="voucher-public">

    {{-- Guest Info Card --}}
    <div class="card border-0 shadow-sm mb-0 rounded-4 overflow-hidden" style="background: var(--cream);">
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
            <p class="text-muted small mb-0">Room: <strong>{{ $voucher->booking?->room?->label ?? $voucher->booking?->room_label ?? 'Temporary' }}</strong></p>
        </div>
    </div>

    {{-- Welcome Banner --}}
    <div class="voucher-banner">
        <div class="voucher-banner__glow"></div>
        <img src="{{ asset('img/chanaya-logo.png') }}" alt="Chanaya" class="voucher-banner__logo">
        <div class="voucher-banner__text">
            <h2 class="voucher-banner__title">Dear Dreamers,</h2>
            <h2 class="voucher-banner__subtitle">Welcome to {{ $voucher->property?->name ?? 'Chanaya' }}</h2>
        </div>
    </div>

    {{-- Stay Info --}}
    <div class="mx-3 my-3 p-3 rounded-3 border" style="border-color: var(--border) !important; background: #fff;">
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
        <h3 class="text-center fw-semibold text-dark border-bottom pb-2 mb-3" style="font-size: 0.95rem; border-color: var(--border) !important;">
            Today's Facility Statuses
        </h3>

        <div>
            @php
            // tempat & jam penukaran per facility (kode template)
            $redeemInfo = [
                'SNACK' => [
                    ['place' => 'Soeji Dining', 'time' => 'Dari check in sampai sebelum checkout'],
                ],
                'TEA' => [
                    ['place' => 'Teras Hutan Bambu', 'time' => 'Check in sampai check out · 3:00 PM - 5:00 PM'],
                ],
                'DINNER' => [
                    ['place' => 'Teras Hutan Bambu (Dinner BBQ)', 'time' => '6:30 PM - 8:30 PM'],
                    ['place' => 'Soeji or Rumpun (Dinner 100K)', 'time' => '6:30 PM - 8:30 PM'],
                ],
                'BREAKFAST' => [
                    ['place' => 'Soeji Dining', 'time' => '7:00 AM - 10:00 AM'],
                ],
                'JOURNAL' => [
                    ['place' => 'Rumah Seni', 'time' => '2:00 PM - 5:00 PM'],
                ],
                'FEED' => [
                    ['place' => 'Rumpun Area', 'time' => 'Check in sampai checkout (one time) · 10:00 - 11:45 atau 13:15 - 16:45 (tergantung cuaca)'],
                ],
            ];
            @endphp
            @forelse($facilityStatuses as $facility)
                <div class="mb-3 p-3 rounded-3 border bg-white shadow-sm" style="border-color: var(--border) !important;">
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
                    @foreach($redeemInfo[strtoupper($facility->code)] ?? [] as $info)
                        <div class="d-flex align-items-center gap-2 mb-1" style="font-size: 0.8rem;">
                            <i class="fa-solid fa-location-dot text-muted" style="font-size: 0.7rem;"></i>
                            <span class="text-muted">{{ $info['place'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2" style="font-size: 0.8rem;">
                            <i class="fa-regular fa-clock text-muted" style="font-size: 0.7rem;"></i>
                            <span class="text-danger">{{ $info['time'] }}</span>
                        </div>
                    @endforeach
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
        <div class="qr-card d-inline-block">
            <div class="qr-card__label">
                <i class="fa-solid fa-qrcode"></i>
                <span>Scan to Redeem</span>
            </div>
            <div class="qr-card__frame">
                <x-qr-code :url="$qrImageUrl" :size="400" square />
            </div>
            <p class="qr-card__instruction">
                Please present your barcode to redeem your voucher.
            </p>
        </div>
        <style>
            .qr-card {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                background: #ffffff;
                border: 1px solid var(--border, #e7e1d6);
                border-radius: 20px;
                padding: 22px 26px 20px;
                box-shadow: 0 10px 30px rgba(45, 58, 42, 0.10);
                max-width: 100%;
            }
                        .qr-card__label {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--forest-500, #3d8b64);
                margin-bottom: 16px;
            }
            .qr-card__label i {
                font-size: 0.85rem;
            }
            .qr-card__frame {
                background: #ffffff;
                border-radius: 12px;
                padding: 12px;
                line-height: 0;
                box-shadow: inset 0 0 0 1px rgba(45, 58, 42, 0.06);
            }
            .qr-card__frame img {
                display: block;
                width: min(220px, 64vw);
                height: auto;
                aspect-ratio: 1;
                border-radius: 4px;
            }
            .qr-card__instruction {
                margin: 16px 0 0;
                max-width: 250px;
                font-size: 0.85rem;
                font-weight: 500;
                color: #5b6b58;
                line-height: 1.45;
            }
            @media (prefers-color-scheme: dark) {
                .qr-card {
                    background: #18221a;
                    border-color: rgba(255,255,255,0.08);
                    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
                }
                .qr-card__frame {
                    background: #ffffff;
                    box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05);
                }
                .qr-card__instruction {
                    color: #b9c4b5;
                }
            }
            /* — Welcome Banner — */
            .voucher-banner {
                position: relative;
                overflow: hidden;
                border-radius: 1rem;
                text-align: center;
                padding: 1.5rem 1.25rem;
                background: linear-gradient(135deg, var(--forest-700) 0%, var(--forest-600) 50%, var(--forest-500) 100%);
            }
            .voucher-banner__glow {
                position: absolute;
                inset: 0;
                background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 60%);
                pointer-events: none;
            }
            .voucher-banner__logo {
                position: absolute;
                top: 50%;
                left: 0.75rem;
                transform: translateY(-50%);
                height: 64px;
                width: auto;
                filter: drop-shadow(0 2px 6px rgba(0,0,0,0.35));
            }
            .voucher-banner__text {
                position: relative;
            }
            .voucher-banner__title,
            .voucher-banner__subtitle {
                font-weight: 700;
                color: #fff;
                margin: 0;
                letter-spacing: 0.4px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.25);
                overflow-wrap: anywhere;
            }
            .voucher-banner__title {
                font-size: clamp(1.05rem, 4.2vw, 1.45rem);
                margin-bottom: 0.25rem;
            }
            .voucher-banner__subtitle {
                font-size: clamp(0.95rem, 3.8vw, 1.35rem);
                letter-spacing: 0.3px;
            }
            @media (max-width: 575.98px) {
                .voucher-banner {
                    padding: 1.1rem 0.9rem;
                }
                .voucher-banner__logo {
                    position: static;
                    display: block;
                    margin: 0 auto 10px;
                    height: 48px;
                    transform: none;
                }
            }
        </style>
    </div>

</div>
@endsection