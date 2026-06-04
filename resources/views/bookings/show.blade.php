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
                <p><strong>Quota basis:</strong> room capacity + {{ $booking->extra_beds }} extra bed(s)</p>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Facilities</div>
            <ul class="list-group list-group-flush">
                @forelse($booking->bookingFacilities as $bf)
                    <li class="list-group-item">
                        {{ $bf->facilityTemplate->name }} (quota {{ $bf->quota_total }})
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
        @if($booking->status->value === 'checked_in')
        <form method="POST" action="{{ route('vouchers.generate') }}">
            @csrf
            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
            <button class="btn btn-primary w-100">Generate Today Vouchers</button>
        </form>
        @endif
        @endcan
    </div>
</div>
@if($booking->dailyVouchers->isNotEmpty())
<div class="card mt-3">
    <div class="card-header">Vouchers</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Date</th><th>Facility</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($booking->dailyVouchers as $voucher)
                <tr>
                    <td>{{ $voucher->valid_date->format('Y-m-d') }}</td>
                    <td>{{ $voucher->facilityTemplate->name }}</td>
                    <td>{{ $voucher->status->value }}</td>
                    <td><a href="{{ route('vouchers.show', $voucher) }}">QR</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
