@extends('layouts.app')
@section('title', 'Guest Vouchers')
@section('page_title', 'Guest Vouchers')
@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Stay Dates</th>
                    <th>QR Code Text</th>
                    <th>Secure Token</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($vouchers as $voucher)
                <tr>
                    <td><strong>{{ $voucher->booking->guest->full_name }}</strong></td>
                    <td>{{ $voucher->booking->room_label ?? $voucher->booking->room?->label ?? 'N/A' }}</td>
                    <td>{{ $voucher->booking->check_in->format('Y-m-d') }} – {{ $voucher->booking->check_out->format('Y-m-d') }}</td>
                    <td><small class="text-mono">{{ $voucher->qr_code }}</small></td>
                    <td><small class="text-mono text-muted">{{ substr($voucher->secure_token, 0, 8) }}...</small></td>
                    <td>
                        <span class="badge bg-{{ $voucher->status->value === 'active' ? 'success' : 'secondary' }}">
                            {{ $voucher->status->value }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('vouchers.show', $voucher) }}" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-qrcode"></i> View QR Card
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($vouchers->hasPages())<div class="card-footer">{{ $vouchers->links() }}</div>@endif
</div>
@endsection
