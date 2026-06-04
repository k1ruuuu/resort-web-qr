@extends('layouts.app')
@section('title', $guest->full_name)
@section('page_title', 'Guest: ' . $guest->full_name)
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Guest Details</h3>
                <div class="card-tools">
                    <a href="{{ route('guests.edit', $guest) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('guests.destroy', $guest) }}" style="display: inline;" onsubmit="return confirm('Delete this guest?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="fw-bold">First Name:</td>
                        <td>{{ $guest->first_name }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Last Name:</td>
                        <td>{{ $guest->last_name }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Email:</td>
                        <td>
                            @if($guest->email)
                                <a href="mailto:{{ $guest->email }}">{{ $guest->email }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Phone:</td>
                        <td>
                            @if($guest->phone)
                                <a href="tel:{{ $guest->phone }}">{{ $guest->phone }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">WhatsApp:</td>
                        <td>{{ $guest->whatsapp ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Document ID:</td>
                        <td>{{ $guest->document_id ?? '—' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    @if($guest->bookings->count() > 0)
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Bookings ({{ $guest->bookings->count() }})</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Property</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($guest->bookings as $booking)
                        <tr>
                            <td>
                                <a href="{{ route('bookings.show', $booking) }}">{{ $booking->booking_code }}</a>
                            </td>
                            <td>{{ $booking->property->name }}</td>
                            <td>{{ $booking->check_in->format('M d, Y') }}</td>
                            <td>{{ $booking->check_out->format('M d, Y') }}</td>
                            <td>{{ $booking->status->value }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
<div class="mt-3">
    <a href="{{ route('guests.index') }}" class="btn btn-secondary">Back to Guests</a>
</div>
@endsection
