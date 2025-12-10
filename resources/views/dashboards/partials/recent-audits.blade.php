<div class="space-y-2">
  @forelse($recentAudits as $log)
    <div class="border rounded p-3 bg-gray-50 flex justify-between items-start gap-3">
      <div>
        <div class="text-sm text-gray-700">
          <span class="font-medium">{{ $log->action }}</span> on <span class="font-medium">{{ $log->table_name }}</span>
          <span class="text-gray-400">#{{ $log->record_id }}</span>
        </div>
        <div class="text-xs text-gray-500 mt-1">
          by <span class="font-medium">{{ $log->staff->username ?? 'System' }}</span>
          • {{ \Carbon\Carbon::parse($log->change_timestamp)->diffForHumans() }}
        </div>
      </div>
      <button onclick="viewDetails({{ $log->log_id }})" class="text-indigo-600 text-sm hover:underline">View</button>
    </div>
  @empty
    <div class="p-4 text-center text-gray-500">No recent activity</div>
  @endforelse
</div>
