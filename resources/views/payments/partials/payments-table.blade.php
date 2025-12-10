<div class="bg-white rounded-xl shadow border border-gray-100 overflow-x-auto">
  <table class="min-w-full text-left text-gray-800">
    <thead class="bg-gray-50 border-b">
      <tr>
        <th class="py-3 px-4 font-medium text-gray-500 uppercase text-xs">#</th>
        <th class="py-3 px-4 font-medium text-gray-500 uppercase text-xs">Membre</th>
        <th class="py-3 px-4 font-medium text-gray-500 uppercase text-xs">Montant</th>
        <th class="py-3 px-4 font-medium text-gray-500 uppercase text-xs">Méthode</th>
        <th class="py-3 px-4 font-medium text-gray-500 uppercase text-xs">Date</th>
        <th class="py-3 px-4 font-medium text-gray-500 uppercase text-xs">Personnel</th>
        <th class="py-3 px-4 font-medium text-gray-500 uppercase text-xs">Notes</th>
        <th class="py-3 px-4 font-medium text-gray-500 uppercase text-xs text-right">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse ($payments as $p)
      <tr class="hover:bg-blue-50 transition">
        <td class="py-3 px-4 text-gray-500 font-mono text-sm">{{ $loop->iteration }}</td>
        <td class="py-3 px-4 font-medium">
          {{ $p->subscription->member->first_name }} {{ $p->subscription->member->last_name }}
        </td>
        <td class="py-3 px-4 text-green-600 font-bold">
          {{ number_format($p->amount, 2) }} DZD
        </td>
        <td class="py-3 px-4">
          @php
            $methodColors = [
              'cash' => 'bg-green-100 text-green-800',
              'card' => 'bg-blue-100 text-blue-800',
              'transfer' => 'bg-purple-100 text-purple-800',
            ];
            $methodLabels = [
              'cash' => 'Espèces',
              'card' => 'Carte',
              'transfer' => 'Virement',
            ];
          @endphp
          <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $methodColors[$p->payment_method] ?? 'bg-gray-100 text-gray-800' }}">
            {{ $methodLabels[$p->payment_method] ?? ucfirst($p->payment_method) }}
          </span>
        </td>
        <td class="py-3 px-4 text-gray-600 text-sm">
          {{ \Carbon\Carbon::parse($p->payment_date)->format('d/m/Y H:i') }}
        </td>
        <td class="py-3 px-4 text-gray-600 text-sm">
          {{ $p->staff->first_name ?? 'N/A' }}
        </td>
        <td class="py-3 px-4 text-gray-500 text-sm italic">
          {{ Str::limit($p->notes, 30) ?: '-' }}
        </td>
        <td class="py-3 px-4 text-right">
          <div class="flex justify-end gap-2">
            <a href="{{ route('payments.edit', $p->payment_id) }}" 
               class="text-yellow-600 hover:text-yellow-800 p-1 rounded-md hover:bg-yellow-50 transition" 
               title="Modifier">
              ✏️
            </a>
            <a href="{{ route('payments.receipt', $p->payment_id) }}" 
               class="text-blue-600 hover:text-blue-800 p-1 rounded-md hover:bg-blue-50 transition" 
               title="Télécharger Reçu" target="_blank">
              📄
            </a>
            @if (Auth::user()->role->role_name === 'admin')
            <form action="{{ route('payments.destroy', $p->payment_id) }}" method="POST" class="inline">
              @csrf @method('DELETE')
              <button type="submit" 
                      onclick="return confirm('Supprimer ce paiement ?')" 
                      class="text-red-600 hover:text-red-800 p-1 rounded-md hover:bg-red-50 transition"
                      title="Supprimer">
                🗑️
              </button>
            </form>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="8" class="text-center text-gray-500 py-8">
          Aucun paiement trouvé.
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-6">
  {{ $payments->links('pagination::tailwind') }}
</div>
