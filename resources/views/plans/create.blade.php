@extends('layouts.app')
@section('title', isset($plan) ? 'Modifier le plan' : 'Créer un plan')

@section('content')
<div class="space-y-8">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <h2 class="text-2xl font-semibold text-gray-800">
      {{ isset($plan) ? '✏️ Modifier le plan' : '➕ Nouveau plan' }}
    </h2>
    <a href="{{ route('plans.index') }}"
       class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition">
      ← Retour
    </a>
  </div>

  <!-- Form Card -->
  <div class="bg-white shadow rounded-xl border border-gray-100 p-8 max-w-4xl mx-auto">
    <form method="POST" 
          action="{{ isset($plan) ? route('plans.update', $plan->plan_id) : route('plans.store') }}" 
          class="space-y-10">
      @csrf
      @if(isset($plan)) @method('PUT') @endif

      <!-- === 1. Informations Générales === -->
      <section>
        <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">📝 Informations Générales</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Nom du plan</label>
            <input type="text" name="plan_name" value="{{ old('plan_name', $plan->plan_name ?? '') }}" required
                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
          </div>



          <div class="md:col-span-2">
            <label class="block text-gray-700 font-medium mb-2">Description</label>
            <textarea name="description" rows="3"
                      class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200"
                      placeholder="Description du plan...">{{ old('description', $plan->description ?? '') }}</textarea>
          </div>
        </div>
      </section>

      <!-- === 2. Configuration du Plan === -->
      <section>
        <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">⚙️ Configuration</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Type de plan</label>
            <select name="plan_type" id="planType" required
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
              @foreach($types as $type)
              <option value="{{ $type }}" {{ old('plan_type', $plan->plan_type ?? 'monthly_weekly') == $type ? 'selected' : '' }}>
                {{ ucfirst(str_replace('_', ' ', $type)) }}
              </option>
              @endforeach
            </select>
          </div>

          <div class="flex items-center pt-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="is_active" value="1" 
                     {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}
                     class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
              <span class="text-gray-700 font-medium">Plan actif</span>
            </label>
          </div>
        </div>

        <!-- Weekly/Monthly Specific Fields -->
        <div id="weeklyFields" class="{{ (old('plan_type', $plan->plan_type ?? 'monthly_weekly') === 'monthly_weekly') ? '' : 'hidden' }} mt-6 border-t pt-6">
          <h4 class="font-medium text-gray-700 mb-3">📅 Options de Durée et Fréquence</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-gray-700 font-medium mb-2">Visites par semaine</label>
              <input type="number" name="visits_per_week" min="1" max="7"
                     value="{{ old('visits_per_week', $plan->visits_per_week ?? '') }}"
                     class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
              <p class="text-xs text-gray-500 mt-1">Laissez vide si illimité</p>
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-2">Durée (mois)</label>
              <input type="number" name="duration_months" min="1" max="24"
                     value="{{ old('duration_months', $plan->duration_months ?? '') }}"
                     class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
            </div>
          </div>
        </div>
      </section>

      <!-- Buttons -->
      <div class="pt-6 border-t flex justify-end gap-4">
        <a href="{{ route('plans.index') }}" class="px-5 py-2 rounded-lg border border-gray-300 hover:bg-gray-100 transition">
          Annuler
        </a>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md">
          {{ isset($plan) ? '💾 Mettre à jour' : '✅ Créer le Plan' }}
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('planType').addEventListener('change', function() {
  const weeklyFields = document.getElementById('weeklyFields');
  weeklyFields.classList.toggle('hidden', this.value !== 'monthly_weekly');
});
</script>
@endsection
