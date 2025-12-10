@extends('layouts.app')
@section('title', 'Activity ↔ Plan Prices')

@section('content')
<div x-data="activityPlanPricesTable()">
  <!-- ===== HEADER ===== -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">Activity ↔ Plan Prices</h2>
    <a href="{{ route('activity-plan-prices.create') }}"
       class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition">
       ➕ Add New Price
    </a>
  </div>

  <!-- ===== SEARCH BAR ===== -->
  <div class="relative mb-6">
    <input type="text" x-model="search" placeholder="Search by activity or plan..."
           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5 shadow-sm transition duration-200">
    <svg xmlns="http://www.w3.org/2000/svg"
         class="absolute left-3 top-3 text-gray-500 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
    </svg>
  </div>

  @if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
  @endif

  <!-- ===== TABLE ===== -->
  <div class="bg-white rounded-xl shadow overflow-x-auto border border-gray-100">
      <table class="min-w-full text-left text-gray-800">
        <thead class="bg-gray-50 border-b">
            <tr>
            <th class="py-3 px-4 font-medium">#</th>
            <th class="py-3 px-4 font-medium">Activity</th>
            <th class="py-3 px-4 font-medium">Plan</th>
            <th class="py-3 px-4 font-medium text-right">Price (DZD)</th>
            <th class="py-3 px-4 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($prices as $p)
            <tr class="hover:bg-blue-50 transition" x-show="matchesSearch('{{ strtolower(($p->activity->name ?? '') . ' ' . ($p->plan->plan_name ?? '')) }}')">
                <td class="py-3 px-4 text-gray-500">{{ $loop->iteration }}</td>
                <td class="py-3 px-4 font-medium">{{ $p->activity->name ?? '-' }}</td>
                <td class="py-3 px-4">{{ $p->plan->plan_name ?? '-' }}</td>
                <td class="py-3 px-4 text-right text-blue-700 font-semibold">{{ number_format($p->price, 2) }}</td>
                <td class="py-3 px-4 text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('activity-plan-prices.edit', $p->id) }}" class="text-yellow-600 hover:text-yellow-800 font-medium">✏️ Edit</a>
                        <form action="{{ route('activity-plan-prices.destroy', $p->id) }}" method="POST" class="delete-form inline-block">
                            @csrf @method('DELETE')
                            <button type="button" class="text-red-600 hover:text-red-800 font-medium delete-btn">🗑 Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-6 text-gray-500">No activity-plan prices found.</td></tr>
            @endforelse
        </tbody>
      </table>
  </div>
</div>

<!-- Alpine & SweetAlert -->


<script>
function activityPlanPricesTable() {
    return {
        search: '',
        matchesSearch(text) {
            return text.includes(this.search.toLowerCase());
        }
    }
}

// ===== SweetAlert Delete Confirmation =====
document.addEventListener('click', e => {
  if (e.target.classList.contains('delete-btn')) {
    e.preventDefault();
    const form = e.target.closest('form');
    Swal.fire({
      title: 'Delete this price?',
      text: 'This action cannot be undone!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d33'
    }).then(result => {
      if (result.isConfirmed) form.submit();
    });
  }
});
</script>
@endsection
