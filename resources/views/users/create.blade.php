@extends('layouts.app')
@section('title', 'New User')
@section('page_title', 'Create New User')
@section('content')

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('users.index') }}">
                <i class="fas fa-users"></i> Users
            </a>
        </li>
        <li class="breadcrumb-item active">Create New</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-10 col-xl-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-user-plus"></i> User Information
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.store') }}" id="createUserForm">
                    @csrf
                    
                    <!-- Personal Information Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-id-card text-primary"></i> Personal Information
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           name="name" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" 
                                           placeholder="Enter full name"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           name="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           value="{{ old('email') }}" 
                                           placeholder="user@example.com"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-lock text-warning"></i> Security
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" 
                                           name="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           placeholder="Minimum 8 characters"
                                           id="password"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="togglePassword"></i>
                                    </button>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Must be at least 8 characters
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Confirm Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" 
                                           name="password_confirmation" 
                                           class="form-control" 
                                           placeholder="Re-enter password"
                                           id="password_confirmation"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="togglePassword('password_confirmation')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Status Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-toggle-on text-success"></i> Account Status
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>
                                        <i class="fas fa-check"></i> Active
                                    </option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>
                                        Inactive
                                    </option>
                                </select>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Inactive users cannot log in
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Roles Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-user-tag text-info"></i> Role Assignment
                        </h5>
                        @forelse($roles as $role)
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="roles[]" 
                                       value="{{ $role->name }}" 
                                       id="role_{{ $role->id }}" 
                                       {{ is_array(old('roles')) && in_array($role->name, old('roles')) ? 'checked' : '' }}
                                       style="width: 3em; height: 1.5em;">
                                <label class="form-check-label ms-2" for="role_{{ $role->id }}">
                                    <strong>{{ $role->name }}</strong>
                                    @if($role->permissions->count() > 0)
                                        <small class="text-muted d-block">
                                            {{ $role->permissions->count() }} permissions
                                        </small>
                                    @endif
                                </label>
                            </div>
                        @empty
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                No roles available. Please create roles first.
                            </div>
                        @endforelse
                    </div>

                    <!-- Form Actions -->
                    <div class="border-top pt-3 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save"></i> Create User
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary px-4">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
});
</script>
@endpush
@endsection
