@extends('layouts.app')
@section('title', $property->name)
@section('page_title', $property->name)
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Property Details</h3>
                <div class="card-tools">
                    <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('properties.destroy', $property) }}" style="display: inline;" onsubmit="return confirm('Delete this property?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="fw-bold">Name:</td>
                        <td>{{ $property->name }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Code:</td>
                        <td><code>{{ $property->code }}</code></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Timezone:</td>
                        <td>{{ $property->timezone }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Address:</td>
                        <td>{{ $property->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Status:</td>
                        <td>
                            <span class="badge bg-{{ $property->is_active ? 'success' : 'secondary' }}">
                                {{ $property->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Rooms:</td>
                        <td>{{ $property->rooms_count }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Bookings:</td>
                        <td>{{ $property->bookings_count }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="mt-3">
    <a href="{{ route('properties.index') }}" class="btn btn-secondary">Back to Properties</a>
</div>
@endsection
