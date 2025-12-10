@extends('layouts.app')
@section('title', 'Modifier un Abonnement')

@section('content')
<form action="{{ route('subscriptions.update', $subscription->subscription_id) }}" method="POST" class="space-y-8">
  @csrf
  @method('PUT')

  <!-- === Membre Info === -->
  <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
      🧍‍♂️ Informations du Membre
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Nom du membre</label>
        <input type="text" value="{{ $subscription->member->first_name }} {{ $subscription->member->last_name }}"
               class="w-full border rounded-lg p-2 bg-gray-100 text-gray-700" readonly>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Téléphone</label>
        <input type="text" value="{{ $subscription->member->phone_number ?? '—' }}"
               class="w-full border rounded-lg p-2 bg-gray-100 text-gray-700" readonly>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Badge attribué</label>
        <input type="text" value="{{ $subscription->member->badge->badge_uid ?? 'Aucun badge' }}"
               class="w-full border rounded-lg p-2 bg-gray-100 text-gray-700" readonly>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Statut du badge</label>
        <span class="inline-block px-3 py-2 rounded-lg text-sm font-semibold
          @if(optional($subscription->member->badge)->status === 'active') bg-green-100 text-green-700
          @elseif(optional($subscription->member->badge)->status === 'inactive') bg-gray-100 text-gray-700
          @elseif(optional($subscription->member->badge)->status === 'lost') bg-yellow-100 text-yellow-700
          @elseif(optional($subscription->member->badge)->status === 'revoked') bg-red-100 text-red-700
          @else bg-gray-200 text-gray-600 @endif">
          {{ ucfirst(optional($subscription->member->badge)->status ?? 'Non défini') }}
        </span>
      </div>
    </div>
  </div>

  <!-- === Subscription Details === -->
  <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
      🗓️ Détails de l’abonnement
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-medium mb-1">Plan</label>
        <select name="plan_id" id="planSelect" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200">
          @foreach($plans as $p)
            <option value="{{ $p->plan_id }}" 
                    data-type="{{ $p->plan_type }}"
                    {{ $subscription->plan_id == $p->plan_id ? 'selected' : '' }}>
              {{ $p->plan_name }} ({{ ucfirst(str_replace('_', ' ', $p->plan_type)) }})
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Statut</label>
        <select name="status" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200">
          @foreach($statuses as $status)
            <option value="{{ $status }}" {{ $subscription->status === $status ? 'selected' : '' }}>
              {{ ucfirst($status) }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Date de début</label>
        <input type="date" name="start_date" 
               value="{{ optional($subscription->start_date)->format('Y-m-d') }}"
               class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Date de fin</label>
        <input type="date" name="end_date"
               value="{{ optional($subscription->end_date)->format('Y-m-d') }}"
               class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
      </div>
    </div>

    <!-- Monthly/Weekly Fields -->
      <div id="weeklyFields" class="{{ $subscription->plan->plan_type === 'monthly_weekly' ? '' : 'hidden' }} mt-6">
        <h3 class="text-lg font-semibold text-blue-700 mb-2">Jours autorisés</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-gray-700 font-medium mb-1">Visites / Semaine</label>
            <select name="visits_per_week" id="visitsSelect" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200">
              <option value="1" {{ $subscription->visits_per_week == 1 ? 'selected' : '' }}>1 fois / semaine</option>
              <option value="2" {{ $subscription->visits_per_week == 2 ? 'selected' : '' }}>2 fois / semaine</option>
            </select>
          </div>

          <div>
            
            @php
              $selectedDays = $subscription->allowedDays->pluck('weekday_id')->toArray();
            @endphp
          <div id="daysSelect" class="{{ $subscription->plan->plan_type === 'monthly_weekly' ? '' : 'hidden' }}">
            <label class="block text-gray-700 mb-2 font-medium">Jours autorisés</label>
            <div class="grid grid-cols-3 gap-3">
              @foreach($weekdays as $day)
                <label class="flex items-center gap-2 bg-gray-50 border rounded-lg p-2 hover:bg-blue-50 cursor-pointer">
                  <input type="checkbox" name="allowed_days[]" 
                        value="{{ $day->weekday_id }}"
                        class="accent-blue-600 day-checkbox"
                        {{ in_array($day->weekday_id, $selectedDays) ? 'checked' : '' }}>
                  <span>{{ $day->day_name }}</span>
                </label>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- === Payment Section === -->
  <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">💳 Paiements</h2>

    <!-- Payment Summary -->
    <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg mb-6 border border-blue-200">
      <div class="flex flex-wrap justify-between items-center mb-3">
        <div>
          <h3 class="text-lg font-semibold text-blue-800">Résumé du paiement</h3>
          <p class="text-sm text-gray-600">État financier de cet abonnement</p>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-600">Montant du plan</p>
          <p class="text-xl font-semibold text-blue-700 plan-price">{{ number_format($planPrice, 2) }} DZD</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mb-3">
        <div class="bg-white shadow-sm p-3 rounded-lg border border-gray-100">
          <span class="text-gray-500">💰 Total payé</span>
          <p class="text-lg font-semibold text-green-700 payment-total">{{ number_format($totalPaid, 2) }} DZD</p>
        </div>
        <div class="bg-white shadow-sm p-3 rounded-lg border border-gray-100">
          <span class="text-gray-500">🧾 Reste à payer</span>
          <p class="text-lg font-semibold text-red-600 payment-remaining">{{ number_format($remaining, 2) }} DZD</p>
        </div>
      </div>
    </div>

    <!-- Payment Table -->
    <div class="overflow-x-auto border rounded-lg mb-6">
      <table class="min-w-full text-left text-sm text-gray-700">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-4 py-2 font-medium">Date</th>
            <th class="px-4 py-2 font-medium">Montant (DZD)</th>
            <th class="px-4 py-2 font-medium">Méthode</th>
            <th class="px-4 py-2 font-medium">Personnel</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($subscription->payments as $p)
          <tr class="hover:bg-gray-50 border-b">
            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($p->payment_date)->format('d/m/Y H:i') }}</td>
            <td class="px-4 py-2">{{ number_format($p->amount, 2) }}</td>
            <td class="px-4 py-2">{{ ucfirst($p->payment_method) }}</td>
            <td class="px-4 py-2">{{ $p->staff->first_name ?? 'N/A' }}</td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center text-gray-500 py-3">Aucun paiement enregistré</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Add Payment -->
    <div class="mt-6 add-payment-section {{ $remaining <= 0 ? 'hidden' : '' }}">
      <h3 class="text-md font-semibold text-gray-700 mb-2">Ajouter un nouveau paiement</h3>
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
        ➕ Ajouter le paiement
      </button>
    </div>
  </div>

  <!-- === Admin Audit Section === -->
  @if (Auth::check() && Auth::user()->role->role_name === 'Admin')
  <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-3">🧾 Informations internes</h2>
    <div class="grid grid-cols-2 gap-6 text-sm text-gray-600">
      <div><strong>Créé par :</strong> {{ $subscription->createdBy->first_name ?? 'System' }}</div>
      <div><strong>Mis à jour par :</strong> {{ $subscription->updatedBy->first_name ?? 'System' }}</div>
      <div><strong>Créé le :</strong> {{ $subscription->created_at->format('d/m/Y H:i') }}</div>
      <div><strong>Modifié le :</strong> {{ $subscription->updated_at->format('d/m/Y H:i') }}</div>
    </div>
  </div>
  @endif

  <!-- ACTIONS -->
  <div class="flex justify-end gap-4">
    <a href="{{ route('subscriptions.index') }}" 
       class="px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">Annuler</a>
    <button type="submit" 
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
      Enregistrer les modifications
    </button>
  </div>
</form>

<!-- === JS SECTION === -->
<script>
const planSelect = document.getElementById('planSelect');
const weeklyFields = document.getElementById('weeklyFields');
const visitsSelect = document.getElementById('visitsSelect');
const checkboxes = document.querySelectorAll('.day-checkbox');
const warning = document.getElementById('dayWarning');

planSelect.addEventListener('change', () => {
  const type = planSelect.options[planSelect.selectedIndex].dataset.type;
  weeklyFields.classList.toggle('hidden', type !== 'monthly_weekly');
});

function validateDays() {
  const checkedCount = [...checkboxes].filter(c => c.checked).length;
  const maxAllowed = parseInt(visitsSelect.value);
  if (checkedCount !== maxAllowed) {
    warning.classList.remove('hidden');
  } else {
    warning.classList.add('hidden');
  }
}
visitsSelect.addEventListener('change', validateDays);
checkboxes.forEach(c => c.addEventListener('change', validateDays));
</script>



<script>
document.addEventListener('DOMContentLoaded', () => {
  const visitsSelect = document.querySelector('select[name="visits_per_week"]');
  const checkboxes = document.querySelectorAll('.day-checkbox');

  function enforceDayLimit() {
    const limit = parseInt(visitsSelect.value || 0);
    const checked = [...checkboxes].filter(cb => cb.checked);
    if (checked.length > limit) {
      Swal.fire({
        icon: 'warning',
        title: 'Limite dépassée',
        text: `Vous pouvez sélectionner au maximum ${limit} jour(s).`,
        confirmButtonColor: '#2563eb'
      });
      checked.pop().checked = false;
    }
  }

  checkboxes.forEach(cb => cb.addEventListener('change', enforceDayLimit));
  visitsSelect?.addEventListener('change', () => checkboxes.forEach(cb => cb.checked = false));
});
</script>


<script>
document.getElementById('addPaymentBtn').addEventListener('click', async () => {
  const amount = document.getElementById('amount').value.trim();
  const method = document.getElementById('payment_method').value;
  const notes = document.getElementById('notes').value;

  if (!amount || !method) {
    Swal.fire({ icon: 'warning', title: 'Champs manquants', text: 'Veuillez remplir tous les champs requis.' });
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

  if (data.success) {
    Swal.fire({ icon: 'success', title: 'Succès', text: data.message, timer: 1800, showConfirmButton: false });

    // Update payment summary card dynamically
    document.querySelector('.payment-total').textContent = data.summary.totalPaid + ' DZD';
    document.querySelector('.payment-remaining').textContent = data.summary.remaining + ' DZD';

    // Optionally clear inputs
    document.getElementById('amount').value = '';
    document.getElementById('payment_method').value = '';
    document.getElementById('notes').value = '';

  } else {
    Swal.fire({ icon: 'error', title: 'Erreur', text: data.message });
  }
});
</script>

@endsection
