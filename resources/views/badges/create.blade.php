@extends('layouts.app')
@section('title', 'Créer un Badge')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h2 class="text-2xl font-semibold text-gray-800">➕ Nouveau Badge d’Accès</h2>
  <a href="{{ route('badges.index') }}" class="text-gray-600 hover:text-gray-800">
    ← Retour
  </a>
</div>

<div class="bg-white rounded-xl shadow border border-gray-100 p-6">
  <form action="{{ route('badges.store') }}" method="POST" class="space-y-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- UID -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">UID du Badge <span class="text-red-500">*</span></label>
        <input type="text" name="badge_uid" required placeholder="Ex: A1B2C3D4"
               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
      </div>

      <!-- Member -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">Membre Assigné</label>
        <select name="member_id" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
          <option value="">— Aucun —</option>
          @foreach($members as $member)
            <option value="{{ $member->member_id }}">{{ $member->first_name }} {{ $member->last_name }}</option>
          @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Seuls les membres sans badge sont listés.</p>
      </div>

      <!-- Status -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">Statut <span class="text-red-500">*</span></label>
        <select name="status" required class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
          <option value="active" selected>Actif</option>
          <option value="inactive">Inactif</option>
          <option value="lost">Perdu</option>
          <option value="revoked">Révoqué</option>
          <option value="blocked">Bloqué</option>
        </select>
      </div>

      <!-- Expires At -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">Date d’expiration</label>
        <input type="date" name="expires_at"
               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
      </div>
    </div>

    <div class="flex justify-end gap-4 pt-4 border-t border-gray-100">
      <a href="{{ route('badges.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-200">
        Annuler
      </a>
      <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300">
        Créer le badge
      </button>
    </div>
  </form>
</div>
@endsection
