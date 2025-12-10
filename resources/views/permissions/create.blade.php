@extends('layouts.app')
@section('title', 'Create Permission')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-gray-800">Add New Permission</h2>
        <a href="{{ route('permissions.index') }}" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition">
            ← Back
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white shadow rounded-xl border border-gray-100 p-8">
        <form action="{{ route('permissions.store') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-gray-700 font-medium mb-2">Permission Name</label>
                <input type="text" name="permission_name" 
                       class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200" 
                       placeholder="e.g. manage_users" required>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Save Permission
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
