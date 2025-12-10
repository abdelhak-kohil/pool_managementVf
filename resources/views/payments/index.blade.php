@extends('layouts.app')
@section('title', 'Gestion des Paiements')

@section('content')
<div x-data="paymentDashboard()" x-init="initPayments()" class="space-y-6">

  <!-- === Header === -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">💳 Gestion des Paiements</h2>
    <a href="{{ route('payments.create') }}" 
       class="bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-blue-700 transition font-medium flex items-center gap-2">
       <span>➕</span> Nouveau paiement
    </a>
  </div>

  <!-- === FILTER BAR === -->
  <div class="bg-white p-4 rounded-xl shadow border border-gray-100 flex flex-wrap items-end gap-4">
    <div class="flex-1 min-w-[150px]">
      <label class="block text-sm font-medium text-gray-700 mb-1">Méthode</label>
      <select x-model="filters.method" @change="fetchPayments()" 
              class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        <option value="all">Toutes</option>
        <option value="cash">Espèces</option>
        <option value="card">Carte bancaire</option>
        <option value="transfer">Virement</option>
      </select>
    </div>

    <div class="flex-1 min-w-[150px]">
      <label class="block text-sm font-medium text-gray-700 mb-1">Abonnement</label>
      <select x-model="filters.plan_id" @change="fetchPayments()" 
              class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        <option value="all">Tous</option>
        @foreach($plans as $plan)
            <option value="{{ $plan->plan_id }}">{{ $plan->plan_name }}</option>
        @endforeach
      </select>
    </div>

    <div class="flex-1 min-w-[150px]">
      <label class="block text-sm font-medium text-gray-700 mb-1">Activité</label>
      <select x-model="filters.activity_id" @change="fetchPayments()" 
              class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        <option value="all">Toutes</option>
        @foreach($activities as $activity)
            <option value="{{ $activity->activity_id }}">{{ $activity->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="flex-1 min-w-[150px]">
      <label class="block text-sm font-medium text-gray-700 mb-1">Reçu par</label>
      <select x-model="filters.staff_id" @change="fetchPayments()" 
              class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        <option value="all">Tous</option>
        @foreach($staffMembers as $staff)
            <option value="{{ $staff->staff_id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
        @endforeach
      </select>
    </div>

    <div class="flex-1 min-w-[200px]">
      <label class="block text-sm font-medium text-gray-700 mb-1">Du</label>
      <input type="date" x-model="filters.from" @change="fetchPayments()"
             class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
    </div>

    <div class="flex-1 min-w-[200px]">
      <label class="block text-sm font-medium text-gray-700 mb-1">Au</label>
      <input type="date" x-model="filters.to" @change="fetchPayments()"
             class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
    </div>

    <div class="flex gap-2">
      <button @click="resetFilters()" 
              class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-200">
        Réinitialiser
      </button>
      
      <button @click="exportFile('excel')" 
              class="px-4 py-2.5 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 focus:ring-4 focus:outline-none focus:ring-green-100 flex items-center gap-2">
              <span>⬇️</span> Excel
      </button>
      
      <button @click="exportFile('pdf')" 
              class="px-4 py-2.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 focus:ring-4 focus:outline-none focus:ring-red-100 flex items-center gap-2">
              <span>📄</span> PDF
      </button>
    </div>
  </div>

  <!-- === SUMMARY CARDS (Dynamic) === -->
  <div id="summaryCardsContainer">
    @include('payments.partials.summary-cards', [
      'totalToday' => $totalToday,
      'totalMonth' => $totalMonth,
      'totalAll'   => $totalAll
    ])
  </div>

  <!-- === TABLE (Dynamic) === -->
  <div id="paymentsTableContainer" class="transition-opacity duration-300">
    @include('payments.partials.payments-table', ['payments' => $payments])
  </div>
</div>

<!-- === Alpine + AJAX Logic === -->
<script>
function paymentDashboard() {
  return {
    filters: { method: 'all', plan_id: 'all', activity_id: 'all', staff_id: 'all', from: '', to: '' },
    loading: false,

    initPayments() {
      const urlParams = new URLSearchParams(window.location.search);
      this.filters.method = urlParams.get('method') || 'all';
      this.filters.plan_id = urlParams.get('plan_id') || 'all';
      this.filters.activity_id = urlParams.get('activity_id') || 'all';
      this.filters.staff_id = urlParams.get('staff_id') || 'all';
      this.filters.from = urlParams.get('from') || '';
      this.filters.to = urlParams.get('to') || '';
      
      this.bindPagination();
    },

    fetchPayments(page = 1) {
      this.loading = true;
      const container = document.getElementById('paymentsTableContainer');
      container.classList.add('opacity-50');

      const params = new URLSearchParams({
        method: this.filters.method,
        plan_id: this.filters.plan_id,
        activity_id: this.filters.activity_id,
        staff_id: this.filters.staff_id,
        from: this.filters.from,
        to: this.filters.to,
        page
      });

      fetch(`{{ route('payments.index') }}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(res => res.json())
      .then(data => {
        container.innerHTML = data.html;
        document.getElementById('summaryCardsContainer').innerHTML = data.cards;
        this.bindPagination();
        container.classList.remove('opacity-50');
        this.loading = false;
      })
      .catch(err => {
        console.error('Erreur lors du chargement des paiements :', err);
        container.classList.remove('opacity-50');
      });
    },

    bindPagination() {
      document.querySelectorAll('#paymentsTableContainer .pagination a').forEach(link => {
        link.addEventListener('click', e => {
          e.preventDefault();
          const page = new URL(link.href).searchParams.get('page');
          this.fetchPayments(page);
        });
      });
    },

    resetFilters() {
      this.filters = { method: 'all', plan_id: 'all', activity_id: 'all', staff_id: 'all', from: '', to: '' };
      this.fetchPayments();
    },

    exportFile(type) {
      const params = new URLSearchParams({
        method: this.filters.method,
        from: this.filters.from,
        to: this.filters.to
      });
      const url = type === 'excel' 
        ? `{{ route('payments.export.excel') }}?${params.toString()}`
        : `{{ route('payments.export.pdf') }}?${params.toString()}`;
      window.open(url, '_blank');
    }
  };
}
</script>
@endsection
