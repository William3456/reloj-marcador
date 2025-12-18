<x-app-layout title="Editar horario">
    <x-slot name="header">
        <x-encabezado :crearEdit="'Editar horario'" />
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-gray-100 shadow rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 p-3 rounded-md bg-green-100 border border-green-300 text-green-800">
                        {{ session('success') }}
                    </div>
                @elseif (session('error'))
                    <div class="mb-4 p-3 rounded-md bg-red-100 border border-red-300 text-red-800">
                        {{ session('error') }}
                    </div>
                @endif
                <form action="{{ route('horarios.update', $horario->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('horarios._form', ['horario' => $horario])

                    <div class="mt-6 flex justify-end">
                        <x-primary-button class="inline-block text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-12 py-2.5 text-center">
                            Guardar
                        </x-primary-button>
                    </div>
               
                </form>
            </div>

        </div>
    </div>
</x-app-layout>