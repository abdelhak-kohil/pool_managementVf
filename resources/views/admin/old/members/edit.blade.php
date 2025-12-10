@extends('layouts.app')
@section('title', 'Edit Member')

@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
  <h2 class="text-xl font-semibold text-gray-800 mb-6">Edit Member</h2>

  <form method="POST" action="{{ route('members.update', $member->member_id) }}" class="space-y-4">
    @csrf

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">First Name</label>
        <input type="text" name="first_name" value="{{ old('first_name', $member->first_name) }}" required class="w-full border rounded-lg px-3 py-2">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Last Name</label>
        <input type="text" name="last_name" value="{{ old('last_name', $member->last_name) }}" required class="w-full border rounded-lg px-3 py-2">
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $member->email) }}" class="w-full border rounded-lg px-3 py-2">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Phone</label>
        <input type="text" name="phone_number" value="{{ old('phone_number', $member->phone_number) }}" class="w-full border rounded-lg px-3 py-2">
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $member->date_of_birth) }}" class="w-full border rounded-lg px-3 py-2">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Address</label>
        <input type="text" name="address" value="{{ old('address', $member->address) }}" class="w-full border rounded-lg px-3 py-2">
      </div>
    </div>

    <div class="flex justify-end mt-4">
      <a href="{{ route('members.index') }}" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
      <button class="ml-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
    </div>
  </form>
</div>
@endsection