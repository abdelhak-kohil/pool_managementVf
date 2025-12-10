@extends('layouts.app')
@section('title', 'Gestion des Congés')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up" x-data="leavesManager()">
    
    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Gestion des Congés
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Suivi des absences, congés maladies et vacances de l'équipe.</p>
        </div>
        <div class="flex gap-3">
             <a href="{{ route('staff.planning.index') }}" class="group relative inline-flex items-center px-5 py-2.5 overflow-hidden rounded-xl bg-white border border-gray-200 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-sm">
                <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path></svg>
                Retour au Planning
            </a>
            <button @click="openModal()" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 border border-transparent rounded-xl shadow-lg text-sm font-semibold text-white hover:from-blue-700 hover:to-indigo-700 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Enregistrer une Absence
            </button>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Pending -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">En Attente</p>
                <p class="text-3xl font-extrabold text-amber-500 mt-1">{{ $leaves->where('status', 'pending')->count() }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <!-- Approved -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Approuvés</p>
                <p class="text-3xl font-extrabold text-emerald-500 mt-1">{{ $leaves->where('status', 'approved')->count() }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <!-- Rejected -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Refusés</p>
                <p class="text-3xl font-extrabold text-red-500 mt-1">{{ $leaves->where('status', 'rejected')->count() }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <!-- Total -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Demandes</p>
                <p class="text-3xl font-extrabold text-blue-600 mt-1">{{ $leaves->count() }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            </div>
        </div>
    </div>

    <!-- LEAVES TABLE -->
    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Membre Staff</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Période</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Type & Motif</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Durée</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th scope="col" class="relative px-6 py-4">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leaves as $leave)
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <!-- Staff -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-sm ring-2 ring-white">
                                    {{ substr($leave->staff->first_name, 0, 1) }}{{ substr($leave->staff->last_name, 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">{{ $leave->staff->full_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $leave->staff->role->role_name ?? 'Staff' }}</div>
                                </div>
                            </div>
                        </td>
                        <!-- Period -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">{{ $leave->start_date->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                {{ $leave->end_date->format('d M Y') }}
                            </div>
                        </td>
                        <!-- Type & Reason -->
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full w-fit
                                    {{ $leave->type === 'sick' ? 'bg-red-100 text-red-800 border border-red-200' : 
                                       ($leave->type === 'vacation' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 
                                       ($leave->type === 'absence' ? 'bg-orange-100 text-orange-800 border border-orange-200' : 'bg-gray-100 text-gray-800 border border-gray-200')) }}">
                                    {{ match($leave->type) { 'vacation' => '🌴 Congés', 'sick' => '🤒 Maladie', 'absence' => '🚫 Absence', default => '❓ ' . $leave->type } }}
                                </span>
                                @if($leave->reason)
                                    <span class="text-xs text-gray-500 italic truncate max-w-[200px]" title="{{ $leave->reason }}">{{Str::limit($leave->reason, 25) }}</span>
                                @endif
                            </div>
                        </td>
                        <!-- Duration -->
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                             {{ $leave->start_date->diffInDays($leave->end_date) + 1 }} jours
                        </td>
                        <!-- Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                             <select onchange="updateStatus({{ $leave->id }}, this.value)" 
                                class="text-xs font-semibold rounded-full px-3 py-1 pr-8 border-0 focus:ring-2 focus:ring-offset-1 cursor-pointer shadow-sm transition-all outline-none appearance-none
                                {{ $leave->status == 'approved' ? 'bg-emerald-100 text-emerald-800 focus:ring-emerald-500' : 
                                   ($leave->status == 'rejected' ? 'bg-red-100 text-red-800 focus:ring-red-500' : 'bg-amber-100 text-amber-800 focus:ring-amber-500') }}">
                                <option value="pending" {{ $leave->status == 'pending' ? 'selected' : '' }}>⏳ En attente</option>
                                <option value="approved" {{ $leave->status == 'approved' ? 'selected' : '' }}>✅ Approuvé</option>
                                <option value="rejected" {{ $leave->status == 'rejected' ? 'selected' : '' }}>❌ Refusé</option>
                            </select>
                        </td>
                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if (Auth::user()->role->role_name === 'admin' || Auth::user()->role->role_name === 'Admin')
                            <button onclick="deleteLeave({{ $leave->id }})" class="text-gray-400 hover:text-red-600 transition p-2 rounded-full hover:bg-red-50 opacity-0 group-hover:opacity-100 focus:opacity-100" title="Supprimer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Aucune demande de congé</h3>
                                <p class="text-gray-500 mt-1">Tout le monde est au travail !</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL -->
    <div x-show="showModal" 
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="closeModal()" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-5">
                       <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                           <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                               <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                           </div>
                           Nouvelle Absence
                       </h3>
                       <button @click="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                           <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                       </button>
                    </div>

                    <form action="{{ route('staff.leaves.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Membre du Staff</label>
                            <select name="staff_id" class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-2.5 bg-gray-50" required>
                                <option value="">Sélectionner...</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->staff_id }}">{{ $staff->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Date de début</label>
                                <input type="date" name="start_date" class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-2.5" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Date de fin</label>
                                <input type="date" name="end_date" class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-2.5" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Type d'absence</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-3 shadow-sm focus:outline-none hover:border-blue-400 hover:bg-blue-50 transition-all">
                                    <input type="radio" name="type" value="vacation" class="sr-only peer" checked>
                                    <span class="flex items-center">
                                        <span class="text-xl mr-3">🌴</span>
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-medium text-gray-900 peer-checked:text-blue-600">Congés Payés</span>
                                        </span>
                                    </span>
                                    <span class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-blue-600 opacity-0 peer-checked:opacity-100 ring-2 ring-white transition-opacity"></span>
                                    <span class="pointer-events-none absolute -inset-px rounded-xl border-2 border-transparent peer-checked:border-blue-500" aria-hidden="true"></span>
                                </label>
                                <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-3 shadow-sm focus:outline-none hover:border-red-400 hover:bg-red-50 transition-all">
                                    <input type="radio" name="type" value="sick" class="sr-only peer">
                                    <span class="flex items-center">
                                        <span class="text-xl mr-3">🤒</span>
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-medium text-gray-900 peer-checked:text-red-600">Maladie</span>
                                        </span>
                                    </span>
                                    <span class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-red-600 opacity-0 peer-checked:opacity-100 ring-2 ring-white transition-opacity"></span>
                                    <span class="pointer-events-none absolute -inset-px rounded-xl border-2 border-transparent peer-checked:border-red-500" aria-hidden="true"></span>
                                </label>
                                <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-3 shadow-sm focus:outline-none hover:border-orange-400 hover:bg-orange-50 transition-all">
                                    <input type="radio" name="type" value="absence" class="sr-only peer">
                                    <span class="flex items-center">
                                        <span class="text-xl mr-3">🚫</span>
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-medium text-gray-900 peer-checked:text-orange-600">Absence</span>
                                        </span>
                                    </span>
                                    <span class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-orange-600 opacity-0 peer-checked:opacity-100 ring-2 ring-white transition-opacity"></span>
                                    <span class="pointer-events-none absolute -inset-px rounded-xl border-2 border-transparent peer-checked:border-orange-500" aria-hidden="true"></span>
                                </label>
                                <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-3 shadow-sm focus:outline-none hover:border-gray-400 hover:bg-gray-50 transition-all">
                                    <input type="radio" name="type" value="other" class="sr-only peer">
                                    <span class="flex items-center">
                                        <span class="text-xl mr-3">❓</span>
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-medium text-gray-900 peer-checked:text-gray-600">Autre</span>
                                        </span>
                                    </span>
                                    <span class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-gray-600 opacity-0 peer-checked:opacity-100 ring-2 ring-white transition-opacity"></span>
                                    <span class="pointer-events-none absolute -inset-px rounded-xl border-2 border-transparent peer-checked:border-gray-500" aria-hidden="true"></span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Motif / Commentaire</label>
                            <textarea name="reason" rows="3" class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-2.5" placeholder="Ex: Rendez-vous médical..."></textarea>
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="closeModal()" class="flex-1 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200">Annuler</button>
                            <button type="submit" class="flex-1 px-4 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 shadow-md hover:shadow-lg transition focus:outline-none focus:ring-2 focus:ring-blue-500">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('leavesManager', () => ({
            showModal: false,
            openModal() { this.showModal = true; },
            closeModal() { this.showModal = false; }
        }));
    });

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    @if(session('success'))
        Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
    @endif
    
    @if(session('error'))
        Toast.fire({ icon: 'error', title: "{{ session('error') }}" });
    @endif

    @if($errors->any())
        Toast.fire({ icon: 'error', title: "{{ $errors->first() }}" });
    @endif

    function updateStatus(id, status) {
        fetch(`/reception/staff/leaves/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: status })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Toast.fire({ icon: 'success', title: 'Statut mis à jour' });
                setTimeout(() => window.location.reload(), 800);
            } else {
                Toast.fire({ icon: 'error', title: 'Erreur lors de la mise à jour' });
            }
        });
    }

    function deleteLeave(id) {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#e5e7eb',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: '<span class="text-gray-700">Annuler</span>'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/reception/staff/leaves/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({icon: 'success', title: 'Supprimé!', timer: 1500, showConfirmButton: false})
                        .then(() => window.location.reload());
                    } else {
                        Swal.fire('Erreur', 'Impossible de supprimer.', 'error');
                    }
                })
                .catch(err => Swal.fire('Erreur', 'Une erreur est survenue.', 'error'));
            }
        });
    }
</script>
@endsection
