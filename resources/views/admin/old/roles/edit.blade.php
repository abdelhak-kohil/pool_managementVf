@extends('layouts.app')
@section('title', 'Edit Role')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-md mx-auto">
  <h2 class="text-xl font-semibold mb-4">Edit Role</h2>

  <form action="{{ route('roles.update', $role->role_id) }}" method="POST">
    @csrf
    <div class="mb-4">
      <label class="block text-sm mb-1">Role Name</label>
      <input type="text" name="role_name" value="{{ old('role_name', $role->role_name) }}"
             class="border rounded px-3 py-2 w-full" required>
    </div>
    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
    <a href="{{ route('roles.index') }}" class="ml-2 text-gray-700 hover:underline">Cancel</a>
  </form>
</div>
@endsection
