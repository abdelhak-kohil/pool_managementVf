@extends('layouts.app')
@section('title', 'Edit Subscription Plan')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h2 class="text-xl font-semibold text-gray-800 mb-6">Edit Subscription Plan</h2>

  <form method="POST" action="{{ route('plans.update', $plan->plan_id) }}" class="space-y-4">
    @csrf

    <!-- Plan Name -->
    <div>
      <label class="block text-sm font-medium text-gray-700">Plan Name</label>
      <input type="text" name="plan_name" value="{{ old('plan_name', $plan->plan_name) }}" required class="w-full border rounded-lg px-3 py-2">
    </div>

    <!-- Description -->
    <div>
      <label class="block text-sm font-medium text-gray-700">Description</label>
      <textarea name="description" rows="3" class="w-full border rounded-lg px-3 py-2">{{ old('description', $plan->description) }}</textarea>
    </div>

    <!-- Price and Type -->
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Price (DZD)</label>
        <input type="number" name="price" value="{{ old('price', $plan->price) }}" required step="0.01" class="w-full border rounded-lg px-3 py-2">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Plan Type</label>
        <select name="plan_type" id="plan_type" required class="w-full border rounded-lg px-3 py-2">
          @foreach($planTypes as $type)
            <option value="{{ $type->type }}" @selected(old('plan_type', $plan->plan_type) === $type->type)>
              {{ ucfirst(str_replace('_', ' ', $type->type)) }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <!-- Extra Fields (Visible only if plan_type == 'monthly_weekly') -->
    <div id="extraFields" class="{{ old('plan_type', $plan->plan_type) === 'monthly_weekly' ? '' : 'hidden' }}">
      <div class="grid md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Visits per Week</label>
          <input type="number" name="visits_per_week" value="{{ old('visits_per_week', $plan->visits_per_week) }}" class="w-full border rounded-lg px-3 py-2">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Duration (Months)</label>
          <input type="number" name="duration_months" value="{{ old('duration_months', $plan->duration_months) }}" class="w-full border rounded-lg px-3 py-2">
        </div>
      </div>
    </div>

    <!-- Active -->
    <div class="mt-3 flex items-center gap-2">
      <input type="checkbox" name="is_active" id="is_active" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="rounded text-blue-600">
      <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>

    <!-- Actions -->
    <div class="flex justify-end mt-4">
      <a href="{{ route('plans.index') }}" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
      <button class="ml-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
    </div>
  </form>
</div>

<!-- JavaScript for conditional fields -->
<script>
document.getElementById('plan_type').addEventListener('change', function() {
  const extraFields = document.getElementById('extraFields');
  if (this.value === 'monthly_weekly') {
    extraFields.classList.remove('hidden');
  } else {
    extraFields.classList.add('hidden');
    document.querySelector('input[name="visits_per_week"]').value = '';
    document.querySelector('input[name="duration_months"]').value = '';
  }
});
</script>
@endsection
