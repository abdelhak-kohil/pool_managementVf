@extends('layouts.app')
@section('title', 'Tableau de Bord des Présences')

@section('content')
<div x-data="attendanceDashboard()" x-init="initDashboard()" class="space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">📊 Tableau de Bord des Présences</h2>
    <div class="flex gap-3">
      <select x-model="period" @change="fetchStats()" class="border-gray-300 rounded-lg text-sm">
        <option value="week">Cette Semaine</option>
        <option value="month">Ce Mois</option>
        <option value="year">Cette Année</option>
        <option value="all_years">Toutes les années</option>
      </select>
      <button @click="exportPdf()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
        <span>📄</span> Exporter PDF
      </button>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <p class="text-sm font-medium text-gray-500">Total Visites</p>
      <p class="text-3xl font-bold text-blue-600 mt-1" x-text="stats.totalVisits"></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <p class="text-sm font-medium text-gray-500">Période</p>
      <p class="text-lg font-semibold text-gray-700 mt-1">
        <span x-text="stats.period.start"></span> - <span x-text="stats.period.end"></span>
      </p>
    </div>
  </div>

  <!-- Current Activity & Modal Trigger -->
  <div class="bg-white p-6 rounded-xl shadow border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
    
    <!-- Current Activity Info -->
    <div class="flex-1 w-full">
      <h3 class="text-lg font-semibold text-gray-800 mb-2">⚡ Activité en Cours</h3>
      
      <template x-if="stats.currentSlot">
        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 flex items-center justify-between">
          <div>
            <h4 class="text-xl font-bold text-blue-700" x-text="stats.currentSlot.activity_name"></h4>
            <p class="text-sm text-blue-600">
              <span x-text="stats.currentSlot.start_time.substring(0,5)"></span> - <span x-text="stats.currentSlot.end_time.substring(0,5)"></span>
            </p>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-blue-800" x-text="stats.currentSlot.attendees"></p>
            <p class="text-xs text-blue-500 uppercase font-semibold">Participants</p>
          </div>
        </div>
      </template>

      <template x-if="!stats.currentSlot">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center text-gray-500 italic">
          Aucune activité en cours actuellement.
        </div>
      </template>
    </div>

    <!-- View All Button -->
    <div>
      <button @click="openSlotsModal = true" class="px-5 py-3 bg-gray-800 hover:bg-gray-900 text-white rounded-lg font-medium shadow transition flex items-center gap-2">
        <span>📅</span> Voir tous les créneaux
      </button>
    </div>
  </div>

  <!-- Slots Modal -->
  <div x-show="openSlotsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm" x-transition.opacity>
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[80vh] overflow-hidden flex flex-col" @click.away="openSlotsModal = false">
      
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
        <h3 class="text-lg font-bold text-gray-800">📅 Créneaux d'Aujourd'hui</h3>
        <button @click="openSlotsModal = false" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="p-6 overflow-y-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <template x-for="slot in stats.todaySlots" :key="slot.slot_id">
            <div class="border rounded-lg p-4 bg-gray-50 hover:bg-white hover:shadow-sm transition">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <h4 class="font-bold text-gray-800" x-text="slot.activity_name || 'Activité'"></h4>
                  <p class="text-xs text-gray-500">
                    <span x-text="slot.start_time.substring(0,5)"></span> - <span x-text="slot.end_time.substring(0,5)"></span>
                  </p>
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-full bg-blue-100 text-blue-700" x-text="slot.percentage + '%'"></span>
              </div>

              <!-- Progress Bar -->
              <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1">
                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" :style="`width: ${slot.percentage}%`"></div>
              </div>
              
              <div class="flex justify-between text-xs text-gray-600 mt-1">
                <span>Participants: <strong x-text="slot.attendees"></strong></span>
                <span>Capacité: <strong x-text="slot.capacity || 'Illimité'"></strong></span>
              </div>
            </div>
          </template>

          <div x-show="!stats.todaySlots || stats.todaySlots.length === 0" class="col-span-full text-center py-10 text-gray-500">
            Aucun créneau prévu pour aujourd'hui.
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-3 border-t bg-gray-50 text-right">
        <button @click="openSlotsModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition">Fermer</button>
      </div>
    </div>
  </div>

  <!-- Charts Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Daily Visits Chart -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Visites par Jour</h3>
      <div class="h-64">
        <canvas id="dailyChart"></canvas>
      </div>
    </div>

    <!-- Heatmap (Peak Hours) -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Heures d'Affluence (Heatmap)</h3>
      <div class="h-64 relative">
        <canvas id="heatmapChart"></canvas>
      </div>
    </div>

  <!-- Charts Grid Row 2 -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Active Days Chart -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Jours les plus Actifs</h3>
      <div class="h-64">
        <canvas id="activeDaysChart"></canvas>
      </div>
    </div>

    <!-- Activities Chart -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Répartition par Activité</h3>
      <div class="h-64">
        <canvas id="activitiesChart"></canvas>
      </div>
    </div>

  </div>

  <!-- Top Members Table -->
  <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800">🏆 Top 10 Membres les Plus Actifs</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 text-gray-800 font-medium">
          <tr>
            <th class="p-4">Rang</th>
            <th class="p-4">Membre</th>
            <th class="p-4 text-right">Visites</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template x-for="(member, index) in stats.topMembers" :key="index">
            <tr class="hover:bg-gray-50">
              <td class="p-4 font-mono text-gray-400" x-text="index + 1"></td>
              <td class="p-4 font-medium text-gray-800">
                <span x-text="member.first_name"></span> <span x-text="member.last_name"></span>
              </td>
              <td class="p-4 text-right font-bold text-blue-600" x-text="member.visit_count"></td>
            </tr>
          </template>
          <tr x-show="!stats.topMembers || stats.topMembers.length === 0">
            <td colspan="3" class="p-6 text-center text-gray-500">Aucune donnée disponible.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Chart.js -->
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>

<script>
function attendanceDashboard() {
  return {
    period: 'week',
    stats: { totalVisits: 0, period: { start: '', end: '' }, topMembers: [], todaySlots: [], currentSlot: null },
    openSlotsModal: false,
    dailyChart: null,
    heatmapChart: null,
    activeDaysChart: null,
    activitiesChart: null,

    initDashboard() {
      this.fetchStats();
    },

    fetchStats() {
      fetch(`{{ route('attendance.stats') }}?period=${this.period}`)
        .then(res => res.json())
        .then(data => {
          this.stats = data;
          this.renderDailyChart(data.daily);
          this.renderHeatmap(data.heatmap);
          this.renderActiveDaysChart(data.activeDays);
          this.renderActivitiesChart(data.activities);
        });
    },

    renderDailyChart(data) {
      const ctx = document.getElementById('dailyChart').getContext('2d');
      if (this.dailyChart) this.dailyChart.destroy();

      this.dailyChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.map(d => d.date),
          datasets: [{
            label: 'Visiteurs',
            data: data.map(d => d.count),
            backgroundColor: '#3b82f6',
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { y: { beginAtZero: true } }
        }
      });
    },

    renderHeatmap(data) {
      const ctx = document.getElementById('heatmapChart').getContext('2d');
      if (this.heatmapChart) this.heatmapChart.destroy();

      const days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
      
      const dataset = data.map(d => ({
        x: d.hour_of_day,
        y: d.day_of_week,
        r: Math.sqrt(d.count) * 4 // Scale radius based on sqrt of count
      }));

      this.heatmapChart = new Chart(ctx, {
        type: 'bubble',
        data: {
          datasets: [{
            label: 'Affluence (Tendance)',
            data: dataset,
            backgroundColor: 'rgba(239, 68, 68, 0.6)',
            borderColor: 'rgba(239, 68, 68, 1)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { 
              min: 6, max: 22, 
              title: { display: true, text: 'Heure (06h - 22h)' } 
            },
            y: { 
              min: 0, max: 6, 
              ticks: { callback: val => days[val] } 
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: (ctx) => {
                  const d = ctx.raw;
                  // Reverse calc to get original count: (r / 4)^2
                  const count = Math.round(Math.pow(d.r / 4, 2));
                  return `${days[d.y]} à ${d.x}h : ~${count} visites (tendance)`;
                }
              }
            }
          }
        }
      });
    },

    renderActiveDaysChart(data) {
      const ctx = document.getElementById('activeDaysChart').getContext('2d');
      if (this.activeDaysChart) this.activeDaysChart.destroy();

      this.activeDaysChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.map(d => d.day_name.trim()),
          datasets: [{
            label: 'Visites Moyennes',
            data: data.map(d => d.count),
            backgroundColor: '#10b981',
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          scales: { x: { beginAtZero: true } }
        }
      });
    },

    renderActivitiesChart(data) {
      const ctx = document.getElementById('activitiesChart').getContext('2d');
      if (this.activitiesChart) this.activitiesChart.destroy();

      this.activitiesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: data.map(d => d.name),
          datasets: [{
            data: data.map(d => d.count),
            backgroundColor: [
              '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'
            ]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'right' }
          }
        }
      });
    },

    exportPdf() {
      window.location.href = `{{ route('attendance.export.pdf') }}?period=${this.period}`;
    }
  };
}
</script>
@endsection
