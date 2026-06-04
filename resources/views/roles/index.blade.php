@extends('layouts.app')
@section('title', 'Roles')
@section('page_title', 'Roles')
@section('content')
<div class="mb-3">
    <a href="{{ route('roles.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Role
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Role Name</th>
                    <th>Permissions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td><strong>{{ $role->name }}</strong></td>
                    <td>
                        @forelse($role->permissions as $permission)
                            <span class="badge bg-secondary mb-1">{{ $permission->name }}</span>
                        @empty
                            <span class="text-muted small">No permissions assigned</span>
                        @endforelse
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($role->name !== 'admin')
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" style="display: inline;" onsubmit="return confirm('Delete this role?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @else
                                <button class="btn btn-sm btn-danger disabled" title="Delete (Core role)" disabled>
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center py-3 text-muted">No roles found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($roles->hasPages())
        <div class="card-footer">
            {{ $roles->links() }}
        </div>
    @endif
</div>
@endsection
