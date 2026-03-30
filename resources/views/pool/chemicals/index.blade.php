@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6" x-data="chemicalManager()">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg flex items-center gap-3">
            <svg class="h-5 w-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="text-green-800 font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg flex items-center gap-3">
            <svg class="h-5 w-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span class="text-red-800 font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Produits Chimiques</h1>
            <p class="text-slate-500">Gestion des stocks et consommation</p>
        </div>
        <button @click="showCreateModal = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nouveau Produit
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Stock Management -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">État des Stocks</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Produit</th>
                                <th class="px-6 py-3 font-semibold">Type</th>
                                <th class="px-6 py-3 font-semibold">En Stock</th>
                                <th class="px-6 py-3 font-semibold">Seuil Min.</th>
                                <th class="px-6 py-3 font-semibold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($chemicals as $chem)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $chem->name }}</td>
                                    <td class="px-6 py-4">{{ ucfirst(str_replace('_', ' ', $chem->type)) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold {{ $chem->quantity_available <= $chem->minimum_threshold ? 'text-red-600' : 'text-slate-700' }}">
                                            {{ $chem->quantity_available }} {{ $chem->unit }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400">{{ $chem->minimum_threshold }} {{ $chem->unit }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-1">
                                            {{-- Quick Add Stock --}}
                                            <form action="{{ route('pool.chemicals.update-stock', $chem) }}" method="POST" class="flex items-center gap-1">
                                                @csrf
                                                <input type="number" name="quantity_added" placeholder="+ Qté" class="w-20 text-xs rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500" min="0" step="0.1">
                                                <button type="submit" class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition" title="Ajouter du stock">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                            </form>

                                            {{-- Edit Button --}}
                                            <button @click="openEdit({
                                                chemical_id: {{ $chem->chemical_id }},
                                                name: '{{ addslashes($chem->name) }}',
                                                type: '{{ $chem->type }}',
                                                unit: '{{ $chem->unit }}',
                                                minimum_threshold: {{ $chem->minimum_threshold }}
                                            })"
                                                class="p-1.5 bg-amber-100 text-amber-600 rounded hover:bg-amber-200 transition" title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            {{-- Delete Button --}}
                                            <button @click="openDelete({{ $chem->chemical_id }}, '{{ addslashes($chem->name) }}')"
                                                class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition" title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Usage History -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800">Historique Consommation</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Date</th>
                                <th class="px-6 py-3 font-semibold">Produit</th>
                                <th class="px-6 py-3 font-semibold">Quantité</th>
                                <th class="px-6 py-3 font-semibold">Technicien</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($usageHistory as $usage)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3">{{ $usage->usage_date->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-3 font-medium">{{ $usage->chemical->name }}</td>
                                    <td class="px-6 py-3 text-red-600 font-medium">- {{ $usage->quantity_used }} {{ $usage->chemical->unit }}</td>
                                    <td class="px-6 py-3">{{ $usage->technician->name ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $usageHistory->links() }}
                </div>
            </div>
        </div>

        <!-- Record Usage Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-6 sticky top-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Enregistrer Consommation</h3>
                <form action="{{ route('pool.chemicals.store-usage') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Produit</label>
                        <select name="chemical_id" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            @foreach($chemicals as $chem)
                                <option value="{{ $chem->chemical_id }}">{{ $chem->name }} ({{ $chem->quantity_available }} {{ $chem->unit }} dispo)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Quantité Utilisée</label>
                        <input type="number" name="quantity_used" step="0.01" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date</label>
                        <input type="datetime-local" name="usage_date" value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Raison / Notes</label>
                        <textarea name="comments" rows="3" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="Traitement choc, régulation pH..."></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                        Enregistrer Sortie
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ======================== CREATE MODAL ======================== --}}
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false"></div>
        {{-- Modal --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="showCreateModal = false">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-blue-600 to-blue-700">
                <h3 class="text-lg font-bold text-white">Nouveau Produit Chimique</h3>
                <button @click="showCreateModal = false" class="text-white/80 hover:text-white transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('pool.chemicals.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nom du produit <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Chlore granulé">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type <span class="text-red-500">*</span></label>
                        <select name="type" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Sélectionner --</option>
                            <option value="chlorine">Chlore</option>
                            <option value="ph_adjuster">Ajusteur pH</option>
                            <option value="algaecide">Algicide</option>
                            <option value="flocculant">Floculant</option>
                            <option value="stabilizer">Stabilisant</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Unité <span class="text-red-500">*</span></label>
                        <select name="unit" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Sélectionner --</option>
                            <option value="kg">Kilogramme (kg)</option>
                            <option value="L">Litre (L)</option>
                            <option value="g">Gramme (g)</option>
                            <option value="mL">Millilitre (mL)</option>
                            <option value="unité">Unité</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Quantité initiale <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity_available" step="0.01" min="0" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Seuil minimum <span class="text-red-500">*</span></label>
                        <input type="number" name="minimum_threshold" step="0.01" min="0" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">Annuler</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ======================== EDIT MODAL ======================== --}}
    <div x-show="showEditModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50" @click="showEditModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="showEditModal = false">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-amber-500 to-amber-600">
                <h3 class="text-lg font-bold text-white">Modifier le Produit</h3>
                <button @click="showEditModal = false" class="text-white/80 hover:text-white transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form :action="editAction" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nom du produit <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="editData.name" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type <span class="text-red-500">*</span></label>
                        <select name="type" x-model="editData.type" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Sélectionner --</option>
                            <option value="chlorine">Chlore</option>
                            <option value="ph_adjuster">Ajusteur pH</option>
                            <option value="algaecide">Algicide</option>
                            <option value="flocculant">Floculant</option>
                            <option value="stabilizer">Stabilisant</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Unité <span class="text-red-500">*</span></label>
                        <select name="unit" x-model="editData.unit" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Sélectionner --</option>
                            <option value="kg">Kilogramme (kg)</option>
                            <option value="L">Litre (L)</option>
                            <option value="g">Gramme (g)</option>
                            <option value="mL">Millilitre (mL)</option>
                            <option value="unité">Unité</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Seuil minimum <span class="text-red-500">*</span></label>
                    <input type="number" name="minimum_threshold" x-model="editData.minimum_threshold" step="0.01" min="0" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">Annuler</button>
                    <button type="submit" class="px-5 py-2 bg-amber-500 text-white font-medium rounded-lg hover:bg-amber-600 transition shadow-sm">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ======================== DELETE CONFIRM MODAL ======================== --}}
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="showDeleteModal = false">
            <div class="p-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-7 w-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Confirmer la suppression</h3>
                <p class="text-slate-500 mb-6">
                    Êtes-vous sûr de vouloir supprimer <strong x-text="deleteName" class="text-slate-700"></strong> ?
                    <br><span class="text-xs text-red-500">Cette action est irréversible.</span>
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition">Annuler</button>
                    <form :action="deleteAction" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-5 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition shadow-sm">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function chemicalManager() {
    return {
        showCreateModal: false,
        showEditModal: false,
        showDeleteModal: false,
        editData: { chemical_id: null, name: '', type: '', unit: '', minimum_threshold: 0 },
        deleteId: null,
        deleteName: '',

        get editAction() {
            return `/pool/chemicals/${this.editData.chemical_id}`;
        },

        get deleteAction() {
            return `/pool/chemicals/${this.deleteId}`;
        },

        openEdit(data) {
            this.editData = { ...data };
            this.showEditModal = true;
        },

        openDelete(id, name) {
            this.deleteId = id;
            this.deleteName = name;
            this.showDeleteModal = true;
        }
    }
}
</script>
@endsection
