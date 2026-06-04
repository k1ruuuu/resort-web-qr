@extends('layouts.app')
@section('title', 'Voucher QR')
@section('page_title', 'Voucher QR')
@section('content')
<div class="card">
    <div class="card-body text-center">
        <p>{{ $voucher->booking->guest->full_name }} — {{ $voucher->facilityTemplate->name }}</p>
        <p class="text-muted">{{ $voucher->valid_date->format('l, d M Y') }}</p>
        <x-qr-code :url="$qrImageUrl" />
        <p class="mt-2 small text-muted">QR: <code>{{ $voucher->qr_code }}</code></p>
        @if($voucher->public_token)
        <p><a href="{{ route('vouchers.public', $voucher->public_token) }}" target="_blank">Public link</a></p>
        @endif
    </div>
</div>
@endsection
