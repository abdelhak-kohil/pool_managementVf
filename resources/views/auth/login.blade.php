<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion Staff - Pool Project</title>
  <script src="{{ asset('vendor/tailwindcss/tailwindcss.js') }}"></script>
<link href="{{ asset('vendor/fonts/inter.css') }}" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">

  <div class="w-full h-full flex overflow-hidden bg-white shadow-2xl rounded-none md:rounded-2xl md:max-w-5xl md:h-[600px] md:m-4">
    
    <!-- Left Side: Branding/Image -->
    <div class="hidden md:flex md:w-1/2 bg-blue-600 text-white flex-col justify-between p-12 relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-blue-800 opacity-90 z-0"></div>
      <!-- Abstract pool pattern overlay -->
      <div class="absolute inset-0 opacity-10 z-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;"></div>
      
      <div class="relative z-10">
        <div class="flex items-center gap-3 text-2xl font-bold">
          <span class="text-3xl">🏊</span>
          <span>Pool Manager</span>
        </div>
      </div>

      <div class="relative z-10 mb-10">
        <h2 class="text-4xl font-bold mb-4">Bienvenue !</h2>
        <p class="text-blue-100 text-lg leading-relaxed">
          Gérez votre piscine, vos membres et vos réservations en toute simplicité avec notre plateforme dédiée.
        </p>
      </div>

      <div class="relative z-10 text-sm text-blue-200">
        &copy; {{ date('Y') }} Pool Project. Tous droits réservés.
      </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="w-full md:w-1/2 flex flex-col justify-center p-8 md:p-12 bg-white">
      <div class="max-w-md mx-auto w-full">
        
        <div class="text-center mb-10">
          <h3 class="text-3xl font-bold text-gray-900 mb-2">Connexion</h3>
          <p class="text-gray-500">Accédez à votre espace staff</p>
        </div>

        @if ($errors->any())
          <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 text-sm flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div>
              <p class="font-medium">Erreur de connexion</p>
              <p>{{ $errors->first('login') }}</p>
            </div>
          </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="space-y-6">
          @csrf
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom d'utilisateur</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <input type="text" name="username" required 
                     class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-none bg-gray-50 focus:bg-white"
                     placeholder="Entrez votre identifiant">
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input type="password" name="password" required 
                     class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-none bg-gray-50 focus:bg-white"
                     placeholder="••••••••">
            </div>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input id="remember-me" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
              <label for="remember-me" class="ml-2 block text-sm text-gray-700 cursor-pointer">Se souvenir de moi</label>
            </div>
            <div class="text-sm">
              <!-- Forgot password link removed -->
            </div>
          </div>

          <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition transform hover:scale-[1.02]">
            Se connecter
          </button>
        </form>

      </div>
    </div>
  </div>

</body>
</html>
