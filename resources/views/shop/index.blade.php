@extends('layouts.app')
@section('title', 'Boutique - Point de Vente')

@section('content')
<div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-120px)]" x-data="shop()">
    
    <!-- LEFT: Products Grid -->
    <div class="lg:w-2/3 flex flex-col">
        <!-- Search & Filter -->
        <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-4 flex gap-4 items-center">
            <div class="relative flex-1">
                <input type="text" x-model="search" placeholder="Rechercher un produit..."
                       class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 text-gray-500 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
                </svg>
            </div>
            <select x-model="categoryFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                <option value="">Toutes les catégories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            @php
                $role = Auth::user()->role;
            @endphp
            @if (in_array($role, ['admin', 'financer']))
            <a href="{{ route('sales.dashboard') }}" class="bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 whitespace-nowrap">
                <span>📊 Dashboard</span>
            </a>
            @endif
        </div>

        <!-- Scrollable Grid -->
        <div class="flex-1 overflow-y-auto pr-2">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($products as $product)
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-4 cursor-pointer hover:shadow-md transition flex flex-col h-full group"
                         x-show="filterProduct('{{ strtolower($product->name) }}', '{{ $product->category_id }}')"
                         @click="addToCart({{ $product }})">
                        
                        <div class="h-40 bg-gray-100 rounded-lg mb-3 overflow-hidden relative">
                            @if($product->images->count() > 0)
                                <div class="carousel w-full h-full relative" x-data="{ activeSlide: 0 }">
                                    @foreach($product->images as $index => $image)
                                        <img src="{{ asset('storage/' . $image->image_path) }}" 
                                             class="absolute top-0 left-0 w-full h-full object-cover transition-opacity duration-300"
                                             x-show="activeSlide === {{ $index }}"
                                             alt="{{ $product->name }}">
                                    @endforeach
                                    @if($product->images->count() > 1)
                                        <div class="absolute bottom-2 left-0 right-0 flex justify-center gap-1 z-10">
                                            @foreach($product->images as $index => $image)
                                                <div class="w-1.5 h-1.5 rounded-full bg-white transition-opacity duration-300" 
                                                     :class="{ 'opacity-100': activeSlide === {{ $index }}, 'opacity-50': activeSlide !== {{ $index }} }"></div>
                                            @endforeach
                                        </div>
                                        <!-- Auto-slide script could be added here, but simple hover/click might be better for POS -->
                                        <div class="absolute inset-0 flex items-center justify-between px-1 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                            <button @click.stop="activeSlide = (activeSlide === 0) ? {{ $product->images->count() - 1 }} : activeSlide - 1" class="bg-black/30 text-white rounded-full p-1 hover:bg-black/50">‹</button>
                                            <button @click.stop="activeSlide = (activeSlide === {{ $product->images->count() - 1 }}) ? 0 : activeSlide + 1" class="bg-black/30 text-white rounded-full p-1 hover:bg-black/50">›</button>
                                        </div>
                                    @endif
                                </div>
                            @elseif($product->image_path)
                                <img src="{{ asset('storage/' . $product->image_path) }}" class="object-cover h-full w-full">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 text-4xl">📦</div>
                            @endif
                            
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition flex items-center justify-center pointer-events-none">
                                <span class="bg-white text-gray-900 px-3 py-1 rounded-lg font-bold opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition shadow-sm text-sm">
                                    Ajouter
                                </span>
                            </div>
                        </div>
                        
                        <h3 class="font-semibold text-gray-800 text-sm mb-1 line-clamp-2">{{ $product->name }}</h3>
                        <div class="mt-auto flex justify-between items-center">
                            <span class="text-blue-600 font-bold">{{ number_format($product->price, 2) }} DA</span>
                            <span class="text-xs px-2 py-1 rounded-full {{ $product->stock_quantity <= $product->alert_threshold ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                Stock: {{ $product->stock_quantity }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="lg:w-1/3 bg-white rounded-xl shadow border border-gray-100 flex flex-col h-full">
        <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                🛒 Panier
                <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full" x-text="cartItemCount"></span>
            </h3>
        </div>

        <!-- Member Search -->
        <div class="p-4 border-b border-gray-100 relative">
            <label class="block text-xs font-medium text-gray-700 mb-1">Client (Membre)</label>
            <div class="relative">
                <!-- Input with Spinner -->
                <div class="relative">
                    <input type="text" x-model="query" @input.debounce.300ms="fetchMembers()" 
                           placeholder="Rechercher un membre (nom, email, tél)..." 
                           class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg pl-3 pr-10 py-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                           :class="{ 'border-green-500 focus:border-green-500 focus:ring-green-500': selectedMember }">
                    
                    <!-- Loading Spinner -->
                    <div x-show="searching" class="absolute right-3 top-2.5">
                        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Dropdown Results -->
                <div x-show="showResults && results.length > 0" @click.away="showResults = false" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="absolute z-50 w-full bg-white shadow-xl rounded-xl mt-2 border border-blue-100 max-h-60 overflow-y-auto">
                    <template x-for="member in results" :key="member.id">
                        <div @click="selectMember(member)" class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 flex items-center gap-3 transition-colors group">
                            <!-- Avatar / Initials -->
                            <div class="w-10 h-10 rounded-full flex-shrink-0 overflow-hidden border border-gray-100 bg-gray-100 flex items-center justify-center">
                                <template x-if="member.photo_path">
                                    <img :src="'/storage/' + member.photo_path" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!member.photo_path">
                                    <span class="font-bold text-gray-400 group-hover:text-blue-600 text-xs" x-text="member.first_name.charAt(0) + member.last_name.charAt(0)"></span>
                                </template>
                            </div>

                            <!-- Info -->
                            <div>
                                <div class="font-bold text-gray-800 text-sm group-hover:text-blue-700" x-text="member.first_name + ' ' + member.last_name"></div>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span x-text="member.email"></span>
                                    <template x-if="member.phone_number">
                                        <span class="flex items-center gap-0.5">
                                            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                            <span x-text="member.phone_number"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <div x-show="selectedMember" class="mt-2 text-sm text-green-600 flex items-center gap-1 bg-green-50 p-2 rounded-lg border border-green-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span x-text="selectedMember ? (selectedMember.first_name + ' ' + selectedMember.last_name) : ''" class="font-medium"></span>
                <button @click="selectedMember = null; query = ''" class="text-red-500 hover:text-red-700 ml-auto text-xs font-bold">✕</button>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <template x-if="cart.length === 0">
                <div class="text-center text-gray-400 py-10 flex flex-col items-center">
                    <span class="text-4xl mb-2">🛒</span>
                    <p>Le panier est vide</p>
                    <p class="text-sm">Cliquez sur un produit pour l'ajouter</p>
                </div>
            </template>
            
            <template x-for="(item, index) in cart" :key="item.id">
                <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-800 text-sm" x-text="item.name"></h4>
                        <div class="text-xs text-gray-500">
                            <span x-text="formatPrice(item.price)"></span> x <span x-text="item.quantity"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center border border-gray-300 rounded-lg bg-white shadow-sm">
                            <button @click="updateQuantity(index, -1)" class="px-2 py-1 text-gray-600 hover:bg-gray-100 rounded-l-lg font-bold">-</button>
                            <span class="px-2 text-sm font-medium w-6 text-center" x-text="item.quantity"></span>
                            <button @click="updateQuantity(index, 1)" class="px-2 py-1 text-gray-600 hover:bg-gray-100 rounded-r-lg font-bold">+</button>
                        </div>
                        <div class="font-bold text-blue-600 text-sm w-16 text-right" x-text="formatPrice(item.price * item.quantity)"></div>
                        <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Checkout Section -->
        <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-600 font-medium">Total</span>
                <span class="text-2xl font-bold text-blue-600" x-text="formatPrice(cartTotal)"></span>
            </div>

            <div class="space-y-3">
                <!-- Payment Method -->
                <div class="grid grid-cols-3 gap-2">
                    <button @click="paymentMethod = 'cash'" 
                            :class="paymentMethod === 'cash' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                            class="border rounded-lg py-2 text-sm font-medium transition flex flex-col items-center justify-center">
                        <span class="text-lg">💵</span>
                        Espèces
                    </button>
                    <button @click="paymentMethod = 'card'" 
                            :class="paymentMethod === 'card' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                            class="border rounded-lg py-2 text-sm font-medium transition flex flex-col items-center justify-center">
                        <span class="text-lg">💳</span>
                        Carte
                    </button>
                    <button @click="paymentMethod = 'other'" 
                            :class="paymentMethod === 'other' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                            class="border rounded-lg py-2 text-sm font-medium transition flex flex-col items-center justify-center">
                        <span class="text-lg">🔄</span>
                        Autre
                    </button>
                </div>

                <button @click="checkout()" 
                        :disabled="cart.length === 0 || loading"
                        class="w-full bg-green-600 text-white font-bold py-3.5 rounded-lg shadow-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center gap-2 transform active:scale-95">
                    <span x-show="!loading" class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Valider la vente
                    </span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Traitement...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function shop() {
    return {
        search: '',
        categoryFilter: '',
        cart: [],
        paymentMethod: 'cash',
        
        // Member Search
        query: '',
        results: [],
        showResults: false,
        selectedMember: null,

        loading: false,

        init() {
            // Optional: Load cart from localStorage
        },

        filterProduct(name, categoryId) {
            const matchesSearch = name.includes(this.search.toLowerCase());
            const matchesCategory = this.categoryFilter === '' || categoryId == this.categoryFilter;
            return matchesSearch && matchesCategory;
        },

        addToCart(product) {
            const existingItem = this.cart.find(item => item.id === product.id);
            if (existingItem) {
                if (existingItem.quantity < product.stock_quantity) {
                    existingItem.quantity++;
                } else {
                    Swal.fire('Stock insuffisant', 'Vous avez atteint la limite du stock disponible.', 'warning');
                }
            } else {
                if (product.stock_quantity > 0) {
                    this.cart.push({
                        id: product.id,
                        name: product.name,
                        price: parseFloat(product.price),
                        quantity: 1,
                        maxStock: product.stock_quantity
                    });
                } else {
                    Swal.fire('Rupture de stock', 'Ce produit n\'est plus disponible.', 'error');
                }
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        updateQuantity(index, change) {
            const item = this.cart[index];
            const newQuantity = item.quantity + change;
            
            if (newQuantity > 0 && newQuantity <= item.maxStock) {
                item.quantity = newQuantity;
            } else if (newQuantity > item.maxStock) {
                Swal.fire('Stock insuffisant', 'Stock maximum atteint.', 'warning');
            } else if (newQuantity <= 0) {
                this.removeFromCart(index);
            }
        },

        get cartTotal() {
            return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        },

        get cartItemCount() {
            return this.cart.reduce((total, item) => total + item.quantity, 0);
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-DZ', { style: 'currency', currency: 'DZD' }).format(price);
        },

        searching: false,

        // Member Search Logic
        fetchMembers() {
            if (this.query.length < 2) {
                this.results = [];
                this.showResults = false;
                return;
            }
            this.searching = true;
            fetch(`{{ route('shop.members.search') }}?q=${this.query}`)
                .then(res => res.json())
                .then(data => {
                    this.results = data;
                    this.showResults = true;
                    this.searching = false;
                })
                .catch(() => {
                    this.searching = false;
                });
        },

        selectMember(member) {
            this.selectedMember = member;
            this.query = ''; 
            this.showResults = false;
        },

        checkout() {
            if (this.cart.length === 0) return;

            this.loading = true;

            fetch('{{ route("shop.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    cart: this.cart,
                    payment_method: this.paymentMethod,
                    member_id: this.selectedMember ? this.selectedMember.member_id : null
                })
            })
            .then(response => response.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    Swal.fire({
                        title: 'Succès!',
                        text: 'Vente enregistrée avec succès.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                    this.cart = [];
                } else {
                    Swal.fire('Erreur', data.message || 'Une erreur est survenue.', 'error');
                }
            })
            .catch(error => {
                this.loading = false;
                console.error('Error:', error);
                Swal.fire('Erreur', 'Une erreur réseau est survenue.', 'error');
            });
        }
    }
}
</script>
@endsection
