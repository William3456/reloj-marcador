<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inicio') }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (auth()->user()->id_rol === 1)
                <div x-data="empresaModal()" 
                     x-on:cerrar-modal-empresa.window="cerrar()"
                     class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <div class="bg-white overflow-hidden shadow-lg rounded-2xl border border-gray-100 hover:shadow-xl transition-shadow duration-300 relative group">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full opacity-20 blur-xl group-hover:opacity-40 transition-opacity"></div>
                        
                        <div class="p-6 relative z-10 flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-building text-3xl text-blue-600"></i>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Perfil de Empresa</h3>
                            <p class="text-gray-500 text-sm mb-6 leading-relaxed">
                                Gestiona la identidad corporativa, dirección y datos fiscales de tu organización.
                            </p>

                            <button @click="open = true; cargarEmpresa();" 
                                    class="w-full py-2.5 px-4 bg-gray-900 hover:bg-blue-700 text-white font-medium rounded-xl shadow-md hover:shadow-lg transform active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-edit text-xs"></i>
                                <span>Administrar Datos</span>
                            </button>
                        </div>
                    </div>

                    <template x-teleport="body">
                        <div x-show="open" 
                             style="display: none;"
                             class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto overflow-x-hidden px-4 py-6 sm:px-0"
                             x-transition:enter="ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            
                            <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" 
                                 @click="open = false"></div>

                            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl transform transition-all"
                                 x-transition:enter="ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                 x-transition:leave="ease-in duration-200"
                                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 rounded-t-2xl">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-blue-100 p-2 rounded-lg">
                                            <i class="fas fa-building text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">Configuración de Empresa</h3>
                                            <p class="text-xs text-gray-500">Actualiza la información legal y de contacto</p>
                                        </div>
                                    </div>
                                    <button @click="open = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-lg p-2 transition-colors">
                                        <i class="fas fa-times text-lg"></i>
                                    </button>
                                </div>

                                <div class="p-8">
                                    <form id="formEmpresa" class="space-y-6">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                            
                                            <div class="col-span-2 md:col-span-1">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre Comercial</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-signature text-gray-400"></i>
                                                    </div>
                                                    <input type="text" id="nombre" name="nombre" placeholder="Ej: Mi Empresa S.A."
                                                           class="pl-10 w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition-colors">
                                                </div>
                                            </div>

                                            <div class="col-span-2 md:col-span-1">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Teléfono</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-phone text-gray-400"></i>
                                                    </div>
                                                    <input type="text" id="telefono" name="telefono" placeholder="Ej: 2255-5555"
                                                           class="pl-10 w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition-colors">
                                                </div>
                                            </div>

                                            <div class="col-span-2">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Dirección Física</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-map-marker-alt text-gray-400"></i>
                                                    </div>
                                                    <input type="text" id="direccion" name="direccion" placeholder="Ej: Calle Principal #123, San Salvador"
                                                           class="pl-10 w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition-colors">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Registro Fiscal</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-file-invoice text-gray-400"></i>
                                                    </div>
                                                    <input type="text" id="registro_fiscal" name="registro_fiscal"
                                                           class="pl-10 w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition-colors">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">NIT</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-id-card text-gray-400"></i>
                                                    </div>
                                                    <input type="text" id="nit" name="nit"
                                                           class="pl-10 w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition-colors">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">DUI (Representante)</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-user-tag text-gray-400"></i>
                                                    </div>
                                                    <input type="text" id="dui" name="dui"
                                                           class="pl-10 w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition-colors">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Correo Electrónico</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-envelope text-gray-400"></i>
                                                    </div>
                                                    <input type="email" id="correo" name="correo" placeholder="contacto@empresa.com"
                                                           class="pl-10 w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition-colors">
                                                </div>
                                            </div>

                                        </div>

                                        <div class="flex items-center justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                                            <button type="button" @click="open = false" 
                                                    class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-blue-300 transition-colors">
                                                Cancelar
                                            </button>
                                            <button type="submit" 
                                                    class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
                                                <i class="fas fa-save"></i>
                                                Guardar Cambios
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-blue-500">
                    <div class="p-6 text-gray-900 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold">Bienvenido de nuevo</h3>
                            <p class="text-gray-500">Sesión iniciada correctamente en el sistema.</p>
                        </div>
                        <i class="fas fa-user-check text-blue-200 text-4xl"></i>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>

<script>
    // --- TU LÓGICA DE JQUERY INTACTA (Solo cambié console.logs por algo más limpio) ---
    $(document).ready(function() {
        $('#formEmpresa').submit(function(e) {
            e.preventDefault(); 
            
            // Añadir feedback visual al botón de guardar (opcional)
            let $btn = $(this).find('button[type="submit"]');
            let originalText = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('empresas.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(data) {
                    $('.error-text').remove();
                    $('input').removeClass('border-red-500 focus:border-red-500 focus:ring-red-500'); // Limpiar clases rojas
                    
                    if (data.success) {
                        alertify.success(data.message);
                        window.dispatchEvent(new CustomEvent('cerrar-modal-empresa'));
                    } else {
                        alertify.error('Error al crear la empresa');
                    }
                },
                error: function(xhr) {
                    $('.error-text').remove();
                    $('input').removeClass('border-red-500 focus:border-red-500 focus:ring-red-500');
                    
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            let input = $('[name="' + key + '"]');
                            // Agregamos borde rojo y quitamos el azul
                            input.addClass('border-red-500 focus:border-red-500 focus:ring-red-500').removeClass('border-gray-300 focus:ring-blue-500 focus:border-blue-500');
                            
                            // Insertamos el error debajo del div relativo (padre del input)
                            input.parent().after('<p class="error-text text-red-500 text-xs font-semibold mt-1 ml-1 flex items-center"><i class="fas fa-exclamation-circle mr-1"></i>' + value[0] + '</p>');
                        });
                        alertify.error('Por favor revisa los errores en el formulario.');
                    } else {
                        alertify.error('Error inesperado.');
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
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
                // Pequeño reset visual antes de cargar
                $('#formEmpresa')[0].reset();
                $('.error-text').remove();
                $('input').removeClass('border-red-500 focus:border-red-500 focus:ring-red-500');

                $.ajax({
                    url: "{{ route('empresas.show') }}",
                    type: "GET",
                    dataType: "json",
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
                        } else {
                            alertify.warning('Complete los datos de su nueva empresa.');
                        }
                    },
                    error: function(xhr) {
                        console.error("Error cargando datos");
                    }
                });
            }
        }
    }
</script>