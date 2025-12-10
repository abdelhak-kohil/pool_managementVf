@extends('layouts.app')
@section('title', 'Portail')

@section('content')
<div class="min-h-[80vh] flex flex-col items-center justify-center">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Bienvenue sur Pool Admin 🏊</h1>
        <p class="text-xl text-gray-600">Sélectionnez votre espace de travail</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl w-full px-4">
        
        <!-- RECEPTION -->
        @if(Auth::user()->hasRole(['admin', 'receptionniste']))
        <a href="{{ route('reception.index') }}" class="group relative bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1">
            <div class="absolute top-0 left-0 w-full h-2 bg-blue-500"></div>
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-blue-100 transition-colors">
                    <span class="text-4xl">👋</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3">Réception</h2>
                <p class="text-gray-500 leading-relaxed">
                    Gestion des entrées, check-in membres, vente de tickets.
                </p>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center group-hover:bg-blue-50 transition-colors">
                <span class="text-sm font-semibold text-blue-600">Accéder</span>
                <svg class="w-5 h-5 text-blue-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </div>
        </a>
        @endif

        <!-- ADMINISTRATION -->
        @if(Auth::user()->hasRole('admin'))
        <a href="{{ route('admin.dashboard') }}" class="group relative bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1">
            <div class="absolute top-0 left-0 w-full h-2 bg-purple-500"></div>
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-purple-100 transition-colors">
                    <span class="text-4xl">⚙️</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3">Administration</h2>
                <p class="text-gray-500 leading-relaxed">
                    Configuration, staff, planning, congés, rôles et logs.
                </p>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center group-hover:bg-purple-50 transition-colors">
                <span class="text-sm font-semibold text-purple-600">Accéder</span>
                <svg class="w-5 h-5 text-purple-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </div>
        </a>
        @endif

        <!-- FINANCE -->
        @if(Auth::user()->hasRole(['admin', 'financer']))
        <a href="{{ route('finance.dashboard') }}" class="group relative bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1">
            <div class="absolute top-0 left-0 w-full h-2 bg-green-500"></div>
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-green-100 transition-colors">
                    <span class="text-4xl">💰</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3">Finance</h2>
                <p class="text-gray-500 leading-relaxed">
                    Suivi des paiements, abonnements, plans tarifaires.
                </p>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center group-hover:bg-green-50 transition-colors">
                <span class="text-sm font-semibold text-green-600">Accéder</span>
                <svg class="w-5 h-5 text-green-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </div>
        </a>

        <a href="{{ route('expenses.index') }}" class="group relative bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1">
            <div class="absolute top-0 left-0 w-full h-2 bg-red-500"></div>
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-red-100 transition-colors">
                    <span class="text-4xl">💸</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3">Dépenses</h2>
                <p class="text-gray-500 leading-relaxed">
                    Gestion des charges, factures et sorties d'argent.
                </p>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center group-hover:bg-red-50 transition-colors">
                <span class="text-sm font-semibold text-red-600">Accéder</span>
                <svg class="w-5 h-5 text-red-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </div>
        </a>
        @endif

        <!-- SHOP -->
        @if(Auth::user()->hasRole(['admin', 'receptionniste']))
        <a href="{{ route('shop.index') }}" class="group relative bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1">
            <div class="absolute top-0 left-0 w-full h-2 bg-yellow-500"></div>
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-yellow-100 transition-colors">
                    <span class="text-4xl">🛒</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3">Boutique</h2>
                <p class="text-gray-500 leading-relaxed">
                    Vente de produits, gestion des stocks et encaissements.
                </p>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center group-hover:bg-yellow-50 transition-colors">
                <span class="text-sm font-semibold text-yellow-600">Accéder</span>
                <svg class="w-5 h-5 text-yellow-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </div>
        </a>
        @endif

        <!-- MAINTENANCE -->
        @if(Auth::user()->hasRole(['admin', 'maintenance_manager', 'pool_technician']))
        <a href="{{ route('pool.dashboard') }}" class="group relative bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1">
            <div class="absolute top-0 left-0 w-full h-2 bg-cyan-500"></div>
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-cyan-50 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-cyan-100 transition-colors">
                    <span class="text-4xl">🔧</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3">Maintenance</h2>
                <p class="text-gray-500 leading-relaxed">
                    Suivi des bassins, équipements, incidents et stocks.
                </p>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center group-hover:bg-cyan-50 transition-colors">
                <span class="text-sm font-semibold text-cyan-600">Accéder</span>
                <svg class="w-5 h-5 text-cyan-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </div>
        </a>
        @endif

    </div>
</div>
@endsection
