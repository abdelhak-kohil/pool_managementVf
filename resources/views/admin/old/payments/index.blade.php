@extends('layouts.app')
@section('title', 'Payments')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold text-gray-800">Payments</h2>
    <a href="{{ route('payments.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Add Payment</a>
  </div>

  <form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="text" name="search" value="{{ $search }}" placeholder="Search by member name or email"
           class="border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200" />

    <select name="payment_method" class="border rounded-lg px-3 py-2">
      <option value="">All Methods</option>
      @foreach($methods as $m)
        <option value="{{ $m->method }}" @selected($method === $m->method)>
          {{ ucfirst($m->method) }}
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
          <th class="border p-3 text-left">Amount (DZD)</th>
          <th class="border p-3 text-left">Method</th>
          <th class="border p-3 text-left">Date</th>
          <th class="border p-3 text-left">Received By</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr class="hover:bg-gray-50">
  <td class="border p-3">{{ $p->payment_id }}</td>
  <td class="border p-3">{{ $p->subscription->member->first_name }} {{ $p->subscription->member->last_name }}</td>
  <td class="border p-3">{{ $p->subscription->plan->plan_name }}</td>
  <td class="border p-3 font-semibold text-green-600">{{ number_format($p->amount, 2) }}</td>
  <td class="border p-3">{{ ucfirst($p->payment_method) }}</td>
  <td class="border p-3">{{ \Carbon\Carbon::parse($p->payment_date)->format('Y-m-d H:i') }}</td>
  <td class="border p-3">
    {{ $p->staff->first_name }} {{ $p->staff->last_name }}
    <a href="{{ route('payments.receipt', $p->payment_id) }}" 
       target="_blank"
       class="ml-2 text-blue-600 hover:underline text-sm">
      🧾 Print
    </a>
  </td>
</tr>
        @empty
          <tr><td colspan="7" class="p-4 text-center text-gray-500">No payments found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $payments->links() }}</div>
</div>
@endsection
