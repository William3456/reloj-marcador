
const MODO_PRUEBAS = false; // Cambiar a true solo para desarrollo
const PRECISION_REQUERIDA = 100; // Metros aceptables

// Ubicación fija de prueba
const UBICACION_FAKE = {
    latitude: 13.69696,
    longitude: -89.24584,
    accuracy: 5
};

const btnMarcar = document.getElementById('btn-marcar');
const statusGps = document.getElementById('gps-status');
const inputLat = document.getElementById('latitud');
const inputLng = document.getElementById('longitud');
const gpsAccuracyText = document.getElementById('gps-accuracy');
const inputFoto = document.getElementById('input-foto');

// Variables de estado
let gpsValido = false;
let fotoValida = false;

function toggleModal(modalID) {
    document.getElementById(modalID).classList.toggle("hidden");
}

// --- RELOJ EN TIEMPO REAL ---
function actualizarReloj() {
    const ahora = new Date();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');
    const el = document.getElementById('reloj-tiempo-real');
    if (el) el.textContent = `${horas}:${minutos}:${segundos}`;
}
setInterval(actualizarReloj, 1000);
actualizarReloj();

// --- GEOLOCALIZACIÓN ---
const options = {
    enableHighAccuracy: true,
    timeout: 15000,
    maximumAge: 0
};

if (MODO_PRUEBAS) {
    setTimeout(aplicarUbicacionFake, 1000);
} else if ("geolocation" in navigator) {
    // watchPosition se queda escuchando cambios en la ubicación
    navigator.geolocation.watchPosition(success, error, options);
} else {
    statusGps.textContent = "GPS no soportado en este navegador";
    statusGps.className = "text-sm text-red-600 font-bold";
}

function aplicarUbicacionFake() {
    success({
        coords: {
            latitude: UBICACION_FAKE.latitude,
            longitude: UBICACION_FAKE.longitude,
            accuracy: UBICACION_FAKE.accuracy
        }
    });
}

function success(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const acc = Math.round(position.coords.accuracy);

    // 1. Mostrar Precisión al Usuario visualmente
    let colorPrecision = 'text-red-500';
    if (acc <= PRECISION_REQUERIDA) colorPrecision = 'text-green-600';
    else if (acc <= PRECISION_REQUERIDA * 2) colorPrecision = 'text-orange-500';

    gpsAccuracyText.innerHTML = `<span class="${colorPrecision} font-bold"><i class="fa-solid fa-satellite-dish"></i> Margen de error: ${acc} metros</span>`;

    // 2. Evaluar si la precisión es aceptable
    if (acc <= PRECISION_REQUERIDA) {
        // -- SEÑAL BUENA --
        gpsValido = true;

        // Llenar inputs ocultos
        inputLat.value = lat;
        inputLng.value = lng;

        // Actualizar UI
        statusGps.textContent = "Ubicación Precisa Confirmada";
        statusGps.className = "text-sm font-bold text-green-700";

        // Icono estático (ya encontró)
        const iconContainer = document.getElementById('gps-icon');
        if (iconContainer) iconContainer.classList.remove('animate-bounce');

        // Actualizar inputs del modal de bloqueo si existe
        const modalLat = document.querySelector('.lat-bloqueo');
        const modalLng = document.querySelector('.lng-bloqueo');
        if (modalLat) modalLat.value = lat;
        if (modalLng) modalLng.value = lng;

    } else {
        // -- SEÑAL MALA / INESTABLE --
        gpsValido = false;

        statusGps.innerHTML = `Mejorando señal... <span class="text-xs text-orange-600">(Acércate a una ventana)</span>`;
        statusGps.className = "text-sm font-bold text-orange-500 animate-pulse";

        // Icono animado (buscando)
        const iconContainer = document.getElementById('gps-icon');
        if (iconContainer) iconContainer.classList.add('animate-bounce');
    }

    actualizarEstadoBoton();
}

function error(err) {
    console.warn('GPS Error: ' + err.message);
    gpsValido = false;
    statusGps.textContent = "Sin señal GPS. Activa la ubicación.";
    statusGps.className = "text-sm font-bold text-red-600";
    gpsAccuracyText.textContent = "";
    actualizarEstadoBoton();
}

// --- FOTOGRAFÍA ---
function comprimirImagen(file, inputElement, previewElement, placeholderElement) {
    if (!file || !file.type.match(/image.*/)) return;

    const reader = new FileReader();
    reader.onload = function (readerEvent) {
        const image = new Image();
        image.onload = function () {
            // Configurar tamaño máximo
            const maxSize = 1280;
            let width = image.width;
            let height = image.height;

            if (width > height) {
                if (width > maxSize) {
                    height *= maxSize / width;
                    width = maxSize;
                }
            } else {
                if (height > maxSize) {
                    width *= maxSize / height;
                    height = maxSize;
                }
            }

            // Dibujar en canvas
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(image, 0, 0, width, height);

            // Mostrar Preview
            previewElement.src = canvas.toDataURL('image/jpeg');
            previewElement.classList.remove('hidden');
            placeholderElement.classList.add('hidden');

            // Convertir canvas a Blob (Comprimir al 80%) y reemplazar el archivo original en el input
            canvas.toBlob(function (blob) {
                // Creamos un nuevo archivo a partir del blob
                let fileAComprimir = new File([blob], file.name, { type: "image/jpeg", lastModified: new Date().getTime() });

                // Magia: Usamos DataTransfer para inyectarlo en el input file original
                let container = new DataTransfer();
                container.items.add(fileAComprimir);
                inputElement.files = container.files; // Sobrescribimos el de 10MB por el de 300KB

                fotoValida = true;
                actualizarEstadoBoton();
            }, 'image/jpeg', 0.8);
        };
        image.src = readerEvent.target.result;
    };
    reader.readAsDataURL(file);
}
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('preview-foto');
    const placeholder = document.getElementById('placeholder-foto');

    if (input.files && input.files[0]) {
        comprimirImagen(input.files[0], input, preview, placeholder);
    }
}

function previewImageModal(event) {
    const input = event.target;
    const preview = document.getElementById('preview-foto-modal');
    const placeholder = document.getElementById('placeholder-modal');

    if (input.files && input.files[0]) {
        comprimirImagen(input.files[0], input, preview, placeholder);
    }
}

// --- VALIDACIÓN FINAL ---
function actualizarEstadoBoton() {
    if (!btnMarcar) return;

    if (enviandoFormulario) return;
    // El botón se habilita SOLO si hay GPS preciso Y Foto tomada
    if (gpsValido && fotoValida) {
        btnMarcar.disabled = false;
        btnMarcar.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
        // Restaurar gradiente original si se quiere, o dejar clases CSS base
    } else {
        btnMarcar.disabled = true;
        btnMarcar.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

let mapHistorial;
let markerHistorial;

function abrirDetalleHistorial(elemento) {
    const tipo = elemento.getAttribute('data-tipo');
    const hora = elemento.getAttribute('data-hora');
    const fecha = elemento.getAttribute('data-fecha');
    const sucursal = elemento.getAttribute('data-sucursal');
    const fotoUrl = elemento.getAttribute('data-foto');
    const lat = parseFloat(elemento.getAttribute('data-lat'));
    const lng = parseFloat(elemento.getAttribute('data-lng'));

    // Recibimos el HTML generado desde Blade
    const badgesHtml = elemento.getAttribute('data-badges');

    // Llenar datos
    document.getElementById('md-titulo').innerText = tipo;
    document.getElementById('md-fecha').innerText = fecha + ' • ' + hora;
    document.getElementById('md-img').src = fotoUrl;
    document.getElementById('md-sucursal').innerText = sucursal;

    // Inyectar los badges
    document.getElementById('md-badges-container').innerHTML = badgesHtml;

    // Mostrar Modal
    const modal = document.getElementById('modal-detalle-historial');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Iniciar Mapa
    initMapHistorial(lat, lng);
}

function cerrarDetalleHistorial() {
    document.getElementById('modal-detalle-historial').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function initMapHistorial(lat, lng) {
    const position = { lat: lat, lng: lng };
    if (!mapHistorial) {
        setTimeout(() => {
            // Verificamos si Google Maps está cargado (ya que lo usas en la vista principal)
            if (typeof google !== 'undefined') {
                mapHistorial = new google.maps.Map(document.getElementById("md-mapa"), {
                    center: position,
                    zoom: 16,
                    disableDefaultUI: true,
                    zoomControl: true,
                });
                markerHistorial = new google.maps.Marker({
                    position: position,
                    map: mapHistorial,
                });
            }
        }, 100);
    } else {
        setTimeout(() => {
            mapHistorial.setCenter(position);
            markerHistorial.setPosition(position);
            google.maps.event.trigger(mapHistorial, 'resize');
        }, 100);
    }
}

// --- LÓGICA DE LOADER Y BLOQUEO DE DOBLE CLICK ---
    let enviandoFormulario = false;

    function activarLoader(btnId) {
        const btn = document.getElementById(btnId);
        if (!btn) return;

        // 1. Bandera para evitar que la validación GPS lo reactive o haya doble envío
        enviandoFormulario = true;

        // 2. Bloquear botón y evitar que se reduzca su tamaño
        btn.disabled = true;
        btn.classList.add('cursor-wait');
        
        // 3. Diseño Flexbox Asegurado + Animación CSS Pura (Cero SVGs)
        btn.innerHTML = `
            <div class="flex items-center justify-center gap-3 w-full h-full">
                
                <div class="w-6 h-6 border-4 border-white border-b-transparent rounded-full animate-spin"></div>
                
                <div class="flex items-end gap-1">
                    <span class="font-black tracking-widest text-white uppercase text-sm">
                        Registrando
                    </span>
                    <div class="flex space-x-1 mb-1.5 ml-1">
                        <div class="w-1.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: -0.3s"></div>
                        <div class="w-1.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: -0.15s"></div>
                        <div class="w-1.5 h-1.5 bg-white rounded-full animate-bounce"></div>
                    </div>
                </div>

            </div>
        `;
    }

// Listener para el formulario PRINCIPAL
const formPrincipal = document.getElementById('form-marcacion');
if (formPrincipal) {
    formPrincipal.addEventListener('submit', function (e) {
        if (enviandoFormulario) {
            e.preventDefault(); // Prevenir doble envío si ya está procesando
            return;
        }
        activarLoader('btn-marcar');
    });
}

// Listener para el formulario del MODAL DE BLOQUEO (si existe)
const btnModal = document.getElementById('btn-marcar-modal');
if (btnModal) {
    // Buscamos el formulario padre del botón del modal
    const formModal = btnModal.closest('form');
    if (formModal) {
        formModal.addEventListener('submit', function (e) {
            if (enviandoFormulario) {
                e.preventDefault();
                return;
            }
            activarLoader('btn-marcar-modal');
        });
    }
}
