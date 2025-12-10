<!-- === PAYMENT SECTION === --> 
<div class="bg-white rounded-xl shadow border border-gray-100 p-6">

  <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
    💳 Paiements de l’abonnement
  </h2>

  @php
    /* MODE BLADE (edit page) */
    $isBlade = isset($subscription);

    if ($isBlade) {
        // $planPrice est passé depuis le contrôleur (SubscriptionController ou PaymentController)
        // On s'assure qu'il est défini, sinon 0
        $planPrice = $planPrice ?? 0;
        $totalPaid = $subscription->payments->sum('amount') ?? 0;
        $remaining = max(0, $planPrice - $totalPaid);
    }
  @endphp

  <!-- === PAYMENT SUMMARY === -->
  <div 
    class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg mb-6 border border-blue-200"
    @if(!$isBlade)
      x-data
    @endif
  >
    <div class="flex flex-wrap justify-between items-center mb-3">
      <div>
        <h3 class="text-lg font-semibold text-blue-800">Résumé du paiement</h3>
        <p class="text-sm text-gray-600">Suivi des transactions et du solde restant</p>
      </div>

      <div class="text-right">
        <p class="text-sm text-gray-600">Montant total du plan</p>

        @if($isBlade)
          <p class="text-xl font-semibold text-blue-700 plan-price">
            {{ number_format($planPrice, 2) }} DZD
          </p>
        @else
          <p class="text-xl font-semibold text-blue-700 plan-price">
            <span x-text="selected.price"></span> DZD
          </p>
        @endif
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mb-3">

      <!-- TOTAL PAYE -->
      <div class="bg-white shadow-sm p-3 rounded-lg border border-gray-100">
        <span class="text-gray-500">💰 Total payé</span>

        @if($isBlade)
          <p class="text-lg font-semibold text-green-700 payment-total">
            {{ number_format($totalPaid, 2) }} DZD
          </p>
        @else
          <p class="text-lg font-semibold text-green-700 payment-total">
            <span x-text="(() => selected.payments.reduce((s,p)=>s+parseFloat(p.amount),0).toFixed(2))()"></span> DZD
          </p>
        @endif
      </div>

      <!-- RESTE -->
      <div class="bg-white shadow-sm p-3 rounded-lg border border-gray-100">
        <span class="text-gray-500">🧾 Reste à payer</span>

        @if($isBlade)
          <p class="text-lg font-semibold {{ $remaining > 0 ? 'text-red-600' : 'text-green-700' }} payment-remaining">
            {{ number_format($remaining, 2) }} DZD
          </p>
        @else
          <p class="text-lg font-semibold text-red-600 payment-remaining">
            <span x-text="(() => {
              const paid = selected.payments.reduce((s,p)=>s+parseFloat(p.amount),0);
              return (selected.price - paid).toFixed(2);
            })()"></span> DZD
          </p>
        @endif
      </div>

    </div>
  </div>

  <!-- ========================= -->
  <!--     PAYMENT TABLE        -->
  <!-- ========================= -->
  <div class="overflow-x-auto border rounded-lg mb-6">

    <table class="min-w-full text-left text-sm text-gray-700">
      <thead class="bg-gray-50 border-b">
        <tr>
          <th class="px-4 py-2 font-medium">Date</th>
          <th class="px-4 py-2 font-medium">Montant (DZD)</th>
          <th class="px-4 py-2 font-medium">Méthode</th>
          <th class="px-4 py-2 font-medium">Reçu par</th>
        </tr>
      </thead>

      <tbody>

        <!-- MODE BLADE -->
        @if($isBlade)
          @forelse ($subscription->payments as $p)
            <tr class="hover:bg-gray-50 border-b">
              <td class="px-4 py-2">{{ \Carbon\Carbon::parse($p->payment_date)->format('d/m/Y H:i') }}</td>
              <td class="px-4 py-2">{{ number_format($p->amount, 2) }}</td>
              <td class="px-4 py-2">{{ ucfirst($p->payment_method) }}</td>
              <td class="px-4 py-2">{{ $p->staff->first_name ?? 'N/A' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-gray-500 py-3">Aucun paiement enregistré</td>
            </tr>
          @endforelse

        <!-- MODE ALPINE.JS -->
        @else
          <template x-if="selected.payments.length">
            <template x-for="p in selected.payments">
              <tr class="hover:bg-gray-50 border-b">
                <td class="px-4 py-2" x-text="new Date(p.date).toLocaleString()"></td>
                <td class="px-4 py-2" x-text="p.amount"></td>
                <td class="px-4 py-2" x-text="p.method"></td>
                <td class="px-4 py-2" x-text="p.staff"></td>
              </tr>
            </template>
          </template>

          <template x-if="!selected.payments.length">
            <tr>
              <td colspan="4" class="text-center text-gray-500 py-3">Aucun paiement enregistré</td>
            </tr>
          </template>
        @endif

      </tbody>
    </table>
  </div>

  <!-- ========================= -->
  <!--    ADD NEW PAYMENT       -->
  <!-- ========================= -->
  @if($isBlade && $remaining > 0)
    <!-- Code EXACT pour la page edit -->
    <div class="mt-6">

      <h3 class="text-md font-semibold text-gray-700 mb-2">➕ Ajouter un nouveau paiement</h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="number" step="0.01" id="amount" placeholder="Montant (DZD)"
               class="border rounded-lg p-2 focus:ring focus:ring-blue-200">

        <select id="payment_method" class="border rounded-lg p-2 focus:ring focus:ring-blue-200">
          <option value="">-- Méthode --</option>
          <option value="cash">Espèces</option>
          <option value="card">Carte</option>
          <option value="transfer">Virement</option>
        </select>

        <input type="text" id="notes" placeholder="Notes (facultatif)"
               class="border rounded-lg p-2 focus:ring focus:ring-blue-200">
      </div>

      <button type="button" id="addPaymentBtn"
              class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
        💸 Enregistrer le paiement
      </button>

    </div>
  @endif
</div>

@if($isBlade)
<!-- === JS SECTION === -->


<script>
document.getElementById('addPaymentBtn')?.addEventListener('click', async () => {
  const amount = document.getElementById('amount').value.trim();
  const method = document.getElementById('payment_method').value;
  const notes = document.getElementById('notes').value;

  if (!amount || !method) {
    Swal.fire({
      icon: 'warning',
      title: 'Champs manquants',
      text: 'Veuillez remplir le montant et la méthode de paiement.'
    });
    return;
  }

  const res = await fetch(`{{ route('subscriptions.payments.ajax', $subscription->subscription_id) }}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({ amount, payment_method: method, notes })
  });

  const data = await res.json();

  if (!data.success) {
    return Swal.fire({
      icon: 'error',
      title: 'Erreur',
      text: data.message || 'Impossible d’enregistrer le paiement.'
    });
  }

  Swal.fire({
    icon: 'success',
    title: 'Paiement ajouté',
    text: data.message,
    timer: 1800,
    position: 'top-end',
    showConfirmButton: false,
    toast: true
  });

  document.querySelector('.payment-total').textContent = data.summary.totalPaid + ' DZD';
  document.querySelector('.payment-remaining').textContent = data.summary.remaining + ' DZD';
  document.querySelector('.plan-price').textContent = data.summary.planPrice + ' DZD';

  // Insert dynamically
  const tableBody = document.querySelector('table tbody');
  const newRow = document.createElement('tr');
  newRow.classList.add('hover:bg-gray-50', 'border-b');
  newRow.innerHTML = `
    <td class="px-4 py-2">${data.newPayment.date}</td>
    <td class="px-4 py-2">${data.newPayment.amount}</td>
    <td class="px-4 py-2">${data.newPayment.method}</td>
    <td class="px-4 py-2">${data.newPayment.staff}</td>
  `;
  tableBody.appendChild(newRow);

});
</script>
@endif
