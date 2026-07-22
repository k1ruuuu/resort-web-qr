@extends('layouts.app')
@section('title', 'New Outlet')
@section('page_title', 'New Outlet')
@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('outlets.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Property</label>
                    <select name="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                        <option value="">Select Property...</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ old('property_id') == $property->id ? 'selected' : '' }}>
                                {{ $property->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('property_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Facilities <small class="text-muted">(select one or more)</small></label>
                    <div class="border rounded p-3 @error('facility_template_ids') border-danger @enderror" style="max-height: 200px; overflow-y: auto;">
                        @forelse($facilityTemplates as $template)
                            <div class="form-check">
                                <input type="checkbox"
                                       name="facility_template_ids[]"
                                       value="{{ $template->id }}"
                                       class="form-check-input"
                                       id="ft-{{ $template->id }}"
                                       {{ in_array($template->id, old('facility_template_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ft-{{ $template->id }}">
                                    {{ $template->name }} <code>({{ $template->code }})</code>
                                </label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No active facilities available.</p>
                        @endforelse
                    </div>
                    @error('facility_template_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('facility_template_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Poolside Bar" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. POOLBAR" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Outlet</button>
                <a href="{{ route('outlets.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
