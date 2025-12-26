<x-app-layout title="Editando departamento {{ $depto->cod_depto }}">
    <x-slot name="header">
        <x-encabezado :crearEdit="'Editando departamento - '.$depto->cod_depto" />
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-gray-100 shadow rounded-lg p-6">
                
                @if (session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-300 text-green-800 shadow-sm">
                        <div class="flex items-center text-sm">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                @elseif (session('error'))
                    <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-300 text-red-800 shadow-sm">
                        <div class="flex items-center text-sm">
                            <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif
                <form action="{{ route('departamentos.update', $depto->id) }}" method="POST">
                    @csrf

                    @method('PUT')
                    @include('departamentos._form', [
                        'departamento' => $depto,
                        'sucursales' => $sucursales,
                    ])

                    <div class="mt-6 flex justify-end">
                        <x-primary-button class="inline-block text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-12 py-2.5 text-center">
                            <i class="fas fa-save mr-2"></i>Guardar
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>