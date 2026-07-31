<table>
    <tr>
        <td colspan="11">RESORT VOUCHER ANALYTICS REPORT</td>
    </tr>
    <tr>
        <td colspan="11">Report Period: {{ $periodLabel }}</td>
    </tr>
    <tr>
        <td colspan="11">Generated on: {{ $exportDate }}</td>
    </tr>
    <tr>
        <td colspan="11">
            Filters:
            @if(!empty($filters['property_id'])) Property ID {{ $filters['property_id'] }} @endif
            @if(!empty($filters['facility_id'])) | Facility ID {{ $filters['facility_id'] }} @endif
            @if(!empty($filters['outlet_id'])) | Outlet ID {{ $filters['outlet_id'] }} @endif
            @if(empty($filters['property_id']) && empty($filters['facility_id']) && empty($filters['outlet_id'])) All properties @endif
        </td>
    </tr>
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">EXECUTIVE SUMMARY</td></tr>
    <tr>
        <th>Total Redemptions</th>
        <th>Total Pax</th>
        <th>Unique Guests</th>
        <th>Avg Pax / Redemption</th>
    </tr>
    <tr>
        <td>{{ $overview->total_redemptions }}</td>
        <td>{{ $overview->total_pax }}</td>
        <td>{{ $overview->unique_guests }}</td>
        <td>{{ $overview->avg_pax_per_redemption }}</td>
    </tr>
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">REDEMPTIONS BY FACILITY</td></tr>
    <tr>
        <th>Facility</th>
        <th>Events</th>
        <th>Pax</th>
    </tr>
    @forelse($redemptionsByFacility as $row)
    <tr>
        <td>{{ $row->facility_name }}</td>
        <td>{{ $row->redemption_count }}</td>
        <td>{{ $row->total_pax }}</td>
    </tr>
    @empty
    <tr><td colspan="3">No data in selected period.</td></tr>
    @endforelse
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">REDEMPTIONS BY OUTLET</td></tr>
    <tr>
        <th>Outlet</th>
        <th>Facility</th>
        <th>Events</th>
        <th>Pax</th>
    </tr>
    @forelse($redemptionsByOutlet as $row)
    <tr>
        <td>{{ $row->outlet_name }}</td>
        <td>{{ $row->facility_name }}</td>
        <td>{{ $row->redemption_count }}</td>
        <td>{{ $row->total_pax }}</td>
    </tr>
    @empty
    <tr><td colspan="4">No data in selected period.</td></tr>
    @endforelse
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">DAILY REDEMPTION TREND</td></tr>
    <tr>
        <th>Date</th>
        <th>Events</th>
        <th>Pax</th>
    </tr>
    @forelse($dailyTrend as $row)
    <tr>
        <td>{{ \Carbon\Carbon::parse($row->date)->format('Y-m-d') }}</td>
        <td>{{ $row->redemption_count }}</td>
        <td>{{ $row->total_pax }}</td>
    </tr>
    @empty
    <tr><td colspan="3">No data in selected period.</td></tr>
    @endforelse
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">DETAILED REDEMPTION LOG</td></tr>
    <tr>
        <th>No</th>
        <th>Date</th>
        <th>Time</th>
        <th>Guest Name</th>
        <th>Room</th>
        <th>Booking Code</th>
        <th>Facility</th>
        <th>Outlet</th>
        <th>Pax Used</th>
        <th>Remaining</th>
        <th>Staff</th>
    </tr>
    @foreach($redemptionDetails as $index => $redemption)
    <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $redemption->date->format('Y-m-d') }}</td>
        <td>{{ $redemption->time }}</td>
        <td>{{ $redemption->guest?->full_name ?? 'N/A' }}</td>
        <td>{{ $redemption->booking?->room?->code ?? $redemption->booking?->room?->number ?? 'N/A' }}</td>
        <td>{{ $redemption->booking?->booking_code ?? $redemption->booking?->reference ?? 'N/A' }}</td>
        <td>{{ $redemption->facilityTemplate?->name ?? 'N/A' }}</td>
        <td>{{ $redemption->outlet?->name ?? 'N/A' }}</td>
        <td>{{ $redemption->pax_used }}</td>
        <td>{{ $redemption->remaining_quota }}</td>
        <td>{{ $redemption->user?->name ?? 'System' }}</td>
    </tr>
    @endforeach
</table>
