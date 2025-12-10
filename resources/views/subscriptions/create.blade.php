@extends('layouts.app')
@section('title', 'Nouvel Abonnement')

@section('content')
<form action="{{ route('subscriptions.store') }}" method="POST" class="space-y-8">
  @csrf

  <!-- 🔹 Informations du membre -->
  <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
      🧍‍♂️ Informations du Membre
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <!-- 1. Subscriber Selection (Searchable) -->
      <div class="md:col-span-2" x-data="memberSelector()">
        <label class="block text-gray-700 font-medium mb-1">Membre</label>
        
        <div class="relative">
          <input type="hidden" name="member_id" :value="selectedMember?.member_id">
          
          <input type="text" 
                 x-model="search"
                 @input="isOpen = true"
                 @click="isOpen = true"
                 @keydown.escape="isOpen = false"
                 @keydown.arrow-down.prevent="highlightNext()"
                 @keydown.arrow-up.prevent="highlightPrev()"
                 @keydown.enter.prevent="selectHighlighted()"
                 class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200"
                 placeholder="Rechercher un membre (Nom, Email, Tél)..."
                 autocomplete="off">

          <!-- Dropdown -->
          <ul x-show="isOpen && filteredMembers.length > 0" 
              @click.away="isOpen = false"
              class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto mt-1">
            <template x-for="(member, index) in filteredMembers" :key="member.member_id">
              <li @click="selectMember(member)"
                  :class="{'bg-blue-50 text-blue-700': index === highlightedIndex}"
                  class="p-2 hover:bg-gray-50 cursor-pointer border-b last:border-b-0">
                <div class="font-medium" x-text="`${member.first_name} ${member.last_name}`"></div>
                <div class="text-xs text-gray-500">
                  <span x-text="member.email"></span> • <span x-text="member.phone_number"></span>
                </div>
              </li>
            </template>
          </ul>

          <div x-show="isOpen && filteredMembers.length === 0" 
               class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg p-3 text-gray-500 mt-1">
            Aucun membre trouvé.
          </div>
        </div>
      </div>

      <!-- Divider -->
      <div class="md:col-span-2 border-t border-gray-100 my-2"></div>

      <!-- 2. Subscription Period -->
      <div>
        <label class="block text-gray-700 font-medium mb-1">Date de début</label>
        <input type="date" name="start_date"
               class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200"
               required>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Date de fin</label>
        <input type="date" name="end_date"
               class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200"
               required>
      </div>

      <!-- Divider -->
      <div class="md:col-span-2 border-t border-gray-100 my-2"></div>

      <!-- 3. Plan Configuration -->
      <div>
        <label class="block text-gray-700 font-medium mb-1">Activité</label>
        <select name="activity_id" id="activitySelect"
                class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
          <option value="">-- Sélectionner une activité --</option>
          @foreach($activities as $a)
            <option value="{{ $a->activity_id }}">{{ $a->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Plan</label>
        <select name="plan_id" id="planSelect"
                class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
          <option value="">-- Sélectionner un plan --</option>
          @foreach($plans as $p)
            <option value="{{ $p->plan_id }}" data-type="{{ $p->plan_type }}" data-visits="{{ $p->visits_per_week }}">
              {{ $p->plan_name }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Divider -->
      <div class="md:col-span-2 border-t border-gray-100 my-2"></div>

      <!-- 4. Financial & Status -->
      <div>
        <label class="block text-gray-700 font-medium mb-1">Prix (DZD)</label>
        <input type="text" id="priceDisplay"
               class="w-full border rounded-lg p-2 bg-gray-100 text-gray-700"
               readonly placeholder="En attente de sélection">
        <input type="hidden" name="price" id="priceValue">
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Statut</label>
        <select name="status"
                class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200">
          @foreach($statuses as $status)
            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </div>

    </div>
  </div>

  <!-- 🔹 Sélection des créneaux -->
  <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">
      🗓️ Créneaux disponibles pour cette activité
    </h2>

    <p class="text-gray-500 text-sm mb-3">Les créneaux affichés sont valides, non bloqués et non réservés.</p>

    <div id="timeSlotsContainer" class="space-y-3 text-gray-700">
      <p class="text-gray-500">Sélectionnez une activité et un plan...</p>
    </div>

    <p id="slotCountInfo" class="text-blue-600 font-medium mt-3 hidden"></p>
  </div>

  <!-- 🔹 Paiement -->
  <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
    <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center gap-2">💳 Paiement</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <div>
        <label class="block text-gray-700 font-medium mb-1">Montant payé (DZD)</label>
        <input type="number" step="0.01" name="amount" id="amount"
               class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Méthode de paiement</label>
        <select name="payment_method"
                class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200" required>
          <option value="cash">Espèces</option>
          <option value="card">Carte</option>
          <option value="transfer">Virement</option>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium mb-1">Notes</label>
        <textarea name="notes" rows="2"
                  class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-200"
                  placeholder="Ex: acompte, réduction, etc."></textarea>
      </div>

    </div>
  </div>

  <!-- 🔹 Actions -->
  <div class="flex justify-end gap-4">
    <a href="{{ route('subscriptions.index') }}"
       class="px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
      Annuler
    </a>
    <button type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
      Enregistrer
    </button>
  </div>
</form>

<!-- === Scripts === -->
<script>
let requiredSlots = 0;

// Charger slots disponibles
async function loadSlots() {
  const activityId = document.getElementById('activitySelect').value;
  const planSelect = document.getElementById('planSelect');
  const planId = planSelect.value;
  const planType = planSelect.options[planSelect.selectedIndex]?.dataset.type;
  const planVisits = parseInt(planSelect.options[planSelect.selectedIndex]?.dataset.visits);

  if (!activityId || !planId) return;

  requiredSlots = planType === "monthly_weekly" ? planVisits : 1;

  document.getElementById('slotCountInfo').classList.remove("hidden");
  document.getElementById('slotCountInfo').innerHTML =
      `🔔 Ce plan requiert <strong>${requiredSlots}</strong> créneau(x) par semaine.`;

  const container = document.getElementById('timeSlotsContainer');
  container.innerHTML = "<p class='text-gray-500'>Chargement...</p>";

  const res = await fetch(`/finance/activity-plan-prices/get-by-activity/${activityId}?plan=${planId}`);
  const data = await res.json();

  if (!data.slots || data.slots.length === 0) {
    container.innerHTML = "<p class='text-gray-500'>Aucun créneau disponible.</p>";
    return;
  }

  // afficher les slots
  container.innerHTML = data.slots.map(slot => `
    <label class="flex items-center gap-3 border rounded-lg p-3 hover:bg-blue-50 cursor-pointer">
      <input type="checkbox" name="slot_ids[]" value="${slot.slot_id}" class="slot-checkbox accent-blue-600">
      <span>🕒 ${slot.day_name} ${slot.start_time} → ${slot.end_time}</span>
    </label>
  `).join('');

  // validation dynamique : empêcher + que requiredSlots
  document.querySelectorAll('.slot-checkbox').forEach(cb => {
    cb.addEventListener('change', () => {
      const checked = document.querySelectorAll('.slot-checkbox:checked').length;
      if (checked > requiredSlots) {
        cb.checked = false;
        Swal.fire({
          icon: 'warning',
          title: 'Limite atteinte',
          text: `Vous devez choisir exactement ${requiredSlots} créneau(x).`
        });
      }
    });
  });
}

// Charger prix
async function loadPrice() {
  const activityId = document.getElementById('activitySelect').value;
  const planId = document.getElementById('planSelect').value;

  if (!activityId || !planId) return;

  const res = await fetch(`/finance/activity-plan-prices/get-by-activity/${activityId}?plan=${planId}`);
  const data = await res.json();

  if (data.price) {
    document.getElementById('priceDisplay').value = data.price.toFixed(2);
    document.getElementById('priceValue').value = data.price;
    document.getElementById('amount').value = data.price;
  }
}

// 📅 Date Rules Enforcement
const startDateInput = document.querySelector('input[name="start_date"]');
const endDateInput = document.querySelector('input[name="end_date"]');
const planSelect = document.getElementById('planSelect');
const perVisitInfo = document.createElement('div');
perVisitInfo.className = "md:col-span-2 bg-blue-50 text-blue-700 p-3 rounded-lg hidden mb-4";
perVisitInfo.innerHTML = "ℹ️ Les abonnements à la séance ne sont valables qu'aujourd'hui.";
// Insert after the grid container of dates
document.querySelector('.grid').appendChild(perVisitInfo);

// Helper to get Algiers date string YYYY-MM-DD
function getAlgiersTodayStr() {
    const now = new Date();
    // en-CA outputs YYYY-MM-DD
    return new Intl.DateTimeFormat('en-CA', { 
        timeZone: 'Africa/Algiers', 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit' 
    }).format(now);
}

// Set min date to today (Algiers time)
const todayStr = getAlgiersTodayStr();
startDateInput.min = todayStr;

function handlePlanChange() {
  const selectedOption = planSelect.options[planSelect.selectedIndex];
  const planType = selectedOption?.dataset.type;

  if (planType === 'per_visit') {
    // 🔒 Lock dates to today
    startDateInput.value = todayStr;
    endDateInput.value = todayStr;
    startDateInput.readOnly = true;
    endDateInput.readOnly = true;
    startDateInput.classList.add('bg-gray-100');
    endDateInput.classList.add('bg-gray-100');
    
    // Show info message
    perVisitInfo.classList.remove('hidden');
  } else {
    // 🔓 Unlock dates
    startDateInput.readOnly = false;
    endDateInput.readOnly = false;
    startDateInput.classList.remove('bg-gray-100');
    endDateInput.classList.remove('bg-gray-100');
    
    // Hide info message
    perVisitInfo.classList.add('hidden');
  }
  
  loadSlots();
  loadPrice();
}

startDateInput.addEventListener('change', function() {
  const dateVal = this.value;
  if (!dateVal) return;

  const dateObj = new Date(dateVal);
  const day = dateObj.getDate();

  // Auto-calculate end date (Last day of the month)
  const year = dateObj.getFullYear();
  const month = dateObj.getMonth();
  const lastDay = new Date(year, month + 1, 0); 
  
  // Format YYYY-MM-DD using local time components to avoid UTC shift
  const offset = lastDay.getTimezoneOffset();
  const lastDayLocal = new Date(lastDay.getTime() - (offset * 60 * 1000));
  const lastDayFormatted = lastDayLocal.toISOString().split('T')[0];
  
  // Only auto-fill end date if NOT per_visit (per_visit is locked to today)
  const selectedOption = planSelect.options[planSelect.selectedIndex];
  if (selectedOption?.dataset.type !== 'per_visit') {
      endDateInput.value = lastDayFormatted;
  }

  // Enable/Disable Monthly Plans
  const isFirstOfMonth = (day === 1);
  
  Array.from(planSelect.options).forEach(opt => {
    if (opt.dataset.type === 'monthly_weekly') {
      if (!isFirstOfMonth) {
        opt.disabled = true;
        if (planSelect.value === opt.value) {
          planSelect.value = "";
          planSelect.dispatchEvent(new Event('change'));
          Swal.fire({
            icon: 'info',
            title: 'Plan non disponible',
            text: 'Les abonnements mensuels ne peuvent commencer que le 1er du mois.'
          });
        }
      } else {
        opt.disabled = false;
      }
    }
  });
});

document.getElementById('activitySelect').addEventListener('change', () => {
  loadSlots();
  loadPrice();
});

planSelect.addEventListener('change', handlePlanChange);

// Override loadSlots to filter for per_visit
const originalLoadSlots = loadSlots;
loadSlots = async function() {
    const activityId = document.getElementById('activitySelect').value;
    const planId = planSelect.value;
    const planType = planSelect.options[planSelect.selectedIndex]?.dataset.type;
    const planVisits = parseInt(planSelect.options[planSelect.selectedIndex]?.dataset.visits);

    if (!activityId || !planId) return;

    requiredSlots = planType === "monthly_weekly" ? planVisits : 1;

    document.getElementById('slotCountInfo').classList.remove("hidden");
    document.getElementById('slotCountInfo').innerHTML =
        `🔔 Ce plan requiert <strong>${requiredSlots}</strong> créneau(x) par semaine.`;

    const container = document.getElementById('timeSlotsContainer');
    container.innerHTML = "<p class='text-gray-500'>Chargement...</p>";

    const res = await fetch(`/finance/activity-plan-prices/get-by-activity/${activityId}?plan=${planId}`);
    const data = await res.json();

    let slots = data.slots || [];

    // 🛡️ Filter for Per-Visit
    if (planType === 'per_visit') {
        const now = new Date();
        
        // Get Algiers time components
        const algiersOptions = { timeZone: 'Africa/Algiers', hour12: false };
        const currentHour = parseInt(now.toLocaleTimeString('en-US', { ...algiersOptions, hour: '2-digit' }));
        const currentMinute = parseInt(now.toLocaleTimeString('en-US', { ...algiersOptions, minute: '2-digit' }));
        const todayDayName = now.toLocaleDateString('en-US', { timeZone: 'Africa/Algiers', weekday: 'long' });
        
        // Quick mapping for safety
        const dayMap = {
            'Monday': 'Lundi', 'Tuesday': 'Mardi', 'Wednesday': 'Mercredi',
            'Thursday': 'Jeudi', 'Friday': 'Vendredi', 'Saturday': 'Samedi', 'Sunday': 'Dimanche'
        };
        const todayFrench = dayMap[todayDayName];

        slots = slots.filter(slot => {
            // 1. Must be today
            if (slot.day_name !== todayFrench) return false;

            // 2. Must be future
            const [h, m] = slot.start_time.split(':').map(Number);
            const [h2, m2] = slot.end_time.split(':').map(Number);

            if (currentHour<h ) {
                return false;
            }

            if(currentHour>h2){
                return false;
            }
            if(currentHour === h && currentMinute < m){
                return false;
            }

            if(currentHour === h2 && currentMinute > m2){
                return false;
            }
            return true;
        });

        if (slots.length === 0) {
            container.innerHTML = "<p class='text-red-500'>Aucun créneau disponible aujourd’hui pour cette activité.</p>";
            return;
        }
    }

    if (slots.length === 0) {
        container.innerHTML = "<p class='text-gray-500'>Aucun créneau disponible.</p>";
        return;
    }

    // 🎨 Render Slots Grouped by Day
    const grouped = slots.reduce((acc, slot) => {
        if (!acc[slot.day_name]) acc[slot.day_name] = [];
        acc[slot.day_name].push(slot);
        return acc;
    }, {});

    let html = '';
    for (const [day, daySlots] of Object.entries(grouped)) {
        html += `
        <div class="mb-6">
            <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span> ${day}
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                ${daySlots.map(slot => `
                <label class="relative cursor-pointer border rounded-xl p-3 flex flex-col items-center justify-center transition-all duration-200 hover:shadow-md hover:border-blue-300 bg-white group select-none">
                    <input type="checkbox" name="slot_ids[]" value="${slot.slot_id}" class="slot-checkbox hidden peer">
                    
                    <!-- Checkmark Icon (Top Right) -->
                    <div class="absolute top-2 right-2 text-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>

                    <span class="text-lg font-bold text-gray-800 peer-checked:text-blue-700">
                        ${slot.start_time.substring(0, 5)}
                    </span>
                    <span class="text-xs text-gray-500 peer-checked:text-blue-600">
                        à ${slot.end_time.substring(0, 5)}
                    </span>
                </label>
                `).join('')}
            </div>
        </div>`;
    }
    container.innerHTML = html;

    // ✨ Add interactivity & Validation
    document.querySelectorAll('.slot-checkbox').forEach(cb => {
        const label = cb.closest('label');
        
        // Function to update visual state
        const updateState = () => {
            if (cb.checked) {
                label.classList.add('bg-blue-50', 'border-blue-500', 'shadow-sm', 'ring-1', 'ring-blue-500');
                label.classList.remove('bg-white', 'border-gray-200');
            } else {
                label.classList.remove('bg-blue-50', 'border-blue-500', 'shadow-sm', 'ring-1', 'ring-blue-500');
                label.classList.add('bg-white', 'border-gray-200'); // Default border
            }
        };

        // Initial state check (if reloaded or pre-filled)
        updateState();

        cb.addEventListener('change', () => {
            const checkedCount = document.querySelectorAll('.slot-checkbox:checked').length;
            
            if (checkedCount > requiredSlots) {
                cb.checked = false;
                Swal.fire({
                    icon: 'warning',
                    title: 'Limite atteinte',
                    text: `Vous devez choisir exactement ${requiredSlots} créneau(x).`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            updateState();
        });
    });
};
  function memberSelector() {
    return {
      search: '',
      isOpen: false,
      highlightedIndex: 0,
      selectedMember: null,
      members: @json($members),

      get filteredMembers() {
        if (this.search === '') {
          return this.members;
        }
        const lowerSearch = this.search.toLowerCase();
        return this.members.filter(m => {
          return (m.first_name + ' ' + m.last_name).toLowerCase().includes(lowerSearch) ||
                 (m.email && m.email.toLowerCase().includes(lowerSearch)) ||
                 (m.phone_number && m.phone_number.includes(lowerSearch));
        });
      },

      selectMember(member) {
        this.selectedMember = member;
        this.search = `${member.first_name} ${member.last_name}`;
        this.isOpen = false;
      },

      highlightNext() {
        if (this.highlightedIndex < this.filteredMembers.length - 1) {
          this.highlightedIndex++;
        }
      },

      highlightPrev() {
        if (this.highlightedIndex > 0) {
          this.highlightedIndex--;
        }
      },

      selectHighlighted() {
        if (this.filteredMembers.length > 0) {
          this.selectMember(this.filteredMembers[this.highlightedIndex]);
        }
      }
    }
  }
</script>

@endsection
