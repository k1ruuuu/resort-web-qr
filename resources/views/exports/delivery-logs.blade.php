<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">No</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Date/Time (Scheduled)</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Guest Name</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Booking Code</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Phone Number</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Message Content</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">QR Code URL</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Status</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Sent At</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $index => $log)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>
                @if($log->scheduled_at)
                    {{ $log->scheduled_at->format('Y-m-d H:i:s') }}
                @else
                    Immediate
                @endif
            </td>
            <td>{{ $log->guest?->full_name ?? 'N/A' }}</td>
            <td>{{ $log->booking?->booking_code ?? $log->booking?->reference ?? 'N/A' }}</td>
            <td>{{ $log->phone_number }}</td>
            <td>{{ $log->message_content }}</td>
            <td>{{ $log->qr_path ?? 'None' }}</td>
            <td>{{ strtoupper($log->delivery_status) }}</td>
            <td>
                @if($log->sent_at)
                    {{ $log->sent_at->format('Y-m-d H:i:s') }}
                @else
                    N/A
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
