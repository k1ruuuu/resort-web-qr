@extends('layouts.app')
@section('title', 'Vouchers')
@section('page_title', 'Vouchers')
@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Guest</th>
                    <th>Facility</th>
                    <th>Quota</th>
                    <th>QR Code</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($vouchers as $voucher)
                <tr>
                    <td>{{ $voucher->valid_date->format('Y-m-d') }}</td>
                    <td>{{ $voucher->booking->guest->full_name }}</td>
                    <td>{{ $voucher->facilityTemplate->name }}</td>
                    <td>{{ $voucher->quota_remaining }}/{{ $voucher->quota_total }}</td>
                    <td><small>{{ $voucher->qr_code }}</small></td>
                    <td>{{ $voucher->status->value }}</td>
                    <td><a href="{{ route('vouchers.show', $voucher) }}" class="btn btn-sm btn-outline-primary">View QR</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($vouchers->hasPages())<div class="card-footer">{{ $vouchers->links() }}</div>@endif
</div>
@endsection
