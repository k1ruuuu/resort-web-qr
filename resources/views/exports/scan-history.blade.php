<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">No</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Scan Date/Time</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">QR Code</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Guest Name</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Room</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Outlet</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Scanned By</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Result</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">IP Address</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">User Agent</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $index => $log)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $log->scanned_at->format('Y-m-d H:i:s') }}</td>
            <td>{{ $log->qr_code }}</td>
            <td>{{ $log->guestVoucher?->guest?->full_name ?? 'N/A' }}</td>
            <td>{{ $log->guestVoucher?->booking?->room?->code ?? $log->guestVoucher?->booking?->room?->number ?? 'N/A' }}</td>
            <td>{{ $log->outlet?->name ?? 'N/A' }}</td>
            <td>{{ $log->user?->name ?? 'System' }}</td>
            <td>{{ strtoupper(str_replace('_', ' ', $log->scan_result)) }}</td>
            <td>{{ $log->ip_address }}</td>
            <td>{{ $log->user_agent }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
