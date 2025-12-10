@extends('layouts.app')

@section('title', 'Staff Dashboard')

@section('content')
<div class="bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold">Staff Dashboard</h2>
  <p class="text-gray-600 mt-2">Welcome, {{ auth()->user()->full_name }} ({{ auth()->user()->role->role_name }})</p>
</div>
@endsection
