<table>
    <tr>
        <td colspan="11" style="text-align: center; font-size: 16px; font-weight: bold; color: #1F4E78;">
            RESORT VOUCHER REDEMPTION REPORT
        </td>
    </tr>
    <tr>
        <td colspan="11" style="text-align: center; font-size: 12px;">
            Generated on: {{ $exportDate }}
        </td>
    </tr>
    <tr>
        <td colspan="11" style="text-align: center; font-size: 11px;">
            Total Redemptions: {{ $totalRedemptions }} | Total Pax: {{ $totalPax }}
        </td>
    </tr>
    <tr><td colspan="11"></td></tr> <!-- Empty row -->
    
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">No</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Date</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Time</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Guest Name</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Room</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Booking Code</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Facility</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Outlet</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Pax Used</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Remaining</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Staff</th>
        </tr>
    </thead>
    <tbody>
        @foreach($redemptions as $index => $redemption)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $redemption->date->format('Y-m-d') }}</td>
            <td>{{ $redemption->time }}</td>
            <td>{{ $redemption->guest?->full_name ?? 'N/A' }}</td>
            <td>{{ $redemption->booking?->room?->code ?? $redemption->booking?->room?->number ?? 'N/A' }}</td>
            <td>{{ $redemption->booking?->booking_code ?? $redemption->booking?->reference ?? 'N/A' }}</td>
            <td>{{ $redemption->facilityTemplate?->name ?? 'N/A' }}</td>
            <td>{{ $redemption->outlet?->name ?? 'N/A' }}</td>
            <td style="text-align: center;">{{ $redemption->pax_used }}</td>
            <td style="text-align: center;">{{ $redemption->remaining_quota }}</td>
            <td>{{ $redemption->user?->name ?? 'System' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
