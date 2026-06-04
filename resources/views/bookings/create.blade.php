@extends('layouts.app')
@section('title', 'New Booking')
@section('page_title', 'New Booking')
@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('bookings.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Property</label>
                    <select name="property_id" class="form-select" required>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Guest</label>
                    <select name="guest_id" class="form-select" required>
                        @foreach($guests as $guest)
                            <option value="{{ $guest->id }}">{{ $guest->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Room</label>
                    <select name="room_id" class="form-select @error('room_id') is-invalid @enderror">
                        <option value="">Select room...</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @if(old('room_id') == $room->id) selected @endif>
                                {{ $room->number }} ({{ $room->property->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Check-in</label>
                    <input type="date" name="check_in" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Check-out</label>
                    <input type="date" name="check_out" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Adults</label>
                    <input type="number" name="adults" value="1" min="1" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Children</label>
                    <input type="number" name="children" value="0" min="0" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Extra beds</label>
                    <input type="number" name="extra_beds" value="0" min="0" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Facilities (optional)</label>
                    @forelse($facilityTemplates as $index => $facility)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="facilities[{{ $index }}][facility_template_id]" value="{{ $facility->id }}" id="facility_{{ $facility->id }}">
                            <label class="form-check-label" for="facility_{{ $facility->id }}">{{ $facility->name }}</label>
                        </div>
                    @empty
                        <p class="text-muted small">No facility templates configured.</p>
                    @endforelse
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Booking</button>
            </div>
        </form>
    </div>
</div>
@endsection
