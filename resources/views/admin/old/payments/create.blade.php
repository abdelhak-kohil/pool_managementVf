@extends('layouts.app')
@section('title', 'Add Payment')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h2 class="text-xl font-semibold text-gray-800 mb-6">Record New Payment</h2>

  <form method="POST" action="{{ route('payments.store') }}" class="space-y-4">
    @csrf

    <div>
      <label class="block text-sm font-medium text-gray-700">Subscription</label>
      <select name="subscription_id" required class="w-full border rounded-lg px-3 py-2">
        <option value="">Select Subscription</option>
        @foreach($subscriptions as $sub)
          <option value="{{ $sub->subscription_id }}">
            {{ $sub->member->first_name }} {{ $sub->member->last_name }} — {{ $sub->plan->plan_name }}
          </option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Amount (DZD)</label>
      <input type="number" name="amount" required step="0.01" min="0" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Payment Method</label>
      <select name="payment_method" required class="w-full border rounded-lg px-3 py-2">
        @foreach($methods as $m)
          <option value="{{ $m->method }}">{{ ucfirst($m->method) }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Notes (optional)</label>
      <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
    </div>

    <div class="flex justify-end mt-4">
      <a href="{{ route('payments.index') }}" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
      <button class="ml-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
    </div>
  </form>
</div>
@endsection
