@extends('layouts.app')
@section('title', 'Nouvel Abonnement')

@section('content')
<form action="{{ route('subscriptions.store') }}" method="POST" class="space-y-8">
  @csrf

  <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
      🧍‍♂️ Informations du Membre
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Membre</label>
        <select name="member_id" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
          <option value="">-- Sélectionner un membre --</option>
          @foreach($members as $m)
            <option value="{{ $m->member_id }}">{{ $m->first_name }} {{ $m->last_name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Plan</label>
        <select name="plan_id" id="planSelect" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
          <option value="">-- Sélectionner un plan --</option>
          @foreach($plans as $p)
            <option value="{{ $p->plan_id }}" data-type="{{ $p->plan_type }}">
              {{ $p->plan_name }} ({{ ucfirst(str_replace('_', ' ', $p->plan_type)) }})
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Date de début</label>
        <input type="date" name="start_date" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Date de fin</label>
        <input type="date" name="end_date" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Statut</label>
        <select name="status" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200">
          @foreach($statuses as $status)
            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>

  <!-- SECTION: Weekly Settings -->
  <div id="weeklyFields" class="bg-white rounded-xl shadow p-6 border border-gray-100 hidden">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
      🗓️ Options hebdomadaires
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Visites par semaine</label>
        <select name="visits_per_week" id="visitsSelect" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200">
          <option value="">-- Choisir --</option>
          <option value="1">1 fois / semaine</option>
          <option value="2">2 fois / semaine</option>
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Jours autorisés</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          @foreach($weekdays as $day)
            <label class="flex items-center gap-2 bg-gray-50 border rounded-lg p-2 hover:bg-blue-50 cursor-pointer">
              <input type="checkbox" name="allowed_days[]" value="{{ $day->weekday_id }}" class="accent-blue-600">
              <span>{{ $day->day_name }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <!-- SECTION: Payment -->
  <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
      💳 Paiement de l’abonnement
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Montant payé (DZD)</label>
        <input type="number" step="0.01" name="amount" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Méthode de paiement</label>
        <select name="payment_method" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
          <option value="cash">Espèces</option>
          <option value="card">Carte</option>
          <option value="transfer">Virement</option>
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Notes</label>
        <textarea name="notes" rows="2" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" placeholder="Ex: acompte, remise, etc."></textarea>
      </div>
    </div>
  </div>

  <!-- SECTION: Audit (Admin Only) -->
  @if (Auth::check() && Auth::user()->role->role_name === 'Admin')
  <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
    <h2 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
      🧾 Informations internes
    </h2>
    <div class="grid grid-cols-2 gap-6 text-sm text-gray-600">
      <div><strong>Créé par :</strong> {{ Auth::user()->first_name ?? 'Admin' }}</div>
      <div><strong>Mis à jour par :</strong> {{ Auth::user()->first_name ?? 'Admin' }}</div>
      <div><strong>Date de création :</strong> {{ now()->format('d/m/Y H:i') }}</div>
      <div><strong>Dernière mise à jour :</strong> {{ now()->format('d/m/Y H:i') }}</div>
    </div>
  </div>
  @endif

  <!-- ACTIONS -->
  <div class="flex justify-end gap-4">
    <a href="{{ route('subscriptions.index') }}" 
       class="px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
      Annuler
    </a>
    <button type="submit" 
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
      Enregistrer l’abonnement
    </button>
  </div>
</form>

<script>
const planSelect = document.getElementById('planSelect');
const weeklyFields = document.getElementById('weeklyFields');
planSelect.addEventListener('change', () => {
  const selected = planSelect.options[planSelect.selectedIndex];
  const type = selected.getAttribute('data-type');
  weeklyFields.classList.toggle('hidden', type !== 'monthly_weekly');
});
</script>
@endsection
