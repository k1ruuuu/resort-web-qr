@extends('layouts.app')

@section('title', 'QR Scan History')
@section('page_title', 'QR Scan History')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Scan Logs</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.scan-history') }}" class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search QR code or guest name..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="scan_result" class="form-control">
                                <option value="">All Results</option>
                                <option value="success" {{ request('scan_result') === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="not_found" {{ request('scan_result') === 'not_found' ? 'selected' : '' }}>Not Found</option>
                                <option value="voucher_not_active" {{ request('scan_result') === 'voucher_not_active' ? 'selected' : '' }}>Voucher Not Active</option>
                                <option value="quota_exceeded" {{ request('scan_result') === 'quota_exceeded' ? 'selected' : '' }}>Quota Exceeded</option>
                                <option value="invalid_date" {{ request('scan_result') === 'invalid_date' ? 'selected' : '' }}>Invalid Date</option>
                                <option value="booking_not_checked_in" {{ request('scan_result') === 'booking_not_checked_in' ? 'selected' : '' }}>Not Checked In</option>
                                <option value="outside_stay_period" {{ request('scan_result') === 'outside_stay_period' ? 'selected' : '' }}>Outside Stay Period</option>
                                <option value="invalid_outlet" {{ request('scan_result') === 'invalid_outlet' ? 'selected' : '' }}>Invalid Outlet</option>
                                <option value="facility_not_linked" {{ request('scan_result') === 'facility_not_linked' ? 'selected' : '' }}>Facility Not Linked</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="outlet_id" class="form-control">
                                <option value="">All Outlets</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control" 
                                   placeholder="From Date"
                                   value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" 
                                   name="date_to" 
                                   class="form-control" 
                                   placeholder="To Date"
                                   value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    @if(request()->hasAny(['search', 'scan_result', 'outlet_id', 'date_from', 'date_to']))
                        <div class="mt-2">
                            <a href="{{ route('reports.scan-history') }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    @endif
                </form>

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Total Records:</strong> {{ $logs->total() }}
                    </div>
                    @can('reports.export')
                    <x-export-button 
                        route="reports.scan-history.export" 
                        :filters="request()->only(['search', 'scan_result', 'outlet_id', 'date_from', 'date_to'])"
                        text="Export Scan History" />
                    @endcan
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>QR Code</th>
                                <th>Guest Name</th>
                                <th>Room</th>
                                <th>Outlet</th>
                                <th>Scanned By</th>
                                <th>Result</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>
                                        <small>{{ $log->scanned_at->format('Y-m-d') }}</small><br>
                                        <small class="text-muted">{{ $log->scanned_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <code class="text-sm">{{ Str::limit($log->qr_code, 20) }}</code>
                                    </td>
                                    <td>
                                        @if($log->guestVoucher && $log->guestVoucher->guest)
                                            {{ $log->guestVoucher->guest->full_name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->guestVoucher && $log->guestVoucher->booking && $log->guestVoucher->booking->room)
                                            {{ $log->guestVoucher->booking->room->label ?? $log->guestVoucher->booking->room->number }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->outlet->name ?? '-' }}</td>
                                    <td>{{ $log->user->name ?? '-' }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match($log->scan_result) {
                                                'success' => 'success',
                                                'not_found' => 'danger',
                                                'voucher_not_active' => 'danger',
                                                'quota_exceeded' => 'warning',
                                                'invalid_date' => 'warning',
                                                'booking_not_checked_in' => 'warning',
                                                'outside_stay_period' => 'warning',
                                                'invalid_outlet' => 'danger',
                                                'facility_not_linked' => 'danger',
                                                default => 'secondary',
                                            };
                                            $displayText = match($log->scan_result) {
                                                'success' => 'Success',
                                                'not_found' => 'Not Found',
                                                'voucher_not_active' => 'Voucher Not Active',
                                                'quota_exceeded' => 'Quota Exceeded',
                                                'invalid_date' => 'Invalid Date',
                                                'booking_not_checked_in' => 'Not Checked In',
                                                'outside_stay_period' => 'Outside Stay Period',
                                                'invalid_outlet' => 'Invalid Outlet',
                                                'facility_not_linked' => 'Facility Not Linked',
                                                default => ucfirst(str_replace('_', ' ', $log->scan_result)),
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }}">{{ $displayText }}</span>
                                    </td>
                                    <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        <p>No scan logs found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} scans
                    </div>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
