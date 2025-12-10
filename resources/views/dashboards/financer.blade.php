@extends('layouts.app')
@section('title', 'Financer Dashboard')

@section('content')
<div class="bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold">Financier Dashboard</h2>
  <p class="mt-2 text-gray-600">Bonjour, {{ auth()->user()->full_name }} ({{ auth()->user()->role->role_name }})</p>
</div>
@endsection
