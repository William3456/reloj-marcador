<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    @if (session('error'))
        <div class="mb-4 font-medium text-sm text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ session('error') }}
        </div>
    @endif
    <form method="POST" action="{{ route('login') }}" id="form-login">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus autocomplete="username" />

        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />


        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
    @push('scripts')
        <script>
            // Si el navegador saca esta página de su caché de historial (Bfcache), forzamos una recarga limpia
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
            document.addEventListener('DOMContentLoaded', function () {

                // 1. Pedimos un token 100% nuevo al servidor (agregamos el tiempo para burlar cualquier caché)
                fetch('/refresh-csrf?t=' + new Date().getTime())
                    .then(response => response.json())
                    .then(data => {
                        // 2. Actualizamos la etiqueta meta de la cabecera
                        const metaToken = document.querySelector('meta[name="csrf-token"]');
                        if (metaToken) metaToken.setAttribute('content', data.token);

                        // 3. Actualizamos el campo oculto de nuestro formulario
                        const inputToken = document.querySelector('input[name="_token"]');
                        if (inputToken) inputToken.value = data.token;
                    })
                    .catch(error => console.error('Error refrescando token:', error));

                // Protección contra autocompletado (Lo que ya teníamos)
                const formLogin = document.getElementById('form-login');
                if (formLogin) {
                    formLogin.addEventListener('submit', function () {
                        const tokenFresco = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const inputForm = formLogin.querySelector('input[name="_token"]');
                        if (inputForm && tokenFresco) {
                            inputForm.value = tokenFresco;
                        }
                    });
                }
            });
        </script>
    @endpush
</x-guest-layout>