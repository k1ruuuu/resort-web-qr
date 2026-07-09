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
                    <label class="form-label">Arrangement Code (optional)</label>
                    <input type="text" name="arrangement_code" class="form-control" placeholder="e.g. RPCGLP26" value="{{ old('arrangement_code') }}">
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
                    @if(!empty($facilityTemplates) && count($facilityTemplates) > 0)
                        <!-- Select All Checkbox -->
                        <div class="form-check mb-2 p-2 bg-light border rounded">
                            <input class="form-check-input" type="checkbox" id="selectAllFacilities">
                            <label class="form-check-label fw-bold text-primary" for="selectAllFacilities">
                                <i class="fas fa-check-double me-2"></i>Select All Facilities
                            </label>
                        </div>
                    @endif
                    
                    @forelse($facilityTemplates as $index => $facility)
                        <div class="form-check">
                            <input class="form-check-input facility-checkbox" type="checkbox" name="facilities[{{ $index }}][facility_template_id]" value="{{ $facility->id }}" id="facility_{{ $facility->id }}">
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

@push('scripts')
<script>
    // Handle "Select All" checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAllFacilities');
    
    if (selectAllCheckbox) {
        const facilityCheckboxes = document.querySelectorAll('.facility-checkbox');
        
        // Handle "Select All" checkbox change
        selectAllCheckbox.addEventListener('change', function() {
            facilityCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
        
        // Handle individual checkbox changes
        facilityCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                // If any checkbox is unchecked, uncheck "Select All"
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // If all checkboxes are checked, check "Select All"
                    const allChecked = Array.from(facilityCheckboxes).every(checkbox => checkbox.checked);
                    if (allChecked) {
                        selectAllCheckbox.checked = true;
                    }
                }
            });
        });
    }
</script>
@endpush
@endsection
