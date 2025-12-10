@extends('layouts.app')
@section('title', 'Add Access Log')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h2 class="text-xl font-semibold text-gray-800 mb-6">Add Manual Access Log</h2>

  <form method="POST" action="{{ route('access-logs.store') }}" class="space-y-4">
    @csrf

    <div>
      <label class="block text-sm font-medium text-gray-700">Member (optional)</label>
      <select name="member_id" class="w-full border rounded-lg px-3 py-2">
        <option value="">— Select Member —</option>
        @foreach($members as $m)
          <option value="{{ $m->member_id }}">{{ $m->first_name }} {{ $m->last_name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Badge UID</label>
      <input type="text" name="badge_uid" required class="w-full border rounded-lg px-3 py-2 font-mono">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Access Decision</label>
      <select name="access_decision" required class="w-full border rounded-lg px-3 py-2">
        @foreach($decisions as $d)
          <option value="{{ $d->decision }}">{{ ucfirst($d->decision) }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Denial Reason (if denied)</label>
      <textarea name="denial_reason" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
    </div>

    <div class="flex justify-end mt-4">
      <a href="{{ route('access-logs.index') }}" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
      <button class="ml-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
    </div>
  </form>
</div>
@endsection
