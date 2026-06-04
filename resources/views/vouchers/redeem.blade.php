@extends('layouts.app')
@section('title', 'Redeem Voucher')
@section('page_title', 'Redeem Voucher')
@section('content')
<div class="card col-md-8">
    <div class="card-body">
        <p class="text-muted small">Scan or paste QR payload: <strong>Room+FacilityCode+Date</strong> (client format).</p>
        <form method="POST" action="{{ route('vouchers.redeem') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">QR Code</label>
                <input type="text" name="qr_code" class="form-control @error('qr_code') is-invalid @enderror" value="{{ old('qr_code') }}" placeholder="J 01 - Forest Tent Japan+BREAKFAST+2026-06-04" required>
                @error('qr_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Outlet</label>
                <select name="outlet_id" class="form-select" required>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Pax used</label>
                <input type="number" name="pax_used" value="1" min="1" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Redeem</button>
        </form>
    </div>
</div>
@endsection
