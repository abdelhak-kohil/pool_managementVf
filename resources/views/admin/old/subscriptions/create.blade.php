@extends('layouts.app')
@section('title', 'New Subscription')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h2 class="text-xl font-semibold text-gray-800 mb-6">Add New Subscription</h2>

  <form method="POST" action="{{ route('subscriptions.store') }}" class="space-y-4">
    @csrf

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Member</label>
        <select name="member_id" required class="w-full border rounded-lg px-3 py-2">
          <option value="">Select Member</option>
          @foreach($members as $m)
            <option value="{{ $m->member_id }}">{{ $m->first_name }} {{ $m->last_name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Plan</label>
        <select name="plan_id" required class="w-full border rounded-lg px-3 py-2">
          <option value="">Select Plan</option>
          @foreach($plans as $p)
            <option value="{{ $p->plan_id }}">{{ $p->plan_name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Start Date</label>
        <input type="date" name="start_date" value="{{ old('start_date') }}" required class="w-full border rounded-lg px-3 py-2">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">End Date</label>
        <input type="date" name="end_date" value="{{ old('end_date') }}" required class="w-full border rounded-lg px-3 py-2">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Status</label>
      <select name="status" required class="w-full border rounded-lg px-3 py-2">
        <option value="active">Active</option>
        <option value="paused">Paused</option>
        <option value="expired">Expired</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="flex justify-end mt-4">
      <a href="{{ route('subscriptions.index') }}" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
      <button class="ml-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
    </div>
  </form>
</div>
@endsection
