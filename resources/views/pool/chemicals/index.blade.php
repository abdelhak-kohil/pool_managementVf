@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Produits Chimiques</h1>
            <p class="text-slate-500">Gestion des stocks et consommation</p>
        </div>
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
                                <th class="px-6 py-3 font-semibold">Actions</th>
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
                                        <form action="{{ route('pool.chemicals.update-stock', $chem) }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            <input type="number" name="quantity_added" placeholder="+ Qté" class="w-20 text-xs rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500" min="0" step="0.1">
                                            <button type="submit" class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition" title="Ajouter du stock">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </form>
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
</div>
@endsection
