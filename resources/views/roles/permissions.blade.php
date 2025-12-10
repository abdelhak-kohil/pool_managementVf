@extends('layouts.app')
@section('title', 'Manage Permissions')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-gray-800">
            Manage Permissions: <span class="text-blue-600">{{ $role->role_name }}</span>
        </h2>
        <a href="{{ route('roles.index') }}" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition">
            ← Back
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white shadow rounded-xl border border-gray-100 p-8">
        <form action="{{ route('roles.permissions.update', $role->role_id) }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($permissions as $perm)
                <label class="relative flex items-center gap-3 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md group">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->permission_id }}"
                           {{ in_array($perm->permission_id, $assigned) ? 'checked' : '' }}
                           class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                    <span class="text-gray-700 font-medium group-hover:text-gray-900 transition-colors">{{ $perm->permission_name }}</span>
                </label>
                @endforeach
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Save Permissions
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
