@extends('layouts.app')
@section('title', 'Member Subscriptions Overview')

@section('content')
<div class="flex justify-between items-center mb-6">
  <h2 class="text-2xl font-semibold text-gray-800">Members & Subscriptions</h2>
</div>

<div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
  <table class="w-full border-collapse text-sm">
    <thead class="bg-gray-100 text-gray-700">
      <tr>
        <th class="p-3 text-left w-1/5">Member</th>
        <th class="p-3 text-left w-2/5">Active Subscriptions</th>
        <th class="p-3 text-center w-1/5">Total Subscriptions</th>
        <th class="p-3 text-right w-1/5">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($members as $member)
        @php
          $activeSubs = $member->subscriptions->where('status', 'active');
        @endphp
        <tr class="border-t hover:bg-gray-50">
          <td class="p-3 align-top">
            <div class="font-semibold text-gray-800">{{ $member->first_name }} {{ $member->last_name }}</div>
            <div class="text-xs text-gray-500">{{ $member->email ?? '-' }}</div>
          </td>
          <td class="p-3 align-top">
            @if($activeSubs->count())
              @foreach($activeSubs as $sub)
                <div class="mb-1">
                  <span class="font-medium">{{ $sub->plan->plan_name ?? '-' }}</span>
                  <span class="text-xs text-gray-500 ml-2">({{ ucfirst($sub->status) }})</span>
                </div>
              @endforeach
            @else
              <span class="text-gray-400 text-xs">No active subscriptions</span>
            @endif
          </td>
          <td class="p-3 text-center">{{ $member->subscriptions->count() }}</td>
          <td class="p-3 text-right">
            <button class="text-blue-600 hover:underline font-medium"
                    onclick="toggleDetails('{{ $member->member_id }}')">
              View Details
            </button>
          </td>
        </tr>

        <!-- COLLAPSIBLE DETAILS -->
        <tr id="details-{{ $member->member_id }}" class="hidden bg-gray-50 border-t">
          <td colspan="4" class="p-4">
            @if($member->subscriptions->count())
              <table class="w-full text-sm border border-gray-200 rounded overflow-hidden">
                <thead class="bg-white text-gray-600">
                  <tr>
                    <th class="p-2 text-left">#</th>
                    <th class="p-2 text-left">Plan</th>
                    <th class="p-2 text-left">Type</th>
                    <th class="p-2 text-center">Visits/Week</th>
                    <th class="p-2 text-left">Allowed Days</th>
                    <th class="p-2 text-left">Period</th>
                    <th class="p-2 text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($member->subscriptions as $sub)
                    @php
                      $days = $sub->allowedDays->pluck('day_name')->map(fn($d) => \Illuminate\Support\Str::limit($d, 3, ''))->toArray();
                      $daysText = implode(', ', $days);
                    @endphp
                    <tr class="border-t hover:bg-white">
                      <td class="p-2">{{ $sub->subscription_id }}</td>
                      <td class="p-2">{{ $sub->plan->plan_name ?? '-' }}</td>
                      <td class="p-2 capitalize">{{ $sub->plan->plan_type ?? '-' }}</td>
                      <td class="p-2 text-center">{{ $sub->visits_per_week ?? '-' }}</td>
                      <td class="p-2">{{ $daysText ?: '—' }}</td>
                      <td class="p-2 text-gray-600">
                        {{ \Carbon\Carbon::parse($sub->start_date)->format('Y-m-d') }}
                        → {{ \Carbon\Carbon::parse($sub->end_date)->format('Y-m-d') }}
                      </td>
                      <td class="p-2 text-center">
                        <span class="px-2 py-1 rounded text-xs font-medium
                          @switch($sub->status)
                            @case('active') bg-green-100 text-green-700 @break
                            @case('paused') bg-yellow-100 text-yellow-700 @break
                            @case('expired') bg-gray-200 text-gray-700 @break
                            @case('cancelled') bg-red-100 text-red-700 @break
                          @endswitch">
                          {{ ucfirst($sub->status) }}
                        </span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @else
              <p class="text-sm text-gray-500">No subscriptions available for this member.</p>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

<script>
function toggleDetails(memberId) {
  const row = document.getElementById(`details-${memberId}`);
  row.classList.toggle('hidden');
}
</script>
@endsection
