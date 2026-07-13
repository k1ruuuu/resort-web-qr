@extends('layouts.app')
@section('title', 'Outlets')
@section('page_title', 'Outlets')
@section('content')
<div class="mb-3">
    <a href="{{ route('outlets.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Outlet
    </a>
</div>

    <div class="card">
        <div class="card-body p-0 table-responsive-stack">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Property</th>
                        <th class="table-col-hide-sm">Facility</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($outlets as $outlet)
                    <tr>
                        <td><strong>{{ $outlet->name }}</strong></td>
                        <td><code class="text-primary font-weight-bold">{{ $outlet->code }}</code></td>
                        <td>{{ $outlet->property->name }}</td>
                        <td class="table-col-hide-sm">{{ $outlet->facilityTemplate->name }}</td>
                        <td>
                            @if($outlet->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group-action d-flex gap-1">
                                <a href="{{ route('outlets.edit', $outlet) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('outlets.destroy', $outlet) }}" style="display: inline;" onsubmit="return confirm('Delete this outlet?');">
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
                        <td colspan="6" class="text-center py-3 text-muted">No outlets found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @if($outlets->hasPages())
        <div class="card-footer">
            {{ $outlets->links() }}
        </div>
    @endif
</div>
@endsection
