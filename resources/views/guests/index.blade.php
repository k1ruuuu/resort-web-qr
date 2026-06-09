@extends('layouts.app')
@section('title', 'Guests')
@section('page_title', 'Guests')
@section('content')
<div class="mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    <a href="{{ route('guests.create') }}" class="btn btn-primary btn-responsive">
        <i class="fas fa-plus"></i> Add Guest
    </a>
    
    <form method="GET" action="{{ route('guests.index') }}" class="d-flex gap-2 w-100" style="max-width: 400px;">
        <input type="text" 
               name="search" 
               class="form-control" 
               placeholder="Search guests..."
               value="{{ request('search') }}">
        <button type="submit" class="btn btn-primary flex-shrink-0">
            <i class="fas fa-search"></i>
        </button>
        @if(request()->filled('search'))
        <a href="{{ route('guests.index') }}" class="btn btn-secondary flex-shrink-0" title="Clear">
            <i class="fas fa-times"></i>
        </a>
        @endif
    </form>
</div>

<div class="card card-responsive">
    <div class="card-body p-0">
        <div class="table-responsive overflow-auto-mobile">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="d-none d-md-table-cell">Email</th>
                        <th class="d-none d-lg-table-cell">Phone</th>
                        <th class="d-none d-xl-table-cell">Document ID</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($guests as $guest)
                    <tr>
                        <td>
                            <strong>{{ $guest->full_name }}</strong>
                            <div class="d-md-none">
                                <small class="text-muted">
                                    {{ $guest->email ?? $guest->phone ?? '—' }}
                                </small>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="text-truncate" style="max-width: 200px;" title="{{ $guest->email }}">
                                {{ $guest->email ?? '—' }}
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell">{{ $guest->phone ?? '—' }}</td>
                        <td class="d-none d-xl-table-cell">
                            <small>{{ $guest->document_id ?? '—' }}</small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('guests.show', $guest) }}" class="btn btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('guests.edit', $guest) }}" class="btn btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('guests.destroy', $guest) }}" class="d-inline" onsubmit="return confirm('Delete this guest?');">
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
                        <td colspan="5" class="text-center py-4 text-muted">
                            @if(request()->filled('search'))
                                No guests found matching "{{ request('search') }}".
                            @else
                                No guests found.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($guests->hasPages())
        <div class="card-footer">
            {{ $guests->links() }}
        </div>
    @endif
</div>
@endsection
