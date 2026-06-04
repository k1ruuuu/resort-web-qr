@extends('layouts.app')
@section('title', 'Edit Booking')
@section('page_title', 'Edit Booking: ' . $booking->reference)
@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('bookings.update', $booking) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Guest</label>
                        <select name="guest_id" class="form-select @error('guest_id') is-invalid @enderror" required>
                            <option value="">Select guest...</option>
                            @foreach($guests as $guest)
                                <option value="{{ $guest->id }}" @if(old('guest_id', $booking->guest_id) == $guest->id) selected @endif>{{ $guest->full_name }}</option>
                            @endforeach
                        </select>
                        @error('guest_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Property</label>
                        <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                            <option value="">Select property...</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" @if(old('property_id', $booking->property_id) == $property->id) selected @endif>{{ $property->name }}</option>
                            @endforeach
                        </select>
                        @error('property_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Check-in</label>
                        <input type="date" name="check_in" class="form-control @error('check_in') is-invalid @enderror" value="{{ old('check_in', $booking->check_in->format('Y-m-d')) }}" required>
                        @error('check_in')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Check-out</label>
                        <input type="date" name="check_out" class="form-control @error('check_out') is-invalid @enderror" value="{{ old('check_out', $booking->check_out->format('Y-m-d')) }}" required>
                        @error('check_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" class="form-control @error('adults') is-invalid @enderror" value="{{ old('adults', $booking->adults) }}">
                        @error('adults')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Children</label>
                        <input type="number" name="children" class="form-control @error('children') is-invalid @enderror" value="{{ old('children', $booking->children) }}">
                        @error('children')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Extra Beds</label>
                        <input type="number" name="extra_beds" class="form-control @error('extra_beds') is-invalid @enderror" value="{{ old('extra_beds', $booking->extra_beds) }}">
                        @error('extra_beds')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Nights</label>
                        <input type="number" name="nights" class="form-control @error('nights') is-invalid @enderror" value="{{ old('nights', $booking->nights) }}" min="1">
                        @error('nights')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Total Pax</label>
                        <input type="number" name="total_pax" class="form-control @error('total_pax') is-invalid @enderror" value="{{ old('total_pax', $booking->total_pax) }}">
                        @error('total_pax')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">PMS Voucher Reference</label>
                        <input type="text" name="pms_voucher_ref" class="form-control @error('pms_voucher_ref') is-invalid @enderror" value="{{ old('pms_voucher_ref', $booking->pms_voucher_ref) }}">
                        @error('pms_voucher_ref')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="pending" @if(old('status', $booking->status->value) === 'pending') selected @endif>Pending</option>
                    <option value="checked_in" @if(old('status', $booking->status->value) === 'checked_in') selected @endif>Checked In</option>
                    <option value="checked_out" @if(old('status', $booking->status->value) === 'checked_out') selected @endif>Checked Out</option>
                    <option value="cancelled" @if(old('status', $booking->status->value) === 'cancelled') selected @endif>Cancelled</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Booking Code (PMS)</label>
                        <input type="text" name="booking_code" class="form-control @error('booking_code') is-invalid @enderror" value="{{ old('booking_code', $booking->booking_code) }}">
                        @error('booking_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference', $booking->reference) }}" required>
                        @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Source</label>
                        <input type="text" name="source" class="form-control @error('source') is-invalid @enderror" value="{{ old('source', $booking->source) }}">
                        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Room Label</label>
                        <input type="text" name="room_label" class="form-control @error('room_label') is-invalid @enderror" value="{{ old('room_label', $booking->room_label) }}">
                        @error('room_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Expected Arrival</label>
                        <input type="date" name="expected_arrival" class="form-control @error('expected_arrival') is-invalid @enderror" value="{{ old('expected_arrival', $booking->expected_arrival?->format('Y-m-d')) }}">
                        @error('expected_arrival')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Expected Departure</label>
                        <input type="date" name="expected_departure" class="form-control @error('expected_departure') is-invalid @enderror" value="{{ old('expected_departure', $booking->expected_departure?->format('Y-m-d')) }}">
                        @error('expected_departure')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Update Booking</button>
                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
