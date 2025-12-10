@extends('layouts.app')
@section('title', 'Edit Subscription')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h2 class="text-xl font-semibold text-gray-800 mb-6">Edit Subscription</h2>

  <form method="POST" action="{{ route('subscriptions.update', $subscription->subscription_id) }}" class="space-y-4">
    @csrf

    <!-- Member & Plan -->
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Member</label>
        <select name="member_id" required class="w-full border rounded-lg px-3 py-2">
          @foreach($members as $m)
            <option value="{{ $m->member_id }}" @selected(old('member_id', $subscription->member_id) == $m->member_id)>
              {{ $m->first_name }} {{ $m->last_name }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Plan</label>
        <select name="plan_id" required class="w-full border rounded-lg px-3 py-2">
          @foreach($plans as $p)
            <option value="{{ $p->plan_id }}" @selected(old('plan_id', $subscription->plan_id) == $p->plan_id)>
              {{ $p->plan_name }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <!-- Dates -->
    <div class="grid md:grid-cols-2 gap-4 mt-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Start Date</label>
        <input type="date" name="start_date" value="{{ old('start_date', $subscription->start_date) }}" required class="w-full border rounded-lg px-3 py-2">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">End Date</label>
        <input type="date" name="end_date" value="{{ old('end_date', $subscription->end_date) }}" required class="w-full border rounded-lg px-3 py-2">
      </div>
    </div>

    <!-- Status -->
    <div class="mt-4">
      <label class="block text-sm font-medium text-gray-700">Status</label>
      <select name="status" required class="w-full border rounded-lg px-3 py-2">
        @foreach($statuses as $s)
          <option value="{{ $s->status }}" @selected(old('status', $subscription->status) === $s->status)>
            {{ ucfirst($s->status) }}
          </option>
        @endforeach
      </select>
    </div>

    <!-- Optional Pause/Resume Fields -->
    <div id="pauseFields" class="{{ old('status', $subscription->status) === 'paused' ? '' : 'hidden' }} mt-4">
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Paused At</label>
          <input type="datetime-local" name="paused_at" value="{{ old('paused_at', $subscription->paused_at ? date('Y-m-d\TH:i', strtotime($subscription->paused_at)) : '') }}" class="w-full border rounded-lg px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Resumes At</label>
          <input type="date" name="resumes_at" value="{{ old('resumes_at', $subscription->resumes_at) }}" class="w-full border rounded-lg px-3 py-2">
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end mt-4">
      <a href="{{ route('subscriptions.index') }}" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
      <button class="ml-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
    </div>
  </form>
</div>

<script>
document.querySelector('select[name="status"]').addEventListener('change', function() {
  const pauseFields = document.getElementById('pauseFields');
  if (this.value === 'paused') {
    pauseFields.classList.remove('hidden');
  } else {
    pauseFields.classList.add('hidden');
    document.querySelector('input[name="paused_at"]').value = '';
    document.querySelector('input[name="resumes_at"]').value = '';
  }
});
</script>
@endsection
