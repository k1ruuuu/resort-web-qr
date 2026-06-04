@extends('layouts.app')
@section('title', 'Create Property')
@section('page_title', 'Create Property')
@section('content')
<div class="card col-md-6">
    <div class="card-body">
        <form method="POST" action="{{ route('properties.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Timezone</label>
                <select name="timezone" class="form-select @error('timezone') is-invalid @enderror" required>
                    <option value="">Select timezone...</option>
                    @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" @if(old('timezone') === $tz) selected @endif>{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address') }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @if(old('is_active', true)) checked @endif>
                    <label class="form-check-label">Active</label>
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('properties.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
