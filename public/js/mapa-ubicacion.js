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
                title: "Ubicación"
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

                // Identificación de lugar o establecimiento
                const nombreLugar =
                    get("establishment") || 
                    get("point_of_interest") || 
                    get("premise") || 
                    place.name || "";

                const calle = get("route");
                const numero = get("street_number");
                const colonia = get("sublocality") || get("neighborhood") || "";
                const municipio = get("locality") || get("administrative_area_level_2");
                const departamento = get("administrative_area_level_1");
                const pais = get("country");

                let texto = [
                    nombreLugar,
                    [calle, numero].filter(Boolean).join(" "),
                    colonia,
                    municipio,
                    departamento,
                    pais
                ].filter(Boolean).join(", ");

                // document.getElementById("direccion").value = texto;
            }
        });
    }

    // Botón de ubicación actual
    document.getElementById("btnMiUbicacion").addEventListener("click", function () {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                setMarker(position.coords.latitude, position.coords.longitude);
            }, function () {
                alert("No se pudo obtener la ubicación.");
            });
        } else {
            alert("El navegador no soporta geolocalización.");
        }
    });

    // Lógica de inicio (Edición vs Creación)
    if (document.getElementById('esEditar').value === "0") {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                setMarker(position.coords.latitude, position.coords.longitude);
            });
        }
    } else {
        let lat = parseFloat(document.getElementById('latitud').value);
        let lng = parseFloat(document.getElementById('longitud').value);

        if (!isNaN(lat) && !isNaN(lng)) {
            setMarker(lat, lng);
        } else {
            console.warn("Coordenadas inválidas en modo edición.");
        }
    }

    // Evento de clic en mapa para posicionar marcador
    map.addListener("click", function (e) {
        setMarker(e.latLng.lat(), e.latLng.lng());
    });

    // Configuración del buscador dinámico
    const MOBILE_BREAKPOINT = 767;
    const TOP_PC = '20px';
    const TOP_MOBILE = '60px';

    const inputContainer = document.createElement("div");
    inputContainer.id = "map-search-container";
    inputContainer.style.position = "absolute";
    inputContainer.style.left = "50%";
    inputContainer.style.transform = "translateX(-50%)";
    inputContainer.style.zIndex = "10000";
    inputContainer.style.display = "flex";
    inputContainer.style.justifyContent = "center";
    inputContainer.style.alignItems = "center";
    inputContainer.style.height = "40px";
    inputContainer.style.width = "80%";
    inputContainer.style.maxWidth = "320px";
    inputContainer.style.minWidth = "200px";
    inputContainer.className = "bg-white rounded-full shadow-lg pointer-events-auto relative";

    function setResponsiveTopPosition() {
        if (window.innerWidth <= MOBILE_BREAKPOINT) {
            inputContainer.style.top = TOP_MOBILE;
        } else {
            inputContainer.style.top = TOP_PC;
        }
    }

    const icon = document.createElement("span");
    icon.style.position = "absolute";
    icon.style.left = "12px";
    icon.style.top = "50%";
    icon.style.transform = "translateY(-50%)";
    icon.style.display = "flex";
    icon.style.alignItems = "center";
    icon.style.pointerEvents = "none";
    icon.innerHTML = `
        <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z"/>
        </svg>`;
    inputContainer.appendChild(icon);

    const input = document.createElement("input");
    input.type = "text";
    input.placeholder = "Buscar ubicación...";
    input.style.width = "100%";
    input.style.height = "100%";
    input.style.padding = "0 12px 0 40px";
    input.style.borderRadius = "999px";
    input.style.border = "none";
    input.style.outline = "none";
    input.style.fontSize = "16px";
    input.className = "pointer-events-auto bg-transparent";
    inputContainer.appendChild(input);

    if (typeof map !== 'undefined' && map.getDiv) {
        map.getDiv().appendChild(inputContainer);
    }

    const autocomplete = new google.maps.places.Autocomplete(input, {
        fields: ["geometry", "formatted_address", "address_components", "name"],
        componentRestrictions: { country: "sv" }
    });
    autocomplete.bindTo("bounds", map);

    autocomplete.addListener("place_changed", function () {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        setMarker(place.geometry.location.lat(), place.geometry.location.lng());
    });

    setResponsiveTopPosition();
    window.addEventListener('resize', setResponsiveTopPosition);

    // Botón flotante de ubicación
    const btnUbicacionFlotante = document.createElement("button");
    btnUbicacionFlotante.innerHTML = `
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-5.686 6-10a6 6 0 10-12 0c0 4.314 6 10 6 10z" />
            <circle cx="12" cy="11" r="2" />
        </svg>`;

    btnUbicacionFlotante.className = "bg-gray-600 hover:bg-gray-700 text-white p-3 rounded-full shadow-lg pointer-events-auto transition-all duration-200 border border-gray-700";
    btnUbicacionFlotante.style.backgroundColor = '#6b7280';
    btnUbicacionFlotante.style.opacity = '1';
    btnUbicacionFlotante.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
    btnUbicacionFlotante.type = "button";

    map.controls[google.maps.ControlPosition.LEFT_BOTTOM].push(btnUbicacionFlotante);

    btnUbicacionFlotante.addEventListener("click", () => {
        if (navigator.geolocation) {
            btnUbicacionFlotante.innerHTML = `
                <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>`;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    setMarker(pos.coords.latitude, pos.coords.longitude);
                    btnUbicacionFlotante.innerHTML = `
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-5.686 6-10a6 6 0 10-12 0c0 4.314 6 10 6 10z" />
                            <circle cx="12" cy="11" r="2" />
                        </svg>`;
                },
                () => {
                    alert("No se pudo obtener la ubicación actual.");
                    btnUbicacionFlotante.innerHTML = `
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-5.686 6-10a6 6 0 10-12 0c0 4.314 6 10 6 10z" />
                            <circle cx="12" cy="11" r="2" />
                        </svg>`;
                }
            );
        } else {
            alert("El navegador no soporta geolocalización.");
        }
    });
});