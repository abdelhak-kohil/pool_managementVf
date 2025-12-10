@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">👋 Bonjour, {{ Auth::user()->first_name }}</h2>
            <p class="text-gray-500 text-sm">Panneau d'administration général.</p>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Total Membres</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalMembers }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Abonnements Actifs</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $activeSubscriptions }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Revenu Mensuel</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($monthlyRevenue, 2) }} €</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Revenu Total</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($totalRevenue, 2) }} €</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Members -->
        <a href="{{ route('members.index') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-blue-100 transition">
                <span class="text-2xl">👥</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Membres</h3>
            <p class="text-gray-500 text-sm mt-1">Gérer les inscriptions et profils.</p>
        </a>

        <!-- Finance -->
        <a href="{{ route('finance.dashboard') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-green-50 text-green-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-green-100 transition">
                <span class="text-2xl">💰</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Finance</h3>
            <p class="text-gray-500 text-sm mt-1">Paiements, abonnements et dépenses.</p>
        </a>

        <!-- Planning -->
        <a href="{{ route('schedule.index') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-purple-100 transition">
                <span class="text-2xl">📅</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Planning</h3>
            <p class="text-gray-500 text-sm mt-1">Gérer le planning hebdomadaire.</p>
        </a>

        <!-- Coaches Tracking -->
        <a href="{{ route('coaches.index') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-orange-100 transition">
                <span class="text-2xl">🏋️</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Suivi Coachs</h3>
            <p class="text-gray-500 text-sm mt-1">Performance et planning des coachs.</p>
        </a>

        <!-- Staff -->
        <a href="{{ route('staff.index') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-gray-50 text-gray-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-gray-100 transition">
                <span class="text-2xl">👔</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Staff</h3>
            <p class="text-gray-500 text-sm mt-1">Gérer les employés et accès.</p>
        </a>

        <!-- Settings -->
        <a href="{{ route('activities.index') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-red-50 text-red-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-red-100 transition">
                <span class="text-2xl">⚙️</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Activités & Paramètres</h3>
            <p class="text-gray-500 text-sm mt-1">Configurer les activités et tarifs.</p>
        </a>

        <!-- Roles & Permissions -->
        <a href="{{ route('roles.index') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-indigo-100 transition">
                <span class="text-2xl">🔐</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Rôles & Permissions</h3>
            <p class="text-gray-500 text-sm mt-1">Gérer les accès et droits.</p>
        </a>

        <!-- Maintenance -->
        <a href="{{ route('pool.dashboard') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-cyan-100 transition">
                <span class="text-2xl">🔧</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Maintenance</h3>
            <p class="text-gray-500 text-sm mt-1">Gestion des bassins et équipements.</p>
        </a>

        <!-- Backups (Admin Only) -->
        @if(Auth::user()->hasRole('admin'))
        <a href="{{ route('admin.backups.index') }}" class="bg-white p-6 rounded-xl shadow border border-gray-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-teal-50 text-teal-600 rounded-lg flex items-center justify-center mb-4 group-hover:bg-teal-100 transition">
                <span class="text-2xl">💾</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Sauvegardes</h3>
            <p class="text-gray-500 text-sm mt-1">Gestion des sauvegardes BDD.</p>
        </a>
        @endif
    </div>
</div>
@endsection
