@extends('layouts.app')
@section('title', 'Add Access Log')

@section('content')
<h2 class="text-2xl font-semibold mb-6 text-gray-800">Add New Access Log</h2>

<form action="{{ route('access-logs.store') }}" method="POST" class="grid grid-cols-2 gap-6">
  @csrf

  <div>
    <label class="block text-gray-700 mb-2">Badge UID</label>
    <input type="text" name="badge_uid" class="w-full border-gray-300 rounded p-2 focus:ring focus:ring-blue-200" required>
  </div>

  <div>
    <label class="block text-gray-700 mb-2">Member (optional)</label>
    <select name="member_id" class="w-full border-gray-300 rounded p-2">
      <option value="">-- Unknown / Guest --</option>
      @foreach($members as $m)
        <option value="{{ $m->member_id }}">{{ $m->first_name }} {{ $m->last_name }}</option>
      @endforeach
    </select>
  </div>

  <div>
    <label class="block text-gray-700 mb-2">Access Decision</label>
    <select name="access_decision" id="decisionSelect" class="w-full border-gray-300 rounded p-2">
      @foreach($decisions as $d)
        <option value="{{ $d }}">{{ ucfirst($d) }}</option>
      @endforeach
    </select>
  </div>

  <div id="reasonField" class="hidden">
    <label class="block text-gray-700 mb-2">Denial Reason</label>
    <input type="text" name="denial_reason" class="w-full border-gray-300 rounded p-2">
  </div>

  <div class="col-span-2">
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
      Save Log
    </button>
  </div>
</form>

<script>
document.getElementById('decisionSelect').addEventListener('change', function() {
  const reason = document.getElementById('reasonField');
  if (this.value === 'denied') reason.classList.remove('hidden');
  else reason.classList.add('hidden');
});
</script>
@endsection
