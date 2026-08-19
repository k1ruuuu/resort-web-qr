@extends('layouts.app')
@section('title', 'Import Log Details')
@section('page_title', 'Import Log Details')
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-info-circle text-muted me-2"></i> Summary
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered m-0">
                    <tr>
                        <th class="w-25">Type</th>
                        <td><span class="badge bg-info">{{ ucfirst($log->type) }}</span></td>
                    </tr>
                    <tr>
                        <th>File</th>
                        <td class="small">{{ $log->filename }}</td>
                    </tr>
                    <tr>
                        <th>Imported By</th>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                    </tr>
                    <tr>
                        <th>Date/Time</th>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($log->status === 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif($log->status === 'partial')
                                <span class="badge bg-warning text-dark">Partial</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-chart-simple text-muted me-2"></i> Statistics
                </h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 col-md-3">
                        <div class="fs-3 fw-bold">{{ $log->total_rows }}</div>
                        <div class="small text-muted">Total Rows</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fs-3 fw-bold text-success">{{ $log->imported }}</div>
                        <div class="small text-muted">Imported</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fs-3 fw-bold text-warning">{{ $log->skipped }}</div>
                        <div class="small text-muted">Skipped</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fs-3 fw-bold text-danger">{{ $log->failed }}</div>
                        <div class="small text-muted">Failed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($log->errors)
<div class="card shadow-sm mt-3">
    <div class="card-header">
        <h3 class="card-title font-weight-bold mb-0">
            <i class="fas fa-exclamation-triangle text-danger me-2"></i> Error Details
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Row</th>
                        <th>Attribute</th>
                        <th>Error</th>
                        <th>Values</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($log->errors as $i => $error)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $error['row'] ?? 'N/A' }}</td>
                            <td>
                                @if(isset($error['attribute']))
                                    <code>{{ $error['attribute'] }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($error['errors']) && is_array($error['errors']))
                                    {{ implode('; ', $error['errors']) }}
                                @elseif(isset($error['message']))
                                    {{ $error['message'] }}
                                @else
                                    {{ json_encode($error) }}
                                @endif
                            </td>
                            <td class="small text-muted">
                                @if(isset($error['values']))
                                    {{ is_array($error['values']) ? json_encode($error['values']) : $error['values'] }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="mt-3">
    <a href="{{ route('import-logs.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Import Logs
    </a>
</div>
@endsection
