@extends('layouts.app')
@section('title', 'Tableau de Bord des Ventes')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">📊 Tableau de Bord des Ventes</h2>
        <div class="flex gap-2">
            <a href="{{ route('shop.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <span>🛒 Point de Vente</span>
            </a>
            <a href="{{ route('products.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                <span>📦 Produits</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Revenue -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Chiffre d'Affaires Total</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalRevenue, 2) }} DZD</h3>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg">
                    <span class="text-2xl">💰</span>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                <span class="text-green-600 font-medium">↑ {{ $totalSalesCount }}</span> ventes au total
            </div>
        </div>

        <!-- Total Profit -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Bénéfice Net Total</p>
                    <h3 class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totalProfit, 2) }} DZD</h3>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <span class="text-2xl">📈</span>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                Marge globale: 
                @if($totalRevenue > 0)
                    <span class="font-medium">{{ number_format(($totalProfit / $totalRevenue) * 100, 1) }}%</span>
                @else
                    0%
                @endif
            </div>
        </div>

        <!-- Monthly Performance -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Ce Mois-ci</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($monthlyRevenue, 2) }} DZD</h3>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg">
                    <span class="text-2xl">📅</span>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                Bénéfice: <span class="text-green-600 font-medium">{{ number_format($monthlyProfit, 2) }} DZD</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4">🏆 Top 5 Produits</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                            <th class="pb-3">Produit</th>
                            <th class="pb-3 text-right">Vendus</th>
                            <th class="pb-3 text-right">Revenu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($topProducts as $product)
                        <tr>
                            <td class="py-3 font-medium text-gray-800">{{ $product->name }}</td>
                            <td class="py-3 text-right">{{ $product->total_sold }}</td>
                            <td class="py-3 text-right font-medium text-blue-600">{{ number_format($product->total_revenue, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Sales -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4">🕒 Ventes Récentes</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                            <th class="pb-3">Date</th>
                            <th class="pb-3">Client</th>
                            <th class="pb-3 text-right">Montant</th>
                            <th class="pb-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentSales as $sale)
                        <tr>
                            <td class="py-3 text-sm text-gray-500">{{ $sale->created_at->format('d/m H:i') }}</td>
                            <td class="py-3 text-sm">
                                @if($sale->member)
                                    {{ $sale->member->first_name }} {{ $sale->member->last_name }}
                                @else
                                    <span class="text-gray-400">Anonyme</span>
                                @endif
                            </td>
                            <td class="py-3 text-right font-medium text-gray-800">{{ number_format($sale->total_amount, 2) }}</td>
                            <td class="py-3 text-right">
                                <a href="{{ route('shop.sale.receipt', $sale->id) }}" target="_blank" class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-1 rounded transition" title="Reçu A4">
                                    📄
                                </a>
                                <a href="{{ route('shop.sale.ticket', $sale->id) }}" target="_blank" class="text-gray-500 hover:text-gray-700 hover:bg-gray-50 p-1 rounded transition" title="Ticket de Caisse">
                                    🧾
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
