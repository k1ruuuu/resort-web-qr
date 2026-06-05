@extends('layouts.app')
@section('title', 'Booking '.$booking->reference)
@section('page_title', 'Booking '.$booking->reference)
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Booking Details</h3>
                <div class="card-tools">
                    <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('bookings.destroy', $booking) }}" style="display: inline;" onsubmit="return confirm('Delete this booking?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Guest:</strong> {{ $booking->guest->full_name }}</p>
                <p><strong>Property:</strong> {{ $booking->property->name }}</p>
                <p><strong>Stay:</strong> {{ $booking->check_in->format('Y-m-d') }} – {{ $booking->check_out->format('Y-m-d') }}</p>
                <p><strong>Pax:</strong> {{ $booking->total_pax }}</p>
                <p><strong>Status:</strong> {{ $booking->status->value }}</p>
                @if($booking->booking_code)<p><strong>PMS Code:</strong> {{ $booking->booking_code }}</p>@endif
                @if($booking->room_label)<p><strong>Room:</strong> {{ $booking->room_label }}</p>@endif
                <p><strong>Quota basis:</strong> total pax ({{ $booking->total_pax }}) + {{ $booking->extra_beds }} extra bed(s) = {{ $booking->total_pax + $booking->extra_beds }} quota</p>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Facilities</div>
            <ul class="list-group list-group-flush">
                @forelse($booking->bookingFacilities as $bf)
                    <li class="list-group-item">
                        {{ $bf->facilityTemplate->name }}
                        <span class="text-muted small">— {{ $bf->start_date->format('Y-m-d') }} to {{ $bf->end_date->format('Y-m-d') }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-warning">
                        No facilities linked. Check in again to auto-attach property facilities, or recreate the booking with facilities selected.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        @can('bookings.checkin')
        @if($booking->status->value !== 'checked_in')
        <form method="POST" action="{{ route('bookings.check-in', $booking) }}" class="mb-2">
            @csrf
            <button class="btn btn-success w-100">Check In</button>
        </form>
        @endif
        @endcan
        @can('vouchers.generate')
        @if($booking->status->value === 'checked_in' && !$booking->guestVoucher)
        <form method="POST" action="{{ route('vouchers.generate') }}">
            @csrf
            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
            <button class="btn btn-primary w-100">Generate Guest Pass</button>
        </form>
        @endif
        @endcan
        @can('vouchers.resend')
        @if($booking->status->value === 'checked_in' && $booking->guestVoucher)
        <form method="POST" action="{{ route('bookings.resend', $booking) }}" class="mt-2">
            @csrf
            <button class="btn btn-warning w-100">
                <i class="fab fa-whatsapp"></i> Resend Voucher
            </button>
        </form>
        @endif
        @endcan
    </div>
</div>
@if($booking->guestVoucher)
<div class="card mt-3">
    <div class="card-header font-weight-bold bg-primary text-white">Active Guest Stay Pass</div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <x-qr-code :url="route('vouchers.qr', $booking->guestVoucher)" :size="150" class="rounded border bg-white p-1" />
            </div>
            <div class="col-md-8">
                <p class="mb-1"><strong>QR Code Text:</strong> <code class="text-dark">{{ $booking->guestVoucher->qr_code }}</code></p>
                <p class="mb-1"><strong>Secure Token:</strong> <code class="text-muted">{{ $booking->guestVoucher->secure_token }}</code></p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">{{ $booking->guestVoucher->status->value }}</span></p>
                <p class="mb-0 mt-2">
                    <a href="{{ route('vouchers.show', $booking->guestVoucher) }}" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-eye"></i> View Card Details
                    </a>
                    <a href="{{ route('vouchers.public', $booking->guestVoucher->secure_token) }}" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-external-link-alt"></i> Open Public Link
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
