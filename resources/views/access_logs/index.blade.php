@extends('layouts.app')
@section('title', 'Access Logs')

@section('content')
<div class="flex justify-between items-center mb-6">
  <h2 class="text-2xl font-semibold text-gray-800">Access Logs</h2>
  <a href="{{ route('access-logs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
    + Add Log
  </a>
</div>

@if(session('success'))
  <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
@endif

<table class="w-full border-collapse border border-gray-200 rounded-lg overflow-hidden text-sm">
  <thead class="bg-gray-100 text-gray-700">
    <tr>
      <th class="p-3 text-left">#</th>
      <th class="p-3 text-left">Badge UID</th>
      <th class="p-3 text-left">Member</th>
      <th class="p-3 text-left">Decision</th>
      <th class="p-3 text-left">Reason</th>
      <th class="p-3 text-left">Time</th>
      <th class="p-3 text-left">Actions</th>
    </tr>
  </thead>
  <tbody>
    @foreach($logs as $log)
      <tr class="border-t hover:bg-gray-50">
        <td class="p-3">{{ $log->log_id }}</td>
        <td class="p-3 font-mono text-sm text-gray-700">{{ $log->badge_uid }}</td>
        <td class="p-3">{{ $log->member->first_name ?? '-' }} {{ $log->member->last_name ?? '' }}</td>
        <td class="p-3">
          <span class="px-2 py-1 rounded text-xs font-medium
            {{ $log->access_decision === 'granted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
            {{ ucfirst($log->access_decision) }}
          </span>
        </td>
        <td class="p-3">{{ $log->denial_reason ?? '-' }}</td>
        <td class="p-3 text-gray-500">{{ \Carbon\Carbon::parse($log->access_time)->format('Y-m-d H:i') }}</td>
        <td class="p-3 flex gap-2">
          <a href="{{ route('access-logs.edit', $log->log_id) }}" class="text-blue-600 hover:underline">Edit</a>
          <form action="{{ route('access-logs.destroy', $log->log_id) }}" method="POST" onsubmit="return confirm('Delete this log?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-red-600 hover:underline">Delete</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endsection
