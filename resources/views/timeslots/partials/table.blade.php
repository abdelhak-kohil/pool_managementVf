<div class="bg-white rounded-xl shadow overflow-x-auto border border-gray-100">
    <table class="min-w-full text-left text-gray-800">
      <thead class="bg-gray-50 border-b">
          <tr>
          <th class="py-3 px-4 font-medium">#</th>
          <th class="py-3 px-4 font-medium">Jour</th>
          <th class="py-3 px-4 font-medium">Créneau</th>
          <th class="py-3 px-4 font-medium">Activité</th>
          <th class="py-3 px-4 font-medium">Groupe</th>
          <th class="py-3 px-4 font-medium">Bloqué</th>
          <th class="py-3 px-4 font-medium text-right">Actions</th>
          </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
          @forelse ($slots as $slot)
          <tr class="hover:bg-blue-50 transition">
              <td class="py-3 px-4 text-gray-500">{{ $loop->iteration + ($slots->currentPage()-1)*$slots->perPage() }}</td>
              <td class="py-3 px-4 font-medium">{{ ucfirst($slot->weekday->day_name ?? $slot->weekday_id) }}</td>
              <td class="py-3 px-4 text-blue-600 font-medium">{{ substr($slot->start_time,0,5) }} → {{ substr($slot->end_time,0,5) }}</td>
              <td class="py-3 px-4">
                  @if($slot->activity)
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border border-gray-200 shadow-sm" 
                            style="background-color: {{ $slot->activity->color_code ?? '#e5e7eb' }}; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                          {{ $slot->activity->name }}
                      </span>
                  @else
                      <span class="text-gray-400">—</span>
                  @endif
              </td>
              <td class="py-3 px-4">{{ $slot->assigned_group ?? '—' }}</td>
              <td class="py-3 px-4">
                  @if($slot->is_blocked)
                      <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-medium">Oui</span>
                  @else
                      <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Non</span>
                  @endif
              </td>
              <td class="py-3 px-4 text-right">
                  <div class="flex justify-end gap-3">
                      <a href="{{ route('timeslots.edit', $slot->slot_id) }}" class="text-yellow-600 hover:text-yellow-800 font-medium">✏️ Modifier</a>
                      <form action="{{ route('timeslots.destroy', $slot->slot_id) }}" method="POST" class="delete-form inline-block">
                          @csrf @method('DELETE')
                          <button type="button" class="text-red-600 hover:text-red-800 font-medium delete-btn">🗑 Supprimer</button>
                      </form>
                  </div>
              </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center py-6 text-gray-500">Aucun créneau trouvé.</td></tr>
          @endforelse
      </tbody>
    </table>
    
    <div class="p-4 border-t border-gray-100">
      {{ $slots->links('pagination::tailwind') }}
    </div>
</div>
