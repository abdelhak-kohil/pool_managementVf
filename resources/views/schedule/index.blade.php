@extends('layouts.app')
@section('title', 'Planning Hebdomadaire (Samedi → Vendredi)')

@section('content')
<div class="mb-6">
  <h2 class="text-2xl font-semibold text-blue-700">🕒 Planning Hebdomadaire</h2>
  <p class="text-gray-500 text-sm">Semaine fixe : Samedi à Vendredi (08:00 → 21:00)</p>
</div>

<!-- 🎨 LÉGENDE -->
<div class="flex flex-wrap gap-3 mb-5 bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
  <h3 class="font-semibold text-gray-700 w-full mb-2">🗂️ Légende des activités</h3>
  @foreach($activities as $activity)
    <div class="flex items-center gap-2">
      <span class="w-4 h-4 rounded" style="background-color: {{ $activity->color_code ?? '#60a5fa' }}"></span>
      <span class="text-sm text-gray-700">{{ $activity->name }}</span>
    </div>
  @endforeach
</div>

<!-- 🗓️ CALENDRIER -->
<div id="calendar" class="bg-white rounded-xl shadow p-4 border border-gray-200"></div>

<!-- ➕ MODAL AJOUT (Admin uniquement) -->
@if(auth()->user()->hasRole('admin'))
<div id="slotModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
    <h3 class="text-lg font-semibold mb-3 text-blue-700">➕ Ajouter un créneau</h3>
    <form id="slotForm">
      @csrf
      <input type="hidden" id="weekday_id" name="weekday_id">

      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700">Activité</label>
        <select name="activity_id" required class="border rounded-lg p-2 w-full focus:ring focus:ring-blue-200">
          <option value="">— Sélectionner une activité —</option>
          @foreach($activities as $activity)
            <option value="{{ $activity->activity_id }}">{{ $activity->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" rows="2" class="border rounded-lg p-2 w-full focus:ring focus:ring-blue-200" placeholder="Commentaires facultatifs..."></textarea>
      </div>

      <div class="text-right">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded mr-2 hover:bg-gray-100">Annuler</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
@endif

<!-- 🧾 MODAL DÉTAILS -->
<div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-2xl relative">
    <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700" onclick="closeDetailsModal()">✕</button>
    <h3 class="text-xl font-semibold text-blue-700 mb-4">📋 Détails du créneau</h3>

    <div id="slotDetails" class="space-y-3 text-gray-700 text-sm">
      <p><strong>Activité :</strong> <span id="d_activity"></span></p>
      <p><strong>Jour :</strong> <span id="d_day"></span></p>
      <p><strong>Heures :</strong> <span id="d_time"></span></p>
      <p><strong>Type de réservation :</strong> <span id="d_type"></span></p>
      <div id="d_member_block" class="hidden">
        <p><strong>Membre :</strong> <span id="d_member"></span></p>
        <p><strong>Téléphone :</strong> <span id="d_phone"></span></p>
      </div>
      <div id="d_group_block" class="hidden">
        <p><strong>Groupe partenaire :</strong> <span id="d_group"></span></p>
        <p><strong>Contact :</strong> <span id="d_contact"></span></p>
      </div>
      <p><strong>Ajouté par :</strong> <span id="d_created_by"></span></p>
      <p><strong>Notes :</strong> <span id="d_notes"></span></p>
      <hr class="my-3">
      
      @if(auth()->user()->hasRole('admin'))
      <div class="text-right">
        <button onclick="deleteSlot()" id="deleteSlotBtn" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">🗑 Supprimer</button>
      </div>
      @endif
    </div>
  </div>
</div>

<!-- JS Libraries -->
<link href="{{ asset('vendor/fullcalendar/index.global.min.css') }}" rel="stylesheet" />
<script src="{{ asset('vendor/fullcalendar/index.global.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

<script>
// Role-based permissions for calendar interaction
const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
let startSel = null, endSel = null, weekdaySel = null, selectedEventId = null;

document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true
  });

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',
    timeZone: 'Africa/Algiers',
    allDaySlot: false,
    slotMinTime: '08:00:00',
    slotMaxTime: '21:00:00',
    locale: 'fr',
    firstDay: 1, 
    editable: isAdmin,      // Only admin can drag/drop
    selectable: isAdmin,    // Only admin can create new slots
    height: 'auto',
    expandRows: true,
    nowIndicator: true,
    headerToolbar: false,
    dayHeaderFormat: { weekday: 'long' },

    events: '{{ route('schedule.events') }}',

    select: function(info) {
      startSel = info.startStr;
      endSel = info.endStr;
      weekdaySel = new Date(info.startStr).getDay() || 7;
      document.getElementById('weekday_id').value = weekdaySel;
      openModal();
    },

    eventDrop: info => handleEventUpdate(info.event),
    eventResize: info => handleEventUpdate(info.event),

    eventClick: async function(info) {
      selectedEventId = info.event.id;
      const res = await fetch(`/admin/schedule/details/${info.event.id}`);
      const data = await res.json();

      if (res.ok) {
        document.getElementById('d_activity').textContent = data.activity_name || '—';
        document.getElementById('d_day').textContent = data.day_name;
        document.getElementById('d_time').textContent = `${data.start_time} → ${data.end_time}`;
        document.getElementById('d_type').textContent = data.reservation_type || 'Non réservé';
        document.getElementById('d_notes').textContent = data.notes || '—';
        document.getElementById('d_created_by').textContent = data.created_by || '—';

        // Gestion membre / groupe
        document.getElementById('d_member_block').classList.add('hidden');
        document.getElementById('d_group_block').classList.add('hidden');

        if (data.reservation_type === 'member_private') {
          document.getElementById('d_member_block').classList.remove('hidden');
          document.getElementById('d_member').textContent = data.member_name;
          document.getElementById('d_phone').textContent = data.member_phone || '—';
        } else if (data.reservation_type === 'partner_group') {
          document.getElementById('d_group_block').classList.remove('hidden');
          document.getElementById('d_group').textContent = data.group_name;
          document.getElementById('d_contact').textContent = data.contact_name || '—';
        }

        openDetailsModal();
      } else {
        Toast.fire({ icon: 'error', title: 'Erreur lors du chargement' });
      }
    }
  });

  calendar.render();

  document.getElementById('slotForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('start', startSel);
    fd.append('end', endSel);

    const res = await fetch('{{ route("schedule.store") }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      body: fd
    });

    const data = await res.json();
    if (res.ok) {
      Toast.fire({ icon: 'success', title: data.success });
      closeModal();
      setTimeout(() => calendar.refetchEvents(), 600);
    } else {
      Toast.fire({ icon: 'error', title: 'Erreur lors de l’ajout' });
    }
  });

  async function handleEventUpdate(event) {
    const payload = {
      start: event.start ? event.start.toISOString() : null,
      end: event.end ? event.end.toISOString() : null,
      weekday: event.start ? (new Date(event.start).getDay() || 7) : null
    };

    try {
      const res = await fetch(`{{ url('admin/schedule/update') }}/${event.id}`, {
        method: 'PUT',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (res.ok) {
        Toast.fire({ icon: 'success', title: data.success || 'Créneau mis à jour' });
        setTimeout(() => calendar.refetchEvents(), 300);
      } else {
        Toast.fire({ icon: 'error', title: data.message || 'Erreur mise à jour' });
        event.revert();
      }
    } catch (err) {
      Toast.fire({ icon: 'error', title: 'Erreur réseau' });
      event.revert();
    }
  }
});

function openModal() {
  document.getElementById('slotModal').classList.remove('hidden');
}
function closeModal() {
  document.getElementById('slotModal').classList.add('hidden');
  document.getElementById('slotForm').reset();
}
function openDetailsModal() {
  document.getElementById('detailsModal').classList.remove('hidden');
}
function closeDetailsModal() {
  document.getElementById('detailsModal').classList.add('hidden');
}
async function deleteSlot() {
  if (!selectedEventId) return;
  Swal.fire({
    title: 'Confirmer la suppression ?',
    text: "Ce créneau sera définitivement supprimé.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    confirmButtonText: 'Supprimer',
    cancelButtonText: 'Annuler'
  }).then(async result => {
    if (result.isConfirmed) {
      const res = await fetch(`{{ url('admin/schedule') }}/${selectedEventId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
      });
      const data = await res.json();
      Swal.fire({ icon: 'success', title: data.success || 'Supprimé' });
      closeDetailsModal();
      setTimeout(() => location.reload(), 800);
    }
  });
}
</script>
@endsection
