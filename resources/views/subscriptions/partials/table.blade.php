<table id="subscriptionsTable" class="min-w-full text-left text-gray-800">
  <thead class="bg-gray-50 border-b">
    <tr>
      <th class="py-3 px-4 font-medium">#</th>
      <th class="py-3 px-4 font-medium">Membre</th>
      <th class="py-3 px-4 font-medium">Activité</th>
      <th class="py-3 px-4 font-medium">Période</th>
      <th class="py-3 px-4 font-medium">Visites / semaine</th>
      <th class="py-3 px-4 font-medium">Statut</th>
      <th class="py-3 px-4 font-medium text-right">Actions</th>
    </tr>
  </thead>
  <tbody class="divide-y divide-gray-100">
    @forelse ($subscriptions as $sub)
    
      @php
        /* === Slots sécurisés === */
        $slotData = $sub->slots->map(function($s) {
            $ts = optional($s->slot);
            $activity = optional($ts->activity);
            $weekday = optional($ts->weekday);

            $start = $ts->start_time;
            $end   = $ts->end_time;

            $duration = null;
            if ($start && $end) {
                $duration = \Carbon\Carbon::parse($start)->diffInMinutes(\Carbon\Carbon::parse($end));
            }

            return [
                'day' => $weekday->day_name ?? '-',
                'activity' => $activity->name ?? '—',
                'activity_color' => $activity->color ?? '#3b82f6',
                'start' => $start ?? '',
                'end' => $end ?? '',
                'duration' => $duration ? ($duration . ' min') : '',
            ];
        });

        /* === Badge === */
        $badge = $sub->member->accessbadge ?? null;
        $badgeColor = match(optional($badge)->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'lost' => 'yellow',
            'revoked' => 'red',
            default => 'gray'
        };

        /* === Allowed Days === */
        $allowedDays = $sub->allowedDays->map(function($d) {
            return optional($d->weekday)->day_name;
        })->filter()->join(', ');

        /* === Data envoyé à AlpineJS === */
        $subData = [
          'id' => $sub->subscription_id,
          'member' => $sub->member 
              ? $sub->member->first_name . ' ' . $sub->member->last_name 
              : ($sub->partnerGroup->name ?? 'Groupe Partenaire'),

          'badge' => [
            'uid' => $badge->badge_uid ?? 'Aucun',
            'status' => $badge->status ?? '—',
            'color' => $badgeColor
          ],

          'activity' => $sub->activity->name ?? '',
          'plan_type' => ucfirst(str_replace('_', ' ', $sub->plan->plan_type)),
          'price' => number_format(\Illuminate\Support\Facades\DB::table('pool_schema.activity_plan_prices')
                ->where('plan_id', $sub->plan_id)
                ->where('activity_id', $sub->activity_id)
                ->value('price') ?? 0, 2, '.', ''),

          'start' => $sub->start_date?->format('Y-m-d'),
          'end' => $sub->end_date?->format('Y-m-d'),
          'status' => ucfirst($sub->status),

          'visits' => $sub->visits_per_week ?? '-',
          'days' => $allowedDays,

          'slots' => $slotData,

          'payments' => $sub->payments->map(function($p) {
            return [
              'amount' => number_format($p->amount, 2, '.', ''),
              'method' => ucfirst($p->payment_method),
              'date' => $p->payment_date,
              'staff' => $p->staff->first_name ?? 'N/A',
            ];
          })->values(),

          'audit' => [
            'created_at' => optional($sub->created_at)->format('Y-m-d H:i'),
            'updated_at' => optional($sub->updated_at)->format('Y-m-d H:i'),
            'created_by' => $sub->createdBy->first_name ?? 'Système',
            'updated_by' => $sub->updatedBy->first_name ?? 'Système',
          ]
        ];
      @endphp

      <tr class="hover:bg-blue-50 transition">
        <td class="py-3 px-4 text-gray-500">{{ ($subscriptions->currentPage() - 1) * $subscriptions->perPage() + $loop->iteration }}</td>
        <td class="py-3 px-4 font-medium">
            @if($sub->member)
                {{ $sub->member->first_name }} {{ $sub->member->last_name }}
            @elseif($sub->partnerGroup)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    🏢 {{ $sub->partnerGroup->name }}
                </span>
            @else
                <span class="text-gray-400 italic">Inconnu</span>
            @endif
        </td>
        <td class="py-3 px-4">{{ $sub->activity->name ?? '-' }}</td>
        <td class="py-3 px-4">{{ $sub->start_date->format('Y-m-d') }} → {{ $sub->end_date->format('Y-m-d') }}</td>
        <td class="py-3 px-4 text-center">{{ $sub->visits_per_week ?? '-' }}</td>

        <td class="py-3 px-4">
          @php
            $colors = [
              'active' => 'bg-green-100 text-green-800',
              'paused' => 'bg-yellow-100 text-yellow-800',
              'expired' => 'bg-gray-200 text-gray-700',
              'cancelled' => 'bg-red-100 text-red-700',
            ];
          @endphp
          <span class="px-3 py-1 rounded-full text-sm font-medium {{ $colors[$sub->status] ?? 'bg-gray-100 text-gray-700' }}">
            {{ ucfirst($sub->status) }}
          </span>
        </td>

        <td class="py-3 px-4 text-right">
          <div class="flex justify-end gap-3">

            <button 
              type="button"
              @click="openModal({{ json_encode($subData) }})"
              class="text-blue-600 hover:text-blue-800 font-medium">
              👁 Voir
            </button>

            <a href="{{ route('subscriptions.edit', $sub->subscription_id) }}"
               class="text-yellow-600 hover:text-yellow-800 font-medium">✏️ Modifier</a>

            @if (Auth::user()->role->role_name === 'admin')
            <form id="delete-form-{{ $sub->subscription_id }}" 
                  action="{{ route('subscriptions.destroy', $sub->subscription_id) }}" 
                  method="POST" class="inline">
              @csrf
              @method('DELETE')
              <button type="button" 
                      class="text-red-600 hover:text-red-800" 
                      onclick="confirmDelete({{ $sub->subscription_id }})">
                🗑 Supprimer
              </button>
            </form>
            @endif

          </div>
        </td>

      </tr>

    @empty
    <tr>
      <td colspan="7" class="text-center py-6 text-gray-500">Aucun abonnement trouvé.</td>
    </tr>
    @endforelse
  </tbody>
</table>

<div class="mt-4 px-4 py-3">
    {{ $subscriptions->links() }}
</div>
