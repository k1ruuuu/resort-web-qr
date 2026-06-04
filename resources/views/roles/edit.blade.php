@extends('layouts.app')
@section('title', 'Edit Role')
@section('page_title', 'Edit Role: ' . $role->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Role Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 mt-4">
                    <label class="form-label d-block font-weight-bold">Permissions</label>
                    <div class="row">
                        @forelse($permissions as $permission)
                            <div class="col-md-4 col-sm-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}" 
                                        {{ (is_array(old('permissions')) && in_array($permission->name, old('permissions'))) || (!is_array(old('permissions')) && $role->hasPermissionTo($permission->name)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                        {{ $permission->name }}
                                    </label>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small col-12">No permissions configured.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
