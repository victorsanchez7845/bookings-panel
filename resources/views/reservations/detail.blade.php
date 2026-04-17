@php
    use Carbon\Carbon;
    // dump($reservation->toArray());
@endphp

@extends('layout.app')
@section('title') Detalle De Reservación @endsection

@push('Css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/css/lightgallery-bundle.min.css" />

    <link href="{{ mix('/assets/css/sections/reservation_details.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/sections/reservation_details.min.css') }}" rel="stylesheet" >
    <style>
        .btn-group > .btn.btn-lg, .btn-group .btn.btn-lg{
            padding: 0.625rem 0.875rem;
            font-size: 11px;
        }
        .pac-container{
            z-index: 9999 !important;
        }

        .countdown-container {
            margin-bottom: 16px;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 16px;            
        }

        .countdown {
            display: flex;
            gap: 16px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .countdown div {
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 15px;
            border-radius: 10px;
            min-width: 70px;
            transition: all 0.3s ease;
        }
        .countdown div span {
            display: block;
            font-size: 16px;
            font-weight: normal;
            opacity: 0.8;
        }

        /* Animación sutil */
        .countdown div:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .expired {
            font-size: 18px;
            font-weight: bold;
            color: #ff5757;
        }

        .order-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 10;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.65);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            pointer-events: none;
            backdrop-filter: blur(2px);
        }
    </style>
@endpush

@php
    $soLang     = in_array($reservation->language, ['en', 'es']) ? $reservation->language : 'es';
    $soProvider = optional($reservation->site)->name;
    $soPax      = trim($reservation->client_first_name . ' ' . $reservation->client_last_name);
    $soCreated  = $reservation->created_at ? date('d M Y', strtotime($reservation->created_at)) : null;

    // Arrival
    $soArrivalItem   = $reservation->items->first(fn($i) => ($i->final_service_type_one ?? null) === 'ARRIVAL');
    $soArrivalParams = $soArrivalItem ? [
        'orderNumber'   => $soArrivalItem->code,
        'createdAt'     => $soCreated,
        'serviceDate'   => $soArrivalItem->op_one_pickup ? date('d M Y', strtotime($soArrivalItem->op_one_pickup)) : null,
        'serviceType'   => $soLang === 'en' ? 'Arrival' : 'Llegada',
        'passengerName' => $soPax,
        'pickupTime'    => $soArrivalItem->op_one_pickup ? date('H:i', strtotime($soArrivalItem->op_one_pickup)) : null,
        'provider'      => $soProvider,
        'flight'        => $soArrivalItem->flight_number ?: null,
        'hotel'         => $soArrivalItem->to_name,
        'adults'        => (int) $soArrivalItem->passengers,
        'luggage'       => (int) $soArrivalItem->passengers,
        'lang'          => $soLang,
    ] : null;

    // Departure / Transfer
    $soDepOwnItem = $reservation->items->first(fn($i) => in_array($i->final_service_type_one ?? null, ['DEPARTURE', 'TRANSFER']));
    $soDepRtItem  = !$soDepOwnItem
        ? $reservation->items->first(fn($i) => ($i->final_service_type_one ?? null) === 'ARRIVAL' && $i->is_round_trip == 1 && !empty($i->op_two_pickup))
        : null;
    $soDepItem = $soDepOwnItem ?? $soDepRtItem;
    $isRtLeg   = !$soDepOwnItem && $soDepRtItem;

    if ($soDepItem) {
        $soDepPickup = $isRtLeg ? $soDepItem->op_two_pickup : $soDepItem->op_one_pickup;
        $soDepHotel  = $isRtLeg ? $soDepItem->to_name       : $soDepItem->from_name;
        $soDepSvcKey = $isRtLeg ? 'DEPARTURE'               : $soDepItem->final_service_type_one;
        $soDepLabels = [
            'es' => ['DEPARTURE' => 'Salida',    'TRANSFER' => 'Traslado'],
            'en' => ['DEPARTURE' => 'Departure', 'TRANSFER' => 'Transfer'],
        ];
        $soDepartureParams = [
            'orderNumber'   => $soDepItem->code,
            'createdAt'     => $soCreated,
            'serviceDate'   => $soDepPickup ? date('d M Y', strtotime($soDepPickup)) : null,
            'serviceType'   => $soDepLabels[$soLang][$soDepSvcKey] ?? $soDepSvcKey,
            'passengerName' => $soPax,
            'pickupTime'    => $soDepPickup ? date('H:i', strtotime($soDepPickup)) : null,
            'provider'      => $soProvider,
            'flight'        => $soDepItem->flight_number ?: null,
            'hotel'         => $soDepHotel,
            'adults'        => (int) $soDepItem->passengers,
            'luggage'       => (int) $soDepItem->passengers,
            'lang'          => $soLang,
        ];
    } else {
        $soDepartureParams = null;
    }
@endphp

@push('Js')
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
    <script>
        const rez_id = {{ isset($reservation->id) ? $reservation->id : 0 }};
        const payment_request_sent = {{ isset($reservation->payment_request_sent) ? $reservation->payment_request_sent : 0 }};
        const rez_currency = "{{ $reservation->currency }}";
        const rez_pending  = {{ round($data['total_sales'], 2) - round($data['total_payments'], 2) }};
        const soArrivalParams   = @json($soArrivalParams);
        const soDepartureParams = @json($soDepartureParams);
    </script>    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/lightgallery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/zoom/lg-zoom.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.10/dayjs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.10/plugin/duration.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.10/plugin/relativeTime.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.gmaps.key') }}&libraries=places"></script>
    <script src="{{ mix('assets/js/sections/reservations/details.min.js') }}"></script>
    @if ( $data['status'] == "QUOTATION" )
        <script>
            dayjs.extend(dayjs_plugin_duration);
            dayjs.extend(dayjs_plugin_relativeTime);

            function iniciarContador(fechaObjetivo) {
                function actualizarContador() {
                    const ahora = dayjs();
                    const vencimiento = dayjs(fechaObjetivo);
                    const diferencia = vencimiento.diff(ahora);

                    if (diferencia <= 0) {
                        document.getElementById("countdown").innerHTML = '<span class="expired">¡Tiempo vencido!</span>';
                        clearInterval(intervalo);
                        return;
                    }

                    const duracion = dayjs.duration(diferencia);
                    document.getElementById("countdown").innerHTML = `
                        <div><span>DÍAS</span>${duracion.days()}</div>
                        <div><span>HORAS</span>${duracion.hours()}</div>
                        <div><span>MINUTOS</span>${duracion.minutes()}</div>
                        <div><span>SEGUNDOS</span>${duracion.seconds()}</div>
                    `;
                }

                actualizarContador();
                const intervalo = setInterval(actualizarContador, 1000);
            }

            // Fecha de vencimiento (Cambia esto por la fecha que necesites)
            const fechaVencimiento = `{{ $reservation->expires_at }}`;
            iniciarContador(fechaVencimiento);
        </script>
    @endif
    <script>
        function loadPdfIntoContainer(containerId, type) {
            var container = document.getElementById(containerId);
            container.innerHTML = '<p class="text-muted p-3">Cargando PDF...</p>';

            fetch('/reports/ccform/pdf?type=' + type + '&id=' + rez_id, {
                credentials: 'same-origin'
            })
            .then(function(response) {
                if (!response.ok) throw new Error('Error ' + response.status);
                return response.blob();
            })
            .then(function(blob) {
                var blobUrl = URL.createObjectURL(blob);
                var iframe = document.createElement('iframe');
                iframe.width = '100%';
                iframe.height = '700px';
                iframe.style.border = '1px solid #ddd';
                iframe.src = blobUrl;
                container.innerHTML = '';
                container.appendChild(iframe);
            })
            .catch(function() {
                container.innerHTML = '<p class="text-danger p-3">No se pudo cargar el PDF. Verifica tu sesión e intenta de nuevo.</p>';
            });
        }

        function searchOne(){
            loadPdfIntoContainer('iframeOneContainer', 'arrival');
        }

        function searchTwo(){
            loadPdfIntoContainer('iframeTwoContainer', 'departure');
        }

        function fetchServiceOrder(containerId, params) {
            var container = document.getElementById(containerId);
            if (container.dataset.loaded) return;
            container.dataset.loaded = '1';
            container.innerHTML = '<p class="text-muted p-3">Cargando PDF...</p>';

            var qs = new URLSearchParams(params).toString();
            fetch('/reports/service-order/pdf?' + qs, {
                credentials: 'same-origin'
            })
            .then(function(response) {
                if (!response.ok) throw new Error('Error ' + response.status);
                return response.blob();
            })
            .then(function(blob) {
                var blobUrl = URL.createObjectURL(blob);
                var iframe = document.createElement('iframe');
                iframe.width = '100%';
                iframe.height = '700px';
                iframe.style.border = '1px solid #ddd';
                iframe.src = blobUrl;
                container.innerHTML = '';
                container.appendChild(iframe);
            })
            .catch(function() {
                container.innerHTML = '<p class="text-danger p-3">No se pudo cargar el PDF. Verifica tu sesión e intenta de nuevo.</p>';
                container.dataset.loaded = '';
            });
        }

        function searchServiceOrderArrival()   { fetchServiceOrder('iframeSOArrivalContainer',   soArrivalParams);   }
        function searchServiceOrderDeparture() { fetchServiceOrder('iframeSODepartureContainer', soDepartureParams); }

        lightGallery(document.getElementById('media-listing'), {
            thumbnail: true,
        })        
    </script>
@endpush

@section('content')
    {{-- @dump($reservation->toArray(), $data); --}}
    <div class="row layout-top-spacing">
        <div class="col-xxl-3 col-xl-4 col-12">
            <div class="card mb-2">
                <div class="card-header" style="display: flex; gap: 5px; justify-content: center; align-items: center; flex-wrap: wrap;">
                    <div class="card-actions float-end" style="order: 2;">
                        @if (auth()->user()->hasPermission(11))

                        {{-- <div class="dropdown show"> --}}
                        {{-- href="#" data-bs-toggle="dropdown" data-bs-display="static" --}}
                            <a class="btn btn-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#serviceClientModal">
                                EDITAR RESERVA
                            </a>
                            {{-- <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#" type="button" data-bs-toggle="modal" data-bs-target="#serviceClientModal">Editar</a>
                            </div> --}}
                        {{-- </div> --}}
                        @endif
                    </div>
                    <button class="btn btn-warning" style="cursor: normal; pointer-events: none; order: 1;">
                        <h5 class="card-title mb-0" style="color: white;">
                            {{ $reservation->site->name }}
                        </h5>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-container table-details-booking">
                        <table class="table table-hover table-striped table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th>Estatus</th>
                                    <td><button type="button" class="btn btn-{{ auth()->user()->classStatusBooking($data['status']) }}">{{ auth()->user()->statusBooking($data['status']) }}</button></td>
                                </tr>
                                @if ( $data['status'] == "QUOTATION" )
                                    <tr>
                                        <th>Fecha limite de pago</th>
                                        <td>{{ $reservation->expires_at }}</td>
                                    </tr>                                    
                                @endif
                                <tr>
                                    <th>Pago al llegar</th>
                                    <td><span class="badge bg-{{ $reservation->pay_at_arrival == 1 ? 'success' : 'danger' }}">{{ $reservation->pay_at_arrival == 1 ? 'Sí' : 'No' }}</span></td>
                                </tr>
                                @if ( $reservation->is_quotation == 0 && $reservation->was_is_quotation == 1 )
                                    <tr>
                                        <th>Fue cotización</th>
                                        <td><span class="badge bg-{{ $reservation->was_is_quotation == 1 ? 'success' : 'danger' }}">{{ $reservation->was_is_quotation == 1 ? 'Sí' : 'No' }}</span></td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Nombre</th>
                                    <td>{{ $reservation->client_first_name }} {{ $reservation->client_last_name }}</td>
                                </tr>
                                <tr>
                                    <th>E-mail</th>
                                    <td>{{ $reservation->client_email }}</td>
                                </tr>
                                <tr>
                                    <th>Teléfono</th>
                                    <td>{{ $reservation->client_phone }}</td>
                                </tr>
                                <tr>
                                    <th>Moneda</th>
                                    <td>{{ $reservation->currency }}</td>
                                </tr>
                                <tr>
                                    <th>Destino</th>
                                    <td>{{ $reservation->destination->name ?? '' }}</td>
                                </tr>
                                <tr>
                                    <th>Referencia</th>
                                    <td>{{ $reservation->reference }}</td>
                                </tr>
                                @if( isset( $reservation->originSale->code ) )
                                    <tr>
                                        <th>Origen de venta</th>
                                        <td>{{ $reservation->originSale->code }}</td>
                                    </tr>                                    
                                @endif
                                @if ( $reservation->callCenterAgent != null )
                                    <tr>
                                        <th>Agente de Call Center</th>
                                        <td>{{ $reservation->callCenterAgent->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Estatus de comisión</th>
                                        <td><span class="badge btn-{{ $reservation->is_commissionable == 1 ? "success ".( auth()->user()->hasPermission(95) ? 'deleteCommission' : '' ) : "danger" }}" data-code="{{ $reservation->id }}" style="cursor: pointer;">{{ $reservation->is_commissionable == 1 ? "Comsionable" : "No comisionable" }}</span></td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Total a pagar:</th>
                                    <td>$ {{ round( $data['total_sales'], 2) }} {{ $reservation->currency }}</td>
                                </tr>
                                <tr>
                                    <th>Total pagado:</th>
                                    <td>$ {{ round( $data['total_payments'], 2) }} {{ $reservation->currency }}</td>
                                </tr>
                                <tr>
                                    <th>Total pendiente de pago:</th>
                                    <td>$ {{ round( $data['total_sales'], 2) - round( $data['total_payments'], 2) }} {{ $reservation->currency }}</td>
                                </tr>
                                @if( isset( $reservation->cancellationType->name_es ) )
                                    <tr>
                                        <th>Motivo de cancelación</th>
                                        <td>{{ $reservation->cancellationType->name_es }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Creación</th>
                                    <td>{{ $reservation->created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    {{-- <hr style="width:95%; margin-left: auto; margin-right: auto;"> --}}
                    @if (auth()->user()->hasPermission(25))
                        <div class="NewTimeLine">
                            <h6 class="my-3">Actividad</h6>
                            <ul>
                                @foreach($reservation->followUps as $key => $followUp)
                                    <li>
                                        @php
                                            $fecha = Carbon::parse($followUp->created_at);
                                        @endphp
                                        <div style="display: flex;justify-content: space-between;align-items: center;">
                                            <strong class="text-black">[{{ $followUp->type }}]</strong>
                                            <span>{{ date("Y/m/d H:i", strtotime($followUp->created_at)) }}</span> 
                                            <span>{{ $fecha->diffForHumans() }}</span>
                                        </div>
                                        <div class="content">
                                            <h3>{{ $followUp->name }}</h3>
                                            <p>{{ $followUp->text }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>                        
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xxl-9 col-xl-8 col-12">
            <div class="controls">
                @csrf
                <button class="btn social-button" id="sendMessageWhatsApp" data-code="{{ $reservation->id }}">
                    <img src="https://affiliates.gotransfers.us/assets/img/icons/png/whatsapp.png" alt="WhatsApp" class="img-fluid" width="20" height="20">
                    WHATSAPP
                </button>

                {{-- NOS PERMITE REENVIO DE CORREO DE LA RESERVACIÓN AL CLIENTE, CUANDO TENEMOS EL PERMISO Y ES PENDIENTE, CONFIRMADA O A CREDITO --}}
                @if ( ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" || $data['status'] == "QUOTATION" ) && auth()->user()->hasPermission(20) )
                    <div class="btn-group btn-group-sm" role="group">
                        <button id="btndefault" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            RE-ENVIO DE CORREO
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="btndefault">
                            <a class="dropdown-item" href="#" onclick="sendMail('{{ $reservation->items->first()->code }}','{{ $reservation->client_email }}','es')">Español</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="sendMail('{{ $reservation->items->first()->code }}','{{ $reservation->client_email }}','en')">Inglés</a>
                        </div>
                    </div>
                @endif

                {{-- NOS PERMITE AGREGAR SEGUIMIENTOS DE LA RESERVA, SOLO CUANDO ESTA COMO PENDIENTE, CONFIRMADA O A CREDITO --}}
                {{-- ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" || $data['status'] == "QUOTATION" ) &&  --}}
                @if ( auth()->user()->hasPermission(23) )
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#reservationFollowModal">AGREGAR SEGUIMIENTO</button>
                @endif

                {{-- NOS PERMITE ENVIAR UN MENSAJE --}}
                {{-- @if ( ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" ) && auth()->user()->hasPermission(21) )
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            ENVIAR MENSAJE
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">SMS</a>
                            <a class="dropdown-item" href="#">Whatsapp</a>
                        </div>
                    </div>
                @endif --}}

                {{-- NOS PERMITE ENVIAR UNA INVITACIÓN DE PAGO AL CLIENTE CUANDO LA RESERVA SEA DIFERENTE DE CANCELADO O DUPLICADO --}}
                @if ( ( $data['status'] != "CANCELLED" && $data['status'] != "DUPLICATED" ) && auth()->user()->hasPermission(22))
                    <div class="btn-group btn-group-sm" role="group">
                        <button id="btndefault" type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            INVITACIÓN DE PAGO
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="btndefault">
                            <a class="dropdown-item" href="#" onclick="sendInvitation(event, '{{ $reservation->id }}','en')">Enviar en inglés</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="sendInvitation(event, '{{ $reservation->id }}','es')">Enviar en español</a>
                        </div>
                    </div>
                @endif

                {{-- NOS PERMITE COPIAR EL LINK DE PAGO PARA ENVIARSELO AL CLIENTE LA RESERVA SEA DIFERENTE DE CANCELADO O DUPLICADO --}}
                @if ( $data['status'] != "CANCELLED" && $data['status'] != "DUPLICATED" )
                    <div class="btn-group btn-group-sm" role="group">
                        <button id="btndefault" type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            COPIAR LINK DE PAGO STRIPE
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="btndefault">
                            <a class="dropdown-item paymentLink" href="javascript:void(0)" data-reservation="{{ $reservation->items[0]['code'] }}" data-email="{{ trim($reservation->client_email) }}" data-language="en" data-type="STRIPE" >Inglés</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item paymentLink" href="javascript:void(0)" data-reservation="{{ $reservation->items[0]['code'] }}" data-email="{{ trim($reservation->client_email) }}" data-language="es" data-type="STRIPE">Español</a>
                        </div>
                        <button id="btndefaultpaypal" type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            COPIAR LINK DE PAGO PAYPAL
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="btndefaultpaypal">
                            <a class="dropdown-item paymentLink" href="javascript:void(0)" data-reservation="{{ $reservation->items[0]['code'] }}" data-email="{{ trim($reservation->client_email) }}" data-language="en" data-type="PAYPAL-V3" >Inglés</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item paymentLink" href="javascript:void(0)" data-reservation="{{ $reservation->items[0]['code'] }}" data-email="{{ trim($reservation->client_email) }}" data-language="es" data-type="PAYPAL-V3">Español</a>
                        </div>
                    </div>
                @endif

                {{-- NOS PERMITE COPIAR EL LINK DE PAGO PARA ENVIARSELO AL CLIENTE LA RESERVA SEA DIFERENTE DE CANCELADO O DUPLICADO --}}
                @if ( $data['status'] != "CANCELLED" && $data['status'] != "DUPLICATED" )
                    <div class="btn-group btn-group-sm" role="group">
                        <button id="btndefault" type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            COPIAR LINK DE PAGO OPENPAY
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="btndefault">
                            <a class="dropdown-item paymentLink" href="javascript:void(0)" data-reservation="{{ $reservation->items[0]['code'] }}" data-email="{{ trim($reservation->client_email) }}" data-language="en" data-type="OPENPAY" >Inglés</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item paymentLink" href="javascript:void(0)" data-reservation="{{ $reservation->items[0]['code'] }}" data-email="{{ trim($reservation->client_email) }}" data-language="es" data-type="OPENPAY">Español</a>
                        </div>
                    </div>
                @endif

                {{-- NOS PERMITE INDICAR QUE CLIENTE PAGARA A LA LLEGADA, SOLO SE MOSTRARA CUANDO SEA COTIZACIÓN O PENDIENTE --}}
                @if ( $reservation->pay_at_arrival == 0 && ( $data['status'] == "QUOTATION" || $data['status'] == "PENDING" ) )
                    <button class="btn btn-warning btn-sm enablePayArrival" id="enablePayArrival" data-code="{{ $reservation->id }}"><i class="align-middle" data-feather="plus"></i> ACTIVAR PAGO A LA LLEGADA</button>
                @endif

                {{-- MOSTRARA EL BOTON DE ACTIVACION DE SERVICIO PLUS, SIEMPRE QUE LA RESERVA NO ESTA CANCELADA NI DUPLICADA --}}
                @if (auth()->user()->hasPermission(94) && $reservation->is_quotation == 0 && $reservation->is_cancelled == 0 && $reservation->is_duplicated == 0 && $reservation->is_advanced == 0 )
                    <button class="btn btn-success btn-sm enablePlusService" id="enablePlusService" data-code="{{ $reservation->id }}"><i class="align-middle" data-feather="delete"></i> ACTIVAR SERVICIO PLUS</button>
                @endif

                {{-- NOS PERMITE PONER COMO CREDITO ABIERTO CUANDO LA RESERVA ESTA CONFIRMADA Y EL CLIENTE QUIERE CANCELAR --}}
                {{-- NECESITO APLICAR RECLAS MUCHO MAS ESPECIFICAS --}}
                @if ( ($data['status'] == "CONFIRMED" || $data['status'] == "CREDIT") && auth()->user()->hasPermission(72) )
                    <button class="btn btn-warning btn-sm markReservationOpenCredit" id="markReservationOpenCredit" data-code="{{ $reservation->id }}" data-status="{{ $data['status'] }}" onclick="openCredit({{ $reservation->id }})"><i class="align-middle" data-feather="delete"></i> CRÉDITO ABIERTO</button>
                @endif
                    
                {{-- NOS PERMITE PODER ACTIVAR LA RESERVA CUANDO ESTA COMO CREDITO ABIERTO --}}
                @if ( ( $data['status'] == "OPENCREDIT" || $data['status'] == "DUPLICATED" || $data['status'] == "CANCELLED" || ( $data['status'] == "CANCELLED" && $reservation->was_is_quotation == 1 ) ) && auth()->user()->hasPermission(67) )
                    <button class="btn btn-success btn-sm reactivateReservation" id="reactivateReservation" data-code="{{ $reservation->id }}" data-status="{{ $data['status'] }}" data-pay_at_arrival="{{ $reservation->pay_at_arrival }}"><i class="align-middle" data-feather="alert-circle"></i> REACTIVAR RESERVA</button>
                @endif

                {{-- NOS PERMITE INDICAR QUE CLIENTE ESTA SOLICITANDO UN REEMBOLSO --}}
                @if ( ( $data['status'] == "CONFIRMED" || $data['status'] == "CANCELLED" ) && $data['total_payments'] > 0 )
                    <button class="btn btn-warning btn-sm refundRequest" id="refundRequest" data-code="{{ $reservation->id }}"><i class="align-middle" data-feather="delete"></i> SOLICITUD DE REEMBOLSO A CONTABILIDAD</button>
                @endif

                {{-- NOS PERMITE MARCAR COMO DUPLICADA LA RESERVA --}}
                @if (auth()->user()->hasPermission(24) && $reservation->is_quotation == 0 && $reservation->is_cancelled == 0 && $reservation->is_duplicated == 0 )
                    <button class="btn btn-danger btn-sm markReservationDuplicate" id="markReservationDuplicate" data-code="{{ $reservation->id }}" data-status="{{ $data['status'] }}"><i class="align-middle" data-feather="delete"></i> MARCAR COMO DUPLICADO</button>
                @endif
                
                                            {{-- NOS PERMITE REALIZAR ESTAS ACCIONES SOLO CUANDO LA RESERVA ESTA PENDIENTE CONFIRMADA O A CREDITO --}}
                                            {{-- Y NOS PERMITE DARLE UNA CALIFICACIÓN A LA RESERVA --}}
                                            @if ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" || $data['status'] == "QUOTATION" )                                                
                                                @if ( $reservation->reserve_rating != NULL )
                                                    <div class="btn-group" role="group" aria-label="likes">                                                    
                                                        <button type="button" class="btn btn-{{ $reservation->reserve_rating == 1 ? 'success' : 'danger' }} bs-tooltip" title="Esta es la calificación final de la reserva."><?=( $reservation->reserve_rating == 1 ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-up"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-down"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path></svg>' )?></button>
                                                    </div>
                                                @else
                                                    <div class="btn-group" role="group" aria-label="likes">                                                    
                                                        <button type="button" class="btn btn-success bs-tooltip enabledLike" title="click para calificar como positiva la reserva." data-reservation="{{ $reservation->id }}" data-status="1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-up"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg></button>
                                                        <button type="button" class="btn btn-danger bs-tooltip enabledLike" title="click para calificar como negativa la reserva." data-reservation="{{ $reservation->id }}" data-status="0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-down"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path></svg></button>
                                                    </div>
                                                @endif
                                            @endif                
            </div>

            @if ( $data['status'] == "QUOTATION" )
                <div class="countdown-container">
                    <h1>Tiempo restante para realizar el pago o la reserva se cancelara automaticamente</h1>
                    <div id="countdown" class="countdown">
                        <div><span>Días</span>00</div>
                        <div><span>Horas</span>00</div>
                        <div><span>Minutos</span>00</div>
                        <div><span>Segundos</span>00</div>
                    </div>
                </div>
            @endif

            <div class="tab">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#icon-tab-1" data-bs-toggle="tab" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin align-middle"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            Detalles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#icon-tab-2" data-bs-toggle="tab" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-bag align-middle"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                            Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#icon-tab-3" data-bs-toggle="tab" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-credit-card align-middle"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            Pagos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#icon-tab-4" data-bs-toggle="tab" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-credit-card align-middle"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            Reembolsos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#icon-tab-8" data-bs-toggle="tab" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-truck align-middle"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            Operación
                        </a>
                    </li>
                    @if (auth()->user()->hasPermission(65))
                        <li class="nav-item">
                            <a class="nav-link" href="#icon-tab-5" data-bs-toggle="tab" role="tab">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-camera align-middle"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                Imagenes
                            </a>
                        </li>
                    @endif

                    @if ($data['transfer_types']['has_arrival'] && $data['status'] != "CANCELLED")
                        <li class="nav-item">
                            <a class="nav-link" href="#icon-tab-6" data-bs-toggle="tab" role="tab" onclick="searchOne()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                                CCform Llegada
                            </a>
                        </li>
                    @endif
                    @if (($data['transfer_types']['has_departure'] || $data['transfer_types']['has_transfer']) && $data['status'] != "CANCELLED")
                        <li class="nav-item">
                            <a class="nav-link" href="#icon-tab-7" data-bs-toggle="tab" role="tab" onclick="searchTwo()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                                @if ($data['transfer_types']['has_transfer'])
                                    CCform Traslado
                                @else
                                    CCform Salida
                                @endif                                
                            </a>
                        </li>                        
                    @endif
                    @if ($data['transfer_types']['has_arrival'] && $data['status'] != "CANCELLED")
                        <li class="nav-item">
                            <a class="nav-link" href="#icon-tab-9" data-bs-toggle="tab" role="tab" onclick="searchServiceOrderArrival()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clipboard"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                                Orden Servicio Llegada
                            </a>
                        </li>
                    @endif
                    @if (($data['transfer_types']['has_departure'] || $data['transfer_types']['has_transfer']) && $data['status'] != "CANCELLED")
                        <li class="nav-item">
                            <a class="nav-link" href="#icon-tab-10" data-bs-toggle="tab" role="tab" onclick="searchServiceOrderDeparture()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clipboard"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                                @if ($data['transfer_types']['has_transfer'])
                                    Orden Servicio Traslado
                                @else
                                    Orden Servicio Salida
                                @endif
                            </a>
                        </li>
                    @endif
                </ul>
                <div class="tab-content">
                    <div class="tab-pane services active" id="icon-tab-1" role="tabpanel">
                        @foreach ($reservation->items as $item)
                            {{-- @dump($item->toArray()); --}}
                            <div class="services-container mb-2">
                                <h3>{{ $item->code }}</h3>
                                {{-- NOS INDICA QUE TIENE ACTIVO EL SERVICIO AVANZADO --}}
                                @if ( $reservation->is_advanced == 1 )
                                    <div class="check-bubble" data-bs-toggle="popover" title="Servicio plus" data-bs-content="incluye cancelación gratuita. bebidas de cortesia. cuponera de descuento. parada de cortesia">
                                        <span class="check-mark">✔</span>
                                    </div>
                                @endif
                                <div class="items-container">
                                    <div class="items">
                                        <div class="information_data">
                                            <p><strong>Tipo:</strong> {{ (( $item->is_round_trip == 1 )? 'Round Trip':'One Way') }}</p>
                                            <p><strong>Vehículo:</strong> {{ $item->destination_service->name }}</p>
                                            <p><strong>Pasajeros:</strong> {{ $item->passengers }}</p>
                                            <p><strong># de Vuelo:</strong> {{ $item->flight_number ?? 'N/A' }}</p>
                                        </div>
                                        <div class="actions d-flex gap-2 mb-3">
                                            <div class="btn-group" role="group" aria-label="Opciones">
                                                @if ( auth()->user()->hasPermission(127) )
                                                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#serviceMapModal" onclick="details.initMap({{ $item }})">VER MAPA</button>
                                                @endif
                                                @if ( ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" || $data['status'] == "QUOTATION" ) && auth()->user()->hasPermission(13))
                                                    <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#serviceEditModal" onclick="details.itemInfo({{ $item }})">EDITAR SERVICIO</button>
                                                @endif

                                                @if ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" || $data['status'] == "QUOTATION" )
                                                    <button type="button" class="btn btn-secondary btn-lg arrivalConfirmation" type="button" data-id="{{ $item->reservations_item_id }}" data-bs-toggle="modal" data-bs-target="#arrivalConfirmationModal">CONFIRMACIÓN DE LLEGADA</button>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-secondary btn-lg dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            CONFIRMACIÓN DE SALIDA
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                        </button>
                                                        <div class="dropdown-menu" style="">
                                                            <a class="dropdown-item" href="#" onclick="sendDepartureConfirmation(event, {{ $item->reservations_item_id }}, {{ $reservation->destination_id }}, 'en', 'departure', {{ $item->is_round_trip }})">Enviar en inglés</a>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="#" onclick="sendDepartureConfirmation(event, {{ $item->reservations_item_id }}, {{ $reservation->destination_id }}, 'es', 'departure', {{ $item->is_round_trip }})">Enviar en español</a>
                                                        </div>
                                                    </div>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-secondary btn-lg dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            TRANSFER RECOGIDA
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                        </button>
                                                        <div class="dropdown-menu" style="">
                                                            <a class="dropdown-item" href="#" onclick="sendDepartureConfirmation(event, {{ $item->reservations_item_id }}, {{ $reservation->destination_id }}, 'en', 'transfer-pickup', {{ $item->is_round_trip }})">Enviar en inglés</a>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="#" onclick="sendDepartureConfirmation(event, {{ $item->reservations_item_id }}, {{ $reservation->destination_id }}, 'es', 'transfer-pickup', {{ $item->is_round_trip }})">Enviar en español</a>
                                                        </div>
                                                    </div>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-secondary btn-lg dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            TRANSFER REGRESO
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                        </button>
                                                        <div class="dropdown-menu" style="">
                                                            <a class="dropdown-item" href="#" onclick="sendDepartureConfirmation(event, {{ $item->reservations_item_id }}, {{ $reservation->destination_id }}, 'en', 'transfer-return', {{ $item->is_round_trip }})">Enviar en inglés</a>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="#" onclick="sendDepartureConfirmation(event, {{ $item->reservations_item_id }}, {{ $reservation->destination_id }}, 'es', 'transfer-return', {{ $item->is_round_trip }})">Enviar en español</a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            @if ( auth()->user()->hasPermission(128) )
                                                <div class="btn-group" role="group" aria-label="delete">
                                                    <button type="button" class="btn btn-primary deleteItem" data-item="{{ $item->id }}">Eliminar véhiculo</button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="item-data">
                                        <div class="table-container">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Tipo de servicio</th>
                                                        <th>Desde</th>
                                                        <th>Hacia</th>
                                                        <th>Pickup</th>
                                                        <th>Estatus de servicio</th>
                                                        <th>Comentario</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            @php
                                                                $service = (object) array( 
                                                                    'final_service_type' => $item->final_service_type_one, 
                                                                    'op_type' => 'TYPE_ONE', 
                                                                    'op_one_preassignment' => $item->op_one_preassignment 
                                                                );
                                                            @endphp
                                                            <?=auth()->user()->renderServicePreassignment($service)?>
                                                        </td>
                                                        <td>{{ auth()->user()->typeService($item->final_service_type_one) }}</td>
                                                        <td>
                                                            <p><strong>Zona</strong>: {{ $item->origin->name }}</p>
                                                            <p><strong>Lugar</strong>: {{ $item->from_name }}</p>
                                                        </td>
                                                        <td>
                                                            <p><strong>Zona</strong>: {{ $item->destination->name }}</p>
                                                            <p><strong>Lugar</strong>: {{ $item->to_name }}</p>
                                                        </td>
                                                        <td>{{ date("Y/m/d H:i", strtotime( $item->op_one_pickup )) }}</td>
                                                        <td>
                                                            @php
                                                                $btn_op_one_type = 'btn-secondary';
                                                                switch ($item->op_one_status) {
                                                                    case 'PENDING':
                                                                        $btn_op_one_type = 'btn-secondary';
                                                                        break;
                                                                    case 'COMPLETED':
                                                                        $btn_op_one_type = 'bg-success';
                                                                        break;
                                                                    case 'NOSHOW':
                                                                        $btn_op_one_type = 'bg-warning';
                                                                        break;
                                                                    case 'CANCELLED':
                                                                        $btn_op_one_type = 'bg-danger';
                                                                        break;
                                                                }
                                                                $tooltip = ( $item->op_one_operation_close == 1 ? 'data-bs-toggle="tooltip" data-bs-placement="top" title="Este es el estatus final asignado por operaciones"' : '' );
                                                            @endphp
                                                            {{-- PERMITIRA LA MODIFICACION DEL SERVICIO, DE ACUERDO A LAS SIGUIENTES REGLAS --}}
                                                            {{-- SOLO CUANDO SE TENGA EL PERMISO --}}
                                                            {{-- NO ESTE CERRADA LA OPERACION --}}
                                                            {{-- CUANDO EL ESTATUS DE LA RESERVA SEA PENDIENTE, CONFIMADO O CREDTIO --}}
                                                            @if ( auth()->user()->hasPermission(68) && $item->op_one_operation_close == 0 && ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" ) )
                                                                <div class="btn-group btn-group-sm">
                                                                    <button type="button" class="btn {{ $btn_op_one_type }} dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color:white;">{{ auth()->user()->statusBooking($item->op_one_status) }}</button>
                                                                    <div class="dropdown-menu" style="">
                                                                        <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="PENDING" data-type="TYPE_ONE">Pendiente</a>
                                                                        <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="COMPLETED" data-type="TYPE_ONE">Completado</a>
                                                                        <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="NOSHOW" data-type="TYPE_ONE">No show</a>
                                                                        <hr>
                                                                        <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="CANCELLED" data-type="TYPE_ONE">Cancelado</a>
                                                                        <hr>
                                                                        <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="NOTOPERATED"    data-type="TYPE_ONE">No operado</a>
                                                                        <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="REFUND"         data-type="TYPE_ONE">Reembolso</a>
                                                                        <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="DISPUTE"        data-type="TYPE_ONE">Disputa</a>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <button <?=$tooltip?> type="button" class="btn {{ $btn_op_one_type }} btn-sm bs-tooltip">{{ auth()->user()->statusBooking($item->op_one_status) }}</button>                                
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                                                                <span style="white-space: pre-line;">
                                                                    {{ isset($item->op_one_comments) ? $item->op_one_comments : ' ---' }}
                                                                </span>
                                                                @if(auth()->user()->hasPermission(13))
                                                                    <button style="word-break: keep-all;" data-bs-toggle="modal" data-bs-target="#item_comment_modal" data-bs-placement="top" title="Click para editar comentario" class="btn btn-primary bs-tooltip edit-comment" type="button" data-item="{{ $item->reservations_item_id }}" data-comment="{{ $item->op_one_comments }}" data-type="one">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>
                                                            {{-- NOS PERMITE ACTUALIZAR Y ENVIAR LA CONFIRMACION DEL SERVICIO AL CLIENTE POR CORREO --}}
                                                            {{-- VER SI EL SERVICIO ESTA EN UNA OPERACION ABIERTA O CERRADA --}}
                                                            {{-- SOLO CUANDO LA RESERVA ESTA PENDIENTE, CONFIRMADA O A CREDITO --}}
                                                            @if ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" )
                                                                <div class="d-flex gap-2">                                                                
                                                                    @php
                                                                        $message_operation_one = ( $item->op_one_operation_close == 1 ? "El servicio se encuentra en una operación cerrada".( auth()->user()->hasPermission(92) ? ", da click si desea desbloquear el servicio del cierre de operación" : "" ) : "El servicio se encuentra en una operacón abierta" );
                                                                    @endphp
                                                                    @if ( auth()->user()->hasPermission(69))
                                                                        <button class="btn {{ $item->op_one_confirmation == 1 ? 'btn-success' : 'btn-warning' }} confirmService" type="button" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="{{ $item->op_one_confirmation == 1 ? 0 : 1 }}" data-type="TYPE_ONE">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle align-middle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                                                        </button>
                                                                    @endif
                                                                    <button data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $message_operation_one }}" class="btn btn-{{ $item->op_one_operation_close == 1 ? "danger" : "success" }} {{  auth()->user()->hasPermission(92) && $item->op_one_operation_close == 1 ? "updateServiceUnlock" : "" }} bs-tooltip" type="button" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-type="TYPE_ONE">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-{{ $item->op_one_operation_close == 1 ? "unlock" : "lock" }} align-middle"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>

                                                    @if($item->is_round_trip == 1)
                                                        <tr>
                                                            <td>
                                                                @php
                                                                    $service = (object) array( 
                                                                        'final_service_type' => $item->final_service_type_two, 
                                                                        'op_type' => 'TYPE_TWO', 
                                                                        'op_two_preassignment' => $item->op_two_preassignment 
                                                                    );
                                                                @endphp
                                                                <?=auth()->user()->renderServicePreassignment($service)?>
                                                            </td>
                                                            <td>{{ auth()->user()->typeService($item->final_service_type_two) }}</td>
                                                            <td>
                                                                <p><strong>Zona</strong>: {{ $item->destination->name }}</p>
                                                                <p><strong>Lugar</strong>: {{ $item->to_name }}</p>                                                                
                                                            </td>
                                                            <td>
                                                                <p><strong>Zona</strong>: {{ $item->origin->name }}</p>
                                                                <p><strong>Lugar</strong>: {{ $item->from_name }}</p>
                                                            </td>
                                                            <td>{{ date("Y/m/d H:i", strtotime( $item->op_two_pickup )) }}</td>
                                                            <td>
                                                                @php
                                                                    $btn_op_two_type = 'btn-secondary';
                                                                    switch ($item->op_two_status) {
                                                                        case 'PENDING':
                                                                            $btn_op_two_type = 'btn-secondary';
                                                                            break;
                                                                        case 'COMPLETED':
                                                                            $btn_op_two_type = 'bg-success';
                                                                            break;
                                                                        case 'NOSHOW':
                                                                            $btn_op_two_type = 'bg-warning';
                                                                            break;
                                                                        case 'CANCELLED':
                                                                            $btn_op_two_type = 'bg-danger';
                                                                            break;
                                                                    }
                                                                    $tooltip = ( $item->op_two_operation_close == 1 ? 'data-bs-toggle="tooltip" data-bs-placement="top" title="Este es el estatus final asignado por operaciones"' : '' );
                                                                @endphp
                                                                {{-- PERMITIRA LA MODIFICACION DEL SERVICIO, DE ACUERDO A LAS SIGUIENTES REGLAS --}}
                                                                {{-- SOLO CUANDO SE TENGA EL PERMISO --}}
                                                                {{-- NO ESTE CERRADA LA OPERACION --}}
                                                                {{-- CUANDO EL ESTATUS DE LA RESERVA SEA PENDIENTE, CONFIMADO O CREDTIO --}}
                                                                @if ( auth()->user()->hasPermission(68) && $item->op_two_operation_close == 0 && ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" ) )
                                                                    <div class="btn-group btn-group-sm">
                                                                        <button type="button" class="btn {{ $btn_op_two_type }} dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color:white;">{{ auth()->user()->statusBooking($item->op_two_status) }}</button>
                                                                        <div class="dropdown-menu" style="">
                                                                            <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="PENDING" data-type="TYPE_TWO">Pendiente</a>
                                                                            <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="COMPLETED" data-type="TYPE_TWO">Completado</a>
                                                                            <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="NOSHOW" data-type="TYPE_TWO">No show</a>
                                                                            <hr>
                                                                            <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="CANCELLED" data-type="TYPE_TWO">Cancelado</a>
                                                                            <hr>
                                                                            <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="NOTOPERATED"    data-type="TYPE_TWO">No operado</a>
                                                                            <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="REFUND"         data-type="TYPE_TWO">Reembolso</a>
                                                                            <a href="javascript:void(0);" class="dropdown-item serviceStatusUpdate" data-reservation="{{ $reservation->id }}" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_one }}" data-status="DISPUTE"        data-type="TYPE_TWO">Disputa</a>                                                                            
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <button <?=$tooltip?> type="button" class="btn {{ $btn_op_two_type }} btn-sm bs-tooltip">{{ auth()->user()->statusBooking($item->op_two_status) }}</button> 
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                                                                    <span style="white-space: pre-line;">
                                                                        {{ isset($item->op_two_comments) ? $item->op_two_comments : ' ---' }}
                                                                    </span>
                                                                    @if(auth()->user()->hasPermission(13))
                                                                        <button style="word-break: keep-all;" data-bs-toggle="modal" data-bs-target="#item_comment_modal" data-bs-placement="top" title="Click para editar comentario" class="btn btn-primary bs-tooltip edit-comment" type="button" data-item="{{ $item->reservations_item_id }}" data-comment="{{ $item->op_two_comments }}" data-type="two">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td>
                                                                {{-- NOS PERMITE ACTUALIZAR Y ENVIAR LA CONFIRMACION DEL SERVICIO AL CLIENTE POR CORREO --}}
                                                                {{-- VER SI EL SERVICIO ESTA EN UNA OPERACION ABIERTA O CERRADA --}}
                                                                {{-- SOLO CUANDO LA RESERVA ESTA PENDIENTE, CONFIRMADA O A CREDITO --}}
                                                                @if ( $data['status'] == "PENDING" || $data['status'] == "PAY_AT_ARRIVAL" || $data['status'] == "CONFIRMED" || $data['status'] == "CREDIT" )
                                                                    @php
                                                                        $message_operation_two = ( $item->op_two_operation_close == 1 ? "El servicio se encuentra en una operación cerrada".( auth()->user()->hasPermission(92) ? ", da click si desea desbloquear el servicio del cierre de operación" : "" ) : "El servicio se encuentra en una operacón abierta" );
                                                                    @endphp
                                                                    <div class="d-flex gap-2">
                                                                        @if (auth()->user()->hasPermission(69))
                                                                            <button class="btn {{ $item->op_two_confirmation == 1 ? 'btn-success' : 'btn-warning' }} confirmService" type="button" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_two }}" data-status="{{ $item->op_two_confirmation == 1 ? 0 : 1 }}" data-type="TYPE_TWO">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle align-middle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                                                            </button>
                                                                        @endif
                                                                        <button data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $message_operation_two }}" class="btn btn-{{ $item->op_two_operation_close == 1 ? "danger" : "success" }} {{  auth()->user()->hasPermission(92) && $item->op_two_operation_close == 1 ? "updateServiceUnlock" : "" }} bs-tooltip" type="button" data-item="{{ $item->reservations_item_id }}" data-service="{{ $item->final_service_type_two }}" data-type="TYPE_TWO">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-{{ $item->op_two_operation_close == 1 ? "unlock" : "lock" }} align-middle"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
                                                                        </button>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>                                
                            </div>                            
                            <input type="hidden" id="from_lat" value="{{ $item->from_lat }}">
                            <input type="hidden" id="from_lng" value="{{ $item->from_lng }}">
                            <input type="hidden" id="to_lat" value="{{ $item->to_lat }}">
                            <input type="hidden" id="to_lng" value="{{ $item->to_lng }}">
                        @endforeach
                    </div>
                    <div class="tab-pane" id="icon-tab-2" role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            @if (auth()->user()->hasPermission(17))
                                <button class="btn btn-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#serviceSalesModal">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus align-middle"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    NUEVA VENTA
                                </button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th class="text-left">Descripción</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-center">Total</th>
                                        {{-- <th class="text-center">Vendedor</th> --}}
                                        <th class="text-center">Fecha de venta</th>
                                        <th class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reservation->sales as $sale)
                                        <tr>
                                            <td>{{ $sale->type->name }}</td>
                                            <td class="text-left">{{ $sale->description }}</td>
                                            <td class="text-center">{{ $sale->quantity }}</td>
                                            <td class="text-center">{{ number_format($sale->total,2) }}</td>
                                            {{-- <td class="text-center">{{ $sale->callCenterAgent->name ?? 'System' }}</td> --}}
                                            <td class="text-center">{{ $sale->created_at }}</td>
                                            <td class="text-center">
                                                @if (auth()->user()->hasPermission(15))
                                                    <a href="#" class="action-btn btn-delete" data-bs-toggle="modal" data-bs-target="#serviceSalesModal" onclick="getSale({{ $sale->id }})">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-2 align-middle"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                                    </a>
                                                @endif
                                                @if (auth()->user()->hasPermission(16))
                                                    <a href="#" class="action-btn btn-delete" onclick="deleteSale({{ $sale->id }})">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash align-middle"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach                                   
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="icon-tab-3" role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            {{-- @if ( $data['status'] != "CREDIT" ) --}}
                                @if (auth()->user()->hasPermission(14) )
                                    <button class="btn btn-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#servicePaymentsModal">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus align-middle"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        NUEVO PAGO
                                    </button>
                                @endif
                            {{-- @endif --}}
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Método</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Moneda</th>
                                        <th class="text-center">TC</th>
                                        <th class="text-start">Ref.</th>
                                        <th class="text-start">Categoria.</th>
                                        <th class="text-center">Fecha de pago</th>
                                        <th class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reservation->payments as $payment)
                                        <tr style="{{ $payment->category == "REFUND" ? 'background-color: #fbeced;' : '' }}">
                                            <td>{{ $payment->payment_method }}</td>
                                            <td>{{ $payment->description }}</td>
                                            <td class="text-end">{{ number_format($payment->total) }}</td>
                                            <td class="text-center">{{ $payment->currency }}</td>
                                            <td class="text-end">{{ number_format($payment->exchange_rate, 2) }}</td>
                                            <td class="text-start">{{ $payment->reference }}</td>
                                            <td class="text-start">{{ $payment->category }}</td>
                                            <td class="text-center">{{ $payment->created_at }}</td>
                                            <td class="text-center">
                                                @if (auth()->user()->hasPermission(15))
                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#servicePaymentsModal" onclick="getPayment({{ $payment->id }})">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-2 align-middle"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                                    </a>
                                                @endif
                                                @if (auth()->user()->hasPermission(16))
                                                    <a href="#" onclick="deletePayment({{ $payment->id }})">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash align-middle"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach                                   
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="icon-tab-4" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>SOLITADO</th>
                                        <th>Estatus</th>
                                        <th>Descripción</th>
                                        <th>Respuesta</th>
                                        <th class="text-center">Fecha de solicitud</th>
                                        <th class="text-center">Fecha de aplicación</th>
                                        <th class="text-center">comprobante de reembolso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reservation->refunds as $refund)
                                        <tr>
                                            <td>{{ isset($refund->user->name) ? $refund->user->name : 'SIN USUARIO QUE SOLICITO EL REEMBOLSO' }}</td>
                                            <td>
                                                <button class="btn btn-{{ auth()->user()->classStatusRefund($refund->status) }} btn-sm">{{ auth()->user()->statusRefund($refund->status) }}</button>
                                            </td>
                                            <td>{{ $refund->message_refund }}</td>
                                            <td>{{ $refund->response_message != NULL ? $refund->response_message : 'SIN COMENTARIO DE RESPUESTA' }}</td>
                                            <td class="text-center">{{ date("Y-m-d", strtotime($refund->created_at)) }}</td>
                                            <td class="text-center">
                                                @if ( $refund->status == "REFUND_NOT_APPLICABLE" )
                                                    {{ 'NO APLICA' }}
                                                @else
                                                    @if ( $refund->end_at != null )
                                                        {{ date("Y-m-d", strtotime($refund->end_at)) }}
                                                    @else
                                                        {{ 'SIN FECHA DE APLICACIÓN DE REEMBOLSO' }}
                                                    @endif                                                    
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ( $refund->status == "REFUND_NOT_APPLICABLE" )
                                                    {{ 'NO APLICA' }}
                                                @else
                                                    @if ( $refund->link_refund != null )
                                                        <a href="{{ $refund->link_refund }}" target="_black">click para ver</a>
                                                    @else
                                                        {{ 'SIN COMPROBANTE DE REEMBOLSO' }}
                                                    @endif                                                    
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach                                   
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="icon-tab-8" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-center">PICKUP</th>
                                        <th class="text-center">TIPO DE SERVICIO</th>
                                        <th class="text-center">PAX</th>
                                        <th class="text-center">ORIGEN</th>
                                        <th class="text-center">DESTINO</th>
                                        <th class="text-center">UNIDAD</th>
                                        <th class="text-center">CONDUCTOR</th>
                                        <th class="text-center">ESTATUS DE OPERACIÓN</th>
                                        <th class="text-center">HORA OPERACIÓN</th>
                                        <th class="text-center">COSTO OPERATIVO</th>
                                        <th class="text-center">ESTATUS DE SERVICIO</th>
                                        <th class="text-center">VEHÍCULO</th>
                                        <th class="text-center">ESTATUS DE RESERVACIÓN</th>
                                        <th class="text-center">PAGO</th>
                                        <th class="text-center">TOTAL</th>
                                        <th class="text-center">MÉTODOS DE PAGO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($operations ?? [] as $value)
                                        <tr>
                                            <td class="text-center">{{ date("Y/m/d H:i", strtotime($value->filtered_date)) }}</td>
                                            <td class="text-center">
                                                {{ $value->final_service_type }}
                                                @if ($value->final_service_type == "ARRIVAL")
                                                    <br><small>{{ $value->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP' }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $value->passengers }}</td>
                                            <td class="text-center">
                                                {{ auth()->user()->setFrom($value, "name") }}
                                                @if (!empty($value->flight_number))
                                                    <br><small>({{ $value->flight_number }})</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ auth()->user()->setTo($value, "name") }}</td>
                                            <td class="text-center">{{ auth()->user()->setOperationUnit($value) }}</td>
                                            <td class="text-center">{{ auth()->user()->setOperationDriver($value) }}</td>
                                            <td class="text-center"><?= auth()->user()->renderOperationStatus($value) ?></td>
                                            <td class="text-center"><?= auth()->user()->setOperationTime($value) ?></td>
                                            <td class="text-center"><?= auth()->user()->setOperatingCost($value) ?></td>
                                            <td class="text-center"><?= auth()->user()->renderServiceStatusOP($value) ?></td>
                                            <td class="text-center">{{ $value->service_type_name }}</td>
                                            <td class="text-center">{{ auth()->user()->statusBooking($value->reservation_status) }}</td>
                                            <td class="text-center">{{ auth()->user()->statusPayment($value->payment_status) }}</td>
                                            <td class="text-center">
                                                {{ number_format(($value->total_balance > 0 ? $value->total_balance : $value->total_sales), 2) }}
                                                {{ $value->currency }}
                                            </td>
                                            <td class="text-center">{{ $value->payment_type_name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if (auth()->user()->hasPermission(65))
                        <div class="tab-pane" id="icon-tab-5" role="tabpanel">
                            @if (auth()->user()->hasPermission(64))
                                <form id="upload-form" class="dropzone" action="/reservations/upload">
                                    @csrf
                                    <input type="hidden" name="folder" value="{{ $reservation->id }}">
                                </form>
                            @endif
                            @if (auth()->user()->hasPermission(65))
                                @if (auth()->user()->hasPermission(66))
                                    <div class="d-flex align-items-center gap-2 mb-2 mt-2">
                                        <button id="btn-toggle-sort" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-sort"></i> Reordenar imágenes
                                        </button>
                                        <div id="sort-controls" style="display:none;" class="d-flex align-items-center gap-2">
                                            <button id="btn-save-order" class="btn btn-sm btn-success">
                                                <i class="fas fa-save me-1"></i> Guardar orden
                                            </button>
                                            <span class="text-muted small">Arrastra las imágenes para reordenarlas</span>
                                        </div>
                                    </div>
                                @endif
                                <div class="image-listing" id="media-listing" data-can-reorder="{{ auth()->user()->hasPermission(66) ? 'true' : 'false' }}"></div>
                            @endif
                        </div>
                    @endif
                    <div class="tab-pane" id="icon-tab-6" role="tabpanel">
                        <div id="iframeOneContainer"></div>
                    </div>
                    <div class="tab-pane" id="icon-tab-7" role="tabpanel">
                        <div id="iframeTwoContainer"></div>
                    </div>
                    <div class="tab-pane" id="icon-tab-9" role="tabpanel">
                        <div id="iframeSOArrivalContainer"></div>
                    </div>
                    <div class="tab-pane" id="icon-tab-10" role="tabpanel">
                        <div id="iframeSODepartureContainer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <x-modals.service_map />
    {{-- MODAL PARA EDITAR LOS DATOS PRINCIPALES, DE LA RESERVA --}}
    <x-modals.edit_reservation_details :reservation=$reservation />

    {{-- MODAL PARA EDITAR UN SERVICIO, DE LA RESERVA --}}
    <x-modals.edit_reservation_service :reservation=$reservation />

    {{-- MODAL PARA AGREGAR UNA NUEVA VENTA, A LA RESERVA --}}
    <x-modals.new_sale_reservation>
        <x-slot name="reservation_id">{{ $reservation->id }}</x-slot>
    </x-modals.new_sale_reservation>

    {{-- MODAL PARA AGREGAR UN PAGO A LA RESERVA --}}
    <x-modals.new_payment_reservation>
        <x-slot name="reservation_id">{{ $reservation->id }}</x-slot>
        <x-slot name="currency">{{ $reservation->currency }}</x-slot>
        <x-slot name="type_site">{{ isset($request['bookingtracking']) ? $request['bookingtracking'] : $reservation->site->type_site }}</x-slot>
        <x-slot name="platform">{{ isset($request['trackingType']) ? $request['trackingType'] : ( $data['status'] == "PENDING" ? 'Bookign' : 'GENERAL' ) }}</x-slot>
    </x-modals.new_payment_reservation>

    <x-modals.new_follow_reservation>
        <x-slot name="reservation_id">{{ $reservation->id }}</x-slot>
    </x-modals.new_follow_reservation>
    <x-modals.reservations.confirmation :reservation=$reservation />
    <x-modals.reservations.confirmation :reservation=$reservation />
    <x-modals.reservations.edit_item_comment />
    <x-modals.reservations.payment_link_amount />
@endsection