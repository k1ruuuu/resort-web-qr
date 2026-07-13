@extends('layouts.app')
@section('title', 'Rooms')
@section('page_title', 'Rooms')
@section('content')
<div class="mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('rooms.create') }}" class="btn btn-primary btn-responsive">
            <i class="fas fa-plus"></i> Add Room
        </a>
        <a href="{{ route('rooms.import') }}" class="btn btn-outline-primary btn-responsive">
            <i class="fas fa-upload"></i> Import
        </a>
    </div>
</div>

    <div class="card">
        <div class="card-body p-0 table-responsive-stack">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th class="table-col-hide-sm">Label</th>
                        <th>Property</th>
                        <th>Type</th>
                        <th class="table-col-hide-xs">Bed Type</th>
                        <th class="table-col-hide-sm">View</th>
                        <th class="table-col-hide-sm">Location</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rooms as $room)
                    <tr>
                        <td><strong>{{ $room->number }}</strong></td>
                        <td class="table-col-hide-sm">{{ $room->label ?? '—' }}</td>
                        <td>{{ $room->property->name }}</td>
                        <td>{{ $room->roomType->name }}</td>
                        <td class="table-col-hide-xs"><span class="badge bg-secondary">{{ $room->bed_type ?? '—' }}</span></td>
                        <td class="table-col-hide-sm">{{ $room->room_view ?? '—' }}</td>
                        <td class="table-col-hide-sm">{{ $room->location ? 'Zone ' . $room->location : '—' }}</td>
                        <td>{{ $room->capacity }}</td>
                        <td>
                            <span class="badge bg-{{ $room->status === 'available' ? 'success' : ($room->status === 'occupied' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($room->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group-action d-flex gap-1 justify-content-end">
                                <a href="{{ route('rooms.show', $room) }}" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('rooms.edit', $room) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('rooms.destroy', $room) }}" class="d-inline" onsubmit="return confirm('Delete this room?');">
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
                        <td colspan="10" class="text-center py-3 text-muted">No rooms found.</td>
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
