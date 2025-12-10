@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.tasks.templates.index') }}" class="text-slate-500 hover:text-slate-700 flex items-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour aux modèles
        </a>
        <h1 class="text-3xl font-bold text-slate-800">Modifier le Modèle</h1>
        <p class="text-slate-500">{{ $template->name }}</p>
    </div>

    <div class="max-w-4xl mx-auto" x-data="templateBuilder({{ json_encode($template->items) }})">
        <form action="{{ route('pool.tasks.templates.update', $template) }}" method="POST">
            @csrf
            @method('PUT')
            
            <!-- General Info -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h2 class="text-lg font-bold text-slate-800 mb-4">Informations Générales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nom du Modèle</label>
                        <input type="text" name="name" value="{{ $template->name }}" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type de Tâche</label>
                        <select name="type" required class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            <option value="daily" {{ $template->type == 'daily' ? 'selected' : '' }}>Journalier</option>
                            <option value="weekly" {{ $template->type == 'weekly' ? 'selected' : '' }}>Hebdomadaire</option>
                            <option value="monthly" {{ $template->type == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-slate-700">Définir comme modèle actif</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Builder -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">Champs de la Check-list</h2>
                    <button type="button" @click="addItem()" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter un champ
                    </button>
                </div>

                <div class="space-y-4" id="items-container">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 relative group">
                            <button type="button" @click="removeItem(index)" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Libellé (Label)</label>
                                    <input type="text" x-model="item.label" class="w-full text-sm rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Type de Champ</label>
                                    <select x-model="item.type" class="w-full text-sm rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                                        <option value="checkbox">Case à cocher (Oui/Non)</option>
                                        <option value="number">Nombre (Mesure)</option>
                                        <option value="text">Texte court</option>
                                        <option value="textarea">Texte long (Commentaire)</option>
                                        <option value="select">Liste déroulante</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Clé (Interne)</label>
                                    <input type="text" x-model="item.key" readonly class="w-full text-sm rounded border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed">
                                </div>
                            </div>

                            <!-- Options for Select Type -->
                            <div x-show="item.type === 'select'" class="mt-4 pt-4 border-t border-slate-200">
                                <label class="block text-xs font-medium text-slate-500 mb-2">Options (Format: valeur:Libellé, séparées par virgule)</label>
                                <input type="text" x-model="item.optionsStr" placeholder="ok:OK, noisy:Bruyante, off:Arrêt" class="w-full text-sm rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </template>
                </div>
                
                <div x-show="items.length === 0" class="text-center py-8 text-slate-400 border-2 border-dashed border-slate-200 rounded-lg">
                    Aucun champ défini.
                </div>
            </div>

            <!-- Hidden Input for JSON Items -->
            <input type="hidden" name="items" :value="JSON.stringify(items)">

            <div class="flex justify-end gap-3">
                <a href="{{ route('pool.tasks.templates.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition">Annuler</a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>

<script>
    function templateBuilder(initialItems) {
        // Transform initial items if needed (e.g. handle options object to string)
        const items = initialItems ? initialItems.map(item => {
            if (item.type === 'select' && typeof item.options === 'object') {
                item.optionsStr = Object.entries(item.options).map(([k, v]) => `${k}:${v}`).join(', ');
            }
            return item;
        }) : [];

        return {
            items: items,
            addItem() {
                this.items.push({
                    label: 'Nouveau Champ',
                    type: 'checkbox',
                    key: 'field_' + Date.now(),
                    optionsStr: ''
                });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            }
        }
    }
</script>
@endsection
