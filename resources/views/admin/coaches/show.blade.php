@extends('layouts.app')
@section('title', 'Profil Coach - ' . $coach->full_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up" x-data="{ tab: 'planning' }">

    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-blue-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <a href="{{ route('coaches.index') }}" class="ml-1 text-sm font-medium text-gray-500 hover:text-blue-600 transition-colors">Coachs</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="ml-1 text-sm font-medium text-gray-800">{{ $coach->full_name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Header / Profile Card -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-8 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-bl-full -mr-16 -mt-16 transition-transform duration-700 group-hover:scale-110"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start md:justify-between gap-6">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <!-- Avatar -->
                <div class="relative">
                    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center text-3xl font-bold shadow-lg shadow-blue-200 transform group-hover:rotate-3 transition-transform duration-300">
                        {{ substr($coach->first_name, 0, 1) }}{{ substr($coach->last_name, 0, 1) }}
                    </div>
                    <div class="absolute -bottom-2 -right-2">
                         @if($coach->is_active)
                            <span class="inline-flex items-center justify-center w-8 h-8 bg-green-100 rounded-full border-2 border-white">
                                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                            </span>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 bg-red-100 rounded-full border-2 border-white">
                                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Info -->
                <div class="text-center md:text-left">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $coach->full_name }}</h1>
                    <div class="mt-2 flex flex-wrap items-center justify-center md:justify-start gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 border border-blue-100">
                             🔍 {{ $coach->specialty ?? 'Coach Sportif' }}
                        </span>
                        <span class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            {{ $coach->email ?? 'N/A' }}
                        </span>
                        <span class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2 3 3 0 003 3 2 2 0 012 2 3 3 0 003 3 2 2 0 012 2 3 3 0 012 2 13 13 0 01-13-13z"></path></svg>
                            {{ $coach->phone_number ?? 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <a href="{{ route('coaches.edit', $coach->staff_id) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Modifier
                </a>
                <form action="{{ route('coaches.destroy', $coach->staff_id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr ?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-red-200 rounded-xl shadow-sm text-sm font-medium text-red-600 hover:bg-red-50 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Left Column: Navigation & Stats -->
        <div class="w-full lg:w-1/4 space-y-6">
            
            <!-- Navigation Menu -->
            <nav class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <button @click="tab = 'planning'; setTimeout(() => renderCalendar(), 100)" 
                    :class="{'bg-blue-50 text-blue-700 border-l-4 border-blue-600': tab === 'planning', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': tab !== 'planning'}"
                    class="w-full flex items-center px-6 py-4 transition-colors duration-200 text-left font-medium">
                    <span class="text-xl mr-3">📅</span> Planning Semainier
                </button>
                <button @click="tab = 'info'" 
                    :class="{'bg-blue-50 text-blue-700 border-l-4 border-blue-600': tab === 'info', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': tab !== 'info'}"
                    class="w-full flex items-center px-6 py-4 transition-colors duration-200 text-left font-medium border-t border-gray-100">
                    <span class="text-xl mr-3">ℹ️</span> Infos & Contrat
                </button>
                <button @click="tab = 'stats'" 
                    :class="{'bg-blue-50 text-blue-700 border-l-4 border-blue-600': tab === 'stats', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': tab !== 'stats'}"
                    class="w-full flex items-center px-6 py-4 transition-colors duration-200 text-left font-medium border-t border-gray-100">
                    <span class="text-xl mr-3">📊</span> Performance
                </button>
            </nav>

            <!-- Quick Stats -->
            <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Aperçu
                </h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-blue-100 text-sm">Volume Hebdomadaire</p>
                        <p class="text-2xl font-bold">{{ $slots->count() }} Créneaux</p>
                    </div>
                    <div>
                        <p class="text-blue-100 text-sm">Dernier Accès</p>
                        <p class="font-medium text-white">{{ $coach->latestAccessLog ? $coach->latestAccessLog->access_time->format('d/m/Y à H:i') : 'Jamais' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Tab Content -->
        <div class="w-full lg:w-3/4">
            
            <!-- TAB: Planning -->
            <div x-show="tab === 'planning'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Planning Hebdomadaire</h3>
                        <a href="{{ route('coaches.planning') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Gérer le planning global &rarr;</a>
                    </div>
                    <div id="calendar" class="min-h-[600px]"></div>
                </div>
            </div>

            <!-- TAB: Info -->
            <div x-show="tab === 'info'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Info -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Informations Personnelles</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nom Complet</dt>
                                <dd class="text-base font-semibold text-gray-900">{{ $coach->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="text-base text-gray-900">{{ $coach->email ?? 'Non renseigné' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                                <dd class="text-base text-gray-900">{{ $coach->phone_number ?? 'Non renseigné' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Spécialité</dt>
                                <dd class="text-base text-gray-900">{{ $coach->specialty ?? 'Aucune' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Contract Details -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Détails du Contrat</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Date d'embauche</dt>
                                <dd class="text-base font-semibold text-gray-900">
                                    {{ $coach->hiring_date ? \Carbon\Carbon::parse($coach->hiring_date)->format('d/m/Y') : 'Non renseignée' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Type de Rémunération</dt>
                                <dd class="text-base text-gray-900">
                                    @if($coach->salary_type === 'per_hour') 
                                        Par Heure
                                    @elseif($coach->salary_type === 'per_session') 
                                        Par Séance
                                    @else 
                                        Mensuel Fixe
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Taux / Salaire</dt>
                                <dd class="text-base font-bold text-gray-900 text-green-600">
                                    {{ number_format($coach->hourly_rate, 2) }} DZD
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Statut du compte</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $coach->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $coach->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:col-span-2">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Notes & Remarques</h3>
                        <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100 text-yellow-800 text-sm whitespace-pre-line leading-relaxed">
                            {{ $coach->notes ?? 'Aucune note particulière pour ce coach.' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Stats (Placeholder) -->
            <div x-show="tab === 'stats'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Analytique bientôt disponible</h3>
                    <p class="text-gray-500 mt-2">Les rapports détaillés sur les performances des coachs seront ajoutés dans une prochaine mise à jour.</p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- FullCalendar Deps -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<script>
    let calendar = null;

    function renderCalendar() {
        if (calendar) {
            calendar.render(); // Just re-render if already exists
            return;
        }

        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'fr',
            firstDay: 6, // Samedi start
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '23:00:00',
            allDaySlot: false,
            expandRows: true,
            height: 'auto',
            events: @json($events),
            eventColor: '#3b82f6',
            eventDisplay: 'block',
            // Custom styling for events
            eventContent: function(arg) {
                return {
                    html: `
                        <div class="p-1 overflow-hidden">
                            <div class="font-bold text-xs">${arg.event.title}</div>
                            <div class="text-[10px] opacity-75">${arg.timeText}</div>
                        </div>
                    `
                };
            }
        });
        
        calendar.render();
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Initialize calendar immediately if on planning tab (which is default)
        setTimeout(() => {
            renderCalendar();
        }, 300);
    });
</script>

<style>
    /* FullCalendar Custom Overrides to match theme */
    .fc-theme-standard td, .fc-theme-standard th { border-color: #f3f4f6; }
    .fc-col-header-cell { background-color: #f9fafb; padding: 12px 0; }
    .fc .fc-toolbar-title { font-size: 1.25rem; font-weight: 700; color: #1f2937; }
    .fc .fc-button-primary { background-color: #ffffff; color: #374151; border-color: #e5e7eb; font-weight: 500; text-transform: capitalize; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s; }
    .fc .fc-button-primary:hover { background-color: #f9fafb; border-color: #d1d5db; color: #111827; }
    .fc .fc-button-primary:not(:disabled).fc-button-active { background-color: #eff6ff; border-color: #3b82f6; color: #2563eb; }
    .fc-v-event { background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 6px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .fc-timegrid-slot { height: 3rem; }
</style>
@endsection
