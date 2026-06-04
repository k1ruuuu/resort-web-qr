@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $bookingCount }}</h3>
                <p>Total Bookings</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $activeVouchers }}</h3>
                <p>Active Vouchers</p>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">Voucher Status</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Status</th><th>Count</th></tr></thead>
            <tbody>
            @forelse($voucherStats as $row)
                <tr><td>{{ $row->status }}</td><td>{{ $row->total }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-muted">No vouchers yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
