@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <div class="flex justify-between items-center mb-6">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Audit Logs</h2>
      <p class="text-sm text-gray-500">Tracks all database changes with before/after details.</p>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 rounded-lg text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="border p-3 text-left">#</th>
          <th class="border p-3 text-left">User</th>
          <th class="border p-3 text-left">Table</th>
          <th class="border p-3 text-left">Action</th>
          <th class="border p-3 text-left">Record ID</th>
          <th class="border p-3 text-left">Date</th>
          <th class="border p-3 text-center">Details</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
          <tr class="hover:bg-gray-50">
            <td class="border p-3">{{ $log->log_id }}</td>
            <td class="border p-3">{{ $log->staff->username ?? 'System' }}</td>
            <td class="border p-3">{{ $log->table_name }}</td>
            <td class="border p-3 font-medium {{ $log->action == 'DELETE' ? 'text-red-600' : ($log->action == 'UPDATE' ? 'text-yellow-600' : 'text-green-600') }}">
              {{ $log->action }}
            </td>
            <td class="border p-3">{{ $log->record_id }}</td>
            <td class="border p-3">{{ $log->change_timestamp->format('Y-m-d H:i:s') }}</td>
            <td class="border p-3 text-center">
              <button onclick="viewDetails({{ $log->log_id }})" class="text-blue-600 hover:underline">View</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-gray-500 p-4">No logs found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $logs->links() }}</div>
</div>

<!-- ✅ Details Modal -->
<!-- ✅ Audit Log Details Modal -->
<div id="logModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg p-6 w-full max-w-3xl shadow-lg overflow-y-auto max-h-[90vh]">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Change Details</h3>
      <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
    </div>

    <div id="logMeta" class="mb-3 text-sm text-gray-600"></div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <h4 class="font-semibold text-gray-700 mb-1">Old Data</h4>
        <div id="oldData" class="bg-gray-50 border border-gray-200 rounded p-3 text-xs overflow-x-auto max-h-[60vh]"></div>
      </div>
      <div>
        <h4 class="font-semibold text-gray-700 mb-1">New Data</h4>
        <div id="newData" class="bg-gray-50 border border-gray-200 rounded p-3 text-xs overflow-x-auto max-h-[60vh]"></div>
      </div>
    </div>

    <div class="flex justify-end mt-5">
      <button onclick="closeModal()" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Close</button>
    </div>
  </div>
</div>

<!-- ✅ Script to load and highlight changes -->
<script>
async function viewDetails(id) {
  const res = await fetch(`/admin/audit/${id}/details`);
  const data = await res.json();

  // Display meta info
  document.getElementById('logMeta').innerHTML = `
    <div><span class="font-semibold text-gray-800">Table:</span> ${data.table}</div>
    <div><span class="font-semibold text-gray-800">Action:</span> 
      <span class="${data.action === 'DELETE' ? 'text-red-600' : data.action === 'UPDATE' ? 'text-yellow-600' : 'text-green-600'} font-semibold">
        ${data.action}
      </span>
    </div>
    <div><span class="font-semibold text-gray-800">By:</span> ${data.changed_by}</div>
    <div><span class="font-semibold text-gray-800">At:</span> ${data.timestamp}</div>
  `;

  // Highlight differences
  const oldDataEl = document.getElementById('oldData');
  const newDataEl = document.getElementById('newData');

  oldDataEl.innerHTML = renderJSONDiff(data.old_data || {}, data.new_data || {}, 'old');
  newDataEl.innerHTML = renderJSONDiff(data.old_data || {}, data.new_data || {}, 'new');

  document.getElementById('logModal').classList.remove('hidden');
}

function renderJSONDiff(oldData, newData, mode) {
  let html = '';

  const allKeys = new Set([...Object.keys(oldData), ...Object.keys(newData)]);
  allKeys.forEach(key => {
    const oldVal = oldData[key];
    const newVal = newData[key];

    if (oldVal === undefined && newVal !== undefined && mode === 'new') {
      // Added
      html += `<div class="text-green-600"><b>+ ${key}</b>: ${formatValue(newVal)}</div>`;
    } else if (oldVal !== undefined && newVal === undefined && mode === 'old') {
      // Removed
      html += `<div class="text-red-600"><b>- ${key}</b>: ${formatValue(oldVal)}</div>`;
    } else if (oldVal !== newVal && oldVal !== undefined && newVal !== undefined) {
      if (mode === 'old') {
        html += `<div class="text-blue-600"><b>~ ${key}</b>: ${formatValue(oldVal)}</div>`;
      } else {
        html += `<div class="text-blue-600"><b>~ ${key}</b>: ${formatValue(newVal)}</div>`;
      }
    } else if (oldVal === newVal && mode === 'new') {
      html += `<div class="text-gray-600"><b>${key}</b>: ${formatValue(newVal)}</div>`;
    }
  });

  if (!html) html = '<span class="text-gray-400 italic">No data</span>';
  return html;
}

function formatValue(value) {
  if (typeof value === 'object' && value !== null) {
    return `<pre class="inline text-gray-700">${JSON.stringify(value, null, 2)}</pre>`;
  }
  return `<span class="text-gray-700">${value}</span>`;
}

function closeModal() {
  document.getElementById('logModal').classList.add('hidden');
}
</script>

@endsection
