@extends('layouts.app')
@section('title', 'Modifier Abonnement Groupe')

@section('content')
<form action="{{ route('subscriptions.groups.update', $subscription->subscription_id) }}" method="POST" class="space-y-8">
  @csrf
  @method('PUT')

  <!-- === PARTNER GROUP INFO === -->
  <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
      🏢 Informations du Groupe Partenaire
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <div>
        <label class="block text-gray-700 font-medium mb-1">Nom du Groupe</label>
        <input type="text" value="{{ $subscription->partnerGroup->name }}"
               class="w-full border rounded-lg p-2 bg-gray-100 text-gray-700" readonly>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Responsable</label>
        <input type="text" value="{{ $subscription->partnerGroup->contact_name ?? '—' }}"
               class="w-full border rounded-lg p-2 bg-gray-100 text-gray-700" readonly>
      </div>

      <div>
            <label class="block text-gray-700 font-medium mb-1">Téléphone</label>
            <input type="text" value="{{ $subscription->partnerGroup->contact_phone ?? '—' }}"
                class="w-full border rounded-lg p-2 bg-gray-100 text-gray-700" readonly>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Email</label>
        <input type="text" value="{{ $subscription->partnerGroup->contact_email ?? '—' }}"
               class="w-full border rounded-lg p-2 bg-gray-100 text-gray-700" readonly>
      </div>

    </div>
  </div>

  <!-- === ACTIVITY & PLAN === -->
  <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">🏊 Activité & Plan</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <div>
        <label class="block text-gray-700 font-medium mb-1">Activité</label>
        <select id="activitySelect" name="activity_id"
                class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
          <option value="">-- Sélectionner une activité --</option>
          @foreach($activities as $a)
            <option value="{{ $a->activity_id }}" data-activitytype="{{$subscription->activity_id }}" {{ $subscription->activity_id == $a->activity_id ? 'selected' : '' }}>
              {{ $a->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Plan</label>
        <select name="plan_id" id="planSelect"
                class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
          <option value="">-- Sélectionner un plan --</option>
          @foreach($plans as $p)
            <option value="{{ $p->plan_id }}" data-plantype="{{$p->plan_type}}" data-visitperweek="{{ $p->visits_per_week }}" {{$subscription->plan->plan_id == $p->plan_id ? 'selected' : ''}} >
              {{ $p->plan_name }} ({{ ucfirst(str_replace('_', ' ', $p->plan_type)) }})
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Prix (DZD)</label>
        <input type="text" id="planPrice" name="plan_price" readonly
               class="w-full border rounded-lg p-2 bg-gray-50 text-gray-700"
               value="{{ number_format($planPrice, 2) }}">
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
    </div>
  </div>

<!-- === DATES & TIME SLOTS === -->
<div class="bg-white rounded-xl shadow border border-gray-100 p-6">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
        📅 Détails de l’abonnement
    </h2>

    <!-- DATES -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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

    <!-- SLOTS AREA -->
    <div id="slotsArea">
        <h3 class="text-lg font-semibold text-blue-700 mb-3">🕒 Créneaux sélectionnés</h3>

        @php
            $currentSlots = $subscription->slots->pluck('slot_id')->toArray();
        @endphp

        <div id="slotsContainer" class="space-y-3">

            @forelse($slots as $slot)
                <label class="flex items-center gap-3 border rounded-lg p-3 bg-gray-50 hover:bg-blue-50 cursor-pointer">

                    <input type="checkbox"
                           name="slot_ids[]"
                           value="{{ $slot->slot_id }}"
                           class="slot-checkbox accent-blue-600"
                           {{ in_array($slot->slot_id, $currentSlots) ? 'checked' : '' }}>

                    <span>
                        <strong>{{ $slot->day_name }}</strong>  
                        {{ substr($slot->start_time, 0, 5) }} → {{ substr($slot->end_time, 0, 5) }}
                    </span>

                </label>
            @empty
                <p class="text-gray-600">Aucun créneau disponible pour cette activité.</p>
            @endforelse

        </div>

        <p class="text-sm text-gray-600 mt-2">
            <strong>Règle :</strong>
            Pour un plan <span id="planTypeLabel">{{ $subscription->plan->plan_type }}</span>,
            vous devez sélectionner le nombre exact de créneaux requis.
        </p>
    </div>
</div>

  <!-- === PAYMENTS === -->
  @include('subscriptions.partials.payments-summary')

  <!-- === ADMIN AUDIT === -->
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
    <a href="{{ route('subscriptions.groups') }}" class="px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">Annuler</a>
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
      💾 Enregistrer les modifications
    </button>
  </div>
</form>

<script>
const activitySelect = document.getElementById('activitySelect');
const planSelect = document.getElementById('planSelect');
const planPrice = document.getElementById('planPrice');
const slotsContainer = document.getElementById('slotsContainer');
const planTypeLabel = document.getElementById('planTypeLabel');
const startDateInput = document.querySelector('input[name="start_date"]');
const endDateInput = document.querySelector('input[name="end_date"]');

// No "per_visit" info box needed for groups generally, but logic can remain or be simplified.
// Keeping it simpler here as partner groups usually have monthly/custom plans.
// Or if they DO have per_visit, we should support it.
// Let's copy the robust logic from Member Edit just in case.

const perVisitInfo = document.createElement('div');
perVisitInfo.className = "md:col-span-2 bg-blue-50 text-blue-700 p-3 rounded-lg hidden mb-4";
perVisitInfo.innerHTML = "ℹ️ Les abonnements à la séance ne sont valables qu'aujourd'hui.";
endDateInput.closest('.grid').appendChild(perVisitInfo);

function getAlgiersTodayStr() {
    const now = new Date();
    return new Intl.DateTimeFormat('en-CA', { 
        timeZone: 'Africa/Algiers', 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit' 
    }).format(now);
}
const todayStr = getAlgiersTodayStr();

function handlePlanChange() {
    const selectedOption = planSelect.options[planSelect.selectedIndex];
    const planType = selectedOption?.dataset.plantype;
    
    if(planType) planTypeLabel.textContent = planType;

    if (planType === 'per_visit') {
        startDateInput.value = todayStr;
        endDateInput.value = todayStr;
        startDateInput.readOnly = true;
        endDateInput.readOnly = true;
        startDateInput.classList.add('bg-gray-100');
        endDateInput.classList.add('bg-gray-100');
        perVisitInfo.classList.remove('hidden');
    } else {
        startDateInput.readOnly = false;
        endDateInput.readOnly = false;
        startDateInput.classList.remove('bg-gray-100');
        endDateInput.classList.remove('bg-gray-100');
        perVisitInfo.classList.add('hidden');
    }
    
    refreshSlots();
    refreshprice();
}

startDateInput.addEventListener('change', function() {
  const dateVal = this.value;
  if (!dateVal) return;

  const dateObj = new Date(dateVal);
  const day = dateObj.getDate();
  const year = dateObj.getFullYear();
  const month = dateObj.getMonth();
  const lastDay = new Date(year, month + 1, 0); 
  const offset = lastDay.getTimezoneOffset();
  const lastDayLocal = new Date(lastDay.getTime() - (offset * 60 * 1000));
  const lastDayFormatted = lastDayLocal.toISOString().split('T')[0];
  
  const selectedOption = planSelect.options[planSelect.selectedIndex];
  if (selectedOption?.dataset.plantype !== 'per_visit') {
      endDateInput.value = lastDayFormatted;
  }
});

async function refreshSlots() {
    let activityId = activitySelect.value;
    let planId = planSelect.value;
    if (!activityId || !planId) return;

    slotsContainer.innerHTML = "<p class='text-gray-500'>Chargement des créneaux...</p>";

    const res = await fetch(`/finance/activity-plan-prices/get-by-activity/${activityId}?plan=${planId}`);
    const data = await res.json();

    let slots = data.slots || [];
    let planType = data.plan_type || "per_visit";
    let selected = @json($currentSlots);

    planTypeLabel.textContent = planType;
    let price = data.price || '0.00';
    planPrice.value = parseFloat(price).toFixed(2);

    slotsContainer.innerHTML = "";

    if (!slots.length) {
        slotsContainer.innerHTML = "<p class='text-gray-500'>Aucun créneau disponible.</p>";
        return;
    }

    // For groups, we might not strictly need the "future time only" filter for per_visit if they are booking ahead?
    // But let's keep consistent with system rules.

    slots.forEach(slot => {
        const label = document.createElement('label');
        label.className = "flex items-center gap-3 border rounded-lg p-3 bg-gray-50 hover:bg-blue-50 cursor-pointer";

        const checkbox = document.createElement('input');
        checkbox.type = "checkbox";
        checkbox.name = "slot_ids[]";
        checkbox.value = slot.slot_id;
        checkbox.className = "slot-checkbox accent-blue-600";
        if (selected.includes(slot.slot_id)) checkbox.checked = true;

        label.appendChild(checkbox);
        const span = document.createElement('span');
        span.innerHTML = `<strong>${slot.day_name}</strong> ${slot.start_time} → ${slot.end_time}`;
        label.appendChild(span);
        slotsContainer.appendChild(label);
    });

    attachSlotLimitLogic(planType);
}

async function refreshprice() {
    let activityId = activitySelect.value;
    let planId = planSelect.value;
    if (!activityId || !planId) return;
    const res = await fetch(`/finance/activity-plan-prices/get-by-activity/${activityId}?plan=${planId}`);
    const data = await res.json();
    let price = data.price || '0.00';
    planPrice.value = parseFloat(price).toFixed(2);
}

function attachSlotLimitLogic(planType) {
    const checkboxes = document.querySelectorAll('.slot-checkbox');
    if (planType === "per_visit") {
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    checkboxes.forEach(other => { if (other !== cb) other.checked = false; });
                }
            });
        });
    }

    if (planType === "monthly_weekly") {
        let select = document.getElementById('planSelect');
        let selected = select.options[select.selectedIndex];
        let visits = selected.dataset.visitperweek;
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const max = visits;
                let count = document.querySelectorAll('.slot-checkbox:checked').length;
                if (count > max) {
                    cb.checked = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Limite atteinte',
                        text: `Ce plan permet seulement ${max} créneau(x) par semaine.`
                    });
                }
            });
        });
    }
}

activitySelect.addEventListener('change', refreshSlots);
planSelect.addEventListener('change', () => handlePlanChange());

const initialPlanType = planSelect.options[planSelect.selectedIndex]?.dataset.plantype;
if (initialPlanType === 'per_visit') {
    startDateInput.readOnly = true;
    endDateInput.readOnly = true;
    startDateInput.classList.add('bg-gray-100');
    endDateInput.classList.add('bg-gray-100');
    perVisitInfo.classList.remove('hidden');
} else {
    startDateInput.readOnly = false;
    endDateInput.readOnly = false;
    startDateInput.classList.remove('bg-gray-100');
    endDateInput.classList.remove('bg-gray-100');
    perVisitInfo.classList.add('hidden');
}

attachSlotLimitLogic(initialPlanType);
</script>
@endsection
