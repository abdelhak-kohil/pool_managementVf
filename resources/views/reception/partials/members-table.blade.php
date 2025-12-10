<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-gray-700">
      <thead class="bg-gray-50/50 uppercase tracking-wider text-xs font-semibold text-gray-500 border-b border-gray-100">
        <tr>
          <th class="py-4 px-6 w-16">ID</th>
          <th class="py-4 px-6">Membre</th>
          <th class="py-4 px-6">Contact</th>
          <th class="py-4 px-6">Badge</th>
          <th class="py-4 px-6">Statut Badge</th>
          <th class="py-4 px-6 text-right">Actions</th>
        </tr>
      </thead>
      <tbody id="membersTableBody" class="divide-y divide-gray-50">
        @forelse($members as $member)
          <tr class="hover:bg-blue-50/50 transition duration-150 ease-in-out group">
            <td class="py-4 px-6 text-gray-400 font-mono text-xs">#{{ $member->member_id }}</td>
            <td class="py-4 px-6">
              <div class="flex items-center">
                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-600 font-bold text-xs ring-2 ring-white shadow-sm mr-3">
                    {{ substr($member->first_name, 0, 1) }}{{ substr($member->last_name, 0, 1) }}
                </div>
                <div>
                  <div class="font-bold text-gray-900">{{ $member->first_name }} {{ $member->last_name }}</div>
                  <div class="text-xs text-gray-500">{{ $member->email ?? 'Sans email' }}</div>
                </div>
              </div>
            </td>
            <td class="py-4 px-6 text-sm text-gray-600">
              @if($member->phone_number)
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                {{ $member->phone_number }}
              </div>
              @else
              <span class="text-gray-400 italic">Non renseigné</span>
              @endif
            </td>
            <td class="py-4 px-6">
                 @if($member->accessbadge && $member->accessbadge->badge_uid)
                 <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded border border-gray-200">
                    {{ $member->accessbadge->badge_uid }}
                 </span>
                 @else
                 <span class="text-xs text-gray-400">—</span>
                 @endif
            </td>
            <td class="py-4 px-6">
              @php
                $badgeStatus = $member->accessbadge->status ?? 'inactive';
                $statusConfig = [
                  'active' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => '✅'],
                  'inactive' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => '💤'],
                  'lost' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => '⚠️'],
                  'blocked' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => '🚫'],
                ];
                $conf = $statusConfig[$badgeStatus] ?? $statusConfig['inactive'];
              @endphp
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $conf['bg'] }} {{ $conf['text'] }} border {{ str_replace('bg-', 'border-', $conf['bg']) }} border-opacity-50">
                <span class="mr-1.5">{{ $conf['icon'] }}</span> {{ ucfirst($badgeStatus) }}
              </span>
            </td>
            <td class="py-4 px-6 text-right">
                <div class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                    @php
                        $subscriptionsData = $member->subscriptions->map(function ($s) {
                            $colors = [
                                'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'paused' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'expired' => 'bg-gray-100 text-gray-600 border-gray-200',
                                'cancelled' => 'bg-red-100 text-red-800 border-red-200',
                            ];
                            $statusClass = $colors[$s->status] ?? 'bg-gray-100 text-gray-600 border-gray-200';
                            return [
                                'plan' => $s->plan->plan_name ?? '-',
                                'start' => optional($s->start_date)->format('d/m/Y'),
                                'end' => optional($s->end_date)->format('d/m/Y'),
                                'status' => ucfirst($s->status ?? 'unknown'),
                                'statusClass' => $statusClass,
                                'visits' => $s->visits_per_week ?? '-',
                                'days' => $s->weekdays && $s->weekdays->count() ? $s->weekdays->pluck('day_name')->join(', ') : 'Tous'
                            ];
                        });
                    @endphp
                  
                  <button 
                    @click='openMemberModal({
                        id: "{{ $member->member_id }}",
                        name: "{{ $member->first_name }} {{ $member->last_name }}",
                        initials: "{{ substr($member->first_name, 0, 1) }}{{ substr($member->last_name, 0, 1) }}",
                        phone: "{{ $member->phone_number ?? '-' }}",
                        email: "{{ $member->email ?? '-' }}",
                        badge: "{{ $member->accessbadge->badge_uid ?? 'N/A' }}",
                        badge_status: "{{ ucfirst($badgeStatus) }}",
                        audit: {
                            created_at: "{{ optional($member->created_at)->format('d/m/Y H:i') ?? '-' }}",
                            updated_at: "{{ optional($member->updated_at)->format('d/m/Y H:i') ?? '-' }}",
                            created_by: "{{ $member->createdBy->first_name ?? 'Système' }}",
                            updated_by: "{{ $member->updatedBy->first_name ?? 'Système' }}"
                        },
                        subscriptions: @json($subscriptionsData)
                    })'
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Détails">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                  </button>

                  <button 
                    @click="checkIn('{{ $member->member_id }}')" 
                    class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Check-in">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  </button>
                </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center py-12">
                <div class="flex flex-col items-center justify-center text-gray-400">
                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <p class="text-lg font-medium">Aucun membre trouvé</p>
                    <p class="text-sm">Essayez une autre recherche.</p>
                </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="bg-white px-4 py-3 border-t border-gray-100">
    {{ $members->links('pagination::tailwind') }}
  </div>
</div>

<!-- === Improved Member Modal === -->
<div 
  x-cloak
  x-show="showMemberModal" 
  class="fixed inset-0 z-50 overflow-y-auto" 
  aria-labelledby="modal-title" 
  role="dialog" 
  aria-modal="true"
>
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    
    <!-- Backdrop -->
    <div 
        x-show="showMemberModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900 bg-opacity-65 transition-opacity backdrop-blur-sm" 
        @click="closeMemberModal"
    ></div>

    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
    
    <!-- Panel -->
    <div 
         x-show="showMemberModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full"
    >
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-5 flex justify-between items-start">
            <div class="flex items-center text-white">
                <div class="h-14 w-14 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl font-bold border border-white/30 mr-4">
                    <span x-text="selectedMember.initials"></span>
                </div>
                <div>
                    <h3 class="text-2xl font-bold tracking-tight" x-text="selectedMember.name"></h3>
                    <p class="text-blue-100 text-sm flex items-center gap-2">
                        <span>🆔 #<span x-text="selectedMember.id"></span></span>
                        <span>•</span>
                        <span x-text="selectedMember.email"></span>
                    </p>
                </div>
            </div>
            <button class="text-white/60 hover:text-white transition" @click="closeMemberModal">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white border-b border-gray-100 flex px-6">
            <button @click="activeTab='details'" :class="activeTab==='details' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-4 text-sm font-medium border-b-2 transition-colors">Détails</button>
            <button @click="activeTab='subscriptions'" :class="activeTab==='subscriptions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-4 text-sm font-medium border-b-2 transition-colors">Abonnements</button>
            <button @click="activeTab='history'; fetchMemberLogs(selectedMember.id)" :class="activeTab==='history' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-4 text-sm font-medium border-b-2 transition-colors">Historique</button>
             @if (Auth::user()->role->role_name === 'admin')
            <button @click="activeTab='audit'" :class="activeTab==='audit' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-3 px-4 text-sm font-medium border-b-2 transition-colors">Audit</button>
            @endif
        </div>

        <!-- Content Area -->
        <div class="px-6 py-6 h-[400px] overflow-y-auto">
            
            <!-- Tab: Details -->
            <div x-show="activeTab === 'details'" class="space-y-4">
                 <div class="grid grid-cols-2 gap-4">
                     <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                         <p class="text-xs uppercase font-bold text-gray-400 mb-1">Téléphone</p>
                         <p class="font-medium text-gray-900" x-text="selectedMember.phone"></p>
                     </div>
                     <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                         <p class="text-xs uppercase font-bold text-gray-400 mb-1">Badge</p>
                         <p class="font-mono text-gray-900" x-text="selectedMember.badge"></p>
                     </div>
                 </div>
                 <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex items-center justify-between">
                     <div>
                         <p class="text-xs uppercase font-bold text-gray-400 mb-1">Statut Badge</p>
                         <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="selectedMember.badge_status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                          x-text="selectedMember.badge_status"></span>
                     </div>
                     <div class="text-2xl" x-text="selectedMember.badge_status === 'Active' ? '✅' : '🚫'"></div>
                 </div>
            </div>

            <!-- Tab: Subscriptions -->
            <div x-show="activeTab === 'subscriptions'" class="space-y-3">
                 <template x-if="!selectedMember.subscriptions || selectedMember.subscriptions.length === 0">
                    <div class="text-center py-10 text-gray-400">
                        <p>Aucun abonnement trouvé.</p>
                    </div>
                </template>
                <template x-for="sub in selectedMember.subscriptions" :key="sub.start">
                    <div class="border rounded-xl p-4 hover:shadow-sm transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                             <h4 class="font-bold text-gray-900" x-text="sub.plan"></h4>
                             <span class="text-xs px-2 py-1 rounded-full border" :class="sub.statusClass" x-text="sub.status"></span>
                        </div>
                        <div class="text-sm text-gray-600 grid grid-cols-2 gap-2">
                            <div>📅 <span x-text="sub.start"></span> ➝ <span x-text="sub.end"></span></div>
                            <div>🎯 <span x-text="sub.visits"></span> visites/semaine</div>
                            <div class="col-span-2">📆 Jours: <span x-text="sub.days"></span></div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Tab: History -->
            <div x-show="activeTab === 'history'">
                <div class="overflow-hidden border rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raison</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template x-for="log in memberLogs" :key="log.id">
                                <tr class="text-sm text-gray-700">
                                    <td class="px-4 py-2 whitespace-nowrap" x-text="new Date(log.access_time).toLocaleString()"></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="log.access_decision === 'granted' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                              x-text="log.access_decision === 'granted' ? 'Autorisé' : 'Refusé'"></span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-500" x-text="log.denial_reason || '-'"></td>
                                </tr>
                            </template>
                            <tr x-show="!memberLogs.length">
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500 text-sm">Chargement ou aucun historique...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Audit -->
            <div x-show="activeTab === 'audit'" class="text-sm text-gray-600 bg-gray-50 p-4 rounded-xl border border-gray-100 font-mono space-y-2">
                <div class="flex justify-between"><span>Créé le:</span> <span class="font-bold text-gray-800" x-text="selectedMember.audit.created_at"></span></div>
                <div class="flex justify-between"><span>Par:</span> <span class="font-bold text-gray-800" x-text="selectedMember.audit.created_by"></span></div>
                <hr class="border-gray-200" />
                <div class="flex justify-between"><span>Mise à jour:</span> <span class="font-bold text-gray-800" x-text="selectedMember.audit.updated_at"></span></div>
                 <div class="flex justify-between"><span>Par:</span> <span class="font-bold text-gray-800" x-text="selectedMember.audit.updated_by"></span></div>
            </div>

        </div>

    </div>
  </div>
</div>
