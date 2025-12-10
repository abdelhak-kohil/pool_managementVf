<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'Login')</title>

  <!-- ✅ TailwindCSS CDN -->
  <script src="{{ asset('vendor/tailwindcss/tailwindcss.js') }}"></script>
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(to right, #2563eb, #1e3a8a);
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen px-4">

  <!-- ✅ Auth Container -->
  <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-8">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Welcome Back 👋</h1>
      <p class="text-gray-500 text-sm">Please login to your account</p>
    </div>

    @yield('content')

    <footer class="text-center mt-6 text-xs text-gray-400">
      &copy; {{ date('Y') }} Your Company. All rights reserved.
    </footer>
  </div>
</body>
</html>
