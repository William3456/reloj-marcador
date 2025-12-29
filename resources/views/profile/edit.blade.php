<x-app-layout title="Perfil de Usuario">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mi Perfil') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg h-fit">
                    <div class="w-full">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg h-fit">
                    <div class="w-full">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

            {{-- <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg border-l-4 border-red-500">
                <div class="w-full">
                    @include('profile.partials.delete-user-form')
                </div>
            </div> --}}

        </div>
    </div>
</x-app-layout>