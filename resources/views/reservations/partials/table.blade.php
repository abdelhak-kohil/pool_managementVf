<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-gray-700">
            <thead class="bg-gray-50/50 uppercase tracking-wider text-xs font-semibold text-gray-500 border-b border-gray-100">
                <tr>
                    <th class="py-4 px-6">Date & Créneau</th>
                    <th class="py-4 px-6">Type</th>
                    <th class="py-4 px-6">Client / Groupe</th>
                    <th class="py-4 px-6">Statut</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reservations as $r)
                <tr class="hover:bg-blue-50/50 transition duration-150 ease-in-out group">
                    <!-- Date & Slot -->
                    <td class="py-4 px-6 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 capitalize">{{ $r->slot->weekday->day_name ?? 'Jour Inconnu' }}</span>
                            <span class="text-xs text-blue-600 font-mono bg-blue-50 px-2 py-0.5 rounded-md w-fit mt-1">
                                {{ substr($r->slot->start_time,0,5) }} - {{ substr($r->slot->end_time,0,5) }}
                            </span>
                        </div>
                    </td>

                    <!-- Type -->
                    <td class="py-4 px-6 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg {{ $r->reservation_type === 'member_private' ? 'bg-indigo-100 text-indigo-600' : 'bg-purple-100 text-purple-600' }}">
                                @if($r->reservation_type === 'member_private')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                @endif
                            </span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $r->reservation_type === 'member_private' ? 'Privée' : 'Partenaire' }}
                            </span>
                        </div>
                    </td>

                    <!-- Client -->
                    <td class="py-4 px-6 whitespace-nowrap">
                        @if($r->member)
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-sm ring-2 ring-white mr-3">
                                    {{ substr($r->member->first_name, 0, 1) }}{{ substr($r->member->last_name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $r->member->first_name }} {{ $r->member->last_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $r->member->phone_number ?? 'Non renseigné' }}</div>
                                </div>
                            </div>
                        @elseif($r->partnerGroup)
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold text-xs shadow-sm ring-2 ring-white mr-3">
                                    {{ substr($r->partnerGroup->name, 0, 2) }}
                                </div>
                                <div class="font-bold text-gray-900 text-sm">{{ $r->partnerGroup->name }}</div>
                            </div>
                        @else
                            <span class="text-gray-400 italic">—</span>
                        @endif
                    </td>

                    <!-- Status -->
                    <td class="py-4 px-6 whitespace-nowrap">
                        @php
                            $statusClasses = [
                                'confirmed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                            ];
                            $statusIcons = [
                                'confirmed' => '✅',
                                'cancelled' => '❌',
                                'pending' => '⏳',
                            ];
                            $classes = $statusClasses[$r->status] ?? 'bg-gray-100 text-gray-600 border-gray-200';
                            $icon = $statusIcons[$r->status] ?? '❓';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $classes }}">
                            <span class="mr-1.5">{{ $icon }}</span>
                            {{ ucfirst($r->status) }}
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="py-4 px-6 text-right whitespace-nowrap">
                        @if (Auth::user()->role->role_name === 'Admin' || Auth::user()->role->role_name === 'admin')
                        <form id="delete-form-{{ $r->reservation_id }}" action="{{ route('reservations.destroy', $r->reservation_id) }}" method="POST" class="inline-block">
                            @csrf @method('DELETE')
                            <button type="button" 
                                    onclick="confirmDelete('delete-form-{{ $r->reservation_id }}')"
                                    class="text-gray-400 hover:text-red-600 transition p-2 rounded-lg hover:bg-red-50 opacity-0 group-hover:opacity-100 focus:opacity-100" 
                                    title="Annuler la réservation">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                             <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                             </div>
                            <p class="text-lg font-medium text-gray-900">Aucune réservation trouvée</p>
                            <p class="text-sm">Commencez par en créer une nouvelle.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $reservations->links('pagination::tailwind') }}
    </div>
</div>
