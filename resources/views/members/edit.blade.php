@extends('layouts.app')
@section('title', 'Modifier le Membre')

@section('content')
<div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">
    
    <!-- HEADER -->
    <div class="flex items-center justify-between mb-8">
        <div>
           <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Modifier le Membre
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Mettez à jour les informations, le badge et les abonnements du membre.</p>
        </div>
        <a href="{{ route('members.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour
        </a>
    </div>

    <form action="{{ route('members.update', $member->member_id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT COLUMN: Personal Info & Photo -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- 1. PERSONAL INFO -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">1</div>
                            <h3 class="text-lg font-semibold text-gray-800">Informations Personnelles</h3>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">ID: {{ $member->member_id }}</span>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Names -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name', $member->last_name) }}" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name', $member->first_name) }}" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all">
                        </div>

                        <!-- Contact -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email', $member->email) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $member->phone_number) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <!-- Date of Birth & Address -->
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date de naissance</label>
                            <input type="date" name="date_of_birth" value="{{ optional($member->date_of_birth)->format('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <input type="text" name="address" value="{{ old('address', $member->address) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                         <!-- Emergency Contact -->
                        <div class="md:col-span-2 pt-4 border-t border-gray-100">
                             <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Contact d'urgence</h4>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                  <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $member->emergency_contact_name) }}" placeholder="Nom" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                  <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $member->emergency_contact_phone) }}" placeholder="Téléphone" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                             </div>
                        </div>
                    </div>
                </div>

                <!-- 2. SUBSCRIPTIONS -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                     <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">2</div>
                        <h3 class="text-lg font-semibold text-gray-800">Abonnements</h3>
                    </div>

                    <div class="p-6">
                        @if ($member->subscriptions->isEmpty())
                           <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                               <p class="text-gray-500 text-sm">Aucun abonnement actif.</p>
                           </div>
                        @else
                           <div class="grid grid-cols-1 gap-4">
                               @foreach ($member->subscriptions as $sub)
                               <div class="border rounded-xl p-4 hover:shadow-sm transition-all bg-white group">
                                   <div class="flex justify-between items-start mb-3">
                                       <div>
                                           <h4 class="font-bold text-gray-900">{{ $sub->plan->plan_name ?? 'Plan Inconnu' }}</h4>
                                           <p class="text-xs text-gray-500 uppercase">{{ ucfirst(str_replace('_', ' ', $sub->plan->plan_type ?? '')) }}</p>
                                       </div>
                                       <span class="px-2 py-1 rounded text-xs font-bold {{ $sub->status === 'active' ? 'bg-green-100 text-green-700' : ($sub->status === 'paused' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                           {{ ucfirst($sub->status) }}
                                       </span>
                                   </div>
                                   
                                   <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
                                       <div> <span class="text-gray-400">Début:</span> {{ optional($sub->start_date)->format('Y-m-d') }}</div>
                                       <div> <span class="text-gray-400">Fin:</span> {{ optional($sub->end_date)->format('Y-m-d') }}</div>
                                   </div>

                                   <div class="pt-3 border-t border-gray-100">
                                       <label class="block text-xs font-medium text-gray-500 mb-1">Mettre à jour le statut</label>
                                       <select name="subscriptions[{{ $sub->subscription_id }}][status]" class="block w-full bg-gray-50 border-gray-200 text-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            @foreach (['active', 'paused', 'expired', 'cancelled'] as $status)
                                              <option value="{{ $status }}" {{ $sub->status === $status ? 'selected' : '' }}>
                                                {{ ucfirst($status) }}
                                              </option>
                                            @endforeach
                                       </select>
                                   </div>
                               </div>
                               @endforeach
                           </div>
                        @endif
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
                    <div class="p-6 space-y-5">
                       
                        <!-- Current Badge Display -->
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center">
                            <p class="text-xs text-indigo-500 uppercase font-bold mb-1">Badge Actuel</p>
                            @if($member->accessBadge)
                                <div class="text-2xl font-mono font-bold text-indigo-700 tracking-wider">{{ $member->accessBadge->badge_uid }}</div>
                                <span class="inline-block mt-2 px-2 py-0.5 bg-white text-indigo-600 text-xs rounded border border-indigo-200">{{ ucfirst($member->accessBadge->status) }}</span>
                            @else
                                <div class="text-lg text-gray-500 italic">Aucun badge</div>
                            @endif
                        </div>

                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Changer de Badge</label>
                            <select name="badge_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono">
                                <option value="">-- Conserver --</option>
                                @foreach($badges as $badge)
                                <option value="{{ $badge->badge_id }}">{{ $badge->badge_uid }} ({{ ucfirst($badge->status) }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Sélectionnez un nouveau badge libre pour remplacer l'actuel.</p>
                        </div>
                    </div>
                </div>
                
                 <!-- PHOTO UPLOAD -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photo de Profil</label>
                        
                        <!-- Create Grid for Current & New -->
                        <div class="flex items-start gap-4 mb-4">
                             @if($member->photo_path)
                                <div class="text-center">
                                    <div class="w-20 h-20 rounded-xl overflow-hidden border border-gray-200 mx-auto shadow-sm">
                                        <img src="{{ Storage::url($member->photo_path) }}" alt="Actuelle" class="w-full h-full object-cover">
                                    </div>
                                    <span class="text-xs text-gray-500 mt-1 block">Actuelle</span>
                                </div>
                             @endif
                             
                             <!-- Upload Area -->
                             <div class="flex-1">
                                <div class="flex justify-center px-4 pt-4 pb-4 border-2 border-gray-300 border-dashed rounded-xl hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer group relative" style="min-height: 80px;">
                                     <input type="file" name="photo" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" id="photoInput" onchange="previewImage(this)">
                                     <div class="space-y-1 text-center" id="uploadPlaceholder">
                                        <div class="text-gray-400 group-hover:text-blue-500 transition-colors">📷</div>
                                        <div class="text-xs text-gray-500">Modifier</div>
                                    </div>
                                    <img id="imagePreview" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl opacity-90" />
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- NOTES -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 space-y-4">
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes Internes</label>
                            <textarea name="notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('notes', $member->notes) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Santé / Allergies</label>
                            <textarea name="health_conditions" rows="3" class="block w-full rounded-lg border-yellow-300 bg-yellow-50 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 sm:text-sm text-yellow-800">{{ old('health_conditions', $member->health_conditions) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- AUDIT -->
                @if(Auth::check() && Auth::user()->role->role_name === 'admin')
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-xs text-gray-500 space-y-1 font-mono">
                    <div class="flex justify-between"><span>Créé le:</span> <span>{{ $member->created_at }}</span></div>
                    <div class="flex justify-between"><span>Créé par:</span> <span>{{ optional($member->createdBy)->first_name ?? 'Système' }}</span></div>
                    <div class="flex justify-between"><span>Modifié le:</span> <span>{{ $member->updated_at }}</span></div>
                    <div class="flex justify-between"><span>Modifié par:</span> <span>{{ optional($member->updatedBy)->first_name ?? 'Système' }}</span></div>
                </div>
                @endif

            </div>
        </div>

        <!-- FORM ACTIONS -->
        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
            <a href="{{ route('members.index') }}" class="px-6 py-3 rounded-xl text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 font-medium transition shadow-sm">
                Annuler
            </a>
            <button type="submit" class="px-8 py-3 rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 font-bold shadow-lg shadow-blue-200 hover:shadow-xl hover:scale-[1.02] transition-all duration-200">
                Enregistrer les Modifications
            </button>
        </div>

    </form>
</div>

<!-- SCRIPTS -->
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
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
    };
    
    // Notifications
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Succès',
            text: '{{ session('success') }}',
            timer: 3000,
            showConfirmButton: false
        });
    @endif
    @if (session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: '{{ session('error') }}'
        });
    @endif
</script>
<style>
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up { animation: fade-in-up 0.5s ease-out; }
</style>
@endsection
