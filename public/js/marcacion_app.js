const MODO_PRUEBAS = false; // Cambiar a true solo para desarrollo
const PRECISION_REQUERIDA = 100; // Metros aceptables

// Ubicación fija de prueba
const UBICACION_FAKE = {
    latitude: 13.76696,
    longitude: -89.24584,
    accuracy: 5
};

const btnMarcar = document.getElementById('btn-marcar');
const statusGps = document.getElementById('gps-status');
const inputLat = document.getElementById('latitud');
const inputLng = document.getElementById('longitud');
const gpsAccuracyText = document.getElementById('gps-accuracy');
const inputFoto = document.getElementById('input-foto');

const btnMarcarModal = document.getElementById('btn-marcar-modal');
const statusGpsModal = document.getElementById('gps-status-modal');
const gpsAccuracyTextModal = document.getElementById('gps-accuracy-modal');

//  NUEVO: Leer si el día de hoy es Home Office
const inputEsRemoto = document.getElementById('es_hoy_remoto');
const esHoyRemoto = inputEsRemoto && inputEsRemoto.value === '1';

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
    navigator.geolocation.watchPosition(success, error, options);
} else {
    statusGps.textContent = "GPS no soportado en este navegador";
    statusGps.className = "text-sm text-red-600 font-bold";
    // Si es remoto, lo perdonamos aunque no haya soporte
    if (esHoyRemoto) aplicarEstiloRemoto(true);
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

// 🌟 FUNCIÓN AUXILIAR PARA PINTAR DE MORADO
function aplicarEstiloRemoto(sinGps = false) {
    gpsValido = true; // Forzamos la validación a true
    
    const claseContenedor = 'flex items-center justify-between p-3 rounded-lg border bg-purple-50 border-purple-200 transition-colors mb-4';
    const textoGps = '<i class="fa-solid fa-house-laptop mr-1"></i> Trabajo Remoto Habilitado';
    const claseTextoGps = 'text-sm font-bold text-purple-700';
    const textoAcc = sinGps ? '<span class="text-purple-500 font-bold uppercase text-[10px]">Sin GPS (Permitido)</span>' : '<span class="text-purple-500 font-bold uppercase text-[10px]">Rango Liberado</span>';

    // Para el formulario principal
    if (statusGps) {
        const contenedor = statusGps.closest('.flex.items-center.justify-between');
        if (contenedor) contenedor.className = claseContenedor;
        statusGps.innerHTML = textoGps;
        statusGps.className = claseTextoGps;
    }
    if (gpsAccuracyText) gpsAccuracyText.innerHTML = textoAcc;

    // Para el modal de olvido de salida
    if (statusGpsModal) {
        const contenedorM = statusGpsModal.closest('.flex.items-center.justify-between');
        if (contenedorM) contenedorM.className = claseContenedor;
        statusGpsModal.innerHTML = textoGps;
        statusGpsModal.className = claseTextoGps;
    }
    if (gpsAccuracyTextModal) gpsAccuracyTextModal.innerHTML = textoAcc;

    const iconContainer = document.getElementById('gps-icon');
    if (iconContainer) iconContainer.classList.remove('animate-bounce');

    actualizarEstadoBoton();
}

function success(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const acc = Math.round(position.coords.accuracy);

    // Llenamos los inputs ocultos siempre que haya coordenadas
    if(inputLat) inputLat.value = lat;
    if(inputLng) inputLng.value = lng;
    const modalLat = document.querySelector('.lat-bloqueo');
    const modalLng = document.querySelector('.lng-bloqueo');
    if (modalLat) modalLat.value = lat;
    if (modalLng) modalLng.value = lng;

    // 🌟 INTERVENCIÓN DE HOME OFFICE
    if (esHoyRemoto) {
        aplicarEstiloRemoto(false);
        return; // Detenemos la validación estricta de metros
    }

    // 1. Mostrar Precisión al Usuario visualmente (Normal)
    let colorPrecision = 'text-red-500';
    if (acc <= PRECISION_REQUERIDA) colorPrecision = 'text-green-600';
    else if (acc <= PRECISION_REQUERIDA * 2) colorPrecision = 'text-orange-500';

    if(gpsAccuracyText) gpsAccuracyText.innerHTML = `<span class="${colorPrecision} font-bold"><i class="fa-solid fa-satellite-dish"></i> Margen de error: ${acc} metros</span>`;
    if(gpsAccuracyTextModal) gpsAccuracyTextModal.innerHTML = `<span class="${colorPrecision} font-bold"><i class="fa-solid fa-satellite-dish"></i> Margen de error: ${acc} metros</span>`;

    // 2. Evaluar si la precisión es aceptable
    if (acc <= PRECISION_REQUERIDA) {
        gpsValido = true;

        if(statusGps) {
            statusGps.textContent = "Ubicación Precisa Confirmada";
            statusGps.className = "text-sm font-bold text-green-700";
        }
        if(statusGpsModal) {
            statusGpsModal.textContent = "Ubicación Precisa Confirmada";
            statusGpsModal.className = "text-sm font-bold text-green-700";
        }
        const iconContainer = document.getElementById('gps-icon');
        if (iconContainer) iconContainer.classList.remove('animate-bounce');

    } else {
        gpsValido = false;

        if(statusGps) {
            statusGps.innerHTML = `Mejorando señal... <span class="text-xs text-orange-600">(Acércate a una ventana)</span>`;
            statusGps.className = "text-sm font-bold text-orange-500 animate-pulse";
        }
        if(statusGpsModal) {
            statusGpsModal.innerHTML = `Mejorando señal... <span class="text-xs text-orange-600">(Acércate a una ventana)</span>`;
            statusGpsModal.className = "text-sm font-bold text-orange-500 animate-pulse";
        }
        const iconContainer = document.getElementById('gps-icon');
        if (iconContainer) iconContainer.classList.add('animate-bounce');
    }

    actualizarEstadoBoton();
}

function error(err) {
    console.warn('GPS Error: ' + err.message);
    
    // 🌟 Si es Home Office, lo dejamos marcar aunque rechace el GPS o falle
    if (esHoyRemoto) {
        aplicarEstiloRemoto(true);
        return;
    }

    gpsValido = false;
    if(statusGps) {
        statusGps.textContent = "Sin señal GPS. Activa la ubicación.";
        statusGps.className = "text-sm font-bold text-red-600";
        gpsAccuracyText.textContent = "";
    }
    if(statusGpsModal) {
        statusGpsModal.textContent = "Sin señal GPS. Activa la ubicación.";
        statusGpsModal.className = "text-sm font-bold text-red-600";
        gpsAccuracyTextModal.textContent = "";
    }
    actualizarEstadoBoton();
}

function actualizarEstadoBoton() {
    if (enviandoFormulario) return;
    
    // 1. Botón Principal
    if (btnMarcar) {
        if (gpsValido && fotoValida) {
            btnMarcar.disabled = false;
            btnMarcar.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
        } else {
            btnMarcar.disabled = true;
            btnMarcar.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // 2. Botón del Modal de Olvido
    if (btnMarcarModal) {
        if (gpsValido && fotoValida) {
            btnMarcarModal.disabled = false;
            btnMarcarModal.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
        } else {
            btnMarcarModal.disabled = true;
            btnMarcarModal.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
}

// --- FOTOGRAFÍA ---
function comprimirImagen(file, inputElement, previewElement, placeholderElement) {
    if (!file || !file.type.match(/image.*/)) return;

    const reader = new FileReader();
    reader.onload = function (readerEvent) {
        const image = new Image();
        image.onload = function () {
            const maxSize = 1280;
            let width = image.width;
            let height = image.height;

            if (width > height) {
                if (width > maxSize) { height *= maxSize / width; width = maxSize; }
            } else {
                if (height > maxSize) { width *= maxSize / height; height = maxSize; }
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(image, 0, 0, width, height);

            previewElement.src = canvas.toDataURL('image/jpeg');
            previewElement.classList.remove('hidden');
            placeholderElement.classList.add('hidden');

            canvas.toBlob(function (blob) {
                let fileAComprimir = new File([blob], file.name, { type: "image/jpeg", lastModified: new Date().getTime() });
                let container = new DataTransfer();
                container.items.add(fileAComprimir);
                inputElement.files = container.files; 
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
    if (input.files && input.files[0]) comprimirImagen(input.files[0], input, preview, placeholder);
}

function previewImageModal(event) {
    const input = event.target;
    const preview = document.getElementById('preview-foto-modal');
    const placeholder = document.getElementById('placeholder-modal');
    if (input.files && input.files[0]) comprimirImagen(input.files[0], input, preview, placeholder);
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
    const badgesHtml = elemento.getAttribute('data-badges');
    
    // NUEVO: Recibimos las horas
    const horasPermiso = elemento.getAttribute('data-horas-permiso');

    document.getElementById('md-titulo').innerText = tipo;
    document.getElementById('md-fecha').innerText = fecha + ' • ' + hora;
    document.getElementById('md-img').src = fotoUrl;
    document.getElementById('md-sucursal').innerText = sucursal;
    document.getElementById('md-badges-container').innerHTML = badgesHtml;

    // 🌟 NUEVO: Mostrar u ocultar la caja de horas
    const boxPermiso = document.getElementById('md-permiso-box');
    const txtHoras = document.getElementById('md-permiso-horas');

    if (boxPermiso && txtHoras) {
        if (horasPermiso && horasPermiso.trim() !== "" && horasPermiso !== "null") {
            txtHoras.innerText = horasPermiso;
            boxPermiso.classList.remove('hidden');
            boxPermiso.classList.add('inline-flex'); // Aseguramos que se muestre compacto
        } else {
            boxPermiso.classList.add('hidden');
            boxPermiso.classList.remove('inline-flex');
        }
    }

    const modal = document.getElementById('modal-detalle-historial');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

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
            if (typeof google !== 'undefined') {
                mapHistorial = new google.maps.Map(document.getElementById("md-mapa"), {
                    center: position, zoom: 16, disableDefaultUI: true, zoomControl: true,
                });
                markerHistorial = new google.maps.Marker({ position: position, map: mapHistorial, });
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

    enviandoFormulario = true;
    btn.disabled = true;
    btn.classList.add('cursor-wait');
    
    btn.innerHTML = `
        <div class="flex items-center justify-center gap-3 w-full h-full">
            <div class="w-6 h-6 border-4 border-white border-b-transparent rounded-full animate-spin"></div>
            <div class="flex items-end gap-1">
                <span class="font-black tracking-widest text-white uppercase text-sm">Registrando</span>
                <div class="flex space-x-1 mb-1.5 ml-1">
                    <div class="w-1.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: -0.3s"></div>
                    <div class="w-1.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: -0.15s"></div>
                    <div class="w-1.5 h-1.5 bg-white rounded-full animate-bounce"></div>
                </div>
            </div>
        </div>
    `;
}

const formPrincipal = document.getElementById('form-marcacion');
if (formPrincipal) {
    formPrincipal.addEventListener('submit', function (e) {
        if (enviandoFormulario) { e.preventDefault(); return; }
        activarLoader('btn-marcar');
    });
}

const btnModal = document.getElementById('btn-marcar-modal');
if (btnModal) {
    const formModal = btnModal.closest('form');
    if (formModal) {
        formModal.addEventListener('submit', function (e) {
            if (enviandoFormulario) { e.preventDefault(); return; }
            activarLoader('btn-marcar-modal');
        });
    }
}