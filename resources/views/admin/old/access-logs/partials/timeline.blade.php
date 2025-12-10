@if($logs->isEmpty())
  <p class="text-gray-500 text-center mt-6">No access logs found.</p>
@else
  <div class="relative border-l border-gray-300 ml-3 pl-6 mt-4">
    @foreach($logs as $log)
      <div class="mb-6 relative">
        <span class="absolute -left-3 top-1.5 w-3 h-3 rounded-full 
          {{ $log->access_decision === 'granted' ? 'bg-green-500' : 'bg-red-500' }}"></span>

        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
          <div class="flex justify-between">
            <h3 class="font-semibold text-gray-800">
              {{ $log->member ? $log->member->first_name . ' ' . $log->member->last_name : 'Unknown Member' }}
            </h3>
            <span class="text-sm text-gray-500">
              {{ \Carbon\Carbon::parse($log->access_time)->format('Y-m-d H:i') }}
            </span>
          </div>

          <p class="text-sm text-gray-600 mt-1">
            Badge: <span class="font-mono text-gray-800">{{ $log->badge_uid }}</span>
          </p>

          <p class="mt-2 text-sm">
            <span class="font-semibold {{ $log->access_decision === 'granted' ? 'text-green-600' : 'text-red-600' }}">
              {{ ucfirst($log->access_decision) }}
            </span>
            @if($log->denial_reason)
              — <span class="text-gray-600 italic">{{ $log->denial_reason }}</span>
            @endif
          </p>
        </div>
      </div>
    @endforeach
  </div>
@endif
