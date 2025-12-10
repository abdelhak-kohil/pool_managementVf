@extends('layouts.app')
@section('title', 'Subscriptions')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold text-gray-800">Subscriptions</h2>
    <a href="{{ route('subscriptions.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ New Subscription</a>
  </div>

  <form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="text" name="search" value="{{ $search }}" placeholder="Search by member name or email"
           class="border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200" />

    <select name="status" class="border rounded-lg px-3 py-2">
      <option value="">All Statuses</option>
      @foreach($statuses as $s)
        <option value="{{ $s->status }}" @selected($status === $s->status)>
          {{ ucfirst($s->status) }}
        </option>
      @endforeach
    </select>

    <button class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">Filter</button>
  </form>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 rounded-lg text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="border p-3 text-left">#</th>
          <th class="border p-3 text-left">Member</th>
          <th class="border p-3 text-left">Plan</th>
          <th class="border p-3 text-left">Start / End</th>
          <th class="border p-3 text-left">Status</th>
          <th class="border p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($subscriptions as $sub)
          <tr class="hover:bg-gray-50">
            <td class="border p-3">{{ $sub->subscription_id }}</td>
            <td class="border p-3">{{ $sub->member->first_name }} {{ $sub->member->last_name }}</td>
            <td class="border p-3">{{ $sub->plan->plan_name }}</td>
            <td class="border p-3">{{ $sub->start_date }} → {{ $sub->end_date }}</td>
            <td class="border p-3">
              <span class="
                @if($sub->status === 'active') text-green-600
                @elseif($sub->status === 'paused') text-yellow-600
                @elseif($sub->status === 'expired') text-gray-600
                @else text-red-600 @endif
                font-semibold">
                {{ ucfirst($sub->status) }}
              </span>
            </td>
            <td class="border p-3 text-center">
              <a href="{{ route('subscriptions.edit', $sub->subscription_id) }}" class="text-blue-600 hover:underline text-sm">Edit</a>
              <form action="{{ route('subscriptions.destroy', $sub->subscription_id) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button onclick="return confirm('Delete this subscription?')" class="text-red-600 hover:underline text-sm ml-2">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="p-4 text-center text-gray-500">No subscriptions found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $subscriptions->links() }}</div>
</div>
@endsection
