@extends('layouts.app')
@section('title', 'Create Room')
@section('page_title', 'Create Room')
@section('content')
<div class="card col-md-6">
    <div class="card-body">
        <form method="POST" action="{{ route('rooms.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Property</label>
                <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required onchange="loadRoomTypes()">
                    <option value="">Select property...</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @if(old('property_id') === (string)$property->id) selected @endif>{{ $property->name }}</option>
                    @endforeach
                </select>
                @error('property_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Number</label>
                <input type="text" name="number" class="form-control @error('number') is-invalid @enderror" value="{{ old('number') }}" required>
                @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Label</label>
                <input type="text" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label') }}">
                @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Room Type</label>
                <select name="room_type_id" id="room_type_id" class="form-select @error('room_type_id') is-invalid @enderror" required>
                    <option value="">Select room type...</option>
                </select>
                @error('room_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Area</label>
                <select name="area_id" id="area_id" class="form-select @error('area_id') is-invalid @enderror">
                    <option value="">Select area...</option>
                </select>
                @error('area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', 2) }}" min="1">
                        @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="available" @if(old('status') === 'available') selected @endif>Available</option>
                            <option value="occupied" @if(old('status') === 'occupied') selected @endif>Occupied</option>
                            <option value="maintenance" @if(old('status') === 'maintenance') selected @endif>Maintenance</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function loadRoomTypes() {
    const propertyId = document.querySelector('select[name="property_id"]').value;
    if (!propertyId) return;
    
    fetch(`/properties/${propertyId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
    });
}
</script>
@endsection
