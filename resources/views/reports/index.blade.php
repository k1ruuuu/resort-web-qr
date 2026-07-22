@extends('layouts.app')
@section('title', 'Reports')
@section('page_title', 'Reports')
@section('content')

{{-- Filter Panel --}}
<div class="card card-outline card-primary mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Report Filters</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-tool d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#reportFilterCollapse" aria-expanded="true">
                <i class="fas fa-chevron-down"></i>
            </button>
            <span class="badge bg-primary">{{ $periodLabel }}</span>
        </div>
    </div>
    <div class="collapse collapse-md-show" id="reportFilterCollapse">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}" id="report-filter-form">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Filter By</label>
                    <select name="filter_type" id="filter_type" class="form-select">
                        <option value="date_range" @selected($filterType === 'date_range')>Date Range</option>
                        <option value="month" @selected($filterType === 'month')>Month</option>
                        <option value="year" @selected($filterType === 'year')>Year</option>
                    </select>
                </div>

                <div class="col-md-3 filter-group" id="filter-date-range" @if($filterType !== 'date_range') style="display:none" @endif>
                    <label class="form-label fw-semibold">From</label>
                    <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
                </div>
                <div class="col-md-3 filter-group" id="filter-date-range-to" @if($filterType !== 'date_range') style="display:none" @endif>
                    <label class="form-label fw-semibold">To</label>
                    <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
                </div>

                <div class="col-md-3 filter-group" id="filter-month" @if($filterType !== 'month') style="display:none" @endif>
                    <label class="form-label fw-semibold">Month</label>
                    <select name="month" class="form-select">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 filter-group" id="filter-month-year" @if($filterType !== 'month') style="display:none" @endif>
                    <label class="form-label fw-semibold">Year</label>
                    <select name="year" class="form-select">
                        @foreach(range(now()->year, now()->year - 5) as $y)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 filter-group" id="filter-year-only" @if($filterType !== 'year') style="display:none" @endif>
                    <label class="form-label fw-semibold">Year</label>
                    <select name="year" class="form-select">
                        @foreach(range(now()->year, now()->year - 5) as $y)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3 align-items-end">
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
                    <label class="form-label fw-semibold">Facility</label>
                    <select name="facility_id" class="form-select">
                        <option value="">All Facilities</option>
                        @foreach($facilities as $facility)
                            <option value="{{ $facility->id }}" @selected($facilityId == $facility->id)>{{ $facility->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Outlet</label>
                    <select name="outlet_id" class="form-select">
                        <option value="">All Outlets</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($outletId == $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search"></i> Apply
                    </button>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary" title="Reset">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    @can('reports.export')
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Export includes summary, breakdowns, and full redemption log for the selected period.</small>
        <x-export-button
            route="reports.redemptions.export"
            :filters="request()->only(['filter_type', 'from', 'to', 'month', 'year', 'property_id', 'facility_id', 'outlet_id'])"
            text="Export Report" />
    </div>
    @endcan
    </div>
</div>

{{-- KPI Summary --}}
<div class="row mb-4">
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-info"><i class="fas fa-receipt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Redemptions</span>
                <span class="info-box-number">{{ number_format($overview->total_redemptions) }}</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Pax Redeemed</span>
                <span class="info-box-number">{{ number_format($overview->total_pax) }}</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-warning"><i class="fas fa-user-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Unique Guests</span>
                <span class="info-box-number">{{ number_format($overview->unique_guests) }}</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-primary"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Avg Pax / Redemption</span>
                <span class="info-box-number">{{ $overview->avg_pax_per_redemption }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Redemptions by Facility --}}
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building mr-1"></i> Redemptions by Facility</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Facility</th>
                                <th class="text-end">Events</th>
                                <th class="text-end">Pax</th>
                                <th class="text-end" style="width:120px">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($redemptions as $row)
                            @php $share = $overview->total_pax > 0 ? round(($row->total_pax / $overview->total_pax) * 100, 1) : 0; @endphp
                            <tr>
                                <td>{{ $row->facility_name }}</td>
                                <td class="text-end">{{ number_format($row->redemption_count) }}</td>
                                <td class="text-end">{{ number_format($row->total_pax) }}</td>
                                <td class="text-end">
                                    <div class="progress" style="height:18px;">
                                        <div class="progress-bar bg-info" style="width:{{ $share }}%">{{ $share }}%</div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No redemptions in this period.</td></tr>
                        @endforelse
                        </tbody>
                        @if($redemptions->isNotEmpty())
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td>Total</td>
                                <td class="text-end">{{ number_format($redemptions->sum('redemption_count')) }}</td>
                                <td class="text-end">{{ number_format($redemptions->sum('total_pax')) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Voucher Status --}}
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ticket-alt mr-1"></i> Voucher Status Overview</h3>
            </div>
            <div class="card-body p-0 table-responsive-stack">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th class="text-end">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($voucherStats as $row)
                        @php
                            $badge = match(is_object($row->status) ? $row->status->value : $row->status) {
                                'active' => 'success',
                                'redeemed' => 'primary',
                                'expired' => 'secondary',
                                'cancelled' => 'danger',
                                default => 'secondary',
                            };
                            $label = ucfirst(is_object($row->status) ? $row->status->value : $row->status);
                        @endphp
                        <tr>
                            <td><span class="badge bg-{{ $badge }}">{{ $label }}</span></td>
                            <td class="text-end">{{ number_format($row->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">No voucher data available.</td></tr>
                    @endforelse
                    </tbody>
                    @if($voucherStats->isNotEmpty())
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td>Total</td>
                            <td class="text-end">{{ number_format($voucherStats->sum('total')) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Redemptions by Outlet --}}
    <div class="col-lg-7 mb-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-store mr-1"></i> Redemptions by Outlet</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Outlet</th>
                                <th>Facility</th>
                                <th class="text-end">Events</th>
                                <th class="text-end">Pax</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($redemptionsByOutlet as $row)
                            <tr>
                                <td>{{ $row->outlet_name }}</td>
                                <td><span class="text-muted">{{ $row->facility_name }}</span></td>
                                <td class="text-end">{{ number_format($row->redemption_count) }}</td>
                                <td class="text-end">{{ number_format($row->total_pax) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No outlet activity in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Trend --}}
    <div class="col-lg-5 mb-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-day mr-1"></i> Daily Trend</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Events</th>
                                <th class="text-end">Pax</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($dailyTrend as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('D, M j') }}</td>
                                <td class="text-end">{{ $row->redemption_count }}</td>
                                <td class="text-end">{{ $row->total_pax }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No daily data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Activity --}}
<div class="card card-outline card-secondary mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Redemptions</h3>
        <div class="card-tools">
            <span class="badge bg-secondary">Latest 15</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date / Time</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Facility</th>
                        <th>Outlet</th>
                        <th class="text-end">Pax</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($recentRedemptions as $log)
                    <tr>
                        <td>
                            <small>{{ $log->date->format('Y-m-d') }}</small><br>
                            <small class="text-muted">{{ $log->time }}</small>
                        </td>
                        <td>{{ $log->guest?->full_name ?? '—' }}</td>
                        <td>{{ $log->booking?->room?->number ?? '—' }}</td>
                        <td>{{ $log->facilityTemplate?->name ?? '—' }}</td>
                        <td>{{ $log->outlet?->name ?? '—' }}</td>
                        <td class="text-end"><span class="badge bg-info">{{ $log->pax_used }}</span></td>
                        <td><small>{{ $log->user?->name ?? 'System' }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No recent redemptions in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
function toggleFilterFields() {
    const type = document.getElementById('filter_type').value;
    const groups = {
        'date_range': ['filter-date-range', 'filter-date-range-to'],
        'month': ['filter-month', 'filter-month-year'],
        'year': ['filter-year-only'],
    };

    document.querySelectorAll('.filter-group').forEach(function (el) {
        el.style.display = 'none';
        el.querySelectorAll('input, select').forEach(function (input) {
            input.disabled = true;
        });
    });

    (groups[type] || []).forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = '';
            el.querySelectorAll('input, select').forEach(function (input) {
                input.disabled = false;
            });
        }
    });
}

document.getElementById('filter_type').addEventListener('change', toggleFilterFields);
toggleFilterFields();
</script>
@endpush
