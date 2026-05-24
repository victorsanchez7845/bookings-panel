let map;
let drawingManager;
let polygon;

// =====================================================
// CONFIGURACIÓN DE CENTRO DEL MAPA POR DESTINO
// =====================================================
// IMPORTANTE:
// Cada vez que agregues un nuevo destination_id en la base de datos,
// debes agregar aquí sus coordenadas centrales.
// Si no existe, el sistema mostrará una alerta y no romperá el mapa.

const DESTINATIONS = {
    1: { // Cancún
        lat: 21.161908,
        lng: -86.851528,
        zoom: 11
    },

    2: { // Los Cabos
        lat: 22.890533,
        lng: -109.916740,
        zoom: 11
    },

    3: { // Punta Cana / República Dominicana
        lat: 18.5601,
        lng: -68.3725,
        zoom: 11
    }
};

// =====================================================
// INICIALIZA TABLA SI EXISTE
// =====================================================

if (document.querySelector('.table-rendering') !== null) {
    components.actionTable($('.table-rendering'));
}

// =====================================================
// CARGA PUNTOS / GEOCERCAS DE UNA ZONA DE EMPRESA
// =====================================================

function getPoints(event, destination_id, zone_id, enterprise_id) {
    event.preventDefault();

    $("#zonesModal").modal("show");

    $.ajax({
        url: `/enterprises/destinations/${destination_id}/points?enterprise_id=${enterprise_id}`,
        type: 'GET',
        data: {
            zone_id: zone_id
        },

        beforeSend: function () {
            showMapLoader();
        },

        success: function (resp) {
            const mapWasInitialized = initMap(destination_id);

            // Si el destino no tiene configuración, detenemos el flujo.
            if (!mapWasInitialized) {
                return;
            }

            // Dibuja geocercas existentes, si las hay.
            drawExistingPolygons(resp, zone_id, destination_id);

            // Activa herramienta para dibujar nueva geocerca.
            initDraw(zone_id);
        }

    }).fail(function (xhr) {
        console.log(xhr);

        Swal.fire(
            '¡ERROR!',
            xhr.responseJSON?.message || 'No se pudieron cargar las geocercas.',
            'error'
        );
    });
}

// =====================================================
// INICIALIZA GOOGLE MAP
// =====================================================

function initMap(destination_id = 1) {
    const defaultDestination = DESTINATIONS[destination_id];

    // Validación para destinos no configurados.
    if (!defaultDestination) {
        $("#zone_map_container").empty();

        Swal.fire({
            icon: 'warning',
            title: 'Destino sin configuración',
            text: `El destino ID ${destination_id} no tiene coordenadas configuradas en zones_enterprise.js.`
        });

        return false;
    }

    const mapOptions = {
        center: {
            lat: defaultDestination.lat,
            lng: defaultDestination.lng
        },
        zoom: defaultDestination.zoom
    };

    map = new google.maps.Map(
        document.getElementById('zone_map_container'),
        mapOptions
    );

    return true;
}

// =====================================================
// DIBUJA GEOCERCAS EXISTENTES
// =====================================================

function drawExistingPolygons(resp, zone_id, destination_id) {
    if (!resp || Object.keys(resp).length === 0) {
        return;
    }

    for (const key in resp) {
        if (!resp.hasOwnProperty(key)) {
            continue;
        }

        const location = resp[key];

        // Si la zona no tiene puntos, se ignora.
        if (!location.points || location.points.length === 0) {
            continue;
        }

        const polygonCoords = [];
        const geocercaPoints = [];

        location.points.forEach(point => {
            geocercaPoints.push(point.point_id);

            polygonCoords.push({
                lat: Number(point.lat),
                lng: Number(point.lng)
            });
        });

        const isCurrentZone = Number(zone_id) === Number(location.id);

        const polygonData = new google.maps.Polygon({
            paths: polygonCoords,
            strokeColor: isCurrentZone ? '#1cbb8c' : '#FF0000',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: isCurrentZone ? '#1cbb8c' : '#FF0000',
            fillOpacity: 0.35
        });

        // Guardamos metadata útil en el polígono.
        polygonData.pointIds = geocercaPoints;
        polygonData.zoneId = location.id;
        polygonData.zoneName = location.name;
        polygonData.destinationId = destination_id;

        polygonData.setMap(map);

        // Permite eliminar la geocerca existente haciendo clic sobre ella.
        google.maps.event.addListener(polygonData, 'click', function () {
            confirmDeletePolygon(this, polygonCoords);
        });
    }
}

// =====================================================
// CONFIRMA ELIMINACIÓN DE GEOCERCA EXISTENTE
// =====================================================

function confirmDeletePolygon(polygonData, polygonCoords) {
    Swal.fire({
        title: '¿Eliminar este punto de la geocerca?',
        html: `Geocerca de la zona: ${polygonData.zoneName}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            deletePolygon(polygonData, polygonCoords);
        }
    });
}

// =====================================================
// ELIMINA GEOCERCA EXISTENTE
// =====================================================

function deletePolygon(polygonData, polygonCoords) {
    $.ajax({
        url: `/enterprises/destinations/${polygonData.zoneId}/points`,
        type: 'DELETE',

        beforeSend: function () {
            Swal.fire({
                title: 'Procesando solicitud...',
                text: 'Por favor, espera mientras se elimina la geocerca.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },

        success: function () {
            Swal.fire({
                title: '¡Éxito!',
                icon: 'success',
                html: 'Geocerca eliminada con éxito.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        }

    }).fail(function () {
        Swal.fire(
            'Error',
            'No se pudo eliminar el punto.',
            'error'
        );

        // Si falla, dejamos visualmente el polígono como estaba.
        polygonData.setPath(polygonCoords);
    });
}

// =====================================================
// ACTIVA HERRAMIENTA DE DIBUJO
// =====================================================

function initDraw(zone_id) {
    drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: google.maps.drawing.OverlayType.POLYGON,
        drawingControl: true,
        drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_CENTER,
            drawingModes: [
                google.maps.drawing.OverlayType.POLYGON
            ]
        }
    });

    drawingManager.setMap(map);

    google.maps.event.addListener(
        drawingManager,
        'overlaycomplete',
        function (event) {
            handlePolygonComplete(event, zone_id);
        }
    );
}

// =====================================================
// CUANDO EL USUARIO TERMINA DE DIBUJAR EL POLÍGONO
// =====================================================

function handlePolygonComplete(event, zone_id) {
    if (event.type !== google.maps.drawing.OverlayType.POLYGON) {
        return;
    }

    polygon = event.overlay;

    const path = polygon.getPath();

    const coordinates = path.getArray().map(coord => ({
        lat: coord.lat(),
        lng: coord.lng()
    }));

    Swal.fire({
        title: '¿Está seguro de guardar la Geocerca?',
        text: 'Esta acción reemplazará la geocerca actual de esta zona.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed === true) {
            savePolygon(zone_id, coordinates);
        } else {
            clearPolygon();
        }
    });
}

// =====================================================
// GUARDA GEOCERCA
// =====================================================

function savePolygon(zone_id, coordinates) {
    $.ajax({
        url: `/enterprises/destinations/${zone_id}/points`,
        type: 'PUT',
        data: {
            coordinates: coordinates
        },

        beforeSend: function () {
            showMapLoader();

            Swal.fire({
                title: 'Procesando solicitud...',
                text: 'Por favor, espera mientras se guarda la geocerca.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },

        success: function () {
            Swal.fire({
                title: '¡Éxito!',
                icon: 'success',
                html: 'Geocerca actualizada con éxito.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        }

    }).fail(function (xhr) {
        console.log(xhr);

        Swal.fire(
            '¡ERROR!',
            xhr.responseJSON?.message || 'No se pudo guardar la geocerca.',
            'error'
        );

        $("#zonesModal").modal("hide");
    });
}

// =====================================================
// BORRA POLÍGONO DIBUJADO SI EL USUARIO CANCELA
// =====================================================

function clearPolygon() {
    if (polygon) {
        polygon.setMap(null);
        polygon = null;
    }
}

// =====================================================
// LOADER DEL MAPA
// =====================================================

function showMapLoader() {
    $("#zone_map_container").empty().html(
        `<div class="spinner-border text-dark me-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>`
    );
}
