@extends('layouts.app')
@section('title', 'Modifier le Badge')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h2 class="text-2xl font-semibold text-gray-800">✏️ Modifier le Badge d’Accès</h2>
  <a href="{{ route('badges.index') }}" class="text-gray-600 hover:text-gray-800">
    ← Retour
  </a>
</div>

<div class="bg-white rounded-xl shadow border border-gray-100 p-6">
  <form action="{{ route('badges.update', $badge->badge_id) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- Badge UID -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">UID du Badge <span class="text-red-500">*</span></label>
        <input type="text" name="badge_uid" value="{{ old('badge_uid', $badge->badge_uid) }}" required
               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
      </div>

      <!-- Assigned Member -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">Membre Assigné</label>
        <select name="member_id" 
                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
          <option value="">— Aucun —</option>
          @foreach($members as $member)
            <option value="{{ $member->member_id }}" 
                    {{ $badge->member_id == $member->member_id ? 'selected' : '' }}>
              {{ $member->first_name }} {{ $member->last_name }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Status -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">Statut <span class="text-red-500">*</span></label>
        <select name="status" required
                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
          @php
            $statuses = ['active' => 'Actif', 'inactive' => 'Inactif', 'lost' => 'Perdu', 'revoked' => 'Révoqué', 'blocked' => 'Bloqué'];
          @endphp
          @foreach($statuses as $key => $label)
            <option value="{{ $key }}" {{ $badge->status === $key ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Expiration Date -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">Date d’expiration</label>
        <input type="date" name="expires_at" 
               value="{{ old('expires_at', optional($badge->expires_at)->format('Y-m-d')) }}" 
               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
      </div>

      <!-- Issued At (read-only) -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">Délivré le</label>
        <input type="text" readonly value="{{ \Carbon\Carbon::parse($badge->issued_at)->format('d/m/Y H:i') }}" 
               class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-lg block p-2.5 cursor-not-allowed">
      </div>

      <!-- Badge ID (for admins only) -->
      @if (Auth::check() && Auth::user()->role->role_name === 'Admin')
      <div>
        <label class="block text-gray-700 font-medium mb-2">ID du Badge</label>
        <input type="text" readonly value="{{ $badge->badge_id }}" 
               class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-lg block p-2.5 cursor-not-allowed">
      </div>
      @endif
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end gap-4 pt-4 border-t border-gray-100">
      <a href="{{ route('badges.index') }}" 
         class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-200">
        Annuler
      </a>

      <button type="submit" 
              class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300">
        Mettre à jour
      </button>
    </div>
  </form>
</div>

<!-- SweetAlert Success Message -->
@if (session('success'))

<script>
Swal.fire({
  icon: 'success',
  title: 'Succès',
  text: '{{ session('success') }}',
  confirmButtonColor: '#2563eb'
});
</script>
@endif
@endsection
