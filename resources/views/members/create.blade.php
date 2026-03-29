@extends('layouts.app')
@section('title', 'Nouveau Membre')

@section('content')
<div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">
    
    <!-- HEADER -->
    <div class="flex items-center justify-between mb-8">
        <div>
           <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Nouveau Membre
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Ajoutez un nouveau membre, assignez un badge et configurez son abonnement.</p>
        </div>
        <a href="{{ route('members.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Veuillez corriger les erreurs suivantes :</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('members.store') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        <input type="hidden" name="status" value="active">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT COLUMN: Personal Info & Photo -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- 1. PERSONAL INFO -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">1</div>
                        <h3 class="text-lg font-semibold text-gray-800">Informations Personnelles</h3>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Names -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm animate-pulse-focus transition-all" placeholder="Nom de famille">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm animate-pulse-focus transition-all" placeholder="Prénom">
                        </div>

                        <!-- Contact -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </div>
                                <input type="email" name="email" class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="exemple@email.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                </div>
                                <input type="text" name="phone_number" class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="0555 123 456">
                            </div>
                        </div>

                        <!-- Date of Birth & Address -->
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date de naissance</label>
                            <input type="date" name="date_of_birth" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <input type="text" name="address" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Adresse complète">
                        </div>

                         <!-- Emergency Contact -->
                        <div class="md:col-span-2 pt-4 border-t border-gray-100">
                             <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Contact d'urgence</h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                  <input type="text" name="emergency_contact_name" placeholder="Nom du contact" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                  <input type="text" name="emergency_contact_phone" placeholder="Téléphone du contact" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                             </div>
                        </div>
                    </div>
                </div>

                <!-- 2. SUBSCRIPTIONS & SLOTS -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                     <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">2</div>
                        <h3 class="text-lg font-semibold text-gray-800">Abonnement & Créneaux</h3>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Activité <span class="text-red-500">*</span></label>
                                <select name="activity_id" id="activitySelect" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">Sélectionner une activité</option>
                                    @foreach($activities as $a)
                                    <option value="{{ $a->activity_id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Plan Tarifaire <span class="text-red-500">*</span></label>
                                <select name="plan_id" id="planSelect" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">Sélectionner un plan</option>
                                    @foreach($plans as $plan)
                                    <option value="{{ $plan->plan_id }}" data-type="{{ $plan->plan_type }}" data-visits="{{ $plan->visits_per_week }}">
                                        {{ $plan->plan_name }} ({{ $plan->plan_type === 'monthly_weekly' ? 'Mensuel' : 'Séance' }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date de début <span class="text-red-500">*</span></label>
                                <input type="date" name="start_date" id="startDate" required value="{{ now()->format('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin <span class="text-red-500">*</span></label>
                                <input type="date" name="end_date" id="endDate" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                        
                         <!-- Slot Picker -->
                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5">
                            <h4 class="text-sm font-bold text-blue-800 mb-1 flex items-center justify-between">
                                <span>Sélection des Créneaux</span>
                                <span id="slotCountInfo" class="text-xs font-normal bg-blue-100 text-blue-800 px-2 py-1 rounded hidden">Requis</span>
                            </h4>
                            <p class="text-xs text-blue-600/70 mb-4">Veuillez sélectionner les créneaux horaires pour cet abonnement.</p>
                            
                            <div id="timeSlotsContainer" class="min-h-[100px] flex items-center justify-center">
                                <span class="text-gray-400 text-sm italic">En attente de sélection (Activité + Plan)...</span>
                            </div>
                        </div>

                        <!-- Price & Payment -->
                         <div class="pt-4 border-t border-gray-100">
                             <div class="bg-gray-900 rounded-xl p-5 text-white shadow-lg">
                                 <div class="flex justify-between items-end mb-4">
                                     <div>
                                         <p class="text-gray-400 text-xs uppercase tracking-wider font-semibold">Total à payer</p>
                                         <div class="flex items-baseline gap-1">
                                             <span class="text-3xl font-extrabold tracking-tight" id="priceDisplay">0.00</span>
                                             <span class="text-sm font-medium text-gray-400">DZD</span>
                                         </div>
                                     </div>
                                      <div class="w-1/2">
                                          <label class="block text-xs font-medium text-gray-400 mb-1">Méthode de Paiement</label>
                                          <select name="payment_method" class="block w-full bg-gray-800 border-gray-700 text-white rounded-lg focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                              <option value="cash">💵 Espèces</option>
                                              <option value="card">💳 Carte Bancaire</option>
                                              <option value="transfer">🏦 Virement</option>
                                          </select>
                                      </div>
                                 </div>
                                 <input type="hidden" name="amount" id="amountInput">
                             </div>
                         </div>
                    </div>
                </div>

            </div>

             <!-- RIGHT COLUMN: Badge & Notes -->
            <div class="space-y-8">
                
                <!-- BADGE ASSIGNMENT -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                     <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">3</div>
                        <h3 class="text-lg font-semibold text-gray-800">Badge d'accès</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex flex-col items-center text-center">
                             <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-xl shadow-sm mb-2">🏷️</div>
                             <p class="text-sm text-indigo-900 font-medium">Assigner un badge physique</p>
                        </div>

                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numéro du Badge</label>
                            <select name="badge_uid" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono">
                                <option value="">-- Aucun --</option>
                                @foreach($freeBadges as $badge)
                                <option value="{{ $badge->badge_uid }}">{{ $badge->badge_uid }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Seuls les badges libres sont listés.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut Initial</label>
                            <select name="badge_status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                 <!-- PHOTO UPLOAD -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photo de Profil</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer group relative">
                             <input type="file" name="photo" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" id="photoInput" onchange="previewImage(this)">
                             <div class="space-y-1 text-center" id="uploadPlaceholder">
                                <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-blue-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <span class="relative cursor-pointer bg-transparent rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        Téléverser
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG jusqu'à 5MB</p>
                            </div>
                            <!-- Preview Preview -->
                            <img id="imagePreview" class="hidden max-h-40 rounded-lg shadow-sm" />
                        </div>
                    </div>
                </div>

                <!-- NOTES -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 space-y-4">
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes Internes</label>
                            <textarea name="notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Observations..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Santé / Allergies</label>
                            <textarea name="health_conditions" rows="3" class="block w-full rounded-lg border-yellow-300 bg-yellow-50 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 sm:text-sm text-yellow-800 placeholder-yellow-800/50" placeholder="Conditions médicales importantes..."></textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- FORM ACTIONS -->
        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
            <a href="{{ route('members.index') }}" class="px-6 py-3 rounded-xl text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 font-medium transition shadow-sm">
                Annuler
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 font-bold shadow-lg shadow-blue-200 hover:shadow-xl hover:scale-[1.02] transition-all duration-200">
                Enregistrer le Membre
            </button>
        </div>

    </form>
</div>

<!-- SCRIPTS -->
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
// --- Image Preview ---
function previewImage(input) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('uploadPlaceholder').classList.add('hidden');
            const img = document.getElementById('imagePreview');
            img.src = e.target.result;
            img.classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // --- State ---
    const planSelect = document.getElementById('planSelect');
    const activitySelect = document.getElementById('activitySelect');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const priceDisplay = document.getElementById('priceDisplay');
    const amountInput = document.getElementById('amountInput');
    const slotContainer = document.getElementById('timeSlotsContainer');
    const slotCountInfo = document.getElementById('slotCountInfo');
    
    let requiredSlots = 0;

    // --- Helper: Algiers Date ---
    function getAlgiersTodayStr() {
        return new Date().toLocaleDateString('en-CA', { timeZone: 'Africa/Algiers' });
    }
    const todayStr = getAlgiersTodayStr();
    startDateInput.setAttribute('min', todayStr);

    // --- Logic ---
    async function loadSlots() {
        const activityId = activitySelect.value;
        const planId = planSelect.value;
        
        if (!activityId || !planId) {
            slotContainer.innerHTML = "<span class='text-gray-400 text-sm italic'>En attente de sélection...</span>";
            return;
        }

        const planOption = planSelect.options[planSelect.selectedIndex];
        const planType = planOption.dataset.type;
        const planVisits = parseInt(planOption.dataset.visits || 1);
        
        requiredSlots = planType === 'monthly_weekly' ? planVisits : 1;
        slotCountInfo.classList.remove('hidden');
        slotCountInfo.textContent = `${requiredSlots} Créneau(x) Requis`;

        slotContainer.innerHTML = "<div class='animate-pulse text-blue-500 text-sm'>Chargement...</div>";

        try {
            const res = await fetch(`/finance/activity-plan-prices/get-by-activity/${activityId}?plan=${planId}`);
            const data = await res.json();
            let slots = data.slots || [];

            // Filter logic (simplified for implementation speed, can be enhanced)
            if (planType === 'per_visit') {
                 // Simple clientside filter or assume backend readiness
                 // For redesign, just showing them is key
            }

            if (slots.length === 0) {
                slotContainer.innerHTML = "<div class='text-red-500 text-sm p-4 text-center bg-red-50 rounded-lg w-full'>Aucun créneau disponible.</div>";
                return;
            }

            // Group by Day
            const grouped = slots.reduce((acc, s) => {
                acc[s.day_name] = acc[s.day_name] || [];
                acc[s.day_name].push(s);
                return acc;
            }, {});

            let html = "<div class='w-full space-y-4'>";
            for (const [day, daySlots] of Object.entries(grouped)) {
                html += `
                <div>
                    <h5 class='text-xs font-bold text-gray-500 uppercase mb-2 ml-1'>${day}</h5>
                    <div class='grid grid-cols-2 lg:grid-cols-3 gap-2'>
                        ${daySlots.map(s => `
                            <label class='cursor-pointer relative overflow-hidden group'>
                                <input type='checkbox' name='slot_ids[]' value='${s.slot_id}' data-day='${day}' class='slot-checkbox peer sr-only'>
                                <div class='border border-gray-200 rounded-lg p-2 text-center transition-all peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 hover:border-blue-300'>
                                    <div class='text-sm font-bold'>${s.start_time.slice(0,5)}</div>
                                    <div class='text-[10px] text-gray-500 peer-checked:text-blue-200'>à ${s.end_time.slice(0,5)}</div>
                                </div>
                            </label>
                        `).join('')}
                    </div>
                </div>`;
            }
            html += "</div>";
            slotContainer.innerHTML = html;

            // Slot Validation Logic
            document.querySelectorAll('.slot-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    if (!this.checked) return;

                    const checkedBoxes = Array.from(document.querySelectorAll('.slot-checkbox:checked'));
                    
                    if (checkedBoxes.length > requiredSlots) {
                        this.checked = false;
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'warning',
                            title: `Maximum ${requiredSlots} créneaux`,
                            showConfirmButton: false,
                            timer: 2000
                        });
                        return;
                    }
                    
                    const myDay = this.dataset.day;
                    const sameDayChecked = checkedBoxes.filter(box => box.dataset.day === myDay);
                    if (sameDayChecked.length > 1) {
                        this.checked = false;
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'warning',
                            title: `Limite journalière`,
                            text: `Un seul créneau permis par jour (${myDay}).`,
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            });

        } catch (e) {
            console.error(e);
            slotContainer.innerHTML = "<div class='text-red-500'>Erreur de chargement.</div>";
        }
    }

    async function loadPrice() {
        const activityId = activitySelect.value;
        const planId = planSelect.value;
        if (!activityId || !planId) return;

        try {
            const res = await fetch(`/finance/activity-plan-prices/get-by-activity/${activityId}?plan=${planId}`);
            const data = await res.json();
            if (data.price !== undefined) {
                priceDisplay.innerText = parseFloat(data.price).toFixed(2);
                amountInput.value = data.price;
            } else {
                priceDisplay.innerText = "0.00";
            }
        } catch (e) { console.error(e); }
    }

    function handlePlanChange() {
        loadSlots();
        loadPrice();
        
        // Date Locking for Per Visit
        const planOption = planSelect.options[planSelect.selectedIndex];
        if (planOption && planOption.dataset.type === 'per_visit') {
            startDateInput.value = todayStr;
            endDateInput.value = todayStr;
            startDateInput.readOnly = true;
            endDateInput.readOnly = true;
            startDateInput.classList.add('bg-gray-100');
            endDateInput.classList.add('bg-gray-100');
        } else {
            startDateInput.readOnly = false;
            endDateInput.readOnly = false;
             startDateInput.classList.remove('bg-gray-100');
            endDateInput.classList.remove('bg-gray-100');
        }
    }

    // --- Listeners ---
    activitySelect.addEventListener('change', () => { loadSlots(); loadPrice(); });
    planSelect.addEventListener('change', handlePlanChange);

    // Auto-Select End Date logic (Optional Polish)
    startDateInput.addEventListener('change', function() {
         const planOption = planSelect.options[planSelect.selectedIndex];
         if (planOption && planOption.dataset.type === 'monthly_weekly') {
             const d = new Date(this.value);
             // Logic to set end date to end of month? Keep simple for now or mirror previous
         }
    });

    if(planSelect.value && activitySelect.value) handlePlanChange();
});
</script>
<style>
    /* Custom animations */
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up { animation: fade-in-up 0.5s ease-out; }
</style>
@endsection
