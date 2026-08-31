@extends('layouts.app')
@section('title', 'Guest Redemption Report')
@section('page_title', 'Guest Redemption Report')
@section('content')

{{-- Filter Panel --}}
<div class="card card-outline card-primary mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Report Filters</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.guest-redemption') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Target Date</label>
                    <input type="date" name="date" value="{{ $date->toDateString() }}" class="form-control">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Property</label>
                    <select name="property_id" class="form-select">
                        <option value="">All Properties</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" @selected($propertyId == $property->id)>{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Redemption Status</label>
                    <select name="status" class="form-select">
                        <option value="all" @selected($statusFilter === 'all')>All</option>
                        <option value="redeemed" @selected($statusFilter === 'redeemed')>Redeemed</option>
                        <option value="not_redeemed" @selected($statusFilter === 'not_redeemed')>Not Redeemed</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search"></i> Apply
                    </button>
                    <a href="{{ route('reports.guest-redemption') }}" class="btn btn-secondary" title="Reset">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-1"></i> Guests ({{ $date->format('d M Y') }})</h3>
            </div>
            <div class="card-body p-0">
                <div class="mb-3 mt-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Total Guests:</strong> {{ count($reportData) }}
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Guest Name</th>
                                <th>Booking Code</th>
                                <th>Room</th>
                                <th>Property</th>
                                <th>Pax Quota</th>
                                <th>Redeemed Today</th>
                                <th>Status Today</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $redeemInfo = [
                                'SNACK' => [
                                    ['place' => 'Soeji Dining', 'time' => 'Available starting at Check-in (14:00 - 20:00 WIB)'],
                                ],
                                'TEA' => [
                                    ['place' => 'Teras Hutan Bambu', 'time' => '15:00 - 17:00 WIB'],
                                ],
                                'DINNER-BBQ' => [
                                    ['place' => 'Teras Hutan Bambu', 'time' => '18:30 - 20:30 WIB'],
                                ],
                                'DINNER100K' => [
                                    ['place' => 'Soeji or Rumpun', 'time' => '18:30 - 20:30 WIB'],
                                ],
                                'DINNER' => [
                                    ['place' => 'Teras Hutan Bambu / Soeji / Rumpun', 'time' => '18:30 - 20:30 WIB'],
                                ],
                                'BREAKFAST' => [
                                    ['place' => 'Soeji Dining', 'time' => '07:00 - 10:00 WIB'],
                                ],
                                'JOURNAL' => [
                                    ['place' => 'Rumah Seni', 'time' => 'Available starting at Check-in (14:00 - 17:00 WIB)'],
                                ],
                                'FEED' => [
                                    ['place' => 'Rumpun Area', 'time' => 'Available starting at Check-in (10:00 - 11:45 WIB atau 13:15 - 16:45 WIB)'],
                                ],
                            ];
                            @endphp
                            @forelse($reportData as $index => $row)
                                <tr data-bs-toggle="collapse" data-bs-target="#collapseRow{{ $index }}" aria-expanded="false" aria-controls="collapseRow{{ $index }}" style="cursor: pointer;">
                                    <td class="text-center align-middle">
                                        <i class="fas fa-chevron-down text-muted"></i>
                                    </td>
                                    <td><strong>{{ $row->guest_name }}</strong></td>
                                    <td><code class="text-sm">{{ $row->booking_code }}</code></td>
                                    <td>{{ $row->room }}</td>
                                    <td>{{ $row->property_name ?? '-' }}</td>
                                    <td>{{ $row->pax_limit }} <small class="text-muted">pax</small></td>
                                    <td>{{ $row->redeemed_pax }} <small class="text-muted">pax</small></td>
                                    <td>
                                        @if($row->has_redeemed)
                                            <span class="badge bg-success text-white">Redeemed</span>
                                        @else
                                            <span class="badge bg-danger text-white">Not Redeemed</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="p-0 border-0">
                                        <div class="collapse" id="collapseRow{{ $index }}">
                                            <div class="p-4 bg-light border-bottom">
                                                <h5 class="mb-3">Facility Statuses for {{ $row->guest_name }}</h5>
                                                <div class="row">
                                                    @forelse($row->facility_statuses as $facility)
                                                        <div class="col-md-6 col-lg-4 mb-3">
                                                            <div class="p-3 rounded-3 border bg-white shadow-sm" style="border-color: var(--border) !important; height: 100%;">
                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                    <span class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $facility->name }}</span>
                                                                    @if($facility->status === 'available')
                                                                        <span class="badge px-2 py-1 rounded-pill text-white bg-success" style="font-size: 0.7rem;">Available today</span>
                                                                    @elseif($facility->status === 'used')
                                                                        <span class="badge px-2 py-1 rounded-pill bg-warning" style="font-size: 0.7rem;">Quota used today</span>
                                                                    @else
                                                                        <span class="badge px-2 py-1 rounded-pill bg-secondary text-white" style="font-size: 0.7rem;">Not available today</span>
                                                                    @endif
                                                                </div>
                                                                @foreach($redeemInfo[strtoupper($facility->code)] ?? [] as $info)
                                                                    <div class="d-flex align-items-center gap-2 mb-1" style="font-size: 0.8rem;">
                                                                        <i class="fas fa-location-dot text-muted" style="font-size: 0.7rem;"></i>
                                                                        <span class="text-muted">{{ $info['place'] }}</span>
                                                                    </div>
                                                                    <div class="d-flex align-items-center gap-2 mb-2" style="font-size: 0.8rem;">
                                                                        <i class="far fa-clock text-muted" style="font-size: 0.7rem;"></i>
                                                                        <span class="text-danger">{{ $info['time'] }}</span>
                                                                    </div>
                                                                @endforeach
                                                                @if($facility->status === 'available')
                                                                    @php
                                                                        $usedPercent = $facility->quota_total > 0 ? ($facility->quota_used / $facility->quota_total) * 100 : 0;
                                                                    @endphp
                                                                    <div class="progress mb-2" style="height: 8px; border-radius: 4px;">
                                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ 100 - $usedPercent }}%" aria-valuenow="{{ $facility->quota_remaining }}" aria-valuemin="0" aria-valuemax="{{ $facility->quota_total }}"></div>
                                                                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $usedPercent }}%" aria-valuenow="{{ $facility->quota_used }}" aria-valuemin="0" aria-valuemax="{{ $facility->quota_total }}"></div>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                                                                        <span class="text-muted">Remaining: <strong class="text-dark">{{ $facility->quota_remaining }}</strong></span>
                                                                        <span class="text-muted">Used: {{ $facility->quota_used }} / {{ $facility->quota_total }}</span>
                                                                    </div>
                                                                @elseif($facility->status === 'used')
                                                                    <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                                                                        <span class="text-muted">Remaining: <strong class="text-dark">0</strong></span>
                                                                        <span class="text-muted">Used: {{ $facility->quota_used }} / {{ $facility->quota_total }}</span>
                                                                    </div>
                                                                @else
                                                                    <p class="mb-0 text-muted small">Period: {{ $facility->start_date->format('d M') }} to {{ $facility->end_date->format('d M') }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="col-12">
                                                            <p class="text-muted text-center py-3">No active facilities found for this pass.</p>
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        <p>No active guests found for this criteria.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
