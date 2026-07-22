@extends('layouts.app')
@section('title', 'Digital Guest Voucher')
@section('page_title', 'Digital Guest Voucher')
@section('content')
<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card card-primary card-outline shadow-lg">
            <div class="card-body box-profile text-center">
                <div class="text-center mb-3">
                    <i class="fas fa-id-card fa-3x text-primary"></i>
                </div>
                <h3 class="profile-username text-center font-weight-bold">{{ $voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest') }}</h3>
                <p class="text-muted text-center mb-4">Digital Guest Card</p>

                <div class="p-3 bg-light rounded border mb-4">
                    <x-qr-code :url="$qrImageUrl" :size="240" class="rounded shadow-sm" />
                    <p class="mt-3 mb-0 small text-muted text-monospace">
                        QR Code: <code>{{ substr($voucher->qr_code, 0, 12) }}{{ strlen($voucher->qr_code) > 12 ? '...' : '' }}</code>
                    </p>
                </div>

                <ul class="list-group list-group-unbordered mb-4 text-left">
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Room</b> 
                        <span>{{ $voucher->booking?->room_label ?? $voucher->booking?->room?->label ?? 'Temporary' }} ({{ $voucher->booking?->room?->code ?? 'TEMP' }})</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Stay Dates</b> 
                        <span>{{ $voucher->booking ? $voucher->booking->check_in->format('d M Y') . ' – ' . $voucher->booking->check_out->format('d M Y') : ($voucher->expires_at ? $voucher->expires_at->format('d M Y H:i') : 'N/A') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Total Pax</b> 
                        <span>
                            @if($voucher->booking)
                                {{ $voucher->booking->total_pax }} (+ {{ $voucher->booking->extra_beds }} Extra Bed){{ $voucher->addition ? ' (+' . $voucher->addition . ' Addition)' : '' }} = <strong>{{ $voucher->booking->total_pax + $voucher->booking->extra_beds + ($voucher->addition ?? 0) }} Quota</strong>
                            @else
                                {{ ($voucher->pax_limit ?? 1) + ($voucher->addition ?? 0) }}
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Booking Ref</b> 
                        <span>{{ $voucher->booking?->booking_code ?? $voucher->booking?->reference ?? 'Temporary' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Status</b> 
                        <span class="badge bg-{{ $voucher->status->value === 'active' ? 'success' : 'secondary' }} d-flex align-items-center px-2">{{ $voucher->status->value }}</span>
                    </li>
                </ul>

                <a href="{{ route('vouchers.public', $voucher->secure_token) }}" target="_blank" class="btn btn-primary btn-block">
                    <i class="fas fa-external-link-alt"></i> View Public Guest Card
                </a>
                
                @can('vouchers.resend')
                @if($voucher->booking_id)
                <form method="POST" action="{{ route('bookings.resend', $voucher->booking_id) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-block">
                        <i class="fab fa-whatsapp"></i> Resend Voucher via WhatsApp
                    </button>
                </form>
                @endif
                @endcan

                {{-- 'Add Facilities to Voucher' feature removed per request --}}
            </div>
        </div>
    </div>
</div>
@endsection
