@extends('layouts.app')
@section('title', 'Rooms')
@section('page_title', 'Rooms')
@section('content')
<div class="mb-3">
    <a href="{{ route('rooms.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Room
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Label</th>
                    <th>Code</th>
                    <th>Property</th>
                    <th>Type</th>
                    <th>Area</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rooms as $room)
                <tr>
                    <td><strong>{{ $room->number }}</strong></td>
                    <td>{{ $room->label ?? '—' }}</td>
                    <td><code>{{ $room->code ?? '—' }}</code></td>
                    <td>{{ $room->property->name }}</td>
                    <td>{{ $room->roomType->name }}</td>
                    <td>{{ $room->area?->name ?? '—' }}</td>
                    <td>{{ $room->capacity }}</td>
                    <td>
                        <span class="badge bg-{{ $room->status === 'available' ? 'success' : ($room->status === 'occupied' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($room->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('rooms.show', $room) }}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('rooms.edit', $room) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('rooms.destroy', $room) }}" style="display: inline;" onsubmit="return confirm('Delete this room?');">
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
                    <td colspan="9" class="text-center py-3 text-muted">No rooms found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($rooms->hasPages())
        <div class="card-footer">
            {{ $rooms->links() }}
        </div>
    @endif
</div>
@endsection
