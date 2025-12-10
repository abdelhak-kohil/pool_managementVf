@extends('layouts.app')
@section('title', 'Modifier le paiement')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h2 class="text-2xl font-semibold text-gray-800">✏️ Modifier le paiement</h2>
  <a href="{{ route('payments.index') }}" class="text-gray-600 hover:text-gray-800 font-medium flex items-center gap-2 transition">
    <span>←</span> Retour
  </a>
</div>

<div class="bg-white rounded-xl shadow border border-gray-100 p-6">
  <form action="{{ route('payments.update', $payment->payment_id) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <!-- Subscription Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Abonnement concerné <span class="text-red-500">*</span></label>
      <select name="subscription_id" required
              class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        @foreach($subscriptions as $s)
          <option value="{{ $s->subscription_id }}" {{ $s->subscription_id == $payment->subscription_id ? 'selected' : '' }}>
            {{ $s->member->first_name }} {{ $s->member->last_name }} — {{ $s->plan->plan_name ?? 'Plan inconnu' }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Amount -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Montant (DZD) <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" name="amount" value="{{ $payment->amount }}" required
               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
      </div>

      <!-- Payment Method -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Méthode de paiement <span class="text-red-500">*</span></label>
        <select name="payment_method" required
                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
          <option value="cash" {{ $payment->payment_method == 'cash' ? 'selected' : '' }}>Espèces</option>
          <option value="card" {{ $payment->payment_method == 'card' ? 'selected' : '' }}>Carte bancaire</option>
          <option value="transfer" {{ $payment->payment_method == 'transfer' ? 'selected' : '' }}>Virement</option>
        </select>
      </div>
    </div>

    <!-- Notes -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optionnel)</label>
      <textarea name="notes" rows="3"
                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5"
                placeholder="Ajouter un commentaire...">{{ $payment->notes }}</textarea>
    </div>

    <!-- Actions -->
    <div class="flex justify-end gap-4 pt-4 border-t border-gray-100">
      <a href="{{ route('payments.index') }}" 
         class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-200">
        Annuler
      </a>
      <button type="submit" 
              class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 transition">
        Mettre à jour
      </button>
    </div>
  </form>
</div>
@endsection
