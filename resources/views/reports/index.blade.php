@extends('layouts.app')
@section('title', 'Reports')
@section('page_title', 'Reports')
@section('content')
<form class="row g-2 mb-3" method="GET">
    <div class="col-auto"><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
    <div class="col-auto"><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-secondary">Filter</button></div>
</form>
<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Redemptions by Facility</div>
            <table class="table mb-0">
                <thead><tr><th>Facility</th><th>Events</th><th>Pax</th></tr></thead>
                <tbody>
                @forelse($redemptions as $row)
                    <tr>
                        <td>{{ $row->facility_name }}</td>
                        <td>{{ $row->redemption_count }}</td>
                        <td>{{ $row->total_pax }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">No data in range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Voucher Status</div>
            <table class="table mb-0">
                @foreach($voucherStats as $row)
                    <tr><td>{{ $row->status }}</td><td>{{ $row->total }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection
