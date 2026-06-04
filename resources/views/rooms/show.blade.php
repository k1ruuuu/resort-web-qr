@extends('layouts.app')
@section('title', $room->label)
@section('page_title', 'Room: ' . $room->label)
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Room Details</h3>
                <div class="card-tools">
                    <a href="{{ route('rooms.edit', $room) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('rooms.destroy', $room) }}" style="display: inline;" onsubmit="return confirm('Delete this room?');">
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
                        <td class="fw-bold">Number:</td>
                        <td>{{ $room->number }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Label:</td>
                        <td>{{ $room->label }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Code:</td>
                        <td><code>{{ $room->code ?? '—' }}</code></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Property:</td>
                        <td>
                            <a href="{{ route('properties.show', $room->property) }}">{{ $room->property->name }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Type:</td>
                        <td>{{ $room->roomType->name }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Area:</td>
                        <td>{{ $room->area?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Capacity:</td>
                        <td>{{ $room->capacity }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Status:</td>
                        <td>
                            <span class="badge bg-{{ $room->status === 'available' ? 'success' : ($room->status === 'occupied' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($room->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="mt-3">
    <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Back to Rooms</a>
</div>
@endsection
