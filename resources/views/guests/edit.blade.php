@extends('layouts.app')
@section('title', 'Edit ' . $guest->full_name)
@section('page_title', 'Edit Guest: ' . $guest->full_name)
@section('content')
<div class="card col-md-6">
    <div class="card-body">
        <form method="POST" action="{{ route('guests.update', $guest) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $guest->first_name) }}" required>
                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $guest->last_name) }}" required>
                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $guest->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $guest->phone) }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Document ID</label>
                <input type="text" name="document_id" class="form-control @error('document_id') is-invalid @enderror" value="{{ old('document_id', $guest->document_id) }}">
                @error('document_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('guests.show', $guest) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
