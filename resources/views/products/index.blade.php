@extends('layouts.app')
@section('title', 'Gestion des Produits')

@section('content')
<div x-data="productsTable()">
  <!-- ===== HEADER ===== -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">🛍️ Gestion des Produits</h2>
    <a href="{{ route('products.create') }}"
       class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition">
       ➕ Nouveau Produit
    </a>
  </div>

  <!-- ===== SEARCH BAR ===== -->
  <div class="relative mb-6">
    <input type="text" x-model="search" placeholder="Rechercher un produit..."
           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5 shadow-sm transition duration-200">
    <svg xmlns="http://www.w3.org/2000/svg"
         class="absolute left-3 top-3 text-gray-500 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
    </svg>
  </div>

  @if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
  @endif

  <!-- ===== TABLE ===== -->
  <div class="bg-white rounded-xl shadow overflow-x-auto border border-gray-100">
      <table class="min-w-full text-left text-gray-800">
        <thead class="bg-gray-50 border-b">
            <tr>
            <th class="py-3 px-4 font-medium">#</th>
            <th class="py-3 px-4 font-medium">Nom</th>
            <th class="py-3 px-4 font-medium">Catégorie</th>
            <th class="py-3 px-4 font-medium">Prix Achat</th>
            <th class="py-3 px-4 font-medium">Prix Vente</th>
            <th class="py-3 px-4 font-medium">Stock</th>
            <th class="py-3 px-4 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($products as $product)
            <tr class="hover:bg-blue-50 transition" x-show="matchesSearch('{{ strtolower($product->name) }}')">
                <td class="py-3 px-4 text-gray-500">{{ $loop->iteration }}</td>
                <td class="py-3 px-4 font-medium">{{ $product->name }}</td>
                <td class="py-3 px-4">
                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-medium">
                        {{ $product->category->name ?? '—' }}
                    </span>
                </td>
                <td class="py-3 px-4 text-gray-600">{{ number_format($product->purchase_price, 2) }} DZD</td>
                <td class="py-3 px-4 font-semibold text-blue-600">{{ number_format($product->price, 2) }} DZD</td>
                <td class="py-3 px-4">
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $product->stock_quantity <= $product->alert_threshold ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                        {{ $product->stock_quantity }}
                    </span>
                </td>
                <td class="py-3 px-4 text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('products.edit', $product->id) }}" class="text-yellow-600 hover:text-yellow-800 font-medium">✏️ Modifier</a>
                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="delete-form inline-block">
                            @csrf @method('DELETE')
                            <button type="button" class="text-red-600 hover:text-red-800 font-medium delete-btn">🗑 Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-6 text-gray-500">Aucun produit trouvé.</td></tr>
            @endforelse
        </tbody>
      </table>
      
      <div class="p-4 border-t border-gray-100">
        {{ $products->links('pagination::tailwind') }}
      </div>
  </div>
</div>

<script>
function productsTable() {
    return {
        search: '',
        matchesSearch(text) {
            return text.includes(this.search.toLowerCase());
        }
    }
}

// ===== SweetAlert Delete Confirmation =====
document.addEventListener('click', e => {
  if (e.target.classList.contains('delete-btn')) {
    e.preventDefault();
    const form = e.target.closest('form');
    Swal.fire({
      title: 'Supprimer ce produit ?',
      text: "Cette action est irréversible.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Oui, supprimer',
      cancelButtonText: 'Annuler',
      confirmButtonColor: '#d33'
    }).then(result => {
      if (result.isConfirmed) form.submit();
    });
  }
});
</script>
@endsection
