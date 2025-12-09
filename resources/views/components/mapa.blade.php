@push('styles')
    <link rel="stylesheet" href="{{ asset('css/mapa.css') }}">
@endpush
<div class="mb-4 md:col-span-2 flex flex-col items-center">
    <label class="">Ubicación</label>

    <div class="relative w-full max-w-3xl flex justify-center">

        {{-- <div id="map" class="w-full max-w-3xl h-[350px] rounded-lg"></div> --}}
        
        <div class="relative w-full h-[500px] rounded-lg" id="map-container">
            <button id="btnMiUbicacion" type="button" class="">
                
            </button>
            
            <div id="map" class="w-full h-full rounded-lg"></div>
        </div>

    </div>

    <input type="hidden" name="latitud" id="latitud" value="{{ $lat ?? '' }}">
    <input type="hidden" name="longitud" id="longitud" value="{{ $lng ?? '' }}">
</div>