@extends('layouts.app')
@section('title', 'Guests')
@section('page_title', 'Guests')
@section('content')
<div class="mb-3">
    <a href="{{ route('guests.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Guest
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Document ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($guests as $guest)
                <tr>
                    <td><strong>{{ $guest->full_name }}</strong></td>
                    <td>{{ $guest->email ?? '—' }}</td>
                    <td>{{ $guest->phone ?? '—' }}</td>
                    <td><small>{{ $guest->document_id ?? '—' }}</small></td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('guests.show', $guest) }}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('guests.edit', $guest) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('guests.destroy', $guest) }}" style="display: inline;" onsubmit="return confirm('Delete this guest?');">
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
                    <td colspan="5" class="text-center py-3 text-muted">No guests found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($guests->hasPages())
        <div class="card-footer">
            {{ $guests->links() }}
        </div>
    @endif
</div>
@endsection
