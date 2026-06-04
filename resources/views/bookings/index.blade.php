@extends('layouts.app')
@section('title', 'Bookings')
@section('page_title', 'Bookings')
@section('content')
<div class="mb-3">
    @can('bookings.create')
    <a href="{{ route('bookings.create') }}" class="btn btn-primary">New Booking</a>
    @endcan
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Property</th>
                    <th>Stay</th>
                    <th>Pax</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td><strong>{{ $booking->reference }}</strong></td>
                    <td>{{ $booking->guest->full_name }}</td>
                    <td>{{ $booking->property->name }}</td>
                    <td>{{ $booking->check_in->format('Y-m-d') }} – {{ $booking->check_out->format('Y-m-d') }}</td>
                    <td>{{ $booking->total_pax }}</td>
                    <td><span class="badge bg-{{ $booking->status->value === 'pending' ? 'warning' : 'success' }}">{{ $booking->status->value }}</span></td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('bookings.destroy', $booking) }}" style="display: inline;" onsubmit="return confirm('Delete this booking?');">
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
                    <td colspan="8" class="text-center py-3 text-muted">No bookings found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($bookings->hasPages())
    <div class="card-footer">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
