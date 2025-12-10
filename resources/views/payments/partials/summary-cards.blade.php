<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  <!-- Total Today -->
  <div class="bg-white p-6 rounded-xl shadow border border-gray-100 flex items-center justify-between">
    <div>
      <p class="text-sm font-medium text-gray-500">Total du jour</p>
      <p class="text-3xl font-bold text-green-600 mt-1">{{ number_format($totalToday, 2) }} DZD</p>
      <p class="text-xs text-gray-400 mt-1">{{ now()->format('d/m/Y') }}</p>
    </div>
    <div class="p-3 bg-green-50 rounded-full">
      <span class="text-2xl">💰</span>
    </div>
  </div>

  <!-- Total Month -->
  <div class="bg-white p-6 rounded-xl shadow border border-gray-100 flex items-center justify-between">
    <div>
      <p class="text-sm font-medium text-gray-500">Total du mois</p>
      <p class="text-3xl font-bold text-blue-600 mt-1">{{ number_format($totalMonth, 2) }} DZD</p>
      <p class="text-xs text-gray-400 mt-1">Depuis {{ now()->startOfMonth()->format('d/m/Y') }}</p>
    </div>
    <div class="p-3 bg-blue-50 rounded-full">
      <span class="text-2xl">📅</span>
    </div>
  </div>

  <!-- Total All Time -->
  <div class="bg-white p-6 rounded-xl shadow border border-gray-100 flex items-center justify-between">
    <div>
      <p class="text-sm font-medium text-gray-500">Total cumulé</p>
      <p class="text-3xl font-bold text-purple-600 mt-1">{{ number_format($totalAll, 2) }} DZD</p>
      <p class="text-xs text-gray-400 mt-1">Tous les paiements</p>
    </div>
    <div class="p-3 bg-purple-50 rounded-full">
      <span class="text-2xl">📈</span>
    </div>
  </div>
</div>
