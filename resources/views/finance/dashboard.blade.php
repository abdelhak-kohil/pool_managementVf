@extends('layouts.app')
@section('title', 'Tableau de Bord Financier')

@section('content')
<div x-data="financeDashboard()" x-init="initDashboard()" class="space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">💰 Tableau de Bord Financier</h2>
    <div class="flex gap-3">
      <a href="{{ route('expenses.index') }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition flex items-center gap-2 shadow-sm">
        <span>💸</span> Gérer Dépenses
      </a>
      <a href="{{ route('sales.dashboard') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 shadow-sm">
        <span>📈</span> Dashboard Ventes
      </a>
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
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
    <!-- Revenue -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100 cursor-pointer hover:border-blue-300 transition"
         @click="goToPayments()">
      <p class="text-sm font-medium text-gray-500">Chiffre d'Affaires</p>
      <p class="text-3xl font-bold text-blue-600 mt-1" x-html="formatCurrency(stats.kpi.revenue)"></p>
      <div class="flex items-center mt-2 text-sm">
        <span :class="stats.kpi.growth >= 0 ? 'text-green-500' : 'text-red-500'" class="font-bold">
            <span x-text="stats.kpi.growth >= 0 ? '+' : ''"></span><span x-text="stats.kpi.growth.toFixed(1)"></span>%
        </span>
        <span class="text-gray-400 ml-1">vs période préc.</span>
      </div>
    </div>

    <!-- Expenses -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100 cursor-pointer hover:border-red-300 transition"
         @click="window.location.href='{{ route('expenses.index') }}'">
      <p class="text-sm font-medium text-gray-500">Dépenses Totales</p>
      <p class="text-3xl font-bold text-red-600 mt-1" x-html="formatCurrency(stats.kpi.expenses)"></p>
    </div>

    <!-- Profit -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <p class="text-sm font-medium text-gray-500">Bénéfice Net</p>
      <p class="text-3xl font-bold mt-1" 
         :class="stats.kpi.profit >= 0 ? 'text-green-600' : 'text-red-600'"
         x-html="formatCurrency(stats.kpi.profit)"></p>
    </div>

    <!-- Subscriptions -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100 cursor-pointer hover:border-purple-300 transition"
         @click="window.location.href='{{ route('subscriptions.index') }}'">
      <p class="text-sm font-medium text-gray-500">Abonnements Vendus</p>
      <p class="text-xl xl:text-2xl font-bold text-purple-600 mt-1 truncate" x-text="stats.kpi.subscriptions"></p>
      <div class="flex items-center mt-2 text-sm text-gray-500">
        <span class="font-bold text-gray-700" x-html="formatCurrency(stats.kpi.subscription_revenue)"></span>
        <span class="ml-1">revenus</span>
      </div>
    </div>

    <!-- Shop Sales -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100 cursor-pointer hover:border-orange-300 transition"
         @click="window.location.href='{{ route('sales.dashboard') }}'">
      <p class="text-sm font-medium text-gray-500">Ventes Boutique</p>
      <p class="text-3xl font-bold text-orange-600 mt-1" x-html="formatCurrency(stats.kpi.shop_revenue)"></p>
      <div class="flex items-center mt-2 text-sm text-gray-500">
        <span class="text-green-600 font-bold" x-html="formatCurrency(stats.kpi.shop_profit)"></span>
        <span class="ml-1">bénéfice</span>
      </div>
    </div>
  </div>

  <!-- Charts Grid Row 1 -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Profit Trend Chart -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">📉 Revenus vs Dépenses</h3>
      <div class="h-64">
        <canvas id="profitChart"></canvas>
      </div>
    </div>

    <!-- Expenses Breakdown Chart -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">💸 Répartition des Dépenses</h3>
      <div class="h-64">
        <canvas id="expensesChart"></canvas>
      </div>
    </div>

  </div>

  <!-- Charts Grid Row 2 -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Sales by Activity -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">🏋️ Ventes par Activité</h3>
      <div class="h-64">
        <canvas id="activitiesChart"></canvas>
      </div>
    </div>

    <!-- Payment Methods Chart -->
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">💳 Méthodes de Paiement</h3>
      <div class="h-64">
        <canvas id="methodsChart"></canvas>
      </div>
    </div>

  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Revenue by Plan Table -->
      <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-800">📊 Chiffre d'Affaires par Plan</h3>
        </div>
        <div class="p-6 space-y-4">
            <template x-for="plan in stats.tables.plans" :key="plan.plan_id">
                <div class="cursor-pointer group" @click="goToPayments('plan_id', plan.plan_id)">
                    <div class="flex justify-between text-sm mb-1 group-hover:text-blue-600 transition-colors">
                        <span class="font-medium text-gray-700" x-text="plan.plan_name"></span>
                        <span class="font-bold text-gray-900" x-html="formatCurrency(plan.total)"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" :style="`width: ${(plan.total / stats.kpi.revenue) * 100}%`"></div>
                    </div>
                </div>
            </template>
            <div x-show="!stats.tables.plans.length" class="text-center text-gray-500 italic">Aucune donnée.</div>
        </div>
      </div>

      <!-- Top Members Table -->
      <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-800">🏆 Top Membres (Revenus)</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-gray-800 font-medium">
              <tr>
                <th class="p-4">Membre</th>
                <th class="p-4 text-center">Trans.</th>
                <th class="p-4 text-right">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <template x-for="member in stats.tables.members" :key="member.member_id">
                <tr class="hover:bg-gray-50 cursor-pointer" @click="goToMember(member.member_id)">
                  <td class="p-4 font-medium text-gray-800">
                    <span x-text="member.first_name"></span> <span x-text="member.last_name"></span>
                  </td>
                  <td class="p-4 text-center" x-text="member.transaction_count"></td>
                  <td class="p-4 text-right font-bold text-blue-600" x-html="formatCurrency(member.total_paid)"></td>
                </tr>
              </template>
              <tr x-show="!stats.tables.members.length">
                <td colspan="3" class="p-6 text-center text-gray-500">Aucune donnée disponible.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

  </div>

</div>

<!-- Chart.js -->
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>

<script>
function financeDashboard() {
  return {
    period: 'month',
    stats: { 
        kpi: { revenue: 0, subscriptions: 0, avg_payment: 0, growth: 0, expenses: 0, profit: 0 },
        period: { start: '', end: '', label: '' },
        tables: { plans: [], members: [] },
        charts: { trend: [], methods: [], activities: [], heatmap: [], expenses_trend: [], expenses_by_category: [] }
    },
    profitChart: null,
    expensesChart: null,
    methodsChart: null,
    activitiesChart: null,

    initDashboard() {
      this.fetchStats();
    },

    fetchStats() {
      fetch(`{{ route('finance.stats') }}?period=${this.period}`)
        .then(res => res.json())
        .then(data => {
          this.stats = data;
          this.renderProfitChart(data.charts.trend, data.charts.expenses_trend);
          this.renderExpensesChart(data.charts.expenses_by_category);
          this.renderMethodsChart(data.charts.methods);
          this.renderActivitiesChart(data.charts.activities);
        });
    },

    formatCurrency(value) {
        let formatted = new Intl.NumberFormat('fr-DZ', { style: 'currency', currency: 'DZD' }).format(value);
        return formatted.replace('DA', '<small class="text-lg font-medium text-gray-400">DA</small>');
    },

    goToPayments(filterKey = null, filterValue = null) {
        let url = `{{ route('payments.index') }}?from=${this.stats.period.start}&to=${this.stats.period.end}`;
        if (filterKey && filterValue) {
            url += `&${filterKey}=${filterValue}`;
        }
        window.location.href = url;
    },

    goToMember(id) {
        window.location.href = `/admin/members/${id}/info`;
    },

    exportPdf() {
        window.location.href = `{{ route('payments.export.pdf') }}?from=${this.stats.period.start}&to=${this.stats.period.end}`;
    },

    renderProfitChart(revenueData, expenseData) {
      const ctx = document.getElementById('profitChart').getContext('2d');
      if (this.profitChart) this.profitChart.destroy();

      // Merge dates
      const labels = [...new Set([...revenueData.map(d => d.date), ...expenseData.map(d => d.date)])].sort();

      const getVal = (data, date) => {
          const found = data.find(d => d.date === date);
          return found ? parseFloat(found.total) : 0;
      };

      this.profitChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Revenus',
              data: labels.map(l => getVal(revenueData, l)),
              borderColor: '#2563eb',
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              borderWidth: 2,
              fill: true,
              tension: 0.3
            },
            {
              label: 'Dépenses',
              data: labels.map(l => getVal(expenseData, l)),
              borderColor: '#dc2626',
              backgroundColor: 'rgba(220, 38, 38, 0.1)',
              borderWidth: 2,
              fill: true,
              tension: 0.3
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { y: { beginAtZero: true } }
        }
      });
    },

    renderExpensesChart(data) {
      const ctx = document.getElementById('expensesChart').getContext('2d');
      if (this.expensesChart) this.expensesChart.destroy();

      const categories = {
            'salary': 'Salaires',
            'electricity': 'Électricité',
            'pool_products': 'Produits Piscine',
            'equipment': 'Matériel',
            'maintenance': 'Maintenance',
            'ads': 'Publicité',
            'other': 'Autre'
      };

      this.expensesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: data.map(d => categories[d.category] || d.category),
          datasets: [{
            data: data.map(d => d.total),
            backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#6b7280']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'right' } }
        }
      });
    },

    renderMethodsChart(data) {
      const ctx = document.getElementById('methodsChart').getContext('2d');
      if (this.methodsChart) this.methodsChart.destroy();

      this.methodsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: data.map(d => d.payment_method),
          datasets: [{
            data: data.map(d => d.total),
            backgroundColor: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'right' } }
        }
      });
    },

    renderActivitiesChart(data) {
      const ctx = document.getElementById('activitiesChart').getContext('2d');
      if (this.activitiesChart) this.activitiesChart.destroy();

      this.activitiesChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.map(d => d.name),
          datasets: [{
            label: 'Ventes',
            data: data.map(d => d.total),
            backgroundColor: '#f59e0b',
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          onClick: (e, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const activityId = data[index].activity_id;
                if(activityId) this.goToPayments('activity_id', activityId);
            }
          }
        }
      });
    }
  };
}
</script>
@endsection
