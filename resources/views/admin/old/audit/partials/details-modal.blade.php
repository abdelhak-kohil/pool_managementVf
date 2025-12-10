<!-- ✅ Audit Log Details Modal -->
<div id="logModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg p-6 w-full max-w-3xl shadow-lg overflow-y-auto max-h-[90vh]">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Change Details</h3>
      <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
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

<!-- ✅ Script to load and highlight JSON diff -->
<script>
async function viewDetails(id) {
  const res = await fetch(`/admin/audit/${id}/details`);
  const data = await res.json();

  // Metadata section
  document.getElementById('logMeta').innerHTML = `
    <div><span class="font-semibold text-gray-800">Table:</span> ${data.table}</div>
    <div><span class="font-semibold text-gray-800">Action:</span> 
      <span class="${data.action === 'DELETE' ? 'text-red-600' : data.action === 'UPDATE' ? 'text-yellow-600' : 'text-green-600'} font-semibold">
        ${data.action}
      </span>
    </div>
    <div><span class="font-semibold text-gray-800">Changed By:</span> ${data.changed_by}</div>
    <div><span class="font-semibold text-gray-800">At:</span> ${data.timestamp}</div>
  `;

  // Render diff
  document.getElementById('oldData').innerHTML = renderJSONDiff(data.old_data || {}, data.new_data || {}, 'old');
  document.getElementById('newData').innerHTML = renderJSONDiff(data.old_data || {}, data.new_data || {}, 'new');

  document.getElementById('logModal').classList.remove('hidden');
}

function renderJSONDiff(oldData, newData, mode) {
  let html = '';
  const allKeys = new Set([...Object.keys(oldData), ...Object.keys(newData)]);

  allKeys.forEach(key => {
    const oldVal = oldData[key];
    const newVal = newData[key];

    if (oldVal === undefined && newVal !== undefined && mode === 'new') {
      html += `<div class="text-green-600"><b>+ ${key}</b>: ${formatValue(newVal)}</div>`;
    } else if (oldVal !== undefined && newVal === undefined && mode === 'old') {
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
