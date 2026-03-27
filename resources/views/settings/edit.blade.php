<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('settings.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('settings.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Agency Settings') }}
                            </h2>
                    
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __("Upload your agency logo to be used in PDF reports.") }}
                            </p>
                        </header>
                    
                        <form method="post" action="{{ route('agency.logo.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
                            @csrf
                    
                            <div>
                                <x-input-label for="agency_logo" :value="__('Agency Logo')" />
                                <input id="agency_logo" name="agency_logo" type="file" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" accept="image/*" />
                                <x-input-error class="mt-2" :messages="$errors->get('agency_logo')" />
                            </div>
                    
                            @if (Auth::user()->agency_logo_path)
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current Logo:</p>
                                    <img src="{{ asset('storage/' . Auth::user()->agency_logo_path) }}" alt="Agency Logo" class="h-16 object-contain bg-gray-100 p-2 rounded">
                                </div>
                            @endif
                    
                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                    
                                @if (session('status') === 'agency-logo-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >{{ __('Saved.') }}</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
