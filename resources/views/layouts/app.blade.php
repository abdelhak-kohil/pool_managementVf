<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Admin Panel')</title>
  <script src="{{ asset('vendor/tailwindcss/tailwindcss.js') }}"></script>
<link href="{{ asset('vendor/fonts/inter.css') }}" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    #sidebar { transition: transform 0.3s ease-in-out, width 0.3s ease-in-out; }
    #overlay { transition: opacity 0.3s ease; }
    [x-cloak] { display: none !important; }
  </style>
  

</head>

<body class="bg-gray-50 text-gray-800 flex min-h-screen">

  <!-- ===== MOBILE OVERLAY ===== -->
  <div id="overlay" class="fixed inset-0 bg-black bg-opacity-30 hidden z-40"></div>

  <!-- ===== LICENSE EXPIRY BANNER ===== -->
  @php
      $daysRemaining = \App\Modules\Licensing\Facades\License::isValid() ? \App\Modules\Licensing\Facades\License::daysRemaining() : -1;
  @endphp
  
  @if($daysRemaining !== null && $daysRemaining >= 0 && $daysRemaining < 30)
  <div class="fixed top-0 left-0 right-0 bg-yellow-500 text-white text-center py-1 z-50 text-sm font-bold shadow-md">
      ⚠️ Votre licence expire dans {{ (int)$daysRemaining }} jours. <a href="{{ route('admin.license.index') }}" class="underline hover:text-yellow-100">Renouveler maintenant</a>.
  </div>
  @endif

  <!-- ===== SIDEBAR ===== -->
  @if (Auth::check())
  <aside id="sidebar"
    class="bg-white w-64 border-r border-gray-200 flex flex-col fixed h-full transform md:translate-x-0 -translate-x-full md:relative md:z-auto z-50 shadow-lg md:shadow-none">

    <!-- Logo -->
    <div class="p-4 border-b flex justify-between items-center">
      <h2 class="text-xl font-bold text-blue-600">🏊 Pool Admin</h2>
      <button id="closeSidebar" class="md:hidden text-gray-600 hover:text-red-500">✕</button>
    </div>

    @php
      $role = strtolower(Auth::user()->role->role_name ?? 'guest');
    @endphp

    <!-- ===== COLLAPSIBLE NAVIGATION ===== -->
    <nav x-data="{ 
           openPrincipal: {{ request()->routeIs('portal.index') || request()->routeIs('admin.dashboard') ? 'true' : 'false' }}, 
           openMembers: {{ request()->routeIs('members.*') || request()->routeIs('subscriptions.*') ? 'true' : 'false' }}, 
           openPlanning: {{ request()->routeIs('schedule.*') || request()->routeIs('timeslots.*') || request()->routeIs('activities.*') || request()->routeIs('reservations.*') ? 'true' : 'false' }}, 
           openFinance: {{ request()->routeIs('finance.dashboard') || request()->routeIs('payments.*') || request()->routeIs('plans.*') || request()->routeIs('activity-plan-prices.*') ? 'true' : 'false' }}, 
           openSales: {{ request()->routeIs('sales.dashboard') || request()->routeIs('shop.*') || request()->routeIs('products.*') || request()->routeIs('categories.*') ? 'true' : 'false' }},
           openHR: {{ request()->routeIs('staff.*') || request()->routeIs('coaches.*') || request()->routeIs('reception.*') ? 'true' : 'false' }}, 
           openMaintenance: {{ request()->routeIs('pool.*') ? 'true' : 'false' }},
           openAdmin: {{ request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('audit.*') || request()->routeIs('badges.*') || request()->routeIs('partner-groups.*') ? 'true' : 'false' }} 
         }" 
         class="flex-1 overflow-y-auto p-3 space-y-2" 
         x-cloak>

      <!-- === 1. Principal === -->
      <div class="space-y-1">
        <button @click="openPrincipal = !openPrincipal" 
                class="flex justify-between items-center w-full px-4 py-2 text-gray-600 font-semibold uppercase text-xs tracking-wide hover:text-blue-700">
          <span>🏠 Principal</span>
          <svg :class="openPrincipal ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div x-show="openPrincipal" x-transition class="ml-3 border-l border-gray-200 pl-3 space-y-1">
          <a href="{{ route('portal.index') }}"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('portal.index') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            ⬅️ Portail
          </a>
          <a href="{{ route('admin.dashboard') }}"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('admin.dashboard') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            📊 Tableau de bord
          </a>
        </div>
      </div>

      <!-- === 2. Gestion Membres === -->
      @if(\App\Modules\Licensing\Facades\License::hasModule('crm'))
      <div class="space-y-1">
        <button @click="openMembers = !openMembers" 
                class="flex justify-between items-center w-full px-4 py-2 text-gray-600 font-semibold uppercase text-xs tracking-wide hover:text-blue-700">
          <span>👥 Gestion Membres</span>
          <svg :class="openMembers ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div x-show="openMembers" x-transition class="ml-3 border-l border-gray-200 pl-3 space-y-1">
          <a href="{{ route('members.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('members.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            👥 Liste des membres
          </a>
          
          @if (in_array($role, ['admin', 'receptionniste']))
          <a href="{{ route('subscriptions.members') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('subscriptions.members') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🗓️ Abonnements Membres
          </a>
          <a href="{{ route('subscriptions.groups') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('subscriptions.groups') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🏢 Abonnements Groupes
          </a>
          @endif

          @if (in_array($role, ['receptionniste', 'admin']))
          <a href="{{ route('reception.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('reception.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🛎️ Réception
          </a>
          @endif

          @if(in_array($role, ['admin','receptionniste']))
          <a href="{{ route('reservations.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('reservations.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
             📅 Réservations
          </a>
          @endif




        </div>
        </div>
      </div>
      @endif

      <!-- === 3. Activités & Planning === -->
      <div class="space-y-1">
        <button @click="openPlanning = !openPlanning" 
                class="flex justify-between items-center w-full px-4 py-2 text-gray-600 font-semibold uppercase text-xs tracking-wide hover:text-blue-700">
          <span>📅 Activités & Planning</span>
          <svg :class="openPlanning ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div x-show="openPlanning" x-transition class="ml-3 border-l border-gray-200 pl-3 space-y-1">

          <a href="{{ route('schedule.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('schedule.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            📅 Planning Hebdomadaire
          </a>
          
          @if ($role === 'admin')
          <a href="{{ route('timeslots.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('timeslots.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
             ⏱ Créneaux
          </a>
          @endif

          <a href="{{ route('activities.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('activities.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🏊 Activités
          </a>


          

        </div>
      </div>

      <!-- === 4. Finance === -->
      @if (in_array($role, ['admin', 'financer']) && \App\Modules\Licensing\Facades\License::hasModule('finance'))
      <div class="space-y-1">
        <button @click="openFinance = !openFinance" 
                class="flex justify-between items-center w-full px-4 py-2 text-gray-600 font-semibold uppercase text-xs tracking-wide hover:text-blue-700">
          <span>💰 Finance</span>
          <svg :class="openFinance ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div x-show="openFinance" x-transition class="ml-3 border-l border-gray-200 pl-3 space-y-1">
          <a href="{{ route('finance.dashboard') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('finance.dashboard') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            📊 Dashboard Finance
          </a>

          <a href="{{ route('payments.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('payments.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            💳 Paiements
          </a>

          <a href="{{ route('plans.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('plans.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            📦 Plans d’abonnement
          </a>
          
          <a href="{{ route('activity-plan-prices.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('activity-plan-prices.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🏷️ Tarifications
          </a>


        </div>
      </div>

      <!-- === 5. Ventes === -->
      @if(\App\Modules\Licensing\Facades\License::hasModule('shop'))
      <div class="space-y-1">
        <button @click="openSales = !openSales" 
                class="flex justify-between items-center w-full px-4 py-2 text-gray-600 font-semibold uppercase text-xs tracking-wide hover:text-blue-700">
          <span>🛍️ Ventes</span>
          <svg :class="openSales ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div x-show="openSales" x-transition class="ml-3 border-l border-gray-200 pl-3 space-y-1">
          <a href="{{ route('sales.dashboard') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('sales.dashboard') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            📊 Dashboard Ventes
          </a>

          <a href="{{ route('shop.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('shop.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🛒 Boutique
          </a>
          
          <a href="{{ route('products.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('products.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🛍️ Produits
          </a>

          <a href="{{ route('categories.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('categories.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            📂 Catégories Produits
          </a>
        </div>
        </div>
      </div>
      @endif
      @endif

      <!-- === 5. RH & Staff === -->
      @if(\App\Modules\Licensing\Facades\License::hasModule('hr'))
      <div class="space-y-1">
        <button @click="openHR = !openHR" 
                class="flex justify-between items-center w-full px-4 py-2 text-gray-600 font-semibold uppercase text-xs tracking-wide hover:text-blue-700">
          <span>👔 RH & Staff</span>
          <svg :class="openHR ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div x-show="openHR" x-transition class="ml-3 border-l border-gray-200 pl-3 space-y-1">
          @if ($role === 'admin')


          <a href="{{ route('staff.hr.dashboard') }}"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('staff.hr.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
             ⏱️ Présence
          </a>

          <a href="{{ route('staff.planning.index') }}"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('staff.planning.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
             📅 Planning
          </a>

          <a href="{{ route('staff.leaves.index') }}"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('staff.leaves.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
             🏖️ Congés
          </a>

          <a href="{{ route('coaches.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('coaches.index') || request()->routeIs('coaches.create') || request()->routeIs('coaches.edit') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🧢 Coachs
          </a>



          @endif


        </div>
      </div>
      @endif

      <!-- === Maintenance === -->
      @if(\App\Modules\Licensing\Facades\License::hasModule('operations'))
      <div class="space-y-1">
        <button @click="openMaintenance = !openMaintenance" 
                class="flex justify-between items-center w-full px-4 py-2 text-gray-600 font-semibold uppercase text-xs tracking-wide hover:text-blue-700">
          <span>🔧 Maintenance</span>
          <svg :class="openMaintenance ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7" />
          </svg>
        </button>

        <div x-show="openMaintenance" x-transition class="ml-3 border-l border-gray-200 pl-3 space-y-1">
          <a href="{{ route('pool.dashboard') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('pool.dashboard') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            📊 Dashboard
          </a>
          <a href="{{ route('pool.facilities.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('pool.facilities.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🏊 Bassins
          </a>
          <a href="{{ route('pool.water-tests.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('pool.water-tests.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            💧 Qualité Eau
          </a>
          <a href="{{ route('pool.equipment.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('pool.equipment.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            ⚙️ Équipements
          </a>
          <a href="{{ route('pool.maintenance.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('pool.maintenance.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🛠️ Planning
          </a>
          <a href="{{ route('pool.chemicals.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('pool.chemicals.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🧪 Chimie
          </a>
          <a href="{{ route('pool.incidents.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('pool.incidents.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🚨 Incidents
          </a>
          <a href="{{ route('pool.tasks.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('pool.tasks.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            ✅ Check-lists
          </a>
        </div>
        </div>
      </div>
      @endif

      <!-- === 6. Administration === -->
      @if ($role === 'admin')
      <div class="space-y-1">
        <button @click="openAdmin = !openAdmin" 
                class="flex justify-between items-center w-full px-4 py-2 text-gray-600 font-semibold uppercase text-xs tracking-wide hover:text-blue-700">
          <span>⚙️ Administration</span>
          <svg :class="openAdmin ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transform transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div x-show="openAdmin" x-transition class="ml-3 border-l border-gray-200 pl-3 space-y-1">
          <a href="{{ route('staff.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('staff.index') || request()->routeIs('staff.create') || request()->routeIs('staff.edit') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            👨‍💼 Personnel
          </a>
          <a href="{{ route('roles.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('roles.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🧩 Rôles
          </a>
          <a href="{{ route('permissions.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('permissions.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🔐 Permissions
          </a>
          <a href="{{ route('audit.dashboard') }}"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('audit.dashboard') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            📜 Logs d’Audit
          </a>
          <a href="{{ route('badges.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('badges.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🎫 Badges d’accès
          </a>
          <a href="{{ route('partner-groups.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('partner-groups.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🏫 Groupes partenaires
          </a>
          
          @if(\App\Modules\Licensing\Facades\License::hasModule('backups'))
          <a href="{{ route('admin.backups.index') }}"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('admin.backups.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            💾 Sauvegardes
          </a>
          @endif
          <a href="{{ route('admin.license.index') }}" 
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
             {{ request()->routeIs('admin.license.*') ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-blue-50 hover:text-blue-700 text-gray-700' }}">
            🔑 Licence
          </a>
        </div>
      </div>
      @endif

    </nav>

    <!-- Footer -->
    <div class="p-4 border-t bg-gray-50">
      <div class="text-sm text-gray-600">
        <span class="font-semibold text-gray-800">{{ Auth::user()->username ?? 'Utilisateur' }}</span>
        <div class="text-xs text-gray-500">{{ ucfirst($role) }}</div>
      </div>
      <a href="{{ route('logout') }}" class="mt-3 inline-block text-red-600 hover:text-red-700 font-medium">
        🚪 Déconnexion
      </a>
    </div>
  </aside>
  @endif

  <!-- ===== MAIN CONTENT ===== -->
  <div style="margin-left: 0px;" class="flex-1 md:ml-64 flex flex-col min-h-screen transition-all duration-300">
    <header class="bg-white shadow-sm flex items-center justify-between px-6 py-3 sticky top-0 z-30">
      <div class="flex items-center gap-3">
        <button id="toggleSidebar" class="md:hidden text-gray-600 hover:text-blue-600 text-2xl">☰</button>
        <h1 class="text-xl font-semibold">@yield('title', 'Dashboard')</h1>
      </div>

      <!-- User Menu -->
      <div class="relative">
        <button id="userMenuBtn" class="flex items-center gap-2 text-gray-700 hover:text-blue-600">
          <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">
            {{ substr(Auth::user()->first_name ?? 'U', 0, 1) }}
          </div>
          <span class="hidden sm:block">{{ Auth::user()->first_name ?? 'User' }}</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div id="userMenu" class="hidden absolute right-0 mt-2 bg-white shadow-lg rounded-lg border border-gray-100 w-48">
          <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-gray-700 hover:bg-blue-50">Dashboard</a>
          <a href="{{ route('logout') }}" class="block px-4 py-2 text-red-600 hover:bg-red-50">Déconnexion</a>
        </div>
      </div>
    </header>

    <main class="flex-1 p-6 bg-gray-50">@yield('content')</main>

    <footer class="bg-white border-t text-center text-gray-500 text-sm py-3 mt-auto">
      © {{ date('Y') }} <span class="text-blue-600 font-semibold">Pool Management System</span>. Tous droits réservés.
    </footer>
  </div>

  <!-- Scripts -->
  <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
  <script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const toggleSidebar = document.getElementById('toggleSidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');

    toggleSidebar?.addEventListener('click', () => {
      sidebar.classList.remove('-translate-x-full');
      overlay.classList.remove('hidden');
    });

    closeSidebar?.addEventListener('click', closeMobileSidebar);
    overlay?.addEventListener('click', closeMobileSidebar);

    function closeMobileSidebar() {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
    }

    userMenuBtn?.addEventListener('click', () => userMenu.classList.toggle('hidden'));
    document.addEventListener('click', e => {
      if (!userMenu.contains(e.target) && !userMenuBtn.contains(e.target)) userMenu.classList.add('hidden');
    });
  </script>

  <!-- ✅ Global SweetAlert Handler -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      @if(session('success'))
        Swal.fire({
          icon: 'success',
          title: 'Succès 🎉',
          html: '<b>{{ session('success') }}</b>',
          timer: 2500,
          showConfirmButton: false,
          position: 'top-end',
          toast: true,
          background: '#ecfdf5',
          color: '#065f46',
          iconColor: '#16a34a',
        });
      @endif

      @if(session('error'))
        Swal.fire({
          icon: 'error',
          title: 'Erreur ⚠️',
          html: '<b>{{ session('error') }}</b>',
          timer: 3500,
          showConfirmButton: false,
          position: 'top-end',
          toast: true,
          background: '#fef2f2',
          color: '#991b1b',
          iconColor: '#dc2626',
        });
      @endif

      @if(session('warning'))
        Swal.fire({
          icon: 'warning',
          title: 'Attention ⚠️',
          html: '<b>{{ session('warning') }}</b>',
          timer: 3000,
          showConfirmButton: false,
          position: 'top-end',
          toast: true,
          background: '#fff7ed',
          color: '#92400e',
          iconColor: '#f59e0b',
        });
      @endif

      @if(session('info'))
        Swal.fire({
          icon: 'info',
          title: 'Information ℹ️',
          html: '<b>{{ session('info') }}</b>',
          timer: 2500,
          showConfirmButton: false,
          position: 'top-end',
          toast: true,
          background: '#eff6ff',
          color: '#1e3a8a',
          iconColor: '#2563eb',
        });
      @endif
    });
  </script>

  @stack('scripts')
</body>
</html>
