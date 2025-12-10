@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="bg-white p-6 rounded shadow">
  <h2 class="text-lg font-semibold">Welcome, {{ auth()->user()->full_name }}!</h2>
  <p class="text-sm text-gray-600 mt-2">
    Role: {{ auth()->user()->role->role_name ?? 'N/A' }}
  </p>
</div>
@endsection
