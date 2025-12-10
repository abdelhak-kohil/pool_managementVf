@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="bg-white p-6 rounded shadow text-center">
  <h2 class="text-2xl font-semibold text-red-600 mb-4">Access Denied</h2>
  <p class="text-gray-700 mb-6">
    Sorry, you do not have permission to access this page.
  </p>
  <a href="{{ route('home') }}" class="bg-blue-600 text-white px-4 py-2 rounded">
    Go Back Home
  </a>
</div>
@endsection
