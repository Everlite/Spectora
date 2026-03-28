<x-auth-layout>
    <div>
        <h3 class="text-gray-900 dark:text-white text-2xl font-bold sm:text-3xl">Create Account</h3>
        <p class="mt-2 text-gray-500 dark:text-gray-400">Get started with Spectora – Professional monitoring for your domains.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-8 space-y-5">
        @csrf

        <!-- Name Row -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="font-medium text-gray-700 dark:text-gray-300">First Name</label>
                <input type="text" name="first_name" id="first_name" required autofocus
                    class="w-full mt-2 px-3 py-2 text-gray-500 bg-transparent outline-none border focus:border-indigo-600 shadow-sm rounded-lg dark:text-gray-300 dark:border-gray-700 dark:focus:border-indigo-500"
                    value="{{ old('first_name') }}">
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>
            <div>
                <label for="last_name" class="font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                <input type="text" name="last_name" id="last_name" required
                    class="w-full mt-2 px-3 py-2 text-gray-500 bg-transparent outline-none border focus:border-indigo-600 shadow-sm rounded-lg dark:text-gray-300 dark:border-gray-700 dark:focus:border-indigo-500"
                    value="{{ old('last_name') }}">
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <!-- Company Name -->
        <div>
            <label for="company_name" class="font-medium text-gray-700 dark:text-gray-300">Company Name</label>
            <input type="text" name="company_name" id="company_name" required
                class="w-full mt-2 px-3 py-2 text-gray-500 bg-transparent outline-none border focus:border-indigo-600 shadow-sm rounded-lg dark:text-gray-300 dark:border-gray-700 dark:focus:border-indigo-500"
                value="{{ old('company_name') }}" placeholder="Your Company LLC">
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <!-- Company Logo -->
        <div>
            <label for="logo" class="font-medium text-gray-700 dark:text-gray-300">Company Logo <span class="text-gray-400 font-normal">(optional)</span></label>
            <div class="mt-2 flex items-center gap-4">
                <label for="logo" class="cursor-pointer flex items-center gap-2 px-4 py-2 border border-dashed border-gray-600 rounded-lg text-sm text-gray-400 hover:border-indigo-500 hover:text-indigo-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Select image</span>
                    <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml" class="hidden">
                </label>
                <span id="logo-filename" class="text-sm text-gray-500"></span>
            </div>
            <p class="mt-1 text-xs text-gray-500">JPG, PNG, SVG or GIF. Max 2MB.</p>
            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="font-medium text-gray-700 dark:text-gray-300">Email Address</label>
            <input type="email" name="email" id="email" required autocomplete="username"
                class="w-full mt-2 px-3 py-2 text-gray-500 bg-transparent outline-none border focus:border-indigo-600 shadow-sm rounded-lg dark:text-gray-300 dark:border-gray-700 dark:focus:border-indigo-500"
                value="{{ old('email') }}">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="font-medium text-gray-700 dark:text-gray-300">Password</label>
            <input type="password" name="password" id="password" required autocomplete="new-password"
                class="w-full mt-2 px-3 py-2 text-gray-500 bg-transparent outline-none border focus:border-indigo-600 shadow-sm rounded-lg dark:text-gray-300 dark:border-gray-700 dark:focus:border-indigo-500">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Password Confirmation -->
        <div>
            <label for="password_confirmation" class="font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                class="w-full mt-2 px-3 py-2 text-gray-500 bg-transparent outline-none border focus:border-indigo-600 shadow-sm rounded-lg dark:text-gray-300 dark:border-gray-700 dark:focus:border-indigo-500">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Submit -->
        <button class="w-full px-4 py-2 text-white font-medium bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-600 rounded-lg duration-150 transition-colors shadow-lg shadow-indigo-500/30">
            Register
        </button>

        <!-- Login Link -->
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Login now</a>
        </p>
    </form>

    <script>
        document.getElementById('logo').addEventListener('change', function(e) {
            const filename = e.target.files[0] ? e.target.files[0].name : '';
            document.getElementById('logo-filename').textContent = filename;
        });
    </script>
</x-auth-layout>
