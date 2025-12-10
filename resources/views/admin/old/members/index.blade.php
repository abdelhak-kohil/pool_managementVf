@extends('layouts.app')
@section('title', 'Members List')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold text-gray-800">Members</h2>
    <a href="{{ route('members.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Add Member</a>
  </div>

  <form method="GET" class="mb-4 flex items-center gap-2">
    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email, or phone"
           class="border rounded-lg px-3 py-2 w-full md:w-80 focus:ring focus:ring-blue-200" />
    <button class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">Search</button>
  </form>

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 rounded-lg text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="border p-3 text-left">#</th>
          <th class="border p-3 text-left">Name</th>
          <th class="border p-3 text-left">Email</th>
          <th class="border p-3 text-left">Phone</th>
          <th class="border p-3 text-left">DOB</th>
          <th class="border p-3 text-left">Address</th>
          <th class="border p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($members as $member)
          <tr class="hover:bg-gray-50">
            <td class="border p-3">{{ $member->member_id }}</td>
            <td class="border p-3 font-medium">{{ $member->first_name }} {{ $member->last_name }}</td>
            <td class="border p-3">{{ $member->email ?? '—' }}</td>
            <td class="border p-3">{{ $member->phone_number ?? '—' }}</td>
            <td class="border p-3">{{ $member->date_of_birth ?? '—' }}</td>
            <td class="border p-3">{{ $member->address ?? '—' }}</td>
            <td class="border p-3 text-center">
              <a href="{{ route('members.edit', $member->member_id) }}" class="text-blue-600 hover:underline text-sm">Edit</a>
              <form action="{{ route('members.destroy', $member->member_id) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button onclick="return confirm('Delete this member?')" class="text-red-600 hover:underline text-sm ml-2">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="p-4 text-center text-gray-500">No members found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $members->links() }}</div>
</div>
@endsection
