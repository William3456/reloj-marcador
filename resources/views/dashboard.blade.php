<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inicio') }}
        </h2>
    </x-slot>

    <div class="py-5">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (auth()->user()->id_rol === 1)
                        <div x-data="empresaModal()" x-ref="empresaModal" x-on:cerrar-modal-empresa.window="open = false"
                            class="max-w-md  bg-white border border-gray-200 rounded-xl shadow-md p-6 text-center">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Administrar Empresa</h3>
                            <p class="text-gray-600 mb-4">
                                Visualiza o edita la información general de tu empresa.
                            </p>
                            <button @click="open = true; cargarEmpresa();"
                                class="inline-block text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                Editar datos
                            </button>

                            <!-- Modal profesional -->
                            <div x-show="open"
                                class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center pt-16"
                                x-transition.opacity>
                                <div @click.away="open = false" x-transition:enter="transform transition duration-300"
                                    x-transition:enter-start="opacity-0 scale-95 -translate-y-10"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transform transition duration-200"
                                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 scale-95 -translate-y-10"
                                    class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-8 overflow-y-auto max-h-[90vh]">

                                    <h3 class="text-2xl font-semibold mb-6 text-gray-800">Editar Empresa</h3>

                                    <form id="formEmpresa" class="space-y-6">
                                        @csrf


                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-gray-700 mb-1" for="nombre">Nombre</label>
                                                <input type="text" id="nombre" name="nombre"
                                                    value="{{ $empresa->nombre ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block text-gray-700 mb-1"
                                                    for="direccion">Dirección</label>
                                                <input type="text" id="direccion" name="direccion"
                                                    value="{{ $empresa->direccion ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block text-gray-700 mb-1" for="telefono">Teléfono</label>
                                                <input type="text" id="telefono" name="telefono"
                                                    value="{{ $empresa->telefono ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block text-gray-700 mb-1" for="registro_fiscal">Registro
                                                    Fiscal</label>
                                                <input type="text" id="registro_fiscal" name="registro_fiscal"
                                                    value="{{ $empresa->registro_fiscal ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block text-gray-700 mb-1" for="nit">NIT</label>
                                                <input type="text" id="nit" name="nit"
                                                    value="{{ $empresa->nit ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block text-gray-700 mb-1" for="dui">DUI</label>
                                                <input type="text" id="dui" name="dui"
                                                    value="{{ $empresa->dui ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="block text-gray-700 mb-1" for="correo">Correo</label>
                                                <input type="email" id="correo" name="correo"
                                                    value="{{ $empresa->correo ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block text-gray-700 mb-1" for="latitud">Latitud</label>
                                                <input type="number" step="0.0000001" id="latitud" name="latitud"
                                                    value="{{ $empresa->latitud ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block text-gray-700 mb-1" for="longitud">Longitud</label>
                                                <input type="number" step="0.0000001" id="longitud" name="longitud"
                                                    value="{{ $empresa->longitud ?? '' }}"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>

                                        <div class="flex justify-end mt-6 space-x-3">
                                            <button type="button" @click="open = false"
                                                class="px-5 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">Cancelar</button>
                                            <button type="submit"
                                                class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Guardar
                                                Cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- Fin Modal -->

                        </div>
                    @else
                        <div
                            class="max-w-md mx-auto bg-white border border-gray-200 rounded-xl shadow-md p-6 text-center">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Bienvenido</h3>
                            <p class="text-gray-600">Has iniciado sesión correctamente.</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>
<script>
    $(document).ready(function() {
        $('#formEmpresa').submit(function(e) {
            e.preventDefault(); // Evita que se recargue la página

            // Serializamos los datos del formulario
            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('empresas.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    // Limpiar errores anteriores
                    $('.error-text').remove();
                    $('input').removeClass('border-red-500');

                    if (data.success) {
                        alertify.success(data.message);
                       window.dispatchEvent(new CustomEvent('cerrar-modal-empresa'));

                    } else {
                        alertify.error('Error al crear la empresa');
                        console.error(data);
                    }
                },
                error: function(xhr) {
                    // Limpiar errores anteriores
                    $('.error-text').remove();
                    $('input').removeClass('border-red-500');

                    if (xhr.status === 422) { // Validación
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            let input = $('[name="' + key + '"]');
                            input.addClass('border-red-500');
                            input.after(
                                '<p class="error-text text-red-500 text-sm mt-1">' +
                                value[0] + '</p>');
                        });
                    } else {
                        alertify.error('Error inesperado en la petición.');
                        console.error(xhr.responseJSON || xhr);
                    }
                }
            });

        });
    });

    function empresaModal() {
        return {
            open: false,
            cerrar() {
                this.open = false;
            },
            cargarEmpresa() {
                $.ajax({
                    url: "{{ route('empresas.show') }}",
                    type: "GET",
                    dataType: "json",
                    beforeSend: function() {
                        console.log("Cargando datos de la empresa...");
                    },
                    success: function(response) {
                        if (response.success && response.empresa) {
                            let e = response.empresa;

                            $('#nombre').val(e.nombre ?? '');
                            $('#direccion').val(e.direccion ?? '');
                            $('#telefono').val(e.telefono ?? '');
                            $('#registro_fiscal').val(e.registro_fiscal ?? '');
                            $('#nit').val(e.nit ?? '');
                            $('#dui').val(e.dui ?? '');
                            $('#correo').val(e.correo ?? '');
                            $('#latitud').val(e.latitud ?? '');
                            $('#longitud').val(e.longitud ?? '');

                            //console.log("Empresa cargada correctamente.");
                        } else {
                            $('#formEmpresa')[0].reset();
                            alertify.warning('No se encontró empresa registrada, creando nueva...');
                            //console.warn("Respuesta inesperada:", response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error AJAX:", xhr.responseText);

                        if (xhr.status === 404) {
                            console.log("No existe una empresa registrada aún.");
                        } else if (xhr.status === 500) {
                            console.log("Error interno");
                        } else {
                            console.log('Error inesperado al cargar los datos.');
                        }
                    }
                });
            }
        }
    }
</script>
