@extends('layouts.app')
@section('title', 'Properties')
@section('page_title', 'Properties')
@section('content')
<div class="mb-3">
    <a href="{{ route('properties.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Property
    </a>
</div>

    <div class="card">
        <div class="card-body p-0 table-responsive-stack">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th class="table-col-hide-sm">Timezone</th>
                        <th class="table-col-hide-sm">Rooms</th>
                        <th class="table-col-hide-sm">Bookings</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($properties as $property)
                    <tr>
                        <td><strong>{{ $property->name }}</strong></td>
                        <td><code>{{ $property->code }}</code></td>
                        <td class="table-col-hide-sm">{{ $property->timezone }}</td>
                        <td class="table-col-hide-sm">{{ $property->rooms_count }}</td>
                        <td class="table-col-hide-sm">{{ $property->bookings_count }}</td>
                        <td>
                            <span class="badge bg-{{ $property->is_active ? 'success' : 'secondary' }}">
                                {{ $property->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group-action d-flex gap-1">
                                <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('properties.destroy', $property) }}" style="display: inline;" onsubmit="return confirm('Delete this property?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-3 text-muted">No properties found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @if($properties->hasPages())
        <div class="card-footer">
            {{ $properties->links() }}
        </div>
    @endif
</div>
@endsection
