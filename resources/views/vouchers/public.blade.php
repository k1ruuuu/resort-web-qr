@extends('layouts.guest')
@section('title', 'Your Voucher')
@section('content')
<div class="text-center">
    <h1 class="h4">{{ $voucher->facilityTemplate->name }}</h1>
    <p>{{ $voucher->booking->guest->full_name }}</p>
    <p class="text-muted">{{ $voucher->valid_date->format('l, d M Y') }}</p>
    <x-qr-code :url="$qrImageUrl" class="rounded" />
    <p class="mt-3">Quota: {{ $voucher->quota_remaining }}/{{ $voucher->quota_total }}</p>
    <p>Status: <strong>{{ $voucher->status->value }}</strong></p>
</div>
@endsection
