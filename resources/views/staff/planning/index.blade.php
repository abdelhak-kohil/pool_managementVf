@extends('layouts.app')
@section('title', 'Planning du Staff')

@section('content')
<div class="max-w-[1920px] mx-auto p-6 md:p-8 animate-fade-in-up space-y-8">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-700 tracking-tight">
                Planning du Personnel
            </h1>
            <p class="mt-2 text-lg text-slate-500 font-medium max-w-2xl">
                Vue d'ensemble des plannings, disponibilités et absences de l'équipe administrative et technique.
            </p>
        </div>
        <div class="flex items-center gap-3">
             <a href="{{ route('staff.leaves.index') }}" 
                class="group relative inline-flex items-center px-6 py-3 rounded-2xl bg-white border border-slate-200 text-slate-700 font-semibold shadow-sm hover:shadow-md hover:border-purple-300 hover:text-purple-700 transition-all duration-200 ease-in-out">
                <div class="absolute inset-0 bg-purple-50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                <span class="relative flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500 transition-transform group-hover:scale-110 duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    Gestion des Absences
                </span>
            </a>
        </div>
    </div>

    <!-- CONTROLS CONTAINER -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-xl shadow-slate-200/40 p-6 flex flex-col xl:flex-row items-center justify-between gap-6 relative overflow-hidden">
        
        <!-- Decorative bg gradient -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-4 w-full xl:w-auto relative z-10">
            
            <!-- Search -->
            <div class="relative group w-full md:w-72">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" id="searchInput" placeholder="Rechercher un membre..." 
                       class="block w-full pl-11 pr-4 py-3 bg-slate-50/50 hover:bg-white border border-slate-200 rounded-xl text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 shadow-sm"
                       oninput="debounceRefresh()">
            </div>

            <!-- Staff Select -->
            <div class="relative group min-w-[200px]">
                 <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <select id="staffFilter" class="appearance-none block w-full pl-11 pr-10 py-3 bg-slate-50/50 hover:bg-white border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 shadow-sm cursor-pointer" onchange="refreshCalendar()">
                    <option value="">Tous les membres</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff->staff_id }}">{{ $staff->full_name }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

            <!-- Type Filter -->
            <div class="relative group min-w-[200px]">
                 <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <select id="typeFilter" class="appearance-none block w-full pl-11 pr-10 py-3 bg-slate-50/50 hover:bg-white border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 shadow-sm cursor-pointer" onchange="refreshCalendar()">
                    <option value="">Tous les types</option>
                    <optgroup label="Activités">
                        <option value="work">Travail</option>
                        <option value="meeting">Réunion</option>
                        <option value="training">Formation</option>
                    </optgroup>
                    <optgroup label="Absences">
                        <option value="vacation">Congés</option>
                        <option value="sick">Maladie</option>
                        <option value="absence">Absence</option>
                    </optgroup>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            
             <button onclick="clearFilters()" class="px-4 py-2 text-sm font-medium text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">Réinitialiser</button>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-600 bg-white/60 p-2.5 rounded-2xl border border-slate-100 shadow-inner">
            <span class="px-2 uppercase tracking-wider text-[10px] text-slate-400 font-bold">Légende:</span>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500 shadow shadow-blue-500/30"></span> Travail</div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500 shadow shadow-amber-500/30"></span> Réunion</div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500 shadow shadow-emerald-500/30"></span> Formation</div>
            <div class="h-4 w-px bg-slate-200 mx-1"></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-purple-500 shadow shadow-purple-500/30"></span> Congés</div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-400 shadow shadow-red-400/30"></span> Maladie</div>
        </div>
    </div>

    <!-- CALENDAR CARD -->
    <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 overflow-hidden relative">
        <div class="p-6 h-[calc(100vh-320px)] min-h-[750px]">
            <div id="calendar" class="h-full w-full font-inter"></div>
        </div>
    </div>

</div>

<!-- ADD MODAL -->
<div id="addModal" class="hidden fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" aria-hidden="true" onclick="closeAddModal()"></div>

    <!-- Modal Panel Wrapper -->
    <div class="flex min-h-screen items-center justify-center p-4 text-center">
        <div class="relative w-full max-w-lg transform overflow-hidden rounded-[2rem] bg-white text-left shadow-2xl transition-all">
            <div class="bg-white px-8 pt-8 pb-8">
                
                <!-- Close Button -->
                <button onclick="closeAddModal()" class="absolute top-6 right-6 p-2 rounded-full bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors z-10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Modal Title -->
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-900">Nouveau Créneau</h3>
                        <p class="text-slate-500 text-sm">Ajoutez une plage horaire au planning.</p>
                    </div>
                </div>

                <!-- Form -->
                <form id="addForm" onsubmit="submitSchedule(event)" class="space-y-6">
                    @csrf
                    
                    <!-- Staff -->
                    <div class="group">
                        <label class="block text-sm font-bold text-slate-700 mb-2.5">Membre du Personnel</label>
                        <div class="relative">
                            <select name="staff_id" id="modalStaffId" class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 py-3.5 pl-4 pr-10 text-slate-700 transition font-medium appearance-none cursor-pointer" required>
                                <option value="">Sélectionner un membre...</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->staff_id }}">{{ $staff->full_name }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <!-- Date -->
                        <div class="group">
                            <label class="block text-sm font-bold text-slate-700 mb-2.5">Date</label>
                            <input type="date" name="date" id="modalDate" class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 py-3.5 px-4 text-slate-700 transition font-medium" required>
                        </div>
                        <!-- Type -->
                        <div class="group">
                            <label class="block text-sm font-bold text-slate-700 mb-2.5">Type</label>
                            <div class="relative">
                                <select name="type" class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 py-3.5 pl-4 pr-10 text-slate-700 transition font-medium appearance-none cursor-pointer">
                                    <option value="work">Travail</option>
                                    <option value="meeting">Réunion</option>
                                    <option value="training">Formation</option>
                                    <option value="other">Autre</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Time Range -->
                    <div class="bg-slate-50 p-5 rounded-2xl border border-dashed border-slate-200 group focus-within:ring-4 focus-within:ring-blue-500/5 focus-within:border-blue-500/40 transition-all">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Plage Horaire</label>
                        <div class="flex items-center gap-4">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <input type="time" name="start_time" id="modalStart" class="block w-full rounded-xl border-slate-200 pl-10 text-center font-mono text-lg font-semibold text-slate-700 focus:border-blue-500 focus:ring-0 transition" required>
                            </div>
                            <span class="text-slate-300 font-bold text-xl">→</span>
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <input type="time" name="end_time" id="modalEnd" class="block w-full rounded-xl border-slate-200 pl-10 text-center font-mono text-lg font-semibold text-slate-700 focus:border-blue-500 focus:ring-0 transition" required>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="group">
                        <label class="block text-sm font-bold text-slate-700 mb-2.5">Notes</label>
                        <textarea name="notes" rows="3" class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 py-3.5 px-4 text-slate-700 placeholder-slate-400 transition" placeholder="Commentaires ou instructions (facultatif)"></textarea>
                    </div>

                    <div class="pt-6 flex gap-4">
                        <button type="button" onclick="closeAddModal()" class="flex-1 px-6 py-3.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-bold hover:bg-slate-50 hover:border-slate-300 transition-all focus:outline-none focus:ring-2 focus:ring-slate-200">
                            Annuler
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-[1.02] active:scale-95 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- DETAILS MODAL -->
<div id="detailsModal" class="hidden fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-md transition-opacity" aria-hidden="true" onclick="closeDetailsModal()"></div>
    
    <!-- Modal Panel Wrapper -->
    <div class="flex min-h-screen items-center justify-center p-4 text-center">
        <div class="relative w-full max-w-md transform overflow-hidden rounded-[2rem] bg-white text-left shadow-2xl transition-all">
            
            <!-- Close Button -->
            <button onclick="closeDetailsModal()" class="absolute top-4 right-4 z-20 w-8 h-8 flex items-center justify-center rounded-full bg-black/10 hover:bg-black/20 text-white transition backdrop-blur-md border border-white/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <!-- Dynamic Header -->
            <div id="d_header" class="h-32 bg-gradient-to-br from-blue-500 to-indigo-600 relative overflow-hidden">
                <!-- Decorative circles -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-8 -mt-8 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full -ml-8 -mb-8 blur-2xl"></div>

                <div class="absolute -bottom-10 left-8">
                    <div class="w-20 h-20 rounded-3xl bg-white shadow-xl flex items-center justify-center text-4xl border-4 border-white ring-1 ring-slate-100">
                        <span id="d_icon">📅</span>
                    </div>
                </div>
            </div>

            <div class="pt-12 px-8 pb-8">
                <!-- Title & Type -->
                <div class="mb-8">
                    <h3 id="d_staff" class="text-2xl font-bold text-slate-900 leading-tight">John Doe</h3>
                    <span id="d_type" class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-500">Travail</span>
                </div>

                <!-- Info Grid -->
                <div class="space-y-6">
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <div class="p-2.5 rounded-xl bg-white text-slate-400 shadow-sm border border-slate-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Date</p>
                            <p id="d_date" class="text-base font-semibold text-slate-800">Lundi 12 Octobre</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <div class="p-2.5 rounded-xl bg-white text-slate-400 shadow-sm border border-slate-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Horaire</p>
                            <p id="d_time" class="text-lg font-mono font-bold text-slate-800">09:00 - 17:00</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Notes & Remarques</p>
                        <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100 text-amber-900 text-sm italic relative">
                            <svg class="w-4 h-4 text-amber-300 absolute top-3 right-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                            <p id="d_notes">Aucune note pour ce créneau.</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                @if (Auth::user()->role->role_name === 'admin' || Auth::user()->role->role_name === 'Admin')
                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-center">
                    <button onclick="deleteCurrentSlot()" id="deleteSlotBtn" class="group flex items-center gap-2 text-red-500 hover:text-red-700 hover:bg-red-50 px-5 py-2.5 rounded-xl transition-all font-semibold text-sm">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Supprimer ce créneau
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<link href="{{ asset('vendor/fullcalendar/index.global.min.css') }}" rel="stylesheet" />
<script src="{{ asset('vendor/fullcalendar/index.global.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    let calendar;
    let selectedEventId = null;
    let searchTimeout;

    document.addEventListener('DOMContentLoaded', function() {
        
        // --- CRITICAL: FIX MODAL Z-INDEX LAYERING ---
        // Moves the modals to document.body to escape the stacking context of the content wrapper.
        const addModal = document.getElementById('addModal');
        const detailsModal = document.getElementById('detailsModal');
        if (addModal) document.body.appendChild(addModal);
        if (detailsModal) document.body.appendChild(detailsModal);
        // --------------------------------------------

        var calendarEl = document.getElementById('calendar');
        
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: '#fff',
            color: '#334155'
        });

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'fr',
            firstDay: 1, // Lundi
            buttonText: {
                today: "Aujourd'hui",
                month: 'Mois',
                week: 'Semaine',
                day: 'Jour',
                list: 'Liste'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '23:00:00',
            allDaySlot: true,
            height: '100%',
            editable: true, 
            selectable: true, 
            nowIndicator: true,
            eventColor: '#3b82f6',
            
            events: {
                url: '{{ route("staff.planning.events") }}',
                extraParams: function() {
                    return {
                        staff_id: document.getElementById('staffFilter').value,
                        search: document.getElementById('searchInput').value,
                        type: document.getElementById('typeFilter').value
                    };
                }
            },

            // Modern Event Rendering with tailwind
            eventContent: function(arg) {
                let timeText = arg.timeText;
                let title = arg.event.title;
                let type = arg.event.extendedProps.type;
                let notes = arg.event.extendedProps.notes;
                let isLeave = arg.event.extendedProps.is_leave;
                
                 let bgClass = {
                    'work': 'bg-blue-100/90 border-l-[3px] border-blue-500 text-blue-800 shadow-sm',
                    'meeting': 'bg-amber-100/90 border-l-[3px] border-amber-500 text-amber-800 shadow-sm',
                    'training': 'bg-emerald-100/90 border-l-[3px] border-emerald-500 text-emerald-800 shadow-sm',
                    'vacation': 'bg-purple-100/90 border-l-[3px] border-purple-500 text-purple-800 shadow-sm striped-bg',
                    'sick': 'bg-red-100/90 border-l-[3px] border-red-500 text-red-800 shadow-sm',
                    'absence': 'bg-orange-100/90 border-l-[3px] border-orange-500 text-orange-800 shadow-sm'
                }[type] || 'bg-slate-100 border-l-[3px] border-slate-500 text-slate-800 shadow-sm';

                let icon = {
                    'work': '💼', 'meeting': '🤝', 'training': '🎓',
                    'vacation': '🌴', 'sick': '🤒', 'absence': '🚫'
                }[type] || '';

                if (isLeave) {
                     return { html: `
                        <div class="h-full w-full p-2 rounded-r-lg ${bgClass} overflow-hidden flex flex-col justify-center hover:opacity-90 transition cursor-pointer">
                            <div class="font-bold text-xs truncate flex items-center gap-1.5">
                                <span class="text-sm opacity-80">${icon}</span> <span>${title}</span>
                            </div>
                        </div>
                    `};
                }

                return { html: `
                    <div class="h-full w-full p-1.5 rounded-r-lg ${bgClass} overflow-hidden flex flex-col hover:opacity-90 transition cursor-pointer">
                        <div class="flex justify-between items-start">
                            <div class="font-bold text-xs truncate flex items-center gap-1.5">
                                <span class="opacity-80">${icon}</span> 
                                <span>${title}</span>
                            </div>
                        </div>
                        <div class="text-[10px] font-semibold opacity-70 mt-0.5 tracking-wide">${timeText}</div>
                        ${notes ? `<div class="mt-auto text-[9px] opacity-60 truncate border-t border-black/5 pt-0.5 flex items-center gap-1"><svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg> Note</div>` : ''}
                    </div>
                `};
            },

            select: function(info) {
                document.getElementById('modalDate').value = info.startStr.split('T')[0];
                if (info.startStr.includes('T')) {
                    document.getElementById('modalStart').value = info.startStr.split('T')[1].substring(0, 5);
                    document.getElementById('modalEnd').value = info.endStr.split('T')[1].substring(0, 5);
                } else {
                    document.getElementById('modalStart').value = '09:00';
                    document.getElementById('modalEnd').value = '17:00';
                }
                const staffFilter = document.getElementById('staffFilter').value;
                if (staffFilter) {
                    document.getElementById('modalStaffId').value = staffFilter;
                }
                openAddModal();
            },

            eventClick: function(info) {
                const props = info.event.extendedProps;
                
                if (props.is_leave) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Gestion des Congés',
                        text: 'Ce créneau est un congé/absence. Gérez-le dans la section dédiée.',
                        confirmButtonText: 'Gestion des Congés',
                        confirmButtonColor: '#8b5cf6', // purple
                        showCancelButton: true,
                        cancelButtonText: 'Fermer',
                        background: '#f8fafc',
                        color: '#1e293b'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "{{ route('staff.leaves.index') }}";
                        }
                    });
                    return;
                }

                selectedEventId = info.event.id.replace('schedule-', '');
                document.getElementById('d_staff').textContent = info.event.title;
                
                // Set Header Color & Icon
                const header = document.getElementById('d_header');
                const iconSpan = document.getElementById('d_icon');
                const typeEl = document.getElementById('d_type');
                
                // Reset classes
                typeEl.className = "inline-block mt-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider";

                const styleMap = {
                    'work': { from: 'from-blue-500', to: 'to-blue-600', icon: '💼', text: 'Travail', typeClass: 'bg-blue-100 text-blue-700' },
                    'meeting': { from: 'from-amber-400', to: 'to-amber-500', icon: '🤝', text: 'Réunion', typeClass: 'bg-amber-100 text-amber-700' },
                    'training': { from: 'from-emerald-500', to: 'to-emerald-600', icon: '🎓', text: 'Formation', typeClass: 'bg-emerald-100 text-emerald-700' }
                };
                const style = styleMap[props.type] || { from: 'from-slate-500', to: 'to-slate-600', icon: '📅', text: props.type, typeClass: 'bg-slate-100 text-slate-700' };

                header.className = `h-32 bg-gradient-to-br ${style.from} ${style.to} relative overflow-hidden`;
                iconSpan.textContent = style.icon;
                typeEl.textContent = style.text;
                typeEl.classList.add(...style.typeClass.split(' '));

                document.getElementById('d_date').textContent = info.event.start.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
                document.getElementById('d_time').textContent = `${info.event.start.toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'})} - ${info.event.end.toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'})}`;
                document.getElementById('d_notes').textContent = props.notes || 'Aucune note pour ce créneau.';

                openDetailsModal();
            },

            eventDrop: function(info) { handleEventUpdate(info); },
            eventResize: function(info) { handleEventUpdate(info); }
        });

        calendar.render();

        function handleEventUpdate(info) {
            if (info.event.extendedProps.is_leave) {
                info.revert();
                Toast.fire({ icon: 'warning', title: 'Impossible de modifier un congé directement.' });
                return;
            }

            const id = info.event.id.replace('schedule-', '');
            const payload = {
                start: info.event.start.toISOString(),
                end: info.event.end.toISOString()
            };

            fetch(`/reception/staff/planning/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Toast.fire({ icon: 'success', title: 'Planning mis à jour' });
                } else {
                    info.revert();
                    Toast.fire({ icon: 'error', title: 'Erreur lors de la mise à jour' });
                }
            })
            .catch(() => {
                info.revert();
                Toast.fire({ icon: 'error', title: 'Erreur réseau' });
            });
        }
    });

    function refreshCalendar() { calendar.refetchEvents(); }
    
    function debounceRefresh() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { refreshCalendar(); }, 300);
    }
    
    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('staffFilter').value = '';
        document.getElementById('typeFilter').value = '';
        refreshCalendar();
    }

    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
        document.getElementById('addForm').reset();
    }

    function openDetailsModal() {
        document.getElementById('detailsModal').classList.remove('hidden');
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').classList.add('hidden');
    }

    function submitSchedule(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        fetch('{{ route("staff.planning.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            if (!res.ok) throw new Error(await res.text());
            return res.json();
        })
        .then(data => {
            closeAddModal();
            refreshCalendar();
            Swal.fire({
                icon: 'success',
                title: 'Excellent !',
                text: 'Le créneau a été ajouté avec succès.',
                timer: 2000,
                showConfirmButton: false,
                background: '#f0fdf4',
                color: '#166534'
            });
        })
        .catch(err => {
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Oups...', text: 'Une erreur est survenue.' });
        });
    }

    function deleteCurrentSlot() {
        if (!selectedEventId) return;

        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#f1f5f9',
            cancelButtonText: '<span class="text-slate-700 font-bold">Annuler</span>',
            confirmButtonText: 'Oui, supprimer',
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/reception/staff/planning/${selectedEventId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                })
                .then(res => {
                    if (res.ok) return res.json();
                    throw new Error('Erreur suppression');
                })
                .then(data => {
                    closeDetailsModal();
                    calendar.getEventById('schedule-' + selectedEventId).remove();
                    Swal.fire({icon: 'success', title: 'Supprimé !', timer: 1500, showConfirmButton: false});
                })
                .catch(err => {
                    Swal.fire('Erreur', 'Impossible de supprimer.', 'error');
                });
            }
        });
    }
</script>
<style>
    /* Custom Scrollbar for events */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }

    /* Polished FullCalendar UI */
    .fc-header-toolbar { @apply mb-6 gap-4 !important; }
    .fc-toolbar-title { @apply text-2xl font-bold text-slate-800 tracking-tight !important; }
    
    .fc-button-primary { 
        @apply bg-white border border-slate-200 text-slate-600 font-semibold px-4 py-2 rounded-xl shadow-sm hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition-all !important; 
    }
    .fc-button-active { 
        @apply bg-slate-900 border-slate-900 text-white shadow-md ring-2 ring-slate-200 ring-offset-1 !important; 
    }
    
    .fc-col-header-cell { 
        @apply bg-slate-50/50 py-4 border-b border-slate-100 !important; 
    }
    .fc-col-header-cell-cushion { 
        @apply text-sm uppercase tracking-widest font-bold text-slate-500 !important; 
    }
    
    .fc-timegrid-slot { @apply h-16 border-slate-50 !important; }
    .fc-theme-standard td, .fc-theme-standard th { @apply border-slate-100 !important; }
    
    .fc-timegrid-now-indicator-line { @apply border-red-500 border-2 !important; }
    .fc-timegrid-now-indicator-arrow { @apply border-red-500 border-2 !important; }

    /* Striped pattern for vacation */
    .striped-bg {
        background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.3) 10px, rgba(255,255,255,0.3) 20px);
    }
</style>
@endsection
