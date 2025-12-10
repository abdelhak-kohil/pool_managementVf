<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 align-middle">
            <thead class="bg-gray-50/50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Membre</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Contact</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Abonnement</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Badge</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($members as $m)
                    <tr class="hover:bg-blue-50/30 transition-colors group">
                        
                        <!-- Member Info -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600 flex items-center justify-center font-bold text-sm shadow-sm ring-2 ring-white">
                                        {{ substr($m->first_name, 0, 1) }}{{ substr($m->last_name, 0, 1) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                                        {{ $m->first_name }} {{ $m->last_name }}
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono">#{{ $m->member_id }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Contact -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $m->email ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $m->phone_number ?? '-' }}</div>
                        </td>

                        <!-- Subscription -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $sub = $m->subscriptions->first();
                            @endphp
                            @if($sub && $sub->plan)
                                <div class="flex flex-col items-start gap-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                        {{ $sub->plan->plan_name }}
                                    </span>
                                    @if($sub->status === 'active')
                                        <span class="text-[10px] text-green-600 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Actif
                                        </span>
                                    @else
                                        <span class="text-[10px] text-gray-500">{{ ucfirst($sub->status) }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Aucun abonnement</span>
                            @endif
                        </td>

                        <!-- Badge -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($m->accessBadge && $m->accessBadge->badge_uid)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200 font-mono">
                                    <svg class="mr-1.5 h-3 w-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                    {{ $m->accessBadge->badge_uid }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400 opacity-50">—</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('members.info', $m->member_id) }}" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Détails">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                                
                                <a href="{{ route('members.edit', $m->member_id) }}" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Modifier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>

                                @if (Auth::check() && Auth::user()->role->role_name === 'admin')
                                    <form action="{{ route('members.destroy', $m->member_id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition delete-btn" title="Supprimer">
                                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-4xl mb-3">👥</span>
                                <p class="text-lg font-medium text-gray-900">Aucun membre trouvé</p>
                                <p class="text-sm">Commencez par ajouter un nouveau membre.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        {{ $members->links('pagination::tailwind') }}
    </div>
</div>
