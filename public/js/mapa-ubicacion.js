document.addEventListener("DOMContentLoaded", function () {
    const elSalvadorBounds = {
        north: 14.45,
        south: 12.98,
        west: -90.20,
        east: -87.65
    };

    const map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 13.6929, lng: -89.2182 },
        zoom: 13,
    });
    map.setOptions({
        restriction: {
            latLngBounds: elSalvadorBounds,
            strictBounds: true
        }
    });

    let marker;

    function setMarker(lat, lng) {
        if (marker) {
            marker.setPosition({ lat, lng });
        } else {
            marker = new google.maps.Marker({
                position: { lat, lng },
                map: map,
                draggable: true,
                title: "Ubicación seleccionada"
            });

            marker.addListener('dragend', function () {
                const pos = marker.getPosition();
                document.getElementById('latitud').value = pos.lat();
                document.getElementById('longitud').value = pos.lng();
            });
        }

        document.getElementById('latitud').value = lat;
        document.getElementById('longitud').value = lng;
        map.setCenter({ lat, lng });
        setAddressFromLatLng(lat, lng);
    }

    function setAddressFromLatLng(lat, lng) {
        const geocoder = new google.maps.Geocoder();

        geocoder.geocode({ location: { lat, lng } }, function (results, status) {
            if (status === "OK" && results[0]) {

                const place = results[0];
                const components = place.address_components || [];

                const get = (type) => {
                    const comp = components.find(c => c.types.includes(type));
                    return comp ? comp.long_name : "";
                };

                // NUEVO: buscar nombre de lugar real si existe
                const nombreLugar =
                    get("establishment") ||     // Universidades, hospitales, centros comerciales
                    get("point_of_interest") || // Restaurantes, locales, tiendas
                    get("premise") ||           // Edificios o conjuntos
                    place.name || "";           // fallback

                const calle = get("route");
                const numero = get("street_number");
                const colonia = get("sublocality") || get("neighborhood") || "";
                const municipio = get("locality") || get("administrative_area_level_2");
                const departamento = get("administrative_area_level_1");
                const pais = get("country");

                // Construcción de la dirección en una sola línea, como pediste
                let texto = [
                    nombreLugar,
                    [calle, numero].filter(Boolean).join(" "),
                    colonia,
                    municipio,
                    departamento,
                    pais
                ].filter(Boolean).join(", ");

                //document.getElementById("direccion").value = texto; //Habilitar si es necesario, obtiene la dirección completa en texto para mostrarlo en textarea
            }
        });
    }

    // Botón “Mi ubicación”
    document.getElementById("btnMiUbicacion").addEventListener("click", function () {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                setMarker(position.coords.latitude, position.coords.longitude);
            }, function () {
                alert("No se pudo obtener tu ubicación.");
            });
        } else {
            alert("Tu navegador no soporta geolocalización.");
        }
    });

    // Obtener ubicación automáticamente
    if (document.getElementById('esEditar').value === "0") {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                setMarker(position.coords.latitude, position.coords.longitude);
            });
        }
    } else {
        let lat = parseFloat(document.getElementById('latitud').value);
        let lng = parseFloat(document.getElementById('longitud').value);

        // Validar que SI existen coords válidas en edición
        if (!isNaN(lat) && !isNaN(lng)) {
            setMarker(lat, lng);
        } else {
            console.warn("Latitud o longitud inválidas en edición, usando posición por defecto.");
        }
    }

    // Clic en el mapa → coloca marcador
    map.addListener("click", function (e) {
        setMarker(e.latLng.lat(), e.latLng.lng());
    });

    // === Buscador  ===

    // Define el punto de corte (breakpoint) para considerar 'móvil'
    const MOBILE_BREAKPOINT = 767; // Ancho máximo en píxeles para aplicar estilos móviles
    const TOP_PC = '20px';
    const TOP_MOBILE = '60px';

    // --- 1. Contenedor Principal ---
    const inputContainer = document.createElement("div");
    inputContainer.id = "map-search-container"; // ID para referencia en la función
    inputContainer.style.position = "absolute";
    inputContainer.style.left = "50%";
    inputContainer.style.transform = "translateX(-50%)";
    inputContainer.style.zIndex = "10000";
    inputContainer.style.display = "flex";
    inputContainer.style.justifyContent = "center";
    inputContainer.style.alignItems = "center";
    inputContainer.style.height = "40px";

    // Estilos de ancho compactos y responsive (PC y móvil)
    inputContainer.style.width = "80%";
    inputContainer.style.maxWidth = "320px";
    inputContainer.style.minWidth = "200px";

    // Clase de Tailwind CSS (Asegúrate de tener Tailwind configurado)
    inputContainer.className = "bg-white rounded-full shadow-lg pointer-events-auto relative";

    // --- 2. Función de Posicionamiento Dinámico (TOP) ---
    function setResponsiveTopPosition() {
        // Si el ancho de la ventana es menor o igual al punto de corte (móvil)
        if (window.innerWidth <= MOBILE_BREAKPOINT) {
            inputContainer.style.top = TOP_MOBILE; // 60px en móvil
        } else {
            // Pantalla grande (PC)
            inputContainer.style.top = TOP_PC; // 20px en PC
        }
    }


    // --- 3. Icono de Búsqueda (Absoluto) ---
    const icon = document.createElement("span");
    icon.style.position = "absolute";
    icon.style.left = "12px";
    icon.style.top = "50%";
    icon.style.transform = "translateY(-50%)";
    icon.style.display = "flex";
    icon.style.alignItems = "center";
    icon.style.pointerEvents = "none"; // Permite el clic a través del icono
    icon.innerHTML = `
 <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
  viewBox="0 0 24 24" stroke="currentColor">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
   d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z"/>
 </svg>`;
    inputContainer.appendChild(icon);


    // --- 4. Campo de Input ---
    const input = document.createElement("input");
    input.type = "text";
    input.placeholder = "Buscar ubicación...";
    input.style.width = "100%";
    input.style.height = "100%";
    input.style.padding = "0 12px 0 40px"; // Deja espacio para el icono
    input.style.borderRadius = "999px";
    input.style.border = "none";
    input.style.outline = "none";
    input.style.fontSize = "16px";
    input.className = "pointer-events-auto bg-transparent";
    inputContainer.appendChild(input);


    // --- 5. Implementación y Listeners ---

    // Adjuntarlo dentro del DIV del mapa (esencial para el modo Fullscreen)
    // **ATENCIÓN: Asegúrate de que la variable `map` esté definida antes de ejecutar esto.**
    if (typeof map !== 'undefined' && map.getDiv) {
        map.getDiv().appendChild(inputContainer);
    } else {
        console.error("Error: La variable 'map' de Google Maps no está definida o accesible.");
    }

    // Configuración del Autocomplete (requiere la librería 'places' de Google Maps)
    const autocomplete = new google.maps.places.Autocomplete(input, {
        fields: ["geometry", "formatted_address", "address_components", "name"],
        componentRestrictions: { country: "sv" }
    });
    autocomplete.bindTo("bounds", map);

    autocomplete.addListener("place_changed", function () {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        // Asume que 'setMarker' es una función definida en tu código para manejar el marcador.
        if (typeof setMarker === 'function') {
            setMarker(place.geometry.location.lat(), place.geometry.location.lng());
        }
    });


    // Ejecutar la comprobación inmediatamente al cargar
    setResponsiveTopPosition();

    // Ejecutar la comprobación cada vez que la ventana cambie de tamaño
    window.addEventListener('resize', setResponsiveTopPosition);

    // ------------------------
    // BOTÓN MI UBICACIÓN
    // ------------------------
    const btnMiUbicacion = document.createElement("button");
    btnMiUbicacion.innerHTML = `
<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
<path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-5.686 6-10a6 6 0 10-12 0c0 4.314 6 10 6 10z" />
<circle cx="12" cy="11" r="2" />
</svg>`;

    // === Estilos: gris sólido, moderno y visible ===
    btnMiUbicacion.className = `
bg-gray-600 hover:bg-gray-700 text-white
p-3 rounded-full shadow-lg pointer-events-auto
transition-all duration-200 border border-gray-700
`;
    btnMiUbicacion.style.backgroundColor = '#6b7280'; // gris sólido (Tailwind gray-500)
    btnMiUbicacion.style.opacity = '1';
    btnMiUbicacion.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';

    btnMiUbicacion.type = "button";

    // === Ubicación del botón en el mapa ===
    map.controls[google.maps.ControlPosition.LEFT_BOTTOM].push(btnMiUbicacion);

    // === Evento de clic ===
    btnMiUbicacion.addEventListener("click", () => {
        if (navigator.geolocation) {
            // Animación de carga
            btnMiUbicacion.innerHTML = `
        <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>`;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const { latitude, longitude } = pos.coords;
                    setMarker(latitude, longitude);
                    btnMiUbicacion.innerHTML = `
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-5.686 6-10a6 6 0 10-12 0c0 4.314 6 10 6 10z" />
                <circle cx="12" cy="11" r="2" />
                </svg>`;
                },
                () => {
                    alert("No se pudo obtener la ubicación actual.");
                    btnMiUbicacion.innerHTML = `
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-5.686 6-10a6 6 0 10-12 0c0 4.314 6 10 6 10z" />
                <circle cx="12" cy="11" r="2" />
                </svg>`;
                }
            );
        } else {
            alert("Tu navegador no soporta geolocalización.");
        }
    });

});
