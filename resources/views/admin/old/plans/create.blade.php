@extends('layouts.app')
@section('title', 'Add Subscription Plan')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h2 class="text-xl font-semibold text-gray-800 mb-6">Add New Plan</h2>

  <form method="POST" action="{{ route('plans.store') }}" class="space-y-4">
    @csrf

    <div>
      <label class="block text-sm font-medium text-gray-700">Plan Name</label>
      <input type="text" name="plan_name" value="{{ old('plan_name') }}" required class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Description</label>
      <textarea name="description" rows="3" class="w-full border rounded-lg px-3 py-2">{{ old('description') }}</textarea>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Price (DZD)</label>
        <input type="number" name="price" value="{{ old('price') }}" required step="0.01" class="w-full border rounded-lg px-3 py-2">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Plan Type</label>
        <select name="plan_type" id="plan_type" required class="w-full border rounded-lg px-3 py-2">
          @foreach($planTypes as $type)
            <option value="{{ $type->type }}" @selected(old('plan_type') === $type->type)>
              {{ ucfirst(str_replace('_', ' ', $type->type)) }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <div id="extraFields" class="{{ old('plan_type') === 'monthly_weekly' ? '' : 'hidden' }}">
      <div class="grid md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Visits per Week</label>
          <input type="number" name="visits_per_week" value="{{ old('visits_per_week') }}" class="w-full border rounded-lg px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Duration (Months)</label>
          <input type="number" name="duration_months" value="{{ old('duration_months') }}" class="w-full border rounded-lg px-3 py-2">
        </div>
      </div>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <input type="checkbox" name="is_active" id="is_active" checked class="rounded text-blue-600">
      <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>

    <div class="flex justify-end mt-4">
      <a href="{{ route('plans.index') }}" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
      <button class="ml-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
    </div>
  </form>
</div>

<script>
document.getElementById('plan_type').addEventListener('change', function() {
  const extraFields = document.getElementById('extraFields');
  if (this.value === 'monthly_weekly') extraFields.classList.remove('hidden');
  else extraFields.classList.add('hidden');
});
</script>
@endsection
