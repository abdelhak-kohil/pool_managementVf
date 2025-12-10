@foreach($recentAccessLogs as $log)
  <tr class="hover:bg-gray-50">
    <td class="p-3">{{ $log->member ? $log->member->first_name . ' ' . $log->member->last_name : '—' }}</td>
    <td class="p-3 font-mono">{{ $log->badge_uid }}</td>
    <td class="p-3">
      <span class="@if($log->access_decision === 'granted') text-green-600 @else text-red-600 @endif font-semibold">
        {{ ucfirst($log->access_decision) }}
      </span>
    </td>
    <td class="p-3">{{ \Carbon\Carbon::parse($log->access_time)->format('Y-m-d H:i') }}</td>
    <td class="p-3">{{ $log->denial_reason ?? '—' }}</td>
  </tr>
@endforeach
