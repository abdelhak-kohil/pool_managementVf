@extends('layouts.app')
@section('title', 'Subscription Plans')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold text-gray-800">Subscription Plans</h2>
    <a href="{{ route('plans.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Add Plan</a>
  </div>

  <form method="GET" class="mb-4 flex items-center gap-2">
    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name or description"
           class="border rounded-lg px-3 py-2 w-full md:w-80 focus:ring focus:ring-blue-200" />
    <button class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">Search</button>
  </form>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 rounded-lg text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="border p-3 text-left">#</th>
          <th class="border p-3 text-left">Name</th>
          <th class="border p-3 text-left">Type</th>
          <th class="border p-3 text-left">Price</th>
          <th class="border p-3 text-left">Visits / Duration</th>
          <th class="border p-3 text-center">Active</th>
          <th class="border p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($plans as $plan)
          <tr class="hover:bg-gray-50">
            <td class="border p-3">{{ $plan->plan_id }}</td>
            <td class="border p-3 font-medium">{{ $plan->plan_name }}</td>
            <td class="border p-3">{{ ucfirst(str_replace('_', ' ', $plan->plan_type)) }}</td>
            <td class="border p-3">{{ number_format($plan->price, 2) }} DZD</td>
            <td class="border p-3">
              @if($plan->plan_type === 'monthly_weekly')
                {{ $plan->visits_per_week }} visits / {{ $plan->duration_months }} months
              @else
                — 
              @endif
            </td>
            <td class="border p-3 text-center">
              <span class="{{ $plan->is_active ? 'text-green-600' : 'text-red-600' }}">
                {{ $plan->is_active ? 'Yes' : 'No' }}
              </span>
            </td>
            <td class="border p-3 text-center">
              <a href="{{ route('plans.edit', $plan->plan_id) }}" class="text-blue-600 hover:underline text-sm">Edit</a>
              <form action="{{ route('plans.destroy', $plan->plan_id) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button onclick="return confirm('Delete this plan?')" class="text-red-600 hover:underline text-sm ml-2">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="p-4 text-center text-gray-500">No plans found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $plans->links() }}</div>
</div>
@endsection
