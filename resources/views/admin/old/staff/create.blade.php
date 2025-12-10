@extends('layouts.app')
@section('title', 'Add New Staff')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h2 class="text-2xl font-semibold text-gray-800 mb-6">Add New Staff Member</h2>

  @if ($errors->any())
    <div class="mb-4 bg-red-50 text-red-600 p-3 rounded border border-red-200">
      <ul class="list-disc pl-5 text-sm">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('staff.store') }}" method="POST" class="space-y-5">
    @csrf

    <div>
      <label class="block text-gray-700 mb-1">First Name</label>
      <input type="text" name="first_name" value="{{ old('first_name') }}" required
             class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
    </div>

    <div>
      <label class="block text-gray-700 mb-1">Last Name</label>
      <input type="text" name="last_name" value="{{ old('last_name') }}" required
             class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
    </div>

    <div>
      <label class="block text-gray-700 mb-1">Username</label>
      <input type="text" name="username" value="{{ old('username') }}" required
             class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
    </div>

    <div>
      <label class="block text-gray-700 mb-1">Password</label>
      <input type="password" name="password" required
             class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
    </div>

    <div>
      <label class="block text-gray-700 mb-1">Role</label>
      <select name="role_id" required
              class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
        <option value="">Select Role</option>
        @foreach ($roles as $role)
          <option value="{{ $role->role_id }}">{{ ucfirst($role->role_name) }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="inline-flex items-center">
        <input type="checkbox" name="is_active" class="rounded text-blue-600 mr-2" checked>
        <span class="text-gray-700">Active</span>
      </label>
    </div>

    <div class="flex justify-end gap-3">
      <a href="{{ route('staff.index') }}" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
      <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700">Save</button>
    </div>
  </form>
</div>


@endsection
