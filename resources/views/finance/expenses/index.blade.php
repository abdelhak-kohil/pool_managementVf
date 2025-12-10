@extends('layouts.app')
@section('title', 'Gestion des Dépenses')

@section('content')
<div x-data="expensesManager()" class="space-y-6">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">💸 Gestion des Dépenses</h2>
            <p class="text-gray-500 text-sm">Suivez les dépenses et charges de l'établissement.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('finance.dashboard') }}" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                ⬅ Retour Dashboard
            </a>
            <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 shadow-sm">
                <span>➕</span> Nouvelle Dépense
            </button>
        </div>
    </div>

    <!-- Filters & Summary -->
    <div class="bg-white p-4 rounded-xl shadow border border-gray-100">
        <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-1/4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Titre ou référence..." class="w-full border-gray-300 rounded-lg text-sm">
            </div>
            <div class="w-full md:w-1/4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <select name="category" class="w-full border-gray-300 rounded-lg text-sm">
                    <option value="all">Toutes</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-1/4 flex gap-2">
                <div class="w-1/2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Du</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Au</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="w-full border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition text-sm">
                    Filtrer
                </button>
                <a href="{{ route('expenses.export.pdf', request()->all()) }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm flex items-center gap-1">
                    <span>📄</span> PDF
                </a>
            </div>
        </form>
    </div>

    <!-- Total Card -->
    <div class="bg-red-50 border border-red-100 p-4 rounded-xl flex items-center justify-between">
        <span class="text-red-700 font-medium">Total Dépenses (Sélection)</span>
        <span class="text-2xl font-bold text-red-700">{{ number_format($totalExpenses, 2, ',', ' ') }} DZD</span>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-gray-800 font-medium border-b border-gray-200">
                <tr>
                    <th class="p-4">Date</th>
                    <th class="p-4">Titre</th>
                    <th class="p-4">Catégorie</th>
                    <th class="p-4">Montant</th>
                    <th class="p-4">Paiement</th>
                    <th class="p-4">Ajouté par</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-4">{{ $expense->expense_date->format('d/m/Y') }}</td>
                    <td class="p-4">
                        <div class="font-medium text-gray-900">{{ $expense->title }}</div>
                        @if($expense->reference)
                            <div class="text-xs text-gray-400">Ref: {{ $expense->reference }}</div>
                        @endif
                    </td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            {{ $categories[$expense->category] ?? $expense->category }}
                        </span>
                    </td>
                    <td class="p-4 font-bold text-red-600">-{{ number_format($expense->amount, 2, ',', ' ') }}</td>
                    <td class="p-4">{{ ucfirst($expense->payment_method) }}</td>
                    <td class="p-4 text-xs">{{ $expense->creator->full_name ?? 'N/A' }}</td>
                    <td class="p-4 text-right flex justify-end gap-2">
                        <button @click="editExpense({{ $expense }})" class="text-blue-600 hover:bg-blue-50 p-1 rounded">✏️</button>
                        <form action="{{ route('expenses.destroy', $expense->expense_id) }}" method="POST" onsubmit="return confirm('Supprimer cette dépense ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:bg-red-50 p-1 rounded">🗑️</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-500">Aucune dépense trouvée.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $expenses->withQueryString()->links() }}
        </div>
    </div>

    <!-- Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 overflow-hidden" @click.away="closeModal()">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800" x-text="isEdit ? 'Modifier Dépense' : 'Nouvelle Dépense'"></h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            
            <form :action="isEdit ? `/finance/expenses/${form.id}` : '{{ route('expenses.store') }}'" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" :value="isEdit ? 'PUT' : 'POST'">
                
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                    <input type="text" name="title" x-model="form.title" required class="w-full border-gray-300 rounded-lg">
                </div>

                <!-- Amount & Date -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Montant (DZD) *</label>
                        <input type="number" step="0.01" name="amount" x-model="form.amount" required class="w-full border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                        <input type="date" name="expense_date" x-model="form.expense_date" required class="w-full border-gray-300 rounded-lg">
                    </div>
                </div>

                <!-- Category & Method -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie *</label>
                        <select name="category" x-model="form.category" required class="w-full border-gray-300 rounded-lg">
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Paiement *</label>
                        <select name="payment_method" x-model="form.payment_method" required class="w-full border-gray-300 rounded-lg">
                            <option value="cash">Espèces</option>
                            <option value="check">Chèque</option>
                            <option value="transfer">Virement</option>
                        </select>
                    </div>
                </div>

                <!-- Reference -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Référence (Facture N°)</label>
                    <input type="text" name="reference" x-model="form.reference" class="w-full border-gray-300 rounded-lg">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" x-model="form.description" rows="2" class="w-full border-gray-300 rounded-lg"></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Annuler</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function expensesManager() {
    return {
        showModal: false,
        isEdit: false,
        form: {
            id: null,
            title: '',
            amount: '',
            expense_date: new Date().toISOString().split('T')[0],
            category: 'other',
            payment_method: 'cash',
            reference: '',
            description: ''
        },

        openModal() {
            this.resetForm();
            this.isEdit = false;
            this.showModal = true;
        },

        editExpense(expense) {
            this.form = {
                id: expense.expense_id,
                title: expense.title,
                amount: expense.amount,
                expense_date: expense.expense_date.split('T')[0], // Handle ISO date if needed
                category: expense.category,
                payment_method: expense.payment_method,
                reference: expense.reference || '',
                description: expense.description || ''
            };
            this.isEdit = true;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
        },

        resetForm() {
            this.form = {
                id: null,
                title: '',
                amount: '',
                expense_date: new Date().toISOString().split('T')[0],
                category: 'other',
                payment_method: 'cash',
                reference: '',
                description: ''
            };
        }
    }
}
</script>
@endsection
