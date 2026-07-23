@extends('layouts.app')
@section('title', 'Import Logs')
@section('page_title', 'Import Logs')
@section('content')
<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold mb-0">
            <i class="fas fa-file-import text-muted me-2"></i> Data Import History
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Type</th>
                        <th>File</th>
                        <th>By</th>
                        <th>Total Rows</th>
                        <th>Imported</th>
                        <th>Skipped</th>
                        <th>Failed</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($log->type) }}</span>
                            </td>
                            <td class="small text-muted" title="{{ $log->filename }}">{{ \Illuminate\Support\Str::limit($log->filename, 30) }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td>{{ $log->total_rows }}</td>
                            <td class="text-success fw-bold">{{ $log->imported }}</td>
                            <td class="text-warning fw-bold">{{ $log->skipped }}</td>
                            <td class="text-danger fw-bold">{{ $log->failed }}</td>
                            <td>
                                @if($log->status === 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($log->status === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                @endif
                            </td>
                            <td>
                                @if($log->errors)
                                    <a href="{{ route('import-logs.show', $log) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                No import logs yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
</div>
@endsection
