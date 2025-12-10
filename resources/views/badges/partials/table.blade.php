<div class="bg-white rounded-xl shadow overflow-x-auto border border-gray-100">
  <table class="min-w-full text-left text-gray-800">
    <thead class="bg-gray-50 border-b">
      <tr>
        <th class="py-3 px-4 font-medium">#</th>
        <th class="py-3 px-4 font-medium">UID</th>
        <th class="py-3 px-4 font-medium">Membre</th>
        <th class="py-3 px-4 font-medium">Statut</th>
        <th class="py-3 px-4 font-medium">Délivré le</th>
        <th class="py-3 px-4 font-medium text-right">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse($badges as $badge)
        <tr class="hover:bg-blue-50 transition">
          <td class="py-3 px-4 text-gray-500">{{ $loop->iteration + ($badges->currentPage() - 1) * $badges->perPage() }}</td>
          <td class="py-3 px-4 font-mono text-sm text-blue-700">{{ $badge->badge_uid }}</td>
          <td class="py-3 px-4">{{ $badge->member?->first_name ?? 'Non assigné' }} {{ $badge->member?->last_name }}</td>
          <td class="py-3 px-4">
            @php
              $colors = [
                'active' => 'bg-green-100 text-green-800',
                'inactive' => 'bg-gray-200 text-gray-700',
                'lost' => 'bg-yellow-100 text-yellow-800',
                'revoked' => 'bg-red-100 text-red-700',
                'blocked' => 'bg-red-200 text-red-800',
              ];
              $class = $colors[$badge->status] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $class }}">{{ ucfirst($badge->status) }}</span>
          </td>
          <td class="py-3 px-4 text-sm">{{ \Carbon\Carbon::parse($badge->issued_at)->format('d/m/Y') }}</td>
          <td class="py-3 px-4 text-right">
            <div class="flex justify-end gap-3">
              <a href="{{ route('badges.edit', $badge->badge_id) }}" class="text-yellow-600 hover:text-yellow-800 font-medium">✏️ Modifier</a>
              @if (Auth::user()->role->role_name === 'Admin')
              <form action="{{ route('badges.destroy', $badge->badge_id) }}" method="POST" class="inline-block delete-form">
                @csrf @method('DELETE')
                <button type="button" class="text-red-600 hover:text-red-800 font-medium delete-btn">🗑 Supprimer</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center py-6 text-gray-500">Aucun badge trouvé.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="p-4 border-t border-gray-100">
    {{ $badges->links('pagination::tailwind') }}
  </div>
</div>
