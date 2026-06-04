@extends('layouts.app')
@section('title', 'Facilities')
@section('page_title', 'Facilities')
@section('content')
<div class="mb-3">
    <a href="{{ route('facilities.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Facility
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Property</th>
                    <th>Sort Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($facilities as $facility)
                <tr>
                    <td><strong>{{ $facility->name }}</strong></td>
                    <td><code class="text-primary font-weight-bold">{{ $facility->code }}</code></td>
                    <td>{{ $facility->property->name }}</td>
                    <td>{{ $facility->sort_order }}</td>
                    <td>
                        @if($facility->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('facilities.edit', $facility) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('facilities.destroy', $facility) }}" style="display: inline;" onsubmit="return confirm('Delete this facility?');">
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
                    <td colspan="6" class="text-center py-3 text-muted">No facilities found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($facilities->hasPages())
        <div class="card-footer">
            {{ $facilities->links() }}
        </div>
    @endif
</div>
@endsection
