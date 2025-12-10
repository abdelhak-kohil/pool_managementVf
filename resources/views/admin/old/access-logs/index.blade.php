@extends('layouts.app')
@section('title', 'Access Logs')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold text-gray-800">Access Logs</h2>
    <div class="flex items-center gap-2">
      <a href="{{ route('access-logs.export') }}" id="exportBtn"
         class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        ⬇️ Export CSV
      </a>
      <a href="{{ route('access-logs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        + Add Manual Log
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="flex flex-wrap items-center gap-3 mb-4">
    <input type="text" id="searchInput" placeholder="Search by member or badge UID"
           class="border rounded-lg px-3 py-2 w-full md:w-64 focus:ring focus:ring-blue-200">

    <input type="date" id="startDate" class="border rounded-lg px-3 py-2">
    <input type="date" id="endDate" class="border rounded-lg px-3 py-2">

    <div class="flex gap-2">
      <button data-decision="" class="filter-btn bg-gray-200 text-gray-700 px-3 py-2 rounded active">All</button>
      <button data-decision="granted" class="filter-btn bg-green-100 text-green-700 px-3 py-2 rounded">Granted</button>
      <button data-decision="denied" class="filter-btn bg-red-100 text-red-700 px-3 py-2 rounded">Denied</button>
    </div>
  </div>

  <!-- Timeline -->
  <div id="timelineContainer">
    @include('admin.access-logs.partials.timeline', ['logs' => $logs])
  </div>
</div>

<script>
let timeout = null;
let currentDecision = '';

function fetchLogs() {
  const search = document.getElementById('searchInput').value;
  const start = document.getElementById('startDate').value;
  const end = document.getElementById('endDate').value;

  const params = new URLSearchParams({
    search: search,
    decision: currentDecision,
    start_date: start,
    end_date: end
  });

  fetch(`{{ route('access-logs.ajaxSearch') }}?${params.toString()}`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('timelineContainer').innerHTML = data.html;
    });
}

document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(timeout);
  timeout = setTimeout(fetchLogs, 400);
});

document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('bg-gray-300'));
    this.classList.add('bg-gray-300');
    currentDecision = this.getAttribute('data-decision');
    fetchLogs();
  });
});

document.getElementById('startDate').addEventListener('change', fetchLogs);
document.getElementById('endDate').addEventListener('change', fetchLogs);

// Update export link dynamically
document.getElementById('exportBtn').addEventListener('click', function(e) {
  e.preventDefault();
  const search = document.getElementById('searchInput').value;
  const start = document.getElementById('startDate').value;
  const end = document.getElementById('endDate').value;
  const params = new URLSearchParams({
    search: search,
    decision: currentDecision,
    start_date: start,
    end_date: end
  });
  window.location.href = `{{ route('access-logs.export') }}?${params.toString()}`;
});
</script>
@endsection
