@extends('layouts.app')
@section('title', 'Rapports Coachs')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Rapports & Paie
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Gérez la rémunération et analysez les performances de vos coachs.</p>
        </div>
        <a href="{{ route('coaches.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
            <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour à la liste
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Individual Report Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 sm:p-8">
                <div class="flex items-center gap-5 mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-200">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Rapport Individuel</h3>
                        <p class="text-sm text-gray-500 mt-1">Générez le rapport détaillé pour un coach spécifique.</p>
                    </div>
                </div>
                
                <form id="reportForm" method="GET" class="space-y-6">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sélectionner un Coach</label>
                            <div class="relative">
                                <select name="coach_id" required class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition py-3 pl-4 pr-10 text-gray-900">
                                    <option value="">-- Choisir dans la liste --</option>
                                    @foreach($coaches as $coach)
                                    <option value="{{ $coach->staff_id }}">{{ $coach->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Mois</label>
                                <select name="month" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition py-3 text-gray-900">
                                    @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::createFromDate(null, $m, 1)->locale('fr')->monthName }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Année</label>
                                <select name="year" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition py-3 text-gray-900">
                                    @foreach(range(now()->year, now()->year - 5) as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <button type="button" onclick="openPreview(event)" class="px-4 py-2.5 bg-gray-900 hover:bg-black text-white font-medium rounded-xl shadow-lg shadow-gray-200 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                             Aperçu
                        </button>
                        <button type="submit" formaction="{{ route('coaches.reports.export') }}" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-lg shadow-blue-200 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            PDF
                        </button>
                        <button type="submit" formaction="{{ route('coaches.reports.excel') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-200 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                             Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Global Report Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 sm:p-8">
                <div class="flex items-center gap-5 mb-8">
                     <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-200">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Rapport Global</h3>
                        <p class="text-sm text-gray-500 mt-1">Vue d'ensemble de tous les coachs et masse salariale.</p>
                    </div>
                </div>

                <form action="{{ route('coaches.reports.global') }}" method="GET" class="space-y-6">
                    <div class="p-5 bg-indigo-50 rounded-xl border border-indigo-100">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="text-sm text-indigo-900 leading-relaxed">
                                <strong>Ce rapport comprend :</strong>
                                <ul class="list-disc list-inside mt-2 space-y-1 text-indigo-800 ml-1">
                                    <li>Résumé des heures par coach</li>
                                    <li>Nombre de sessions validées</li>
                                    <li>Calcul détaillé de la rémunération</li>
                                    <li>Total global de la masse salariale</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mois</label>
                            <select name="month" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition py-3 text-gray-900">
                                @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate(null, $m, 1)->locale('fr')->monthName }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Année</label>
                            <select name="year" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition py-3 text-gray-900">
                                @foreach(range(now()->year, now()->year - 5) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pt-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="submit" formaction="{{ route('coaches.reports.global') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-200 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                             PDF Global
                        </button>
                        <button type="submit" formaction="{{ route('coaches.reports.global.excel') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-200 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                             Excel Global
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop"></div>

        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-3xl sm:w-full opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" id="modalContent">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                         <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900" id="modal-title">Aperçu du Rapport</h3>
                            <p class="text-sm text-gray-500">Vérifiez les données avant l'exportation</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeModal()" class="bg-white rounded-full p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-6 space-y-6">
                    <!-- Coach Info -->
                    <div class="flex items-center justify-between bg-blue-50 p-5 rounded-2xl border border-blue-100">
                        <div>
                            <p class="text-sm text-blue-600 font-bold uppercase tracking-wide opacity-80">Coach</p>
                            <p class="text-2xl font-extrabold text-blue-900 mt-1" id="modalCoachName">--</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-blue-600 font-bold uppercase tracking-wide opacity-80">Période</p>
                            <p class="text-xl font-bold text-blue-900 mt-1" id="modalPeriod">--</p>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center text-center group hover:border-blue-200 transition-colors">
                            <div class="p-3 bg-gray-50 rounded-full mb-3 group-hover:bg-blue-50 transition-colors">
                                 <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Sessions</p>
                            <p class="text-3xl font-extrabold text-gray-900 mt-1" id="modalSessions">0</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center text-center group hover:border-blue-200 transition-colors">
                            <div class="p-3 bg-gray-50 rounded-full mb-3 group-hover:bg-blue-50 transition-colors">
                                 <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Heures</p>
                            <p class="text-3xl font-extrabold text-gray-900 mt-1" id="modalHours">0h</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center text-center group hover:border-green-200 transition-colors">
                             <div class="p-3 bg-gray-50 rounded-full mb-3 group-hover:bg-green-50 transition-colors">
                                 <svg class="w-6 h-6 text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Salaire Est.</p>
                            <p class="text-3xl font-extrabold text-green-600 mt-1" id="modalSalary">0 DZD</p>
                        </div>
                    </div>

                    <!-- Sessions Table -->
                    <div class="bg-white border text-left border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                             <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                Détail des sessions validées
                            </h4>
                        </div>
                        <div class="max-h-60 overflow-y-auto custom-scrollbar">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white sticky top-0 shadow-sm">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Horaire</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Activité</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100" id="modalSessionsTable">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="bg-gray-50 px-6 py-4 flex flex-col sm:flex-row justify-end gap-3 border-t border-gray-100">
                    <button type="button" onclick="closeModal()" class="w-full sm:w-auto px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-medium rounded-xl shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Fermer
                    </button>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <button onclick="submitDownload('pdf')" class="flex-1 sm:flex-none w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-lg shadow-blue-200 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Télécharger PDF
                        </button>
                        <button onclick="submitDownload('excel')" class="flex-1 sm:flex-none w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-200 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Télécharger Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
    
    /* Animation classes for Modal */
    .modal-enter { opacity: 0; }
    .modal-enter-active { opacity: 1; transition: opacity 300ms ease-out; }
    .modal-exit { opacity: 1; }
    .modal-exit-active { opacity: 0; transition: opacity 200ms ease-in; }
    
    .modal-content-enter { opacity: 0; transform: translateY(1rem) scale(0.95); }
    .modal-content-enter-active { opacity: 1; transform: translateY(0) scale(1); transition: all 300ms ease-out; }
    .modal-content-exit { opacity: 1; transform: translateY(0) scale(1); }
    .modal-content-exit-active { opacity: 0; transform: translateY(1rem) scale(0.95); transition: all 200ms ease-in; }
</style>

<script>
function openPreview(e) {
    if (e) e.preventDefault();
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    if (!formData.get('coach_id')) {
        Swal.fire({
            icon: 'warning',
            title: 'Attention',
            text: 'Veuillez sélectionner un coach.',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }

    // Show loading state if desired
    
    const params = new URLSearchParams(formData);

    fetch(`{{ route('coaches.reports.preview') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalCoachName').textContent = data.coach_name;
            document.getElementById('modalPeriod').textContent = data.period; // Ensure backend sends formatted period string if needed, or format here
            document.getElementById('modalSessions').textContent = data.sessions_count;
            document.getElementById('modalHours').textContent = data.total_hours + 'h';
            document.getElementById('modalSalary').textContent = data.salary;

            const tbody = document.getElementById('modalSessionsTable');
            tbody.innerHTML = '';
            
            if (data.sessions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-12 text-center text-gray-400 text-sm italic">Aucune session validée trouvée pour cette période.</td></tr>';
            } else {
                data.sessions.forEach(session => {
                    const row = `
                        <tr class="hover:bg-blue-50/50 transition border-b border-gray-50 last:border-0">
                            <td class="px-4 py-3 text-sm text-gray-900 font-medium whitespace-nowrap">${session.date} <span class="text-xs text-gray-400 font-normal ml-1">(${session.day_name})</span></td>
                            <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">${session.start_time.substring(0,5)} - ${session.end_time.substring(0,5)}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    ${session.activity}
                                </span>
                            </td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
            }

            showModal();
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Erreur lors du chargement de l\'aperçu.',
                confirmButtonColor: '#ef4444'
            });
        });
}

function showModal() {
    const modal = document.getElementById('previewModal');
    const backdrop = document.getElementById('modalBackdrop');
    const content = document.getElementById('modalContent');
    
    modal.classList.remove('hidden');
    // Trigger reflow
    void modal.offsetWidth;
    
    backdrop.classList.remove('opacity-0');
    content.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
}

function closeModal() {
    const modal = document.getElementById('previewModal');
    const backdrop = document.getElementById('modalBackdrop');
    const content = document.getElementById('modalContent');
    
    backdrop.classList.add('opacity-0');
    content.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function submitDownload(type) {
    const form = document.getElementById('reportForm');
    if (type === 'pdf') {
        form.action = "{{ route('coaches.reports.export') }}";
    } else {
        form.action = "{{ route('coaches.reports.excel') }}";
    }
    form.submit();
    closeModal();
}

// Close modal on backdrop click
document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this || e.target.id === 'modalBackdrop') {
        closeModal();
    }
});
</script>
@endsection
