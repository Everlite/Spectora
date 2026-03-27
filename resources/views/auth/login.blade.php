<x-auth-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div>
        <h3 class="text-gray-900 dark:text-white text-2xl font-bold sm:text-3xl">Willkommen zurück</h3>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="font-medium text-gray-700 dark:text-gray-300">E-Mail Adresse</label>
            <input type="email" name="email" id="email" required autofocus autocomplete="username"
                class="w-full mt-2 px-3 py-2 text-gray-500 bg-transparent outline-none border focus:border-indigo-600 shadow-sm rounded-lg dark:text-gray-300 dark:border-gray-700 dark:focus:border-indigo-500"
                value="{{ old('email') }}">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="font-medium text-gray-700 dark:text-gray-300">Passwort</label>
            <input type="password" name="password" id="password" required autocomplete="current-password"
                class="w-full mt-2 px-3 py-2 text-gray-500 bg-transparent outline-none border focus:border-indigo-600 shadow-sm rounded-lg dark:text-gray-300 dark:border-gray-700 dark:focus:border-indigo-500">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700">
                <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Angemeldet bleiben</label>
            </div>
        </div>

        <button class="w-full px-4 py-2 text-white font-medium bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-600 rounded-lg duration-150 transition-colors shadow-lg shadow-indigo-500/30">
            Einloggen
        </button>
    </form>
</x-auth-layout>
