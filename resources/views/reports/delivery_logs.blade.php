@extends('layouts.app')
@section('title', 'WhatsApp Delivery Logs')
@section('page_title', 'WhatsApp Delivery Logs')
@section('content')
<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold mb-0">
            <i class="fas fa-list text-muted me-2"></i> WhatsApp Message Logs
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Guest</th>
                        <th>Phone</th>
                        <th>Message Content</th>
                        <th>QR Code URL</th>
                        <th>Status</th>
                        <th>Sent At</th>
                        <th>API Response</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                @if($log->scheduled_at)
                                    <span class="text-muted d-block small">Scheduled:</span>
                                    <span>{{ $log->scheduled_at->format('Y-m-d H:i') }}</span>
                                @else
                                    <span class="badge bg-light text-dark border">Immediate</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $log->guest->full_name }}</strong>
                                <span class="d-block small text-muted">Booking: {{ $log->booking->booking_code ?? $log->booking->reference }}</span>
                            </td>
                            <td><code class="text-dark">{{ $log->phone_number }}</code></td>
                            <td>
                                <a href="#" class="small" data-bs-toggle="collapse" data-bs-target="#msg-{{ $log->id }}">
                                    View Message ({{ strlen($log->message_content) }} chars)
                                </a>
                                <div id="msg-{{ $log->id }}" class="collapse mt-1 p-2 bg-light border rounded small" style="white-space: pre-wrap;">{{ $log->message_content }}</div>
                            </td>
                            <td>
                                @if($log->qr_path)
                                    <a href="{{ $log->qr_path }}" target="_blank" class="small text-truncate d-inline-block" style="max-width: 150px;">
                                        {{ basename($log->qr_path) }}
                                    </a>
                                @else
                                    <span class="text-muted small">None</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $log->delivery_status === 'sent' ? 'success' : ($log->delivery_status === 'failed' ? 'danger' : 'warning') }} px-2 py-1">
                                    {{ $log->delivery_status }}
                                </span>
                            </td>
                            <td>
                                @if($log->sent_at)
                                    <span>{{ $log->sent_at->format('Y-m-d H:i:s') }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if($log->provider_response)
                                    <a href="#" class="small text-monospace" data-bs-toggle="collapse" data-bs-target="#resp-{{ $log->id }}">
                                        Show Raw
                                    </a>
                                    <div id="resp-{{ $log->id }}" class="collapse mt-1 p-2 bg-dark text-light border rounded small font-monospace" style="max-width: 250px; overflow-x: auto;">
                                        {{ $log->provider_response }}
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No delivery logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
