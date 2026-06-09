@extends('layouts.app')
@section('title', 'Bookings')
@section('page_title', 'Bookings')
@section('content')
<div class="mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    @can('bookings.create')
    <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-responsive">
        <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">New</span> Booking
    </a>
    @else
    <div></div>
    @endcan
    
    <button class="btn btn-outline-secondary btn-responsive filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
        <i class="fas fa-filter"></i> Filters
    </button>
</div>

<!-- Search & Filter Form -->
<div class="collapse filter-collapse mb-3 {{ request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to']) ? 'show' : '' }}" id="filterCollapse">
    <div class="card card-responsive">
        <div class="card-body">
            <form method="GET" action="{{ route('bookings.index') }}">
                <div class="row g-3 form-row-responsive">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Name, email, room..."
                               value="{{ request('search') }}">
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Checked In</option>
                            <option value="checked_out" {{ request('status') === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">Property</label>
                        <select name="property_id" class="form-select">
                            <option value="">All</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                                    {{ $property->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" 
                               name="date_from" 
                               class="form-control" 
                               value="{{ request('date_from') }}">
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" 
                               name="date_to" 
                               class="form-control" 
                               value="{{ request('date_to') }}">
                    </div>
                    
                    <div class="col-12 col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i><span class="d-none d-lg-inline ms-1">Go</span>
                        </button>
                    </div>
                </div>
                
                @if(request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to']))
                <div class="mt-2">
                    <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>

<div class="card card-responsive">
    <div class="card-body p-0">
        <div class="table-responsive overflow-auto-mobile">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Guest</th>
                        <th class="d-none d-md-table-cell">Property</th>
                        <th class="d-none d-lg-table-cell">Stay</th>
                        <th class="d-none d-sm-table-cell">Pax</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($bookings as $booking)
                    <tr>
                        <td><strong class="text-truncate d-inline-block" style="max-width: 100px;">{{ $booking->reference }}</strong></td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $booking->guest->full_name }}">
                                {{ $booking->guest->full_name }}
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="text-truncate" style="max-width: 120px;" title="{{ $booking->property->name }}">
                                {{ $booking->property->name }}
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <small>{{ $booking->check_in->format('M d') }} – {{ $booking->check_out->format('M d') }}</small>
                        </td>
                        <td class="d-none d-sm-table-cell">{{ $booking->total_pax }}</td>
                        <td>
                            <span class="badge bg-{{ $booking->status->value === 'pending' ? 'warning' : 'success' }} text-white">
                                <span class="d-none d-sm-inline">{{ $booking->status->value }}</span>
                                <span class="d-inline d-sm-none">{{ substr($booking->status->value, 0, 1) }}</span>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('bookings.destroy', $booking) }}" class="d-inline" onsubmit="return confirm('Delete this booking?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            @if(request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to']))
                                No bookings found matching your filters.
                            @else
                                No bookings found.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($bookings->hasPages())
    <div class="card-footer">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
