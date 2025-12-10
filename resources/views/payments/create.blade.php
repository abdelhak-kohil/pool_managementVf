@extends('layouts.app')
@section('title', 'Ajouter un Paiement')

@section('content')
<div x-data="paymentPage()" class="space-y-6">

  <!-- HEADER -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">💳 Nouveau Paiement</h2>
    <a href="{{ route('payments.index') }}" 
       class="text-gray-600 hover:text-gray-800 font-medium flex items-center gap-2 transition">
       <span>←</span> Retour à la liste
    </a>
  </div>

  <!-- TABLE: Subscriptions -->
  <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-800">📊 Abonnements avec solde impayé</h3>
        <p class="text-sm text-gray-500 mt-1">Sélectionnez un abonnement pour enregistrer un paiement rapide.</p>
      </div>
      
      <div class="relative">
        <input type="text" placeholder="Rechercher un membre ou plan..."
               x-model="search"
               class="w-full md:w-72 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 text-gray-400 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
        </svg>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-left text-gray-800">
        <thead class="bg-gray-50 border-b text-xs uppercase text-gray-500">
          <tr>
            <th class="px-6 py-3 font-medium">Membre</th>
            <th class="px-6 py-3 font-medium">Plan</th>
            <th class="px-6 py-3 font-medium text-right">Total</th>
            <th class="px-6 py-3 font-medium text-right">Payé</th>
            <th class="px-6 py-3 font-medium text-right">Reste</th>
            <th class="px-6 py-3 font-medium text-center">Statut</th>
            <th class="px-6 py-3 font-medium text-center">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach ($subscriptions as $sub)
            @php
              $totalPaid = $sub->payments->sum('amount');
              $planPrice = \Illuminate\Support\Facades\DB::table('pool_schema.activity_plan_prices')
                  ->where('plan_id', $sub->plan_id)
                  ->where('activity_id', $sub->activity_id)
                  ->value('price') ?? 0;
              $remaining = max(0, $planPrice - $totalPaid);
            @endphp
            @if ($remaining > 0)
            <tr class="hover:bg-blue-50 transition"
                x-show="matchesSearch('{{ $sub->member->first_name }} {{ $sub->member->last_name }} {{ $sub->plan->plan_name }}')">
              <td class="px-6 py-4 font-medium">{{ $sub->member->first_name }} {{ $sub->member->last_name }}</td>
              <td class="px-6 py-4 text-gray-600">{{ $sub->plan->plan_name }}</td>
              <td class="px-6 py-4 text-right text-gray-900">{{ number_format($planPrice, 2) }}</td>
              <td class="px-6 py-4 text-right text-green-600 font-medium">{{ number_format($totalPaid, 2) }}</td>
              <td class="px-6 py-4 text-right text-red-600 font-bold">{{ number_format($remaining, 2) }}</td>
              <td class="px-6 py-4 text-center">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                  Incomplet
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <button type="button"
                        class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-xs px-3 py-2 transition"
                        @click="openPaymentModal('{{ $sub->subscription_id }}', '{{ $sub->member->first_name }} {{ $sub->member->last_name }}', '{{ $sub->plan->plan_name }}', {{ $remaining }})">
                  ⚡ Payer
                </button>
              </td>
            </tr>
            @endif
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- MODAL: Add Payment -->
  <div x-cloak x-show="showModal" x-transition.opacity
       class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg relative transform transition-all"
         @click.away="closeModal">
      
      <button @click="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <div class="p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">💳 Enregistrer un Paiement</h3>

        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6 text-sm text-blue-800">
          <div class="flex justify-between mb-1">
            <span class="font-medium">Membre :</span>
            <span x-text="modalMember"></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-medium">Plan :</span>
            <span x-text="modalPlan"></span>
          </div>
          <div class="flex justify-between font-bold text-blue-900 mt-2 pt-2 border-t border-blue-200">
            <span>Reste à payer :</span>
            <span x-text="modalRemaining + ' DZD'"></span>
          </div>
        </div>

        <form method="POST" action="{{ route('payments.store') }}" @submit.prevent="validateAndSubmit($event)">
          @csrf
          <input type="hidden" name="subscription_id" :value="modalSubscriptionId">

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Montant (DZD) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" min="0" x-model.number="amount"
                     @input="checkAmount"
                     name="amount" required
                     class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
              <p x-show="isInvalid" class="text-red-600 text-xs mt-1 font-medium">
                ⚠️ Le montant dépasse le reste à payer (<span x-text="modalRemaining"></span> DZD).
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Méthode de paiement <span class="text-red-500">*</span></label>
              <select name="payment_method" x-model="method" required
                      class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                <option value="">-- Choisir --</option>
                <option value="cash">Espèces</option>
                <option value="card">Carte bancaire</option>
                <option value="transfer">Virement</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
              <textarea name="notes" rows="2" x-model="notes"
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5"
                        placeholder="Référence transaction, commentaire..."></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-8">
            <button type="button" @click="closeModal"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-200">
              Annuler
            </button>
            <button type="submit"
                    :disabled="isInvalid"
                    :class="isInvalid ? 'bg-gray-300 cursor-not-allowed text-gray-500' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                    class="px-5 py-2.5 text-sm font-medium rounded-lg focus:ring-4 focus:outline-none focus:ring-blue-300 transition">
              Enregistrer le paiement
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>



<script>
function paymentPage() {
  return {
    search: '',
    showModal: false,
    modalSubscriptionId: null,
    modalMember: '',
    modalPlan: '',
    modalRemaining: 0,
    amount: '',
    method: '',
    notes: '',
    isInvalid: false,

    matchesSearch(text) {
      return text.toLowerCase().includes(this.search.toLowerCase());
    },

    openPaymentModal(id, member, plan, remaining) {
      this.showModal = true;
      this.modalSubscriptionId = id;
      this.modalMember = member;
      this.modalPlan = plan;
      this.modalRemaining = parseFloat(remaining);
      this.amount = remaining;
      this.method = 'cash';
      this.notes = '';
      this.isInvalid = false;
    },

    closeModal() {
      this.showModal = false;
      this.isInvalid = false;
    },

    checkAmount() {
      if (this.amount > this.modalRemaining) {
        this.isInvalid = true;
      } else {
        this.isInvalid = false;
      }
    },

    validateAndSubmit(e) {
      if (this.amount > this.modalRemaining) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: 'Montant trop élevé',
          text: `Le paiement (${this.amount} DZD) dépasse le reste à payer (${this.modalRemaining} DZD).`,
          confirmButtonColor: '#d33'
        });
        this.isInvalid = true;
      } else {
        this.isInvalid = false;
        e.target.submit();
      }
    }
  }
}
</script>

<style>
  [x-cloak] { display: none !important; }
</style>
@endsection
